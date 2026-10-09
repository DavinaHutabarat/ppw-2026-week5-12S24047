<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index()
    {
        // withCount: jumlah buku per kategori dalam 1 kueri (tanpa N+1)
        $kategoris = Kategori::withCount('bukus')->latest()->paginate(10);
        return view('kategori.index', compact('kategoris'));
    }

    public function create()
    {
        return view('kategori.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_kategori' => 'required|string|max:10|unique:kategoris,kode_kategori',
            'nama_kategori' => 'required|string|min:3|max:100',
        ]);

        Kategori::create($validated);

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function show(Kategori $kategori)
    {
        $kategori->load('bukus');
        return view('kategori.show', compact('kategori'));
    }

    public function edit(Kategori $kategori)
    {
        return view('kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'kode_kategori' => 'required|string|max:10|unique:kategoris,kode_kategori,' . $kategori->id,
            'nama_kategori' => 'required|string|min:3|max:100',
        ]);

        $kategori->update($validated);

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil diperbarui!');
    }

    public function destroy(Kategori $kategori)
    {
        // Karena cascadeOnDelete, semua buku di kategori ini ikut terhapus
        $kategori->delete();

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori dan buku di dalamnya berhasil dihapus!');
    }
}