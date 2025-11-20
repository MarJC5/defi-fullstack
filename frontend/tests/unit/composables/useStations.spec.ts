import type { Station } from '@/types/api'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent } from 'vue'
import { useStations } from '@/composables/useStations'

// Mock fetch globally
const mockFetch = vi.fn()
global.fetch = mockFetch

describe('useStations', () => {
  const mockStations: Station[] = [
    { id: 1, shortName: 'MX', longName: 'Montreux' },
    { id: 2, shortName: 'CGE', longName: 'Montreux-Collège' },
    { id: 3, shortName: 'VV', longName: 'Vevey' },
  ]

  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('initial state', () => {
    it('should have loading as false initially before mount', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStations,
      })

      const TestComponent = defineComponent({
        setup () {
          const { loading } = useStations()
          return { loading }
        },
        template: '<div>{{ loading }}</div>',
      })

      const wrapper = mount(TestComponent)
      await flushPromises()

      // After mount completes, loading should be false
      expect(wrapper.vm.loading).toBe(false)
    })

    it('should have stations as empty array initially', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStations,
      })

      const TestComponent = defineComponent({
        setup () {
          const { stations } = useStations()
          return { stations }
        },
        template: '<div></div>',
      })

      const wrapper = mount(TestComponent)

      // Before fetch completes, stations should be empty
      expect(wrapper.vm.stations).toEqual([])

      await flushPromises()

      // After fetch completes, stations should be loaded
      expect(wrapper.vm.stations).toEqual(mockStations)
    })

    it('should have error as null initially', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStations,
      })

      const TestComponent = defineComponent({
        setup () {
          const { error } = useStations()
          return { error }
        },
        template: '<div></div>',
      })

      const wrapper = mount(TestComponent)
      expect(wrapper.vm.error).toBeNull()

      await flushPromises()

      expect(wrapper.vm.error).toBeNull()
    })
  })

  describe('loadStations', () => {
    it('should load stations on mount', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStations,
      })

      const TestComponent = defineComponent({
        setup () {
          const { stations } = useStations()
          return { stations }
        },
        template: '<div></div>',
      })

      mount(TestComponent)
      await flushPromises()

      expect(mockFetch).toHaveBeenCalledWith('/data/stations.json')
    })

    it('should set loading to true during fetch', async () => {
      let resolvePromise: (value: unknown) => void
      const promise = new Promise(resolve => {
        resolvePromise = resolve
      })

      mockFetch.mockReturnValueOnce(promise)

      const TestComponent = defineComponent({
        setup () {
          const { stations, loading } = useStations()
          return { stations, loading }
        },
        template: '<div>{{ loading }}</div>',
      })

      const wrapper = mount(TestComponent)
      await flushPromises()

      // Should be loading while fetch is pending
      expect(wrapper.vm.loading).toBe(true)

      // Resolve the promise
      resolvePromise!({
        ok: true,
        json: async () => mockStations,
      })
      await flushPromises()

      // Should not be loading after fetch completes
      expect(wrapper.vm.loading).toBe(false)
    })

    it('should set stations on successful fetch', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStations,
      })

      const TestComponent = defineComponent({
        setup () {
          const { stations } = useStations()
          return { stations }
        },
        template: '<div></div>',
      })

      const wrapper = mount(TestComponent)
      await flushPromises()

      expect(wrapper.vm.stations).toEqual(mockStations)
    })

    it('should set error on failed fetch', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
      })

      const TestComponent = defineComponent({
        setup () {
          const { stations, error } = useStations()
          return { stations, error }
        },
        template: '<div></div>',
      })

      const wrapper = mount(TestComponent)
      await flushPromises()

      expect(wrapper.vm.error).toBe('Failed to load stations')
      expect(wrapper.vm.stations).toEqual([])
    })

    it('should handle network errors', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      const TestComponent = defineComponent({
        setup () {
          const { stations, error } = useStations()
          return { stations, error }
        },
        template: '<div></div>',
      })

      const wrapper = mount(TestComponent)
      await flushPromises()

      expect(wrapper.vm.error).toBe('Network error')
      expect(wrapper.vm.stations).toEqual([])
    })

    it('should be able to manually reload stations', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockStations,
      })

      const TestComponent = defineComponent({
        setup () {
          const { stations, loadStations } = useStations()
          return { stations, loadStations }
        },
        template: '<div></div>',
      })

      const wrapper = mount(TestComponent)
      await flushPromises()

      expect(mockFetch).toHaveBeenCalledTimes(1)

      const newStations: Station[] = [
        { id: 4, shortName: 'ZW', longName: 'Zweisimmen' },
      ]

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => newStations,
      })

      await wrapper.vm.loadStations()
      await flushPromises()

      expect(mockFetch).toHaveBeenCalledTimes(2)
      expect(wrapper.vm.stations).toEqual(newStations)
    })
  })
})
