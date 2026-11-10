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

Berikut adalah bukti tangkapan layar pengujian fungsional web service SIPUS-Del menggunakan Postman sesuai dengan masing-masing modul:

---

### 1. Modul SOAP Clearance Service (Pengecekan Bebas Pustaka)

#### A. Verifikasi Status Bebas Pustaka Mahasiswa (200 OK)
Permintaan `POST /soap/sipus` dengan payload SOAP XML berisi `<nim_peminjam>12S24047</nim_peminjam>`. Sistem memeriksa basis data peminjaman aktif dan mengembalikan respon SOAP Envelope dengan `<active_loans>0</active_loans>` dan `<is_cleared>true</is_cleared>`.  
![SOAP Clearance Check Berhasil](docs/screenshots/Screenshot%202026-11-06%20143303.png)

#### B. Penanganan SOAP Fault saat Input Cacat / Kosong (400 Bad Request)
Permintaan `POST /soap/sipus` dengan elemen `<nim_peminjam></nim_peminjam>` kosong. Sistem menolak dan merespon dengan `<soap:Fault>` HTTP 400 Bad Request: *"Nilai &lt;nim_peminjam&gt; tidak boleh kosong."*.  
![SOAP Fault NIM Kosong](docs/screenshots/Screenshot%202026-11-06%20143552.png)

---

### 2. Modul Autentikasi Pustakawan (Laravel Sanctum)

#### A. Login Pustakawan dan Penerbitan Bearer Token (200 OK)
Permintaan `POST /api/v1/auth/login` menggunakan kredensial pustakawan uji (`pustakawan@del.ac.id` / `password123`). Sistem menerbitkan Personal Access Token Sanctum dan mengembalikan data profil pustakawan terautentikasi.  
![Login Pustakawan Sanctum](docs/screenshots/Screenshot%202026-11-06%20143858.png)

---

### 3. Modul Katalog Buku RESTful API, HATEOAS, dan Mutasi

#### A. Katalog Buku Publik Terpaginasi Bebas N+1 (200 OK)
Permintaan `GET /api/v1/bukus` dengan format JSON. Data buku dimuat bersama relasi `kategori` dan agregat `total_peminjaman` secara atomik (*eager loading*) sehingga terbebas dari isu query N+1.  
![Katalog Buku Terpaginasi](docs/screenshots/Screenshot%202026-11-06%20144048.png)

#### B. Penambahan Buku Baru (POST 201 Created)
Permintaan `POST /api/v1/bukus` dengan autentikasi Bearer Token pustakawan. Buku baru *"Pemrograman API Modern"* berhasil ditambahkan dengan status `201 Created` disertai header `Location`.  
![Tambah Buku Baru](docs/screenshots/Screenshot%202026-11-06%20144444.png)

#### C. Pembaruan Sebagian Atribut Buku (PATCH 200 OK)
Permintaan `PATCH /api/v1/bukus/7` dengan payload parsial `{"stok": 5}`. Sistem memperbarui atribut stok menjadi 5 tanpa memodifikasi field lainnya.  
![Update Sebagian PATCH](docs/screenshots/Screenshot%202026-11-06%20144601.png)

#### D. Pembaruan Penuh Buku dengan Reset Nullable (PUT 200 OK)
Permintaan `PUT /api/v1/bukus/7` mengirim seluruh atribut buku tanpa menyertakan `sinopsis`. Atribut `sinopsis` secara otomatis di-reset menjadi `null`.  
![Update Penuh PUT](docs/screenshots/Screenshot%202026-11-06%20144637.png)

---

### 4. Modul Sirkulasi Peminjaman Atomik (Pessimistic Concurrency Lock)

#### A. Transaksi Peminjaman Baru Berhasil (POST 201 Created)
Permintaan `POST /api/v1/peminjamans` oleh mahasiswa NIM `12S24047` untuk meminjam buku ID 7. Transaksi dibungkus *pessimistic locking* (`lockForUpdate`), stok buku dipotong `-1`, kode transaksi unik diterbitkan (`PINJ-0CPEWV`), dan mengembalikan status `201 Created`.  
![Peminjaman Buku Berhasil](docs/screenshots/Screenshot%202026-11-06%20144811.png)

#### B. Penolakan Peminjaman Ganda oleh Mahasiswa yang Sama (409 Conflict)
Permintaan `POST /api/v1/peminjamans` kedua kali oleh mahasiswa NIM `12S24047` untuk judul buku yang sama yang belum dikembalikan. Sistem menolak dengan respon standar RFC 7807 `409 Conflict`: *"Mahasiswa dengan NIM 12S24047 masih meminjam buku ... dan belum mengembalikannya."*.  
![Penolakan Pinjam Ganda 409](docs/screenshots/Screenshot%202026-11-06%20144859.png)

#### C. Penolakan Peminjaman saat Stok Buku Habis (422 Unprocessable Content)
Permintaan `POST /api/v1/peminjamans` untuk buku ID 3 yang memiliki stok 0. Transaksi di-rollback secara atomik dan menghasilkan respon RFC 7807 `422 Unprocessable Content`: *"Stok buku sedang habis."*.  
![Penolakan Stok Habis 422](docs/screenshots/Screenshot%202026-11-06%20144943.png)

#### D. Pengembalian Buku Berhasil & Pemulihan Stok (PATCH 200 OK)
Permintaan `PATCH /api/v1/peminjamans/5/kembalikan` mengunci baris data transaksi dan buku. Sistem memperbarui status menjadi `'dikembalikan'`, mengisi tanggal pengembalian, dan memulihkan stok fisik buku (`+1`).  
![Pengembalian Buku Berhasil](docs/screenshots/Screenshot%202026-11-06%20145053.png)

#### E. Penolakan Pengembalian Buku yang Sudah Dikembalikan (409 Conflict)
Permintaan `PATCH /api/v1/peminjamans/5/kembalikan` dijalankan kembali pada transaksi yang sudah berstatus `'dikembalikan'`. Sistem menolak dengan respon RFC 7807 `409 Conflict`: *"Buku pada transaksi ini sudah berstatus dikembalikan sebelumnya."*.  
![Penolakan Pengembalian Berulang 409](docs/screenshots/Screenshot%202026-11-06%20145113.png)

---

### 5. Modul Integrasi Klien API Eksternal (Consumer API)

#### A. Pengujian Konsumsi Layanan HTTP Eksternal
Pengujian pemanggilan layanan HTTP eksternal pihak ketiga dengan manajemen header otentikasi Bearer dan payload JSON mutasi profil (`PATCH https://api.github.com/user`).  
![Konsumsi API Eksternal](docs/screenshots/Screenshot%202026-11-06%20145553.png)