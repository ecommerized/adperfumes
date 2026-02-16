<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAT (Value Added Tax) Configuration
    |--------------------------------------------------------------------------
    |
    | Default VAT rate for UAE. Merchants enter tax-inclusive prices, and
    | the system back-calculates the VAT amount.
    |
    */

    'vat_rate' => env('ACCOUNTING_VAT_RATE', 5.00),

    /*
    |--------------------------------------------------------------------------
    | Corporate Tax Configuration (UAE)
    |--------------------------------------------------------------------------
    |
    | UAE Corporate Tax is 9% on profits above AED 375,000.
    | This is calculated on NET profits, not gross revenue.
    |
    */

    'corporate_tax_rate' => env('ACCOUNTING_CORPORATE_TAX_RATE', 9.00),
    'corporate_tax_threshold' => env('ACCOUNTING_CORPORATE_TAX_THRESHOLD', 375000),

    /*
    |--------------------------------------------------------------------------
    | Platform Fee Configuration
    |--------------------------------------------------------------------------
    |
    | Platform fee charged on all transactions (Tap's platform fee: 0.25%)
    |
    */

    'platform_fee_percentage' => env('ACCOUNTING_PLATFORM_FEE', 0.25),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Fee Structures
    |--------------------------------------------------------------------------
    |
    | Fee structures for different payment methods and card types.
    | Based on Tap Payments agreement dated 04-22-2024.
    |
    | Structure: ['percentage' => X.XX, 'fixed' => Y.YY]
    |
    */

    'payment_fees' => [
        'tap' => [
            'local_visa' => ['percentage' => 2.25, 'fixed' => 1.00],
            'local_mastercard' => ['percentage' => 2.25, 'fixed' => 1.00],
            'local_mada' => ['percentage' => 2.25, 'fixed' => 1.00],

            'regional_visa' => ['percentage' => 2.55, 'fixed' => 1.00],
            'regional_mastercard' => ['percentage' => 2.55, 'fixed' => 1.00],

            'international_visa' => ['percentage' => 2.55, 'fixed' => 1.00],
            'international_mastercard' => ['percentage' => 2.55, 'fixed' => 1.00],

            'amex' => ['percentage' => 3.2, 'fixed' => 1.00],

            'default' => ['percentage' => 2.25, 'fixed' => 1.00], // fallback
        ],

        'tabby' => [
            'bnpl' => ['percentage' => 6.5, 'fixed' => 1.00],
        ],

        'tamara' => [
            'bnpl' => ['percentage' => 3.5, 'fixed' => 0.00], // estimated
        ],

        'cod' => [
            'cash' => ['percentage' => 0.00, 'fixed' => 0.00], // no gateway fees
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Settlement Configuration
    |--------------------------------------------------------------------------
    |
    | Merchant settlement schedule and eligibility rules.
    |
    */

    'settlement' => [
        // Days after delivery before merchant is eligible for settlement
        'merchant_hold_days' => env('ACCOUNTING_MERCHANT_HOLD_DAYS', 15),

        // Fixed payout dates each month (1st, 8th, 15th, 22nd)
        'payout_days' => [1, 8, 15, 22],

        // Own store settlement (direct from Tap: T+2)
        'own_store_hold_days' => env('ACCOUNTING_OWN_STORE_HOLD_DAYS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Commission Configuration
    |--------------------------------------------------------------------------
    |
    | Default commission rates and structures.
    |
    */

    'commission' => [
        // Global default commission rate (applied if no other rules match)
        'default_rate' => env('ACCOUNTING_DEFAULT_COMMISSION', 15.00),

        // Commission calculation base
        'calculate_on' => 'subtotal_excluding_tax', // commission on pre-tax amount

        // Priority order for commission resolution
        'priority' => [
            'product',   // product-level commission (highest priority)
            'category',  // category-level commission
            'tier',      // volume-based tiers
            'merchant',  // merchant-specific rate
            'global',    // global default (lowest priority)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    */

    'currency' => env('ACCOUNTING_CURRENCY', 'AED'),
    'currency_symbol' => env('ACCOUNTING_CURRENCY_SYMBOL', 'AED'),

    /*
    |--------------------------------------------------------------------------
    | Refund Configuration
    |--------------------------------------------------------------------------
    */

    'refund' => [
        // Refund window (days after delivery)
        'refund_window_days' => env('ACCOUNTING_REFUND_WINDOW', 14),

        // Merchant recovery methods
        'recovery_method' => 'deduct_next_settlement', // or 'direct_repayment'
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    */

    'reports' => [
        // Tax report frequencies
        'tax_report_frequencies' => ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'],

        // Fiscal year start month (1 = January)
        'fiscal_year_start_month' => env('ACCOUNTING_FISCAL_YEAR_START', 1),

        // Enable automatic report generation
        'auto_generate_reports' => env('ACCOUNTING_AUTO_REPORTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Own Store Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for platform's own store (merchant_id = NULL).
    |
    */

    'own_store' => [
        // Commission rate for own store (0% = no commission)
        'commission_rate' => 0.00,

        // Who pays payment fees for own store orders?
        'payment_fees_paid_by' => 'platform', // 'platform' or 'deduct_from_revenue'

        // Merchant ID representing own store (null = use NULL in database)
        'merchant_id' => null,

        // Store identifier
        'store_name' => env('OWN_STORE_NAME', 'AD Perfumes'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GCC Country Codes (for regional card detection)
    |--------------------------------------------------------------------------
    */

    'gcc_countries' => ['AE', 'SA', 'KW', 'BH', 'OM', 'QA'],

];
