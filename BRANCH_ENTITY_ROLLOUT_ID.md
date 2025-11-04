# Rencana Pengenalan Entitas Cabang (Branch) – Bahasa Indonesia

Dokumen ini merangkum diskusi: dari pertanyaan “kita perlu menambahkan entitas Branch?” hingga rekomendasi implementasi bertahap dengan perubahan minimal pada kode yang sudah ada.

## Ringkasan
- Ya, sebaiknya kita perkenalkan entitas Branch (Cabang) sebagai entity first-class.
- Lakukan secara bertahap (incremental), hindari “big bang” refactor.
- Tahap awal: tambah kolom dan relasi (dual-write), baca data masih kompatibel dengan pola lama (fallback), lalu bertahap pindahkan query/policy ke branch_id.

## Kenapa Perlu Branch?
- Memisahkan aktor (User) dari unit organisasi (Branch) membuat domain lebih jelas.
- Scoping data (stok, penggunaan, laporan) jadi konsisten: admin = semua, manager = per cabang, user = miliknya.
- Mengurangi logika “gudang per user sebagai representasi cabang” yang membingungkan dan rawan error.

## Model Data Target (minimal dan future-proof)
- tb_cabang (Branch)
  - id_cabang (ULID, PK)
  - kode_cabang (unique)
  - nama_cabang
  - alamat (opsional)
  - timestamps
- tb_users (User)
  - + branch_id (FK → tb_cabang.id_cabang, indexed)
  - Pertahankan unique_id (PK) seperti sekarang
  - Secara bertahap deprecate penggunaan langsung branch_name
- tb_gudang (stok per cabang, bukan per user)
  - + branch_id (FK)
  - Sementara pertahankan unique_id untuk backwards-compat
- tb_penggunaan_barang (usage)
  - + branch_id (FK)
  - Pertahankan unique_id untuk mencatat aktor (pemilik/pembuat)
- (Opsional untuk performa pelaporan) tb_pengajuan / tb_detail_pengajuan + branch_id

## Rencana Rollout Bertahap (tanpa downtime)
### Phase 0 – Persiapan (tanpa mengubah perilaku)
1) Buat model Branch + migration tb_cabang.
2) Tambah kolom nullable branch_id di: users, gudang, penggunaan_barang (dan pengajuan jika perlu).
3) Backfill:
   - Ambil daftar cabang unik dari sumber saat ini (mis. users.branch_name).
   - Insert ke tb_cabang, map users.branch_id.
   - Set gudang.branch_id via join lewat user (mengikuti pola “unique_id mewakili cabang”).
   - Set penggunaan_barang.branch_id = user.branch_id untuk tiap baris.
4) Tambah relasi:
   - User belongsTo Branch; Branch hasMany Users.
   - Gudang belongsTo Branch & Barang; PenggunaanBarang belongsTo User & Branch (& Barang).
5) Dual-write (tulis lama+baru):
   - Pada create/update, isi field legacy (mis. unique_id) dan field baru (branch_id) secara konsisten.
6) Dual-read (fallback):
   - Scope seperti forUser/forBranch baca branch_id jika ada, fallback ke unique_id jika belum terisi.
7) Index/constraints:
   - Tambahkan index branch_id di tabel terkait. FK constraints bisa diaktifkan penuh di Phase 2.

### Phase 1 – Ganti ke Pembacaan Berbasis Branch
1) Update services/controllers/policies ke scoping branch_id:
   - Admin: semua cabang.
   - Manager: data dengan branch_id == manager.branch_id.
   - User: datanya sendiri (unique_id) dan/atau query laporan yang berbasis cabang.
2) Resource API: kirimkan metadata branch seperlunya (mis. { id, kode, nama }) agar FE tak perlu join.
3) Tetap dual-write untuk memastikan konsistensi selama transisi.

### Phase 2 – Depresiasi Legacy
1) Lepas kode yang bergantung pada gudang.unique_id untuk scoping.
2) Hapus penggunaan branch_name langsung (opsional, bisa tetap display-only).
3) (Opsional) Hapus unique_id dari gudang jika tidak dibutuhkan lagi; pertahankan di penggunaan_barang untuk menyimpan aktor.
4) Perketat constraints (NOT NULL branch_id jika sudah 100% terisi), aktifkan FK secara penuh.

## Dampak Perubahan Kode
- Backend
  - Migrations: 3–5 file (create tb_cabang, add branch_id, index & FK).
  - Models: tambah Branch; update relasi di User, Gudang, PenggunaanBarang.
  - Services/Controllers: ganti filter unique_id → branch_id untuk scoping agregat (stok, laporan), tetap pakai unique_id untuk aktor.
  - Policies: manager check via branch_id.
  - Resources: sertakan info branch minimal.
- Frontend
  - Hampir tidak terdampak pada tahap awal (endpoint tidak berubah).
  - Peningkatan opsional: tampilkan info cabang, filter per cabang (khusus admin).

## Risiko & Mitigasi
- Integritas data: aktifkan FK setelah backfill; mulai dengan index branch_id.
- Dependency tersembunyi: lakukan pencarian repo untuk penggunaan unique_id pada scoping layanan dan policy.
- Performa: tambahkan composite index untuk query umum, mis. (branch_id, id_barang) atau (branch_id, created_at).
- Rollback: selama Phase 0–1 tetap dual write/read sehingga aman revert jika ada issue.

## Estimasi Waktu
- Phase 0: 1–2 hari (migration, backfill, dual write/read, relasi).
- Phase 1: 1–2 hari (switch controller/service/policy ke branch_id, adjust resource, regression test).
- Phase 2: 0.5–1 hari (hapus legacy, final constraints).
- Total: ~3–5 hari (tergantung banyaknya endpoint laporan dan jumlah query yang perlu diubah).

## Kriteria Penerimaan
- Manager hanya melihat data sesuai cabang di: Penggunaan, Pengajuan, Gudang, Laporan.
- User tetap berfungsi normal tanpa perubahan UI.
- Record baru selalu mengisi branch_id secara konsisten.
- Tidak ada 500 error pada alur Penggunaan/Stok.
- Query laporan/stok memiliki performa setara atau lebih baik.

## Catatan Pelaksanaan (Rekomendasi Praktis)
- Mulai dari tabel paling kritikal untuk scoping (Gudang/Stok → Penggunaan → Laporan).
- Siapkan command artisan untuk backfill agar dapat diulang (idempotent) dan ada logging.
- Feature-flag pembacaan berbasis branch untuk memudahkan A/B dan rollback cepat.

## Next Actions (opsional)
- [ ] Buat migration tb_cabang + tambah branch_id di users, gudang, penggunaan_barang.
- [ ] Buat seeder/backfill command untuk mapping branch.
- [ ] Tambah relasi model & dual write/read.
- [ ] Ubah scoping di services/policies ke branch_id (Phase 1).
- [ ] Bersihkan legacy & kencangkan constraints (Phase 2).

---
Dokumen ini ditujukan untuk developer FE/BE agar selaras pada konsep cabang dan timeline migrasinya, dengan pendekatan bertahap dan perubahan minimal pada kode yang sudah berjalan.