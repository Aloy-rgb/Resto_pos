<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        return view('catalog.index', [
            'categories' => Category::withCount('menuItems')->orderBy('name')->get(),
            'menuItems' => MenuItem::with('category')->orderBy('name')->get(),
            'tables' => DiningTable::orderBy('table_number')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:categories,name'], 'description' => ['nullable', 'string', 'max:5000']]);
        Category::create($data);
        return back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id], 'description' => ['nullable', 'string', 'max:5000']]);
        $category->update($data);
        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        abort_if($category->menuItems()->exists(), 422, 'Move or delete this category’s menu items first.');
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function storeMenuItem(Request $request): RedirectResponse
    {
        MenuItem::create($this->menuItemData($request));
        return back()->with('success', 'Menu item created.');
    }

    public function updateMenuItem(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $menuItem->update($this->menuItemData($request));
        return back()->with('success', 'Menu item updated.');
    }

    public function destroyMenuItem(MenuItem $menuItem): RedirectResponse
    {
        abort_if($menuItem->orderItems()->exists(), 422, 'This item is in order history. Mark it unavailable instead.');
        $menuItem->delete();
        return back()->with('success', 'Menu item deleted.');
    }

    public function storeTable(Request $request): RedirectResponse
    {
        DiningTable::create($this->tableData($request));
        return back()->with('success', 'Dining table created.');
    }

    public function updateTable(Request $request, DiningTable $diningTable): RedirectResponse
    {
        $data = $this->tableData($request, $diningTable);
        unset($data['status']);
        $diningTable->update($data);
        return back()->with('success', 'Dining table updated.');
    }

    public function destroyTable(DiningTable $diningTable): RedirectResponse
    {
        abort_if($diningTable->orders()->exists(), 422, 'This table appears in order history and cannot be deleted.');
        $diningTable->delete();
        return back()->with('success', 'Dining table deleted.');
    }

    private function menuItemData(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'current_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'is_available' => ['sometimes', 'boolean'],
        ]);
        $data['is_available'] = $request->boolean('is_available');
        return $data;
    }

    private function tableData(Request $request, ?DiningTable $table = null): array
    {
        return $request->validate([
            'table_number' => ['required', 'integer', 'min:1', 'max:999', 'unique:dining_tables,table_number'.($table ? ','.$table->id : '')],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
