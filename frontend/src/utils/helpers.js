// Format Rupiah
export const formatRupiah = (number) => {
  if (!number && number !== 0) return 'Rp 0'
  return 'Rp ' + Number(number).toLocaleString('id-ID')
}

// Status Mapping ke Bahasa Indonesia
export const statusMap = {
  // Vehicle
  available: 'Tersedia',
  maintenance: 'Perawatan',
  in_use: 'Digunakan',
  retired: 'Pensiun',
  // Driver
  active: 'Aktif',
  inactive: 'Tidak Aktif',
  // Maintenance Schedule
  scheduled: 'Terjadwal',
  done: 'Selesai',
  cancelled: 'Batal',
  // Maintenance Request
  pending: 'Menunggu',
  approved: 'Disetujui',
  rejected: 'Ditolak',
  // Fuel Expense
  pending: 'Menunggu',
  approved: 'Disetujui',
  rejected: 'Ditolak',
  // Shipping Project
  draft: 'Draft',
  confirmed: 'Dikonfirmasi',
  completed: 'Selesai',
  cancelled: 'Batal',
  // Delivery Task
  planned: 'Direncanakan',
  ongoing: 'Berjalan',
  completed: 'Selesai',
  cancelled: 'Batal',
}

// Service Type Mapping
export const serviceTypeMap = {
  oil_change: 'Ganti Oli',
  tire_replacement: 'Ganti Ban',
  sparepart: 'Sparepart',
  general: 'Service Umum',
}

// Urgency Mapping
export const urgencyMap = {
  low: 'Rendah',
  medium: 'Sedang',
  high: 'Tinggi',
}

// Shipping Method Mapping
export const shippingMethodMap = {
  darat: 'Darat',
  udara: 'Udara',
}

// Fuel Type Mapping
export const fuelTypeMap = {
  bensin: 'Bensin',
  diesel: 'Diesel',
  electric: 'Listrik',
}