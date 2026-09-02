<template>
  <div class="landing-page">
    <nav class="navbar">
      <div class="container">
        <div class="logo">
          <!-- 🔥 LOGO GAMBAR SAJA, TANPA TEKS -->
          <img src="/images/albatros_logo.jpg" alt="Albatros Logistik" class="logo-img" />
        </div>
        <div class="nav-actions">
          <button @click="goToLogin" class="btn-login-nav">
            <i class="fas fa-sign-in-alt"></i> Login
          </button>
        </div>
      </div>
    </nav>

    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <h1>Manajemen Logistik <br /><span>Modern & Efisien</span></h1>
          <p class="hero-description">
            Kelola seluruh operasional logistik Albatros Makassar dalam satu platform terintegrasi.
            Mulai dari kendaraan, driver, pengiriman, hingga keuangan.
          </p>
          <div class="hero-buttons">
            <button @click="goToLogin" class="btn-primary">
              <i class="fas fa-rocket"></i> Mulai Sekarang
            </button>
            <a href="#features" class="btn-outline">
              <i class="fas fa-chevron-down"></i> Lihat Fitur
            </a>
          </div>
        </div>
        <div class="hero-image">
          <img src="/images/landing_page.png" alt="Ilustrasi Logistik" class="hero-img" />
        </div>
      </div>
    </section>

    <section id="features" class="features">
      <div class="container">
        <div class="section-header">
          <h2>Fitur Unggulan</h2>
          <p>Semua yang Anda butuhkan untuk mengelola logistik dengan mudah</p>
        </div>
        <div class="features-grid">
          <div v-for="feature in features" :key="feature.title" class="feature-card">
            <div class="feature-icon" :style="{ background: feature.color }">
              <i :class="feature.icon"></i>
            </div>
            <h3>{{ feature.title }}</h3>
            <p>{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="stats">
      <div class="container">
        <div class="stats-grid">
          <div v-for="stat in stats" :key="stat.label" class="stat-item">
            <span class="stat-number">{{ stat.value }}</span>
            <span class="stat-label">{{ stat.label }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="cta">
      <div class="container">
        <div class="cta-content">
          <h2>Siap Mengelola Logistik Lebih Baik?</h2>
          <p>Gabung sekarang dan rasakan kemudahan mengelola armada dan pengiriman.</p>
          <button @click="goToLogin" class="btn-primary btn-large">
            <i class="fas fa-rocket"></i> Mulai Sekarang
          </button>
        </div>
      </div>
    </section>

    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <i class="fas fa-ship"></i>
            <span>Albatros Logistik</span>
            <p>Sistem Manajemen Logistik &copy; 2026</p>
          </div>
          <div class="footer-links">
            <a href="#">Tentang</a>
            <a href="#">Kebijakan Privasi</a>
            <a href="#">Kontak</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const goToLogin = () => {
  router.push('/login')
}

const features = ref([
  {
    title: 'Manajemen Kendaraan',
    description: 'Kelola seluruh armada kendaraan dengan mudah, dari status hingga riwayat perawatan.',
    icon: 'fas fa-truck',
    color: '#4f46e5'
  },
  {
    title: 'Tugas Pengantaran',
    description: 'Atur penugasan driver dan kendaraan untuk setiap pengiriman dengan cepat.',
    icon: 'fas fa-tasks',
    color: '#0ea5e9'
  },
  {
    title: 'Pengajuan Biaya',
    description: 'Ajukan dan setujui biaya operasional secara terintegrasi dengan keuangan.',
    icon: 'fas fa-coins',
    color: '#eab308'
  },
  {
    title: 'Dashboard Keuangan',
    description: 'Pantau pemasukan dan pengeluaran secara real-time dengan grafik interaktif.',
    icon: 'fas fa-chart-pie',
    color: '#22c55e'
  },
  {
    title: 'Invoice & Tagihan',
    description: 'Buat invoice otomatis dari project yang selesai dan lacak status pembayaran.',
    icon: 'fas fa-file-invoice',
    color: '#dc2626'
  },
  {
    title: 'History Perawatan',
    description: 'Catat riwayat perawatan kendaraan dan verifikasi penyelesaiannya.',
    icon: 'fas fa-history',
    color: '#6b7280'
  }
])

const stats = ref([
  { value: '4+', label: 'Modul Utama' },
  { value: '100%', label: 'Terintegrasi' },
  { value: '24/7', label: 'Akses' },
  { value: 'Siap Pakai', label: 'Cloud-Based' }
])
</script>

<style scoped>
/* ====== RESET & GLOBAL ====== */
.landing-page {
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  color: #1f2937;
  background: #ffffff;
  line-height: 1.6;
}
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* ===== NAVBAR ===== */
.navbar {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  box-shadow: 0 1px 10px rgba(0,0,0,0.05);
  padding: 16px 0;
  position: sticky;
  top: 0;
  z-index: 100;
}
.navbar .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.logo {
  display: flex;
  align-items: center;
}
.logo-img {
  height: 120px;
  width: auto;
  object-fit: contain;
}
.btn-login-nav {
  background: #1a4a7a;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 30px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.btn-login-nav:hover {
  background: #0d2b45;
  transform: translateY(-2px);
}

/* ===== HERO ===== */
.hero {
  padding: 60px 0 80px;
  background: linear-gradient(135deg, #f0f7ff 0%, #e6f0fa 100%);
}
.hero .container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: center;
}
.hero-content h1 {
  font-size: 48px;
  font-weight: 800;
  line-height: 1.2;
  color: #0d2b45;
}
.hero-content h1 span {
  color: #1a4a7a;
}
.hero-description {
  font-size: 18px;
  color: #4a5568;
  margin: 20px 0 32px;
}
.hero-buttons {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}
.btn-primary {
  background: #1a4a7a;
  color: white;
  border: none;
  padding: 14px 32px;
  border-radius: 40px;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  transition: 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.btn-primary:hover {
  background: #0d2b45;
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(26, 74, 122, 0.25);
}
.btn-outline {
  background: transparent;
  color: #1a4a7a;
  border: 2px solid #1a4a7a;
  padding: 12px 28px;
  border-radius: 40px;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  transition: 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}
.btn-outline:hover {
  background: #1a4a7a;
  color: white;
  transform: translateY(-3px);
}
.hero-image {
  display: flex;
  justify-content: center;
  align-items: center;
}
.hero-img {
  max-width: 100%;
  height: auto;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.08);
}

/* ===== FEATURES ===== */
.features {
  padding: 80px 0;
  background: white;
}
.section-header {
  text-align: center;
  margin-bottom: 48px;
}
.section-header h2 {
  font-size: 36px;
  font-weight: 700;
  color: #0d2b45;
  margin-bottom: 8px;
}
.section-header p {
  font-size: 18px;
  color: #6b7280;
}
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px;
}
.feature-card {
  background: #f9fafb;
  padding: 32px 24px;
  border-radius: 16px;
  text-align: center;
  transition: 0.25s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.08);
}
.feature-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  margin-bottom: 16px;
}
.feature-card h3 {
  font-size: 20px;
  font-weight: 700;
  color: #0d2b45;
  margin-bottom: 8px;
}
.feature-card p {
  color: #6b7280;
  font-size: 15px;
}

