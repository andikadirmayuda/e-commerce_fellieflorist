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

        .stats-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(244, 63, 94, 0.1);
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .form-enter {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-pink-100 rounded-xl mr-3">
                    <i class="bi bi-cash-coin text-pink-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Laporan Cash Flow</h1>
                    <p class="text-sm text-gray-500 mt-1">Ringkasan arus kas masuk & keluar toko</p>
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
            <!-- Grafik Cashflow Owner & Selain Owner -->
            <div class="section-card p-6 mb-8">
                <h2 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="bi bi-bar-chart-line text-pink-500"></i> Grafik Arus Kas Bulan Ini (Personal & Business)
                </h2>
                <canvas id="cashflowChart" height="80"></canvas>
            </div>

            <!-- Statistik Ringkas Owner & Selain Owner -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Personal (Gabungan Bisnis + Personal) -->
                <div class="stats-card p-6">
                    <div class="mb-2 flex items-center gap-2">
                        <i class="bi bi-person-badge text-pink-500 text-xl"></i>
                        <span class="font-bold text-pink-700">Personal (Gabungan)</span>
                    </div>
                    <div class="mb-2 text-xs text-gray-500 font-semibold">Total Pendapatan Toko Bulan Ini</div>
                    <div class="text-xl font-bold text-green-700 mb-1">
                        Rp{{ number_format($totalPendapatanToko ?? 0, 0, ',', '.') }}</div>
                    <div class="text-xs text-gray-500 mb-2">(Penjualan Langsung:
                        Rp{{ number_format($totalSale ?? 0, 0, ',', '.') }}, Pemesanan Online:
                        Rp{{ number_format($totalOrder ?? 0, 0, ',', '.') }})</div>
                    <div class="mb-2 text-xs text-gray-500 font-semibold">Pemasukan Lain (Personal + Bisnis)</div>
                    <div class="text-lg font-bold text-blue-700 mb-2">
                        Rp{{ number_format(($inflowLainNonOwner ?? 0) + ($inflowLainOwner ?? 0), 0, ',', '.') }}</div>
                    <div class="mb-2 text-xs text-gray-500 font-semibold">Total Pengeluaran (Personal + Bisnis)</div>
                    <div class="text-lg font-bold text-red-700 mb-2">
                        Rp{{ number_format(($totalOutflowNonOwner ?? 0) + ($totalOutflowOwner ?? 0), 0, ',', '.') }}
                    </div>
                    <div class="mb-2 text-xs text-gray-500 font-semibold">Saldo Bersih Personal (Gabungan)</div>
                    <div class="text-2xl font-bold {{ $saldoBersihNonOwner >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        Rp{{ number_format($saldoBersihNonOwner ?? 0, 0, ',', '.') }}</div>
                    <div class="mb-2 text-xs font-semibold text-gray-600 mt-2">Profit / Kerugian (%)</div>
                    <div class="text-lg font-bold {{ $saldoBersihNonOwner >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        @php
                            $totalPemasukanPersonal = ($totalPendapatanToko ?? 0) + ($inflowLainNonOwner ?? 0) + ($inflowLainOwner ?? 0);
                            $profitPercentPersonal = $totalPemasukanPersonal > 0 && ($saldoBersihNonOwner ?? 0) > 0 ? round(($saldoBersihNonOwner ?? 0) / $totalPemasukanPersonal * 100, 2) : 0;
                            $lossPercentPersonal = $totalPemasukanPersonal > 0 && ($saldoBersihNonOwner ?? 0) < 0 ? round(abs($saldoBersihNonOwner ?? 0) / $totalPemasukanPersonal * 100, 2) : 0;
                        @endphp
                        @if($profitPercentPersonal > 0)
                            Profit {{ $profitPercentPersonal }}%
                        @endif
                        @if($lossPercentPersonal > 0)
                            Loss {{ $lossPercentPersonal }}%
                        @endif
                        @if($profitPercentPersonal == 0 && $lossPercentPersonal == 0)
                            0%
                        @endif
                    </div>
                </div>
                <!-- Business (Bisnis Saja) -->
                <div class="stats-card p-6">
                    <div class="mb-2 flex items-center gap-2">
                        <i class="bi bi-people text-blue-500 text-xl"></i>
                        <span class="font-bold text-blue-700">Business</span>
                    </div>
                    <div class="mb-2 text-xs text-gray-500 font-semibold">Total Pendapatan Toko Bulan Ini</div>
                    <div class="text-xl font-bold text-green-700 mb-1">
                        Rp{{ number_format($totalPendapatanToko ?? 0, 0, ',', '.') }}</div>
                    <div class="text-xs text-gray-500 mb-2">(Penjualan Langsung:
                        Rp{{ number_format($totalSale ?? 0, 0, ',', '.') }}, Pemesanan Online:
                        Rp{{ number_format($totalOrder ?? 0, 0, ',', '.') }})</div>
                    <div class="mb-2 text-xs text-gray-500 font-semibold">Pemasukan Lain (Bisnis Saja)</div>
                    <div class="text-lg font-bold text-blue-700 mb-2">
                        Rp{{ number_format($inflowLainOwner ?? 0, 0, ',', '.') }}</div>
                    <div class="mb-2 text-xs text-gray-500 font-semibold">Total Pengeluaran (Bisnis Saja)</div>
                    <div class="text-lg font-bold text-red-700 mb-2">
                        Rp{{ number_format($totalOutflowOwner ?? 0, 0, ',', '.') }}</div>
                    <div class="mb-2 text-xs text-gray-500 font-semibold">Saldo Bersih Business</div>
                    <div class="text-2xl font-bold {{ $saldoBersihOwner >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        Rp{{ number_format($saldoBersihOwner ?? 0, 0, ',', '.') }}</div>
                    <div class="mb-2 text-xs font-semibold text-gray-600 mt-2">Profit / Kerugian (%)</div>
                    <div class="text-lg font-bold {{ $saldoBersihOwner >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        @php
                            $totalPemasukanOwner = ($totalPendapatanToko ?? 0) + ($inflowLainOwner ?? 0);
                            $profitPercentOwner = $totalPemasukanOwner > 0 && ($saldoBersihOwner ?? 0) > 0 ? round(($saldoBersihOwner ?? 0) / $totalPemasukanOwner * 100, 2) : 0;
                            $lossPercentOwner = $totalPemasukanOwner > 0 && ($saldoBersihOwner ?? 0) < 0 ? round(abs($saldoBersihOwner ?? 0) / $totalPemasukanOwner * 100, 2) : 0;
                        @endphp
                        @if($profitPercentOwner > 0)
                            Profit {{ $profitPercentOwner }}%
                        @endif
                        @if($lossPercentOwner > 0)
                            Loss {{ $lossPercentOwner }}%
                        @endif
                        @if($profitPercentOwner == 0 && $lossPercentOwner == 0)
                            0%
                        @endif
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <form method="GET"
                class="flex flex-wrap items-end gap-3 bg-white p-3 rounded-xl shadow-sm border border-gray-100 mb-6 mt-6"
                id="cashflowFilterForm">
                <div class="flex flex-wrap gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            <i class="bi bi-calendar3 mr-1 text-pink-500"></i>
                            Tanggal Mulai
                        </label>
                        <input type="date" name="start_date" value="{{ request('start_date', '') }}"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            <i class="bi bi-calendar3 mr-1 text-pink-500"></i>
                            Tanggal Akhir
                        </label>
                        <input type="date" name="end_date" value="{{ request('end_date', '') }}"
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
                        Tampilkan
                    </button>
                    <button type="submit" name="export" value="pdf" formaction="{{ route('reports.cashflow.pdf') }}"
                        class="h-9 px-4 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-all duration-200 flex items-center">
                        <i class="bi bi-file-earmark-pdf mr-1.5"></i>
                        Export PDF
                    </button>
                    <a href="{{ route('reports.cashflow') }}"
                        class="h-9 px-4 bg-white border border-gray-200 hover:border-pink-500 hover:bg-pink-50 text-gray-700 hover:text-pink-600 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center">
                        <i class="bi bi-arrow-counterclockwise mr-1.5"></i>
                        Reset
                    </a>
                </div>
            </form>
            <!-- Tabel Data Cashflow Owner -->
            <div class="section-card p-6 mb-8 text-center">
                <h3 class="text-lg font-bold text-pink-700 mb-2 flex items-center gap-2"><i
                        class="bi bi-person-badge"></i> Transaksi Cashflow | Personal</h3>
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashFlowsOwner as $cf)
                                <tr class="border-b hover:bg-pink-50/50">
                                    <td class="px-4 py-2">{{ $cf->transaction_date }}</td>
                                    <td class="px-4 py-2">{{ $cf->category->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($cf->type) }}</td>
                                    <td class="px-4 py-2">Rp{{ number_format($cf->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($cf->payment_method) }}</td>
                                    <td class="px-4 py-2">{{ $cf->description }}</td>
                                    <td class="px-4 py-2">
                                        @if($cf->user)
                                            {{ $cf->user->name }}<br>
                                            <span
                                                class="text-xs text-gray-500">{{ ucfirst($cf->user->status ?? '-') }}</span><br>
                                            <span class="text-xs text-gray-400">
                                                @if($cf->created_at)
                                                    {{ \Carbon\Carbon::parse($cf->created_at)->translatedFormat('l, d F Y H:i:s') }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-gray-400">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Tabel Data Cashflow Selain Owner -->
            <div class="section-card p-6 mb-8 text-center">
                <h3 class="text-lg font-bold text-blue-700 mb-2 flex items-center gap-2"><i class="bi bi-people"></i>
                    Transaksi Cashflow | Business</h3>
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashFlowsNonOwner as $cf)
                                <tr class="border-b hover:bg-blue-50/50">
                                    <td class="px-4 py-2">{{ $cf->transaction_date }}</td>
                                    <td class="px-4 py-2">{{ $cf->category->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($cf->type) }}</td>
                                    <td class="px-4 py-2">Rp{{ number_format($cf->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2">{{ ucfirst($cf->payment_method) }}</td>
                                    <td class="px-4 py-2">{{ $cf->description }}</td>
                                    <td class="px-4 py-2">
                                        @if($cf->user)
                                            {{ $cf->user->name }}<br>
                                            <span
                                                class="text-xs text-gray-500">{{ ucfirst($cf->user->status ?? '-') }}</span><br>
                                            <span class="text-xs text-gray-400">
                                                @if($cf->created_at)
                                                    {{ \Carbon\Carbon::parse($cf->created_at)->translatedFormat('l, d F Y H:i:s') }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-gray-400">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <script>
        function exportPDF() {
            const form = document.getElementById('cashflowFilterForm');
            const url = new URL(form.action || window.location.href);
            const params = new URLSearchParams(new FormData(form));
            params.set('export', 'pdf');
            window.open(url.pathname + '?' + params.toString(), '_blank');
        }
        document.addEventListener('DOMContentLoaded', function () {
            // Data Owner
            const pendapatanToko = {{ $totalPendapatanToko ?? 0 }};
            const inflowLainOwner = {{ $inflowLainOwner ?? 0 }};
            const pemasukanOwner = pendapatanToko + inflowLainOwner;
            const pengeluaranOwner = {{ $totalOutflowOwner ?? 0 }};
            const saldoBersihOwner = {{ $saldoBersihOwner ?? 0 }};
            // Data Selain Owner
            const inflowLainNonOwner = {{ $inflowLainNonOwner ?? 0 }};
            const pemasukanNonOwner = pendapatanToko + inflowLainNonOwner;
            const pengeluaranNonOwner = {{ $totalOutflowNonOwner ?? 0 }};
            const saldoBersihNonOwner = {{ $saldoBersihNonOwner ?? 0 }};

            const ctx = document.getElementById('cashflowChart').getContext('2d');

            // Hitung persentase profit/loss untuk chart (dibagi total pemasukan saja)
            const totalPemasukanOwner = pendapatanToko + inflowLainOwner;
            const profitPercentOwner = totalPemasukanOwner > 0 && saldoBersihOwner > 0 ? Math.round(saldoBersihOwner / totalPemasukanOwner * 10000) / 100 : 0;
            const lossPercentOwner = totalPemasukanOwner > 0 && saldoBersihOwner < 0 ? Math.round(Math.abs(saldoBersihOwner) / totalPemasukanOwner * 10000) / 100 : 0;
            const totalPemasukanNonOwner = pendapatanToko + inflowLainNonOwner;
            const profitPercentNonOwner = totalPemasukanNonOwner > 0 && saldoBersihNonOwner > 0 ? Math.round(saldoBersihNonOwner / totalPemasukanNonOwner * 10000) / 100 : 0;
            const lossPercentNonOwner = totalPemasukanNonOwner > 0 && saldoBersihNonOwner < 0 ? Math.round(Math.abs(saldoBersihNonOwner) / totalPemasukanNonOwner * 10000) / 100 : 0;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Pendapatan Toko', 'Pemasukan Lain', 'Total Pemasukan', 'Pengeluaran', 'Saldo Bersih', 'Profit (%)', 'Loss (%)'],
                    datasets: [
                        {
                            label: 'Personal',
                            data: [pendapatanToko, inflowLainOwner, pemasukanOwner, pengeluaranOwner, saldoBersihOwner, profitPercentOwner, lossPercentOwner],
                            backgroundColor: [
                                'rgba(236, 72, 153, 0.7)', // pink
                                'rgba(59, 130, 246, 0.7)', // blue
                                'rgba(34,197,94,0.5)',      // green
                                'rgba(239, 68, 68, 0.7)',  // red
                                saldoBersihOwner >= 0 ? 'rgba(59, 130, 246, 0.7)' : 'rgba(239, 68, 68, 0.7)',
                                'rgba(34,197,94,0.7)',      // profit (green)
                                'rgba(239, 68, 68, 0.7)'   // loss (red)
                            ],
                            borderRadius: 10,
                            maxBarThickness: 40
                        },
                        {
                            label: 'Business',
                            data: [pendapatanToko, inflowLainNonOwner, pemasukanNonOwner, pengeluaranNonOwner, saldoBersihNonOwner, profitPercentNonOwner, lossPercentNonOwner],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.4)', // blue
                                'rgba(16, 185, 129, 0.4)', // green
                                'rgba(34,197,94,0.2)',      // green
                                'rgba(239, 68, 68, 0.4)',  // red
                                saldoBersihNonOwner >= 0 ? 'rgba(59, 130, 246, 0.4)' : 'rgba(239, 68, 68, 0.4)',
                                'rgba(34,197,94,0.4)',      // profit (green)
                                'rgba(239, 68, 68, 0.4)'   // loss (red)
                            ],
                            borderRadius: 10,
                            maxBarThickness: 40
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    // Untuk kolom profit/loss tampilkan %
                                    if (context.dataIndex === 5 || context.dataIndex === 6) {
                                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
                                    }
                                    let val = context.parsed.y || context.parsed;
                                    return context.dataset.label + ': Rp' + val.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value, index, values) {
                                    // Untuk kolom profit/loss tampilkan %
                                    if (values.length > 5 && (index === 5 || index === 6)) {
                                        return value + '%';
                                    }
                                    return 'Rp' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>