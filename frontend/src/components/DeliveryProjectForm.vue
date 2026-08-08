<template>
  <div class="project-container">
    <h2>📦 Input Project Pengantaran</h2>

    <!-- Tombol Tambah -->
    <button @click="openForm()" class="btn-add">+ Buat Project Baru</button>

    <!-- Form -->
    <div v-if="showForm" class="form-container">
      <h3>{{ editingId ? 'Edit Project' : 'Buat Project Baru' }}</h3>
      <form @submit.prevent="saveProject">
        <!-- Client -->
        <div class="form-group">
          <label>Client <span class="required">*</span></label>
          <select v-model="form.client_id" @change="fetchClientData" required>
            <option value="">Pilih Client</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>

        <!-- No PO -->
        <div class="form-group">
          <label>No PO</label>
          <input v-model="form.po_number" placeholder="Nomor PO dari client" />
        </div>

        <!-- Invoice Number (auto generated, readonly) -->
        <div class="form-group">
          <label>No Invoice</label>
          <input :value="form.invoice_number" disabled readonly />
          <small class="hint">Otomatis digenerate</small>
        </div>

        <!-- Pengirim -->
        <div class="form-group">
          <label>Nama Pengirim <span class="required">*</span></label>
          <input v-model="form.sender_name" required placeholder="Nama pengirim" />
        </div>
        <div class="form-group">
          <label>Alamat Pengirim <span class="required">*</span></label>
          <textarea v-model="form.sender_address" rows="2" required placeholder="Alamat lengkap pengirim"></textarea>
        </div>
        <div class="form-group">
          <label>No Telp Pengirim <span class="required">*</span></label>
          <input v-model="form.sender_phone" required placeholder="No telepon pengirim" />
        </div>

        <!-- Penerima -->
        <div class="form-group">
          <label>Nama Penerima <span class="required">*</span></label>
          <input v-model="form.receiver_name" required placeholder="Nama penerima" />
        </div>
        <div class="form-group">
          <label>Alamat Penerima <span class="required">*</span></label>
          <textarea v-model="form.receiver_address" rows="2" required placeholder="Alamat lengkap penerima"></textarea>
        </div>
        <div class="form-group">
          <label>No Telp Penerima <span class="required">*</span></label>
          <input v-model="form.receiver_phone" required placeholder="No telepon penerima" />
        </div>

        <!-- Detail Barang -->
        <div class="form-group">
          <label>Keterangan Barang</label>
          <textarea v-model="form.goods_description" rows="2" placeholder="Jenis barang, dll"></textarea>
        </div>
        <div class="form-group">
          <label>Berat (kg)</label>
          <input v-model="form.weight_kg" type="number" step="0.01" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Koli</label>
          <input v-model="form.collie" type="number" step="1" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Volumetrik</label>
          <input v-model="form.volumetric" type="number" step="0.01" placeholder="0" />
        </div>
        <div class="form-group">
          <label>PPN</label>
          <select v-model="form.ppn">
            <option :value="true">Ya</option>
            <option :value="false">Tidak</option>
          </select>
        </div>
        <div class="form-group">
          <label>Diskon (Rp)</label>
          <input v-model="form.discount" type="number" step="0.01" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Asuransi (Rp)</label>
          <input v-model="form.insurance" type="number" step="0.01" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Nilai Barang (Rp)</label>
          <input v-model="form.goods_value" type="number" step="0.01" placeholder="0" />
        </div>

        <!-- Mode Pengiriman -->
        <div class="form-group">
          <label>Kiriman Via <span class="required">*</span></label>
          <select v-model="form.shipping_mode" required>
            <option value="darat">Darat</option>
            <option value="udara">Udara</option>
          </select>
        </div>

        <!-- Status -->
        <div class="form-group">
          <label>Status</label>
          <select v-model="form.status">
            <option value="draft">Draft</option>
            <option value="confirmed">Confirmed</option>
            <option value="delivered">Delivered</option>
            <option value="invoiced">Invoiced</option>
          </select>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save">Simpan</button>
          <button type="button" @click="closeForm" class="btn-cancel">Batal</button>
        </div>
      </form>
    </div>

    <!-- Daftar Project -->
    <button @click="fetchProjects" class="btn-refresh">Muat Data</button>

    <ul v-if="projects.length">
      <li v-for="p in projects" :key="p.id" class="project-item">
        <div class="project-info">
          <strong>{{ p.client?.name }}</strong> | Invoice: {{ p.invoice_number }}
          <span :class="'status-' + p.status">{{ p.status }}</span>
          <div class="detail">PO: {{ p.po_number || '-' }}</div>
          <div class="detail">Pengirim: {{ p.sender_name }} → Penerima: {{ p.receiver_name }}</div>
          <div class="detail">Mode: {{ p.shipping_mode }} | Berat: {{ p.weight_kg }} kg | Koli: {{ p.collie }}</div>
        </div>
        <div class="project-actions">
          <button @click="editProject(p)" class="btn-edit">Edit</button>
          <button @click="deleteProject(p.id)" class="btn-delete">Hapus</button>
        </div>
      </li>
    </ul>
    <p v-else class="empty-message">Belum ada project.</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from '../axios'

