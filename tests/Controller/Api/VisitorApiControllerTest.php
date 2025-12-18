<?php

namespace App\Tests\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class VisitorApiControllerTest
 *
 * Test cases for visitor status update api
 *
 * @package App\Tests\Api
 */
class VisitorApiControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test update visitor activity status
     *
     * @return void
     */
    public function testUpdateVisitorActivityStatus(): void
    {
        $this->client->request('GET', '/api/visitor/update/activity');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
