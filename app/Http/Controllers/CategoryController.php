<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();
        $search = $request->input('search');
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        $categories = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        return inertia('categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        $categories = Category::all(['id', 'name']);
        return inertia('categories/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        $category = Category::create($validated);
        return redirect()->route('categories.index');
    }

    public function show(Category $category)
    {
        return $category;
    }

    public function edit(Category $category)
    {
        $categories = Category::where('id', '!=', $category->id)->get(['id', 'name']);
        return inertia('categories/Edit', [
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        $category->update($validated);

        // Redirect to categories index with a success message for Inertia
        return redirect()->route('categories.index');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->noContent();
    }
} 