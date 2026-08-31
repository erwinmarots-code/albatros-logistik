<template>
  <div class="landing">
    <!-- NAVBAR: Logo Besar di Tengah -->
    <nav class="landing-nav">
      <div class="container">
        <div class="logo">
          <img
            src="/images/albatros_logo.jpg"
            alt="Albatros Logistik"
            class="logo-image"
          />
        </div>
        <button @click="goToLogin" class="btn-login-nav">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <h1>Manajemen Logistik <br><span>Modern &amp; Efisien</span></h1>
          <p>
            Kelola seluruh operasional logistik Albatros Makassar dalam satu
            platform terintegrasi.
          </p>
          <button @click="goToLogin" class="btn-hero">
            <i class="fas fa-rocket"></i> Mulai Sekarang
          </button>
        </div>
        <div class="hero-image">
          <img
            src="/images/landing_page.png"
            alt="Ilustrasi Logistik"
            class="hero-illustration"
          />
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section class="features">
      <div class="container">
        <h2>Fitur Unggulan</h2>
        <p class="section-subtitle">
          Semua yang Anda butuhkan untuk mengelola logistik dengan mudah
        </p>
        <div class="features-grid">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="feature-card"
          >
            <div class="feature-icon">
              <i :class="feature.icon"></i>
            </div>
            <h3>{{ feature.title }}</h3>
            <p>{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta">
      <div class="container">
        <div class="cta-content">
          <h2>Siap Mengelola Logistik Lebih Baik?</h2>
          <p>Gabung sekarang dan rasakan kemudahan mengelola armada dan pengiriman.</p>
          <button @click="goToLogin" class="btn-cta">
            <i class="fas fa-arrow-right"></i> Mulai Sekarang
          </button>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <img
              src="/images/albatros_logo.jpg"
              alt="Albatros Logistik"
              class="footer-logo"
            />
            <span>Albatros Logistik</span>
          </div>
          <span class="footer-copy">&copy; 2026 Albatros Logistik. All rights reserved.</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'LandingPage',
  data() {
    return {
      features: [
        {
          title: 'Manajemen Kendaraan',
          description: 'Kelola seluruh armada kendaraan dengan mudah, dari status hingga riwayat perawatan.',
          icon: 'fas fa-truck',
        },
        {
          title: 'Tugas Pengantaran',
          description: 'Atur penugasan driver dan kendaraan untuk setiap pengiriman dengan cepat.',
          icon: 'fas fa-tasks',
        },
        {
          title: 'Pengajuan Biaya',
          description: 'Ajukan dan setujui biaya operasional secara terintegrasi dengan keuangan.',
          icon: 'fas fa-coins',
        },
        {
          title: 'Dashboard Keuangan',
          description: 'Pantau pemasukan dan pengeluaran secara real-time dengan grafik interaktif.',
          icon: 'fas fa-chart-pie',
        },
        {
          title: 'Invoice & Tagihan',
          description: 'Buat invoice otomatis dari project yang selesai dan lacak status pembayaran.',
          icon: 'fas fa-file-invoice',
        },
        {
          title: 'History Perawatan',
          description: 'Catat riwayat perawatan kendaraan dan verifikasi penyelesaiannya.',
          icon: 'fas fa-tools',
        },
      ],
    }
  },
  methods: {
    goToLogin() {
      this.$router.push('/login')
    },
  },
}
</script>

<style scoped>
/* ========================================================== */
/* GLOBAL */
/* ========================================================== */
.landing {
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  color: #1a202c;
  background: #ffffff;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 24px;
}

/* ========================================================== */
/* NAVBAR – LOGO 3x LEBAR & JELAS */
/* ========================================================== */
.landing-nav {
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 20px 0; /* padding diperbesar agar logo tidak mentok */
  position: sticky;
  top: 0;
  z-index: 100;
  backdrop-filter: blur(8px);
  background: rgba(255, 255, 255, 0.92);
}

.landing-nav .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  display: flex;
  align-items: center;
}

/* 🔥 LOGO 3 KALI LIPAT LEBIH BESAR */
.logo-image {
  height: 144px;  /* 3x dari 48px */
  width: auto;
  object-fit: contain;
  max-width: 100%;
}

