<?php

namespace App\Controller\Admin;

use Exception;
use App\Entity\User;
use App\Util\SecurityUtil;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Annotation\CsrfProtection;
use App\Form\PasswordChangeFormType;
use App\Form\UsernameChangeFormType;
use App\Form\ProfilePicChangeFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AccountSettingsController
 *
 * Account settings controller provides user account changes
 * Configurable properties: username, password & profile picture
 *
 * @package App\Controller\Admin
 */
class AccountSettingsController extends AbstractController
{
    private AuthManager $authManager;
    private SecurityUtil $securityUtil;
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AuthManager $authManager,
        SecurityUtil $securityUtil,
        ErrorManager $errorManager,
        EntityManagerInterface $entityManager
    ) {
        $this->authManager = $authManager;
        $this->securityUtil = $securityUtil;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Handle account settings table page
     *
     * @return Response The account settings table view
     */
    #[Route('/admin/account/settings', methods: ['GET'], name: 'admin_account_settings_table')]
    public function accountSettingsTable(): Response
    {
        // return account settings table view
        return $this->render('admin/account-settings.twig', [
            'profilePicChangeForm' => null,
            'usernameChangeForm' => null,
            'passwordChangeForm' => null,
            'errorMsg' => null
        ]);
    }

    /**
     * Handle profile picture update form
     *
     * @param Request $request The request object
     *
     * @return Response The picture change view
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/account/settings/pic', methods: ['GET', 'POST'], name: 'admin_account_settings_pic_change')]
    public function accountSettingsPicChange(Request $request): Response
    {
        // init default resources
        $errorMsg = null;
        $user = new User();

        // create picture update form
        $form = $this->createForm(ProfilePicChangeFormType::class, $user);
        $form->handleRequest($request);

        // check is form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get image data
            $image = $form->get('profile-pic')->getData();
            $extension = $image->getClientOriginalExtension();

            // check if file is image
            if ($extension == 'jpg' or $extension == 'jpeg' or $extension == 'png') {
                // get user repository
                $userRepo = $this->authManager->getUserRepository(['username' => $this->authManager->getUsername()]);

                // get image content
                $fileContents = file_get_contents($image);

                // encode image to base64
                $imageCode = base64_encode($fileContents);

                // update profile picture
                $userRepo->setProfilePic($imageCode);

                try {
                    // flush user data to database
                    $this->entityManager->flush();

                    // redirect back to account settings table
                    return $this->redirectToRoute('admin_account_settings_table');
                } catch (Exception $e) {
                    $this->errorManager->handleError(
                        msg: 'error to upload profile pic: ' . $e->getMessage(),
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
            } else {
                $errorMsg = 'please select image file';
            }
        }

        // render profile picture update form view
        return $this->render('admin/account-settings.twig', [
            'profilePicChangeForm' => $form->createView(),
            'usernameChangeForm' => null,
            'passwordChangeForm' => null,
            'errorMsg' => $errorMsg
        ]);
    }

    /**
     * Handle change username form
     *
     * @param Request $request The request object
     *
     * @return Response The username change view
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/account/settings/username', methods: ['GET', 'POST'], name: 'admin_account_settings_username_change')]
    public function accountSettingsUsernameChange(Request $request): Response
    {
        // init default resources
        $errorMsg = null;
        $user = new User();

        // create username form change
        $form = $this->createForm(UsernameChangeFormType::class, $user);
        $form->handleRequest($request);

        // check is form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get username
            $username = $form->get('username')->getData();

            // get user repository
            $userRepo = $this->authManager->getUserRepository(['username' => $this->authManager->getUsername()]);

            // update username
            $userRepo->setUsername($username);

            try {
                // flush user data to database
                $this->entityManager->flush();

                // redirect back to values table
                return $this->redirectToRoute('admin_account_settings_table');
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    msg: 'error to upload profile pic: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        // render username change form view
        return $this->render('admin/account-settings.twig', [
            'profilePicChangeForm' => null,
            'passwordChangeForm' => null,
            'usernameChangeForm' => $form,
            'errorMsg' => $errorMsg
        ]);
    }

    /**
     * Handle change password form
     *
     * @param Request $request The request object
     *
     * @return Response The password change view
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/account/settings/password', methods: ['GET', 'POST'], name: 'admin_account_settings_password_change')]
    public function accountSettingsPasswordChange(Request $request): Response
    {
        // init default resources
        $errorMsg = null;
        $user = new User();

        // create username form change
        $form = $this->createForm(PasswordChangeFormType::class, $user);
        $form->handleRequest($request);

        // check is form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get passwords
            $password = $form->get('password')->getData();
            $rePassword = $form->get('repassword')->getData();

            // get user repository
            $userRepo = $this->authManager->getUserRepository(['username' => $this->authManager->getUsername()]);

            // check if passwords match
            if ($password != $rePassword) {
                $errorMsg = 'Your passwords is not match!';
            } else {
                // hash password (Argon2)
                $passwordHash = $this->securityUtil->generateHash($password);

                // update password
                $userRepo->setPassword($passwordHash);

                try {
                    // flush user data to database
                    $this->entityManager->flush();

                    // redirect back to account settings table
                    return $this->redirectToRoute('admin_account_settings_table');
                } catch (Exception $e) {
                    $this->errorManager->handleError(
                        msg: 'error to upload profile pic: ' . $e->getMessage(),
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
            }
        }

        // render password change form view
        return $this->render('admin/account-settings.twig', [
            'profilePicChangeForm' => null,
            'usernameChangeForm' => null,
            'passwordChangeForm' => $form,
            'errorMsg' => $errorMsg
        ]);
    }

    /**
     * Handle reset authentication token (hard logout from all devices)
     *
     * @return Response The account settings table view
     */
    #[Route('/admin/account/settings/reset-token', methods: ['POST'], name: 'admin_account_settings_reset_token')]
    public function accountSettingsResetToken(): Response
    {
        // get current username
        $username = $this->authManager->getUsername();

        // regenerate user token
        $resetState = $this->authManager->regenerateUserToken($username);

        // check if reset is success
        if ($resetState['status']) {
            // force logout current user (session will be invalid)
            $this->authManager->logout();

            // add success flash message
            $this->addFlash('success', 'Authentication token has been reset.');

            // redirect to login page with token reset indicator
            return $this->redirectToRoute('auth_login');
        } else {
            // render error message
            return $this->render('admin/account-settings.twig', [
                'profilePicChangeForm' => null,
                'usernameChangeForm' => null,
                'passwordChangeForm' => null,
                'errorMsg' => 'Failed to reset authentication token: ' . $resetState['message']
            ]);
        }
    }
}
