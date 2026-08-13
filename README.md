# 🚛 Albatros Makassar - Sistem Manajemen Logistik

Sistem manajemen logistik untuk perusahaan Albatros Makassar.

## Fitur
- Manajemen Kendaraan, Driver, Client
- Pengajuan Perawatan & Approve/Reject
- Project Pengantaran & Tugas
- Pengajuan Biaya Operasional
- Dashboard Keuangan & Invoice
- Multi-role (Admin PO, Admin Transport, Admin Finance, Super Admin)

## Teknologi
- Backend: Laravel 12 + Sanctum
- Frontend: Vue 3 + Vite
- Database: MySQL

## Cara Instalasi
1. Clone repository
2. `composer install` di folder `backend`
3. `npm install` di folder `frontend`
4. Konfigurasi `.env`
5. `php artisan migrate --seed`
6. `php artisan serve` & `npm run dev`