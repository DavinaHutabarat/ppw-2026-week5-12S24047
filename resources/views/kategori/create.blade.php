<x-layout title="Tambah Kategori">
    <h4 class="mb-3">Tambah Kategori</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('kategori.store') }}" method="POST">
                @csrf
                @include('kategori._form')
                <button type="submit" class="btn btn-del">Simpan</button>
                <a href="{{ route('kategori.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</x-layout>