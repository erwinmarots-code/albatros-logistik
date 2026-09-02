<template>
  <div id="app">
    <!-- LAYOUT LOGIN -->
    <div v-if="isLoggedIn" class="app-layout">
      <aside class="sidebar">
        <div class="sidebar-brand">
          <span>Albatros Logistik</span>
        </div>

        <nav class="sidebar-nav">
          <router-link to="/dashboard" class="nav-link" active-class="active">
            <i class="fas fa-tachometer-alt"></i> Dashboard
          </router-link>

          <div v-if="showDataMaster" class="nav-section">Data Master</div>
          <router-link v-if="canAccess('clients')" to="/clients" class="nav-link" active-class="active">
            <i class="fas fa-users"></i> Client
          </router-link>
          <router-link v-if="canAccess('vehicles')" to="/vehicles" class="nav-link" active-class="active">
            <i class="fas fa-truck"></i> Kendaraan
          </router-link>
          <router-link v-if="canAccess('drivers')" to="/drivers" class="nav-link" active-class="active">
            <i class="fas fa-user-tie"></i> Driver
          </router-link>

          <div v-if="showOperasional" class="nav-section">Operasional</div>
          <router-link v-if="canAccess('projects')" to="/projects" class="nav-link" active-class="active">
            <i class="fas fa-folder-open"></i> Project
          </router-link>
          <router-link v-if="canAccess('delivery-tasks')" to="/delivery-tasks" class="nav-link" active-class="active">
            <i class="fas fa-tasks"></i> Tugas Kirim
          </router-link>
          <router-link v-if="canAccess('fuel-expenses')" to="/fuel-expenses" class="nav-link" active-class="active">
            <i class="fas fa-coins"></i> Pengajuan Biaya
          </router-link>

          <router-link v-if="canAccess('maintenance-requests')" to="/maintenance-requests" class="nav-link" active-class="active">
            <i class="fas fa-tools"></i> Perawatan
          </router-link>

          <div v-if="showInventory" class="nav-section">Inventory</div>
          <router-link v-if="canAccess('spare-parts')" to="/spare-parts" class="nav-link" active-class="active">
            <i class="fas fa-boxes"></i> Spare Part
          </router-link>

          <div v-if="showKeuangan" class="nav-section">Keuangan</div>
          <router-link v-if="canAccess('invoices')" to="/invoices" class="nav-link" active-class="active">
            <i class="fas fa-file-invoice"></i> Invoice
          </router-link>
          <router-link v-if="canAccess('financial-dashboard')" to="/financial" class="nav-link" active-class="active">
            <i class="fas fa-chart-pie"></i> Keuangan
          </router-link>

          <div v-if="user?.role === 'super_admin'" class="nav-section">Pengaturan</div>
          <router-link v-if="user?.role === 'super_admin'" to="/users" class="nav-link" active-class="active">
            <i class="fas fa-users-cog"></i> Kelola Akun
          </router-link>
          <router-link v-if="user?.role === 'super_admin'" to="/settings" class="nav-link" active-class="active">
            <i class="fas fa-cog"></i> Pengaturan Sistem
          </router-link>
          <router-link v-if="user?.role === 'super_admin'" to="/permissions" class="nav-link" active-class="active">
            <i class="fas fa-lock"></i> Manajemen Akses
          </router-link>

          <button @click="logout" class="nav-link logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
          </button>
        </nav>
      </aside>

      <main class="main-content">
        <header class="topbar">
          <div class="topbar-left"><h1>{{ pageTitle }}</h1></div>
          <div class="topbar-right">
            <span class="user-info">{{ user?.name || 'User' }}</span>
            <span class="role-badge">{{ user?.role || 'Role' }}</span>
            <button class="btn-change-password" @click="showChangePassword = true">
              <i class="fas fa-key"></i> Ganti Password
            </button>
          </div>
        </header>
        <div class="content">
          <router-view v-if="isLoggedIn" :key="isLoggedIn" />
        </div>
      </main>
    </div>

    <div v-else>
      <router-view />
    </div>

    <ChangePasswordModal
      :show="showChangePassword"
      @close="showChangePassword = false"
      @success="handlePasswordSuccess"
    />
  </div>
</template>

