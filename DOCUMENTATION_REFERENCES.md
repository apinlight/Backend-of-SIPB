# SIPB Backend References (2020–2025)

Updated: 2025-11-10

Purpose: Curated, open-access references and authoritative docs that align with SIPB backend architecture and features (Laravel API, Sanctum, Spatie Permission, inventory/stock, reporting/exports, validation, resources, routing keys, and security). Each entry includes a short relevance note tied to our codebase (controllers → services → resources → exports).

## Inventory & Stock Management

- Puspitasari D.E., Susanto B.A., Alhamri R.Z. 2023. Sistem Informasi Penjualan dan Manajemen Stok Berbasis Web Studi Kasus Silver Cell Group. Jurnal Informatika dan Multimedia 15(1):20–30. DOI: https://doi.org/10.33795/jtim.v15i1.4156
  - Relevance: End-to-end stock recording (produk masuk/keluar/retur) with Laravel/MySQL; informs our `PenggunaanBarangService` and stock recap logic used in laporan.

- Pratiwi A.D. 2023. Sistem Informasi Manajemen Keuangan Berbasis Web (Laravel). Jurnal Informatika dan Multimedia 15(2):1–5. DOI: https://doi.org/10.33795/jtim.v15i2.4434
  - Relevance: Multi-transaction flows (sales, purchases, returns, receivables/payables) + stock + reports; supports aggregation patterns in `LaporanService`.

- Astutik E., Mustagfirin M. 2020. Sistem Informasi Ketersediaan Obat Menggunakan Framework Laravel. JINRPL 2(1). DOI: https://doi.org/10.36499/jinrpl.v2i1.3188
  - Relevance: Inventory CRUD for pharmacy, mirrors SIPB persediaan flow; supports constraints for stock availability checks.

## Reporting, Exports (Excel/PDF) & Decision Support

- Setyowati D.E., Nugroho B.A., Widyastuti R. 2024. Implementasi MOORA… (Laravel). JTIM 15(2):25–33. DOI: https://doi.org/10.33795/jtim.v15i2.4795
  - Relevance: Periodic reports and PDF printing; can inspire advanced decision support (e.g., prioritization for procurement) if needed.

- Gustina R., Leidiyana H. 2020. Sistem Informasi Penggajian (Laravel). JSiI 7(1). DOI: https://doi.org/10.30656/jsii.v7i1.1726
  - Relevance: Report generation cadence and efficiency in a transactional domain; supports our batch export mindset.

- Syahli A., Kurniati R., Hidayasari N. 2025. Alat OSINT Berbasis Web… (Laravel + multi-API). Techno.Com 24(3). DOI: https://doi.org/10.62411/tc.v24i3.13769
  - Relevance: Composing detailed analytical reports from multiple data sources; analogous to multi-table cabang/stock summaries.

- Laravel Excel (Maatwebsite) – README: https://github.com/SpartnerNL/Laravel-Excel
  - Relevance: Use chunked queries and queued exports in `app/Exports/*` to avoid memory spikes on large reports.

## RBAC (Spatie) & Authentication (Sanctum), CORS & Security

- Sanctum Docs (Laravel 11.x): https://laravel.com/docs/11.x/sanctum
  - Relevance: Stateless Bearer token protection across `/api/v1`; token abilities, pruning expired tokens, SPA vs token mode.

- Spatie Laravel Permission v6: https://spatie.be/docs/laravel-permission/v6/introduction
  - Relevance: Roles/permissions, middleware (`role:`, `permission:`), cache invalidation; aligns with roles admin/manager/user.

- OWASP API Security Top 10: https://owasp.org/API-Security/
  - Relevance: BOLA/BFIA, mass assignment, security misconfiguration; validates our policy checks and `$fillable`/guarded use.

## Service-Layer Architecture, Testing & Process

- Agustha E.B., Adhy S., Nugraheni D.M.K. 2024. Monitoring Informasi Proyek (ICONIX, Laravel 10). JMASIF 15(2). DOI: https://doi.org/10.14710/jmasif.15.2.62416
  - Relevance: Strong separation of concerns (MVC), scenario-based black box testing; supports our thin controllers + `app/Services/*` approach.

- Pakaja F. et al. 2024. Sistem Informasi WO (Laravel, Prototype, Blackbox). JTIK 10(1). DOI: https://doi.org/10.37012/jtik.v10i1.2121
  - Relevance: Iterative validation of forms/endpoints; encourages expanding Feature tests in `tests/Feature/*`.

## Data Model & Keys (UUID/ULID), Routing Keys & API Resources

- Routing: Customizing the key (Laravel 11.x): https://laravel.com/docs/11.x/routing#customizing-the-key
  - Relevance: `User::getRouteKeyName()` = `unique_id` for `PUT /users/{unique_id}`; avoid `id` mismatches.

- API Resources (Laravel 11.x): https://laravel.com/docs/11.x/eloquent-resources
  - Relevance: Always wrap API responses via Resource/Collection; conditional attributes/relationships for lean JSON.

- Validation (Laravel 11.x): https://laravel.com/docs/11.x/validation
  - Relevance: Consistent Form Request usage (e.g., `UpdateProfileRequest`), input normalization (`prepareForValidation`), and rules (e.g., `ulid`).

- (Optional) Spatie Permission UUID/ULID notes: https://spatie.be/docs/laravel-permission/v6/advanced-usage/uuid
  - Relevance: If/when enabling non-integer keys across RBAC tables.

## Multi-role/Branch Scoping & Data Access

- Cinderatama T.A. et al. 2021. GIS Distribusi Taman (Laravel, multi peran). JAGI 5(2). DOI: https://doi.org/10.30871/jagi.v5i2.3308
  - Relevance: Role-based data access patterns; inspiration for cabang scoping and admin/head differentiation.

- Susanto S., Meidina A.H. 2021. Management System… (Laravel). IJCCS 15(4). DOI: https://doi.org/10.22146/ijccs.68204
  - Relevance: Efficient search & data management under Laravel; motivates indexing fields frequently queried (e.g., `jenis_barang_id`, `created_at`).

## How We Cite (Internal Style)

Author(s). Year. Title. Journal Volume(Issue):Pages. DOI:full-doi-url

Example:
Puspitasari D.E., Susanto B.A., Alhamri R.Z. 2023. Sistem Informasi Penjualan dan Manajemen Stok Berbasis Web Studi Kasus Silver Cell Group. Jurnal Informatika dan Multimedia 15(1):20–30. DOI: https://doi.org/10.33795/jtim.v15i1.4156

## Notes

- All DOIs above resolve to article landing pages as of 2025-11-10; entries previously found non-resolving or mismatching were excluded.
- Where scholarly sources are sparse for framework-specific features (Sanctum, Spatie, Laravel Excel), official documentation is cited as authoritative.
