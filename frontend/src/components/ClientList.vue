<template>
  <div>
    <!-- HEADER -->
    <div class="list-header">
      <h2><i class="fas fa-users"></i> Manajemen Client</h2>
    </div>

    <!-- TOOLBAR (Import & Export di sini) -->
    <div class="toolbar">
      <button @click="openForm()" class="btn-primary">
        <i class="fas fa-plus-circle"></i> Tambah Client
      </button>
      <button @click="loadClients" class="btn-outline">
        <i class="fas fa-sync-alt"></i> Muat
      </button>

      <!-- IMPORT (Input file tersembunyi) -->
      <label class="btn-outline" style="cursor:pointer;">
        <i class="fas fa-file-upload"></i> Import
        <input type="file" ref="fileInput" @change="handleImport" accept=".xlsx,.csv" style="display:none">
      </label>

      <!-- EXPORT (HANYA 1) -->
      <button @click="exportData" class="btn-outline">
        <i class="fas fa-file-excel"></i> Export
      </button>
    </div>

    <!-- FORM Tambah/Edit -->
    <div v-if="showForm" class="form-card">
      <h4>{{ formMode === 'add' ? 'Tambah Client' : 'Edit Client' }}</h4>
      <form @submit.prevent="saveClient">
        <div class="form-row">
          <div class="form-group">
            <label>Nama <span class="required">*</span></label>
            <input v-model="form.name" required />
          </div>
          <div class="form-group">
            <label>Telepon</label>
            <input v-model="form.phone" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Email</label>
            <input v-model="form.email" type="email" />
          </div>
          <div class="form-group">
            <label>NPWP</label>
            <input v-model="form.npwp" />
          </div>
        </div>
        <div class="form-group">
          <label>Alamat</label>
          <textarea v-model="form.address" rows="2"></textarea>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
          <button type="button" @click="closeForm" class="btn-cancel">Batal</button>
        </div>
      </form>
    </div>

    <!-- TABLE -->
    <div v-if="loading" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i> Memuat...
    </div>
    <div v-else class="table-card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>#</th><th>Nama</th><th>Email</th><th>Telepon</th><th>NPWP</th><th class="text-center">Aksi</th></tr>
          </thead>
          <tbody>
            <tr v-for="(c, idx) in clients" :key="c.id">
              <td>{{ idx+1 }}</td>
              <td><strong>{{ c.name }}</strong></td>
              <td>{{ c.email || '-' }}</td>
              <td>{{ c.phone || '-' }}</td>
              <td>{{ c.npwp || '-' }}</td>
              <td class="text-center">
                <button @click="editClient(c)" class="btn-icon"><i class="fas fa-edit"></i></button>
                <button @click="deleteClient(c.id)" class="btn-icon danger"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
            <tr v-if="clients.length === 0">
              <td colspan="6" class="text-center empty-state">Belum ada client</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  data() {
    return {
      loading: false,
      clients: [],
      showForm: false,
      formMode: 'add',
      editingId: null,
      form: { name: '', phone: '', email: '', npwp: '', address: '' }
    }
  },
  methods: {
    async loadClients() {
      this.loading = true
      try {
        const res = await axios.get('/clients')
        this.clients = res.data.data || []
      } catch (e) { console.error(e) }
      finally { this.loading = false }
    },

    // ===== EXPORT (BENAR: pakai blob) =====
    async exportData() {
      try {
        const res = await axios.get('/clients/export', { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'clients.xlsx')
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (e) {
        alert('Gagal export: ' + e.message)
      }
    },

    // ===== IMPORT (BENAR: pakai FormData) =====
    async handleImport(event) {
      const file = event.target.files[0]
      if (!file) return
      const formData = new FormData()
      formData.append('file', file)
      try {
        await axios.post('/clients/import', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        alert('Import berhasil!')
        this.loadClients()
      } catch (e) {
        alert('Gagal import: ' + (e.response?.data?.message || e.message))
      }
      this.$refs.fileInput.value = ''
    },

    // ===== CRUD =====
    openForm(mode = 'add', data = null) {
      this.formMode = mode
      this.showForm = true
      if (mode === 'add') {
        this.form = { name: '', phone: '', email: '', npwp: '', address: '' }
        this.editingId = null
      } else if (data) {
        this.form = { ...data }
        this.editingId = data.id
      }
    },
    closeForm() {
      this.showForm = false
      this.formMode = 'add'
      this.editingId = null
    },
    async saveClient() {
      try {
        if (this.formMode === 'add') {
          await axios.post('/clients', this.form)
          alert('Client berhasil ditambahkan!')
        } else {
          await axios.put(`/clients/${this.editingId}`, this.form)
          alert('Client berhasil diupdate!')
        }
        this.closeForm()
        this.loadClients()
      } catch (e) {
        alert('Gagal: ' + (e.response?.data?.message || e.message))
      }
    },
    editClient(client) { this.openForm('edit', client) },
    async deleteClient(id) {
      if (!confirm('Yakin hapus client ini?')) return
      try {
        await axios.delete(`/clients/${id}`)
        alert('Client dihapus!')
        this.loadClients()
      } catch (e) {
        alert('Gagal hapus: ' + e.message)
      }
    }
  },
  mounted() { this.loadClients() }
}
</script>

<style scoped>
/* ====== GAYA UMUM ====== */
.list-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.list-header h2 { font-size: 24px; font-weight: 700; color: #0d2b45; margin: 0; display: flex; align-items: center; gap: 8px; }
.list-header h2 i { color: #2b6cb0; }

.toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; align-items: center; }
.btn-primary { background: #2b6cb0; color: white; border: none; padding: 8px 18px; border-radius: 30px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
.btn-primary:hover { background: #1a4a7a; transform: translateY(-2px); }
.btn-outline { background: white; color: #2d3748; border: 1.5px solid #e2e8f0; padding: 8px 18px; border-radius: 30px; font-weight: 500; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
.btn-outline:hover { border-color: #2b6cb0; background: #f7fafc; transform: translateY(-2px); }

/* FORM */
.form-card { background: white; border-radius: 16px; padding: 20px 24px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
.form-card h4 { margin: 0 0 16px 0; font-size: 18px; color: #0d2b45; border-bottom: 2px solid #f1f3f5; padding-bottom: 10px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-weight: 600; font-size: 14px; color: #2d3748; margin-bottom: 4px; }
.form-group .required { color: #e53e3e; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: white; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #2b6cb0; box-shadow: 0 0 0 3px rgba(43,108,176,0.1); }
.form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
.btn-save { background: #2b6cb0; color: white; border: none; padding: 8px 24px; border-radius: 30px; font-weight: 600; cursor: pointer; }
.btn-save:hover { background: #1a4a7a; }
.btn-cancel { background: #e2e8f0; color: #2d3748; border: none; padding: 8px 24px; border-radius: 30px; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #cbd5e1; }

/* TABLE */
.table-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; }
.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
thead { background: #f7fafc; border-bottom: 2px solid #e2e8f0; }
th, td { padding: 10px 14px; text-align: left; }
tbody tr { border-bottom: 1px solid #f1f3f5; }
tbody tr:hover { background: #f7fafc; }
.text-center { text-align: center; }
.empty-state { padding: 20px; color: #a0aec0; }

.btn-icon { background: transparent; border: none; padding: 4px 8px; color: #4a5568; cursor: pointer; transition: 0.2s; }
.btn-icon:hover { color: #2b6cb0; }
.btn-icon.danger:hover { color: #e53e3e; }

.loading-state { text-align: center; padding: 40px; color: #4a5568; }
</style>