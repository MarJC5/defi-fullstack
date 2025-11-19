import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import LoginForm from '@/components/LoginForm.vue'
import { useAuth } from '@/composables/useAuth'

vi.mock('@/composables/useAuth', () => ({
  useAuth: vi.fn(),
}))

const vuetify = createVuetify({ components, directives })

describe('LoginForm', () => {
  const mockLogin = vi.fn()
  let mockAuthError: ReturnType<typeof ref<string | null>>
  let mockIsLoading: ReturnType<typeof ref<boolean>>

  beforeEach(() => {
    vi.clearAllMocks()
    mockAuthError = ref(null)
    mockIsLoading = ref(false)

    vi.mocked(useAuth).mockReturnValue({
      isAuthenticated: ref(false),
      authError: mockAuthError,
      isLoading: mockIsLoading,
      isCheckingAuth: ref(false),
      login: mockLogin,
      logout: vi.fn(),
      checkAuth: vi.fn(),
    } as unknown as ReturnType<typeof useAuth>)
  })

  const mountComponent = () => {
    return mount(LoginForm, {
      global: {
        plugins: [vuetify],
      },
    })
  }

  it('renders login form with username and password fields', () => {
    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="input-username"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="input-password"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="btn-login"]').exists()).toBe(true)
  })

  it('calls login with form data on submit', async () => {
    const wrapper = mountComponent()

    const usernameInput = wrapper.find('[data-testid="input-username"] input')
    const passwordInput = wrapper.find('[data-testid="input-password"] input')

    await usernameInput.setValue('api_user')
    await passwordInput.setValue('api_password')

    await wrapper.find('form').trigger('submit')

    expect(mockLogin).toHaveBeenCalledWith({
      username: 'api_user',
      password: 'api_password',
    })
  })

  it('displays error message when authError is set', async () => {
    mockAuthError.value = 'Invalid credentials'

    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="login-error"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Invalid credentials')
  })

  it('disables login button when loading', () => {
    mockIsLoading.value = true

    const wrapper = mountComponent()

    const button = wrapper.find('[data-testid="btn-login"]')
    expect(button.attributes('disabled')).toBeDefined()
  })

  it('does not display error when authError is null', () => {
    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="login-error"]').exists()).toBe(false)
  })
})
