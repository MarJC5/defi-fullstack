<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2" color="primary">
        mdi-train
      </v-icon>
      Route Information
    </v-card-title>
    <v-card-subtitle class="pb-2">
      <v-chip color="primary" data-testid="result-analytic-code" size="small" variant="outlined">
        {{ route.analyticCode }}
      </v-chip>
      <span class="ml-2 text-medium-emphasis" data-testid="result-distance">
        {{ route.distanceKm }} km total
      </span>
    </v-card-subtitle>

    <v-divider />

    <v-card-text data-testid="result-path">
      <v-timeline align="start" density="compact" side="end">
        <v-timeline-item
          v-for="(station, index) in route.path"
          :key="`${station}-${index}`"
          :data-testid="`path-station-${index}`"
          :dot-color="index === 0 ? 'success' : index === route.path.length - 1 ? 'error' : 'primary'"
          :size="index === 0 || index === route.path.length - 1 ? 'small' : 'x-small'"
        >
          <div class="d-flex align-center mb-1">
            <div class="text-h6 font-weight-medium">
              {{ getStationName(station) }}
            </div>

            <v-chip
              v-if="index === 0"
              class="ml-3"
              color="success"
              size="x-small"
              variant="flat"
            >
              Departure
            </v-chip>
            <v-chip
              v-else-if="index === route.path.length - 1"
              class="ml-3"
              color="error"
              size="x-small"
              variant="flat"
            >
              Arrival
            </v-chip>
          </div>

          <div class="text-caption text-medium-emphasis">
            Station code: {{ station }}
          </div>

          <div v-if="index > 0" class="text-caption mt-1">
            <v-icon color="primary" size="x-small">
              mdi-arrow-right
            </v-icon>
            <span class="ml-1">
              {{ getSegmentDistance(index) }} km from {{ route.path[index - 1] }}
            </span>
          </div>
        </v-timeline-item>
      </v-timeline>
    </v-card-text>

    <v-divider />

    <v-card-actions class="px-4 py-3">
      <v-icon color="primary" size="small">
        mdi-calendar-clock
      </v-icon>
      <span class="text-caption text-medium-emphasis ml-2">
        Calculated on {{ formatDate(route.createdAt) }}
      </span>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import type { Route } from '@/types/api'
  import { useStations } from '@/composables/useStations'

  const props = defineProps<{
    route: Route
  }>()

  const { stations } = useStations()

  // Get full station name from code
  function getStationName (code: string): string {
    const station = stations.value.find(s => s.shortName === code)
    return station?.longName || code
  }

  // Calculate distance for a segment (placeholder - would need distances data)
  function getSegmentDistance (_index: number): string {
    // For now, calculate proportional distance
    // In a real app, you'd fetch this from the distances.json or backend
    const totalDistance = props.route.distanceKm
    const segments = props.route.path.length - 1
    const avgDistance = totalDistance / segments
    return avgDistance.toFixed(2)
  }

  // Format date
  function formatDate (dateString: string): string {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('en-US', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(date)
  }
</script>
