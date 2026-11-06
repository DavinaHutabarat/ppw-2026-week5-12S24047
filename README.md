# SIPUS-Del (Sistem Informasi Perpustakaan Kampus Del)

**Mata Kuliah**: Pemrograman & Pengujian Web (12S3101)  
**Program Studi**: Sarjana Sistem Informasi - Institut Teknologi Del  
**Tahun Akademik**: 2025/2026  

---

## 1. Identitas Pengembang & Kredensial Uji Sistem
- **Nama Mahasiswa**: Davina Olivia Yosefanny Hutabarat
- **NIM**: 12S24047
- **Branch Penugasan**: `week9-rest-api`
- **Framework**: Laravel 11.x (PHP 8.4)

### Kredensial Akun Pustakawan Uji (Seeder)
- **Email**: `pustakawan@del.ac.id`
- **Password**: `password123`
*(Diinisialisasi secara otomatis melalui `DatabaseSeeder` / `SipusSeeder`)*

---

## 2. Analisis Kelayakan Arsitektur: Provider vs Consumer & SOAP vs REST

Dalam perancangan sistem informasi modern tingkat kampus, interoperabilitas antar-layanan ditentukan oleh ketepatan pemilihan pola komunikasi web service.

### A. Pola Provider vs Consumer pada SIPUS-Del

1. **SIPUS-Del sebagai Provider (Penyedia Layanan)**:
   - **RESTful API Provider**: Sistem menyediakan endpoint API v1 berbasis JSON untuk dikonsumsi oleh aplikasi klien (antarmuka Web Blade, Mobile Application civitas akademika Del, maupun portal perpustakaan terpadu).
   - **SOAP Service Provider**: Sistem menyediakan antarmuka SOAP XML formal (`/soap/sipus`) dengan kontrak WSDL ringkas untuk keperluan audit kelulusan (*Clearance Check*). Layanan ini dikonsumsi oleh Sistem Informasi Akademik (SIA/BAA) Del saat mahasiswa mengajukan pendaftaran wisuda atau yudisium untuk memastikan tidak ada tanggungan pinjaman buku aktif.

2. **SIPUS-Del sebagai Consumer (Konsumen Layanan)**:
   - Sistem bertindak sebagai klien HTTP yang mengonsumsi layanan publik pihak ketiga, yaitu **OpenLibrary Books API**. Saat pustakawan atau mahasiswa membuka detail buku, sistem secara mandiri menghubungi upstream OpenLibrary menggunakan nomor ISBN buku untuk memperkaya metadata bibliografi (penulis, sinopsis penerbit, tanggal terbit internasional, cover buku) tanpa membebani penyimpanan basis data lokal kampus.

---

### B. Perbandingan Komprehensif: SOAP vs REST

| Aspek Evaluasi | SOAP (Simple Object Access Protocol) | RESTful API (Representational State Transfer) |
| :--- | :--- | :--- |
| **Sifat Arsitektural** | Protokol komunikasi terstandarisasi ketat (W3C standard) | Gaya arsitektur (*architectural style*) berbasis protokol HTTP |
| **Format Pertukaran Data** | Eksklusif menggunakan format XML | Umumnya menggunakan JSON (ringkas dan hemat bandwidth) |
| **Definisi Kontrak** | Sangat formal via dokumen WSDL (*Web Services Description Language*) | Fleksibel dan human-readable via OpenAPI (OAS 3.0) / Postman |
| **Transport Protocol** | Independen (dapat berjalan di atas HTTP, SMTP, JMS, TCP) | Terikat langsung pada semantik protokol HTTP standar |
| **Dukungan Caching** | Terbatas (semua request umumnya menggunakan HTTP POST) | Sangat baik (memanfaatkan HTTP caching header seperti `ETag`, `Cache-Control`) |
| **Beban Kinerja (Overhead)** | Lebih berat karena amplop SOAP (*Envelope/Body*) dan parsing XML | Ringan, cepat diparsing oleh mesin JavaScript / browser / mobile |
| **Kasus Penggunaan Terbaik** | Komunikasi enterprise B2B antar-sistem internal kampus (akademik, keuangan, yudisium) yang mensyaratkan integritas data tinggi dan kontrak kaku | Integrasi modern aplikasi web, aplikasi seluler, public catalog, dan konsumsi microservices |

**Justifikasi Penerapan di SIPUS-Del**:
- **SOAP** diterapkan khusus pada modul *Clearance Check* (`CheckClearanceStatus`) karena proses yudisium/wisuda memerlukan kepastian kontrak biner yang baku, deterministik, dan dapat diverifikasi langsung oleh service registry kampus.
- **RESTful API** diterapkan pada seluruh modul inti perpustakaan (katalog buku dan sirkulasi peminjaman) demi kemudahan konsumsi oleh berbagai platform klien, dukungan *stateless token-based authentication*, dan pemanfaatan semantik HTTP murni (kode status, method, header Location, dan format kesalahan standar RFC 7807).

