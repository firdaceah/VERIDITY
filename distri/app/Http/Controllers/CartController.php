<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index()
    {
        return view('distri.cart', [
            'items' => $this->cartItems(),
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $quantity = $validated['quantity'] ?? 1;
        $existing = DB::table('cart_items')
            ->where('user_id', Auth::id())
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existing) {
            DB::table('cart_items')->where('id', $existing->id)->update([
                'quantity' => $existing->quantity + $quantity,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('cart_items')->insert([
                'user_id' => Auth::id(),
                'product_id' => $validated['product_id'],
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($request->boolean('redirect_checkout')) {
            return redirect()->route('distri.checkout', 'cart');
        }

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:1']);

        DB::table('cart_items')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['quantity' => $validated['quantity'], 'updated_at' => now()]);

        return back();
    }

    public function destroy($id)
    {
        DB::table('cart_items')->where('id', $id)->where('user_id', Auth::id())->delete();

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }

    private function cartItems()
    {
        return DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('cart_items.user_id', Auth::id())
            ->select('cart_items.*', 'products.name', 'products.price', 'products.image', 'products.image_url', 'products.stock', 'categories.name as category_name')
            ->orderBy('cart_items.created_at', 'desc')
            ->get();
    }
}
