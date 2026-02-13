<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function returnPolicy()
    {
        return view('pages.return-policy');
    }

    public function shippingPolicy()
    {
        return view('pages.shipping-policy');
    }

    public function privacyPolicy()
    {
        return view('pages.privacy-policy');
    }

    public function wholesale()
    {
        return view('pages.wholesale');
    }

    public function flashSale()
    {
        $products = Product::with('brand')
            ->where('on_sale', true)
            ->whereNotNull('original_price')
            ->where('original_price', '>', 0)
            ->whereColumn('price', '<', 'original_price')
            ->orderByRaw('((original_price - price) / original_price) DESC')
            ->paginate(48);

        return view('pages.flash-sale', compact('products'));
    }

    public function giftCards()
    {
        return view('pages.gift-cards');
    }

    public function contactSubmit(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:2000',
        ]);

        // TODO: Send email notification to admin
        // TODO: Store in database if needed

        return redirect()->route('contact')->with('success', 'Thank you for contacting us! We will get back to you soon.');
    }

    public function wholesaleSubmit(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string|max:500',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
        ]);

        // TODO: Send email notification to admin
        // TODO: Store in database if needed

        return redirect()->route('wholesale')->with('success', 'Thank you for your wholesale inquiry! Our team will contact you within 24-48 hours.');
    }
}
