<?php

use App\Jobs\Transaction\ProcessPaymentJob;
use App\Models\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('throttles payment endpoint', function (): void {
    for ($i = 1; $i <= 60; ++$i) {
        $this->actingAs($this->user,'api')->getJson('/api/v1/payment');
    }

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/v1/payment');

    $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
        ->assertJson([
            'message' => 'Too Many Attempts.',
        ]);
});

it('authenticates user before make a payment', function (): void {
    $response = $this->postJson('/api/v1/payment', ['amount' => 1_000_000]);

    $response->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

it('validates input before make a payment', function (): void {
    $response = $this->actingAs($this->user, 'api')
        ->postJson('/api/v1/payment', ['amount' => null]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJson([
            'message' => 'The amount field is required.',
            'errors' => [
                'amount' => [
                    'The amount field is required.',
                ],
            ],
        ]);
});

it('creates new transaction with pending status', function (): void {
    $transaction = Transaction::factory()->make();

    Queue::fake();

    $response = $this->actingAs($this->user, 'api')
        ->postJson('/api/v1/payment', ['amount' => $transaction->amount]);

    Queue::assertPushed(ProcessPaymentJob::class);

    $response->assertStatus(Response::HTTP_CREATED);

    assertDatabaseHas((new Transaction)->getTable(), [
        'user_id' => $this->user->id,
        'amount' => $transaction->amount,
        'status' => TransactionStatus::Pending->value,
    ]);
});

it('authenticates user before view transaction list', function (): void {
    $response = $this->getJson('/api/v1/payment');

    $response->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

it('returns list of transaction for authenticated user', function (): void {
    $transactions = Transaction::factory()
        ->for($this->user)
        ->create();
    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/v1/payment');

    $response->assertOk()
        ->assertJson([
            'data' => [Arr::except($transactions->toArray(), ['user_id', 'user_name'])],
        ])
        ->assertJsonStructure([
            'data' => [],
            'links' => [
                'first',
                'next',
                'prev',
                'last',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'links' => [],
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);
});

it('authenticates to access transaction summary', function (): void {
    $this->getJson('/api/v1/payment/summary')
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

it('calculates summary for all transactions', function (): void {
    // clean table before make a calculation for summary
    Transaction::query()->truncate();

    $transactions = Transaction::factory($count = 2)
        ->for($this->user)
        ->create([
            'status' => TransactionStatus::Completed,
        ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/v1/payment/summary');

    $maxAmount = $transactions->where('status', value: TransactionStatus::Completed)
        ->max('amount');

    $minAmount = $transactions->where('status', value: TransactionStatus::Completed)
        ->min('amount');

    $highest = $transactions->firstWhere(function (Transaction $transaction) use ($maxAmount): bool {
        return $transaction->status === TransactionStatus::Completed
            && $transaction->amount >= $maxAmount;
    });

    $lowest = $transactions->firstWhere(function (Transaction $transaction) use ($minAmount): bool {
        return $transaction->status === TransactionStatus::Completed
            && $transaction->amount >= $minAmount;
    });

    $maxName = $transactions->max(fn(Transaction $transaction): int => strlen($transaction->user->name));

    $longest = $transactions
        ->firstWhere(fn(Transaction $transaction): bool => strlen($transaction->user->name) === $maxName);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'total_transaction' => $count,

                'average_amount' => round($transactions->avg('amount'), 2),

                'highest_transaction' => [
                    'id' => $highest->id,
                    'user_id' => $highest->user_id,
                    'amount' => $highest->amount,
                    'status' => $highest->status->value,
                ],

                'lowest_transaction' => [
                    'id' => $lowest->id,
                    'user_id' => $lowest->user_id,
                    'amount' => $lowest->amount,
                    'status' => $lowest->status->value,
                ],

                'longest_name_transaction' => [
                    'id' => $longest->id,
                    'user_id' => $longest->user_id,
                    'amount' => $longest->amount,
                    'status' => $longest->status->value,
                ],

                'status_distribution' => $transactions->groupBy('status')
                    ->mapWithKeys(function (Collection $transactions, string $status): array {
                        return [$status => $transactions->count()];
                    })
                    ->toArray(),
            ]
        ]);
});
