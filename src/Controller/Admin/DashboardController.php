<?php
namespace App\Controller\Admin;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
#[AdminDashboard(routePath: \App\Security\AdminArea::PATH, routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $em,private Connection $db,private \App\Service\StarterPacks $packs,private \App\Service\FontLibrary $fonts) {}
    public function index(): Response {
        $repository=$this->em->getRepository(Page::class);
        return $this->render('admin/dashboard.html.twig',[
            'active_starter'=>$this->packs->active(),'font_count'=>count($this->fonts->all()),
            'page_count'=>$repository->createQueryBuilder('p')->select('COUNT(p.id)')->where('p.deletedAt IS NULL AND p.kind != :post')->setParameter('post','Beitrag')->getQuery()->getSingleScalarResult(),
            'post_count'=>$repository->count(['kind'=>'Beitrag','deletedAt'=>null]),
            'draft_count'=>$repository->count(['published'=>false,'deletedAt'=>null]),
            'trash_count'=>$repository->createQueryBuilder('p')->select('COUNT(p.id)')->where('p.deletedAt IS NOT NULL')->getQuery()->getSingleScalarResult(),
            'next_bookings'=>$this->isGranted('ROLE_ADMIN')?$this->db->fetchAllAssociative("SELECT starts_at,appointment_type FROM appointment WHERE status='confirmed' AND starts_at>? ORDER BY starts_at LIMIT 4",[time()]):[],
            'booking_count'=>$this->isGranted('ROLE_ADMIN')?$this->db->fetchOne("SELECT COUNT(*) FROM appointment WHERE status='confirmed' AND starts_at>?",[time()]):null,
            'total_pages'=>$repository->count(['deletedAt'=>null]),
            'german_pages'=>$repository->count(['locale'=>'de-AT','deletedAt'=>null]),
            'english_pages'=>$repository->count(['locale'=>'en-AT','deletedAt'=>null]),
            'published_pages'=>$repository->count(['published'=>true,'deletedAt'=>null]),
            'recent_pages'=>$repository->findBy(['deletedAt'=>null],['updatedAt'=>'DESC','path'=>'ASC'],5),
        ]);
    }
    public function configureDashboard(): Dashboard { return Dashboard::new()->setTitle('Care Studio')->setTranslationDomain('messages')->setLocales(['de'=>'Deutsch','en'=>'English'])->setDefaultColorScheme('light')->setTheme(Theme::new()->primaryColor('#111111','#ededed')->grays('neutral')->radius('none')); }
    public function configureMenuItems(): iterable {
        yield MenuItem::linkToDashboard('Übersicht','internal:home');
        yield MenuItem::linkTo(PageCrudController::class, 'Pages', 'internal:file-lines');
        yield MenuItem::linkTo(PostCrudController::class, 'Articles', 'internal:file-lines');
        yield MenuItem::linkToRoute('Calendar / Bookings','internal:detail','admin_calendar')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Media library','internal:desktop','admin_media');
        yield MenuItem::linkToRoute('Trash','internal:delete','admin_trash');
        yield MenuItem::linkToRoute('Announcements','internal:file-lines','admin_announcements')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Starter packs','internal:folder-open','admin_starters')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Appearance','internal:desktop','admin_appearance')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Fonts','internal:file-lines','admin_fonts')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Menus','internal:menu-bars','admin_menus')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Session settings', 'internal:lock', 'admin_session_settings')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Users','internal:user','admin_users')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Cache','internal:gear','admin_cache')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToUrl('Website öffnen','internal:desktop','/')->setLinkTarget('_blank')->setLinkRel('noopener noreferrer');
        yield MenuItem::linkToLogout('Abmelden','internal:sign-out');
    }
}


