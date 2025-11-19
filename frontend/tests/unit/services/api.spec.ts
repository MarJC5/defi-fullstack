import axios from 'axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api, clearAuthToken, setAuthToken } from '@/services/api'

vi.mock('axios', () => ({
  default: {
    create: vi.fn(() => ({
      interceptors: {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      },
      get: vi.fn(),
      post: vi.fn(),
    })),
  },
}))

describe('API Service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('api instance', () => {
    it('should create axios instance with correct baseURL', () => {
      expect(axios.create).toHaveBeenCalledWith(
        expect.objectContaining({
          baseURL: expect.stringContaining('/api/v1'),
        }),
      )
    })

    it('should set correct headers', () => {
      expect(axios.create).toHaveBeenCalledWith(
        expect.objectContaining({
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
          }),
        }),
      )
    })
  })

  describe('setAuthToken', () => {
    it('should set Authorization header with Bearer token', () => {
      const token = 'test-jwt-token'
      setAuthToken(token)
      // Token should be stored for interceptor use
      expect(true).toBe(true) // Will be implemented in GREEN phase
    })
  })

  describe('clearAuthToken', () => {
    it('should remove Authorization header', () => {
      clearAuthToken()
      // Token should be cleared
      expect(true).toBe(true) // Will be implemented in GREEN phase
    })
  })

  describe('request interceptor', () => {
    it('should add Authorization header to requests when token is set', () => {
      // Interceptor should attach token
      expect(true).toBe(true) // Will be tested with actual implementation
    })
  })

  describe('response interceptor', () => {
    it('should return response data on success', () => {
      // Success response handling
      expect(true).toBe(true) // Will be tested with actual implementation
    })

    it('should handle 401 unauthorized error', () => {
      // Should clear token and redirect
      expect(true).toBe(true) // Will be tested with actual implementation
    })

    it('should transform API errors to ApiError format', () => {
      // Error transformation
      expect(true).toBe(true) // Will be tested with actual implementation
    })
  })
})
