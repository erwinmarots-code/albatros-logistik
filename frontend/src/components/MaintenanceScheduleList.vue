<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-calendar-check"></i> Jadwal Service</h2>
      <p class="module-subtitle">Kelola jadwal perawatan rutin kendaraan</p>
    </div>

    <div class="toolbar">
      <button v-if="canCreate" @click="openForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Tambah Jadwal
      </button>
      <button @click="fetchItems" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat Data
      </button>
    </div>

    <!-- Form -->
    <div v-if="showForm && canCreate" class="form-container">
      <h3><i class="fas fa-edit"></i> {{ formMode === 'add' ? 'Tambah Jadwal Service' : 'Edit Jadwal Service' }}</h3>
      <form @submit.prevent="saveItem">
        <div class="form-group">
          <label><i class="fas fa-car"></i> Kendaraan <span class="required">*</span></label>
          <select v-model="form.vehicle_id" required>
            <option value="">Pilih Kendaraan</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }} - {{ v.brand }} {{ v.model }}</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-tools"></i> Tipe Service <span class="required">*</span></label>
          <select v-model="form.type" required>
            <option value="oil_change">Ganti Oli</option>
            <option value="tire_replacement">Ganti Ban</option>
            <option value="sparepart">Sparepart</option>
            <option value="general">Service Umum</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-pencil-alt"></i> Deskripsi</label>
          <input v-model="form.description" placeholder="Deskripsi service" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-calendar-alt"></i> Terakhir</label>
          <input v-model="form.last_date" type="date" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-calendar-plus"></i> Berikutnya <span class="required">*</span></label>
          <input v-model="form.next_date" type="date" required />
        </div>
        <div class="form-group">
          <label><i class="fas fa-road"></i> Interval (km)</label>
          <input v-model="form.mileage_interval" type="number" placeholder="5000" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-money-bill-wave"></i> Estimasi Biaya</label>
          <input v-model="form.estimated_cost" type="number" placeholder="0" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-circle"></i> Status</label>
          <select v-model="form.status">
            <option value="scheduled">Terjadwal</option>
            <option value="done">Selesai</option>
            <option value="cancelled">Batal</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
          <button type="button" @click="closeForm" class="btn-cancel"><i class="fas fa-times"></i> Batal</button>
        </div>
      </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper" v-if="items && items.length">
      <table class="modern-table">
        <thead>
          <tr>
            <th>#</th>
            <th><i class="fas fa-car"></i> Kendaraan</th>
            <th><i class="fas fa-tools"></i> Tipe</th>
            <th>Deskripsi</th>
            <th><i class="fas fa-calendar-alt"></i> Terakhir</th>
            <th><i class="fas fa-calendar-plus"></i> Berikutnya</th>
            <th><i class="fas fa-road"></i> Interval</th>
            <th><i class="fas fa-money-bill-wave"></i> Estimasi</th>
            <th><i class="fas fa-circle"></i> Status</th>
            <th class="text-center"><i class="fas fa-cogs"></i> Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in items" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td>{{ item.vehicle?.plate_number }} - {{ item.vehicle?.brand }}</td>
            <td><span class="type-badge">{{ serviceTypeMap[item.type] || item.type }}</span></td>
            <td>{{ item.description || '-' }}</td>
            <td>{{ item.last_date || '-' }}</td>
            <td>{{ item.next_date || '-' }}</td>
            <td>{{ item.mileage_interval || '-' }} km</td>
            <td>{{ formatRupiah(item.estimated_cost) }}</td>
            <td><span :class="'status-badge-' + item.status">{{ statusMap[item.status] || item.status }}</span></td>
            <td class="action-cell">
              <button v-if="canCreate" @click="editItem(item)" class="btn-edit" title="Edit"><i class="fas fa-edit"></i></button>
              <button v-if="canCreate" @click="deleteItem(item.id)" class="btn-delete" title="Hapus"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message"><i class="fas fa-inbox"></i> Belum ada jadwal service.</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from '../axios'
import { formatRupiah, statusMap, serviceTypeMap } from '../utils/helpers'

const user = JSON.parse(localStorage.getItem('user') || '{}')
const userRole = user.role || ''
const canCreate = computed(() => userRole !== 'admin_finance')

const items = ref([])
const vehicles = ref([])
const showForm = ref(false)
const formMode = ref('add')
const editingId = ref(null)

const form = reactive({
  vehicle_id: '',
  type: 'oil_change',
  description: '',
  last_date: '',
  next_date: '',
  mileage_interval: '',
  estimated_cost: '',
  status: 'scheduled',
})

const fetchItems = async () => {
  try {
    const res = await axios.get('/maintenance-schedules')
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

const openForm = (mode = 'add', data = null) => {
  if (!canCreate.value) return
  formMode.value = mode
  showForm.value = true
  if (mode === 'add') {
    form.vehicle_id = ''
    form.type = 'oil_change'
    form.description = ''
    form.last_date = ''
    form.next_date = ''
    form.mileage_interval = ''
    form.estimated_cost = ''
    form.status = 'scheduled'
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
      await axios.post('/maintenance-schedules', form)
      alert('Jadwal service berhasil ditambahkan!')
    } else {
      await axios.put(`/maintenance-schedules/${editingId.value}`, form)
      alert('Jadwal service berhasil diupdate!')
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
  if (!confirm('Yakin hapus jadwal ini?')) return
  try {
    await axios.delete(`/maintenance-schedules/${id}`)
    alert('Jadwal dihapus!')
    await fetchItems()
  } catch (error) {
    alert('Gagal hapus: ' + error.message)
  }
}

onMounted(() => {
  fetchItems()
  fetchVehicles()
})
</script>