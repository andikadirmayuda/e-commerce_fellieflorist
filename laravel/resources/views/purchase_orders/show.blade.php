<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-pink-700">
                <i class="bi bi-cart-check mr-2"></i>
                Detail Purchase Order
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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="section-card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="bi bi-info-circle mr-2 text-pink-500"></i>
                    Informasi Purchase Order
                </h2>
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="font-semibold text-pink-700 flex items-center mb-2">
                            <i class="bi bi-person-circle mr-1"></i>
                            {{ $order->customer_name }}
                        </div>
                        <div class="text-xs text-gray-500 mb-2">{{ $order->customer_phone }}</div>
                        <div class="text-sm text-gray-700 mb-2">Tanggal Ambil: <span
                                class="font-bold">{{ $order->pickup_date }}</span></div>
                        <div class="text-sm text-gray-700">Catatan: <span
                                class="text-gray-500">{{ $order->notes ?? '-' }}</span></div>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2 mt-6 flex items-center">
                    <i class="bi bi-box-seam mr-2 text-pink-500"></i>
                    Produk Dipesan
                </h3>
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-pink-50 to-rose-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Produk</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Jumlah</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Satuan</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="px-6 py-4">{{ $item->product->name ?? '-' }}</td>
                                    <td class="px-6 py-4">{{ $item->quantity }}</td>
                                    <td class="px-6 py-4">{{ $item->unit }}</td>
                                    <td class="px-6 py-4">{{ $item->item_notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>