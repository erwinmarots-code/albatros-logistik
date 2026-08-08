<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-chart-pie"></i> Dashboard Keuangan</h2>
      <p class="module-subtitle">Ringkasan pemasukan & pengeluaran</p>
    </div>

    <!-- ===== TOOLBAR ===== -->
    <div class="toolbar">
      <button @click="fetchTransactions" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat Data
      </button>
      <button @click="exportExcel" class="btn-export">
        <i class="fas fa-file-excel"></i> Export Excel
      </button>
      <button @click="openForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Tambah Transaksi Manual
      </button>
    </div>

    <!-- ===== FILTER PERIODE ===== -->
    <div class="filter-row">
      <label>Dari: <input v-model="filter.from" type="date" @change="fetchSummary" /></label>
      <label>Sampai: <input v-model="filter.to" type="date" @change="fetchSummary" /></label>
    </div>

    <!-- ===== SUMMARY CARDS ===== -->
    <div class="summary-cards">
      <div class="card income">
        <h3><i class="fas fa-arrow-up"></i> Pemasukan</h3>
        <p>{{ formatRupiah(summary.total_income) }}</p>
      </div>
      <div class="card expense">
        <h3><i class="fas fa-arrow-down"></i> Pengeluaran</h3>
        <p>{{ formatRupiah(summary.total_expense) }}</p>
      </div>
      <div class="card balance">
        <h3><i class="fas fa-wallet"></i> Saldo</h3>
        <p :style="{ color: summary.balance >= 0 ? 'green' : 'red' }">
          {{ formatRupiah(summary.balance) }}
        </p>
      </div>
    </div>

    <!-- ===== RINCIAN PER KATEGORI ===== -->
    <div class="category-section">
      <h3><i class="fas fa-tags"></i> Rincian per Kategori</h3>
      <ul>
        <li v-for="item in summary.by_category" :key="item.category">
          <span>{{ item.category }}</span>
          <span :class="item.type === 'income' ? 'income' : 'expense'">
            {{ item.type === 'income' ? '+' : '-' }} {{ formatRupiah(item.total) }}
          </span>
        </li>
      </ul>
    </div>

    <hr />

    <!-- ===== FORM TAMBAH TRANSAKSI ===== -->
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

    <!-- ===== TABEL TRANSAKSI ===== -->
    <div class="table-wrapper" v-if="transactions && transactions.length">
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
          <tr v-for="(item, index) in transactions" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td>{{ item.transaction_date }}</td>
            <td><span :class="'type-badge-' + item.type">{{ item.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</span></td>
            <td>{{ item.category }}</td>
            <td>{{ formatRupiah(item.amount) }}</td>
            <td>{{ item.description || '-' }}</td>
            <td>{{ item.vehicle?.plate_number || '-' }}</td>
            <td class="action-cell">
              <button @click="editTransaction(item)" class="btn-edit" title="Edit"><i class="fas fa-edit"></i></button>
              <button @click="deleteTransaction(item.id)" class="btn-delete" title="Hapus"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message"><i class="fas fa-inbox"></i> Belum ada transaksi keuangan.</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from '../axios'
import { formatRupiah } from '../utils/helpers'

// ===== STATE =====
const transactions = ref([])
const vehicles = ref([])
const summary = ref({})
const showForm = ref(false)
const editingTransId = ref(null)

const filter = reactive({
  from: '',
  to: '',
})

const transForm = reactive({
  transaction_date: new Date().toISOString().split('T')[0],
  type: 'income',
  category: 'client_payment',
  amount: '',
  description: '',
  vehicle_id: '',
})

// ===== FUNGSI FETCH DATA =====
const fetchTransactions = async () => {
  try {
    const res = await axios.get('/financial-transactions')
    transactions.value = res.data.data || []
  } catch (error) {
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
    alert('Gagal memuat summary: ' + error.message)
  }
}

const fetchVehicles = async () => {
  try {
    const res = await axios.get('/vehicles')
    vehicles.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat kendaraan: ' + error.message)
  }
}

// ===== FORM =====
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

// ===== MOUNTED =====
onMounted(() => {
  fetchTransactions()
  fetchSummary()
  fetchVehicles()
})
</script>

<style scoped>
/* ====== GAYA MODUL ====== */
.module-container { max-width: 1200px; margin: 0 auto; }
.module-header { margin-bottom: 20px; }
.module-header h2 { font-size: 24px; color: #0d2b45; display: flex; align-items: center; gap: 10px; }
.module-header h2 i { color: #1a4a7a; }
.module-subtitle { color: #6c757d; font-size: 14px; margin-top: 2px; }

/* ===== TOOLBAR ===== */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}
.btn-add, .btn-refresh, .btn-export {
  padding: 10px 22px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.25s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.btn-add { background: linear-gradient(135deg, #28a745, #218838); color: white; }
.btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.3); }
.btn-refresh { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
.btn-refresh:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(23,162,184,0.3); }
.btn-export { background: linear-gradient(135deg, #007bff, #0069d9); color: white; }
.btn-export:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,123,255,0.3); }

/* ===== FILTER ===== */
.filter-row {
  display: flex;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.filter-row label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}
.filter-row input {
  padding: 6px 12px;
  border: 1px solid #ccc;
  border-radius: 8px;
}

/* ===== SUMMARY CARDS ===== */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}
.card {
  background: white;
  padding: 16px 20px;
  border-radius: 16px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.04);
  border-left: 4px solid #1a4a7a;
}
.card.income { border-left-color: #28a745; }
.card.expense { border-left-color: #dc3545; }
.card.balance { border-left-color: #17a2b8; }
.card h3 {
  font-size: 14px;
  color: #6c757d;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.card p {
  font-size: 22px;
  font-weight: 700;
  margin: 0;
}

/* ===== KATEGORI ===== */
.category-section {
  background: #f8fafc;
  border-radius: 16px;
  padding: 16px 20px;
  margin-bottom: 24px;
}
.category-section h3 {
  font-size: 16px;
  color: #0d2b45;
  display: flex;
  align-items: center;
  gap: 8px;
}
.category-section ul {
  list-style: none;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 8px;
}
.category-section li {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
  border-bottom: 1px solid #e9ecef;
}
.category-section li span:last-child.income { color: #28a745; }
.category-section li span:last-child.expense { color: #dc3545; }

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
.btn-save { background: linear-gradient(135deg, #28a745, #218838); color: white; }
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
.modern-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  min-width: 700px;
}
.modern-table thead {
  background: #f8fafc;
  border-bottom: 2px solid #e9ecef;
}
.modern-table thead th {
  padding: 14px 16px;
  text-align: left;
  font-weight: 700;
  color: #2d3748;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.modern-table thead th i {
  margin-right: 6px;
  color: #1a4a7a;
}
.modern-table tbody tr {
  border-bottom: 1px solid #f1f3f5;
  transition: background 0.15s ease;
}
.modern-table tbody tr:hover { background: #f8fafc; }
.modern-table tbody td {
  padding: 12px 16px;
  color: #2d3748;
  vertical-align: middle;
}
.modern-table tbody td:first-child {
  font-weight: 600;
  color: #6c757d;
  width: 40px;
  text-align: center;
}
.text-center { text-align: center; }
.action-cell {
  display: flex;
  gap: 8px;
  justify-content: center;
  flex-wrap: wrap;
}

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
.empty-message i {
  font-size: 40px;
  display: block;
  margin-bottom: 12px;
  color: #dee2e6;
}

@media (max-width: 768px) {
  .form-group {
    grid-template-columns: 1fr;
    gap: 4px;
  }
  .form-group label {
    text-align: left;
    justify-content: flex-start;
  }
  .modern-table {
    font-size: 13px;
    min-width: 500px;
  }
  .modern-table thead th,
  .modern-table tbody td {
    padding: 10px 12px;
  }
  .action-cell {
    gap: 4px;
  }
}
</style>