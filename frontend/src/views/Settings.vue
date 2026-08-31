<template>
  <div class="settings-container">
    <h2><i class="fas fa-cog"></i> Pengaturan Sistem</h2>

    <!-- Tabs -->
    <div class="tabs">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        :class="['tab', { active: activeTab === tab.key }]"
        @click="activeTab = tab.key"
      >
        <i :class="tab.icon"></i> {{ tab.label }}
      </button>
    </div>

    <!-- ========================================================== -->
    <!-- TAB 1: PROFILE PERUSAHAAN -->
    <!-- ========================================================== -->
    <div v-if="activeTab === 'profile'" class="tab-content">
      <div class="card">
        <h3><i class="fas fa-building"></i> Profile Perusahaan</h3>
        <form @submit.prevent="saveProfile" class="form-grid">
          <div class="form-group">
            <label>Nama Perusahaan <span class="required">*</span></label>
            <input v-model="profile.company_name" type="text" class="form-control" required />
          </div>
          <div class="form-group">
            <label>Alamat <span class="required">*</span></label>
            <textarea v-model="profile.company_address" class="form-control" rows="2" required></textarea>
          </div>
          <div class="form-group">
            <label>Telepon <span class="required">*</span></label>
            <input v-model="profile.company_phone" type="text" class="form-control" required />
          </div>
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input v-model="profile.company_email" type="email" class="form-control" required />
          </div>
          <div class="form-group">
            <label>NPWP</label>
            <input v-model="profile.company_tax" type="text" class="form-control" />
          </div>
          <div class="form-group full-width">
            <label>Logo Perusahaan</label>
            <div class="logo-upload">
              <div v-if="profile.logo" class="logo-preview">
                <img :src="getLogoUrl(profile.logo)" alt="Logo" />
                <button type="button" class="btn btn-sm btn-danger" @click="removeLogo">Hapus</button>
              </div>
              <div v-else class="logo-placeholder">
                <i class="fas fa-image"></i>
                <p>Belum ada logo</p>
              </div>
              <input type="file" accept="image/*" @change="uploadLogo" ref="logoInput" />
              <button type="button" class="btn btn-secondary" @click="$refs.logoInput.click()">Pilih Logo</button>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-success" :disabled="loadingProfile">
              <i v-if="loadingProfile" class="fas fa-spinner fa-spin"></i>
              {{ loadingProfile ? 'Menyimpan...' : 'Simpan Profile' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================================== -->
    <!-- TAB 2: FORMAT KODE -->
    <!-- ========================================================== -->
    <div v-if="activeTab === 'codes'" class="tab-content">
      <div class="card">
        <h3><i class="fas fa-code"></i> Format Kode Unik</h3>
        <p class="text-muted">Gunakan placeholder <code>{PREFIX}</code>, <code>{RANDOM}</code>, <code>{DATE}</code>, <code>{YEAR}</code>, <code>{MONTH}</code>, <code>{DAY}</code>, <code>{PO}</code>, <code>{ID}</code></p>
        <form @submit.prevent="saveFormats" class="form-grid">
          <div class="form-group">
            <label>Prefix Resi</label>
            <input v-model="formats.resi_prefix" type="text" class="form-control" />
          </div>
          <div class="form-group">
            <label>Format Resi</label>
            <input v-model="formats.resi_format" type="text" class="form-control" placeholder="{PREFIX}-{RANDOM}" />
          </div>
          <div class="form-group">
            <label>Prefix Invoice</label>
            <input v-model="formats.invoice_prefix" type="text" class="form-control" />
          </div>
          <div class="form-group">
            <label>Format Invoice</label>
            <input v-model="formats.invoice_format" type="text" class="form-control" placeholder="{PREFIX}-{PO}" />
          </div>
          <div class="form-group">
            <label>Prefix Pengajuan Biaya</label>
            <input v-model="formats.fuel_prefix" type="text" class="form-control" />
          </div>
          <div class="form-group">
            <label>Format Pengajuan Biaya</label>
            <input v-model="formats.fuel_format" type="text" class="form-control" />
          </div>
          <div class="form-group">
            <label>Prefix Perawatan</label>
            <input v-model="formats.maintenance_prefix" type="text" class="form-control" />
          </div>
          <div class="form-group">
            <label>Format Perawatan</label>
            <input v-model="formats.maintenance_format" type="text" class="form-control" />
          </div>
          <div class="form-actions full-width">
            <button type="submit" class="btn btn-success" :disabled="loadingFormats">
              <i v-if="loadingFormats" class="fas fa-spinner fa-spin"></i>
              {{ loadingFormats ? 'Menyimpan...' : 'Simpan Format' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================================== -->
    <!-- TAB 3: EKSPOR & IMPOR -->
    <!-- ========================================================== -->
    <div v-if="activeTab === 'export'" class="tab-content">
      <div class="card">
        <h3><i class="fas fa-file-export"></i> Ekspor & Impor Data</h3>
        <div class="export-grid">
          <div v-for="menu in exportMenus" :key="menu.key" class="export-item">
            <div class="export-info">
              <i :class="menu.icon"></i>
              <span>{{ menu.label }}</span>
            </div>
            <div class="export-actions">
              <button class="btn btn-sm btn-primary" @click="exportData(menu.key)">
                <i class="fas fa-download"></i> Ekspor
              </button>
              <button class="btn btn-sm btn-secondary" @click="triggerImport(menu.key)">
                <i class="fas fa-upload"></i> Impor
              </button>
              <input
                type="file"
                accept=".csv"
                :ref="'import_' + menu.key"
                style="display:none"
                @change="importData($event, menu.key)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  name: 'Settings',
  data() {
    return {
      activeTab: 'profile',
      tabs: [
        { key: 'profile', label: 'Profile Perusahaan', icon: 'fas fa-building' },
        { key: 'codes', label: 'Format Kode', icon: 'fas fa-code' },
        { key: 'export', label: 'Ekspor & Impor', icon: 'fas fa-file-export' },
      ],
      profile: {
        company_name: '',
        company_address: '',
        company_phone: '',
        company_email: '',
        company_tax: '',
        logo: null,
      },
      formats: {
        resi_prefix: 'RESI',
        invoice_prefix: 'INV',
        fuel_prefix: 'FUEL',
        maintenance_prefix: 'MNT',
        resi_format: '{PREFIX}-{RANDOM}',
        invoice_format: '{PREFIX}-{PO}',
        fuel_format: '{PREFIX}-{DATE}-{RANDOM}',
        maintenance_format: '{PREFIX}-{DATE}-{RANDOM}',
      },
      loadingProfile: false,
      loadingFormats: false,
      exportMenus: [
        { key: 'clients', label: 'Client', icon: 'fas fa-users' },
        { key: 'vehicles', label: 'Kendaraan', icon: 'fas fa-truck' },
        { key: 'drivers', label: 'Driver', icon: 'fas fa-user-tie' },
        { key: 'projects', label: 'Project', icon: 'fas fa-folder-open' },
        { key: 'delivery-tasks', label: 'Tugas Kirim', icon: 'fas fa-tasks' },
        { key: 'fuel-expenses', label: 'Pengajuan Biaya', icon: 'fas fa-coins' },
        { key: 'maintenance-requests', label: 'Perawatan', icon: 'fas fa-tools' },
        { key: 'invoices', label: 'Invoice', icon: 'fas fa-file-invoice' },
        { key: 'financial-transactions', label: 'Transaksi Keuangan', icon: 'fas fa-chart-pie' },
        { key: 'users', label: 'User', icon: 'fas fa-users-cog' },
        { key: 'spare-parts', label: 'Spare Part', icon: 'fas fa-boxes' },
      ],
    }
  },
  mounted() {
    this.loadProfile()
    this.loadFormats()
  },
  methods: {
    async loadProfile() {
      try {
        const res = await axios.get('/settings/company-profile')
        this.profile = res.data.data
      } catch (e) {
        console.error('Error loading profile:', e)
      }
    },

    async loadFormats() {
      try {
        const res = await axios.get('/settings/code-formats')
        this.formats = res.data.data
      } catch (e) {
        console.error('Error loading formats:', e)
      }
    },

    getLogoUrl(path) {
      if (!path) return ''
      return `http://localhost:8000/storage/${path}`
    },

    async saveProfile() {
      this.loadingProfile = true
      try {
        await axios.put('/settings/company-profile', this.profile)
        alert('Profile perusahaan berhasil disimpan')
      } catch (e) {
        alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loadingProfile = false
      }
    },

    async uploadLogo(event) {
      const file = event.target.files[0]
      if (!file) return

      const formData = new FormData()
      formData.append('logo', file)

      try {
        const res = await axios.post('/settings/logo', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.profile.logo = res.data.data.logo
        alert('Logo berhasil diupload')
      } catch (e) {
        alert('Gagal upload logo: ' + (e.response?.data?.message || e.message))
      }
      event.target.value = ''
    },

    async removeLogo() {
      this.profile.logo = null
      // Backend akan handle hapus file saat upload berikutnya
    },

    async saveFormats() {
      this.loadingFormats = true
      try {
        await axios.put('/settings/code-formats', this.formats)
        alert('Format kode berhasil disimpan')
      } catch (e) {
        alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loadingFormats = false
      }
    },

    async exportData(menu) {
      try {
        const res = await axios.get(`/${menu}/export`, {
          responseType: 'blob'
        })
        const blob = new Blob([res.data], { type: 'text/csv' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `${menu}_export_${new Date().toISOString().slice(0,10)}.csv`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (e) {
        alert('Gagal ekspor: ' + (e.response?.data?.message || e.message))
      }
    },

    triggerImport(menu) {
      const ref = this.$refs['import_' + menu]
      if (ref && ref[0]) ref[0].click()
    },

    async importData(event, menu) {
      const file = event.target.files[0]
      if (!file) return

      const formData = new FormData()
      formData.append('file', file)

      try {
        const res = await axios.post(`/${menu}/import`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        alert(res.data.message || 'Import berhasil')
      } catch (e) {
        alert('Gagal import: ' + (e.response?.data?.message || e.message))
      }
      event.target.value = ''
    }
  }
}
</script>

<style scoped>
.settings-container { max-width: 1000px; margin: 0 auto; padding: 0 16px; }
.settings-container h2 { font-size: 24px; font-weight: 700; color: #0d2b45; margin-bottom: 24px; }
.settings-container h2 i { color: #2b6cb0; margin-right: 8px; }

.tabs { display: flex; gap: 4px; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px; }
.tab { padding: 10px 20px; border: none; background: none; font-size: 14px; font-weight: 600; color: #4a5568; cursor: pointer; transition: 0.2s; border-bottom: 3px solid transparent; }
.tab:hover { color: #2b6cb0; }
.tab.active { color: #2b6cb0; border-bottom-color: #2b6cb0; }
.tab i { margin-right: 6px; }

.card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 24px; }
.card h3 { font-size: 18px; font-weight: 600; color: #0d2b45; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px; }
.card h3 i { color: #2b6cb0; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; }
.form-group.full-width { grid-column: 1 / -1; }
.form-group label { font-weight: 600; font-size: 14px; color: #2d3748; margin-bottom: 4px; }
.form-group .required { color: #dc2626; }
.form-control { padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: 0.2s; width: 100%; }
.form-control:focus { outline: none; border-color: #2b6cb0; box-shadow: 0 0 0 3px rgba(43,108,176,0.15); }
textarea.form-control { resize: vertical; min-height: 60px; }
.form-actions { grid-column: 1 / -1; display: flex; gap: 12px; margin-top: 16px; justify-content: flex-end; }

.logo-upload { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.logo-preview { display: flex; align-items: center; gap: 12px; }
.logo-preview img { width: 80px; height: 80px; object-fit: contain; border: 1px solid #e2e8f0; border-radius: 8px; padding: 4px; background: white; }
.logo-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 80px; height: 80px; border: 2px dashed #e2e8f0; border-radius: 8px; color: #6b7280; font-size: 12px; }
.logo-placeholder i { font-size: 28px; margin-bottom: 4px; }

.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn-primary { background: #2b6cb0; color: white; }
.btn-primary:hover { background: #1a4a7a; transform: translateY(-2px); }
.btn-success { background: #22c55e; color: white; }
.btn-success:hover { background: #16a34a; }
.btn-secondary { background: #e2e8f0; color: #2d3748; }
.btn-secondary:hover { background: #cbd5e1; }
.btn-danger { background: #dc2626; color: white; }
.btn-danger:hover { background: #b91c1c; }
.btn-sm { padding: 4px 12px; font-size: 13px; }

.text-muted { color: #6b7280; font-size: 13px; margin-bottom: 12px; }
.text-muted code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px; }

.export-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px; }
.export-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; transition: 0.2s; }
.export-item:hover { background: #f7fafc; }
.export-info { display: flex; align-items: center; gap: 10px; font-weight: 500; }
.export-info i { width: 20px; color: #2b6cb0; }
.export-actions { display: flex; gap: 6px; }

@media (max-width: 768px) {
  .form-grid { grid-template-columns: 1fr; }
  .tabs { overflow-x: auto; flex-wrap: nowrap; }
  .tab { white-space: nowrap; }
}
</style>