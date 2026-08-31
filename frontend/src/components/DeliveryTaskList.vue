<template>
  <div class="delivery-task-container">
    <!-- Header -->
    <div class="page-header">
      <h2><i class="fas fa-tasks"></i> Tugas Kirim</h2>
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
        <button class="btn btn-primary" @click="openModal()">
          <i class="fas fa-plus-circle"></i> Tambah Tugas
        </button>
      </div>
    </div>

    <!-- Tabel -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-filter">
          <input
            v-model="search"
            type="text"
            class="form-control-sm"
            placeholder="Cari No Resi / Project..."
            @input="fetchData"
          />
          <select
            v-model="filterStatus"
            class="form-control-sm"
            @change="fetchData"
          >
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="assigned">Ditugaskan</option>
            <option value="in_progress">Dalam Perjalanan</option>
            <option value="completed">Selesai</option>
            <option value="cancelled">Dibatalkan</option>
          </select>
        </div>
        <span class="table-info">Total: {{ totalItems }} data</span>
      </div>

      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>No Resi</th>
              <th>Project</th>
              <th>Kendaraan</th>
              <th>Driver</th>
              <th>Penerima</th>
              <th>Tanggal Kirim</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="8" class="text-center">Memuat...</td></tr>
            <tr v-else-if="!data.length"><td colspan="8" class="text-center">Belum ada tugas</td></tr>
            <tr v-for="item in data" :key="item.id">
              <td><strong>{{ item.no_resi || '-' }}</strong></td>
              <td>{{ item.project?.no_po || '-' }}</td>
              <td>{{ item.vehicle?.plate_number || '-' }}</td>
              <td>{{ item.driver?.name || '-' }}</td>
              <td>{{ item.receiver_name || '-' }}</td>
              <td>{{ formatDate(item.tanggal || item.delivery_date) }}</td>
              <td>
                <span class="badge" :class="statusBadge(item.status)">
                  {{ statusLabel(item.status) }}
                </span>
              </td>
              <td class="text-center">
                <!-- Tombol Update Status -->
                <button
                  v-if="canUpdateStatus(item)"
                  class="btn-icon status-update"
                  title="Update Status"
                  @click="openStatusModal(item)"
                >
                  <i class="fas fa-arrow-right"></i>
                </button>
                <button class="btn-icon" title="Edit" @click="openModal(item)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon danger" title="Hapus" @click="deleteData(item.id)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Tambah/Edit -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class="fas fa-truck"></i> {{ formMode === 'edit' ? 'Edit Tugas Kirim' : 'Tambah Tugas Kirim' }}</h3>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <form @submit.prevent="saveData" class="modal-form">
          <div v-if="validationErrors" class="error-box full-width">
            <ul>
              <li v-for="(err, key) in validationErrors" :key="key">
                <strong>{{ key }}:</strong> {{ err.join(', ') }}
              </li>
            </ul>
          </div>
          <div class="form-grid">
            <!-- Project -->
            <div class="form-group">
              <label>Project <span class="required">*</span></label>
              <select
                v-model="form.project_id"
                class="form-control"
                required
                @change="onProjectChange"
              >
                <option value="">Pilih Project</option>
                <option
                  v-for="p in projects"
                  :key="p.id"
                  :value="p.id"
                >
                  {{ p.no_po }} - {{ p.client?.name || '-' }}
                  <span v-if="p.status" class="text-muted">({{ p.status }})</span>
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>No Resi <span class="required">*</span></label>
              <input v-model="form.no_resi" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Kendaraan <span class="required">*</span></label>
              <select v-model="form.vehicle_id" class="form-control" required>
                <option value="">Pilih Kendaraan</option>
                <option
                  v-for="v in vehicles"
                  :key="v.id"
                  :value="v.id"
                >
                  {{ v.plate_number }} - {{ v.brand }} {{ v.model }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Driver <span class="required">*</span></label>
              <select v-model="form.driver_id" class="form-control" required>
                <option value="">Pilih Driver</option>
                <option
                  v-for="d in drivers"
                  :key="d.id"
                  :value="d.id"
                >
                  {{ d.name }} ({{ d.license_number || '-' }})
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Client <span class="required">*</span></label>
              <select v-model="form.client_id" class="form-control" required>
                <option value="">Pilih Client</option>
                <option
                  v-for="c in clients"
                  :key="c.id"
                  :value="c.id"
                >
                  {{ c.name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Tanggal Kirim <span class="required">*</span></label>
              <input
                v-model="form.delivery_date"
                type="date"
                class="form-control"
                required
              />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-control">
                <option v-for="s in statusOptions" :key="s" :value="s">
                  {{ statusLabel(s) }}
                </option>
              </select>
            </div>
            <!-- Data Penerima -->
            <div class="form-group full-width">
              <label class="sub-title">Data Penerima</label>
            </div>
            <div class="form-group">
              <label>Nama Penerima <span class="required">*</span></label>
              <input v-model="form.receiver_name" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Telepon Penerima <span class="required">*</span></label>
              <input v-model="form.receiver_phone" type="text" class="form-control" required />
            </div>
            <div class="form-group full-width">
              <label>Alamat Penerima <span class="required">*</span></label>
              <textarea v-model="form.receiver_address" class="form-control" rows="2" required></textarea>
            </div>
            <!-- Data Barang -->
            <div class="form-group full-width">
              <label class="sub-title">Data Barang</label>
            </div>
            <div class="form-group">
              <label>Deskripsi Barang</label>
              <input v-model="form.goods_description" type="text" class="form-control" />
            </div>
            <div class="form-group">
              <label>Berat (kg)</label>
              <input v-model.number="form.weight_kg" type="number" class="form-control" min="0" />
            </div>
            <div class="form-group">
              <label>Collie</label>
              <input v-model.number="form.collie" type="number" class="form-control" min="0" />
            </div>
            <div class="form-group full-width">
              <label>Catatan</label>
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
          <p><strong>No Resi:</strong> {{ selectedTask?.no_resi }}</p>
          <p>
            <strong>Status Saat Ini:</strong>
            <span class="badge" :class="statusBadge(selectedTask?.status)">
              {{ statusLabel(selectedTask?.status) }}
            </span>
          </p>
        </div>
        <div class="status-actions">
          <button
            v-for="s in availableStatuses"
            :key="s"
            class="btn btn-status"
            :class="statusButtonClass(s)"
            @click="updateStatus(selectedTask?.id, s)"
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

const STATUS_ORDER = ['draft', 'assigned', 'in_progress', 'completed', 'cancelled']

export default {
  name: 'DeliveryTaskList',
  data() {
    return {
      data: [],
      projects: [],
      vehicles: [],
      drivers: [],
      clients: [],
      loading: false,
      search: '',
      filterStatus: '',
      totalItems: 0,

      showModal: false,
      formMode: 'add',
      loadingSubmit: false,
      validationErrors: null,
      form: {
        id: null,
        project_id: '',
        vehicle_id: '',
        driver_id: '',
        client_id: '',
        no_resi: '',
        delivery_date: '',
        receiver_name: '',
        receiver_address: '',
        receiver_phone: '',
        goods_description: '',
        weight_kg: 0,
        collie: 0,
        status: 'draft',
        notes: '',
      },

      showStatusModal: false,
      selectedTask: null,
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
      return ['super_admin', 'admin_project'].includes(role)
    },
    statusOptions() {
      return STATUS_ORDER
    },
    availableStatuses() {
      if (!this.selectedTask) return []
      const current = this.selectedTask.status
      const index = STATUS_ORDER.indexOf(current)
      const nextStatuses = []
      if (index !== -1 && index < STATUS_ORDER.length - 1) {
        nextStatuses.push(STATUS_ORDER[index + 1])
      }
      if (current !== 'cancelled' && current !== 'completed') {
        nextStatuses.push('cancelled')
      }
      return nextStatuses
    },
  },
  setup() {
    const { isExporting, exportData } = useExport('delivery-tasks')
    return { isExporting, exportData }
  },
  mounted() {
    this.fetchData()
    this.fetchOptions()
  },
  methods: {
    // ===== FETCH DATA =====
    async fetchData() {
      this.loading = true
      try {
        const params = {
          search: this.search || undefined,
          status: this.filterStatus || undefined,
        }
        const res = await axios.get('/delivery-tasks', { params })
        this.data = res.data.data || []
        this.totalItems = this.data.length
      } catch (e) {
        console.error(e)
      } finally {
        this.loading = false
      }
    },

    async fetchOptions() {
      try {
        const [projRes, vehRes, drvRes, cliRes] = await Promise.all([
          axios.get('/projects/po-list'),
          axios.get('/vehicles?all=true'),
          axios.get('/drivers?all=true'),
          axios.get('/clients'),
        ])
        // Data projects sudah difilter oleh backend (status != completed/cancelled)
        this.projects = projRes.data.data || []
        this.vehicles = vehRes.data.data || []
        this.drivers = drvRes.data.data || []
        this.clients = cliRes.data.data || []
      } catch (e) {
        console.error(e)
      }
    },

    // ===== HELPER =====
    generateResi() {
      return 'RESI-' + Math.random().toString(36).substring(2, 8).toUpperCase()
    },
    getTodayDate() {
      const d = new Date()
      return (
        d.getFullYear() +
        '-' +
        String(d.getMonth() + 1).padStart(2, '0') +
        '-' +
        String(d.getDate()).padStart(2, '0')
      )
    },
    formatDateInput(date) {
      if (!date) return ''
      const d = new Date(date)
      if (isNaN(d.getTime())) return ''
      return (
        d.getFullYear() +
        '-' +
        String(d.getMonth() + 1).padStart(2, '0') +
        '-' +
        String(d.getDate()).padStart(2, '0')
      )
    },
    formatDate(date) {
      if (!date) return '-'
      const d = new Date(date)
      if (isNaN(d.getTime())) return '-'
      return (
        String(d.getDate()).padStart(2, '0') +
        '-' +
        String(d.getMonth() + 1).padStart(2, '0') +
        '-' +
        d.getFullYear()
      )
    },
    statusLabel(status) {
      const map = {
        draft: 'Draft',
        assigned: 'Ditugaskan',
        in_progress: 'Dalam Perjalanan',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
      }
      return map[status] || status
    },
    statusBadge(status) {
      const map = {
        draft: 'badge-secondary',
        assigned: 'badge-info',
        in_progress: 'badge-warning',
        completed: 'badge-success',
        cancelled: 'badge-danger',
      }
      return map[status] || 'badge-secondary'
    },
    statusButtonClass(status) {
      return {
        'btn-info': status === 'assigned',
        'btn-warning': status === 'in_progress',
        'btn-success': status === 'completed',
        'btn-danger': status === 'cancelled',
      }
    },

    // ===== CRUD =====
    onProjectChange() {
      const p = this.projects.find((x) => x.id === this.form.project_id)
      if (p) {
        this.form.client_id = p.client_id || ''
        if (!this.form.no_resi) this.form.no_resi = this.generateResi()
      } else {
        this.form.client_id = ''
      }
    },

    openModal(item = null) {
      this.validationErrors = null
      if (item) {
        this.formMode = 'edit'
        this.form = {
          id: item.id,
          project_id: item.project_id || '',
          vehicle_id: item.vehicle_id || '',
          driver_id: item.driver_id || '',
          client_id: item.client_id || '',
          no_resi: item.no_resi || '',
          delivery_date: this.formatDateInput(item.tanggal || item.delivery_date),
          receiver_name: item.receiver_name || '',
          receiver_address: item.receiver_address || '',
          receiver_phone: item.receiver_phone || '',
          goods_description: item.goods_description || '',
          weight_kg: item.weight_kg || 0,
          collie: item.collie || 0,
          status: item.status || 'draft',
          notes: item.notes || '',
        }
      } else {
        this.formMode = 'add'
        this.form = {
          id: null,
          project_id: '',
          vehicle_id: '',
          driver_id: '',
          client_id: '',
          no_resi: this.generateResi(),
          delivery_date: this.getTodayDate(),
          receiver_name: '',
          receiver_address: '',
          receiver_phone: '',
          goods_description: '',
          weight_kg: 0,
          collie: 0,
          status: 'draft',
          notes: '',
        }
      }
      this.showModal = true
    },

    closeModal() {
      this.showModal = false
      this.loadingSubmit = false
      this.validationErrors = null
    },

    async saveData() {
      this.loadingSubmit = true
      this.validationErrors = null
      try {
        const payload = { ...this.form }
        delete payload.id
        let response
        if (this.formMode === 'edit') {
          response = await axios.put(`/delivery-tasks/${this.form.id}`, payload)
        } else {
          response = await axios.post('/delivery-tasks', payload)
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
      } finally {
        this.loadingSubmit = false
      }
    },

    async deleteData(id) {
      if (!confirm('Yakin hapus?')) return
      try {
        await axios.delete(`/delivery-tasks/${id}`)
        this.fetchData()
      } catch (e) {
        alert('Gagal hapus: ' + (e.response?.data?.message || e.message))
      }
    },

    // ===== STATUS UPDATE =====
    canUpdateStatus(item) {
      if (!item) return false
      const current = item.status
      return current !== 'completed' && current !== 'cancelled'
    },

    openStatusModal(item) {
      this.selectedTask = item
      this.showStatusModal = true
    },

    closeStatusModal() {
      this.showStatusModal = false
      this.selectedTask = null
      this.loadingStatus = false
    },

    async updateStatus(id, newStatus) {
      this.loadingStatus = true
      this.selectedStatus = newStatus
      try {
        await axios.patch(`/delivery-tasks/${id}/status`, { status: newStatus })
        this.closeStatusModal()
        this.fetchData()
        alert('Status berhasil diupdate')
      } catch (e) {
        alert('Gagal update status: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loadingStatus = false
        this.selectedStatus = ''
      }
    },

    // ===== 🔥 EXPORT EXCEL =====
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
/* Semua gaya tetap seperti sebelumnya */
.delivery-task-container {
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

.table-card {
  background: white;
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}
.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 12px;
}
.table-filter {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}
.table-info {
  font-size: 14px;
  color: #6b7280;
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
.btn-icon.status-update:hover {
  color: #22c55e;
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
.btn-status {
  min-width: 120px;
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
  .table-header {
    flex-direction: column;
    align-items: stretch;
  }
  .table-filter {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>