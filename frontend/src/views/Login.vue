<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-header">
        <i class="fas fa-ship"></i>
        <h2>Albatros Logistik</h2>
        <p>Masuk ke sistem manajemen</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label>Email</label>
          <input
            v-model="email"
            type="email"
            placeholder="admin@albatros.com"
            required
          />
        </div>
        <div class="form-group">
          <label>Password</label>
          <input
            v-model="password"
            type="password"
            placeholder="********"
            required
          />
        </div>
        <button type="submit" class="btn-login" :disabled="loading">
          <i v-if="loading" class="fas fa-spinner fa-spin"></i>
          <span v-else>Login</span>
        </button>
        <p v-if="error" class="error-message">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  data() {
    return {
      email: '',
      password: '',
      loading: false,
      error: '',
    }
  },
  methods: {
    async handleLogin() {
      this.loading = true
      this.error = ''
      try {
        const res = await axios.post('/login', {
          email: this.email,
          password: this.password,
        })
        const data = res.data
        localStorage.setItem('token', data.token)
        localStorage.setItem('user', JSON.stringify(data.user))
        this.$router.push('/dashboard')
      } catch (err) {
        const msg = err.response?.data?.message || 'Login gagal, coba lagi.'
        this.error = msg
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.login-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #0d2b45, #1a4a7a);
}
.login-card {
  background: white;
  border-radius: 20px;
  padding: 40px 48px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.login-header {
  text-align: center;
  margin-bottom: 32px;
}
.login-header i {
  font-size: 48px;
  color: #1a4a7a;
}
.login-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #0d2b45;
  margin: 8px 0 4px;
}
.login-header p {
  color: #6b7280;
  font-size: 14px;
}
.login-form .form-group {
  margin-bottom: 18px;
}
.login-form label {
  display: block;
  font-weight: 600;
  font-size: 14px;
  color: #2d3748;
  margin-bottom: 4px;
}
.login-form input {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: 0.2s;
}
.login-form input:focus {
  outline: none;
  border-color: #1a4a7a;
  box-shadow: 0 0 0 3px rgba(26,74,122,0.12);
}
.btn-login {
  width: 100%;
  padding: 12px;
  background: #1a4a7a;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  transition: 0.2s;
}
.btn-login:hover { background: #0d2b45; transform: translateY(-2px); }
.btn-login:disabled { opacity: 0.6; cursor: not-allowed; }
.error-message {
  margin-top: 12px;
  color: #dc2626;
  font-size: 14px;
  text-align: center;
}
</style>