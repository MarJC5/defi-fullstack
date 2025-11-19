import type { ApiError } from '@/types/api'
import axios, { type AxiosError, type AxiosInstance } from 'axios'

// Create axios instance with credentials for cookie auth
export const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // Send cookies with requests
})

// Response interceptor - handle errors
api.interceptors.response.use(
  response => {
    return response.data
  },
  (error: AxiosError<ApiError>) => {
    const apiError: ApiError = {
      message: error.response?.data?.message || error.message || 'An error occurred',
      details: error.response?.data?.details,
      code: error.response?.data?.code || String(error.response?.status),
    }

    return Promise.reject(apiError)
  },
)
