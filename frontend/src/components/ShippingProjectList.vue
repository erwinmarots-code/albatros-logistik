<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-folder-open"></i> Project Pengantaran</h2>
      <p class="module-subtitle">Kelola project pengantaran dan generate nomor resi</p>
    </div>

    <!-- ===== TOOLBAR YANG RAPI ===== -->
    <div class="toolbar">
      <button v-if="canCreate" @click="openForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Buat Project
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
      <h3><i class="fas fa-edit"></i> {{ formMode === 'add' ? 'Buat Project Baru' : 'Edit Project' }}</h3>
      <form @submit.prevent="saveItem">
        <div class="form-group">
          <label><i class="fas fa-building"></i> Client <span class="required">*</span></label>
          <select v-model="form.client_id" @change="fillClientData" required>
            <option value="">Pilih Client</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-tag"></i> No. PO</label>
          <input v-model="form.no_po" placeholder="Nomor PO (referensi)" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-ticket-alt"></i> No. Resi</label>
          <input v-model="form.resi_number" placeholder="Kosongkan untuk auto generate" />
          <small style="grid-column: 2; font-size: 12px; color: #6c757d;">Biarkan kosong untuk generate otomatis</small>
        </div>
        <div class="form-group">
          <label><i class="fas fa-user"></i> Nama Pengirim <span class="required">*</span></label>
          <input v-model="form.sender_name" required placeholder="Nama pengirim" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Alamat Pengirim <span class="required">*</span></label>
          <input v-model="form.sender_address" required placeholder="Alamat pengirim" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-phone"></i> Telp Pengirim <span class="required">*</span></label>
          <input v-model="form.sender_phone" required placeholder="Telepon pengirim" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-user"></i> Nama Penerima <span class="required">*</span></label>
          <input v-model="form.receiver_name" required placeholder="Nama penerima" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Alamat Penerima <span class="required">*</span></label>
          <input v-model="form.receiver_address" required placeholder="Alamat penerima" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-phone"></i> Telp Penerima <span class="required">*</span></label>
          <input v-model="form.receiver_phone" required placeholder="Telepon penerima" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-pencil-alt"></i> Keterangan Barang</label>
          <input v-model="form.goods_description" placeholder="Catatan tambahan" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-weight-hanging"></i> Berat (Kg)</label>
          <input v-model="form.weight_kg" type="number" step="0.01" placeholder="0" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-boxes"></i> Koli</label>
          <input v-model="form.collie" type="number" placeholder="0" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-cube"></i> Volumetrik</label>
          <input v-model="form.volumetric" placeholder="Contoh: P60 X L90 X L32" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-money-bill-wave"></i> Nilai Barang (Rp)</label>
          <input v-model="form.goods_value" type="number" step="0.01" placeholder="0" />
        </div>
        <div class="form-group">
          <label><i class="fas fa-truck"></i> Kiriman Via <span class="required">*</span></label>
          <select v-model="form.shipping_method" required>
            <option value="darat">Darat</option>
            <option value="udara">Udara</option>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-circle"></i> Status</label>
          <select v-model="form.status">
            <option value="draft">Draft</option>
            <option value="confirmed">Dikonfirmasi</option>
            <option value="completed">Selesai</option>
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
            <th><i class="fas fa-tag"></i> No. PO</th>
            <th><i class="fas fa-ticket-alt"></i> No. Resi</th>
            <th><i class="fas fa-building"></i> Client</th>
            <th><i class="fas fa-user"></i> Pengirim</th>
            <th><i class="fas fa-user"></i> Penerima</th>
            <th><i class="fas fa-truck"></i> Via</th>
            <th><i class="fas fa-box"></i> Berat/Koli</th>
            <th><i class="fas fa-cube"></i> Volumetrik</th>
            <th><i class="fas fa-circle"></i> Status</th>
            <th class="text-center"><i class="fas fa-cogs"></i> Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in items" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td>{{ item.no_po || '-' }}</td>
            <td><strong>{{ item.resi_number }}</strong></td>
            <td>{{ item.client?.name }}</td>
            <td>{{ item.sender_name }}</td>
            <td>{{ item.receiver_name }}</td>
            <td>{{ shippingMethodMap[item.shipping_method] || item.shipping_method }}</td>
            <td>{{ item.weight_kg || 0 }} kg / {{ item.collie || 0 }} koli</td>
            <td>{{ item.volumetric || '-' }}</td>
            <td><span :class="'status-badge-' + item.status">{{ statusMap[item.status] || item.status }}</span></td>
            <td class="action-cell">
              <button v-if="canCreate" @click="editItem(item)" class="btn-edit"><i class="fas fa-edit"></i></button>
              <button v-if="canCreate" @click="deleteItem(item.id)" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message"><i class="fas fa-inbox"></i> Belum ada project.</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from '../axios'
