<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

class StatsControllerTest extends WebTestCase
{
    private function getAuthHeaders(): array
    {
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        $user = new InMemoryUser('api_user', '', ['ROLE_API']);
        $token = $jwtManager->create($user);

        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];
    }

    public function testGetDistancesWithoutAuth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/stats/distances');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetDistancesReturnsEmptyListWhenNoRoutes(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/v1/stats/distances',
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('items', $response);
        $this->assertArrayHasKey('groupBy', $response);
        $this->assertIsArray($response['items']);
        $this->assertEquals('none', $response['groupBy']);
    }

    /**
     * @group database
     * This test requires database persistence to work properly.
     * With InMemoryRepository, state is lost between requests.
     */
    public function testGetDistancesAggregatesByAnalyticCode(): void
    {
        $client = static::createClient();

        // First create some routes
        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([
                'fromStationId' => 'MX',
                'toStationId' => 'CGE',
                'analyticCode' => 'STATS-001',
            ])
        );
        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([
                'fromStationId' => 'MX',
                'toStationId' => 'BEMM',
                'analyticCode' => 'STATS-001',
            ])
        );
        $this->assertResponseStatusCodeSame(201);

        // Now get stats
        $client->request(
            'GET',
            '/api/v1/stats/distances',
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('items', $response);
        $this->assertNotEmpty($response['items']);

        // Find our analytic code
        $found = false;
        foreach ($response['items'] as $item) {
            if ($item['analyticCode'] === 'STATS-001') {
                $found = true;
                $this->assertArrayHasKey('totalDistanceKm', $item);
                $this->assertGreaterThan(0, $item['totalDistanceKm']);
            }
        }
        $this->assertTrue($found, 'Analytic code STATS-001 not found in response');
    }

    public function testGetDistancesWithDateFilter(): void
    {
        $client = static::createClient();

        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $client->request(
            'GET',
            '/api/v1/stats/distances',
            ['from' => $today, 'to' => $tomorrow],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('from', $response);
        $this->assertArrayHasKey('to', $response);
        $this->assertEquals($today, $response['from']);
        $this->assertEquals($tomorrow, $response['to']);
    }

    public function testGetDistancesWithGroupByMonth(): void
    {
        $client = static::createClient();

        // First create a route to have data
        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([
                'fromStationId' => 'MX',
                'toStationId' => 'CGE',
                'analyticCode' => 'PERIOD-TEST',
            ])
        );
        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/api/v1/stats/distances',
            ['groupBy' => 'month'],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('month', $response['groupBy']);

        // Check that items have periodStart and periodEnd when groupBy is used
        $this->assertNotEmpty($response['items']);
        foreach ($response['items'] as $item) {
            $this->assertArrayHasKey('periodStart', $item, 'periodStart is required when groupBy is used');
            $this->assertArrayHasKey('periodEnd', $item, 'periodEnd is required when groupBy is used');
            $this->assertArrayHasKey('group', $item);
        }
    }

    public function testGetDistancesWithGroupByDay(): void
    {
        $client = static::createClient();

        // First create a route to have data
        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([
                'fromStationId' => 'MX',
                'toStationId' => 'CGE',
                'analyticCode' => 'DAY-TEST',
            ])
        );
        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/api/v1/stats/distances',
            ['groupBy' => 'day'],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('day', $response['groupBy']);
        $this->assertNotEmpty($response['items']);

        foreach ($response['items'] as $item) {
            if ($item['analyticCode'] === 'DAY-TEST') {
                $this->assertArrayHasKey('periodStart', $item);
                $this->assertArrayHasKey('periodEnd', $item);
                // For day groupBy, periodStart and periodEnd should be the same date
                $this->assertEquals($item['periodStart'], $item['periodEnd']);
                $this->assertEquals($item['group'], $item['periodStart']);
            }
        }
    }

    public function testGetDistancesWithGroupByYear(): void
    {
        $client = static::createClient();

        // First create a route to have data
        $client->request(
            'POST',
            '/api/v1/routes',
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([
                'fromStationId' => 'MX',
                'toStationId' => 'CGE',
                'analyticCode' => 'YEAR-TEST',
            ])
        );
        $this->assertResponseStatusCodeSame(201);

        $client->request(
            'GET',
            '/api/v1/stats/distances',
            ['groupBy' => 'year'],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('year', $response['groupBy']);
        $this->assertNotEmpty($response['items']);

        foreach ($response['items'] as $item) {
            if ($item['analyticCode'] === 'YEAR-TEST') {
                $this->assertArrayHasKey('periodStart', $item);
                $this->assertArrayHasKey('periodEnd', $item);
                // For year groupBy, periodStart should be first day of year
                $year = $item['group'];
                $this->assertEquals("$year-01-01", $item['periodStart']);
                $this->assertEquals("$year-12-31", $item['periodEnd']);
            }
        }
    }

    public function testGetDistancesWithInvalidGroupBy(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/v1/stats/distances',
            ['groupBy' => 'invalid'],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetDistancesWithInvalidDateRange(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/v1/stats/distances',
            ['from' => '2025-12-31', 'to' => '2025-01-01'],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatusCodeSame(400);
    }
}
