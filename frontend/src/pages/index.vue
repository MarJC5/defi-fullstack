<template>
  <v-container>
    <!-- Loading state while checking auth -->
    <v-row v-if="isCheckingAuth" align="center" justify="center">
      <v-col class="text-center" cols="12">
        <v-progress-circular indeterminate />
      </v-col>
    </v-row>

    <!-- Login form when not authenticated -->
    <v-row v-else-if="!isAuthenticated" align="center" justify="center" style="min-height: 80vh">
      <v-col cols="12" lg="4" md="6" sm="8">
        <LoginForm />
      </v-col>
    </v-row>

    <!-- Main app when authenticated -->
    <template v-else>
      <v-row class="mb-4">
        <v-col>
          <v-btn-toggle
            v-model="activeView"
            color="primary"
            data-testid="view-toggle"
            density="compact"
            divided
            mandatory
            variant="outlined"
          >
            <v-btn data-testid="btn-calculator" value="calculator">
              <v-icon start>
                mdi-calculator
              </v-icon>
              Calculator
            </v-btn>
            <v-btn data-testid="btn-statistics" value="statistics">
              <v-icon start>
                mdi-chart-line
              </v-icon>
              Statistics
            </v-btn>
          </v-btn-toggle>
        </v-col>
        <v-col class="text-right">
          <v-btn
            color="primary"
            data-testid="btn-logout"
            prepend-icon="mdi-logout"
            variant="outlined"
            @click="logout"
          >
            Logout
          </v-btn>
        </v-col>
      </v-row>

      <!-- Calculator View -->
      <template v-if="activeView === 'calculator'">
        <v-row>
          <v-col cols="12" md="6">
            <RouteForm
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
      </template>

      <!-- Statistics View -->
      <template v-else-if="activeView === 'statistics'">
        <v-row>
          <v-col cols="12">
            <StatsChart />
          </v-col>
        </v-row>
      </template>
    </template>
  </v-container>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import LoginForm from '@/components/LoginForm.vue'
  import RouteForm from '@/components/RouteForm.vue'
  import RouteResult from '@/components/RouteResult.vue'
  import StatsChart from '@/components/StatsChart.vue'
  import { useAuth } from '@/composables/useAuth'
  import { useRoutes } from '@/composables/useRoutes'

  const { isAuthenticated, isCheckingAuth, logout, checkAuth } = useAuth()
  const { loading, error, route, calculateRoute } = useRoutes()

  const activeView = ref<'calculator' | 'statistics'>('calculator')

  onMounted(() => {
    checkAuth()
  })
</script>
