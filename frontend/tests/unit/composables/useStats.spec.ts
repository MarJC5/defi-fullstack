import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useStats } from '@/composables/useStats'
import { statsService } from '@/services/stats'

vi.mock('@/services/stats', () => ({
  statsService: {
    getDistances: vi.fn(),
  },
}))

describe('useStats', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('loadStats', () => {
    it('sets stats on successful load', async () => {
      const mockResponse = {
        from: null,
        to: null,
        groupBy: 'none' as const,
        items: [
          { analyticCode: 'ANA-001', totalDistanceKm: 100.5 },
        ],
      }
      vi.mocked(statsService.getDistances).mockResolvedValue(mockResponse)

      const { loadStats, stats, loading, error } = useStats()

      await loadStats()

      expect(statsService.getDistances).toHaveBeenCalledWith({})
      expect(stats.value).toEqual(mockResponse)
      expect(loading.value).toBe(false)
      expect(error.value).toBeNull()
    })

    it('passes params to service', async () => {
      const mockResponse = {
        from: '2025-01-01',
        to: '2025-01-31',
        groupBy: 'day' as const,
        items: [],
      }
      vi.mocked(statsService.getDistances).mockResolvedValue(mockResponse)

      const { loadStats } = useStats()

      await loadStats({
        from: '2025-01-01',
        to: '2025-01-31',
        groupBy: 'day',
      })

      expect(statsService.getDistances).toHaveBeenCalledWith({
        from: '2025-01-01',
        to: '2025-01-31',
        groupBy: 'day',
      })
    })

    it('sets error on failure', async () => {
      vi.mocked(statsService.getDistances).mockRejectedValue({
        message: 'Server error',
      })

      const { loadStats, stats, error } = useStats()

      await loadStats()

      expect(stats.value).toBeNull()
      expect(error.value).toBe('Server error')
    })

    it('sets loading during request', async () => {
      let resolvePromise: (value: unknown) => void
      const promise = new Promise(resolve => {
        resolvePromise = resolve
      })
      vi.mocked(statsService.getDistances).mockReturnValue(promise as Promise<never>)

      const { loadStats, loading } = useStats()

      const loadPromise = loadStats()
      expect(loading.value).toBe(true)

      resolvePromise!({
        from: null,
        to: null,
        groupBy: 'none',
        items: [],
      })
      await loadPromise

      expect(loading.value).toBe(false)
    })
  })

  describe('getChartData', () => {
    it('returns empty arrays when no stats', () => {
      const { getChartData } = useStats()

      const result = getChartData()

      expect(result).toEqual({ labels: [], values: [] })
    })

    it('returns labels and values from stats', async () => {
      const mockResponse = {
        from: null,
        to: null,
        groupBy: 'none' as const,
        items: [
          { analyticCode: 'ANA-001', totalDistanceKm: 100.5 },
          { analyticCode: 'ANA-002', totalDistanceKm: 200 },
        ],
      }
      vi.mocked(statsService.getDistances).mockResolvedValue(mockResponse)

      const { loadStats, getChartData } = useStats()
      await loadStats()

      const result = getChartData()

      expect(result.labels).toEqual(['ANA-001', 'ANA-002'])
      expect(result.values).toEqual([100.5, 200])
    })

    it('includes group in label when present', async () => {
      const mockResponse = {
        from: null,
        to: null,
        groupBy: 'month' as const,
        items: [
          { analyticCode: 'ANA-001', totalDistanceKm: 100.5, group: '2025-01' },
        ],
      }
      vi.mocked(statsService.getDistances).mockResolvedValue(mockResponse)

      const { loadStats, getChartData } = useStats()
      await loadStats()

      const result = getChartData()

      expect(result.labels).toEqual(['ANA-001 (2025-01)'])
    })
  })
})
