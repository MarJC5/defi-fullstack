import type { Route, RouteRequest } from '@/types/api'
import { ref } from 'vue'
import { routeService } from '@/services/route'

export function useRoutes () {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const route = ref<Route | null>(null)

  const calculateRoute = async (request: RouteRequest) => {
    loading.value = true
    error.value = null

    try {
      route.value = await routeService.calculateRoute(request)
    } catch (error_: unknown) {
      const errorObj = error_ as { message?: string }
      error.value = errorObj.message || 'Failed to calculate route'
      route.value = null
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    route,
    calculateRoute,
  }
}
