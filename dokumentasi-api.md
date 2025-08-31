# SIPB API Documentation

## Base URL
```
https://sipb.crulxproject.com/api/v1
```

---

## Health Check

```http
GET /api/v1/online
```
**Response:**  
```json
{ "message": "API is online" }
```

---

## Authentication

### Register
```http
POST /api/register
```
**Request:**
```json
{
    "username": "string",
    "email": "string",
    "password": "string",
    "branch_name": "string"
}
```
**Response:**
```json
{
    "status": true,
    "message": "Registration successful",
    "user": { /* user fields */ }
}
```

### Login
```http
POST /api/login
```
**Request:**
```json
{
    "login": "string",     // username or email
    "password": "string"
}
```
**Response:**
```json
{
    "status": true,
    "message": "Login successful",
    "user": { /* user fields */ },
    "token": "string"
}
```

### Logout
```http
POST /api/logout
```
**Header:**  
`Authorization: Bearer <token>`

**Response:**  
```json
{
    "status": true,
    "message": "Logout successful"
}
```

### Forgot Password
```http
POST /api/forgot-password
```
**Request:**
```json
{
    "email": "string"
}
```
**Response:**
```json
{
    "status": true,
    "message": "Password reset link sent"
}
```

### Reset Password
```http
POST /api/reset-password
```
**Request:**
```json
{
    "email": "string",
    "token": "string",
    "password": "string",
    "password_confirmation": "string"
}
```
**Response:**
```json
{
    "status": true,
    "message": "Password reset successful"
}
```

### Email Verification
```http
POST /api/email/verification-notification
GET /api/verify-email/{id}/{hash}
```
**Response:**  
```json
{
    "status": true,
    "message": "Verification email sent"
}
```

---

## Authorization

All endpoints below require:
```http
Authorization: Bearer <token>
```

---

## Role Access Matrix

| Endpoint Group         | Admin | Manager | User  |
|----------------------- |:-----:|:-------:|:-----:|
| `/users`               |   ✓   |         |       |
| `/profile`             |   ✓   |   ✓     |   ✓   |
| `/jenis-barang`        |   ✓   |         |       |
| `/barang` (GET)        |   ✓   |   ✓     |   ✓   |
| `/barang` (POST/PUT/DELETE) | ✓ |       |       |
| `/pengajuan` (GET)     |   ✓   |   ✓     |   ✓   |
| `/pengajuan` (POST)    |       |         |   ✓   |
| `/pengajuan` (PUT/DELETE) | ✓  |         | ✓\*   |
| `/gudang` (GET)        |   ✓   |   ✓     |   ✓   |
| `/gudang` (POST/PUT/DELETE) | ✓ |       |       |
| `/penggunaan-barang` (GET) | ✓ |   ✓     |   ✓   |
| `/penggunaan-barang` (POST) |  |         |   ✓   |
| `/penggunaan-barang` (PUT/DELETE) | ✓ |  | ✓\*   |
| `/penggunaan-barang/approve` | ✓ | ✓   |       |
| `/stok-tersedia`       |   ✓   |   ✓     |   ✓   |
| `/batas-barang`        |   ✓   |         |       |
| `/global-settings`     |   ✓   |         |       |
| `/pengajuan/barang-info` | ✓ |   ✓     |   ✓   |
| `/pending-approvals`   |   ✓   |   ✓     |       |
| `/laporan/summary`     |   ✓   |   ✓     |       |
| `/laporan/barang`      |   ✓   |   ✓     |       |
| `/laporan/pengajuan`   |   ✓   |   ✓     |       |
| `/laporan/cabang`      |   ✓   |   ✓     |       |
| `/laporan/penggunaan`  |   ✓   |   ✓     |       |
| `/laporan/stok`        |   ✓   |   ✓     |       |
| `/laporan/export/*` (Excel) | ✓ |   ✓     |       |

\*User can only update/delete their own records with appropriate status.  
\*\*Excel export endpoints are available for Admin and Manager roles only.  
\*\*\*Global settings management is Admin only, but some endpoints are accessible to all roles.

---

## Validation Rules

### Register
- `username`: required, string, unique
- `email`: required, email, unique
- `password`: required, min:8
- `branch_name`: required, string

### Login
- `login`: required, string (username or email)
- `password`: required, string

### Barang
- `id_barang`: required, string, unique
- `nama_barang`: required, string
- `id_jenis_barang`: required, exists:jenis_barang,id_jenis_barang
- `harga_barang`: required, numeric, min:0

### Pengajuan
- `id_pengajuan`: required, string, unique
- `unique_id`: required, exists:users,unique_id
- `status_pengajuan`: required, in:Menunggu Persetujuan,Disetujui,Ditolak
- `tipe_pengajuan`: optional, in:biasa,manual (default: biasa)

