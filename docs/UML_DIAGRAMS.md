# 🎨 UML Diagrams - SIPB Backend

**Generated:** November 3, 2025  
**Architecture:** Service-Oriented Laravel 12 API

---

## 📋 Table of Contents

1. [Class Diagram](#1-class-diagram-architecture)
2. [Sequence Diagrams](#2-sequence-diagrams-workflows)
3. [Component Diagram](#3-component-diagram-layered-architecture)

---

## 1. Class Diagram (Architecture)

### Service-Controller-Model-Policy Relationships

```mermaid
classDiagram
    %% Controllers Layer
    class PenggunaanBarangController {
        -PenggunaanBarangService service
        +index() JsonResponse
        +store(Request) JsonResponse
        +show(id) JsonResponse
        +update(Request, id) JsonResponse
        +destroy(id) JsonResponse
        +getAvailableStock() JsonResponse
        +getStockForItem(id) JsonResponse
    }

    class BarangController {
        -BarangService service
        +index() JsonResponse
        +store(Request) JsonResponse
        +show(id) JsonResponse
        +update(Request, id) JsonResponse
        +destroy(id) JsonResponse
    }

    class PengajuanController {
        -PengajuanService service
        +index() JsonResponse
        +store(Request) JsonResponse
        +show(id) JsonResponse
        +update(Request, id) JsonResponse
        +approve(id) JsonResponse
        +reject(id) JsonResponse
    }

    %% Services Layer
    class PenggunaanBarangService {
        +getAll(filters) Collection
        +getById(id) PenggunaanBarang
        +recordUsage(data) PenggunaanBarang
        +update(id, data) PenggunaanBarang
        +delete(id) bool
        +getAvailableStock(filters) Collection
        +getStockForItem(idBarang) StokGudang
    }

    class BarangService {
        +getAll(filters) Collection
        +getById(id) Barang
        +create(data) Barang
        +update(id, data) Barang
        +delete(id) bool
    }

    class PengajuanService {
        +getAll(filters) Collection
        +create(data) Pengajuan
        +approve(id, adminId) Pengajuan
        +reject(id, adminId, reason) Pengajuan
    }

    %% Models Layer
    class User {
        +ULID unique_id
        +string name
        +string email
        +string password
        +hasRole(role) bool
        +hasAnyRole(roles) bool
        +penggunaanBarang() HasMany
        +pengajuan() HasMany
        +stokGudang() HasMany
    }

    class PenggunaanBarang {
        +ULID id_penggunaan_barang
        +ULID id_barang
        +ULID id_user
        +int jumlah
        +enum status
        +date tanggal_penggunaan
        +user() BelongsTo
        +barang() BelongsTo
    }

    class Barang {
        +ULID id_barang
        +string nama_barang
        +string kode_barang
        +ULID id_jenis_barang
        +int stok_minimum
        +jenisBarang() BelongsTo
        +stokGudang() HasMany
        +penggunaanBarang() HasMany
    }

    class StokGudang {
        +ULID id_stok_gudang
        +ULID id_barang
        +ULID id_user
        +int stok_tersedia
        +date tanggal_update
        +barang() BelongsTo
        +user() BelongsTo
    }

    class Pengajuan {
        +ULID id_pengajuan
        +ULID id_barang
        +ULID id_user_pengaju
        +ULID id_admin_approval
        +int jumlah
        +enum status
        +string alasan_penolakan
        +userPengaju() BelongsTo
        +adminApproval() BelongsTo
        +barang() BelongsTo
    }

    class JenisBarang {
        +ULID id_jenis_barang
        +string nama_jenis
        +string kode_jenis
        +barang() HasMany
    }

    %% Policies Layer
    class PenggunaanBarangPolicy {
        +viewAny(User) bool
        +view(User, PenggunaanBarang) bool
        +create(User) bool
        +update(User, PenggunaanBarang) bool
        +delete(User, PenggunaanBarang) bool
    }

    class BarangPolicy {
        +viewAny(User) bool
        +view(User, Barang) bool
        +create(User) bool
        +update(User, Barang) bool
        +delete(User, Barang) bool
    }

    class PengajuanPolicy {
        +viewAny(User) bool
        +view(User, Pengajuan) bool
        +create(User) bool
        +update(User, Pengajuan) bool
        +approve(User) bool
        +reject(User) bool
    }

    %% Relationships - Controller to Service
    PenggunaanBarangController --> PenggunaanBarangService : uses
    BarangController --> BarangService : uses
    PengajuanController --> PengajuanService : uses

    %% Relationships - Service to Model
    PenggunaanBarangService --> PenggunaanBarang : manages
    PenggunaanBarangService --> StokGudang : updates stock
    BarangService --> Barang : manages
    PengajuanService --> Pengajuan : manages

    %% Relationships - Model to Model
    User "1" -- "*" PenggunaanBarang : creates
    User "1" -- "*" Pengajuan : submits
    User "1" -- "*" StokGudang : owns
    Barang "1" -- "*" PenggunaanBarang : used in
    Barang "1" -- "*" StokGudang : tracked in
    Barang "1" -- "*" Pengajuan : requested
    Barang "*" -- "1" JenisBarang : belongs to

    %% Relationships - Controller to Policy
    PenggunaanBarangController ..> PenggunaanBarangPolicy : authorizes
    BarangController ..> BarangPolicy : authorizes
    PengajuanController ..> PengajuanPolicy : authorizes

    %% Notes
    note for PenggunaanBarangService "Auto-approve on create\nDecrements stock immediately"
    note for User "Roles: admin, manager, user\nManager is read-only"
    note for PenggunaanBarang "Status: auto-approved\nNo approval workflow"
```

### Key Design Patterns

- **Thin Controllers:** Only handle HTTP request/response
- **Service Layer:** All business logic encapsulated here
- **Policy Authorization:** Gate checks before actions
- **Eloquent Relations:** Type-safe model relationships

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
    FE->>API: POST /api/v1/auth/login
    API->>Ctrl: login(Request)
    
    Ctrl->>Ctrl: validate credentials
    alt Invalid Credentials
        Ctrl-->>API: 401 Unauthorized
        API-->>FE: {"status": "error", "message": "Invalid credentials"}
        FE-->>User: Show error
    else Valid Credentials
        Ctrl->>Guard: attempt(credentials)
        Guard->>DB: Check user credentials
        DB-->>Guard: User found
        
        Ctrl->>DB: createToken('api-token')
        DB-->>Ctrl: Token created
        
        Ctrl->>Ctrl: Load user roles & permissions
        Ctrl-->>API: 200 OK + token + user data
        API-->>FE: {"status": "success", "data": {...}, "token": "..."}
        FE->>FE: Store token in localStorage
        FE-->>User: Redirect to dashboard
    end
```

---

### 2.2 Penggunaan Barang Creation (Auto-Approve)

```mermaid
sequenceDiagram
    actor User
    participant FE as Frontend
    participant API as API Routes
    participant Ctrl as PenggunaanBarangController
    participant Policy as PenggunaanBarangPolicy
    participant Service as PenggunaanBarangService
    participant Model as PenggunaanBarang
    participant Stock as StokGudang
    participant DB as Database

    User->>FE: Fill usage form
    FE->>API: POST /api/v1/penggunaan-barang
    Note over FE,API: Authorization: Bearer {token}
    
    API->>Ctrl: store(Request)
    Ctrl->>Ctrl: Validate input
    
    alt Validation Failed
        Ctrl-->>API: 422 Unprocessable Entity
        API-->>FE: {"status": "error", "errors": {...}}
        FE-->>User: Show validation errors
    else Validation Passed
        Ctrl->>Policy: authorize('create')
        Policy->>Policy: Check user role
        
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

**Generated by:** GitHub Copilot  
**Last Updated:** November 3, 2025  
**Version:** 1.0
