<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SipusSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Pustakawan uji
        User::updateOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            [
                'name'     => 'Pustakawan Del',
                'password' => Hash::make('password123'),
            ]
        );

        $kategori = Kategori::firstOrCreate(
            ['kode_kategori' => 'TEK'],
            ['nama_kategori' => 'Teknologi']
        );

        // 2. Sampel Buku Stok 0 (Uji penolakan & rollback saat stok habis)
        $bukuKosong = Buku::updateOrCreate(
            ['isbn' => '978-602-0001-01'],
            [
                'judul'        => 'Sistem Terdistribusi Modern',
                'penulis'      => 'Chandro Pardede',
                'penerbit'     => 'Del Press',
                'tahun_terbit' => 2026,
                'kategori_id'  => $kategori->id,
                'stok'         => 0,
                'sinopsis'     => 'Buku sampel dengan stok 0 untuk menguji penolakan peminjaman.',
            ]
        );

        // 3. Sampel Buku Stok 1 (Uji peminjaman atomik & lockForUpdate)
        $bukuSatu = Buku::updateOrCreate(
            ['isbn' => '978-602-0001-02'],
            [
                'judul'        => 'Arsitektur Basis Data ACID',
                'penulis'      => 'Chandro Pardede',
                'penerbit'     => 'Del Press',
                'tahun_terbit' => 2026,
                'kategori_id'  => $kategori->id,
                'stok'         => 1,
                'sinopsis'     => 'Buku sampel untuk menguji Pessimistic Locking.',
            ]
        );

        // 4. Sampel Buku untuk riwayat transaksi
        $bukuRiwayat = Buku::updateOrCreate(
            ['isbn' => '978-602-0001-03'],
            [
                'judul'        => 'Pemrograman Web dengan Laravel 11',
                'penulis'      => 'Del Press',
                'penerbit'     => 'Del Press',
                'tahun_terbit' => 2026,
                'kategori_id'  => $kategori->id,
                'stok'         => 4, // stok awal 5, 1 sedang dipinjam
                'sinopsis'     => 'Buku sampel untuk data peminjaman dan pengembalian.',
            ]
        );

        // 5. Sampel transaksi peminjaman
        // a) Sedang dipinjam (stok buku sudah dikurangi di atas)
        Peminjaman::updateOrCreate(
            ['kode_pinjam' => 'PJM-SEED-0001'],
            [
                'buku_id'             => $bukuRiwayat->id,
                'nim_peminjam'        => '12S24001',
                'nama_peminjam'       => 'Mahasiswa Uji Satu',
                'tanggal_pinjam'      => now()->subDays(3)->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays(4)->toDateString(),
                'tanggal_kembali'     => null,
                'status'              => 'dipinjam',
            ]
        );

        // b) Sudah dikembalikan
        Peminjaman::updateOrCreate(
            ['kode_pinjam' => 'PJM-SEED-0002'],
            [
                'buku_id'             => $bukuRiwayat->id,
                'nim_peminjam'        => '12S24002',
                'nama_peminjam'       => 'Mahasiswa Uji Dua',
                'tanggal_pinjam'      => now()->subDays(10)->toDateString(),
                'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
                'tanggal_kembali'     => now()->subDays(4)->toDateString(),
                'status'              => 'dikembalikan',
            ]
        );
    }
}