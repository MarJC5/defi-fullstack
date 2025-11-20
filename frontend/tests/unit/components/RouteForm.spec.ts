import type { Station } from '@/types/api'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import RouteForm from '@/components/RouteForm.vue'

// Mock the useStations composable
vi.mock('@/composables/useStations', () => ({
  useStations: vi.fn(),
}))

describe('RouteForm', () => {
  const mockStations: Station[] = [
    { id: 1, shortName: 'MX', longName: 'Montreux' },
    { id: 2, shortName: 'CGE', longName: 'Montreux-Collège' },
    { id: 3, shortName: 'VV', longName: 'Vevey' },
  ]

  beforeEach(async () => {
    const { useStations } = await import('@/composables/useStations')
    vi.mocked(useStations).mockReturnValue({
      stations: ref(mockStations),
      loading: ref(false),
      error: ref(null),
      loadStations: vi.fn(),
    })
  })

  const mountComponent = (props = {}) => {
    return mount(RouteForm, {
      props,
    })
  }

  describe('rendering', () => {
    it('renders from station input', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="input-from-station-id"]').exists()).toBe(true)
    })

    it('renders to station input', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="input-to-station-id"]').exists()).toBe(true)
    })

    it('renders analytic code input', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="input-analytic-code"]').exists()).toBe(true)
    })

    it('renders submit button', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="btn-submit"]').exists()).toBe(true)
    })
  })

  describe('form submission', () => {
    it('emits submit event with form data', async () => {
      const wrapper = mountComponent()

      // Set values directly on the component's reactive form
      // This simulates user selection from autocomplete
      wrapper.vm.form.fromStationId = 'MX'
      wrapper.vm.form.toStationId = 'CGE'

      const analyticInput = wrapper.find('[data-testid="input-analytic-code"] input')
      await analyticInput.setValue('TEST-001')

      await wrapper.find('form').trigger('submit')

      expect(wrapper.emitted('submit')).toBeTruthy()
      expect(wrapper.emitted('submit')![0]).toEqual([
        {
          fromStationId: 'MX',
          toStationId: 'CGE',
          analyticCode: 'TEST-001',
        },
      ])
    })
  })

  describe('loading state', () => {
    it('shows loading state on submit button when loading prop is true', () => {
      const wrapper = mountComponent({ loading: true })
      const submitBtn = wrapper.find('[data-testid="btn-submit"]')
      expect(submitBtn.classes()).toContain('v-btn--loading')
    })

    it('disables submit button when loading', () => {
      const wrapper = mountComponent({ loading: true })
      const submitBtn = wrapper.find('[data-testid="btn-submit"]')
      expect(submitBtn.attributes('disabled')).toBeDefined()
    })
  })
})
