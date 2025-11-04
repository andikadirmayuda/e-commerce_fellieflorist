<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-pink-700">
                <i class="bi bi-cart-plus mr-2"></i>
                Buat Purchase Order
            </h1>
            <a href="{{ route('purchase-orders.index') }}"
                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-xl transition-all duration-200">
                <i class="bi bi-arrow-left mr-1"></i>
                Kembali
            </a>
        </div>
    </x-slot>

    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #fdf2f8 0%, #ffffff 50%, #f0fdf4 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .input-focus {
            transition: all 0.3s ease;
        }

        .input-focus:focus {
            ring: 2px;
            ring-color: rgba(244, 63, 94, 0.5);
            border-color: rgb(244, 63, 94);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

    <div class="py-8 gradient-bg min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-pink-500 to-rose-600 rounded-full mb-4 shadow-lg">
                    <i class="bi bi-cart-plus text-2xl text-white"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">
                    Buat <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-rose-600">Purchase
                        Order</span>
                </h2>
                <p class="text-gray-600">Lengkapi data purchase order dengan detail dan benar.</p>
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

            <form action="{{ route('purchase-orders.store') }}" method="POST" class="space-y-8">
                @csrf
                <!-- Informasi Customer -->
                <div class="section-card p-6">
                    <div class="mb-6 pb-4 border-b border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="bi bi-person-circle mr-2 text-pink-500"></i>
                            Informasi Customer
                        </h3>
                        <p class="text-gray-500 text-sm mt-1">Data customer pemesan</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Nama Customer
                            </label>
                            <input type="text" name="customer_name"
                                class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Nomor HP
                            </label>
                            <input type="text" name="customer_phone"
                                class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Tanggal Ambil
                            </label>
                            <input type="date" name="pickup_date"
                                class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Catatan
                            </label>
                            <textarea name="notes"
                                class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Produk -->
                <div class="section-card p-6">
                    <div class="mb-6 pb-4 border-b border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="bi bi-box-seam mr-2 text-pink-500"></i>
                            Produk
                        </h3>
                        <p class="text-gray-500 text-sm mt-1">Daftar produk yang dipesan</p>
                    </div>
                    <div id="product-items">
                        <div class="flex gap-2 mb-2">
                            <select name="items[0][product_id]"
                                class="form-select w-64 product-select input-focus focus:outline-none rounded-xl border border-pink-200"
                                required>
                                <option value="">-- Pilih Produk --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} (Stok: {{ $product->current_stock }} {{ $product->base_unit }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="items[0][quantity]"
                                class="form-input w-24 input-focus focus:outline-none rounded-xl border border-pink-200"
                                min="1" value="1" required placeholder="Jumlah">
                            <select name="items[0][unit]"
                                class="form-select w-24 input-focus focus:outline-none rounded-xl border border-pink-200"
                                required>
                                <option value="">Satuan</option>
                                <option value="tangkai">Tangkai</option>
                                <option value="item">Item</option>
                                <option value="ikat">Ikat</option>
                                <option value="paket">Paket</option>
                            </select>
                            <input type="text" name="items[0][item_notes]"
                                class="form-input w-32 input-focus focus:outline-none rounded-xl border border-pink-200"
                                placeholder="Catatan produk (opsional)">
                            <button type="button" onclick="addProductRow()"
                                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-xl flex items-center gap-1 shadow"
                                title="Tambah produk">
                                <i class="bi bi-plus-lg"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex justify-end space-x-4 pt-6">
                    <a href="{{ route('purchase-orders.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="bi bi-x-circle mr-2"></i>
                        Batal
                    </a>
                    <button type="submit"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="bi bi-check-circle mr-2"></i>
                        Simpan
                    </button>
                </div>
            </form>

            <script>
                let productIndex = 1;
                function addProductRow() {
                    const container = document.getElementById('product-items');
                    const row = document.createElement('div');
                    row.className = 'flex gap-2 mb-2';
                    row.innerHTML = `
                        <select name="items[${productIndex}][product_id]" class="form-select w-64 product-select input-focus focus:outline-none rounded-xl border border-pink-200" required>
                            <option value="">-- Pilih Produk --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} (Stok: {{ $product->current_stock }} {{ $product->base_unit }})
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="items[${productIndex}][quantity]" class="form-input w-24 input-focus focus:outline-none rounded-xl border border-pink-200" min="1" value="1" required placeholder="Jumlah">
                        <select name="items[${productIndex}][unit]" class="form-select w-24 input-focus focus:outline-none rounded-xl border border-pink-200" required>
                            <option value="">Satuan</option>
                            <option value="tangkai">Tangkai</option>
                            <option value="item">Item</option>
                            <option value="ikat">Ikat</option>
                            <option value="paket">Paket</option>
                        </select>
                        <input type="text" name="items[${productIndex}][item_notes]" class="form-input w-32 input-focus focus:outline-none rounded-xl border border-pink-200" placeholder="Catatan produk (opsional)">
                        <button type="button" onclick="this.parentNode.remove()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-xl flex items-center gap-1 shadow" title="Hapus produk">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    `;
                    container.appendChild(row);
                    productIndex++;
                }
                // Tom Select Autocomplete
                document.addEventListener('DOMContentLoaded', function () {
                    if (window.TomSelect === undefined) {
                        var script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js';
                        script.onload = function () {
                            initTomSelect();
                        };
                        document.body.appendChild(script);
                        var css = document.createElement('link');
                        css.rel = 'stylesheet';
                        css.href = 'https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css';
                        document.head.appendChild(css);
                    } else {
                        initTomSelect();
                    }
                });

                function initTomSelect() {
                    document.querySelectorAll('.product-select').forEach(function (el) {
                        if (!el.tomselect) {
                            new TomSelect(el, {
                                create: false,
                                sortField: {
                                    field: 'text',
                                    direction: 'asc'
                                },
                                placeholder: 'Cari produk...'
                            });
                        }
                    });
                }
            </script>
        </div>
    </div>
</x-app-layout>