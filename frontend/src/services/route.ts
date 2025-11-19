import type { AnalyticDistance, HealthStatus, Route, RouteRequest, StatsQueryParams } from '@/types/api'
import { api } from '@/services/api'

export const routeService = {
  async calculateRoute (request: RouteRequest): Promise<Route> {
    return api.post('/routes', request) as Promise<Route>
  },

  async getStats (params: StatsQueryParams = {}): Promise<AnalyticDistance[]> {
    const response = await api.get('/stats', { params }) as { data: AnalyticDistance[] }
    return response.data
  },

  async getHealth (): Promise<HealthStatus> {
    return api.get('/health') as Promise<HealthStatus>
  },
}
