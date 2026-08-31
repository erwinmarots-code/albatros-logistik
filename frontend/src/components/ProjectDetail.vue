<template>
  <div class="project-detail-container">
    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <i class="fas fa-spinner fa-spin fa-2x"></i>
      <p>Memuat data project...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
      <button class="btn btn-primary mt-3" @click="fetchData">Coba Lagi</button>
    </div>

    <!-- Content -->
    <div v-else-if="project" class="project-detail">
      <!-- Header -->
      <div class="page-header">
        <h2>
          <i class="fas fa-folder-open"></i> Detail Project
          <span class="badge" :class="statusBadge(project.status)">{{ project.status }}</span>
        </h2>
        <div class="header-actions">
          <button class="btn btn-secondary" @click="goBack">
            <i class="fas fa-arrow-left"></i> Kembali
          </button>
          <button class="btn btn-primary" @click="editProject">
            <i class="fas fa-edit"></i> Edit
          </button>
        </div>
      </div>

      <!-- Informasi Project -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-info-circle"></i> Informasi Project</h3>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <label>No PO</label>
              <span>{{ project.no_po }}</span>
            </div>
            <div class="info-item">
              <label>No Resi</label>
              <span>{{ project.no_resi || '-' }}</span>
            </div>
            <div class="info-item">
              <label>Client</label>
              <span>{{ project.client?.name || '-' }}</span>
            </div>
            <div class="info-item">
              <label>Cabang</label>
              <span>{{ project.branch?.code || '-' }} - {{ project.branch?.name || '-' }}</span>
            </div>
            <div class="info-item">
              <label>Nilai Kontrak</label>
              <span>Rp {{ formatNumber(project.contract_value) }}</span>
            </div>
            <div class="info-item">
              <label>Metode Kirim</label>
              <span>{{ project.shipping_method || '-' }}</span>
            </div>
            <div class="info-item">
              <label>Status</label>
              <span><span class="badge" :class="statusBadge(project.status)">{{ project.status }}</span></span>
            </div>
            <div class="info-item">
              <label>Dibuat Oleh</label>
              <span>{{ project.creator?.name || '-' }}</span>
            </div>
            <div class="info-item">
              <label>Dibuat Pada</label>
              <span>{{ formatDate(project.created_at) }}</span>
            </div>
            <div class="info-item full-width">
              <label>Catatan</label>
              <span>{{ project.notes || '-' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Data Pengirim -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-user"></i> Data Pengirim</h3>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <label>Nama</label>
              <span>{{ project.sender_name || '-' }}</span>
            </div>
            <div class="info-item">
              <label>Telepon</label>
              <span>{{ project.sender_phone || '-' }}</span>
            </div>
            <div class="info-item full-width">
              <label>Alamat</label>
              <span>{{ project.sender_address || '-' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Daftar Tugas Kirim -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-tasks"></i> Daftar Tugas Kirim</h3>
          <router-link :to="`/delivery-tasks?project_id=${project.id}`" class="btn btn-sm btn-primary">
            <i class="fas fa-plus-circle"></i> Tambah Tugas
          </router-link>
        </div>
        <div class="card-body">
          <div v-if="project.delivery_tasks && project.delivery_tasks.length" class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>No Resi</th>
                  <th>Kendaraan</th>
                  <th>Driver</th>
                  <th>Penerima</th>
                  <th>Alamat</th>
                  <th>Telepon</th>
                  <th>Tanggal Kirim</th>
                  <th>Status</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="task in project.delivery_tasks" :key="task.id">
                  <td><strong>{{ task.no_resi }}</strong></td>
                  <td>{{ task.vehicle?.plate_number || '-' }}</td>
                  <td>{{ task.driver?.name || '-' }}</td>
                  <td>{{ task.receiver_name || '-' }}</td>
                  <td>{{ task.receiver_address || '-' }}</td>
                  <td>{{ task.receiver_phone || '-' }}</td>
                  <td>{{ formatDate(task.tanggal || task.delivery_date) }}</td>
                  <td><span class="badge" :class="statusBadge(task.status)">{{ task.status }}</span></td>
                  <td class="text-center">
                    <router-link :to="`/delivery-tasks/${task.id}/edit`" class="btn-icon" title="Edit">
                      <i class="fas fa-edit"></i>
                    </router-link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else>
            <p class="text-muted">Belum ada tugas kirim untuk project ini.</p>
            <router-link :to="`/delivery-tasks?project_id=${project.id}`" class="btn btn-primary btn-sm">
              <i class="fas fa-plus-circle"></i> Buat Tugas Kirim
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  name: 'ProjectDetail',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      project: null,
      loading: false,
      error: null
    }
  },
  mounted() {
    this.fetchData()
  },
  methods: {
    async fetchData() {
      this.loading = true
      this.error = null
      try {
        const res = await axios.get(`/projects/${this.id}`)
        this.project = res.data.data
      } catch (e) {
        this.error = e.response?.data?.message || 'Gagal memuat data project'
        console.error('Error fetching project detail:', e)
      } finally {
        this.loading = false
      }
    },

    editProject() {
      this.$router.push(`/projects/${this.id}/edit`)
    },

    goBack() {
      this.$router.push('/projects')
    },

    formatDate(date) {
      if (!date) return '-'
      const d = new Date(date)
      if (isNaN(d.getTime())) return '-'
      return String(d.getDate()).padStart(2, '0') + '-' +
             String(d.getMonth() + 1).padStart(2, '0') + '-' +
             d.getFullYear()
    },

    formatNumber(val) {
      if (!val) return '0'
      return val.toLocaleString('id-ID')
    },

    statusBadge(status) {
      const map = {
        draft: 'badge-secondary',
        confirmed: 'badge-info',
        on_delivery: 'badge-warning',
        completed: 'badge-success',
        cancelled: 'badge-danger',
        assigned: 'badge-info',
        in_progress: 'badge-warning'
      }
      return map[status] || 'badge-secondary'
    }
  }
}
</script>

<style scoped>
.project-detail-container {
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
  display: flex;
  align-items: center;
  gap: 12px;
}
.page-header h2 i {
  color: #2b6cb0;
  margin-right: 8px;
}
.header-actions {
  display: flex;
  gap: 10px;
}

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
  text-decoration: none;
}
.btn-primary {
  background: #2b6cb0;
  color: white;
}
.btn-primary:hover {
  background: #1a4a7a;
  transform: translateY(-2px);
}
.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}
.btn-secondary:hover {
  background: #cbd5e1;
}
.btn-sm {
  padding: 4px 12px;
  font-size: 13px;
}

