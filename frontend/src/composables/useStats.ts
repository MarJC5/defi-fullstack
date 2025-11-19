import { ref } from 'vue'
import { type StatsParams, type StatsResponse, statsService } from '@/services/stats'

export function useStats () {
  const stats = ref<StatsResponse | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function loadStats (params: StatsParams = {}) {
    loading.value = true
    error.value = null

    try {
      stats.value = await statsService.getDistances(params)
    } catch (error_: unknown) {
      stats.value = null
      error.value = (error_ as { message: string }).message
    } finally {
      loading.value = false
    }
  }

  function getChartData () {
    if (!stats.value) {
      return { labels: [], values: [] }
    }

    const labels = stats.value.items.map(item => {
      if (item.group) {
        return `${item.analyticCode} (${item.group})`
      }
      return item.analyticCode
    })

    const values = stats.value.items.map(item => item.totalDistanceKm)

    return { labels, values }
  }

  return {
    stats,
    loading,
    error,
    loadStats,
    getChartData,
  }
}
