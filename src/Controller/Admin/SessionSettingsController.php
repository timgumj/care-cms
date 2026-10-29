<?php
namespace App\Controller\Admin;

use App\Security\{AdminArea, SessionSettings};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class SessionSettingsController extends AbstractController
{
    #[Route(AdminArea::PATH.'/session-settings', name: 'admin_session_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, SessionSettings $settings): Response
    {
        $error = null;
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('session_settings', $request->request->get('_csrf_token'))) throw $this->createAccessDeniedException();
            $idle = filter_var($request->request->get('idle_minutes'), FILTER_VALIDATE_INT);
            $maximum = filter_var($request->request->get('maximum_hours'), FILTER_VALIDATE_INT);
            $values = ['idle_minutes'=>$idle, 'maximum_hours'=>$maximum];
            if (!$settings->valid($values)) {
                $error = 'Choose 5–120 idle minutes and 1–24 hours. The idle limit cannot exceed the total duration.';
            } else {
                try {
                    $settings->save($values);
                    $this->addFlash('session_settings_saved', 'Session settings saved.');
                    return $this->redirectToRoute('admin_session_settings', ['_locale'=>$request->getLocale()]);
                } catch (\RuntimeException $exception) {
                    $error = 'Settings could not be saved. Please check the private security directory permissions.';
                }
            }
        }
        return $this->render('admin/session_settings.html.twig', ['settings'=>$settings->read(), 'error'=>$error]);
    }
}
