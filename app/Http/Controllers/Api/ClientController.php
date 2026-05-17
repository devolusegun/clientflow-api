<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * List all clients for the authenticated user.
     * Supports search, sorting, and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection //JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:100',
            'sort_by' => 'sometimes|string|in:name,email,company,created_at',
            'sort_dir' => 'sometimes|string|in:asc,desc',
        ]);

        /**$query = $request->user()
            ->clients()
            ->withCount('invoices')
            ->withSum('invoices', 'total_amount');

        // Search by name, email, or company
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy  = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $allowed = ['name', 'email', 'company', 'created_at'];
        if (in_array($sortBy, $allowed)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $clients = $query->paginate(min((int) $request->query('per_page', 15), 100));

        return response()->json($clients);*/
        $clients = $request->user()
            ->clients()
            ->withCount('invoices')
            ->withSum('invoices', 'total_amount')
            ->search($request->query('search'))
            ->sorted(
                $request->query('sort_by', 'created_at'),
                $request->query('sort_dir', 'desc')
            )
            ->paginate(min((int) $request->query('per_page', 15), 100));

        //
        return ClientResource::collection($clients);
    }   

    /**
     * Create a new client.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $request->user()->clients()->create($request->validated());

        return response()->json([
            'message' => 'Client created successfully.',
            'client'  => $client,
        ], 201);
    }

    /**
     * Show a single client with their invoice summary.
     */
    public function show(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);

        $client->load(['invoices' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return response()->json([
            'client'          => $client,
            'total_billed'    => $client->total_billed,
            'total_paid'      => $client->total_paid,
            'invoice_summary' => $client->invoice_summary,
        ]);
    }

    /**
     * Update a client's details.
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);

        $client->update($request->validated());

        return response()->json([
            'message' => 'Client updated.',
            'client'  => $client->fresh(),
        ]);
    }

    /**
     * Soft-delete a client.
     * Clients with invoices are archived, not permanently deleted.
     */
    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);

        if ($client->invoices()->exists()) {
            $client->delete(); // soft delete — invoices preserved
            return response()->json([
                'message' => 'Client archived. Their invoices have been preserved.',
            ]);
        }

        $client->forceDelete();

        return response()->json([
            'message' => 'Client permanently deleted.',
        ]);
    }

    /**
     * Restore a soft-deleted client.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $client = Client::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $client->restore();

        return response()->json([
            'message' => 'Client restored.',
            'client'  => $client,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function authorizeClient(Request $request, Client $client): void
    {
        abort_if(
            $client->user_id !== $request->user()->id,
            403,
            'You do not have permission to access this client.'
        );
    }
}
