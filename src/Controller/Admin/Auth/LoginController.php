<?php

namespace App\Controller\Admin\Auth;

use App\Entity\User;
use App\Util\SecurityUtil;
use App\Form\LoginFormType;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Annotation\CsrfProtection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class LoginController
 *
 * Login controller provides user login functionality
 * Note: Login uses custom authenticator (not Symfony security)
 *
 * @package App\Controller\Admin\Auth
 */
class LoginController extends AbstractController
{
    private LogManager $logManager;
    private AuthManager $authManager;
    private SecurityUtil $securityUtil;

    public function __construct(LogManager $logManager, AuthManager $authManager, SecurityUtil $securityUtil)
    {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->securityUtil = $securityUtil;
    }

    /**
     * Handle auth login page
     *
     * @param Request $request The request object
     *
     * @return Response The login page view or login redirect
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/login', methods: ['GET', 'POST'], name: 'auth_login')]
    public function login(Request $request): Response
    {
        // check if user is already logged in
        if ($this->authManager->isUserLogedin()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        // init default resources
        $user = new User();
        $errorMsg = null;

        // create register form
        $form = $this->createForm(LoginFormType::class, $user);
        $form->handleRequest($request);

        // check form if submited
        if ($form->isSubmitted() && $form->isValid()) {
            // get form data
            $username = $form->get('username')->getData();
            $password = $form->get('password')->getData();
            $remember = $form->get('remember')->getData();

            // get user data
            $userData = $this->authManager->getUserRepository(['username' => $username]);

            // check if user exist
            if ($userData != null) {
                // get user password form database
                $userPassword = $userData->getPassword();

                // check if password valid
                if ($this->securityUtil->verifyPassword($password, $userPassword)) {
                    $this->authManager->login($username, $userData->getToken(), $remember);
                } else {
                    // invalid password error
                    $this->logManager->log(
                        name: 'authenticator',
                        message: 'trying to login with: ' . $username . ':' . $password . ' password is wrong'
                    );
                    $errorMsg = 'Incorrect username or password.';
                }
            } else {
                // user not exist error
                $this->logManager->log(
                    name: 'authenticator',
                    message: 'trying to login with: ' . $username . ':' . $password . ' user not exist'
                );
                $errorMsg = 'Incorrect username or password.';
            }

            // redirect to dashboard (if login OK)
            if ($errorMsg == null && $this->authManager->isUserLogedin()) {
                return $this->redirectToRoute('admin_dashboard');
            }
        }

        // render login form view
        return $this->render('admin/auth/login.twig', [
            'isUsersEmpty' => $this->authManager->isRegisterPageAllowed(),
            'loginForm' => $form->createView(),
            'errorMsg' => $errorMsg
        ]);
    }
}
