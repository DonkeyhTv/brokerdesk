import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import { onUnauthenticated } from './api/http'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App)

app.use(createPinia())
app.use(router)

onUnauthenticated(() => {
  useAuthStore().clear()

  if (router.currentRoute.value.meta.public !== true) {
    void router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
  }
})

// The first navigation checks the session: mounting before it ends would flash the signed-in layout to visitors.
void router.isReady().then(() => app.mount('#app'))
