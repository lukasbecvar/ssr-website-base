<?php

namespace App\Tests\Controller\Others;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AntilogControllerTest
 *
 * Test cases for antilog component
 *
 * @package App\Tests\Others
 */
class AntilogControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test enable antilog
     *
     * @return void
     */
    public function testEnableAntiLog(): void
    {
        $this->client->request('GET', '/antilog/5369362536');

        // assert response
        $this->assertResponseRedirects('/admin/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
