<template>
  <div class="dashboard">
    <div class="page-header">
      <h2><i class="fas fa-chart-pie"></i> Dashboard</h2>
      <p class="greeting">Selamat datang, {{ user?.name || 'User' }}!</p>
      <div class="header-divider"></div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" @click="goTo('clients')">
        <i class="fas fa-building stat-icon"></i>
        <div class="stat-info">
          <span class="stat-value">{{ stats.clients }}</span>
          <span class="stat-label">Client</span>
        </div>
      </div>
      <div class="stat-card" @click="goTo('projects')">
        <i class="fas fa-folder-open stat-icon"></i>
        <div class="stat-info">
          <span class="stat-value">{{ stats.projects }}</span>
          <span class="stat-label">Project</span>
        </div>
      </div>
      <div class="stat-card" @click="goTo('delivery-tasks')">
        <i class="fas fa-tasks stat-icon"></i>
        <div class="stat-info">
          <span class="stat-value">{{ stats.tasks }}</span>
          <span class="stat-label">Tugas</span>
        </div>
      </div>
      <div class="stat-card" @click="goTo('vehicles')">
        <i class="fas fa-car stat-icon"></i>
        <div class="stat-info">
          <span class="stat-value">{{ stats.vehicles }}</span>
          <span class="stat-label">Kendaraan</span>
        </div>
      </div>
      <div class="stat-card" @click="goTo('drivers')">
        <i class="fas fa-user-tie stat-icon"></i>
        <div class="stat-info">
          <span class="stat-value">{{ stats.drivers }}</span>
          <span class="stat-label">Driver</span>
        </div>
      </div>
    </div>

    <div class="recent-section" v-if="recentActivities.length">
      <h3><i class="fas fa-clock"></i> Aktivitas Terbaru</h3>
      <ul>
        <li v-for="act in recentActivities" :key="act.id">
          <span class="act-icon"><i :class="act.icon"></i></span>
          {{ act.message }}
          <span class="act-time">{{ act.time }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../../axios'

const props = defineProps({
  user: Object
})

const emit = defineEmits(['navigate'])

const stats = ref({
  clients: 0,
  projects: 0,
  tasks: 0,
  vehicles: 0,
  drivers: 0
})

const recentActivities = ref([])

const goTo = (page) => {
  emit('navigate', page)
}

const fetchStats = async () => {
  try {
    const res = await axios.get('/dashboard/stats')
    const data = res.data
    stats.value = {
      clients: data.total_clients || 0,
      projects: data.total_projects || 0,
      tasks: data.total_tasks || 0,
      vehicles: data.total_vehicles || 0,
      drivers: data.total_drivers || 0
    }

    // Contoh aktivitas (bisa diganti dengan data dari API)
    recentActivities.value = [
      { id: 1, icon: 'fas fa-plus-circle', message: 'Client baru ditambahkan', time: '5 menit lalu' },
      { id: 2, icon: 'fas fa-truck', message: 'Tugas pengantaran selesai', time: '10 menit lalu' }
    ]
  } catch (error) {
    console.error('Gagal memuat statistik:', error)
  }
}

onMounted(fetchStats)
</script>

<style scoped>
.dashboard {
  padding: 10px 0;
}

.page-header {
  margin-bottom: 28px;
}

.page-header h2 {
  font-size: 26px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0 0 4px 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-header h2 i {
  color: #1a4a7a;
}

.greeting {
  font-size: 18px;
  color: #4a5568;
  margin: 0 0 12px 0;
}

.header-divider {
  height: 3px;
  background: linear-gradient(90deg, #1a4a7a, #e6f0fa);
  border-radius: 4px;
  max-width: 200px;
}

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
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  border-left: 4px solid #1a4a7a;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);
}

.stat-icon {
  font-size: 32px;
  color: #1a4a7a;
  width: 48px;
  text-align: center;
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #0d2b45;
  line-height: 1.2;
}

.stat-label {
  font-size: 14px;
  color: #6c757d;
  font-weight: 500;
}

.recent-section {
  background: #f8fafc;
  border-radius: 16px;
  padding: 20px;
  margin-top: 20px;
}

.recent-section h3 {
  font-size: 18px;
  color: #0d2b45;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.recent-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.recent-section li {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid #e9ecef;
}

.act-icon {
  color: #1a4a7a;
  width: 24px;
  text-align: center;
}

.act-time {
  margin-left: auto;
  color: #6c757d;
  font-size: 13px;
}

@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }
  .stat-card {
    padding: 16px;
  }
}
</style>