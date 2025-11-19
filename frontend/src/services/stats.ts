import { api } from '@/services/api'

export interface AnalyticDistance {
  analyticCode: string
  totalDistanceKm: number
  group?: string
}

export interface StatsResponse {
  from: string | null
  to: string | null
  groupBy: 'day' | 'month' | 'year' | 'none'
  items: AnalyticDistance[]
}

export interface StatsParams {
  from?: string
  to?: string
  groupBy?: 'day' | 'month' | 'year' | 'none'
}

export const statsService = {
  async getDistances (params: StatsParams = {}): Promise<StatsResponse> {
    const queryParams = new URLSearchParams()

    if (params.from) queryParams.append('from', params.from)
    if (params.to) queryParams.append('to', params.to)
    if (params.groupBy) queryParams.append('groupBy', params.groupBy)

    const queryString = queryParams.toString()
    const url = queryString ? `/stats/distances?${queryString}` : '/stats/distances'

    return api.get(url) as Promise<StatsResponse>
  },
}
