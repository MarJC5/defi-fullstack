<template>
  <v-form @submit.prevent="handleSubmit">
    <v-card>
      <v-card-title>Login</v-card-title>
      <v-card-text>
        <v-alert
          v-if="authError"
          class="mb-4"
          data-testid="login-error"
          type="error"
        >
          {{ authError }}
        </v-alert>

        <v-text-field
          v-model="form.username"
          data-testid="input-username"
          label="Username"
          required
        />

        <v-text-field
          v-model="form.password"
          data-testid="input-password"
          label="Password"
          required
          type="password"
        />
      </v-card-text>
      <v-card-actions>
        <v-btn
          block
          color="primary"
          data-testid="btn-login"
          :disabled="isLoading"
          :loading="isLoading"
          prepend-icon="mdi-login"
          size="large"
          type="submit"
        >
          Login
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>

<script setup lang="ts">
  import { reactive } from 'vue'
  import { useAuth } from '@/composables/useAuth'

  const { authError, isLoading, login } = useAuth()

  const form = reactive({
    username: '',
    password: '',
  })

  async function handleSubmit () {
    await login(form)
  }
</script>
