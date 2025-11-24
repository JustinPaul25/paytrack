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
        // Main users page - show overview with links to staff and customers
        $totalUsers = User::count();
        $totalStaff = User::role(['Admin', 'Staff'])->count();
        $totalCustomers = Customer::count();

        return Inertia::render('users/Index', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalStaff' => $totalStaff,
                'totalCustomers' => $totalCustomers,
            ],
        ]);
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
        $roles = Role::all(['id', 'name']);
        return Inertia::render('users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => 'array',
            'roles.*' => 'integer|exists:roles,id',
            'profile_image' => 'nullable|image|max:20480',
        ]);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        if (!empty($validated['roles'])) {
            $roleNames = \Spatie\Permission\Models\Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
            $user->syncRoles($roleNames);
        }
        if ($request->hasFile('profile_image')) {
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile');
        }
        if ($request->has('create_another')) {
            return redirect()->route('users.create')->with('success', 'User created successfully!');
        }
        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function edit(User $user)
    {
        $roles = Role::all(['id', 'name']);
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
            'profile_image' => 'nullable|image|max:20480',
        ]);
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
            $user->clearMediaCollection('profile');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile');
        }
        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        // return redirect()->route('users.index')->with('success', 'User deleted successfully!');
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
} 