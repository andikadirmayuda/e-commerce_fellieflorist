<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Cash Flow</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #888;
            padding: 6px 8px;
            text-align: center;
        }

        th {
            background: #f9c2d1;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .summary-table th,
        .summary-table td {
            border: none;
            background: none;
        }
    </style>
</head>

<body>
    <h2 style="text-align:center;">Laporan Cash Flow
        @if($month)
            <br><span style="font-size:14px;">Periode:
                {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}</span>
        @endif
    </h2>
    <table class="summary-table" style="margin-bottom: 10px;">
        <tr>
            <th colspan="2" style="text-align:left; font-size:14px; background:#f3f4f6;">Personal (Gabungan)</th>
        </tr>
        <tr>
            <th class="text-left">Total Pendapatan Toko</th>
            <td class="text-right">Rp{{ number_format($totalPendapatanToko ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th class="text-left">Pemasukan Lain (Personal + Bisnis)</th>
            <td class="text-right">
                Rp{{ number_format(($inflowLainNonOwner ?? 0) + ($inflowLainOwner ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th class="text-left">Total Pengeluaran (Personal + Bisnis)</th>
            <td class="text-right">
                Rp{{ number_format(($totalOutflowNonOwner ?? 0) + ($totalOutflowOwner ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th class="text-left">Saldo Bersih Personal (Gabungan)</th>
            <td class="text-right">Rp{{ number_format($saldoBersihNonOwner ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
        </tr>
        <tr>
            <th colspan="2" style="text-align:left; font-size:14px; background:#f3f4f6;">Business</th>
        </tr>
        <tr>
            <th class="text-left">Total Pendapatan Toko</th>
            <td class="text-right">Rp{{ number_format($totalPendapatanToko ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th class="text-left">Pemasukan Lain (Bisnis Saja)</th>
            <td class="text-right">Rp{{ number_format($inflowLainOwner ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th class="text-left">Total Pengeluaran (Bisnis Saja)</th>
            <td class="text-right">Rp{{ number_format($totalOutflowOwner ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th class="text-left">Saldo Bersih Business</th>
            <td class="text-right">Rp{{ number_format($saldoBersihOwner ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>
    <h4 style="margin-top:30px; margin-bottom:5px; text-align:left;">Transaksi Cashflow | Personal (Gabungan)</h4>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Tipe</th>
                <th>Jumlah</th>
                <th>Metode</th>
                <th>Keterangan</th>
                <th>Dibuat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cashFlowsOwner as $cf)
                <tr>
                    <td>{{ $cf->transaction_date }}</td>
                    <td>{{ $cf->category->name ?? '-' }}</td>
                    <td>{{ ucfirst($cf->type) }}</td>
                    <td class="text-right">Rp{{ number_format($cf->amount, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($cf->payment_method) }}</td>
                    <td class="text-left">{{ $cf->description }}</td>
                    <td>
                        @if($cf->user)
                            {{ $cf->user->name }}<br>
                            <span style="font-size:10px; color:#888;">{{ ucfirst($cf->user->status ?? '-') }}</span><br>
                            <span style="font-size:10px; color:#aaa;">
                                @if($cf->created_at)
                                    {{ \Carbon\Carbon::parse($cf->created_at)->translatedFormat('d-m-Y H:i') }}
                                @endif
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h4 style="margin-top:30px; margin-bottom:5px; text-align:left;">Transaksi Cashflow | Business</h4>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Tipe</th>
                <th>Jumlah</th>
                <th>Metode</th>
                <th>Keterangan</th>
                <th>Dibuat Oleh</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cashFlowsNonOwner as $cf)
                <tr>
                    <td>{{ $cf->transaction_date }}</td>
                    <td>{{ $cf->category->name ?? '-' }}</td>
                    <td>{{ ucfirst($cf->type) }}</td>
                    <td class="text-right">Rp{{ number_format($cf->amount, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($cf->payment_method) }}</td>
                    <td class="text-left">{{ $cf->description }}</td>
                    <td>
                        @if($cf->user)
                            {{ $cf->user->name }}<br>
                            <span style="font-size:10px; color:#888;">{{ ucfirst($cf->user->status ?? '-') }}</span><br>
                            <span style="font-size:10px; color:#aaa;">
                                @if($cf->created_at)
                                    {{ \Carbon\Carbon::parse($cf->created_at)->translatedFormat('d-m-Y H:i') }}
                                @endif
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="font-size:10px; color:#888; text-align:right;">Exported: {{ now()->translatedFormat('d F Y H:i') }}
    </div>
</body>

</html>