<template>
  <v-card>
    <v-card-title>Distance Statistics</v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="fromDate"
            clearable
            data-testid="input-from-date"
            label="From Date"
            type="date"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="toDate"
            clearable
            data-testid="input-to-date"
            label="To Date"
            type="date"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="groupBy"
            clearable
            data-testid="select-group-by"
            item-title="title"
            item-value="value"
            :items="groupByOptions"
            label="Group By"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-btn
            block
            color="primary"
            data-testid="btn-load-stats"
            :disabled="loading"
            :loading="loading"
            size="large"
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

      <v-row v-if="stats && stats.items.length > 0">
        <v-col cols="12" lg="7">
          <v-card class="mt-4" color="surface" variant="flat">
            <v-card-title class="text-subtitle-1">
              Distance Chart
            </v-card-title>
            <v-card-text>
              <DistanceChart
                data-testid="stats-chart"
                :labels="chartData.labels"
                :values="chartData.values"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="5">
          <v-card class="mt-4 d-flex flex-column" color="surface" style="height: calc(100% - 16px)" variant="flat">
            <v-card-title class="text-subtitle-1">
              Detailed Statistics
            </v-card-title>
            <v-card-text class="flex-grow-1 overflow-y-auto" style="max-height: 500px">
              <v-table data-testid="stats-table" density="comfortable" fixed-header hover>
                <thead>
                  <tr>
                    <th class="text-left">
                      Analytic Code
                    </th>
                    <th v-if="stats.groupBy !== 'none'" class="text-left">
                      Group
                    </th>
                    <th class="text-right">
                      Total Distance (km)
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in stats.items" :key="item.analyticCode + (item.group || '')">
                    <td>
                      <v-chip color="primary" size="small" variant="outlined">
                        {{ item.analyticCode }}
                      </v-chip>
                    </td>
                    <td v-if="stats.groupBy !== 'none'">{{ item.group }}</td>
                    <td class="text-right font-weight-bold">
                      {{ item.totalDistanceKm.toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td class="font-weight-bold" :colspan="stats.groupBy !== 'none' ? 2 : 1">
                      Total
                    </td>
                    <td class="text-right font-weight-bold">
                      {{ totalDistance.toFixed(2) }} km
                    </td>
                  </tr>
                </tfoot>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-empty-state
        v-else-if="!loading && stats && stats.items.length === 0"
        class="mt-4"
        headline="No data available"
        text="No route calculations have been made for the selected period. Try creating some routes first or adjusting the date range."
        title="No Statistics"
      />
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import DistanceChart from '@/components/DistanceChart.vue'
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

  const totalDistance = computed(() => {
    if (!stats.value || !stats.value.items) return 0
    return stats.value.items.reduce((sum, item) => sum + item.totalDistanceKm, 0)
  })

  async function handleLoadStats () {
    const params: Record<string, string> = {}
    if (fromDate.value) params.from = fromDate.value
    if (toDate.value) params.to = toDate.value
    if (groupBy.value && groupBy.value !== 'none') params.groupBy = groupBy.value

    await loadStats(params)
  }

  onMounted(() => {
    loadStats({})
  })
</script>
