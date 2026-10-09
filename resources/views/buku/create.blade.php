<x-layout title="Tambah Buku">
    <h4 class="mb-3">Tambah Buku</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('buku.store') }}" method="POST">
                @csrf
                @include('buku._form')
                <button type="submit" class="btn btn-del">Simpan</button>
                <a href="{{ route('buku.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</x-layout>