### Penggunaan Barang
- `id_barang`: required, exists:barang,id_barang
- `jumlah_digunakan`: required, integer, min:1
- `keperluan`: required, string, max:255
- `tanggal_penggunaan`: required, date
- `keterangan`: optional, string, max:1000

### Gudang
- `unique_id`: required, exists:users,unique_id
- `id_barang`: required, exists:barang,id_barang
- `jumlah_barang`: required, integer, min:0

### Global Settings
- `monthly_limit`: required, integer, min:1, max:50

### Pengajuan Helper Endpoints
- `search`: optional, string, max:255
- `months`: optional, integer, min:1, max:12

---

## Error Response Examples

**401 Unauthorized**
```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden**
```json
{
    "message": "This action is unauthorized."
}
```

**422 Validation Error**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": [
            "The field_name field is required."
        ]
    }
}
```

---

## Endpoints

### Users
- `GET    /api/v1/users` — List users  
  **Response:**
  ```json
  {
      "data": [
          {
              "unique_id": "string",
              "username": "string",
              "email": "string",
              "branch_name": "string",
              "roles": ["admin" | "user" | "manager"]
          }
      ]
  }
  ```
- `POST   /api/v1/users` — Create user  
  **Request:**
  ```json
  {
      "username": "string",
      "email": "string",
      "password": "string",
      "branch_name": "string",
      "role": "admin" | "user" | "manager"
  }
  ```
- `GET    /api/v1/users/{user}` — Get user detail  
- `PUT    /api/v1/users/{user}` — Update user  
- `DELETE /api/v1/users/{user}` — Delete user
- `PUT    /api/v1/users/{unique_id}/toggle-status` — Toggle user status
- `PUT    /api/v1/users/{unique_id}/reset-password` — Reset user password
- `GET    /api/v1/profile` — Get current user profile
- `PUT    /api/v1/profile` — Update current user profile  

---

### Jenis Barang
- `GET    /api/v1/jenis-barang` — List jenis barang  
  **Response:**
  ```json
  {
      "data": [
          {
              "id_jenis_barang": "string",
              "nama_jenis_barang": "string"
          }
      ]
  }
  ```
- `POST   /api/v1/jenis-barang` — Create jenis barang  
  **Request:**
  ```json
  {
      "nama_jenis_barang": "string"
  }
  ```
- `GET    /api/v1/jenis-barang/{jenis_barang}` — Get jenis barang  
- `PUT    /api/v1/jenis-barang/{jenis_barang}` — Update jenis barang  
- `DELETE /api/v1/jenis-barang/{jenis_barang}` — Delete jenis barang
- `PUT    /api/v1/jenis-barang/{id}/toggle-status` — Toggle jenis barang status
- `GET    /api/v1/jenis-barang/active` — Get active jenis barang only  

---

### Barang
- `GET    /api/v1/barang` — List barang  
  **Response:**
  ```json
  {
      "data": [
          {
              "id_barang": "string",
              "nama_barang": "string",
              "id_jenis_barang": "string",
              "harga_barang": number,
              "jenis_barang": {
                  "id_jenis_barang": "string",
                  "nama_jenis_barang": "string"
              }
          }
      ]
  }
  ```
- `POST   /api/v1/barang` — Create barang  
  **Request:**
  ```json
  {
      "id_barang": "string",
      "nama_barang": "string",
      "id_jenis_barang": "string",
      "harga_barang": number
  }
  ```
- `GET    /api/v1/barang/{barang}` — Get barang  
- `PUT    /api/v1/barang/{barang}` — Update barang  
- `DELETE /api/v1/barang/{barang}` — Delete barang
- `GET    /api/v1/barang/stock-summary` — Get stock summary statistics  

---

### Pengajuan
- `GET    /api/v1/pengajuan` — List pengajuan  
  **Response:**
  ```json
  {
      "data": [
          {
              "id_pengajuan": "string",
              "unique_id": "string",
              "status_pengajuan": "Menunggu Persetujuan" | "Disetujui" | "Ditolak",
              "tipe_pengajuan": "biasa" | "manual",
              "user": {
                  "unique_id": "string",
                  "username": "string"
              },
              "details": [
                  {
                      "id_barang": "string",
                      "jumlah": number,
                      "barang": {
                          "nama_barang": "string"
                      }
                  }
              ]
          }
      ]
  }
  ```
- `POST   /api/v1/pengajuan` — Create pengajuan  
  **Request:**
  ```json
  {
      "id_pengajuan": "string",
      "unique_id": "string",
      "status_pengajuan": "Menunggu Persetujuan",
      "tipe_pengajuan": "biasa" //atau "manual"
  }
  ```
