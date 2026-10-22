# Jaya POS API

Backend dan panel admin untuk **Jaya POS**, sistem kasir cafe yang dirancang untuk dijual ke banyak cafe. Repo ini adalah **otak dan pusat data** dari seluruh sistem.

---

## 1. Ini web apa?

Project ini berisi dua hal dalam satu aplikasi Laravel:

| Bagian | Alamat | Dipakai oleh | Fungsi |
|---|---|---|---|
| **Panel Admin** (web) | `/admin` | Super admin, owner, manager | Mengelola menu, kategori, opsi, dan pengguna lewat browser |
| **API** (tanpa tampilan) | `/api/v1/...` | Aplikasi kasir Flutter | Melayani login PIN dan mengirim data menu ke tablet/HP kasir |

Jadi web ini **bukan aplikasi kasir itu sendiri**. Kasir bekerja di aplikasi Android (project terpisah: `jaya_pos_app`), dan aplikasi itu mengambil semua datanya dari web ini.

```
  Browser (owner/admin)            Tablet/HP kasir (Flutter)
          |                                  |
      /admin (Filament)                 /api/v1 (JSON)
          \                                  /
           \________  Laravel  _____________/
                         |
                       MySQL
                    (database jaya_pos)
```

## 2. Dipakai untuk apa?

Produk ini dijual ke cafe yang belum memakai program kasir. Setiap cafe pelanggan mendapat:

- **Aplikasi kasir** untuk transaksi harian.
- **Panel web** untuk mengatur menu, harga, opsi, dan pengguna.
- **Data terpisah**: satu sistem melayani banyak cafe, tetapi cafe A tidak pernah melihat data cafe B (*multi-tenant*).

Yang sudah ada saat ini:

- Panel admin: kelola **Menu, Kategori, Opsi / Modifier, Pengguna**.
- API: **login PIN** dan **unduh data menu** untuk aplikasi kasir.
- Struktur database lengkap untuk penjualan, shift, stok, dan resep (tabelnya sudah ada, fiturnya menyusul).

## 3. Siapa bisa melakukan apa

| Role | Panel web `/admin` | Aplikasi kasir |
|---|---|---|
| `super_admin` (pemilik produk) | Ya, melihat semua cafe | Tidak |
| `owner` | Ya, hanya cafe-nya | Ya (laporan dan persetujuan void) |
| `manager` | Ya, hanya cafe-nya | Ya |
| `cashier` | **Tidak** (ditolak) | Ya |

Kasir yang mencoba login ke `/admin` akan melihat pesan "These credentials do not match our records". Itu disengaja, sebagai tanda akun tidak diizinkan masuk panel.

---

## 4. Cara pakai

### A. Menyalakan server (Windows + Laragon)

```powershell
cd C:\laragon\www\jaya-pos-api
php artisan serve
```

Pastikan Laragon (MySQL) menyala. Untuk dicoba dari HP/tablet asli di jaringan yang sama, pakai `php artisan serve --host=0.0.0.0` dan izinkan di firewall Windows. Dari emulator Android, alamat servernya `http://10.0.2.2:8000`.

### B. Memakai panel admin

1. Buka `http://127.0.0.1:8000/admin`.
2. Login:

| Akun | Email | Password |
|---|---|---|
| Super admin | akun dari `php artisan make:filament-user` | milik Anda |
| Owner Jaya Cafe (demo) | `owner@jaya.test` | `password` |

3. Menu di sidebar:
   - **Katalog**: *Menu* (nama, harga, kategori, status tersedia/habis, grup opsi), *Kategori*, *Opsi / Modifier* (grup seperti Gula, Es, Ukuran, Topping beserta pilihan dan tambahan harga).
   - **Pengelolaan**: *Pengguna* (nama, email, password web, **PIN kasir**, role, outlet, aktif/nonaktif).
4. Untuk menandai menu **Habis**, matikan toggle *Tersedia* di tabel Menu. Perubahan langsung tersimpan.
5. Untuk menambah kasir baru: **Pengguna, New Pengguna**, isi PIN 4 sampai 6 angka, pilih role `cashier`.

Super admin melihat kolom **Cafe** di tabel Menu. Owner tidak, karena ia hanya melihat cafe-nya.

### C. Memakai API

Semua alamat berawalan `http://127.0.0.1:8000/api/v1`. Alurnya yang dipakai aplikasi kasir:

| Langkah | Method dan alamat | Butuh token? | Keterangan |
|---|---|---|---|
| 1. Daftar staf | `GET /cafes/{kode-cafe}/staff` | Tidak | Untuk layar pilih nama. Contoh kode cafe: `jaya-cafe` |
| 2. Login PIN | `POST /auth/pin-login` | Tidak | Kirim `cafe`, `user_id`, `pin`, `device_name`. Mengembalikan **token** |
| 3. Unduh menu | `GET /menu` | **Ya** | Kategori, produk, grup opsi, dan meja dalam satu paket |
| 4. Profil | `GET /me` | **Ya** | Data pengguna dan pengaturan cafe |
| 5. Keluar | `POST /auth/logout` | **Ya** | Menghapus token perangkat itu |

