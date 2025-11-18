<template>
  <v-card class="pa-6" max-width="500">
    <v-card-title class="text-h5 text-center">
      System Health Check
    </v-card-title>

    <v-card-text>
      <v-alert
        :type="apiAlertType"
        :title="'API: ' + healthStatus"
        variant="tonal"
        class="mb-3"
      />

      <v-alert
        v-if="dbStatus"
        :type="dbStatus === 'OK' ? 'success' : 'error'"
        :title="'Database: ' + dbStatus"
        variant="tonal"
        class="mb-3"
      />

      <p v-if="timestamp" class="text-caption text-grey text-center mb-0">
        Last check: {{ timestamp }}
      </p>
    </v-card-text>

    <v-card-actions class="justify-center">
      <v-btn
        color="primary"
        variant="tonal"
        @click="checkHealth"
        :loading="isChecking"
      >
        Check Again
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script lang="ts" setup>
import axios from 'axios'

const healthStatus = ref<string>('Checking...')
const isHealthy = ref<boolean | null>(null)
const isChecking = ref<boolean>(false)
const timestamp = ref<string>('')
const dbStatus = ref<string>('')

const apiAlertType = computed(() => {
  if (isChecking.value) return 'info'
  if (isHealthy.value === null) return 'info'
  return isHealthy.value ? 'success' : 'error'
})

const checkHealth = async () => {
  isChecking.value = true
  healthStatus.value = 'Checking...'
  dbStatus.value = ''

  try {
    const response = await axios.get('/api/v1/health')
    healthStatus.value = response.data.status || 'OK'
    timestamp.value = response.data.timestamp || new Date().toISOString()
    dbStatus.value = response.data.database || ''
    isHealthy.value = response.data.status === 'OK'
  } catch (error) {
    healthStatus.value = 'Error connecting to backend'
    timestamp.value = new Date().toISOString()
    isHealthy.value = false
  } finally {
    isChecking.value = false
  }
}

onMounted(() => {
  checkHealth()
})
</script>
