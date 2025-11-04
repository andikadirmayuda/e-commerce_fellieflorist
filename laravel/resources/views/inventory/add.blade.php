<x-app-layout>
    <div class="max-w-xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold text-pink-700 mb-6 flex items-center">
            <i class="bi bi-pencil-square mr-2"></i>
            Input Stok Masuk Produk
        </h2>
        <form action="{{ route('inventory.add.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="product_id" class="block text-sm font-semibold text-gray-700 mb-2">Produk</label>
                <select name="product_id" id="product_id" class="w-full px-4 py-3 border rounded-xl focus:ring-pink-500"
                    required>
                    <option value="">Pilih Produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}
                            ({{ $product->category->name }} - {{ $product->current_stock }})</option>
                    @endforeach
                </select>
                <!-- Select2 CSS CDN -->
                <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
                    rel="stylesheet" />
                <!-- jQuery CDN (wajib sebelum Select2) -->
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <!-- Select2 JS CDN -->
                <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
                <script>
                    function initSelect2Produk() {
                        if (window.jQuery && $('#product_id').length) {
                            $('#product_id').select2({
                                placeholder: 'Cari atau pilih produk',
                                allowClear: true,
                                width: '100%'
                            });
                        }
                    }
                    document.addEventListener('DOMContentLoaded', initSelect2Produk);
                    // Untuk Livewire atau turbolinks, jika digunakan:
                    document.addEventListener('livewire:load', initSelect2Produk);
                </script>
            </div>
            <div class="mb-4">
                <label for="unit" class="block text-sm font-semibold text-gray-700 mb-2">Satuan</label>
                <input type="text" name="unit" id="unit" value="tangkai"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-pink-500 bg-gray-100" readonly required>
            </div>
            <div class="mb-4">
                <label for="qty" class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Masuk</label>
                <input type="number" name="qty" id="qty" min="1"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-pink-500" required>
            </div>
            <script>
                // Data satuan per produk dari backend
                const productUnits = @json($productUnits);
                const unitSelect = document.getElementById('unit');
                const productSelect = document.getElementById('product_id');

                function updateUnitOptions() {
                    const productId = productSelect.value;
                    // Kosongkan dropdown
                    unitSelect.innerHTML = '<option value="">Pilih Satuan</option>';
                    let foundTangkai = false;
                    if (productUnits[productId]) {
                        productUnits[productId].forEach(unit => {
                            const opt = document.createElement('option');
                            opt.value = unit;
                            opt.textContent = unit;
                            if (unit.toLowerCase() === 'tangkai') {
                                opt.selected = true;
                                foundTangkai = true;
                            }
                            unitSelect.appendChild(opt);
                        });
                        // Jika tidak ada 'tangkai', pilih opsi pertama selain placeholder
                        if (!foundTangkai && unitSelect.options.length > 1) {
                            unitSelect.options[1].selected = true;
                        }
                    }
                }
                productSelect.addEventListener('change', updateUnitOptions);
                // Inisialisasi jika ada value terpilih
                if (productSelect.value) updateUnitOptions();
            </script>
            <div class="mb-4">
                <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Masuk</label>
                <input type="text" name="date" id="date"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-pink-500 bg-gray-100" readonly required>
            </div>
            <script>
                // Set tanggal hari ini secara otomatis (format yyyy-mm-dd)
                const dateInput = document.getElementById('date');
                if (dateInput) {
                    const today = new Date();
                    const yyyy = today.getFullYear();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');
                    dateInput.value = `${yyyy}-${mm}-${dd}`;
                }
            </script>
            <div class="mb-4">
                <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">Catatan (Opsional)</label>
                <textarea name="notes" id="notes" rows="3"
                    class="w-full px-4 py-3 border rounded-xl focus:ring-pink-500">Stok Baru</textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                    class="bg-pink-500 hover:bg-pink-600 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200">
                    <i class="bi bi-plus-circle mr-1"></i>
                    Simpan Stok Masuk
                </button>
            </div>
        </form>
    </div>
</x-app-layout>