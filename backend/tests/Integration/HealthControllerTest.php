<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpointReturnsOkStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/health');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('timestamp', $response);
        $this->assertArrayHasKey('service', $response);
        $this->assertArrayHasKey('database', $response);

        $this->assertSame('trainrouting-backend', $response['service']);
        $this->assertContains($response['status'], ['OK', 'DEGRADED']);
    }

    public function testHealthEndpointReturnsValidTimestamp(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/health');

        $response = json_decode($client->getResponse()->getContent(), true);

        // Verify timestamp is in ATOM format
        $timestamp = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $response['timestamp']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $timestamp);
    }

    public function testHealthEndpointWithDatabaseConnection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/health');

        $response = json_decode($client->getResponse()->getContent(), true);

        // In test environment with proper database, should return OK
        $this->assertContains($response['database'], ['OK']);
        $this->assertSame('OK', $response['status']);
    }
}
