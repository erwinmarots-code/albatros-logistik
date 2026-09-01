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
// CEK TOKEN SAAT APLIKASI DIMULAI
// ============================================================

const token = localStorage.getItem('token')
if (token) {
  // Verifikasi token ke server
  axios.get('/user')
    .then((response) => {
      // Token valid, update user data jika perlu
      const userData = response.data.data || response.data
      if (userData) {
        localStorage.setItem('user', JSON.stringify(userData))
      }
    })
    .catch(() => {
      // Token tidak valid, hapus dari localStorage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      // Redirect ke login jika tidak sedang di halaman login/landing
      const path = window.location.pathname
      if (path !== '/' && path !== '/login') {
        window.location.href = '/login'
      }
    })
}