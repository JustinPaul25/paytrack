<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\CustomerRequest;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->paginate(10);
        return Inertia::render('customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function create()
    {
        return Inertia::render('customers/Create');
    }

    public function store(CustomerRequest $request)
    {
        $data = $request->validated();
        $customer = Customer::create($data);
        if ($request->hasFile('profile_image')) {
            $customer->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }
        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        $customer->load('media');
        return Inertia::render('customers/Edit', [
            'customer' => $customer,
            'profile_image_url' => $customer->getFirstMediaUrl('profile_image'),
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
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
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
} 