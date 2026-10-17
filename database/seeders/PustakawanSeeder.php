<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PustakawanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Pustakawan Uji Login
        User::updateOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            [
                'name' => 'Pustakawan Del',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Kategori Buku
        $kategori = Kategori::firstOrCreate(
            ['kode_kategori' => 'KT-TI'],
            [
                'nama_kategori' => 'Teknologi Informasi'
            ]
        );

        // 3. Sampel Buku stok = 0 (untuk uji tolak transaksi ACID)
        Buku::firstOrCreate(
            ['isbn' => '978-602-0001'],
            [
                'kategori_id'  => $kategori->id,
                'judul'        => 'Sistem Terdistribusi Lanjutan',
                'penulis'      => 'Staf Pengajar TI',
                'penerbit'     => 'Del Press',
                'tahun_terbit' => 2024,
                'stok'         => 0,
            ]
        );

        // 4. Sampel Buku stok = 1 (untuk uji race condition & locking)
        Buku::firstOrCreate(
            ['isbn' => '978-602-0002'],
            [
                'kategori_id'  => $kategori->id,
                'judul'        => 'Basis Data Berbasis ACID',
                'penulis'      => 'Chandro Pardede',
                'penerbit'     => 'Informatika Press',
                'tahun_terbit' => 2025,
                'stok'         => 1,
            ]
        );
    }
}