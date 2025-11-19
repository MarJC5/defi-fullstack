# Frontend - Vue.js 3 + Vuetify 3 + TypeScript 5

## Tech Stack (from README)

- **TypeScript 5** (required)
- **Vue.js 3** (their production framework)
- **Vuetify 3** (their UI framework)
- **Vitest** (recommended) or Jest
- **ESLint + Prettier** (linting)

---

## Project Setup

### Initial Setup (Official Vuetify Scaffolding)

```bash
# Create project with Vuetify CLI
npm create vuetify@latest frontend -- --preset essentials --typescript --package-manager npm

# Add additional dependencies
cd frontend
npm install axios
npm install -D tailwindcss @tailwindcss/vite vitest @vitest/coverage-v8 @vue/test-utils
```

### Directory Structure (Vuetify Scaffolding)

```
frontend/
├── src/
│   ├── assets/
│   ├── components/
│   │   ├── AppFooter.vue
│   │   ├── LoginForm.vue
│   │   ├── RouteForm.vue
│   │   ├── RouteResult.vue
│   │   └── StationSelect.vue
│   ├── layouts/
│   │   └── default.vue
│   ├── pages/              # File-based routing
│   │   ├── index.vue       # Home page
│   │   └── stats.vue       # Stats page
│   ├── plugins/
│   │   ├── index.ts
│   │   └── vuetify.ts
│   ├── router/
│   ├── composables/
│   │   ├── useAuth.ts
│   │   └── useRoutes.ts
│   ├── services/
│   │   ├── api.ts
│   │   ├── auth.ts
│   │   └── route.ts
│   ├── stores/
│   │   └── app.ts
│   ├── styles/
│   │   └── settings.scss
│   ├── App.vue
│   └── main.ts
├── tests/
│   ├── unit/
│   │   ├── components/
│   │   │   ├── RouteForm.spec.ts
│   │   │   └── StationSelect.spec.ts
│   │   └── services/
│   │       └── routeService.spec.ts
│   └── setup.ts
├── public/
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.mts
├── eslint.config.js
└── Dockerfile
```

### package.json

```json
{
  "name": "train-routing-frontend",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vue-tsc && vite build",
    "preview": "vite preview",
    "test": "vitest",
    "test:coverage": "vitest run --coverage",
    "lint": "eslint src --ext .ts,.vue",
    "lint:fix": "eslint src --ext .ts,.vue --fix",
    "format": "prettier --write src/"
  },
  "dependencies": {
    "vue": "^3.4",
    "vue-router": "^4.0",
    "vuetify": "^3.0",
    "@mdi/font": "^7.0",
    "axios": "^1.0",
    "chart.js": "^4.0",
    "vue-chartjs": "^5.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0",
    "@vue/test-utils": "^2.0",
    "typescript": "^5.0",
    "vite": "^5.0",
    "vitest": "^2.0",
    "@vitest/coverage-v8": "^2.0",
    "vue-tsc": "^2.0",
    "eslint": "^9.0",
    "eslint-plugin-vue": "^9.0",
    "@typescript-eslint/eslint-plugin": "^8.0",
    "@typescript-eslint/parser": "^8.0",
    "prettier": "^3.0",
    "jsdom": "^24.0"
  }
}
```

---

## TypeScript Types (from OpenAPI)

```typescript
// src/types/api.ts

export interface RouteRequest {
  fromStationId: string;
  toStationId: string;
  analyticCode: string;
}

export interface Route {
  id: string;
  fromStationId: string;
  toStationId: string;
  analyticCode: string;
  distanceKm: number;
  path: string[];
  createdAt: string;
}

export interface AnalyticDistance {
  analyticCode: string;
  totalDistanceKm: number;
  periodStart?: string;
  periodEnd?: string;
  group?: string;
}

export interface AnalyticDistanceList {
  from: string | null;
  to: string | null;
  groupBy: 'day' | 'month' | 'year' | 'none';
  items: AnalyticDistance[];
}

export interface ApiError {
  code?: string;
  message: string;
  details?: string[];
}

export interface Station {
  id: number;
  shortName: string;
  longName: string;
}
```

---

## TDD Workflow

### Red-Green-Refactor for Components

#### Step 1: RED - Write failing test

