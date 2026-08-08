<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-tools"></i> Pengajuan Biaya Perawatan Kendaraan</h2>
      <p class="module-subtitle">Kelola pengajuan biaya perawatan dan perbaikan kendaraan (menunggu persetujuan)</p>
    </div>

    <!-- ===== TOOLBAR YANG RAPI ===== -->
    <div class="toolbar">
      <button v-if="canCreate" @click="openForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Buat Pengajuan
      </button>
      <button @click="fetchItems" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat Data
      </button>
      <button v-if="canExport" @click="exportExcel" class="btn-export">
        <i class="fas fa-file-excel"></i> Export Excel
      </button>
    </div>

    <!-- Form -->
    <div v-if="showForm && canCreate" class="form-container">
      <h3><i class="fas fa-edit"></i> {{ formMode === 'add' ? 'Buat Pengajuan Baru' : 'Edit Pengajuan' }}</h3>
      <form @submit.prevent="saveItem">
        <div class="form-group">
          <label><i class="fas fa-car"></i> Kendaraan <span class="required">*</span></label>
          <select v-model="form.vehicle_id" required>
            <option value="">Pilih Kendaraan</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }} - {{ v.brand }} {{ v.model }}</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-user"></i> Driver</label>
          <select v-model="form.driver_id">
            <option value="">Pilih Driver</option>
            <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-pencil-alt"></i> Deskripsi <span class="required">*</span></label>
          <textarea v-model="form.description" rows="2" required placeholder="Jelaskan masalah/perawatan"></textarea>
        </div>
        <div class="form-group">
          <label><i class="fas fa-tools"></i> Jenis Service <span class="required">*</span></label>
          <select v-model="form.service_type" required>
            <option value="oil_change">Ganti Oli</option>
            <option value="tire_replacement">Ganti Ban</option>
            <option value="sparepart">Sparepart</option>
            <option value="general">Service Umum</option>
            <option value="other">Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-calendar-alt"></i> Tanggal <span class="required">*</span></label>
          <input v-model="form.request_date" type="date" required />
        </div>
        <div class="form-group">
          <label><i class="fas fa-money-bill-wave"></i> Estimasi Biaya</label>
          <input v-model="form.estimated_cost" type="number" placeholder="0" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-exclamation-triangle"></i> Urgensi <span class="required">*</span></label>
          <select v-model="form.urgency" required>
            <option value="low">Rendah</option>
            <option value="medium">Sedang</option>
            <option value="high">Tinggi</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
          <button type="button" @click="closeForm" class="btn-cancel"><i class="fas fa-times"></i> Batal</button>
        </div>
      </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper" v-if="pendingItems.length">
      <table class="modern-table">
        <thead>
          <tr>
            <th>#</th>
            <th><i class="fas fa-car"></i> Kendaraan</th>
            <th><i class="fas fa-user"></i> Driver</th>
            <th>Deskripsi</th>
            <th><i class="fas fa-tools"></i> Jenis Service</th>
            <th><i class="fas fa-calendar-alt"></i> Tanggal</th>
            <th><i class="fas fa-money-bill-wave"></i> Estimasi</th>
            <th><i class="fas fa-exclamation-triangle"></i> Urgensi</th>
            <th><i class="fas fa-circle"></i> Status</th>
            <th class="text-center"><i class="fas fa-cogs"></i> Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in pendingItems" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td>{{ item.vehicle?.plate_number }}</td>
            <td>{{ item.driver?.name || '-' }}</td>
            <td>{{ item.description }}</td>
            <td><span class="type-badge">{{ serviceTypeMap[item.service_type] || item.service_type || '-' }}</span></td>
            <td>{{ item.request_date }}</td>
            <td>{{ formatRupiah(item.estimated_cost) }}</td>
            <td><span :class="'urgency-badge-' + item.urgency">{{ urgencyMap[item.urgency] || item.urgency }}</span></td>
            <td><span :class="'status-badge-' + item.status">{{ statusMap[item.status] || item.status }}</span></td>
            <td class="action-cell">
              <template v-if="canApprove && item.status === 'pending'">
                <button @click="approveItem(item.id)" class="btn-approve" title="Setujui"><i class="fas fa-check-circle"></i></button>
                <button @click="rejectItem(item.id)" class="btn-reject" title="Tolak"><i class="fas fa-times-circle"></i></button>
              </template>
              <template v-if="canCreate && item.status === 'pending'">
                <button @click="editItem(item)" class="btn-edit" title="Edit"><i class="fas fa-edit"></i></button>
                <button @click="deleteItem(item.id)" class="btn-delete" title="Hapus"><i class="fas fa-trash-alt"></i></button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message"><i class="fas fa-inbox"></i> Tidak ada pengajuan yang menunggu persetujuan.</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from '../axios'
