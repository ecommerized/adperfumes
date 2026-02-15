<?php

namespace App\Console\Commands;

use App\Services\SettlementService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessSettlements extends Command
{
    protected $signature = 'settlements:process {--date= : Payout date (YYYY-MM-DD), defaults to today}';

    protected $description = 'Generate merchant settlements for eligible orders on payout dates (1st, 8th, 15th, 22nd)';

    public function handle(SettlementService $settlementService): int
    {
        $dateOption = $this->option('date');
        $payoutDate = $dateOption ? Carbon::parse($dateOption) : Carbon::today();

        $this->info("Processing settlements for {$payoutDate->toDateString()}...");

        $settlements = $settlementService->generateSettlements($payoutDate);

        if (empty($settlements)) {
            $this->info('No eligible orders found for settlement.');

            return self::SUCCESS;
        }

        foreach ($settlements as $settlement) {
            $this->line("  Settlement #{$settlement->id} — Merchant #{$settlement->merchant_id} — Payout: AED {$settlement->merchant_payout}");
        }

        $this->info('Generated ' . count($settlements) . ' settlements.');

        return self::SUCCESS;
    }
}