---

## 3. Katalog Lengkap Endpoint SIPUS-Del v1

Berikut adalah tabel katalog seluruh endpoint layanan web service yang tersedia pada aplikasi SIPUS-Del:

| Metode | URI Endpoint | Autentikasi | Deskripsi Fungsional | Kode Status Respon |
| :---: | :--- | :---: | :--- | :---: |
| **GET** | `/soap/sipus.wsdl` | Publik | Menyajikan dokumen definisi kontrak WSDL SOAP | `200` |
| **POST** | `/soap/sipus` | Publik | Memproses verifikasi bebas pustaka (*Clearance*) via SOAP Envelope | `200`, `400` (SOAP Fault) |
| **POST** | `/api/v1/auth/login` | Publik | Otentikasi pustakawan dan penerbitan token Bearer Sanctum | `200`, `401`, `422` |
| **GET** | `/api/v1/auth/me` | Bearer Token | Mengambil data profil pustakawan terautentikasi | `200`, `401` |
| **POST** | `/api/v1/auth/logout` | Bearer Token | Mencabut (*revoke*) token aktif sesi yang digunakan | `204`, `401` |
| **GET** | `/api/v1/bukus` | Publik | Katalog buku terpaginasi (bebas N+1, filter, sorting, HATEOAS) | `200` |
| **GET** | `/api/v1/bukus/{id}` | Publik | Detail buku lengkap beserta relasi kategori dan riwayat peminjaman | `200`, `404` |
| **POST** | `/api/v1/bukus` | Bearer Token | Menambahkan data buku baru (mengembalikan header `Location`) | `201`, `401`, `422` |
| **PUT** | `/api/v1/bukus/{id}` | Bearer Token | Memperbarui seluruh atribut buku (reset nullable field jika absen) | `200`, `401`, `404`, `422` |
| **PATCH** | `/api/v1/bukus/{id}` | Bearer Token | Memperbarui sebagian atribut buku (menolak payload kosong) | `200`, `400`, `401`, `404`, `422` |
| **DELETE** | `/api/v1/bukus/{id}` | Bearer Token | Menghapus buku (menolak dengan 409 jika ada pinjaman aktif) | `204`, `401`, `404`, `409` |
| **GET** | `/api/v1/bukus/{id}/referensi-eksternal` | Publik | Integrasi konsumer OpenLibrary API via ISBN dengan timeout 8 detik | `200`, `404`, `502` |
| **GET** | `/api/v1/peminjamans` | Bearer Token | Daftar transaksi sirkulasi peminjaman terpaginasi | `200`, `401` |
| **GET** | `/api/v1/peminjamans/{id}` | Bearer Token | Detail satu data transaksi sirkulasi peminjaman | `200`, `401`, `404` |
| **POST** | `/api/v1/peminjamans` | Bearer Token | Peminjaman atomik (Pessimistic Lock, cegah pinjam ganda, stok -1) | `201`, `401`, `409`, `422` |
| **PATCH** | `/api/v1/peminjamans/{id}/kembalikan` | Bearer Token | Pengembalian atomik (Pessimistic Lock, pulihkan stok +1, tanggal kembali) | `200`, `401`, `404`, `409` |

*Catatan: Seluruh respon galat pada rute `/api/*` dikembalikan dalam format standar **RFC 7807 (`application/problem+json`)** yang memuat properti `type`, `title`, `status`, `detail`, `instance`, dan array `errors` (pada validasi 422).*

---

## 4. Panduan Instalasi dan Menjalankan Proyek

### Kebutuhan Lingkungan:
- PHP >= 8.3 (dengan ekstensi `pdo_mysql`, `curl`, `mbstring`, `xml`, `zip`, `intl` aktif)
- MySQL / MariaDB Server (misalnya bawaan Laragon / XAMPP)
- Composer 2.x

### Langkah-Langkah Menjalankan:
1. **Clone repository dan beralih ke branch tugas**:
   ```bash
   git checkout week9-rest-api
   ```

2. **Pasang seluruh dependensi aplikasi**:
   ```bash
   composer install
   ```

3. **Konfigurasi Lingkungan (`.env`)**:
   Salin `.env.example` jika file `.env` belum tersedia:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Pastikan konfigurasi basis data pada `.env` telah sesuai:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ppw-week6
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Jalankan Migrasi dan Seeder**:
   ```bash
   php artisan migrate:fresh --seed
   ```
   *Perintah ini akan membuat struktur tabel basis data lengkap, tabel `personal_access_tokens` Sanctum, serta menginisialisasi akun pustakawan dan sampel data buku/peminjaman.*

5. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Aplikasi akan melayani permintaan pada `http://localhost:8000`.

---

## 5. Dokumentasi Kontrak & Koleksi Postman

Berkas kontrak dan koleksi pengujian resmi telah disediakan di direktori `docs/`:
- **Spesifikasi OpenAPI 3.0.3**: [`docs/openapi-sipus-v1.yaml`](docs/openapi-sipus-v1.yaml)
- **Koleksi Postman v2.1.0**: [`docs/SIPUS-Del-APIv1.postman_collection.json`](docs/SIPUS-Del-APIv1.postman_collection.json)
  - Variabel bawaan: `base_url` (`http://localhost:8000`) dan `token`.
  - Dilengkapi skrip otomatis penangkap token setelah *Login Pustakawan*.

---

## 6. Bukti Pengujian Sistem (Screenshots)

Berikut adalah placeholder bukti tangkapan layar pengujian fungsional web service SIPUS-Del:

### 1. Bukti Uji 1: Layanan SOAP Clearance Service
Pengujian permintaan kontrak `GET /soap/sipus.wsdl` dan pengujian `POST /soap/sipus` untuk pengecekan bebas pustaka mahasiswa (status aktif > 0 dan bebas pustaka 0 pinjaman), serta penanganan `soap:Fault` saat payload XML rusak atau NIM kosong.  
![Bukti Uji 1 - Layanan SOAP Clearance](docs/screenshots/01-soap-clearance.png)

---

### 2. Bukti Uji 2: Penanganan Exception Global Standar RFC 7807
Pengujian respon format `application/problem+json` pada rute API saat terjadi error 401 Unauthorized (tanpa token), 404 Not Found (resource ID tidak ditemukan), dan 422 Unprocessable Content (validasi input gagal dengan rincian atribut `errors`).  
![Bukti Uji 2 - RFC 7807 Problem Details](docs/screenshots/02-rfc7807-exceptions.png)

---

### 3. Bukti Uji 3: Autentikasi Pustakawan Sanctum
Pengujian siklus hidup otentikasi REST API: `POST /api/v1/auth/login` yang berhasil menerbitkan Personal Access Token, pengambilan profil pustakawan pada `GET /api/v1/auth/me`, dan pencabutan token pada `POST /api/v1/auth/logout`.  
![Bukti Uji 3 - Sanctum Authentication](docs/screenshots/03-sanctum-auth.png)

---

### 4. Bukti Uji 4: Katalog Buku REST API, Filter & Tautan HATEOAS
Pengujian endpoint publik `GET /api/v1/bukus` dengan pagination meta, parameter pencarian, sorting whitelist, pencegahan query N+1, serta verifikasi bahwa link HATEOAS `_links.pinjam_buku` hanya muncul apabila stok buku fisik > 0.  
![Bukti Uji 4 - Katalog Buku & HATEOAS](docs/screenshots/04-books-catalog-hateoas.png)

---

### 5. Bukti Uji 5: Operasi CRUD Mutasi Buku & HTTP Status Code
Pengujian operasi mutasi: `POST /api/v1/bukus` (201 Created + header Location), `PUT` update penuh yang mereset nilai nullable menjadi null, `PATCH` yang menolak payload kosong dengan 400 Bad Request, dan penolakan `DELETE` dengan 409 Conflict saat buku masih memiliki pinjaman aktif.  
![Bukti Uji 5 - CRUD Buku Mutasi](docs/screenshots/05-books-crud-mutation.png)

---

### 6. Bukti Uji 6: Sirkulasi Peminjaman & Pengembalian Atomik (ACID Concurrency)
Pengujian transaksi sirkulasi peminjaman dengan *Pessimistic Locking* (`lockForUpdate`): penolakan peminjaman buku stok 0 (422), penolakan mahasiswa meminjam buku yang sama sebelum dikembalikan (409), transaksi sukses (201 Created), dan pengembalian buku (200 OK) yang memulihkan stok serta menolak pengembalian berulang (409).  
![Bukti Uji 6 - Sirkulasi Peminjaman Atomik](docs/screenshots/06-loans-circulation-atomic.png)

---

### 7. Bukti Uji 7: Konsumer Layanan Eksternal OpenLibrary API
Pengujian konsumsi data bibliografi eksternal pada endpoint `GET /api/v1/bukus/{id}/referensi-eksternal` dengan batasan timeout 8 detik, serta penanganan respon saat ISBN tidak ditemukan (404) dan penanganan kegagalan jaringan upstream (502 Bad Gateway) tanpa menyebabkan aplikasi mengalami *crash*.  
![Bukti Uji 7 - Konsumer OpenLibrary](docs/screenshots/07-openlibrary-consumer.png)