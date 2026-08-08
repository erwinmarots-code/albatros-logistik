<template>
  <div id="app">
    <!-- ===== HEADER ===== -->
    <header class="app-header">
      <div class="header-content">
        <h1><i class="fas fa-ship"></i> Albatros Makassar</h1>
        <p class="subtitle">Sistem Manajemen Logistik</p>
      </div>
      <div class="header-right">
        <span v-if="isLoggedIn" class="user-info">
          <i class="fas fa-user-circle"></i> {{ user?.name || 'User' }}
          <span class="role-badge">{{ roleLabel }}</span>
          <button @click="logout" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
          </button>
        </span>
        <button v-else @click="showLogin = true" class="btn-login">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </div>
    </header>

    <!-- ===== MODAL LOGIN ===== -->
    <div v-if="showLogin" class="modal-overlay" @click.self="showLogin = false">
      <div class="modal-content">
        <h2><i class="fas fa-lock"></i> Login</h2>
        <form @submit.prevent="login">
          <div class="form-group">
            <label>Email</label>
            <input v-model="loginForm.email" type="email" required placeholder="email@example.com" />
          </div>
          <div class="form-group">
            <label>Password</label>
            <input v-model="loginForm.password" type="password" required placeholder="********" />
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-login-submit" :disabled="loading">
              <i v-if="loading" class="fas fa-spinner fa-spin"></i>
              {{ loading ? 'Memproses...' : 'Login' }}
            </button>
            <button type="button" @click="showLogin = false" class="btn-cancel">Batal</button>
          </div>
          <p v-if="loginError" class="error-text">{{ loginError }}</p>
        </form>
      </div>
    </div>

    <!-- ===== NAVIGASI ===== -->
    <nav class="app-nav" v-if="isLoggedIn">
      <button
        v-for="tab in accessibleTabs"
        :key="tab.key"
        :class="['nav-btn', { active: currentPage === tab.key }]"
        @click="navigateTo(tab.key)"
      >
        <i :class="tab.icon"></i> {{ tab.label }}
      </button>
    </nav>

    <!-- ===== KONTEN ===== -->
    <main class="app-content">
      <div v-if="isLoggedIn">
        <VehicleList v-if="currentPage === 'vehicles'" />
        <DriverList v-if="currentPage === 'drivers'" />
        <ClientList v-if="currentPage === 'clients'" />
        <HistoryMaintenanceList v-if="currentPage === 'history'" />
        <MaintenanceRequestList v-if="currentPage === 'requests'" />
        <ShippingProjectList v-if="currentPage === 'projects'" />
        <DeliveryTaskList v-if="currentPage === 'delivery-tasks'" />
        <FuelExpenseList v-if="currentPage === 'fuel-expenses'" />
        <FinancialDashboard v-if="currentPage === 'financial'" />
        <InvoiceList v-if="currentPage === 'invoices'" />
      </div>
      <div v-else class="login-prompt">
        <h2>Selamat Datang di Albatros Makassar</h2>
        <p>Silakan login untuk mengakses sistem.</p>
        <button @click="showLogin = true" class="btn-login-large">Login</button>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from './axios'   // <-- IMPORT DARI FILE KUSTOM AXIOS (baseURL sudah '/api')
import VehicleList from './components/VehicleList.vue'
import DriverList from './components/DriverList.vue'
import ClientList from './components/ClientList.vue'
import HistoryMaintenanceList from './components/HistoryMaintenanceList.vue'
import MaintenanceRequestList from './components/MaintenanceRequestList.vue'
import ShippingProjectList from './components/ShippingProjectList.vue'
import DeliveryTaskList from './components/DeliveryTaskList.vue'
import FuelExpenseList from './components/FuelExpenseList.vue'
import FinancialDashboard from './components/FinancialDashboard.vue'
import InvoiceList from './components/InvoiceList.vue'

// ===== STATE =====
const isLoggedIn = ref(false)
const user = ref(null)
const token = ref(null)
const loading = ref(false)
const loginError = ref('')
const showLogin = ref(false)
const currentPage = ref('vehicles')

const loginForm = ref({
  email: '',
  password: '',
})

