<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class MetricsExportControllerTest
 *
 * Test cases for metrics export controller
 *
 * @package App\Tests
 */
class MetricsExportControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test get metrics when metrics exporter is disabled
     *
     * @return void
     */
    public function testGetMetricsWhenMetricsExporterDisabled(): void
    {
        // simulate metrics exporter disabled
        $_ENV['METRICS_EXPORTER_ENABLED'] = 'false';

        // make request to exporter
        $this->client->request('GET', '/metrics/export');

        // assert response
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * Test get metrics with forbidden ip
     *
     * @return void
     */
    public function testGetMetricsWithForbiddenIP(): void
    {
        // set ip address for simulate forbidden ip
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // make request to exporter
        $this->client->request('GET', '/metrics/export');

        // assert response
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * Test get metrics
     *
     * @return void
     */
    public function testGetMetrics(): void
    {
        // simulate metrics exporter enabled
        $_ENV['METRICS_EXPORTER_ENABLED'] = 'true';

        // set ip address for simulate allowed ip
        $_SERVER['REMOTE_ADDR'] = '172.18.0.1';

        // make request to exporter
        $this->client->request('GET', '/metrics/export');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertArrayHasKey('visitors_count', $responseData);
        $this->assertArrayHasKey('total_visitors_count', $responseData);
        $this->assertIsInt($responseData['visitors_count']);
        $this->assertIsInt($responseData['total_visitors_count']);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }
}
