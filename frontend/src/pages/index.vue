<template>
  <v-container>
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
  </v-container>
</template>

<script setup lang="ts">
  import RouteForm from '@/components/RouteForm.vue'
  import RouteResult from '@/components/RouteResult.vue'
  import { useRoutes } from '@/composables/useRoutes'

  const { loading, error, route, calculateRoute } = useRoutes()
</script>
