import type { Route, RouteRequest } from '@/types/api'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useRoutes } from '@/composables/useRoutes'
import { routeService } from '@/services/route'

vi.mock('@/services/route', () => ({
  routeService: {
    calculateRoute: vi.fn(),
  },
}))

describe('useRoutes', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('should have loading as false', () => {
      const { loading } = useRoutes()
      expect(loading.value).toBe(false)
    })

    it('should have error as null', () => {
      const { error } = useRoutes()
      expect(error.value).toBeNull()
    })

    it('should have route as null', () => {
      const { route } = useRoutes()
      expect(route.value).toBeNull()
    })
  })

  describe('calculateRoute', () => {
    it('should set loading to true during calculation', async () => {
      const { loading, calculateRoute } = useRoutes()

      const mockRoute: Route = {
        id: '123',
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
        distanceKm: 15.5,
        path: ['MX', 'ABC', 'CGE'],
        createdAt: '2025-01-01T00:00:00Z',
      }

      vi.mocked(routeService.calculateRoute).mockImplementation(async () => {
        expect(loading.value).toBe(true)
        return mockRoute
      })

      const request: RouteRequest = {
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
      }

      await calculateRoute(request)
      expect(loading.value).toBe(false)
    })

    it('should set route on success', async () => {
      const { route, calculateRoute } = useRoutes()

      const mockRoute: Route = {
        id: '123',
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
        distanceKm: 15.5,
        path: ['MX', 'ABC', 'CGE'],
        createdAt: '2025-01-01T00:00:00Z',
      }

      vi.mocked(routeService.calculateRoute).mockResolvedValue(mockRoute)

      const request: RouteRequest = {
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
      }

      await calculateRoute(request)
      expect(route.value).toEqual(mockRoute)
    })

    it('should set error on failure', async () => {
      const { error, route, calculateRoute } = useRoutes()

      vi.mocked(routeService.calculateRoute).mockRejectedValue({
        message: 'Station not found',
      })

      const request: RouteRequest = {
        fromStationId: 'INVALID',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
      }

      await calculateRoute(request)
      expect(error.value).toBe('Station not found')
      expect(route.value).toBeNull()
    })

    it('should clear previous error on new calculation', async () => {
      const { error, calculateRoute } = useRoutes()

      vi.mocked(routeService.calculateRoute).mockRejectedValueOnce({
        message: 'First error',
      })

      await calculateRoute({
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
      })

      expect(error.value).toBe('First error')

      const mockRoute: Route = {
        id: '123',
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
        distanceKm: 15.5,
        path: ['MX', 'ABC', 'CGE'],
        createdAt: '2025-01-01T00:00:00Z',
      }

      vi.mocked(routeService.calculateRoute).mockResolvedValueOnce(mockRoute)

      await calculateRoute({
        fromStationId: 'MX',
        toStationId: 'CGE',
        analyticCode: 'TEST-001',
      })

      expect(error.value).toBeNull()
    })
  })
})