- `GET    /api/v1/pengajuan/{pengajuan}` — Get pengajuan  
- `PUT    /api/v1/pengajuan/{pengajuan}` — Update pengajuan  
- `DELETE /api/v1/pengajuan/{pengajuan}` — Delete pengajuan  

#### Pengajuan Helper Endpoints
- `GET    /api/v1/pengajuan/barang-info` — Get item information for creating requests
  **Query Parameters:**
  - `search`: Search items by name
  
  **Response:**
  ```json
  {
      "status": true,
      "data": {
          "barang": [
              {
                  "id_barang": "string",
                  "nama_barang": "string",
                  "harga_barang": number,
                  "jenis_barang": {
                      "nama_jenis": "string"
                  }
              }
          ],
          "userStock": {
              "BRG001": 5,
              "BRG002": 10
          },
          "monthlyLimit": 20,
          "monthlyUsed": 15
      }
  }
  ```

- `GET    /api/v1/pengajuan/barang-history/{id_barang}` — Get item request history
  **Query Parameters:**
  - `months`: Number of months to look back (default: 6)
  
  **Response:**
  ```json
  {
      "status": true,
      "data": {
          "history": [
              {
                  "id_pengajuan": "string",
                  "jumlah": number,
                  "status_pengajuan": "string",
                  "created_at": "datetime"
              }
          ],
          "summary": {
              "total_requested": 25,
              "approved_count": 8,
              "pending_count": 1,
              "rejected_count": 1
          }
      }
  }
  ```

---

### Detail Pengajuan
- `GET    /api/v1/detail-pengajuan` — List all detail pengajuan  
  **Response:**
  ```json
  {
      "data": [
          {
              "id_pengajuan": "string",
              "id_barang": "string",
              "jumlah": number,
              "barang": {
                  "nama_barang": "string"
              }, 
          }
      ]
  }
  ```
- `POST   /api/v1/detail-pengajuan` — Create detail pengajuan  
  **Request:**
  ```json
  {
      "id_pengajuan": "string",
      "id_barang": "string",
      "jumlah": number
  }
  ```
- `GET    /api/v1/detail-pengajuan/{id_pengajuan}/{id_barang}` — Get detail  
- `PUT    /api/v1/detail-pengajuan/{id_pengajuan}/{id_barang}` — Update detail  
- `DELETE /api/v1/detail-pengajuan/{id_pengajuan}/{id_barang}` — Delete detail
- `POST   /api/v1/detail-pengajuan/bulk-create` — Bulk create detail pengajuan
- `GET    /api/v1/detail-pengajuan/by-pengajuan/{id_pengajuan}` — Get details by pengajuan ID  

---

### Gudang
- `GET    /api/v1/gudang` — List gudang  
  **Response:**
  ```json
  {
      "data": [
          {
              "unique_id": "string",
              "id_barang": "string",
              "jumlah_barang": number,
              "user": {
                  "username": "string"
              },
              "barang": {
                  "nama_barang": "string"
              }
          }
      ]
  }
  ```
- `POST   /api/v1/gudang` — Create gudang entry  
  **Request:**
  ```json
  {
      "unique_id": "string",
      "id_barang": "string",
      "jumlah_barang": number
  }
  ```
- `GET    /api/v1/gudang/{unique_id}/{id_barang}` — Get gudang entry  
- `PUT    /api/v1/gudang/{unique_id}/{id_barang}` — Update gudang entry  
- `DELETE /api/v1/gudang/{unique_id}/{id_barang}` — Delete gudang entry
- `PUT    /api/v1/gudang/{unique_id}/{id_barang}/adjust-stock` — Adjust stock quantity  

---

### 🆕 Penggunaan Barang (Item Usage)
- `GET    /api/v1/penggunaan-barang` — List penggunaan barang  
  **Query Parameters:**
  - `status`: pending, approved, rejected
  - `id_barang`: filter by item
  - `tanggal_dari`: start date filter
  - `tanggal_sampai`: end date filter
  - `search`: search in item name or purpose
  - `page`: pagination page
  - `per_page`: items per page (default: 15)
  
  **Response:**
  ```json
  {
      "success": true,
      "message": "Data penggunaan barang berhasil diambil",
      "data": [
          {
              "id_penggunaan": number,
              "unique_id": "string",
              "id_barang": "string",
              "jumlah_digunakan": number,
              "keperluan": "string",
              "tanggal_penggunaan": "date",
              "keterangan": "string",
              "status": "pending" | "approved" | "rejected",
              "approved_by": "string",
              "approved_at": "datetime",
              "created_at": "datetime",
              "updated_at": "datetime",
              "user": {
                  "unique_id": "string",
                  "username": "string",
                  "branch_name": "string"
              },
              "barang": {
                  "id_barang": "string",
                  "nama_barang": "string",
                  "harga_barang": number,
                  "jenis_barang": {
                      "nama_jenis_barang": "string"
                  }
              },
              "approver": {
                  "username": "string"
              }
          }
      ],
      "meta": {
          "current_page": number,
          "last_page": number,
          "per_page": number,
          "total": number,
          "from": number,
          "to": number
      }
  }
  ```

