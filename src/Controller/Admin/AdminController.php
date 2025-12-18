<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AdminController
 *
 * Admin controller provides initialization of admin site
 * Controller redirects logged in users to dashboard page
 *
 * @package App\Controller\Admin
 */
class AdminController extends AbstractController
{
    /**
     * Initialize the admin site
     *
     * @return Response Redirect to dashboard page
     */
    #[Route('/admin', methods: ['GET'], name: 'admin_init')]
    public function admin(): Response
    {
        // redirect to dashboard page
        return $this->redirectToRoute('admin_dashboard');
    }
}
