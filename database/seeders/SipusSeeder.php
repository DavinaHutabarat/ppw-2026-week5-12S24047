<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kategori;
use App\Models\Buku;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SipusSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Pustakawan Uji
        User::updateOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            [
                'name'     => 'Pustakawan Del',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Kategori Sampel
        $kategori = Kategori::firstOrCreate(
            ['nama' => 'Teknologi Informasi'],
            ['deskripsi' => 'Koleksi buku teknologi dan informatika']
        );

        // 3. Sampel Buku Stok 0 (Uji validasi penolakan kuota/stok habis)
        Buku::updateOrCreate(
            ['isbn' => '978-602-0001-01'],
            [
                'judul'        => 'Pengujian Web Lanjut (Stok Habis)',
                'penulis'      => 'Chandro Pardede',
                'penerbit'     => 'Del Press',
                'tahun_terbit' => 2026,
                'kategori_id'  => $kategori->id,
                'stok'         => 0,
                'sinopsis'     => 'Buku sampel untuk menguji penolakan transaksi saat stok = 0.',
            ]
        );

        // 4. Sampel Buku Stok 1 (Uji peminjaman atomik & lockForUpdate)
        Buku::updateOrCreate(
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
    }
}