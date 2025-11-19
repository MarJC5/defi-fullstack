import { config } from '@vue/test-utils'
import { afterEach, beforeAll, vi } from 'vitest'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

// Create Vuetify instance for tests
const vuetify = createVuetify({
  components,
  directives,
})

// Global plugins for all tests
config.global.plugins = [vuetify]

// Mock ResizeObserver (not available in jsdom)
beforeAll(() => {
  global.ResizeObserver = vi.fn().mockImplementation(() => ({
    observe: vi.fn(),
    unobserve: vi.fn(),
    disconnect: vi.fn(),
  }))
})

// Clear all mocks after each test
afterEach(() => {
  vi.clearAllMocks()
})
