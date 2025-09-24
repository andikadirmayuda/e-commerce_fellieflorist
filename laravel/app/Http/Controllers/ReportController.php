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

        // Rekap stok masuk, keluar, penyesuaian, dan total per produk
        $rekap = [];
        foreach ($products as $product) {
            $masuk = InventoryLog::where('product_id', $product->id)
                ->where('qty', '>', 0)
                ->whereBetween('created_at', [$start, $end])
                ->sum('qty');
            $keluar = InventoryLog::where('product_id', $product->id)
                ->where('qty', '<', 0)
                ->whereBetween('created_at', [$start, $end])
                ->sum('qty');
            $penyesuaian = InventoryLog::where('product_id', $product->id)
                ->where('source', 'adjustment')
                ->whereBetween('created_at', [$start, $end])
                ->sum('qty');
            $rekap[$product->id] = [
                'masuk' => $masuk,
                'keluar' => abs($keluar),
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

        // Status untuk public order - mapping yang benar berdasarkan status yang ada
        // Status yang dianggap "Lunas/Dibayar": paid, processed, completed
        // Status yang dianggap "Belum Lunas": pending, unpaid
        $statusLunas = ['paid', 'processed', 'completed'];
        $statusBelumLunas = ['pending', 'unpaid'];

        $totalLunas = $orders->whereIn('status', $statusLunas)->count();
        $totalBelumLunas = $orders->whereIn('status', $statusBelumLunas)->count();

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
            'totalLunas',
            'totalBelumLunas',
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

        // Total pendapatan dari pemesanan (hitung dari items menggunakan join)
        $totalPemesanan = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        // Total per metode pembayaran di public order (hitung dari items)
        $totalCashOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->where('public_orders.payment_method', 'cash')
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        $totalTransferOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->where('public_orders.payment_method', 'transfer')
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        $totalDebitOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->where('public_orders.payment_method', 'debit')
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        $totalEwalletOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->where('public_orders.payment_method', 'e-wallet')
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        // Total pendapatan gabungan
        $totalPendapatan = $totalPenjualan + $totalPemesanan;

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
            'totalEwalletOrder'
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

            // Calculate stock recap
            $rekap = [];
            foreach ($products as $product) {
                $masuk = InventoryLog::where('product_id', $product->id)
                    ->where('type', 'masuk')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('qty');

                $keluar = InventoryLog::where('product_id', $product->id)
                    ->where('type', 'keluar')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('qty');

                $penyesuaian = InventoryLog::where('product_id', $product->id)
                    ->where('type', 'penyesuaian')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('qty');

                $rekap[$product->id] = [
                    'masuk' => $masuk,
                    'keluar' => abs($keluar),
                    'penyesuaian' => $penyesuaian,
                    'stok_akhir' => $product->current_stock
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

            $totalPendapatan = $totalPenjualan + $totalPemesanan;

            // Pendapatan harian
            $harian = [];
            foreach (range(0, now()->parse($end)->diffInDays(now()->parse($start))) as $i) {
                $date = now()->parse($start)->copy()->addDays($i)->toDateString();

                $dailyPenjualan = Sale::whereDate('created_at', $date)->sum('total');
                $dailyPemesanan = DB::table('public_orders')
                    ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
                    ->whereDate('public_orders.created_at', $date)
                    ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
                    ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

                $harian[$date] = [
                    'penjualan' => $dailyPenjualan,
                    'pemesanan' => $dailyPemesanan,
                ];
            }

            // Load and render PDF
            $pdf = Pdf::loadView('reports.income_pdf', compact('start', 'end', 'totalPenjualan', 'totalPemesanan', 'totalPendapatan', 'harian'));
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
}
