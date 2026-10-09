<div class="mb-3">
    <label for="kode_kategori" class="form-label">Kode Kategori</label>
    <input type="text" name="kode_kategori" id="kode_kategori"
           class="form-control @error('kode_kategori') is-invalid @enderror"
           value="{{ old('kode_kategori', $kategori->kode_kategori ?? '') }}">
    @error('kode_kategori') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="nama_kategori" class="form-label">Nama Kategori</label>
    <input type="text" name="nama_kategori" id="nama_kategori"
           class="form-control @error('nama_kategori') is-invalid @enderror"
           value="{{ old('nama_kategori', $kategori->nama_kategori ?? '') }}">
    @error('nama_kategori') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>  