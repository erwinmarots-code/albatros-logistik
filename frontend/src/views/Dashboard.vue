<template>
  <div class="dashboard-page">
    <!-- ===== HEADER DASHBOARD ===== -->
    <div class="page-header">
      <h1><i class="fas fa-chart-pie"></i> Dashboard</h1>
      <p class="greeting">Selamat bekerja, {{ userName }}!</p>
      <div class="header-divider"></div>
    </div>

    <!-- ===== UNTUK ADMIN FINANCE & SUPER ADMIN ===== -->
    <template v-if="isFinanceOrSuperAdmin">
      <DashboardStats />
      <div class="chart-recent-grid">
        <DashboardChart />
        <RecentTransactions />
      </div>
    </template>

    <!-- ===== UNTUK ADMIN PO & ADMIN TRANSPORT ===== -->
    <div v-else class="non-finance-dashboard">
      <!-- Statistik Cards -->
      <div class="stats-grid">
        <div v-for="stat in statsList" :key="stat.label" class="stat-card" :style="{ borderLeftColor: stat.color }">
          <div class="stat-icon" :style="{ background: stat.color }">
            <i :class="stat.icon"></i>
          </div>
          <div class="stat-content">
            <span class="stat-number">{{ stat.value }}</span>
            <span class="stat-label">{{ stat.label }}</span>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="quick-links-grid">
        <router-link
          v-for="link in quickLinks"
          :key="link.path"
          :to="link.path"
          class="quick-link"
          :style="{ '--hover-color': link.color }"
        >
          <div class="quick-icon" :style="{ background: link.color }">
            <i :class="link.icon"></i>
          </div>
          <span class="quick-label">{{ link.label }}</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from '../axios'
import DashboardStats from '@/components/dashboard/DashboardStats.vue'
import DashboardChart from '@/components/dashboard/DashboardChart.vue'
import RecentTransactions from '@/components/dashboard/RecentTransactions.vue'

const user = ref(JSON.parse(localStorage.getItem('user') || '{}'))
const statsData = ref({
  projects: 0,
  tasks: 0,
  vehicles: 0,
  drivers: 0,
})

const userName = computed(() => {
  const name = user.value?.name || 'User'
  return name.replace(/^Admin\s+/i, '').trim()
})

const isFinanceOrSuperAdmin = computed(() => {
  const role = user.value?.role
  return role === 'admin_finance' || role === 'super_admin'
})

// ===== STATISTIK UNTUK CARD =====
const statsList = computed(() => [
  { label: 'Total Project', value: statsData.value.projects, icon: 'fas fa-folder-open', color: '#4f46e5' },
  { label: 'Total Tugas', value: statsData.value.tasks, icon: 'fas fa-tasks', color: '#0ea5e9' },
  { label: 'Total Kendaraan', value: statsData.value.vehicles, icon: 'fas fa-car', color: '#22c55e' },
  { label: 'Total Driver', value: statsData.value.drivers, icon: 'fas fa-user-tie', color: '#eab308' },
])

// ===== FETCH STATISTIK DARI API =====
const fetchStats = async () => {
  try {
    const res = await axios.get('/dashboard/stats')
    const data = res.data
    statsData.value = {
      projects: data.total_projects || 0,
      tasks: data.total_tasks || 0,
      vehicles: data.total_vehicles || 0,
      drivers: data.total_drivers || 0,
    }
  } catch (error) {
    console.error('Gagal memuat statistik:', error)
  }
}

// ===== QUICK LINKS BERDASARKAN ROLE =====
const quickLinks = computed(() => {
  const role = user.value?.role

  if (role === 'admin_po') {
    return [
      { label: 'Project', path: '/projects', icon: 'fas fa-folder-open', color: '#4f46e5' },
      { label: 'Tugas', path: '/delivery-tasks', icon: 'fas fa-tasks', color: '#0ea5e9' },
      { label: 'Kendaraan', path: '/vehicles', icon: 'fas fa-car', color: '#22c55e' },
      { label: 'Driver', path: '/drivers', icon: 'fas fa-user-tie', color: '#eab308' },
      { label: 'Histori Perawatan', path: '/history', icon: 'fas fa-history', color: '#6b7280' },
    ]
  }

  if (role === 'admin_transport') {
    return [
      { label: 'Project', path: '/projects', icon: 'fas fa-folder-open', color: '#4f46e5' },
      { label: 'Tugas', path: '/delivery-tasks', icon: 'fas fa-tasks', color: '#0ea5e9' },
      { label: 'Biaya', path: '/fuel-expenses', icon: 'fas fa-coins', color: '#eab308' },
      { label: 'Kendaraan', path: '/vehicles', icon: 'fas fa-car', color: '#22c55e' },
      { label: 'Driver', path: '/drivers', icon: 'fas fa-user-tie', color: '#6f42c1' },
      { label: 'Pengajuan Perawatan', path: '/requests', icon: 'fas fa-tools', color: '#dc2626' },
      { label: 'Histori Perawatan', path: '/history', icon: 'fas fa-history', color: '#6b7280' },
    ]
  }

  return []
})

onMounted(() => {
  if (!isFinanceOrSuperAdmin.value) {
    fetchStats()
  }
})
</script>

<style scoped>
/* ====== GAYA SAMA SEPERTI SEBELUMNYA ====== */
.dashboard-page { padding: 10px 0; }

.page-header { margin-bottom: 28px; }
.page-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0 0 4px 0;
  display: flex;
  align-items: center;
  gap: 12px;
}
.page-header h1 i { color: #1a4a7a; }
.greeting {
  font-size: 18px;
  color: #4a5568;
  margin: 0 0 12px 0;
}
.header-divider {
  height: 3px;
  background: linear-gradient(90deg, #1a4a7a, #e6f0fa);
  border-radius: 4px;
  margin-top: 8px;
  max-width: 200px;
}

/* ===== STATISTIK CARDS ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}
.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  border-left: 4px solid #1a4a7a;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.10);
}
.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
  flex-shrink: 0;
}
.stat-content {
  display: flex;
  flex-direction: column;
}
.stat-number {
  font-size: 24px;
  font-weight: 700;
  color: #0d2b45;
  line-height: 1.2;
}
.stat-label {
  font-size: 13px;
  color: #6c757d;
  font-weight: 500;
}

/* ===== QUICK LINKS ===== */
.quick-links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 16px;
}
.quick-link {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 24px 16px;
  background: white;
  border-radius: 16px;
  text-decoration: none;
  color: #0d2b45;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  transition: transform 0.2s, box-shadow 0.2s;
  border-bottom: 3px solid transparent;
}
.quick-link:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  border-bottom-color: var(--hover-color, #1a4a7a);
}
.quick-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}
.quick-label {
  font-weight: 600;
  font-size: 14px;
  text-align: center;
}

/* ===== CHART & RECENT (Finance) ===== */
.chart-recent-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  margin-top: 20px;
}
@media (min-width: 1024px) {
  .chart-recent-grid {
    grid-template-columns: 2fr 1fr;
  }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }
  .quick-links-grid {
    grid-template-columns: 1fr 1fr;
  }
  .quick-link {
    padding: 16px 12px;
  }
}
</style>