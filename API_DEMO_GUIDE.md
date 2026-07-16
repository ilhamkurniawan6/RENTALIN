# RENTALIN API - Demo Guide

## Daftar Isi
1. [Setup Database](#setup-database)
2. [User Demo](#user-demo)
3. [Authentication](#authentication)
4. [Items CRUD API](#items-crud-api)
5. [Rentals API](#rentals-api)
6. [Response Format](#response-format)
7. [Contoh Request Lengkap](#contoh-request-lengkap)

---

## Setup Database

Pastikan database `tensting_db` sudah ter-import dengan data demo:

```bash
cd c:\laragon\www\RENTALIN_TENSTING - Copy
mysql -u root -proot -D tensting_db < database.sql
mysql -u root -proot -D tensting_db < scripts/seed-demo-items.sql
```

**Database**: `tensting_db`
**User**: `root`
**Password**: `root`
**Host**: `localhost`

---

## User Demo

Daftar user yang sudah tersedia di database untuk testing:

| ID | Name | Email | Password | Role | Current Role |
|----|------|-------|----------|------|--------------|
| 1 | Budi Santoso | budi.santoso@university.edu | password | user | penyewakan |
| 2 | Sarah Putri | sarah.putri@university.edu | password | user | penyewa |
| 3 | Michael Wijaya | michael.wijaya@university.edu | password | user | penyewakan |
| 4 | Emma Lestari | emma.lestari@university.edu | password | user | penyewa |
| 5 | Admin User | admin@university.edu | password | admin | penyewa |

**Password untuk semua user**: `password` (hashed dengan bcrypt)

---

## Authentication

### Login

**Endpoint**: `POST /src/pages/api/auth_login.php`

**Request**:
```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/auth_login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "budi.santoso@university.edu",
    "password": "password"
  }' \
  -c cookies.txt
```

**Response** (Success 200):
```json
{
  "message": "Login berhasil.",
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi.santoso@university.edu",
    "phone": "08123456789",
    "avatar": "",
    "role": "user",
    "current_role": "penyewakan",
    "preferred_role": "penyewakan"
  }
}
```

### Check Auth Status

**Endpoint**: `GET /src/pages/api/auth-status.php`

```bash
curl -X GET http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/auth-status.php \
  -b cookies.txt
```

**Response** (User logged in):
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi.santoso@university.edu",
    "role": "user",
    "current_role": "penyewakan"
  },
  "csrf_token": "abc123..."
}
```

---

## Items CRUD API

**Base URL**: `/src/pages/api/items-crud.php`

### 1. List Items (User punya barang)

**Endpoint**: `GET /items-crud.php?action=list`

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php?action=list" \
  -H "Content-Type: application/json" \
  -b cookies.txt
```

**Response** (200):
```json
{
  "success": true,
  "message": "Daftar barang Anda",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Kamera Sony A7 III",
        "category": "Camera",
        "description": "Kamera mirrorless profesional dengan AF cepat dan performa 4K",
        "pricePerDay": 150000,
        "location": "Kampus Utama",
        "availability": true,
        "createdAt": "2026-03-01 10:00:00",
        "updatedAt": "2026-03-01 10:00:00"
      }
    ]
  }
}
```

### 2. Get Item Detail

**Endpoint**: `GET /items-crud.php?action=detail&id=1`

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php?action=detail&id=1" \
  -H "Content-Type: application/json" \
  -b cookies.txt
```

**Response** (200):
```json
{
  "success": true,
  "message": "Detail barang",
  "data": {
    "item": {
      "id": 1,
      "name": "Kamera Sony A7 III",
      "category": "Camera",
      "description": "Kamera mirrorless profesional dengan AF cepat dan performa 4K",
      "pricePerDay": 150000,
      "location": "Kampus Utama",
      "availability": true,
      "createdAt": "2026-03-01 10:00:00",
      "updatedAt": "2026-03-01 10:00:00"
    }
  }
}
```

### 3. Create Item (POST)

**Endpoint**: `POST /items-crud.php`

```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "name": "Laptop Gaming ASUS ROG",
    "category": "Laptop",
    "description": "Laptop gaming dengan RTX 3070 dan performa tinggi untuk gaming dan design profesional",
    "price_per_day": 200000,
    "location": "Gedung IT",
    "availability": true
  }'
```

**Response** (201):
```json
{
  "success": true,
  "message": "Barang berhasil dibuat",
  "data": {
    "itemId": 32,
    "item": {
      "id": 32,
      "name": "Laptop Gaming ASUS ROG",
      "category": "Laptop",
      "description": "Laptop gaming dengan RTX 3070 dan performa tinggi untuk gaming dan design profesional",
      "pricePerDay": 200000,
      "location": "Gedung IT",
      "availability": true
    }
  }
}
```

### 4. Update Item (PUT)

**Endpoint**: `PUT /items-crud.php`

```bash
curl -X PUT http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "id": 32,
    "name": "Laptop Gaming ASUS ROG RTX 3080",
    "category": "Laptop",
    "description": "Laptop gaming dengan RTX 3080 dan performa ultra tinggi",
    "price_per_day": 250000,
    "location": "Gedung IT",
    "availability": false
  }'
```

**Response** (200):
```json
{
  "success": true,
  "message": "Barang berhasil diupdate",
  "data": {
    "itemId": 32,
    "item": {
      "id": 32,
      "name": "Laptop Gaming ASUS ROG RTX 3080",
      "category": "Laptop",
      "description": "Laptop gaming dengan RTX 3080 dan performa ultra tinggi",
      "pricePerDay": 250000,
      "location": "Gedung IT",
      "availability": false
    }
  }
}
```

### 5. Delete Item (DELETE)

**Endpoint**: `DELETE /items-crud.php?id=32`

```bash
curl -X DELETE "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php?id=32" \
  -H "Content-Type: application/json" \
  -b cookies.txt
```

**Response** (200):
```json
{
  "success": true,
  "message": "Barang berhasil dihapus",
  "data": {
    "itemId": 32
  }
}
```

---

## Rentals API

**Base URL**: `/src/pages/api/rentals-crud.php`

### 1. List User Rentals

**Endpoint**: `GET /rentals-crud.php?action=list`

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/rentals-crud.php?action=list" \
  -H "Content-Type: application/json" \
  -b cookies.txt
```

**Response** (200):
```json
{
  "success": true,
  "message": "Daftar penyewaan Anda",
  "data": {
    "rentals": [
      {
        "id": 1,
        "itemId": 1,
        "itemName": "Kamera Sony A7 III",
        "ownerName": "Budi Santoso",
        "startDate": "2026-03-01",
        "endDate": "2026-03-03",
        "totalPrice": 300000,
        "status": "completed",
        "createdAt": "2026-03-01 11:00:00"
      }
    ],
    "count": 1
  }
}
```

### 2. Get Rental Detail

**Endpoint**: `GET /rentals-crud.php?action=detail&id=1`

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/rentals-crud.php?action=detail&id=1" \
  -H "Content-Type: application/json" \
  -b cookies.txt
```

### 3. Create Rental (POST)

**Endpoint**: `POST /rentals-crud.php`

```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/rentals-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "item_id": 2,
    "start_date": "2026-08-01",
    "end_date": "2026-08-05"
  }'
```

**Response** (201):
```json
{
  "success": true,
  "message": "Penyewaan berhasil dibuat",
  "data": {
    "rentalId": 21,
    "itemId": 2,
    "startDate": "2026-08-01",
    "endDate": "2026-08-05",
    "days": 4,
    "totalPrice": 400000,
    "status": "pending"
  }
}
```

### 4. Rental History (Pagination)

**Endpoint**: `GET /rentals-crud.php?action=history&page=1&limit=10`

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/rentals-crud.php?action=history&page=1&limit=10" \
  -H "Content-Type: application/json" \
  -b cookies.txt
```

**Response** (200):
```json
{
  "success": true,
  "message": "Riwayat penyewaan",
  "data": {
    "rentals": [
      {
        "id": 20,
        "itemId": 30,
        "itemName": "iPhone 14 Pro Max",
        "startDate": "2026-04-25",
        "endDate": "2026-04-26",
        "totalPrice": 95000,
        "status": "completed",
        "createdAt": "2026-04-25 14:30:00"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 20,
      "totalPages": 2
    }
  }
}
```

---

## Response Format

Semua endpoint mengembalikan response dengan format yang konsisten:

### Success Response
```json
{
  "success": true,
  "message": "Pesan sukses",
  "data": {
    "...": "data payload"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Pesan error",
  "data": null
}
```

### HTTP Status Codes
- `200` - OK (GET, PUT, DELETE berhasil)
- `201` - Created (POST berhasil membuat resource)
- `400` - Bad Request (validasi input gagal)
- `401` - Unauthorized (harus login)
- `403` - Forbidden (tidak punya akses)
- `404` - Not Found (resource tidak ditemukan)
- `405` - Method Not Allowed
- `409` - Conflict (conflict logika bisnis)
- `422` - Unprocessable Entity (validasi gagal)
- `500` - Internal Server Error

---

## Contoh Request Lengkap

### Skenario 1: Login → Buat Item → Lihat Item

**Step 1: Login sebagai Budi (user 1 - penyewakan)**

```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/auth_login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"budi.santoso@university.edu","password":"password"}' \
  -c cookies.txt
```

**Step 2: Buat barang baru**

```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "name": "Drone DJI Air 3S",
    "category": "Drone",
    "description": "Drone profesional dengan kamera 48MP dan jangkauan 46km",
    "price_per_day": 300000,
    "location": "Lapangan Utama",
    "availability": true
  }'
```

Catat `itemId` dari response (misal 45).

**Step 3: Lihat daftar barang Budi**

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php?action=list" \
  -b cookies.txt
```

**Step 4: Edit barang**

```bash
curl -X PUT http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "id": 45,
    "name": "Drone DJI Air 3S Pro",
    "category": "Drone",
    "description": "Drone profesional dengan kamera 48MP dan jangkauan 46km plus aksesoris lengkap",
    "price_per_day": 350000,
    "location": "Lapangan Utama",
    "availability": true
  }'
```

**Step 5: Hapus barang**

```bash
curl -X DELETE "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php?id=45" \
  -b cookies.txt
```

---

### Skenario 2: Login → Lihat Items → Buat Rental → Lihat Rental

**Step 1: Login sebagai Sarah (user 2 - penyewa)**

```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/auth_login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"sarah.putri@university.edu","password":"password"}' \
  -c cookies2.txt
