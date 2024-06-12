<?php

use App\Models\Enums\TransactionStatus;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $statuses = array_map(function (TransactionStatus $status): string {
            return $status->value;
        }, TransactionStatus::cases());

        Schema::create('transactions', function (Blueprint $table) use ($statuses) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->decimal('amount', 20);
            $table->enum('status', $statuses)
                ->default(TransactionStatus::Pending->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
