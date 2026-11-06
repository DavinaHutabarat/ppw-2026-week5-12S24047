<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeminjamanApiController extends Controller
{
    use ApiResponse;

    /**
     * Menampilkan daftar transaksi peminjaman terpaginasi (pustakawan terautentikasi).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $query = Peminjaman::query()->with('buku');

        // Filter: status (dipinjam / dikembalikan)
        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->input('status'));
        });

        // Filter: nim
        $query->when($request->filled('nim'), function ($q) use ($request) {
            $q->where('nim_peminjam', $request->input('nim'));
        });

        $peminjamans = $query->latest()->paginate($perPage)->withQueryString();

        return PeminjamanResource::collection($peminjamans);
    }

    /**
     * Menampilkan detail satu transaksi peminjaman.
     */
    public function show(int|string $id): JsonResponse|PeminjamanResource
    {
        $peminjaman = Peminjaman::with('buku')->find($id);

        if (! $peminjaman) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'Not Found',
                404,
                "Resource transaksi peminjaman dengan ID {$id} tidak ditemukan."
            );
        }

        return (new PeminjamanResource($peminjaman))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Membuat transaksi peminjaman baru secara atomik dengan pessimistic locking (auth).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buku_id'       => 'required|integer|exists:bukus,id',
            'nim_peminjam'  => 'required|string|max:15',
            'nama_peminjam' => 'required|string|max:150',
            'durasi_hari'   => 'required|integer|min:1|max:30',
        ]);

        $nim = strtoupper(trim($validated['nim_peminjam']));

        $peminjaman = DB::transaction(function () use ($validated, $nim) {
            /** @var Buku $buku */
            $buku = Buku::where('id', $validated['buku_id'])->lockForUpdate()->firstOrFail();

            // 1. Cek stok buku
            if ($buku->stok < 1) {
                abort(422, 'Stok buku sedang habis.');
            }

            // 2. Cek apakah peminjam masih meminjam buku yang sama dan belum dikembalikan
            $sudahPinjam = Peminjaman::where('buku_id', $buku->id)
                ->where('nim_peminjam', $nim)
                ->where('status', 'dipinjam')
                ->lockForUpdate()
                ->exists();

            if ($sudahPinjam) {
                abort(409, "Mahasiswa dengan NIM {$nim} masih meminjam buku '{$buku->judul}' dan belum mengembalikannya.");
            }

            // 3. Kurangi stok buku
            $buku->decrement('stok');

            // 4. Hitung tanggal jatuh tempo berdasarkan durasi_hari
            $tanggalPinjam = now()->toDateString();
            $tanggalJatuhTempo = now()->addDays((int) $validated['durasi_hari'])->toDateString();

            // 5. Simpan transaksi peminjaman
            return Peminjaman::create([
                'kode_pinjam'         => 'PINJ-' . strtoupper(Str::random(6)),
                'buku_id'             => $buku->id,
                'nim_peminjam'        => $nim,
                'nama_peminjam'       => $validated['nama_peminjam'],
                'tanggal_pinjam'      => $tanggalPinjam,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'status'              => 'dipinjam',
            ]);
        }, 3);

        $peminjaman->load('buku');
        $location = url("/api/v1/peminjamans/{$peminjaman->id}");

        return (new PeminjamanResource($peminjaman))
            ->response()
            ->header('Location', $location)
            ->setStatusCode(201);
    }

    /**
     * Mengembalikan buku secara atomik dengan pessimistic locking (auth).
     */
    public function kembalikan(int|string $id): JsonResponse
    {
        $peminjaman = Peminjaman::find($id);

        if (! $peminjaman) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'Not Found',
                404,
                "Resource transaksi peminjaman dengan ID {$id} tidak ditemukan."
            );
        }

        $peminjaman = DB::transaction(function () use ($id) {
            /** @var Peminjaman $trx */
            $trx = Peminjaman::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($trx->status === 'dikembalikan') {
                abort(409, 'Buku pada transaksi ini sudah berstatus dikembalikan sebelumnya.');
            }

            // Tambahkan stok buku kembali
            $trx->buku()->lockForUpdate()->firstOrFail()->increment('stok');

            // Perbarui status dan tanggal pengembalian
            $trx->update([
                'status'          => 'dikembalikan',
                'tanggal_kembali' => now()->toDateString(),
            ]);

            return $trx;
        }, 3);

        $peminjaman->load('buku');

        return (new PeminjamanResource($peminjaman))
            ->response()
            ->setStatusCode(200);
    }
}
