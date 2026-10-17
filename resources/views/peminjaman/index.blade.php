@extends('components.layout')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Sirkulasi Peminjaman Buku (ACID Transaction)</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPinjam">
            + Tambah Peminjaman
        </button>
    </div>

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

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode Pinjam</th>
                        <th>Buku</th>
                        <th>Peminjam</th>
                        <th>Tgl Pinjam / Tempo</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($peminjamans as $p)
                        <tr>
                            <td><strong>{{ $p->kode_pinjam }}</strong></td>
                            <td>{{ $p->buku->judul ?? '-' }}</td>
                            <td>
                                <div>{{ $p->nama_peminjam }}</div>
                                <small class="text-muted">{{ $p->nim_peminjam }}</small>
                            </td>
                            <td>
                                <div>Pinjam: {{ $p->tanggal_pinjam }}</div>
                                <small class="text-danger">Tempo: {{ $p->tanggal_jatuh_tempo }}</small>
                            </td>
                            <td>
                                @if ($p->status === 'dipinjam')
                                    <span class="badge bg-warning text-dark">Dipinjam</span>
                                @else
                                    <span class="badge bg-success">Dikembalikan</span>
                                    <br><small class="text-muted">{{ $p->tanggal_kembali }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($p->status === 'dipinjam')
                                    <form action="{{ route('peminjaman.kembali', $p->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-success">Kembalikan</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat peminjaman.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $peminjamans->links() }}
    </div>
</div>

{{-- Modal Pinjam Buku --}}
<div class="modal fade" id="modalPinjam" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('peminjaman.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Formulir Peminjaman Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pilih Buku (Stok Tersedia)</label>
                    <select name="buku_id" class="form-select" required>
                        <option value="">-- Pilih Buku --</option>
                        @foreach ($bukus as $buku)
                            <option value="{{ $buku->id }}">{{ $buku->judul }} (Sisa: {{ $buku->stok }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">NIM Mahasiswa</label>
                    <input type="text" name="nim_peminjam" class="form-control" value="{{ old('nim_peminjam', session('last_borrower_nim')) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Mahasiswa</label>
                    <input type="text" name="nama_peminjam" class="form-control" value="{{ old('nama_peminjam') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Pinjam</label>
                    <input type="date" name="tanggal_pinjam" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Jatuh Tempo</label>
                    <input type="date" name="tanggal_jatuh_tempo" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Transaksi (ACID)</button>
            </div>
        </form>
    </div>
</div>
@endsection