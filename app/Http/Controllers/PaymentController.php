<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Payments\TapPayment;
use App\Services\PaymentFeeService;
use App\Services\Shipping\AramexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    protected $tapPayment;
    protected $aramexService;
    protected $paymentFeeService;

    public function __construct(
        TapPayment $tapPayment,
        AramexService $aramexService,
        PaymentFeeService $paymentFeeService
    ) {
        $this->tapPayment = $tapPayment;
        $this->aramexService = $aramexService;
        $this->paymentFeeService = $paymentFeeService;
    }

    /**
     * Handle Tap payment callback (webhook)
     * This is called by Tap servers
     */
    public function tapCallback(Request $request)
    {
        try {
            Log::info('Tap Callback Received', $request->all());

            $chargeId = $request->input('id') ?? $request->input('tap_id');

            if (!$chargeId) {
                Log::error('Tap Callback: No charge ID provided');
                return response()->json(['error' => 'No charge ID'], 400);
            }

            // Verify payment with Tap
            $paymentData = $this->tapPayment->verifyPayment($chargeId);

            if (!$paymentData['success']) {
                Log::error('Tap Payment Verification Failed', $paymentData);
                return response()->json(['error' => 'Verification failed'], 400);
            }

            // Find order by order number
            $order = Order::where('order_number', $paymentData['order_number'])->first();

            if (!$order) {
                Log::error('Tap Callback: Order not found', ['order_number' => $paymentData['order_number']]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Update order payment status
            $order->update([
                'payment_status' => $paymentData['is_paid'] ? 'paid' : 'failed',
                'payment_id' => $paymentData['transaction_id'],
                'payment_response' => json_encode($paymentData['data']),
                'status' => $paymentData['is_paid'] ? 'confirmed' : 'pending',
            ]);

            // Calculate and store payment fees if payment successful
            if ($paymentData['is_paid']) {
                try {
                    $this->paymentFeeService->updateOrderPaymentFees($order, $paymentData['data'] ?? []);
                    Log::info('Payment fees calculated and stored', [
                        'order' => $order->order_number,
                        'gateway_fee' => $order->fresh()->payment_gateway_fee_total,
                        'platform_fee' => $order->fresh()->platform_fee_amount,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to calculate payment fees', [
                        'order' => $order->order_number,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Order Payment Updated', [
                'order' => $order->order_number,
                'payment_status' => $order->payment_status,
            ]);

            // Send order confirmation email if payment successful
            if ($paymentData['is_paid']) {
                Mail::to($order->email)->send(new OrderConfirmationMail($order->load('items')));
                Log::info('Order Confirmation Email Sent', ['order' => $order->order_number]);

                // Create Aramex shipment automatically
                $this->createAramexShipment($order);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Tap Callback Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Handle Tap payment return (customer redirect)
     * This is where customer lands after payment
     */
    public function tapReturn(Request $request)
    {
        try {
            $chargeId = $request->input('tap_id');

            if (!$chargeId) {
                return redirect()->route('home')->with('error', 'Invalid payment response');
            }

            // Verify payment
            $paymentData = $this->tapPayment->verifyPayment($chargeId);

            if (!$paymentData['success']) {
                return redirect()->route('home')->with('error', 'Payment verification failed');
            }

            // Find order
            $order = Order::where('order_number', $paymentData['order_number'])->first();

            if (!$order) {
                return redirect()->route('home')->with('error', 'Order not found');
            }

            // Update order if not already updated by callback
            $wasUpdated = false;
            if ($order->payment_status === 'pending') {
                $order->update([
                    'payment_status' => $paymentData['is_paid'] ? 'paid' : 'failed',
                    'payment_id' => $paymentData['transaction_id'],
                    'payment_response' => json_encode($paymentData['data']),
                    'status' => $paymentData['is_paid'] ? 'confirmed' : 'pending',
                ]);
                $wasUpdated = true;

                // Calculate and store payment fees if payment successful
                if ($paymentData['is_paid']) {
                    try {
                        $this->paymentFeeService->updateOrderPaymentFees($order, $paymentData['data'] ?? []);
                    } catch (\Exception $e) {
                        Log::error('Failed to calculate payment fees in return', [
                            'order' => $order->order_number,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Send email if payment successful and order was just updated (callback might not have fired yet)
            if ($paymentData['is_paid'] && $wasUpdated) {
                try {
                    Mail::to($order->email)->send(new OrderConfirmationMail($order->load('items')));
                    Log::info('Order Confirmation Email Sent from Return', ['order' => $order->order_number]);
                } catch (\Exception $e) {
                    Log::error('Failed to send order confirmation email', [
                        'order' => $order->order_number,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Redirect to order confirmation page
            if ($paymentData['is_paid']) {
                return redirect()->route('order.confirmation', $order->order_number)
                    ->with('success', 'Payment successful! Your order has been confirmed.');
            } else {
                return redirect()->route('order.confirmation', $order->order_number)
                    ->with('error', 'Payment failed. Please try again or contact support.');
            }

        } catch (\Exception $e) {
            Log::error('Tap Return Exception', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return redirect()->route('home')->with('error', 'An error occurred processing your payment');
        }
    }

    /**
     * Display order confirmation page
     */
    public function orderConfirmation($orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        return view('orders.confirmation', compact('order'));
    }

    /**
     * Display order tracking page
     */
    public function trackOrder(Request $request)
    {
        $order = null;
        $tracking = null;
        $orderNumber = $request->query('order');

        if ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)
                ->orWhere('tracking_number', $orderNumber)
                ->first();

            if ($order && $order->tracking_number) {
                $tracking = $this->aramexService->trackShipment($order->tracking_number);
            }
        }

        return view('orders.track', compact('order', 'tracking', 'orderNumber'));
    }

    /**
     * Create Aramex shipment for paid order
     *
     * @param Order $order
     * @return void
     */
    protected function createAramexShipment(Order $order): void
    {
        try {
            $shipmentData = [
                'order_number' => $order->order_number,
                'full_name' => $order->full_name,
                'email' => $order->email,
                'phone' => $order->phone,
                'address' => $order->address,
                'city' => $order->city,
                'country' => $order->country,
                'postal_code' => $order->postal_code,
            ];

            $result = $this->aramexService->createShipment($shipmentData);

            if ($result['success']) {
                $order->update([
                    'tracking_number' => $result['tracking_number'],
                    'aramex_shipment_id' => $result['aramex_shipment_id'] ?? $result['tracking_number'],
                    'status' => 'processing',
                ]);

                Log::info('Aramex Shipment Created', [
                    'order' => $order->order_number,
                    'tracking' => $result['tracking_number'],
                ]);
            } else {
                Log::warning('Aramex Shipment Creation Failed', [
                    'order' => $order->order_number,
                    'message' => $result['message'] ?? 'Unknown error',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Aramex Shipment Exception', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
