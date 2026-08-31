<template>
  <div class="recent-card">
    <h3>Transaksi Terakhir</h3>
    <div class="table-wrapper">
      <table class="modern-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Nominal</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in transactions" :key="item.id">
            <td>{{ item.transaction_date }}</td>
            <td>{{ item.category }}</td>
            <td>{{ formatRupiah(item.amount) }}</td>
            <td>
              <span :class="'status-badge-' + item.type">
                {{ item.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
              </span>
            </td>
          </tr>
          <tr v-if="!transactions.length">
            <td colspan="4" style="text-align:center; color:#6b7280;">Belum ada transaksi.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '../../axios'
import { formatRupiah } from '../../utils/helpers'

const transactions = ref([])

const fetchRecent = async () => {
  try {
    const res = await axios.get('/dashboard/recent-transactions')
    transactions.value = res.data.data || []
  } catch (error) {
    console.error('Gagal memuat transaksi terakhir:', error)
  }
}

onMounted(fetchRecent)
</script>

<style scoped>
.recent-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  margin-top: 20px;
}
.recent-card h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 16px 0;
}
.status-badge-income {
  background: #22c55e;
  color: white;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 12px;
}
.status-badge-expense {
  background: #ef4444;
  color: white;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 12px;
}
</style>