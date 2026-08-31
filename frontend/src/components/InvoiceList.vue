<template>
  <div class="invoice-container">
    <!-- Header -->
    <div class="page-header">
      <h2><i class="fas fa-file-invoice"></i> Manajemen Invoice</h2>
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
        <button class="btn btn-primary" @click="openForm">
          <i class="fas fa-plus-circle"></i> Tambah Invoice
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <input
        v-model="search"
        type="text"
        class="form-control-sm"
        placeholder="Cari No Invoice / Client..."
        @input="fetchData"
      />
      <select v-model="filterStatus" class="form-control-sm" @change="fetchData">
        <option value="">Semua Status</option>
        <option value="draft">Draft</option>
        <option value="sent">Sent</option>
        <option value="paid">Paid</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <!-- Tabel -->
    <div class="table-card">
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>No Invoice</th>
              <th>Client</th>
              <th>Project</th>
              <th>Total</th>
              <th>Jatuh Tempo</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center">Memuat...</td>
            </tr>
            <tr v-else-if="!data.length">
              <td colspan="7" class="text-center">Tidak ada data</td>
            </tr>
            <tr v-for="item in data" :key="item.id">
              <td><strong>{{ item.invoice_number }}</strong></td>
              <td>{{ item.client?.name || '-' }}</td>
              <td>{{ item.shipping_project?.no_po || '-' }}</td>
              <td>{{ formatCurrency(item.total_amount) }}</td>
              <td>{{ formatDate(item.due_date) }}</td>
              <td>
                <span class="badge" :class="statusBadge(item.status)">
                  {{ statusLabel(item.status) }}
                </span>
              </td>
              <td class="text-center">
                <!-- Update Status -->
                <button
                  v-if="canUpdateStatus(item)"
                  class="btn-icon status-update"
                  title="Update Status"
                  @click="openStatusModal(item)"
                >
                  <i class="fas fa-arrow-right"></i>
                </button>
                <!-- Print -->
                <button class="btn-icon print" title="Cetak / Download PDF" @click="printInvoice(item.id)">
                  <i class="fas fa-print"></i>
                </button>
                <!-- Edit -->
                <button class="btn-icon" title="Edit" @click="editInvoice(item)">
                  <i class="fas fa-edit"></i>
                </button>
                <!-- Hapus -->
                <button class="btn-icon danger" title="Hapus" @click="deleteInvoice(item.id)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Tambah / Edit -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class="fas fa-file-invoice"></i> {{ modalMode === 'edit' ? 'Edit Invoice' : 'Tambah Invoice' }}</h3>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <form @submit.prevent="saveInvoice">
          <div v-if="validationErrors" class="error-box">
            <ul>
              <li v-for="(err, key) in validationErrors" :key="key">
                <strong>{{ key }}:</strong> {{ err.join(', ') }}
              </li>
            </ul>
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label>No Invoice <span class="required">*</span></label>
              <input
                v-model="form.invoice_number"
                type="text"
                class="form-control"
                readonly
                :placeholder="form.invoice_number ? '' : 'Pilih Project terlebih dahulu'"
              />
            </div>

            <!-- 🔥 Field No PO / Project – Disabled saat Edit -->
            <div class="form-group">
              <label>No PO / Project <span class="required">*</span></label>
              <select
                v-model="form.shipping_project_id"
                class="form-control"
                required
                @change="onProjectChange"
                :disabled="modalMode === 'edit'"
              >
                <option value="">Pilih Project</option>
                <option
                  v-for="p in availableProjects"
                  :key="p.id"
                  :value="p.id"
                >
                  {{ p.no_po }} - {{ p.client?.name || '-' }}
                </option>
              </select>
              <small v-if="modalMode === 'edit'" class="text-muted">
                ⚠️ Project tidak dapat diubah saat edit invoice.
              </small>
            </div>

            <div class="form-group">
              <label>Client <span class="required">*</span></label>
              <select v-model="form.client_id" class="form-control" required>
                <option value="">Pilih Client</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">
                  {{ c.name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Total Amount <span class="required">*</span></label>
              <input
                v-model.number="form.total_amount"
                type="number"
                class="form-control"
                min="0"
                required
                placeholder="Otomatis dari project"
              />
            </div>
            <div class="form-group">
              <label>Jatuh Tempo <span class="required">*</span></label>
              <input v-model="form.due_date" type="date" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-control">
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
              </select>
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

    <!-- Modal Update Status -->
    <div v-if="showStatusModal" class="modal-overlay" @click.self="closeStatusModal">
      <div class="modal-card modal-sm">
        <div class="modal-header">
          <h3><i class="fas fa-exchange-alt"></i> Update Status Invoice</h3>
          <button class="btn-close" @click="closeStatusModal">&times;</button>
        </div>
        <div class="status-info">
          <p><strong>No Invoice:</strong> {{ selectedInvoice?.invoice_number }}</p>
          <p>
            <strong>Status Saat Ini:</strong>
            <span class="badge" :class="statusBadge(selectedInvoice?.status)">
              {{ statusLabel(selectedInvoice?.status) }}
            </span>
          </p>
        </div>
        <div class="status-actions">
          <button
            v-for="s in availableStatuses"
            :key="s"
            class="btn btn-status"
            :class="statusButtonClass(s)"
            @click="updateStatus(selectedInvoice?.id, s)"
            :disabled="loadingStatus"
          >
            <i v-if="loadingStatus && s === selectedStatus" class="fas fa-spinner fa-spin"></i>
            {{ statusLabel(s) }}
          </button>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="closeStatusModal">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'
import { useExport } from '../composables/useExport'

const STATUS_ORDER = ['draft', 'sent', 'paid', 'cancelled']

export default {
  name: 'InvoiceList',
  data() {
    return {
      data: [],
      clients: [],
      availableProjects: [],
      loading: false,
      search: '',
      filterStatus: '',
      showModal: false,
      modalMode: 'add',
      loadingSubmit: false,
      validationErrors: null,
      form: {
        id: null,
        invoice_number: '',
        shipping_project_id: '',
        client_id: '',
        total_amount: 0,
        due_date: '',
        status: 'draft',
      },

      showStatusModal: false,
      selectedInvoice: null,
      loadingStatus: false,
      selectedStatus: '',
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
    availableStatuses() {
      if (!this.selectedInvoice) return []
      const current = this.selectedInvoice.status
      const index = STATUS_ORDER.indexOf(current)
      const nextStatuses = []
      if (index !== -1 && index < STATUS_ORDER.length - 1) {
        nextStatuses.push(STATUS_ORDER[index + 1])
      }
      if (current !== 'cancelled') {
        nextStatuses.push('cancelled')
      }
      return nextStatuses
    },
    canUpdateStatus() {
      return (item) => {
        if (!item) return false
        if (this.user?.role === 'super_admin') return true
        return item.status !== 'paid' && item.status !== 'cancelled'
      }
    },
  },
  setup() {
    const { isExporting, exportData } = useExport('invoices')
    return { isExporting, exportData }
  },
  mounted() {
    this.fetchData()
    this.fetchOptions()
  },
  methods: {
    formatCurrency(val) {
      if (!val) return 'Rp 0'
      return 'Rp ' + Number(val).toLocaleString('id-ID')
    },
    formatDate(date) {
      if (!date) return '-'
      const d = new Date(date)
      return String(d.getDate()).padStart(2, '0') + '-' +
             String(d.getMonth() + 1).padStart(2, '0') + '-' +
             d.getFullYear()
    },
    statusLabel(status) {
      const map = {
        draft: 'Draft',
        sent: 'Sent',
        paid: 'Paid',
        cancelled: 'Cancelled',
      }
      return map[status] || status
    },
    statusBadge(status) {
      const map = {
        draft: 'badge-secondary',
        sent: 'badge-info',
        paid: 'badge-success',
        cancelled: 'badge-danger',
      }
      return map[status] || 'badge-secondary'
    },
    statusButtonClass(status) {
      return {
        'btn-info': status === 'sent',
        'btn-success': status === 'paid',
        'btn-danger': status === 'cancelled',
      }
    },

    async fetchData() {
      this.loading = true
      try {
        const params = {
          search: this.search || undefined,
          status: this.filterStatus || undefined,
        }
        const res = await axios.get('/invoices', { params })
        this.data = res.data.data || []
      } catch (e) {
        console.error('Error fetching invoices:', e)
        alert('Gagal memuat invoice')
      } finally {
        this.loading = false
      }
    },

    async fetchOptions() {
      try {
        const [clientsRes, projectsRes] = await Promise.all([
          axios.get('/clients'),
          axios.get('/invoices/available-projects'),
        ])
        this.clients = clientsRes.data.data || []
        this.availableProjects = projectsRes.data.data || []
      } catch (e) {
        console.error('Error fetching options:', e)
        alert('Gagal memuat data opsi')
      }
    },

    onProjectChange() {
      const selected = this.availableProjects.find(p => p.id === this.form.shipping_project_id)
      if (selected) {
        this.form.client_id = selected.client_id || ''
        this.form.total_amount = selected.contract_value || 0
        this.form.invoice_number = selected.no_po ? `INV-${selected.no_po}` : ''
      } else {
        this.form.client_id = ''
        this.form.total_amount = 0
        this.form.invoice_number = ''
      }
    },

    openForm() {
      this.modalMode = 'add'
      this.validationErrors = null
      this.form = {
        id: null,
        invoice_number: '',
        shipping_project_id: '',
        client_id: '',
        total_amount: 0,
        due_date: new Date().toISOString().slice(0, 10),
        status: 'draft',
      }
      this.fetchOptions()
      this.showModal = true
    },

    editInvoice(item) {
      this.modalMode = 'edit'
      this.validationErrors = null

      // 🔥 Saat edit, kita isi form dengan data invoice yang sudah ada
      this.form = {
        id: item.id,
        invoice_number: item.invoice_number,
        shipping_project_id: item.shipping_project_id,
        client_id: item.client_id,
        total_amount: item.total_amount,
        due_date: item.due_date,
        status: item.status,
      }

      // 🔥 Kita juga perlu memuat availableProjects agar select bisa menampilkan pilihan
      // (meskipun disabled, kita tetap butuh data untuk menampilkan label project yang dipilih)
      this.fetchOptions().then(() => {
        // Pastikan project yang dipilih ada di daftar availableProjects
        // Jika tidak ada (misal project sudah punya invoice lain), kita bisa tambahkan manual
        const selected = this.availableProjects.find(p => p.id === item.shipping_project_id)
        if (!selected) {
          // Jika project tidak ada di daftar (karena sudah punya invoice), kita tambahkan sementara
          this.availableProjects.push({
            id: item.shipping_project_id,
            no_po: item.shipping_project?.no_po || `Project #${item.shipping_project_id}`,
            client_id: item.client_id,
            client: item.client || { name: '-' },
            contract_value: item.total_amount,
          })
        }
      })

      this.showModal = true
    },

    closeModal() {
      this.showModal = false
      this.loadingSubmit = false
      this.validationErrors = null
    },

    async saveInvoice() {
      this.loadingSubmit = true
      this.validationErrors = null
      try {
        const payload = { ...this.form }
        delete payload.id

        let response
        if (this.modalMode === 'edit') {
          response = await axios.put(`/invoices/${this.form.id}`, payload)
        } else {
          response = await axios.post('/invoices', payload)
        }

        this.closeModal()
        this.fetchData()
        alert(response.data.message || 'Data berhasil disimpan')
      } catch (e) {
        if (e.response?.status === 422) {
          this.validationErrors = e.response.data.errors
        } else {
          alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
        }
        console.error('Error saving invoice:', e)
      } finally {
        this.loadingSubmit = false
      }
    },

    async deleteInvoice(id) {
      if (!confirm('Yakin hapus invoice ini?')) return
      try {
        await axios.delete(`/invoices/${id}`)
        this.fetchData()
        alert('Invoice berhasil dihapus')
      } catch (e) {
        alert('Gagal hapus: ' + (e.response?.data?.message || e.message))
      }
    },

    // ===== UPDATE STATUS =====
    openStatusModal(item) {
      this.selectedInvoice = item
      this.showStatusModal = true
    },

    closeStatusModal() {
      this.showStatusModal = false
      this.selectedInvoice = null
      this.loadingStatus = false
    },

    async updateStatus(id, newStatus) {
      this.loadingStatus = true
      this.selectedStatus = newStatus
      try {
        await axios.put(`/invoices/${id}`, { status: newStatus })
        this.closeStatusModal()
        this.fetchData()
        alert('Status invoice berhasil diupdate')
      } catch (e) {
        alert('Gagal update status: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loadingStatus = false
        this.selectedStatus = ''
      }
    },

    // ===== PRINT =====
    async printInvoice(id) {
      try {
        const response = await axios.get(`/invoices/${id}/print`, {
          responseType: 'blob'
        })

        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)

        const link = document.createElement('a')
        link.href = url
        link.download = `INVOICE-${id}.pdf`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (e) {
        let errorMsg = 'Gagal mencetak invoice'
        if (e.response) {
          const contentType = e.response.headers['content-type']
          if (contentType && contentType.includes('application/json')) {
            try {
              const text = await e.response.data.text()
              const json = JSON.parse(text)
              errorMsg = json.message || errorMsg
            } catch (_) {
              errorMsg = e.response.statusText || errorMsg
            }
          } else {
            errorMsg = e.response.statusText || errorMsg
          }
        } else if (e.message) {
          errorMsg = e.message
        }
        alert('Gagal mencetak invoice: ' + errorMsg)
      }
    },

    // ===== EXPORT =====
    async handleExport() {
      await this.exportData({
        search: this.search || undefined,
        status: this.filterStatus || undefined,
      })
    },
  },
}
</script>

<style scoped>
/* Style sama seperti sebelumnya, ditambah .text-muted */
.text-muted {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}

.invoice-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

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

.filter-bar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.filter-bar .form-control-sm {
  padding: 6px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 6px;
  font-size: 14px;
  background: white;
}

.table-card {
  background: white;
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
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

.badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}
.badge-secondary {
  background: #e2e8f0;
  color: #475569;
}
.badge-info {
  background: #dbeafe;
  color: #1e40af;
}
.badge-success {
  background: #d1fae5;
  color: #065f46;
}
.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.btn-icon {
  background: transparent;
  border: none;
  padding: 4px 8px;
  color: #4a5568;
  cursor: pointer;
  transition: 0.2s;
  font-size: 16px;
}
.btn-icon:hover {
  color: #2b6cb0;
}
.btn-icon.danger:hover {
  color: #dc2626;
}
.btn-icon.print {
  color: #2b6cb0;
}
.btn-icon.print:hover {
  color: #1a4a7a;
}
.btn-icon.status-update {
  color: #22c55e;
}
.btn-icon.status-update:hover {
  color: #16a34a;
}

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
  max-width: 680px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.modal-sm {
  max-width: 500px !important;
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

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 20px;
}
.form-group {
  display: flex;
  flex-direction: column;
}
.form-group label {
  font-weight: 600;
  font-size: 14px;
  color: #2d3748;
  margin-bottom: 4px;
}
.form-group .required {
  color: #dc2626;
}
.form-control {
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
  width: 100%;
}
.form-control:focus {
  outline: none;
  border-color: #2b6cb0;
  box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.15);
}
.form-control[readonly] {
  background: #f1f5f9;
  cursor: not-allowed;
}
.form-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 12px;
  margin-top: 16px;
  justify-content: flex-end;
}

.error-box {
  grid-column: 1 / -1;
  padding: 12px 16px;
  background: #fee2e2;
  border: 1px solid #dc2626;
  border-radius: 8px;
  color: #991b1b;
}
.error-box ul {
  margin: 0;
  padding-left: 20px;
}
.error-box ul li {
  font-size: 14px;
}

.status-info {
  margin-bottom: 20px;
}
.status-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
  margin-bottom: 20px;
}
.btn-status {
  min-width: 100px;
}
.btn-status.btn-info {
  background: #3b82f6;
  color: white;
}
.btn-status.btn-success {
  background: #22c55e;
  color: white;
}
.btn-status.btn-danger {
  background: #dc2626;
  color: white;
}
.btn-status:hover {
  opacity: 0.8;
}

@media (max-width: 768px) {
  .form-grid {
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
}
</style>