- `POST   /api/v1/penggunaan-barang` — Create penggunaan barang  
  **Request:**
  ```json
  {
      "id_barang": "string",
      "jumlah_digunakan": number,
      "keperluan": "string",
      "tanggal_penggunaan": "date",
      "keterangan": "string" // optional
  }
  ```
  **Response:**
  ```json
  {
      "success": true,
      "message": "Penggunaan barang berhasil dicatat",
      "data": {
          "id_penggunaan": number,
          "unique_id": "string",
          "id_barang": "string",
          "jumlah_digunakan": number,
          "keperluan": "string",
          "tanggal_penggunaan": "date",
          "keterangan": "string",
          "status": "approved",
          "approved_by": "string",
          "approved_at": "datetime",
          "user": { /* user object */ },
          "barang": { /* barang object */ }
      }
  }
  ```

- `GET    /api/v1/penggunaan-barang/{id}` — Get penggunaan barang detail  
  **Response:**
  ```json
  {
      "success": true,
      "message": "Detail penggunaan barang berhasil diambil",
      "data": {
          "id_penggunaan": number,
          "unique_id": "string",
          "id_barang": "string",
          "jumlah_digunakan": number,
          "keperluan": "string",
          "tanggal_penggunaan": "date",
          "keterangan": "string",
          "status": "approved" | "pending" | "rejected",
          "approved_by": "string",
          "approved_at": "datetime",
          "user": { /* user object */ },
          "barang": { /* barang object */ },
          "approver": { /* approver user object */ }
      }
  }
  ```

- `PUT    /api/v1/penggunaan-barang/{id}` — Update penggunaan barang  
  **Note:** Only allowed if status is 'pending'  
  **Request:**
  ```json
  {
      "jumlah_digunakan": number, // optional
      "keperluan": "string", // optional
      "tanggal_penggunaan": "date", // optional
      "keterangan": "string" // optional
  }
  ```
  **Response:**
  ```json
  {
      "success": true,
      "message": "Penggunaan barang berhasil diperbarui",
      "data": { /* updated penggunaan object */ }
  }
  ```

- `DELETE /api/v1/penggunaan-barang/{id}` — Delete penggunaan barang  
  **Note:** Only allowed if status is 'pending' or 'rejected'  
  **Response:**
  ```json
  {
      "success": true,
      "message": "Penggunaan barang berhasil dihapus"
  }
  ```

- `POST   /api/v1/penggunaan-barang/{id}/approve` — Approve penggunaan barang  
  **Access:** Admin, Manager only  
  **Response:**
  ```json
  {
      "success": true,
      "message": "Penggunaan barang berhasil disetujui",
      "data": { /* approved penggunaan object */ }
  }
  ```

- `POST   /api/v1/penggunaan-barang/{id}/reject` — Reject penggunaan barang  
  **Access:** Admin, Manager only  
  **Response:**
  ```json
  {
      "success": true,
      "message": "Penggunaan barang berhasil ditolak",
      "data": { /* rejected penggunaan object */ }
  }
  ```

#### Extended Penggunaan Barang Endpoints

- `GET    /api/v1/penggunaan-barang/my-requests` — Get current user's usage requests
  **Query Parameters:**
  - `status`: pending, approved, rejected
  - `page`: page number
  - `per_page`: items per page
  
  **Response:**
  ```json
  {
      "success": true,
      "message": "Data penggunaan barang berhasil diambil",
      "data": [
          {
              "id_penggunaan": number,
              "id_barang": "string",
              "jumlah_digunakan": number,
              "keperluan": "string",
              "status": "pending|approved|rejected",
              "tanggal_penggunaan": "date",
              "barang": { /* barang object */ }
          }
      ]
  }
  ```

- `GET    /api/v1/pending-approvals` — Get pending approvals for managers/admins
  **Access:** Manager, Admin only
  **Response:**
  ```json
  {
      "success": true,
      "message": "Data pending approvals berhasil diambil",
      "data": [
          {
              "id_penggunaan": number,
              "user": {
                  "username": "string",
                  "branch_name": "string"
              },
              "barang": {
                  "nama_barang": "string"
              },
              "jumlah_digunakan": number,
              "keperluan": "string",
              "tanggal_penggunaan": "date"
          }
      ]
  }
  ```

#### Admin Force Operations (Admin Only)

- `PUT    /api/v1/penggunaan-barang/{id}/force-update` — Force update usage record
  **Access:** Admin only
  **Note:** Can update any status, bypasses normal restrictions
  
