import { api } from '@/services/api'

export interface LoginCredentials {
  username: string
  password: string
}

export interface LoginResponse {
  message: string
}

export const authService = {
  async login (credentials: LoginCredentials): Promise<LoginResponse> {
    return api.post('/auth/login', credentials) as Promise<LoginResponse>
  },

  async logout (): Promise<void> {
    await api.post('/auth/logout')
  },
}