const projects = ref([])
const clients = ref([])
const showForm = ref(false)
const editingId = ref(null)

const form = reactive({
  client_id: '',
  po_number: '',
  invoice_number: '',
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
  ppn: false,
  discount: '',
  insurance: '',
  goods_value: '',
  shipping_mode: 'darat',
  status: 'draft',
})

const fetchProjects = async () => {
  try {
    const res = await axios.get('/api/delivery-projects')
    projects.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat project:', error)
    alert('Gagal memuat data: ' + error.message)
    projects.value = []
  }
}

const fetchClients = async () => {
  try {
    const res = await axios.get('/api/clients')
    clients.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat client:', error)
  }
}

const fetchClientData = async () => {
  if (!form.client_id) return
  try {
    const res = await axios.get(`/api/delivery-projects/client/${form.client_id}`)
    const data = res.data
    form.sender_name = data.name || ''
    form.sender_phone = data.phone || ''
    form.sender_address = data.address || ''
  } catch (error) {
    console.error('Gagal ambil data client:', error)
  }
}

const openForm = (data = null) => {
  showForm.value = true
  if (data) {
    // Edit mode
    editingId.value = data.id
    Object.assign(form, data)
    form.invoice_number = data.invoice_number
  } else {
    // Add mode
    editingId.value = null
    form.client_id = ''
    form.po_number = ''
    form.invoice_number = ''
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
    form.ppn = false
    form.discount = ''
    form.insurance = ''
    form.goods_value = ''
    form.shipping_mode = 'darat'
    form.status = 'draft'
  }
}

const closeForm = () => {
  showForm.value = false
  editingId.value = null
}

const saveProject = async () => {
  try {
    if (editingId.value) {
      await axios.put(`/api/delivery-projects/${editingId.value}`, form)
      alert('Project berhasil diupdate!')
    } else {
      await axios.post('/api/delivery-projects', form)
      alert('Project berhasil dibuat!')
    }
    closeForm()
    await fetchProjects()
  } catch (error) {
    console.error('Error:', error)
    if (error.response?.status === 422) {
      alert('Validasi gagal: ' + JSON.stringify(error.response.data.errors))
    } else {
      alert('Gagal menyimpan: ' + error.message)
    }
  }
}

const editProject = (p) => openForm(p)
const deleteProject = async (id) => {
  if (!confirm('Hapus project ini?')) return
  try {
    await axios.delete(`/api/delivery-projects/${id}`)
    alert('Project dihapus!')
    await fetchProjects()
  } catch (error) {
    alert('Gagal hapus: ' + error.message)
  }
}

onMounted(() => {
  fetchProjects()
  fetchClients()
})
</script>

<style scoped>
/* Sama seperti style komponen lain, bisa disesuaikan */
</style>