<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sirkulasi Peminjaman - SIPUS-Del</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Daftar Sirkulasi Peminjaman</h1>
            <a href="{{ route('buku.index') }}" class="text-blue-600 hover:underline">&larr; Kembali ke Katalog Buku</a>
        </div>

        @if(session('success'))
            <div class="p-4 mb-4 text-green-800 bg-green-200 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 mb-4 text-red-800 bg-red-200 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-3">Kode Pinjam</th>
                        <th class="p-3">Buku</th>
                        <th class="p-3">Peminjam</th>
                        <th class="p-3">Tgl Pinjam</th>
                        <th class="p-3">Jatuh Tempo</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($peminjamans as $p)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3 font-semibold">{{ $p->kode_pinjam }}</td>
                        <td class="p-3">{{ $p->buku->judul ?? '-' }}</td>
                        <td class="p-3">{{ $p->nama_peminjam }} ({{ $p->nim_peminjam }})</td>
                        <td class="p-3">{{ $p->tanggal_pinjam }}</td>
                        <td class="p-3">{{ $p->tanggal_jatuh_tempo }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded {{ $p->status === 'dipinjam' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                {{ ucfirst($p->status) }}
                            </span>
                        </td>
                        <td class="p-3">
                            @if($p->status === 'dipinjam')
                            <form action="{{ route('peminjaman.return', $p->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">Kembalikan</button>
                            </form>
                            @else
                            <span class="text-xs text-gray-400">Selesai ({{ $p->tanggal_kembali }})</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500">Belum ada transaksi peminjaman.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $peminjamans->links() }}
        </div>
    </div>
</body>
</html>