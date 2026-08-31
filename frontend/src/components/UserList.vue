<template>
  <div class="user-container">
    <!-- Header -->
    <div class="page-header">
      <h2><i class="fas fa-users-cog"></i> Manajemen Akun</h2>
      <button class="btn btn-primary" @click="openForm">
        <i class="fas fa-plus-circle"></i> Tambah User
      </button>
    </div>

    <!-- Tabel -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-filter">
          <input
            v-model="search"
            type="text"
            class="form-control-sm"
            placeholder="Cari nama / email / role..."
            @input="fetchData"
          />
        </div>
        <span class="table-info">Total: {{ totalItems }}</span>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nama</th>
              <th>Email</th>
              <th>Role</th>
              <th>Cabang</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="6" class="text-center">Memuat...</td></tr>
            <tr v-else-if="!data.length"><td colspan="6" class="text-center">Tidak ada data</td></tr>
            <tr v-for="item in data" :key="item.id">
              <td>{{ item.id }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.email }}</td>
              <td><span class="badge" :class="roleBadge(item.role)">{{ item.role }}</span></td>
              <td>{{ item.branch?.name || '-' }}</td>
              <td class="text-center">
                <button class="btn-icon" title="Edit" @click="editUser(item)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon danger" title="Hapus" @click="deleteUser(item.id)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form (UserForm) -->
    <UserForm
      v-if="showModal"
      :mode="modalMode"
      :user-data="selectedUser"
      @close="closeModal"
      @saved="onSaved"
    />
  </div>
</template>

<script>
import axios from '../axios'
import UserForm from './UserForm.vue'

export default {
  name: 'UserList',
  components: { UserForm },
  data() {
    return {
      data: [],
      loading: false,
      search: '',
      totalItems: 0,
      showModal: false,
      modalMode: 'add',
      selectedUser: null,
    }
  },
  mounted() {
    this.fetchData()
  },
  methods: {
    async fetchData() {
      this.loading = true
      try {
        const params = { search: this.search || undefined }
        const res = await axios.get('/users', { params })
        this.data = res.data.data || []
        this.totalItems = this.data.length
      } catch (e) {
        console.error('Error fetching users:', e)
        alert('Gagal memuat data user')
      } finally {
        this.loading = false
      }
    },

    openForm() {
      this.modalMode = 'add'
      this.selectedUser = null
      this.showModal = true
    },

    editUser(item) {
      this.modalMode = 'edit'
      this.selectedUser = { ...item }
      this.showModal = true
    },

    closeModal() {
      this.showModal = false
    },

    onSaved() {
      this.closeModal()
      this.fetchData()
    },

    async deleteUser(id) {
      if (!confirm('Yakin hapus user ini?')) return
      try {
        await axios.delete(`/users/${id}`)
        this.fetchData()
      } catch (e) {
        alert('Gagal hapus: ' + (e.response?.data?.message || e.message))
      }
    },

    roleBadge(role) {
      const map = {
        super_admin: 'badge-danger',
        admin_project: 'badge-info',
        admin_transport: 'badge-warning',
        admin_finance: 'badge-success',
        branch_admin: 'badge-primary',
        staff: 'badge-secondary',
      }
      return map[role] || 'badge-secondary'
    }
  }
}
</script>

<style scoped>
.user-container { max-width: 1200px; margin: 0 auto; padding: 0 16px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.page-header h2 { font-size: 24px; font-weight: 700; color: #0d2b45; margin: 0; }
.page-header h2 i { color: #2b6cb0; margin-right: 8px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; }
.btn-primary { background: #2b6cb0; color: white; }
.btn-primary:hover { background: #1a4a7a; transform: translateY(-2px); }
.table-card { background: white; border-radius: 16px; padding: 16px 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden; }
.table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.table-filter { display: flex; gap: 12px; flex-wrap: wrap; }
.table-info { font-size: 14px; color: #6b7280; }
.table-wrapper { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table thead { background: #f7fafc; border-bottom: 2px solid #e2e8f0; }
.table th { padding: 10px 12px; text-align: left; font-weight: 600; color: #2d3748; white-space: nowrap; }
.table td { padding: 10px 12px; border-bottom: 1px solid #f1f3f5; vertical-align: middle; }
.table tbody tr:hover { background: #f7fafc; }
.text-center { text-align: center; }
.badge { display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-info { background: #dbeafe; color: #1e40af; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-primary { background: #c7d2fe; color: #3730a3; }
.badge-secondary { background: #e2e8f0; color: #475569; }
.btn-icon { background: transparent; border: none; padding: 4px 8px; color: #4a5568; cursor: pointer; transition: 0.2s; font-size: 16px; }
.btn-icon:hover { color: #2b6cb0; }
.btn-icon.danger:hover { color: #dc2626; }
</style>