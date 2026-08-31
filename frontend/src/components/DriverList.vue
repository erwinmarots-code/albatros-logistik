<template>
  <div>
    <div class="list-header">
      <h2><i class="fas fa-user-tie"></i> Manajemen Driver</h2>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar">
      <button @click="openForm()" class="btn-primary">
        <i class="fas fa-plus-circle"></i> Tambah Driver
      </button>
      <button @click="loadDrivers" class="btn-outline">
        <i class="fas fa-sync-alt"></i> Muat
      </button>
      <label class="btn-outline" style="cursor:pointer;">
        <i class="fas fa-file-upload"></i> Import
        <input type="file" ref="fileInput" @change="handleImport" accept=".xlsx,.csv" style="display:none">
      </label>
      <button @click="exportData" class="btn-outline">
        <i class="fas fa-file-excel"></i> Export
      </button>
    </div>

    <!-- FORM -->
    <div v-if="showForm" class="form-card">
      <h4>{{ formMode === 'add' ? 'Tambah Driver' : 'Edit Driver' }}</h4>
      <form @submit.prevent="saveDriver">
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
            <label>No. SIM</label>
            <input v-model="form.license_number" />
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="form.status">
              <option value="available">Available</option>
              <option value="on_duty">On Duty</option>
              <option value="off">Off</option>
            </select>
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
    <div v-if="loading" class="loading-state"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>
    <div v-else class="table-card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>#</th><th>Nama</th><th>Telepon</th><th>No. SIM</th><th>Alamat</th><th>Status</th><th class="text-center">Aksi</th></tr>
          </thead>
          <tbody>
            <tr v-for="(d, idx) in drivers" :key="d.id">
              <td>{{ idx+1 }}</td>
              <td><strong>{{ d.name }}</strong></td>
              <td>{{ d.phone || '-' }}</td>
              <td>{{ d.license_number || '-' }}</td>
              <td>{{ d.address || '-' }}</td>
              <td><span class="badge" :class="'badge-' + d.status">{{ d.status }}</span></td>
              <td class="text-center">
                <button @click="editDriver(d)" class="btn-icon"><i class="fas fa-edit"></i></button>
                <button @click="deleteDriver(d.id)" class="btn-icon danger"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
            <tr v-if="drivers.length === 0"><td colspan="7" class="text-center empty-state">Belum ada driver</td></tr>
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
      drivers: [],
      showForm: false,
      formMode: 'add',
      editingId: null,
      form: { name: '', phone: '', license_number: '', address: '', status: 'available' }
    }
  },
  methods: {
    async loadDrivers() {
      this.loading = true
      try {
        const res = await axios.get('/drivers')
        this.drivers = res.data.data || []
      } catch (e) { console.error(e) }
      finally { this.loading = false }
    },
    async exportData() {
      try {
        const res = await axios.get('/drivers/export', { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'drivers.xlsx')
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (e) {
        alert('Gagal export: ' + e.message)
      }
    },
    async handleImport(event) {
      const file = event.target.files[0]
      if (!file) return
      const formData = new FormData()
      formData.append('file', file)
      try {
        await axios.post('/drivers/import', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        alert('Import berhasil!')
        this.loadDrivers()
      } catch (e) {
        alert('Gagal import: ' + (e.response?.data?.message || e.message))
      }
      this.$refs.fileInput.value = ''
    },
    openForm(mode = 'add', data = null) {
      this.formMode = mode
      this.showForm = true
      if (mode === 'add') {
        this.form = { name: '', phone: '', license_number: '', address: '', status: 'available' }
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
    async saveDriver() {
      try {
        if (this.formMode === 'add') {
          await axios.post('/drivers', this.form)
          alert('Driver berhasil ditambahkan!')
        } else {
          await axios.put(`/drivers/${this.editingId}`, this.form)
          alert('Driver berhasil diupdate!')
        }
        this.closeForm()
        this.loadDrivers()
      } catch (e) {
        alert('Gagal: ' + (e.response?.data?.message || e.message))
      }
    },
    editDriver(d) { this.openForm('edit', d) },
    async deleteDriver(id) {
      if (!confirm('Yakin hapus driver ini?')) return
      try {
        await axios.delete(`/drivers/${id}`)
        alert('Driver dihapus!')
        this.loadDrivers()
      } catch (e) {
        alert('Gagal hapus: ' + e.message)
      }
    }
  },
  mounted() { this.loadDrivers() }
}
</script>

<style scoped>
/* Gaya sama dengan VehicleList */
.list-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.list-header h2 { font-size: 24px; font-weight: 700; color: #0d2b45; margin: 0; display: flex; align-items: center; gap: 8px; }
.list-header h2 i { color: #2b6cb0; }
.toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; align-items: center; }
.btn-primary { background: #2b6cb0; color: white; border: none; padding: 8px 18px; border-radius: 30px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
.btn-primary:hover { background: #1a4a7a; transform: translateY(-2px); }
.btn-outline { background: white; color: #2d3748; border: 1.5px solid #e2e8f0; padding: 8px 18px; border-radius: 30px; font-weight: 500; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
.btn-outline:hover { border-color: #2b6cb0; background: #f7fafc; transform: translateY(-2px); }
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
.table-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; }
.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
thead { background: #f7fafc; border-bottom: 2px solid #e2e8f0; }
th, td { padding: 10px 14px; text-align: left; }
tbody tr { border-bottom: 1px solid #f1f3f5; }
tbody tr:hover { background: #f7fafc; }
.text-center { text-align: center; }
.empty-state { padding: 20px; color: #a0aec0; }
.badge { display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
.badge-available { background: #d1fae5; color: #065f46; }
.badge-on_duty { background: #dbeafe; color: #1e40af; }
.badge-off { background: #fef3c7; color: #92400e; }
.btn-icon { background: transparent; border: none; padding: 4px 8px; color: #4a5568; cursor: pointer; transition: 0.2s; }
.btn-icon:hover { color: #2b6cb0; }
.btn-icon.danger:hover { color: #e53e3e; }
.loading-state { text-align: center; padding: 40px; color: #4a5568; }
</style>