.card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  margin-bottom: 24px;
  overflow: hidden;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f7fafc;
}
.card-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #0d2b45;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.card-header h3 i {
  color: #2b6cb0;
}
.card-body {
  padding: 20px 24px;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px 24px;
}
.info-item {
  display: flex;
  flex-direction: column;
}
.info-item.full-width {
  grid-column: 1 / -1;
}
.info-item label {
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}
.info-item span {
  font-size: 15px;
  color: #1a202c;
  word-break: break-word;
}

.table-responsive {
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
.text-muted {
  color: #6b7280;
  font-style: italic;
}

.badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}
.badge-secondary {
  background: #e2e8f0;
  color: #475569;
}
.badge-info {
  background: #dbeafe;
  color: #1e40af;
}
.badge-warning {
  background: #fef3c7;
  color: #92400e;
}
.badge-success {
  background: #d1fae5;
  color: #065f46;
}
.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.btn-icon {
  background: transparent;
  border: none;
  padding: 4px 8px;
  color: #4a5568;
  cursor: pointer;
  transition: 0.2s;
  font-size: 16px;
  text-decoration: none;
}
.btn-icon:hover {
  color: #2b6cb0;
}

.alert {
  padding: 20px;
  border-radius: 8px;
  background: #fee2e2;
  border: 1px solid #dc2626;
  color: #991b1b;
  text-align: center;
}
.text-center {
  text-align: center;
}
.py-5 {
  padding: 40px 0;
}
.mt-3 {
  margin-top: 12px;
}
.fa-spin {
  animation: fa-spin 2s infinite linear;
}
@keyframes fa-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr 1fr;
  }
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  .header-actions {
    justify-content: stretch;
  }
}
</style>