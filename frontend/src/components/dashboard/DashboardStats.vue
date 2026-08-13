<template>
  <div class="stats-grid">
    <div v-for="stat in stats" :key="stat.label" class="stat-card">
      <div class="stat-icon" :style="{ background: stat.color }">
        <i :class="stat.icon"></i>
      </div>
      <div class="stat-content">
        <p class="stat-label">{{ stat.label }}</p>
        <p class="stat-value">{{ stat.value }}</p>
        <small v-if="stat.change !== undefined" class="stat-change" :class="stat.change >= 0 ? 'positive' : 'negative'">
          {{ stat.change >= 0 ? '+' : '' }}{{ stat.change }}% dari bulan lalu
        </small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../../axios'
import { formatRupiah } from '../../utils/helpers'

const stats = ref([
  { label: 'Total Kendaraan', value: 0, icon: 'fas fa-car', color: '#4f46e5' },
  { label: 'Total Driver', value: 0, icon: 'fas fa-user-tie', color: '#0ea5e9' },
  { label: 'Project Selesai', value: 0, icon: 'fas fa-check-circle', color: '#22c55e' },
  { label: 'Pendapatan Bulan Ini', value: 'Rp 0', icon: 'fas fa-money-bill-wave', color: '#eab308', change: 0 },
])

const fetchStats = async () => {
  try {
    const res = await axios.get('/dashboard/stats')
    const data = res.data
    stats.value = [
      { label: 'Total Kendaraan', value: data.total_vehicles, icon: 'fas fa-car', color: '#4f46e5' },
      { label: 'Total Driver', value: data.total_drivers, icon: 'fas fa-user-tie', color: '#0ea5e9' },
      { label: 'Project Selesai', value: data.completed_projects, icon: 'fas fa-check-circle', color: '#22c55e' },
      { label: 'Pendapatan Bulan Ini', value: formatRupiah(data.monthly_income), icon: 'fas fa-money-bill-wave', color: '#eab308', change: data.income_change },
    ]
  } catch (error) {
    console.error('Gagal memuat statistik:', error)
  }
}

onMounted(fetchStats)
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}
.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  flex-shrink: 0;
}
.stat-content { flex: 1; min-width: 0; }
.stat-label {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
  font-weight: 500;
}
.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  margin: 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.stat-change {
  font-size: 12px;
  font-weight: 600;
}
.stat-change.positive { color: #22c55e; }
.stat-change.negative { color: #ef4444; }
</style>