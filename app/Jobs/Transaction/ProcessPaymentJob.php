<?php

namespace App\Jobs\Transaction;

use App\Models\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Transaction $transaction)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // recheck transaction if it still exists
        if (! Transaction::whereKey($this->transaction->getKey())->exists()) {
            Log::error('Transaction not found.', [
                'id' => $this->transaction->id,
            ]);

            return;
        }

        // do something heavy to process payment here

        // update payment status
        $status = Arr::random([
            TransactionStatus::Completed,
            TransactionStatus::Failed,
        ]);

        $this->transaction
            ->fill(compact('status'))
            ->save();

        Cache::forget('payment.summary');
    }
}
