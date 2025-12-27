<?php

namespace App\Tests\Manager;

use Exception;
use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\JsonUtil;
use App\Entity\Visitor;
use Doctrine\ORM\Query;
use App\Util\CookieUtil;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use App\Manager\VisitorManager;
use PHPUnit\Framework\TestCase;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

/**
 * Class LogManagerTest
 *
 * Test cases for LogManager
 *
 * @package App\Tests\Manager
 */
class LogManagerTest extends TestCase
{
    private LogManager $logManager;
    private AppUtil & MockObject $appUtil;
    private JsonUtil & MockObject $jsonUtil;
    private CookieUtil & MockObject $cookieUtil;
    private ErrorManager & MockObject $errorManager;
    private SecurityUtil & MockObject $securityUtil;
    private LogRepository & MockObject $logRepository;
    private VisitorManager & MockObject $visitorManager;
    private VisitorInfoUtil & MockObject $visitorInfoUtil;
    private EntityManagerInterface & MockObject $entityManager;

    // config properties for dynamic mock behavior
    private string $logLevel = '0';
    private string $externalLogUrl = '';
    private string $itemsPerPage = '10';
    private string $logsEnabled = 'false';
    private string $antiLogCookieValue = '';
    private string $externalLogApiToken = '';
    private string $externalLogEnabled = 'false';

    protected function setUp(): void
    {
        // testing config
        $this->logLevel = '0';
        $this->externalLogUrl = '';
        $this->itemsPerPage = '10';
        $this->logsEnabled = 'false';
        $this->antiLogCookieValue = '';
        $this->externalLogApiToken = '';
        $this->externalLogEnabled = 'false';

        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->jsonUtil = $this->createMock(JsonUtil::class);
        $this->cookieUtil = $this->createMock(CookieUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $this->logRepository = $this->createMock(LogRepository::class);
        $this->visitorManager = $this->createMock(VisitorManager::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // configure AppUtil mock to read from class properties
        $this->appUtil->method('getEnvValue')->will(new ReturnCallback(function ($key) {
            return match ($key) {
                'LOGS_ENABLED' => $this->logsEnabled,
                'LOG_LEVEL' => $this->logLevel,
                'ANTI_LOG_COOKIE' => $this->antiLogCookieValue,
                'EXTERNAL_LOG_ENABLED' => $this->externalLogEnabled,
                'EXTERNAL_LOG_URL' => $this->externalLogUrl,
                'EXTERNAL_LOG_API_TOKEN' => $this->externalLogApiToken,
                'ITEMS_PER_PAGE' => $this->itemsPerPage,
                default => ''
            };
        }));

        // common mocks for VisitorInfoUtil and SecurityUtil
        $this->visitorInfoUtil->method('getIP')->willReturn('127.0.0.1');
        $this->securityUtil->method('escapeString')->willReturnArgument(0);
        $this->visitorInfoUtil->method('getUserAgent')->willReturn('Test Browser');
        $this->visitorManager->method('getVisitorRepository')->willReturn($this->createMock(Visitor::class));

        // init log manager instance
        $this->logManager = new LogManager(
            $this->appUtil,
            $this->jsonUtil,
            $this->cookieUtil,
            $this->errorManager,
            $this->securityUtil,
            $this->logRepository,
            $this->visitorManager,
            $this->visitorInfoUtil,
            $this->entityManager
        );
    }

    /**
     * Test log saves entity when logging is enabled
     *
     * @return void
     */
    public function testLogSavesEntityWhenEnabled(): void
    {
        $this->logsEnabled = 'true';
        $this->logLevel = '4';

        // expect persist and flush
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Log::class));
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->log('test-log', 'This is a test message');
    }

    /**
     * Test log does not save when disabled in config
     *
     * @return void
     */
    public function testLogDoesNotSaveWhenDisabled(): void
    {
        $this->logLevel = '4';
        $this->logsEnabled = 'false';

        // expect not persist
        $this->entityManager->expects($this->never())->method('persist');

        // call tested method
        $this->logManager->log('test-log', 'Message');
    }

