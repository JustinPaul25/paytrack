<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRegistrationRequest;
use App\Models\Customer;
use App\Models\CustomerLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class CustomerRegistrationController extends Controller
{
    /**
     * Show the customer registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/CustomerRegister');
    }

    /**
     * Handle an incoming customer registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(CustomerRegistrationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        // Only save location if it's valid (not 0,0 or empty)
        // Ensure lat and lng are numbers, not strings
        $location = null;
        if (isset($data['location']) && 
            is_array($data['location']) && 
            isset($data['location']['lat'], $data['location']['lng'])) {
            $lat = is_numeric($data['location']['lat']) ? (float) $data['location']['lat'] : null;
            $lng = is_numeric($data['location']['lng']) ? (float) $data['location']['lng'] : null;
            
            // Only save if coordinates are valid and not 0,0
            if ($lat !== null && $lng !== null && ($lat != 0 || $lng != 0)) {
                $location = [
                    'lat' => $lat,
                    'lng' => $lng,
                ];
            }
        }
        
        // Create customer (unverified by default - admin must verify)
        $customer = Customer::create([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'barangay' => $data['barangay'] ?? null,
            'city_municipality' => $data['city_municipality'] ?? null,
            'province' => $data['province'] ?? null,
            'location' => $location,
            'verified_at' => null, // Customer needs admin verification
        ]);

        // Create corresponding user account with customer's password
        $user = User::create([
            'name' => $customer->name,
            'email' => $customer->email,
            'password' => Hash::make($data['password']),
        ]);

        // Assign "Customer" role if roles system is present
        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles(['Customer']);
        }

        // Do NOT mark email as verified - admin verification is required for login

        event(new Registered($user));

        // Dispatch event to notify admins of new customer registration
        event(new \App\Events\CustomerRegistered($customer));

        // Log the customer self-registration (user_id will be null since it's self-registration)
        CustomerLog::create([
            'customer_id' => $customer->id,
            'user_id' => null, // Self-registration, no admin/staff involved
            'action' => 'registered',
            'description' => "Customer {$customer->name} registered themselves.",
            'changes' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('login')->with('status', 'registration-successful');
    }
}

