<template>
  <div class="login-container">
    <div class="login-card">
      <!-- 🔥 LOGO VIA CSS (BUKAN TAG <img>) -->
      <div class="login-logo">
        <div class="logo-image"></div>
        <h2>Albatros Logistik</h2>
        <p>Sistem Manajemen Logistik</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div v-if="errorMessage" class="error-box">
          {{ errorMessage }}
        </div>

        <div class="form-group">
          <label>Email</label>
          <input
            v-model="email"
            type="email"
            class="form-control"
            placeholder="Masukkan email"
            required
          />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input
            v-model="password"
            type="password"
            class="form-control"
            placeholder="Masukkan password"
            required
          />
        </div>

        <button type="submit" class="btn-login" :disabled="loading">
          <i v-if="loading" class="fas fa-spinner fa-spin"></i>
          {{ loading ? 'Memproses...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  name: 'Login',
  data() {
    return {
      email: '',
      password: '',
      loading: false,
      errorMessage: '',
    }
  },
  methods: {
    async handleLogin() {
      this.loading = true
      this.errorMessage = ''
      try {
        const response = await axios.post('/login', {
          email: this.email,
          password: this.password,
        })

        const data = response.data
        localStorage.setItem('token', data.token)
        localStorage.setItem('user', JSON.stringify(data.user))

        window.location.href = '/dashboard'
      } catch (error) {
        if (error.response && error.response.data) {
          this.errorMessage = error.response.data.message || 'Login gagal'
        } else {
          this.errorMessage = 'Terjadi kesalahan, silakan coba lagi'
        }
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #0d2b45 0%, #1a4a7a 100%);
  padding: 20px;
}

.login-card {
  background: white;
  border-radius: 20px;
  padding: 40px 36px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

/* 🔥 LOGO VIA CSS BACKGROUND */
.login-logo {
  text-align: center;
  margin-bottom: 28px;
}
.login-logo .logo-image {
  width: 120px;
  height: 120px;
  margin: 0 auto 12px auto;
  background: url('/images/albatros_logo_new.jpg') no-repeat center;
  background-size: contain;
}
.login-logo h2 {
  font-size: 22px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0;
}
.login-logo p {
  color: #6b7280;
  font-size: 14px;
  margin: 4px 0 0 0;
}

.form-group {
  margin-bottom: 16px;
}
.form-group label {
  display: block;
  font-weight: 600;
  font-size: 14px;
  color: #2d3748;
  margin-bottom: 4px;
}
.form-control {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
}
.form-control:focus {
  outline: none;
  border-color: #2b6cb0;
  box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.15);
}

.error-box {
  padding: 10px 14px;
  background: #fee2e2;
  border: 1px solid #dc2626;
  border-radius: 8px;
  color: #991b1b;
  font-size: 14px;
  margin-bottom: 16px;
}

.btn-login {
  width: 100%;
  padding: 12px;
  background: #2b6cb0;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
}
.btn-login:hover {
  background: #1a4a7a;
}
.btn-login:active {
  transform: scale(0.97);
}
.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-login i {
  margin-right: 8px;
}

@media (max-width: 480px) {
  .login-card {
    padding: 28px 20px;
  }
  .login-logo .logo-image {
    height: 80px;
    width: 80px;
  }
  .login-logo h2 {
    font-size: 18px;
  }
}
</style>