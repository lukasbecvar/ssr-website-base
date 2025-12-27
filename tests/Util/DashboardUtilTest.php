<?php

namespace App\Tests\Util;

use App\Util\JsonUtil;
use App\Util\DashboardUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DashboardUtilTest
 *
 * Test cases for dashboard util class
 *
 * @package App\Tests\Util
 */
class DashboardUtilTest extends TestCase
{
    private DashboardUtil $dashboardUtil;
    private JsonUtil & MockObject $jsonUtil;
    private ErrorManager & MockObject $errorManager;
    private EntityManagerInterface & MockObject $entityManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->jsonUtil = $this->createMock(JsonUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // create instance of DashboardUtil
        $this->dashboardUtil = new DashboardUtil($this->jsonUtil, $this->errorManager, $this->entityManager);
    }

    /**
     * Test get database entity count
     *
     * @return void
     */
    public function testGetDatabaseEntityCount(): void
    {
        $entity = new class {
        };
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())->method('findAll')->willReturn(['entity1', 'entity2', 'entity3']);

        // mock entity manager
        $this->entityManager->expects($this->once())->method('getRepository')->with(get_class($entity))->willReturn($repository);

        // call tested method
        $count = $this->dashboardUtil->getDatabaseEntityCount($entity);

        // assert result
        $this->assertEquals(3, $count);
    }

    /**
     * Test get database entity count with search criteria
     *
     * @return void
     */
    public function testGetDatabaseEntityCountWithSearchCriteria(): void
    {
        $entity = new class {
        };
        $searchCriteria = ['field' => 'value'];
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())->method('findBy')->with($searchCriteria)->willReturn(['entity1', 'entity2']);

        // mock entity manager
        $this->entityManager->expects($this->once())->method('getRepository')->with(get_class($entity))->willReturn($repository);

        // call tested method
        $count = $this->dashboardUtil->getDatabaseEntityCount($entity, $searchCriteria);

        // assert result
        $this->assertEquals(2, $count);
    }

    /**
     * Test is browser list found
     *
     * @return void
     */
    public function testIsBrowserListFound(): void
    {
        // mock json util
        $this->jsonUtil->expects($this->once())->method('getJson')->with($this->stringContains('/../../config/browser-list.json'))->willReturn(['some', 'data']);

        // call tested method
        $result = $this->dashboardUtil->isBrowserListFound();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test is browser list not found
     *
     * @return void
     */
    public function testIsBrowserListNotFound(): void
    {
        // mock json util
        $this->jsonUtil->expects($this->once())->method('getJson')->with($this->stringContains('/../../config/browser-list.json'))->willReturn(null);

        // call tested method
        $result = $this->dashboardUtil->isBrowserListFound();

        // assert result
        $this->assertFalse($result);
    }
}
