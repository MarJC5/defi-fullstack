<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RouteControllerTest extends WebTestCase
{
    public function testCreateRouteSuccess(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fromStationId' => 'MX',
                'toStationId' => 'CGE',
                'analyticCode' => 'TEST-001',
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('MX', $response['fromStationId']);
        $this->assertEquals('CGE', $response['toStationId']);
        $this->assertEquals('TEST-001', $response['analyticCode']);
        $this->assertArrayHasKey('distanceKm', $response);
        $this->assertArrayHasKey('path', $response);
        $this->assertArrayHasKey('createdAt', $response);
        $this->assertEquals(['MX', 'CGE'], $response['path']);
    }

    public function testCreateRouteWithMultipleStops(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fromStationId' => 'MX',
                'toStationId' => 'BEMM',
                'analyticCode' => 'TEST-002',
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertGreaterThan(2, count($response['path']));
        $this->assertEquals('MX', $response['path'][0]);
        $this->assertEquals('BEMM', $response['path'][count($response['path']) - 1]);
    }

    public function testCreateRouteWithInvalidFromStation(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fromStationId' => 'INVALID',
                'toStationId' => 'CGE',
                'analyticCode' => 'TEST-003',
            ])
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateRouteWithInvalidToStation(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fromStationId' => 'MX',
                'toStationId' => 'INVALID',
                'analyticCode' => 'TEST-004',
            ])
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateRouteWithMissingFields(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fromStationId' => 'MX',
                // missing toStationId and analyticCode
            ])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateRouteWithEmptyBody(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $this->assertResponseStatusCodeSame(400);
    }
}
