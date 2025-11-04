<style>
    .select2-container--default .select2-selection--single {
        min-height: 48px;
        font-size: 1.1rem;
        border-radius: 0.75rem;
        border-color: #f9a8d4;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 48px;
        padding-left: 16px;
    }

    .select2-dropdown {
        border-radius: 0.75rem;
        box-shadow: 0 4px 24px 0 #f9a8d455;
        font-size: 1.05rem;
    }

    .select2-results__option {
        padding: 12px 18px 12px 18px;
        transition: background 0.2s;
    }

    .select2-results__option--highlighted {
        background: #f9a8d4 !important;
        color: #b91c1c !important;
    }

    .prod-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .prod-icon {
        background: #f3f4f6;
        color: #b91c1c;
        border-radius: 10px;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 10px;
    }

    .prod-main {
        flex: 1 1 0%;
        min-width: 0;
    }

    .prod-name {
        font-weight: 600;
        font-size: 1.08em;
        color: #22223b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .prod-cat {
        color: #be185d;
        font-size: 0.98em;
        font-weight: 600;
        margin-top: 2px;
    }

    .prod-stock {
        font-size: 1em;
        font-weight: 600;
        color: #374151;
        background: #f3f4f6;
        border-radius: 8px;
        padding: 2px 10px;
        margin-left: 8px;
    }

    .prod-habis {
        font-size: 1em;
        font-weight: 600;
        color: #be185d;
        background: #fce7f3;
        border-radius: 8px;
        padding: 2px 10px;
        margin-left: 8px;
    }
</style>
<style>
    .select2-container--default .select2-selection--single {
        min-height: 48px;
        font-size: 1.1rem;
        border-radius: 0.75rem;
        border-color: #f9a8d4;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 48px;
        padding-left: 16px;
    }

    .select2-dropdown {
        border-radius: 0.75rem;
        box-shadow: 0 4px 24px 0 #f9a8d455;
        font-size: 1.05rem;
    }

    .select2-results__option {
        padding: 10px 16px 10px 16px;
        transition: background 0.2s;
    }

    .select2-results__option--highlighted {
        background: #f9a8d4 !important;
        color: #b91c1c !important;
    }

    .badge-kat {
        background: #fce7f3;
        color: #be185d;
        border-radius: 8px;
        padding: 2px 8px;
        font-size: 13px;
        margin-left: 8px;
        font-weight: 600;
        display: inline-block;
    }

    .badge-stok {
        background: #f3f4f6;
        color: #374151;
        border-radius: 8px;
        padding: 2px 8px;
        font-size: 13px;
        margin-left: 6px;
        font-weight: 600;
        display: inline-block;
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-red-700">
                <i class="bi bi-tools mr-2"></i>
                Input Stok Rusak
            </h1>
            <a href="{{ route('inventory.index') }}"
                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-xl transition-all duration-200">
                <i class="bi bi-arrow-left mr-1"></i>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8 gradient-bg min-h-screen">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="section-card p-8">
                <div class="mb-8 pb-6 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="bi bi-tools mr-2 text-red-500"></i>
                        Form Input Stok Rusak
                    </h3>
                    <p class="text-gray-500 text-sm mt-1">Catat stok produk yang rusak agar tidak tercampur stok siap
                        jual</p>
                </div>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6">
                        <div class="flex items-start">
                            <i class="bi bi-exclamation-triangle mr-2 mt-0.5"></i>
                            <div>
                                <h4 class="font-semibold mb-2">Terdapat kesalahan:</h4>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li class="text-sm">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('inventory.adjust.damaged') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-box mr-1 text-pink-500"></i>
                            Pilih Produk
                        </label>
                        <select id="product_id" name="product_id"
                            class="w-full border-pink-200 rounded-xl input-focus focus:outline-none bg-white" required>
                            <option value="" selected hidden>Pilih Produk</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}" data-category="{{ $prod->category->name ?? '-' }}"
                                    data-stock="{{ $prod->current_stock }}">
                                    {{ $prod->name }}
                                </option>
                            @endforeach
                        </select>
                        <!-- Select2 CSS -->
                        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
                            rel="stylesheet" />
                        <!-- Select2 JS -->
                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
                        <script>
                            function formatProduct(state) {
                                if (!state.id) {
                                    return state.text;
                                }
                                var category = $(state.element).data('category');
                                var stock = $(state.element).data('stock');
                                var icon = '<span class="prod-icon"><i class="bi bi-box"></i></span>';
                                var stockLabel = '';
                                if (typeof stock !== 'undefined' && stock <= 0) {
                                    stockLabel = '<span class="prod-habis">Habis</span>';
                                } else if (typeof stock !== 'undefined') {
                                    stockLabel = '<span class="prod-stock">Stok: ' + stock + '</span>';
                                }
                                return $(
                                    '<div class="prod-row">'
                                    + icon
                                    + '<div class="prod-main">'
                                    + '<div class="prod-name">' + state.text + '</div>'
                                    + (category ? '<div class="prod-cat">' + category + '</div>' : '')
                                    + '</div>'
                                    + stockLabel
                                    + '</div>'
                                );
                            }
                            $(document).ready(function () {
                                $('#product_id').select2({
                                    templateResult: formatProduct,
                                    templateSelection: formatProduct,
                                    width: '100%',
                                    dropdownAutoWidth: true,
                                    placeholder: '-- Pilih Produk --',
                                    allowClear: true
                                });
                            });
                        </script>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-123 mr-1 text-pink-500"></i>
                            Jumlah Stok Rusak
                        </label>
                        <input type="number" id="quantity" name="quantity" min="1" step="1"
                            class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none"
                            placeholder="Masukkan jumlah stok rusak" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-chat-dots mr-1 text-pink-500"></i>
                            Catatan Kerusakan
                        </label>
                        <select id="notes_select" name="notes_select"
                            class="w-full border-pink-200 rounded-xl input-focus focus:outline-none bg-white mb-2">
                            <option value="Rusak">Rusak</option>
                            <option value="Layu">Layu</option>
                            <option value="Patah">Patah</option>
                            <option value="Busuk">Busuk</option>
                            <option value="lainnya">Lainnya...</option>
                        </select>
                        <input type="text" id="notes" name="notes" style="display:none;"
                            class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none"
                            placeholder="Masukkan keterangan kerusakan lain...">
                    </div>
                    <script>
                        const notesSelect = document.getElementById('notes_select');
                        const notesInput = document.getElementById('notes');
                        notesSelect.addEventListener('change', function () {
                            if (this.value === 'lainnya') {
                                notesInput.style.display = 'block';
                                notesInput.name = 'notes';
                                notesInput.required = true;
                                notesInput.value = '';
                            } else {
                                notesInput.style.display = 'none';
                                notesInput.name = 'notes';
                                notesInput.required = false;
                                notesInput.value = this.value;
                            }
                        });
                        // Set default value on page load
                        window.addEventListener('DOMContentLoaded', function () {
                            notesInput.value = notesSelect.value;
                        });
                    </script>
                    <div class="flex items-center gap-4 pt-4">
                        <button type="submit"
                            class="bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-bold py-3 px-8 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="bi bi-tools mr-2"></i>
                            Catat Stok Rusak
                        </button>
                        <a href="{{ route('inventory.index') }}"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-xl transition-all duration-200">
                            <i class="bi bi-x-circle mr-2"></i>
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>