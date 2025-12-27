<?php

namespace App\Tests\Entity;

use DateTime;
use App\Entity\User;
use App\Entity\Visitor;
use PHPUnit\Framework\TestCase;

/**
 * Class UserTest
 *
 * Test cases for user entity
 *
 * @package App\Tests\Entity
 */
class UserTest extends TestCase
{
    /**
     * Test user entity
     *
     * @return void
     */
    public function testUserEntity(): void
    {
        $user = new User();
        $visitor = new Visitor();
        $time = new DateTime();

        // set values
        $user->setUsername('testuser');
        $user->setPassword('hashed_password');
        $user->setRole('ROLE_ADMIN');
        $user->setIpAddress('10.0.0.1');
        $user->setToken('random_token');
        $user->setRegistedTime($time);
        $user->setLastLoginTime($time);
        $user->setProfilePic('avatar-image');
        $user->setVisitor($visitor);

        // assert values for getters
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertEquals('ROLE_ADMIN', $user->getRole());
        $this->assertEquals('10.0.0.1', $user->getIpAddress());
        $this->assertEquals('random_token', $user->getToken());
        $this->assertSame($time, $user->getRegistedTime());
        $this->assertSame($time, $user->getLastLoginTime());
        $this->assertEquals('avatar-image', $user->getProfilePic());
        $this->assertSame($visitor, $user->getVisitor());
    }
}
