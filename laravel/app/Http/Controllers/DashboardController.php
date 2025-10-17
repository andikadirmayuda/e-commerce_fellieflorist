<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Role;
use App\Models\User;
use App\Models\PublicOrder;
use App\Models\InventoryLog;
use App\Models\BouquetCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Bouquet Ready Stock & Performance
        $bouquetReadyStock = \App\Models\Bouquet::with(['category'])
            ->whereHas('components', function ($q) {
                $q->whereHas('product', function ($q2) {
                    $q2->where('current_stock', '>', 0);
                });
            })
            ->get();

        // Hitung total penjualan untuk setiap bouquet dari public_order_items
        foreach ($bouquetReadyStock as $bouquet) {
            $soldCount = \App\Models\PublicOrderItem::where('item_type', 'bouquet')
                ->where('bouquet_id', $bouquet->id)
                ->sum('quantity');
            $bouquet->total_sold = $soldCount;
        }

        // Urutkan bouquetReadyStock berdasarkan total_sold (terlaris)
        $bouquetReadyStock = $bouquetReadyStock->sortByDesc('total_sold')->values();
        // Enable query logging
        DB::enableQueryLog();

        $user = Auth::user();

        // Statistik utama
        // Hitung pelanggan online berdasarkan unique wa_number dari PublicOrder, bukan dari tabel Customer
        $totalCustomers = PublicOrder::select('wa_number')
            ->whereNotNull('customer_name')
            ->whereNotNull('wa_number')
            ->where('wa_number', '!=', '')
            ->where('wa_number', '!=', '-')
            ->distinct()
            ->count();

        $totalProducts = Product::count();
        $totalOrders = PublicOrder::whereIn('status', ['pending', 'confirmed', 'processing', 'ready', 'completed'])->count();
        $totalSales = Sale::count(); // Sales menggunakan SoftDeletes, count() otomatis exclude yang deleted

        // Total pendapatan dari Sales dan PublicOrder (hanya bulan & tahun berjalan)
        $salesRevenue = Sale::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        // Hitung pendapatan dari PublicOrder melalui items (quantity * price) hanya bulan & tahun berjalan
        $ordersRevenue = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->whereMonth('public_orders.created_at', now()->month)
            ->whereYear('public_orders.created_at', now()->year)
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        $totalRevenue = $salesRevenue + $ordersRevenue;

        // Ambil semua produk dengan stok > 0
        $readyProducts = Product::with(['category'])
            ->where('current_stock', '>', 0)
            ->orderBy('name')
            ->get();

        // Pesanan terbaru
        $recentOrders = PublicOrder::latest()->take(6)->get();

        // Data grafik penjualan (7 hari terakhir) - PERBAIKAN
        $sales = Sale::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as total')
            ->where('created_at', '>=', now()->subDays(6))
            // Tidak ada filter status karena Sale menggunakan SoftDeletes
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Buat array 7 hari terakhir untuk memastikan semua tanggal tampil
        $last7DaysSales = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateFormatted = now()->subDays($i)->format('d M');
            $saleData = $sales->where('date', $date)->first();
            $count = $saleData->count ?? 0;
            $total = $saleData->total ?? 0;

            $last7DaysSales->push([
                'date' => $dateFormatted,
                'count' => $count,
                'total' => $total
            ]);
        }

        $salesChartData = [
            'labels' => $last7DaysSales->pluck('date')->toArray(),
            'datasets' => [[
                'label' => 'Transaksi Penjualan',
                'data' => $last7DaysSales->pluck('count')->toArray(),
                'backgroundColor' => '#3B82F6',
                'borderColor' => '#3B82F6',
                'fill' => false,
            ]],
        ];

        // Data grafik pesanan (7 hari terakhir) - PERBAIKAN QUERY
        // Gunakan DB query builder langsung untuk menghindari konflik dengan model accessor
        $ordersQuery = DB::table('public_orders')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as order_count')
            ->where('created_at', '>=', now()->subDays(6))
            ->whereIn('status', ['pending', 'confirmed', 'processing', 'ready', 'completed'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Buat array 7 hari terakhir untuk memastikan semua tanggal tampil pada chart pesanan
        $last7DaysOrders = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateFormatted = now()->subDays($i)->format('d M');
            $orderData = $ordersQuery->where('date', $date)->first();
            $count = $orderData->order_count ?? 0;
            $last7DaysOrders->push([
                'date' => $dateFormatted,
                'count' => $count
            ]);
        }

        $ordersChartData = [
            'labels' => $last7DaysOrders->pluck('date')->toArray(),
            'datasets' => [[
                'label' => 'Jumlah Pesanan',
                'data' => $last7DaysOrders->pluck('count')->toArray(),
                'backgroundColor' => '#A21CAF',
                'borderColor' => '#A21CAF',
                'fill' => false,
            ]],
        ];

        // Data untuk Performa Produk (berdasarkan kategori)
        // Hitung penjualan dari sales
        $directSales = DB::table('categories as c')
            ->select([
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('COALESCE(SUM(si.quantity), 0) as total_sold')
            ])
            ->join('products as p', 'p.category_id', '=', 'c.id')
            ->join('sale_items as si', 'si.product_id', '=', 'p.id')
            ->join('sales as s', function ($join) {
                $join->on('s.id', '=', 'si.sale_id')
                    ->whereNull('s.deleted_at');
            })
            ->groupBy('c.id', 'c.name');

        // Hitung penjualan dari public orders
        $onlineOrders = DB::table('categories as c')
            ->select([
                'c.id as category_id',
                'c.name as category_name',
                DB::raw('COALESCE(SUM(poi.quantity), 0) as total_sold')
            ])
            ->join('products as p', 'p.category_id', '=', 'c.id')
            ->join('public_order_items as poi', 'poi.product_id', '=', 'p.id')
            ->join('public_orders as po', function ($join) {
                $join->on('po.id', '=', 'poi.public_order_id')
                    ->whereIn('po.status', ['completed', 'delivered']);
            })
            ->groupBy('c.id', 'c.name');

        // Dapatkan penjualan langsung
        $directSalesResult = $directSales->get();

        // Dapatkan penjualan online
        $onlineOrdersResult = $onlineOrders->get();

        // Gabungkan hasil penjualan langsung dan online
        $salesByCategory = collect();

        // Masukkan data penjualan langsung
        foreach ($directSalesResult as $sale) {
            $salesByCategory->put($sale->category_id, [
                'category_name' => $sale->category_name,
                'total_sold' => $sale->total_sold
            ]);
        }

        // Gabungkan dengan penjualan online
        foreach ($onlineOrdersResult as $order) {
            if ($salesByCategory->has($order->category_id)) {
                // Update existing category
                $current = $salesByCategory->get($order->category_id);
                $salesByCategory->put($order->category_id, [
                    'category_name' => $current['category_name'],
                    'total_sold' => $current['total_sold'] + $order->total_sold
                ]);
            } else {
                // Add new category
                $salesByCategory->put($order->category_id, [
                    'category_name' => $order->category_name,
                    'total_sold' => $order->total_sold
                ]);
            }
        }

        // Filter yang memiliki penjualan dan urutkan
        $salesByCategory = $salesByCategory
            ->filter(function ($value) {
                return $value['total_sold'] > 0;
            })
            ->sortByDesc('total_sold')
            ->values();

        // Debug info
        info('Direct Sales:', $directSalesResult->toArray());
        info('Online Orders:', $onlineOrdersResult->toArray());
        info('Combined Sales:', $salesByCategory->toArray());

        // Debug: tampilkan query yang dijalankan
        Log::info('Query Categories Sales:', [
            'query' => DB::getQueryLog()[count(DB::getQueryLog()) - 1] ?? 'No query logged'
        ]);

        $productsByCategory = collect($salesByCategory);

        // Debug data penjualan per kategori
        Log::info('Product Sales by Category:', $productsByCategory->toArray());

        $productChartData = [
            'labels' => $productsByCategory->pluck('category_name')->toArray(),
            'data' => $productsByCategory->pluck('total_sold')->toArray()
        ];


        // Filter date range for revenue chart
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $month = $request->input('month');
        $year = $request->input('year');

        if ($startDate && $endDate) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
        } elseif ($month && $year) {
            $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
        } else {
            $start = now()->copy()->startOfMonth();
            $end = now()->copy()->endOfMonth();
        }

        $days = $start->diffInDays($end) + 1;

        // Query sales per hari (filtered)
        $sales = Sale::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Query revenue public orders per hari (filtered)
        $revenueQuery = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->selectRaw('DATE(public_orders.created_at) as date, SUM(public_order_items.quantity * public_order_items.price) as revenue')
            ->whereBetween('public_orders.created_at', [$start, $end])
            ->whereIn('public_orders.status', ['pending', 'confirmed', 'processing', 'ready', 'completed'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Buat array seluruh hari di rentang yang dipilih
        $filteredRevenue = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $dateFormatted = $start->copy()->addDays($i)->format('d M');

            $salesData = $sales->where('date', $date)->first();
            $salesTotal = $salesData->total ?? 0;

            $revenueData = $revenueQuery->where('date', $date)->first();
            $revenue = $revenueData->revenue ?? 0;

            $filteredRevenue->push([
                'date' => $dateFormatted,
                'total' => $salesTotal + $revenue
            ]);
        }

        $revenueChartData = [
            'labels' => $filteredRevenue->pluck('date')->toArray(),
            'datasets' => [[
                'label' => 'Pendapatan Harian',
                'data' => $filteredRevenue->pluck('total')->toArray(),
                'backgroundColor' => '#10B981',
                'borderColor' => '#10B981',
                'fill' => false,
            ]],
        ];

        // Data Grafik Alur Kas
        // Perhitungan cashflow untuk dashboard
        $totalSale = Sale::whereNull('deleted_at')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $totalOrder = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->whereIn('public_orders.status', ['confirmed', 'processing', 'ready', 'completed'])
            ->whereMonth('public_orders.created_at', now()->month)
            ->whereYear('public_orders.created_at', now()->year)
            ->sum(DB::raw('public_order_items.quantity * public_order_items.price'));

        $totalPendapatanToko = $totalSale + $totalOrder;

        $inflowLain = DB::table('cash_flows')
            ->where('type', 'inflow')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        $totalOutflow = DB::table('cash_flows')
            ->where('type', 'outflow')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        $saldoBersih = $totalPendapatanToko + $inflowLain - $totalOutflow;


        // Produk ready stock (stok > 0), urutkan berdasarkan total_sold (terlaris)
        $readyProducts = Product::with(['category', 'prices'])
            ->where('current_stock', '>', 0)
            ->get();

        // Hitung penjualan untuk setiap produk
        foreach ($readyProducts as $product) {
            // Hitung total penjualan dari sale_items
            $soldInSales = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $product->id)
                ->whereNull('sales.deleted_at')
                ->sum('sale_items.quantity');

            // Hitung total penjualan dari public_orders
            $soldInOrders = DB::table('public_order_items')
                ->join('public_orders', 'public_orders.id', '=', 'public_order_items.public_order_id')
                ->where('public_order_items.product_id', $product->id)
                ->whereIn('public_orders.status', ['completed', 'delivered'])
                ->sum('public_order_items.quantity');

            $totalSold = $soldInSales + $soldInOrders;
            $product->total_sold = $totalSold;
        }

        // Urutkan readyProducts berdasarkan total_sold (terlaris)
        $readyProducts = $readyProducts->sortByDesc('total_sold')->values();

        // Data untuk Performa Produk (berdasarkan kategori)
        $productPerformance = DB::table('categories')
            ->select(
                'categories.name as category_name',
                DB::raw('COALESCE(SUM(DISTINCT sale_items.quantity), 0) + COALESCE(SUM(DISTINCT order_items.quantity), 0) as total_sold')
            )
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->leftJoin(DB::raw('(
                SELECT sale_items.product_id, sale_items.quantity
                FROM sale_items
                JOIN sales ON sales.id = sale_items.sale_id
                WHERE sales.deleted_at IS NULL
            ) as sale_items'), 'products.id', '=', 'sale_items.product_id')
            ->leftJoin(DB::raw('(
                SELECT public_order_items.product_id, public_order_items.quantity
                FROM public_order_items
                JOIN public_orders ON public_orders.id = public_order_items.public_order_id
                WHERE public_orders.status IN ("completed", "delivered")
            ) as order_items'), 'products.id', '=', 'order_items.product_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc(DB::raw('total_sold'))
            ->take(5)
            ->get();

        $productChartData = [
            'labels' => $productPerformance->pluck('category_name')->toArray(),
            'data' => $productPerformance->pluck('total_sold')->toArray(),
            'tooltip' => $productPerformance->map(function ($item) {
                return "{$item->category_name}: {$item->total_sold} terjual";
            })->toArray()
        ];

        // Data untuk Performa Bouquet - penjualan dari public_order_items
        $bouquetCategorySales = \App\Models\BouquetCategory::with(['bouquets'])->get()->map(function ($category) {
            $totalSold = 0;
            foreach ($category->bouquets as $bouquet) {
                $sold = \App\Models\PublicOrderItem::where('item_type', 'bouquet')
                    ->where('bouquet_id', $bouquet->id)
                    ->sum('quantity');
                $totalSold += $sold;
            }
            return [
                'category_name' => $category->name,
                'total_sold' => $totalSold
            ];
        })->filter(function ($item) {
            return $item['total_sold'] > 0;
        })->sortByDesc('total_sold')->take(5)->values();

        // Jika tidak ada data penjualan bouquet, fallback ke semua kategori yang tersedia
        if ($bouquetCategorySales->isEmpty()) {
            $bouquetCategorySales = \App\Models\BouquetCategory::with(['bouquets'])->get()->map(function ($category) {
                $totalStock = $category->bouquets->count();
                return [
                    'category_name' => $category->name,
                    'total_stock' => $totalStock
                ];
            })->sortByDesc('total_stock')->take(5)->values();
        }

        $bouquetChartData = [
            'labels' => $bouquetCategorySales->pluck('category_name')->toArray(),
            'data' => $bouquetCategorySales->pluck(isset($bouquetCategorySales->first()['total_sold']) ? 'total_sold' : 'total_stock')->toArray(),
            'tooltip' => $bouquetCategorySales->map(function ($item) {
                $metric = isset($item['total_sold']) ? 'terjual' : 'stok';
                $value = isset($item['total_sold']) ? $item['total_sold'] : $item['total_stock'];
                return "{$item['category_name']}: {$value} {$metric}";
            })->toArray()
        ];

        // Debug: Uncomment untuk debugging
        // Performa Pendapatan Pertahun (per bulan)
        $year = now()->year;
        $monthlySales = Sale::selectRaw('MONTH(created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->get();

        $monthlyOrders = DB::table('public_orders')
            ->join('public_order_items', 'public_orders.id', '=', 'public_order_items.public_order_id')
            ->selectRaw('MONTH(public_orders.created_at) as month, SUM(public_order_items.quantity * public_order_items.price) as revenue')
            ->whereYear('public_orders.created_at', $year)
            ->whereIn('public_orders.status', ['pending', 'confirmed', 'processing', 'ready', 'completed'])
            ->groupBy('month')
            ->get();

        $months = collect(range(1, 12));
        $monthLabels = $months->map(function ($m) {
            return \Carbon\Carbon::create()->month($m)->translatedFormat('F');
        })->toArray();
        $monthlyTotals = $months->map(function ($m) use ($monthlySales, $monthlyOrders) {
            $sales = $monthlySales->where('month', $m)->first();
            $orders = $monthlyOrders->where('month', $m)->first();
            $salesTotal = $sales->total ?? 0;
            $ordersTotal = $orders->revenue ?? 0;
            return $salesTotal + $ordersTotal;
        })->toArray();

        $yearlyRevenueChartData = [
            'labels' => $monthLabels,
            'datasets' => [[
                'label' => 'Pendapatan Bulanan',
                'data' => $monthlyTotals,
                'backgroundColor' => '#F472B6',
                'borderColor' => '#EC4899',
                'fill' => true,
            ]],
        ];
        // dd([
        //     'bouquet_category_sales' => $bouquetCategorySales,
        //     'bouquet_chart_data' => $bouquetChartData,
        //     'all_categories' => BouquetCategory::with('bouquets')->get(),
        //     'bouquet_order_items_sample' => BouquetOrderItem::with(['bouquet.category'])->take(5)->get()
        // ]);

        // Hitung penjualan untuk setiap produk
        foreach ($readyProducts as $product) {
            // Hitung total penjualan dari sale_items
            $soldInSales = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $product->id)
                ->whereNull('sales.deleted_at')
                ->sum('sale_items.quantity');

            // Hitung total penjualan dari public_orders
            $soldInOrders = DB::table('public_order_items')
                ->join('public_orders', 'public_orders.id', '=', 'public_order_items.public_order_id')
                ->where('public_order_items.product_id', $product->id)
                ->whereIn('public_orders.status', ['completed', 'delivered'])
                ->sum('public_order_items.quantity');

            $totalSold = $soldInSales + $soldInOrders;
            $product->total_sold = $totalSold;
        }

        $data = compact(
            'user',
            'totalCustomers',
            'totalProducts',
            'totalOrders',
            'totalSales',
            'totalRevenue',
            'recentOrders',
            'salesChartData',
            'ordersChartData',
            'revenueChartData',
            'readyProducts',
            'productChartData',
            'bouquetChartData',
            'bouquetReadyStock',
            'totalPendapatanToko',
            'totalSale',
            'totalOrder',
            'inflowLain',
            'totalOutflow',
            'saldoBersih',
            'yearlyRevenueChartData'
        );

        // Selalu arahkan ke dashboard utama
        return view('dashboard', $data);
    }
}
