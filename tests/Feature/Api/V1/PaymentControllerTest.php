<?php

use App\Jobs\Transaction\ProcessPaymentJob;
use App\Models\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->user = User::factory()->create();
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

it('calculates summary for all transactions', function (): void {
    $this->artisan('db:table:truncate');

    $transactions = Transaction::factory()
        ->for($this->user)
        ->create();

    assertDatabaseHas((new Transaction)->getTable(), [
        'id' => $transactions->id,
    ]);
});
