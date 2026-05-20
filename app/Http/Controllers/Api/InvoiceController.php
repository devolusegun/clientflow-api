<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse as HttpFoundationJsonResponse;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceItemResource;
// and for ClientController:
use App\Http\Resources\ClientResource;
use App\Http\Resources\DashboardResource;

class InvoiceController extends Controller
{
    /**
     * List all invoices for the authenticated user.
     * Supports filtering by status, client, date range, and search.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per page' => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|string|in:draft,sent,paid,overdue,cancelled',
            'client_id' => 'sometimes|integer|exists:clients,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_eequal:date_from',
            'search' => 'sometimes|string|max:100',
            'sort_by' => 'sometimes|string|in:created_at,issue_date,due_date,total_amount',
            'sort_dir' => 'sometimes|string|in:asc,desc',
        ]);

        /**$query = $request->user()
            ->invoices()
            ->with('client:id,name,company,email')
            ->withCount('items');

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by client
        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Filter by date range (issue_date)
        if ($from = $request->query('date_from')) {
            $query->whereDate('issue_date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('issue_date', '<=', $to);
        }

        // Search by invoice number or client name
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($cq) =>
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('company', 'like', "%{$search}%")
                  );
            });
        }

        // Flag overdue invoices automatically
        $query->orderBy('created_at', 'desc');

        $invoices = $query->paginate(min((int) $request->query('per_page', 15), 100));*/

        $invoices = $request->user()
            ->invoices()
            ->with('client:id,name,company,email')
            ->withCount('items')
            ->byStatus($request->query('status'))
            ->when(
                $request->query('client_id'),
                fn($q, $id) =>
                $q->where('client_id', $id)
            )
            ->search($request->query('search'))
            ->dateRange($request->query('date_from'), $request->query('date_to'))
            ->sorted(
                $request->query('sort_by', 'created_at'),
                $request->query('sort_dir', 'desc')
            )
            ->paginate(min((int) $request->query('per_page', 15), 100));

