<template>
  <div class="form-container">
    <h3><i class="fas fa-file-invoice"></i> Buat Invoice Baru</h3>
    
    <form @submit.prevent="submitInvoice">
      
      <!-- Pilihan Project -->
      <div class="form-group">
        <label>Pilih Project / Resi <span class="required">*</span></label>
        <p class="helper-text">Centang beberapa No Resi/PO yang akan ditagihkan ke satu Client yang sama.</p>
        
        <div class="table-wrapper" v-if="availableProjects.length">
          <table class="select-table">
            <thead>
              <tr>
                <th width="40">Pilih</th>
                <th>No. Resi</th>
                <th>No. PO</th>
                <th>Client</th>
                <th class="text-right">Nilai Tagihan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="proj in availableProjects" :key="proj.id">
                <td class="text-center">
                  <input type="checkbox" :value="proj.id" v-model="selectedProjectIds" />
                </td>
                <td><strong>{{ proj.resi_number || '-' }}</strong></td>
                <td>{{ proj.no_po || '-' }}</td>
                <td>{{ proj.client?.name || '-' }}</td>
                <td class="text-right">{{ formatRupiah(proj.goods_value || 0) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-else class="empty-state">
          <i class="fas fa-inbox"></i> Tidak ada project selesai yang belum di-invoice.
        </div>
      </div>

      <!-- Informasi Tambahan -->
      <div class="form-group">
        <label><i class="fas fa-calendar-alt"></i> Jatuh Tempo</label>
        <input v-model="form.due_date" type="date" />
      </div>
      <div class="form-group">
        <label><i class="fas fa-sticky-note"></i> Catatan</label>
        <textarea v-model="form.notes" placeholder="Opsional: keterangan tambahan untuk client"></textarea>
      </div>

      <!-- Ringkasan & Tombol Simpan -->
      <div class="summary-bar">
        <div class="total-display">
          <span>Total Tagihan:</span>
          <span class="total-amount">{{ formatRupiah(totalAmount) }}</span>
        </div>
        <div class="form-actions">
          <button type="button" @click="$emit('close')" class="btn-cancel">Batal</button>
          <button type="submit" class="btn-save" :disabled="selectedProjectIds.length === 0">
            <i class="fas fa-save"></i> Buat Invoice
          </button>
        </div>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import axios from '../axios'
import { formatRupiah } from '../utils/helpers'

const emit = defineEmits(['close', 'created'])

// ===== STATE =====
const availableProjects = ref([])
const selectedProjectIds = ref([])
const loading = ref(false)

const form = reactive({
  due_date: '',
  notes: ''
})

// ===== COMPUTED =====
const totalAmount = computed(() => {
  // Jumlahkan goods_value dari project yang dipilih
  return availableProjects.value
    .filter(p => selectedProjectIds.value.includes(p.id))
    .reduce((sum, p) => sum + (p.goods_value || 0), 0)
})

// ===== FETCH PROJECTS YANG TERSEDIA =====
const fetchProjects = async () => {
  try {
    const res = await axios.get('/invoices/available-projects')
    availableProjects.value = res.data.data || []
  } catch (error) {
    alert('Gagal memuat daftar project: ' + error.message)
  }
}

// ===== SUBMIT =====
const submitInvoice = async () => {
  if (selectedProjectIds.value.length === 0) {
    alert('Silakan pilih minimal 1 project / resi.')
    return
  }

  loading.value = true
  try {
    const payload = {
      shipping_project_ids: selectedProjectIds.value,
      due_date: form.due_date || null,
      notes: form.notes || null,
    }
    await axios.post('/invoices', payload)
    alert('Invoice berhasil dibuat!')
    emit('created')
  } catch (error) {
    alert('Gagal membuat invoice: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

// ===== MOUNTED =====
onMounted(() => {
  // Set default jatuh tempo 14 hari dari sekarang
  const now = new Date()
  now.setDate(now.getDate() + 14)
  form.due_date = now.toISOString().split('T')[0]
  fetchProjects()
})
</script>

<style scoped>
.form-container {
  background: white;
  border-radius: 16px;
  padding: 24px 28px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
  margin: 16px 0 24px;
}
.form-container h3 {
  font-size: 20px;
  color: #0d2b45;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 2px solid #e9ecef;
  padding-bottom: 12px;
  margin-bottom: 20px;
}
.form-container h3 i { color: #1a4a7a; }

.form-group { margin-bottom: 18px; }
.form-group label {
  font-weight: 600;
  color: #2d3748;
  display: block;
  margin-bottom: 6px;
}
.required { color: #dc3545; }
.helper-text { color: #6c757d; font-size: 13px; margin-bottom: 10px; }

/* TABLE */
.table-wrapper {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.select-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.select-table thead {
  background: #f8fafc;
  border-bottom: 2px solid #e9ecef;
}
.select-table th, .select-table td {
  padding: 10px 14px;
  text-align: left;
  border-bottom: 1px solid #f1f3f5;
}
.select-table th { font-weight: 600; font-size: 12px; text-transform: uppercase; color: #6c757d; }
.select-table tbody tr:hover { background: #f8fafc; }
.select-table input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #1a4a7a;
}
.text-right { text-align: right; }
.text-center { text-align: center; }

.empty-state {
  text-align: center;
  padding: 30px 0;
  color: #6c757d;
  border: 1px dashed #e2e8f0;
  border-radius: 10px;
}
.empty-state i { font-size: 30px; display: block; margin-bottom: 10px; }

/* FORM INPUT */
.form-group input[type="date"],
.form-group textarea {
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
  background: white;
  width: 100%;
  box-sizing: border-box;
}
.form-group input:focus, .form-group textarea:focus {
  outline: none;
  border-color: #1a4a7a;
  box-shadow: 0 0 0 3px rgba(26,74,122,0.12);
}

/* SUMMARY BAR */
.summary-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 2px solid #e9ecef;
  margin-top: 10px;
  flex-wrap: wrap;
  gap: 12px;
}
.total-display {
  font-size: 16px;
  font-weight: 600;
  color: #2d3748;
}
.total-amount {
  font-size: 22px;
  color: #0d2b45;
  margin-left: 8px;
}

/* BUTTONS */
.btn-save, .btn-cancel {
  padding: 10px 28px;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}
.btn-save { background: #1a4a7a; color: white; }
.btn-save:hover { background: #0d2b45; transform: translateY(-2px); }
.btn-save:disabled { background: #94a3b8; cursor: not-allowed; transform: none; }
.btn-cancel { background: #6c757d; color: white; }
.btn-cancel:hover { background: #5a6268; transform: translateY(-2px); }

@media (max-width: 768px) {
  .summary-bar { flex-direction: column; align-items: stretch; }
  .form-actions { display: flex; gap: 8px; justify-content: flex-end; }
}
</style>