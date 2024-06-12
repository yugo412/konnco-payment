<?php

namespace App\Http\Resources;

use App\Models\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PaymentSummaryResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return Cache::rememberForever('payment.summary', function () {
            $maxAmount = $this->collection
                ->where('status', value: TransactionStatus::Completed)
                ->max('amount');

            $minAmount = $this->collection
                ->where('status', value: TransactionStatus::Completed)
                ->min('amount');

            $maxName = $this->collection->max(fn (Transaction $transaction): int => strlen($transaction->user->name));

            return [
                'total_transaction' => $this->collection->count(),
                'average_amount' => round($this->collection->avg('amount'), 2),
                'highest_transaction' => $this->collection
                    ->firstWhere(function (Transaction $transaction) use ($maxAmount): bool {
                        return $transaction->status === TransactionStatus::Completed
                            && $transaction->amount >= $maxAmount;
                    }),
                'lowest_transaction' => $this->collection
                    ->firstWhere(function (Transaction $transaction) use ($minAmount): bool {
                        return $transaction->status === TransactionStatus::Completed
                            && $transaction->amount === $minAmount;
                    }),
                'longest_name_transaction' => $this->collection
                    ->firstWhere(fn (Transaction $transaction): bool => strlen($transaction->user->name) === $maxName),
                'status_distribution' => $this->collection
                    ->groupBy('status')
                    ->mapWithKeys(function (Collection $group, string $status): array {
                        return [TransactionStatus::tryFrom($status)->value => $group->count()];
                    })
                    ->toArray(),
            ];
        });
    }
}