- `DELETE /api/v1/penggunaan-barang/{id}/force-delete` — Force delete usage record
  **Access:** Admin only
  **Note:** Can delete any status, bypasses normal restrictions

---

### 🆕 Stok Tersedia (Available Stock)
- `GET    /api/v1/stok-tersedia` — Get available stock for current user  
  **Query Parameters:**
  - `id_barang`: filter by specific item
  - `search`: search items by name
  
  **Response:**
  ```json
  {
      "success": true,
      "message": "Data stok tersedia berhasil diambil",
      "data": [
          {
              "id_barang": "string",
              "nama_barang": "string",
              "jenis_barang": "string",
              "jumlah_tersedia": number,
              "harga_satuan": number,
              "total_nilai": number,
              "user_info": {
                  "unique_id": "string",
                  "username": "string",
                  "branch_name": "string"
              }
          }
      ],
      "meta": {
          "total_items": number,
          "total_stock": number,
          "total_value": number,
          "user_role": "string",
          "user_branch": "string"
      }
  }
  ```

- `GET    /api/v1/stok-tersedia/{id_barang}` — Get stock for specific item
  **Response:**
  ```json
  {
      "success": true,
      "message": "Data stok barang berhasil diambil",
      "data": {
          "id_barang": "string",
          "nama_barang": "string",
          "jenis_barang": "string",
          "jumlah_tersedia": number,
          "harga_satuan": number,
          "has_stock": boolean,
          "can_use": boolean
      }
  }
  ```

---

### Batas Barang
- `GET    /api/v1/batas-barang` — List batas barang  
- `POST   /api/v1/batas-barang` — Create batas barang  
- `GET    /api/v1/batas-barang/{id_barang}` — Get batas barang  
- `PUT    /api/v1/batas-barang/{id_barang}` — Update batas barang  
- `DELETE /api/v1/batas-barang/{id_barang}` — Delete batas barang
- `POST   /api/v1/batas-barang/check-allocation` — Check allocation status  

---

### Global Settings
- `GET    /api/v1/global-settings` — List all global settings
  **Response:**
  ```json
  {
      "status": true,
      "data": {
          "monthly_pengajuan_limit": 10,
          "system_name": "SIPB",
          "version": "1.0"
      }
  }
  ```

- `GET    /api/v1/global-settings/monthly-limit` — Get monthly pengajuan limit
  **Response:**
  ```json
  {
      "status": true,
      "data": {
          "monthly_limit": 10
      }
  }
  ```

- `PUT    /api/v1/global-settings/monthly-limit` — Update monthly pengajuan limit
  **Access:** Admin only
  **Request:**
  ```json
  {
      "monthly_limit": 15
  }
  ```
  **Response:**
  ```json
  {
      "status": true,
      "message": "Monthly pengajuan limit updated successfully",
      "data": {
          "monthly_limit": 15
      }
  }
  ```

---

### 📊 Reports (Data Analytics)

#### Basic Report Endpoints
- `GET    /api/v1/laporan/summary` — Get system overview summary
- `GET    /api/v1/laporan/barang` — Get item analysis report data
- `GET    /api/v1/laporan/pengajuan` — Get requests report data  
- `GET    /api/v1/laporan/cabang` — Get branch summary (Manager+)
- `GET    /api/v1/laporan/penggunaan` — Get usage analytics (Manager+)
- `GET    /api/v1/laporan/stok` — Get inventory status (Manager+)

#### Basic Report Parameters
**Query Parameters:**
- `period`: today, week, month, year, custom
- `start_date`: YYYY-MM-DD (required if period=custom)
- `end_date`: YYYY-MM-DD (required if period=custom)
- `branch`: Filter by branch name (Admin only)
- `status`: Filter by status (for pengajuan/penggunaan reports)

#### Example: Get Summary Report
```http
GET /api/v1/laporan/summary?period=month&branch=Jakarta
```
**Response:**
```json
{
    "status": true,
    "data": {
        "total_pengajuan": 45,
        "total_disetujui": 32,
        "total_menunggu": 8,
        "total_ditolak": 3,
        "total_selesai": 2,
        "total_nilai": 15750000
    }
}
```

---

### 📊 Excel Export (Reports)

#### Export Endpoints
- `GET    /api/v1/laporan/export/summary` — Export summary report
- `GET    /api/v1/laporan/export/barang` — Export item analysis report
- `GET    /api/v1/laporan/export/pengajuan` — Export requests report
- `GET    /api/v1/laporan/export/penggunaan` — Export usage analytics report (Manager+)
- `GET    /api/v1/laporan/export/stok` — Export inventory report (Manager+)
- `GET    /api/v1/laporan/export/all` — Export comprehensive report (Manager+)
- `GET    /api/v1/laporan/export?type={type}` — Legacy export with type parameter

