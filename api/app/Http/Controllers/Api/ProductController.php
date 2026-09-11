<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $products = Product::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%"))
            ->latest()
            ->paginate(25);

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:80'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
        ]);
        $validated['status'] = $this->statusFor((int) ($validated['stock'] ?? 0), (int) ($validated['reorder_level'] ?? 5));

        return response()->json(Product::create($validated), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'category' => ['sometimes', 'string', 'max:80'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
        ]);
        $stock = (int) ($validated['stock'] ?? $product->stock);
        $reorder = (int) ($validated['reorder_level'] ?? $product->reorder_level);
        $validated['status'] = $this->statusFor($stock, $reorder);
        $product->update($validated);

        return response()->json($product->refresh());
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->update(['status' => 'Archived']);
        return response()->json(['message' => 'Product archived']);
    }

    private function statusFor(int $stock, int $reorder): string
    {
        return $stock === 0 ? 'Out of Stock' : ($stock <= $reorder ? 'Low Stock' : 'Active');
    }
}