```

**Step 2: Lihat barang yang tersedia dari user 1**

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php?action=list" \
  -H "Content-Type: application/json"
```

Lihat item dengan id=2 (Lensa 24-70mm) milik user 1.

**Step 3: Buat rental untuk item id 2**

```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/rentals-crud.php \
  -H "Content-Type: application/json" \
  -b cookies2.txt \
  -d '{
    "item_id": 2,
    "start_date": "2026-08-15",
    "end_date": "2026-08-17"
  }'
```

**Step 4: Lihat daftar rental Sarah**

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/rentals-crud.php?action=list" \
  -b cookies2.txt
```

**Step 5: Lihat riwayat rental dengan pagination**

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/rentals-crud.php?action=history&page=1&limit=5" \
  -b cookies2.txt
```

---

## Error Examples

### Error 1: Validasi Input (422)

```bash
curl -X POST http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "name": "TV",
    "category": "Electronics",
    "description": "Pendek",
    "price_per_day": -100,
    "location": ""
  }'
```

Response:
```json
{
  "success": false,
  "message": "Nama barang minimal 3 karakter, Harga per hari harus lebih dari 0, Lokasi harus diisi",
  "data": null
}
```

### Error 2: Tidak Authorized (401)

```bash
curl -X GET "http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php?action=list"
```

