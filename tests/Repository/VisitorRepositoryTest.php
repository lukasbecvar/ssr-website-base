<?php

namespace App\Tests\Repository;

use App\Entity\Visitor;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class VisitorRepositoryTest
 *
 * Test cases for visitor repository
 *
 * @package App\Tests\Repository
 */
class VisitorRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::$kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test get all IDs
     *
     * @return void
     */
    public function testGetAllIds(): void
    {
        /** @var \App\Repository\VisitorRepository $visitorRepository */
        $visitorRepository = $this->entityManager->getRepository(\App\Entity\Visitor::class);

        // get visitors
        $visitors = $visitorRepository->getAllIds();

        // assert result
        $this->assertIsArray($visitors, 'The result should be an array of IDs.');
    }

    /**
     * Test find visitors by time filter
     *
     * @return void
     */
    public function testFindByTimeFilter(): void
    {
        /** @var \App\Repository\VisitorRepository $visitorRepository */
        $visitorRepository = $this->entityManager->getRepository(Visitor::class);

        // get visitors
        $visitors = $visitorRepository->findByTimeFilter('H');

        // assert result
        $this->assertIsArray($visitors, 'The result should be an array of visitors.');
    }

    /**
     * Test find visitors by time filter as iterable
     *
     * @return void
     */
    public function testFindByTimeFilterIterable(): void
    {
        /** @var \App\Repository\VisitorRepository $visitorRepository */
        $visitorRepository = $this->entityManager->getRepository(Visitor::class);

        // get visitors
        $visitors = $visitorRepository->findByTimeFilterIterable('H');

        // assert result
        $this->assertIsIterable($visitors, 'The result should be an iterable of visitors.');
    }

    /**
     * Test get visitors count by period
     *
     * @return void
     */
    public function testGetVisitorsCountByPeriod(): void
    {
        /** @var \App\Repository\VisitorRepository $visitorRepository */
        $visitorRepository = $this->entityManager->getRepository(Visitor::class);

        // get visitors
        $visitors = $visitorRepository->getVisitorsCountByPeriod('last_week');

        // assert result
        $this->assertIsArray($visitors, 'The result should be an associative array of visitor counts.');
    }

    /**
     * Test get visitors by country
     *
     * @return void
     */
    public function testGetVisitorsByCountry(): void
    {
        /** @var \App\Repository\VisitorRepository $visitorRepository */
        $visitorRepository = $this->entityManager->getRepository(Visitor::class);

        // get visitors
        $visitors = $visitorRepository->getVisitorsByCountry();

        // assert result
        $this->assertIsArray($visitors, 'The result should be an associative array of country visitor counts.');
    }

    /**
     * Test get visitors by city
     *
     * @return void
     */
    public function testGetVisitorsByCity(): void
    {
        /** @var \App\Repository\VisitorRepository $visitorRepository */
        $visitorRepository = $this->entityManager->getRepository(Visitor::class);

        // get visitors
        $visitors = $visitorRepository->getVisitorsByCity();

        // assert result
        $this->assertIsArray($visitors, 'The result should be an associative array of city visitor counts.');
    }

    /**
     * Test get visitors used browsers
     *
     * @return void
     */
    public function testGetVisitorsUsedBrowsers(): void
    {
        /** @var \App\Repository\VisitorRepository $visitorRepository */
        $visitorRepository = $this->entityManager->getRepository(Visitor::class);

        // get visitors
        $visitors = $visitorRepository->getVisitorsUsedBrowsers();

        // assert result
        $this->assertIsArray($visitors, 'The result should be an associative array of browser visitor counts.');
    }
}
