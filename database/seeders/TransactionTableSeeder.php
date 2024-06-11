<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (User::factory(1_000)->create() as $user) {
            Transaction::factory(10)
                ->for($user)
                ->create();
        }
    }
}
