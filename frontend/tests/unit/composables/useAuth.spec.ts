import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuth } from '@/composables/useAuth'
import { api } from '@/services/api'
import { authService } from '@/services/auth'

vi.mock('@/services/auth', () => ({
  authService: {
    login: vi.fn(),
    logout: vi.fn(),
  },
}))

vi.mock('@/services/api', () => ({
  api: {
    get: vi.fn(),
  },
}))

describe('useAuth', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset auth state between tests
    const { isAuthenticated, authError, isLoading, isCheckingAuth } = useAuth()
    isAuthenticated.value = false
    authError.value = null
    isLoading.value = false
    isCheckingAuth.value = false
  })

  describe('login', () => {
    it('sets isAuthenticated to true on successful login', async () => {
      vi.mocked(authService.login).mockResolvedValue({ message: 'Login successful' })

      const { login, isAuthenticated, isLoading } = useAuth()

      await login({ username: 'api_user', password: 'api_password' })

      expect(authService.login).toHaveBeenCalledWith({
        username: 'api_user',
        password: 'api_password',
      })
      expect(isAuthenticated.value).toBe(true)
      expect(isLoading.value).toBe(false)
    })

    it('sets authError on failed login', async () => {
      vi.mocked(authService.login).mockRejectedValue({ message: 'Invalid credentials' })

      const { login, isAuthenticated, authError } = useAuth()

      await login({ username: 'api_user', password: 'wrong' })

      expect(isAuthenticated.value).toBe(false)
      expect(authError.value).toBe('Invalid credentials')
    })

    it('sets isLoading during login', async () => {
      let resolveLogin: (value: unknown) => void
      const loginPromise = new Promise(resolve => {
        resolveLogin = resolve
      })
      vi.mocked(authService.login).mockReturnValue(loginPromise as Promise<{ message: string }>)

      const { login, isLoading } = useAuth()

      const loginCall = login({ username: 'api_user', password: 'api_password' })
      expect(isLoading.value).toBe(true)

      resolveLogin!({ message: 'Login successful' })
      await loginCall

      expect(isLoading.value).toBe(false)
    })
  })

  describe('logout', () => {
    it('sets isAuthenticated to false on logout', async () => {
      vi.mocked(authService.logout).mockResolvedValue(undefined)

      const { logout, isAuthenticated } = useAuth()
      isAuthenticated.value = true

      await logout()

      expect(authService.logout).toHaveBeenCalled()
      expect(isAuthenticated.value).toBe(false)
    })
  })

  describe('checkAuth', () => {
    it('sets isAuthenticated to true when /auth/me succeeds', async () => {
      vi.mocked(api.get).mockResolvedValue({ authenticated: true })

      const { checkAuth, isAuthenticated, isCheckingAuth } = useAuth()

      await checkAuth()

      expect(api.get).toHaveBeenCalledWith('/auth/me')
      expect(isAuthenticated.value).toBe(true)
      expect(isCheckingAuth.value).toBe(false)
    })

    it('sets isAuthenticated to false when /auth/me fails', async () => {
      vi.mocked(api.get).mockRejectedValue({ message: 'Unauthorized', code: '401' })

      const { checkAuth, isAuthenticated } = useAuth()

      await checkAuth()

      expect(isAuthenticated.value).toBe(false)
    })

    it('sets isCheckingAuth during check', async () => {
      let resolveCheck: (value: unknown) => void
      const checkPromise = new Promise(resolve => {
        resolveCheck = resolve
      })
      vi.mocked(api.get).mockReturnValue(checkPromise as Promise<unknown>)

      const { checkAuth, isCheckingAuth } = useAuth()

      const checkCall = checkAuth()
      expect(isCheckingAuth.value).toBe(true)

      resolveCheck!({ authenticated: true })
      await checkCall

      expect(isCheckingAuth.value).toBe(false)
    })
  })
})
