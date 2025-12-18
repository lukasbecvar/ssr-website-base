<?php

namespace App\Tests\Controller\Public;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class HomeControllerTest
 *
 * Test cases for home page
 *
 * @package App\Tests\Public
 */
class HomeControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load home page
     *
     * @return void
     */
    public function testLoadHomePage(): void
    {
        $this->client->request('GET', '/');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
