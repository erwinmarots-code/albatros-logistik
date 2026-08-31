<template>
  <div class="financial-dashboard">
    <div class="page-header">
      <h2><i class="fas fa-chart-pie"></i> Dashboard Keuangan</h2>
      <div class="header-actions">
        <button
          v-if="canExport"
          class="btn btn-outline-export"
          @click="handleExport"
          :disabled="isExporting"
        >
          <i class="fas fa-file-excel"></i>
          {{ isExporting ? 'Mengekspor...' : 'Export Excel' }}
        </button>
        <button class="btn btn-primary" @click="openModal">
          <i class="fas fa-plus-circle"></i> Tambah Transaksi
        </button>
      </div>
    </div>

    <div class="summary-grid">
      <div class="summary-card income">
        <div class="summary-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="summary-content">
          <span class="summary-label">Total Pemasukan</span>
          <CountUp
            :start-val="0"
            :end-val="totalIncome"
            :options="{
              prefix: 'Rp ',
              decimal: ',',
              separator: '.',
              duration: 1.5,
            }"
            class="summary-value"
          />
        </div>
      </div>
      <div class="summary-card expense">
        <div class="summary-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="summary-content">
          <span class="summary-label">Total Pengeluaran</span>
          <CountUp
            :start-val="0"
            :end-val="totalExpense"
            :options="{
              prefix: 'Rp ',
              decimal: ',',
              separator: '.',
              duration: 1.5,
            }"
            class="summary-value"
          />
        </div>
      </div>
      <div class="summary-card balance">
        <div class="summary-icon"><i class="fas fa-wallet"></i></div>
        <div class="summary-content">
          <span class="summary-label">Saldo Akhir</span>
          <CountUp
            :start-val="0"
            :end-val="balance"
            :options="{
              prefix: 'Rp ',
              decimal: ',',
              separator: '.',
              duration: 1.5,
            }"
            class="summary-value"
            :style="{ color: balance >= 0 ? '#22c55e' : '#dc2626' }"
          />
        </div>
      </div>
      <div class="summary-card outstanding">
        <div class="summary-icon"><i class="fas fa-file-invoice"></i></div>
        <div class="summary-content">
          <span class="summary-label">Outstanding PO</span>
          <CountUp
            :start-val="0"
            :end-val="outstandingCount"
            :options="{
              suffix: ' Invoice',
              duration: 1.5,
            }"
            class="summary-value"
          />
        </div>
      </div>
    </div>

    <div class="chart-card">
      <div class="chart-header">
        <h3>Pendapatan & Pengeluaran 6 Bulan Terakhir</h3>
        <div class="chart-legend">
          <span><span class="dot income-dot"></span> Pemasukan</span>
          <span><span class="dot expense-dot"></span> Pengeluaran</span>
        </div>
      </div>
      <div class="chart-wrapper">
        <canvas id="financialChart"></canvas>
      </div>
    </div>

    <div class="table-card">
      <div class="table-header">
        <h3><i class="fas fa-history"></i> Transaksi Terbaru</h3>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Tipe</th>
              <th>Kategori</th>
              <th>Nominal</th>
              <th>Deskripsi</th>
              <th>No. PO / Resi</th>
              <th>No. Perawatan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingTransactions"><td colspan="7" class="text-center">Memuat data...</td></tr>
            <tr v-else-if="!recentTransactions.length"><td colspan="7" class="text-center">Belum ada transaksi</td></tr>
            <tr v-for="tx in recentTransactions" :key="tx.id">
              <td>{{ formatDate(tx.transaction_date) }}</td>
              <td>
                <span class="badge" :class="tx.type === 'income' ? 'badge-income' : 'badge-expense'">
                  {{ tx.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                </span>
              </td>
              <td>{{ tx.category || '-' }}</td>
              <td class="currency">{{ formatCurrency(tx.amount) }}</td>
              <td>{{ tx.description || '-' }}</td>
              <td>{{ tx.po_number || '-' }}</td>
              <td>{{ tx.maintenance_number || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class="fas fa-plus-circle"></i> Tambah Transaksi</h3>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <form @submit.prevent="saveTransaction" class="modal-form">
          <div class="form-grid">
            <div class="form-group">
              <label>Tipe Transaksi <span class="required">*</span></label>
              <select v-model="form.type" class="form-control" required>
                <option value="">Pilih Tipe</option>
                <option value="income">Pemasukan</option>
                <option value="expense">Pengeluaran</option>
              </select>
            </div>
            <div class="form-group">
              <label>Kategori <span class="required">*</span></label>
              <select v-model="form.category" class="form-control" @change="onCategoryChange" required>
                <option value="">Pilih Kategori</option>
                <option value="service">Service</option>
                <option value="fuel">Bahan Bakar</option>
                <option value="toll">Tol</option>
                <option value="parking">Parkir</option>
                <option value="salary">Gaji</option>
                <option value="client_payment">Pembayaran Client</option>
                <option value="other">Lainnya...</option>
              </select>
            </div>
            <div v-if="form.category === 'other'" class="form-group">
              <label>Kategori Lainnya <span class="required">*</span></label>
              <input v-model="form.custom_category" type="text" class="form-control" placeholder="Masukkan kategori lain" required />
            </div>
            <div class="form-group">
              <label>Nominal (Rp) <span class="required">*</span></label>
              <input v-model.number="form.amount" type="number" class="form-control" placeholder="0" required min="1" />
            </div>
            <div class="form-group">
              <label>Tanggal Transaksi <span class="required">*</span></label>
              <input v-model="form.transaction_date" type="date" class="form-control" required />
            </div>
            <div class="form-group">
              <label>No. PO / No. Resi</label>
              <input v-model="form.po_number" type="text" class="form-control" placeholder="Kosongkan jika tidak ada" />
            </div>
            <div class="form-group">
              <label>No. Pengajuan Perawatan</label>
              <input v-model="form.maintenance_number" type="text" class="form-control" placeholder="Kosongkan jika tidak ada" />
            </div>
            <div class="form-group full-width">
              <label>Deskripsi</label>
              <textarea v-model="form.description" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeModal">Batal</button>
            <button type="submit" class="btn btn-success" :disabled="loadingSubmit">
              <i v-if="loadingSubmit" class="fas fa-spinner fa-spin"></i>
              {{ loadingSubmit ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'
import { Chart, registerables } from 'chart.js'
import CountUp from 'vue-countup-v3'
import { useExport } from '../composables/useExport'

Chart.register(...registerables)

export default {
  name: 'FinancialDashboard',
  components: { CountUp },
  data() {
    return {
      totalIncome: 0,
      totalExpense: 0,
      balance: 0,
      outstandingCount: 0,
      chartLabels: [],
      chartIncome: [],
      chartExpense: [],
      chartInstance: null,
      recentTransactions: [],
      loadingTransactions: false,
      showModal: false,
      loadingSubmit: false,
      form: {
        type: '',
        category: '',
        custom_category: '',
        amount: 0,
        transaction_date: new Date().toISOString().slice(0, 10),
        description: '',
        po_number: '',
        maintenance_number: '',
      },
    }
  },
  computed: {
    user() {
      return JSON.parse(localStorage.getItem('user') || '{}')
    },
    canExport() {
      const role = this.user?.role
      return ['super_admin', 'admin_finance'].includes(role)
    },
  },
  setup() {
    const { isExporting, exportData } = useExport('financial-transactions')
    return { isExporting, exportData }
  },
  mounted() {
    this.loadSummary()
    this.loadChart()
    this.loadTransactions()
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
      return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    },

    async loadSummary() {
      try {
        const res = await axios.get('/financial-summary')
        const data = res.data.data || res.data || {}
        this.totalIncome = data.total_income || 0
        this.totalExpense = data.total_expense || 0
        this.balance = data.balance || 0
        this.outstandingCount = data.outstanding_count || 0
      } catch (e) {
        console.error('Error loading summary:', e)
      }
    },

    async loadChart() {
      try {
        const res = await axios.get('/financial/chart')
        this.chartLabels = res.data.labels || []
        this.chartIncome = res.data.income || []
        this.chartExpense = res.data.expense || []
        if (this.chartLabels.length) {
          this.renderChart()
        }
      } catch (e) {
        console.error('Error loading chart:', e)
      }
    },

    renderChart() {
      const ctx = document.getElementById('financialChart')
      if (!ctx) return
      if (this.chartInstance) {
        this.chartInstance.destroy()
      }
      this.chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: this.chartLabels,
          datasets: [
            { label: 'Pemasukan', data: this.chartIncome, backgroundColor: '#4f46e5', borderRadius: 6 },
            { label: 'Pengeluaran', data: this.chartExpense, backgroundColor: '#ef4444', borderRadius: 6 },
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

    async loadTransactions() {
      this.loadingTransactions = true
      try {
        const res = await axios.get('/financial-transactions?limit=10&sort=desc')
        this.recentTransactions = res.data.data || []
      } catch (e) {
        console.error('Error loading transactions:', e)
      } finally {
        this.loadingTransactions = false
      }
    },

    openModal() {
      this.form = {
        type: '',
        category: '',
        custom_category: '',
        amount: 0,
        transaction_date: new Date().toISOString().slice(0, 10),
        description: '',
        po_number: '',
        maintenance_number: '',
      }
      this.showModal = true
    },

    closeModal() {
      this.showModal = false
      this.loadingSubmit = false
    },

    onCategoryChange() {
      if (this.form.category !== 'other') {
        this.form.custom_category = ''
      }
    },

    async saveTransaction() {
      if (this.form.category === 'other' && !this.form.custom_category.trim()) {
        alert('Harap masukkan kategori lainnya')
        return
      }

      let finalCategory = this.form.category
      if (this.form.category === 'other') {
        finalCategory = this.form.custom_category.trim()
      }

      if (!this.form.type || !finalCategory || !this.form.amount || !this.form.transaction_date) {
        alert('Harap isi semua field yang wajib (bertanda *)')
        return
      }

      this.loadingSubmit = true
      try {
        const payload = {
          type: this.form.type,
          category: finalCategory,
          amount: this.form.amount,
          transaction_date: this.form.transaction_date,
          description: this.form.description || null,
          po_number: this.form.po_number || null,
          maintenance_number: this.form.maintenance_number || null,
        }

        await axios.post('/financial-transactions', payload)
        this.closeModal()
        this.loadSummary()
        this.loadChart()
        this.loadTransactions()
      } catch (e) {
        console.error('Error saving transaction:', e)
        alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loadingSubmit = false
      }
    },

    async handleExport() {
      await this.exportData({})
    },
  },
}
</script>

<style scoped>
/* ========================================================== */
/* GLOBAL */
/* ========================================================== */
.financial-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

/* ========================================================== */
/* HEADER */
/* ========================================================== */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 12px;
}
.page-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0;
}
.page-header h2 i {
  color: #2b6cb0;
  margin-right: 8px;
}
.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

/* ========================================================== */
/* BUTTONS */
/* ========================================================== */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-primary {
  background: #2b6cb0;
  color: white;
}
.btn-primary:hover {
  background: #1a4a7a;
  transform: translateY(-2px);
}
.btn-success {
  background: #22c55e;
  color: white;
}
.btn-success:hover {
  background: #16a34a;
}
.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}
.btn-secondary:hover {
  background: #cbd5e1;
}
.btn-close {
  background: transparent;
  border: none;
  font-size: 28px;
  line-height: 1;
  cursor: pointer;
  color: #6b7280;
}
.btn-close:hover {
  color: #dc2626;
}
.btn-outline-export {
  background: white;
  color: #2d3748;
  border: 1.5px solid #2b6cb0;
  padding: 8px 18px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: 0.2s;
}
.btn-outline-export:hover {
  background: #2b6cb0;
  color: white;
  transform: translateY(-2px);
}
.btn-outline-export:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* ========================================================== */
/* SUMMARY CARDS */
/* ========================================================== */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.summary-card {
  background: white;
  padding: 20px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  transition: 0.2s;
}
.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}
.summary-icon {
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
.summary-card.income .summary-icon {
  background: #4f46e5;
}
.summary-card.expense .summary-icon {
  background: #ef4444;
}
.summary-card.balance .summary-icon {
  background: #22c55e;
}
.summary-card.outstanding .summary-icon {
  background: #eab308;
}
.summary-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.summary-label {
  font-size: 13px;
  color: #6b7280;
  font-weight: 500;
}
.summary-value {
  font-size: 22px;
  font-weight: 700;
  color: #1a202c;
}

/* ========================================================== */
/* CHART */
/* ========================================================== */
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
.income-dot {
  background: #4f46e5;
}
.expense-dot {
  background: #ef4444;
}
.chart-wrapper {
  height: 280px;
  position: relative;
}

/* ========================================================== */
/* TABLE */
/* ========================================================== */
.table-card {
  background: white;
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}
.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
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
  padding: 10px 12px;
  text-align: left;
  font-weight: 600;
  color: #2d3748;
  white-space: nowrap;
}
.table td {
  padding: 10px 12px;
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

/* ========================================================== */
/* BADGE */
/* ========================================================== */
.badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.badge-income {
  background: #dbeafe;
  color: #1e40af;
}
.badge-expense {
  background: #fee2e2;
  color: #991b1b;
}

/* ========================================================== */
/* MODAL */
/* ========================================================== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}
.modal-card {
  background: white;
  border-radius: 20px;
  padding: 28px 32px;
  width: 100%;
  max-width: 640px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.modal-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0;
}
.modal-header h3 i {
  color: #2b6cb0;
  margin-right: 8px;
}
.modal-form .form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 24px;
}
.modal-form .form-group {
  display: flex;
  flex-direction: column;
}
.modal-form .form-group.full-width {
  grid-column: 1 / -1;
}
.modal-form .form-group label {
  font-weight: 600;
  font-size: 14px;
  color: #2d3748;
  margin-bottom: 4px;
}
.modal-form .form-group .required {
  color: #dc2626;
}
.modal-form .form-control {
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
  width: 100%;
}
.modal-form .form-control:focus {
  outline: none;
  border-color: #2b6cb0;
  box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.15);
}
.modal-form textarea.form-control {
  resize: vertical;
  min-height: 60px;
}
.modal-form .form-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 12px;
  margin-top: 16px;
  justify-content: flex-end;
}

/* ========================================================== */
/* RESPONSIVE */
/* ========================================================== */
@media (max-width: 768px) {
  .modal-form .form-grid {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  .header-actions {
    justify-content: stretch;
    flex-direction: column;
  }
  .header-actions .btn {
    justify-content: center;
  }
  .summary-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>