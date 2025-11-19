<template>
  <v-card>
    <v-card-title>Distance Statistics</v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="fromDate"
            data-testid="input-from-date"
            label="From Date"
            type="date"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="toDate"
            data-testid="input-to-date"
            label="To Date"
            type="date"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="groupBy"
            data-testid="select-group-by"
            :items="groupByOptions"
            label="Group By"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-btn
            color="primary"
            data-testid="btn-load-stats"
            :disabled="loading"
            :loading="loading"
            @click="handleLoadStats"
          >
            Load Stats
          </v-btn>
        </v-col>
      </v-row>

      <v-progress-linear
        v-if="loading"
        data-testid="loading-indicator"
        indeterminate
      />

      <v-alert v-if="error" class="mt-4" type="error">
        {{ error }}
      </v-alert>

      <template v-if="stats">
        <v-sparkline
          auto-line-width
          class="mt-4"
          color="primary"
          data-testid="stats-chart"
          line-width="2"
          :model-value="chartData.values"
          padding="16"
          smooth
          stroke-linecap="round"
        />

        <v-table data-testid="stats-table" density="compact">
          <thead>
            <tr>
              <th>Analytic Code</th>
              <th v-if="stats.groupBy !== 'none'">Group</th>
              <th>Total Distance (km)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in stats.items" :key="item.analyticCode + (item.group || '')">
              <td>{{ item.analyticCode }}</td>
              <td v-if="stats.groupBy !== 'none'">{{ item.group }}</td>
              <td>{{ item.totalDistanceKm }}</td>
            </tr>
          </tbody>
        </v-table>
      </template>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useStats } from '@/composables/useStats'

  const { stats, loading, error, loadStats, getChartData } = useStats()

  const fromDate = ref('')
  const toDate = ref('')
  const groupBy = ref<'day' | 'month' | 'year' | 'none'>('none')

  const groupByOptions = [
    { title: 'None', value: 'none' },
    { title: 'Day', value: 'day' },
    { title: 'Month', value: 'month' },
    { title: 'Year', value: 'year' },
  ]

  const chartData = computed(() => getChartData())

  async function handleLoadStats () {
    const params: Record<string, string> = {}
    if (fromDate.value) params.from = fromDate.value
    if (toDate.value) params.to = toDate.value
    if (groupBy.value !== 'none') params.groupBy = groupBy.value

    await loadStats(params)
  }

  onMounted(() => {
    loadStats({})
  })
</script>
