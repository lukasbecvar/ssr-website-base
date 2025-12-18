<?php

namespace App\Controller\Public;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class HomeController
 *
 * Home controller provides main website homepage
 *
 * @package App\Controller\Public
*/
class HomeController extends AbstractController
{
    /**
     * Handle home page
     *
     * @return Response The home page view response
     */
    #[Route(path: '/', methods: ['GET'], name: 'public_home')]
    public function homePage(): Response
    {
        return $this->render('public/home.twig');
    }
}
