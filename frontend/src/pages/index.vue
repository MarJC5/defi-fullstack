<template>
  <v-container>
    <!-- Loading state while checking auth -->
    <v-row v-if="isCheckingAuth" align="center" justify="center">
      <v-col class="text-center" cols="12">
        <v-progress-circular indeterminate />
      </v-col>
    </v-row>

    <!-- Login form when not authenticated -->
    <v-row v-else-if="!isAuthenticated" align="center" justify="center">
      <v-col cols="12" lg="4" md="6" sm="8">
        <LoginForm />
      </v-col>
    </v-row>

    <!-- Main app when authenticated -->
    <template v-else>
      <v-row class="mb-4">
        <v-col class="text-right">
          <v-btn
            color="secondary"
            data-testid="btn-logout"
            variant="outlined"
            @click="logout"
          >
            Logout
          </v-btn>
        </v-col>
      </v-row>

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
  </v-container>
</template>

<script setup lang="ts">
  import { onMounted } from 'vue'
  import LoginForm from '@/components/LoginForm.vue'
  import RouteForm from '@/components/RouteForm.vue'
  import RouteResult from '@/components/RouteResult.vue'
  import { useAuth } from '@/composables/useAuth'
  import { useRoutes } from '@/composables/useRoutes'

  const { isAuthenticated, isCheckingAuth, logout, checkAuth } = useAuth()
  const { loading, error, route, calculateRoute } = useRoutes()

  onMounted(() => {
    checkAuth()
  })
</script>
