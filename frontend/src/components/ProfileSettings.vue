<template>
  <div class="profile-container">
    <div class="page-header">
      <h2><i class="fas fa-user-cog"></i> Pengaturan Akun</h2>
    </div>

    <div class="profile-card">
      <h3>Informasi Akun</h3>
      <div class="info-grid">
        <div class="info-item">
          <label>Nama</label>
          <span>{{ user?.name }}</span>
        </div>
        <div class="info-item">
          <label>Email</label>
          <span>{{ user?.email }}</span>
        </div>
        <div class="info-item">
          <label>Role</label>
          <span class="badge" :class="roleBadge">{{ roleLabel }}</span>
        </div>
        <div class="info-item">
          <label>Cabang</label>
          <span>{{ user?.branch?.name || '-' }}</span>
        </div>
      </div>
    </div>

    <div class="profile-card">
      <h3>Ganti Password</h3>
      <form @submit.prevent="updatePassword">
        <div class="form-group">
          <label>Password Lama <span class="required">*</span></label>
          <input
            v-model="form.current_password"
            type="password"
            class="form-control"
            required
            placeholder="Masukkan password lama"
          />
        </div>
        <div class="form-group">
          <label>Password Baru <span class="required">*</span></label>
          <input
            v-model="form.new_password"
            type="password"
            class="form-control"
            required
            placeholder="Minimal 6 karakter"
          />
        </div>
        <div class="form-group">
          <label>Konfirmasi Password Baru <span class="required">*</span></label>
          <input
            v-model="form.new_password_confirmation"
            type="password"
            class="form-control"
            required
            placeholder="Ulangi password baru"
          />
        </div>
        <div v-if="errorMessage" class="error-box">
          {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="success-box">
          {{ successMessage }}
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-success" :disabled="loading">
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            {{ loading ? 'Menyimpan...' : 'Update Password' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  name: 'ProfileSettings',
  data() {
    return {
      loading: false,
      errorMessage: '',
      successMessage: '',
      form: {
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
      },
    }
  },
  computed: {
    user() {
      return JSON.parse(localStorage.getItem('user') || '{}')
    },
    roleLabel() {
      const map = {
        super_admin: 'Super Admin',
        admin_finance: 'Admin Keuangan',
        admin_transport: 'Admin Transport',
        admin_project: 'Admin Project',
        branch_admin: 'Admin Cabang',
        staff: 'Staff',
      }
      return map[this.user?.role] || this.user?.role
    },
    roleBadge() {
      const map = {
        super_admin: 'badge-danger',
        admin_finance: 'badge-info',
        admin_transport: 'badge-warning',
        admin_project: 'badge-primary',
        branch_admin: 'badge-secondary',
        staff: 'badge-success',
      }
      return map[this.user?.role] || 'badge-secondary'
    },
  },
  methods: {
    async updatePassword() {
      this.loading = true
      this.errorMessage = ''
      this.successMessage = ''

      try {
        await axios.post('/update-password', this.form)
        this.successMessage = 'Password berhasil diubah!'
        this.form = {
          current_password: '',
          new_password: '',
          new_password_confirmation: '',
        }
      } catch (e) {
        if (e.response?.status === 400) {
          this.errorMessage = e.response?.data?.message || 'Password lama tidak sesuai.'
        } else if (e.response?.status === 422) {
          const errors = e.response?.data?.errors
          if (errors) {
            this.errorMessage = Object.values(errors).flat().join(', ')
          } else {
            this.errorMessage = 'Validasi gagal. Periksa input Anda.'
          }
        } else {
          this.errorMessage = e.response?.data?.message || 'Gagal update password.'
        }
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.profile-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 16px;
}

.page-header {
  margin-bottom: 24px;
}
.page-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0;
}
.page-header h2 i {
  color: #2b6cb0;
  margin-right: 8px;
}

.profile-card {
  background: white;
  border-radius: 16px;
  padding: 24px 28px;
  margin-bottom: 24px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.profile-card h3 {
  font-size: 18px;
  font-weight: 600;
  color: #0d2b45;
  margin: 0 0 16px 0;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 12px;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 24px;
}
.info-item label {
  display: block;
  font-weight: 600;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 2px;
}
.info-item span {
  font-size: 16px;
  font-weight: 500;
  color: #1a202c;
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
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}
.form-control:focus {
  outline: none;
  border-color: #2b6cb0;
  box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.15);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 8px;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 24px;
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
.btn-success:hover {
  background: #16a34a;
  transform: translateY(-2px);
}
.btn-success:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.badge {
  display: inline-block;
  padding: 2px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}
.badge-info {
  background: #dbeafe;
  color: #1e40af;
}
.badge-warning {
  background: #fef3c7;
  color: #92400e;
}
.badge-primary {
  background: #dbeafe;
  color: #1e40af;
}
.badge-secondary {
  background: #e2e8f0;
  color: #475569;
}
.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.error-box {
  padding: 12px 16px;
  background: #fee2e2;
  border: 1px solid #dc2626;
  border-radius: 8px;
  color: #991b1b;
  margin-bottom: 12px;
}
.success-box {
  padding: 12px 16px;
  background: #d1fae5;
  border: 1px solid #22c55e;
  border-radius: 8px;
  color: #065f46;
  margin-bottom: 12px;
}

@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>