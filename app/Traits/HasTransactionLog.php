<?php

namespace App\Traits;

use App\Models\TransactionLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTransactionLog
{
    public function transactionLogs(): MorphMany
    {
        return $this->morphMany(TransactionLog::class, 'loggable');
    }

    public function logTransaction(string $action, ?array $oldValues = null, ?array $newValues = null): void
    {
        $this->transactionLogs()->create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'performed_by' => auth()->id(),
        ]);
    }
}
