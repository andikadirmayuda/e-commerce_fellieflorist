<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashFlowController extends Controller
{
    // Tampilkan daftar cash flow (dengan filter tanggal/kategori jika ada)
    public function index(Request $request)
    {
        $query = CashFlow::with(['category', 'user']);
        if ($request->filled('date')) {
            $query->where('transaction_date', $request->date);
        }
        if ($request->filled('month')) {
            $query->whereMonth('transaction_date', $request->month);
        }
        if ($request->filled('year')) {
            $query->whereYear('transaction_date', $request->year);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        $cashFlows = $query->orderBy('transaction_date', 'desc')->paginate(20);
        $categories = CashFlowCategory::all();
        return view('cashflow.index', compact('cashFlows', 'categories'));
    }

    // Form tambah cash flow
    public function create()
    {
        $categories = CashFlowCategory::all();
        return view('cashflow.create', compact('categories'));
    }

    // Simpan cash flow baru
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:cash_flow_categories,id',
            'type' => 'required|in:inflow,outflow',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'required|in:cash,transfer,ewallet',
            'transaction_date' => 'required|date',
        ]);
        CashFlow::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'payment_method' => $request->payment_method,
            'transaction_date' => $request->transaction_date,
        ]);
        return redirect()->route('cashflow.index')->with('success', 'Transaksi berhasil disimpan.');
    }

    // Form edit cash flow
    public function edit($id)
    {
        $cashFlow = CashFlow::findOrFail($id);
        $categories = CashFlowCategory::all();
        return view('cashflow.edit', compact('cashFlow', 'categories'));
    }

    // Update cash flow
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:cash_flow_categories,id',
            'type' => 'required|in:inflow,outflow',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'payment_method' => 'required|in:cash,transfer,ewallet',
            'transaction_date' => 'required|date',
        ]);
        $cashFlow = CashFlow::findOrFail($id);
        $cashFlow->update([
            'category_id' => $request->category_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'payment_method' => $request->payment_method,
            'transaction_date' => $request->transaction_date,
        ]);
        return redirect()->route('cashflow.index')->with('success', 'Transaksi berhasil diupdate.');
    }

    // Hapus cash flow
    public function destroy($id)
    {
        $cashFlow = CashFlow::findOrFail($id);
        $cashFlow->delete();
        return redirect()->route('cashflow.index')->with('success', 'Transaksi berhasil dihapus.');
    }
}