```typescript
// tests/unit/components/RouteForm.spec.ts
import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createVuetify } from 'vuetify';
import RouteForm from '@/components/RouteForm.vue';

const vuetify = createVuetify();

describe('RouteForm', () => {
  const mountComponent = (props = {}) => {
    return mount(RouteForm, {
      props,
      global: {
        plugins: [vuetify],
      },
    });
  };

  it('renders station selects and analytic code input', () => {
    const wrapper = mountComponent();

    expect(wrapper.find('[data-testid="from-station"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="to-station"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="analytic-code"]').exists()).toBe(true);
  });

  it('emits submit event with form data', async () => {
    const wrapper = mountComponent({
      stations: [
        { id: 1, shortName: 'MX', longName: 'Montreux' },
        { id: 2, shortName: 'ZW', longName: 'Zweisimmen' },
      ],
    });

    await wrapper.find('[data-testid="from-station"]').setValue('MX');
    await wrapper.find('[data-testid="to-station"]').setValue('ZW');
    await wrapper.find('[data-testid="analytic-code"]').setValue('TEST-001');
    await wrapper.find('form').trigger('submit');

    expect(wrapper.emitted('submit')).toBeTruthy();
    expect(wrapper.emitted('submit')![0]).toEqual([
      {
        fromStationId: 'MX',
        toStationId: 'ZW',
        analyticCode: 'TEST-001',
      },
    ]);
  });

  it('disables submit button when form is invalid', () => {
    const wrapper = mountComponent();

    const submitBtn = wrapper.find('[data-testid="submit-btn"]');
    expect(submitBtn.attributes('disabled')).toBeDefined();
  });

  it('shows validation error for empty analytic code', async () => {
    const wrapper = mountComponent();

    await wrapper.find('[data-testid="analytic-code"]').setValue('');
    await wrapper.find('[data-testid="analytic-code"]').trigger('blur');

    expect(wrapper.text()).toContain('Analytic code is required');
  });
});
```

#### Step 2: GREEN - Implement component

```vue
<!-- src/components/RouteForm.vue -->
<template>
  <v-form @submit.prevent="handleSubmit" v-model="isValid">
    <v-card>
      <v-card-title>Calculate Route</v-card-title>
      <v-card-text>
        <v-autocomplete
          v-model="form.fromStationId"
          :items="stations"
          item-title="longName"
          item-value="shortName"
          label="From Station"
          data-testid="from-station"
          :rules="[rules.required]"
        />

        <v-autocomplete
          v-model="form.toStationId"
          :items="stations"
          item-title="longName"
          item-value="shortName"
          label="To Station"
          data-testid="to-station"
          :rules="[rules.required]"
        />

        <v-text-field
          v-model="form.analyticCode"
          label="Analytic Code"
          data-testid="analytic-code"
          :rules="[rules.required]"
          placeholder="e.g., ANA-123"
        />
      </v-card-text>
      <v-card-actions>
        <v-btn
          type="submit"
          color="primary"
          :disabled="!isValid || loading"
          :loading="loading"
          data-testid="submit-btn"
        >
          Calculate
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import type { Station, RouteRequest } from '@/types/api';

interface Props {
  stations: Station[];
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  stations: () => [],
  loading: false,
});

const emit = defineEmits<{
  submit: [data: RouteRequest];
}>();

const isValid = ref(false);

const form = reactive<RouteRequest>({
  fromStationId: '',
  toStationId: '',
  analyticCode: '',
});

const rules = {
  required: (v: string) => !!v || 'This field is required',
};

const handleSubmit = () => {
  if (isValid.value) {
    emit('submit', { ...form });
  }
};
</script>
```

#### Step 3: REFACTOR

- Extract validation rules to composable
- Add loading states
- Improve accessibility

---

## Services with TDD

### API Service (httpOnly Cookie Auth)

The API service uses httpOnly cookies for JWT authentication. The browser automatically includes the cookie with each request - no manual token management needed.

```typescript
// src/services/api.ts
import type { ApiError } from '@/types/api';
import axios, { type AxiosError, type AxiosInstance } from 'axios';

// Create axios instance with credentials for cookie auth
export const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // Send cookies with requests
});

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => {
    return response.data;
  },
  (error: AxiosError<ApiError>) => {
    const apiError: ApiError = {
      message: error.response?.data?.message || error.message || 'An error occurred',
      details: error.response?.data?.details,
      code: error.response?.data?.code || String(error.response?.status),
    };

    return Promise.reject(apiError);
  }
);
```

### Auth Service

```typescript
// src/services/auth.ts
import { api } from '@/services/api';

export interface LoginCredentials {
  username: string;
  password: string;
}

export interface LoginResponse {
  message: string;
}

export const authService = {
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    return api.post('/auth/login', credentials) as Promise<LoginResponse>;
  },

  async logout(): Promise<void> {
    await api.post('/auth/logout');
  },
};
```

See [7-authentication.md](7-authentication.md) for complete authentication documentation.

