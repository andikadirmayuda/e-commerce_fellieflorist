<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with('items.product')->latest()->paginate(20);
        return view('purchase_orders.index', compact('orders'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->get();
        return view('purchase_orders.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'pickup_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:30',
            'items.*.item_notes' => 'nullable|string|max:255',
        ]);

        $order = PurchaseOrder::create([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'pickup_date' => $data['pickup_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? null,
                'item_notes' => $item['item_notes'] ?? null,
            ]);
        }

        return redirect()->route('purchase-orders.index')->with('success', 'Purchase order berhasil disimpan!');
    }

    public function show($id)
    {
        $order = PurchaseOrder::with('items.product')->findOrFail($id);
        return view('purchase_orders.show', compact('order'));
    }

    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);
        $order->items()->delete();
        $order->delete();
        return redirect()->route('purchase-orders.index')->with('success', 'Purchase order berhasil dihapus!');
    }

    public function edit($id)
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);
        $products = Product::where('is_active', true)->get();
        return view('purchase_orders.edit', compact('order', 'products'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'pickup_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:30',
            'items.*.item_notes' => 'nullable|string|max:255',
        ]);

        $order = PurchaseOrder::findOrFail($id);
        $order->update([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'pickup_date' => $data['pickup_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        // Hapus item lama dan buat ulang
        $order->items()->delete();
        foreach ($data['items'] as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? null,
                'item_notes' => $item['item_notes'] ?? null,
            ]);
        }
        return redirect()->route('purchase-orders.index')->with('success', 'Purchase order berhasil diupdate!');
    }

    public function report(Request $request)
    {
        $query = PurchaseOrder::with('items.product')->latest();
        if ($request->filled('start')) {
            $query->where('pickup_date', '>=', $request->start);
        }
        if ($request->filled('end')) {
            $query->where('pickup_date', '<=', $request->end);
        }
        $orders = $query->get();
        return view('reports.porchase_order', compact('orders'));
    }
}
