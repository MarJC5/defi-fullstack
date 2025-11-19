import { describe, expect, it } from 'vitest'
import { api } from '@/services/api'

describe('API Service', () => {
  describe('api instance', () => {
    it('should have correct baseURL configured', () => {
      expect(api.defaults.baseURL).toContain('/api/v1')
    })

    it('should have correct headers configured', () => {
      expect(api.defaults.headers['Content-Type']).toBe('application/json')
    })

    it('should have withCredentials enabled for cookie auth', () => {
      expect(api.defaults.withCredentials).toBe(true)
    })
  })

  describe('interceptors', () => {
    it('should have response interceptor configured', () => {
      expect(api.interceptors.response).toBeDefined()
    })
  })
})
