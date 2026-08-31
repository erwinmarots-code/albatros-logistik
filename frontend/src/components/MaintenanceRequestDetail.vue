<template>
  <div class="maintenance-detail-container">
    <div class="page-header">
      <h2><i class="fas fa-tools"></i> Detail Perawatan #{{ id }}</h2>
      <div class="header-actions">
        <button class="btn btn-secondary" @click="goBack">
          <i class="fas fa-arrow-left"></i> Kembali
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <i class="fas fa-spinner fa-spin fa-2x"></i>
      <p>Memuat data...</p>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <div v-else-if="data" class="detail-content">
      <!-- Informasi Umum -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-info-circle"></i> Informasi Perawatan</h3>
          <span class="badge" :class="statusBadge(data.status)">{{ data.status }}</span>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item"><label>ID</label><span>#{{ data.id }}</span></div>
            <div class="info-item"><label>Kendaraan</label><span>{{ data.vehicle?.plate_number || '-' }}</span></div>
            <div class="info-item"><label>Driver</label><span>{{ data.driver?.name || '-' }}</span></div>
            <div class="info-item"><label>Tanggal Pengajuan</label><span>{{ formatDate(data.request_date) }}</span></div>
            <div class="info-item"><label>Jenis Service</label><span>{{ serviceTypeLabel(data.service_type) }}</span></div>
            <div class="info-item"><label>Urgensi</label><span class="badge" :class="urgencyBadge(data.urgency)">{{ data.urgency }}</span></div>
            <div class="info-item"><label>Estimasi Biaya</label><span>{{ formatCurrency(data.estimated_cost) }}</span></div>
            <div class="info-item"><label>Status</label><span class="badge" :class="statusBadge(data.status)">{{ data.status }}</span></div>
            <div class="info-item"><label>Disetujui Oleh</label><span>{{ data.approver?.name || '-' }}</span></div>
            <div class="info-item"><label>Disetujui Pada</label><span>{{ formatDate(data.approved_at) }}</span></div>
            <div class="info-item"><label>Dieksekusi Oleh</label><span>{{ data.executor?.name || '-' }}</span></div>
            <div class="info-item"><label>Dieksekusi Pada</label><span>{{ formatDate(data.executed_at) }}</span></div>
            <div class="info-item full-width"><label>Deskripsi</label><span>{{ data.description }}</span></div>
          </div>
        </div>
      </div>

      <!-- Spare Part yang Digunakan -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-boxes"></i> Spare Part yang Digunakan</h3>
        </div>
        <div class="card-body">
          <div v-if="data.items && data.items.length" class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama Spare Part</th>
                  <th>Kategori</th>
                  <th>Jumlah</th>
                  <th>Odometer Sebelum</th>
                  <th>Odometer Sesudah</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in data.items" :key="item.id">
                  <td><strong>{{ item.spare_part?.code || '-' }}</strong></td>
                  <td>{{ item.spare_part?.name || '-' }}</td>
                  <td>{{ item.spare_part?.category === 'sekali_pakai' ? 'Sekali Pakai' : 'Bisa Berulang' }}</td>
                  <td>{{ item.quantity }}</td>
                  <td>{{ item.odometer_before || '-' }}</td>
                  <td>{{ item.odometer_after || '-' }}</td>
                  <td>{{ item.notes || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else>
            <p class="text-muted">Tidak ada spare part yang digunakan.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  name: 'MaintenanceRequestDetail',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      data: null,
      loading: false,
      error: null
    }
  },
  mounted() {
    this.fetchData()
  },
  methods: {
    formatCurrency(val) {
      if (!val) return 'Rp 0'
      return 'Rp ' + Number(val).toLocaleString('id-ID')
    },
    formatDate(date) {
      if (!date) return '-'
      const d = new Date(date)
      return String(d.getDate()).padStart(2, '0') + '-' +
             String(d.getMonth() + 1).padStart(2, '0') + '-' +
             d.getFullYear() + ' ' +
             String(d.getHours()).padStart(2, '0') + ':' +
             String(d.getMinutes()).padStart(2, '0')
    },
    serviceTypeLabel(type) {
      const map = {
        oil_change: 'Ganti Oli',
        tire_replacement: 'Ganti Ban',
        sparepart: 'Sparepart',
        general: 'Service Umum',
        other: 'Lainnya'
      }
      return map[type] || type
    },
    statusBadge(status) {
      const map = {
        pending: 'badge-warning',
        approved: 'badge-info',
        rejected: 'badge-danger',
        done: 'badge-success'
      }
      return map[status] || 'badge-secondary'
    },
    urgencyBadge(urgency) {
      const map = {
        low: 'badge-secondary',
        medium: 'badge-warning',
        high: 'badge-danger'
      }
      return map[urgency] || 'badge-secondary'
    },
    async fetchData() {
      this.loading = true
      this.error = null
      try {
        const res = await axios.get(`/maintenance-requests/${this.id}`)
        this.data = res.data.data
      } catch (e) {
        this.error = e.response?.data?.message || 'Gagal memuat data'
        console.error('Error fetching maintenance detail:', e)
      } finally {
        this.loading = false
      }
    },
    goBack() {
      this.$router.push('/maintenance-requests')
    }
  }
}
</script>

<style scoped>
.maintenance-detail-container { max-width: 1200px; margin: 0 auto; padding: 0 16px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.page-header h2 { font-size: 24px; font-weight: 700; color: #0d2b45; margin: 0; display: flex; align-items: center; gap: 12px; }
.page-header h2 i { color: #2b6cb0; margin-right: 8px; }

.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; }
.btn-secondary { background: #e2e8f0; color: #2d3748; }
.btn-secondary:hover { background: #cbd5e1; }

.card { background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
.card-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; background: #f7fafc; }
.card-header h3 { font-size: 18px; font-weight: 600; color: #0d2b45; margin: 0; display: flex; align-items: center; gap: 8px; }
.card-header h3 i { color: #2b6cb0; }
.card-body { padding: 20px 24px; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px 24px; }
.info-item { display: flex; flex-direction: column; }
.info-item.full-width { grid-column: 1 / -1; }
.info-item label { font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
.info-item span { font-size: 15px; color: #1a202c; word-break: break-word; }

.table-responsive { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table thead { background: #f7fafc; border-bottom: 2px solid #e2e8f0; }
.table th { padding: 10px 12px; text-align: left; font-weight: 600; color: #2d3748; white-space: nowrap; }
.table td { padding: 10px 12px; border-bottom: 1px solid #f1f3f5; vertical-align: middle; }
.table tbody tr:hover { background: #f7fafc; }
.text-center { text-align: center; }
.text-muted { color: #6b7280; font-style: italic; }

.badge { display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-info { background: #dbeafe; color: #1e40af; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-secondary { background: #e2e8f0; color: #475569; }

.alert { padding: 20px; border-radius: 8px; background: #fee2e2; border: 1px solid #dc2626; color: #991b1b; text-align: center; }
.py-5 { padding: 40px 0; }
.fa-spin { animation: fa-spin 2s infinite linear; }
@keyframes fa-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

@media (max-width: 768px) { .info-grid { grid-template-columns: 1fr 1fr; } }
</style>