<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BukuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $links = [
            'self' => [
                'href'   => url("/api/v1/bukus/{$this->id}"),
                'method' => 'GET',
            ],
            'referensi_eksternal' => [
                'href'   => url("/api/v1/bukus/{$this->id}/referensi-eksternal"),
                'method' => 'GET',
            ],
        ];

        if ($this->kategori_id) {
            $links['kategori'] = [
                'href'   => url("/api/v1/bukus?kategori_id={$this->kategori_id}"),
                'method' => 'GET',
            ];
        }

        // HATEOAS: _links.pinjam_buku HANYA muncul jika stok > 0
        if ($this->stok > 0) {
            $links['pinjam_buku'] = [
                'href'        => url('/api/v1/peminjamans'),
                'method'      => 'POST',
                'description' => 'Membuat transaksi peminjaman untuk buku ini',
            ];
        }

        return [
            'id'                 => $this->id,
            'isbn'               => $this->isbn,
            'judul'              => $this->judul,
            'penulis'            => $this->penulis,
            'penerbit'           => $this->penerbit,
            'tahun_terbit'       => (int) $this->tahun_terbit,
            'stok'               => (int) $this->stok,
            'sinopsis'           => $this->sinopsis,
            'kategori_id'        => $this->kategori_id,
            'kategori'           => $this->whenLoaded('kategori', function () {
                return [
                    'id'            => $this->kategori->id,
                    'kode_kategori' => $this->kategori->kode_kategori,
                    'nama_kategori' => $this->kategori->nama_kategori,
                ];
            }),
            'total_peminjaman'   => $this->whenCounted('peminjamans'),
            'riwayat_peminjaman' => $this->whenLoaded('peminjamans', function () {
                return $this->peminjamans->map(function ($peminjaman) {
                    return [
                        'id'                  => $peminjaman->id,
                        'kode_pinjam'         => $peminjaman->kode_pinjam,
                        'nim_peminjam'        => $peminjaman->nim_peminjam,
                        'nama_peminjam'       => $peminjaman->nama_peminjam,
                        'tanggal_pinjam'      => $peminjaman->tanggal_pinjam,
                        'tanggal_jatuh_tempo' => $peminjaman->tanggal_jatuh_tempo,
                        'tanggal_kembali'     => $peminjaman->tanggal_kembali,
                        'status'              => $peminjaman->status,
                    ];
                });
            }),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
            '_links'             => $links,
        ];
    }
}
