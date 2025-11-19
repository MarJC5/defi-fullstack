<template>
  <v-container class="fill-height">
    <v-row align="center" justify="center">
      <v-col cols="12" lg="4" md="6" sm="8">
        <HealthCheck />
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts" setup>
  import HealthCheck from '@/components/HealthCheck.vue'
</script>
