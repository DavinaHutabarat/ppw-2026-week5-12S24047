<x-layout title="Edit Buku">
    <h4 class="mb-3">Edit Buku</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('buku.update', $buku) }}" method="POST">
                @csrf
                @method('PUT')
                @include('buku._form')
                <button type="submit" class="btn btn-del">Perbarui</button>
                <a href="{{ route('buku.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</x-layout>