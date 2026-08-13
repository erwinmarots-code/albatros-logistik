<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-coins"></i> Pengajuan Biaya Operasional</h2>
      <p class="module-subtitle">Kelola pengajuan biaya perjalanan (menunggu persetujuan)</p>
    </div>

    <div class="toolbar">
      <button v-if="canCreate" @click="openForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Ajukan Biaya
      </button>
      <button @click="fetchItems" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat Data
      </button>
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input v-model="searchQuery" type="text" class="search-input" placeholder="Cari pengajuan..." />
      </div>
    </div>

    <!-- Form -->
    <div v-if="showForm && canCreate" class="form-container">
      <h3><i class="fas fa-edit"></i> {{ formMode === 'add' ? 'Ajukan Biaya Baru' : 'Edit Pengajuan' }}</h3>
      <form @submit.prevent="saveItem">
        <div class="form-group">
          <label><i class="fas fa-tasks"></i> Tugas Pengantaran <span class="required">*</span></label>
          <select v-model="form.delivery_task_id" required>
            <option value="">Pilih Tugas</option>
            <option v-for="t in tasks" :key="t.id" :value="t.id">
              {{ t.client?.name }} - {{ t.vehicle?.plate_number }} ({{ t.task_date }})
            </option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-tag"></i> Jenis Biaya <span class="required">*</span></label>
          <select v-model="form.type" required>
            <option value="fuel">BBM</option>
            <option value="toll">Tol</option>
            <option value="parking">Parkir</option>
            <option value="meal">Makan</option>
            <option value="other">Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-money-bill-wave"></i> Nominal (Rp) <span class="required">*</span></label>
          <input v-model="form.amount" type="number" required placeholder="0" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-pencil-alt"></i> Deskripsi</label>
          <input v-model="form.description" placeholder="Keterangan" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-calendar-alt"></i> Tanggal Pengajuan <span class="required">*</span></label>
          <input v-model="form.request_date" type="date" required />
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
          <button type="button" @click="closeForm" class="btn-cancel"><i class="fas fa-times"></i> Batal</button>
        </div>
      </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper" v-if="filteredItems.length">
      <table class="modern-table">
        <thead>
          <tr>
            <th>#</th>
            <th><i class="fas fa-key"></i> Kode Unik</th>
            <th><i class="fas fa-tasks"></i> Tugas</th>
            <th><i class="fas fa-tag"></i> Jenis</th>
            <th><i class="fas fa-money-bill-wave"></i> Nominal</th>
            <th><i class="fas fa-calendar-alt"></i> Tanggal</th>
            <th><i class="fas fa-circle"></i> Status</th>
            <th class="text-center"><i class="fas fa-cogs"></i> Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in filteredItems" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td><strong>{{ item.unique_code }}</strong></td>
            <td>{{ item.delivery_task?.client?.name || '-' }}</td>
            <td>
              <span :class="'type-badge-' + item.type">{{ item.type }}</span>
            </td>
            <td>{{ formatRupiah(item.amount) }}</td>
            <td>{{ formatDate(item.request_date) }}</td>
            <td>
              <span :class="'status-badge-' + item.status">{{ statusMap[item.status] || item.status }}</span>
            </td>
            <td class="action-cell">
              <template v-if="canApprove && item.status === 'pending'">
                <button @click="approveItem(item.id)" class="btn-approve" title="Setujui">
                  <i class="fas fa-check-circle"></i>
                </button>
                <button @click="rejectItem(item.id)" class="btn-reject" title="Tolak">
                  <i class="fas fa-times-circle"></i>
                </button>
              </template>
              <button v-if="canCreate" @click="editItem(item)" class="btn-edit" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button v-if="canCreate" @click="deleteItem(item.id)" class="btn-delete" title="Hapus">
                <i class="fas fa-trash-alt"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message">
      <i class="fas fa-inbox"></i>
      {{ searchQuery ? 'Tidak ada pengajuan yang cocok dengan pencarian.' : 'Tidak ada pengajuan biaya yang menunggu persetujuan.' }}
    </p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from '../axios'
import { formatRupiah, statusMap } from '../utils/helpers'

// ===== HELPER FORMAT TANGGAL =====
const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

// ===== USER ROLE =====
const user = JSON.parse(localStorage.getItem('user') || '{}')
const userRole = user.role || ''
const canCreate = computed(() => userRole !== 'admin_finance')
const canApprove = computed(() => ['admin_finance', 'super_admin'].includes(userRole))

// ===== STATE =====
const items = ref([])
const tasks = ref([])
const searchQuery = ref('')
const showForm = ref(false)
const formMode = ref('add')
const editingId = ref(null)

const form = reactive({
  delivery_task_id: '',
  type: 'fuel',
  amount: '',
  description: '',
  request_date: '',
})

// ===== COMPUTED FILTER =====
const filteredItems = computed(() => {
  let data = items.value.filter(item => item.status === 'pending')
  if (!searchQuery.value) return data
  const q = searchQuery.value.toLowerCase()
  return data.filter(item =>
    item.unique_code?.toLowerCase().includes(q) ||
    item.type?.toLowerCase().includes(q) ||
    item.amount?.toString().includes(q) ||
    item.status?.toLowerCase().includes(q) ||
    item.delivery_task?.client?.name?.toLowerCase().includes(q)
  )
})

// ===== FETCH DATA =====
const fetchItems = async () => {
  try {
    const res = await axios.get('/fuel-expenses')
    items.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat data: ' + error.message)
  }
}

const fetchTasks = async () => {
  try {
    const res = await axios.get('/delivery-tasks')
    tasks.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat tugas: ' + error.message)
  }
}

// ===== FORM =====
const openForm = (mode = 'add', data = null) => {
  if (!canCreate.value) return
  formMode.value = mode
  showForm.value = true
  if (mode === 'add') {
    form.delivery_task_id = ''
    form.type = 'fuel'
    form.amount = ''
    form.description = ''
    form.request_date = ''
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

// ===== CRUD =====
const saveItem = async () => {
  try {
    if (formMode.value === 'add') {
      await axios.post('/fuel-expenses', form)
      alert('Pengajuan biaya berhasil dibuat!')
    } else {
      await axios.put(`/fuel-expenses/${editingId.value}`, form)
      alert('Pengajuan biaya berhasil diupdate!')
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
    await axios.post(`/fuel-expenses/${id}/approve`)
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
    await axios.post(`/fuel-expenses/${id}/reject`)
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
    await axios.delete(`/fuel-expenses/${id}`)
    alert('Pengajuan dihapus!')
    await fetchItems()
  } catch (error) {
    alert('Gagal hapus: ' + error.message)
  }
}

// ===== MOUNTED =====
onMounted(() => {
  fetchItems()
  fetchTasks()
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

.type-badge-fuel {
  background: #ffc107;
  color: black;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.type-badge-toll {
  background: #17a2b8;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.type-badge-parking {
  background: #6f42c1;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.type-badge-meal {
  background: #fd7e14;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.type-badge-other {
  background: #6c757d;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge-pending {
  background: #ffc107;
  color: black;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.status-badge-approved {
  background: #28a745;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.status-badge-rejected {
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
.btn-reject {
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