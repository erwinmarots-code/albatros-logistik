<template>
  <div class="chart-card">
    <div class="chart-header">
      <h3>Pendapatan & Pengeluaran (6 Bulan Terakhir)</h3>
      <div class="chart-legend">
        <span><span class="dot income-dot"></span> Pemasukan</span>
        <span><span class="dot expense-dot"></span> Pengeluaran</span>
      </div>
    </div>
    <div class="chart-wrapper" v-if="!loading">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
    <div v-else class="chart-loading">
      <i class="fas fa-spinner fa-spin"></i> Memuat grafik...
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'
import axios from '../../axios'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const loading = ref(true)
const chartData = ref({
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
  datasets: [
    {
      label: 'Pemasukan',
      backgroundColor: '#4f46e5',
      borderRadius: 6,
      data: [],
    },
    {
      label: 'Pengeluaran',
      backgroundColor: '#ef4444',
      borderRadius: 6,
      data: [],
    },
  ],
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value) => {
          if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'JT'
          if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(1) + 'RB'
          return 'Rp ' + value
        },
      },
    },
  },
}

const fetchChart = async () => {
  try {
    const res = await axios.get('/dashboard/chart')
    chartData.value = {
      labels: res.data.labels,
      datasets: [
        { ...chartData.value.datasets[0], data: res.data.income },
        { ...chartData.value.datasets[1], data: res.data.expense },
      ],
    }
  } catch (error) {
    console.error('Gagal memuat grafik:', error)
  } finally {
    loading.value = false
  }
}

onMounted(fetchChart)
</script>

<style scoped>
.chart-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  margin-top: 20px;
}
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.chart-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}
.chart-legend {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #6b7280;
}
.chart-legend .dot {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 4px;
  margin-right: 4px;
}
.dot.income-dot { background: #4f46e5; }
.dot.expense-dot { background: #ef4444; }
.chart-wrapper {
  height: 280px;
  position: relative;
}
.chart-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 280px;
  color: #6b7280;
  font-size: 14px;
}
.chart-loading i { margin-right: 8px; }
</style>