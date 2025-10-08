<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Bouquet;
use App\Models\BouquetSize;
use App\Models\BouquetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PublicFlowerController extends Controller
{
    public function index()
    {
        // Tampilkan semua produk bunga, termasuk yang stoknya 0.
        // Urutkan: yang ada stok dulu, lalu habis, kemudian berdasarkan nama.

        $flowers = Product::with(['category', 'prices'])
            ->get()
            ->sortBy(function ($flower) {
                // 1. Produk dengan harga promo tampil paling atas
                $hasPromo = $flower->prices->where('type', 'promo')->isNotEmpty() ? 0 : 1;
                // 2. Stok tersedia (current_stock > 0) tampil sebelum stok habis
                $stockOrder = $flower->current_stock > 0 ? 0 : 1;
                // 3. Harga termurah
                $minPrice = $flower->prices->min('price') ?? 999999999;
                // Gabungkan jadi array untuk multi-level sorting
                return [$hasPromo, $stockOrder, $minPrice];
            })->values();

        $lastUpdated = Product::max('updated_at') ?? now();

        $activeTab = request()->query('tab', 'flowers');

        // Ambil semua kategori bunga yang digunakan oleh produk
        $flowerCategories = \App\Models\Category::whereIn('id', $flowers->pluck('category_id')->unique()->filter())->orderBy('name')->get();

        // Jika tab adalah bouquets, ambil data bouquet juga (untuk backward compatibility)
        $bouquetData = [];
        if ($activeTab === 'bouquets') {
            $bouquetController = new PublicBouquetController();
            $bouquetData = $bouquetController->getBouquetData();
            // Update last updated untuk include bouquet data
            $lastUpdated = max($lastUpdated, Bouquet::max('updated_at') ?? now());
        }

        return view('public.flowers', array_merge([
            'flowers' => $flowers,
            'flowerCategories' => $flowerCategories,
            'lastUpdated' => $lastUpdated,
            'activeTab' => $activeTab
        ], $bouquetData));
    }

    public function getFlowerData()
    {
        // Method untuk mendapatkan data flowers yang bisa dipanggil dari controller lain
        // Sertakan juga produk dengan stok 0 agar UI bisa menandai "Habis".

        $flowers = Product::with(['category', 'prices'])
            ->orderByRaw('(current_stock > 0) desc')
            ->get()
            ->sortBy(function ($flower) {
                return $flower->prices->min('price') ?? 999999999;
            })->values();

        return [
            'flowers' => $flowers,
        ];
    }

    /**
     * API: Ambil stok terbaru produk bunga
     */
    public function getStock($id)
    {
        $flower = \App\Models\Product::find($id);
        if (!$flower) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json([
            'id' => $flower->id,
            'current_stock' => $flower->current_stock,
            'base_unit' => $flower->base_unit,
        ]);
    }
}
