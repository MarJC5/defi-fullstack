import type { Station } from '@/types/api'
import { onMounted, ref } from 'vue'

export function useStations () {
  const stations = ref<Station[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const loadStations = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/data/stations.json')
      if (!response.ok) {
        throw new Error('Failed to load stations')
      }
      stations.value = await response.json()
    } catch (error_: unknown) {
      const errorObj = error_ as { message?: string }
      error.value = errorObj.message || 'Failed to load stations'
      stations.value = []
    } finally {
      loading.value = false
    }
  }

  // Auto-load stations when composable is used
  onMounted(() => {
    loadStations()
  })

  return {
    stations,
    loading,
    error,
    loadStations,
  }
}
