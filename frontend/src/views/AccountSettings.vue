<template>
  <div class="account-settings">
    <h2><i class="fas fa-user-cog"></i> Pengaturan Akun</h2>
    
    <!-- Ganti Password -->
    <div class="card">
      <h3>Ganti Password</h3>
      <form @submit.prevent="handleUpdatePassword">
        <div class="form-group">
          <label>Password Saat Ini <span class="required">*</span></label>
          <input 
            v-model="passwordForm.current_password" 
            type="password" 
            class="form-control" 
            required
            placeholder="Masukkan password saat ini"
          />
        </div>
        <div class="form-group">
          <label>Password Baru <span class="required">*</span></label>
          <input 
            v-model="passwordForm.new_password" 
            type="password" 
            class="form-control" 
            required
            placeholder="Minimal 6 karakter"
          />
        </div>
        <div class="form-group">
          <label>Konfirmasi Password Baru <span class="required">*</span></label>
          <input 
            v-model="passwordForm.new_password_confirmation" 
            type="password" 
            class="form-control" 
            required
            placeholder="Ulangi password baru"
          />
        </div>
        <div v-if="passwordError" class="error-message">
          <ul>
            <li v-for="(err, key) in passwordError" :key="key">
              <strong>{{ key }}:</strong> {{ Array.isArray(err) ? err.join(', ') : err }}
            </li>
          </ul>
        </div>
        <button type="submit" class="btn btn-primary" :disabled="loading">
          <i v-if="loading" class="fas fa-spinner fa-spin"></i>
          {{ loading ? 'Menyimpan...' : 'Update Password' }}
        </button>
      </form>
    </div>

    <!-- Manajemen Akses (Hanya Super Admin) -->
    <div v-if="user?.role === 'super_admin'" class="card">
      <h3><i class="fas fa-shield-alt"></i> Manajemen Akses Menu</h3>
      <p class="text-muted">Berikan atau batalkan akses menu untuk setiap role</p>
      
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Menu</th>
              <th v-for="role in roles" :key="role" class="text-center">
                {{ roleLabel(role) }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="group in groupedPermissions" :key="group.group">
              <td colspan="100" class="group-header">{{ group.group }}</td>
            </tr>
            <tr v-for="perm in groupedPermissions.flatMap(g => g.permissions)" :key="perm.id">
              <td>{{ perm.label }}</td>
              <td v-for="role in roles" :key="role" class="text-center">
                <input 
                  type="checkbox" 
                  :checked="isChecked(role, perm.name)"
                  @change="togglePermission(role, perm.name, $event)"
                  :disabled="role === 'super_admin'"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <button 
        class="btn btn-success" 
        @click="savePermissions" 
        :disabled="loadingPermissions"
      >
        <i v-if="loadingPermissions" class="fas fa-spinner fa-spin"></i>
        {{ loadingPermissions ? 'Menyimpan...' : 'Simpan Pengaturan Akses' }}
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import axios from '../axios'

export default {
  name: 'AccountSettings',
  setup() {
    const { user, updatePassword } = useAuth()
    
    const loading = ref(false)
    const loadingPermissions = ref(false)
    const passwordError = ref(null)
    const passwordForm = ref({
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    })

    // Data permission
    const permissions = ref([])
    const rolePermissions = ref({})
    const roles = ['super_admin', 'admin_finance', 'admin_transport', 'admin_project', 'branch_admin', 'staff']

    const groupedPermissions = computed(() => {
      const groups = {}
      permissions.value.forEach(perm => {
        if (!groups[perm.group]) {
          groups[perm.group] = { group: perm.group, permissions: [] }
        }
        groups[perm.group].permissions.push(perm)
      })
      return Object.values(groups)
    })

    const roleLabel = (role) => {
      const labels = {
        super_admin: 'Super Admin',
        admin_finance: 'Admin Keuangan',
        admin_transport: 'Admin Transport',
        admin_project: 'Admin Project',
        branch_admin: 'Admin Cabang',
        staff: 'Staff'
      }
      return labels[role] || role
    }

    const isChecked = (role, permissionName) => {
      return rolePermissions.value[role]?.includes(permissionName) || false
    }

    const loadPermissions = async () => {
      try {
        const [permRes, rolePermRes] = await Promise.all([
          axios.get('/permissions'),
          axios.get('/permissions/role/all')
        ])
        permissions.value = permRes.data.data || []
        rolePermissions.value = rolePermRes.data.data || {}
      } catch (error) {
        console.error('Error loading permissions:', error)
        alert('Gagal memuat data akses')
      }
    }

    const togglePermission = (role, permissionName, event) => {
      if (role === 'super_admin') return
      
      if (!rolePermissions.value[role]) {
        rolePermissions.value[role] = []
      }
      
      if (event.target.checked) {
        if (!rolePermissions.value[role].includes(permissionName)) {
          rolePermissions.value[role].push(permissionName)
        }
      } else {
        rolePermissions.value[role] = rolePermissions.value[role].filter(p => p !== permissionName)
      }
    }

    const savePermissions = async () => {
      loadingPermissions.value = true
      try {
        // Untuk setiap role (kecuali super_admin), kirim permission list
        const payload = {}
        Object.keys(rolePermissions.value).forEach(role => {
          if (role !== 'super_admin') {
            payload[role] = rolePermissions.value[role]
          }
        })
        
        await axios.put('/permissions/role/all', payload)
        alert('Pengaturan akses berhasil disimpan')
      } catch (error) {
        console.error('Error saving permissions:', error)
        alert('Gagal menyimpan pengaturan akses: ' + (error.response?.data?.message || error.message))
      } finally {
        loadingPermissions.value = false
      }
    }

    const handleUpdatePassword = async () => {
      loading.value = true
      passwordError.value = null
      
      const result = await updatePassword(
        passwordForm.value.current_password,
        passwordForm.value.new_password,
        passwordForm.value.new_password_confirmation
      )
      
      if (result.success) {
        alert(result.message)
        passwordForm.value = {
          current_password: '',
          new_password: '',
          new_password_confirmation: '',
        }
      } else {
        passwordError.value = result.errors
      }
      
      loading.value = false
    }

    onMounted(() => {
      if (user.value?.role === 'super_admin') {
        loadPermissions()
      }
    })

    return {
      user,
      loading,
      loadingPermissions,
      passwordForm,
      passwordError,
      permissions,
      rolePermissions,
      roles,
      groupedPermissions,
      roleLabel,
      isChecked,
      togglePermission,
      savePermissions,
      handleUpdatePassword,
    }
  }
}
</script>

<style scoped>
.account-settings {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
}

.account-settings h2 {
  font-size: 24px;
  font-weight: 700;
  color: #0d2b45;
  margin-bottom: 24px;
}
.account-settings h2 i {
  color: #2b6cb0;
  margin-right: 8px;
}

.card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.card h3 {
  font-size: 18px;
  font-weight: 600;
  color: #0d2b45;
  margin: 0 0 16px 0;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 12px;
}
.card h3 i {
  margin-right: 8px;
  color: #2b6cb0;
}

.form-group {
  margin-bottom: 16px;
}
.form-group label {
  display: block;
  font-weight: 600;
  font-size: 14px;
  color: #2d3748;
  margin-bottom: 4px;
}
.form-group .required {
  color: #dc2626;
}
.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
}
.form-control:focus {
  outline: none;
  border-color: #2b6cb0;
  box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.15);
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
}
.btn-primary {
  background: #2b6cb0;
  color: white;
}
.btn-primary:hover {
  background: #1a4a7a;
}
.btn-success {
  background: #22c55e;
  color: white;
}
.btn-success:hover {
  background: #16a34a;
}
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  padding: 12px 16px;
  background: #fee2e2;
  border: 1px solid #dc2626;
  border-radius: 8px;
  color: #991b1b;
  margin-bottom: 16px;
}
.error-message ul {
  margin: 0;
  padding-left: 20px;
}
.error-message ul li {
  font-size: 14px;
}

.text-muted {
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 16px;
}

.table-wrapper {
  overflow-x: auto;
  margin-bottom: 16px;
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
}
.table td {
  padding: 10px 12px;
  border-bottom: 1px solid #f1f3f5;
  vertical-align: middle;
}
.table .text-center {
  text-align: center;
}
.table .group-header {
  font-weight: 700;
  color: #0d2b45;
  background: #f1f5f9;
}

input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}
input[type="checkbox"]:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
</style>