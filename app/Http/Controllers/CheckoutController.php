<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Payments\TapPayment;
use App\Payments\TabbyPayment;
use App\Payments\TamaraPayment;
use App\Services\CheckoutCalculator;
use App\Services\CommissionService;
use App\Services\DiscountService;
use App\Services\SettingsService;
use App\Services\Shipping\AramexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    protected $calculator;
    protected $aramexService;
    protected $tapPayment;
    protected $tabbyPayment;
    protected $tamaraPayment;
    protected $discountService;
    protected $settingsService;
    protected $commissionService;

    public function __construct(
        CheckoutCalculator $calculator,
        AramexService $aramexService,
        TapPayment $tapPayment,
        TabbyPayment $tabbyPayment,
        TamaraPayment $tamaraPayment,
        DiscountService $discountService,
        SettingsService $settingsService,
        CommissionService $commissionService
    ) {
        $this->calculator = $calculator;
        $this->aramexService = $aramexService;
        $this->tapPayment = $tapPayment;
        $this->tabbyPayment = $tabbyPayment;
        $this->tamaraPayment = $tamaraPayment;
        $this->discountService = $discountService;
        $this->settingsService = $settingsService;
        $this->commissionService = $commissionService;
    }

    /**
     * Display one-page guest checkout
     */
    public function index()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        // Calculate shipping
        $shippingRate = $this->aramexService->calculateShippingRate([], 1.0);

        // Calculate totals
        $cartItems = array_values($cart);
        $totals = $this->calculator->calculateTotals($cartItems, $shippingRate['rate']);

        // Get enabled payment methods from admin settings
        $enabledPaymentMethods = $this->getEnabledPaymentMethods();

        return view('checkout.index', compact('cart', 'totals', 'shippingRate', 'enabledPaymentMethods'));
    }

    /**
     * Process checkout and create order
     */
    public function process(Request $request)
    {
        // Build allowed payment methods dynamically from admin settings
        $enabledPaymentMethods = $this->getEnabledPaymentMethods();
        $allowedMethods = implode(',', array_keys($enabledPaymentMethods));

        $validated = $request->validate([
            'email' => 'required|email',
            'phone' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'city' => 'required',
            'country' => 'required',
            'postal_code' => 'nullable',
            'discount_code' => 'nullable|string',
            'payment_method' => 'nullable|string|in:' . $allowedMethods,
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        try {
            DB::beginTransaction();

            // Calculate totals
            $cartItems = array_values($cart);
            $shippingRate = $this->aramexService->calculateShippingRate($validated, 1.0);
            $totals = $this->calculator->calculateTotals($cartItems, $shippingRate['rate'], $request->discount_code);

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'country' => $validated['country'],
                'postal_code' => $validated['postal_code'] ?? null,
                'subtotal' => $totals['subtotal'],
                'shipping' => $totals['shipping'],
                'discount' => $totals['discount'],
                'grand_total' => $totals['grand_total'],
                'currency' => 'AED',
                'discount_code' => $validated['discount_code'] ?? null,
                'payment_method' => $validated['payment_method'] ?? 'tap',
                'payment_status' => 'pending',
                'shipping_method' => 'aramex',
                'status' => 'pending',
            ]);

            // Create order items with commission calculation via CommissionService
            foreach ($cart as $productId => $item) {
                $merchantId = $item['merchant_id'] ?? null;
                $commissionRate = null;
                $commissionAmount = 0;
                $subtotal = $item['price'] * $item['quantity'];

                if ($merchantId) {
                    $merchant = Merchant::find($merchantId);
                    $product = Product::find($item['product_id']);

                    if ($merchant && $product) {
                        $commissionInfo = $this->commissionService->resolveCommission($product, $merchant);
                        $commissionRate = $commissionInfo['rate'];
                        $commissionAmount = $this->commissionService->calculateAmount($subtotal, $commissionInfo);
                    } else {
                        $commissionRate = $merchant?->effective_commission ?? 15.00;
                        $commissionAmount = round($subtotal * $commissionRate / 100, 2);
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'merchant_id' => $merchantId,
                    'product_name' => $item['name'],
                    'product_slug' => $item['slug'],
                    'brand_name' => $item['brand'],
                    'product_image' => $item['image'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                ]);
            }

            DB::commit();

            // Increment discount usage if discount was applied
            if (!empty($validated['discount_code']) && isset($totals['discount_info']) && $totals['discount_info']['valid']) {
                try {
                    $this->discountService->incrementUsage($validated['discount_code']);
                    Log::info('Discount Usage Incremented', [
                        'order' => $order->order_number,
                        'discount_code' => $validated['discount_code'],
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to Increment Discount Usage', [
                        'order' => $order->order_number,
                        'discount_code' => $validated['discount_code'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Clear cart after order creation
            session()->forget('cart');

            // Process payment based on selected method
            $paymentMethod = $validated['payment_method'] ?? array_key_first($enabledPaymentMethods);

            if ($paymentMethod === 'cod') {
                return $this->processWithCod($order);
            } elseif ($paymentMethod === 'tap') {
                return $this->processWithTap($order);
            } elseif ($paymentMethod === 'tabby') {
                return $this->processWithTabby($order);
            } elseif ($paymentMethod === 'tamara') {
                return $this->processWithTamara($order);
            }

            // Default: redirect to confirmation
            return redirect()->route('order.confirmation', $order->order_number);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Process Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('checkout.index')
                ->with('error', 'An error occurred while processing your order. Please try again.');
        }
    }

    /**
     * Process payment with Tap
     */
    protected function processWithTap(Order $order)
    {
        try {
            $chargeData = $this->tapPayment->createCharge([
                'amount' => $this->tapPayment->formatAmount($order->grand_total),
                'currency' => $order->currency,
                'description' => 'Order #' . $order->order_number,
                'order_number' => $order->order_number,
                'email' => $order->email,
                'first_name' => $order->first_name,
                'last_name' => $order->last_name,
                'phone' => $order->phone,
            ]);

            if ($chargeData['success']) {
                // Update order with payment ID
                $order->update([
                    'payment_id' => $chargeData['charge_id'],
                ]);

                // Redirect to Tap payment page
                return redirect($chargeData['redirect_url']);
            } else {
                Log::error('Tap Payment Failed', [
                    'order' => $order->order_number,
                    'error' => $chargeData['error'] ?? 'Unknown error',
                ]);

                return redirect()->route('order.confirmation', $order->order_number)
                    ->with('error', 'Payment initiation failed: ' . ($chargeData['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('Tap Payment Exception', [
                'order' => $order->order_number,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('order.confirmation', $order->order_number)
                ->with('error', 'Payment gateway error occurred.');
        }
    }

    /**
     * Process payment with Tabby BNPL
     */
    protected function processWithTabby(Order $order)
    {
        try {
            // Check if Tabby is available for this amount
            if (!$this->tabbyPayment->isAvailable($order->grand_total, $order->currency)) {
                return redirect()->route('order.confirmation', $order->order_number)
                    ->with('error', 'Tabby is not available for this order amount. Please choose another payment method.');
            }

            // Prepare order items for Tabby
            $items = [];
            foreach ($order->items as $item) {
                $items[] = [
                    'title' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'category' => 'Perfumes',
                ];
            }

            $paymentData = $this->tabbyPayment->createPayment([
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'description' => 'Order #' . $order->order_number,
                'order_number' => $order->order_number,
                'name' => $order->full_name,
                'email' => $order->email,
                'phone' => $order->phone,
                'address' => $order->address,
                'city' => $order->city,
                'postal_code' => $order->postal_code,
                'shipping' => $order->shipping,
                'discount' => $order->discount,
                'success_url' => route('payment.tabby.success'),
                'cancel_url' => route('payment.tabby.cancel'),
                'failure_url' => route('payment.tabby.failure'),
            ]);

            if ($paymentData['success']) {
                // Update order with payment ID
                $order->update([
                    'payment_id' => $paymentData['payment_id'],
                ]);

                // Redirect to Tabby payment page
                return redirect($paymentData['redirect_url']);
            } else {
                Log::error('Tabby Payment Failed', [
                    'order' => $order->order_number,
                    'error' => $paymentData['error'] ?? 'Unknown error',
                ]);

                return redirect()->route('order.confirmation', $order->order_number)
                    ->with('error', 'Tabby payment initiation failed: ' . ($paymentData['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('Tabby Payment Exception', [
                'order' => $order->order_number,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('order.confirmation', $order->order_number)
                ->with('error', 'Tabby payment gateway error occurred.');
        }
    }

    /**
     * Process payment with Tamara BNPL
     */
    protected function processWithTamara(Order $order)
    {
        try {
            // Check if Tamara is available for this amount
            if (!$this->tamaraPayment->isAvailable($order->grand_total, $order->currency)) {
                return redirect()->route('order.confirmation', $order->order_number)
                    ->with('error', 'Tamara is not available for this order amount. Please choose another payment method.');
            }

            // Prepare order items for Tamara
            $items = [];
            foreach ($order->items as $item) {
                $items[] = [
                    'reference_id' => $item->product_id,
                    'type' => 'Physical',
                    'name' => $item->product_name,
                    'sku' => $item->product_slug,
                    'quantity' => $item->quantity,
                    'unit_price' => [
                        'amount' => number_format($item->price, 2, '.', ''),
                        'currency' => $order->currency,
                    ],
                    'total_amount' => [
                        'amount' => number_format($item->subtotal, 2, '.', ''),
                        'currency' => $order->currency,
                    ],
                ];
            }

            $checkoutData = $this->tamaraPayment->createCheckout([
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'description' => 'Order #' . $order->order_number,
                'order_number' => $order->order_number,
                'first_name' => $order->first_name,
                'last_name' => $order->last_name,
                'email' => $order->email,
                'phone' => $order->phone,
                'address' => $order->address,
                'city' => $order->city,
                'country' => $order->country,
                'shipping' => $order->shipping,
                'discount' => $order->discount,
                'items' => $items,
                'success_url' => route('payment.tamara.success'),
                'cancel_url' => route('payment.tamara.cancel'),
                'failure_url' => route('payment.tamara.failure'),
                'notification_url' => route('payment.tamara.webhook'),
            ]);

            if ($checkoutData['success']) {
                // Update order with payment IDs
                $order->update([
                    'payment_id' => $checkoutData['checkout_id'],
                    'tamara_order_id' => $checkoutData['order_id'],
                ]);

                // Redirect to Tamara checkout page
                return redirect($checkoutData['redirect_url']);
            } else {
                Log::error('Tamara Checkout Failed', [
                    'order' => $order->order_number,
                    'error' => $checkoutData['error'] ?? 'Unknown error',
                ]);

                return redirect()->route('order.confirmation', $order->order_number)
                    ->with('error', 'Tamara checkout initiation failed: ' . ($checkoutData['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('Tamara Checkout Exception', [
                'order' => $order->order_number,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('order.confirmation', $order->order_number)
                ->with('error', 'Tamara payment gateway error occurred.');
        }
    }

    /**
     * Process order with Cash on Delivery
     */
    protected function processWithCod(Order $order)
    {
        $order->update([
            'payment_method' => 'cod',
            'payment_status' => 'cod_pending',
            'status' => 'confirmed',
        ]);

        return redirect()->route('order.confirmation', $order->order_number)
            ->with('success', 'Order placed successfully! Payment will be collected on delivery.');
    }

    /**
     * Get enabled payment methods from admin settings
     */
    protected function getEnabledPaymentMethods(): array
    {
        $methods = [];

        if ((bool) $this->settingsService->get('payment_tap_enabled', true)) {
            $methods['tap'] = [
                'label' => 'Credit/Debit Card',
                'description' => 'Pay securely with Tap Payments',
            ];
        }

        if ((bool) $this->settingsService->get('payment_tabby_enabled', false)) {
            $methods['tabby'] = [
                'label' => 'Tabby - Buy Now Pay Later',
                'description' => 'Split into 4 interest-free payments',
            ];
        }

        if ((bool) $this->settingsService->get('payment_tamara_enabled', false)) {
            $methods['tamara'] = [
                'label' => 'Tamara - Buy Now Pay Later',
                'description' => 'Pay in 3 installments, 0% interest',
            ];
        }

        if ((bool) $this->settingsService->get('payment_cod_enabled', false)) {
            $methods['cod'] = [
                'label' => 'Cash on Delivery',
                'description' => 'Pay when you receive your order',
            ];
        }

        return $methods;
    }
}