import { formatRupiah, statusMap, urgencyMap, serviceTypeMap } from '../utils/helpers'

const user = JSON.parse(localStorage.getItem('user') || '{}')
const userRole = user.role || ''
const canCreate = computed(() => userRole !== 'admin_finance')
const canApprove = computed(() => ['admin_finance', 'super_admin'].includes(userRole))
const canExport = computed(() => true)

const items = ref([])
const vehicles = ref([])
const drivers = ref([])
const showForm = ref(false)
const formMode = ref('add')
const editingId = ref(null)

const form = reactive({
  vehicle_id: '',
  driver_id: '',
  description: '',
  service_type: 'oil_change',
  request_date: new Date().toISOString().split('T')[0],
  estimated_cost: '',
  urgency: 'medium',
})

const pendingItems = computed(() => items.value.filter(item => item.status === 'pending'))

const fetchItems = async () => {
  try {
    const res = await axios.get('/maintenance-requests')
    items.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat data: ' + error.message)
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

const fetchDrivers = async () => {
  try {
    const res = await axios.get('/drivers')
    drivers.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat driver: ' + error.message)
  }
}

const openForm = (mode = 'add', data = null) => {
  if (!canCreate.value) return
  formMode.value = mode
  showForm.value = true
  if (mode === 'add') {
    form.vehicle_id = ''
    form.driver_id = ''
    form.description = ''
    form.service_type = 'oil_change'
    form.request_date = new Date().toISOString().split('T')[0]
    form.estimated_cost = ''
    form.urgency = 'medium'
    editingId.value = null
  } else if (data) {
    Object.assign(form, data)
    editingId.value = data.id
  }
}

const closeForm = () => {
  showForm.value = false
  formMode.value = 'add'
  editingId.value = null
}

const saveItem = async () => {
  try {
    if (formMode.value === 'add') {
      await axios.post('/maintenance-requests', form)
      alert('Pengajuan berhasil dibuat!')
    } else {
      await axios.put(`/maintenance-requests/${editingId.value}`, form)
      alert('Pengajuan berhasil diupdate!')
    }
    closeForm()
    await fetchItems()
  } catch (error) {
    alert('Gagal menyimpan: ' + (error.response?.data?.message || error.message))
  }
}

const approveItem = async (id) => {
  if (!canApprove.value) return
  if (!confirm('Setujui pengajuan ini?')) return
  try {
    await axios.post(`/maintenance-requests/${id}/approve`)
    alert('Pengajuan disetujui!')
    await fetchItems()
  } catch (error) {
    alert('Gagal menyetujui: ' + error.message)
  }
}

const rejectItem = async (id) => {
  if (!canApprove.value) return
  if (!confirm('Tolak pengajuan ini?')) return
  try {
    await axios.post(`/maintenance-requests/${id}/reject`)
    alert('Pengajuan ditolak!')
    await fetchItems()
  } catch (error) {
    alert('Gagal menolak: ' + error.message)
  }
}

const editItem = (item) => openForm('edit', item)
const deleteItem = async (id) => {
  if (!canCreate.value) return
  if (!confirm('Yakin hapus pengajuan ini?')) return
  try {
    await axios.delete(`/maintenance-requests/${id}`)
    alert('Pengajuan dihapus!')
    await fetchItems()
  } catch (error) {
    alert('Gagal hapus: ' + error.message)
  }
}

const exportExcel = async () => {
  try {
    const response = await axios.get('/export/maintenance-requests', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'pengajuan_perawatan.xlsx')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    alert('Gagal export: ' + error.message)
  }
}

onMounted(() => {
  fetchItems()
  fetchVehicles()
  fetchDrivers()
})
</script>

<style scoped>
/* ====== GAYA SAMA DENGAN VEHICLELIST ====== */
.module-container { max-width: 1200px; margin: 0 auto; }
.module-header { margin-bottom: 20px; }
.module-header h2 { font-size: 24px; color: #0d2b45; display: flex; align-items: center; gap: 10px; }
.module-header h2 i { color: #1a4a7a; }
.module-subtitle { color: #6c757d; font-size: 14px; margin-top: 2px; }

.toolbar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.btn-add, .btn-refresh, .btn-export {
  padding: 10px 22px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;
  display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.btn-add { background: linear-gradient(135deg, #28a745, #218838); color: white; }
.btn-add:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.3); }
.btn-refresh { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
.btn-refresh:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(23,162,184,0.3); }
.btn-export { background: linear-gradient(135deg, #007bff, #0069d9); color: white; }
.btn-export:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,123,255,0.3); }

/* FORM */
.form-container { background: white; border-radius: 16px; padding: 24px 28px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin: 16px 0 24px; }
.form-container h3 { font-size: 20px; color: #0d2b45; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #e9ecef; padding-bottom: 12px; margin-bottom: 20px; }
.form-container h3 i { color: #1a4a7a; }
.form-group { display: grid; grid-template-columns: 160px 1fr; align-items: center; gap: 14px; margin-bottom: 14px; }
.form-group label { font-weight: 600; color: #2d3748; text-align: right; display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
.form-group label i { color: #1a4a7a; width: 20px; text-align: center; }
.required { color: #dc3545; margin-left: 2px; }
.form-group input, .form-group select, .form-group textarea { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: border-color 0.2s; background: white; width: 100%; box-sizing: border-box; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #1a4a7a; box-shadow: 0 0 0 3px rgba(26,74,122,0.12); }
.form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e9ecef; }
.btn-save, .btn-cancel { padding: 10px 28px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
.btn-save { background: linear-gradient(135deg, #28a745, #218838); color: white; }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.3); }
.btn-cancel { background: #6c757d; color: white; }
.btn-cancel:hover { background: #5a6268; transform: translateY(-2px); }

/* TABLE */
.table-wrapper { overflow-x: auto; background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 16px; }
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

/* BADGE */
.urgency-badge-low { background: #17a2b8; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.urgency-badge-medium { background: #ffc107; color: black; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.urgency-badge-high { background: #dc3545; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-pending { background: #ffc107; color: black; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.type-badge { background: #6c757d; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

.btn-edit, .btn-delete, .btn-approve, .btn-reject {
  border: none; border-radius: 8px; padding: 6px 10px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px;
}
.btn-edit { background: #ffc107; color: #212529; }
.btn-edit:hover { background: #e0a800; transform: scale(1.08); }
.btn-delete { background: #dc3545; color: white; }
.btn-delete:hover { background: #c82333; transform: scale(1.08); }
.btn-approve { background: #28a745; color: white; }
.btn-approve:hover { background: #218838; transform: scale(1.08); }
.btn-reject { background: #dc3545; color: white; }
.btn-reject:hover { background: #c82333; transform: scale(1.08); }

.empty-message { text-align: center; padding: 40px 20px; color: #6c757d; font-size: 16px; background: #f8f9fa; border-radius: 16px; }
.empty-message i { font-size: 40px; display: block; margin-bottom: 12px; color: #dee2e6; }

@media (max-width: 768px) {
  .form-group { grid-template-columns: 1fr; gap: 4px; }
  .form-group label { text-align: left; justify-content: flex-start; }
  .modern-table { font-size: 13px; min-width: 500px; }
  .modern-table thead th, .modern-table tbody td { padding: 10px 12px; }
  .action-cell { gap: 4px; }
}
</style>