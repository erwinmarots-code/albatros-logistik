<template>
  <div class="project-container">
    <div class="page-header">
      <h2><i class="fas fa-folder-open"></i> Project Pengantaran</h2>
      <button class="btn btn-primary" @click="openForm">
        <i class="fas fa-plus-circle"></i> Tambah Project
      </button>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <input
        v-model="search"
        type="text"
        class="form-control-sm"
        placeholder="Cari No PO / Resi..."
        @input="fetchData"
      />
      <select v-model="filterStatus" class="form-control-sm" @change="fetchData">
        <option value="">Semua Status</option>
        <option value="draft">Draft</option>
        <option value="confirmed">Confirmed</option>
        <option value="on_delivery">On Delivery</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <!-- Tabel -->
    <div class="table-card">
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>No PO</th>
              <th>No Resi</th>
              <th>Client</th>
              <th>Cabang</th>
              <th>Status</th>
              <th>Nilai</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="7" class="text-center">Memuat...</td></tr>
            <tr v-else-if="!data.length"><td colspan="7" class="text-center">Tidak ada data</td></tr>
            <tr v-for="item in data" :key="item.id">
              <td><strong>{{ item.no_po }}</strong></td>
              <td>{{ item.no_resi || '-' }}</td>
              <td>{{ item.client?.name || '-' }}</td>
              <td>{{ item.branch?.code || '-' }}</td>
              <td><span class="badge" :class="statusBadge(item.status)">{{ statusLabel(item.status) }}</span></td>
              <td>{{ formatCurrency(item.contract_value) }}</td>
              <td class="text-center">
                <!-- 🔥 Tombol Update Status -->
                <button
                  v-if="canUpdateStatus(item)"
                  class="btn-icon status-update"
                  title="Update Status"
                  @click="openStatusModal(item)"
                >
                  <i class="fas fa-arrow-right"></i>
                </button>

                <button class="btn-icon" title="Edit" @click="editProject(item)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon danger" title="Hapus" @click="deleteProject(item.id)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Tambah/Edit (Tidak Diubah) -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class="fas fa-edit"></i> {{ modalMode === 'edit' ? 'Edit Project' : 'Tambah Project' }}</h3>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <form @submit.prevent="saveProject" class="modal-form">
          <div v-if="validationErrors" class="error-box full-width">
            <ul>
              <li v-for="(err, key) in validationErrors" :key="key">
                <strong>{{ key }}:</strong> {{ err.join(', ') }}
              </li>
            </ul>
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label>Client <span class="required">*</span></label>
              <select v-model="form.client_id" class="form-control" required @change="autoFillSender">
                <option value="">Pilih Client</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Cabang <span class="required">*</span></label>
              <select v-model="form.branch_id" class="form-control" required>
                <option value="">Pilih Cabang</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.code }} - {{ b.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>No PO <span class="required">*</span></label>
              <input v-model="form.no_po" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>No Resi</label>
              <input v-model="form.no_resi" type="text" class="form-control" placeholder="Otomatis jika kosong" />
            </div>
            <div class="form-group full-width">
              <label class="sub-title">Data Pengirim</label>
              <small class="text-muted">Akan terisi otomatis dari data client</small>
            </div>
            <div class="form-group">
              <label>Nama Pengirim <span class="required">*</span></label>
              <input v-model="form.sender_name" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Telepon Pengirim <span class="required">*</span></label>
              <input v-model="form.sender_phone" type="text" class="form-control" required />
            </div>
            <div class="form-group full-width">
              <label>Alamat Pengirim <span class="required">*</span></label>
              <textarea v-model="form.sender_address" class="form-control" rows="2" required></textarea>
            </div>
            <div class="form-group full-width">
              <label class="sub-title">Data Pelengkap</label>
            </div>
            <div class="form-group">
              <label>Nilai Kontrak (Rp)</label>
              <input v-model.number="form.contract_value" type="number" class="form-control" min="0" placeholder="0" />
            </div>
            <div class="form-group">
              <label>Metode Kirim <span class="required">*</span></label>
              <select v-model="form.shipping_method" class="form-control" required>
                <option value="">Pilih Metode</option>
                <option value="darat">Darat</option>
                <option value="udara">Udara</option>
                <option value="laut">Laut</option>
              </select>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-control">
                <option value="draft">Draft</option>
                <option value="confirmed">Confirmed</option>
                <option value="on_delivery">On Delivery</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="form-group full-width">
              <label>Catatan / Notes</label>
              <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
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
          <h3><i class="fas fa-exchange-alt"></i> Update Status</h3>
          <button class="btn-close" @click="closeStatusModal">&times;</button>
        </div>
        <div class="status-info">
          <p><strong>No PO:</strong> {{ selectedProject?.no_po }}</p>
          <p>
            <strong>Status Saat Ini:</strong>
            <span class="badge" :class="statusBadge(selectedProject?.status)">
              {{ statusLabel(selectedProject?.status) }}
            </span>
          </p>
        </div>
        <div class="status-actions">
          <button
            v-for="s in availableStatuses"
            :key="s"
            class="btn btn-status"
            :class="statusButtonClass(s)"
            @click="updateStatus(selectedProject?.id, s)"
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

const STATUS_ORDER = ['draft', 'confirmed', 'on_delivery', 'completed']

export default {
  name: 'ProjectList',
  data() {
    return {
      data: [],
      clients: [],
      branches: [],
      loading: false,
      search: '',
      filterStatus: '',
      totalItems: 0,
      showModal: false,
      modalMode: 'add',
      loadingSubmit: false,
      validationErrors: null,
      form: {
        id: null,
        client_id: '',
        branch_id: '',
        no_po: '',
        no_resi: '',
        sender_name: '',
        sender_address: '',
        sender_phone: '',
        contract_value: 0,
        shipping_method: '',
        status: 'draft',
        notes: '',
      },

      // 🔥 Status Modal
      showStatusModal: false,
      selectedProject: null,
      loadingStatus: false,
      selectedStatus: '',
    }
  },
  computed: {
    user() {
      return JSON.parse(localStorage.getItem('user') || '{}')
    },
    availableStatuses() {
      if (!this.selectedProject) return []
      const current = this.selectedProject.status
      const index = STATUS_ORDER.indexOf(current)
      const nextStatuses = []
      // Status berikutnya sesuai urutan
      if (index !== -1 && index < STATUS_ORDER.length - 1) {
        nextStatuses.push(STATUS_ORDER[index + 1])
      }
      // Bisa juga tambahkan 'cancelled' jika status bukan completed
      if (current !== 'completed' && current !== 'cancelled') {
        nextStatuses.push('cancelled')
      }
      return nextStatuses
    },
    canUpdateStatus() {
      // Hanya user dengan role admin_project atau super_admin, dan status tidak final
      return (item) => {
        if (!item) return false
        if (this.user?.role === 'super_admin') return true
        if (this.user?.role === 'admin_project') {
          return item.status !== 'completed' && item.status !== 'cancelled'
        }
        return false
      }
    },
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
    statusLabel(status) {
      const map = {
        draft: 'Draft',
        confirmed: 'Confirmed',
        on_delivery: 'On Delivery',
        completed: 'Completed',
        cancelled: 'Cancelled',
      }
      return map[status] || status
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
    statusButtonClass(status) {
      return {
        'btn-info': status === 'confirmed',
        'btn-warning': status === 'on_delivery',
        'btn-success': status === 'completed',
        'btn-danger': status === 'cancelled',
      }
    },

    async fetchData() {
      this.loading = true
      try {
        const params = { search: this.search || undefined, status: this.filterStatus || undefined }
        const res = await axios.get('/projects', { params })
        this.data = res.data.data || []
        this.totalItems = this.data.length
      } catch (e) {
        console.error('Error fetching projects:', e)
        alert('Gagal memuat data project')
      } finally {
        this.loading = false
      }
    },

    async fetchOptions() {
      try {
        const [cRes, bRes] = await Promise.all([
          axios.get('/clients'),
          axios.get('/branches')
        ])
        this.clients = cRes.data.data || []
        this.branches = bRes.data.data || []
      } catch (e) {
        console.error('Error fetching options:', e)
      }
    },

    autoFillSender() {
      const selectedClient = this.clients.find((c) => c.id === this.form.client_id)
      if (selectedClient) {
        this.form.sender_name = selectedClient.name || ''
        this.form.sender_address = selectedClient.address || ''
        this.form.sender_phone = selectedClient.phone || ''
      } else {
        this.form.sender_name = ''
        this.form.sender_address = ''
        this.form.sender_phone = ''
      }
    },

    openForm() {
      this.modalMode = 'add'
      this.validationErrors = null
      this.form = {
        id: null,
        client_id: '',
        branch_id: '',
        no_po: '',
        no_resi: '',
        sender_name: '',
        sender_address: '',
        sender_phone: '',
        contract_value: 0,
        shipping_method: '',
        status: 'draft',
        notes: '',
      }
      this.showModal = true
    },

    editProject(item) {
      this.modalMode = 'edit'
      this.validationErrors = null
      this.form = {
        id: item.id,
        client_id: item.client_id,
        branch_id: item.branch_id,
        no_po: item.no_po,
        no_resi: item.no_resi || '',
        sender_name: item.sender_name || '',
        sender_address: item.sender_address || '',
        sender_phone: item.sender_phone || '',
        contract_value: item.contract_value || 0,
        shipping_method: item.shipping_method || '',
        status: item.status || 'draft',
        notes: item.notes || '',
      }
      this.showModal = true
    },

    closeModal() {
      this.showModal = false
      this.validationErrors = null
    },

    async saveProject() {
      this.loadingSubmit = true
      this.validationErrors = null
      try {
        const payload = { ...this.form }
        delete payload.id

        let response
        if (this.modalMode === 'edit') {
          response = await axios.put(`/projects/${this.form.id}`, payload)
        } else {
          response = await axios.post('/projects', payload)
        }

        this.closeModal()
        this.fetchData()
        alert(response.data.message || 'Data berhasil disimpan')
      } catch (e) {
        if (e.response && e.response.status === 422) {
          this.validationErrors = e.response.data.errors
        } else {
          alert('Gagal simpan: ' + (e.response?.data?.message || e.message))
        }
        console.error('Error saving project:', e)
      } finally {
        this.loadingSubmit = false
      }
    },

    async deleteProject(id) {
      if (!confirm('Yakin hapus project ini?')) return
      try {
        await axios.delete(`/projects/${id}`)
        this.fetchData()
        alert('Project berhasil dihapus')
      } catch (e) {
        alert('Gagal hapus: ' + (e.response?.data?.message || e.message))
      }
    },

    // ===== UPDATE STATUS =====
    openStatusModal(item) {
      this.selectedProject = item
      this.showStatusModal = true
    },

    closeStatusModal() {
      this.showStatusModal = false
      this.selectedProject = null
      this.loadingStatus = false
    },

    async updateStatus(id, newStatus) {
      this.loadingStatus = true
      this.selectedStatus = newStatus
      try {
        await axios.patch(`/projects/${id}/status`, { status: newStatus })
        this.closeStatusModal()
        this.fetchData()
        alert('Status project berhasil diupdate')
      } catch (e) {
        alert('Gagal update status: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loadingStatus = false
        this.selectedStatus = ''
      }
    },
  }
}
</script>

<style scoped>
.project-container {
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
.btn-status {
  min-width: 100px;
}
.btn-status.btn-info {
  background: #3b82f6;
  color: white;
}
.btn-status.btn-warning {
  background: #f59e0b;
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

.table-card {
  background: white;
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
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
  max-width: 780px;
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

.modal-form .form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 20px;
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
.modal-form .form-group .sub-title {
  font-size: 16px;
  font-weight: 700;
  color: #0d2b45;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 4px;
  width: 100%;
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
  min-height: 50px;
}
.modal-form .form-actions {
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

.text-muted {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

@media (max-width: 768px) {
  .modal-form .form-grid {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  .table-header {
    flex-direction: column;
    align-items: stretch;
  }
  .table-filter {
    flex-direction: column;
  }
  .modal-sm {
    max-width: 100% !important;
  }
}
</style>