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

    public function test_invoice_number_is_auto_generated(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/invoices', [
                'client_id'  => $client->id,
                'issue_date' => '2025-01-15',
                'due_date'   => '2025-02-15',
                'items'      => [['description' => 'Test', 'quantity' => 1, 'unit_price' => 100]],
            ]);

        $invoice = Invoice::first();
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $invoice->invoice_number);

    }

    public function test_user_can_transition_invoice_to_paid(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $invoice = Invoice::create([
            'user_id'        => $user->id,
            'client_id'      => $client->id,
            'invoice_number' => 'INV-2025-0001',
            'status'         => Invoice::STATUS_SENT,
            'issue_date'     => '2025-01-01',
            'due_date'       => '2025-02-01',
            'total_amount'   => 500,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/invoices/{$invoice->id}/status", [
                'status' => 'paid',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_invalid_status_transition_is_rejected(): void{
        /** @var User $user */
        $user = User::factory() -> create();
        $client = Client::factory()->for($user)->create();

        $invoice = Invoice::create([
            'user id' => $user->id,
            'cliend_id' => $client->id,
            'invoice_number' => 'INV-2025-0002',
            'status' => Invoice::STATUS_DRAFT,
            'issue_date' => '2026-01-01',
            'due_date' => '2026-02-01',
            'total_amount' => 500,

        ]);

        // draft cannot go directly to paid
        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/invoices/{$invoice->id}/status", [
                'status' => 'paid',
            ]);

        $response->assertStatus(422);
        $this->assertEquals('draft', $invoice->fresh()->status);
    }

}