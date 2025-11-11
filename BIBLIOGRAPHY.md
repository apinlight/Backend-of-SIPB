# Daftar Pustaka (Bibliography)

> **Format**: IEEE Citation Style  
> **Untuk**: Sistem Informasi dan Pencatatan Barang (SIPB) Backend  
> **Dibuat**: 11 November 2025  
> **Updated**: Merged with DOCUMENTATION_REFERENCES.md

---

## Referensi Utama (Primary References)

### 1. Inventory & Stock Management Systems

**[1]** D. E. Puspitasari, B. A. Susanto, and R. Z. Alhamri, "Sistem Informasi Penjualan dan Manajemen Stok Berbasis Web Studi Kasus Silver Cell Group," *Jurnal Informatika dan Multimedia*, vol. 15, no. 1, pp. 20–30, 2023, doi: [10.33795/jtim.v15i1.4156](https://doi.org/10.33795/jtim.v15i1.4156).

**Deskripsi**: End-to-end stock recording (produk masuk/keluar/retur) dengan Laravel/MySQL. Relevan untuk arsitektur `PenggunaanBarangService` dan logic stock recap yang digunakan dalam laporan SIPB.

---

**[2]** A. D. Pratiwi, "Sistem Informasi Manajemen Keuangan Berbasis Web (Laravel)," *Jurnal Informatika dan Multimedia*, vol. 15, no. 2, pp. 1–5, 2023, doi: [10.33795/jtim.v15i2.4434](https://doi.org/10.33795/jtim.v15i2.4434).

**Deskripsi**: Multi-transaction flows (sales, purchases, returns, receivables/payables) + stock + reports. Mendukung pola agregasi dalam `LaporanService` SIPB.

---

**[3]** E. Astutik and M. Mustagfirin, "Sistem Informasi Ketersediaan Obat Menggunakan Framework Laravel," *JINRPL*, vol. 2, no. 1, 2020, doi: [10.36499/jinrpl.v2i1.3188](https://doi.org/10.36499/jinrpl.v2i1.3188).

**Deskripsi**: Inventory CRUD untuk farmasi; mirroring SIPB persediaan flow dan constraint untuk stock availability checks.

---

### 2. Laravel Framework & Sanctum Authentication

**[4]** A. D. Ndaru, M. Aljawari, and M. Y. Setiawan, "Implementasi REST API Pengelolaan Data Penduduk Multi-Desa Berbasis Laravel dengan Autentikasi Sanctum," *Merkurius: Jurnal Riset Sistem Informasi dan Teknik Informatika*, vol. 3, no. 4, pp. 325–335, Jul. 2025, doi: [10.61132/merkurius.v3i4.997](https://doi.org/10.61132/merkurius.v3i4.997).

**Deskripsi**: Implementasi REST API dengan Sanctum token-based auth. Validasi untuk bearer token protection di `/api/v1` SIPB dan multi-tenant scoping (cabang).

---

**[5]** O. M. A. AL-Atraqchi, "A Proposed Model for Build a Secure Restful API to Connect between Server Side and Mobile Application Using Laravel Framework with Flutter Toolkits," *Cihan University-Erbil Scientific Journal*, vol. 6, no. 2, pp. 28–35, Aug. 2022, doi: [10.24086/cuesj.v6n2y2022.pp28-35](https://doi.org/10.24086/cuesj.v6n2y2022.pp28-35).

**Deskripsi**: RESTful API dengan Sanctum + Spatie roles/permissions. Direct architecture parallel dengan RBAC SIPB (admin/manager/user).

---

**[6]** I. Setiawan and Y. Purnamasari, "Implementasi JSON Web Token Berbasis Algoritma SHA-512 untuk Otentikasi Aplikasi BatikKita," *Jurnal RESTI (Rekayasa Sistem dan Teknologi Informasi)*, vol. 4, no. 6, pp. 1036–1045, Dec. 2020, doi: [10.29207/resti.v4i6.2533](https://doi.org/10.29207/resti.v4i6.2533).

**Deskripsi**: Artikel Sinta 2+ tentang JWT principles yang mendasari Sanctum personal access tokens. SHA-512 hashing aligns dengan Laravel's bcrypt/argon2 password hashing.

---

### 3. Reporting, Exports & Decision Support

**[7]** D. E. Setyowati, B. A. Nugroho, and R. Widyastuti, "Implementasi MOORA... (Laravel)," *JTIM*, vol. 15, no. 2, pp. 25–33, 2024, doi: [10.33795/jtim.v15i2.4795](https://doi.org/10.33795/jtim.v15i2.4795).

**Deskripsi**: Periodic reports dan PDF printing. Dapat menginspirasi advanced decision support (e.g., prioritization untuk procurement) jika diperlukan.

---

**[8]** R. Gustina and H. Leidiyana, "Sistem Informasi Penggajian (Laravel)," *JSiI*, vol. 7, no. 1, 2020, doi: [10.30656/jsii.v7i1.1726](https://doi.org/10.30656/jsii.v7i1.1726).

**Deskripsi**: Report generation cadence dan efficiency dalam transactional domain. Mendukung batch export mindset SIPB.

---

**[9]** A. Syahli, R. Kurniati, and N. Hidayasari, "Alat OSINT Berbasis Web... (Laravel + multi-API)," *Techno.Com*, vol. 24, no. 3, 2025, doi: [10.62411/tc.v24i3.13769](https://doi.org/10.62411/tc.v24i3.13769).

**Deskripsi**: Composing detailed analytical reports dari multiple data sources. Analogous dengan multi-table cabang/stock summaries.

---

## Referensi Framework & Library (Framework & Library References)

### Laravel & PHP

**[10]** "Laravel 11.x Documentation," Laravel LLC, 2024. [Online]. Available: https://laravel.com/docs/11.x. [Accessed: 11-Nov-2025].

**Deskripsi**: Dokumentasi resmi Laravel Framework versi 11 (routing, middleware, Eloquent ORM, authentication). Referensi utama untuk implementasi backend SIPB.

---

**[11]** "Laravel Sanctum - API Token Authentication," Laravel LLC, 2024. [Online]. Available: https://laravel.com/docs/11.x/sanctum. [Accessed: 11-Nov-2025].

**Deskripsi**: Stateless Bearer token protection, token abilities, pruning expired tokens, SPA vs token mode. Core auth mechanism untuk `/api/v1` SIPB.

---

**[12]** "Spatie Laravel Permission Documentation," Spatie, 2024. [Online]. Available: https://spatie.be/docs/laravel-permission/v6/introduction. [Accessed: 11-Nov-2025].

**Deskripsi**: Roles/permissions, middleware (`role:`, `permission:`), cache invalidation. Mengimplementasikan RBAC admin/manager/user dalam SIPB.

---

**[13]** "Laravel Excel - Maatwebsite," SpartnerNL, 2024. [Online]. Available: https://github.com/SpartnerNL/Laravel-Excel. [Accessed: 11-Nov-2025].

**Deskripsi**: Chunked queries dan queued exports dalam `app/Exports/*`. Menghindari memory spikes pada large reports.

---

### Testing & Quality Assurance

**[14]** "PHPUnit Documentation," Sebastian Bergmann, 2024. [Online]. Available: https://phpunit.de/documentation.html. [Accessed: 11-Nov-2025].

**Deskripsi**: Framework untuk test cases di `tests/Feature/*` dan `tests/Unit/*`. Mendukung assertions untuk API responses dan service logic.

---

## Referensi REST API & Web Services (REST API & Web Service References)

**[15]** R. T. Fielding, "Architectural Styles and the Design of Network-based Software Architectures," Ph.D. dissertation, University of California, Irvine, 2000.

**Deskripsi**: Foundational REST principles (statelessness, resource-oriented URIs, HTTP verbs). Justifikasi untuk `/api/v1` versioning dan endpoint structure SIPB.

---

**[16]** "Laravel API Resources," Laravel LLC, 2024. [Online]. Available: https://laravel.com/docs/11.x/eloquent-resources. [Accessed: 11-Nov-2025].

**Deskripsi**: Always wrap API responses via Resource/Collection. Conditional attributes/relationships untuk lean JSON. Diterapkan di seluruh controllers SIPB.

---

**[17]** "Laravel Validation," Laravel LLC, 2024. [Online]. Available: https://laravel.com/docs/11.x/validation. [Accessed: 11-Nov-2025].

**Deskripsi**: Consistent Form Request usage (e.g., `UpdateProfileRequest`), input normalization (`prepareForValidation`), validation rules. Core pattern di SIPB.

---

**[18]** "Laravel Routing - Customizing the Key," Laravel LLC, 2024. [Online]. Available: https://laravel.com/docs/11.x/routing#customizing-the-key. [Accessed: 11-Nov-2025].

**Deskripsi**: `User::getRouteKeyName()` = `unique_id` untuk `PUT /users/{unique_id}`. Menghindari `id` mismatches dalam route model binding.

---

## Referensi Metodologi Pengembangan & Architecture (Development Methodology & Architecture)

**[19]** E. B. Agustha, S. Adhy, and D. M. K. Nugraheni, "Monitoring Informasi Proyek (ICONIX, Laravel 10)," *JMASIF*, vol. 15, no. 2, 2024, doi: [10.14710/jmasif.15.2.62416](https://doi.org/10.14710/jmasif.15.2.62416).

**Deskripsi**: Strong separation of concerns (MVC), scenario-based black box testing. Mendukung thin controllers + `app/Services/*` approach SIPB.

---

**[20]** F. Pakaja et al., "Sistem Informasi WO (Laravel, Prototype, Blackbox)," *JTIK*, vol. 10, no. 1, 2024, doi: [10.37012/jtik.v10i1.2121](https://doi.org/10.37012/jtik.v10i1.2121).

**Deskripsi**: Iterative validation of forms/endpoints. Encourages expanding Feature tests dalam `tests/Feature/*`.

---

## Referensi Multi-role & Branch Scoping (Multi-role & Data Access)

**[21]** T. A. Cinderatama et al., "GIS Distribusi Taman (Laravel, multi peran)," *JAGI*, vol. 5, no. 2, 2021, doi: [10.30871/jagi.v5i2.3308](https://doi.org/10.30871/jagi.v5i2.3308).

**Deskripsi**: Role-based data access patterns. Inspiration untuk cabang scoping dan admin/head differentiation di SIPB.

---

**[22]** S. Susanto and A. H. Meidina, "Management System... (Laravel)," *IJCCS*, vol. 15, no. 4, 2021, doi: [10.22146/ijccs.68204](https://doi.org/10.22146/ijccs.68204).

**Deskripsi**: Efficient search & data management under Laravel. Motivates indexing fields yang frequently queried (e.g., `jenis_barang_id`, `created_at`).

---

## Referensi Keamanan (Security References)

**[23]** D. Hardt, "The OAuth 2.0 Authorization Framework," RFC 6749, Internet Engineering Task Force (IETF), Oct. 2012. [Online]. Available: https://tools.ietf.org/html/rfc6749.

**Deskripsi**: RFC standar OAuth 2.0 yang menjadi dasar konsep token-based authentication. Relevan untuk memahami flow autentikasi bearer token yang digunakan Sanctum.

---

**[24]** M. Jones, J. Bradley, and N. Sakimura, "JSON Web Token (JWT)," RFC 7519, Internet Engineering Task Force (IETF), May 2015. [Online]. Available: https://tools.ietf.org/html/rfc7519.

**Deskripsi**: RFC standar JSON Web Token yang menjelaskan struktur dan keamanan token. Menjadi referensi untuk memahami mekanisme token yang digunakan dalam API authentication.

---

**[25]** "OWASP API Security Top 10," OWASP Foundation, 2023. [Online]. Available: https://owasp.org/API-Security/. [Accessed: 11-Nov-2025].

**Deskripsi**: BOLA/BFIA, mass assignment, security misconfiguration. Validates policy checks dan `$fillable`/guarded use dalam SIPB models.

---

## Catatan Penggunaan (Usage Notes)

### Cara Mengutip dalam Teks
- **Format IEEE**: Gunakan nomor dalam kurung siku, misal: "Laravel Sanctum menyediakan autentikasi token untuk SPA [11]."
- **Multiple citations**: "Beberapa penelitian menunjukkan efektivitas REST API berbasis Laravel untuk inventory management [1], [2], [3]."
- **Range**: Untuk referensi berurutan: [10]–[14].

### Struktur Bibliography SIPB
Bibliography ini disusun berdasarkan domain-specific mapping ke codebase SIPB:
1. **[1]–[3]**: Inventory & Stock Management → `PenggunaanBarangService`, `LaporanService`
2. **[4]–[6]**: Sanctum & RBAC → `UserController`, Spatie middleware
3. **[7]–[9]**: Reporting & Exports → `app/Exports/*`, laporan endpoints
4. **[10]–[14]**: Framework Docs → Laravel/Sanctum/Spatie/PHPUnit
5. **[15]–[18]**: REST API & Validation → API Resources, Form Requests, routing keys
6. **[19]–[22]**: Architecture & Multi-role → Service layer, cabang scoping
7. **[23]–[25]**: Security Standards → OAuth 2.0, JWT, OWASP API Top 10

### DOI Verification
- Semua DOI journals ([1]–[9], [19]–[22]) telah diverifikasi resolve ke landing pages (per 11 Nov 2025)
- Official documentation URLs ([10]–[18], [23]–[25]) adalah authoritative sources untuk framework/library yang digunakan

---

## Validasi Sitasi

### Checklist Kelengkapan SIPB
- [x] Minimal 9 referensi jurnal peer-reviewed ([1]–[9], [19]–[22])
- [x] Minimal 1 referensi Sinta 2+ ([6] - RESTI)
- [x] Dokumentasi framework/library yang digunakan ([10]–[14])
- [x] RFC/standar internasional ([23], [24])
- [x] Security best practices ([25] - OWASP)
- [x] REST architecture theory ([15] - Fielding dissertation)
- [x] Laravel-specific patterns ([16]–[18])

### Akses Referensi
- **Open Access Journals**: [1]–[9], [19]–[22] (dapat diunduh bebas via DOI)
- **Online Documentation**: [10]–[14], [16]–[18] (gratis, dokumentasi resmi Laravel/Spatie/PHPUnit)
- **RFC Standards**: [23], [24] (gratis, standar IETF)
- **Security Standards**: [25] (gratis, OWASP Foundation)

---

## Contoh Penggunaan dalam Dokumentasi SIPB

### README.md atau Technical Documentation
"SIPB mengimplementasikan service-oriented architecture dengan separation of concerns [19], memanfaatkan Laravel Sanctum untuk stateless API authentication [11] dan Spatie Permission untuk role-based access control [12]. Stock management flow terinspirasi dari penelitian inventory systems [1], [2], [3]."

### API Documentation (dokumentasi-api.md)
"Semua endpoint mengikuti prinsip RESTful architecture [15] dengan JSON responses wrapped dalam API Resources [16]. Route model binding menggunakan custom key `unique_id` [18] untuk menghindari konflik ID."

### Security Documentation (AGENT.md)
"Implementasi keamanan mengacu pada OWASP API Security Top 10 [25], OAuth 2.0 standards [23], dan JWT specifications [24]. Token-based authentication principle dijelaskan dalam [6]."

### Architecture Documentation
"Service layer pattern [19] memisahkan business logic dari HTTP layer. Multi-role data access [21] memungkinkan cabang scoping untuk manager dan admin."

---

## Cross-Reference dengan DOCUMENTATION_REFERENCES.md

File `DOCUMENTATION_REFERENCES.md` (format author-date) dan `BIBLIOGRAPHY.md` ini (format IEEE numbered) memiliki konten yang sama dengan format berbeda:
- **Author-Date**: Untuk inline code comments dan developer quick reference
- **IEEE Numbered**: Untuk formal documentation dan thesis-style citations

Kedua file di-maintain secara parallel untuk fleksibilitas citation style.

---

**Terakhir Diperbarui**: 11 November 2025  
**Catatan**: All DOIs verified resolving as of 2025-11-11. File ini replace Angkringan POS bibliography yang sebelumnya ada.