        return response()->json($invoices);
        //return InvoiceResource::collection($invoices);
    }

    /**
     * Create a new invoice with line items in a single request.
     * Wraps everything in a transaction — if any item fails, nothing is saved.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = DB::transaction(function () use ($request) {
            $invoiceData = $request->safe()->except('items');

            $invoice = $request->user()->invoices()->create([
                ...$invoiceData,
                'invoice_number'  => Invoice::generateNumber($request->user()->id),
                'subtotal'        => 0,
                'tax_amount'      => 0,
                'total_amount'    => 0,
                'currency'        => $request->currency
                    ?? $request->user()->currency
                    ?? 'USD',
            ]);

            // Create line items (model observers auto-calculate line_total
            // and trigger invoice recalculation after each save)
            foreach ($request->items as $index => $itemData) {
                $invoice->items()->create([
                    'description' => $itemData['description'],
                    'quantity'    => $itemData['quantity'],
                    'unit_price'  => $itemData['unit_price'],
                    'sort_order'  => $index,
                ]);
            }

            //return $invoice->fresh(['items', 'client']);
            return new InvoiceResource($invoice->fresh(['items', 'client']));
        });

        return response()->json([
            'message' => 'Invoice created.',
            'invoice' => $invoice,
        ], 201);
    }

    /**
     * Showing a single invoice with all line items and client details.
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $invoice->load(['client', 'items']);
        

        // Auto-mark overdue on retrieval
        $invoice->checkAndMarkOverdue();

        return response()->json([
            'invoice'      => new InvoiceResource($invoice->fresh(['items', 'client'])), //$invoice->fresh(['client', 'items']),
            'is_overdue'   => $invoice->is_overdue,
            'days_overdue' => $invoice->days_overdue,
        ]);
    }

    /**
     * Update invoice details and/or replace all line items.
     * Uses a transaction to keep invoice + items consistent.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        // Cannot edit a paid or cancelled invoice
        if (in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED])) {
            return response()->json([
                'message' => "A {$invoice->status} invoice cannot be edited.",
            ], 422);
        }

        DB::transaction(function () use ($request, $invoice) {
            $invoice->update($request->safe()->except('items'));

            // Replace all items if provided
            if ($request->has('items')) {
                $invoice->items()->delete();
                foreach ($request->items as $index => $itemData) {
                    $invoice->items()->create([
                        'description' => $itemData['description'],
                        'quantity'    => $itemData['quantity'],
                        'unit_price'  => $itemData['unit_price'],
                        'sort_order'  => $index,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Invoice updated.',
            'invoice' => new InvoiceResource($invoice->fresh(['items', 'client'])),
        ]);
    }

    /**
     * Transition invoice status via dedicated action endpoints.
     * Valid transitions:
     *   draft     → sent
     *   sent      → paid | overdue | cancelled
     *   overdue   → paid | cancelled
     *   draft     → cancelled
     */
    public function updateStatus(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $request->validate([
            'status' => 'required|in:' . implode(',', Invoice::STATUSES),
        ]);

        $newStatus = $request->status;
        $current   = $invoice->status;

        $allowed = [
            Invoice::STATUS_DRAFT    => [Invoice::STATUS_SENT, Invoice::STATUS_CANCELLED],
            Invoice::STATUS_SENT     => [Invoice::STATUS_PAID, Invoice::STATUS_OVERDUE, Invoice::STATUS_CANCELLED],
            Invoice::STATUS_OVERDUE  => [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED],
        ];

        if (! isset($allowed[$current]) || ! in_array($newStatus, $allowed[$current])) {
            return response()->json([
                'message' => "Cannot transition from '{$current}' to '{$newStatus}'.",
            ], 422);
        }

        if ($newStatus === Invoice::STATUS_PAID) {
            $invoice->markAsPaid();
        } else {
            $invoice->update(['status' => $newStatus]);
        }

        return response()->json([
            'message' => "Invoice marked as {$newStatus}.",
            'invoice' => new InvoiceResource($invoice->fresh(['items', 'client'])), //$invoice->fresh(['items', 'client']), 
        ]);
    }

    /**
     * Soft-delete (archive) an invoice.
     * Only draft or cancelled invoices can be deleted.
     */
    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        if (! in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_CANCELLED])) {
            return response()->json([
                'message' => 'Only draft or cancelled invoices can be deleted.',
            ], 422);
        }

        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted.']);
    }

    /**
     * Dashboard summary statistics for the authenticated user.
     */
    public function summary(Request $request): DashboardResource
    {
        $request->validate([]);

        $userId = $request->user()->id;

        $stats = Invoice::forUser($userId)
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as overdue_count,
                SUM(CASE WHEN status = ? THEN total_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = ? THEN total_amount ELSE 0 END) as total_outstanding,
                SUM(CASE WHEN status = ? THEN total_amount ELSE 0 END) as total_overdue
            ', [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID,
                Invoice::STATUS_OVERDUE,
                Invoice::STATUS_PAID,
                Invoice::STATUS_SENT,
                Invoice::STATUS_OVERDUE,
            ])
            ->first();

        // Recent activity — last 5 invoices
        $recent = Invoice::forUser($userId)
            ->with('client:id,name,company')
            ->latest()
            ->limit(5)
            ->get(['id', 'invoice_number', 'client_id', 'status', 'total_amount', 'due_date', 'issue_date']);

        // Monthly revenue for current year (for chart)
        $monthly = Invoice::forUser($userId)
            ->where('status', Invoice::STATUS_PAID)
            ->whereYear('paid_at', now()->year)
            ->selectRaw('MONTH(paid_at) as month, SUM(total_amount) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        /**return response()->json([
            'stats'          => $stats,
            'recent_invoices' => $recent,
            'monthly_revenue' => $monthly,
        ]);**/
        return new DashboardResource([
            'stats' => $stats,
            'recent_invoices' => $recent,
            'monthly_revenue' => $monthly,
        ]);
    }

    public function overdue(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        // Auto-promote any sent+past-due invoices to overdue status
        $request->user()
            ->invoices()
            ->where('status', Invoice::STATUS_SENT)
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => Invoice::STATUS_OVERDUE]);

        $invoices = $request->user()
            ->invoices()
            ->with('client:id,name,company,email')
            ->where('status', Invoice::STATUS_OVERDUE)
            ->orderBy('due_date', 'asc')
            ->paginate($request->query('per_page', 15));

        $totalOverdue = $request->user()
            ->invoices()
            ->where('status', Invoice::STATUS_OVERDUE)
            ->sum('total_amount');

        return response()->json(['total_overdue_amount' => $totalOverdue, 'invoices' => $invoices]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        abort_if(
            $invoice->user_id !== $request->user()->id,
            403,
            'You do not have permission to access this invoice.'
        );
    }
}
