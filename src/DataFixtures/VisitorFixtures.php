<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Visitor;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

/**
 * Class VisitorFixtures
 *
 * VisitorFixtures loads sample visitor data into the database
 *
 * @package App\DataFixtures
 */
class VisitorFixtures extends Fixture
{
    /**
     * User agents for different browsers
     *
     * @var array<string>
     */
    private $browsers = [
        'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)',
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36',
        'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.6367.91 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
    ];

    /**
     * Operating systems list
     *
     * @var array<string>
     */
    private $os = [
        'Windows',
        'Linux',
        'OSX'
    ];

    /**
     * Countries list
     *
     * @var array<string>
     */
    private $county = [
        'CZ',
        'US',
        'NL'
    ];

    /**
     * Cities list
     *
     * @var array<string>
     */
    private $city = [
        'Amsterdam',
        'Singapore',
        'Prague'
    ];

    /**
     * Load visitor fixtures into the database
     *
     * @param ObjectManager $manager The entity manager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $currentDate = new DateTime();
        for ($i = 0; $i < 1000; $i++) {
            // init visitor entity
            $visitor = new Visitor();

            // randomize last visit and first visit within the last year
            $randomDays = rand(0, 365);
            $randomHours = rand(0, 23);
            $randomMinutes = rand(0, 59);
            $firstVisit = clone $currentDate;
            $lastVisit = clone $currentDate;
            $firstVisit->modify("-$randomDays days -$randomHours hours -$randomMinutes minutes");
            $lastVisit->modify("-$randomDays days -$randomHours hours -$randomMinutes minutes");

            // set visitor entity data
            $visitor->setFirstVisit($firstVisit)
                ->setLastVisit($lastVisit)
                ->setFirstVisitSite('https://homepage.idk')
                ->setBrowser($this->browsers[array_rand($this->browsers)])
                ->setOs($this->os[array_rand($this->os)])
                ->setReferer('google.com')
                ->setCity($this->city[array_rand($this->city)])
                ->setCountry($this->county[array_rand($this->county)])
                ->setIpAddress('192.168.1.' . $i)
                ->setBannedStatus($i % 2 === 0)
                ->setBanReason($i % 2 === 0 ? 'reason for ban' : 'non-banned')
                ->setBannedTime(null)
                ->setEmail($i % 2 === 0 ? 'Unknown' : 'visitor' . $i . '@example.com');

            // persist visitor entity
            $manager->persist($visitor);
        }

        // flush all visitor objects to the database
        $manager->flush();
    }
}
