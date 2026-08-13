<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-car"></i> Manajemen Kendaraan</h2>
      <p class="module-subtitle">Kelola data kendaraan operasional</p>
    </div>

    <div class="toolbar">
      <button v-if="canCreate" @click="openForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Tambah Kendaraan
      </button>
      <button @click="fetchItems" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat Data
      </button>
      <button @click="exportExcel" class="btn-export">
        <i class="fas fa-file-excel"></i> Export Excel
      </button>
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input v-model="searchQuery" type="text" class="search-input" placeholder="Cari kendaraan..." />
      </div>
    </div>

    <!-- Form -->
    <div v-if="showForm && canCreate" class="form-container">
      <h3><i class="fas fa-edit"></i> {{ formMode === 'add' ? 'Tambah Kendaraan' : 'Edit Kendaraan' }}</h3>
      <form @submit.prevent="saveItem">
        <div class="form-group">
          <label><i class="fas fa-id-card"></i> No. Polisi <span class="required">*</span></label>
          <input v-model="form.plate_number" required placeholder="DD 1234 AB" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-tag"></i> Merek <span class="required">*</span></label>
          <input v-model="form.brand" required placeholder="Toyota" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-car-side"></i> Model <span class="required">*</span></label>
          <input v-model="form.model" required placeholder="Hiace" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-calendar"></i> Tahun <span class="required">*</span></label>
          <input v-model="form.year" type="number" required placeholder="2020" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-palette"></i> Warna</label>
          <input v-model="form.color" placeholder="Putih" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-tachometer-alt"></i> Kapasitas Mesin</label>
          <input v-model="form.engine_capacity" placeholder="2800 cc" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-gas-pump"></i> BBM <span class="required">*</span></label>
          <select v-model="form.fuel_type" required>
            <option value="bensin">Bensin</option>
            <option value="diesel">Diesel</option>
            <option value="electric">Listrik</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-circle"></i> Status <span class="required">*</span></label>
          <select v-model="form.status" required>
            <option value="available">Tersedia</option>
            <option value="maintenance">Perawatan</option>
            <option value="in_use">Digunakan</option>
            <option value="retired">Pensiun</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-money-bill-wave"></i> Harga Beli</label>
          <input v-model="form.price" type="number" placeholder="0" />
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
            <th><i class="fas fa-id-card"></i> No. Polisi</th>
            <th><i class="fas fa-tag"></i> Merek</th>
            <th><i class="fas fa-car-side"></i> Model</th>
            <th><i class="fas fa-calendar"></i> Tahun</th>
            <th><i class="fas fa-palette"></i> Warna</th>
            <th><i class="fas fa-gas-pump"></i> BBM</th>
            <th><i class="fas fa-circle"></i> Status</th>
            <th><i class="fas fa-money-bill-wave"></i> Harga</th>
            <th class="text-center"><i class="fas fa-cogs"></i> Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in filteredItems" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td><strong>{{ item.plate_number }}</strong></td>
            <td>{{ item.brand }}</td>
            <td>{{ item.model }}</td>
            <td>{{ item.year }}</td>
            <td>{{ item.color || '-' }}</td>
            <td>{{ item.fuel_type }}</td>
            <td><span :class="'status-badge-' + item.status">{{ statusMap[item.status] || item.status }}</span></td>
            <td>{{ formatRupiah(item.price) }}</td>
            <td class="action-cell">
              <button v-if="canCreate" @click="editItem(item)" class="btn-edit"><i class="fas fa-edit"></i></button>
              <button v-if="canCreate" @click="deleteItem(item.id)" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message"><i class="fas fa-inbox"></i> {{ searchQuery ? 'Tidak ada kendaraan yang cocok dengan pencarian.' : 'Belum ada data kendaraan.' }}</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from '../axios'
import { formatRupiah, statusMap } from '../utils/helpers'

const user = JSON.parse(localStorage.getItem('user') || '{}')
const userRole = user.role || ''
const canCreate = computed(() => userRole !== 'admin_finance' && userRole !== 'admin_transport')

const items = ref([])
const searchQuery = ref('')
const showForm = ref(false)
const formMode = ref('add')
const editingId = ref(null)

const form = reactive({
  plate_number: '',
  brand: '',
  model: '',
  year: '',
  color: '',
  engine_capacity: '',
  fuel_type: 'bensin',
  status: 'available',
  price: '',
})