.btn-login-nav {
  background: #2b6cb0;
  color: white;
  border: none;
  padding: 12px 32px; /* tombol diperbesar proporsional */
  border-radius: 30px;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.btn-login-nav:hover {
  background: #1a4a7a;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
}

/* ========================================================== */
/* HERO (tetap) */
/* ========================================================== */
.hero {
  padding: 70px 0 80px;
  background: linear-gradient(135deg, #f0f7ff 0%, #e6f0fa 100%);
}

.hero .container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 50px;
  align-items: center;
}

.hero-content h1 {
  font-size: 46px;
  font-weight: 800;
  line-height: 1.15;
  color: #0d2b45;
  margin: 0 0 16px 0;
}

.hero-content h1 span {
  color: #2b6cb0;
}

.hero-content p {
  font-size: 18px;
  color: #4a5568;
  line-height: 1.7;
  margin: 0 0 28px 0;
  max-width: 500px;
}

.btn-hero {
  background: #2b6cb0;
  color: white;
  border: none;
  padding: 14px 36px;
  border-radius: 40px;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: all 0.25s ease;
}

.btn-hero:hover {
  background: #1a4a7a;
  transform: translateY(-3px);
  box-shadow: 0 8px 28px rgba(43, 108, 176, 0.35);
}

.hero-image {
  display: flex;
  justify-content: center;
  align-items: center;
}

.hero-illustration {
  max-width: 100%;
  height: auto;
  max-height: 420px;
  object-fit: contain;
  border-radius: 16px;
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.08);
}

/* ========================================================== */
/* FEATURES */
/* ========================================================== */
.features {
  padding: 70px 0 80px;
  background: #ffffff;
}

.features h2 {
  text-align: center;
  font-size: 34px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0 0 8px 0;
}

.section-subtitle {
  text-align: center;
  font-size: 18px;
  color: #4a5568;
  margin: 0 0 44px 0;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
}

.feature-card {
  background: #f7fafc;
  padding: 32px 24px;
  border-radius: 16px;
  text-align: center;
  transition: all 0.25s ease;
  border: 1px solid transparent;
}

.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.06);
  border-color: #e2e8f0;
}

.feature-icon {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  background: linear-gradient(135deg, #2b6cb0, #4299e1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 26px;
  margin-bottom: 16px;
}

.feature-card h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0d2b45;
  margin: 0 0 8px 0;
}

.feature-card p {
  color: #4a5568;
  font-size: 15px;
  line-height: 1.6;
  margin: 0;
}

/* ========================================================== */
/* CTA */
/* ========================================================== */
.cta {
  padding: 70px 0 80px;
  background: linear-gradient(135deg, #0d2b45 0%, #1a4a7a 100%);
}

.cta-content {
  text-align: center;
  max-width: 700px;
  margin: 0 auto;
}

.cta-content h2 {
  font-size: 34px;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 12px 0;
}

.cta-content p {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  margin: 0 0 32px 0;
}

.btn-cta {
  background: #ffffff;
  color: #0d2b45;
  border: none;
  padding: 14px 40px;
  border-radius: 40px;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: all 0.25s ease;
}

.btn-cta:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 32px rgba(255, 255, 255, 0.2);
}

/* ========================================================== */
/* FOOTER – LOGO 3x LEBIH BESAR */
/* ========================================================== */
.footer {
  padding: 28px 0;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 18px;
  font-weight: 600;
  color: #0d2b45;
}

/* 🔥 FOOTER LOGO 3x LEBIH BESAR */
.footer-logo {
  height: 84px;  /* 3x dari 28px */
  width: auto;
  object-fit: contain;
}

.footer-copy {
  font-size: 14px;
  color: #6b7280;
}

/* ========================================================== */
/* RESPONSIVE – MENYESUAIKAN UKURAN LOGO DI LAYAR KECIL */
/* ========================================================== */
@media (max-width: 768px) {
  .logo-image {
    height: 80px; /* tetap besar, tapi lebih proporsional di mobile */
  }

  .footer-logo {
    height: 50px;
  }

  .hero .container {
    grid-template-columns: 1fr;
    text-align: center;
    gap: 30px;
  }

  .hero-content h1 {
    font-size: 32px;
  }

  .hero-content p {
    max-width: 100%;
    font-size: 16px;
  }

  .hero-illustration {
    max-height: 250px;
  }

  .hero-image {
    order: -1;
  }

  .features-grid {
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .cta-content h2 {
    font-size: 26px;
  }

  .footer-content {
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .logo-image {
    height: 60px;
  }

  .footer-logo {
    height: 40px;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .hero-content h1 {
    font-size: 26px;
  }

  .btn-login-nav {
    padding: 8px 20px;
    font-size: 13px;
  }

  .btn-hero {
    padding: 12px 28px;
    font-size: 14px;
  }

  .btn-cta {
    padding: 12px 28px;
    font-size: 14px;
  }
}
</style>