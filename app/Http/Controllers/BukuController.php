<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

public function index(Request $request)
{
    // Filter dinamis dan withCount relasi peminjamans
    $bukus = Buku::with('kategori')
        ->withCount('peminjamans')
        ->when($request->search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        })
        ->when($request->kategori_id, fn($q, $katId) => $q->where('kategori_id', $katId))
        ->latest()
        ->paginate(10)
        ->withQueryString();

    // Statistik agregasi ringkas via Fluent Query Builder (DB::table)
    $stats = [
        'total_judul'           => DB::table('bukus')->count(),
        'total_stok_tersedia'   => DB::table('bukus')->sum('stok'),
        'total_sedang_dipinjam' => DB::table('peminjamans')->where('status', 'dipinjam')->count(),
        'total_dikembalikan'    => DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
    ];

    $kategoris = Kategori::all();

    return view('buku.index', compact('bukus', 'stats', 'kategoris'));
}