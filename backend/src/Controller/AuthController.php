<?php

declare(strict_types=1);

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

#[Route('/api/v1/auth')]
#[OA\Tag(name: 'Authentication')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserProviderInterface $userProvider,
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    #[OA\Post(
        operationId: 'login',
        summary: 'Authenticate user and get JWT cookie',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'api_user'),
                    new OA\Property(property: 'password', type: 'string', example: 'api_password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful - JWT token set in httpOnly cookie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Missing credentials'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ],
        security: []
    )]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username']) || !isset($data['password'])) {
            return new JsonResponse(
                ['error' => 'Username and password are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $username = $data['username'];
        $password = $data['password'];

        try {
            $user = $this->userProvider->loadUserByIdentifier($username);
        } catch (\Exception) {
            return new JsonResponse(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Verify password
        if (!$user instanceof PasswordAuthenticatedUserInterface || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        // Create response with httpOnly cookie
        $response = new JsonResponse(['message' => 'Login successful']);

        $cookie = Cookie::create('jwt_token')
            ->withValue($token)
            ->withExpires(time() + 3600) // 1 hour
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('lax');

        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    #[OA\Get(
        operationId: 'me',
        summary: 'Check authentication status',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'authenticated', type: 'boolean', example: true),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    public function me(): JsonResponse
    {
        return new JsonResponse(['authenticated' => true]);
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    #[OA\Post(
        operationId: 'logout',
        summary: 'Clear JWT cookie and logout',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logout successful'),
                    ]
                )
            ),
        ],
        security: []
    )]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(['message' => 'Logout successful']);

        // Clear the cookie by setting it to expire in the past
        $response->headers->clearCookie(
            'jwt_token',
            '/',
            null,
            true,  // secure
            true,  // httpOnly
            'lax'  // sameSite
        );

        return $response;
    }
}
