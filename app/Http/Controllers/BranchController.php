<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Traits\HandlesDeletionRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\BranchRequest;

class BranchController extends Controller
{
    use HandlesDeletionRequests;
    public function index(Request $request)
    {
        $query = Branch::query();
        
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('manager_name', 'like', "%{$search}%");
            });
        }
        
        $branches = $query->latest()->paginate(10)->withQueryString();
        
        // Calculate stats
        $stats = [
            'totalBranches' => Branch::count(),
            'activeBranches' => Branch::active()->count(),
            'inactiveBranches' => Branch::inactive()->count(),
            'maintenanceBranches' => Branch::maintenance()->count(),
        ];
        
        return Inertia::render('branches/Index', [
            'branches' => $branches,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('branches/Create');
    }

    public function store(BranchRequest $request)
    {
        try {
            $data = $request->validated();
            $branch = Branch::create($data);
            
            if ($request->hasFile('branch_image')) {
                $branch->addMediaFromRequest('branch_image')->toMediaCollection('branch_image');
            }
            
            return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create branch. Please try again.');
        }
    }

    public function show(Branch $branch)
    {
        $branch->load('media');
        return Inertia::render('branches/Show', [
            'branch' => $branch,
            'branch_image_url' => $branch->getFirstMediaUrl('branch_image'),
        ]);
    }

    public function edit(Branch $branch)
    {
        $branch->load('media');
        return Inertia::render('branches/Edit', [
            'branch' => $branch,
            'branch_image_url' => $branch->getFirstMediaUrl('branch_image'),
        ]);
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        try {
            $data = $request->validated();
            $branch->update($data);
            
            if ($request->hasFile('branch_image')) {
                $branch->clearMediaCollection('branch_image');
                $branch->addMediaFromRequest('branch_image')->toMediaCollection('branch_image');
            }
            
            return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update branch. Please try again.');
        }
    }

    public function destroy(Branch $branch)
    {
        return $this->handleDeletion(
            $branch,
            'branch',
            request()->input('reason'),
            route('branches.index')
        );
    }
} 