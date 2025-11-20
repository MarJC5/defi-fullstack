<template>
  <div>
    <v-btn-toggle
      v-model="chartType"
      class="mb-4"
      color="primary"
      density="compact"
      divided
      mandatory
      variant="outlined"
    >
      <v-btn value="bar">
        <v-icon start>
          mdi-chart-bar
        </v-icon>
        Bar
      </v-btn>
      <v-btn value="horizontalBar">
        <v-icon start>
          mdi-chart-bar-stacked
        </v-icon>
        Horizontal
      </v-btn>
      <v-btn value="pie">
        <v-icon start>
          mdi-chart-pie
        </v-icon>
        Pie
      </v-btn>
    </v-btn-toggle>

    <div class="chart-container">
      <Bar
        v-if="chartType === 'bar'"
        :data="barChartData"
        :options="barChartOptions"
      />
      <Bar
        v-else-if="chartType === 'horizontalBar'"
        :data="barChartData"
        :options="horizontalBarChartOptions"
      />
      <Pie
        v-else-if="chartType === 'pie'"
        :data="pieChartData"
        :options="pieChartOptions"
      />
    </div>

    <div class="text-center mt-3 text-caption text-medium-emphasis">
      {{ labels.length }} analytic code(s) | Total: {{ totalDistance.toFixed(2) }} km
    </div>
  </div>
</template>

<script setup lang="ts">
  import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
  } from 'chart.js'
  import { computed, ref } from 'vue'
  import { Bar, Pie } from 'vue-chartjs'

  // Register Chart.js components
  ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
  )

  interface Props {
    labels: string[]
    values: number[]
  }

  const props = defineProps<Props>()

  const chartType = ref<'bar' | 'horizontalBar' | 'pie'>('bar')

  const totalDistance = computed(() => {
    return props.values.reduce((sum, val) => sum + val, 0)
  })

  // Bar Chart (Vertical)
  const barChartData = computed(() => ({
    labels: props.labels,
    datasets: [
      {
        label: 'Distance (km)',
        data: props.values,
        backgroundColor: [
          'rgba(63, 81, 181, 0.8)', // Indigo 500
          'rgba(92, 107, 192, 0.8)', // Indigo 400
          'rgba(121, 134, 203, 0.8)', // Indigo 300
          'rgba(159, 168, 218, 0.8)', // Indigo 200
          'rgba(197, 202, 233, 0.8)', // Indigo 100
        ],
        borderColor: [
          'rgba(63, 81, 181, 1)', // Indigo 500
          'rgba(92, 107, 192, 1)', // Indigo 400
          'rgba(121, 134, 203, 1)', // Indigo 300
          'rgba(159, 168, 218, 1)', // Indigo 200
          'rgba(197, 202, 233, 1)', // Indigo 100
        ],
        borderWidth: 2,
      },
    ],
  }))

  const barChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.5,
    plugins: {
      legend: {
        display: false,
      },
      title: {
        display: false,
      },
      tooltip: {
        callbacks: {
          label: (context: any) => {
            return `${context.parsed.y?.toFixed(2) ?? 0} km`
          },
        },
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        title: {
          display: true,
          text: 'Distance (km)',
        },
      },
      x: {
        title: {
          display: true,
          text: 'Analytic Code',
        },
      },
    },
  }))

  // Horizontal Bar Chart
  const horizontalBarChartOptions = computed(() => ({
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.5,
    plugins: {
      legend: {
        display: false,
      },
      title: {
        display: false,
      },
      tooltip: {
        callbacks: {
          label: (context: any) => {
            return `${context.parsed.x?.toFixed(2) ?? 0} km`
          },
        },
      },
    },
    scales: {
      x: {
        beginAtZero: true,
        title: {
          display: true,
          text: 'Distance (km)',
        },
      },
      y: {
        title: {
          display: true,
          text: 'Analytic Code',
        },
      },
    },
  }))

  // Pie Chart
  const pieChartData = computed(() => ({
    labels: props.labels,
    datasets: [
      {
        data: props.values,
        backgroundColor: [
          'rgba(63, 81, 181, 0.8)', // Indigo 500
          'rgba(92, 107, 192, 0.8)', // Indigo 400
          'rgba(121, 134, 203, 0.8)', // Indigo 300
          'rgba(159, 168, 218, 0.8)', // Indigo 200
          'rgba(197, 202, 233, 0.8)', // Indigo 100
          'rgba(232, 234, 246, 0.8)', // Indigo 50
        ],
        borderColor: [
          'rgba(63, 81, 181, 1)', // Indigo 500
          'rgba(92, 107, 192, 1)', // Indigo 400
          'rgba(121, 134, 203, 1)', // Indigo 300
          'rgba(159, 168, 218, 1)', // Indigo 200
          'rgba(197, 202, 233, 1)', // Indigo 100
          'rgba(232, 234, 246, 1)', // Indigo 50
        ],
        borderWidth: 2,
      },
    ],
  }))

  const pieChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.3,
    plugins: {
      legend: {
        display: true,
        position: 'right' as const,
      },
      title: {
        display: false,
      },
      tooltip: {
        callbacks: {
          label: (context: any) => {
            const percentage = ((context.parsed / totalDistance.value) * 100).toFixed(1)
            return `${context.label}: ${context.parsed?.toFixed(2) ?? 0} km (${percentage}%)`
          },
        },
      },
    },
  }))
</script>

<style scoped>
.chart-container {
  position: relative;
  min-height: 300px;
}
</style>
