<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Traits\HandlesDeletionRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use HandlesDeletionRequests;
    public function index(Request $request)
    {
        $query = Product::with('category');
        $search = $request->input('search');
        $lowStock = $request->input('low_stock');
        $categoryId = $request->input('category_id');
        $stockFilter = $request->input('stock_filter'); // 'highest' or 'lowest'
        $sortBy = $request->input('sort_by', 'updated_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Validate sort parameters
        $allowedSortFields = ['name', 'stock', 'initial_stock', 'selling_price', 'updated_at'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'updated_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('SKU', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($categoryQuery) use ($search) {
                      $categoryQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by category if requested
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        // Filter by low stock if requested
        if ($lowStock) {
            $query->where('stock', '<=', 10);
        }
        
        // Filter by stock level (highest/lowest)
        // When stock_filter is active, ensure we sort by stock
        if ($stockFilter === 'highest') {
            // Sort by stock descending to show highest stock first
            $sortBy = 'stock';
            $sortOrder = 'desc';
        } elseif ($stockFilter === 'lowest') {
            // Sort by stock ascending to show lowest stock first
            $sortBy = 'stock';
            $sortOrder = 'asc';
        }
        
        $products = $query->orderBy($sortBy, $sortOrder)->paginate(10)->withQueryString();

        // Calculate statistics
        $totalProducts = Product::count();
        $totalStock = Product::sum('stock');
        $lowStockItems = Product::where('stock', '<=', 10)->count(); // Consider items with 10 or less as low stock
        
        // Calculate total value (selling_price is stored in cents, so we divide by 100)
        $totalValue = Product::sum(\DB::raw('stock * (selling_price / 100)'));
        
        // Get all categories for the dropdown
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return inertia('products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'low_stock' => $lowStock,
                'category_id' => $categoryId,
                'stock_filter' => $stockFilter,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
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
            'unit' => 'required|string|in:pcs,set,box',
            'SKU' => 'nullable|string|max:255|unique:products,SKU,NULL,id,deleted_at,NULL',
            'image' => 'nullable|image|max:20048',
        ]);
        
        // Set initial_stock to stock value when creating a new product
        $validated['initial_stock'] = $validated['stock'];
        
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
            'unit' => 'required|string|in:pcs,set,box',
            'SKU' => 'nullable|string|max:255|unique:products,SKU,' . $product->id . ',id,deleted_at,NULL',
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
        return $this->handleDeletion(
            $product,
            'product',
            request()->input('reason'),
            route('products.index')
        );
    }

    /**
     * Inertia page: list soft-deleted products.
     */
    public function trashedIndex(Request $request)
    {
        $search = $request->input('search');
        $query = Product::onlyTrashed()->with('category');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('SKU', 'like', "%{$search}%");
            });
        }
        $products = $query->orderBy('deleted_at', 'desc')->paginate(10)->withQueryString();

        return inertia('products/Trashed', [
            'products' => $products,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Return a paginated list of soft-deleted products.
     */
    public function trashed(Request $request)
    {
        $perPage = (int) ($request->input('per_page') ?? 10);
        $products = Product::onlyTrashed()
            ->with('category')
            ->orderBy('deleted_at', 'desc')
            ->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->back()->with('success', 'Product restored successfully.');
    }

    /**
     * Add stock to a product (increments both initial_stock and stock, and logs the movement)
     */
    public function addStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $quantity = (int) $validated['quantity'];
        $beforeStock = (int) $product->stock;
        $beforeInitialStock = (int) $product->initial_stock;

        // Increment both initial_stock and stock
        $product->increment('initial_stock', $quantity);
        $product->increment('stock', $quantity);

        // Refresh to get updated values
        $product->refresh();

        // Log the stock movement
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => 'adjustment',
            'quantity' => $quantity,
            'quantity_before' => $beforeStock,
            'quantity_after' => $product->stock,
            'notes' => $validated['notes'] ?? "Stock added: {$quantity} units",
        ]);

        return response()->json([
            'success' => true,
            'message' => "Successfully added {$quantity} units to stock.",
            'product' => [
                'stock' => $product->stock,
                'initial_stock' => $product->initial_stock,
            ],
        ]);
    }

    /**
     * Display stock history for a product
     */
    public function stockHistory(Request $request, Product $product)
    {
        $type = $request->input('type');
        $search = $request->input('search');

        $query = StockMovement::where('product_id', $product->id)
            ->with(['user', 'invoice', 'refund'])
            ->orderBy('created_at', 'desc');

        // Filter by type if specified
        if ($type) {
            $query->where('type', $type);
        }

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $movements = $query->paginate(20)->withQueryString();

        // Get unique types for filter dropdown
        $types = StockMovement::where('product_id', $product->id)
            ->distinct()
            ->pluck('type')
            ->sort()
            ->values();

        return inertia('products/StockHistory', [
            'product' => $product->load('category'),
            'movements' => $movements,
            'filters' => [
                'type' => $type,
                'search' => $search,
            ],
            'types' => $types,
        ]);
    }
} 