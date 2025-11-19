<template>
  <v-form @submit.prevent="handleSubmit">
    <v-card>
      <v-card-title>Calculate Route</v-card-title>
      <v-card-text>
        <v-text-field
          v-model="form.fromStationId"
          data-testid="input-from-station-id"
          label="From Station"
          placeholder="e.g., MX"
          required
        />

        <v-text-field
          v-model="form.toStationId"
          data-testid="input-to-station-id"
          label="To Station"
          placeholder="e.g., CGE"
          required
        />

        <v-text-field
          v-model="form.analyticCode"
          data-testid="input-analytic-code"
          label="Analytic Code"
          placeholder="e.g., ANA-123"
          required
        />
      </v-card-text>
      <v-card-actions>
        <v-btn
          color="primary"
          data-testid="btn-submit"
          :disabled="loading"
          :loading="loading"
          type="submit"
        >
          Calculate
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup lang="ts">
  import type { RouteRequest } from '@/types/api'
  import { reactive } from 'vue'

  interface Props {
    loading?: boolean
  }

  withDefaults(defineProps<Props>(), {
    loading: false,
  })

  const emit = defineEmits<{
    submit: [data: RouteRequest]
  }>()

  const form = reactive<RouteRequest>({
    fromStationId: '',
    toStationId: '',
    analyticCode: '',
  })

  function handleSubmit () {
    emit('submit', { ...form })
  }
</script>
