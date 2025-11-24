# 🎨 UML Diagrams - SIMBA Backend

**Generated:** November 22, 2025  
**Architecture:** Service-Oriented Laravel 12 API (Updated after Cabang & Gudang refactor, Pengajuan stock logic changes)

---

## 📋 Table of Contents

1. [Class Diagram](#1-class-diagram-architecture)
2. [Sequence Diagrams](#2-sequence-diagrams-workflows)
3. [Component Diagram](#3-component-diagram-layered-architecture)

---

## 1. Class Diagram (Architecture)

### Service-Controller-Model-Policy Relationships (Updated)

```mermaid
classDiagram
    %% Controllers Layer (thin HTTP handlers)
    class PenggunaanBarangController {
        -PenggunaanBarangService service
        +index() JsonResponse
        +store(Request) JsonResponse
        +show(id) JsonResponse
        +update(Request,id) JsonResponse
        +destroy(id) JsonResponse
        +getAvailableStock() JsonResponse
        +getStockForItem(id) JsonResponse
    }
    class BarangController {
        -BarangService service
        +index() JsonResponse
        +store(Request) JsonResponse
        +show(id) JsonResponse
        +update(Request,id) JsonResponse
        +destroy(id) JsonResponse
    }
    class PengajuanController {
        -PengajuanService service
        +index() JsonResponse
        +store(Request) JsonResponse
        +show(id) JsonResponse
        +update(Request,id) JsonResponse
        +approve(id) JsonResponse
        +reject(id) JsonResponse
        +history(idBarang) JsonResponse
    }

    %% Services Layer
    class PengajuanService {
        +getAll(filters) Collection
        +create(data) Pengajuan
        +approve(id, approver) Pengajuan
        +reject(id, approver, reason) Pengajuan
        +getInfoForForm(user, filters) object
        +getItemHistory(user, idBarang, months) array
        +transferStock(pengajuan, approver) void
    }
    class PenggunaanBarangService {
        +recordUsage(data) PenggunaanBarang
        +getAvailableStock(filters) Collection
    }
    class BarangService {
        +getAll(filters) Collection
        +create(data) Barang
        +update(id,data) Barang
        +delete(id) bool
    }

    %% Models Layer
    class Cabang {
        +string id_cabang
        +string nama_cabang
        +bool is_pusat
        +users() HasMany
        +gudang() HasMany
    }
    class Gudang {
        +string id_cabang
        +string id_barang
        +int jumlah_barang
        +string? keterangan
        +string? tipe
        +cabang() BelongsTo
        +barang() BelongsTo
    }
    class User {
        +string unique_id
        +string username
        +string email
        +string password (hidden)
        +string? id_cabang
        +bool is_active
        +pengajuan() HasMany
        +gudang() HasMany (via cabang)
        +cabang() BelongsTo
        +hasRole(role) bool
    }
    class JenisBarang {
        +string id_jenis_barang
        +string nama_jenis_barang
        +bool is_active
        +barang() HasMany
    }
    class Barang {
        +string id_barang
        +string nama_barang
        +string id_jenis_barang
        +int harga_barang
        +string? deskripsi
        +string? satuan
        +int batas_minimum
        +jenisBarang() BelongsTo
        +gudangEntries() HasMany
        +detailPengajuan() HasMany
        +batasBarang() HasOne
    }
    class BatasBarang {
        +string id_barang
        +int batas_barang (minimum threshold)
        +barang() BelongsTo
    }
    class Pengajuan {
        +string id_pengajuan
        +string unique_id (user)
        +string status_pengajuan
        +string? tipe_pengajuan
        +string? bukti_file
        +string? approved_by
        +datetime? approved_at
        +string? rejected_by
        +datetime? rejected_at
        +string? rejection_reason
        +string? approval_notes
        +string? keterangan
        +user() BelongsTo
        +approver() BelongsTo
        +rejector() BelongsTo
        +details() HasMany
        +isMutable() bool
    }
    class DetailPengajuan {
        +string id_pengajuan
        +string id_barang
        +int jumlah
        +string? keterangan
        +pengajuan() BelongsTo
        +barang() BelongsTo
    }

    %% Policies Layer
    class PengajuanPolicy {
        +viewAny(User) bool
        +view(User,Pengajuan) bool
        +create(User) bool
        +update(User,Pengajuan) bool
        +approve(User) bool
        +reject(User) bool
    }
    class BarangPolicy {
        +viewAny(User) bool
        +view(User,Barang) bool
        +create(User) bool
        +update(User,Barang) bool
        +delete(User,Barang) bool
    }
    class PenggunaanBarangPolicy {
        +viewAny(User) bool
        +create(User) bool
    }

    %% Relationships - Controller to Service
    PengajuanController --> PengajuanService : uses
    PenggunaanBarangController --> PenggunaanBarangService : uses
    BarangController --> BarangService : uses

    %% Relationships - Service to Model
    PengajuanService --> Pengajuan : manages
    PengajuanService --> DetailPengajuan : aggregates
    PenggunaanBarangService --> Gudang : updates stock
    BarangService --> Barang : manages

    %% Model Relationships
    Cabang "1" -- "*" User : assigns
    Cabang "1" -- "*" Gudang : holds_stock
    User "1" -- "*" Pengajuan : submits
    Pengajuan "1" -- "*" DetailPengajuan : contains
    Barang "1" -- "*" DetailPengajuan : requested
    Barang "1" -- "*" Gudang : stocked_in
    Barang "*" -- "1" JenisBarang : belongs_to
    Barang "1" -- "1" BatasBarang : threshold

    %% Notes
    note for Gudang "Logical composite key (id_cabang + id_barang)"
    note for Cabang "Central warehouse flagged via is_pusat=true"
    note for PengajuanService "Approval transfers stock Pusat -> Cabang"
    note for Pengajuan "Statuses: Pending, Disetujui, Ditolak, Selesai, Draft"
    note for Barang "batas_minimum drives dynamic stock status UI"
    note for BatasBarang "Global minimum threshold (not per user)"
    note for User "Spatie roles: admin, manager, user"
```

### Key Design Patterns (Unchanged Principles)

- **Thin Controllers:** Only handle HTTP request/response
- **Service Layer:** All business logic encapsulated here
- **Policy Authorization:** Gate checks before actions
- **Eloquent Relations:** Type-safe model relationships
- **Transaction Safety:** Stock transfer & usage mutations wrapped atomically
- **Stateless Auth:** Sanctum Bearer tokens + Spatie Roles

---

## 2. Sequence Diagrams (Workflows)

### 2.1 Login Flow (Sanctum Authentication)

```mermaid
sequenceDiagram
    actor User
    participant FE as Frontend (Vue)
    participant API as API Routes
    participant Ctrl as AuthController
    participant Guard as Sanctum Guard
    participant DB as Database

    User->>FE: Enter credentials
    FE->>API: POST /api/v1/login
    API->>Ctrl: login(Request)
    Ctrl->>Ctrl: validate credentials
    alt Invalid Credentials
        Ctrl-->>API: 401 Unauthorized
        API-->>FE: {"status":"error","message":"Invalid credentials"}
        FE-->>User: Show error
    else Valid Credentials
        Ctrl->>Guard: attempt(credentials)
        Guard->>DB: Verify user
        DB-->>Guard: OK
        Ctrl->>DB: createToken('api-token')
        DB-->>Ctrl: token
        Ctrl->>Ctrl: Load roles & permissions
        Ctrl-->>API: 200 OK + token + user data
        API-->>FE: {"status":"success","data":{...},"token":"..."}
        FE->>FE: Persist token (localStorage)
        FE-->>User: Redirect dashboard
    end
```

### 2.2 Penggunaan Barang Creation (Auto-Approve, Branch-Based Stock)

```mermaid
sequenceDiagram
    actor User
    participant FE as Frontend
    participant API as API Routes
    participant Ctrl as PenggunaanBarangController
    participant Policy as PenggunaanBarangPolicy
    participant Service as PenggunaanBarangService
    participant Gud as Gudang
    participant DB as Database

    User->>FE: Fill usage form
    FE->>API: POST /api/v1/penggunaan-barang
    Note over FE,API: Authorization: Bearer {token}
    API->>Ctrl: store()
    Ctrl->>Ctrl: Validate input
    Ctrl->>Policy: authorize('create')
    alt Not Authorized
        Policy-->>Ctrl: deny
        Ctrl-->>API: 403
        API-->>FE: {"status":"error","message":"Unauthorized"}
    else Authorized
        Policy-->>Ctrl: allow
        Ctrl->>Service: recordUsage(data, user)
        Service->>Gud: Check branch stock (id_cabang)
        Gud->>DB: SELECT jumlah_barang
        DB-->>Gud: current value
        alt Insufficient
            Service-->>Ctrl: error
            Ctrl-->>API: 409 Stock Conflict
            API-->>FE: {"status":"error","message":"Stock not enough"}
        else Sufficient
            Service->>DB: BEGIN
            Service->>DB: INSERT penggunaan_barang (auto-approved)
            Service->>Gud: decrement jumlah_barang
            Gud->>DB: UPDATE tb_gudang
            Service->>DB: COMMIT
            Ctrl-->>API: 201 Created
            API-->>FE: {"status":"success","data":{...}}
            FE-->>User: Show success
        end
    end
```

**Key Points:**
- Auto-approved usage
- Branch-based stock (`id_cabang`) not per-user private stock
- Manager role read-only (cannot create)

### 2.3 Pengajuan Approval & Stock Transfer (New)

```mermaid
sequenceDiagram
    actor User as Requester
    actor Admin as Approver
    participant FE as Frontend
    participant API as API Routes
    participant Ctrl as PengajuanController
    participant Service as PengajuanService
    participant Model as Pengajuan
    participant Detail as DetailPengajuan
    participant Pusat as Gudang (is_pusat)
    participant Cab as Gudang (Cabang User)
    participant DB as Database

    User->>FE: Submit pengajuan form (details[])
    FE->>API: POST /api/v1/pengajuan
    API->>Ctrl: store()
    Ctrl->>Service: create(data,user)
    Service->>DB: BEGIN
    Service->>Model: INSERT pengajuan (Pending)
    loop each item
        Service->>Detail: INSERT detail (id_pengajuan,id_barang,jumlah)
    end
    Service->>DB: COMMIT
    Ctrl-->>FE: 201 Pending

    Admin->>FE: Approve action
    FE->>API: POST /api/v1/pengajuan/{id}/approve
    API->>Ctrl: approve(id)
    Ctrl->>Service: approve(pengajuan, admin)
    Service->>Pusat: VERIFY central stock (aggregate SUM)
    alt Any item insufficient
        Service-->>Ctrl: Throw stock error
        Ctrl-->>API: 409 Conflict
        API-->>FE: {"status":"error","message":"Central stock insufficient"}
    else All sufficient
        Service->>DB: BEGIN
        loop each detail
            Service->>Pusat: decrement jumlah_barang
            Service->>Cab: increment/create branch row
        end
        Service->>Model: UPDATE status -> Disetujui
        Service->>DB: COMMIT
        Ctrl-->>API: 200 Approved
        API-->>FE: {"status":"success","data":{...}}
        FE-->>User: Show approval success
    end
```

**Key Changes:**
- Stock movement is branch-based using `id_cabang` (not user-based records)
- Central warehouse identified by `cabang.is_pusat = true`
- No per-item per-user limit; monthly usage tracked separately (form info API)

### 2.4 Export Excel Flow (Reconfirmed)

```mermaid
sequenceDiagram
    actor User
    participant FE as Frontend
    participant API as API Routes
    participant Ctrl as LaporanController
    participant Policy as LaporanPolicy
    participant Service as LaporanService
    participant Export as PenggunaanBarangExport
    participant Excel as Excel Engine
    participant Storage as Storage Disk

    User->>FE: Click "Export Excel"
    FE->>API: GET /api/v1/laporan/export/penggunaan
    API->>Ctrl: exportPenggunaan()
    Ctrl->>Policy: authorize('export')
    alt Denied
        Policy-->>Ctrl: false
        Ctrl-->>API: 403
        API-->>FE: {"status":"error"}
    else Allowed
        Policy-->>Ctrl: true
        Ctrl->>Service: getExportData(filters,user)
        Service-->>Ctrl: Collection
        Ctrl->>Export: new Export(data)
        Export->>Excel: generate workbook
        Excel-->>Ctrl: binary stream
        Ctrl-->>API: 200 attachment
        API-->>FE: XLSX download
        FE-->>User: Save file
    end
```

**Export Rules (Reconfirmed):**
- ✅ Admin: Export all data (all cabang)
- ✅ Manager: Export all data (oversight)
- ✅ User: Export own cabang-scoped / personal usage records

---

## 3. Component Diagram (Layered Architecture)

### System-Level Architecture (Updated Stock & Cabang Context)

```mermaid
graph TB
    subgraph "Client Layer"
        FE[Vue.js 3 SPA<br/>Vite + Pinia + Router]
    end

    subgraph "API Gateway"
        CORS[CORS Middleware]
        Auth[Sanctum Auth]
        Rate[Rate Limiter]
    end

    subgraph "Application Layer"
        Routes[API Routes /api/v1/*]
        Controllers[Controllers]
        FormReq[Form Requests]
        Resources[API Resources]
        Services[Service Layer]
        Policies[Policies]
        Models[Eloquent Models]
        Exports[Excel Exports]
        Events[Events]
        Jobs[Queue Jobs]
    end

    subgraph "External Services"
        Sanctum[Sanctum Tokens]
        Spatie[Spatie Permission]
        Telescope[Telescope Debug]
    end

    subgraph "Data Storage"
        DB[(MariaDB<br/>tb_users, tb_cabang, tb_pengajuan, tb_detail_pengajuan, tb_barang, tb_gudang, tb_batas_barang)]
        Cache[(Redis/File Cache)]
        Storage[File Storage]
    end

    FE -->|Bearer JSON| CORS --> Auth --> Rate --> Routes
    Routes --> Controllers --> Services --> Models --> DB
    Controllers --> FormReq
    Controllers --> Policies
    Controllers --> Resources
    Controllers --> Exports
    Services --> Events
    Services --> Jobs
    Services --> Cache
    Models --> Storage
    Auth --> Sanctum
    Policies --> Spatie
    Controllers -.debug.-> Telescope

    Resources --> FE

    classDef gateway fill:#ffa500,stroke:#333,color:#fff
    classDef service fill:#ff6b6b,stroke:#333,color:#fff
    classDef model fill:#4ecdc4,stroke:#333,color:#fff
    classDef data fill:#aa96da,stroke:#333,color:#fff
    classDef external fill:#f38181,stroke:#333,color:#fff
    classDef client fill:#42b983,stroke:#333,color:#fff

    class CORS,Auth,Rate gateway
    class Services service
    class Models model
    class DB,Cache,Storage data
    class Sanctum,Spatie,Telescope external
    class FE client

    %% Updated Notes
    note right of Services "PengajuanService: central (is_pusat) stock aggregation + transfer"
    note right of Models "Gudang now branch-based, not per-user private stock"
    note right of DB "Cabang.is_pusat differentiates central vs branch warehouses"
    note right of Resources "Adds stock_info to barang items"
```

### Request Flow (Updated Pengajuan Approval)

1. User submits draft pengajuan → Pending with detail rows
2. Approver triggers approval → validates central (is_pusat) stock
3. Transaction: decrement pusat Gudang; increment/create cabang Gudang rows
4. Status updated → Disetujui; monthly usage reflected in form info
5. Resource returns enriched pengajuan with detail & derived stock movements
6. Frontend updates UI accordingly

---

## 📊 Diagram Legend

| Symbol | Meaning |
|--------|---------|
| `-->` | Dependency / Uses |
| `--` | Association |
| `..>` | Authorizes / Policy relation |
| `*` | Many (Cardinality) |
| `1` | One (Cardinality) |

---

## 🎯 Key Architectural Principles (Reaffirmed)

1. Separation of Concerns (Controllers vs Services vs Policies)
2. Explicit Central Warehouse (`is_pusat`) replacing name-based heuristics
3. Branch-Level Inventory (Gudang keyed by `id_cabang`,`id_barang`)
4. Immutable Approved Pengajuan (mutable only if Pending/Draft)
5. Stock Safety via Transactional Transfer
6. Stateless Auth + RBAC enforced server-side

---

## 🛠️ Tools Used

- Mermaid for diagrams
- Laravel Sanctum + Spatie Permission for auth/RBAC
- Maatwebsite Excel for exports

---

        
        alt Not Authorized (Manager)
            Policy-->>Ctrl: 403 Forbidden
            Ctrl-->>API: 403 Forbidden
            API-->>FE: {"status": "error", "message": "Unauthorized"}
            FE-->>User: Show error
        else Authorized (Admin/User)
            Policy-->>Ctrl: true
            
            Ctrl->>Service: recordUsage(data)
            Service->>Stock: Check available stock
            Stock->>DB: SELECT stok_tersedia
            DB-->>Stock: Current stock
            
            alt Insufficient Stock
                Stock-->>Service: Stock not enough
                Service-->>Ctrl: Exception
                Ctrl-->>API: 400 Bad Request
                API-->>FE: {"status": "error", "message": "Stock not enough"}
                FE-->>User: Show error
            else Sufficient Stock
                Service->>DB: BEGIN TRANSACTION
                
                Service->>Model: create(data)
                Note over Model: status = 'approved' (auto)
                Model->>DB: INSERT penggunaan_barang
                DB-->>Model: Record created
                
                Service->>Stock: decrement stock
                Stock->>DB: UPDATE stok_gudang SET stok_tersedia = stok_tersedia - jumlah
                DB-->>Stock: Stock updated
                
                Service->>DB: COMMIT TRANSACTION
                Service-->>Ctrl: PenggunaanBarang object
                
                Ctrl->>Ctrl: Transform to Resource
                Ctrl-->>API: 201 Created
                API-->>FE: {"status": "success", "data": {...}}
                FE->>FE: Update table
                FE-->>User: Show success message
            end
        end
    end
```

**Key Points:**
- ✅ Auto-approved on creation (no approval workflow)
- ✅ Stock decremented immediately in transaction
- ✅ Manager cannot create (read-only access)
- ✅ User can only create for own records

---

### 2.3 Export Excel Flow

```mermaid
sequenceDiagram
    actor User
    participant FE as Frontend
    participant API as API Routes
    participant Ctrl as LaporanController
    participant Policy as LaporanPolicy
    participant Service as LaporanService
    participant Export as PenggunaanBarangExport
    participant Excel as Maatwebsite/Excel
    participant Storage as Storage Disk

    User->>FE: Click "Export Excel"
    FE->>API: GET /api/v1/laporan/export/penggunaan?filters=...
    Note over FE,API: Authorization: Bearer {token}
    
    API->>Ctrl: exportPenggunaan(Request)
    Ctrl->>Policy: authorize('export')
    Policy->>Policy: Check user role & filters
    
    alt Not Authorized
        Policy-->>Ctrl: 403 Forbidden
        Ctrl-->>API: 403 Forbidden
        API-->>FE: {"status": "error"}
        FE-->>User: Show error
    else Authorized
        Policy-->>Ctrl: true
        
        Ctrl->>Service: getExportData(filters, user)
        
        alt Manager/Admin
            Note over Service: Apply global filters
            Service->>Service: Query all records
        else User
            Note over Service: Apply user scope
            Service->>Service: Query own records only
        end
        
        Service-->>Ctrl: Collection of records
        
        Ctrl->>Export: new PenggunaanBarangExport(data)
        Export->>Export: Map data to spreadsheet format
        
        Ctrl->>Excel: download()
        Excel->>Excel: Generate XLSX file
        Excel->>Excel: Add headers, styling, formulas
        
        Excel-->>Ctrl: Binary file stream
        Ctrl-->>API: 200 OK (application/vnd.ms-excel)
        Note over API,FE: Content-Disposition attachment
        API-->>FE: Excel file download
        FE->>FE: Browser triggers download
        FE-->>User: File saved to Downloads
    end
```

**Export Rules:**
- ✅ Admin: Export all data (all branches)
- ✅ Manager: Export all data (monitoring/oversight)
- ✅ User: Export own data only (scoped by unique_id)

---

## 3. Component Diagram (Layered Architecture)

### System-Level Architecture

```mermaid
graph TB
    subgraph "Client Layer"
        FE[Vue.js 3 SPA<br/>Vite + Pinia + Vue Router]
    end

    subgraph "API Gateway"
        CORS[CORS Middleware]
        Auth[Sanctum Auth Middleware]
        RateLimit[Rate Limiter]
    end

    subgraph "Application Layer - Laravel 12"
        Routes[API Routes<br/>/api/v1/*]
        
        subgraph "HTTP Layer"
            Controllers[Controllers<br/>Thin HTTP Handlers]
            FormRequests[Form Requests<br/>Validation]
            Resources[API Resources<br/>Response Transform]
        end
        
        subgraph "Business Logic Layer"
            Services[Services<br/>Business Logic]
            Policies[Policies<br/>Authorization]
        end
        
        subgraph "Data Layer"
            Models[Eloquent Models<br/>ORM]
            Repositories[Eloquent Relations]
        end
        
        subgraph "Support Layer"
            Exports[Excel Exports<br/>Maatwebsite]
            Events[Events & Listeners]
            Jobs[Queue Jobs]
        end
    end

    subgraph "External Services"
        Sanctum[Laravel Sanctum<br/>Token Auth]
        Spatie[Spatie Permission<br/>Roles & Permissions]
        Telescope[Laravel Telescope<br/>Debugging]
    end

    subgraph "Data Storage"
        DB[(MariaDB Database<br/>Custom Tables: tb_*)]
        Cache[(Redis Cache<br/>Optional)]
        Storage[File Storage<br/>Local/S3]
    end

    %% Client to API Gateway
    FE -->|HTTP/JSON<br/>Bearer Token| CORS
    CORS --> Auth
    Auth --> RateLimit
    RateLimit --> Routes

    %% API Gateway to HTTP Layer
    Routes --> Controllers
    Controllers --> FormRequests
    FormRequests -.validate.-> Controllers
    Controllers --> Resources

    %% HTTP to Business Logic
    Controllers --> Policies
    Policies -.authorize.-> Controllers
    Controllers --> Services

    %% Business Logic to Data
    Services --> Models
    Models --> Repositories
    Repositories --> DB

    %% Support Services
    Controllers --> Exports
    Services --> Events
    Services --> Jobs

    %% External Services
    Auth --> Sanctum
    Policies --> Spatie
    Controllers -.debug.-> Telescope

    %% Data Storage
    Models --> DB
    Services --> Cache
    Exports --> Storage

    %% Response Flow
    Resources -.transform.-> Controllers
    Controllers -->|JSON Response| FE

    %% Styling
    classDef frontend fill:#42b983,stroke:#333,stroke-width:2px,color:#fff
    classDef middleware fill:#ffa500,stroke:#333,stroke-width:2px,color:#fff
    classDef http fill:#61dafb,stroke:#333,stroke-width:2px,color:#000
    classDef business fill:#ff6b6b,stroke:#333,stroke-width:2px,color:#fff
    classDef data fill:#4ecdc4,stroke:#333,stroke-width:2px,color:#fff
    classDef support fill:#95e1d3,stroke:#333,stroke-width:2px,color:#000
    classDef external fill:#f38181,stroke:#333,stroke-width:2px,color:#fff
    classDef storage fill:#aa96da,stroke:#333,stroke-width:2px,color:#fff

    class FE frontend
    class CORS,Auth,RateLimit middleware
    class Controllers,FormRequests,Resources http
    class Services,Policies business
    class Models,Repositories data
    class Exports,Events,Jobs support
    class Sanctum,Spatie,Telescope external
    class DB,Cache,Storage storage
```

### Request Flow

```
1. User Action (Frontend Vue.js)
   ↓
2. HTTP Request + Bearer Token
   ↓
3. CORS Check → Auth Middleware → Rate Limiter
   ↓
4. API Routes (/api/v1/*)
   ↓
5. Controller (Thin Handler)
   ├─→ Form Request (Validate Input)
   ├─→ Policy (Authorize Action)
   └─→ Service (Business Logic)
       ↓
6. Service Layer
   ├─→ Eloquent Model (Data Access)
   ├─→ Database Transaction (if needed)
   └─→ External Services (if needed)
       ↓
7. Database (MariaDB)
   ↓
8. Response Flow
   ├─→ Service returns Model/Collection
   ├─→ Controller transforms via Resource
   └─→ JSON Response
       ↓
9. Frontend Updates UI
```

---

## 📊 Diagram Legend

### Relationship Types

| Symbol | Meaning |
|--------|---------|
| `-->` | Dependency / Uses |
| `--` | Association |
| `..>` | Implements / Authorizes |
| `*` | Many (Cardinality) |
| `1` | One (Cardinality) |

### Multiplicity

- `1` : Exactly one
- `*` : Zero or many
- `0..1` : Zero or one
- `1..*` : One or many

---

## 🎯 Key Architectural Principles

### 1. **Separation of Concerns**
- Controllers: HTTP only
- Services: Business logic
- Models: Data representation
- Policies: Authorization

### 2. **Single Responsibility**
Each class has one reason to change:
- `BarangService`: Manages barang business logic
- `BarangPolicy`: Enforces barang authorization
- `BarangController`: Handles barang HTTP requests

### 3. **Dependency Injection**
```php
public function __construct(
    protected BarangService $barangService
) {}
```

### 4. **Transaction Safety**
```php
DB::transaction(function () {
    // Critical operations
});
```

### 5. **Authorization First**
```php
$this->authorize('create', Barang::class);
```

---

## 🔄 Data Flow Patterns

### Create Operation
```
User Input → Validation → Authorization → Service Logic → 
Database Transaction → Response Transform → JSON Output
```

### Query Operation
```
Request Filters → Authorization → Service with Scopes → 
Database Query → Resource Collection → JSON Output
```

### Update Operation
```
Request Data → Validation → Authorization → Service Logic → 
Find Model → Update Fields → Save → Resource → JSON Output
```

---

## 📚 References

- **Business Rules:** [BUSINESS_RULES.md](../BUSINESS_RULES.md)
- **API Documentation:** [dokumentasi-api.md](../dokumentasi-api.md)
- **Architecture Guide:** [docs/INDEX.md](INDEX.md)
- **Test Coverage:** [TEST_REPORT.md](../TEST_REPORT.md)

---

## 🛠️ Tools Used

- **Mermaid:** Markdown-native diagrams
- **VS Code:** Preview with "Markdown Preview Mermaid Support" extension
- **GitHub:** Native Mermaid rendering in README/docs

---

## 4. Use Case Diagram (PlantUML)

Diagram ini menggambarkan interaksi aktor (Admin, Manager, User Cabang, System Jobs) dengan fungsionalitas utama SIMBA. Gunakan file `docs/USE_CASE_SIMBA.puml` untuk render formal UML. Jika PlantUML tidak tersedia, gunakan fallback Mermaid sederhana di bawah.

### PlantUML Source
```plantuml
@startuml
actor Admin
actor Manager
actor User as CabangUser
actor System as Jobs
rectangle SIMBA {
  (Login) (Logout) (Lihat Dashboard)
  (Ajukan Pengadaan Barang) (Catat Penggunaan Barang)
  (Lihat Stok Cabang) (Lihat Laporan Cabang) (Export Laporan Cabang)
  (Approve / Reject Pengajuan) (Kelola Pengguna) (Kelola Master Data Barang)
  (Lihat Stok Global) (Lihat Semua Pengajuan) (Export Laporan Global)
  (Prune Token Expired) (Proses Queue Export)
}
Admin --> (Approve / Reject Pengajuan)
Admin --> (Kelola Pengguna)
Admin --> (Kelola Master Data Barang)
Admin --> (Export Laporan Global)
Manager --> (Lihat Stok Global)
Manager --> (Lihat Semua Pengajuan)
Manager --> (Export Laporan Global)
CabangUser --> (Ajukan Pengadaan Barang)
CabangUser --> (Catat Penggunaan Barang)
CabangUser --> (Export Laporan Cabang)
Jobs --> (Prune Token Expired)
Jobs --> (Proses Queue Export)
@enduml
```

### Mermaid Fallback (Approximation)
```mermaid
graph LR
  Admin((Admin))
  Manager((Manager))
  User((User Cabang))
  Jobs((System Jobs))
  subgraph UseCases
    UCLogin[Login]
    UCLogout[Logout]
    UCDash[Dashboard]
    UCPengajuan[Ajukan Pengadaan]
    UCPakai[Catat Penggunaan]
    UCStokCabang[Stok Cabang]
    UCLapCabang[Laporan Cabang]
    UCExportCabang[Export Laporan Cabang]
    UCApprove[Approve/Reject Pengajuan]
    UCKelolaUser[Kelola Pengguna]
    UCMaster[Master Data Barang]
    UCStokGlobal[Stok Global]
    UCPengajuanGlobal[Semua Pengajuan]
    UCExportGlobal[Export Laporan Global]
    UCPrune[Prune Token]
    UCQueue[Proses Queue Export]
  end
  Admin --- UCApprove
  Admin --- UCKelolaUser
  Admin --- UCMaster
  Admin --- UCExportGlobal
  Manager --- UCStokGlobal
  Manager --- UCPengajuanGlobal
  Manager --- UCExportGlobal
  User --- UCPengajuan
  User --- UCPakai
  User --- UCExportCabang
  Jobs --- UCPrune
  Jobs --- UCQueue
```

### Penjelasan Aktor & Hak Akses Singkat
- **Admin:** Full akses (persetujuan, master data, global laporan & stok).
- **Manager:** Read-only global (monitor stok & pengajuan, export global).
- **User Cabang:** Operasional cabang (pengajuan, penggunaan, laporan cabang).
- **System Jobs:** Proses otomatis (prune token kadaluarsa, proses antrian export). 

**Generated by:** GitHub Copilot  
**Last Updated:** November 22, 2025  
**Version:** 1.2 (Tambah Use Case Diagram)
