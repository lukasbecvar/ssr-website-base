<?php

namespace App\Controller\Admin\Auth;

use App\Entity\User;
use App\Manager\AuthManager;
use App\Form\RegisterFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class RegisterController
 *
 * Register controller provides user register functionality
 * Note: Login uses custom authenticator (not Symfony security)
 * Note: This functionality is enabled only if users table is empty or admin users
 *
 * @package App\Controller\Admin\Auth
 */
class RegisterController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Handle user register page
     *
     * @param Request $request The request object
     *
     * @return Response The registration page view or registration redirect
     */
    #[Route('/register', methods: ['GET', 'POST'], name: 'auth_register')]
    public function register(Request $request): Response
    {
        // check if user table is empty or if user is admin
        if (!$this->authManager->isRegisterPageAllowed()) {
            return $this->redirectToRoute('auth_login');
        }

        // init default resources
        $errorMsg = null;
        $user = new User();

        // create register form
        $form = $this->createForm(RegisterFormType::class, $user);
        $form->handleRequest($request);

        // check if form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get form data
            $username = $form->get('username')->getData();
            $password = $form->get('password')->getData();
            $rePassword = $form->get('re-password')->getData();

            // check if username is already used
            if ($this->authManager->getUserRepository(['username' => $username]) != null) {
                $errorMsg = 'This username is already in use';
            } else {
                // check if passwords not match
                if ($password != $rePassword) {
                    $errorMsg = 'Your passwords dont match';
                } else {
                    // register new user
                    $this->authManager->registerNewUser($username, $password);
                    return $this->redirectToRoute('admin_dashboard');
                }
            }
        }

        // render registration form view
        return $this->render('admin/auth/register.twig', [
            'registrationForm' => $form->createView(),
            'errorMsg' => $errorMsg
        ]);
    }
}
