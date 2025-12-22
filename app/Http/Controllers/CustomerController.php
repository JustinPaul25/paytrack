<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
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
        $customer->update($data);
        if ($request->hasFile('profile_image')) {
            $customer->clearMediaCollection('profile_image');
            $customer->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
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
        
        // Block delete if related records exist to avoid FK errors
        $hasInvoices = \App\Models\Invoice::where('customer_id', $customer->id)->exists();
        $hasDeliveries = \App\Models\Delivery::where('customer_id', $customer->id)->exists();

        if ($hasInvoices || $hasDeliveries) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete this customer because there are related records (invoices or deliveries). Please remove or reassign those first.');
        }

        // Remove profile image if present
        $customer->clearMediaCollection('profile_image');
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
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

        return redirect()->route('customers.index', ['pending' => true])
            ->with('success', "Customer {$customer->name} has been verified and notified via email.");
    }
} 