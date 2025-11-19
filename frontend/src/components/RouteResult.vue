<template>
  <v-card>
    <v-card-title>Route Result</v-card-title>
    <v-card-text>
      <v-list>
        <v-list-item data-testid="result-distance">
          <v-list-item-title>Distance</v-list-item-title>
          <v-list-item-subtitle>
            {{ route.distanceKm }} km
          </v-list-item-subtitle>
        </v-list-item>

        <v-list-item data-testid="result-analytic-code">
          <v-list-item-title>Analytic Code</v-list-item-title>
          <v-list-item-subtitle>
            {{ route.analyticCode }}
          </v-list-item-subtitle>
        </v-list-item>

        <v-list-item>
          <v-list-item-title>Route</v-list-item-title>
          <v-list-item-subtitle>
            {{ route.fromStationId }} → {{ route.toStationId }}
          </v-list-item-subtitle>
        </v-list-item>

        <v-list-item data-testid="result-path">
          <v-list-item-title>Path</v-list-item-title>
          <v-list-item-subtitle>
            <v-chip
              v-for="station in route.path"
              :key="station"
              class="mr-1 mb-1"
              size="small"
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
  import type { Route } from '@/types/api'

  defineProps<{
    route: Route
  }>()
</script>
