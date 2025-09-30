<?php

namespace App\Http\Controllers;

use App\Models\CashFlowCategory;
use Illuminate\Http\Request;

class CashFlowCategoryController extends Controller
{
    public function index()
    {
        $categories = CashFlowCategory::all();
        return view('cashflow_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('cashflow_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        CashFlowCategory::create($request->only('name'));
        return redirect()->route('cashflow-categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $category = CashFlowCategory::findOrFail($id);
        return view('cashflow_categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $category = CashFlowCategory::findOrFail($id);
        $category->update($request->only('name'));
        return redirect()->route('cashflow-categories.index')->with('success', 'Kategori berhasil diupdate.');
    }

    public function destroy($id)
    {
        $category = CashFlowCategory::findOrFail($id);
        $category->delete();
        return redirect()->route('cashflow-categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
