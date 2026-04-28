<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user
        $user = User::create([
            'name'         => 'Solomon Olusegun',
            'email'        => 'demo@clientflow.test',
            'password'     => Hash::make('password'),
            'company_name' => 'DevOlusegun Studio',
            'phone'        => '+234 815 536 6935',
            'currency'     => 'USD',
        ]);

        // Clients
        $clients = collect([
            ['name' => 'DonateWater Foundation',  'company' => 'DonateWater',    'email' => 'projects@donatewater.org',   'country' => 'Switzerland'],
            ['name' => 'YOMA Platform',           'company' => 'YOMA',           'email' => 'tech@yoma.world',            'country' => 'South Africa'],
            ['name' => 'Goodwall',                'company' => 'Goodwall SA',    'email' => 'dev@goodwall.io',            'country' => 'Switzerland'],
            ['name' => 'Geotech Nigeria',         'company' => 'Geotech Ltd',    'email' => 'admin@geotech.ng',           'country' => 'Nigeria'],
            ['name' => 'iStephmond',              'company' => 'iStephmond Inc', 'email' => 'hello@istephmond.com',       'country' => 'Nigeria'],
        ])->map(fn ($data) => $user->clients()->create($data));

        // Invoices with realistic data
        $invoiceData = [
            // Paid
            [
                'client'   => $clients[0],
                'status'   => Invoice::STATUS_PAID,
                'days_ago' => 90,
                'due_days' => 30,
                'items'    => [
                    ['Web platform design and development', 1, 3500.00],
                    ['MySQL database setup and optimisation', 1, 500.00],
                    ['Monthly hosting management (3 months)', 3, 150.00],
                ],
            ],
            [
                'client'   => $clients[1],
                'status'   => Invoice::STATUS_PAID,
                'days_ago' => 60,
                'due_days' => 14,
                'items'    => [
                    ['Campaign management – Meta Ads (Month 1)', 1, 4000.00],
                    ['Creative briefing and ad copy', 1, 500.00],
                ],
            ],
            // Sent (outstanding)
            [
                'client'   => $clients[2],
                'status'   => Invoice::STATUS_SENT,
                'days_ago' => 10,
                'due_days' => 30,
                'items'    => [
                    ['API integration – user acquisition module', 1, 2200.00],
                    ['Performance testing and optimisation', 1, 300.00],
                ],
            ],
            // Overdue
            [
                'client'   => $clients[3],
                'status'   => Invoice::STATUS_OVERDUE,
                'days_ago' => 45,
                'due_days' => 14,
                'items'    => [
                    ['Backend support – system architecture review', 1, 1800.00],
                    ['Documentation and handover', 1, 200.00],
                ],
            ],
            // Draft
            [
                'client'   => $clients[4],
                'status'   => Invoice::STATUS_DRAFT,
                'days_ago' => 2,
                'due_days' => 30,
                'items'    => [
                    ['3-month ad campaign management (flat fee)', 1, 12000.00],
                ],
            ],
        ];

        foreach ($invoiceData as $data) {
            $issueDate = now()->subDays($data['days_ago']);
            $dueDate   = $issueDate->copy()->addDays($data['due_days']);

            $invoice = $user->invoices()->create([
                'client_id'      => $data['client']->id,
                'invoice_number' => Invoice::generateNumber($user->id),
                'status'         => $data['status'],
                'issue_date'     => $issueDate,
                'due_date'       => $dueDate,
                'paid_at'        => $data['status'] === Invoice::STATUS_PAID ? $dueDate->subDays(5) : null,
                'tax_rate'       => 0,
                'discount_amount'=> 0,
                'subtotal'       => 0,
                'tax_amount'     => 0,
                'total_amount'   => 0,
                'currency'       => 'USD',
            ]);

            foreach ($data['items'] as $index => [$desc, $qty, $price]) {
                $invoice->items()->create([
                    'description' => $desc,
                    'quantity'    => $qty,
                    'unit_price'  => $price,
                    'sort_order'  => $index,
                ]);
            }
        }

        $this->command->info('Demo data seeded. Login: demo@clientflow.test / password');
    }
}