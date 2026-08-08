<template>
  <div class="dashboard-container">
    <h2><i class="fas fa-chart-pie"></i> Dashboard</h2>
    <p class="subtitle">Ringkasan data dan statistik</p>

    <!-- Statistik Card -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-car"></i></div>
        <div class="stat-info">
          <span class="stat-value">{{ stats.total_vehicles || 0 }}</span>
          <span class="stat-label">Total Kendaraan</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-info">
          <span class="stat-value">{{ stats.total_drivers || 0 }}</span>
          <span class="stat-label">Total Driver</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-building"></i></div>
        <div class="stat-info">
          <span class="stat-value">{{ stats.total_clients || 0 }}</span>
          <span class="stat-label">Total Client</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-tools"></i></div>
        <div class="stat-info">
          <span class="stat-value">{{ stats.pending_requests || 0 }}</span>
          <span class="stat-label">Pengajuan Pending</span>
        </div>
      </div>
    </div>

    <!-- Keuangan -->
    <div class="finance-summary">
      <h3><i class="fas fa-money-bill-wave"></i> Keuangan Bulan Ini</h3>
      <div class="finance-grid">
        <div class="finance-card income">
          <span>Pemasukan</span>
          <strong>{{ formatRupiah(stats.income) }}</strong>
        </div>
        <div class="finance-card expense">
          <span>Pengeluaran</span>
          <strong>{{ formatRupiah(stats.expense) }}</strong>
        </div>
        <div class="finance-card balance">
          <span>Saldo</span>
          <strong :style="{ color: stats.balance >= 0 ? 'green' : 'red' }">
            {{ formatRupiah(stats.balance) }}
          </strong>
        </div>
      </div>
    </div>

    <!-- Grafik -->
    <div class="chart-container">
      <h3><i class="fas fa-chart-bar"></i> Pengajuan Perawatan (6 Bulan Terakhir)</h3>
      <canvas ref="chartCanvas" width="400" height="200"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../axios'
import { Chart, registerables } from 'chart.js'
import { formatRupiah } from '../utils/helpers'

Chart.register(...registerables)

const stats = ref({})
const chartCanvas = ref(null)
let chartInstance = null

const fetchDashboard = async () => {
  try {
    const res = await axios.get('/api/dashboard')
    stats.value = res.data
    renderChart()
  } catch (error) {
    alert('Gagal memuat dashboard: ' + error.message)
  }
}

const renderChart = () => {
  if (chartInstance) {
    chartInstance.destroy()
  }

  if (!chartCanvas.value) return

  const ctx = chartCanvas.value.getContext('2d')
  const labels = stats.value.chart_data?.map(item => item.month) || []
  const data = stats.value.chart_data?.map(item => item.total) || []

  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Jumlah Pengajuan',
        data: data,
        backgroundColor: 'rgba(26, 74, 122, 0.6)',
        borderColor: 'rgba(26, 74, 122, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 }
        }
      }
    }
  })
}

onMounted(() => {
  fetchDashboard()
})
</script>

<style scoped>
.dashboard-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}
.dashboard-container h2 {
  font-size: 28px;
  color: #0d2b45;
}
.subtitle {
  color: #6c757d;
  font-size: 14px;
  margin-bottom: 24px;
}
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
  box-shadow: 0 2px 10px rgba(0,0,0,0.04);
  display: flex;
  align-items: center;
  gap: 16px;
}
.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #e9ecef;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #1a4a7a;
}
.stat-info {
  display: flex;
  flex-direction: column;
}
.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #0d2b45;
}
.stat-label {
  font-size: 14px;
  color: #6c757d;
}
.finance-summary {
  background: white;
  border-radius: 16px;
  padding: 20px 24px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.04);
  margin-bottom: 30px;
}
.finance-summary h3 {
  margin-top: 0;
  margin-bottom: 16px;
  color: #0d2b45;
}
.finance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 20px;
}
.finance-card {
  text-align: center;
}
.finance-card span {
  display: block;
  font-size: 14px;
  color: #6c757d;
}
.finance-card strong {
  font-size: 22px;
}
.finance-card.income strong { color: #28a745; }
.finance-card.expense strong { color: #dc3545; }
.finance-card.balance strong { color: #17a2b8; }

.chart-container {
  background: white;
  border-radius: 16px;
  padding: 20px 24px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}
.chart-container h3 {
  margin-top: 0;
  margin-bottom: 16px;
  color: #0d2b45;
}
</style>