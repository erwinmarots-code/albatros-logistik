import axios from 'axios'

const instance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
})

// ============================================================
// INTERCEPTOR REQUEST: Tambahkan token
// ============================================================
instance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// ============================================================
// INTERCEPTOR RESPONSE: Jangan redirect otomatis
// ============================================================
instance.interceptors.response.use(
  (response) => response,
  (error) => {
    // Jika error 401, hanya log, jangan redirect otomatis
    if (error.response && error.response.status === 401) {
      console.warn('⚠️ 401 Unauthorized – token mungkin expired')
      // Biarkan komponen menangani redirect sendiri
    }
    return Promise.reject(error)
  }
)

export default instance