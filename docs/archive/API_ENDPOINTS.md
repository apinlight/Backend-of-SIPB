# SIPB API Endpoints Documentation

**Last Updated:** October 28, 2025  
**API Version:** 1.0  
**Base URL:** `/api/v1`

---

## Table of Contents
- [Public Endpoints](#public-endpoints)
- [Authentication Endpoints](#authentication-endpoints)
- [User Management](#user-management)
- [Barang (Items)](#barang-items)
- [Jenis Barang (Item Categories)](#jenis-barang-item-categories)
- [Pengajuan (Procurement Requests)](#pengajuan-procurement-requests)
- [Detail Pengajuan](#detail-pengajuan)
- [Gudang (Warehouse/Stock)](#gudang-warehousestock)
- [Penggunaan Barang (Item Usage)](#penggunaan-barang-item-usage)
- [Batas Barang (Item Limits)](#batas-barang-item-limits)
- [Global Settings](#global-settings)
- [Laporan (Reports)](#laporan-reports)

---

## Legend

**Access Levels:**
- 🌐 **Public** - No authentication required
- 🔓 **Authenticated** - Any logged-in user
- 👤 **User** - User role
- 👔 **Manager** - Manager role
- 🔐 **Admin** - Admin role only
- 👔🔐 **Manager/Admin** - Manager or Admin role

---

## Public Endpoints

### Health & Status

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/online` | 🌐 Public | Check if API is online |
| GET | `/api/v1/health` | 🌐 Public | Health check with timestamp |
| GET | `/` | 🌐 Public | API service information (JSON) |

**Response Example:**
```json
{
  "message": "API is online"
}
```

---

## Authentication Endpoints

### Public Auth (No Token Required)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | `/api/v1/register` | 🌐 Public | Register new user account |
| POST | `/api/v1/login` | 🌐 Public | Login and obtain access token |

**Rate Limit:** 5 requests/minute

### Protected Auth (Token Required)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | `/api/v1/refresh-token` | 🔓 Authenticated | Refresh access token |
| POST | `/api/v1/logout` | 🔓 Authenticated | Logout current session |
| POST | `/api/v1/logout-all` | 🔓 Authenticated | Logout all sessions |
| GET | `/api/v1/me` | 🔓 Authenticated | Get current user info |
| POST | `/api/v1/validate-token` | 🔓 Authenticated | Validate token validity |
| POST | `/api/v1/change-password` | 🔓 Authenticated | Change password (3 req/5min) |
| GET | `/api/v1/sessions` | 🔓 Authenticated | Get active sessions/tokens |
| DELETE | `/api/v1/sessions/{tokenId}` | 🔓 Authenticated | Revoke specific session |
| DELETE | `/api/v1/sessions/expired` | 🔓 Authenticated | Clean expired tokens |

**Rate Limit:** 30 requests/minute (except change-password: 3/5min)

---

## User Management

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/profile` | 🔓 Authenticated | Get own profile |
| PUT | `/api/v1/profile` | 🔓 Authenticated | Update own profile |
| GET | `/api/v1/users` | 🔓 Authenticated | List all users (scoped by role) |
| POST | `/api/v1/users` | 🔐 Admin | Create new user |
| GET | `/api/v1/users/{id}` | 🔓 Authenticated | Get user details |
| PUT | `/api/v1/users/{id}` | 🔐 Admin | Update user |
| DELETE | `/api/v1/users/{id}` | 🔐 Admin | Delete user |
| POST | `/api/v1/users/{id}/toggle-status` | 🔐 Admin | Activate/deactivate user |
| POST | `/api/v1/users/{id}/reset-password` | 🔐 Admin | Reset user password |

**Authorization:**
- Admin: Can manage all users
- Manager: Can view users in their branch
- User: Can only view own profile

---

## Barang (Items)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/barang` | 🔓 Authenticated | List all items (paginated) |
| POST | `/api/v1/barang` | 🔐 Admin | Create new item |
| GET | `/api/v1/barang/{id}` | 🔓 Authenticated | Get item details |
| PUT | `/api/v1/barang/{id}` | 🔐 Admin | Update item |
| DELETE | `/api/v1/barang/{id}` | 🔐 Admin | Delete item |

**Business Logic:**
- All users can view items
- Only Admin can create/edit/delete items
- Items must have valid `jenis_barang` (category)

---

## Jenis Barang (Item Categories)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/jenis-barang` | 🔓 Authenticated | List all categories |
| POST | `/api/v1/jenis-barang` | 🔐 Admin | Create category |
| GET | `/api/v1/jenis-barang/{id}` | 🔓 Authenticated | Get category details |
| PUT | `/api/v1/jenis-barang/{id}` | 🔐 Admin | Update category |
| DELETE | `/api/v1/jenis-barang/{id}` | 🔐 Admin | Delete category |
| GET | `/api/v1/jenis-barang/list/active` | 🔓 Authenticated | Get only active categories |
| POST | `/api/v1/jenis-barang/{id}/toggle-status` | 🔐 Admin | Activate/deactivate category |

**Business Logic:**
- Active categories can be assigned to items
- Inactive categories are hidden from selection but preserve historical data

---

## Pengajuan (Procurement Requests)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/pengajuan` | 🔓 Authenticated | List pengajuan (scoped by role) |
| POST | `/api/v1/pengajuan` | 👤👔 User/Manager | Create new pengajuan |
| GET | `/api/v1/pengajuan/{id}` | 🔓 Authenticated | Get pengajuan details |
| PUT | `/api/v1/pengajuan/{id}` | 👔🔐 Manager/Admin | Update status (approve/reject) |
| DELETE | `/api/v1/pengajuan/{id}` | 👤👔🔐 Owner/Admin | Delete pengajuan (if pending) |
| GET | `/api/v1/pengajuan/info/barang` | 🔓 Authenticated | Get items available for request |
| GET | `/api/v1/pengajuan/info/barang-history/{id_barang}` | 🔓 Authenticated | Get item procurement history |

**Authorization:**
- User: Can view/create/delete own pengajuan
- Manager: Can view branch pengajuan, approve/reject
- Admin: Can view all, approve/reject, manage stock transfer

**Status Flow:**
1. `Menunggu Persetujuan` (Pending) - Initial status
2. `Disetujui` (Approved) - Admin/Manager approved, stock transferred
3. `Ditolak` (Rejected) - Rejected with reason
4. `Selesai` (Completed) - Optional future status

**Business Logic:**
- Stock validation on approval
- Automatic stock transfer from admin/manager to user warehouse
- Monthly submission limit enforced (configurable)
- File upload support for `tipe_pengajuan: mandiri`

---

## Detail Pengajuan

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/detail-pengajuan` | 🔓 Authenticated | List detail pengajuan |
| POST | `/api/v1/detail-pengajuan` | 🔐 Admin | Create detail (manual procurement) |
| GET | `/api/v1/detail-pengajuan/{id}` | 🔓 Authenticated | Get detail |
| PUT | `/api/v1/detail-pengajuan/{id}` | 🔐 Admin | Update detail |
| DELETE | `/api/v1/detail-pengajuan/{id}` | 🔐 Admin | Delete detail |

**Purpose:**
- Manage individual items within a pengajuan
- Used for manual procurement entry by admin

---

## Gudang (Warehouse/Stock)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/gudang` | 🔓 Authenticated | List stock (scoped by role) |
| POST | `/api/v1/gudang` | 🔐 Admin | Add stock entry |
| GET | `/api/v1/gudang/{unique_id}/{id_barang}` | 🔓 Authenticated | Get specific stock |
| PUT | `/api/v1/gudang/{unique_id}/{id_barang}` | 🔐 Admin | Update stock |
| DELETE | `/api/v1/gudang/{unique_id}/{id_barang}` | 🔐 Admin | Remove stock entry |
| POST | `/api/v1/gudang/{unique_id}/{id_barang}/adjust-stock` | 🔐 Admin | Manually adjust stock quantity |
| GET | `/api/v1/stok/tersedia` | 🔓 Authenticated | Get own available stock |
| GET | `/api/v1/stok/tersedia/{id_barang}` | 🔓 Authenticated | Get stock for specific item |

**Authorization:**
- User: Can view own warehouse stock
- Manager: Can view branch warehouses
- Admin: Can view/manage all warehouses

**Composite Primary Key:** `(unique_id, id_barang)`

---

## Penggunaan Barang (Item Usage)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/penggunaan-barang` | 🔓 Authenticated | List usage records |
| POST | `/api/v1/penggunaan-barang` | 🔓 Authenticated | Record item usage |
| GET | `/api/v1/penggunaan-barang/{id}` | 🔓 Authenticated | Get usage details |
| PUT | `/api/v1/penggunaan-barang/{id}` | 🔓 Authenticated | Update usage record |
| DELETE | `/api/v1/penggunaan-barang/{id}` | 🔓 Authenticated | Delete usage record |
| POST | `/api/v1/penggunaan-barang/{id}/approve` | 👔🔐 Manager/Admin | Approve usage |
| POST | `/api/v1/penggunaan-barang/{id}/reject` | 👔🔐 Manager/Admin | Reject usage |
| GET | `/api/v1/penggunaan-barang/pending/approvals` | 👔🔐 Manager/Admin | Get pending approvals |

**Business Logic:**
- Users record item usage from their warehouse
- Manager/Admin approve or reject usage
- Approved usage decrements stock
- Tracks usage purpose and quantity

---

## Batas Barang (Item Limits)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/batas-barang` | 🔓 Authenticated | List all item limits |
| POST | `/api/v1/batas-barang` | 🔐 Admin | Create item limit |
| GET | `/api/v1/batas-barang/{id}` | 🔓 Authenticated | Get limit details |
| PUT | `/api/v1/batas-barang/{id}` | 🔐 Admin | Update limit |
| DELETE | `/api/v1/batas-barang/{id}` | 🔐 Admin | Delete limit |
| POST | `/api/v1/batas-barang/check-allocation` | 🔓 Authenticated | Check if allocation is within limits |

**Purpose:**
- Set minimum stock thresholds per item
- Alert when stock falls below threshold
- Status: `Aman`, `Menipis`, `Kritis`, `Habis`

---

## Global Settings

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/global-settings` | 🔐 Admin | Get all global settings |
| GET | `/api/v1/global-settings/monthly-limit` | 🔐 Admin | Get monthly pengajuan limit |
| PUT | `/api/v1/global-settings/monthly-limit` | 🔐 Admin | Update monthly limit |

**Settings:**
- **Monthly Limit:** Max pengajuan per user per month (default: 5)

---

## Laporan (Reports)

### Report Data Endpoints

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/laporan/summary` | 🔓 Authenticated | Get summary report (all pengajuan) |
| GET | `/api/v1/laporan/barang` | 🔓 Authenticated | Get item/stock report |
| GET | `/api/v1/laporan/pengajuan` | 🔓 Authenticated | Get pengajuan report |
| GET | `/api/v1/laporan/cabang` | 👔🔐 Manager/Admin | Get branch report |
| GET | `/api/v1/laporan/penggunaan` | 👔🔐 Manager/Admin | Get usage report |
| GET | `/api/v1/laporan/stok` | 👔🔐 Manager/Admin | Get stock report |
| GET | `/api/v1/laporan/stok-summary` | 👔🔐 Manager/Admin | Get stock summary |

**Query Parameters:**
- `start_date` - Filter by start date (YYYY-MM-DD)
- `end_date` - Filter by end date (YYYY-MM-DD)
- `period` - Predefined period (last_7_days, last_30_days, this_month, last_month, this_year)
- `branch` - Filter by branch (admin only)
- `status` - Filter by status

**Response Format:**
```json
{
  "summary": {
    "total_items": 150,
    "total_value": 50000000
  },
  "details": [
    { "id": "...", "nama": "...", "jumlah": 10 }
  ],
  "by_status": {
    "Aman": 100,
    "Menipis": 30,
    "Kritis": 15,
    "Habis": 5
  }
}
```

### Excel Export Endpoints

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/laporan/export/summary` | 👔🔐 Manager/Admin | Export summary to Excel |
| GET | `/api/v1/laporan/export/barang` | 👔🔐 Manager/Admin | Export item report to Excel |
| GET | `/api/v1/laporan/export/pengajuan` | 👔🔐 Manager/Admin | Export pengajuan to Excel |
| GET | `/api/v1/laporan/export/penggunaan` | 👔🔐 Manager/Admin | Export usage to Excel |
| GET | `/api/v1/laporan/export/stok` | 👔🔐 Manager/Admin | Export stock to Excel |
| GET | `/api/v1/laporan/export/all` | 👔🔐 Manager/Admin | Export all reports to Excel |

**Response:**
- Content-Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Filename: `Report_Type_YYYY-MM-DD_HH-mm-ss.xlsx`

**Excel Features:**
- Multiple sheets (Summary, Details, Filters)
- Professional formatting with colors
- Alternating row backgrounds
- Status color-coding
- Export metadata (user, timestamp, filters)

### Word Export Endpoints

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/api/v1/laporan/export-word/summary` | 👔🔐 Manager/Admin | Export summary to Word |
| GET | `/api/v1/laporan/export-word/barang` | 👔🔐 Manager/Admin | Export item report to Word |

**Response:**
- Content-Type: `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
- Filename: `Report_Type_YYYY-MM-DD_HH-mm-ss.docx`

**Word Features:**
- Professional document formatting
- Color-coded tables (blue headers, green/red status)
- Landscape orientation for wide tables (Barang report)
- Alternating row backgrounds
- Export metadata and footer

---

## Error Responses

### Standard Error Format
```json
{
  "status": false,
  "message": "Error message here",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `204` - No Content (successful deletion)
- `400` - Bad Request
- `401` - Unauthenticated
- `403` - Unauthorized (insufficient permissions)
- `404` - Not Found
- `405` - Method Not Allowed
- `422` - Validation Error
- `429` - Too Many Requests (rate limit)
- `500` - Internal Server Error

---

## Rate Limiting

| Route Group | Rate Limit |
|-------------|------------|
| Public Auth (`/register`, `/login`) | 5 requests/minute |
| Public API (`/online`, `/health`) | 30 requests/minute |
| Protected API (authenticated) | 100 requests/minute |
| Manager routes | 150 requests/minute |
| Admin routes | 200 requests/minute |
| Password change | 3 requests/5 minutes |
| Debug routes (dev only) | 1000 requests/minute |

**Headers Returned:**
- `X-RateLimit-Limit` - Max requests allowed
- `X-RateLimit-Remaining` - Requests remaining
- `Retry-After` - Seconds until rate limit resets (when exceeded)

---

## Authentication

### Sanctum Token-Based Auth

**How to Authenticate:**
1. Login via `POST /api/v1/login`
2. Receive `access_token` in response
3. Include token in all subsequent requests:

```http
Authorization: Bearer {access_token}
```

**Token Lifetime:**
- Default: 60 minutes
- Configurable in `config/sanctum.php`

**Token Management:**
- Tokens stored in `personal_access_tokens` table
- Can have multiple active tokens (different devices)
- Can revoke individual tokens or all at once

---

## CORS Configuration

**Allowed Origins:**
- Development: `http://localhost:5173`, `http://localhost:3000`
- Production: Configured via `FRONTEND_URL` environment variable

**Allowed Methods:**
- GET, POST, PUT, PATCH, DELETE, OPTIONS

**Allowed Headers:**
- Content-Type, Authorization, X-Requested-With

**Exposed Headers:**
- Content-Disposition (for file downloads)

---

## Pagination

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20, max: 100)

**Response Format:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  },
  "links": {
    "first": "http://api.example.com/items?page=1",
    "last": "http://api.example.com/items?page=5",
    "prev": null,
    "next": "http://api.example.com/items?page=2"
  }
}
```

---

## Security Headers

All responses include security headers:
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: no-referrer-when-downgrade`
- `Content-Security-Policy` (production only)
- `Strict-Transport-Security` (production HTTPS only)

---

## Notes

1. **Database:** MariaDB/MySQL with custom table names (prefix: `tb_`)
2. **Primary Keys:** UUID/ULID (not auto-increment integers)
3. **Timestamps:** All models have `created_at` and `updated_at`
4. **Soft Deletes:** Not implemented (hard deletes only)
5. **File Uploads:** Stored in `storage/app/public/`
6. **Logs:** Available via Laravel Telescope (development only)

---

## Environment Variables

Key environment variables for API configuration:

```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SESSION_DOMAIN=localhost

CORS_ALLOWED_ORIGINS=http://localhost:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sipb
```

---

## Support

- **Documentation:** [GitHub Repository](https://github.com/apinlight/backend)
- **API Issues:** Create issue on GitHub
- **Security:** Report vulnerabilities privately

---

**Generated by:** SIPB Backend Team  
**Last Review:** October 28, 2025
