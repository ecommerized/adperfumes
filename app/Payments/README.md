# Payment Gateway Integration

This directory will contain payment gateway integrations for:

## Supported Gateways (Phase 2)

1. **Tap Payments**
   - File: `TapPayment.php`
   - Integration: Tap API
   - Methods: Redirect or widget-based

2. **Tabby (BNPL)**
   - File: `TabbyPayment.php`
   - Integration: Tabby API
   - Methods: Buy Now Pay Later

3. **Tamara (BNPL)**
   - File: `TamaraPayment.php`
   - Integration: Tamara API
   - Methods: Buy Now Pay Later

## Architecture Rules

- All payment classes receive final calculated total from `CheckoutCalculator`
- Payment classes are separated from checkout controller
- Support both redirect and widget-based flows
- Never calculate totals in payment gateway classes
- Always validate payment response server-side

## Implementation Timeline

- **Phase 1**: Folder structure only (current)
- **Phase 2**: Full payment gateway integration
