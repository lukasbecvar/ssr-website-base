<?php

namespace App\Tests\Twig;

use PHPUnit\Framework\TestCase;
use App\Twig\DatabaseHelperExtension;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class DatabaseHelperExtensionTest
 *
 * Test cases for database helper twig extension
 *
 * @package App\Tests\Twig
 */
class DatabaseHelperExtensionTest extends TestCase
{
    private DatabaseHelperExtension $databaseHelperExtension;

    protected function setUp(): void
    {
        $this->databaseHelperExtension = new DatabaseHelperExtension();
    }

    /**
     * Input type data provider
     *
     * @return array<int, array<int, string>> The input type data
     */
    public static function provideInputTypeData(): array
    {
        return [
            ['INT', 'number'],
            ['INTEGER', 'number'],
            ['TINYINT', 'number'],
            ['FLOAT', 'number'],
            ['DECIMAL', 'number'],
            ['DATE', 'date'],
            ['DATETIME', 'datetime-local'],
            ['TIMESTAMP', 'datetime-local'],
            ['TIME', 'time'],
            ['BOOLEAN', 'checkbox'],
            ['TINYINT(1)', 'checkbox'],
            ['TEXT', 'textarea'],
            ['LONGTEXT', 'textarea'],
            ['VARCHAR(255)', 'text'],
            ['CHAR(10)', 'text'],
            ['UNKNOWN_TYPE', 'text']
        ];
    }

    /**
     * Format value data provider
     *
     * @return array<int, array<int, mixed>> The format value data
     */
    public static function provideFormatValueData(): array
    {
        return [
            ['2024-12-24 15:30:00', 'datetime-local', '2024-12-24T15:30'],
            ['invalid-date', 'datetime-local', 'invalid-date'],
            ['2024-12-24', 'date', '2024-12-24'],
            ['2024-12-24 10:00:00', 'date', '2024-12-24'],
            ['15:30:00', 'time', '15:30'],
            ['15:30', 'time', '15:30'],
            ['1', 'checkbox', 'true'],
            [1, 'checkbox', 'true'],
            [true, 'checkbox', 'true'],
            ['true', 'checkbox', 'true'],
            [0, 'checkbox', ''],
            ['0', 'checkbox', ''],
            ['test string', 'text', 'test string'],
            [null, 'text', ''],
            ['', 'text', '']
        ];
    }

    /**
     * Test get functions
     *
     * @return void
     */
    public function testGetFunctions(): void
    {
        // call tested method
        $functions = $this->databaseHelperExtension->getFunctions();

        // assert result
        $this->assertCount(2, $functions);
        $this->assertEquals('getInputTypeFromDbType', $functions[0]->getName());
        $this->assertEquals([$this->databaseHelperExtension, 'getInputTypeFromDbType'], $functions[0]->getCallable());
        $this->assertEquals('formatValueForInput', $functions[1]->getName());
        $this->assertEquals([$this->databaseHelperExtension, 'formatValueForInput'], $functions[1]->getCallable());
    }

    /**
     * Test get input type from db type
     *
     * @param string $dbType The database type
     * @param string $expected The expected input type
     * @return void
     */
    #[DataProvider('provideInputTypeData')]
    public function testGetInputTypeFromDbType(string $dbType, string $expected): void
    {
        $this->assertEquals($expected, $this->databaseHelperExtension->getInputTypeFromDbType($dbType));
    }

    /**
     * Test format value for input
     *
     * @param mixed $value The value to format
     * @param string $inputType The input type
     * @param string $expected The expected formatted value
     * @return void
     */
    #[DataProvider('provideFormatValueData')]
    public function testFormatValueForInput($value, string $inputType, string $expected): void
    {
        $this->assertEquals($expected, $this->databaseHelperExtension->formatValueForInput($value, $inputType));
    }
}