import { statusMap, shippingMethodMap } from '../utils/helpers'

const user = JSON.parse(localStorage.getItem('user') || '{}')
const userRole = user.role || ''
const canCreate = computed(() => userRole !== 'admin_finance')
const canExport = computed(() => true)

const items = ref([])
const clients = ref([])
const showForm = ref(false)
const formMode = ref('add')
const editingId = ref(null)

const form = reactive({
  client_id: '',
  no_po: '',
  resi_number: '',
  sender_name: '',
  sender_address: '',
  sender_phone: '',
  receiver_name: '',
  receiver_address: '',
  receiver_phone: '',
  goods_description: '',
  weight_kg: '',
  collie: '',
  volumetric: '',
  goods_value: '',
  shipping_method: 'darat',
  status: 'draft',
})

const fetchItems = async () => {
  try {
    const res = await axios.get('/shipping-projects')
    items.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat data: ' + error.message)
  }
}

const fetchClients = async () => {
  try {
    const res = await axios.get('/clients')
    clients.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat client: ' + error.message)
  }
}

const fillClientData = () => {
  const selected = clients.value.find(c => c.id == form.client_id)
  if (selected) {
    form.sender_name = selected.name || ''
    form.sender_address = selected.address || ''
    form.sender_phone = selected.phone || ''
  } else {
    form.sender_name = ''
    form.sender_address = ''
    form.sender_phone = ''
  }
}

const openForm = (mode = 'add', data = null) => {
  if (!canCreate.value) return
  formMode.value = mode
  showForm.value = true
  if (mode === 'add') {
    form.client_id = ''
    form.no_po = ''
    form.resi_number = ''
    form.sender_name = ''
    form.sender_address = ''
    form.sender_phone = ''
    form.receiver_name = ''
    form.receiver_address = ''
    form.receiver_phone = ''
    form.goods_description = ''
    form.weight_kg = ''
    form.collie = ''
    form.volumetric = ''
    form.goods_value = ''
    form.shipping_method = 'darat'
    form.status = 'draft'
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
      await axios.post('/shipping-projects', form)
      alert('Project berhasil dibuat!')
    } else {
      await axios.put(`/shipping-projects/${editingId.value}`, form)
      alert('Project berhasil diupdate!')
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
  if (!confirm('Yakin hapus project ini?')) return
  try {
    await axios.delete(`/shipping-projects/${id}`)
    alert('Project dihapus!')
    await fetchItems()
  } catch (error) {
    alert('Gagal hapus: ' + error.message)
  }
}

const exportExcel = async () => {
  try {
    const response = await axios.get('/export/shipping-projects', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'shipping_projects.xlsx')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    alert('Gagal export: ' + error.message)
  }
}

onMounted(() => {
  fetchItems()
  fetchClients()
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
.form-group input, .form-group select { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: border-color 0.2s; background: white; width: 100%; box-sizing: border-box; }
.form-group input:focus, .form-group select:focus { outline: none; border-color: #1a4a7a; box-shadow: 0 0 0 3px rgba(26,74,122,0.12); }
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

/* BADGE STATUS PROJECT */
.status-badge-draft { background: #6c757d; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-confirmed { background: #17a2b8; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-completed { background: #28a745; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-cancelled { background: #dc3545; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

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