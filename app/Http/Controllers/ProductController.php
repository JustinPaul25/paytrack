<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');
        $search = $request->input('search');
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        $products = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        // Calculate statistics
        $totalProducts = Product::count();
        $totalStock = Product::sum('stock');
        $lowStockItems = Product::where('stock', '<=', 10)->count(); // Consider items with 10 or less as low stock
        
        // Calculate total value (selling_price is stored in cents, so we divide by 100)
        $totalValue = Product::sum(\DB::raw('stock * (selling_price / 100)'));

        return inertia('products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'totalProducts' => $totalProducts,
                'totalStock' => $totalStock,
                'lowStockItems' => $lowStockItems,
                'totalValue' => $totalValue,
            ],
        ]);
    }

    public function create()
    {
        $categories = Category::all(['id', 'name']);
        return inertia('products/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'SKU' => 'required|string|max:255|unique:products,SKU',
            'image' => 'nullable|image|max:20048',
        ]);
        $product = Product::create($validated);
        if ($request->hasFile('image')) {
            $product->addMediaFromRequest('image')->toMediaCollection('images');
        }
        return redirect()->route('products.index');
    }

    public function show(Product $product)
    {
        $product->load('category');
        return $product;
    }

    public function edit(Product $product)
    {
        $categories = Category::all(['id', 'name']);
        $product->load('media');
        return inertia('products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'image_url' => $product->getFirstMediaUrl('images'),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'SKU' => 'required|string|max:255|unique:products,SKU,' . $product->id,
            'image' => 'nullable|image|max:20048',
        ]);
        $product->update($validated);
        if ($request->hasFile('image')) {
            $product->clearMediaCollection('images');
            $product->addMediaFromRequest('image')->toMediaCollection('images');
        }
        return redirect()->route('products.index');
    }

    public function destroy(Product $product)
    {
        $product->delete();
    }
} 