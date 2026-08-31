<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="project-form">
      <div class="form-header">
        <h3><i class="fas fa-folder-plus"></i> {{ isEdit ? 'Edit Project' : 'Buat Project Baru' }}</h3>
        <button @click="$emit('close')" class="btn-close">&times;</button>
      </div>

      <form @submit.prevent="submitForm" class="form-body">
        <!-- Client & Branch -->
        <div class="form-grid">
          <div class="form-group">
            <label>Client <span class="required">*</span></label>
            <select v-model="form.client_id" required>
              <option value="">Pilih Client</option>
              <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Cabang <span class="required">*</span></label>
            <select v-model="form.branch_id" required>
              <option value="">Pilih Cabang</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.code }} - {{ b.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>No. PO</label>
            <input v-model="form.no_po" placeholder="PO-001" />
          </div>
          <div class="form-group">
            <label>No. Resi</label>
            <input v-model="form.no_resi" placeholder="Auto generate jika kosong" />
            <small class="hint">Kosongkan untuk auto generate</small>
          </div>
        </div>

        <!-- Pengirim -->
        <div class="form-section">
          <h4><i class="fas fa-user"></i> Data Pengirim</h4>
          <div class="form-grid">
            <div class="form-group full-width">
              <label>Nama <span class="required">*</span></label>
              <input v-model="form.sender_name" required />
            </div>
            <div class="form-group full-width">
              <label>Alamat <span class="required">*</span></label>
              <textarea v-model="form.sender_address" rows="2" required></textarea>
            </div>
            <div class="form-group">
              <label>Telepon <span class="required">*</span></label>
              <input v-model="form.sender_phone" required />
            </div>
          </div>
        </div>

        <!-- Penerima -->
        <div class="form-section">
          <h4><i class="fas fa-user-check"></i> Data Penerima</h4>
          <div class="form-grid">
            <div class="form-group full-width">
              <label>Nama <span class="required">*</span></label>
              <input v-model="form.receiver_name" required />
            </div>
            <div class="form-group full-width">
              <label>Alamat <span class="required">*</span></label>
              <textarea v-model="form.receiver_address" rows="2" required></textarea>
            </div>
            <div class="form-group">
              <label>Telepon <span class="required">*</span></label>
              <input v-model="form.receiver_phone" required />
            </div>
          </div>
        </div>

        <!-- Barang -->
        <div class="form-section">
          <h4><i class="fas fa-box"></i> Detail Barang</h4>
          <div class="form-grid">
            <div class="form-group full-width">
              <label>Deskripsi</label>
              <textarea v-model="form.goods_description" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>Berat (kg)</label>
              <input v-model.number="form.weight_kg" type="number" step="0.01" />
            </div>
            <div class="form-group">
              <label>Koli</label>
              <input v-model.number="form.collie" type="number" min="0" />
            </div>
            <div class="form-group">
              <label>Volumetrik</label>
              <input v-model="form.volumetric" placeholder="80x40x30" />
            </div>
            <div class="form-group">
              <label>Nilai</label>
              <input v-model.number="form.goods_value" type="number" step="0.01" />
            </div>
          </div>
        </div>

        <!-- Pengiriman -->
        <div class="form-section">
          <h4><i class="fas fa-truck"></i> Pengiriman</h4>
          <div class="form-grid">
            <div class="form-group">
              <label>Metode <span class="required">*</span></label>
              <select v-model="form.shipping_method" required>
                <option value="darat">Darat</option>
                <option value="udara">Udara</option>
                <option value="laut">Laut</option>
              </select>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status">
                <option value="draft">Draft</option>
                <option value="confirmed">Confirmed</option>
                <option value="on_delivery">On Delivery</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="form-group full-width">
              <label>Catatan</label>
              <textarea v-model="form.notes" rows="2"></textarea>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save"><i class="fas fa-save"></i> {{ isEdit ? 'Update' : 'Simpan' }}</button>
          <button type="button" @click="$emit('close')" class="btn-cancel">Batal</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from '../axios'

export default {
  props: {
    projectId: {
      type: Number,
      default: null
    }
  },
  emits: ['close', 'saved'],
  data() {
    return {
      loading: false,
      clients: [],
      branches: [],
      isEdit: false,
      form: {
        client_id: '',
        branch_id: '',
        no_po: '',
        no_resi: '',
        sender_name: '',
        sender_address: '',
        sender_phone: '',
        receiver_name: '',
        receiver_address: '',
        receiver_phone: '',
        goods_description: '',
        weight_kg: null,
        collie: null,
        volumetric: '',
        goods_value: null,
        shipping_method: 'darat',
        status: 'draft',
        notes: ''
      }
    }
  },
  methods: {
    async loadClients() {
      try {
        const res = await axios.get('/clients')
        this.clients = res.data.data || []
      } catch (e) { console.error(e) }
    },
    async loadBranches() {
      try {
        const res = await axios.get('/branches')
        this.branches = res.data.data || []
      } catch (e) { console.error(e) }
    },
    async loadProject() {
      if (!this.projectId) return
      this.isEdit = true
      try {
        const res = await axios.get(`/projects/${this.projectId}`)
        const data = res.data.data
        Object.keys(this.form).forEach(key => {
          if (data[key] !== undefined) this.form[key] = data[key]
        })
      } catch (e) {
        console.error(e)
        alert('Gagal memuat data project')
      }
    },
    async submitForm() {
      this.loading = true
      try {
        const payload = { ...this.form }
        if (this.isEdit) {
          await axios.put(`/projects/${this.projectId}`, payload)
          alert('Project berhasil diupdate!')
        } else {
          await axios.post('/projects', payload)
          alert('Project berhasil dibuat!')
        }
        this.$emit('saved')
        this.$emit('close')
      } catch (e) {
        alert('Gagal: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loading = false
      }
    }
  },
  mounted() {
    this.loadClients()
    this.loadBranches()
    if (this.projectId) this.loadProject()
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.4);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 20px;
}
.project-form {
  background: white;
  border-radius: 20px;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 24px 80px rgba(0,0,0,0.2);
}
.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  background: white;
  border-radius: 20px 20px 0 0;
  z-index: 10;
}
.form-header h3 {
  margin: 0;
  font-size: 20px;
  color: #0d2b45;
  display: flex;
  align-items: center;
  gap: 8px;
}
.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #a0aec0;
  cursor: pointer;
}
.btn-close:hover { color: #e53e3e; }

.form-body { padding: 24px; }
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.form-group { margin-bottom: 4px; }
.form-group.full-width { grid-column: 1 / -1; }
.form-group label {
  display: block;
  font-weight: 600;
  font-size: 14px;
  color: #2d3748;
  margin-bottom: 4px;
}
.form-group .required { color: #e53e3e; margin-left: 2px; }
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: border 0.2s;
  font-family: inherit;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #2b6cb0;
  box-shadow: 0 0 0 3px rgba(43,108,176,0.1);
}
.form-group .hint {
  display: block;
  font-size: 12px;
  color: #a0aec0;
  margin-top: 2px;
}

.form-section {
  margin-top: 20px;
  padding: 16px;
  background: #f7fafc;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}
.form-section h4 {
  margin: 0 0 12px;
  font-size: 16px;
  color: #0d2b45;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}
.btn-save {
  background: #2b6cb0;
  color: white;
  border: none;
  padding: 10px 28px;
  border-radius: 30px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
}
.btn-save:hover { background: #1a4a7a; transform: translateY(-2px); }
.btn-cancel {
  background: #e2e8f0;
  color: #2d3748;
  border: none;
  padding: 10px 28px;
  border-radius: 30px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
}
.btn-cancel:hover { background: #cbd5e0; }

@media (max-width: 600px) {
  .form-grid { grid-template-columns: 1fr; }
}
</style>