<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-chart-pie"></i> Dashboard Keuangan</h2>
      <p class="module-subtitle">Ringkasan pemasukan & pengeluaran</p>
    </div>

    <!-- Toolbar dengan Filter Cepat -->
    <div class="toolbar">
      <div class="filter-group">
        <button
          v-for="(filter, key) in quickFilters"
          :key="key"
          :class="['btn-filter', { active: activeFilter === key }]"
          @click="applyQuickFilter(key)"
        >
          <i :class="filter.icon"></i> {{ filter.label }}
        </button>
      </div>
      <div class="filter-custom">
        <label>Dari: <input v-model="filter.from" type="date" @change="applyCustomFilter" /></label>
        <label>Sampai: <input v-model="filter.to" type="date" @change="applyCustomFilter" /></label>
      </div>
      <button @click="fetchData" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat Data
      </button>
      <button @click="exportExcel" class="btn-export">
        <i class="fas fa-file-excel"></i> Export Excel
      </button>
      <button @click="openForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Tambah Transaksi
      </button>
    </div>

    <!-- Summary Cards dengan Animasi Angka -->
    <div class="summary-cards">
      <div class="card income">
        <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="card-content">
          <h3>Pemasukan</h3>
          <CountUp
            v-if="summary.total_income !== undefined"
            :start-val="0"
            :end-val="summary.total_income || 0"
            :duration="1.5"
            :decimals="0"
            :prefix="'Rp '"
            :thousands-sep="'.'"
            :decimal-sep="','"
            class="card-value"
          />
          <small v-if="summary.income_change !== undefined" class="change positive">
            <i class="fas fa-arrow-up"></i> {{ summary.income_change }}% dari bulan lalu
          </small>
        </div>
      </div>
      <div class="card expense">
        <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="card-content">
          <h3>Pengeluaran</h3>
          <CountUp
            v-if="summary.total_expense !== undefined"
            :start-val="0"
            :end-val="summary.total_expense || 0"
            :duration="1.5"
            :decimals="0"
            :prefix="'Rp '"
            :thousands-sep="'.'"
            :decimal-sep="','"
            class="card-value"
          />
          <small v-if="summary.expense_change !== undefined" class="change negative">
            <i class="fas fa-arrow-down"></i> {{ summary.expense_change }}% dari bulan lalu
          </small>
        </div>
      </div>
      <div class="card balance">
        <div class="card-icon"><i class="fas fa-wallet"></i></div>
        <div class="card-content">
          <h3>Saldo</h3>
          <CountUp
            v-if="summary.balance !== undefined"
            :start-val="0"
            :end-val="summary.balance || 0"
            :duration="1.8"
            :decimals="0"
            :prefix="'Rp '"
            :thousands-sep="'.'"
            :decimal-sep="','"
            class="card-value"
            :style="{ color: summary.balance >= 0 ? '#22c55e' : '#ef4444' }"
          />
          <small class="change neutral">Periode terpilih</small>
        </div>
      </div>
    </div>

    <!-- Grafik Interaktif -->
    <div class="chart-section">
      <div class="chart-header">
        <h3>Pendapatan & Pengeluaran (6 Bulan Terakhir)</h3>
        <div class="chart-legend">
          <span><span class="dot income-dot"></span> Pemasukan</span>
          <span><span class="dot expense-dot"></span> Pengeluaran</span>
        </div>
      </div>
      <div class="chart-wrapper" v-if="!chartLoading">
        <Bar :data="chartData" :options="chartOptions" />
      </div>
      <div v-else class="chart-loading">
        <i class="fas fa-spinner fa-spin"></i> Memuat grafik...
      </div>
    </div>

    <!-- Form Tambah/Edit Transaksi -->
    <div v-if="showForm" class="form-container">
      <h3><i class="fas fa-edit"></i> {{ editingTransId ? 'Edit Transaksi' : 'Tambah Transaksi Manual' }}</h3>
      <form @submit.prevent="saveTransaction">
        <div class="form-group">
          <label><i class="fas fa-calendar-alt"></i> Tanggal <span class="required">*</span></label>
          <input v-model="transForm.transaction_date" type="date" required />
        </div>
        <div class="form-group">
          <label><i class="fas fa-exchange-alt"></i> Tipe <span class="required">*</span></label>
          <select v-model="transForm.type" required>
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-tags"></i> Kategori <span class="required">*</span></label>
          <select v-model="transForm.category" required>
            <option value="client_payment">Pembayaran Client</option>
            <option value="service">Service</option>
            <option value="fuel">BBM</option>
            <option value="toll">Tol</option>
            <option value="parking">Parkir</option>
            <option value="salary">Gaji</option>
            <option value="other">Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-money-bill-wave"></i> Nominal <span class="required">*</span></label>
          <input v-model="transForm.amount" type="number" required placeholder="0" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-pencil-alt"></i> Deskripsi</label>
          <input v-model="transForm.description" placeholder="Keterangan" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-car"></i> Kendaraan</label>
          <select v-model="transForm.vehicle_id">
            <option value="">Pilih Kendaraan</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
          <button type="button" @click="closeForm" class="btn-cancel"><i class="fas fa-times"></i> Batal</button>
        </div>
      </form>
    </div>

    <!-- Tabel Transaksi dengan Search -->
    <div class="table-wrapper" v-if="filteredTransactions.length">
      <table class="modern-table">
        <thead>
          <tr>
            <th>#</th>
            <th><i class="fas fa-calendar-alt"></i> Tanggal</th>
            <th><i class="fas fa-exchange-alt"></i> Tipe</th>
            <th><i class="fas fa-tags"></i> Kategori</th>
            <th><i class="fas fa-money-bill-wave"></i> Nominal</th>
            <th>Deskripsi</th>
            <th><i class="fas fa-car"></i> Kendaraan</th>
            <th class="text-center"><i class="fas fa-cogs"></i> Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in filteredTransactions" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td>{{ item.transaction_date }}</td>
            <td>
              <span :class="'type-badge-' + item.type">
                {{ item.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
              </span>
            </td>
            <td>{{ item.category }}</td>
            <td>{{ formatRupiah(item.amount) }}</td>
            <td>{{ item.description || '-' }}</td>
            <td>{{ item.vehicle?.plate_number || '-' }}</td>
            <td class="action-cell">
              <button @click="editTransaction(item)" class="btn-edit"><i class="fas fa-edit"></i></button>
              <button @click="deleteTransaction(item.id)" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message">{{ searchQuery ? 'Tidak ada transaksi yang cocok dengan pencarian.' : 'Belum ada transaksi keuangan.' }}</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import axios from '../axios'
import { formatRupiah } from '../utils/helpers'
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
import CountUp from 'vue-countup-v3'   

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

// ===== STATE =====
const transactions = ref([])
const vehicles = ref([])
const summary = ref({})
const chartLoading = ref(true)
const showForm = ref(false)
const editingTransId = ref(null)
const searchQuery = ref('')

const filter = reactive({
  from: '',
  to: '',
})

const activeFilter = ref('month') // default bulan ini

const quickFilters = {
  today: { label: 'Hari Ini', icon: 'fas fa-calendar-day' },
  week: { label: 'Minggu Ini', icon: 'fas fa-calendar-week' },
  month: { label: 'Bulan Ini', icon: 'fas fa-calendar-alt' },
  custom: { label: 'Kustom', icon: 'fas fa-calendar' },
}

const transForm = reactive({
  transaction_date: new Date().toISOString().split('T')[0],
  type: 'income',
  category: 'client_payment',
  amount: '',
  description: '',
  vehicle_id: '',
})

// ===== CHART OPTIONS =====
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    tooltip: {
      callbacks: {
        label: (context) => {
          let label = context.dataset.label || ''
          let value = context.parsed.y
          if (value >= 1000000) return label + ': Rp ' + (value / 1000000).toFixed(1) + ' JT'
          if (value >= 1000) return label + ': Rp ' + (value / 1000).toFixed(1) + ' RB'
          return label + ': Rp ' + value
        }
      }
    },
    legend: { display: false }
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

// ===== COMPUTED =====
const filteredTransactions = computed(() => {
  let data = transactions.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    data = data.filter(item =>
      item.category?.toLowerCase().includes(q) ||
      item.description?.toLowerCase().includes(q) ||
      item.type?.toLowerCase().includes(q) ||
      item.vehicle?.plate_number?.toLowerCase().includes(q)
    )
  }
  return data
})

// ===== FUNGSI FILTER =====
const applyQuickFilter = (key) => {
  activeFilter.value = key
  const now = new Date()
  let from, to
  switch (key) {
    case 'today':
      from = new Date(now)
      to = new Date(now)
      break
    case 'week':
      const day = now.getDay()
      from = new Date(now)
      from.setDate(now.getDate() - day)
      to = new Date(now)
      to.setDate(now.getDate() + (6 - day))
      break
    case 'month':
      from = new Date(now.getFullYear(), now.getMonth(), 1)
      to = new Date(now.getFullYear(), now.getMonth() + 1, 0)
      break
    default:
      return
  }
  filter.from = from.toISOString().split('T')[0]
  filter.to = to.toISOString().split('T')[0]
  fetchSummary()
}

const applyCustomFilter = () => {
  activeFilter.value = 'custom'
  fetchSummary()
}

// ===== FETCH DATA =====
const fetchTransactions = async () => {
  try {
    const res = await axios.get('/financial-transactions')
    transactions.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat transaksi:', error)
    alert('Gagal memuat transaksi: ' + error.message)
  }
}

const fetchSummary = async () => {
  try {
    const params = {}
    if (filter.from) params.from = filter.from
    if (filter.to) params.to = filter.to
    const res = await axios.get('/financial-summary', { params })
    summary.value = res.data || {}
  } catch (error) {
    console.error('Gagal memuat summary:', error)
    alert('Gagal memuat summary: ' + error.message)
  }
}

const fetchVehicles = async () => {
  try {
    const res = await axios.get('/vehicles')
    vehicles.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat kendaraan:', error)
    alert('Gagal memuat kendaraan: ' + error.message)
  }
}

const fetchChart = async () => {
  chartLoading.value = true
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
    chartLoading.value = false
  }
}

