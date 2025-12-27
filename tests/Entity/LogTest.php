<?php

namespace App\Tests\Entity;

use DateTime;
use App\Entity\Log;
use App\Entity\Visitor;
use PHPUnit\Framework\TestCase;

/**
 * Class LogTest
 *
 * Test cases for log entity
 *
 * @package App\Tests\Entity
 */
class LogTest extends TestCase
{
    /**
     * Test log entity
     *
     * @return void
     */
    public function testLogEntity(): void
    {
        $log = new Log();
        $time = new DateTime();
        $visitor = new Visitor();

        // set values
        $log->setName('test_log');
        $log->setValue('test_value');
        $log->setTime($time);
        $log->setIpAddress('127.0.0.1');
        $log->setStatus('unread');
        $log->setVisitor($visitor);

        // assert values for getters
        $this->assertEquals('test_log', $log->getName());
        $this->assertEquals('test_value', $log->getValue());
        $this->assertSame($time, $log->getTime());
        $this->assertEquals('127.0.0.1', $log->getIpAddress());
        $this->assertEquals('unread', $log->getStatus());
        $this->assertSame($visitor, $log->getVisitor());
    }
}
