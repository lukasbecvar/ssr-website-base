<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Log;
use App\Entity\Visitor;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Class LogFixtures
 *
 * LogFixtures loads sample log data into the database
 *
 * @package App\DataFixtures
 */
class LogFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            VisitorFixtures::class
        ];
    }

    /**
     * Load log fixtures into the database
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // testing data
        $logsData = [
            [
                'name' => 'internal-error',
                'value' => 'find error: An exception occurred in the driver: SQLSTATE[HY000] [2002] No such file or directory',
                'time' => new DateTime('2023-01-01 12:00:00'),
                'ip_address' => '2a00:1028:838e:71a6:bfd:3ae:61:cbbd',
                'browser' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'status' => 'unreaded',
                'visitor_id' => 32
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: barnhill.maira@gmail.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-02 12:00:00'),
                'ip_address' => '45.131.195.176',
                'browser' => 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:114.0) Gecko/20100101 Firefox/114.0',
                'status' => 'unreaded',
                'visitor_id' => 82
            ],
            [
                'name' => 'internal-error',
                'value' => 'not found error, image: wd7icA2dTKTv9vp5SseaqBPf8kiszAdQ, not found in database',
                'time' => new DateTime('2023-01-03 12:00:00'),
                'ip_address' => '34.148.220.118',
                'browser' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11.6; rv:92.0) Gecko/20100101 Firefox/92.0',
                'status' => 'unreaded',
                'visitor_id' => 193
            ],
            [
                'name' => 'authenticator',
                'value' => 'user: lukasbecvar logged in',
                'time' => new DateTime('2023-01-04 12:00:00'),
                'ip_address' => '2a00:1028:838e:71a6:ec0a:1029:dc19:fee1',
                'browser' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'status' => 'unreaded',
                'visitor_id' => 283
            ],
            [
                'name' => 'anti-log',
                'value' => 'user: lukasbecvar unset antilog',
                'time' => new DateTime('2023-01-05 12:00:00'),
                'ip_address' => '2a00:1028:838e:71a6:ec0a:1029:dc19:fee1',
                'browser' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'status' => 'unreaded',
                'visitor_id' => 283
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: noreplyhere@aol.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-06 12:00:00'),
                'ip_address' => '163.5.241.114',
                'browser' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 Vivaldi/5.3.2679.68',
                'status' => 'unreaded',
                'visitor_id' => 305
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: lemaster.ivy33@gmail.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-07 12:00:00'),
                'ip_address' => '64.64.108.41',
                'browser' => 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'status' => 'unreaded',
                'visitor_id' => 365
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: christiane.costas@gmail.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-08 12:00:00'),
                'ip_address' => '64.64.123.55',
                'browser' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'status' => 'unreaded',
                'visitor_id' => 389
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: conolly.galen@msn.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-09 12:00:00'),
                'ip_address' => '103.163.220.52',
                'browser' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'status' => 'unreaded',
                'visitor_id' => 508
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: willmott.sharyn51@yahoo.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-10 12:00:00'),
                'ip_address' => '93.127.170.23',
                'browser' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 OPR/89.0.4447.51',
                'status' => 'unreaded',
                'visitor_id' => 631
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: admin@charterunionfin.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-11 12:00:00'),
                'ip_address' => '45.86.201.10',
                'browser' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'status' => 'unreaded',
                'visitor_id' => 697
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: noreplyhere@aol.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-12 12:00:00'),
                'ip_address' => '62.12.114.42',
                'browser' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
                'status' => 'unreaded',
                'visitor_id' => 703
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: reece.levay@gmail.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-13 12:00:00'),
                'ip_address' => '185.132.187.97',
                'browser' => 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:114.0) Gecko/20100101 Firefox/114.0',
                'status' => 'unreaded',
                'visitor_id' => 775
            ],
            [
                'name' => 'message-sender',
                'value' => 'message by: tammi.gloeckner2@gmail.com, has been blocked: honeypot used',
                'time' => new DateTime('2023-01-14 12:00:00'),
                'ip_address' => '173.244.55.12',
                'browser' => 'Mozilla/5.0 (Linux x86_64; rv:114.0) Gecko/20100101 Firefox/114.0',
                'status' => 'unreaded',
                'visitor_id' => 814
            ]
        ];

        // create objects with the given data
        foreach ($logsData as $logData) {
            $log = new Log();

            // set log properties
            $log->setName($logData['name'])
                ->setValue($logData['value'])
                ->setTime($logData['time'])
                ->setIpAddress($logData['ip_address'])
                ->setBrowser($logData['browser'])
                ->setStatus($logData['status'])
                ->setVisitor($manager->getRepository(Visitor::class)->find($logData['visitor_id']));

            // persist the log object
            $manager->persist($log);
        }

        // flush all log objects to the database
        $manager->flush();
    }
}
