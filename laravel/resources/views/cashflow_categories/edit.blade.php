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
    </style>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-pink-100 rounded-xl mr-3">
                    <i class="bi bi-pencil-square text-pink-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Kategori Cash Flow</h1>
                    <p class="text-sm text-gray-500 mt-1">Ubah nama kategori pemasukan/pengeluaran kas</p>
                </div>
            </div>
            <a href="{{ route('cashflow-categories.index') }}"
                class="h-9 px-4 bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
                <i class="bi bi-arrow-left mr-1.5"></i>
                Kembali
            </a>
        </div>
    </x-slot>
    <div class="py-8 gradient-bg min-h-screen">
        <div class="max-w-xl mx-auto">
            <div class="section-card p-8">
                <form action="{{ route('cashflow-categories.update', $category->id) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Kategori</label>
                        <input type="text" name="name"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-sm"
                            value="{{ $category->name }}" required>
                    </div>
                    <div class="flex gap-2">
                        <button
                            class="h-9 px-4 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded-lg transition-all duration-200 flex items-center"
                            type="submit">
                            <i class="bi bi-save mr-1.5"></i> Update
                        </button>
                        <a href="{{ route('cashflow-categories.index') }}"
                            class="h-9 px-4 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center">
                            <i class="bi bi-x-circle mr-1.5"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>