const fetchData = () => {
  fetchTransactions()
  fetchSummary()
  fetchChart()
}

// ===== CRUD TRANSAKSI =====
const openForm = (mode = 'add', data = null) => {
  showForm.value = true
  if (mode === 'add') {
    transForm.transaction_date = new Date().toISOString().split('T')[0]
    transForm.type = 'income'
    transForm.category = 'client_payment'
    transForm.amount = ''
    transForm.description = ''
    transForm.vehicle_id = ''
    editingTransId.value = null
  } else if (data) {
    Object.assign(transForm, data)
    editingTransId.value = data.id
  }
}

const closeForm = () => {
  showForm.value = false
  editingTransId.value = null
}

const saveTransaction = async () => {
  try {
    if (editingTransId.value) {
      await axios.put(`/financial-transactions/${editingTransId.value}`, transForm)
      alert('Transaksi berhasil diupdate!')
    } else {
      await axios.post('/financial-transactions', transForm)
      alert('Transaksi berhasil ditambahkan!')
    }
    closeForm()
    await fetchTransactions()
    await fetchSummary()
  } catch (error) {
    alert('Gagal menyimpan: ' + (error.response?.data?.message || error.message))
  }
}

const editTransaction = (item) => openForm('edit', item)
const deleteTransaction = async (id) => {
  if (!confirm('Yakin hapus transaksi ini?')) return
  try {
    await axios.delete(`/financial-transactions/${id}`)
    alert('Transaksi dihapus!')
    await fetchTransactions()
    await fetchSummary()
  } catch (error) {
    alert('Gagal hapus: ' + error.message)
  }
}

