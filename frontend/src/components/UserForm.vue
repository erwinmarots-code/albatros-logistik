<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <div class="modal-header">
        <h3><i class="fas fa-user-plus"></i> {{ mode === 'edit' ? 'Edit User' : 'Tambah User' }}</h3>
        <button class="btn-close" @click="$emit('close')">&times;</button>
      </div>

      <form @submit.prevent="saveUser" class="modal-form">
        <div class="form-grid">
          <!-- Nama -->
          <div class="form-group">
            <label>Nama Lengkap <span class="required">*</span></label>
            <input v-model="form.name" type="text" class="form-control" required />
          </div>

          <!-- Email -->
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input v-model="form.email" type="email" class="form-control" required />
          </div>

          <!-- Password -->
          <div class="form-group">
            <label>Password <span v-if="mode === 'add'" class="required">*</span></label>
            <input
              v-model="form.password"
              type="password"
              class="form-control"
              :required="mode === 'add'"
              :placeholder="mode === 'edit' ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter'"
            />
          </div>

          <div class="form-group">
            <label>Konfirmasi Password <span v-if="mode === 'add'" class="required">*</span></label>
            <input
              v-model="form.password_confirmation"
              type="password"
              class="form-control"
              :required="mode === 'add'"
            />
          </div>

          <!-- Role -->
          <div class="form-group">
            <label>Role <span class="required">*</span></label>
            <select v-model="form.role" class="form-control" required>
              <option value="">Pilih Role</option>
              <option value="super_admin">Super Admin</option>
              <option value="admin_project">Admin Project</option>
              <option value="admin_transport">Admin Transport</option>
              <option value="admin_finance">Admin Finance</option>
              <option value="branch_admin">Branch Admin</option>
              <option value="staff">Staff</option>
            </select>
          </div>

          <!-- Branch -->
          <div class="form-group">
            <label>Cabang</label>
            <select v-model="form.branch_id" class="form-control">
              <option value="">Pilih Cabang (Opsional)</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.code }} - {{ b.name }}</option>
            </select>
          </div>
        </div>

        <!-- 🔥 TAMPILKAN ERROR VALIDASI (dari backend atau frontend) -->
        <div v-if="validationErrors" class="error-box">
          <ul>
            <li v-for="(err, key) in validationErrors" :key="key">
              <strong>{{ key }}:</strong> {{ err.join(', ') }}
            </li>
          </ul>
        </div>
        <div v-if="frontendError" class="error-box">
          {{ frontendError }}
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">Batal</button>
          <button type="submit" class="btn btn-success" :disabled="loading">
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  name: 'UserForm',
  props: {
    mode: {
      type: String,
      default: 'add' // 'add' or 'edit'
    },
    userData: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'saved'],
  data() {
    return {
      loading: false,
      validationErrors: null,
      frontendError: null,
      branches: [],
      form: {
        id: null,
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: '',
        branch_id: '',
      }
    }
  },
  mounted() {
    this.fetchBranches()
    if (this.mode === 'edit' && this.userData) {
      this.form = {
        id: this.userData.id,
        name: this.userData.name,
        email: this.userData.email,
        password: '',
        password_confirmation: '',
        role: this.userData.role,
        branch_id: this.userData.branch_id || '',
      }
    }
  },
  methods: {
    async fetchBranches() {
      try {
        const res = await axios.get('/branches')
        this.branches = res.data.data || []
      } catch (e) {
        console.error('Error fetching branches:', e)
      }
    },

    async saveUser() {
      // Reset error
      this.validationErrors = null
      this.frontendError = null

      // 🔥 Validasi frontend
      if (this.mode === 'add') {
        if (!this.form.password || this.form.password.length < 6) {
          this.frontendError = 'Password harus minimal 6 karakter'
          return
        }
        if (this.form.password !== this.form.password_confirmation) {
          this.frontendError = 'Konfirmasi password tidak cocok'
          return
        }
      }

      // Jika mode edit dan password diisi, validasi juga
      if (this.mode === 'edit' && this.form.password) {
        if (this.form.password.length < 6) {
          this.frontendError = 'Password harus minimal 6 karakter'
          return
        }
        if (this.form.password !== this.form.password_confirmation) {
          this.frontendError = 'Konfirmasi password tidak cocok'
          return
        }
      }

      this.loading = true

      // 🔥 Build payload
      const payload = {
        name: this.form.name,
        email: this.form.email,
        role: this.form.role,
        branch_id: this.form.branch_id || null,
      }

      // Kirim password hanya jika diisi
      if (this.form.password) {
        payload.password = this.form.password
        payload.password_confirmation = this.form.password_confirmation
      }

      // 🔥 Debug: cek payload di console
      console.log('Payload yang dikirim:', payload)

      try {
        let response
        if (this.mode === 'edit') {
          response = await axios.put(`/users/${this.form.id}`, payload)
        } else {
          response = await axios.post('/users', payload)
        }

        this.$emit('saved', response.data)
        alert(response.data.message || 'Data berhasil disimpan')
      } catch (e) {
        if (e.response && e.response.status === 422) {
          this.validationErrors = e.response.data.errors
          console.error('Validation errors:', this.validationErrors)
        } else {
          alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
        }
        console.error('Error saving user:', e)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
/* ========================================================== */
/* MODAL (sama seperti sebelumnya) */
/* ========================================================== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}
.modal-card {
  background: white;
  border-radius: 20px;
  padding: 28px 32px;
  width: 100%;
  max-width: 640px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.modal-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0;
}
.modal-header h3 i {
  color: #2b6cb0;
  margin-right: 8px;
}
.btn-close {
  background: transparent;
  border: none;
  font-size: 28px;
  line-height: 1;
  cursor: pointer;
  color: #6b7280;
}
.btn-close:hover { color: #dc2626; }

.modal-form .form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 24px;
}
.modal-form .form-group {
  display: flex;
  flex-direction: column;
}
.modal-form .form-group.full-width {
  grid-column: 1 / -1;
}
.modal-form .form-group label {
  font-weight: 600;
  font-size: 14px;
  color: #2d3748;
  margin-bottom: 4px;
}
.modal-form .form-group .required {
  color: #dc2626;
}
.modal-form .form-control {
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
  width: 100%;
}
.modal-form .form-control:focus {
  outline: none;
  border-color: #2b6cb0;
  box-shadow: 0 0 0 3px rgba(43,108,176,0.15);
}
.modal-form .form-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 12px;
  margin-top: 16px;
  justify-content: flex-end;
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
.btn-success {
  background: #22c55e;
  color: white;
}
.btn-success:hover { background: #16a34a; }
.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}
.btn-secondary:hover { background: #cbd5e1; }

.error-box {
  grid-column: 1 / -1;
  padding: 12px 16px;
  background: #fee2e2;
  border: 1px solid #dc2626;
  border-radius: 8px;
  color: #991b1b;
}
.error-box ul { margin: 0; padding-left: 20px; }
.error-box ul li { font-size: 14px; }

@media (max-width: 768px) {
  .modal-form .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>