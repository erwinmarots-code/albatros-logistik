<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-history"></i> History Perawatan Kendaraan</h2>
      <p class="module-subtitle">Riwayat perawatan dan verifikasi penyelesaian</p>
    </div>

    <div class="toolbar">
      <button @click="fetchItems" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat Data
      </button>
      <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input v-model="searchQuery" type="text" class="search-input" placeholder="Cari history..." />
      </div>
      <!-- 🔥 TOMBOL EXPORT -->
      <button
        v-if="canExport"
        class="btn-export"
        @click="handleExport"
        :disabled="isExporting"
      >
        <i class="fas fa-file-excel"></i>
        {{ isExporting ? 'Mengekspor...' : 'Export Excel' }}
      </button>
    </div>

    <div class="table-wrapper" v-if="filteredItems.length">
      <table class="modern-table">
        <thead>
          <tr>
            <th>#</th>
            <th><i class="fas fa-car"></i> Kendaraan</th>
            <th>Deskripsi</th>
            <th><i class="fas fa-tools"></i> Jenis Service</th>
            <th><i class="fas fa-calendar-alt"></i> Tanggal</th>
            <th><i class="fas fa-money-bill-wave"></i> Estimasi</th>
            <th><i class="fas fa-circle"></i> Status</th>
            <th><i class="fas fa-check-circle"></i> Selesai</th>
            <th class="text-center"><i class="fas fa-cogs"></i> Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in filteredItems" :key="item.id">
            <td>{{ index + 1 }}</td>
            <td>{{ item.vehicle?.plate_number }} - {{ item.vehicle?.brand }}</td>
            <td>{{ item.description || '-' }}</td>
            <td>
              <span class="type-badge">{{ serviceTypeMap[item.service_type] || item.service_type || '-' }}</span>
            </td>
            <td>{{ formatDate(item.request_date) }}</td>
            <td>{{ formatRupiah(item.estimated_cost) }}</td>
            <td>
              <span :class="'status-badge-' + item.status">{{ statusMap[item.status] || item.status }}</span>
            </td>
            <td>
              <span v-if="item.is_executed" class="executed-badge">
                <i class="fas fa-check-circle"></i> {{ formatDate(item.executed_at) }}
              </span>
              <span v-else class="not-executed-badge">
                <i class="fas fa-clock"></i> Belum
              </span>
            </td>
            <td class="action-cell">
              <button
                v-if="canExecute && item.status === 'approved' && !item.is_executed"
                @click="executeItem(item.id)"
                class="btn-execute"
              >
                <i class="fas fa-check-double"></i> Selesai
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message">
      <i class="fas fa-inbox"></i>
      {{ searchQuery ? 'Tidak ada history yang cocok dengan pencarian.' : 'Belum ada riwayat perawatan.' }}
    </p>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../axios'
import { formatRupiah, statusMap, serviceTypeMap } from '../utils/helpers'
import { useExport } from '../composables/useExport'

// Helper format tanggal
const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const user = JSON.parse(localStorage.getItem('user') || '{}')
const userRole = user.role || ''
const canExecute = computed(() => ['admin_finance', 'super_admin'].includes(userRole))

// 🔥 Hak export: super_admin, admin_transport, admin_finance
const canExport = computed(() => ['super_admin', 'admin_transport', 'admin_finance'].includes(userRole))

// 🔥 Composabel untuk export
const { isExporting, exportData } = useExport('maintenance-requests')

const items = ref([])
const searchQuery = ref('')

const filteredItems = computed(() => {
  let data = items.value.filter(item => item.status === 'approved' || item.status === 'done')
  if (!searchQuery.value) return data
  const q = searchQuery.value.toLowerCase()
  return data.filter(item =>
    item.vehicle?.plate_number?.toLowerCase().includes(q) ||
    item.description?.toLowerCase().includes(q) ||
    item.service_type?.toLowerCase().includes(q)
  )
})

const fetchItems = async () => {
  try {
    const res = await axios.get('/maintenance-requests')
    items.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat data: ' + error.message)
  }
}

const executeItem = async (id) => {
  if (!canExecute.value) return
  if (!confirm('Tandai perawatan ini sebagai SELESAI?')) return
  try {
    await axios.post(`/maintenance-requests/${id}/execute`)
    alert('Perawatan berhasil ditandai selesai!')
    await fetchItems()
  } catch (error) {
    alert('Gagal: ' + (error.response?.data?.message || error.message))
  }
}

// 🔥 Handle Export
const handleExport = async () => {
  await exportData({
    search: searchQuery.value || undefined,
    status: 'approved,done', // hanya export history yang sudah approved/done
  })
}

onMounted(fetchItems)
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

/* 🔥 Tombol Export */
.btn-export {
  background: white;
  color: #2d3748;
  border: 1.5px solid #2b6cb0;
  padding: 10px 22px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: 0.2s;
}
.btn-export:hover {
  background: #2b6cb0;
  color: white;
  transform: translateY(-2px);
}
.btn-export:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
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

.type-badge {
  background: #6c757d;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge-approved {
  background: #17a2b8;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.status-badge-done {
  background: #28a745;
  color: white;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.executed-badge {
  color: #28a745;
  font-weight: 600;
}
.not-executed-badge {
  color: #dc3545;
  font-weight: 600;
}

.btn-execute {
  background: #28a745;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 6px 14px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-weight: 600;
}
.btn-execute:hover {
  background: #218838;
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
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  .search-wrapper {
    max-width: 100%;
  }
  .btn-export {
    justify-content: center;
  }
}
</style>