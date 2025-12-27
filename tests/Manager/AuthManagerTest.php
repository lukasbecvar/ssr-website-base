<?php

namespace App\Tests\Manager;

use DateTime;
use Exception;
use App\Entity\User;
use RuntimeException;
use App\Entity\Visitor;
use Doctrine\ORM\Query;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use Doctrine\ORM\QueryBuilder;
use App\Manager\VisitorManager;
use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManagerTest
 *
 * Test cases for AuthManager
 *
 * @package App\Tests\Manager
 */
class AuthManagerTest extends TestCase
{
    private AuthManager $authManager;
    private LogManager & MockObject $logManager;
    private CookieUtil & MockObject $cookieUtil;
    private SessionUtil & MockObject $sessionUtil;
    private ErrorManager & MockObject $errorManager;
    private SecurityUtil & MockObject $securityUtil;
    private UserRepository & MockObject $userRepository;
    private VisitorManager & MockObject $visitorManager;
    private VisitorInfoUtil & MockObject $visitorInfoUtil;
    private EntityManagerInterface & MockObject $entityManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->logManager = $this->createMock(LogManager::class);
        $this->cookieUtil = $this->createMock(CookieUtil::class);
        $this->sessionUtil = $this->createMock(SessionUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->visitorManager = $this->createMock(VisitorManager::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // init auth manager instance
        $this->authManager = new AuthManager(
            $this->logManager,
            $this->cookieUtil,
            $this->sessionUtil,
            $this->errorManager,
            $this->securityUtil,
            $this->userRepository,
            $this->visitorManager,
            $this->visitorInfoUtil,
            $this->entityManager
        );
    }

    /**
     * Test isUserLogedin when session does not exist
     *
     * @return void
     */
    public function testIsUserLogedinNoSession(): void
    {
        // mock session check
        $this->sessionUtil->expects($this->once())->method('checkSession')->with('login-token')->willReturn(false);

        // call tested method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test isUserLogedin when user not found in DB
     *
     * @return void
     */
    public function testIsUserLogedinUserNotFound(): void
    {
        // mock invalid user token
        $this->sessionUtil->expects($this->once())->method('checkSession')->with('login-token')->willReturn(true);
        $this->sessionUtil->expects($this->once())->method('getSessionValue')->with('login-token')->willReturn('invalid_token');
        $this->userRepository->expects($this->once())->method('getUserByToken')->with('invalid_token')->willReturn(null);

        // expect session destroy
        $this->sessionUtil->expects($this->once())->method('destroySession');

        // call tested method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test isUserLogedin with success result
     *
     * @return void
     */
    public function testIsUserLogedinSuccess(): void
    {
        // mock valid user token
        $this->sessionUtil->expects($this->once())->method('checkSession')->with('login-token')->willReturn(true);
        $this->sessionUtil->expects($this->once())->method('getSessionValue')->with('login-token')->willReturn('valid_token');
        $this->userRepository->expects($this->once())->method('getUserByToken')->with('valid_token')->willReturn(new User());

        // call tested method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test login when user already logged in
     *
     * @return void
     */
    public function testLoginAlreadyLoggedIn(): void
    {
        // simulate logged in
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('token');
        $this->userRepository->method('getUserByToken')->willReturn(new User());

        // should not call login logic
        $this->sessionUtil->expects($this->never())->method('setSession');

        // call tested method
        $this->authManager->login('user', 'token', false);
    }

    /**
     * Test login with empty token
     *
     * @return void
     */
    public function testLoginEmptyToken(): void
    {
        // simulate not logged in
        $this->sessionUtil->method('checkSession')->willReturn(false);

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error you are is already logged in'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->authManager->login('user', '', false);
    }

    /**
     * Test login success without remember me
     *
     * @return void
     */
    public function testLoginSuccessNoRemember(): void
    {
        // simulate not logged in
        $this->sessionUtil->method('checkSession')->willReturn(false);

        // expect session regenerate
        $this->sessionUtil->expects($this->once())->method('regenerateSession');
        $this->sessionUtil->expects($this->once())->method('setSession');

        // mock updateUserData dependencies
        $this->visitorInfoUtil->method('getIP')->willReturn('127.0.0.1');
        $this->userRepository->method('getUserByToken')->willReturn(new User());
        $this->userRepository->method('findOneBy')->willReturn(new User());
        $this->visitorManager->method('getVisitorRepository')->willReturn(new Visitor());
        $this->logManager->expects($this->once())->method('log');
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->authManager->login('user', 'token123', false);
    }

    /**
     * Test login success with remember me
     *
     * @return void
     */
    public function testLoginSuccessRemember(): void
    {
        // simulate not logged in
        $this->sessionUtil->method('checkSession')->willReturn(false);
        $this->sessionUtil->expects($this->once())->method('regenerateSession');
        $this->sessionUtil->expects($this->once())->method('setSession');

        // mock updateUserData dependencies
        $this->visitorInfoUtil->method('getIP')->willReturn('127.0.0.1');
        $this->userRepository->method('getUserByToken')->willReturn(new User());
        $this->userRepository->method('findOneBy')->willReturn(new User());
        $this->visitorManager->method('getVisitorRepository')->willReturn(new Visitor());
        $this->entityManager->expects($this->once())->method('flush');
        $this->logManager->expects($this->once())->method('log');

        // expect cookie set (remember me enabled)
        $this->cookieUtil->expects($this->once())->method('set')->with('login-token-cookie', 'token123');

        // call tested method
        $this->authManager->login('user', 'token123', true);
    }

    /**
     * Test login success with remember me but cookie already exists
     *
     * @return void
     */
    public function testLoginSuccessRememberCookieExists(): void
    {
        // simulate not logged in
        $this->sessionUtil->method('checkSession')->willReturn(false);
        $this->visitorInfoUtil->method('getIP')->willReturn('127.0.0.1');
        $this->userRepository->method('getUserByToken')->willReturn(new User());
        $this->userRepository->method('findOneBy')->willReturn(new User());
        $this->visitorManager->method('getVisitorRepository')->willReturn(new Visitor());

        // simulate cookie exists
        $_COOKIE['login-token-cookie'] = 'existing_token';

        // expect set() NEVER called
        $this->cookieUtil->expects($this->never())->method('set');

        // call tested method
        $this->authManager->login('user', 'token123', true);

        // cleanup
        unset($_COOKIE['login-token-cookie']);
    }

    /**
     * Test logout process
     *
     * @return void
     */
    public function testLogout(): void
    {
        // mock user entity
        $user = new User();
        $user->setUsername('testuser');

        // simulate logged in
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('token');
        $this->userRepository->method('getUserByToken')->willReturn($user);

        // expect action log
        $this->logManager->expects($this->once())->method('log')->with('authenticator', 'user: testuser logout');

        // expect cookie unset and session destroy
        $this->cookieUtil->expects($this->once())->method('unset');
        $this->sessionUtil->expects($this->once())->method('destroySession');

        // call tested method
        $this->authManager->logout();
    }

    /**
     * Test updateUserData success
     *
     * @return void
     */
    public function testUpdateUserDataSuccess(): void
    {
        // mock user entity
        $user = new User();
        $visitor = new Visitor();

        // mock IP address
        $this->visitorInfoUtil->method('getIP')->willReturn('1.2.3.4');

        // mock getUserRepository logic (used internally by updateUserData)
        $this->userRepository->expects($this->once())->method('findOneBy')->willReturn($user);

        // mock getUserToken (called by updateUserData)
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('test_token');
        $this->userRepository->method('getUserByToken')->willReturn($user);

        // mock visitor entity get
        $this->visitorManager->expects($this->once())->method('getVisitorRepository')->with('1.2.3.4')->willReturn($visitor);

        // expect flush new data
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->authManager->updateUserData();

        // assert visitor entity is set
        $this->assertSame($visitor, $user->getVisitor());
        $this->assertInstanceOf(DateTime::class, $user->getLastLoginTime());
    }

    /**
     * Test updateUserData flush error
     *
     * @return void
     */
    public function testUpdateUserDataFlushError(): void
    {
        $this->visitorInfoUtil->method('getIP')->willReturn('1.2.3.4');
        $this->userRepository->method('findOneBy')->willReturn(new User());

        // mock getUserToken (called by updateUserData)
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('test_token');
        $this->userRepository->method('getUserByToken')->willReturn(new User());

        // mock visitor entity get
        $this->visitorManager->method('getVisitorRepository')->willReturn(new Visitor());

        // simulate flush error
        $this->entityManager->expects($this->once())->method('flush')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('flush error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->authManager->updateUserData();
    }

    /**
     * Test registerNewUser success
     *
     * @return void
     */
    public function testRegisterNewUserSuccess(): void
    {
        $this->visitorInfoUtil->method('getIP')->willReturn('1.2.3.4');
        $this->visitorManager->method('getVisitorRepository')->willReturn(new Visitor());
        $this->securityUtil->method('generateHash')->willReturn('hashed_pass');
        $this->userRepository->method('getUserByToken')->willReturn(null);

        // expect persist and flush new user to database
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->entityManager->expects($this->exactly(2))->method('flush');

        // expect log registration and login events
        $this->logManager->expects($this->exactly(2))->method('log');

        // mock login dependencies called after registration
        $this->sessionUtil->method('checkSession')->willReturn(false);
        $this->sessionUtil->expects($this->once())->method('regenerateSession');
        $this->sessionUtil->expects($this->once())->method('setSession');
        $this->visitorInfoUtil->method('getIP')->willReturn('1.2.3.4');
        $this->userRepository->method('findOneBy')->willReturn(new User());
        $this->visitorManager->method('getVisitorRepository')->willReturn(new Visitor());

        // call tested method
        $this->authManager->registerNewUser('newuser', 'password');
    }

    /**
     * Test registerNewUser exception (persist error)
     *
     * @return void
     */
    public function testRegisterNewUserException(): void
    {
        $this->visitorInfoUtil->method('getIP')->willReturn('1.2.3.4');
        $this->visitorManager->method('getVisitorRepository')->willReturn(new Visitor());
        $this->securityUtil->method('generateHash')->willReturn('hashed_pass');
        $this->userRepository->method('getUserByToken')->willReturn(null);

        // simulate persist error
        $this->entityManager->expects($this->once())->method('persist')->willThrowException(new Exception('Fail to persist'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to register new user'),
            $this->equalTo(Response::HTTP_BAD_REQUEST)
        );

        // call tested method
        $this->authManager->registerNewUser('user', 'pass');
    }

    /**
     * Test getUsername
     *
     * @return void
     */
    public function testGetUsername(): void
    {
        // mock user entity
        $user = new User();
        $user->setUsername('alice');

        // mock session check
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('current_token');

        // mock getUserByToken
        $this->userRepository->method('getUserByToken')->willReturnMap([
            ['valid_token', $user],
            ['current_token', $user],
            ['invalid_token', null]
        ]);

        // call tested method and assert result
        $this->assertEquals('alice', $this->authManager->getUsername('valid_token'));
        $this->assertEquals('alice', $this->authManager->getUsername('self'));
        $this->assertEquals('Unknown', $this->authManager->getUsername('invalid_token'));
    }

    /**
     * Test getUserRole
     *
     * @return void
     */
    public function testGetUserRole(): void
    {
        // mock user entity
        $user = new User();
        $user->setRole('Admin');

        // mock session check
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('current_token');

        // mock getUserByToken
        $this->userRepository->method('getUserByToken')->willReturnMap([
            ['valid_token', $user],
            ['current_token', $user],
            ['invalid_token', null]
        ]);

        // call tested method and assert result
        $this->assertEquals('Admin', $this->authManager->getUserRole('valid_token'));
        $this->assertEquals('Admin', $this->authManager->getUserRole('self'));
        $this->assertNull($this->authManager->getUserRole('invalid_token'));
    }

    /**
     * Test getUserProfilePic
     *
     * @return void
     */
    public function testGetUserProfilePic(): void
    {
        // mock user entity
        $user = new User();
        $user->setProfilePic('pic_data');

        // mock session check
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('current_token');

        // mock getUserByToken
        $this->userRepository->method('getUserByToken')->willReturnMap([
            ['valid_token', $user],
            ['current_token', $user],
            ['invalid_token', null]
        ]);

        // call tested method and assert result
        $this->assertEquals('pic_data', $this->authManager->getUserProfilePic('valid_token'));
        $this->assertEquals('pic_data', $this->authManager->getUserProfilePic('self'));
        $this->assertNull($this->authManager->getUserProfilePic('invalid_token'));
    }

    /**
     * Test isUsersEmpty when users empty
     *
     * @return void
     */
    public function testIsUsersEmptyWhenUsersEmpty(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // mock query builder
        $this->userRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // case empty check
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn(0);
        $this->assertTrue($this->authManager->isUsersEmpty());
    }

    /**
     * Test getUserRepository (by criteria)
     *
     * @return void
     */
    public function testGetUserRepository(): void
    {
        // mock user entity
        $user = new User();
        $this->userRepository->expects($this->once())->method('findOneBy')->with(['id' => 1])->willReturn($user);

        // call tested method
        $result = $this->authManager->getUserRepository(['id' => 1]);

        // assert result
        $this->assertSame($user, $result);
    }

    /**
     * Test getUserRepository error handling (handleError should be called and terminate)
     *
     * @return void
     */
    public function testGetUserRepositoryErrorHandling(): void
    {
        // mock findOneBy error
        $this->userRepository->method('findOneBy')->willThrowException(new Exception('DB Fail'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('find error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        )->willThrowException(new RuntimeException('Expected handleError to terminate'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected handleError to terminate');

        // call tested method
        $this->authManager->getUserRepository(['id' => 1]);
    }

    /**
     * Test isAdmin
     *
     * @return void
     */
    public function testIsAdmin(): void
    {
        // setup for getUserToken (called multiple times by isAdmin)
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('any_token');

        // mock user entities
        $adminUser = new User();
        $adminUser->setRole('Admin');
        $ownerUser = new User();
        $ownerUser->setRole('Owner');
        $normalUser = new User();
        $normalUser->setRole('User');

        $this->userRepository->method('getUserByToken')->willReturnOnConsecutiveCalls(
            $adminUser,    // Call 1: isUserLogedin() -> getUserByToken() for first isAdmin()
            $adminUser,    // Call 2: getUserRole() -> getUserByToken() for first isAdmin()
            $ownerUser,    // Call 3: isUserLogedin() -> getUserByToken() for second isAdmin()
            $ownerUser,    // Call 4: getUserRole() -> getUserByToken() for second isAdmin()
            $normalUser,   // Call 5: isUserLogedin() -> getUserByToken() for third isAdmin()
            $normalUser,   // Call 6: getUserRole() -> getUserByToken() for third isAdmin()
            null,          // Call 7: isUserLogedin() -> getUserByToken() after reset (to make getUserToken return null)
            null           // Call 8: (redundant but safe to have enough returns for sequential calls)
        );

        // test admin
        $this->assertTrue($this->authManager->isAdmin());

        // test owner
        $this->assertTrue($this->authManager->isAdmin());

        // test normal user
        $this->assertFalse($this->authManager->isAdmin());
    }

    /**
     * Test isRegisterPageAllowed when users empty
     *
     * @return void
     */
    public function testIsRegisterPageAllowedUsersEmpty(): void
    {
        // simulate users empty
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $this->userRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(0);

        // call tested method
        $result = $this->authManager->isRegisterPageAllowed();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test isRegisterPageAllowed when not empty users, not logged in
     *
     * @return void
     */
    public function testIsRegisterPageAllowedNotLoggedIn(): void
    {
        // simulate user not logged in and users not empty
        $this->sessionUtil->method('checkSession')->willReturn(false);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $this->userRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(1);

        // call tested method
        $result = $this->authManager->isRegisterPageAllowed();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test isRegisterPageAllowed when not empty users, logged in as Admin
     *
     * @return void
     */
    public function testIsRegisterPageAllowedLoggedInAsAdmin(): void
    {
        // simulate admin user session
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('admin_token');
        $adminUser = new User();
        $adminUser->setRole('Admin');

        // Mock getUserByToken for isUserLogedin
        $this->userRepository->method('getUserByToken')->willReturnOnConsecutiveCalls(
            $adminUser, // for isUserLogedin
            $adminUser, // for isAdmin (first call)
            $adminUser  // for isAdmin (second call)
        );

        // simulate users not empty
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $this->userRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(1);

        // call tested method
        $result = $this->authManager->isRegisterPageAllowed();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test isRegisterPageAllowed when not empty users, logged in as normal user
     *
     * @return void
     */
    public function testIsRegisterPageAllowedLoggedInAsNormalUser(): void
    {
        // simulate user session
        $this->sessionUtil->method('checkSession')->willReturn(true);
        $this->sessionUtil->method('getSessionValue')->willReturn('user_token');
        $normalUser = new User();
        $normalUser->setRole('User');

        // Mock getUserByToken for isUserLogedin
        $this->userRepository->method('getUserByToken')->willReturnOnConsecutiveCalls(
            $normalUser, // for isUserLogedin
            $normalUser, // for isAdmin (first call)
            $normalUser  // for isAdmin (second call)
        );

        // simulate users not empty
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $this->userRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(1);

        // call tested method
        $result = $this->authManager->isRegisterPageAllowed();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test generateUserToken uniqueness (simulating one collision)
     *
     * @return void
     */
    public function testGenerateUserToken(): void
    {
        $this->userRepository->expects($this->exactly(2))->method('getUserByToken')->willReturnOnConsecutiveCalls(new User(), null);

        // call tested method
        $token = $this->authManager->generateUserToken();

        // assert result
        $this->assertEquals(32, strlen($token));
        $this->assertIsString($token);
    }

    /**
     * Test regenerateUsersTokens
     *
     * @return void
     */
    public function testRegenerateUsersTokens(): void
    {
        // mock user entities
        $user1 = new User();
        $user2 = new User();

        // mock users get
        $this->userRepository->method('findAll')->willReturn([$user1, $user2]);
        $this->userRepository->method('getUserByToken')->willReturn(null);

        // expect flush new data
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $result = $this->authManager->regenerateUsersTokens();

        // assert result
        $this->assertTrue($result['status']);
        $this->assertNotNull($user1->getToken());
        $this->assertNotNull($user2->getToken());
        $this->assertNotEquals($user1->getToken(), $user2->getToken());
    }

    /**
     * Test regenerateUsersTokens failure (flush throws exception)
     *
     * @return void
     */
    public function testRegenerateUsersTokensFailure(): void
    {
        // mock users get
        $this->userRepository->method('findAll')->willReturn([new User()]);
        $this->userRepository->method('getUserByToken')->willReturn(null);

        // simulate flush error
        $this->entityManager->method('flush')->willThrowException(new Exception('Error during flush'));

        // call tested method
        $result = $this->authManager->regenerateUsersTokens();

        // assert result
        $this->assertFalse($result['status']);
        $this->assertEquals('Error during flush', $result['message']);
    }

    /**
     * Test regenerateUserToken success
     *
     * @return void
     */
    public function testRegenerateUserToken(): void
    {
        // mock user entity
        $user = new User();
        $oldToken = 'old_token';
        $user->setToken($oldToken);

        // mock user get
        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->userRepository->method('getUserByToken')->willReturn(null);

        // expect flush new data
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $result = $this->authManager->regenerateUserToken('test_username');

        // assert result
        $this->assertTrue($result['status']);
        $this->assertNotNull($user->getToken());
        $this->assertNotEquals($oldToken, $user->getToken());
    }

    /**
     * Test regenerateUserToken user not found
     *
     * @return void
     */
    public function testRegenerateUserTokenNotFound(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        // call tested method
        $result = $this->authManager->regenerateUserToken('non_existent_user');

        // assert result
        $this->assertFalse($result['status']);
        $this->assertEquals('User not found', $result['message']);
    }

    /**
     * Test regenerateUserToken failure (flush throws exception)
     *
     * @return void
     */
    public function testRegenerateUserTokenFailure(): void
    {
        // mock user entity
        $user = new User();
        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->userRepository->method('getUserByToken')->willReturn(null);

        // simulate flush error
        $this->entityManager->method('flush')->willThrowException(new Exception('Error during single user flush'));

        // call tested method
        $result = $this->authManager->regenerateUserToken('test_username');

        // assert result
        $this->assertFalse($result['status']);
        $this->assertEquals('Error during single user flush', $result['message']);
    }
}
