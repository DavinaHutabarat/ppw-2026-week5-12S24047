<x-layout title="Daftar Buku">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Daftar Buku</h4>
        <a href="{{ route('buku.create') }}" class="btn btn-del">+ Tambah Buku</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered table-del bg-white align-middle">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Penulis</th>
                    <th>Tahun</th>
                    <th>Stok</th>
                    <th style="width: 220px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bukus as $buku)
                    <tr>
                        <td>{{ $buku->isbn }}</td>
                        <td>{{ $buku->judul }}</td>
                        <td>{{ $buku->kategori->nama_kategori }}</td>
                        <td>{{ $buku->penulis }}</td>
                        <td>{{ $buku->tahun_terbit }}</td>
                        <td>{{ $buku->stok }}</td>
                        <td>
                            <a href="{{ route('buku.show', $buku) }}" class="btn btn-sm btn-info">Detail</a>
                            <a href="{{ route('buku.edit', $buku) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Yakin hapus buku ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada data buku.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $bukus->links() }}
</x-layout>