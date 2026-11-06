<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BukuApiController extends Controller
{
    use ApiResponse;

    /**
     * Whitelist kolom untuk pengurutan data buku.
     */
    protected array $sortable = [
        'id',
        'isbn',
        'judul',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'stok',
        'created_at',
    ];

    /**
     * Menampilkan daftar katalog buku terpaginasi (publik, bebas N+1).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 50)); // Batas maksimum 50

        $query = Buku::query()
            ->with('kategori')
            ->withCount('peminjamans');

        // Filter: search (judul / penulis / isbn)
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where(function ($sub) use ($search) {
                $sub->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        });

        // Filter: kategori_id
        $query->when($request->filled('kategori_id'), function ($q) use ($request) {
            $q->where('kategori_id', $request->input('kategori_id'));
        });

        // Filter: tersedia (stok > 0)
        $query->when($request->boolean('tersedia'), function ($q) {
            $q->where('stok', '>', 0);
        });

        // Sorting: whitelist
        $sortParam = $request->input('sort', '-created_at');
        $direction = str_starts_with($sortParam, '-') ? 'desc' : 'asc';
        $column = lstrip_prefix($sortParam, '-');

        if (! in_array($column, $this->sortable, true)) {
            $column = 'created_at';
            $direction = 'desc';
        }

        $bukus = $query->orderBy($column, $direction)->paginate($perPage)->withQueryString();

        return BukuResource::collection($bukus);
    }

    /**
     * Menampilkan detail satu buku beserta relasi kategori dan riwayat peminjaman (publik).
     */
    public function show(int|string $id): JsonResponse|BukuResource
    {
        $buku = Buku::with(['kategori', 'peminjamans'])->find($id);

        if (! $buku) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'Not Found',
                404,
                "Resource buku dengan ID {$id} tidak ditemukan."
            );
        }

        return (new BukuResource($buku))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Menyimpan data buku baru (pustakawan terautentikasi).
     */
    public function store(Request $request): JsonResponse
    {
        $currentYear = (int) date('Y');

        $validated = $request->validate([
            'isbn'         => 'required|string|max:20|unique:bukus,isbn',
            'judul'        => 'required|string|max:200',
            'penulis'      => 'required|string|max:150',
            'penerbit'     => 'required|string|max:150',
            'tahun_terbit' => "required|integer|min:1000|max:{$currentYear}",
            'kategori_id'  => 'required|integer|exists:kategoris,id',
            'stok'         => 'required|integer|min:0',
            'sinopsis'     => 'nullable|string',
        ]);

        $buku = Buku::create($validated);
        $buku->load('kategori');

        $location = url("/api/v1/bukus/{$buku->id}");

        return (new BukuResource($buku))
            ->response()
            ->header('Location', $location)
            ->setStatusCode(201);
    }

    /**
     * Memperbarui seluruh atribut buku (PUT).
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'Not Found',
                404,
                "Resource buku dengan ID {$id} tidak ditemukan."
            );
        }

        $currentYear = (int) date('Y');

        $validated = $request->validate([
            'isbn'         => "required|string|max:20|unique:bukus,isbn,{$buku->id}",
            'judul'        => 'required|string|max:200',
            'penulis'      => 'required|string|max:150',
            'penerbit'     => 'required|string|max:150',
            'tahun_terbit' => "required|integer|min:1000|max:{$currentYear}",
            'kategori_id'  => 'required|integer|exists:kategoris,id',
            'stok'         => 'required|integer|min:0',
            'sinopsis'     => 'nullable|string',
        ]);

        // Pada PUT, atribut nullable yang tidak dikirimkan di-reset menjadi null
        if (! array_key_exists('sinopsis', $validated)) {
            $validated['sinopsis'] = null;
        }

        $buku->update($validated);
        $buku->load('kategori');

        return (new BukuResource($buku))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Memperbarui sebagian atribut buku (PATCH).
     */
    public function patch(Request $request, int|string $id): JsonResponse
    {
        $payload = $request->all();

        // Tolak payload kosong dengan 400 Bad Request
        if (empty($payload)) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7231#section-6.5.1',
                'Bad Request',
                400,
                'Payload untuk operasi PATCH tidak boleh kosong.'
            );
        }

        $buku = Buku::find($id);

        if (! $buku) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'Not Found',
                404,
                "Resource buku dengan ID {$id} tidak ditemukan."
            );
        }

        $currentYear = (int) date('Y');

        $validated = $request->validate([
            'isbn'         => "sometimes|required|string|max:20|unique:bukus,isbn,{$buku->id}",
            'judul'        => 'sometimes|required|string|max:200',
            'penulis'      => 'sometimes|required|string|max:150',
            'penerbit'     => 'sometimes|required|string|max:150',
            'tahun_terbit' => "sometimes|required|integer|min:1000|max:{$currentYear}",
            'kategori_id'  => 'sometimes|required|integer|exists:kategoris,id',
            'stok'         => 'sometimes|required|integer|min:0',
            'sinopsis'     => 'sometimes|nullable|string',
        ]);

        $buku->update($validated);
        $buku->load('kategori');

        return (new BukuResource($buku))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Menghapus buku (DELETE).
     */
    public function destroy(int|string $id): Response|JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'Not Found',
                404,
                "Resource buku dengan ID {$id} tidak ditemukan."
            );
        }

        // Tolak jika ada transaksi peminjaman berstatus 'dipinjam'
        $hasActiveLoans = $buku->peminjamans()->where('status', 'dipinjam')->exists();

        if ($hasActiveLoans) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7231#section-6.5.8',
                'Conflict',
                409,
                "Buku '{$buku->judul}' tidak dapat dihapus karena masih memiliki transaksi peminjaman aktif."
            );
        }

        $buku->delete();

        return $this->noContentResponse();
    }
}

/**
 * Helper function untuk menghapus prefix string jika ada.
 */
function lstrip_prefix(string $string, string $prefix): string
{
    if (str_starts_with($string, $prefix)) {
        return substr($string, strlen($prefix));
    }
    return $string;
}