// ===== EXPORT EXCEL =====
const exportExcel = async () => {
  try {
    let url = '/export/financial-transactions'
    const params = new URLSearchParams()
    if (filter.from) params.append('from', filter.from)
    if (filter.to) params.append('to', filter.to)
    if (params.toString()) url += '?' + params.toString()

    const response = await axios.get(url, { responseType: 'blob' })
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = `laporan_keuangan_${new Date().toISOString().slice(0,10)}.xlsx`
    link.click()
    window.URL.revokeObjectURL(link.href)
  } catch (error) {
    alert('Gagal export: ' + error.message)
  }
}

// ===== WATCH FILTER =====
watch(() => [filter.from, filter.to], () => {
  if (activeFilter.value !== 'custom') return
  fetchSummary()
})

// ===== MOUNTED =====
onMounted(() => {
  // Set default filter: bulan ini
  const now = new Date()
  const from = new Date(now.getFullYear(), now.getMonth(), 1)
  const to = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  filter.from = from.toISOString().split('T')[0]
  filter.to = to.toISOString().split('T')[0]
  activeFilter.value = 'month'
  fetchData()
  fetchVehicles()
})
</script>

<style scoped>
/* ====== GAYA MODERN ====== */
.module-container { max-width: 1200px; margin: 0 auto; }
.module-header { margin-bottom: 24px; }
.module-header h2 { font-size: 28px; color: #0d2b45; display: flex; align-items: center; gap: 12px; }
.module-header h2 i { color: #1a4a7a; }
.module-subtitle { color: #6c757d; font-size: 14px; margin-top: 2px; }

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
  align-items: center;
}
.filter-group {
  display: flex;
  gap: 4px;
  background: #f1f3f5;
  padding: 4px;
  border-radius: 30px;
}
.btn-filter {
  padding: 6px 16px;
  border: none;
  border-radius: 30px;
  background: transparent;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
  color: #495057;
}
.btn-filter:hover { background: rgba(0,0,0,0.05); }
.btn-filter.active {
  background: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  color: #0d2b45;
}
.btn-filter i { margin-right: 4px; }

.filter-custom {
  display: flex;
  gap: 12px;
  align-items: center;
}
.filter-custom label {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  font-weight: 500;
}
.filter-custom input {
  padding: 6px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
}

.btn-add, .btn-refresh, .btn-export {
  padding: 8px 18px;
  border: none;
  border-radius: 30px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 13px;
}
.btn-add { background: #28a745; color: white; }
.btn-add:hover { background: #218838; transform: translateY(-1px); }
.btn-refresh { background: #17a2b8; color: white; }
.btn-refresh:hover { background: #138496; transform: translateY(-1px); }
.btn-export { background: #007bff; color: white; }
.btn-export:hover { background: #0069d9; transform: translateY(-1px); }

/* ===== SUMMARY CARDS ===== */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 28px;
}
.card {
  background: white;
  border-radius: 16px;
  padding: 20px 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.10);
}
.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}
.card.income .card-icon { background: #e6f7e6; color: #28a745; }
.card.expense .card-icon { background: #fde8e8; color: #dc3545; }
.card.balance .card-icon { background: #e6f2ff; color: #1a4a7a; }

.card-content { flex: 1; }
.card-content h3 { font-size: 13px; color: #6c757d; margin: 0 0 4px 0; font-weight: 500; }
.card-value { font-size: 22px; font-weight: 700; color: #0d2b45; }
.card .change { font-size: 12px; font-weight: 600; margin-left: 4px; }
.change.positive { color: #28a745; }
.change.negative { color: #dc3545; }
.change.neutral { color: #6c757d; }

/* ===== CHART ===== */
.chart-section {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  margin-bottom: 24px;
}
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.chart-header h3 { font-size: 16px; font-weight: 600; color: #0d2b45; margin: 0; }
.chart-legend {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #6c757d;
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
.chart-wrapper { height: 280px; position: relative; }
.chart-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 280px;
  color: #6c757d;
}
.chart-loading i { margin-right: 8px; }

/* ===== FORM ===== */
.form-container {
  background: white;
  border-radius: 16px;
  padding: 24px 28px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
  margin: 16px 0 24px;
}
.form-container h3 {
  font-size: 20px;
  color: #0d2b45;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 2px solid #e9ecef;
  padding-bottom: 12px;
  margin-bottom: 20px;
}
.form-container h3 i { color: #1a4a7a; }
.form-group {
  display: grid;
  grid-template-columns: 160px 1fr;
  align-items: center;
  gap: 14px;
  margin-bottom: 14px;
}
.form-group label {
  font-weight: 600;
  color: #2d3748;
  text-align: right;
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: flex-end;
}
.form-group label i { color: #1a4a7a; width: 20px; text-align: center; }
.required { color: #dc3545; margin-left: 2px; }
.form-group input, .form-group select {
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
  background: white;
  width: 100%;
  box-sizing: border-box;
}
.form-group input:focus, .form-group select:focus {
  outline: none;
  border-color: #1a4a7a;
  box-shadow: 0 0 0 3px rgba(26,74,122,0.12);
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e9ecef;
}
.btn-save, .btn-cancel {
  padding: 10px 28px;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}
.btn-save { background: #28a745; color: white; }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.3); }
.btn-cancel { background: #6c757d; color: white; }
.btn-cancel:hover { background: #5a6268; transform: translateY(-2px); }

/* ===== TABLE ===== */
.table-wrapper {
  overflow-x: auto;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
  padding: 4px 0;
  margin-top: 16px;
}
.modern-table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 700px; }
.modern-table thead { background: #f8fafc; border-bottom: 2px solid #e9ecef; }
.modern-table thead th { padding: 14px 16px; text-align: left; font-weight: 700; color: #2d3748; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
.modern-table thead th i { margin-right: 6px; color: #1a4a7a; }
.modern-table tbody tr { border-bottom: 1px solid #f1f3f5; transition: background 0.15s ease; }
.modern-table tbody tr:hover { background: #f8fafc; }
.modern-table tbody td { padding: 12px 16px; color: #2d3748; vertical-align: middle; }
.modern-table tbody td:first-child { font-weight: 600; color: #6c757d; width: 40px; text-align: center; }
.text-center { text-align: center; }
.action-cell { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }

.type-badge-income {
  background: #28a745;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.type-badge-expense {
  background: #dc3545;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.btn-edit, .btn-delete {
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

.empty-message {
  text-align: center;
  padding: 40px 20px;
  color: #6c757d;
  font-size: 16px;
  background: #f8f9fa;
  border-radius: 16px;
}
.empty-message i { font-size: 40px; display: block; margin-bottom: 12px; color: #dee2e6; }

@media (max-width: 768px) {
  .form-group { grid-template-columns: 1fr; gap: 4px; }
  .form-group label { text-align: left; justify-content: flex-start; }
  .modern-table { font-size: 13px; min-width: 500px; }
  .modern-table thead th, .modern-table tbody td { padding: 10px 12px; }
  .action-cell { gap: 4px; }
}
</style>