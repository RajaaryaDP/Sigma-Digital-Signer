# SIGMA — Aplikasi Web MVC Native PHP (PBL)


## 🛠️ Persyaratan Lingkungan
- PHP 8.1+ (dengan ekstensi `pdo_pgsql`, `gd`, `fileinfo`)
- Composer
- PostgreSQL 14+

---

## 📦 Setup & Perintah Aplikasi

Jalankan perintah ini langsung di dalam folder proyek:

```bash
# 1. Pasang dependensi
composer install

# 2. Pastikan server PostgreSQL berjalan dan database sesuai config/database.php tersedia
#    (role aplikasi dan database akan dicoba disiapkan oleh config/database.php)

# 3. Migrasi skema database SIGMA
composer migrate

# (Opsi reset ulang: menghapus tabel SIGMA dan data di dalamnya)
php tools/migrate.php --fresh

# 4. Seeding data awal civitas & akun pengujian
composer seed

# 5. Jalankan development server
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
