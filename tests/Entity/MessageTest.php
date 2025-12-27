<?php

namespace App\Tests\Entity;

use DateTime;
use App\Entity\Message;
use App\Entity\Visitor;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageTest
 *
 * Test cases for message entity
 *
 * @package App\Tests\Entity
 */
class MessageTest extends TestCase
{
    /**
     * Test message entity
     *
     * @return void
     */
    public function testMessageEntity(): void
    {
        $message = new Message();
        $time = new DateTime();
        $visitor = new Visitor();

        // set values
        $message->setMessage('Hello World');
        $message->setIpAddress('192.168.1.1');
        $message->setTime($time);
        $message->setStatus('open');
        $message->setVisitor($visitor);

        // assert values for getters
        $this->assertEquals('Hello World', $message->getMessage());
        $this->assertEquals('192.168.1.1', $message->getIpAddress());
        $this->assertSame($time, $message->getTime());
        $this->assertEquals('open', $message->getStatus());
        $this->assertSame($visitor, $message->getVisitor());
    }
}