### Route Service with Tests

```typescript
// tests/unit/services/routeService.spec.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { routeService } from '@/services/routeService';
import api from '@/services/api';

vi.mock('@/services/api');

describe('routeService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('calculateRoute', () => {
    it('sends POST request with correct data', async () => {
      const mockRoute = {
        id: '123',
        fromStationId: 'MX',
        toStationId: 'ZW',
        analyticCode: 'TEST-001',
        distanceKm: 62.08,
        path: ['MX', 'CGE', 'ZW'],
        createdAt: '2025-01-01T00:00:00Z',
      };

      vi.mocked(api.post).mockResolvedValue({ data: mockRoute });

      const result = await routeService.calculateRoute({
        fromStationId: 'MX',
        toStationId: 'ZW',
        analyticCode: 'TEST-001',
      });

      expect(api.post).toHaveBeenCalledWith('/routes', {
        fromStationId: 'MX',
        toStationId: 'ZW',
        analyticCode: 'TEST-001',
      });
      expect(result).toEqual(mockRoute);
    });

    it('throws error for unknown station', async () => {
      vi.mocked(api.post).mockRejectedValue({
        response: {
          status: 422,
          data: {
            code: 'STATION_NOT_FOUND',
            message: "Station 'UNKNOWN' not found",
          },
        },
      });

      await expect(
        routeService.calculateRoute({
          fromStationId: 'UNKNOWN',
          toStationId: 'ZW',
          analyticCode: 'TEST-001',
        })
      ).rejects.toThrow();
    });
  });
});

// src/services/routeService.ts
import api from './api';
import type { Route, RouteRequest } from '@/types/api';

export const routeService = {
  async calculateRoute(data: RouteRequest): Promise<Route> {
    const response = await api.post<Route>('/routes', data);
    return response.data;
  },

  async getStations(): Promise<Station[]> {
    // Load from static file or API
    const response = await fetch('/stations.json');
    return response.json();
  },
};
```

### Stats Service (Bonus)

```typescript
// src/services/statsService.ts
import api from './api';
import type { AnalyticDistanceList } from '@/types/api';

export interface StatsParams {
  from?: string;
  to?: string;
  groupBy?: 'day' | 'month' | 'year' | 'none';
}

export const statsService = {
  async getDistances(params: StatsParams = {}): Promise<AnalyticDistanceList> {
    const response = await api.get<AnalyticDistanceList>('/stats/distances', {
      params,
    });
    return response.data;
  },
};
```

---

## Composables

```typescript
// src/composables/useRoutes.ts
import { ref } from 'vue';
import { routeService } from '@/services/routeService';
import type { Route, RouteRequest, Station } from '@/types/api';

export function useRoutes() {
  const loading = ref(false);
  const error = ref<string | null>(null);
  const route = ref<Route | null>(null);
  const stations = ref<Station[]>([]);

  const loadStations = async () => {
    try {
      stations.value = await routeService.getStations();
    } catch (e) {
      error.value = 'Failed to load stations';
    }
  };

  const calculateRoute = async (data: RouteRequest) => {
    loading.value = true;
    error.value = null;

    try {
      route.value = await routeService.calculateRoute(data);
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Failed to calculate route';
      route.value = null;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    route,
    stations,
    loadStations,
    calculateRoute,
  };
}
```

---

## Views

### Home View

```vue
<!-- src/views/HomeView.vue -->
<template>
  <v-container>
    <v-row>
      <v-col cols="12" md="6">
        <RouteForm
          :stations="stations"
          :loading="loading"
          @submit="calculateRoute"
        />
      </v-col>
      <v-col cols="12" md="6">
        <RouteResult v-if="route" :route="route" />
        <v-alert v-else-if="error" type="error">
          {{ error }}
        </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import RouteForm from '@/components/RouteForm.vue';
import RouteResult from '@/components/RouteResult.vue';
import { useRoutes } from '@/composables/useRoutes';

const { loading, error, route, stations, loadStations, calculateRoute } =
  useRoutes();

onMounted(() => {
  loadStations();
});
</script>
```

### Route Result Component

