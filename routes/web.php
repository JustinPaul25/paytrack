<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\SalesAnalyticsController;
use App\Http\Controllers\SalesTransactionController;


Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::get('dashboard', [SalesAnalyticsController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// (refund routes removed)

Route::middleware(['auth'])->group(function () {
    // (refund-request admin routes removed)
    // Roles management - Admin only
    Route::middleware('role:Admin')->group(function () {
        Route::get('roles', [RolesController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RolesController::class, 'create'])->name('roles.create');
        Route::post('roles', [RolesController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [RolesController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RolesController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RolesController::class, 'destroy'])->name('roles.destroy');
    });

    // User CRUD routes
    // Users management - Admin only
    Route::middleware('role:Admin')->group(function () {
        Route::get('users', [UsersController::class, 'index'])->name('users.index');
        Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
        Route::post('users', [UsersController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::post('users/{user}', [UsersController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
    });

    // Category CRUD routes (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::get('categories', [\App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/create', [\App\Http\Controllers\CategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [\App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [\App\Http\Controllers\CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Products (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        // Soft-deleted product utilities (place BEFORE resource to avoid conflict with products/{product})
        Route::get('products/trashed', [ProductController::class, 'trashedIndex'])->name('products.trashed.index'); // Inertia page
        Route::get('products-trashed', [ProductController::class, 'trashed'])->name('products.trashed'); // JSON
        Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
        // Product CRUD routes
        Route::resource('products', ProductController::class)->except(['update']);
        Route::post('products/{product}', [ProductController::class, 'update'])->name('products.update');
    });

    // Customers (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::resource('customers', CustomerController::class)->except(['update']);
        Route::post('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    });

    // Invoice CRUD routes (all authenticated can view/manage per your policy)
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class)->except(['update']);
    Route::post('invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('invoices/{invoice}/mark-paid', [\App\Http\Controllers\InvoiceController::class, 'markPaid'])->name('invoices.markPaid');

    // Refund Requests
    Route::get('refund-requests', [\App\Http\Controllers\RefundRequestController::class, 'index'])
        ->middleware('role:Admin')
        ->name('refundRequests.index');
    Route::get('invoices/{invoice}/refund-request', [\App\Http\Controllers\RefundRequestController::class, 'create'])->name('refundRequests.create');
    Route::post('invoices/{invoice}/refund-request', [\App\Http\Controllers\RefundRequestController::class, 'store'])->name('refundRequests.store');
    Route::post('refund-requests/{refundRequest}/approve', [\App\Http\Controllers\RefundRequestController::class, 'approve'])
        ->middleware('role:Admin')
        ->name('refundRequests.approve');
    // (refund routes removed)

    // Deliveries (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::get('deliveries/shortcut', [DeliveryController::class, 'shortcut'])->name('deliveries.shortcut');
        Route::resource('deliveries', DeliveryController::class)->except(['update']);
        // Accept update via POST for compatibility, and PUT/PATCH for RESTful clients
        Route::post('deliveries/{delivery}', [DeliveryController::class, 'update'])->name('deliveries.update');
        Route::match(['put', 'patch'], 'deliveries/{delivery}', [DeliveryController::class, 'update']);
    });

    // Sales Analytics routes (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::get('sales/analytics', [SalesAnalyticsController::class, 'index'])->name('sales.analytics');
        Route::get('sales/transactions', [SalesTransactionController::class, 'index'])->name('sales.transactions');
    });
    
    // Sales Prediction routes
    Route::get('sales/predictions', [\App\Http\Controllers\SalesPredictionController::class, 'getPredictions'])->name('sales.predictions');

    // Expenses routes (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::resource('expenses', \App\Http\Controllers\ExpenseController::class)->except(['update']);
        Route::post('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('expenses.update');
    });

    // Cash Flow (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::get('finance/cash-flow', [CashFlowController::class, 'index'])->name('finance.cash-flow');
        Route::get('finance/reports', [FinancialReportController::class, 'index'])->name('finance.reports');
        Route::get('finance/reports/export', [FinancialReportController::class, 'export'])->name('finance.reports.export');
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
