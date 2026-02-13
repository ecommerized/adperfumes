<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CheckoutCalculator;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $calculator;

    public function __construct(CheckoutCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Display cart page
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $totals = $this->calculateCartTotals($cart);

        return view('cart.index', compact('cart', 'totals'));
    }

    /**
     * Add product to cart
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);

        $cart = session()->get('cart', []);

        // If product exists in cart, update quantity
        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] += $request->quantity;
        } else {
            // Add new product to cart
            $cart[$product->id] = [
                'product_id' => $product->id,
                'merchant_id' => $product->merchant_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'quantity' => $request->quantity,
                'image' => $product->image,
                'brand' => $product->brand->name ?? 'Unknown'
            ];
        }

        session()->put('cart', $cart);

        return redirect()->back()->with('success', 'Product added to cart successfully!');
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = session()->get('cart', []);

        if (isset($cart[$request->product_id])) {
            $cart[$request->product_id]['quantity'] = $request->quantity;
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated successfully!');
    }

    /**
     * Remove item from cart
     */
    public function remove($id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Item removed from cart!');
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        session()->forget('cart');

        return redirect()->route('cart.index')->with('success', 'Cart cleared successfully!');
    }

    /**
     * Calculate cart totals using CheckoutCalculator service
     */
    private function calculateCartTotals(array $cart): array
    {
        $cartItems = array_values($cart);

        return $this->calculator->calculateTotals($cartItems);
    }
}
