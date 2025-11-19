import type { AnalyticDistance, Route, RouteRequest, StatsQueryParams } from '@/types/api'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/services/api'
import { routeService } from '@/services/route'

vi.mock('@/services/api', () => ({
  api: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

describe('Route Service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('calculateRoute', () => {
    it('should call POST /routes with route request', async () => {
      const request: RouteRequest = {
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
      }

      const mockRoute: Route = {
        id: '123',
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
        distanceKm: 15.5,
        path: ['MX', 'ABC', 'CGE'],
        createdAt: '2025-01-01T00:00:00Z',
      }

      vi.mocked(api.post).mockResolvedValue(mockRoute)

      const result = await routeService.calculateRoute(request)

      expect(api.post).toHaveBeenCalledWith('/routes', request)
      expect(result).toEqual(mockRoute)
    })

    it('should throw error when API call fails', async () => {
      const request: RouteRequest = {
        fromStationId: 'MX',
        toStationId: 'INVALID',
        analyticCode: 'TEST-001',
      }

      vi.mocked(api.post).mockRejectedValue({
        message: 'Station not found',
        code: '404',
      })

      await expect(routeService.calculateRoute(request)).rejects.toMatchObject({
        message: 'Station not found',
      })
    })
  })

  describe('getStats', () => {
    it('should call GET /stats without params', async () => {
      const mockStats: AnalyticDistance[] = [
        { analyticCode: 'TEST-001', totalDistanceKm: 100 },
        { analyticCode: 'TEST-002', totalDistanceKm: 200 },
      ]

      vi.mocked(api.get).mockResolvedValue({ data: mockStats })

      const result = await routeService.getStats()

      expect(api.get).toHaveBeenCalledWith('/stats', { params: {} })
      expect(result).toEqual(mockStats)
    })

    it('should call GET /stats with query params', async () => {
      const params: StatsQueryParams = {
        from: '2025-01-01',
        to: '2025-01-31',
        groupBy: 'day',
      }

      const mockStats: AnalyticDistance[] = [
        { analyticCode: 'TEST-001', totalDistanceKm: 50, periodStart: '2025-01-01', periodEnd: '2025-01-31', group: 'day' },
      ]

      vi.mocked(api.get).mockResolvedValue({ data: mockStats })

      const result = await routeService.getStats(params)

      expect(api.get).toHaveBeenCalledWith('/stats', { params })
      expect(result).toEqual(mockStats)
    })
  })

  describe('getHealth', () => {
    it('should call GET /health', async () => {
      const mockHealth = {
        status: 'OK' as const,
        timestamp: '2025-01-01T00:00:00Z',
        service: 'api',
        database: 'connected',
      }

      vi.mocked(api.get).mockResolvedValue(mockHealth)

      const result = await routeService.getHealth()

      expect(api.get).toHaveBeenCalledWith('/health')
      expect(result).toEqual(mockHealth)
    })
  })
})
