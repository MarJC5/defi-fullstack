import type { ApiError } from '@/types/api'
import axios, { type AxiosError, type AxiosInstance } from 'axios'

// Token storage
let authToken: string | null = null

// Create axios instance
export const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
  },
})

// Token management
export function setAuthToken (token: string): void {
  authToken = token
}

export function clearAuthToken (): void {
  authToken = null
}

export function getAuthToken (): string | null {
  return authToken
}

// Request interceptor - add auth token
api.interceptors.request.use(
  config => {
    if (authToken) {
      config.headers.Authorization = `Bearer ${authToken}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  },
)

// Response interceptor - handle errors
api.interceptors.response.use(
  response => {
    return response.data
  },
  (error: AxiosError<ApiError>) => {
    if (error.response?.status === 401) {
      clearAuthToken()
      // Optionally redirect to login
      window.location.href = '/login'
    }

    const apiError: ApiError = {
      message: error.response?.data?.message || error.message || 'An error occurred',
      details: error.response?.data?.details,
      code: error.response?.data?.code || String(error.response?.status),
    }

    return Promise.reject(apiError)
  },
)