```vue
<!-- src/components/RouteResult.vue -->
<template>
  <v-card>
    <v-card-title>Route Result</v-card-title>
    <v-card-text>
      <v-list>
        <v-list-item>
          <v-list-item-title>Distance</v-list-item-title>
          <v-list-item-subtitle>
            {{ route.distanceKm }} km
          </v-list-item-subtitle>
        </v-list-item>

        <v-list-item>
          <v-list-item-title>Analytic Code</v-list-item-title>
          <v-list-item-subtitle>
            {{ route.analyticCode }}
          </v-list-item-subtitle>
        </v-list-item>

        <v-list-item>
          <v-list-item-title>Path</v-list-item-title>
          <v-list-item-subtitle>
            <v-chip
              v-for="station in route.path"
              :key="station"
              size="small"
              class="mr-1 mb-1"
            >
              {{ station }}
            </v-chip>
          </v-list-item-subtitle>
        </v-list-item>
      </v-list>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import type { Route } from '@/types/api';

defineProps<{
  route: Route;
}>();
</script>
```

---

## Stats View (Bonus)

```vue
<!-- src/views/StatsView.vue -->
<template>
  <v-container>
    <v-card>
      <v-card-title>Distance Statistics</v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="filters.from"
              type="date"
              label="From Date"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field v-model="filters.to" type="date" label="To Date" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="filters.groupBy"
              :items="groupByOptions"
              label="Group By"
            />
          </v-col>
        </v-row>

        <v-btn @click="loadStats" :loading="loading">Load Stats</v-btn>

        <div v-if="stats" class="mt-4">
          <Bar :data="chartData" :options="chartOptions" />
        </div>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { statsService } from '@/services/statsService';
import type { AnalyticDistanceList } from '@/types/api';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
);

const loading = ref(false);
const stats = ref<AnalyticDistanceList | null>(null);

const filters = ref({
  from: '',
  to: '',
  groupBy: 'none' as const,
});

const groupByOptions = [
  { title: 'None', value: 'none' },
  { title: 'Day', value: 'day' },
  { title: 'Month', value: 'month' },
  { title: 'Year', value: 'year' },
];

const loadStats = async () => {
  loading.value = true;
  try {
    stats.value = await statsService.getDistances(filters.value);
  } finally {
    loading.value = false;
  }
};

const chartData = computed(() => ({
  labels: stats.value?.items.map((i) => i.analyticCode) || [],
  datasets: [
    {
      label: 'Total Distance (km)',
      data: stats.value?.items.map((i) => i.totalDistanceKm) || [],
      backgroundColor: '#1976d2',
    },
  ],
}));

const chartOptions = {
  responsive: true,
  plugins: {
    legend: { position: 'top' as const },
    title: { display: true, text: 'Distances by Analytic Code' },
  },
};
</script>
```

---

## Vuetify Plugin

```typescript
// src/plugins/vuetify.ts
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
  },
});
```

---

## Main Entry

```typescript
// src/main.ts
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import vuetify from './plugins/vuetify';

createApp(App).use(router).use(vuetify).mount('#app');
```

---

## Testing Configuration

### vitest.config.ts

```typescript
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'url';

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/setup.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: ['node_modules/', 'tests/'],
      thresholds: {
        lines: 70,
        functions: 70,
        branches: 70,
        statements: 70,
      },
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
});
```

### tests/setup.ts

```typescript
import { config } from '@vue/test-utils';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

const vuetify = createVuetify({ components, directives });

config.global.plugins = [vuetify];
```

---

## Running Tests

```bash
# Run tests
npm test

# Watch mode
npm test -- --watch

# Coverage report
npm run test:coverage

# Run specific test
npm test -- RouteForm
```

---

## ESLint Configuration

```javascript
// eslint.config.js
import pluginVue from 'eslint-plugin-vue';
import typescript from '@typescript-eslint/eslint-plugin';
import parser from '@typescript-eslint/parser';
import vueParser from 'vue-eslint-parser';

export default [
  {
    files: ['**/*.ts', '**/*.vue'],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: parser,
        ecmaVersion: 'latest',
        sourceType: 'module',
      },
    },
    plugins: {
      vue: pluginVue,
      '@typescript-eslint': typescript,
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      '@typescript-eslint/no-unused-vars': 'error',
      '@typescript-eslint/explicit-function-return-type': 'off',
    },
  },
];
```

---

## XP Practices Applied

### Small Iterations
- One component at a time
- Commit after each test passes
- PR per feature

### Continuous Refactoring
- Extract composables when logic is reused
- Keep components small and focused
- Remove dead code

### Simple Design
- Start without state management (Pinia)
- Add complexity only when needed
- Use TypeScript for documentation

### Pair Programming
- Code review on PRs
- Share knowledge through clear code

---

## Implementation Order (TDD)

1. **Types** - Define from OpenAPI spec
2. **API service** - HTTP client with interceptors
3. **Route service** - Business logic with tests
4. **RouteForm component** - Input with validation
5. **RouteResult component** - Display result
6. **HomeView** - Compose components
7. **StatsView** - Bonus feature with chart
