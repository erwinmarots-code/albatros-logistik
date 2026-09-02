<template>
  <div class="dashboard-container">
    <!-- HEADER -->
    <div class="dashboard-header">
      <div class="welcome">
        <h1>Dashboard</h1>
        <p>Selamat datang, {{ user?.name || 'User' }}</p>
      </div>
      <div class="logo-container">
        <div class="logo-image"></div>
      </div>
    </div>

    <!-- STATISTIK UTAMA -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
        <div class="stat-content">
          <span class="stat-label">Total Project</span>
          <span class="stat-value">{{ stats.projects || 0 }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-truck"></i></div>
        <div class="stat-content">
          <span class="stat-label">Total Kendaraan</span>
          <span class="stat-value">{{ stats.vehicles || 0 }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-content">
          <span class="stat-label">Total Driver</span>
          <span class="stat-value">{{ stats.drivers || 0 }}</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-tasks"></i></div>
        <div class="stat-content">
          <span class="stat-label">Total Tugas</span>
          <span class="stat-value">{{ stats.tasks || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- RINGKASAN KEUANGAN (hanya untuk super_admin & admin_finance) -->
    <div v-if="canAccessFinance" class="finance-summary">
      <div class="finance-card income">
        <div class="finance-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="finance-content">
          <span class="finance-label">Pemasukan</span>
          <span class="finance-value">{{ formatCurrency(finance.total_income) }}</span>
        </div>
      </div>
      <div class="finance-card expense">
        <div class="finance-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="finance-content">
          <span class="finance-label">Pengeluaran</span>
          <span class="finance-value">{{ formatCurrency(finance.total_expense) }}</span>
        </div>
      </div>
      <div class="finance-card balance">
        <div class="finance-icon"><i class="fas fa-wallet"></i></div>
        <div class="finance-content">
          <span class="finance-label">Saldo Akhir</span>
          <span class="finance-value" :style="{ color: finance.balance >= 0 ? '#22c55e' : '#dc2626' }">
            {{ formatCurrency(finance.balance) }}
          </span>
        </div>
      </div>
      <div class="finance-card outstanding">
        <div class="finance-icon"><i class="fas fa-file-invoice"></i></div>
        <div class="finance-content">
          <span class="finance-label">Outstanding PO</span>
          <span class="finance-value">{{ finance.outstanding_count || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- GRAFIK 6 BULAN TERAKHIR -->
    <div v-if="canAccessFinance" class="chart-card">
      <div class="chart-header">
        <h3>Pendapatan & Pengeluaran 6 Bulan Terakhir</h3>
        <div class="chart-legend">
          <span><span class="dot income-dot"></span> Pemasukan</span>
          <span><span class="dot expense-dot"></span> Pengeluaran</span>
        </div>
      </div>
      <div class="chart-wrapper">
        <canvas id="dashboardChart"></canvas>
      </div>
    </div>

    <!-- TABEL TRANSAKSI TERBARU -->
    <div v-if="canAccessFinance" class="table-card">
      <div class="table-header">
        <h3><i class="fas fa-history"></i> Transaksi Terbaru</h3>
        <router-link to="/financial" class="btn-link">Lihat Semua →</router-link>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Tipe</th>
              <th>Kategori</th>
              <th>Nominal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingTransactions"><td colspan="4" class="text-center">Memuat...</td></tr>
            <tr v-else-if="!recentTransactions.length"><td colspan="4" class="text-center">Belum ada transaksi</td></tr>
            <tr v-for="tx in recentTransactions" :key="tx.id">
              <td>{{ formatDate(tx.transaction_date) }}</td>
              <td>
                <span class="badge" :class="tx.type === 'income' ? 'badge-income' : 'badge-expense'">
                  {{ tx.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                </span>
              </td>
              <td>{{ tx.category || '-' }}</td>
              <td class="currency">{{ formatCurrency(tx.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PROJECT TERBARU -->
    <div class="table-card">
      <div class="table-header">
        <h3><i class="fas fa-folder-open"></i> Project Terbaru</h3>
        <router-link to="/projects" class="btn-link">Lihat Semua →</router-link>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>No PO</th>
              <th>Client</th>
              <th>Status</th>
              <th>Nilai</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingProjects"><td colspan="4" class="text-center">Memuat...</td></tr>
            <tr v-else-if="!recentProjects.length"><td colspan="4" class="text-center">Belum ada project</td></tr>
            <tr v-for="p in recentProjects" :key="p.id">
              <td><strong>{{ p.no_po }}</strong></td>
              <td>{{ p.client?.name || '-' }}</td>
              <td><span class="badge" :class="statusBadge(p.status)">{{ p.status }}</span></td>
              <td>{{ formatCurrency(p.contract_value) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PENGAJUAN BIAYA PENDING -->
    <div v-if="canAccessFinance || canAccessTransport" class="table-card">
      <div class="table-header">
        <h3><i class="fas fa-coins"></i> Pengajuan Biaya Pending</h3>
        <router-link to="/fuel-expenses" class="btn-link">Lihat Semua →</router-link>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>No. Resi</th>
              <th>Jenis</th>
              <th>Nominal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingFuel"><td colspan="4" class="text-center">Memuat...</td></tr>
            <tr v-else-if="!pendingFuel.length"><td colspan="4" class="text-center">Tidak ada pengajuan pending</td></tr>
            <tr v-for="f in pendingFuel" :key="f.id">
              <td><strong>{{ f.unique_code }}</strong></td>
              <td>{{ f.delivery_task?.no_resi || '-' }}</td>
              <td>{{ f.type_label || f.type }}</td>
              <td>{{ formatCurrency(f.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

export default {
  name: 'Dashboard',
  data() {
    return {
      user: JSON.parse(localStorage.getItem('user') || '{}'),
      stats: {},
      finance: {},
      recentTransactions: [],
      recentProjects: [],
      pendingFuel: [],
      loadingTransactions: false,
      loadingProjects: false,
      loadingFuel: false,
      chartInstance: null,
    }
  },
  computed: {
    canAccessFinance() {
      const role = this.user?.role
      return ['super_admin', 'admin_finance'].includes(role)
    },
    canAccessTransport() {
      const role = this.user?.role
      return ['super_admin', 'admin_transport'].includes(role)
    },
  },
  mounted() {
    this.loadAllData()
  },
  beforeUnmount() {
    if (this.chartInstance) {
      this.chartInstance.destroy()
    }
  },
  methods: {
    formatCurrency(val) {
      if (!val) return 'Rp 0'
      return 'Rp ' + Number(val).toLocaleString('id-ID')
    },
    formatDate(date) {
      if (!date) return '-'
      const d = new Date(date)
      if (isNaN(d.getTime())) return '-'
      return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
    },
    statusBadge(status) {
      const map = {
        draft: 'badge-secondary',
        confirmed: 'badge-info',
        on_delivery: 'badge-warning',
        completed: 'badge-success',
        cancelled: 'badge-danger',
      }
      return map[status] || 'badge-secondary'
    },

    async loadAllData() {
      await this.loadStats()
      if (this.canAccessFinance) {
        await this.loadFinance()
        await this.loadTransactions()
        await this.loadChart()
      }
      await this.loadProjects()
      if (this.canAccessFinance || this.canAccessTransport) {
        await this.loadPendingFuel()
      }
    },

    async loadStats() {
      try {
        const res = await axios.get('/dashboard/stats')
        this.stats = res.data.data || {}
      } catch (e) {
        console.error('Error loading stats:', e)
      }
    },

    async loadFinance() {
      try {
        const res = await axios.get('/financial-summary')
        this.finance = res.data.data || res.data || {}
      } catch (e) {
        console.error('Error loading finance:', e)
      }
    },

    async loadTransactions() {
      this.loadingTransactions = true
      try {
        const res = await axios.get('/financial-transactions?limit=5&sort=desc')
        this.recentTransactions = res.data.data || []
      } catch (e) {
        console.error('Error loading transactions:', e)
      } finally {
        this.loadingTransactions = false
      }
    },

    async loadProjects() {
      this.loadingProjects = true
      try {
        const res = await axios.get('/projects?limit=5&sort=desc')
        this.recentProjects = res.data.data || []
      } catch (e) {
        console.error('Error loading projects:', e)
      } finally {
        this.loadingProjects = false
      }
    },

    async loadPendingFuel() {
      this.loadingFuel = true
      try {
        const res = await axios.get('/fuel-expenses?status=pending&limit=5')
        this.pendingFuel = res.data.data || []
      } catch (e) {
        console.error('Error loading pending fuel:', e)
      } finally {
        this.loadingFuel = false
      }
    },

    async loadChart() {
      try {
        const res = await axios.get('/financial/chart')
        const labels = res.data.labels || []
        const income = res.data.income || []
        const expense = res.data.expense || []
        if (labels.length) {
          this.renderChart(labels, income, expense)
        }
      } catch (e) {
        console.error('Error loading chart:', e)
      }
    },

    renderChart(labels, income, expense) {
      const ctx = document.getElementById('dashboardChart')
      if (!ctx) return
      if (this.chartInstance) {
        this.chartInstance.destroy()
      }
      this.chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            { label: 'Pemasukan', data: income, backgroundColor: '#4f46e5', borderRadius: 6 },
            { label: 'Pengeluaran', data: expense, backgroundColor: '#ef4444', borderRadius: 6 },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (value) => {
                  if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + ' JT'
                  if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(1) + ' RB'
                  return 'Rp ' + value
                },
              },
            },
          },
        },
      })
    },
  },
}
</script>

<style scoped>
.dashboard-container {
  padding: 20px;
}

/* HEADER */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}
.dashboard-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0;
}
.dashboard-header p {
  color: #6b7280;
  margin: 4px 0 0 0;
}
.logo-container {
  flex-shrink: 0;
}
.logo-image {
  height: 60px;
  width: 120px;
  background: url('/images/albatros_logo_new.jpg') no-repeat center;
  background-size: contain;
  background-color: white;
  padding: 6px 12px;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* STATISTIK */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.stat-card {
  background: white;
  padding: 20px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}
.stat-icon {
  width: 48px;
  height: 48px;
  background: #dbeafe;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #2b6cb0;
  font-size: 20px;
}
.stat-content {
  display: flex;
  flex-direction: column;
}
.stat-label {
  font-size: 13px;
  color: #6b7280;
}
.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #1a202c;
}

/* KEUANGAN */
.finance-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.finance-card {
  background: white;
  padding: 16px 20px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}
.finance-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 18px;
  flex-shrink: 0;
}
.finance-card.income .finance-icon { background: #4f46e5; }
.finance-card.expense .finance-icon { background: #ef4444; }
.finance-card.balance .finance-icon { background: #22c55e; }
.finance-card.outstanding .finance-icon { background: #eab308; }
.finance-content {
  display: flex;
  flex-direction: column;
}
.finance-label {
  font-size: 12px;
  color: #6b7280;
}
.finance-value {
  font-size: 20px;
  font-weight: 700;
  color: #1a202c;
}

/* CHART */
.chart-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  margin-bottom: 24px;
}
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.chart-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #0d2b45;
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
.income-dot { background: #4f46e5; }
.expense-dot { background: #ef4444; }
.chart-wrapper {
  height: 260px;
  position: relative;
}

/* TABEL */
.table-card {
  background: white;
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  margin-bottom: 24px;
}
.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.table-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #0d2b45;
  margin: 0;
}
.table-header h3 i {
  color: #2b6cb0;
  margin-right: 8px;
}
.btn-link {
  color: #2b6cb0;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
}
.btn-link:hover {
  text-decoration: underline;
}
.table-wrapper {
  overflow-x: auto;
}
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.table thead {
  background: #f7fafc;
  border-bottom: 2px solid #e2e8f0;
}
.table th {
  padding: 8px 12px;
  text-align: left;
  font-weight: 600;
  color: #2d3748;
  white-space: nowrap;
}
.table td {
  padding: 8px 12px;
  border-bottom: 1px solid #f1f3f5;
  vertical-align: middle;
}
.table tbody tr:hover {
  background: #f7fafc;
}
.text-center {
  text-align: center;
}
.currency {
  font-weight: 600;
  color: #1a202c;
}

/* BADGE */
.badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}
.badge-income {
  background: #dbeafe;
  color: #1e40af;
}
.badge-expense {
  background: #fee2e2;
  color: #991b1b;
}
.badge-secondary {
  background: #e2e8f0;
  color: #475569;
}
.badge-info {
  background: #dbeafe;
  color: #1e40af;
}
.badge-warning {
  background: #fef3c7;
  color: #92400e;
}
.badge-success {
  background: #d1fae5;
  color: #065f46;
}
.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

@media (max-width: 768px) {
  .finance-summary {
    grid-template-columns: 1fr 1fr;
  }
}
</style>