# Authentication - Single-User API Key Pattern

## Overview

This application uses a **single-user API key pattern** with JWT authentication. It provides secure API protection without the complexity of user management.

---

## Architecture

### Why This Pattern?

The README requires "authentification" but not user management. This approach:
- Demonstrates proper JWT authentication flow
- Protects API endpoints from unauthorized access
- Uses industry-standard security practices
- Keeps complexity appropriate for the challenge scope

### Security Features

1. **JWT Tokens** - Stateless, signed tokens with 1-hour expiry
2. **httpOnly Cookies** - Prevents XSS attacks (JavaScript cannot access token)
3. **CORS with Credentials** - Only allows requests from authorized origins
4. **Password Hashing** - bcrypt with configurable cost
5. **HTTPS** - Required in production

---

## Configuration

### Environment Variables

```bash
# backend/.env
API_USER_NAME=api_user
API_USER_PASSWORD_HASH='$2y$12$...'  # bcrypt hash
```

### Generating Password Hash

```bash
# Generate hash for a new password
docker compose run --rm backend php -r "echo password_hash('your_password', PASSWORD_BCRYPT) . PHP_EOL;"
```

### Changing Credentials

1. Generate a new password hash
2. Update `API_USER_NAME` and `API_USER_PASSWORD_HASH` in `.env`
3. Restart the backend container

---

## Endpoints

### POST /api/v1/auth/login

Authenticates user and sets JWT cookie.

**Request:**
```json
{
  "username": "api_user",
  "password": "api_password"
}
```

**Response (200):**
```json
{
  "message": "Login successful"
}
```

**Headers:**
```
Set-Cookie: jwt_token=eyJ...; HttpOnly; SameSite=Lax; Secure; Path=/
```

### POST /api/v1/auth/logout

Clears the JWT cookie.

**Response (200):**
```json
{
  "message": "Logout successful"
}
```

---

## Frontend Integration

### API Service Configuration

```typescript
// src/services/api.ts
export const api = axios.create({
  baseURL: '/api/v1',
  withCredentials: true, // Send cookies with requests
})
```

### Auth Composable

```typescript
// src/composables/useAuth.ts
export function useAuth() {
  const isAuthenticated = ref(false)

  const login = async (credentials) => {
    await authService.login(credentials)
    isAuthenticated.value = true
  }

  const logout = async () => {
    await authService.logout()
    isAuthenticated.value = false
  }

  return { isAuthenticated, login, logout }
}
```

### Login Flow

1. User opens app → sees login form
2. User enters credentials
3. Frontend calls `/api/v1/auth/login`
4. Backend validates and sets httpOnly cookie
5. Frontend marks user as authenticated
6. Subsequent API requests automatically include cookie

---

## Backend Implementation

### AuthController

```php
#[Route('/api/v1/auth')]
class AuthController extends AbstractController
{
    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // 1. Validate credentials
        $user = $this->userProvider->loadUserByIdentifier($username);

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // 2. Generate JWT
        $token = $this->jwtManager->create($user);

        // 3. Set httpOnly cookie
        $cookie = Cookie::create('jwt_token')
            ->withValue($token)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('lax');

        $response->headers->setCookie($cookie);

        return $response;
    }
}
```

### JWT Token Extraction

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    token_extractors:
        # For Swagger/API clients
        authorization_header:
            enabled: true
            prefix: Bearer
        # For frontend (cookie)
        cookie:
            enabled: true
            name: jwt_token
```

---

## Swagger/OpenAPI Access

Swagger UI can still use Bearer token authentication:

1. Generate token: `make jwt-token`
2. In Swagger UI, click "Authorize"
3. Enter: `Bearer <token>`

This allows API testing without the frontend.

---

## Security Considerations

### What This Pattern Protects Against

- **Unauthorized API access** - JWT required for protected endpoints
- **XSS attacks** - httpOnly cookies cannot be stolen by JavaScript
- **CSRF attacks** - SameSite=Strict cookie policy
- **Token theft** - Tokens expire after 1 hour

### Limitations

- **Single user only** - No per-user permissions
- **Shared credentials** - Anyone with the password has full access
- **No audit trail** - Cannot track who made which request

### When to Upgrade

Consider adding user management when you need:
- Multiple users with different permissions
- Audit logging per user
- Self-service password reset
- OAuth/SSO integration

---

## Testing

### Manual Testing

```bash
# Login
curl -k -X POST https://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "api_user", "password": "api_password"}' \
  -c cookies.txt

# Access protected endpoint
curl -k -X POST https://localhost/api/v1/routes \
  -H "Content-Type: application/json" \
  -d '{"fromStationId": "MX", "toStationId": "CGE", "analyticCode": "TEST"}' \
  -b cookies.txt
```

### Automated Tests

```php
// tests/Integration/AuthControllerTest.php
public function testLoginWithValidCredentials(): void
{
    $client->request('POST', '/api/v1/auth/login', [], [], [], json_encode([
        'username' => 'api_user',
        'password' => 'api_password',
    ]));

    $this->assertResponseIsSuccessful();

    $cookie = $response->headers->getCookies()[0];
    $this->assertTrue($cookie->isHttpOnly());
}
```

---

## Default Credentials

For development and testing:
- **Username**: `api_user`
- **Password**: `api_password`

⚠️ **Change these in production!**
