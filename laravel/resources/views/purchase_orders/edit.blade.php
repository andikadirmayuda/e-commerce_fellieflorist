<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-pink-700">
                <i class="bi bi-pencil-square mr-2"></i>
                Edit Purchase Order
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
                    <i class="bi bi-pencil-square text-2xl text-white"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">
                    Edit <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-rose-600">Purchase
                        Order</span>
                </h2>
                <p class="text-gray-600">Perbarui data purchase order dengan detail dan benar.</p>
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

            <form action="{{ route('purchase-orders.update', $order->id) }}" method="POST" class="space-y-8">
                @csrf
                @method('PUT')
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
                                value="{{ old('customer_name', $order->customer_name) }}"
                                class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Nomor HP
                            </label>
                            <input type="text" name="customer_phone"
                                value="{{ old('customer_phone', $order->customer_phone) }}"
                                class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Tanggal Ambil
                            </label>
                            <input type="date" name="pickup_date" value="{{ old('pickup_date', $order->pickup_date) }}"
                                class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Catatan (opsional)
                            </label>
                            <textarea name="notes"
                                class="w-full px-4 py-3 border border-pink-200 rounded-xl input-focus focus:outline-none"
                                placeholder="Catatan umum untuk order, bukan per produk">{{ old('notes', $order->notes) }}</textarea>
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
                        @foreach($order->items as $i => $item)
                            <div class="flex gap-2 mb-2">
                                <select name="items[{{ $i }}][product_id]"
                                    class="form-select w-64 product-select input-focus focus:outline-none rounded-xl border border-pink-200"
                                    required>
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }} (Stok: {{ $product->current_stock }} {{ $product->base_unit }})
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" name="items[{{ $i }}][quantity]"
                                    class="form-input w-24 input-focus focus:outline-none rounded-xl border border-pink-200"
                                    min="1" value="{{ $item->quantity }}" required placeholder="Jumlah">
                                <select name="items[{{ $i }}][unit]"
                                    class="form-select w-24 input-focus focus:outline-none rounded-xl border border-pink-200"
                                    required>
                                    <option value="">Satuan</option>
                                    <option value="tangkai" {{ $item->unit == 'tangkai' ? 'selected' : '' }}>Tangkai</option>
                                    <option value="item" {{ $item->unit == 'item' ? 'selected' : '' }}>Item</option>
                                    <option value="ikat" {{ $item->unit == 'ikat' ? 'selected' : '' }}>Ikat</option>
                                    <option value="paket" {{ $item->unit == 'paket' ? 'selected' : '' }}>Paket</option>
                                </select>
                                <input type="text" name="items[{{ $i }}][item_notes]"
                                    class="form-input w-32 input-focus focus:outline-none rounded-xl border border-pink-200"
                                    placeholder="Catatan produk (opsional)" value="{{ $item->item_notes }}">
                            </div>
                        @endforeach
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
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>