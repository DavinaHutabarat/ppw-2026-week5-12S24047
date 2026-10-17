<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PeminjamanController extends Controller
{
    public function index(Request $request)
    {
        $peminjamans = Peminjaman::with('buku')
            ->when($request->search, function ($query, $search) {
                $query->where('kode_pinjam', 'like', "%{$search}%")
                      ->orWhere('nim_peminjam', 'like', "%{$search}%")
                      ->orWhere('nama_peminjam', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $bukus = Buku::all();

        return view('peminjaman.index', compact('peminjamans', 'bukus'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'buku_id' => 'required|exists:bukus,id',
            'nim_peminjam' => 'required|string|max:15',
            'nama_peminjam' => 'required|string|max:150',
            'tanggal_pinjam' => 'required|date',
            'tanggal_jatuh_tempo' => 'required|date|after_or_equal:tanggal_pinjam',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $buku = Buku::where('id', $validated['buku_id'])->lockForUpdate()->firstOrFail();

                if ($buku->stok < 1) {
                    throw new \RuntimeException("Stok buku '{$buku->judul}' habis!");
                }

                $sudahPinjam = Peminjaman::where('buku_id', $buku->id)
                    ->where('nim_peminjam', $validated['nim_peminjam'])
                    ->where('status', 'dipinjam')
                    ->lockForUpdate()
                    ->exists();

                if ($sudahPinjam) {
                    throw new \RuntimeException("Mahasiswa dengan NIM {$validated['nim_peminjam']} masih meminjam buku ini.");
                }

                $buku->decrement('stok');

                Peminjaman::create([
                    'kode_pinjam' => 'PINJ-' . strtoupper(Str::random(6)),
                    'buku_id' => $buku->id,
                    'nim_peminjam' => strtoupper(trim($validated['nim_peminjam'])),
                    'nama_peminjam' => $validated['nama_peminjam'],
                    'tanggal_pinjam' => $validated['tanggal_pinjam'],
                    'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                    'status' => 'dipinjam',
                ]);
            }, 3);

            session(['last_borrower_nim' => $validated['nim_peminjam']]);

            return back()->with('success', 'Transaksi peminjaman buku berhasil disimpan secara atomik!');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION SIRKULASI PINJAM]: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan basis data saat memproses peminjaman.');
        }
    }

    public function kembalikan(int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $peminjaman = Peminjaman::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($peminjaman->status === 'dikembalikan') {
                    throw new \RuntimeException('Buku ini sudah berstatus dikembalikan.');
                }

                $peminjaman->buku()->lockForUpdate()->firstOrFail()->increment('stok');

                $peminjaman->update([
                    'status' => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);
            }, 3);

            return back()->with('success', 'Buku berhasil dikembalikan dan stok diperbarui!');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION SIRKULASI KEMBALI]: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses pengembalian buku pada basis data.');
        }
    }
}