#### Export Parameters
**Query Parameters:**
- `period`: today, week, month, year, custom
- `start_date`: YYYY-MM-DD (required if period=custom)
- `end_date`: YYYY-MM-DD (required if period=custom)
- `branch`: Filter by branch name (Admin only)
- `status`: Filter by status (for penggunaan export)
- `keperluan`: Filter by purpose (for penggunaan export)
- `stock_level`: empty, low, normal (for stok export)

#### Export Summary Report
```http
GET /api/v1/laporan/export/summary
```
**Query Parameters:**
```
period=month&branch=Jakarta
```
**Response:**
Excel file download with multiple sheets:
- Summary Report: Key metrics and totals
- Export Info: User, filters, generation metadata

**Example:**
```javascript
// Download summary report for current month
fetch('/api/v1/laporan/export/summary?period=month', {
    headers: {
        'Authorization': 'Bearer TOKEN',
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    }
}).then(response => response.blob())
  .then(blob => {
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'Summary_Report.xlsx';
      link.click();
  });
```

#### Export Barang Report
```http
GET /api/v1/laporan/export/barang
```
**Response:**
Excel file with sheets:
- Barang Detail: Item-by-item analysis with stock and procurement data
- Summary: Aggregated statistics and KPIs
- Export Info: Metadata

#### Export Pengajuan Report
```http
GET /api/v1/laporan/export/pengajuan
```
**Response:**
Excel file with sheets:
- Pengajuan Detail: Request details with user and status info
- Summary: Overall request statistics
- By Status: Status breakdown analysis
- Export Info: Metadata

#### Export Penggunaan Report (Manager+ Access)
```http
GET /api/v1/laporan/export/penggunaan
```
**Query Parameters:**
```
status=approved&tanggal_dari=2024-01-01&tanggal_sampai=2024-01-31
```
**Response:**
Excel file with sheets:
- Penggunaan Detail: Usage records with full details
- Summary: Usage statistics and totals
- By Barang: Item usage analysis
- By Branch: Branch comparison
- Export Info: Metadata

#### Export Stok Report (Manager+ Access)
```http
GET /api/v1/laporan/export/stok
```
**Query Parameters:**
```
stock_level=low&branch=Jakarta
```
**Response:**
Excel file with sheets:
- Stok Detail: Current inventory with user and item details
- Summary: Stock statistics and health metrics
- By Branch: Branch-level stock comparison
- Low Stock Alert: Items requiring attention (empty/low stock)
- Export Info: Metadata

#### Legacy Export (Backward Compatibility)
```http
GET /api/v1/laporan/export?type=summary
GET /api/v1/laporan/export?type=barang
GET /api/v1/laporan/export?type=pengajuan
GET /api/v1/laporan/export?type=penggunaan
GET /api/v1/laporan/export?type=stok
GET /api/v1/laporan/export?type=all
```

#### Export Access Control
| Export Type | Admin | Manager | User |
|-------------|:-----:|:-------:|:----:|
| Summary     |   ✓   |    ✓    |  ✓   |
| Barang      |   ✓   |    ✓    |  ✓   |
| Pengajuan   |   ✓   |    ✓    |  ✓   |
| Penggunaan  |   ✓   |    ✓    |  ❌  |
| Stok        |   ✓   |    ✓    |  ❌  |
| All         |   ✓   |    ✓    |  ❌  |

**Note:** All exports respect role-based data filtering:
- **Admin**: Can export all data across all branches
- **Manager**: Can export data from their branch only
- **User**: Can export basic reports with their accessible data

#### Export File Features
- **Multi-sheet workbooks** with professional formatting
- **Colored headers** and proper alignment
- **Number formatting** for currency (Rp) and percentages
- **Conditional formatting** for alerts and status indicators
- **Auto-sizing columns** for optimal readability
- **Export metadata** showing filters applied and generation info
- **File naming convention**: `{ReportType}_Report_YYYY-MM-DD_HH-mm-ss.xlsx`

#### Excel Export Error Responses

**403 Forbidden - Access Denied**
```json
{
    "status": false,
    "message": "Access denied - insufficient permissions"
}
```

**400 Bad Request - Invalid Type**
```json
{
    "status": false,
    "message": "Invalid export type. Available types: summary, barang, pengajuan, penggunaan, stok, all"
}
```

**500 Internal Server Error - Export Failed**
```json
{
    "status": false,
    "message": "Failed to export summary: [error details]"
}
```

---

## 🆕 Error Responses for Penggunaan Barang

**400 Bad Request - Insufficient Stock**
```json
{
    "success": false,
    "message": "Stok tidak mencukupi",
    "data": {
        "current_stock": 5,
        "requested": 10
    }
}
```

**400 Bad Request - Cannot Modify Approved Usage**
```json
{
    "success": false,
    "message": "Penggunaan barang yang sudah disetujui tidak dapat diubah"
}
```

