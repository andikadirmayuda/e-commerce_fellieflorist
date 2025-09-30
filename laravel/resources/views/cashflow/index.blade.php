@php \Carbon\Carbon::setLocale('id'); @endphp
<x-app-layout>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #fdf2f8 0%, #ffffff 50%, #f0fdf4 100%);
        }

        .section-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.1);
            transition: all 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .action-btn {
            @apply px-3 py-1 rounded-lg font-semibold text-sm transition-all duration-200;
        }
    </style>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-pink-100 rounded-xl mr-3">
                    <i class="bi bi-cash-coin text-pink-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Daftar Transaksi Cash Flow</h1>
                    <p class="text-sm text-gray-500 mt-1">Semua transaksi pemasukan & pengeluaran kas toko</p>
                </div>
            </div>
            <a href="{{ route('dashboard') }}"
                class="h-9 px-4 bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="bi bi-arrow-left mr-1.5"></i>
                Kembali
            </a>
        </div>
    </x-slot>
    <div class="py-8 gradient-bg min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
                <a href="{{ route('cashflow.create') }}"
                    class="h-10 px-5 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg shadow transition-all duration-200 flex items-center gap-2">
                    <i class="bi bi-plus-circle mr-1.5"></i> Tambah Transaksi
                </a>
                @if(session('success'))
                    <div class="bg-green-100 text-green-700 p-2 rounded shadow">{{ session('success') }}</div>
                @endif
            </div>
            <!-- Filter Form -->
            <form method="GET"
                class="flex flex-wrap items-end gap-3 bg-white p-3 rounded-xl shadow-sm border border-gray-100 mb-6">
                <div class="flex flex-wrap gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            <i class="bi bi-calendar3 mr-1 text-pink-500"></i>
                            Tanggal
                        </label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            <i class="bi bi-tags mr-1 text-pink-500"></i>
                            Kategori
                        </label>
                        <select name="category_id"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-sm">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="h-9 px-4 bg-pink-500 hover:bg-pink-600 text-white text-sm font-semibold rounded-lg transition-all duration-200 flex items-center">
                        <i class="bi bi-search mr-1.5"></i>
                        Filter
                    </button>
                    <a href="{{ route('cashflow.index') }}"
                        class="h-9 px-4 bg-white border border-gray-200 hover:border-pink-500 hover:bg-pink-50 text-gray-700 hover:text-pink-600 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center">
                        <i class="bi bi-arrow-counterclockwise mr-1.5"></i>
                        Reset
                    </a>
                </div>
            </form>
            <!-- Tabel Data Cashflow Dibuat oleh Owner -->
            <div class="section-card p-6 mb-8">
                <h2 class="text-lg font-bold mb-4 text-pink-600">Transaksi oleh Owner</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-pink-50">
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Tanggal</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Kategori</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Tipe</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Jumlah</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Metode</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Keterangan</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Dibuat Oleh</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // DEBUG: tampilkan user field pada setiap transaksi
                                // Sementara, tampilkan field user->role, user->status, user->name
                                $ownerCashFlows = $cashFlows->filter(function ($cf) {
                                    // Filter berdasarkan nama user owner
                                    return $cf->user && $cf->user->name === 'Owner Florist';
                                });
                            @endphp
                            @forelse($ownerCashFlows as $cf)
                                <tr class="border-b hover:bg-pink-50/50">
                                    <td class="px-4 py-2">{{ $cf->transaction_date }}</td>
                                    <td class="px-4 py-2">{{ $cf->category->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($cf->type) }}</td>
                                    <td class="px-4 py-2">Rp{{ number_format($cf->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($cf->payment_method) }}</td>
                                    <td class="px-4 py-2">{{ $cf->description }}</td>
                                    <td class="px-4 py-2">
                                        {{ $cf->user->name }}<br>
                                        {{-- <span class="text-xs text-gray-500">Status: {{ $cf->user->status ?? '-' }} |
                                            Role:
                                            {{ $cf->user->role ?? '-' }}</span><br> --}}
                                        <span class="text-xs text-gray-400">
                                            @if($cf->created_at)
                                                {{ \Carbon\Carbon::parse($cf->created_at)->translatedFormat('l, d F Y H:i:s') }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('cashflow.edit', $cf->id) }}"
                                            class="action-btn bg-blue-100 hover:bg-blue-200 text-blue-700 mr-2"><i
                                                class="bi bi-pencil-square"></i> Edit</a>
                                        <form action="{{ route('cashflow.destroy', $cf->id) }}" method="POST"
                                            class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="action-btn bg-red-100 hover:bg-red-200 text-red-700"
                                                onclick="return confirm('Yakin hapus?')"><i class="bi bi-trash"></i>
                                                Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-gray-400">Tidak ada data oleh owner</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Data Cashflow Dibuat oleh Selain Owner (Admin, Kasir, dll) -->
            <div class="section-card p-6 mb-8">
                <h2 class="text-lg font-bold mb-4 text-blue-600">Transaksi oleh Admin, Kasir, dll</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-blue-50">
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Tanggal</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Kategori</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Tipe</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Jumlah</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Metode</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Keterangan</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Dibuat Oleh</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $nonOwnerCashFlows = $cashFlows->filter(function ($cf) {
                                    // Selain user owner
                                    return $cf->user && $cf->user->name !== 'Owner Florist';
                                });
                            @endphp
                            @forelse($nonOwnerCashFlows as $cf)
                                <tr class="border-b hover:bg-blue-50/50">
                                    <td class="px-4 py-2">{{ $cf->transaction_date }}</td>
                                    <td class="px-4 py-2">{{ $cf->category->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($cf->type) }}</td>
                                    <td class="px-4 py-2">Rp{{ number_format($cf->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($cf->payment_method) }}</td>
                                    <td class="px-4 py-2">{{ $cf->description }}</td>
                                    <td class="px-4 py-2">
                                        {{ $cf->user->name }}<br>
                                        {{-- <span class="text-xs text-gray-500">Status: {{ $cf->user->status ?? '-' }} |
                                            Role:
                                            {{ $cf->user->role ?? '-' }}</span><br> --}}
                                        <span class="text-xs text-gray-400">
                                            @if($cf->created_at)
                                                {{ \Carbon\Carbon::parse($cf->created_at)->translatedFormat('l, d F Y H:i:s') }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('cashflow.edit', $cf->id) }}"
                                            class="action-btn bg-blue-100 hover:bg-blue-200 text-blue-700 mr-2"><i
                                                class="bi bi-pencil-square"></i> Edit</a>
                                        <form action="{{ route('cashflow.destroy', $cf->id) }}" method="POST"
                                            class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="action-btn bg-red-100 hover:bg-red-200 text-red-700"
                                                onclick="return confirm('Yakin hapus?')"><i class="bi bi-trash"></i>
                                                Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-gray-400">Tidak ada data oleh
                                        admin/kasir/dll</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $cashFlows->links() }}</div>
        </div>
    </div>
</x-app-layout>