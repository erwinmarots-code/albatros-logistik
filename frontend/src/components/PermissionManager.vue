<template>
  <div class="permission-container">
    <div class="page-header">
      <h2><i class="fas fa-lock"></i> Manajemen Akses (Permission)</h2>
      <p class="subtitle">Atur menu apa saja yang dapat diakses oleh setiap role.</p>
    </div>

    <div v-if="loading" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i> Memuat data...
    </div>

    <div v-else>
      <div class="role-selector">
        <label>Pilih Role:</label>
        <select v-model="selectedRole" @change="loadRolePermissions" class="form-control">
          <option v-for="role in roles" :key="role" :value="role">
            {{ roleLabels[role] || role }}
          </option>
        </select>
      </div>

      <div class="permission-grid">
        <div v-for="group in groupedPermissions" :key="group.group" class="permission-group">
          <h4>{{ group.group }}</h4>
          <div class="permission-items">
            <label
              v-for="perm in group.items"
              :key="perm.id"
              class="permission-item"
            >
              <input
                type="checkbox"
                :value="perm.id"
                v-model="selectedPermissions"
              />
              <span>{{ perm.label }}</span>
            </label>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button class="btn btn-success" @click="savePermissions" :disabled="saving">
          <i v-if="saving" class="fas fa-spinner fa-spin"></i>
          {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  name: 'PermissionManager',
  data() {
    return {
      roles: ['super_admin', 'admin_project', 'admin_transport', 'admin_finance', 'branch_admin', 'staff'],
      roleLabels: {
        super_admin: 'Super Admin',
        admin_project: 'Admin Project',
        admin_transport: 'Admin Transport',
        admin_finance: 'Admin Finance',
        branch_admin: 'Branch Admin',
        staff: 'Staff',
      },
      selectedRole: 'admin_project',
      allPermissions: [],
      selectedPermissions: [],
      loading: false,
      saving: false,
    }
  },
  computed: {
    groupedPermissions() {
      const groups = {}
      this.allPermissions.forEach(perm => {
        if (!groups[perm.group]) groups[perm.group] = []
        groups[perm.group].push(perm)
      })
      return Object.keys(groups).map(group => ({
        group,
        items: groups[group],
      }))
    },
  },
  mounted() {
    this.loadPermissions()
  },
  methods: {
    async loadPermissions() {
      this.loading = true
      try {
        const res = await axios.get('/permissions')
        this.allPermissions = res.data.data || []
        this.loadRolePermissions()
      } catch (e) {
        alert('Gagal memuat data permission: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loading = false
      }
    },

    async loadRolePermissions() {
      if (!this.selectedRole) return
      try {
        const res = await axios.get(`/permissions/role/${this.selectedRole}`)
        const permNames = res.data.data || []
        this.selectedPermissions = this.allPermissions
          .filter(p => permNames.includes(p.name))
          .map(p => p.id)
      } catch (e) {
        alert('Gagal memuat permission role: ' + (e.response?.data?.message || e.message))
      }
    },

    async savePermissions() {
      this.saving = true
      try {
        const permNames = this.allPermissions
          .filter(p => this.selectedPermissions.includes(p.id))
          .map(p => p.name)

        await axios.put(`/permissions/role/${this.selectedRole}`, {
          permissions: permNames,
        })
        alert('Perubahan berhasil disimpan!')
      } catch (e) {
        if (e.response && e.response.data && e.response.data.errors) {
          const errMsg = Object.values(e.response.data.errors).flat().join('\n')
          alert('Gagal menyimpan: ' + errMsg)
        } else {
          alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
        }
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<style scoped>
.permission-container { max-width: 900px; margin: 0 auto; padding: 20px 16px; }
.page-header { margin-bottom: 24px; }
.page-header h2 { font-size: 24px; font-weight: 700; color: #0d2b45; margin: 0 0 4px 0; }
.page-header h2 i { color: #2b6cb0; margin-right: 8px; }
.subtitle { color: #6b7280; font-size: 14px; margin: 0; }

.role-selector { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.role-selector label { font-weight: 600; font-size: 14px; color: #2d3748; }
.role-selector .form-control { padding: 6px 12px; border: 1.5px solid #e2e8f0; border-radius: 6px; font-size: 14px; background: white; min-width: 180px; }

.permission-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px 32px; background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.permission-group h4 { font-size: 16px; font-weight: 700; color: #0d2b45; margin: 0 0 12px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; }
.permission-items { display: flex; flex-direction: column; gap: 8px; }
.permission-item { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; padding: 4px 0; }
.permission-item input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: #2b6cb0; }
.permission-item span { color: #2d3748; }

.form-actions { display: flex; justify-content: flex-end; margin-top: 20px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; }
.btn-success { background: #22c55e; color: white; }
.btn-success:hover { background: #16a34a; }
.btn-success:disabled { opacity: 0.6; cursor: not-allowed; }

.loading-state { text-align: center; padding: 60px 20px; color: #6b7280; font-size: 16px; }
.loading-state i { font-size: 32px; display: block; margin-bottom: 12px; color: #2b6cb0; }

@media (max-width: 768px) { .permission-grid { grid-template-columns: 1fr; } }
</style>