import { createApp } from 'vue'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import ToastService from 'primevue/toastservice'
import router from './router'
import App from './App.vue'
import 'primeicons/primeicons.css'
import 'highlight.js/styles/github-dark.css'
import '@/core/styles/theme.css'
import '@/core/styles/base.css'
import '@/core/styles/markdown.css'

document.documentElement.classList.add('dark')

const app = createApp(App)

app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: { darkModeSelector: '.dark' }
  }
})
app.use(ToastService)
app.use(router)
app.mount('#app')
