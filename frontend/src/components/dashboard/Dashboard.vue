<template>
  <div class="dashboard-page">
    <div class="page-header">
      <h1>📊 Dashboard</h1>
      <p>Selamat datang, {{ user?.name }}! Ini ringkasan aktivitas Albatros Makassar.</p>
    </div>

    <!-- Kartu Statistik -->
    <DashboardStats />

    <!-- Grafik & Transaksi Terakhir -->
    <div class="chart-recent-grid">
      <DashboardChart />
      <RecentTransactions />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DashboardStats from '@/components/dashboard/DashboardStats.vue'
import DashboardChart from '@/components/dashboard/DashboardChart.vue'
import RecentTransactions from '@/components/dashboard/RecentTransactions.vue'

const user = ref(JSON.parse(localStorage.getItem('user') || '{}'))
</script>

<style scoped>
.dashboard-page {
  padding: 10px 0;
}
.page-header {
  margin-bottom: 24px;
}
.page-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0 0 4px 0;
}
.page-header p {
  color: #6b7280;
  font-size: 16px;
  margin: 0;
}
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
</style>