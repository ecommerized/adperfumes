<?php

namespace App\Services;

use App\Models\CommissionRule;
use App\Models\Merchant;
use App\Models\Product;

class CommissionService
{
    /**
     * Resolve the applicable commission for a product + merchant combination.
     * Priority: product > category > tier > merchant > global > merchant.commission_percentage fallback
     *
     * @return array{rate: float, type: string, source: string, rule_id: int|null}
     */
    public function resolveCommission(Product $product, Merchant $merchant): array
    {
        // 1. Product-level rule
        $rule = CommissionRule::active()->valid()
            ->where('level', 'product')
            ->where('product_id', $product->id)
            ->orderBy('priority')
            ->first();

        if ($rule) {
            return $this->formatResult($rule, 'product_rule');
        }

        // 2. Category-level rule
        $categoryIds = $product->categories()->pluck('categories.id')->toArray();
        if (!empty($categoryIds)) {
            $rule = CommissionRule::active()->valid()
                ->where('level', 'category')
                ->whereIn('category_id', $categoryIds)
                ->orderBy('priority')
                ->first();

            if ($rule) {
                return $this->formatResult($rule, 'category_rule');
            }
        }

        // 3. Tier-level rule (based on merchant's sales volume)
        $rule = CommissionRule::active()->valid()
            ->where('level', 'tier')
            ->where('type', 'tiered')
            ->orderBy('priority')
            ->first();

        if ($rule && $rule->tier_rules) {
            $merchantVolume = $merchant->orderItems()->sum('subtotal');
            $tierRate = $this->resolveTierRate($rule->tier_rules, $merchantVolume);
            if ($tierRate !== null) {
                return [
                    'rate' => $tierRate,
                    'type' => 'percentage',
                    'source' => 'tier_rule',
                    'rule_id' => $rule->id,
                ];
            }
        }

        // 4. Merchant-level rule
        $rule = CommissionRule::active()->valid()
            ->where('level', 'merchant')
            ->where('merchant_id', $merchant->id)
            ->orderBy('priority')
            ->first();

        if ($rule) {
            return $this->formatResult($rule, 'merchant_rule');
        }

        // 5. Global rule
        $rule = CommissionRule::active()->valid()
            ->where('level', 'global')
            ->orderBy('priority')
            ->first();

        if ($rule) {
            return $this->formatResult($rule, 'global_rule');
        }

        // 6. Fallback to merchant's commission_percentage
        return [
            'rate' => $merchant->effective_commission,
            'type' => 'percentage',
            'source' => 'merchant_default',
            'rule_id' => null,
        ];
    }

    /**
     * Calculate commission amount based on resolved commission info.
     */
    public function calculateAmount(float $subtotal, array $commissionInfo): float
    {
        return match ($commissionInfo['type']) {
            'percentage' => round($subtotal * $commissionInfo['rate'] / 100, 2),
            'fixed' => round($commissionInfo['rate'], 2),
            'hybrid' => round(($subtotal * $commissionInfo['rate'] / 100) + ($commissionInfo['fixed_amount'] ?? 0), 2),
            default => round($subtotal * $commissionInfo['rate'] / 100, 2),
        };
    }

    /**
     * Format a commission rule into a standard result array.
     */
    private function formatResult(CommissionRule $rule, string $source): array
    {
        return [
            'rate' => $rule->type === 'fixed' ? (float) $rule->fixed_amount : (float) $rule->percentage_rate,
            'type' => $rule->type,
            'source' => $source,
            'rule_id' => $rule->id,
            'fixed_amount' => $rule->type === 'hybrid' ? (float) $rule->fixed_amount : null,
        ];
    }

    /**
     * Resolve the applicable tier rate based on sales volume.
     * tier_rules format: [{"min": 0, "max": 10000, "rate": 15}, {"min": 10000, "max": 50000, "rate": 12}, ...]
     */
    private function resolveTierRate(array $tierRules, float $volume): ?float
    {
        foreach ($tierRules as $tier) {
            $min = $tier['min'] ?? 0;
            $max = $tier['max'] ?? PHP_FLOAT_MAX;

            if ($volume >= $min && $volume < $max) {
                return (float) $tier['rate'];
            }
        }

        return null;
    }
}
