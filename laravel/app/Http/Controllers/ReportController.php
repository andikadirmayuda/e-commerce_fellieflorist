<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Product;
use App\Models\InventoryLog;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // Laporan Cashflow
    public function cashflow(Request $request)
    {
        // Set Carbon locale ke Indonesia
        \Carbon\Carbon::setLocale('id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $categoryId = $request->input('category_id');

        // Default: jika tidak ada filter tanggal, pakai bulan berjalan
        if (!$startDate || !$endDate) {
            $month = $request->input('month', now()->format('Y-m'));
            $year = substr($month, 0, 4);
            $mon = substr($month, 5, 2);
            $startDate = "$year-$mon-01";
            $endDate = date('Y-m-t', strtotime($startDate));
        }

        $query = \App\Models\CashFlow::with(['category', 'user'])
            ->whereBetween('transaction_date', [$startDate, $endDate]);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        $cashFlows = $query->orderBy('transaction_date', 'desc')->get();

        // Pisahkan cashflow berdasarkan status user (owner vs selain owner)
        $ownerName = 'Owner Florist';
        $cashFlowsOwner = $cashFlows->filter(function ($cf) use ($ownerName) {
            $name = $cf->user && $cf->user->name ? trim($cf->user->name) : '';
            return strcasecmp($name, $ownerName) === 0;
        });
        $cashFlowsNonOwner = $cashFlows->filter(function ($cf) use ($ownerName) {
            $name = $cf->user && $cf->user->name ? trim($cf->user->name) : '';
            return strcasecmp($name, $ownerName) !== 0;
        });

        // Ringkasan Owner
        $inflowLainOwner = $cashFlowsOwner->where('type', 'inflow')->sum('amount');
        $totalOutflowOwner = $cashFlowsOwner->where('type', 'outflow')->sum('amount');

        // Ringkasan Selain Owner
        $inflowLainNonOwner = $cashFlowsNonOwner->where('type', 'inflow')->sum('amount');
        $totalOutflowNonOwner = $cashFlowsNonOwner->where('type', 'outflow')->sum('amount');

        // Ambil semua kategori untuk filter
        $categories = \App\Models\CashFlowCategory::all();

        // Hitung pendapatan toko dari Sale dan PublicOrder
        $totalSale = \App\Models\Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total');
        $totalOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$startDate, $endDate])
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));
        $totalPendapatanToko = $totalSale + $totalOrder;

        // Saldo bersih Personal = (pendapatan toko + pemasukan lain personal + pemasukan lain bisnis) - (pengeluaran personal + pengeluaran bisnis)
        $saldoBersihNonOwner = $totalPendapatanToko + $inflowLainNonOwner + $inflowLainOwner - ($totalOutflowNonOwner + $totalOutflowOwner);
        // Saldo bersih Business (Owner) tetap seperti sebelumnya
        $saldoBersihOwner = $totalPendapatanToko + $inflowLainOwner - $totalOutflowOwner;

        return view('reports.cashflow', [
            'cashFlowsOwner' => $cashFlowsOwner,
            'cashFlowsNonOwner' => $cashFlowsNonOwner,
            'categories' => $categories,
            'totalPendapatanToko' => $totalPendapatanToko,
            'totalSale' => $totalSale,
            'totalOrder' => $totalOrder,
            // Owner
            'inflowLainOwner' => $inflowLainOwner,
            'totalOutflowOwner' => $totalOutflowOwner,
            'saldoBersihOwner' => $saldoBersihOwner,
            // NonOwner
            'inflowLainNonOwner' => $inflowLainNonOwner,
            'totalOutflowNonOwner' => $totalOutflowNonOwner,
            'saldoBersihNonOwner' => $saldoBersihNonOwner,
        ]);
    }
    // Laporan Penjualan
    public function sales(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Base query with date range
        $query = Sale::whereBetween('created_at', [$start, $end]);

        // Apply status filter if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Get all sales data with relationships (no pagination)
        $sales = $query->with(['items.product', 'deletedBy'])
            ->latest()
            ->get();

        // Calculate statistics
        $totalSales = $query->count();
        $totalRevenue = $query->sum('total');
        $averageTransaction = $totalSales > 0 ? ($totalRevenue / $totalSales) : 0;

        // Statistik pendapatan berdasarkan metode pembayaran
        $totalCash = (clone $query)->where('payment_method', 'cash')->sum('total');
        $totalTransfer = (clone $query)->where('payment_method', 'transfer')->sum('total');
        $totalDebit = (clone $query)->where('payment_method', 'debit')->sum('total');

        return view('reports.sales', compact(
            'sales',
            'totalSales',
            'totalRevenue',
            'averageTransaction',
            'totalCash',
            'totalTransfer',
            'totalDebit',
            'start',
            'end'
        ));
    }

    // Laporan Stok Terintegrasi
    public function stock(Request $request)
    {
        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->endOfMonth()->toDateString());

        $products = Product::with('category')->get();
        $logs = InventoryLog::whereBetween('created_at', [$start, $end])->latest()->limit(100)->get();

        // Rekap stok masuk, keluar (total, sale, public_order), penyesuaian, dan total per produk
        $rekap = [];
        foreach ($products as $product) {
            // Stok keluar karena rusak
            $keluar_rusak = InventoryLog::where('product_id', $product->id)
                ->where('qty', '<', 0)
                ->where('source', InventoryLog::SOURCE_DAMAGED)
                ->whereBetween('created_at', [$start, $end])
                ->sum('qty');
            $masuk = InventoryLog::where('product_id', $product->id)
                ->where('qty', '>', 0)
                ->whereBetween('created_at', [$start, $end])
                ->sum('qty');

            // Stok keluar dari sale
            $keluar_sale = InventoryLog::where('product_id', $product->id)
                ->where('qty', '<', 0)
                ->where('source', InventoryLog::SOURCE_SALE)
                ->whereBetween('created_at', [$start, $end])
                ->sum('qty');

            // Stok keluar dari public_order (semua tipe public_order)
            $publicOrderSources = [
                InventoryLog::SOURCE_PUBLIC_ORDER_PRODUCT,
                InventoryLog::SOURCE_PUBLIC_ORDER_BOUQUET,
                InventoryLog::SOURCE_PUBLIC_ORDER_CUSTOM,
                InventoryLog::SOURCE_PUBLIC_ORDER_HOLD,
                InventoryLog::SOURCE_PUBLIC_ORDER_BOUQUET_HOLD,
                InventoryLog::SOURCE_PUBLIC_ORDER_CUSTOM_HOLD,
            ];
            $keluar_public_order = InventoryLog::where('product_id', $product->id)
                ->where('qty', '<', 0)
                ->whereIn('source', $publicOrderSources)
                ->whereBetween('created_at', [$start, $end])
                ->sum('qty');

            // Stok keluar total hanya dari sale + public order
            $keluar = abs($keluar_sale) + abs($keluar_public_order);

            $penyesuaian = InventoryLog::where('product_id', $product->id)
                ->where('source', InventoryLog::SOURCE_ADJUSTMENT)
                ->whereBetween('created_at', [$start, $end])
                ->sum('qty');

            $rekap[$product->id] = [
                'masuk' => $masuk,
                'keluar' => $keluar,
                'keluar_sale' => abs($keluar_sale),
                'keluar_public_order' => abs($keluar_public_order),
                'keluar_rusak' => abs($keluar_rusak),
                'penyesuaian' => $penyesuaian,
                'stok_akhir' => $product->current_stock,
            ];
        }

        return view('reports.stock', compact('products', 'logs', 'rekap', 'start', 'end'));
    }

    // Ekspor laporan penjualan ke PDF
    public function salesPdf(Request $request)
    {
        try {
            $start = $request->input('start_date', now()->startOfMonth()->toDateString());
            $end = $request->input('end_date', now()->endOfMonth()->toDateString());

            // Get sales data with relationships
            $sales = Sale::with(['items.product'])
                ->whereBetween('created_at', [$start, $end])
                ->get();

            // Calculate summary statistics
            $totalPendapatan = $sales->sum('total');
            $totalTransaksi = $sales->count();

            // Statistik pendapatan berdasarkan metode pembayaran
            $totalCash = $sales->where('payment_method', 'cash')->sum('total');
            $totalTransfer = $sales->where('payment_method', 'transfer')->sum('total');
            $totalDebit = $sales->where('payment_method', 'debit')->sum('total');

            // Load and render PDF using DomPDF
            $pdf = Pdf::loadView('reports.sales_pdf', compact('sales', 'start', 'end', 'totalPendapatan', 'totalTransaksi', 'totalCash', 'totalTransfer', 'totalDebit'));

            // Set paper size and orientation
            $pdf->setPaper('a4', 'portrait');

            // Generate filename based on date range
            $filename = "laporan_penjualan_{$start}_to_{$end}.pdf";

            // Return PDF for download
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengexport PDF: ' . $e->getMessage()]);
        }
    }

    // Laporan Pemesanan
    public function orders(Request $request)
    {
        $start = $request->input('start_date', now()->startOfYear()->toDateString());
        $end = $request->input('end_date', now()->endOfYear()->toDateString());

        // Mengambil data public order (pemesanan online)
        $orders = \App\Models\PublicOrder::with(['items.product'])
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();

        $totalOrder = $orders->count();
        $totalNominal = $orders->sum('total');


        // Status yang dianggap "Lunas/Dibayar": paid, processed, completed
        $statusLunas = ['paid', 'processed', 'completed'];
        $statusBelumLunas = ['pending', 'unpaid'];

        $totalLunas = $orders->whereIn('status', $statusLunas)->count();
        $totalBelumLunas = $orders->whereIn('status', $statusBelumLunas)->count();

        // Total nominal pesanan yang sudah menghasilkan pendapatan (lunas/selesai)
        $totalNominalLunas = $orders->whereIn('status', $statusLunas)->sum('total');

        // Tambahan: statistik jumlah order per status
        $totalPending = $orders->where('status', 'pending')->count();
        $totalProcessed = $orders->where('status', 'processed')->count();
        $totalCompleted = $orders->where('status', 'completed')->count();
        $totalCancelled = $orders->where('status', 'cancelled')->count();

        // Statistik pendapatan berdasarkan metode pembayaran
        $totalCashOrder = $orders->where('payment_method', 'cash')->sum('total');
        $totalTransferOrder = $orders->where('payment_method', 'transfer')->sum('total');
        $totalDebitOrder = $orders->where('payment_method', 'debit')->sum('total');
        $totalEwalletOrder = $orders->where('payment_method', 'e-wallet')->sum('total');

        return view('reports.orders', compact(
            'orders',
            'start',
            'end',
            'totalOrder',
            'totalNominal',
            'totalNominalLunas',
            'totalLunas',
            'totalBelumLunas',
            'totalPending',
            'totalProcessed',
            'totalCompleted',
            'totalCancelled',
            'totalCashOrder',
            'totalTransferOrder',
            'totalDebitOrder',
            'totalEwalletOrder'
        ));
    }

    // Laporan Pelanggan
    public function customers(Request $request)
    {
        $start = $request->input('start_date', now()->startOfYear()->toDateString());
        $end = $request->input('end_date', now()->endOfYear()->toDateString());

        // Mengambil data pelanggan dari public_order
        $publicOrders = \App\Models\PublicOrder::with(['items.product'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // Grup data berdasarkan wa_number (nomor WhatsApp) dan kumpulkan semua nama yang berbeda
        // Filter out orders with empty or null wa_number first
        $validOrders = $publicOrders->filter(function ($order) {
            return !empty($order->wa_number) && $order->wa_number !== '-';
        });

        $customers = $validOrders->groupBy('wa_number')->map(function ($orders, $waNumber) {
            $totalOrders = $orders->count();
            $totalSpent = $orders->sum('total');

            // Kumpulkan semua nama unik dari order dengan nomor WA yang sama
            $uniqueNames = $orders->pluck('customer_name')->unique()->values()->toArray();

            // Ambil nama yang paling sering muncul sebagai nama utama
            $nameFrequency = $orders->countBy('customer_name');
            $primaryName = $nameFrequency->sortDesc()->keys()->first();

            return (object) [
                'name' => $primaryName, // Nama yang paling sering digunakan
                'all_names' => $uniqueNames, // Semua nama yang pernah digunakan
                'names_count' => count($uniqueNames), // Jumlah variasi nama
                'phone' => $waNumber,
                'orders_count' => $totalOrders,
                'total_spent' => $totalSpent,
                'orders' => $orders
            ];
        })->sortByDesc('total_spent')->values();

        $totalCustomer = $customers->count();
        $totalOrder = $publicOrders->count();
        $topCustomer = $customers->first();

        return view('reports.customers', compact('customers', 'start', 'end', 'totalCustomer', 'totalOrder', 'topCustomer'));
    }

    // Laporan Pendapatan
    public function income(Request $request)
    {
        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->endOfMonth()->toDateString());

        // Total pendapatan dari penjualan
        $totalPenjualan = Sale::whereBetween('created_at', [$start, $end])->sum('total');

        // Total per metode pembayaran di sale
        $totalCashSale = Sale::whereBetween('created_at', [$start, $end])->where('payment_method', 'cash')->sum('total');
        $totalTransferSale = Sale::whereBetween('created_at', [$start, $end])->where('payment_method', 'transfer')->sum('total');
        $totalDebitSale = Sale::whereBetween('created_at', [$start, $end])->where('payment_method', 'debit')->sum('total');

        // Total pendapatan dari pemesanan (sum dari item, status paid, processed, completed)
        $statusLunas = ['paid', 'processed', 'completed'];
        $totalPemesanan = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->whereIn('public_orders.status', $statusLunas)
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        // Total per metode pembayaran di public order (sum dari item, status paid, processed, completed)
        $totalCashOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->where('public_orders.payment_method', 'cash')
            ->whereIn('public_orders.status', $statusLunas)
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        $totalTransferOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->where('public_orders.payment_method', 'transfer')
            ->whereIn('public_orders.status', $statusLunas)
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        $totalDebitOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->where('public_orders.payment_method', 'debit')
            ->whereIn('public_orders.status', $statusLunas)
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        $totalEwalletOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->where('public_orders.payment_method', 'e-wallet')
            ->whereIn('public_orders.status', $statusLunas)
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));


        // Ambil data cash flow (inflow dan outflow) pada periode yang sama
        $totalInflow = \App\Models\CashFlow::where('type', 'inflow')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');
        $totalOutflow = \App\Models\CashFlow::where('type', 'outflow')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        // Breakdown per metode pembayaran untuk cash flow
        $totalCashInflow = \App\Models\CashFlow::where('type', 'inflow')->where('payment_method', 'cash')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $totalCashOutflow = \App\Models\CashFlow::where('type', 'outflow')->where('payment_method', 'cash')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $totalTransferInflow = \App\Models\CashFlow::where('type', 'inflow')->where('payment_method', 'transfer')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $totalTransferOutflow = \App\Models\CashFlow::where('type', 'outflow')->where('payment_method', 'transfer')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $totalDebitInflow = \App\Models\CashFlow::where('type', 'inflow')->where('payment_method', 'debit')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $totalDebitOutflow = \App\Models\CashFlow::where('type', 'outflow')->where('payment_method', 'debit')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $totalEwalletInflow = \App\Models\CashFlow::where('type', 'inflow')->where('payment_method', 'e-wallet')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $totalEwalletOutflow = \App\Models\CashFlow::where('type', 'outflow')->where('payment_method', 'e-wallet')->whereBetween('transaction_date', [$start, $end])->sum('amount');

        // Statistik khusus cashflow saja (tanpa sale/order)
        $totalCashflowCash = $totalCashInflow - $totalCashOutflow;
        $totalCashflowTransfer = $totalTransferInflow - $totalTransferOutflow;
        $totalCashflowEwallet = $totalEwalletInflow - $totalEwalletOutflow;

        // Total pendapatan gabungan (penjualan + pemesanan + pemasukan cash flow - pengeluaran cash flow)
        $totalPendapatan = ($totalPenjualan + $totalPemesanan + $totalInflow) - $totalOutflow;

        // Breakdown total cash, transfer, debit, e-wallet (sale + order + inflow cashflow)
        $totalCash = $totalCashSale + $totalCashOrder;
        $totalTransfer = $totalTransferSale + $totalTransferOrder;
        $totalDebit = $totalDebitSale + $totalDebitOrder;
        $totalEwallet = $totalEwalletOrder + $totalEwalletInflow;

        // Pendapatan harian: hanya tampilkan tanggal yang ada transaksi
        $harian = [];
        $saleDates = Sale::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date')
            ->groupBy('date')
            ->pluck('date')
            ->toArray();
        $orderDates = DB::table('public_orders')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['confirmed', 'processing', 'ready', 'completed'])
            ->selectRaw('DATE(created_at) as date')
            ->groupBy('date')
            ->pluck('date')
            ->toArray();
        $allDates = array_unique(array_merge($saleDates, $orderDates));
        sort($allDates);
        foreach ($allDates as $date) {
            $dailyPenjualan = Sale::whereDate('created_at', $date)->sum('total');
            $dailyPemesanan = DB::table('public_orders')
                ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
                ->whereDate('public_orders.created_at', $date)
                ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
                ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));
            if ($dailyPenjualan > 0 || $dailyPemesanan > 0) {
                $harian[$date] = [
                    'penjualan' => $dailyPenjualan,
                    'pemesanan' => $dailyPemesanan,
                ];
            }
        }

        // Pendapatan mingguan
        $mingguan = [];
        $startWeek = now()->parse($start)->startOfWeek();
        $endWeek = now()->parse($end)->endOfWeek();
        for ($date = $startWeek->copy(); $date <= $endWeek; $date->addWeek()) {
            $weekStart = $date->copy();
            $weekEnd = $date->copy()->endOfWeek();

            $weeklyPenjualan = Sale::whereBetween('created_at', [$weekStart, $weekEnd])->sum('total');

            $weeklyPemesanan = DB::table('public_orders')
                ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
                ->whereBetween('public_orders.created_at', [$weekStart, $weekEnd])
                ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
                ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

            $mingguan[$weekStart->format('d M Y')] = [
                'penjualan' => $weeklyPenjualan,
                'pemesanan' => $weeklyPemesanan,
            ];
        }

        // Pendapatan bulanan
        $bulanan = [];
        $startMonth = now()->parse($start)->startOf('month');
        $endMonth = now()->parse($end)->endOf('month');

        // Generate bulan berdasarkan range yang dipilih
        $currentMonth = $startMonth->copy();
        while ($currentMonth <= $endMonth) {
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();

            $monthlyPenjualan = Sale::whereBetween('created_at', [$monthStart, $monthEnd])->sum('total');

            $monthlyPemesanan = DB::table('public_orders')
                ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
                ->whereBetween('public_orders.created_at', [$monthStart, $monthEnd])
                ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
                ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

            $bulanan[$monthStart->format('M Y')] = [
                'penjualan' => $monthlyPenjualan,
                'pemesanan' => $monthlyPemesanan,
            ];

            $currentMonth->addMonth();
        }

        return view('reports.income', compact(
            'start',
            'end',
            'totalPenjualan',
            'totalPemesanan',
            'totalPendapatan',
            'harian',
            'mingguan',
            'bulanan',
            'totalCashSale',
            'totalTransferSale',
            'totalDebitSale',
            'totalCashOrder',
            'totalTransferOrder',
            'totalDebitOrder',
            'totalEwalletOrder',
            // cash flow summary
            'totalInflow',
            'totalOutflow',
            'totalCashInflow',
            'totalCashOutflow',
            'totalTransferInflow',
            'totalTransferOutflow',
            'totalDebitInflow',
            'totalDebitOutflow',
            'totalEwalletInflow',
            'totalEwalletOutflow',
            // new breakdown
            'totalCash',
            'totalTransfer',
            'totalDebit',
            'totalEwallet',
            'totalCashflowCash',
            'totalCashflowTransfer',
            'totalCashflowEwallet'
        ));
    }

    // Ekspor laporan stok ke PDF
    public function stockPdf(Request $request)
    {
        try {
            $start = $request->input('start_date', now()->startOfMonth()->toDateString());
            $end = $request->input('end_date', now()->endOfMonth()->toDateString());

            // Get products with categories
            $products = Product::with('category')->get();

            // Get stock logs
            $logs = InventoryLog::with('product')
                ->whereBetween('created_at', [$start, $end])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            // Calculate stock recap (sama seperti di fungsi stock)
            $rekap = [];
            foreach ($products as $product) {
                // Stok keluar karena rusak
                $keluar_rusak = InventoryLog::where('product_id', $product->id)
                    ->where('qty', '<', 0)
                    ->where('source', InventoryLog::SOURCE_DAMAGED)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('qty');
                $masuk = InventoryLog::where('product_id', $product->id)
                    ->where('qty', '>', 0)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('qty');

                // Stok keluar dari sale
                $keluar_sale = InventoryLog::where('product_id', $product->id)
                    ->where('qty', '<', 0)
                    ->where('source', InventoryLog::SOURCE_SALE)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('qty');

                // Stok keluar dari public_order (semua tipe public_order)
                $publicOrderSources = [
                    InventoryLog::SOURCE_PUBLIC_ORDER_PRODUCT,
                    InventoryLog::SOURCE_PUBLIC_ORDER_BOUQUET,
                    InventoryLog::SOURCE_PUBLIC_ORDER_CUSTOM,
                    InventoryLog::SOURCE_PUBLIC_ORDER_HOLD,
                    InventoryLog::SOURCE_PUBLIC_ORDER_BOUQUET_HOLD,
                    InventoryLog::SOURCE_PUBLIC_ORDER_CUSTOM_HOLD,
                ];
                $keluar_public_order = InventoryLog::where('product_id', $product->id)
                    ->where('qty', '<', 0)
                    ->whereIn('source', $publicOrderSources)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('qty');

                // Stok keluar total hanya dari sale + public order
                $keluar = abs($keluar_sale) + abs($keluar_public_order);

                $penyesuaian = InventoryLog::where('product_id', $product->id)
                    ->where('source', InventoryLog::SOURCE_ADJUSTMENT)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('qty');

                $rekap[$product->id] = [
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'keluar_sale' => abs($keluar_sale),
                    'keluar_public_order' => abs($keluar_public_order),
                    'keluar_rusak' => abs($keluar_rusak),
                    'penyesuaian' => $penyesuaian,
                    'stok_akhir' => $product->current_stock,
                ];
            }

            // Load and render PDF
            $pdf = Pdf::loadView('reports.stock_pdf', compact('products', 'logs', 'rekap', 'start', 'end'));
            $pdf->setPaper('a4', 'portrait');

            $filename = "laporan_stok_{$start}_to_{$end}.pdf";
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengexport PDF: ' . $e->getMessage()]);
        }
    }

    // Ekspor laporan pendapatan ke PDF
    public function incomePdf(Request $request)
    {
        try {
            $start = $request->input('start_date', now()->startOfMonth()->toDateString());
            $end = $request->input('end_date', now()->endOfMonth()->toDateString());

            // Total pendapatan dari penjualan
            $totalPenjualan = Sale::whereBetween('created_at', [$start, $end])->sum('total');

            // Total pendapatan dari pemesanan
            $totalPemesanan = DB::table('public_orders')
                ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
                ->whereBetween('public_orders.created_at', [$start, $end])
                ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
                ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

            // Ambil data cash flow (inflow dan outflow) pada periode yang sama
            $totalInflow = \App\Models\CashFlow::where('type', 'inflow')
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount');
            $totalOutflow = \App\Models\CashFlow::where('type', 'outflow')
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount');

            // Breakdown per metode pembayaran untuk cash flow
            $totalCashInflow = \App\Models\CashFlow::where('type', 'inflow')->where('payment_method', 'cash')->whereBetween('transaction_date', [$start, $end])->sum('amount');
            $totalCashOutflow = \App\Models\CashFlow::where('type', 'outflow')->where('payment_method', 'cash')->whereBetween('transaction_date', [$start, $end])->sum('amount');
            $totalTransferInflow = \App\Models\CashFlow::where('type', 'inflow')->where('payment_method', 'transfer')->whereBetween('transaction_date', [$start, $end])->sum('amount');
            $totalTransferOutflow = \App\Models\CashFlow::where('type', 'outflow')->where('payment_method', 'transfer')->whereBetween('transaction_date', [$start, $end])->sum('amount');
            $totalEwalletInflow = \App\Models\CashFlow::where('type', 'inflow')->where('payment_method', 'e-wallet')->whereBetween('transaction_date', [$start, $end])->sum('amount');
            $totalEwalletOutflow = \App\Models\CashFlow::where('type', 'outflow')->where('payment_method', 'e-wallet')->whereBetween('transaction_date', [$start, $end])->sum('amount');

            // Statistik khusus cashflow saja (tanpa sale/order)
            $totalCashflowCash = $totalCashInflow - $totalCashOutflow;
            $totalCashflowTransfer = $totalTransferInflow - $totalTransferOutflow;
            $totalCashflowEwallet = $totalEwalletInflow - $totalEwalletOutflow;

            // Total pendapatan gabungan (penjualan + pemesanan + pemasukan cash flow - pengeluaran cash flow)
            $totalPendapatan = ($totalPenjualan + $totalPemesanan + $totalInflow) - $totalOutflow;

            // Pendapatan harian: hanya tampilkan tanggal yang ada transaksi
            $harian = [];
            $saleDates = Sale::whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date')
                ->groupBy('date')
                ->pluck('date')
                ->toArray();
            $orderDates = DB::table('public_orders')
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('status', ['confirmed', 'processing', 'ready', 'completed'])
                ->selectRaw('DATE(created_at) as date')
                ->groupBy('date')
                ->pluck('date')
                ->toArray();
            $allDates = array_unique(array_merge($saleDates, $orderDates));
            sort($allDates);
            foreach ($allDates as $date) {
                $dailyPenjualan = Sale::whereDate('created_at', $date)->sum('total');
                $dailyPemesanan = DB::table('public_orders')
                    ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
                    ->whereDate('public_orders.created_at', $date)
                    ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
                    ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));
                if ($dailyPenjualan > 0 || $dailyPemesanan > 0) {
                    $harian[$date] = [
                        'penjualan' => $dailyPenjualan,
                        'pemesanan' => $dailyPemesanan,
                    ];
                }
            }

            // Load and render PDF
            $pdf = Pdf::loadView('reports.income_pdf', compact(
                'start',
                'end',
                'totalPenjualan',
                'totalPemesanan',
                'totalPendapatan',
                'harian',
                'totalInflow',
                'totalOutflow',
                'totalCashflowCash',
                'totalCashflowTransfer',
                'totalCashflowEwallet'
            ));
            $pdf->setPaper('a4', 'portrait');

            $filename = "laporan_pendapatan_{$start}_to_{$end}.pdf";
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengexport PDF: ' . $e->getMessage()]);
        }
    }

    // Ekspor laporan pesanan ke PDF
    public function ordersPdf(Request $request)
    {
        try {
            $start = $request->input('start_date', now()->startOfMonth()->toDateString());
            $end = $request->input('end_date', now()->endOfMonth()->toDateString());

            // Get public orders data
            $orders = \App\Models\PublicOrder::with('items.product')
                ->whereBetween('created_at', [$start, $end])
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate statistics
            $totalOrder = $orders->count();
            $totalNominal = $orders->sum(function ($order) {
                return $order->items->sum(function ($item) {
                    return $item->quantity * $item->price;
                });
            });
            // Status yang dianggap "Lunas/Dibayar": paid, processed, completed
            $statusLunas = ['paid', 'processed', 'completed'];
            $totalLunas = $orders->whereIn('status', $statusLunas)->count();

            // Statistik status tambahan
            $totalPending = $orders->where('status', 'pending')->count();
            $totalProcessed = $orders->where('status', 'processed')->count();
            $totalCompleted = $orders->where('status', 'completed')->count();
            $totalCancelled = $orders->where('status', 'cancelled')->count();

            // Statistik pendapatan berdasarkan metode pembayaran
            $totalCashOrder = $orders->where('payment_method', 'cash')->sum('total');
            $totalTransferOrder = $orders->where('payment_method', 'transfer')->sum('total');
            $totalDebitOrder = $orders->where('payment_method', 'debit')->sum('total');
            $totalEwalletOrder = $orders->where('payment_method', 'e-wallet')->sum('total');

            // Load and render PDF
            $pdf = Pdf::loadView('reports.orders_pdf', compact(
                'orders',
                'start',
                'end',
                'totalOrder',
                'totalNominal',
                'totalLunas',
                'totalPending',
                'totalProcessed',
                'totalCompleted',
                'totalCancelled',
                'totalCashOrder',
                'totalTransferOrder',
                'totalDebitOrder',
                'totalEwalletOrder'
            ));
            $pdf->setPaper('a4', 'portrait');

            $filename = "laporan_pesanan_{$start}_to_{$end}.pdf";
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengexport PDF: ' . $e->getMessage()]);
        }
    }

    // Export laporan cashflow ke PDF
    public function cashflowPdf(Request $request)
    {
        \Carbon\Carbon::setLocale('id');
        $month = $request->input('month', now()->format('Y-m'));
        $categoryId = $request->input('category_id');

        $query = \App\Models\CashFlow::with(['category', 'user'])
            ->whereYear('transaction_date', substr($month, 0, 4))
            ->whereMonth('transaction_date', substr($month, 5, 2));
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        $cashFlows = $query->orderBy('transaction_date', 'desc')->get();

        $ownerName = 'Owner Florist';
        $cashFlowsOwner = $cashFlows->filter(function ($cf) use ($ownerName) {
            $name = $cf->user && $cf->user->name ? trim($cf->user->name) : '';
            return strcasecmp($name, $ownerName) === 0;
        });
        $cashFlowsNonOwner = $cashFlows->filter(function ($cf) use ($ownerName) {
            $name = $cf->user && $cf->user->name ? trim($cf->user->name) : '';
            return strcasecmp($name, $ownerName) !== 0;
        });

        $inflowLainOwner = $cashFlowsOwner->where('type', 'inflow')->sum('amount');
        $totalOutflowOwner = $cashFlowsOwner->where('type', 'outflow')->sum('amount');
        $inflowLainNonOwner = $cashFlowsNonOwner->where('type', 'inflow')->sum('amount');
        $totalOutflowNonOwner = $cashFlowsNonOwner->where('type', 'outflow')->sum('amount');

        $year = substr($month, 0, 4);
        $mon = substr($month, 5, 2);
        $startDate = "$year-$mon-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $totalSale = \App\Models\Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total');
        $totalOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$startDate, $endDate])
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));
        $totalPendapatanToko = $totalSale + $totalOrder;

        // Saldo bersih Personal = (pendapatan toko + pemasukan lain personal + pemasukan lain bisnis) - (pengeluaran personal + pengeluaran bisnis)
        $saldoBersihNonOwner = $totalPendapatanToko + $inflowLainNonOwner + $inflowLainOwner - ($totalOutflowNonOwner + $totalOutflowOwner);
        // Saldo bersih Business (Owner)
        $saldoBersihOwner = $totalPendapatanToko + $inflowLainOwner - $totalOutflowOwner;

        // Untuk PDF, tampilkan ringkasan Personal Gabungan dan Business
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.cashflow_pdf', [
            'month' => $month,
            'totalPendapatanToko' => $totalPendapatanToko,
            'totalSale' => $totalSale,
            'totalOrder' => $totalOrder,
            'inflowLainOwner' => $inflowLainOwner,
            'inflowLainNonOwner' => $inflowLainNonOwner,
            'totalOutflowOwner' => $totalOutflowOwner,
            'totalOutflowNonOwner' => $totalOutflowNonOwner,
            'saldoBersihOwner' => $saldoBersihOwner,
            'saldoBersihNonOwner' => $saldoBersihNonOwner,
            'cashFlowsOwner' => $cashFlowsOwner,
            'cashFlowsNonOwner' => $cashFlowsNonOwner,
        ]);
        $pdf->setPaper('a4', 'portrait');
        $filename = 'laporan_cashflow_' . $month . '.pdf';
        return $pdf->download($filename);
    }
}