<script>
import { computed, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from './axios'
import ChangePasswordModal from './components/ChangePasswordModal.vue'

export default {
  name: 'App',
  components: {
    ChangePasswordModal,
  },
  setup() {
    const router = useRouter()
    const route = useRoute()

    const token = ref(localStorage.getItem('token'))
    const user = ref(JSON.parse(localStorage.getItem('user') || '{}'))
    const showChangePassword = ref(false)

    const isLoggedIn = computed(() => !!token.value && token.value.length > 0)

    const canAccess = (menu) => {
      if (user.value?.role === 'super_admin') return true
      const permissions = user.value?.permissions || []
      return permissions.includes(menu)
    }

    const showDataMaster = computed(() => canAccess('clients') || canAccess('vehicles') || canAccess('drivers'))
    const showOperasional = computed(() => canAccess('projects') || canAccess('delivery-tasks') || canAccess('fuel-expenses') || canAccess('maintenance-requests'))
    const showInventory = computed(() => canAccess('spare-parts'))
    const showKeuangan = computed(() => canAccess('invoices') || canAccess('financial-dashboard'))

    // =============================================================
    // 🔥 LOGOUT – LANGSUNG KE LANDING PAGE TANPA INTERFERENSI
    // =============================================================
    const logout = () => {
    // Hapus semua data session
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    sessionStorage.clear()
    token.value = null
    user.value = {}

    // Redirect ke landing page dengan timestamp untuk memecah cache
    const timestamp = Date.now()
    window.location.href = '/?_=' + timestamp
    }

    const handlePasswordSuccess = (message) => {
      alert(message || 'Password berhasil diubah!')
    }

    const pageTitle = computed(() => {
      const titles = {
        '/dashboard': 'Dashboard',
        '/clients': 'Manajemen Client',
        '/vehicles': 'Manajemen Kendaraan',
        '/drivers': 'Manajemen Driver',
        '/projects': 'Project Pengantaran',
        '/delivery-tasks': 'Tugas Pengiriman',
        '/fuel-expenses': 'Pengajuan Biaya',
        '/maintenance-requests': 'Perawatan Kendaraan',
        '/invoices': 'Tagihan / Invoice',
        '/financial': 'Dashboard Keuangan',
        '/users': 'Kelola Akun',
        '/spare-parts': 'Inventory Spare Part',
        '/permissions': 'Manajemen Akses',
        '/settings': 'Pengaturan Sistem',
      }
      if (route.path.startsWith('/projects/')) return 'Detail Project'
      return titles[route.path] || 'Aplikasi Logistik'
    })

    watch(
      () => localStorage.getItem('token'),
      (newToken) => {
        token.value = newToken
        if (!newToken) user.value = {}
        else user.value = JSON.parse(localStorage.getItem('user') || '{}')
      }
    )

    return {
      isLoggedIn,
      user,
      logout,
      pageTitle,
      canAccess,
      showDataMaster,
      showOperasional,
      showInventory,
      showKeuangan,
      showChangePassword,
      handlePasswordSuccess,
    }
  }
}
</script>

<style scoped>
/* ========================================================== */
/* RESET & GLOBAL */
/* ========================================================== */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
#app {
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  height: 100vh;
}

/* ========================================================== */
/* LAYOUT UTAMA */
/* ========================================================== */
.app-layout {
  display: flex;
  height: 100vh;
  background: #f1f5f9;
}

/* ========================================================== */
/* SIDEBAR */
/* ========================================================== */
.sidebar {
  width: 250px;
  background: #0d2b45;
  color: #e2e8f0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  overflow-y: auto;
}
.sidebar-brand {
  padding: 16px 20px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.sidebar-brand span {
  font-size: 20px;
  font-weight: 700;
  color: white;
  letter-spacing: 0.5px;
}

.sidebar-nav {
  padding: 16px 12px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-section {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #94a3b8;
  padding: 12px 12px 4px 12px;
  font-weight: 600;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 10px;
  color: #cbd5e1;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}
.nav-link i {
  width: 20px;
  text-align: center;
  font-size: 16px;
}
.nav-link:hover {
  background: rgba(255, 255, 255, 0.08);
  color: white;
}
.nav-link.active {
  background: rgba(66, 153, 225, 0.2);
  color: white;
}
.nav-link.active i {
  color: #4299e1;
}

.logout-btn {
  margin-top: auto;
  color: #f87171;
}
.logout-btn:hover {
  background: rgba(248, 113, 113, 0.15);
  color: #fca5a5;
}

/* ========================================================== */
/* MAIN CONTENT */
/* ========================================================== */
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.topbar {
  background: white;
  padding: 16px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}
.topbar-left h1 {
  font-size: 22px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0;
}
.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}
.user-info {
  font-weight: 600;
  color: #1a202c;
}
.role-badge {
  background: #e2e8f0;
  padding: 4px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  color: #2d3748;
  text-transform: capitalize;
}

.btn-change-password {
  background: transparent;
  border: 1.5px solid #e2e8f0;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  color: #2d3748;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}
.btn-change-password:hover {
  border-color: #2b6cb0;
  background: #f7fafc;
  color: #2b6cb0;
}
.btn-change-password i {
  font-size: 14px;
}

.content {
  flex: 1;
  padding: 24px 32px;
  overflow-y: auto;
  background: #f8fafc;
}

/* ========================================================== */
/* RESPONSIVE */
/* ========================================================== */
@media (max-width: 768px) {
  .sidebar {
    width: 200px;
  }
  .topbar {
    padding: 12px 16px;
  }
  .topbar-left h1 {
    font-size: 18px;
  }
  .content {
    padding: 16px;
  }
  .sidebar-brand span {
    font-size: 18px;
  }
  .btn-change-password span {
    display: none;
  }
  .btn-change-password {
    padding: 6px 10px;
  }
}

@media (max-width: 480px) {
  .sidebar {
    width: 60px;
  }
  .sidebar-brand span {
    display: none;
  }
  .sidebar-brand {
    justify-content: center;
    padding: 12px;
  }
  .nav-link span {
    display: none;
  }
  .nav-link {
    justify-content: center;
    padding: 12px;
  }
  .nav-section {
    display: none;
  }
  .topbar {
    padding: 10px 12px;
    flex-wrap: wrap;
    gap: 8px;
  }
  .topbar-left h1 {
    font-size: 16px;
  }
  .content {
    padding: 12px;
  }
  .topbar-right {
    gap: 8px;
    flex-wrap: wrap;
  }
  .user-info {
    font-size: 13px;
  }
  .btn-change-password {
    font-size: 12px;
    padding: 4px 8px;
  }
  .btn-change-password span {
    display: none;
  }
}
</style>