**403 Forbidden - No Approval Access**
```json
{
    "success": false,
    "message": "Tidak memiliki akses untuk menyetujui penggunaan barang"
}
```

---

## 🆕 Business Logic Notes

### Penggunaan Barang Workflow:
1. **User creates usage record** → Status: `approved` (auto-approved)
2. **Stock automatically reduced** from gudang
3. **If insufficient stock** → Request rejected with error
4. **Admin/Manager can view** all usage records for their scope
5. **Users can only view** their own usage records

### Stock Management:
- **Gudang stock** represents actual available inventory
- **When items are used** → Stock reduces immediately
- **When stock reaches 0** → Gudang record is deleted
- **Negative stock not allowed** → Usage request will be rejected

### Access Control:
- **Admin**: Can see all usage records across all branches
- **Manager**: Can see usage records for their branch only  
- **User**: Can only see their own usage records
- **Approval rights**: Admin and Manager only (if approval workflow enabled)

---

## 📊 Complete API Endpoint Summary

### Core Functionality
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/api/v1/penggunaan-barang` | List usage records | All authenticated |
| POST | `/api/v1/penggunaan-barang` | Create usage record | User, Manager, Admin |
| GET | `/api/v1/penggunaan-barang/{id}` | Get usage detail | Owner, Manager, Admin |
| PUT | `/api/v1/penggunaan-barang/{id}` | Update usage (pending only) | Owner, Manager, Admin |
| DELETE | `/api/v1/penggunaan-barang/{id}` | Delete usage (pending/rejected only) | Owner, Manager, Admin |
| POST | `/api/v1/penggunaan-barang/{id}/approve` | Approve usage | Manager, Admin |
| POST | `/api/v1/penggunaan-barang/{id}/reject` | Reject usage | Manager, Admin |
| PUT | `/api/v1/penggunaan-barang/{id}/force-update` | Force update usage | Admin |
| DELETE | `/api/v1/penggunaan-barang/{id}/force-delete` | Force delete usage | Admin |
| GET | `/api/v1/stok-tersedia` | Get available stock | All authenticated |
| GET | `/api/v1/stok-tersedia/{id_barang}` | Get stock for specific item | All authenticated |

### Helper & Extended Endpoints
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/api/v1/pengajuan/barang-info` | Get item info for requests | All authenticated |
| GET | `/api/v1/pengajuan/barang-history/{id_barang}` | Get item request history | All authenticated |
| GET | `/api/v1/penggunaan-barang/my-requests` | Get user's own usage requests | User, Manager, Admin |
| GET | `/api/v1/pending-approvals` | Get pending approvals | Manager, Admin |
| PUT | `/api/v1/penggunaan-barang/{id}/force-update` | Force update usage | Admin |
| DELETE | `/api/v1/penggunaan-barang/{id}/force-delete` | Force delete usage | Admin |

### Global Settings Endpoints
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/api/v1/global-settings` | List all global settings | Admin |
| GET | `/api/v1/global-settings/monthly-limit` | Get monthly request limit | Admin |
| PUT | `/api/v1/global-settings/monthly-limit` | Update monthly request limit | Admin |

### Reports Endpoints (Complete List)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/api/v1/laporan/summary` | Get summary report data | Manager, Admin |
| GET | `/api/v1/laporan/barang` | Get item analysis data | Manager, Admin |
| GET | `/api/v1/laporan/pengajuan` | Get requests report data | Manager, Admin |
| GET | `/api/v1/laporan/cabang` | Get branch summary data | Manager, Admin |
| GET | `/api/v1/laporan/penggunaan` | Get usage analytics data | Manager, Admin |
| GET | `/api/v1/laporan/stok` | Get inventory status data | Manager, Admin |

### Excel Export Endpoints
| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| GET | `/api/v1/laporan/export/summary` | Export summary report | Manager, Admin |
| GET | `/api/v1/laporan/export/barang` | Export item analysis | Manager, Admin |
| GET | `/api/v1/laporan/export/pengajuan` | Export requests data | Manager, Admin |
| GET | `/api/v1/laporan/export/penggunaan` | Export usage analytics | Manager, Admin |
| GET | `/api/v1/laporan/export/stok` | Export inventory report | Manager, Admin |
| GET | `/api/v1/laporan/export/all` | Export comprehensive report | Manager, Admin |
| GET | `/api/v1/laporan/export?type={type}` | Legacy export with type | Manager, Admin |

---

## 🚀 Frontend Integration Examples

