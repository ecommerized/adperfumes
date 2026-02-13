<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckMerchantApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('merchant')->check()) {
            $merchant = Auth::guard('merchant')->user();

            if (!$merchant->isApproved()) {
                Auth::guard('merchant')->logout();

                $message = match ($merchant->status) {
                    'pending' => 'Your merchant account is pending approval. Please wait for admin approval.',
                    'rejected' => 'Your merchant account has been rejected. Reason: ' . $merchant->rejection_reason,
                    'suspended' => 'Your merchant account has been suspended. Reason: ' . $merchant->rejection_reason,
                    default => 'Your merchant account is not active.',
                };

                return redirect()->route('filament.merchant.auth.login')->with('error', $message);
            }
        }

        return $next($request);
    }
}
