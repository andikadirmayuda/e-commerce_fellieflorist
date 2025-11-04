<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-pink-700">
                <i class="bi bi-cart-check mr-2"></i>
                Daftar Purchase Order
            </h1>
            <a href="{{ route('purchase-orders.create') }}"
                class="bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                <i class="bi bi-plus-lg mr-1"></i>
                Buat Purchase Order
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="section-card p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="bi bi-list-check mr-2 text-pink-500"></i>
                    Daftar Purchase Order
                </h2>
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                        role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="table-header">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Tanggal Ambil</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Customer</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Produk</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Catatan</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($orders as $order)
                                <tr class="hover:bg-gray-50 transition-colors">
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
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">

                                            {{-- edit --}}
                                            <a href="{{ route('purchase-orders.edit', $order->id) }}"
                                                class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow hover:shadow-lg">
                                                <i class="bi bi-pencil-square mr-1"></i> Edit
                                            </a>
                                            <a href="{{ route('purchase-orders.show', $order->id) }}"
                                                class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow hover:shadow-lg">
                                                <i class="bi bi-eye mr-1"></i> Detail
                                            </a>
                                            <form action="{{ route('purchase-orders.destroy', $order->id) }}" method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus purchase order ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow hover:shadow-lg">
                                                    <i class="bi bi-trash mr-1"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-gray-400">Belum ada purchase order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $orders->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>