<template>
  <div class="fuel-expense-container">
    <!-- Header -->
    <div class="page-header">
      <h2><i class="fas fa-coins"></i> Pengajuan Biaya Operasional</h2>
      <div class="header-actions">
        <!-- 🔥 TOMBOL EXPORT -->
        <button
          v-if="canExport"
          class="btn btn-outline-export"
          @click="handleExport"
          :disabled="isExporting"
        >
          <i class="fas fa-file-excel"></i>
          {{ isExporting ? 'Mengekspor...' : 'Export Excel' }}
        </button>
        <button v-if="canCreate" class="btn btn-primary" @click="openForm">
          <i class="fas fa-plus-circle"></i> Tambah Pengajuan
        </button>
      </div>
    </div>

    <!-- Form Tambah/Edit -->
    <div v-if="showForm && canCreate" class="form-card">
      <h3>{{ formMode === 'edit' ? 'Edit Pengajuan' : 'Tambah Pengajuan Baru' }}</h3>
      <form @submit.prevent="saveData" class="form-grid">
        <div v-if="validationErrors" class="error-box full-width">
          <ul>
            <li v-for="(err, key) in validationErrors" :key="key">
              <strong>{{ key }}:</strong> {{ err.join(', ') }}
            </li>
          </ul>
        </div>

        <!-- No. Resi / Project (hanya yang belum punya pengajuan) -->
        <div class="form-group">
          <label>No. Resi / Project <span class="required">*</span></label>
          <select
            v-model="form.delivery_task_id"
            class="form-control"
            required
            @change="autoFillFromTask"
          >
            <option value="">Pilih Tugas Kirim</option>
            <option
              v-for="task in deliveryTasks"
              :key="task.id"
              :value="task.id"
            >
              {{ task.no_resi }} - {{ task.project?.no_po || '-' }}
            </option>
          </select>
        </div>

        <!-- Jenis Biaya -->
        <div class="form-group">
          <label>Jenis Biaya <span class="required">*</span></label>
          <select v-model="form.type" class="form-control" required>
            <option value="">Pilih Jenis</option>
            <option value="bahan_bakar">Bahan Bakar</option>
            <option value="toll">Tol</option>
            <option value="parkir">Parkir</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>

        <!-- Nominal -->
        <div class="form-group">
          <label>Nominal (Rp) <span class="required">*</span></label>
          <input
            v-model.number="form.amount"
            type="number"
            class="form-control"
            placeholder="0"
            required
            min="0"
          />
        </div>

        <!-- Kendaraan (auto-fill) -->
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

        <!-- Driver (auto-fill) -->
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

        <!-- Tanggal Transaksi -->
        <div class="form-group">
          <label>Tanggal Transaksi <span class="required">*</span></label>
          <input
            v-model="form.transaction_date"
            type="date"
            class="form-control"
            required
          />
        </div>

        <!-- Deskripsi -->
        <div class="form-group full-width">
          <label>Deskripsi / Keterangan</label>
          <textarea
            v-model="form.description"
            class="form-control"
            rows="2"
            placeholder="Catatan tambahan"
          ></textarea>
        </div>

        <div class="form-actions full-width">
          <button type="submit" class="btn btn-success" :disabled="loading">
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            {{ formMode === 'edit' ? 'Update' : 'Simpan' }}
          </button>
          <button type="button" class="btn btn-secondary" @click="closeForm">
            Batal
          </button>
        </div>
      </form>
    </div>

    <!-- Tabel Data -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-filter">
          <select
            v-model="filterStatus"
            class="form-control-sm"
            @change="fetchData"
          >
            <option value="">Semua Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
          </select>
          <input
            v-model="search"
            type="text"
            class="form-control-sm"
            placeholder="Cari Kode / No. Resi..."
            @input="fetchData"
          />
        </div>
        <span class="table-info">Total: {{ totalItems }} data</span>
      </div>

      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Kode Unik</th>
              <th>No. Resi</th>
              <th>Jenis</th>
              <th>Nominal</th>
              <th>Kendaraan</th>
              <th>Driver</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="9" class="text-center">Memuat...</td>
            </tr>
            <tr v-else-if="!data.length">
              <td colspan="9" class="text-center">Tidak ada data</td>
            </tr>
            <tr v-for="item in data" :key="item.id">
              <td><strong>{{ item.unique_code }}</strong></td>
              <td>{{ item.delivery_task?.no_resi || '-' }}</td>
              <td>{{ item.type_label || item.type }}</td>
              <td class="currency">{{ formatCurrency(item.amount) }}</td>
              <td>{{ item.vehicle?.plate_number || '-' }}</td>
              <td>{{ item.driver?.name || '-' }}</td>
              <td>{{ formatDateDisplay(item.transaction_date) }}</td>
              <td>
                <span
                  class="badge"
                  :class="statusBadge(item.status)"
                >
                  {{ item.status_label || item.status }}
                </span>
              </td>
              <td class="text-center">
                <!-- Approve (admin_finance / super_admin) -->
                <button
                  v-if="canApprove && item.status === 'pending'"
                  class="btn-icon approve"
                  title="Setujui"
                  @click="approveData(item.id)"
                >
                  <i class="fas fa-check-circle"></i>
                </button>

                <!-- Reject (admin_finance / super_admin) -->
                <button
                  v-if="canApprove && item.status === 'pending'"
                  class="btn-icon reject"
                  title="Tolak"
                  @click="rejectData(item.id)"
                >
                  <i class="fas fa-times-circle"></i>
                </button>

                <!-- Edit (hanya untuk pending dan pembuat) -->
                <button
                  v-if="canEdit(item)"
                  class="btn-icon"
                  title="Edit"
                  @click="editData(item)"
                >
                  <i class="fas fa-edit"></i>
                </button>

                <!-- Hapus (hanya Super Admin) -->
                <button
                  v-if="canDelete"
                  class="btn-icon danger"
                  title="Hapus"
                  @click="deleteData(item.id)"
                >
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'
import { useExport } from '../composables/useExport'

