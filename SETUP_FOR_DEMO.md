# RENTALIN - Panduan Siap Demo

## Status Sistem ✓

- ✓ Database `tensting_db` sudah terisi
- ✓ 15 demo users siap login
- ✓ 44 items terdistribusi per user
- ✓ 24 rental records untuk testing
- ✓ API endpoints lengkap (CRUD item & rental)
- ✓ Session & authentication siap

---

## Akses Cepat

### URL Aplikasi
```
http://localhost/RENTALIN_TENSTING%20-%20Copy
```

### Demo User Terbaik untuk Testing

#### A. Penyewakan (Pemilik Barang)
- **Email**: `budi.santoso@university.edu`
- **Password**: `password`
- **Punya**: 5 barang
- **Role**: User (Penyewakan)

#### B. Penyewa (Pembeli)
- **Email**: `sarah.putri@university.edu`
- **Password**: `password`
- **Punya**: 4 barang
- **Role**: User (Penyewa)

#### C. Admin
- **Email**: `admin@university.edu`
- **Password**: `password`
- **Role**: Admin

---

## Testing Skenario

### Skenario 1: Create → List → Edit → Delete Item (5 menit)

1. **Login** sebagai Budi (penyewakan)
   - Akses: http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/login/index.php
   - Email: `budi.santoso@university.edu`
   - Password: `password`

2. **Create Item Baru**
   - Klik "Tambah Barang Baru" di dashboard
   - Isi form dengan data:
     - Nama: "Kamera Test Demo"
     - Kategori: Camera
     - Harga: 100000
     - Lokasi: Kampus Utama
     - Deskripsi: "Demo testing barang rental"
   - Klik "Simpan"

3. **List & Edit**
   - Lihat barang di dashboard tab "Barang Saya"
   - Klik edit pada barang yang baru dibuat
   - Ubah harga jadi 120000
   - Simpan

4. **Delete**
   - Klik delete pada barang tersebut
   - Konfirmasi

✓ CRUD item berhasil!

---

### Skenario 2: Rental Flow (5 menit)

1. **Login** sebagai Sarah (penyewa)
   - Email: `sarah.putri@university.edu`
   - Password: `password`

2. **Browse & Pilih Item**
   - Akses: http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/browse/index.php
   - Lihat barang dari user lain
   - Klik detail pada barang (misalnya item Budi)

3. **Buat Rental**
   - Klik tombol "Pesan" atau "Sewa"
   - Pilih tanggal mulai & tanggal kembali
   - Lihat harga otomatis terhitung
   - Klik "Konfirmasi Rental"

4. **Lihat Rental di Dashboard**
   - Kembali ke dashboard
   - Tab "Penyewaan Saya"
   - Lihat rental yang baru dibuat dengan status "pending"
   - Lihat total harga sudah dihitung

✓ Rental flow berhasil!

---

## API Testing dengan curl

Semua API endpoint siap untuk testing. Contoh cepat:

### 1. Login via API
```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/auth_login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"budi.santoso@university.edu","password":"password"}' \
  -c cookies.txt
```

### 2. Create Item
```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "name":"Drone Test",
    "category":"Drone",
    "description":"Drone untuk testing API endpoint",
    "price_per_day":500000,
    "location":"Test",
    "availability":true
  }'
```

### 3. List Items
```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php?action=list" \
  -b cookies.txt
```

### 4. Create Rental
```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/rentals-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "item_id":2,
    "start_date":"2026-08-01",
    "end_date":"2026-08-05"
  }'
```

📌 **Lengkapnya lihat di: [API_DEMO_GUIDE.md](./API_DEMO_GUIDE.md)**

---

## Struktur Endpoint

### Items CRUD
| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/items-crud.php?action=list` | List item user |
| GET | `/items-crud.php?action=detail&id=X` | Detail item |
| POST | `/items-crud.php` | Buat item baru |
| PUT | `/items-crud.php` | Edit item |
| DELETE | `/items-crud.php?id=X` | Hapus item |

### Rentals CRUD
| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/rentals-crud.php?action=list` | List rental user |
| GET | `/rentals-crud.php?action=detail&id=X` | Detail rental |
| GET | `/rentals-crud.php?action=history&page=1&limit=10` | Rental history |
| POST | `/rentals-crud.php` | Buat rental baru |

### Auth
| Method | Endpoint | Action |
|--------|----------|--------|
| POST | `/auth_login.php` | Login |
| GET | `/auth-status.php` | Check session |

---

## Response Format

**Success Response** (200, 201):
```json
{
  "success": true,
  "message": "Operasi berhasil",
  "data": { ... }
}
```

**Error Response** (400, 401, 403, 404, 500):
```json
{
  "success": false,
  "message": "Deskripsi error",
  "data": null
}
```

---

## Validasi Sistem

Untuk memastikan semua siap, jalankan:
```bash
php verify-setup.php
```

Output yang diharapkan:
```
=== VERIFICATION COMPLETE ===
✓ System ready for demo!
```

---

## Troubleshooting

### Issue 1: "Unable to connect to database"
**Solusi:**
- Pastikan MySQL running: `mysql -u root -proot -D tensting_db`
- Cek file `src/services/koneksi.php` - pastikan `DB_HOST`, `DB_USER`, `DB_PASS` sesuai