// ===== DAFTAR MENU & ROLE =====
const allTabs = [
  { key: 'vehicles', label: 'Kendaraan', icon: 'fas fa-car', roles: ['admin_transport', 'super_admin'] },
  { key: 'drivers', label: 'Driver', icon: 'fas fa-user-tie', roles: ['admin_transport', 'super_admin'] },
  { key: 'clients', label: 'Client', icon: 'fas fa-building', roles: ['admin_po', 'super_admin'] },
  { key: 'history', label: 'History Perawatan', icon: 'fas fa-history', roles: ['admin_transport', 'admin_finance', 'super_admin'] },
  { key: 'requests', label: 'Pengajuan Perawatan', icon: 'fas fa-tools', roles: ['admin_transport', 'admin_finance', 'super_admin'] },
  { key: 'projects', label: 'Project', icon: 'fas fa-folder-open', roles: ['admin_po', 'super_admin'] },
  { key: 'delivery-tasks', label: 'Tugas', icon: 'fas fa-tasks', roles: ['admin_po', 'admin_transport', 'super_admin'] },
  { key: 'fuel-expenses', label: 'Biaya', icon: 'fas fa-coins', roles: ['admin_po', 'admin_transport', 'admin_finance', 'super_admin'] },
  { key: 'financial', label: 'Keuangan', icon: 'fas fa-chart-pie', roles: ['admin_finance', 'super_admin'] },
  { key: 'invoices', label: 'Invoice', icon: 'fas fa-file-invoice', roles: ['admin_finance', 'super_admin'] },
]

// ===== COMPUTED =====
const accessibleTabs = computed(() => {
  if (!isLoggedIn.value || !user.value) return []
  return allTabs.filter(tab => tab.roles.includes(user.value.role))
})

const roleLabel = computed(() => {
  if (!user.value) return ''
  const map = {
    admin_po: 'Admin PO',
    admin_transport: 'Admin Transport',
    admin_finance: 'Admin Finance',
    super_admin: 'Super Admin',
  }
  return map[user.value.role] || user.value.role
})

// ===== FUNGSI =====
const navigateTo = (key) => {
  currentPage.value = key
}

const login = async () => {
  loading.value = true
  loginError.value = ''
  try {
    const res = await axios.post('/login', loginForm.value)   // TANPA PREFIX /api
    token.value = res.data.token
    user.value = res.data.user
    isLoggedIn.value = true
    localStorage.setItem('token', token.value)
    localStorage.setItem('user', JSON.stringify(user.value))
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    showLogin.value = false
    loginForm.value = { email: '', password: '' }
    const firstTab = accessibleTabs.value[0]
    if (firstTab) currentPage.value = firstTab.key
  } catch (error) {
    loginError.value = error.response?.data?.message || 'Login gagal. Periksa email dan password.'
  } finally {
    loading.value = false
  }
}

const logout = async () => {
  try {
    await axios.post('/logout')
  } catch (e) { /* ignore */ }
  token.value = null
  user.value = null
  isLoggedIn.value = false
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  delete axios.defaults.headers.common['Authorization']
  currentPage.value = 'vehicles'
}

// ===== MOUNTED =====
onMounted(() => {
  const storedToken = localStorage.getItem('token')
  const storedUser = localStorage.getItem('user')
  if (storedToken && storedUser) {
    token.value = storedToken
    user.value = JSON.parse(storedUser)
    isLoggedIn.value = true
    axios.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
    const firstTab = accessibleTabs.value[0]
    if (firstTab) currentPage.value = firstTab.key
  }
})
</script>

<style scoped>
/* ===== LOCAL STYLE ===== */
* { box-sizing: border-box; margin: 0; padding: 0; }
#app {
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  background: #f0f2f5;
  min-height: 100vh;
  padding: 20px;
}

