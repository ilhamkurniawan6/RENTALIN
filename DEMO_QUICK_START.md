# RENTALIN - Ready for Demo ✓

## Mulai Demo Dalam 3 Langkah

### 1. Buka Aplikasi
```
http://localhost/RENTALIN_TENSTING%20-%20Copy
```

### 2. Login dengan Demo Account
- **Email**: `budi.santoso@university.edu`
- **Password**: `password`

### 3. Test Fitur
- Lihat barang di dashboard
- Tambah barang baru
- Edit/hapus barang
- Browse barang dari user lain
- Buat rental
- Lihat history rental

---

## Quick Features Demo

| Fitur | Akses | User Demo |
|-------|-------|-----------|
| **Create Item** | Dashboard > Tambah Barang | Budi (penyewakan) |
| **Read Items** | Dashboard / Browse | Semua |
| **Edit Item** | Dashboard > Barang Saya | Pemilik barang |
| **Delete Item** | Dashboard > Barang Saya | Pemilik barang |
| **Browse & Rental** | Browse / Item Detail | Sarah (penyewa) |
| **View Rental** | Dashboard > Penyewaan | Penyewa |
| **Admin Panel** | Mode Admin | Admin user |

---

## All Demo Users

```
1. Budi Santoso           budi.santoso@university.edu       (penyewakan)
2. Sarah Putri            sarah.putri@university.edu        (penyewa)
3. Michael Wijaya         michael.wijaya@university.edu     (penyewakan)
4. Emma Lestari           emma.lestari@university.edu       (penyewa)
5. Admin User             admin@university.edu              (admin)
```

**Password untuk semua: `password`**

---

## File Dokumentasi

- 📖 **[SETUP_FOR_DEMO.md](./SETUP_FOR_DEMO.md)** - Panduan detail untuk presentasi
- 🔌 **[API_DEMO_GUIDE.md](./API_DEMO_GUIDE.md)** - Dokumentasi API lengkap + curl examples
- ✓ **[verify-setup.php](./verify-setup.php)** - Script verifikasi setup

---

## API Endpoints

### Items CRUD
```bash
# List items
GET /src/pages/api/items-crud.php?action=list

# Create item
POST /src/pages/api/items-crud.php
Body: {name, category, description, price_per_day, location, availability}

# Update item
PUT /src/pages/api/items-crud.php
Body: {id, name, category, ...}

# Delete item
DELETE /src/pages/api/items-crud.php?id=X
```

### Rentals CRUD
```bash
# List rentals
GET /src/pages/api/rentals-crud.php?action=list

# Create rental
POST /src/pages/api/rentals-crud.php
Body: {item_id, start_date, end_date}

# Get rental history
GET /src/pages/api/rentals-crud.php?action=history?page=1&limit=10
```

---

## Verifikasi Setup

Jalankan untuk confirm system siap:
```bash
php verify-setup.php
```

Expected output:
```
✓ Connected to database
✓ users table exists
✓ items table exists
✓ rentals table exists
...
✓ System ready for demo!
```

---

## Database Info

- **Database**: `tensting_db`
- **Users**: 15 (sudah siap)
- **Items**: 44 (sudah terdistribusi)
- **Rentals**: 24 (sudah ada)
- **Password DB**: root/root

---

## Troubleshooting

**Q: Tidak bisa login?**  
A: Pastikan database sudah import `database.sql`

**Q: Item tidak bisa dibuat?**  
A: Semua field harus diisi, harus sudah login, harga harus > 0

**Q: Rental gagal?**  
A: Item harus tersedia, tidak bisa rental barang sendiri, tanggal valid

---

## Next: Deploy?

Untuk production setup, lihat:
- `README.md` - Project overview
- `CONTRIBUTING.md` - Development guidelines
- `DUAL_ROLE_GUIDE.md` - Fitur dual role detailed

---

**Status: ✓ READY**  
*Last verified: 2026-07-16*
