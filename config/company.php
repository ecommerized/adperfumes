<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | This file contains your company's business information used for
    | invoices, receipts, and legal documents. Update these values
    | with your actual company details.
    |
    */

    /**
     * Company/Store Name
     */
    'name' => env('COMPANY_NAME', 'AD Perfumes'),

    /**
     * UAE VAT Registration Number (TRN)
     *
     * This is your 15-digit Tax Registration Number issued by the
     * UAE Federal Tax Authority (FTA).
     *
     * Format: 123456789012345 (15 digits)
     */
    'vat_number' => env('COMPANY_VAT_NUMBER', ''),

    /**
     * Company Address
     */
    'address' => env('COMPANY_ADDRESS', 'Dubai, United Arab Emirates'),

    /**
     * Contact Information
     */
    'phone' => env('COMPANY_PHONE', '+971-XX-XXX-XXXX'),
    'email' => env('COMPANY_EMAIL', 'support@adperfumes.com'),
    'website' => env('COMPANY_WEBSITE', 'https://adperfumes.com'),

    /**
     * Business Registration Details
     */
    'trade_license' => env('COMPANY_TRADE_LICENSE', ''),
    'registration_number' => env('COMPANY_REGISTRATION_NUMBER', ''),

    /**
     * Bank Details (for settlements and payments)
     */
    'bank_name' => env('COMPANY_BANK_NAME', ''),
    'bank_account_name' => env('COMPANY_BANK_ACCOUNT_NAME', ''),
    'bank_account_number' => env('COMPANY_BANK_ACCOUNT_NUMBER', ''),
    'bank_iban' => env('COMPANY_BANK_IBAN', ''),
    'bank_swift' => env('COMPANY_BANK_SWIFT', ''),

    /**
     * Invoice Settings
     */
    'invoice_prefix' => env('COMPANY_INVOICE_PREFIX', 'INV'),
    'invoice_footer' => env('COMPANY_INVOICE_FOOTER', 'Thank you for your business!'),

    /**
     * Tax Settings
     */
    'vat_rate' => 5.0, // UAE VAT rate (5%)
    'corporate_tax_rate' => 9.0, // UAE Corporate Tax rate (9%)
    'corporate_tax_threshold' => 375000.00, // AED 375,000 threshold

    /**
     * Legal Information
     */
    'terms_url' => env('COMPANY_TERMS_URL', ''),
    'privacy_url' => env('COMPANY_PRIVACY_URL', ''),
    'refund_policy_url' => env('COMPANY_REFUND_POLICY_URL', ''),
];
