<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Message;
use App\Util\SecurityUtil;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class MessageFixtures
 *
 * MessageFixtures loads sample inbox data into the database
 *
 * @package App\DataFixtures
 */
class MessageFixtures extends Fixture
{
    private SecurityUtil $securityUtil;

    public function __construct(SecurityUtil $securityUtil)
    {
        $this->securityUtil = $securityUtil;
    }

    /**
     * Load inbox fixtures into the database
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // testing messages
        $messageData = [
            ['message' => 'test message 1'],
            ['message' => 'test message 2'],
            ['message' => 'test message 3'],
            ['message' => 'test message 4']
        ];

        // create message fixtures
        foreach ($messageData as $data) {
            $message = new Message();

            // set message properties
            $message->setName('Lukáš Bečvář')
                ->setEmail('becvarlukas99@gmail.com	')
                ->setMessage($this->securityUtil->encryptAes($data['message']))
                ->setTime(new DateTime('2023-02-12 12:00:00'))
                ->setIpAddress('172.18.0.1')
                ->setStatus('open')
                ->setVisitorID(1);

            // persist message object
            $manager->persist($message);
        }

        // flush all message objects to the database
        $manager->flush();
    }
}
