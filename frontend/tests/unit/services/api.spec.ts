import { beforeEach, describe, expect, it } from 'vitest'
import { api, clearAuthToken, getAuthToken, setAuthToken } from '@/services/api'

describe('API Service', () => {
  beforeEach(() => {
    clearAuthToken()
  })

  describe('api instance', () => {
    it('should have correct baseURL configured', () => {
      expect(api.defaults.baseURL).toContain('/api/v1')
    })

    it('should have correct headers configured', () => {
      expect(api.defaults.headers['Content-Type']).toBe('application/json')
    })
  })

  describe('setAuthToken', () => {
    it('should store the token', () => {
      const token = 'test-jwt-token'
      setAuthToken(token)
      expect(getAuthToken()).toBe(token)
    })
  })

  describe('clearAuthToken', () => {
    it('should remove the stored token', () => {
      setAuthToken('test-token')
      clearAuthToken()
      expect(getAuthToken()).toBeNull()
    })
  })

  describe('interceptors', () => {
    it('should have request interceptor configured', () => {
      expect(api.interceptors.request).toBeDefined()
    })

    it('should have response interceptor configured', () => {
      expect(api.interceptors.response).toBeDefined()
    })
  })
})
