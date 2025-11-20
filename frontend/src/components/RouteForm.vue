<template>
  <v-form @submit.prevent="handleSubmit">
    <v-card>
      <v-card-title>Calculate Route</v-card-title>
      <v-card-text>
        <v-autocomplete
          v-model="form.fromStationId"
          clearable
          data-testid="input-from-station-id"
          item-title="longName"
          item-value="shortName"
          :items="stations"
          label="From Station"
          :loading="stationsLoading"
          no-data-text="No stations found"
          placeholder="Search for a station..."
          required
        >
          <template #item="{ props, item }">
            <v-list-item v-bind="props">
              <template #title>
                {{ item.raw.longName }}
              </template>
              <template #subtitle>
                {{ item.raw.shortName }}
              </template>
            </v-list-item>
          </template>
        </v-autocomplete>

        <v-autocomplete
          v-model="form.toStationId"
          clearable
          data-testid="input-to-station-id"
          item-title="longName"
          item-value="shortName"
          :items="stations"
          label="To Station"
          :loading="stationsLoading"
          no-data-text="No stations found"
          placeholder="Search for a station..."
          required
        >
          <template #item="{ props, item }">
            <v-list-item v-bind="props">
              <template #title>
                {{ item.raw.longName }}
              </template>
              <template #subtitle>
                {{ item.raw.shortName }}
              </template>
            </v-list-item>
          </template>
        </v-autocomplete>

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
          :disabled="loading || stationsLoading"
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
  import { useStations } from '@/composables/useStations'

  interface Props {
    loading?: boolean
  }

  withDefaults(defineProps<Props>(), {
    loading: false,
  })

  const emit = defineEmits<{
    submit: [data: RouteRequest]
  }>()

  const { stations, loading: stationsLoading } = useStations()

  const form = reactive<RouteRequest>({
    fromStationId: '',
    toStationId: '',
    analyticCode: '',
  })

  function handleSubmit () {
    emit('submit', { ...form })
  }
</script>
