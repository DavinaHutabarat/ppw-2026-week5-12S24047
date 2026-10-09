<x-layout title="Detail Kategori">
    <h4 class="mb-3">Detail Kategori</h4>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <table class="table table-borderless mb-0">
                <tr><th style="width: 200px">Kode</th><td>{{ $kategori->kode_kategori }}</td></tr>
                <tr><th>Nama</th><td>{{ $kategori->nama_kategori }}</td></tr>
            </table>
        </div>
    </div>

    <h5>Buku dalam kategori ini</h5>
    <ul class="list-group mb-3">
        @forelse($kategori->bukus as $buku)
            <li class="list-group-item">
                <a href="{{ route('buku.show', $buku) }}">{{ $buku->judul }}</a>
                <span class="text-muted">— {{ $buku->penulis }}</span>
            </li>
        @empty
            <li class="list-group-item text-muted">Belum ada buku di kategori ini.</li>
        @endforelse
    </ul>

    <a href="{{ route('kategori.index') }}" class="btn btn-secondary">Kembali</a>
</x-layout>