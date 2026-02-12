<?php

namespace App\Http\Controllers;

use App\Models\DeletionRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeletionRequestController extends Controller
{
    /**
     * Display a listing of deletion requests
     */
    public function index(Request $request)
    {
        // Only Owner and Admin can view deletion requests
        if (!auth()->user()->hasAnyRole(['Owner', 'Admin'])) {
            abort(403);
        }

        $status = $request->input('status', '');
        $query = DeletionRequest::with(['requester', 'reviewer'])
            ->orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return Inertia::render('deletion-requests/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    /**
     * Approve a deletion request
     */
    public function approve(Request $request, DeletionRequest $deletionRequest)
    {
        // Only Owner and Admin can approve deletion requests
        if (!auth()->user()->hasAnyRole(['Owner', 'Admin'])) {
            abort(403);
        }

        if ($deletionRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This deletion request has already been processed.');
        }

        try {
            // Get the deletable model
            $deletable = $deletionRequest->deletable;

            if (!$deletable) {
                return redirect()->back()->with('error', 'The item to be deleted no longer exists.');
            }

            // Handle Customer deletion specially (has complex logic)
            if ($deletable instanceof \App\Models\Customer) {
                $this->deleteCustomer($deletable);
            } elseif ($deletable instanceof \App\Models\Invoice) {
                // Handle Invoice deletion (needs to delete invoice items first)
                \Illuminate\Support\Facades\DB::beginTransaction();
                try {
                    $deletable->invoiceItems()->delete();
                    $deletable->delete();
                    \Illuminate\Support\Facades\DB::commit();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    throw $e;
                }
            } else {
                // Perform standard deletion
                // Check if model has a clearMediaCollection method
                if (method_exists($deletable, 'clearMediaCollection')) {
                    try {
                        if ($deletable instanceof \App\Models\Product) {
                            $deletable->clearMediaCollection('images');
                        } elseif ($deletable instanceof \App\Models\User) {
                            $deletable->clearMediaCollection('profile');
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to clear media collection: ' . $e->getMessage());
                    }
                }
                $deletable->delete();
            }

            // Update the deletion request
            $deletionRequest->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'review_notes' => $request->input('review_notes'),
                'reviewed_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Deletion request approved and item deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to approve deletion request: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to approve deletion request: ' . $e->getMessage());
        }
    }

    /**
     * Reject a deletion request
     */
    public function reject(Request $request, DeletionRequest $deletionRequest)
    {
        // Only Owner and Admin can reject deletion requests
        if (!auth()->user()->hasAnyRole(['Owner', 'Admin'])) {
            abort(403);
        }

        if ($deletionRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This deletion request has already been processed.');
        }

        $deletionRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'review_notes' => $request->input('review_notes'),
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Deletion request rejected.');
    }

    /**
     * Handle Customer deletion with complex logic
     */
    private function deleteCustomer(\App\Models\Customer $customer)
    {
        // Check if related records exist
        $hasInvoices = \App\Models\Invoice::where('customer_id', $customer->id)->exists();
        $hasDeliveries = \App\Models\Delivery::where('customer_id', $customer->id)->exists();

        // Log the customer deletion
        \App\Models\CustomerLog::create([
            'customer_id' => $customer->id,
            'action' => 'deleted',
            'description' => "Customer {$customer->name} was deleted by " . (auth()->user()->hasRole('Owner') ? 'owner' : 'admin') . " via deletion request.",
            'user_id' => auth()->id(),
        ]);

        // Remove profile image if present
        $customer->clearMediaCollection('profile_image');
        
        // Always soft delete the associated user account to archive the customer
        $user = \App\Models\User::withTrashed()->where('email', $customer->email)->first();
        if ($user && !$user->trashed()) {
            $user->delete(); // Soft delete the user (archives the customer)
        } elseif (!$user) {
            // If no user exists and no related records, we can safely delete the customer
            if (!$hasInvoices && !$hasDeliveries) {
                $customer->delete();
            }
        }
    }
}
