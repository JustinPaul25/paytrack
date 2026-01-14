<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        // Unified user management table
        return $this->adminManagement($request);
    }

    public function staff(Request $request)
    {
        $query = User::role(['Admin', 'Staff']);
        
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $users = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();
        $users->getCollection()->transform(function ($user) {
            $roles = $user->roles->pluck('name')->toArray();
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'updated_at' => $user->updated_at,
                'profile_image_url' => $user->profile_image_url,
            ];
        });

        return Inertia::render('users/Staff', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function customers(Request $request)
    {
        $query = Customer::with(['invoices' => function ($q) {
            $q->where('status', 'completed');
        }]);

        $search = $request->input('search');
        $duration = $request->input('duration', 'all'); // all, month, year

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get all customers first to calculate stats, then paginate
        $allCustomers = $query->orderBy('updated_at', 'desc')->get();

        // Calculate purchase statistics for each customer
        $customersWithStats = $allCustomers->map(function ($customer) use ($duration) {
            $invoiceQuery = Invoice::where('customer_id', $customer->id)
                ->where('status', 'completed');

            // Apply duration filter
            if ($duration === 'month') {
                $invoiceQuery->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
            } elseif ($duration === 'year') {
                $invoiceQuery->whereYear('created_at', Carbon::now()->year);
            }

            $invoices = $invoiceQuery->get();
            
            // Get top purchases (products)
            $topProducts = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->join('products', 'invoice_items.product_id', '=', 'products.id')
                ->where('invoices.customer_id', $customer->id)
                ->where('invoices.status', 'completed');

            if ($duration === 'month') {
                $topProducts->whereMonth('invoices.created_at', Carbon::now()->month)
                    ->whereYear('invoices.created_at', Carbon::now()->year);
            } elseif ($duration === 'year') {
                $topProducts->whereYear('invoices.created_at', Carbon::now()->year);
            }

            $topProducts = $topProducts
                ->select('products.name', DB::raw('SUM(invoice_items.quantity) as total_quantity'), DB::raw('SUM(invoice_items.total) as total_amount'))
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_quantity', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'quantity' => $item->total_quantity,
                        'amount' => $item->total_amount / 100, // Convert from cents
                    ];
                });

            $totalPurchases = $invoices->sum('total_amount') / 100; // Convert from cents
            $totalInvoices = $invoices->count();

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'company_name' => $customer->company_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'purok' => $customer->purok,
                'barangay' => $customer->barangay,
                'city_municipality' => $customer->city_municipality,
                'province' => $customer->province,
                'location' => $customer->location,
                'total_purchases' => $totalPurchases,
                'total_invoices' => $totalInvoices,
                'top_products' => $topProducts,
                'updated_at' => $customer->updated_at,
            ];
        });

        // Sort by total purchases descending
        $customersWithStats = $customersWithStats->sortByDesc('total_purchases')->values();

        // Paginate manually
        $currentPage = $request->input('page', 1);
        $perPage = 5;
        $total = $customersWithStats->count();
        $items = $customersWithStats->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Create paginator manually
        $customers = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('users/Customers', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'duration' => $duration,
            ],
        ]);
    }

    public function create()
    {
        // No roles needed since we automatically assign Staff role
        return Inertia::render('users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5MB in kilobytes
                'dimensions:max_width=2000,max_height=2000',
            ],
        ], [
            'name.required' => 'The name field is required.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'profile_image.image' => 'The profile image must be an image file.',
            'profile_image.mimes' => 'The profile image must be a JPEG, PNG, or WebP file.',
            'profile_image.max' => 'The profile image may not be greater than 5MB.',
            'profile_image.dimensions' => 'The profile image dimensions must not exceed 2000x2000 pixels.',
        ]);
        
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            
            // Automatically assign Staff role
            $user->assignRole('Staff');
            
            if ($request->hasFile('profile_image')) {
                try {
                    $user->addMediaFromRequest('profile_image')->toMediaCollection('profile');
                } catch (\Exception $e) {
                    // If image upload fails, continue without image but log the error
                    \Log::error('Failed to upload profile image for user ' . $user->id . ': ' . $e->getMessage());
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['profile_image' => 'Failed to upload profile image. Please try again.']);
                }
            }
            
            if ($request->has('create_another')) {
                return redirect()->route('users.create')->with('success', 'User created successfully!');
            }
            return redirect()->route('users.index')->with('success', 'User created successfully!');
        } catch (\Exception $e) {
            \Log::error('Failed to create user: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create user. Please try again.']);
        }
    }

    public function edit(User $user)
    {
        // Exclude Customer role from available roles (Admin can only assign Admin or Staff)
        $roles = Role::where('name', '!=', 'Customer')->get(['id', 'name']);
        $userRoles = $user->roles->pluck('id')->toArray();
        return Inertia::render('users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $userRoles,
            'profileImageUrl' => $user->profile_image_url,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles' => 'array',
            'roles.*' => 'integer|exists:roles,id',
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5MB in kilobytes
                'dimensions:max_width=2000,max_height=2000',
            ],
        ], [
            'name.required' => 'The name field is required.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
            'roles.array' => 'The roles must be an array.',
            'roles.*.exists' => 'One or more selected roles are invalid.',
            'profile_image.image' => 'The profile image must be an image file.',
            'profile_image.mimes' => 'The profile image must be a JPEG, PNG, or WebP file.',
            'profile_image.max' => 'The profile image may not be greater than 5MB.',
            'profile_image.dimensions' => 'The profile image dimensions must not exceed 2000x2000 pixels.',
        ]);
        
        try {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();
            
            if (!empty($validated['roles'])) {
                $roleNames = \Spatie\Permission\Models\Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
                $user->syncRoles($roleNames);
            }
            
            if ($request->hasFile('profile_image')) {
                try {
                    $user->clearMediaCollection('profile');
                    $user->addMediaFromRequest('profile_image')->toMediaCollection('profile');
                } catch (\Exception $e) {
                    // If image upload fails, continue without updating image but log the error
                    \Log::error('Failed to upload profile image for user ' . $user->id . ': ' . $e->getMessage());
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['profile_image' => 'Failed to upload profile image. Please try again.']);
                }
            }
            
            return redirect()->route('users.index')->with('success', 'User updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Failed to update user ' . $user->id . ': ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update user. Please try again.']);
        }
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    public function archives(Request $request)
    {
        $query = User::onlyTrashed()->role(['Admin', 'Staff']);
        
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $users = $query->orderBy('deleted_at', 'desc')->paginate(10)->withQueryString();
        $users->getCollection()->transform(function ($user) {
            $roles = $user->roles->pluck('name')->toArray();
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'deleted_at' => $user->deleted_at,
                'profile_image_url' => $user->profile_image_url,
            ];
        });

        return Inertia::render('users/Archives', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        return redirect()->route('users.archives')->with('success', 'User restored successfully!');
    }

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->forceDelete();
        return redirect()->route('users.archives')->with('success', 'User permanently deleted!');
    }

    /**
     * Set a user to active status.
     * For staff users: restores if soft-deleted
     * For customers: verifies the customer and restores if soft-deleted
     */
    public function setActive(Request $request, $id)
    {
        $type = $request->input('type'); // 'staff' or 'customer'

        if ($type === 'staff') {
            // Check if user is soft-deleted, if so restore them
            $user = User::withTrashed()->findOrFail($id);
            
            if ($user->trashed()) {
                $user->restore();
                return redirect()->back()->with('success', 'User restored and set to active successfully!');
            } else {
                return redirect()->back()->with('info', 'User is already active.');
            }
        } else {
            // For customers, restore the soft-deleted user
            $customer = Customer::findOrFail($id);
            
            // Find the user by email, including trashed users
            $user = User::withTrashed()->where('email', $customer->email)->first();
            
            if ($user && $user->trashed()) {
                // Restore the soft-deleted user
                $user->restore();
                
                // Also verify the customer if not already verified
                if (!$customer->isVerified()) {
                    $customer->update([
                        'verified_at' => now(),
                    ]);
                }
                
                // Verify the user's email if not already verified
                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
                
                // Redirect to users table if current user is an admin
                if (auth()->user()->hasRole('Admin')) {
                    return redirect()->route('users.index')
                        ->with('success', 'Customer user restored and set to active successfully!');
                }
                
                return redirect()->back()->with('success', 'Customer user restored and set to active successfully!');
            } elseif ($user && !$user->trashed()) {
                // User is not deleted, just verify the customer if needed
                if (!$customer->isVerified()) {
                    $customer->update([
                        'verified_at' => now(),
                    ]);
                    // Redirect to users table if current user is an admin
                    if (auth()->user()->hasRole('Admin')) {
                        return redirect()->route('users.index')
                            ->with('success', 'Customer verified and set to active successfully!');
                    }
                    return redirect()->back()->with('success', 'Customer verified and set to active successfully!');
                }
                return redirect()->back()->with('info', 'Customer is already active.');
            } else {
                // No user exists, just verify the customer
                if (!$customer->isVerified()) {
                    $customer->update([
                        'verified_at' => now(),
                    ]);
                    // Redirect to users table if current user is an admin
                    if (auth()->user()->hasRole('Admin')) {
                        return redirect()->route('users.index')
                            ->with('success', 'Customer verified successfully!');
                    }
                    return redirect()->back()->with('success', 'Customer verified successfully!');
                }
                return redirect()->back()->with('info', 'Customer is already verified.');
            }
        }
    }

    public function adminManagement(Request $request)
    {
        $search = $request->input('search');
        $verificationStatus = $request->input('verification_status'); // 'verified', 'unverified', null (all)
        $userRole = $request->input('user_role'); // 'admin', 'staff', 'customer', null (all)
        $archived = $request->boolean('archived', false);

        $allUsers = collect();

        // Get staff/admin users (not archived unless requested)
        if (!$archived) {
            $staffQuery = User::role(['Admin', 'Staff'])->whereNull('deleted_at');
        } else {
            $staffQuery = User::onlyTrashed()->role(['Admin', 'Staff']);
        }

        if ($search) {
            $staffQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staffUsers = $staffQuery->get();
        $staffUsers->transform(function ($user) {
            $roles = $user->roles->pluck('name')->toArray();
            $primaryRole = in_array('Admin', $roles) ? 'Admin' : (in_array('Staff', $roles) ? 'Staff' : 'User');
            return [
                'id' => $user->id,
                'type' => 'staff',
                'name' => $user->name,
                'company' => null,
                'email' => $user->email,
                'user_role' => $primaryRole,
                'phone' => null,
                'status' => 'Active',
                'is_verified' => true,
                'is_archived' => $user->trashed(),
                'deleted_at' => $user->deleted_at,
                'updated_at' => $user->updated_at,
            ];
        });

        // Apply role filter for staff users
        if ($userRole === 'admin') {
            $staffUsers = $staffUsers->filter(function ($user) {
                return $user['user_role'] === 'Admin';
            });
        } elseif ($userRole === 'staff') {
            $staffUsers = $staffUsers->filter(function ($user) {
                return $user['user_role'] === 'Staff';
            });
        }

        $allUsers = $allUsers->merge($staffUsers);

        // Get customers (only if not filtering by admin/staff role, or if filtering by customer role)
        if (!$userRole || $userRole === 'customer') {
            $customerQuery = Customer::query();
            
            if ($archived) {
                // For archived customers, we need to check if their associated user is deleted
                // Since Customer doesn't have soft deletes, we'll check via User relationship
                $customerQuery->whereHas('user', function ($q) {
                    $q->onlyTrashed();
                });
            } else {
                // Show non-archived customers: those without a user OR with a non-deleted user
                // whereHas excludes trashed models by default, so this will only match non-trashed users
                $customerQuery->where(function ($q) {
                    // Customers without any user
                    $q->whereDoesntHave('user')
                      // OR customers with a non-trashed user (whereHas excludes trashed by default)
                      ->orWhereHas('user');
                });
                // Explicitly exclude customers that have a trashed user using whereNotExists
                $customerQuery->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('users')
                        ->whereColumn('users.email', 'customers.email')
                        ->whereNotNull('users.deleted_at');
                });
            }

            // Apply verification filter for customers
            if ($verificationStatus === 'verified') {
                $customerQuery->whereNotNull('verified_at');
            } elseif ($verificationStatus === 'unverified') {
                $customerQuery->whereNull('verified_at');
            }

            if ($search) {
                $customerQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $customers = $customerQuery->get();
            $customers->transform(function ($customer) {
                $user = $customer->user;
                return [
                    'id' => $customer->id,
                    'type' => 'customer',
                    'name' => $customer->name,
                    'company' => $customer->company_name,
                    'email' => $customer->email,
                    'user_role' => 'Customer',
                    'phone' => $customer->phone,
                    'status' => $customer->isVerified() ? 'Verified' : 'Unverified',
                    'is_verified' => $customer->isVerified(),
                    'is_archived' => $user ? $user->trashed() : false,
                    'deleted_at' => $user ? $user->deleted_at : null,
                    'updated_at' => $customer->updated_at,
                ];
            });

            $allUsers = $allUsers->merge($customers);
        }

        // Sort by updated_at descending
        $allUsers = $allUsers->sortByDesc('updated_at')->values();

        // Paginate manually
        $currentPage = $request->input('page', 1);
        $perPage = 10;
        $total = $allUsers->count();
        $items = $allUsers->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Create paginator manually
        $paginatedUsers = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('users/Index', [
            'users' => $paginatedUsers,
            'filters' => [
                'search' => $search,
                'verification_status' => $verificationStatus,
                'user_role' => $userRole,
                'archived' => $archived,
            ],
        ]);
    }
} 