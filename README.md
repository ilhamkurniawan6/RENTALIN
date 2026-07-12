
# Rentalin

Rentalin adalah aplikasi web marketplace penyewaan barang untuk mahasiswa, dengan alur login, dashboard pengguna, fitur tambah barang, mode dual-role, dan dashboard admin. Aplikasi ini dikembangkan sebagai project PHP + MySQL dengan tampilan multi-halaman berbasis HTML, CSS, dan JavaScript vanilla.

## Ringkasan Proyek

Proyek ini menggabungkan beberapa fitur utama:

- Pendaftaran dan login pengguna
- Mode dual-role: Penyewa dan Penyewakan
- Dashboard pengguna untuk melihat penyewaan, barang yang dipublikasikan, dan pengaturan profil
- Form tambah barang dengan validasi dan upload gambar
- Dashboard admin untuk mengelola pengguna, barang, dan transaksi
- Pengujian end-to-end menggunakan Playwright

## Fitur Utama

- Autentikasi pengguna dengan sesi PHP
- Switch role antar mode Penyewa dan Penyewakan
- Halaman utama, browse item, detail item, dashboard, add item, dan admin dashboard
- Integrasi database MySQL/MariaDB melalui PHP mysqli
- Upload avatar dan gambar barang
- Endpoint API internal untuk operasi admin dan cleanup test

## Stack Teknologi

- PHP 8+
- MySQL / MariaDB
- HTML, CSS, JavaScript vanilla
- Playwright untuk pengujian E2E

## Struktur Proyek

- src/pages/home, browse, item-detail, login, register, dashboard, admin-dashboard, add-item
- src/pages/api untuk endpoint backend
- src/services untuk koneksi database, autentikasi, session, dan helper
- src/mock dan src/store untuk data dan state frontend
- tests dan scripts untuk pengujian otomatis

## Persiapan Lokal

1. Pastikan lingkungan Anda sudah memiliki:
   - Apache + PHP
   - MySQL / MariaDB
   - Node.js dan npm

2. Impor database ke MySQL:

```bash
mysql -u root -p < database.sql
```

Jika Anda memperbarui instalasi yang sudah ada, jalankan juga:

```bash
mysql -u root -p < migration_dual_role.sql
```

3. Konfigurasi koneksi database.
   Secara default, aplikasi akan mencoba menggunakan:
   - host: localhost
   - user: root
   - database: testing_db

   Anda juga dapat mengatur variabel environment berikut:

```bash
DB_HOST=localhost
DB_USER=root
DB_NAME=testing_db
DB_PASS=root
```

4. Jalankan aplikasi dari folder proyek Anda melalui Laragon atau server PHP.

## Menjalankan Aplikasi

Jika menggunakan Laragon, tempatkan folder proyek di direktori www lalu buka:

```text
http://localhost/RENTALIN_TENSTING%20-%20Copy/
```

Alternatif lain:

```bash
php -S localhost:8000
```

Lalu buka:

```text
http://localhost:8000/
```

## Menjalankan Pengujian E2E

Install dependency dan browser Playwright:

```bash
npm install
npx playwright install
```

Jalankan seluruh suite:

```bash
npm run test:e2e
```

Untuk smoke test yang lebih cepat:

```bash
npx playwright test tests/auth-role.spec.js tests/add-item.spec.js tests/admin-dashboard.spec.js
```

Untuk membersihkan data test:

```bash
npm run cleanup:tests
```

> Perlu file .dev_token di root proyek untuk endpoint cleanup test. File ini bersifat lokal dan tidak boleh di-commit.

## Dokumen Pendukung

- DEV_SETUP.md: panduan setup dan testing lokal
- DUAL_ROLE_GUIDE.md: penjelasan fitur dual-role
- docs/PROJECT_STRUCTURE.md: struktur folder proyek
- CONTRIBUTING.md: panduan kontribusi
- ATTRIBUTIONS.md: daftar atribusi pihak ketiga

## Catatan

- Aplikasi ini dirancang untuk kebutuhan demo, pengembangan lokal, dan pengujian fitur marketplace penyewaan mahasiswa.
- Untuk pengalaman terbaik, jalankan di lingkungan yang sudah menyediakan Apache dan MySQL secara bersamaan.
  