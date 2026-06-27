<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_invoice_with_line_items(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/invoices', [
                'client_id'  => $client->id,
                'issue_date' => '2025-01-15',
                'due_date'   => '2025-02-15',
                'tax_rate'   => 10,
                'currency'   => 'USD',
                'items'      => [
                    ['description' => 'Web development', 'quantity' => 1, 'unit_price' => 1000],
                    ['description' => 'Hosting setup',   'quantity' => 1, 'unit_price' => 200],
                ],
            ]);

        $response->assertStatus(201);

        $invoice = Invoice::first();
        $this->assertEquals(1200, $invoice->subtotal);
        $this->assertEquals(120, $invoice->tax_amount);
        $this->assertEquals(1320, $invoice->total_amount);
        $this->assertCount(2, $invoice->items);
    }

}