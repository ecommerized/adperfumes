<?php

namespace App\Console\Commands;

use App\Services\SettlementService;
use Illuminate\Console\Command;

class CalculateSettlementEligibility extends Command
{
    protected $signature = 'settlements:calculate-eligibility';

    protected $description = 'Calculate settlement eligibility for delivered orders (settlement_eligible_at = delivered_at + 15 days)';

    public function handle(SettlementService $settlementService): int
    {
        $this->info('Calculating settlement eligibility...');

        $updated = $settlementService->calculateEligibility();

        $this->info("Updated {$updated} orders with settlement eligibility dates.");

        return self::SUCCESS;
    }
}
