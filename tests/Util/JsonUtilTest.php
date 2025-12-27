<?php

namespace Tests\Unit\Util;

use App\Util\AppUtil;
use App\Util\JsonUtil;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class JsonUtilTest
 *
 * Test cases for json util class
 *
 * @package Tests\Unit\Util
 */
class JsonUtilTest extends TestCase
{
    private JsonUtil $jsonUtil;
    private AppUtil & MockObject $appUtilMock;
    private LoggerInterface & MockObject $loggerMock;

    private string $tempDir;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // create instance of JsonUtil
        $this->jsonUtil = new JsonUtil($this->appUtilMock, $this->loggerMock);

        // temp dir
        $this->tempDir = sys_get_temp_dir() . '/json_util_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // cleanup
        array_map('unlink', glob("$this->tempDir/*.*"));
        rmdir($this->tempDir);
    }

    /**
     * Test get json from valid file
     *
     * @return void
     */
    public function testGetJsonFromValidFile(): void
    {
        $expectedData = ['key' => 'value', 'num' => 123];
        $filePath = $this->tempDir . '/valid.json';
        file_put_contents($filePath, json_encode($expectedData));

        // get JSON data
        $jsonData = $this->jsonUtil->getJson($filePath);

        // assert the data type
        $this->assertIsArray($jsonData);
        $this->assertEquals($expectedData, $jsonData);
    }

    /**
     * Test get json with invalid json syntax
     *
     * @return void
     */
    public function testGetJsonWithInvalidJsonSyntax(): void
    {
        $filePath = $this->tempDir . '/invalid.json';
        file_put_contents($filePath, '{"key": "value"'); // missing closing brace

        // get JSON data
        $jsonData = $this->jsonUtil->getJson($filePath);

        // json_decode returns null for invalid JSON
        $this->assertNull($jsonData);
    }

    /**
     * Test get json from non-existent file
     *
     * @return void
     */
    public function testGetJsonFromNonExistentFile(): void
    {
        // ignore E_WARNING during this specific call
        set_error_handler(function () {
            return true;
        }, E_WARNING);

        try {
            $jsonData = $this->jsonUtil->getJson($this->tempDir . '/does_not_exist.json');
            $this->assertNull($jsonData);
        } finally {
            restore_error_handler();
        }
    }
}
