/**
 * API Types - Generated from OpenAPI spec (docs/openapi.yml)
 */

// Request types
export interface RouteRequest {
  fromStationId: string
  toStationId: string
  analyticCode: string
}

// Response types
export interface Route {
  id: string
  fromStationId: string
  toStationId: string
  analyticCode: string
  distanceKm: number
  path: string[]
  createdAt: string
}

export interface AnalyticDistance {
  analyticCode: string
  totalDistanceKm: number
  periodStart?: string
  periodEnd?: string
  group?: string
}

export interface AnalyticDistanceList {
  data: AnalyticDistance[]
}

// Error types
export interface ApiError {
  message: string
  details?: string[]
  code?: string
}

export interface ValidationError {
  message: string
  details: string[]
}

// Query parameters
export interface StatsQueryParams {
  from?: string
  to?: string
  groupBy?: 'day' | 'month' | 'year'
}

// Health check
export interface HealthStatus {
  status: 'OK' | 'DEGRADED'
  timestamp: string
  service: string
  database: string
}