### JavaScript/Vue.js Usage:
```javascript
// Get available stock
const response = await API.get('/api/v1/stok-tersedia')
const availableStock = response.data.data

// Create usage record
const usageData = {
    id_barang: 'BRG001',
    jumlah_digunakan: 5,
    keperluan: 'Maintenance rutin',
    tanggal_penggunaan: '2024-01-15',
    keterangan: 'Untuk perbaikan AC kantor'
}
const result = await API.post('/api/v1/penggunaan-barang', usageData)

// Get usage history with filters
const params = {
    status: 'approved',
    tanggal_dari: '2024-01-01',
    tanggal_sampai: '2024-01-31',
    page: 1,
    per_page: 20
}
const history = await API.get('/api/v1/penggunaan-barang', { params })
```

### Excel Export Examples:
```javascript
// Export summary report
const exportSummaryReport = async (filters = {}) => {
    const params = new URLSearchParams(filters);
    const response = await fetch(`/api/v1/laporan/export/summary?${params}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
    });
    
    if (response.ok) {
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `Summary_Report_${new Date().toISOString().slice(0,10)}.xlsx`;
        link.click();
        URL.revokeObjectURL(url);
    }
};

// Export usage analytics with filters
const exportUsageReport = async () => {
    const filters = {
        period: 'month',
        status: 'approved',
        tanggal_dari: '2024-01-01',
        tanggal_sampai: '2024-01-31'
    };
    
    try {
        const params = new URLSearchParams(filters);
        const response = await fetch(`/api/v1/laporan/export/penggunaan?${params}`, {
            headers: {
                'Authorization': `Bearer ${userToken}`,
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            }
        });
        
        if (response.ok) {
            const blob = await response.blob();
            downloadFile(blob, 'Usage_Analytics_Report.xlsx');
        } else {
            const error = await response.json();
            console.error('Export failed:', error.message);
        }
    } catch (error) {
        console.error('Export error:', error);
    }
};

// Helper function for file download
const downloadFile = (blob, filename) => {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

// Export inventory report with stock level filter
const exportInventoryReport = async (stockLevel = null) => {
    const filters = stockLevel ? { stock_level: stockLevel } : {};
    const params = new URLSearchParams(filters);
    
    const response = await fetch(`/api/v1/laporan/export/stok?${params}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
    });
    
    if (response.ok) {
        const blob = await response.blob();
        const filename = stockLevel ? 
            `Inventory_${stockLevel}_Stock_Report.xlsx` : 
            'Inventory_Report.xlsx';
        downloadFile(blob, filename);
    }
};

// Export all reports (comprehensive)
const exportAllReports = async () => {
    const response = await fetch('/api/v1/laporan/export/all', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
    });
    
    if (response.ok) {
        const blob = await response.blob();
        downloadFile(blob, `Comprehensive_Report_${Date.now()}.xlsx`);
    }
};
```

This completes your **full inventory management system** with both **procurement**, **consumption tracking**, and **comprehensive Excel export functionality**! 🎯📊

## **🛠️ COMMANDS TO GENERATE/UPDATE FILES**

### **1. Run the Migration:**
```bash
php artisan make:migration create_tb_penggunaan_barang_table
```

### **2. Generate the Controller:**
```bash
php artisan make:controller Api/PenggunaanBarangController --api
```

### **3. Generate the Model:**
```bash
php artisan make:model PenggunaanBarang
```

### **4. Update Documentation:**
Replace your `dokumentasi-api.md` with the updated version above.

### **5. Run Migration:**
```bash
php artisan migrate
```

### **6. Clear Cache:**
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

## 📋 **Excel Export Package Information**

The SIPB system uses **Laravel Excel v3.1.64** (latest stable version) for robust Excel export functionality.

### **Package Details:**
- **Package**: `maatwebsite/excel:^3.1`
- **Version**: 3.1.64 (as of July 2025)
- **Dependencies**: phpoffice/phpspreadsheet ^1.29.9
- **Laravel Support**: 5.8 to 12.x
- **PHP Support**: 7.0+ to 8.4+

### **Key Features:**
- ✅ **Multi-sheet exports** with styling and formatting
- ✅ **Role-based access control** (Manager/Admin only)
- ✅ **Dynamic filtering** with query parameters
- ✅ **Conditional formatting** for stock alerts
- ✅ **Number formatting** for currency values
- ✅ **Metadata sheets** with filter information
- ✅ **Memory efficient** for large datasets

### **Export File Structure:**
Each export contains multiple sheets:
- **Detail Sheet**: Raw data with all records
- **Summary Sheet**: Aggregate metrics and totals
- **Analysis Sheets**: Groupings by status, branch, etc.
- **Filters Sheet**: Applied filters and metadata
- **Alert Sheets**: Low stock warnings (for stock exports)

### **Installation Commands:**
```bash
# Install the package
composer require "maatwebsite/excel:^3.1" --ignore-platform-req=ext-zip

# Publish configuration
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config

# Clear caches
php artisan config:clear && php artisan route:clear
```

