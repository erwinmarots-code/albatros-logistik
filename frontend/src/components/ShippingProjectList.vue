<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-folder-open"></i> Project Pengantaran</h2>
      <p class="module-subtitle">Kelola project dan statusnya</p>
    </div>

    <div class="toolbar">
      <button v-if="canCreate" @click="openForm()" class="btn-add">
        <i class="fas fa-plus-circle"></i> Buat Project
      </button>
      <button @click="fetchProjects" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Muat
      </button>
      <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input v-model="searchQuery" placeholder="Cari project..." />
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper">
      <table class="modern-table">
        <thead>
          <tr>
            <th>No PO</th>
            <th>No Resi</th>
            <th>Client</th>
            <th>Branch</th>
            <th>Nilai Kontrak</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in filteredProjects" :key="p.id">
            <td><strong>{{ p.no_po }}</strong></td>
            <td>{{ p.no_resi || '-' }}</td>
            <td>{{ p.client?.name || '-' }}</td>
            <td>{{ p.branch?.code || '-' }}</td>
            <td>Rp {{ formatNumber(p.contract_value) }}</td>
            <td><span class="badge" :class="'badge-' + p.status">{{ p.status }}</span></td>
            <td class="action-cell">
              <!-- Detail -->
              <button @click="viewDetail(p.id)" class="btn-icon" title="Detail">
                <i class="fas fa-eye"></i>
              </button>
              <!-- Edit (hanya jika status draft/confirmed) -->
              <button v-if="canEdit(p)" @click="openForm(p)" class="btn-icon" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <!-- Buat Pengajuan Biaya (jika sudah ada delivery task) -->
              <button v-if="canCreateFuel(p)" @click="createFuelExpense(p.id)" class="btn-icon" title="Buat Pengajuan Biaya">
                <i class="fas fa-coins"></i>
              </button>
              <!-- Update Status -->
              <button v-if="canUpdateStatus(p)" @click="changeStatus(p)" class="btn-icon" title="Update Status">
                <i class="fas fa-arrow-right"></i>
              </button>
              <!-- Selesai (Completed) -->
              <button v-if="canComplete(p)" @click="completeProject(p.id)" class="btn-icon success" title="Selesai">
                <i class="fas fa-check-circle"></i>
              </button>
              <!-- Hapus -->
              <button v-if="canDelete(p)" @click="deleteProject(p.id)" class="btn-icon danger" title="Hapus">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../axios'

const user = JSON.parse(localStorage.getItem('user') || '{}')
const userRole = user.role || ''

const projects = ref([])
const searchQuery = ref('')

const canCreate = computed(() => ['admin_project', 'super_admin'].includes(userRole))

const filteredProjects = computed(() => {
  if (!searchQuery.value) return projects.value
  const q = searchQuery.value.toLowerCase()
  return projects.value.filter(p =>
    p.no_po?.toLowerCase().includes(q) ||
    p.no_resi?.toLowerCase().includes(q) ||
    p.client?.name?.toLowerCase().includes(q)
  )
})

const fetchProjects = async () => {
  try {
    const res = await axios.get('/projects')
    projects.value = res.data.data || []
  } catch (e) {
    alert('Gagal memuat data: ' + e.message)
  }
}

// Helper: cek apakah bisa edit
const canEdit = (p) => {
  if (userRole === 'super_admin') return true
  if (userRole !== 'admin_project') return false
  return ['draft', 'confirmed'].includes(p.status)
}

// Helper: cek apakah bisa buat pengajuan biaya (harus ada delivery task)
const canCreateFuel = (p) => {
  return p.delivery_tasks?.length > 0 && ['confirmed', 'on_delivery'].includes(p.status)
}

// Helper: cek apakah bisa update status (hanya maju satu langkah)
const canUpdateStatus = (p) => {
  if (userRole === 'super_admin') return true
  if (!['admin_project', 'admin_transport'].includes(userRole)) return false
  const statuses = ['draft', 'confirmed', 'on_delivery', 'completed', 'cancelled']
  const currentIdx = statuses.indexOf(p.status)
  return currentIdx < statuses.length - 1 && currentIdx !== -1
}

// Helper: cek apakah bisa complete (completed)
const canComplete = (p) => {
  return p.status === 'on_delivery' && ['admin_project', 'super_admin'].includes(userRole)
}

// Helper: cek apakah bisa delete
const canDelete = (p) => {
  if (userRole === 'super_admin') return true
  if (userRole !== 'admin_project') return false
  return ['draft', 'confirmed'].includes(p.status)
}

// === UPDATE STATUS ===
const changeStatus = async (p) => {
  const statuses = ['draft', 'confirmed', 'on_delivery', 'completed', 'cancelled']
  const currentIdx = statuses.indexOf(p.status)
  const nextStatus = statuses[currentIdx + 1]
  if (!nextStatus) return alert('Tidak ada status berikutnya')
  if (!confirm(`Ubah status menjadi "${nextStatus}"?`)) return
  try {
    await axios.patch(`/projects/${p.id}/status`, { status: nextStatus })
    alert('Status berhasil diupdate!')
    fetchProjects()
  } catch (e) {
    alert('Gagal update status: ' + e.message)
  }
}

// === COMPLETE PROJECT ===
const completeProject = async (id) => {
  if (!confirm('Yakin project ini sudah selesai?')) return
  try {
    await axios.patch(`/projects/${id}/status`, { status: 'completed' })
    alert('Project selesai!')
    fetchProjects()
  } catch (e) {
    alert('Gagal: ' + e.message)
  }
}

// === BUAT PENGAJUAN BIAYA ===
const createFuelExpense = (projectId) => {
  // Redirect atau buka modal untuk memilih delivery task
  alert('Fungsi buat pengajuan biaya (pilih delivery task)')
}

// === LAINNYA ===
const formatNumber = (val) => {
  if (!val) return '0'
  return val.toLocaleString('id-ID')
}

const openForm = (p) => { /* buka modal form */ }
const viewDetail = (id) => { /* navigasi ke detail */ }
const deleteProject = async (id) => {
  if (!confirm('Yakin hapus?')) return
  try {
    await axios.delete(`/projects/${id}`)
    alert('Project dihapus!')
    fetchProjects()
  } catch (e) {
    alert('Gagal hapus: ' + e.message)
  }
}

onMounted(fetchProjects)
</script>