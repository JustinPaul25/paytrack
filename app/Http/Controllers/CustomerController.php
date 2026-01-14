<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\CustomerRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerCredentials;
use App\Mail\CustomerAccountVerified;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $pending = $request->boolean('pending', false);

        $query = Customer::query();

        // Exclude customers with trashed (deleted) user accounts
        // These customers are considered archived and should only appear in archived views
        $query->where(function ($q) {
            $q->whereDoesntHave('user')
              ->orWhereHas('user', function ($subQ) {
                  $subQ->whereNull('deleted_at');
              });
        });

        // Filter by pending verifications if requested
        if ($pending) {
            $query->whereNull('verified_at');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->orderBy('updated_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Optional stats for the collapsible widget (safe if not used)
        $stats = [
            'totalCustomers' => Customer::count(),
            'customersWithCompany' => Customer::whereNotNull('company_name')->where('company_name', '!=', '')->count(),
            'customersWithPhone' => Customer::whereNotNull('phone')->where('phone', '!=', '')->count(),
            'recentlyAdded' => Customer::where('created_at', '>=', now()->subDays(7))->count(),
            'pendingApprovals' => Customer::whereNull('verified_at')->count(),
        ];

        return Inertia::render('customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'pending' => $pending,
            ],
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        // Only admins can create customers
        if (!auth()->user()->hasRole('Admin')) {
            return redirect()->route('customers.index')
                ->with('error', 'You do not have permission to create customer data.');
        }
        
        return Inertia::render('customers/Create');
    }

    public function store(CustomerRequest $request)
    {
        // Only admins can create customers
        if (!auth()->user()->hasRole('Admin')) {
            return redirect()->route('customers.index')
                ->with('error', 'You do not have permission to create customer data.');
        }
        
        $data = $request->validated();
        $customer = Customer::create($data);
        if ($request->hasFile('profile_image')) {
            $customer->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        // Create corresponding user account for the customer
        $generatedPassword = Str::random(10);
        $user = User::create([
            'name' => $customer->name,
            'email' => $customer->email,
            'password' => Hash::make($generatedPassword),
        ]);
        // Assign "Customer" role if roles system is present
        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles(['Customer']);
        }
        // Email credentials to the customer
        Mail::to($customer->email)->queue(new CustomerCredentials($customer->name, $customer->email, $generatedPassword));

        // Log the customer creation
        $this->logCustomerActivity($customer, 'created', "Customer {$customer->name} was created by admin.");

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        // Admin and Staff can view customers
        if (!auth()->user()->hasAnyRole(['Admin', 'Staff'])) {
            return redirect()->route('customers.index')
                ->with('error', 'You do not have permission to view customer data.');
        }
        
        $customer->load('media');
        return Inertia::render('customers/Show', [
            'customer' => $customer,
            'profile_image_url' => $customer->getFirstMediaUrl('profile_image'),
        ]);
    }

    public function edit(Customer $customer)
    {
        // Only admins can edit customers
        if (!auth()->user()->hasRole('Admin')) {
            return redirect()->route('customers.index')
                ->with('error', 'You do not have permission to edit customer data.');
        }
        
        $customer->load('media');
        return Inertia::render('customers/Edit', [
            'customer' => $customer,
            'profile_image_url' => $customer->getFirstMediaUrl('profile_image'),
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        // Only admins can update customers
        if (!auth()->user()->hasRole('Admin')) {
            return redirect()->route('customers.index')
                ->with('error', 'You do not have permission to modify customer data.');
        }
        
        $data = $request->validated();
        $originalData = $customer->getOriginal();
        $customer->update($data);
        
        // Track changes
        $changes = [];
        foreach ($data as $key => $value) {
            if (isset($originalData[$key]) && $originalData[$key] != $value) {
                $changes[$key] = [
                    'old' => $originalData[$key],
                    'new' => $value,
                ];
            }
        }
        
        if ($request->hasFile('profile_image')) {
            $customer->clearMediaCollection('profile_image');
            $customer->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
            $changes['profile_image'] = ['old' => 'previous image', 'new' => 'new image uploaded'];
        }
        
        // Log the customer update
        $description = !empty($changes) 
            ? "Customer {$customer->name} was updated. Fields changed: " . implode(', ', array_keys($changes))
            : "Customer {$customer->name} was updated.";
        $this->logCustomerActivity($customer, 'updated', $description, $changes);
        
        // Redirect to users.index if coming from user management, otherwise customers.index
        if ($request->query('from') === 'users') {
            return redirect()->route('users.index')->with('success', 'Customer updated successfully.');
        }
        
        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        // Only admins can delete customers
        if (!auth()->user()->hasRole('Admin')) {
            return redirect()->route('customers.index')
                ->with('error', 'You do not have permission to delete customer data.');
        }
        
        // Check if related records exist (for informational purposes)
        $hasInvoices = \App\Models\Invoice::where('customer_id', $customer->id)->exists();
        $hasDeliveries = \App\Models\Delivery::where('customer_id', $customer->id)->exists();

        // Log the customer deletion before deleting
        $this->logCustomerActivity($customer, 'deleted', "Customer {$customer->name} was deleted by admin.");

        // Remove profile image if present
        $customer->clearMediaCollection('profile_image');
        
        // Always soft delete the associated user account to archive the customer
        // This allows the customer to appear in archived when filtering
        // The customer record itself is kept for historical data (invoices/deliveries)
        // Find user by email, including trashed users (in case it was already soft deleted)
        $user = User::withTrashed()->where('email', $customer->email)->first();
        if ($user && !$user->trashed()) {
            $user->delete(); // Soft delete the user (archives the customer)
        } elseif ($user && $user->trashed()) {
            // User is already soft deleted, nothing to do
        } else {
            // If no user exists and no related records, we can safely delete the customer
            if (!$hasInvoices && !$hasDeliveries) {
                $customer->delete();
            }
            // If no user but has related records, we can't delete the customer record
            // but there's nothing to archive, so just return success
        }

        // Redirect back to the previous page (could be users management or customers page)
        $message = 'Customer archived successfully.';
        if ($hasInvoices || $hasDeliveries) {
            $message .= ' Customer record kept for historical data (invoices/deliveries).';
        }
        return redirect()->back()->with('success', $message);
    }

    public function approve(Customer $customer)
    {
        // Only admins can verify customers
        if (!auth()->user()->hasRole('Admin')) {
            return redirect()->route('customers.index')
                ->with('error', 'You do not have permission to verify customers.');
        }

        // Check if already verified
        if ($customer->isVerified()) {
            return redirect()->route('customers.index')
                ->with('info', 'This customer is already verified.');
        }

        // Verify the customer
        $customer->update([
            'verified_at' => now(),
        ]);

        // Also verify the associated user account's email
        $user = $customer->user;
        if ($user && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Mark all customer registration notifications as read for all admins
        // Since the customer is now approved, no admin needs to see this notification anymore
        Notification::where('type', 'customer.registered')
            ->where('notifiable_type', Customer::class)
            ->where('notifiable_id', $customer->id)
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        // Send verification email to customer
        $loginUrl = route('login', [], true);
        Mail::to($customer->email)->queue(new CustomerAccountVerified($customer->name, $loginUrl));

        // Log the customer verification
        $this->logCustomerActivity($customer, 'verified', "Customer {$customer->name} was verified by admin.");

        // Redirect to users table if current user is an admin
        if (auth()->user()->hasRole('Admin')) {
            return redirect()->route('users.index')
                ->with('success', "Customer {$customer->name} has been verified and notified via email.");
        }

        return redirect()->route('customers.index', ['pending' => true])
            ->with('success', "Customer {$customer->name} has been verified and notified via email.");
    }

    /**
     * Helper method to log customer activities.
     */
    protected function logCustomerActivity(
        Customer $customer,
        string $action,
        string $description,
        ?array $changes = null
    ): void {
        CustomerLog::create([
            'customer_id' => $customer->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
} 