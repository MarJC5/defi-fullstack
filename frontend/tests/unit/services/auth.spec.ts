import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/services/api'
import { authService } from '@/services/auth'

vi.mock('@/services/api', () => ({
  api: {
    post: vi.fn(),
  },
}))

describe('authService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('login', () => {
    it('calls api.post with correct endpoint and credentials', async () => {
      const mockResponse = { message: 'Login successful' }
      vi.mocked(api.post).mockResolvedValue(mockResponse)

      const credentials = { username: 'api_user', password: 'api_password' }
      const result = await authService.login(credentials)

      expect(api.post).toHaveBeenCalledWith('/auth/login', credentials)
      expect(result).toEqual(mockResponse)
    })

    it('throws error on invalid credentials', async () => {
      const error = { message: 'Invalid credentials', code: '401' }
      vi.mocked(api.post).mockRejectedValue(error)

      const credentials = { username: 'api_user', password: 'wrong' }

      await expect(authService.login(credentials)).rejects.toEqual(error)
    })
  })

  describe('logout', () => {
    it('calls api.post with correct endpoint', async () => {
      vi.mocked(api.post).mockResolvedValue(undefined)

      await authService.logout()

      expect(api.post).toHaveBeenCalledWith('/auth/logout')
    })
  })
})
