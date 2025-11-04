<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-pink-700">
                <i class="bi bi-file-earmark-text mr-2"></i>
                Laporan Purchase Order
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

        .table-header {
            background: linear-gradient(90deg, #fdf2f8 0%, #f0fdf4 100%);
        }
    </style>

    <div class="py-8 gradient-bg min-h-screen">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="section-card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="bi bi-bar-chart mr-2 text-pink-500"></i>
                    Rekap Purchase Order
                </h2>
                <form method="GET" class="mb-6 flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Awal</label>
                        <input type="date" name="start" value="{{ request('start') }}"
                            class="form-input px-4 py-2 border border-pink-200 rounded-xl input-focus focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Akhir</label>
                        <input type="date" name="end" value="{{ request('end') }}"
                            class="form-input px-4 py-2 border border-pink-200 rounded-xl input-focus focus:outline-none">
                    </div>
                    <button type="submit"
                        class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-2 rounded-xl font-semibold shadow">Filter</button>
                </form>
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="table-header">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Tanggal Ambil</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Customer</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Produk</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($orders as $order)
                                <tr>
                                    <td class="px-6 py-4">{{ $order->pickup_date }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-pink-700 flex items-center">
                                            <i class="bi bi-person-circle mr-1"></i>
                                            {{ $order->customer_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $order->customer_phone }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <ul class="list-disc ml-4">
                                            @foreach($order->items as $item)
                                                <li>
                                                    <span
                                                        class="font-medium text-gray-800">{{ $item->product->name ?? '-' }}</span>
                                                    <span class="text-xs text-gray-500">({{ $item->quantity }}
                                                        {{ $item->unit }})</span>
                                                    @if($item->item_notes)
                                                        <span class="text-xs text-blue-400">- {{ $item->item_notes }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="px-6 py-4">{{ $order->notes }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-gray-400">Tidak ada data purchase order.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>