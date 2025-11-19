import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import RouteForm from '@/components/RouteForm.vue'

const vuetify = createVuetify({
  components,
  directives,
})

describe('RouteForm', () => {
  const mountComponent = (props = {}) => {
    return mount(RouteForm, {
      props,
      global: {
        plugins: [vuetify],
      },
    })
  }

  describe('rendering', () => {
    it('renders from station input', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="from-station"]').exists()).toBe(true)
    })

    it('renders to station input', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="to-station"]').exists()).toBe(true)
    })

    it('renders analytic code input', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="analytic-code"]').exists()).toBe(true)
    })

    it('renders submit button', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="submit-btn"]').exists()).toBe(true)
    })
  })

  describe('form submission', () => {
    it('emits submit event with form data', async () => {
      const wrapper = mountComponent()

      const fromInput = wrapper.find('[data-testid="from-station"] input')
      const toInput = wrapper.find('[data-testid="to-station"] input')
      const analyticInput = wrapper.find('[data-testid="analytic-code"] input')

      await fromInput.setValue('MX')
      await toInput.setValue('CGE')
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
      const submitBtn = wrapper.find('[data-testid="submit-btn"]')
      expect(submitBtn.classes()).toContain('v-btn--loading')
    })

    it('disables submit button when loading', () => {
      const wrapper = mountComponent({ loading: true })
      const submitBtn = wrapper.find('[data-testid="submit-btn"]')
      expect(submitBtn.attributes('disabled')).toBeDefined()
    })
  })
})
