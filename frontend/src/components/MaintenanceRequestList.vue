<template>
  <div class="maintenance-container">
    <!-- Header -->
    <div class="page-header">
      <h2><i class="fas fa-tools"></i> Pengajuan Perawatan Kendaraan</h2>
      <div class="header-actions">
        <!-- Tombol Export -->
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

    <!-- Filter -->
    <div class="filter-bar">
      <input
        v-model="search"
        type="text"
        class="form-control-sm"
        placeholder="Cari kode / deskripsi..."
        @input="fetchData"
      />
      <select v-model="filterStatus" class="form-control-sm" @change="fetchData">
        <option value="">Semua Status</option>
        <option value="pending">Pending</option>
        <option value="approved">Disetujui</option>
        <option value="rejected">Ditolak</option>
        <option value="done">Selesai</option>
      </select>
      <select v-model="filterServiceType" class="form-control-sm" @change="fetchData">
        <option value="">Semua Tipe Service</option>
        <option value="oil_change">Ganti Oli</option>
        <option value="tire_replacement">Ganti Ban</option>
        <option value="sparepart">Sparepart</option>
        <option value="general">General Service</option>
        <option value="other">Lainnya</option>
      </select>
      <select v-model="filterUrgency" class="form-control-sm" @change="fetchData">
        <option value="">Semua Urgency</option>
        <option value="low">Rendah</option>
        <option value="medium">Sedang</option>
        <option value="high">Tinggi</option>
      </select>
    </div>

    <!-- Tabel Data -->
    <div class="table-card">
      <div class="table-header">
        <span class="table-info">Total: {{ totalItems }} data</span>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Kendaraan</th>
              <th>Driver</th>
              <th>Tipe Service</th>
              <th>Urgency</th>
              <th>Estimasi</th>
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
              <td><strong>{{ item.request_code || '-' }}</strong></td>
              <td>{{ item.vehicle?.plate_number || '-' }}</td>
              <td>{{ item.driver?.name || '-' }}</td>
              <td>{{ serviceTypeLabel(item.service_type) }}</td>
              <td>
                <span class="badge" :class="urgencyBadge(item.urgency)">
                  {{ urgencyLabel(item.urgency) }}
                </span>
              </td>
              <td>{{ formatCurrency(item.estimated_cost) }}</td>
              <td>{{ formatDateDisplay(item.request_date) }}</td>
              <td>
                <span class="badge" :class="statusBadge(item.status)">
                  {{ statusLabel(item.status) }}
                </span>
              </td>
              <td class="text-center">
                <!-- Approve -->
                <button
                  v-if="canApprove && item.status === 'pending'"
                  class="btn-icon approve"
                  title="Setujui"
                  @click="approveData(item.id)"
                >
                  <i class="fas fa-check-circle"></i>
                </button>
                <!-- Reject -->
                <button
                  v-if="canApprove && item.status === 'pending'"
                  class="btn-icon reject"
                  title="Tolak"
                  @click="rejectData(item.id)"
                >
                  <i class="fas fa-times-circle"></i>
                </button>
                <!-- Execute (Selesai) -->
                <button
                  v-if="canExecute && item.status === 'approved' && !item.is_executed"
                  class="btn-icon execute"
                  title="Tandai Selesai"
                  @click="executeData(item.id)"
                >
                  <i class="fas fa-check-double"></i>
                </button>
                <!-- Edit -->
                <button
                  v-if="canEdit(item)"
                  class="btn-icon"
                  title="Edit"
                  @click="editData(item)"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <!-- Hapus -->
                <button
                  v-if="canDelete(item)"
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

    <!-- Modal Tambah / Edit -->
    <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class="fas fa-tools"></i> {{ formMode === 'edit' ? 'Edit Pengajuan' : 'Tambah Pengajuan' }}</h3>
          <button class="btn-close" @click="closeForm">&times;</button>
        </div>
        <form @submit.prevent="saveData">
          <div v-if="validationErrors" class="error-box">
            <ul>
              <li v-for="(err, key) in validationErrors" :key="key">
                <strong>{{ key }}:</strong> {{ err.join(', ') }}
              </li>
            </ul>
          </div>

          <!-- Form Utama -->
          <div class="form-grid">
            <div class="form-group">
              <label>Kode Pengajuan</label>
              <input
                v-model="form.request_code"
                type="text"
                class="form-control"
                readonly
                placeholder="Auto-generated"
              />
              <small class="text-muted">Kode akan dibuat otomatis</small>
            </div>

            <div class="form-group">
              <label>Kendaraan <span class="required">*</span></label>
              <select v-model="form.vehicle_id" class="form-control" required>
                <option value="">Pilih Kendaraan</option>
                <option v-for="v in vehicles" :key="v.id" :value="v.id">
                  {{ v.plate_number }} - {{ v.brand }} {{ v.model }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Driver <span class="required">*</span></label>
              <select v-model="form.driver_id" class="form-control" required>
                <option value="">Pilih Driver</option>
                <option v-for="d in drivers" :key="d.id" :value="d.id">
                  {{ d.name }} ({{ d.license_number || '-' }})
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Tipe Service <span class="required">*</span></label>
              <select v-model="form.service_type" class="form-control" required>
                <option value="">Pilih Tipe</option>
                <option value="oil_change">Ganti Oli</option>
                <option value="tire_replacement">Ganti Ban</option>
                <option value="sparepart">Sparepart</option>
                <option value="general">General Service</option>
                <option value="other">Lainnya</option>
              </select>
            </div>

            <div class="form-group">
              <label>Urgency <span class="required">*</span></label>
              <select v-model="form.urgency" class="form-control" required>
                <option value="low">Rendah</option>
                <option value="medium">Sedang</option>
                <option value="high">Tinggi</option>
              </select>
            </div>

            <div class="form-group">
              <label>Estimasi Biaya (Rp)</label>
              <input
                v-model.number="form.estimated_cost"
                type="number"
                class="form-control"
                min="0"
                placeholder="0"
              />
            </div>

            <div class="form-group">
              <label>Tanggal Pengajuan <span class="required">*</span></label>
              <input
                v-model="form.request_date"
                type="date"
                class="form-control"
                required
              />
            </div>

            <div class="form-group">
              <label>Tanggal Service (opsional)</label>
              <input
                v-model="form.scheduled_date"
                type="date"
                class="form-control"
              />
            </div>

            <div class="form-group full-width">
              <label>Deskripsi <span class="required">*</span></label>
              <textarea
                v-model="form.description"
                class="form-control"
                rows="3"
                required
                placeholder="Jelaskan masalah / kebutuhan perawatan"
              ></textarea>
            </div>
          </div>

          <!-- 🔥 TABEL SPARE PART -->
          <div class="spare-part-section">
            <h4><i class="fas fa-boxes"></i> Spare Part Digunakan</h4>
            <p class="text-muted">Tambahkan spare part yang digunakan dalam perawatan ini.</p>

            <div class="table-responsive">
              <table class="table table-sparepart">
                <thead>
                  <tr>
                    <th style="width:30%;">Nama Spare Part</th>
                    <th style="width:12%;">Jumlah</th>
                    <th style="width:15%;">Odometer Awal (km)</th>
                    <th style="width:15%;">Odometer Akhir (km)</th>
                    <th style="width:20%;">Keterangan</th>
                    <th style="width:8%;" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in form.items" :key="index">
                    <td>
                      <select v-model="item.spare_part_id" class="form-control">
                        <option value="">Pilih Spare Part</option>
                        <option
                          v-for="part in spareParts"
                          :key="part.id"
                          :value="part.id"
                        >
                          {{ part.code }} - {{ part.name }} (Stok: {{ part.stock }})
                        </option>
                      </select>
                    </td>
                    <td>
                      <input
                        v-model.number="item.quantity"
                        type="number"
                        class="form-control"
                        min="1"
                        placeholder="1"
                      />
                    </td>
                    <td>
                      <input
                        v-model.number="item.odometer_before"
                        type="number"
                        class="form-control"
                        min="0"
                        placeholder="0"
                      />
                    </td>
                    <td>
                      <input
                        v-model.number="item.odometer_after"
                        type="number"
                        class="form-control"
                        min="0"
                        placeholder="0"
                      />
                    </td>
                    <td>
                      <input
                        v-model="item.notes"
                        type="text"
                        class="form-control"
                        placeholder="Catatan"
                      />
                    </td>
                    <td class="text-center">
                      <button
                        type="button"
                        class="btn-icon danger"
                        @click="removeItem(index)"
                      >
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!form.items.length">
                    <td colspan="6" class="text-center text-muted">
                      Belum ada spare part. Klik tombol "Tambah Baris" untuk menambahkan.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <button
              type="button"
              class="btn btn-outline-add"
              @click="addItemRow"
            >
              <i class="fas fa-plus-circle"></i> Tambah Baris
            </button>
          </div>

          <!-- Tombol Aksi Form -->
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeForm">Batal</button>
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
import { useExport } from '../composables/useExport'

export default {
  name: 'MaintenanceRequestList',
  data() {
    return {
      data: [],
      vehicles: [],
      drivers: [],
      spareParts: [],
      loading: false,
      search: '',
      filterStatus: '',
      filterServiceType: '',
      filterUrgency: '',
      totalItems: 0,

      showForm: false,
      formMode: 'add',
      loadingSubmit: false,
      validationErrors: null,
      form: {
        id: null,
        request_code: '',
        vehicle_id: '',
        driver_id: '',
        service_type: '',
        urgency: 'medium',
        estimated_cost: 0,
        request_date: this.getTodayDate(),
        scheduled_date: '',
        description: '',
        items: [], // 🔥 Array untuk spare part items
      },
    }
  },
  computed: {
    user() {
      return JSON.parse(localStorage.getItem('user') || '{}')
    },
    canCreate() {
      return ['super_admin', 'admin_project', 'admin_finance', 'admin_transport', 'branch_admin', 'staff'].includes(this.user?.role)
    },
    canApprove() {
      return ['super_admin', 'admin_finance'].includes(this.user?.role)
    },
    canExecute() {
      return ['super_admin', 'admin_finance', 'admin_transport'].includes(this.user?.role)
    },
    canExport() {
      return ['super_admin', 'admin_finance', 'admin_transport'].includes(this.user?.role)
    },
    canEdit() {
      return (item) => {
        if (!item) return false
        if (this.user?.role === 'super_admin') return true
        if (this.user?.role === 'admin_finance') return false
        if (this.user?.role === 'admin_transport') return false
        return item.status === 'pending' && item.created_by === this.user?.id
      }
    },
    canDelete() {
      return (item) => {
        if (!item) return false
        if (this.user?.role === 'super_admin') return true
        return item.status === 'pending' && item.created_by === this.user?.id
      }
    },
  },
  setup() {
    const { isExporting, exportData } = useExport('maintenance-requests')
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

    formatCurrency(val) {
      if (!val) return 'Rp 0'
      return 'Rp ' + Number(val).toLocaleString('id-ID')
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

    serviceTypeLabel(type) {
      const map = {
        oil_change: 'Ganti Oli',
        tire_replacement: 'Ganti Ban',
        sparepart: 'Sparepart',
        general: 'General Service',
        other: 'Lainnya',
      }
      return map[type] || type
    },

    urgencyLabel(urgency) {
      const map = {
        low: 'Rendah',
        medium: 'Sedang',
        high: 'Tinggi',
      }
      return map[urgency] || urgency
    },

    urgencyBadge(urgency) {
      const map = {
        low: 'badge-info',
        medium: 'badge-warning',
        high: 'badge-danger',
      }
      return map[urgency] || 'badge-secondary'
    },

    statusLabel(status) {
      const map = {
        pending: 'Pending',
        approved: 'Disetujui',
        rejected: 'Ditolak',
        done: 'Selesai',
      }
      return map[status] || status
    },

    statusBadge(status) {
      const map = {
        pending: 'badge-warning',
        approved: 'badge-info',
        rejected: 'badge-danger',
        done: 'badge-success',
      }
      return map[status] || 'badge-secondary'
    },

    // ===== FETCH DATA =====
    async fetchData() {
      this.loading = true
      try {
        const params = {
          search: this.search || undefined,
          status: this.filterStatus || undefined,
          service_type: this.filterServiceType || undefined,
          urgency: this.filterUrgency || undefined,
        }
        const res = await axios.get('/maintenance-requests', { params })
        this.data = res.data.data || []
        this.totalItems = this.data.length
      } catch (e) {
        console.error('Error fetching maintenance requests:', e)
        alert('Gagal memuat data: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loading = false
      }
    },

    async fetchOptions() {
      try {
        const [vehiclesRes, driversRes, partsRes] = await Promise.all([
          axios.get('/vehicles?all=true'),
          axios.get('/drivers?all=true'),
          axios.get('/spare-parts?all=true'),
        ])
        this.vehicles = vehiclesRes.data.data || []
        this.drivers = driversRes.data.data || []
        this.spareParts = partsRes.data.data || []
      } catch (e) {
        console.error('Error fetching options:', e)
      }
    },

    // ===== CRUD =====
    openForm() {
      this.formMode = 'add'
      this.validationErrors = null
      this.form = {
        id: null,
        request_code: 'MNT-' + Math.random().toString(36).substring(2, 8).toUpperCase(),
        vehicle_id: '',
        driver_id: '',
        service_type: '',
        urgency: 'medium',
        estimated_cost: 0,
        request_date: this.getTodayDate(),
        scheduled_date: '',
        description: '',
        items: [], // Reset items
      }
      // Tambahkan satu baris kosong
      this.addItemRow()
      this.showForm = true
    },

    editData(item) {
      this.formMode = 'edit'
      this.validationErrors = null
      this.form = {
        id: item.id,
        request_code: item.request_code || '',
        vehicle_id: item.vehicle_id || '',
        driver_id: item.driver_id || '',
        service_type: item.service_type || '',
        urgency: item.urgency || 'medium',
        estimated_cost: item.estimated_cost || 0,
        request_date: item.request_date || this.getTodayDate(),
        scheduled_date: item.scheduled_date || '',
        description: item.description || '',
        items: [],
      }

      // Jika ada items, load ke form
      if (item.items && item.items.length > 0) {
        this.form.items = item.items.map((it) => ({
          spare_part_id: it.spare_part_id || '',
          quantity: it.quantity || 1,
          odometer_before: it.odometer_before || null,
          odometer_after: it.odometer_after || null,
          notes: it.notes || '',
        }))
      } else {
        this.addItemRow() // Tambahkan baris kosong jika tidak ada
      }

      this.showForm = true
    },

    closeForm() {
      this.showForm = false
      this.loadingSubmit = false
      this.validationErrors = null
    },

    // ===== SPARE PART ITEMS =====
    addItemRow() {
      this.form.items.push({
        spare_part_id: '',
        quantity: 1,
        odometer_before: null,
        odometer_after: null,
        notes: '',
      })
    },

    removeItem(index) {
      if (this.form.items.length <= 1) {
        alert('Minimal harus ada satu baris spare part.')
        return
      }
      this.form.items.splice(index, 1)
    },

    // ===== SAVE =====
    async saveData() {
      this.loadingSubmit = true
      this.validationErrors = null
      try {
        // Validasi minimal satu item terisi
        const hasValidItem = this.form.items.some(
          (it) => it.spare_part_id && it.quantity > 0
        )
        if (!hasValidItem) {
          alert('Silakan pilih minimal satu spare part dan isi jumlahnya.')
          this.loadingSubmit = false
          return
        }

        const payload = { ...this.form }
        delete payload.id

        // Bersihkan items: hanya kirim yang memiliki spare_part_id
        payload.items = this.form.items
          .filter((it) => it.spare_part_id)
          .map((it) => ({
            spare_part_id: it.spare_part_id,
            quantity: it.quantity || 1,
            odometer_before: it.odometer_before || null,
            odometer_after: it.odometer_after || null,
            notes: it.notes || '',
          }))

        let response
        if (this.formMode === 'edit') {
          response = await axios.put(`/maintenance-requests/${this.form.id}`, payload)
        } else {
          response = await axios.post('/maintenance-requests', payload)
        }
        this.closeForm()
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
      if (!confirm('Yakin hapus pengajuan ini?')) return
      try {
        await axios.delete(`/maintenance-requests/${id}`)
        this.fetchData()
        alert('Pengajuan berhasil dihapus')
      } catch (e) {
        alert('Gagal hapus: ' + (e.response?.data?.message || e.message))
      }
    },

    // ===== APPROVE / REJECT / EXECUTE =====
    async approveData(id) {
      if (!confirm('Setujui pengajuan ini?')) return
      try {
        await axios.post(`/maintenance-requests/${id}/approve`)
        this.fetchData()
        alert('Pengajuan berhasil disetujui')
      } catch (e) {
        alert('Gagal menyetujui: ' + (e.response?.data?.message || e.message))
      }
    },

    async rejectData(id) {
      if (!confirm('Tolak pengajuan ini?')) return
      try {
        await axios.post(`/maintenance-requests/${id}/reject`)
        this.fetchData()
        alert('Pengajuan berhasil ditolak')
      } catch (e) {
        alert('Gagal menolak: ' + (e.response?.data?.message || e.message))
      }
    },

    async executeData(id) {
      if (!confirm('Tandai perawatan ini sebagai SELESAI?')) return
      try {
        await axios.post(`/maintenance-requests/${id}/execute`)
        this.fetchData()
        alert('Perawatan berhasil ditandai selesai!')
      } catch (e) {
        alert('Gagal: ' + (e.response?.data?.message || e.message))
      }
    },

    // ===== EXPORT =====
    async handleExport() {
      await this.exportData({
        search: this.search || undefined,
        status: this.filterStatus || undefined,
        service_type: this.filterServiceType || undefined,
        urgency: this.filterUrgency || undefined,
      })
    },
  },
}
</script>

<style scoped>
/* ========================================================== */
/* GAYA DASAR (Sama seperti sebelumnya) */
/* ========================================================== */
.maintenance-container {
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
.btn-outline-add {
  background: white;
  color: #2b6cb0;
  border: 1.5px solid #2b6cb0;
  padding: 6px 16px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 13px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: 0.2s;
}
.btn-outline-add:hover {
  background: #2b6cb0;
  color: white;
}

/* ========================================================== */
/* FILTER */
/* ========================================================== */
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

/* ========================================================== */
/* TABLE UTAMA */
/* ========================================================== */
.table-card {
  background: white;
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}
.table-header {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
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

/* ========================================================== */
/* BADGE */
/* ========================================================== */
.badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}
.badge-success {
  background: #d1fae5;
  color: #065f46;
}
.badge-info {
  background: #dbeafe;
  color: #1e40af;
}
.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}
.badge-warning {
  background: #fef3c7;
  color: #92400e;
}
.badge-secondary {
  background: #e2e8f0;
  color: #475569;
}

/* ========================================================== */
/* ICON BUTTONS */
/* ========================================================== */
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
.btn-icon.execute {
  color: #3b82f6;
}
.btn-icon.execute:hover {
  color: #2563eb;
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
  max-width: 900px;
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

/* ========================================================== */
/* FORM UTAMA */
/* ========================================================== */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 20px;
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
.form-control[readonly] {
  background: #f1f5f9;
  cursor: not-allowed;
}
textarea.form-control {
  resize: vertical;
  min-height: 80px;
}
.form-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 12px;
  margin-top: 16px;
  justify-content: flex-end;
}
.text-muted {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}

/* ========================================================== */
/* SPARE PART TABLE */
/* ========================================================== */
.spare-part-section {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 2px solid #e2e8f0;
}
.spare-part-section h4 {
  font-size: 16px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0 0 4px 0;
}
.spare-part-section h4 i {
  color: #2b6cb0;
  margin-right: 8px;
}
.table-responsive {
  overflow-x: auto;
  margin: 12px 0 12px 0;
}
.table-sparepart {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.table-sparepart thead {
  background: #f7fafc;
  border-bottom: 2px solid #e2e8f0;
}
.table-sparepart th {
  padding: 8px 8px;
  text-align: left;
  font-weight: 600;
  color: #2d3748;
  white-space: nowrap;
}
.table-sparepart td {
  padding: 6px 8px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f3f5;
}
.table-sparepart .form-control {
  padding: 4px 8px;
  font-size: 13px;
  border-radius: 6px;
  min-width: 60px;
}
.table-sparepart .btn-icon {
  font-size: 14px;
  padding: 2px 4px;
}

/* ========================================================== */
/* ERROR BOX */
/* ========================================================== */
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

/* ========================================================== */
/* RESPONSIVE */
/* ========================================================== */
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
  .filter-bar {
    flex-direction: column;
  }
  .modal-card {
    padding: 16px;
    max-width: 100%;
  }
  .table-sparepart {
    font-size: 12px;
  }
  .table-sparepart th,
  .table-sparepart td {
    padding: 4px 4px;
  }
}
</style>