@extends('components.layout')

@section('content')
<div class="container py-4">
    {{-- Notifikasi Flash Session --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Kartu Statistik Query Builder --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary text-white shadow-sm p-3">
                <span class="fs-6">Total Judul Buku</span>
                <h3 class="fw-bold mb-0">{{ $stats['total_judul'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success text-white shadow-sm p-3">
                <span class="fs-6">Total Stok Fisik</span>
                <h3 class="fw-bold mb-0">{{ $stats['total_stok'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-warning text-dark shadow-sm p-3">
                <span class="fs-6">Buku Sedang Dipinjam</span>
                <h3 class="fw-bold mb-0">{{ $stats['total_dipinjam'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info text-white shadow-sm p-3">
                <span class="fs-6">Total Dikembalikan</span>
                <h3 class="fw-bold mb-0">{{ $stats['total_dikembalikan'] }}</h3>
            </div>
        </div>
    </div>

    {{-- Filter Pencarian Dinamis --}}
    <form method="GET" action="{{ route('buku.index') }}" class="row g-2 mb-4">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Cari judul, penulis, atau ISBN..." value="{{ request('search') }}">
        </div>
        <div class="col-md-4">
            <select name="kategori_id" class="form-select">
                <option value="">Semua Kategori</option>
                @foreach ($kategoris as $kategori)
                    <option value="{{ $kategori->id }}" {{ request('kategori_id') == $kategori->id ? 'selected' : '' }}>
                        {{ $kategori->nama_kategori }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark w-100">Cari</button>
            <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    {{-- Tabel Katalog Buku --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ISBN</th>
                        <th>Judul Buku</th>
                        <th>Kategori</th>
                        <th>Penulis / Penerbit</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center">Dipinjam (x)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukus as $buku)
                        <tr>
                            <td><code>{{ $buku->isbn }}</code></td>
                            <td class="fw-semibold">{{ $buku->judul }}</td>
                            <td><span class="badge bg-secondary">{{ $buku->kategori->nama_kategori ?? '-' }}</span></td>
                            <td>{{ $buku->penulis }} ({{ $buku->penerbit ?? '-' }})</td>
                            <td class="text-center">
                                @if ($buku->stok > 0)
                                    <span class="badge bg-success">{{ $buku->stok }}</span>
                                @else
                                    <span class="badge bg-danger">Habis (0)</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill">{{ $buku->peminjamans_count }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada data buku ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $bukus->links() }}
    </div>
</div>
@endsection