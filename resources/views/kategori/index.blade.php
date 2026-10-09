<x-layout title="Daftar Kategori">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Daftar Kategori</h4>
        <a href="{{ route('kategori.create') }}" class="btn btn-del">+ Tambah Kategori</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered table-del bg-white align-middle">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kategori</th>
                    <th>Jumlah Buku</th>
                    <th style="width: 220px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kategoris as $kategori)
                    <tr>
                        <td>{{ $kategori->kode_kategori }}</td>
                        <td>{{ $kategori->nama_kategori }}</td>
                        <td>{{ $kategori->bukus_count }}</td>
                        <td>
                            <a href="{{ route('kategori.show', $kategori) }}" class="btn btn-sm btn-info">Detail</a>
                            <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('kategori.destroy', $kategori) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus kategori ini beserta semua bukunya?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Belum ada data kategori.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $kategoris->links() }}
</x-layout>