/* ===== STATS ===== */
.stats {
  background: #0d2b45;
  color: white;
  padding: 60px 0;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  text-align: center;
}
.stat-item {
  display: flex;
  flex-direction: column;
}
.stat-number {
  font-size: 40px;
  font-weight: 800;
  color: #ffd700;
}
.stat-label {
  font-size: 16px;
  opacity: 0.8;
}

/* ===== CTA ===== */
.cta {
  padding: 80px 0;
  background: linear-gradient(135deg, #1a4a7a, #0d2b45);
  color: white;
  text-align: center;
}
.cta-content h2 {
  font-size: 36px;
  font-weight: 700;
  margin-bottom: 12px;
}
.cta-content p {
  font-size: 18px;
  opacity: 0.8;
  margin-bottom: 32px;
}
.btn-large {
  padding: 16px 48px;
  font-size: 20px;
}

/* ===== FOOTER ===== */
.footer {
  background: #f9fafb;
  padding: 40px 0;
  border-top: 1px solid #e5e7eb;
}
.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}
.footer-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}
.footer-brand i {
  font-size: 24px;
  color: #1a4a7a;
}
.footer-brand span {
  font-weight: 700;
  font-size: 18px;
  color: #0d2b45;
}
.footer-brand p {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}
.footer-links {
  display: flex;
  gap: 20px;
}
.footer-links a {
  color: #4a5568;
  text-decoration: none;
  font-size: 14px;
  transition: 0.2s;
}
.footer-links a:hover {
  color: #1a4a7a;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .hero .container {
    grid-template-columns: 1fr;
    text-align: center;
  }
  .hero-buttons { justify-content: center; }
  .hero-content h1 { font-size: 32px; }
  .hero-image { margin-top: 20px; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .footer-content { flex-direction: column; text-align: center; }
}
@media (max-width: 480px) {
  .navbar .container { flex-direction: column; gap: 12px; }
  .btn-login-nav { width: 100%; justify-content: center; }
  .features-grid { grid-template-columns: 1fr; }
  .logo-img { height: 80px; }
}
</style>