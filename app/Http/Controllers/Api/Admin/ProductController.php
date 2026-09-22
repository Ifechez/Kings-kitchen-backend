<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\VercelBlobStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(['products' => Product::with('category')->orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_available' => ['boolean'],
            'is_hot' => ['boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

        if ($request->hasFile('image')) {
            // The container's filesystem isn't durable between requests on Vercel,
            // so images go to Vercel Blob instead of a local disk. We store the
            // full public URL Blob returns directly in `image` — see the
            // full_image_url accessor on the Product model.
            $data['image'] = app(VercelBlobStorage::class)->upload($request->file('image'));
        }

        $product = Product::create($data);

        return response()->json(['product' => $product], 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_available' => ['boolean'],
            'is_hot' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            $blob = app(VercelBlobStorage::class);
            // Best-effort cleanup of the old image — a failed delete shouldn't block the update.
            if ($product->image && str_starts_with($product->image, 'http')) {
                try {
                    $blob->delete($product->image);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $data['image'] = $blob->upload($request->file('image'));
        }

        $product->update($data);

        return response()->json(['product' => $product]);
    }

    public function destroy(Product $product)
    {
        if ($product->image && str_starts_with($product->image, 'http')) {
            try {
                app(VercelBlobStorage::class)->delete($product->image);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $product->delete();
        return response()->json(['message' => 'Product removed.']);
    }
}
