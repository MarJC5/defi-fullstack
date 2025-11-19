import type { Route } from '@/types/api'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import RouteResult from '@/components/RouteResult.vue'

describe('RouteResult', () => {
  const mockRoute: Route = {
    id: '123',
    fromStationId: 'MX',
    toStationId: 'CGE',
    analyticCode: 'TEST-001',
    distanceKm: 15.5,
    path: ['MX', 'ABC', 'CGE'],
    createdAt: '2025-01-01T00:00:00Z',
  }

  const mountComponent = (props?: { route: Route }) => {
    return mount(RouteResult, {
      props: props ?? { route: mockRoute },
    })
  }

  describe('rendering', () => {
    it('renders distance', () => {
      const wrapper = mountComponent()
      expect(wrapper.text()).toContain('15.5')
      expect(wrapper.text()).toContain('km')
    })

    it('renders analytic code', () => {
      const wrapper = mountComponent()
      expect(wrapper.text()).toContain('TEST-001')
    })

    it('renders path stations', () => {
      const wrapper = mountComponent()
      expect(wrapper.text()).toContain('MX')
      expect(wrapper.text()).toContain('ABC')
      expect(wrapper.text()).toContain('CGE')
    })

    it('renders from and to stations', () => {
      const wrapper = mountComponent()
      expect(wrapper.text()).toContain('MX')
      expect(wrapper.text()).toContain('CGE')
    })
  })

  describe('data-testid', () => {
    it('has distance element with testid', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="result-distance"]').exists()).toBe(true)
    })

    it('has analytic code element with testid', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="result-analytic-code"]').exists()).toBe(true)
    })

    it('has path element with testid', () => {
      const wrapper = mountComponent()
      expect(wrapper.find('[data-testid="result-path"]').exists()).toBe(true)
    })
  })
})
