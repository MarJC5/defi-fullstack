import { fileURLToPath } from 'node:url'
import { configDefaults, defineConfig, mergeConfig } from 'vitest/config'
import viteConfig from './vite.config.mts'

export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      environment: 'jsdom',
      exclude: [...configDefaults.exclude, 'e2e/**'],
      root: fileURLToPath(new URL('./', import.meta.url)),
      setupFiles: ['./tests/setup.ts'],
      server: {
        deps: {
          inline: ['vuetify'],
        },
      },
      css: true,
      coverage: {
        provider: 'v8',
        reporter: ['text', 'html', 'clover'],
        reportsDirectory: './coverage',
        exclude: [
          'node_modules/',
          'tests/',
          '**/*.d.ts',
          '**/*.config.*',
          '**/main.ts',
          '**/plugins/**',
          '**/router/**',
          '**/stores/**',
          '**/layouts/**',
          '**/App.vue',
          '**/types/**',
          '**/pages/**',
          '**/HealthCheck.vue',
          '**/AppFooter.vue',
        ],
        thresholds: {
          lines: 70,
          functions: 70,
          branches: 70,
          statements: 70,
        },
      },
    },
  }),
)