const filteredItems = computed(() => {
  if (!searchQuery.value) return items.value
  const q = searchQuery.value.toLowerCase()
  return items.value.filter(item =>
    item.plate_number?.toLowerCase().includes(q) ||
    item.brand?.toLowerCase().includes(q) ||
    item.model?.toLowerCase().includes(q) ||
    item.color?.toLowerCase().includes(q) ||
    item.fuel_type?.toLowerCase().includes(q) ||
    item.status?.toLowerCase().includes(q)
  )
})

const fetchItems = async () => {
  try {
    const res = await axios.get('/vehicles')
    items.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat data: ' + error.message)
  }
}

const openForm = (mode = 'add', data = null) => {
  if (!canCreate.value) return
  formMode.value = mode
  showForm.value = true
  if (mode === 'add') {
    Object.assign(form, { plate_number: '', brand: '', model: '', year: '', color: '', engine_capacity: '', fuel_type: 'bensin', status: 'available', price: '' })
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
      await axios.post('/vehicles', form)
      alert('Kendaraan berhasil ditambahkan!')
    } else {
      await axios.put(`/vehicles/${editingId.value}`, form)
      alert('Kendaraan berhasil diupdate!')
    }
    closeForm()
    await fetchItems()
  } catch (error) {
    alert('Gagal menyimpan: ' + (error.response?.data?.message || error.message))
  }
}

const editItem = (item) => openForm('edit', item)
const deleteItem = async (id) => {
  if (!canCreate.value) return
  if (!confirm('Yakin hapus kendaraan ini?')) return
  try {
    await axios.delete(`/vehicles/${id}`)
    alert('Kendaraan dihapus!')
    await fetchItems()
  } catch (error) {
    alert('Gagal hapus: ' + error.message)
  }
}

const exportExcel = async () => {
  try {
    const response = await axios.get('/export/vehicles', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'kendaraan.xlsx')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    alert('Gagal export: ' + error.message)
  }
}

onMounted(fetchItems)
</script>

<style scoped>
.module-container { max-width: 1200px; margin: 0 auto; }
.module-header { margin-bottom: 20px; }
.module-header h2 { font-size: 24px; color: #0d2b45; display: flex; align-items: center; gap: 10px; }
.module-header h2 i { color: #1a4a7a; }
.module-subtitle { color: #6c757d; font-size: 14px; margin-top: 2px; }

.toolbar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; align-items: center; }
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
.search-icon { color: #94a3b8; margin-right: 8px; }
.search-input { border: none; padding: 10px 0; font-size: 14px; width: 100%; outline: none; background: transparent; }

.form-container { background: white; border-radius: 16px; padding: 24px 28px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin: 16px 0 24px; }
.form-container h3 { font-size: 20px; color: #0d2b45; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #e9ecef; padding-bottom: 12px; margin-bottom: 20px; }
.form-container h3 i { color: #1a4a7a; }
.form-group { display: grid; grid-template-columns: 160px 1fr; align-items: center; gap: 14px; margin-bottom: 14px; }
.form-group label { font-weight: 600; color: #2d3748; text-align: right; display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
.form-group label i { color: #1a4a7a; width: 20px; text-align: center; }
.required { color: #dc3545; margin-left: 2px; }
.form-group input, .form-group select { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: border-color 0.2s; background: white; width: 100%; box-sizing: border-box; }
.form-group input:focus, .form-group select:focus { outline: none; border-color: #1a4a7a; box-shadow: 0 0 0 3px rgba(26,74,122,0.12); }
.form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e9ecef; }
.btn-save, .btn-cancel { padding: 10px 28px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
.btn-save { background: linear-gradient(135deg, #28a745, #218838); color: white; }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.3); }
.btn-cancel { background: #6c757d; color: white; }
.btn-cancel:hover { background: #5a6268; transform: translateY(-2px); }

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

.status-badge-available { background: #28a745; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-maintenance { background: #ffc107; color: black; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-in_use { background: #17a2b8; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-retired { background: #dc3545; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

.btn-edit, .btn-delete { border: none; border-radius: 8px; padding: 6px 10px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; }
.btn-edit { background: #ffc107; color: #212529; }
.btn-edit:hover { background: #e0a800; transform: scale(1.08); }
.btn-delete { background: #dc3545; color: white; }
.btn-delete:hover { background: #c82333; transform: scale(1.08); }

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