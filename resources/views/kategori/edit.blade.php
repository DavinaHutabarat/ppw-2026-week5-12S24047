<x-layout title="Edit Kategori">
    <h4 class="mb-3">Edit Kategori</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('kategori.update', $kategori) }}" method="POST">
                @csrf
                @method('PUT')
                @include('kategori._form')
                <button type="submit" class="btn btn-del">Perbarui</button>
                <a href="{{ route('kategori.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</x-layout>