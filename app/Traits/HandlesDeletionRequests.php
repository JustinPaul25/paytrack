<?php

namespace App\Traits;

use App\Models\DeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

trait HandlesDeletionRequests
{
    /**
     * Handle deletion request or direct deletion based on user role
     * 
     * @param mixed $deletable The model instance to delete
     * @param string $deletableName Human-readable name for the item
     * @param string|null $reason Optional reason for deletion
     * @param string|null $redirectRoute Route to redirect to after creating request
     * @return RedirectResponse|JsonResponse
     */
    protected function handleDeletion($deletable, string $deletableName, ?string $reason = null, ?string $redirectRoute = null)
    {
        $user = auth()->user();

        // Owner and Admin can delete directly
        if ($user->hasAnyRole(['Owner', 'Admin'])) {
            try {
                // Check if model has a clearMediaCollection method (for products, customers, etc.)
                if (method_exists($deletable, 'clearMediaCollection')) {
                    // Try to clear media collections
                    try {
                        if ($deletable instanceof \App\Models\Product) {
                            $deletable->clearMediaCollection('images');
                        } elseif ($deletable instanceof \App\Models\Customer) {
                            $deletable->clearMediaCollection('profile_image');
                        } elseif ($deletable instanceof \App\Models\User) {
                            $deletable->clearMediaCollection('profile');
                        }
                    } catch (\Exception $e) {
                        // Continue even if media clearing fails
                        \Log::warning('Failed to clear media collection: ' . $e->getMessage());
                    }
                }

                $deletable->delete();

                if (request()->expectsJson()) {
                    return response()->noContent();
                }

                $redirectRoute = $redirectRoute ?? request()->headers->get('referer') ?? '/';
                return redirect($redirectRoute)->with('success', ucfirst($deletableName) . ' deleted successfully.');
            } catch (\Exception $e) {
                \Log::error('Failed to delete item: ' . $e->getMessage());
                
                if (request()->expectsJson()) {
                    return response()->json([
                        'message' => 'Failed to delete: ' . $e->getMessage()
                    ], 500);
                }

                return redirect()->back()->with('error', 'Failed to delete: ' . $e->getMessage());
            }
        }

        // Staff needs to request permission
        if ($user->hasRole('Staff')) {
            // Create deletion request
            DeletionRequest::create([
                'requested_by' => $user->id,
                'deletable_type' => get_class($deletable),
                'deletable_id' => $deletable->id,
                'deletable_name' => $deletableName,
                'reason' => $reason ?? 'Deletion requested by staff member.',
                'status' => 'pending',
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Deletion request submitted. Waiting for owner/admin approval.',
                    'requires_approval' => true,
                ], 202);
            }

            $redirectRoute = $redirectRoute ?? request()->headers->get('referer') ?? '/';
            return redirect($redirectRoute)->with('info', 'Deletion request submitted. Waiting for owner/admin approval.');
        }

        // Customer or other roles - deny access
        abort(403, 'You do not have permission to delete this item.');
    }
}
