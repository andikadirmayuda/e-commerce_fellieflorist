<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Pesanan Online Fellie Florist</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #000;
            margin: 0;
        }

        .report-title {
            font-size: 18px;
            color: #666;
            margin: 5px 0;
        }

        .period {
            background: #f5f5f5;
            padding: 8px;
            border-radius: 4px;
            margin: 15px 0;
            text-align: center;
        }

        .summary {
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .summary-item {
            margin-bottom: 10px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
        }

        th {
            background: #f8f9fa;
            color: #333;
            font-weight: bold;
            padding: 12px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        td {
            padding: 10px 8px;
            border: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .page-number {
            text-align: right;
            font-size: 10px;
            color: #999;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 class="company-name">Fellie Florist</h1>
        <p class="report-title">Laporan Pesanan Online</p>
    </div>

    <div class="period">
        Periode: {{ \Carbon\Carbon::parse($start)->format('d F Y') }} s/d
        {{ \Carbon\Carbon::parse($end)->format('d F Y') }}
    </div>

    <div class="summary">
        <div class="summary-item">Total Pesanan: <strong>{{ $totalOrder }}</strong> pesanan</div>
        <div class="summary-item">Total Pending: <strong>{{ $totalPending }}</strong></div>
        <div class="summary-item">Total Diproses: <strong>{{ $totalProcessed }}</strong></div>
        <div class="summary-item">Total Selesai: <strong>{{ $totalCompleted }}</strong></div>
        <div class="summary-item">Total Dibatalkan: <strong>{{ $totalCancelled }}</strong></div>
        <div class="summary-item">Total Sudah Dibayar: <strong>{{ $totalLunas }}</strong></div>
        <div class="summary-item">Pendapatan Cash:
            <strong>Rp{{ number_format($totalCashOrder, 0, ',', '.') }}</strong>
        </div>
        <div class="summary-item">Pendapatan Transfer:
            <strong>Rp{{ number_format($totalTransferOrder, 0, ',', '.') }}</strong>
        </div>
        <div class="summary-item">Pendapatan Debit:
            <strong>Rp{{ number_format($totalDebitOrder, 0, ',', '.') }}</strong>
        </div>
        <div class="summary-item">Pendapatan E-Wallet:
            <strong>Rp{{ number_format($totalEwalletOrder, 0, ',', '.') }}</strong>
        </div>
        <br>
        <div class="summary-item">Total Nilai Pesanan:
            <strong>Rp{{ number_format($totalNominal, 0, ',', '.') }}</strong>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">Tanggal</th>
                <th style="width: 20%">No. Order</th>
                <th style="width: 15%">Pelanggan</th>
                <th style="width: 10%">Status</th>
                <th style="width: 15%">Metode Bayar</th>
                <th style="width: 20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $order->order_number }}</td>
                    <td>
                        {{ $order->customer_name ?? '-' }}<br>
                        @if($order->customer_phone)
                            <span style="color:#888; font-size:11px">{{ $order->customer_phone }}</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $text = match ($order->status) {
                                'paid' => 'Sudah Dibayar',
                                'processed' => 'Diproses',
                                'completed' => 'Selesai',
                                'unpaid' => 'Belum Dibayar',
                                default => ucfirst($order->status),
                            };
                        @endphp
                        {{ $text }}
                    </td>
                    <td>{{ $order->payment_method ? ucfirst($order->payment_method) : '-' }}</td>
                    <td class="text-right">Rp{{ number_format($order->total, 0, ',', '.') }}
                        @if($order->delivery_fee > 0)
                            <br><span style="color:#888; font-size:11px">+Ongkir:
                                Rp{{ number_format($order->delivery_fee, 0, ',', '.') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data pemesanan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada {{ now()->format('d F Y H:i') }} WIB</p>
    </div>

    <div class="page-number">
        Halaman 1
    </div>
</body>

</html>