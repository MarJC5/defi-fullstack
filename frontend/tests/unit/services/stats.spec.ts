import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/services/api'
import { statsService } from '@/services/stats'

vi.mock('@/services/api', () => ({
  api: {
    get: vi.fn(),
  },
}))

describe('statsService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getDistances', () => {
    it('calls api.get with correct endpoint without params', async () => {
      const mockResponse = {
        from: null,
        to: null,
        groupBy: 'none',
        items: [],
      }
      vi.mocked(api.get).mockResolvedValue(mockResponse)

      const result = await statsService.getDistances()

      expect(api.get).toHaveBeenCalledWith('/stats/distances')
      expect(result).toEqual(mockResponse)
    })

    it('calls api.get with query params when provided', async () => {
      const mockResponse = {
        from: '2025-01-01',
        to: '2025-01-31',
        groupBy: 'day',
        items: [
          { analyticCode: 'ANA-001', totalDistanceKm: 100.5, group: '2025-01-01' },
        ],
      }
      vi.mocked(api.get).mockResolvedValue(mockResponse)

      const result = await statsService.getDistances({
        from: '2025-01-01',
        to: '2025-01-31',
        groupBy: 'day',
      })

      expect(api.get).toHaveBeenCalledWith(
        '/stats/distances?from=2025-01-01&to=2025-01-31&groupBy=day',
      )
      expect(result).toEqual(mockResponse)
    })

    it('calls api.get with partial params', async () => {
      const mockResponse = {
        from: null,
        to: null,
        groupBy: 'month',
        items: [],
      }
      vi.mocked(api.get).mockResolvedValue(mockResponse)

      await statsService.getDistances({ groupBy: 'month' })

      expect(api.get).toHaveBeenCalledWith('/stats/distances?groupBy=month')
    })

    it('throws error on API failure', async () => {
      const error = { message: 'Server error', code: '500' }
      vi.mocked(api.get).mockRejectedValue(error)

      await expect(statsService.getDistances()).rejects.toEqual(error)
    })
  })
})
