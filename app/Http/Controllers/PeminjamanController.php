<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use RuntimeException;

class PeminjamanController extends Controller
{
    public function index()
    {
        $peminjamans = Peminjaman::with('buku')->latest()->paginate(10);
        return view('peminjaman.index', compact('peminjamans'));
    }

    public function store(Request $request, int $bukuId)
    {
        $validated = $request->validate([
            'nim_peminjam'  => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
        ]);

        try {
            // Transaksi basis data ACID
            DB::transaction(function () use ($validated, $bukuId) {
                // Pessimistic Locking: SELECT ... FOR UPDATE
                $buku = Buku::where('id', $bukuId)->lockForUpdate()->firstOrFail();

                // 1. Validasi stok > 0
                if ($buku->stok < 1) {
                    throw new RuntimeException("Stok buku '{$buku->judul}' sedang kosong!");
                }

                // 2. Cegah NIM yang sama meminjam buku yang sama jika belum dikembalikan
                $sedangMeminjam = Peminjaman::where('buku_id', $bukuId)
                    ->where('nim_peminjam', strtoupper(trim($validated['nim_peminjam'])))
                    ->where('status', 'dipinjam')
                    ->exists();

                if ($sedangMeminjam) {
                    throw new RuntimeException("Mahasiswa dengan NIM ini masih meminjam buku tersebut!");
                }

                // 3. Kurangi stok dan simpan transaksi
                $buku->decrement('stok');

                Peminjaman::create([
                    'kode_pinjam'         => 'PJM-' . strtoupper(Str::random(6)),
                    'buku_id'             => $bukuId,
                    'nim_peminjam'        => strtoupper(trim($validated['nim_peminjam'])),
                    'nama_peminjam'       => $validated['nama_peminjam'],
                    'tanggal_pinjam'      => now(),
                    'tanggal_jatuh_tempo' => now()->addDays(7),
                    'status'              => 'dipinjam',
                ]);
            }, 3);

            // Simpan NIM peminjam terakhir ke session state
            session(['last_borrower_nim' => $validated['nim_peminjam']]);

            return back()->with('success', 'Transaksi peminjaman berhasil diproses.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION PEMINJAMAN]: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan basis data.');
        }
    }

    public function returnBook(int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                // Kunci baris peminjaman
                $peminjaman = Peminjaman::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($peminjaman->status === 'dikembalikan') {
                    throw new RuntimeException("Buku ini telah dikembalikan sebelumnya.");
                }

                // Kunci baris buku untuk update stok
                $buku = Buku::where('id', $peminjaman->buku_id)->lockForUpdate()->firstOrFail();

                $peminjaman->update([
                    'status'          => 'dikembalikan',
                    'tanggal_kembali' => now(),
                ]);

                $buku->increment('stok');
            }, 3);

            return back()->with('success', 'Buku berhasil dikembalikan dan stok diperbarui.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION PENGEMBALIAN]: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses pengembalian buku.');
        }
    }
}