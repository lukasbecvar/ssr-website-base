<?php

namespace App\Tests\Entity;

use DateTime;
use App\Entity\Log;
use App\Entity\User;
use App\Entity\Message;
use App\Entity\Visitor;
use PHPUnit\Framework\TestCase;

/**
 * Class VisitorTest
 *
 * Test cases for visitor entity
 *
 * @package App\Tests\Entity
 */
class VisitorTest extends TestCase
{
    /**
     * Test visitor entity
     *
     * @return void
     */
    public function testVisitorEntity(): void
    {
        $visitor = new Visitor();
        $firstVisit = new DateTime();
        $lastVisit = new DateTime();

        // set values
        $visitor->setFirstVisit($firstVisit);
        $visitor->setLastVisit($lastVisit);
        $visitor->setBrowser('Chrome');
        $visitor->setOs('Linux');
        $visitor->setCity('Prague');
        $visitor->setCountry('Czech Republic');
        $visitor->setIpAddress('8.8.8.8');
        $visitor->setBannedStatus(true);
        $visitor->setBanReason('Spam');
        $visitor->setBannedTime($lastVisit);
        $visitor->setEmail('test@example.com');

        // assert values for getters
        $this->assertSame($firstVisit, $visitor->getFirstVisit());
        $this->assertSame($lastVisit, $visitor->getLastVisit());
        $this->assertEquals('Chrome', $visitor->getBrowser());
        $this->assertEquals('Linux', $visitor->getOs());
        $this->assertEquals('Prague', $visitor->getCity());
        $this->assertEquals('Czech Republic', $visitor->getCountry());
        $this->assertEquals('8.8.8.8', $visitor->getIpAddress());
        $this->assertTrue($visitor->getBannedStatus());
        $this->assertEquals('Spam', $visitor->getBanReason());
        $this->assertSame($lastVisit, $visitor->getBannedTime());
        $this->assertEquals('test@example.com', $visitor->getEmail());
    }

    /**
     * Test relation to user
     *
     * @return void
     */
    public function testRelationToUser(): void
    {
        $user = new User();
        $visitor = new Visitor();

        // add user to visitor
        $visitor->addUser($user);

        // assert user is in visitor
        $this->assertTrue($visitor->getUsers()->contains($user));
        $this->assertSame($visitor, $user->getVisitor());

        // remove user from visitor
        $visitor->removeUser($user);

        // assert user is not associated with visitor
        $this->assertFalse($visitor->getUsers()->contains($user));
    }

    /**
     * Test relation to message
     *
     * @return void
     */
    public function testRelationToMessage(): void
    {
        $visitor = new Visitor();
        $message = new Message();

        // add message to visitor
        $visitor->addMessage($message);

        // assert message is in visitor
        $this->assertTrue($visitor->getMessages()->contains($message));
        $this->assertSame($visitor, $message->getVisitor());

        // remove message from visitor
        $visitor->removeMessage($message);

        // assert message is not associated with visitor
        $this->assertFalse($visitor->getMessages()->contains($message));
    }

    /**
     * Test relation to log
     *
     * @return void
     */
    public function testRelationToLog(): void
    {
        $log = new Log();
        $visitor = new Visitor();

        // add log to visitor
        $visitor->addLog($log);

        // assert log is in visitor
        $this->assertTrue($visitor->getLogs()->contains($log));
        $this->assertSame($visitor, $log->getVisitor());

        // remove log from visitor
        $visitor->removeLog($log);

        // assert log is not associated with visitor
        $this->assertFalse($visitor->getLogs()->contains($log));
    }
}
