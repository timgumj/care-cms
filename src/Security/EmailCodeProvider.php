<?php
namespace App\Security;
use App\Entity\AdminUser;
use Doctrine\DBAL\Connection;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\{TwoFactorProviderInterface,TwoFactorFormRendererInterface};
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
final class EmailCodeProvider implements TwoFactorProviderInterface
{
    public function __construct(private Connection $db, private RequestStack $requests, private MailerInterface $mailer,
        private EmailCodeFormRenderer $renderer,
        #[Autowire(service: 'limiter.email_code_send')] private RateLimiterFactory $sendLimiter,
        #[Autowire('%kernel.secret%')] private string $secret,
        #[Autowire('%kernel.environment%')] private string $environment,
        #[Autowire('%env(bool:EMAIL_2FA_ENABLED)%')] private bool $enabled,
        #[Autowire('%env(LOGIN_MAIL_FROM)%')] private string $sender,
        #[Autowire('%env(MAILER_DSN)%')] private string $dsn) {}
    public function beginAuthentication(AuthenticationContextInterface $context): bool {
        return $context->getUser() instanceof AdminUser && ($this->environment==='prod' || $this->enabled);
    }
    private function digest(AdminUser $user, string $nonce, string $code): string {
        return hash_hmac('sha256',$user->id.'|'.$user->password.'|'.$user->securityVersion.'|'.$nonce.'|'.$code,$this->secret);
    }
    public function prepareAuthentication(object $user): void {
        if(!$user instanceof AdminUser || !$user->id || !$user->getRoles())throw new \LogicException('Active account required.');
        $options=[];parse_str(parse_url($this->dsn,PHP_URL_QUERY)?:'',$options);
        $tls=str_starts_with($this->dsn,'smtps://') || (str_starts_with($this->dsn,'smtp://') && filter_var($options['require_tls']??false,FILTER_VALIDATE_BOOLEAN));
        if($this->environment==='prod' && (strlen($this->secret)<32 || !$tls || (isset($options['verify_peer'])&&!filter_var($options['verify_peer'],FILTER_VALIDATE_BOOLEAN))))
            throw new CustomUserMessageAuthenticationException('Secure login email delivery is not configured. Contact the site administrator.');
        if(!filter_var($this->sender,FILTER_VALIDATE_EMAIL) || !preg_match('~^(smtps?|sendmail)://~',$this->dsn))
            throw new CustomUserMessageAuthenticationException('Login email delivery is not configured. Contact the site administrator.');
        if(!$this->sendLimiter->create('user:'.$user->id)->consume()->isAccepted())
            throw new CustomUserMessageAuthenticationException('Too many login attempts. Please try again later.');
        $session=$this->requests->getSession();
        $nonce=bin2hex(random_bytes(32)); $session->set('_email_code_nonce',$nonce);
        $code=(string)random_int(10000000,99999999);
        $this->db->transactional(function()use($user,$nonce,$code){
            $this->db->delete('admin_email_challenge',['user_id'=>$user->id]);
            $this->db->insert('admin_email_challenge',['user_id'=>$user->id,'code_hash'=>$this->digest($user,$nonce,$code),'session_hash'=>hash('sha256',$nonce),'expires_at'=>time()+600,'attempts'=>0]);
        });
        try {
            $this->mailer->send((new Email())->from($this->sender)->to($user->email)->subject('Care Studio administration — login code / Anmeldecode')
                ->text("Your login code / Ihr Anmeldecode: ".$code."\n\nValid for 10 minutes, one use only. / 10 Minuten gültig, nur einmal verwendbar.\nNever share this code. / Geben Sie diesen Code niemals weiter.\nIf you did not try to sign in, contact your site administrator. / Falls Sie keine Anmeldung versucht haben, kontaktieren Sie Ihren Administrator."));
        } catch(\Throwable $e) {
            $this->db->delete('admin_email_challenge',['user_id'=>$user->id]);
            throw new CustomUserMessageAuthenticationException('Login email could not be delivered. Please try again later.');
        }
    }
    public function validateAuthenticationCode(object $user, string $authenticationCode): bool {
        if(!$user instanceof AdminUser || !$user->getRoles())return false;
        $nonce=$this->requests->getSession()->get('_email_code_nonce','');
        if(!is_string($nonce)||strlen($nonce)!==64)return false;
        $params=[$user->id,hash('sha256',$nonce),time()];
        // Atomic increment before comparison caps guesses, including malformed input.
        if($this->db->executeStatement('UPDATE admin_email_challenge SET attempts = attempts + 1 WHERE user_id = ? AND session_hash = ? AND expires_at > ? AND attempts < 5',$params)!==1)return false;
        $row=$this->db->fetchAssociative('SELECT code_hash FROM admin_email_challenge WHERE user_id = ? AND session_hash = ? AND expires_at > ?',$params);
        $code=trim($authenticationCode);
        if(!$row||!preg_match('/^[0-9]{8}$/D',$code)||!hash_equals($row['code_hash'],$this->digest($user,$nonce,$code)))return false;
        // Conditional delete prevents replay across concurrent submissions.
        $accepted=$this->db->executeStatement('DELETE FROM admin_email_challenge WHERE user_id = ? AND session_hash = ? AND code_hash = ? AND expires_at > ?',[$user->id,hash('sha256',$nonce),$row['code_hash'],time()])===1;
        if($accepted){$this->requests->getSession()->set('_email_verified_at',time());$this->requests->getSession()->remove('_email_code_nonce');$this->requests->getSession()->migrate(true);}
        return $accepted;
    }
    public function getFormRenderer(): TwoFactorFormRendererInterface { return $this->renderer; }
}
