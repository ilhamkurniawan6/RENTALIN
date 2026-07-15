# Sistem Dual-Role Rentalin 🎯

Dokumentasi untuk sistem dual-role yang memungkinkan satu akun email untuk beralih antara peran **Penyewa** (Renter) dan **Penyewakan** (Property Owner).

## Daftar Isi
1. [Pengenalan](#pengenalan)
2. [Fitur Utama](#fitur-utama)
3. [Cara Kerja](#cara-kerja)
4. [Panduan Pengguna](#panduan-pengguna)
5. [Database](#database)
6. [API Endpoints](#api-endpoints)
7. [Troubleshooting](#troubleshooting)

---

## Pengenalan

Sistem dual-role memungkinkan satu pengguna dengan satu akun email untuk:
- **Menyewa barang** (Penyewa) - browse dan menyewa item dari pengguna lain
- **Menyewakan barang** (Penyewakan) - mendaftarkan dan menyewakan barang mereka sendiri
- **Beralih peran dengan mudah** - cukup klik tombol di dashboard

### Contoh Kasus Penggunaan
- **Mahasiswa A** ingin mencari kamera untuk dipinjam → Mode **Penyewa**
- **Mahasiswa A** juga punya laptop yang ingin disewakan → Beralih ke mode **Penyewakan**
- **Mahasiswa A** bisa dengan bebas beralih antar mode sesuai kebutuhan

---

## Fitur Utama

### ✅ Untuk Penyewa (Mode: Penyewa)
- Menjelajahi katalog barang
- Mencari dan filter barang
- Melihat detail barang & pemilik
- Membuat permintaan penyewaan
- Melihat riwayat penyewaan sendiri
- Tidak bisa menambah barang baru

### ✅ Untuk Penyewakan (Mode: Penyewakan)
- Menambah barang baru untuk disewakan
- Mengelola daftar barang milik sendiri
- Melihat status ketersediaan barang
- Mengelola permintaan penyewaan
- Melihat riwayat penyewaan barang sendiri
- Menerima rating dari penyewa

### ✅ Umum
- Toggle mode kapan saja di dashboard
- Satu email = unlimited role switching
- Data penyewaan tersimpan di database
- Rating & review tetap konsisten

---

## Cara Kerja

### Flow Diagram

```
┌─────────────────────────────────────┐
│      User Login / Register          │
│  (Auto set current_role = penyewa)  │
└────────────┬────────────────────────┘
             │
             ↓
┌─────────────────────────────────────┐
│        Buka Dashboard               │
│  Lihat Role Toggle Buttons          │
└────────────┬────────────────────────┘
             │
        ┌────┴─────────┐
        │              │
        ↓              ↓
   [Penyewa]      [Penyewakan]
   Mode Active    Mode Active
        │              │
        ↓              ↓
   Browse &        Add Item &
   Rent Items      Manage Items
        │              │
        └────┬─────────┘
             │
        (Switch Anytime)
```

### Database Changes

**Tabel Users** - Kolom Baru:
- `current_role` ENUM('penyewa', 'penyewakan') - Role aktif sekarang
- `preferred_role` ENUM('penyewa', 'penyewakan') - Role default (opsional)

```sql
ALTER TABLE users ADD COLUMN current_role ENUM('penyewa', 'penyewakan') DEFAULT 'penyewa';
ALTER TABLE users ADD COLUMN preferred_role ENUM('penyewa', 'penyewakan') DEFAULT 'penyewa';
```

---

## Panduan Pengguna

### Instalasi & Setup

#### 1. Update Database (Existing Installation)
Jika sudah memiliki database `tenting_db`, jalankan migration:

```bash
mysql -u root -p tenting_db < migration_dual_role.sql
```

#### 2. Fresh Installation
Jika setup baru, database.sql sudah include kolom dual-role:

```bash
mysql -u root -p < database.sql
```

### Menggunakan Sistem

#### Login Pertama Kali
1. User mendaftar dengan email
2. Default role: **Penyewa** (Mode Browse & Rent)
3. Buka Dashboard
4. Klik tombol **Penyewakan** untuk beralih

#### Beralih Mode (Mode Switching)
1. Buka **Dashboard**
2. Di sidebar atas, lihat **"Mode:" dengan 2 tombol**
   - 🔍 Penyewa (Browse & Rent)
   - 🎁 Penyewakan (List & Manage)
3. Klik tombol untuk beralih
4. Tombol aktif akan lebih terang (opacity 1.0)
5. UI akan update otomatis

#### Mode Penyewa (Renter)
Tampilan:
- **Tab aktif**: "Penyewaan Saya"
- **Tab items tersembunyi**
- **Tombol "Tambah Barang" tidak terlihat**
- **Nav bar normal**

Fitur:
- Jelajahi barang → Menu "Jelajahi Barang"
- Lihat penyewaan aktif
- Browse & search barang

#### Mode Penyewakan (Owner)
Tampilan:
- **Tab aktif**: "Barang Saya"
- **Tab rentals tetap ada**
- **Tombol "Tambah Barang Baru" terlihat**
- **Nav bar sama**

Fitur:
- Tambah barang baru → Klik "Tambah Barang Baru"
- Kelola barang yang didaftar
- Lihat penyewaan terhadap barang Anda

---

## Database

### Schema Tabel Users

```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  phone VARCHAR(15),
  password VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') DEFAULT 'user',
  
  -- Kolom baru untuk dual-role
  current_role ENUM('penyewa', 'penyewakan') DEFAULT 'penyewa',
  preferred_role ENUM('penyewa', 'penyewakan') DEFAULT 'penyewa',
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_email (email),
  INDEX idx_role (role),
  INDEX idx_current_role (current_role)
);
```

### Session User Object

Setelah login, `$_SESSION['auth_user']` berisi:

```php
Array (
    'id' => 1,
    'name' => 'Budi Santoso',
    'email' => 'budi@university.edu',
    'phone' => '08123456789',
    'role' => 'user',
    'current_role' => 'penyewa',      // ← Peran aktif sekarang
    'preferred_role' => 'penyewa',    // ← Default role (opsional)
)
```

### Query Berguna

```sql
-- Get user dengan current role
SELECT id, name, email, current_role, preferred_role 
FROM users 
WHERE id = 1;

-- Get semua items dari penyewakan tertentu
SELECT i.* FROM items i
WHERE i.user_id = 1
AND EXISTS (
  SELECT 1 FROM users u 
  WHERE u.id = 1 
  AND u.current_role = 'penyewakan'
);

-- Get rental activities dari penyewa
SELECT r.* FROM rentals r
WHERE r.user_id = 1
AND EXISTS (
  SELECT 1 FROM users u 
  WHERE u.id = 1 
  AND u.current_role = 'penyewa'
);
```

---

## API Endpoints

### 1. Login
**POST** `/src/pages/login/app.php`

Response includes `current_role`:
```json
{
  "success": true,
  "message": "Login berhasil.",
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@university.edu",
    "phone": "08123456789",
    "role": "user",
    "current_role": "penyewa",
    "preferred_role": "penyewa"
  },
  "redirect": "../dashboard/index.php"
}
```

### 2. Register
**POST** `/src/pages/register/app.php`

New users otomatis mendapat:
- `current_role` = 'penyewa'
- `preferred_role` = 'penyewa'

### 3. Switch Role ⭐ NEW
**POST** `/src/pages/dashboard/switch-role.php`

Request:
```
Form Data:
  role = 'penyewa' | 'penyewakan'
```

Response Success:
```json
{
  "success": true,
  "message": "Role berhasil diubah ke penyewakan",
  "current_role": "penyewakan"
}
```

Response Error:
```json
{
  "success": false,
  "message": "Role tidak valid."
}
```

---

## Troubleshooting

### Q: Mode toggle tidak muncul di dashboard
**A:** 
- Pastikan sudah login dengan benar
- Clear browser cache (Ctrl+Shift+Del)
- Refresh halaman
- Check console untuk error

### Q: Role tidak tersimpan setelah beralih
**A:**
- Check database connection di `koneksi.php`
- Pastikan column `current_role` sudah ada di database
- Jalankan migration: `mysql -u root -p tenting_db < migration_dual_role.sql`
- Check browser console untuk error message

### Q: "Tambah Barang" button tidak terlihat
**A:**
- Mode harus di **Penyewakan** (bukan Penyewa)
- Klik tombol 🎁 **Penyewakan** di sidebar
- Button akan muncul setelah 1-2 detik

### Q: Bagaimana data penyewaan terpelihara saat switch role?
**A:**
- Data tidak hilang! Semua penyewaan tersimpan di database
- Ketika mode **Penyewa**: Lihat penyewaan yang Anda buat
- Ketika mode **Penyewakan**: Lihat penyewaan terhadap barang Anda
- History tetap ada di database

### Q: Bisakah saya set default role?
**A:**
Belum ada UI untuk itu, tapi bisa via database:
```sql
UPDATE users SET preferred_role = 'penyewakan' WHERE id = 1;
```

Nanti bisa ditambah feature untuk set default role di pengaturan.

---

## File Struktur

```
RENTALIN_TENSTING - Copy/
├── database.sql                          # Schema dgn dual-role support
├── migration_dual_role.sql               # Migration untuk existing DB
├── index.php                             # Root entry point
└── src/
    ├── pages/
    │   ├── login/
    │   │   ├── app.php                  # ✏️ Updated: set current_role saat login
    │   │   └── index.php
    │   ├── register/
    │   │   ├── app.php                  # ✏️ Updated: set default current_role
    │   │   └── index.php
    │   ├── dashboard/
    │   │   ├── index.php                # ✏️ Updated: added role toggle UI & logic
    │   │   ├── app.php
    │   │   ├── switch-role.php          # 🆕 NEW: handle role switching
    │   │   └── styles.css               # ✏️ Updated: added role toggle styles
    │   ├── add-item/
    │   │   ├── index.php                # ✏️ Updated: added role mode indicator
    │   │   ├── app.php
    │   │   └── styles.css
    │   └── [other pages...]
    └── services/
        └── koneksi.php
```

### File yang Diubah (Modified)
- ✏️ `database.sql` - Schema diupdate
- ✏️ `src/pages/login/app.php` - Set current_role from DB
- ✏️ `src/pages/register/app.php` - Init default current_role
- ✏️ `src/pages/dashboard/index.php` - Added role toggle UI & logic
- ✏️ `src/pages/dashboard/styles.css` - Added toggle button styles
- ✏️ `src/pages/add-item/index.php` - Added role mode indicator

### File yang Ditambahkan (New)
- 🆕 `src/pages/dashboard/switch-role.php` - Role switching backend
- 🆕 `migration_dual_role.sql` - Migration script

---

## Implementasi Selesai! ✨

Sistem dual-role sekarang siap digunakan. User bisa:
1. ✅ Login dengan email
2. ✅ Default masuk mode Penyewa
3. ✅ Toggle ke Penyewakan kapan saja
4. ✅ Manage barang saat mode Penyewakan
5. ✅ Browse barang saat mode Penyewa
6. ✅ Switch unlimited tanpa batasan

Enjoy! 🎉
