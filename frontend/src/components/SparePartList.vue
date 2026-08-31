<template>
  <div class="spare-part-container">
    <!-- Header -->
    <div class="page-header">
      <h2><i class="fas fa-boxes"></i> Inventory Spare Part</h2>
      <div class="header-actions">
        <!-- 🔥 TOMBOL EXPORT -->
        <button
          v-if="canExport"
          class="btn btn-outline-export"
          @click="handleExport"
          :disabled="isExporting"
        >
          <i class="fas fa-file-excel"></i>
          {{ isExporting ? 'Mengekspor...' : 'Export Excel' }}
        </button>
        <button class="btn btn-primary" @click="openForm">
          <i class="fas fa-plus-circle"></i> Tambah Spare Part
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <input
        v-model="search"
        type="text"
        class="form-control-sm"
        placeholder="Cari kode / nama..."
        @input="fetchData"
      />
      <select v-model="filterStatus" class="form-control-sm" @change="fetchData">
        <option value="">Semua Status</option>
        <option value="tersedia">Tersedia</option>
        <option value="sedang_dipakai">Sedang Dipakai</option>
        <option value="stok_habis">Stok Habis</option>
        <option value="perlu_restok">Perlu Restok</option>
        <option v-if="isSuperAdmin" value="rusak_tidak_layak">Rusak / Tidak Layak</option>
      </select>
      <select v-model="filterCategory" class="form-control-sm" @change="fetchData">
        <option value="">Semua Kategori</option>
        <option value="sekali_pakai">Sekali Pakai</option>
        <option value="berulang">Bisa Dipakai Berulang</option>
      </select>
    </div>

    <!-- Tabel -->
    <div class="table-card">
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama</th>
              <th>Kategori</th>
              <th>Stok</th>
              <th>Min Stok</th>
              <th>Harga</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center">Memuat...</td>
            </tr>
            <tr v-else-if="!data.length">
              <td colspan="8" class="text-center">Tidak ada data</td>
            </tr>
            <tr v-for="item in data" :key="item.id">
              <td><strong>{{ item.code }}</strong></td>
              <td>{{ item.name }}</td>
              <td>{{ item.category === 'sekali_pakai' ? 'Sekali Pakai' : 'Bisa Berulang' }}</td>
              <td :class="{ 'text-danger': item.stock <= item.min_stock }">{{ item.stock }}</td>
              <td>{{ item.min_stock }}</td>
              <td>{{ formatCurrency(item.price) }}</td>
              <td><span class="badge" :class="statusBadge(item.status)">{{ statusLabel(item.status) }}</span></td>
              <td class="text-center">
                <!-- Restok -->
                <button class="btn-icon" title="Tambah Stok" @click="openRestock(item)">
                  <i class="fas fa-plus-circle"></i>
                </button>
                <!-- History -->
                <button class="btn-icon" title="History" @click="viewHistory(item.id)">
                  <i class="fas fa-history"></i>
                </button>
                <!-- Edit -->
                <button class="btn-icon" title="Edit" @click="editData(item)">
                  <i class="fas fa-edit"></i>
                </button>
                <!-- Mark Unusable (Hanya Super Admin / Admin Transport) -->
                <button
                  v-if="canMarkUnusable"
                  class="btn-icon danger"
                  title="Tandai Rusak/Tidak Layak"
                  @click="markUnusable(item.id)"
                >
                  <i class="fas fa-skull"></i>
                </button>
                <!-- Hapus (Super Admin only) -->
                <button
                  v-if="isSuperAdmin"
                  class="btn-icon danger"
                  title="Hapus"
                  @click="deleteData(item.id)"
                >
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Tambah / Edit -->
    <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class="fas fa-box"></i> {{ formMode === 'edit' ? 'Edit Spare Part' : 'Tambah Spare Part' }}</h3>
          <button class="btn-close" @click="closeForm">&times;</button>
        </div>
        <form @submit.prevent="saveData">
          <div class="form-grid">
            <div class="form-group">
              <label>Kode <span class="required">*</span></label>
              <input v-model="form.code" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Nama <span class="required">*</span></label>
              <input v-model="form.name" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Kategori <span class="required">*</span></label>
              <select v-model="form.category" class="form-control" required>
                <option value="sekali_pakai">Sekali Pakai</option>
                <option value="berulang">Bisa Dipakai Berulang</option>
              </select>
            </div>
            <div class="form-group">
              <label>Satuan</label>
              <input v-model="form.unit" type="text" class="form-control" placeholder="pcs, liter, set" />
            </div>
            <div class="form-group">
              <label>Stok Awal <span class="required">*</span></label>
              <input v-model.number="form.stock" type="number" class="form-control" required min="0" />
            </div>
            <div class="form-group">
              <label>Min Stok</label>
              <input v-model.number="form.min_stock" type="number" class="form-control" min="0" />
            </div>
            <div class="form-group">
              <label>Harga (Rp)</label>
              <input v-model.number="form.price" type="number" class="form-control" min="0" />
            </div>
            <div class="form-group">
              <label>Cabang <span class="required">*</span></label>
              <select v-model="form.branch_id" class="form-control" required>
                <option value="">Pilih Cabang</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </div>
            <div v-if="form.category === 'berulang'" class="form-group">
              <label>Batas Jarak (km)</label>
              <input v-model.number="form.lifespan_km" type="number" class="form-control" min="0" />
            </div>
            <div v-if="form.category === 'berulang'" class="form-group">
              <label>Batas Waktu (bulan)</label>
              <input v-model.number="form.lifespan_months" type="number" class="form-control" min="0" />
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeForm">Batal</button>
            <button type="submit" class="btn btn-success" :disabled="loadingSubmit">
              <i v-if="loadingSubmit" class="fas fa-spinner fa-spin"></i>
              {{ loadingSubmit ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Restock -->
    <div v-if="showRestock" class="modal-overlay" @click.self="closeRestock">
      <div class="modal-card modal-sm">
        <div class="modal-header">
          <h3><i class="fas fa-plus-circle"></i> Tambah Stok</h3>
          <button class="btn-close" @click="closeRestock">&times;</button>
        </div>
        <form @submit.prevent="saveRestock">
          <div class="form-group">
            <label>Spare Part</label>
            <p><strong>{{ selectedSparePart?.name }}</strong> (Stok saat ini: {{ selectedSparePart?.stock }})</p>
          </div>
          <div class="form-group">
            <label>Jumlah Tambahan <span class="required">*</span></label>
            <input v-model.number="restockQuantity" type="number" class="form-control" required min="1" />
          </div>
          <div class="form-group">
            <label>Catatan</label>
            <input v-model="restockNotes" type="text" class="form-control" />
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeRestock">Batal</button>
            <button type="submit" class="btn btn-success" :disabled="loadingSubmit">
              <i v-if="loadingSubmit" class="fas fa-spinner fa-spin"></i>
              {{ loadingSubmit ? 'Menyimpan...' : 'Tambah' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal History -->
    <div v-if="showHistory" class="modal-overlay" @click.self="closeHistory">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class="fas fa-history"></i> History Pergerakan Stok</h3>
          <button class="btn-close" @click="closeHistory">&times;</button>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Catatan</th>
                <th>User</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="historyLoading">
                <td colspan="5" class="text-center">Memuat...</td>
              </tr>
              <tr v-else-if="!historyData.length">
                <td colspan="5" class="text-center">Belum ada history</td>
              </tr>
              <tr v-for="mov in historyData" :key="mov.id">
                <td>{{ formatDate(mov.created_at) }}</td>
                <td><span class="badge" :class="movementBadge(mov.movement_type)">{{ movementLabel(mov.movement_type) }}</span></td>
                <td>{{ mov.quantity }}</td>
                <td>{{ mov.notes || '-' }}</td>
                <td>{{ mov.creator?.name || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="closeHistory">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios'
import { useExport } from '../composables/useExport'

export default {
  name: 'SparePartList',
  data() {
    return {
      data: [],
      branches: [],
      loading: false,
      search: '',
      filterStatus: '',
      filterCategory: '',
      isSuperAdmin: false,
      canMarkUnusable: false,

      showForm: false,
      formMode: 'add',
      loadingSubmit: false,
      form: {
        id: null,
        code: '',
        name: '',
        category: 'sekali_pakai',
        unit: 'pcs',
        stock: 0,
        min_stock: 0,
        price: 0,
        lifespan_km: null,
        lifespan_months: null,
        branch_id: '',
      },

      showRestock: false,
      selectedSparePart: null,
      restockQuantity: 1,
      restockNotes: '',

      showHistory: false,
      historyData: [],
      historyLoading: false,
    }
  },
  computed: {
    user() {
      return JSON.parse(localStorage.getItem('user') || '{}')
    },
    canExport() {
      const role = this.user?.role
      return ['super_admin', 'admin_transport'].includes(role)
    },
  },
  setup() {
    // 🔥 Composabel untuk export
    const { isExporting, exportData } = useExport('spare-parts')
    return { isExporting, exportData }
  },
  mounted() {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    this.isSuperAdmin = user.role === 'super_admin'
    this.canMarkUnusable = ['super_admin', 'admin_transport'].includes(user.role)
    this.fetchData()
    this.fetchBranches()
  },
  methods: {
    formatCurrency(val) {
      if (!val) return 'Rp 0'
      return 'Rp ' + Number(val).toLocaleString('id-ID')
    },

    formatDate(date) {
      if (!date) return '-'
      const d = new Date(date)
      return String(d.getDate()).padStart(2, '0') + '-' +
             String(d.getMonth() + 1).padStart(2, '0') + '-' +
             d.getFullYear() + ' ' +
             String(d.getHours()).padStart(2, '0') + ':' +
             String(d.getMinutes()).padStart(2, '0')
    },

    statusLabel(status) {
      const map = {
        tersedia: 'Tersedia',
        sedang_dipakai: 'Sedang Dipakai',
        stok_habis: 'Stok Habis',
        perlu_restok: 'Perlu Restok',
        rusak_tidak_layak: 'Rusak / Tidak Layak'
      }
      return map[status] || status
    },

    statusBadge(status) {
      const map = {
        tersedia: 'badge-success',
        sedang_dipakai: 'badge-info',
        stok_habis: 'badge-danger',
        perlu_restok: 'badge-warning',
        rusak_tidak_layak: 'badge-secondary'
      }
      return map[status] || 'badge-secondary'
    },

    movementLabel(type) {
      const map = {
        masuk: 'Masuk',
        keluar: 'Keluar',
        rusak: 'Rusak',
        koreksi: 'Koreksi'
      }
      return map[type] || type
    },

    movementBadge(type) {
      const map = {
        masuk: 'badge-success',
        keluar: 'badge-info',
        rusak: 'badge-danger',
        koreksi: 'badge-warning'
      }
      return map[type] || 'badge-secondary'
    },

    async fetchData() {
      this.loading = true
      try {
        const params = {
          search: this.search || undefined,
          status: this.filterStatus || undefined,
          category: this.filterCategory || undefined,
        }
        const res = await axios.get('/spare-parts', { params })
        this.data = res.data.data || []
      } catch (e) {
        console.error('Error fetching spare parts:', e)
        alert('Gagal memuat data spare part')
      } finally {
        this.loading = false
      }
    },

    async fetchBranches() {
      try {
        const res = await axios.get('/branches')
        this.branches = res.data.data || []
      } catch (e) {
        console.error('Error fetching branches:', e)
      }
    },

    openForm() {
      this.formMode = 'add'
      this.form = {
        id: null,
        code: '',
        name: '',
        category: 'sekali_pakai',
        unit: 'pcs',
        stock: 0,
        min_stock: 0,
        price: 0,
        lifespan_km: null,
        lifespan_months: null,
        branch_id: '',
      }
      this.showForm = true
    },

    editData(item) {
      this.formMode = 'edit'
      this.form = { ...item }
      this.showForm = true
    },

    closeForm() {
      this.showForm = false
      this.loadingSubmit = false
    },

    async saveData() {
      this.loadingSubmit = true
      try {
        const payload = { ...this.form }
        delete payload.id
        delete payload.created_by
        delete payload.created_at
        delete payload.updated_at
        delete payload.branch
        delete payload.creator
        delete payload.movements

        let response
        if (this.formMode === 'edit') {
          response = await axios.put(`/spare-parts/${this.form.id}`, payload)
        } else {
          response = await axios.post('/spare-parts', payload)
        }
        this.closeForm()
        this.fetchData()
        alert(response.data.message || 'Data berhasil disimpan')
      } catch (e) {
        if (e.response?.status === 422) {
          alert('Validasi gagal: ' + JSON.stringify(e.response.data.errors))
        } else {
          alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
        }
      } finally {
        this.loadingSubmit = false
      }
    },

    openRestock(item) {
      this.selectedSparePart = item
      this.restockQuantity = 1
      this.restockNotes = ''
      this.showRestock = true
    },

    closeRestock() {
      this.showRestock = false
      this.selectedSparePart = null
      this.loadingSubmit = false
    },

    async saveRestock() {
      this.loadingSubmit = true
      try {
        await axios.post(`/spare-parts/${this.selectedSparePart.id}/restock`, {
          quantity: this.restockQuantity,
          notes: this.restockNotes
        })
        this.closeRestock()
        this.fetchData()
        alert('Stok berhasil ditambahkan')
      } catch (e) {
        alert('Gagal menambah stok: ' + (e.response?.data?.message || e.message))
      } finally {
        this.loadingSubmit = false
      }
    },

    async viewHistory(id) {
      this.showHistory = true
      this.historyLoading = true
      try {
        const res = await axios.get(`/spare-parts/${id}/movements`)
        this.historyData = res.data.data || []
      } catch (e) {
        console.error('Error fetching history:', e)
        alert('Gagal memuat history')
      } finally {
        this.historyLoading = false
      }
    },

    closeHistory() {
      this.showHistory = false
      this.historyData = []
    },

    async markUnusable(id) {
      if (!confirm('Yakin menandai spare part ini sebagai rusak/tidak layak? Data akan dihapus dari inventory.')) return
      try {
        await axios.post(`/spare-parts/${id}/mark-unusable`)
        this.fetchData()
        alert('Spare part berhasil ditandai rusak/tidak layak')
      } catch (e) {
        alert('Gagal: ' + (e.response?.data?.message || e.message))
      }
    },

    async deleteData(id) {
      if (!confirm('Yakin hapus spare part ini?')) return
      try {
        await axios.delete(`/spare-parts/${id}`)
        this.fetchData()
        alert('Spare part berhasil dihapus')
      } catch (e) {
        alert('Gagal hapus: ' + (e.response?.data?.message || e.message))
      }
    },

    // ===== 🔥 EXPORT EXCEL =====
    async handleExport() {
      await this.exportData({
        search: this.search || undefined,
        status: this.filterStatus || undefined,
        category: this.filterCategory || undefined,
      })
    },
  }
}
</script>

<style scoped>
/* Tambahkan style untuk tombol export dan header actions */
.spare-part-container { max-width: 1200px; margin: 0 auto; padding: 0 16px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.page-header h2 { font-size: 24px; font-weight: 700; color: #0d2b45; margin: 0; }
.page-header h2 i { color: #2b6cb0; margin-right: 8px; }
.header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; }
.btn-primary { background: #2b6cb0; color: white; }
.btn-primary:hover { background: #1a4a7a; transform: translateY(-2px); }
.btn-success { background: #22c55e; color: white; }
.btn-success:hover { background: #16a34a; }
.btn-secondary { background: #e2e8f0; color: #2d3748; }
.btn-secondary:hover { background: #cbd5e1; }
.btn-close { background: transparent; border: none; font-size: 28px; line-height: 1; cursor: pointer; color: #6b7280; }
.btn-close:hover { color: #dc2626; }
.btn-outline-export {
  background: white;
  color: #2d3748;
  border: 1.5px solid #2b6cb0;
  padding: 8px 18px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: 0.2s;
}
.btn-outline-export:hover {
  background: #2b6cb0;
  color: white;
  transform: translateY(-2px);
}
.btn-outline-export:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.filter-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.filter-bar .form-control-sm { padding: 6px 12px; border: 1.5px solid #e2e8f0; border-radius: 6px; font-size: 14px; background: white; }

.table-card { background: white; border-radius: 16px; padding: 16px 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }
.table-wrapper { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table thead { background: #f7fafc; border-bottom: 2px solid #e2e8f0; }
.table th { padding: 10px 12px; text-align: left; font-weight: 600; color: #2d3748; white-space: nowrap; }
.table td { padding: 10px 12px; border-bottom: 1px solid #f1f3f5; vertical-align: middle; }
.table tbody tr:hover { background: #f7fafc; }
.text-center { text-align: center; }
.text-danger { color: #dc2626; font-weight: 700; }

.badge { display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-info { background: #dbeafe; color: #1e40af; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-secondary { background: #e2e8f0; color: #475569; }

.btn-icon { background: transparent; border: none; padding: 4px 8px; color: #4a5568; cursor: pointer; transition: 0.2s; font-size: 16px; }
.btn-icon:hover { color: #2b6cb0; }
.btn-icon.danger:hover { color: #dc2626; }

.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
.modal-card { background: white; border-radius: 20px; padding: 28px 32px; width: 100%; max-width: 780px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.modal-sm { max-width: 500px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal-header h3 { font-size: 20px; font-weight: 700; color: #0d2b45; margin: 0; }
.modal-header h3 i { color: #2b6cb0; margin-right: 8px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 20px; }
.form-group { display: flex; flex-direction: column; }
.form-group label { font-weight: 600; font-size: 14px; color: #2d3748; margin-bottom: 4px; }
.form-group .required { color: #dc2626; }
.form-control { padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; width: 100%; }
.form-control:focus { outline: none; border-color: #2b6cb0; box-shadow: 0 0 0 3px rgba(43,108,176,0.15); }
.form-actions { grid-column: 1 / -1; display: flex; gap: 12px; margin-top: 16px; justify-content: flex-end; }

@media (max-width: 768px) {
  .form-grid { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; align-items: stretch; }
  .header-actions { justify-content: stretch; flex-direction: column; }
  .header-actions .btn { justify-content: center; }
}
</style>