<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Draft Custom Bouquet (Publik)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 font-sans">
    <div class="max-w-3xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-[#f2527d] mb-6 text-center">Daftar Draft Custom Bouquet</h1>
        @if($drafts->isEmpty())
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                Belum ada draft custom bouquet.
            </div>
        @else
            <div class="bg-white rounded-lg shadow p-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Harga</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Terakhir Diubah</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($drafts as $draft)
                            <tr>
                                <td class="px-4 py-2">#{{ $draft->id }}</td>
                                <td class="px-4 py-2">{{ $draft->name ?? 'Draft Custom Bouquet' }}</td>
                                <td class="px-4 py-2">Rp {{ number_format($draft->total_price, 0, ',', '.') }}</td>
                                <td class="px-4 py-2">{{ $draft->updated_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ url('/custom-bouquet/create?draft_id=' . $draft->id) }}"
                                        class="px-2 py-1 rounded-lg bg-pink-500 text-white font-semibold shadow hover:bg-pink-600 transition">Lanjutkan
                                        Custom</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</body>

</html>