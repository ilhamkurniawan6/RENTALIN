# ✓ RENTALIN PROJECT - DEMO CHECKLIST

## Status: READY FOR DEMO ✓

---

## ✓ Selesai

### Backend API
- [x] Items CRUD API (`items-crud.php`)
  - [x] GET list items
  - [x] GET item detail
  - [x] POST create item
  - [x] PUT update item
  - [x] DELETE item
  - [x] Validasi input (name, category, description, price, location)
  - [x] Ownership verification (user hanya akses barang sendiri)

- [x] Rentals CRUD API (`rentals-crud.php`)
  - [x] GET list rentals
  - [x] GET rental detail
  - [x] GET rental history (dengan pagination)
  - [x] POST create rental
  - [x] Auto calculate total price
  - [x] Validasi tanggal (start < end, format valid)
  - [x] Cek availability barang
  - [x] Cek ownership (tidak bisa rental barang sendiri)

- [x] Authentication & Session
  - [x] Login API (`auth_login.php`)
  - [x] Auth status check (`auth-status.php`)
  - [x] Session management
  - [x] User context

### Database
- [x] 15 demo users siap
- [x] 44 items terdistribusi per user
- [x] 24 rental records untuk testing
- [x] All tables created & validated

### Response Format
- [x] Konsisten JSON: `{ success, message, data }`
- [x] HTTP status codes proper (200, 201, 400, 401, 403, 404, 405, 500)
- [x] Error messages jelas
- [x] Success messages deskriptif

### Documentation
- [x] DEMO_QUICK_START.md - 3 langkah mulai
- [x] SETUP_FOR_DEMO.md - Skenario detail & flow
- [x] API_DEMO_GUIDE.md - API reference lengkap + curl examples
- [x] verify-setup.php - Verification script
- [x] This checklist

---

## ✓ Demo Users Ready

| ID | Name | Email | Role | Current | Items | Password |
|----|------|-------|------|---------|-------|----------|
| 1 | Budi Santoso | budi.santoso@university.edu | user | penyewakan | 5 | password |
| 2 | Sarah Putri | sarah.putri@university.edu | user | penyewa | 4 | password |
| 3 | Michael Wijaya | michael.wijaya@university.edu | user | penyewakan | 4 | password |
| 4 | Emma Lestari | emma.lestari@university.edu | user | penyewa | 4 | password |
| 5 | Admin | admin@university.edu | admin | penyewa | 2 | password |

---

## ✓ Quick Test Commands

### Verify Setup
```bash
php verify-setup.php
```

### Open App
```
http://localhost/RENTALIN_TENSTING%20-%20Copy
```

### API Test (Login)
```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/auth_login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"budi.santoso@university.edu","password":"password"}' \
  -c cookies.txt
```

### API Test (Create Item)
```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "name":"Test Item",
    "category":"Camera",
    "description":"Test description for demo item",
    "price_per_day":100000,
    "location":"Test Location",
    "availability":true
  }'
```

---

## ✓ Demo Scenarios

### Scenario A: Item CRUD (5 min)
1. Login sebagai Budi
2. Create item baru
3. List items milik Budi
4. Update item (ubah harga)
5. Delete item

**Expected**: Semua operasi berhasil, item muncul/hilang di list

### Scenario B: Rental Flow (5 min)
1. Login sebagai Sarah
2. Browse items dari user lain
3. View detail item
4. Create rental
5. Lihat rental di dashboard
6. Lihat rental history

**Expected**: Rental tersimpan dengan status pending, total price terkalkulasi

### Scenario C: API Testing (5 min)
1. Login via API → dapatkan session
2. POST create item
3. GET list items
4. PUT update item
5. DELETE item

**Expected**: Response JSON konsisten, status codes benar

---

## ✓ Validasi Tertulis

### Items Validation
- ✓ Nama: minimal 3 karakter
- ✓ Kategori: tidak boleh kosong
- ✓ Deskripsi: minimal 10 karakter
- ✓ Harga: harus > 0
- ✓ Lokasi: tidak boleh kosong
- ✓ Ownership: hanya pemilik yang bisa edit/delete

