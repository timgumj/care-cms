<?php
namespace App\Security;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Twig\Environment;
final class EmailCodeFormRenderer implements TwoFactorFormRendererInterface
{
    public function __construct(private Environment $twig) {}
    public function renderForm(Request $request, array $templateVars): Response { return new Response($this->twig->render('admin/verify.html.twig',$templateVars)); }
}