Contoh uji lewat PowerShell:

```powershell
# 1. Daftar staf
Invoke-RestMethod http://127.0.0.1:8000/api/v1/cafes/jaya-cafe/staff | ConvertTo-Json -Depth 5

# 2. Login PIN (ganti user_id sesuai hasil langkah 1)
$body = @{cafe='jaya-cafe'; user_id=3; pin='1111'; device_name='Tablet Kasir 1'} | ConvertTo-Json
$r = Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8000/api/v1/auth/pin-login -Body $body -ContentType 'application/json'

# 3. Unduh menu memakai token
Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v1/menu -Headers @{Authorization="Bearer $($r.token)"; Accept='application/json'} | ConvertTo-Json -Depth 6
```

Respons gagal yang normal: **422 "PIN salah."** untuk PIN yang keliru, **401** untuk token yang hilang atau tidak valid, **429** bila login dicoba lebih dari 6 kali per menit.

Header `Accept: application/json` wajib disertakan agar kegagalan autentikasi dijawab sebagai JSON (401), bukan dialihkan ke halaman login.

---

## 5. Penjelasan istilah

**API.** Pintu layanan yang bisa dipanggil oleh program lain, tanpa tampilan. Aplikasi Flutter "bertanya" ke API ("daftar menu cafe ini?"), lalu API menjawab dengan data berformat **JSON**. Karena API, panel web dan aplikasi kasir memakai data yang sama dan selalu sinkron.

**JSON.** Format teks untuk pertukaran data, misalnya `{"name": "Americano", "price": 18000}`. Dipahami oleh Laravel maupun Flutter.

**Token.** Seperti **tiket masuk**. Setelah PIN benar, server memberikan satu string acak panjang (contoh `1|l6cdm...`). Untuk setiap permintaan berikutnya, aplikasi cukup menunjukkan token itu, dan tidak perlu mengirim PIN lagi. Ini lebih aman karena PIN hanya dikirim sekali.

**Bearer.** Cara standar menyertakan token, yaitu lewat header `Authorization: Bearer <token>`.

**Laravel Sanctum.** Paket resmi Laravel yang **membuat, menyimpan, dan memeriksa token**. Token disimpan di tabel `personal_access_tokens` dalam bentuk ter-hash, jadi bocornya database tidak langsung membocorkan token yang bisa dipakai. Satu perangkat mendapat satu token (diberi nama lewat `device_name`), sehingga kita bisa mencabut akses satu tablet tanpa mengganggu yang lain. Token ini punya kemampuan (*ability*) `pos`, yang dipakai untuk membatasi apa yang boleh dilakukan.

**`auth:sanctum` (middleware).** Penjaga pintu di `routes/api.php`. Rute yang dibungkus penjaga ini hanya bisa diakses bila tokennya valid, dan otomatis mengenali siapa penggunanya.

**Throttle (`throttle:6,1`).** Pembatas laju: maksimal 6 percobaan login per menit. PIN hanya 4 sampai 6 angka sehingga rawan ditebak berulang-ulang, dan pembatas ini mempersulitnya.

**Hash.** Mengubah PIN/password menjadi kode acak satu arah sebelum disimpan. Yang tersimpan di database bukan PIN aslinya. Saat login, Laravel mencocokkan PIN yang diketik dengan hash-nya (`Hash::check`).

**Multi-tenant dan `cafe_id`.** Satu sistem melayani banyak cafe. Hampir semua tabel punya kolom `cafe_id`. Trait `BelongsToCafe` otomatis menyaring data sehingga user cafe hanya melihat data cafe-nya, dan mengisi `cafe_id` saat data baru dibuat. User tanpa `cafe_id` (super admin) melihat semuanya.

**Filament.** Perangkat untuk membangun panel admin dengan cepat: tabel, form, pencarian, filter, dan hak akses sudah tersedia. Panel `/admin` dibuat dengannya.

**Spatie Permission.** Paket untuk **role** (`super_admin`, `owner`, `manager`, `cashier`). Dipakai untuk menentukan siapa boleh masuk panel dan nanti siapa boleh menyetujui void.

**Migration.** File PHP yang mendefinisikan tabel database. Membuat struktur database bisa diulang dan terdokumentasi (`php artisan migrate`).

**Seeder.** Skrip pengisi data contoh (`CafeDemoSeeder`): cafe demo, akun, role, menu, dan meja.

**Model (Eloquent).** Kelas PHP yang mewakili satu tabel, misalnya `Product` untuk tabel `products`.

