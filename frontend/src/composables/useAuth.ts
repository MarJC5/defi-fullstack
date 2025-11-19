import type { LoginCredentials } from '@/services/auth'
import { ref } from 'vue'
import { api } from '@/services/api'
import { authService } from '@/services/auth'

// Global auth state
const isAuthenticated = ref(false)
const authError = ref<string | null>(null)
const isLoading = ref(false)
const isCheckingAuth = ref(false)

export function useAuth () {
  const login = async (credentials: LoginCredentials) => {
    isLoading.value = true
    authError.value = null

    try {
      await authService.login(credentials)
      isAuthenticated.value = true
    } catch (error: unknown) {
      const errorObj = error as { message?: string }
      authError.value = errorObj.message || 'Login failed'
      isAuthenticated.value = false
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    try {
      await authService.logout()
    } finally {
      isAuthenticated.value = false
    }
  }

  const checkAuth = async () => {
    isCheckingAuth.value = true
    try {
      // Try to access a protected endpoint to verify auth status
      await api.get('/auth/me')
      isAuthenticated.value = true
    } catch {
      isAuthenticated.value = false
    } finally {
      isCheckingAuth.value = false
    }
  }

  return {
    isAuthenticated,
    authError,
    isLoading,
    isCheckingAuth,
    login,
    logout,
    checkAuth,
  }
}