export default {
  name: 'FuelExpenseList',
  data() {
    return {
      data: [],
      deliveryTasks: [],
      vehicles: [],
      drivers: [],
      loading: false,
      showForm: false,
      formMode: 'add',
      validationErrors: null,
      filterStatus: '',
      search: '',
      totalItems: 0,
      form: {
        id: null,
        delivery_task_id: '',
        type: '',
        amount: 0,
        vehicle_id: '',
        driver_id: '',
        transaction_date: this.getTodayDate(),
        description: '',
      },
    }
  },
  computed: {
    user() {
      return JSON.parse(localStorage.getItem('user') || '{}')
    },
    // Hak membuat pengajuan
    canCreate() {
      return ['super_admin', 'admin_project', 'admin_finance', 'branch_admin', 'staff'].includes(this.user?.role)
    },
    // Hak approve/reject
    canApprove() {
      return ['super_admin', 'admin_finance'].includes(this.user?.role)
    },
    // 🔥 Hak export
    canExport() {
      return ['super_admin', 'admin_finance', 'admin_transport'].includes(this.user?.role)
    },
    // Hak edit (pending & milik sendiri, kecuali admin_finance yang tidak bisa edit)
    canEdit() {
      return (item) => {
        if (item.status !== 'pending') return false
        if (this.user?.role === 'super_admin') return true
        if (this.user?.role === 'admin_finance') return false
        return item.created_by === this.user?.id
      }
    },
    // Hak hapus (hanya Super Admin)
    canDelete() {
      return this.user?.role === 'super_admin'
    },
  },
  setup() {
    // 🔥 Composabel untuk export
    const { isExporting, exportData } = useExport('fuel-expenses')
    return { isExporting, exportData }
  },
  mounted() {
    this.fetchData()
    this.fetchOptions()
  },
  methods: {
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

    formatDateDisplay(date) {
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

    formatCurrency(val) {
      if (!val) return 'Rp 0'
      return 'Rp ' + Number(val).toLocaleString('id-ID')
    },

    async fetchData() {
      this.loading = true
      try {
        const params = {
          status: this.filterStatus || undefined,
          search: this.search || undefined,
        }
        const res = await axios.get('/fuel-expenses', { params })
        this.data = res.data.data || []
        this.totalItems = this.data.length
      } catch (e) {
        console.error('Error fetching fuel expenses:', e)
        alert('Gagal memuat data: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loading = false
      }
    },

    // 🔥 Hanya ambil delivery task yang belum memiliki pengajuan biaya
    async fetchOptions() {
      try {
        const [tasksRes, vehiclesRes, driversRes] = await Promise.all([
          axios.get('/delivery-tasks?available_for_expense=true&limit=100'),
          axios.get('/vehicles?all=true'),
          axios.get('/drivers?all=true'),
        ])
        this.deliveryTasks = tasksRes.data.data || []
        this.vehicles = vehiclesRes.data.data || []
        this.drivers = driversRes.data.data || []
      } catch (e) {
        console.error('Error fetching options:', e)
      }
    },

    // Auto-fill kendaraan, driver, dan tanggal dari task yang dipilih
    autoFillFromTask() {
      const selectedTask = this.deliveryTasks.find(
        (t) => t.id === this.form.delivery_task_id
      )
      if (selectedTask) {
        this.form.vehicle_id = selectedTask.vehicle_id || ''
        this.form.driver_id = selectedTask.driver_id || ''
        const taskDate = selectedTask.tanggal || selectedTask.delivery_date
        this.form.transaction_date =
          this.formatDateInput(taskDate) || this.getTodayDate()
        if (!this.form.description) {
          this.form.description = `Biaya untuk tugas ${selectedTask.no_resi}`
        }
      } else {
        this.form.vehicle_id = ''
        this.form.driver_id = ''
        this.form.transaction_date = this.getTodayDate()
        this.form.description = ''
      }
    },

    openForm() {
      this.formMode = 'add'
      this.validationErrors = null
      this.form = {
        id: null,
        delivery_task_id: '',
        type: '',
        amount: 0,
        vehicle_id: '',
        driver_id: '',
        transaction_date: this.getTodayDate(),
        description: '',
      }
      this.showForm = true
    },

    closeForm() {
      this.showForm = false
      this.validationErrors = null
    },

    editData(item) {
      this.formMode = 'edit'
      this.validationErrors = null
      this.form = {
        id: item.id,
        delivery_task_id: item.delivery_task_id || '',
        type: item.type || '',
        amount: item.amount || 0,
        vehicle_id: item.vehicle_id || '',
        driver_id: item.driver_id || '',
        transaction_date: this.formatDateInput(item.transaction_date) || this.getTodayDate(),
        description: item.description || '',
      }
      this.showForm = true
    },

    async saveData() {
      this.loading = true
      this.validationErrors = null
      try {
        const payload = { ...this.form }
        delete payload.id

        if (this.formMode === 'edit') {
          await axios.put(`/fuel-expenses/${this.form.id}`, payload)
        } else {
          await axios.post('/fuel-expenses', payload)
        }
        this.closeForm()
        this.fetchData()
        // Refresh dropdown options agar task yang sudah dipakai tidak muncul lagi
        this.fetchOptions()
        alert('Data berhasil disimpan')
      } catch (e) {
        if (e.response && e.response.status === 422) {
          this.validationErrors = e.response.data.errors
        } else {
          alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
        }
      } finally {
        this.loading = false
      }
    },

    async approveData(id) {
      if (!confirm('Setujui pengajuan ini?')) return
      try {
        await axios.post(`/fuel-expenses/${id}/approve`)
        this.fetchData()
        alert('Pengajuan berhasil disetujui')
      } catch (e) {
        alert('Gagal menyetujui: ' + (e.response?.data?.message || e.message))
      }
    },

    async rejectData(id) {
      if (!confirm('Tolak pengajuan ini?')) return
      try {
        await axios.post(`/fuel-expenses/${id}/reject`)
        this.fetchData()
        alert('Pengajuan berhasil ditolak')
      } catch (e) {
        alert('Gagal menolak: ' + (e.response?.data?.message || e.message))
      }
    },

    async deleteData(id) {
      if (!confirm('Yakin ingin menghapus pengajuan ini?')) return
      try {
        await axios.delete(`/fuel-expenses/${id}`)
        this.fetchData()
        // Refresh dropdown options agar task yang dihapus muncul kembali
        this.fetchOptions()
        alert('Pengajuan berhasil dihapus')
      } catch (e) {
        alert('Gagal menghapus: ' + (e.response?.data?.message || e.message))
      }
    },

    statusBadge(status) {
      const map = {
        pending: 'badge-warning',
        approved: 'badge-success',
        rejected: 'badge-danger',
      }
      return map[status] || 'badge-secondary'
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
.fuel-expense-container {
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

.form-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.form-card h3 {
  font-size: 18px;
  font-weight: 600;
  color: #0d2b45;
  margin: 0 0 20px 0;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 12px;
}
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 24px;
}
.form-group {
  display: flex;
  flex-direction: column;
}
.form-group.full-width {
  grid-column: 1 / -1;
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
textarea.form-control {
  resize: vertical;
  min-height: 60px;
}
.form-control-sm {
  padding: 6px 10px;
  border: 1.5px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
  background: white;
}
.form-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 12px;
  margin-top: 8px;
  justify-content: flex-end;
}

.table-card {
  background: white;
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
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
.currency {
  font-weight: 600;
  color: #1a202c;
}

.badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
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
.badge-secondary {
  background: #e2e8f0;
  color: #475569;
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
.btn-icon.approve {
  color: #22c55e;
}
.btn-icon.approve:hover {
  color: #16a34a;
}
.btn-icon.reject {
  color: #dc2626;
}
.btn-icon.reject:hover {
  color: #b91c1c;
}

.error-box {
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
  .table-header {
    flex-direction: column;
    align-items: stretch;
  }
  .table-filter {
    flex-direction: column;
  }
}
</style>