<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['TEK', 'Teknologi', [
                'Pemrograman Web dengan Laravel',
                'Dasar-Dasar Basis Data',
                'Algoritma dan Struktur Data',
                'Jaringan Komputer Modern',
                'Pengantar Kecerdasan Buatan',
            ]],
            ['SNS', 'Sains', [
                'Fisika Dasar untuk Perguruan Tinggi',
                'Kimia Organik Modern',
                'Biologi Molekuler',
                'Pengantar Astronomi',
                'Matematika Diskrit',
            ]],
            ['BIS', 'Bisnis', [
                'Manajemen Strategis',
                'Pengantar Akuntansi',
                'Kewirausahaan Digital',
                'Pemasaran Modern',
                'Manajemen Proyek Teknologi Informasi',
            ]],
            ['SAS', 'Sastra', [
                'Kumpulan Cerita Rakyat Nusantara',
                'Antologi Puisi Indonesia',
                'Teori Sastra Kontemporer',
                'Dongeng Batak Toba',
                'Bahasa dan Budaya Indonesia',
            ]],
            ['SEJ', 'Sejarah', [
                'Sejarah Indonesia Modern',
                'Peradaban Dunia Kuno',
                'Sejarah Sumatera Utara',
                'Perang Dunia dalam Perspektif Asia',
                'Dasar-Dasar Ilmu Sejarah',
            ]],
            ['SEN', 'Seni dan Desain', [
                'Desain Antarmuka Pengguna',
                'Dasar-Dasar Fotografi',
                'Seni Rupa Nusantara',
                'Sejarah Musik Dunia',
                'Tipografi dan Tata Letak',
            ]],
        ];

        foreach ($data as [$kode, $nama, $judulJudul]) {
            $kategori = Kategori::create([
                'kode_kategori' => $kode,
                'nama_kategori' => $nama,
            ]);

            foreach ($judulJudul as $judul) {
                Buku::factory()->for($kategori)->create(['judul' => $judul]);
            }
        }
    }
}