Response:
```json
{
  "success": false,
  "message": "Anda harus login terlebih dahulu",
  "data": null
}
```

### Error 3: Forbidden Access (403)

User 2 coba edit item milik user 1:

```bash
curl -X PUT http://localhost/RENTALIN_TENSTING%20-%20Copy/src/pages/api/items-crud.php \
  -H "Content-Type: application/json" \
  -b cookies2.txt \
  -d '{
    "id": 1,
    "name": "Updated Name",
    "category": "Camera",
    "description": "Attempting to edit other user item",
    "price_per_day": 150000,
    "location": "Kampus",
    "availability": true
  }'
```

Response:
```json
{
  "success": false,
  "message": "Anda tidak memiliki hak akses",
  "data": null
}
```

---

## Tips Testing dengan Postman

1. **Buat Collection** dengan nama "Rentalin Demo"
2. **Buat Environment Variable**:
   - `base_url` = `http://localhost/RENTALIN_TENSTING%20-%20Copy`
   - `user1_email` = `budi.santoso@university.edu`
   - `user2_email` = `sarah.putri@university.edu`

3. **Gunakan Pre-request Script** untuk auto-login:
   ```javascript
   pm.sendRequest({
     url: pm.variables.get("base_url") + "/src/pages/api/auth_login.php",
     method: 'POST',
     header: { 'Content-Type': 'application/json' },
     body: {
       mode: 'raw',
       raw: JSON.stringify({
         email: pm.variables.get("user1_email"),
         password: "password"
       })
     }
   }, (err, response) => {
     if (!err) pm.variables.set("auth_user", response.json().user);
   });
   ```

---

## Troubleshooting

### Session/Cookie Issue
Pastikan menggunakan `-b cookies.txt` untuk menyimpan session cookie dari login.

### Database Connection Error
Cek:
```bash
mysql -h localhost -uroot -proot -e "USE tensting_db; SELECT COUNT(*) FROM users;"
```

### CORS Issue (jika testing dari domain lain)
Tambahkan header ke api file:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

---

## Next Steps untuk Demo

1. ✅ Setup database dengan seed data
2. ✅ Test semua endpoint dengan curl
3. ✅ Test di Postman
4. ✅ Buka UI di browser (http://localhost/RENTALIN_TENSTING%20-%20Copy)
5. ✅ Demo live kepada client
