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
| `/jenis-barang`        |   ✓   |         |       |
| `/barang` (GET)        |   ✓   |   ✓     |   ✓   |
| `/barang` (POST/PUT/DELETE) | ✓ |       |       |
| `/pengajuan` (GET)     |   ✓   |   ✓     |   ✓   |
| `/pengajuan` (POST)    |       |         |   ✓   |
| `/pengajuan` (PUT/DELETE) | ✓  |         | ✓\*   |
| `/gudang` (GET)        |   ✓   |   ✓     |   ✓   |
| `/gudang` (POST/PUT/DELETE) | ✓ |       |       |
| `/batas-barang`        |   ✓   |         |       |
| `/batas-pengajuan`     |   ✓   |         |       |

\*User can only update/delete their own pengajuan with status "Menunggu Persetujuan".

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

### Gudang
- `unique_id`: required, exists:users,unique_id
- `id_barang`: required, exists:barang,id_barang
- `jumlah_barang`: required, integer, min:0

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

---

### Batas Barang
- `GET    /api/v1/batas-barang` — List batas barang  
  **Response:**
  ```json
  {
      "data": [
          {
              "id_barang": "string",
              "batas_barang": number
          }
      ]
  }
  ```
- `POST   /api/v1/batas-barang` — Create batas barang  
  **Request:**
  ```json
  {
      "id_barang": "string",
      "batas_barang": number
  }
  ```
- `GET    /api/v1/batas-barang/{batas_barang}` — Get batas barang  
- `PUT    /api/v1/batas-barang/{batas_barang}` — Update batas barang  
- `DELETE /api/v1/batas-barang/{batas_barang}` — Delete batas barang  

---

### Batas Pengajuan
- `GET    /api/v1/batas-pengajuan` — List batas pengajuan  
  **Response:**
  ```json
  {
      "data": [
          {
              "id_barang": "string",
              "batas_pengajuan": number
          }
      ]
  }
  ```
- `POST   /api/v1/batas-pengajuan` — Create batas pengajuan  
  **Request:**
  ```json
  {
      "id_barang": "string",
      "batas_pengajuan": number
  }
  ```
- `GET    /api/v1/batas-pengajuan/{batas_pengajuan}` — Get batas pengajuan  
- `PUT    /api/v1/batas-pengajuan/{batas_pengajuan}` — Update batas pengajuan  
- `DELETE /api/v1/batas-pengajuan/{batas_pengajuan}` — Delete batas pengajuan  

---

## Response Codes

- `200`: Success
- `201`: Created
- `204`: No Content (successful deletion)
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Server Error

---

## CORS

- `supports_credentials` must be enabled in backend CORS config.
- Allowed origins:
    - `https://fe-sipb.crulxproject.com`
    - `https://sipb.crulxproject.com`
    - `http://127.0.0.2:5173`
    - `http://127.0.0.1`

---

## Notes

- All endpoints except login/register/forgot-password/reset-password/email-verification require Bearer token.
- Role-based access is enforced (see README for details).
- All IDs use ULID format.
- Timestamps are in UTC.
- For more details, see the [README.md](README.md).