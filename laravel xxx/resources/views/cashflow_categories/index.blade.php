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
                    <i class="bi bi-tags text-pink-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Kategori Cash Flow</h1>
                    <p class="text-sm text-gray-500 mt-1">Daftar kategori pemasukan & pengeluaran kas toko</p>
                </div>
            </div>
            <a href="{{ route('cashflow-categories.create') }}"
                class="h-9 px-4 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="bi bi-plus-circle mr-1.5"></i>
                Tambah Kategori
            </a>
        </div>
    </x-slot>
    <div class="py-8 gradient-bg min-h-screen">
        <div class="max-w-3xl mx-auto">
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-2 rounded shadow mb-4">{{ session('success') }}</div>
            @endif
            <div class="section-card p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-pink-50">
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Nama</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr class="border-b hover:bg-pink-50/50">
                                    <td class="px-4 py-2 text-center">{{ $cat->name }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <a href="{{ route('cashflow-categories.edit', $cat->id) }}"
                                            class="action-btn bg-blue-100 hover:bg-blue-200 text-blue-700 mr-2"><i
                                                class="bi bi-pencil-square"></i> Edit</a>
                                        <form action="{{ route('cashflow-categories.destroy', $cat->id) }}" method="POST"
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
                                    <td colspan="2" class="text-center py-4 text-gray-400">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>