### Issue 2: "Login gagal"
**Solusi:**
- Pastikan user ada di database: `mysql -u root -proot -D tensting_db -e "SELECT * FROM users LIMIT 1"`
- Pastikan password `password` (bukan user password asli)
- Database sudah ter-import dari `database.sql`

### Issue 3: "Item tidak bisa dibuat"
**Solusi:**
- Harus sudah login terlebih dahulu (session aktif)
- Semua field harus diisi (nama, kategori, deskripsi, harga, lokasi)
- Harga harus angka > 0

### Issue 4: "Rental gagal dibuat"
**Solusi:**
- Item harus tersedia (availability = true)
- Tidak bisa rental barang milik sendiri
- Tanggal kembali harus setelah tanggal mulai

### Issue 5: API return 401 (unauthorized)
**Solusi:**
- Harus login dulu dengan curl: `curl ... -c cookies.txt`
- Kirim request dengan cookie: `curl ... -b cookies.txt`

---

## File-file Penting

```
src/
├── pages/
│   ├── api/
│   │   ├── items-crud.php           ← NEW: Items CRUD API
│   │   ├── rentals-crud.php         ← NEW: Rentals CRUD API
│   │   ├── auth_login.php           ← Existing: Login
│   │   ├── auth-status.php          ← Check auth session
│   │   ├── items.php                ← Existing: Read items
│   │   └── categories.php           ← Existing: Read categories
│   ├── login/
│   │   └── index.php                ← Login page
│   ├── dashboard/
│   │   └── index.php                ← User dashboard
│   ├── add-item/
│   │   └── index.php                ← Add item form
│   └── browse/
│       └── index.php                ← Browse items
├── services/
│   ├── koneksi.php                  ← DB connection
│   ├── session-init.php             ← Session management
│   ├── auth_login.php               ← Login logic
│   └── url-helper.php               ← URL utilities
└── styles/
    ├── global.css
    ├── variables.css
    └── reset.css

Database/
├── database.sql                     ← Main schema + data
└── scripts/
    └── seed-demo-items.sql          ← Extra demo items

Documentation/
├── API_DEMO_GUIDE.md                ← Detailed API guide
├── SETUP_FOR_DEMO.md                ← This file
├── verify-setup.php                 ← Setup verification script
└── README.md                        ← Project overview
```

---

## Demo Flow untuk Presentasi (10 menit)

### Flow A: Show CRUD (dari browser)
1. **Login** sebagai Budi
   - "Sekarang saya login sebagai penyewakan..."
   
2. **Show Dashboard**
   - "Di sini user bisa lihat barangnya, berapa yang tersedia, dll"
   
3. **Create Item**
   - "Tambah item baru dengan form yang sederhana"
   - Klik "Tambah Barang Baru"
   - Isi form → Simpan
   - "Item sekarang ada di list!"
   
4. **Edit Item**
   - Klik edit → ubah harga → simpan
   - "Update berhasil langsung"
   
5. **Delete Item**
   - Klik delete → konfirmasi
   - "Barang sudah hilang dari list"

### Flow B: Show Rental (dari browser)
1. **Logout & Login** sebagai Sarah
   - "Sekarang kita jadi user penyewa"
   
2. **Browse Items**
   - Buka halaman browse
   - "Lihat barang-barang dari user lain"
   
3. **View Detail & Book**
   - Klik salah satu item
   - "Lihat detail barang dan owner-nya"
   - Klik "Sewa" / "Pesan"
   - Isi tanggal → konfirmasi
   
4. **Dashboard Rental**
   - "Sekarang rental ada di dashboard Sarah"
   - Lihat status pending, tanggal, total harga
   
5. **Rental History**
   - Lihat history rental dengan pagination

### Flow C: Show API (dari Postman/Terminal)
1. **Buka Postman**
   - Import collection dari API_DEMO_GUIDE.md
   
2. **Demo Login**
   - POST /auth_login
   - Show: credential → response dengan user data
   
3. **Demo Create Item**
   - POST /items-crud
   - Show: request body → response dengan item_id
   
4. **Demo List Item**
   - GET /items-crud?action=list
   - Show: JSON response dengan array items
   
5. **Demo Create Rental**
   - POST /rentals-crud
   - Show: request dengan tanggal → calculated total_price

---

## Catatan Demo Besok

✓ Database sudah siap dengan data realistic  
✓ 15 user untuk testing berbagai role  
✓ 44 items siap untuk browse & rental  
✓ API endpoint stabil & siap  
✓ Response format konsisten JSON  
✓ Error handling sudah implemented  
✓ Validation di semua endpoint  
✓ Session & authentication working  

**Estimated duration: 15 menit untuk full demo**

---

## Quick Commands

```bash
# Verify setup
php verify-setup.php

# Open browser
start http://localhost/RENTALIN_TENSTING%20-%20Copy

# MySQL access
mysql -u root -proot -D tensting_db

# API docs
cat API_DEMO_GUIDE.md
```

---

## Support

Jika ada yang tidak jelas, cek:
1. `API_DEMO_GUIDE.md` - Dokumentasi API lengkap
2. `src/pages/api/items-crud.php` - Source code endpoint
3. `src/pages/api/rentals-crud.php` - Source code endpoint
4. `verify-setup.php` - Debug database

---

**Status: ✓ READY FOR DEMO**  
*Last Updated: 2026-07-16*  
*Database: 15 users, 44 items, 24 rentals*
