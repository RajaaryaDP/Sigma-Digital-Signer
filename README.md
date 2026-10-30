# SIGMA — Aplikasi Web MVC Native PHP (PBL)

Repositori ini memuat seluruh kode sumber aplikasi web **SIGMA (Sistem Tanda Tangan Digital)** untuk Politeknik Negeri Jakarta (PNJ).

---

## ⚠️ PERHATIAN PENGEMBANG & AGENT AI (OpenCode / DeepSeek)
1. **Status Implementasi:** Task 1 s.d. Task 7 sudah **SELESAI (DONE)**. Tugas saat ini adalah **Task 8: Halaman Verifikasi Publik Scan QR (`/verify?token=...`)**.
2. **Modul Warisan UTS (Legacy Archive):**
   - Modul `controllers/Accounts.php`, `controllers/AccountTypes.php`, `controllers/Actions.php` beserta model dan view terkait adalah **modul arsip latihan UTS**.
   - **JANGAN DIUBAH, JANGAN DIHAPUS, DAN JANGAN DIJADIKAN ACUAN** untuk fitur SIGMA.
3. **Modul Aktif Resmi SIGMA:**
   - **Autentikasi & Sesi:** `controllers/Auth.php`, `models/UsersModel.php`, `views/auth/`
   - **Master Data Civitas (CRUD Admin):** `controllers/Civitas.php`, `models/CivitasModel.php`, `views/civitas/`
   - **Template Tanda Tangan Pribadi:** `controllers/UserSignatures.php`, `models/UserSignaturesModel.php`, `views/user-signatures/`
   - **Manajemen & Alur Dokumen:** `controllers/Documents.php`, `models/DocumentsModel.php`, `views/documents/`
   - **Verifikasi Publik (Scan QR):** `controllers/Verify.php`, `views/verify/`
   - **Log Audit Terpusat:** `models/AuditLogModel.php`

---

## 🛠️ Persyaratan Lingkungan
- PHP 8.1+ (dengan ekstensi `pdo_mysql`, `gd`, `fileinfo`)
- Composer
- MySQL / MariaDB (Laragon / XAMPP)

---

## 📦 Setup & Perintah Aplikasi

Jalankan perintah ini langsung di dalam folder `MVC_example/`:

```bash
# 1. Pasang dependensi
composer install

# 2. Migrasi skema database (7 tabel resmi SIGMA)
composer migrate

# (Opsi reset ulang database bersih)
php tools/migrate.php --fresh

# 3. Seeding data awal civitas & akun pengujian
composer seed

# 4. Jalankan development server
composer serve
```

Server lokal akan berjalan di `http://localhost:5000`.

---

## 🔑 Akun Uji Coba Default (Password: `password`)
| Peran | Email | NIP / NIM | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@sigma.local` | `198501012010121001` | Verifikasi berkas & CRUD master civitas |
| **Mahasiswa (Pengaju)** | `mahasiswa@sigma.local` | `2107411001` | Unggah PDF & penataan slot berjenjang |
| **Dosen Pembimbing** | `dosen@sigma.local` | `197505102000031002` | Penandatangan Step 1 (Sequential) |
| **Ketua Jurusan (Kajur)** | `kajur@sigma.local` | `196803151993031001` | Penandatangan Step 2 (Sequential) |
| **Tenaga Kependidikan** | `tendik@sigma.local` | `198002202005012003` | Staf administrasi |

---

## 🔒 Konvensi Pengembangan
- **Routing:** Pola `index.php` `/{resource}/{id?}/{action?}`.
- **Model:** Wajib menggunakan PDO Prepared Statements (`$stmt->prepare()`), Soft Delete (`deleted_at IS NULL`), dan UUID v4 PHP (`Uuid::uuid4()->toString()`).
- **Autoload:** Tidak ada autoloader aplikasi; setiap file wajib me-`require_once` model/helper yang dipakai.
- **Penyimpanan Berkas Privat:** Berkas PDF dan gambar tanda tangan disimpan di `storage/` dan hanya disajikan lewat controller streaming, tidak disajikan statis oleh web server.
