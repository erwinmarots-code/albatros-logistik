<template>
  <div class="module-container" id="invoice-print-area">
    <div class="module-header">
      <h2><i class="fas fa-file-invoice"></i> Detail Invoice</h2>
      <p class="module-subtitle">
        Invoice: <strong>{{ invoice?.invoice_number }}</strong>
        <span :class="'status-badge-' + invoice?.status">{{ invoice?.status }}</span>
      </p>
    </div>

    <div class="toolbar no-print">
      <button @click="$emit('back')" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</button>
      <button @click="printInvoice" class="btn-print"><i class="fas fa-print"></i> Cetak Invoice</button>
      <button @click="saveInvoice" class="btn-save"><i class="fas fa-save"></i> Simpan Perubahan</button>
    </div>

    <div class="info-section" v-if="invoice">
      <div class="info-grid">
        <div><strong>No. Resi:</strong> {{ shippingProject?.resi_number }}</div>
        <div><strong>No. PO:</strong> {{ shippingProject?.no_po || '-' }}</div>
        <div><strong>Client:</strong> {{ shippingProject?.client?.name }}</div>
        <div><strong>Kiriman Via:</strong> {{ shippingProject?.shipping_method }}</div>
        <div><strong>Status Project:</strong> {{ shippingProject?.status }}</div>
        <div><strong>Tanggal Invoice:</strong> {{ invoice.invoice_date }}</div>
      </div>
    </div>

    <div class="table-wrapper" v-if="invoice">
      <table class="modern-table">
        <thead>
          <tr>
            <th>No PO</th>
            <th>No Resi</th>
            <th>Penerima & Alamat</th>
            <th>Kota Asal</th>
            <th>Kota Tujuan</th>
            <th>Koli</th>
            <th>Berat (Kg)</th>
            <th>Tanggal Tugas</th>
            <th>Tanggal Selesai</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ shippingProject?.no_po || '-' }}</td>
            <td><strong>{{ shippingProject?.resi_number }}</strong></td>
            <td>
              {{ shippingProject?.receiver_name }}<br>
              <small>{{ shippingProject?.receiver_address }}</small>
            </td>
            <td>{{ origin || '-' }}</td>
            <td>{{ destination || '-' }}</td>
            <td>{{ shippingProject?.collie || 0 }}</td>
            <td>{{ shippingProject?.weight_kg || 0 }}</td>
            <td>{{ firstTaskDate || '-' }}</td>
            <td>{{ lastTaskDate || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="form-container no-print" style="margin-top: 24px;">
      <h3><i class="fas fa-pencil-alt"></i> Detail Tagihan</h3>
      <div class="form-group">
        <label><i class="fas fa-comment"></i> Keterangan</label>
        <textarea v-model="form.notes" rows="3" placeholder="Catatan tambahan untuk invoice"></textarea>
      </div>
      <div class="form-group">
        <label><i class="fas fa-money-bill-wave"></i> Nilai Tagihan (Rp) <span class="required">*</span></label>
        <input v-model="form.total_amount" type="number" step="0.01" required placeholder="0" />
      </div>
      <div class="form-group">
        <label><i class="fas fa-calendar-alt"></i> Jatuh Tempo</label>
        <input v-model="form.due_date" type="date" />
      </div>
      <div class="form-group">
        <label><i class="fas fa-circle"></i> Status</label>
        <select v-model="form.status">
          <option value="draft">Draft</option>
          <option value="sent">Sent</option>
          <option value="paid">Paid</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <div class="invoice-footer print-only">
      <p>Terima kasih telah menggunakan jasa Albatros Makassar.</p>
      <p style="font-size: 12px; color: #6c757d;">Invoice ini dibuat secara otomatis oleh sistem.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from '../axios'

const props = defineProps({
  invoiceId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['back', 'updated'])

const invoice = ref(null)
const loading = ref(true)
const form = reactive({
  notes: '',
  total_amount: '',
  due_date: '',
  status: 'draft',
})

const shippingProject = computed(() => invoice.value?.shipping_project)

const firstTask = computed(() => {
  const tasks = shippingProject.value?.delivery_tasks || []
  return tasks.length > 0 ? tasks[0] : null
})

const origin = computed(() => firstTask.value?.origin || null)
const destination = computed(() => firstTask.value?.destination || null)

const firstTaskDate = computed(() => {
  const tasks = shippingProject.value?.delivery_tasks || []
  if (tasks.length === 0) return null
  return tasks[0]?.task_date || null
})

const lastTaskDate = computed(() => {
  const tasks = shippingProject.value?.delivery_tasks || []
  if (tasks.length === 0) return null
  const completed = tasks.filter(t => t.status === 'completed' || t.status === 'done')
  if (completed.length > 0) {
    return completed[completed.length - 1]?.task_date || null
  }
  return tasks[tasks.length - 1]?.task_date || null
})

const fetchDetail = async () => {
  loading.value = true
  try {
    const res = await axios.get(`/invoices/${props.invoiceId}/detail`)
    invoice.value = res.data.data
    form.notes = invoice.value.notes || ''
    form.total_amount = invoice.value.total_amount || ''
    form.due_date = invoice.value.due_date || ''
    form.status = invoice.value.status || 'draft'
  } catch (error) {
    alert('Gagal memuat detail invoice: ' + error.message)
  } finally {
    loading.value = false
  }
}

const saveInvoice = async () => {
  try {
    await axios.put(`/invoices/${props.invoiceId}`, {
      notes: form.notes,
      total_amount: form.total_amount,
      due_date: form.due_date || null,
      status: form.status,
    })
    alert('Invoice berhasil diperbarui!')
    emit('updated')
    await fetchDetail()
  } catch (error) {
    alert('Gagal menyimpan: ' + (error.response?.data?.message || error.message))
  }
}

const printInvoice = () => {
  window.print()
}

onMounted(() => {
  fetchDetail()
})
</script>

<style scoped>
.module-container { max-width: 1200px; margin: 0 auto; }
.module-header { margin-bottom: 20px; }
.module-header h2 { font-size: 24px; color: #0d2b45; display: flex; align-items: center; gap: 10px; }
.module-header h2 i { color: #1a4a7a; }
.module-subtitle { color: #6c757d; font-size: 14px; margin-top: 2px; }

.toolbar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.btn-back, .btn-print, .btn-save { padding: 10px 22px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.btn-back { background: #6c757d; color: white; }
.btn-back:hover { background: #5a6268; transform: translateY(-2px); }
.btn-print { background: #17a2b8; color: white; }
.btn-print:hover { background: #138496; transform: translateY(-2px); }
.btn-save { background: linear-gradient(135deg, #28a745, #218838); color: white; }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.3); }

.info-section { background: #f8fafc; border-radius: 16px; padding: 16px 20px; margin-bottom: 20px; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
.info-grid div { font-size: 14px; color: #2d3748; }

.table-wrapper { overflow-x: auto; background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 4px 0; margin-bottom: 20px; }
.modern-table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 700px; }
.modern-table thead { background: #f8fafc; border-bottom: 2px solid #e9ecef; }
.modern-table thead th { padding: 14px 16px; text-align: left; font-weight: 700; color: #2d3748; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
.modern-table thead th i { margin-right: 6px; color: #1a4a7a; }
.modern-table tbody tr { border-bottom: 1px solid #f1f3f5; transition: background 0.15s ease; }
.modern-table tbody tr:hover { background: #f8fafc; }
.modern-table tbody td { padding: 12px 16px; color: #2d3748; vertical-align: middle; }

.form-container { background: white; border-radius: 16px; padding: 24px 28px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin: 16px 0; }
.form-container h3 { font-size: 20px; color: #0d2b45; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #e9ecef; padding-bottom: 12px; margin-bottom: 20px; }
.form-container h3 i { color: #1a4a7a; }
.form-group { display: grid; grid-template-columns: 160px 1fr; align-items: center; gap: 14px; margin-bottom: 14px; }
.form-group label { font-weight: 600; color: #2d3748; text-align: right; display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
.form-group label i { color: #1a4a7a; width: 20px; text-align: center; }
.required { color: #dc3545; margin-left: 2px; }
.form-group input, .form-group select, .form-group textarea { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: border-color 0.2s; background: white; width: 100%; box-sizing: border-box; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #1a4a7a; box-shadow: 0 0 0 3px rgba(26,74,122,0.12); }

.status-badge-draft { background: #6c757d; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-sent { background: #17a2b8; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-paid { background: #28a745; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge-cancelled { background: #dc3545; color: white; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

.invoice-footer { text-align: center; padding: 20px 0; border-top: 2px solid #e9ecef; margin-top: 20px; }
.print-only { display: none; }

.loading { text-align: center; padding: 40px; font-size: 18px; color: #1a4a7a; }
.loading i { margin-right: 12px; }

@media (max-width: 768px) {
  .form-group { grid-template-columns: 1fr; gap: 4px; }
  .form-group label { text-align: left; justify-content: flex-start; }
  .modern-table { font-size: 13px; min-width: 500px; }
  .modern-table thead th, .modern-table tbody td { padding: 10px 12px; }
}
</style>