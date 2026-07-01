<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);

        return [
            'user_id'        => User::factory(),
            'client_id'      => Client::factory(),
            'invoice_number' => 'INV-' . date('Y') . '-' . fake()->unique()->numberBetween(1000, 9999),
            'status'         => Invoice::STATUS_DRAFT,
            'issue_date'     => now()->subDays(fake()->numberBetween(1, 60)),
            'due_date'       => now()->addDays(fake()->numberBetween(1, 30)),
            'subtotal'       => $subtotal,
            'tax_rate'       => 0,
            'tax_amount'     => 0,
            'discount_amount'=> 0,
            'total_amount'   => $subtotal,
            'currency'       => 'USD',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status'  => Invoice::STATUS_PAID,
            'paid_at' => now()->subDays(fake()->numberBetween(1, 10)),
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn () => ['status' => Invoice::STATUS_SENT]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status'   => Invoice::STATUS_OVERDUE,
            'due_date' => now()->subDays(10),
        ]);
    }
}