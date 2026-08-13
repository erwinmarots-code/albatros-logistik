<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-file-invoice"></i> Tagihan (Invoice)</h2>
      <p class="module-subtitle">Kelola tagihan berdasarkan project yang sudah selesai</p>
    </div>

    <div class="toolbar">
      <button @click="openGenerateForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Buat Invoice
      </button>
      <button @click="fetchItems" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat Data
      </button>
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input v-model="searchQuery" type="text" class="search-input" placeholder="Cari invoice..." />
      </div>
    </div>

    <!-- Form Generate Invoice -->
    <div v-if="showGenerateForm" class="form-container">
      <h3><i class="fas fa-file-invoice"></i> Buat Invoice Baru</h3>
      <form @submit.prevent="generateInvoice">
        <div class="form-group">
          <label><i class="fas fa-ticket-alt"></i> Pilih Project (No Resi) <span class="required">*</span></label>
          <select v-model="selectedProjectId" required>
            <option value="">-- Pilih Project --</option>
            <option v-for="p in availableProjects" :key="p.id" :value="p.id">
              {{ p.resi_number }} - {{ p.no_po || 'No PO' }} - {{ p.client?.name }}
            </option>
          </select>
          <p v-if="availableProjects.length === 0" style="grid-column: 2; color: #dc3545; font-size: 13px;">
            Belum ada project selesai yang bisa dibuat invoice.
          </p>
        </div>
        <div class="form-group">
          <label><i class="fas fa-calendar-alt"></i> Jatuh Tempo</label>
          <input v-model="dueDate" type="date" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-pencil-alt"></i> Catatan</label>
          <input v-model="notes" placeholder="Catatan tambahan" />
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-save"><i class="fas fa-save"></i> Generate Invoice</button>
          <button type="button" @click="closeGenerateForm" class="btn-cancel"><i class="fas fa-times"></i> Batal</button>
        </div>
      </form>
    </div>

    <!-- Detail Invoice (jika ada) -->
    <InvoiceDetail v-if="showDetail" :invoice-id="selectedInvoiceId" @back="backToList" @updated="fetchItems" />

    <!-- Tabel Invoice -->
    <div v-else>
      <div class="table-wrapper" v-if="filteredItems.length">
        <table class="modern-table">
          <thead>
            <tr>
              <th>#</th>
              <th><i class="fas fa-hashtag"></i> No. Invoice</th>
              <th><i class="fas fa-ticket-alt"></i> No. Resi</th>
              <th><i class="fas fa-tag"></i> No. PO</th>
              <th><i class="fas fa-building"></i> Client</th>
              <th><i class="fas fa-calendar-alt"></i> Tanggal</th>
              <th><i class="fas fa-calendar-alt"></i> Jatuh Tempo</th>
              <th><i class="fas fa-money-bill-wave"></i> Total</th>
              <th><i class="fas fa-circle"></i> Status</th>
              <th class="text-center"><i class="fas fa-cogs"></i> Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in filteredItems" :key="item.id">
              <td>{{ index + 1 }}</td>
              <td><strong>{{ item.invoice_number }}</strong></td>
              <td>{{ item.shipping_project?.resi_number || '-' }}</td>
              <td>{{ item.shipping_project?.no_po || '-' }}</td>
              <td>{{ item.shipping_project?.client?.name }}</td>
              <td>{{ formatDate(item.invoice_date) }}</td>
              <td>{{ formatDate(item.due_date) || '-' }}</td>
              <td>{{ formatRupiah(item.total_amount) }}</td>
              <td>
                <span :class="'status-badge-' + item.status">{{ item.status }}</span>
              </td>
              <td class="action-cell">
                <button @click="viewDetail(item.id)" class="btn-detail" title="Detail">
                  <i class="fas fa-eye"></i>
                </button>
                <button v-if="item.status === 'draft' || item.status === 'sent'" @click="updateStatus(item.id, 'paid')" class="btn-approve" title="Bayar">
                  <i class="fas fa-check-circle"></i>
                </button>
                <button v-if="item.status === 'draft' || item.status === 'sent'" @click="updateStatus(item.id, 'cancelled')" class="btn-reject" title="Batal">
                  <i class="fas fa-times-circle"></i>
                </button>
                <button @click="deleteInvoice(item.id)" class="btn-delete" title="Hapus">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="empty-message">
        <i class="fas fa-inbox"></i>
        {{ searchQuery ? 'Tidak ada invoice yang cocok dengan pencarian.' : 'Belum ada invoice.' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../axios'
import { formatRupiah } from '../utils/helpers'
import InvoiceDetail from './InvoiceDetail.vue'

// Helper format tanggal
const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const items = ref([])
const availableProjects = ref([])
const showGenerateForm = ref(false)
const selectedProjectId = ref('')
const dueDate = ref('')
const notes = ref('')
const searchQuery = ref('')

const showDetail = ref(false)
const selectedInvoiceId = ref(null)

const filteredItems = computed(() => {
  if (!searchQuery.value) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.invoice_number?.toLowerCase().includes(q) ||
    item.shipping_project?.resi_number?.toLowerCase().includes(q) ||
    item.shipping_project?.no_po?.toLowerCase().includes(q) ||
    item.shipping_project?.client?.name?.toLowerCase().includes(q) ||
    item.status?.toLowerCase().includes(q)
  )
})

const fetchItems = async () => {
  try {
    const res = await axios.get('/invoices')
    items.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat data invoice: ' + error.message)
  }
}

const fetchAvailableProjects = async () => {
  try {
    const res = await axios.get('/available-projects')
    availableProjects.value = res.data.data || []
  } catch (error) {
    console.error('Error fetch available projects:', error)
    alert('Gagal memuat data project: ' + error.message)
  }
}

const openGenerateForm = () => {
  showGenerateForm.value = true
  selectedProjectId.value = ''
  dueDate.value = ''
  notes.value = ''
  fetchAvailableProjects()
}

const closeGenerateForm = () => {
  showGenerateForm.value = false
}

const generateInvoice = async () => {
  if (!selectedProjectId.value) {
    alert('Pilih project terlebih dahulu!')
    return
  }
  try {
    await axios.post('/invoices', {
      shipping_project_id: selectedProjectId.value,
      due_date: dueDate.value || null,
      notes: notes.value || null,
    })
    alert('Invoice berhasil dibuat!')
    closeGenerateForm()
    await fetchItems()
  } catch (error) {
    const msg = error.response?.data?.message || error.message
    alert('Gagal membuat invoice: ' + msg)
  }
}

const updateStatus = async (id, status) => {
  if (!confirm(`Ubah status invoice menjadi ${status}?`)) return
  try {
    await axios.put(`/invoices/${id}`, { status })
    alert('Status berhasil diupdate!')
    await fetchItems()
  } catch (error) {
    alert('Gagal update status: ' + error.message)
  }
}

const deleteInvoice = async (id) => {
  if (!confirm('Yakin hapus invoice ini?')) return
  try {
    await axios.delete(`/invoices/${id}`)
    alert('Invoice dihapus!')
    await fetchItems()
  } catch (error) {
    alert('Gagal hapus: ' + error.message)
  }
}

const viewDetail = (id) => {
  selectedInvoiceId.value = id
  showDetail.value = true
}

const backToList = () => {
  showDetail.value = false
  selectedInvoiceId.value = null
  fetchItems()
}

onMounted(() => {
  fetchItems()
})
</script>

<style scoped>
/* ====== GAYA KONSISTEN ====== */
.module-container {
  max-width: 1200px;
  margin: 0 auto;
}
.module-header {
  margin-bottom: 20px;
}
.module-header h2 {
  font-size: 24px;
  color: #0d2b45;
  display: flex;
  align-items: center;
  gap: 10px;
}
.module-header h2 i {
  color: #1a4a7a;
}
.module-subtitle {
  color: #6c757d;
  font-size: 14px;
  margin-top: 2px;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
  align-items: center;
}
.btn-add,
.btn-refresh {
  padding: 10px 22px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.25s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.btn-add {
  background: linear-gradient(135deg, #28a745, #218838);
  color: white;
}
.btn-add:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
}
.btn-refresh {
  background: linear-gradient(135deg, #17a2b8, #138496);
  color: white;
}
.btn-refresh:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(23, 162, 184, 0.3);
}

.search-wrapper {
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0 12px;
  transition: border-color 0.2s, box-shadow 0.2s;
  flex: 1;
  max-width: 300px;
}
.search-wrapper:focus-within {
  border-color: #1a4a7a;
  box-shadow: 0 0 0 3px rgba(26, 74, 122, 0.12);
}
.search-icon {
  color: #94a3b8;
  margin-right: 8px;
}
.search-input {
  border: none;
  padding: 10px 0;
  font-size: 14px;
  width: 100%;
  outline: none;
  background: transparent;
}

.form-container {
  background: white;
  border-radius: 16px;
  padding: 24px 28px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
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
.form-container h3 i {
  color: #1a4a7a;
}
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
.form-group label i {
  color: #1a4a7a;
  width: 20px;
  text-align: center;
}
.required {
  color: #dc3545;
  margin-left: 2px;
}
.form-group input,
.form-group select {
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
  background: white;
  width: 100%;
  box-sizing: border-box;
}
.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #1a4a7a;
  box-shadow: 0 0 0 3px rgba(26, 74, 122, 0.12);
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e9ecef;
}
.btn-save,
.btn-cancel {
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
.btn-save {
  background: linear-gradient(135deg, #28a745, #218838);
  color: white;
}
.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
}
.btn-cancel {
  background: #6c757d;
  color: white;
}
.btn-cancel:hover {
  background: #5a6268;
  transform: translateY(-2px);
}

.table-wrapper {
  overflow-x: auto;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
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
.modern-table tbody tr:hover {
  background: #f8fafc;
}
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
.text-center {
  text-align: center;
}
.action-cell {
  display: flex;
  gap: 8px;
  justify-content: center;
  flex-wrap: wrap;
}

.status-badge-draft {
  background: #6c757d;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.status-badge-sent {
  background: #17a2b8;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.status-badge-paid {
  background: #28a745;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.status-badge-cancelled {
  background: #dc3545;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.btn-edit,
.btn-delete,
.btn-approve,
.btn-reject,
.btn-detail {
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
.btn-edit {
  background: #ffc107;
  color: #212529;
}
.btn-edit:hover {
  background: #e0a800;
  transform: scale(1.08);
}
.btn-delete {
  background: #dc3545;
  color: white;
}
.btn-delete:hover {
  background: #c82333;
  transform: scale(1.08);
}
.btn-approve {
  background: #28a745;
  color: white;
}
.btn-approve:hover {
  background: #218838;
  transform: scale(1.08);
}
.btn-reject {
  background: #dc3545;
  color: white;
}
.btn-reject:hover {
  background: #c82333;
  transform: scale(1.08);
}
.btn-detail {
  background: #17a2b8;
  color: white;
}
.btn-detail:hover {
  background: #138496;
  transform: scale(1.08);
}

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