**UUID.** ID acak panjang untuk tabel `orders` dan `shifts`. Dibuat di perangkat kasir supaya transaksi yang dikirim ulang (misalnya setelah sinyal putus) tidak menjadi data ganda.

---

## 6. Tech stack

- PHP 8.4, Laravel 12, **Filament 5** (panel admin)
- MySQL 8 (database `jaya_pos`)
- **Laravel Sanctum** (token API)
- spatie/laravel-permission, spatie/laravel-activitylog, spatie/laravel-medialibrary
- Aplikasi kasir: Flutter (Dart), project terpisah `jaya_pos_app`

## 7. Struktur kode penting

```
app/
  Http/Controllers/Api/
    AuthController.php        # staf, login PIN, profil, logout
    MenuController.php        # paket data menu
  Models/
    Concerns/BelongsToCafe.php  # pemisah data antar cafe
    Cafe, Outlet, Category, Product, OptionGroup, Option, DiningTable, User
  Filament/Resources/
    Products/  Categories/  OptionGroups/  Users/
  Providers/Filament/AdminPanelProvider.php   # nama, warna, grup menu panel
routes/api.php                # semua alamat API
database/
  migrations/                 # tahap 1 (katalog) dan tahap 2 (penjualan dan stok)
  seeders/CafeDemoSeeder.php
```

## 8. Instalasi dari nol

```powershell
cd C:\laragon\www\jaya-pos-api
composer install
copy .env.example .env
php artisan key:generate
```

Buat database `jaya_pos` (HeidiSQL), lalu atur `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jaya_pos
DB_USERNAME=root
DB_PASSWORD=
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
```

```powershell
php artisan migrate
php artisan db:seed --class=CafeDemoSeeder
php artisan make:filament-user
php artisan serve
```

Akun demo hasil seeder:

| Akun | Email | Password | PIN |
|---|---|---|---|
| Owner Jaya Cafe | `owner@jaya.test` | `password` | `1234` |
| Kasir (Rina) | `kasir@jaya.test` | `password` | `1111` |

> Jangan menjalankan `migrate:fresh`, karena menghapus akun super admin buatan `make:filament-user`.

---

## 9. Catatan keamanan

- **Ganti semua akun, password, dan PIN demo sebelum dipakai di cafe sungguhan.**
- Token Sanctum secara bawaan **tidak kedaluwarsa**. Sebelum produksi, atur masa berlaku di `config/sanctum.php` (`expiration`) dan sediakan cara mencabut token perangkat yang hilang.
- Token uji dari percobaan manual bisa dihapus dengan `php artisan tinker`, lalu `App\Models\User::find(3)->tokens()->delete();`
- Selalu gunakan **HTTPS** di server produksi, karena token dan PIN dikirim lewat jaringan.
- Cafe yang dinonaktifkan (`is_active = false`) tidak bisa login baru, tetapi token lama belum otomatis dicabut. Itu perlu ditangani sebelum rilis.

## 10. Status pengerjaan

- [x] Setup Laravel, Filament, MySQL, Sanctum, paket Spatie
- [x] Migrasi tahap 1 dan 2, model katalog, seeder data demo
- [x] Pemisahan data per cafe (diuji: super admin, owner, kasir)
- [x] Panel admin: Menu, Kategori, Opsi / Modifier, Pengguna
- [x] API: staf, login PIN, profil, logout, menu (diuji)
- [ ] Aplikasi Flutter (login PIN, grid menu, keranjang, bayar, struk)
- [ ] API transaksi: shift, order (idempoten dengan UUID), pembayaran, void dengan PIN owner
- [ ] Model dan Filament untuk resep, bahan baku, supplier, pembelian, stok opname
- [ ] Laporan pemilik (omzet, laba rugi, margin per menu, selisih kas)
- [ ] Penyesuaian tampilan admin agar sama dengan mockup (custom page Filament)
- [ ] Sinkronisasi offline, printer Bluetooth, panel Super Admin dan paket langganan

## 11. Langkah selanjutnya

1. **Aplikasi Flutter** (`jaya_pos_app`): tema dari mockup, layar kode cafe, pilih staf, PIN, lalu grid menu dari `/menu`.
2. **API transaksi**: buka/tutup shift, simpan order, pembayaran, void.
3. **Resep dan stok**: panel dan pengurangan stok otomatis.
4. **Tampilan admin sesuai mockup** setelah jalur tampilannya diputuskan bersama direksi.
5. **Laporan pemilik**, offline, printer, dan panel Super Admin.

## 12. Masalah yang pernah muncul (Windows)

- `composer require filament/filament:"^5.0"` gagal karena PowerShell memakan `^`. Pakai `composer require filament/filament`.
- Login kasir ke `/admin` tampil "credentials do not match". Itu penolakan role, bukan password salah.
- PowerShell memotong hasil `Invoke-RestMethod`. Tambahkan `| ConvertTo-Json -Depth 5` untuk melihat hasil lengkap.