    /**
     * Test log respects Anti-Log Cookie
     *
     * @return void
     */
    public function testLogRespectsAntiLogCookie(): void
    {
        $this->logsEnabled = 'true';
        $this->antiLogCookieValue = 'secret_token';

        // simulate cookie existence
        $_COOKIE['anti-log-cookie'] = 'secret_token';
        $this->cookieUtil->method('get')->with('anti-log-cookie')->willReturn('secret_token');

        // should NOT persist
        $this->entityManager->expects($this->never())->method('persist');

        // call tested method
        $this->logManager->log('test-log', 'Message');

        // cleanup
        unset($_COOKIE['anti-log-cookie']);
    }

    /**
     * Test log bypasses Anti-Log Cookie when requested
     *
     * @return void
     */
    public function testLogBypassesAntiLogCookie(): void
    {
        $this->logLevel = '4';
        $this->logsEnabled = 'true';
        $this->antiLogCookieValue = 'secret_token';

        // simulate cookie existence
        $_COOKIE['anti-log-cookie'] = 'secret_token';
        $this->cookieUtil->method('get')->willReturn('secret_token');

        // should persist because of bypass = true
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->log('test-log', 'Message', true);

        // cleanup
        unset($_COOKIE['anti-log-cookie']);
    }

    /**
     * Test filtering based on Log Level
     *
     * @return void
     */
    public function testLogLevelFiltering(): void
    {
        // case 1: Level 2 (Low) -> should ignore 'database' logs (needs < 3)
        $this->logsEnabled = 'true';
        $this->logLevel = '2';

        // expect not persist
        $this->entityManager->expects($this->never())->method('persist');

        // call tested method
        $this->logManager->log('database', 'Database view');

        // re-setup
        $this->setUp();

        // case 2: Level 1 (Very Low) -> should ignore 'message-sender' logs (needs < 2)
        $this->logsEnabled = 'true';
        $this->logLevel = '1';

        // expect not persist
        $this->entityManager->expects($this->never())->method('persist');

        // call tested method
        $this->logManager->log('message-sender', 'Email sent');

        // re-setup
        $this->setUp();

        // case 3: Level 3 -> Should log 'database'
        $this->logsEnabled = 'true';
        $this->logLevel = '3';

        // expect persist and flush
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->log('database', 'Database view');

        // re-setup
        $this->setUp();

        // case 4: Level 2 -> Should log 'message-sender'
        $this->logsEnabled = 'true';
        $this->logLevel = '2';

        // expect persist and flush
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->log('message-sender', 'Email sent');
    }

    /**
     * Test message truncation
     *
     * @return void
     */
    public function testLogMessageTruncation(): void
    {
        $this->logsEnabled = 'true';
        $this->logLevel = '4';

        $longMessage = str_repeat('A', 600);

        $this->entityManager->expects($this->once())->method('persist')->with($this->callback(function (Log $log) {
            // check if message ends with "..." and is shorter than original
            return str_ends_with($log->getValue(), '...') && strlen($log->getValue()) <= 515; // 512 + 3
        }));

        // call tested method
        $this->logManager->log('test', $longMessage);
    }

    /**
     * Test external logging execution
     *
     * @return void
     */
    public function testExternalLogExecution(): void
    {
        $this->logLevel = '4';
        $this->logsEnabled = 'true';
        $this->externalLogEnabled = 'true';
        $this->externalLogUrl = 'http://api.log';
        $this->externalLogApiToken = 'token123';

        // expect JsonUtil to be called with correct positional arguments
        $this->jsonUtil->expects($this->once())->method('getJson')->with(
            $this->stringContains('http://api.log'),
            'POST',
            'token123'
        );

        // call tested method
        $this->logManager->log('test', 'message');
    }