### Rentals Validation
- ✓ Item ID: valid & exists
- ✓ Item availability: harus true
- ✓ Start date: format valid (YYYY-MM-DD)
- ✓ End date: format valid & > start_date
- ✓ Max duration: 365 hari
- ✓ Ownership: tidak bisa rental barang sendiri
- ✓ Price calculation: days × price_per_day

### Security
- ✓ User authentication required untuk semua endpoint
- ✓ User hanya akses data miliknya (ownership check)
- ✓ SQL injection prevention (prepared statements)
- ✓ Input sanitization

---

## ✓ Error Handling

| Scenario | Status | Message | Tested |
|----------|--------|---------|--------|
| Tidak login | 401 | "Anda harus login" | ✓ |
| Item tidak ditemukan | 404 | "Barang tidak ditemukan" | ✓ |
| Akses item user lain | 403 | "Tidak punya hak akses" | ✓ |
| Validasi gagal | 422 | "Detail error" | ✓ |
| Method not allowed | 405 | "Method tidak diizinkan" | ✓ |
| DB error | 500 | "Database error" | ✓ |

---

## ✓ Files Created/Modified

### New Files
- `src/pages/api/items-crud.php` - Items CRUD endpoint
- `src/pages/api/rentals-crud.php` - Rentals CRUD endpoint
- `API_DEMO_GUIDE.md` - API documentation
- `SETUP_FOR_DEMO.md` - Demo scenarios guide
- `DEMO_QUICK_START.md` - Quick start guide
- `verify-setup.php` - Setup verification
- `test-api.php` - API test script
- `DEMO_CHECKLIST.md` - This file

### Existing Files (Verified)
- `database.sql` - ✓ Has 15 users, demo data
- `src/services/koneksi.php` - ✓ DB connection working
- `src/pages/api/auth_login.php` - ✓ Login working
- `src/pages/api/auth-status.php` - ✓ Session check working

---

## ✓ Database State

```
Database: tensting_db
Host: localhost
User: root
Password: root

Tables:
├── users (15 records)
├── items (44 records)
├── rentals (24 records)
├── reviews (25 records)
├── categories (10 records)
└── notifications

Data Distribution:
- Users: 15 active (12 demo + 3 extra)
- Items: 44 total (avg 3.7 per user)
- Rentals: 24 total (statuses: completed, active, pending, cancelled)
```

---

## ✓ Next: Demo Flow

### Total Time: ~20 menit

1. **Setup Check** (1 min)
   - Verify setup: `php verify-setup.php`

2. **UI Demo** (10 min)
   - Scenario A (CRUD): 5 min
   - Scenario B (Rental): 5 min

3. **API Demo** (8 min)
   - Postman atau curl
   - Scenario C (API): 5 min
   - Q&A: 3 min

4. **Wrap-up** (1 min)
   - Tanya-jawab
   - Next steps

---

## ✓ Troubleshooting Quick Fixes

| Problem | Solution |
|---------|----------|
| Can't login | Check database imported, user exists |
| Item create fails | All fields required, price > 0 |
| Rental fails | Item must exist & available, dates valid |
| API 401 | Must login first, use cookies |
| DB connection error | Ensure MySQL running, creds correct |

---

## ✓ Files to Share with Demo

1. **DEMO_QUICK_START.md** - Start here
2. **SETUP_FOR_DEMO.md** - Detailed scenarios
3. **API_DEMO_GUIDE.md** - API reference
4. **verify-setup.php** - Verify before demo

---

## ✓ Go/No-Go Checklist (Before Demo)

- [ ] Database running: `mysql -u root -proot -D tensting_db`
- [ ] Verify setup: `php verify-setup.php` → ✓ output
- [ ] Open app: http://localhost/RENTALIN_TENSTING%20-%20Copy
- [ ] Login works: budi.santoso@university.edu / password
- [ ] Items visible: List items at dashboard
- [ ] API response: GET /items-crud.php → JSON response
- [ ] Documentation ready: 3 guide files present

---

## Status: ✓ READY FOR DEMO

**All systems operational. Demo can proceed.**

*Prepared: 2026-07-16*  
*Database: 15 users, 44 items, 24 rentals*  
*API Endpoints: 8 endpoints (CRUD items + rentals + auth)*  
*Documentation: 4 guide files complete*
