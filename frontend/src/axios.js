import axios from 'axios'

// Buat instance axios dengan base URL relatif
const instance = axios.create({
  baseURL: '/api',
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
      console.log('✅ Authorization header set:', config.headers.Authorization)
    } else {
      console.warn('⚠️ No token found!')
    }
    return config
  },
  (error) => Promise.reject(error)
)

// ============================================================
// INTERCEPTOR RESPONSE: Handle error 401 (Unauthorized)
// ============================================================
instance.interceptors.response.use(
  (response) => response,
  (error) => {
    // Jika error 401 dan BUKAN request ke /logout
    if (error.response && error.response.status === 401) {
      // 🔥 PENGECUALIAN: Jangan redirect jika request ke /logout
      if (error.config.url === '/logout') {
        return Promise.reject(error) // Biarkan gagal, tapi tidak redirect
      }

      // Hapus token dan user
      localStorage.removeItem('token')
      localStorage.removeItem('user')

      // Redirect ke login jika belum di halaman login/landing
      if (window.location.pathname !== '/login' && window.location.pathname !== '/') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default instance