    /**
     * Test log handles database exception
     *
     * @return void
     */
    public function testLogHandlesDatabaseException(): void
    {
        $this->logsEnabled = 'true';
        $this->logLevel = '4';

        // throw exception on flush
        $this->entityManager->method('flush')->willThrowException(new Exception('DB Error'));

        // expect ErrorManager to handle it
        $this->errorManager->expects($this->once())->method('handleError')->with($this->stringContains('log-error: DB Error'));

        // call tested method
        $this->logManager->log('test', 'message');
    }

    /**
     * Test getLogs returns processed logs
     *
     * @return void
     */
    public function testGetLogsReturnsProcessedLogs(): void
    {
        $page = 1;
        $status = 'unreaded';
        $username = 'test_user';
        $this->logsEnabled = 'true';
        $this->logLevel = '4';
        $this->itemsPerPage = '10';

        // create mock log
        $log = new Log();
        $log->setBrowser('Mozilla/5.0 ...');

        // mock repository
        $this->logRepository->expects($this->once())->method('getLogsByStatus')->with($status, 0, 10)->willReturn([$log]);

        // mock shortify
        $this->visitorInfoUtil->expects($this->once())->method('getBrowserShortify')->willReturn('Firefox');

        // call tested method
        $result = $this->logManager->getLogs($status, $username, $page);

        // assert result
        $this->assertCount(1, $result);
        $this->assertEquals('Firefox', $result[0]->getBrowser());
    }

    /**
     * Test getLogsWhereIP returns processed logs
     *
     * @return void
     */
    public function testGetLogsWhereIP(): void
    {
        $page = 1;
        $username = 'test_user';
        $ipAddress = '127.0.0.1';
        $this->logsEnabled = 'true';
        $this->itemsPerPage = '10';

        // create mock log
        $log = new Log();
        $log->setBrowser('Mozilla/5.0 ...');

        // mock repository
        $this->logRepository->expects($this->once())->method('getLogsByIpAddress')->with($ipAddress, 0, 10)->willReturn([$log]);

        // mock shortify
        $this->visitorInfoUtil->expects($this->once())->method('getBrowserShortify')->willReturn('Firefox');

        // call tested method
        $result = $this->logManager->getLogsWhereIP($ipAddress, $username, $page);

        // assert result
        $this->assertCount(1, $result);
        $this->assertEquals('Firefox', $result[0]->getBrowser());
    }

    /**
     * Test getLogsCount returns count
     *
     * @return void
     */
    public function testGetLogsCount(): void
    {
        // mock repository
        $this->logRepository->expects($this->once())->method('count')->with(['status' => 'unreaded'])->willReturn(5);

        // call tested method
        $this->assertEquals(5, $this->logManager->getLogsCount('unreaded'));
    }

    /**
     * Test getLoginLogsCount returns count
     *
     * @return void
     */
    public function testGetLoginLogsCount(): void
    {
        // mock repository
        $this->logRepository->expects($this->once())->method('count')->with(['name' => 'authenticator'])->willReturn(42);

        // call tested method
        $result = $this->logManager->getLoginLogsCount();

        // assert result
        $this->assertEquals(42, $result);
    }

    /**
     * Test setReaded executes DQL
     *
     * @return void
     */
    public function testSetReaded(): void
    {
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())->method('execute');

        // expect query execution
        $this->entityManager->expects($this->once())->method('createQuery')->with("UPDATE App\Entity\Log l SET l.status = 'readed'")->willReturn($queryMock);

        // call tested method
        $this->logManager->setReaded();
    }

    /**
     * Test setting Anti-Log cookie
     *
     * @return void
     */
    public function testSetAntiLogCookie(): void
    {
        $this->antiLogCookieValue = 'secret';

        // expect cookie set
        $this->cookieUtil->expects($this->once())->method('set')->with(
            'anti-log-cookie',
            'secret',
            $this->greaterThan(time())
        );

        // call tested method
        $this->logManager->setAntiLogCookie();
    }
}
