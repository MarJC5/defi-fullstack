import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import StatsChart from '@/components/StatsChart.vue'
import { useStats } from '@/composables/useStats'

vi.mock('@/composables/useStats', () => ({
  useStats: vi.fn(),
}))

describe('StatsChart', () => {
  const mockLoadStats = vi.fn()
  const mockGetChartData = vi.fn()

  const createMockUseStats = (overrides: Record<string, unknown> = {}) => ({
    stats: ref(null),
    loading: ref(false),
    error: ref(null),
    loadStats: mockLoadStats,
    getChartData: mockGetChartData,
    ...overrides,
  })

  beforeEach(() => {
    vi.clearAllMocks()
    mockGetChartData.mockReturnValue({ labels: [], values: [] })
    vi.mocked(useStats).mockReturnValue(createMockUseStats())
  })

  const mountComponent = () => {
    return mount(StatsChart)
  }

  describe('rendering', () => {
    it('renders component title', () => {
      const wrapper = mountComponent()
      expect(wrapper.text()).toContain('Distance Statistics')
    })

    it('renders date filter inputs', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="input-from-date"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="input-to-date"]').exists()).toBe(true)
    })

    it('renders group by select', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="select-group-by"]').exists()).toBe(true)
    })

    it('renders load button', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="btn-load-stats"]').exists()).toBe(true)
    })
  })

  describe('loading state', () => {
    it('shows loading indicator when loading', () => {
      vi.mocked(useStats).mockReturnValue(createMockUseStats({
        loading: ref(true),
      }))

      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="loading-indicator"]').exists()).toBe(true)
    })

    it('disables load button when loading', () => {
      vi.mocked(useStats).mockReturnValue(createMockUseStats({
        loading: ref(true),
      }))

      const wrapper = mountComponent()
      const loadBtn = wrapper.find('[data-testid="btn-load-stats"]')
      expect(loadBtn.attributes('disabled')).toBeDefined()
    })
  })

  describe('error state', () => {
    it('displays error message when error occurs', () => {
      vi.mocked(useStats).mockReturnValue(createMockUseStats({
        error: ref('Failed to load stats'),
      }))

      const wrapper = mountComponent()
      expect(wrapper.text()).toContain('Failed to load stats')
    })
  })

  describe('stats display', () => {
    it('renders sparkline chart when stats loaded', () => {
      mockGetChartData.mockReturnValue({
        labels: ['ANA-001', 'ANA-002'],
        values: [100, 200],
      })

      vi.mocked(useStats).mockReturnValue(createMockUseStats({
        stats: ref({
          from: null,
          to: null,
          groupBy: 'none',
          items: [
            { analyticCode: 'ANA-001', totalDistanceKm: 100 },
            { analyticCode: 'ANA-002', totalDistanceKm: 200 },
          ],
        }),
      }))

      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="stats-chart"]').exists()).toBe(true)
    })

    it('renders stats table with data', () => {
      vi.mocked(useStats).mockReturnValue(createMockUseStats({
        stats: ref({
          from: null,
          to: null,
          groupBy: 'none',
          items: [
            { analyticCode: 'ANA-001', totalDistanceKm: 100.5 },
          ],
        }),
      }))

      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="stats-table"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('ANA-001')
      expect(wrapper.text()).toContain('100.5')
    })
  })

  describe('data loading', () => {
    it('calls loadStats on mount', () => {
      mountComponent()
      expect(mockLoadStats).toHaveBeenCalledWith({})
    })

    it('calls loadStats with params when load button clicked', async () => {
      const wrapper = mountComponent()

      await wrapper.find('[data-testid="btn-load-stats"]').trigger('click')

      expect(mockLoadStats).toHaveBeenCalled()
    })
  })
})
