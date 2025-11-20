/**
 * plugins/vuetify.ts
 *
 * Framework documentation: https://vuetifyjs.com`
 */

// Composables
import { createVuetify } from 'vuetify'
// Styles
import '@mdi/font/css/materialdesignicons.css'

import 'vuetify/styles'

// https://vuetifyjs.com/en/introduction/why-vuetify/#feature-guides
export default createVuetify({
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#3F51B5', // Indigo 500
          secondary: '#5C6BC0', // Indigo 400
          accent: '#7986CB', // Indigo 300
          error: '#E53935', // Red 600
          warning: '#FB8C00', // Orange 600
          info: '#039BE5', // Light Blue 600
          success: '#43A047', // Green 600
          background: '#FAFAFA', // Gray 50
          surface: '#FFFFFF', // White
        },
      },
    },
  },
})
