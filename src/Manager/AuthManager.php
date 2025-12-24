<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\User;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Util\VisitorInfoUtil;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManager
 *
 * AuthManager provides login, logout & authorization functionality
 * Note: Login uses custom authenticator (not Symfony security)
 *
 * @package App\Manager
 */
class AuthManager
{
    private LogManager $logManager;
    private CookieUtil $cookieUtil;
    private SessionUtil $sessionUtil;
    private ErrorManager $errorManager;
    private SecurityUtil $securityUtil;
    private UserRepository $userRepository;
    private VisitorManager $visitorManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LogManager $logManager,
        CookieUtil $cookieUtil,
        SessionUtil $sessionUtil,
        ErrorManager $errorManager,
        SecurityUtil $securityUtil,
        UserRepository $userRepository,
        VisitorManager $visitorManager,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager
    ) {
        $this->logManager = $logManager;
        $this->cookieUtil = $cookieUtil;
        $this->sessionUtil = $sessionUtil;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->visitorManager = $visitorManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isUserLogedin(): bool
    {
        // check if session exist
        if (!$this->sessionUtil->checkSession('login-token')) {
            return false;
        }

        // get login token from session
        $loginToken = $this->sessionUtil->getSessionValue('login-token');

        // check if token exist in database
        if ($this->userRepository->getUserByToken($loginToken) != null) {
            return true;
        } else {
            // destroy session if token not found in users database
            $this->sessionUtil->destroySession();
        }

        return false;
    }

    /**
     * Login user into the system
     *
     * @param string $username The username of the user to log in
     * @param string $userToken The token of the user to log in
     * @param bool $remember Whether to remember the user's login
     *
     * @return void
     */
    public function login(string $username, string $userToken, bool $remember): void
    {
        // check if user is not logged in
        if (!$this->isUserLogedin()) {
            // check if user token is valid
            if (!empty($userToken)) {
                // regenerate session id before persisting login data
                $this->sessionUtil->regenerateSession();

                // set login session
                $this->sessionUtil->setSession('login-token', $userToken);

                // check if remember-me set (auto login cookie)
                if ($remember) {
                    if (!isset($_COOKIE['login-token-cookie'])) {
                        $this->cookieUtil->set(
                            name: 'login-token-cookie',
                            value: $userToken,
                            expiration: time() + (60 * 60 * 24 * 7 * 365)
                        );
                    }
                }

                // update user data (last login time, ip address, visitor id)
                $this->updateUserData();

                // log auth event
                $this->logManager->log('authenticator', 'user: ' . $username . ' logged in');
            } else {
                $this->errorManager->handleError(
                    msg: 'error you are is already logged in: ' . $userToken,
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }
    }

    /**
     * Logout user with destroy user session and login cookie
     *
     * @return void
     */
    public function logout(): void
    {
        // check if user logged in
        if ($this->isUserLogedin()) {
            // get current user
            $user = $this->userRepository->getUserByToken($this->getUserToken());

            // log logout event
            $this->logManager->log('authenticator', 'user: ' . $user->getUsername() . ' logout');

            // unset login cookie
            $this->cookieUtil->unset('login-token-cookie');

            // unset login session
            $this->sessionUtil->destroySession();
        }
    }

    /**
     * Update user data
     *
     * @return void
     */
    public function updateUserData(): void
    {
        // get current visitor ip address
        $ipAddress = $this->visitorInfoUtil->getIP();

        // get user data
        $user = $this->getUserRepository(['token' => $this->getUserToken()]);

        // get visitor repository
        $visitor = $this->visitorManager->getVisitorRepository($ipAddress);

        // check if user repo found
        if ($user != null) {
            // update last login time
            $user->setLastLoginTime(new DateTime());

            // update visitor
            $user->setVisitor($visitor);

            try {
                // flush updated user data to database
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    msg: 'flush error: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }
    }

    /**
     * Register new user
     *
     * @param string $username The username for the new user
     * @param string $password The password for the new user
     *
     * @return void
     */
    public function registerNewUser(string $username, string $password): void
    {
        // init user enity
        $user = new User();

        // get user ip
        $ipAddress = $this->visitorInfoUtil->getIP();

        // generate token
        $token = ByteString::fromRandom(32)->toString();

        // get visitor repository
        $visitor = $this->visitorManager->getVisitorRepository($ipAddress);

        // password hash
        $hashedPassword = $this->securityUtil->generateHash($password);

        // default profile pics base64
        $imageBase64 = 'non-pic';

        // set user entity data
        $user->setUsername($username)
            ->setPassword($hashedPassword)
            ->setRole('Owner')
            ->setIpAddress($ipAddress)
            ->setToken($token)
            ->setRegistedTime(new DateTime())
            ->setLastLoginTime(null)
            ->setProfilePic($imageBase64)
            ->setVisitor($visitor);

        try {
            // insert new user to database
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // log registration event
            $this->logManager->log('authenticator', 'registration new user: ' . $username . ' registred');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to register new user: ' . $e->getMessage(),
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // set user token (login-token session)
        if (!$this->isUserLogedin()) {
            $this->login($username, $user->getToken(), false);
        }
    }

    /**
     * Get login token from current user session
     *
     * @return string|null The login token or null if not found or invalid
     */
    public function getUserToken(): ?string
    {
        // check if session exist
        if (!$this->sessionUtil->checkSession('login-token')) {
            return null;
        }

        // get login token from session
        $loginToken = $this->sessionUtil->getSessionValue('login-token');

        // check if token exist in database
        if ($this->userRepository->getUserByToken($loginToken) != null) {
            return $loginToken;
        }

        return null;
    }

    /**
     * Get username associated with the given token
     *
     * @param string $token The user token to retrieve the username for
     *
     * @return string The username or null if not found
     */
    public function getUsername(string $token = 'self'): string
    {
        // get token
        if ($token == 'self') {
            $token = $this->getUserToken();
        }

        // user repository
        $user = $this->userRepository->getUserByToken($token);

        // check if user repo found
        if ($user != null) {
            return $user->getUsername();
        }

        return 'Unknown';
    }

    /**
     * Get role associated with the given token
     *
     * @param string $token The user token to retrieve the role for
     *
     * @return string|null The user role or null if not found
     */
    public function getUserRole(string $token = 'self'): ?string
    {
        // get token
        if ($token == 'self') {
            $token = $this->getUserToken();
        }

        // user repository
        $user = $this->userRepository->getUserByToken($token);

        // check if user repo found
        if ($user != null) {
            return $user->getRole();
        }

        return null;
    }

    /**
     * Get profile picture URL associated with given token
     *
     * @param string $token The user token
     *
     * @return string|null The profile picture in base64 or null if not found
     */
    public function getUserProfilePic(string $token = 'self'): ?string
    {
        // get token
        if ($token == 'self') {
            $token = $this->getUserToken();
        }

        // user repository
        $user = $this->userRepository->getUserByToken($token);

        // check if user repo found
        if ($user != null) {
            $pic = $user->getProfilePic();

            return $pic;
        }

        return null;
    }

    /**
     * Check if user repository is empty
     *
     * @return bool True if the user repository is empty, false otherwise
     */
    public function isUsersEmpty(): bool
    {
        // get users count
        $count = $this->userRepository
            ->createQueryBuilder('p')->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        // check if count is zero
        if ($count == 0) {
            return true;
        }

        return false;
    }

    /**
     * Get user entity from repository based on provided criteria
     *
     * @param array<mixed> $array The criteria to search
     *
     * @return object|null The user entity or null if not found
     */
    public function getUserRepository(array $array): ?object
    {
        // try to find user in database
        try {
            return $this->userRepository->findOneBy($array);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'find error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check if user associated with current session is an administrator
     *
     * @return bool True if the user is an administrator, false otherwise
     */
    public function isAdmin(): bool
    {
        // get self user token
        $token = $this->getUserToken();

        // check if token found
        if ($token == null) {
            return false;
        }

        // get user role
        $role = $this->getUserRole($token);

        // check if user role is admin
        if ($role == 'Owner' || $role == 'Admin') {
            return true;
        }

        return false;
    }

    /**
     * Check if registration page is allowed based on the current system state
     *
     * @return bool True if the registration page is allowed, false otherwise
     */
    public function isRegisterPageAllowed(): bool
    {
        if ($this->isUsersEmpty() or ($this->isUserLogedin() && $this->isAdmin())) {
            return true;
        }
        return false;
    }

    /**
     * Generate a unique token for a user
     *
     * @param int $length The length of the generated token
     *
     * @return string The generated user token
     */
    public function generateUserToken(int $length = 32): string
    {
        // generate user token
        $token = ByteString::fromRandom($length)->toString();

        // check if user token is not already taken
        if ($this->userRepository->getUserByToken($token) != null) {
            $this->generateUserToken();
        }

        return $token;
    }

    /**
     * Regenerate auth tokens for all users in the database
     *
     * This method regenerates tokens for all users in the database, ensuring uniqueness for each token
     *
     * @return array<bool|null|string> Regenerate status and message
     */
    public function regenerateUsersTokens(): array
    {
        $state = [
            'status' => true,
            'message' => null
        ];

        // get all users in database
        $users = $this->userRepository->findAll();

        // regenerate all users tokens
        foreach ($users as $user) {
            // regenerate new token
            $newToken = $this->generateUserToken();

            // set new token
            $user->setToken($newToken);
        }

        try {
            // flush data to database
            $this->entityManager->flush();
        } catch (Exception $e) {
            $state = [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

        return $state;
    }

    /**
     * Regenerate auth token for specific user
     *
     * This method regenerates token for a specific user, forcing logout from all devices
     *
     * @param string $username The username to regenerate token for
     *
     * @return array<bool|null|string> Regenerate status and message
     */
    public function regenerateUserToken(string $username): array
    {
        $state = [
            'status' => true,
            'message' => null
        ];

        // get user by username
        $user = $this->userRepository->findOneBy(['username' => $username]);

        // check if user exists
        if ($user === null) {
            return [
                'status' => false,
                'message' => 'User not found'
            ];
        }

        // generate new token
        $newToken = $this->generateUserToken();

        // set new token
        $user->setToken($newToken);

        try {
            // flush data to database
            $this->entityManager->flush();
        } catch (Exception $e) {
            $state = [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

        return $state;
    }

    /**
     * Get list of all online users
     *
     * @return array<User> The online users list
     */
    public function getOnlineUsersList(): array
    {
        // get online visitor IDs
        $onlineVisitorIds = $this->visitorManager->getOnlineVisitorIDs();

        // return empty array if no online visitors
        if (empty($onlineVisitorIds)) {
            return [];
        }

        // get users associated with online visitor IDs
        return $this->userRepository->findBy(['visitor' => $onlineVisitorIds]);
    }
}
