<template>
  <div class="module-container">
    <div class="module-header">
      <h2><i class="fas fa-file-alt"></i> Laporan Pengiriman</h2>
      <p class="module-subtitle">Ringkasan pengiriman yang sudah selesai</p>
    </div>

    <div class="toolbar">
      <button @click="fetchReport" class="btn-refresh"><i class="fas fa-sync-alt"></i> Muat Data</button>
    </div>

    <div class="table-wrapper" v-if="items && items.length">
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
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in items" :key="index">
            <td>{{ item.no_po }}</td>
            <td><strong>{{ item.resi_number }}</strong></td>
            <td>
              <div><strong>{{ item.receiver_name }}</strong></div>
              <div class="address">{{ item.receiver_address }}</div>
            </td>
            <td>{{ item.origin }}</td>
            <td>{{ item.destination }}</td>
            <td>{{ item.collie }}</td>
            <td>{{ item.weight_kg }}</td>
            <td>{{ item.task_date }}</td>
            <td>{{ item.completed_date }}</td>
            <td>
              <input 
                v-model="item.notes" 
                @change="saveNotes(index, item.notes)" 
                placeholder="Isi keterangan" 
                class="notes-input"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="empty-message"><i class="fas fa-inbox"></i> Belum ada data pengiriman selesai.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../axios'

const items = ref([])

const fetchReport = async () => {
  try {
    const res = await axios.get('/api/delivery-report')
    // Ambil data, dan jika ada notes yang tersimpan di localStorage atau database, kita ambil
    // Untuk sementara, kita simpan notes di localStorage agar tidak hilang saat refresh
    const savedNotes = JSON.parse(localStorage.getItem('delivery_notes') || '{}')
    const data = res.data.data || []
    data.forEach(item => {
      item.notes = savedNotes[item.resi_number] || ''
    })
    items.value = data
  } catch (error) {
    alert('Gagal memuat data: ' + error.message)
  }
}

const saveNotes = (index, notes) => {
  const item = items.value[index]
  if (item) {
    const savedNotes = JSON.parse(localStorage.getItem('delivery_notes') || '{}')
    savedNotes[item.resi_number] = notes
    localStorage.setItem('delivery_notes', JSON.stringify(savedNotes))
  }
}

onMounted(fetchReport)
</script>

<style scoped>
/* Gaya seperti komponen lainnya */
.module-container { max-width: 1200px; margin: 0 auto; }
.module-header { margin-bottom: 20px; }
.module-header h2 { font-size: 24px; color: #0d2b45; display: flex; align-items: center; gap: 10px; }
.module-header h2 i { color: #1a4a7a; }
.module-subtitle { color: #6c757d; font-size: 14px; margin-top: 2px; }

.toolbar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.btn-refresh { padding: 10px 22px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s; background: linear-gradient(135deg, #17a2b8, #138496); color: white; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.btn-refresh:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(23,162,184,0.3); }

.table-wrapper { overflow-x: auto; background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 4px 0; }
.modern-table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 800px; }
.modern-table thead { background: #f8fafc; border-bottom: 2px solid #e9ecef; }
.modern-table thead th { padding: 14px 16px; text-align: left; font-weight: 700; color: #2d3748; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
.modern-table thead th i { margin-right: 6px; color: #1a4a7a; }
.modern-table tbody tr { border-bottom: 1px solid #f1f3f5; transition: background 0.15s ease; }
.modern-table tbody tr:hover { background: #f8fafc; }
.modern-table tbody td { padding: 12px 16px; color: #2d3748; vertical-align: middle; }
.modern-table tbody td:first-child { font-weight: 600; color: #6c757d; width: 40px; text-align: center; }

.address { font-size: 0.85em; color: #6c757d; }
.notes-input { width: 100%; padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
.notes-input:focus { outline: none; border-color: #1a4a7a; box-shadow: 0 0 0 2px rgba(26,74,122,0.1); }

.empty-message { text-align: center; padding: 40px 20px; color: #6c757d; font-size: 16px; background: #f8f9fa; border-radius: 16px; }
.empty-message i { font-size: 40px; display: block; margin-bottom: 12px; color: #dee2e6; }

@media (max-width: 768px) {
  .modern-table { font-size: 13px; min-width: 500px; }
  .modern-table thead th, .modern-table tbody td { padding: 10px 12px; }
}
</style>