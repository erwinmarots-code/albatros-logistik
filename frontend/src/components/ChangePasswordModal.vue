<template>
  <div v-if="show" class="modal-overlay" @click.self="close">
    <div class="modal-card">
      <div class="modal-header">
        <h3><i class="fas fa-key"></i> Ganti Password</h3>
        <button class="btn-close" @click="close">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div v-if="error && error.general" class="error-box">
          <ul>
            <li v-for="(err, idx) in error.general" :key="idx">{{ err }}</li>
          </ul>
        </div>

        <div class="form-group">
          <label>Password Saat Ini <span class="required">*</span></label>
          <input
            v-model="form.current_password"
            type="password"
            class="form-control"
            required
            placeholder="Masukkan password lama"
          />
          <div v-if="error && error.current_password" class="field-error">
            <span v-for="(err, idx) in error.current_password" :key="idx">{{ err }}</span>
          </div>
        </div>

        <div class="form-group">
          <label>Password Baru <span class="required">*</span></label>
          <input
            v-model="form.password"
            type="password"
            class="form-control"
            required
            placeholder="Minimal 6 karakter"
          />
          <div v-if="error && error.password" class="field-error">
            <span v-for="(err, idx) in error.password" :key="idx">{{ err }}</span>
          </div>
        </div>

        <div class="form-group">
          <label>Konfirmasi Password Baru <span class="required">*</span></label>
          <input
            v-model="form.password_confirmation"
            type="password"
            class="form-control"
            required
            placeholder="Ulangi password baru"
          />
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="close">Batal</button>
          <button type="submit" class="btn btn-success" :disabled="loading">
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            {{ loading ? 'Memproses...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useAuth } from '../composables/useAuth'

export default {
  props: {
    show: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['close', 'success'],
  setup(props, { emit }) {
    const { updatePassword, isLoading, error } = useAuth()
    const loading = isLoading

    const form = reactive({
      current_password: '',
      password: '',
      password_confirmation: '',
    })

    const handleSubmit = async () => {
      try {
        await updatePassword(
          form.current_password,
          form.password,
          form.password_confirmation
        )
        // Reset form
        form.current_password = ''
        form.password = ''
        form.password_confirmation = ''
        emit('success', 'Password berhasil diubah!')
        emit('close')
      } catch (e) {
        // Error sudah ditangani di composable
      }
    }

    const close = () => {
      form.current_password = ''
      form.password = ''
      form.password_confirmation = ''
      emit('close')
    }

    return {
      form,
      loading,
      error,
      handleSubmit,
      close,
    }
  },
}
</script>

<style scoped>
/* ====== GAYA MODAL ====== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
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
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
.btn-close:hover {
  color: #dc2626;
}

/* ====== FORM ====== */
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
  transition: border-color 0.2s;
}
.form-control:focus {
  outline: none;
  border-color: #2b6cb0;
  box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.15);
}
.field-error {
  color: #dc2626;
  font-size: 13px;
  margin-top: 4px;
}
.error-box {
  padding: 12px 16px;
  background: #fee2e2;
  border: 1px solid #dc2626;
  border-radius: 8px;
  color: #991b1b;
  margin-bottom: 16px;
}
.error-box ul {
  margin: 0;
  padding-left: 20px;
}
.error-box ul li {
  font-size: 14px;
}

/* ====== BUTTONS ====== */
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 16px;
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
.btn-success:hover {
  background: #16a34a;
}
.btn-success:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}
.btn-secondary:hover {
  background: #cbd5e1;
}
</style>