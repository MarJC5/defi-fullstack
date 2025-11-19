<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testLoginWithValidCredentialsReturnsSuccessAndSetsCookie(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'api_user',
                'password' => 'api_password',
            ])
        );

        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        // Check response body
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Login successful', $data['message']);

        // Check httpOnly cookie is set
        $cookie = $response->headers->getCookies()[0] ?? null;
        $this->assertNotNull($cookie, 'JWT cookie should be set');
        $this->assertEquals('jwt_token', $cookie->getName());
        $this->assertTrue($cookie->isHttpOnly(), 'Cookie should be httpOnly');
        $this->assertEquals('lax', $cookie->getSameSite());
    }

    public function testLoginWithInvalidCredentialsReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'api_user',
                'password' => 'wrong_password',
            ])
        );

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testLoginWithMissingFieldsReturns400(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'api_user',
                // missing password
            ])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLogoutClearsCookie(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/auth/logout');

        $this->assertResponseIsSuccessful();

        // Check cookie is cleared (expires in past)
        $cookie = $client->getResponse()->headers->getCookies()[0] ?? null;
        $this->assertNotNull($cookie);
        $this->assertEquals('jwt_token', $cookie->getName());
        $this->assertTrue($cookie->isCleared(), 'Cookie should be cleared');
    }

    public function testProtectedEndpointWorksWithCookie(): void
    {
        $client = static::createClient();

        // First login to get cookie
        $client->request(
            'POST',
            '/api/v1/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'api_user',
                'password' => 'api_password',
            ])
        );

        $this->assertResponseIsSuccessful();

        // Now access protected endpoint - cookie should be sent automatically
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

        $this->assertResponseIsSuccessful();
    }
}
