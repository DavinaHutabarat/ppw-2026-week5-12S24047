<x-layout title="Detail Buku">
    <h4 class="mb-3">Detail Buku</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-borderless mb-0">
                <tr><th style="width: 200px">ISBN</th><td>{{ $buku->isbn }}</td></tr>
                <tr><th>Judul</th><td>{{ $buku->judul }}</td></tr>
                <tr><th>Kategori</th><td>{{ $buku->kategori->nama_kategori }}</td></tr>
                <tr><th>Penulis</th><td>{{ $buku->penulis }}</td></tr>
                <tr><th>Penerbit</th><td>{{ $buku->penerbit }}</td></tr>
                <tr><th>Tahun Terbit</th><td>{{ $buku->tahun_terbit }}</td></tr>
                <tr><th>Stok</th><td>{{ $buku->stok }}</td></tr>
                <tr><th>Sinopsis</th><td>{{ $buku->sinopsis ?: '-' }}</td></tr>
            </table>
        </div>
    </div>

    <a href="{{ route('buku.index') }}" class="btn btn-secondary mt-3">Kembali</a>
</x-layout>