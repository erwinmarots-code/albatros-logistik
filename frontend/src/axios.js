import axios from 'axios'

// Buat instance axios dengan base URL yang benar (relatif)
const instance = axios.create({
  baseURL: '/api', // 🔥 Ganti dari 'http://localhost:8000/api'
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
})

// ============================================================
// INTERCEPTOR REQUEST: Tambahkan token ke setiap request
// ============================================================
instance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// ============================================================
// INTERCEPTOR RESPONSE: Handle error 401 (Unauthorized)
// ============================================================
instance.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    // Jika error 401 (token expired / invalid)
    if (error.response && error.response.status === 401) {
      // Hapus token dan user dari localStorage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      
      // Redirect ke login (jika belum di halaman login)
      if (window.location.pathname !== '/login' && window.location.pathname !== '/') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default instance