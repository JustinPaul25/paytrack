<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerVerificationController extends Controller
{
    /**
     * Display a listing of customers for verification.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        $search = $request->input('search');
        $status = $request->input('status', 'pending'); // pending, verified, all

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by verification status
        if ($status === 'pending') {
            $query->whereNull('verified_at');
        } elseif ($status === 'verified') {
            $query->whereNotNull('verified_at');
        }
        // 'all' shows both verified and unverified

        $customers = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $customers->getCollection()->transform(function ($customer) {
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
                'verified_at' => $customer->verified_at,
                'created_at' => $customer->created_at,
                'is_verified' => $customer->isVerified(),
            ];
        });

        return Inertia::render('users/CustomerVerification', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Verify a customer.
     */
    public function verify(Customer $customer)
    {
        $customer->verified_at = now();
        $customer->save();

        // Also mark the associated user's email as verified if not already
        $user = User::where('email', $customer->email)->first();
        if ($user && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->back()->with('success', 'Customer verified successfully!');
    }

    /**
     * Unverify a customer.
     */
    public function unverify(Customer $customer)
    {
        $customer->verified_at = null;
        $customer->save();

        return redirect()->back()->with('success', 'Customer verification removed successfully!');
    }
}
