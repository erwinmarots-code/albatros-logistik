console.log('🔥 main.js mulai dijalankan')

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import axios from './axios'

// ============================================================
// BUAT APP INSTANCE
// ============================================================
const app = createApp(App)

// ============================================================
// ERROR HANDLER UNTUK MENANGKAP ERROR DI VUE
// ============================================================
app.config.errorHandler = (err, vm, info) => {
  console.error('❌ Vue error:', err, info)
}

// ============================================================
// PASANG ROUTER
// ============================================================
app.use(router)

// ============================================================
// MOUNT APP
// ============================================================
app.mount('#app')

console.log('✅ Vue app mounted!')

// ============================================================
// 🔥 CEK TOKEN – DIHAPUS / DIKOMENTARI AGAR TIDAK REDIRECT GANGGU LOGOUT
// ============================================================
// Kode ini dikomentari karena menyebabkan redirect 401 saat logout
// Biarkan App.vue yang menangani status login/logout
/*
const token = localStorage.getItem('token')
if (token) {
  axios.get('/user')
    .then((response) => {
      const userData = response.data.data || response.data
      if (userData) {
        localStorage.setItem('user', JSON.stringify(userData))
      }
    })
    .catch(() => {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      const path = window.location.pathname
      if (path !== '/' && path !== '/login') {
        window.location.href = '/login'
      }
    })
}
*/