.app-header {
  background: linear-gradient(135deg, #0d2b45 0%, #1a4a7a 100%);
  border-radius: 16px;
  padding: 16px 32px;
  margin-bottom: 24px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  box-shadow: 0 8px 30px rgba(13, 43, 69, 0.3);
}
.header-content h1 { font-size: 28px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.header-content h1 i { color: #ffd700; }
.subtitle { opacity: 0.8; font-size: 14px; margin-top: 2px; }
.header-right { display: flex; align-items: center; gap: 16px; }
.user-info { display: flex; align-items: center; gap: 12px; font-size: 14px; }
.role-badge { background: #ffd700; color: #0d2b45; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.btn-logout { background: #dc3545; color: white; border: none; border-radius: 8px; padding: 6px 14px; cursor: pointer; font-weight: 600; }
.btn-logout:hover { background: #c82333; }
.btn-login { background: #28a745; color: white; border: none; border-radius: 8px; padding: 8px 20px; cursor: pointer; font-weight: 600; }
.btn-login:hover { background: #218838; }

.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}
.modal-content {
  background: white;
  padding: 32px;
  border-radius: 16px;
  width: 400px;
  max-width: 90%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.modal-content h2 { text-align: center; margin-bottom: 24px; color: #0d2b45; }
.modal-content .form-group { margin-bottom: 16px; }
.modal-content .form-group label { display: block; font-weight: 600; margin-bottom: 4px; }
.modal-content .form-group input { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
.modal-content .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; }
.btn-login-submit { background: #1a4a7a; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn-login-submit:hover { background: #0d2b45; }
.btn-cancel { background: #6c757d; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #5a6268; }
.error-text { color: #dc3545; margin-top: 12px; text-align: center; }

.app-nav { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
.nav-btn {
  padding: 10px 20px;
  background: white;
  border: none;
  border-radius: 30px;
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
  cursor: pointer;
  transition: all 0.25s ease;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.nav-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.nav-btn.active { background: #1a4a7a; color: white; box-shadow: 0 4px 16px rgba(26, 74, 122, 0.3); }
.nav-btn i { font-size: 16px; }

.app-content {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.04);
  min-height: 300px;
}
.login-prompt { text-align: center; padding: 60px 20px; }
.login-prompt h2 { color: #0d2b45; margin-bottom: 12px; }
.btn-login-large {
  margin-top: 20px;
  background: #1a4a7a;
  color: white;
  border: none;
  padding: 12px 40px;
  border-radius: 30px;
  font-weight: 600;
  font-size: 18px;
  cursor: pointer;
}
.btn-login-large:hover { background: #0d2b45; }

@media (max-width: 768px) {
  .app-header { flex-direction: column; align-items: stretch; gap: 12px; }
  .header-right { justify-content: flex-end; }
  .app-nav { gap: 6px; }
  .nav-btn { padding: 8px 14px; font-size: 13px; }
  .app-content { padding: 16px; }
}
</style>

<!-- ===== GLOBAL CSS UNTUK TABEL & KOMPONEN ===== -->
<style>
.table-wrapper {
  overflow-x: auto;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
  padding: 4px 0;
  margin-top: 16px;
}
.modern-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  min-width: 700px;
}
.modern-table thead {
  background: #f8fafc;
  border-bottom: 2px solid #e9ecef;
}
.modern-table thead th {
  padding: 14px 16px;
  text-align: left;
  font-weight: 700;
  color: #2d3748;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.modern-table thead th i { margin-right: 6px; color: #1a4a7a; }
.modern-table tbody tr { border-bottom: 1px solid #f1f3f5; transition: background 0.15s ease; }
.modern-table tbody tr:hover { background: #f8fafc; }
.modern-table tbody td { padding: 12px 16px; color: #2d3748; vertical-align: middle; }
.modern-table tbody td:first-child { font-weight: 600; color: #6c757d; width: 40px; text-align: center; }
.text-center { text-align: center; }
.action-cell { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }

.btn-edit, .btn-delete, .btn-approve, .btn-reject, .btn-detail {
  border: none;
  border-radius: 8px;
  padding: 6px 10px;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}
.btn-edit { background: #ffc107; color: #212529; }
.btn-edit:hover { background: #e0a800; transform: scale(1.08); }
.btn-delete { background: #dc3545; color: white; }
.btn-delete:hover { background: #c82333; transform: scale(1.08); }
.btn-approve { background: #28a745; color: white; }
.btn-approve:hover { background: #218838; transform: scale(1.08); }
.btn-reject { background: #dc3545; color: white; }
.btn-reject:hover { background: #c82333; transform: scale(1.08); }
.btn-detail { background: #17a2b8; color: white; }
.btn-detail:hover { background: #138496; transform: scale(1.08); }
</style>