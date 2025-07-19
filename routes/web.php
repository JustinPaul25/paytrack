<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\SalesAnalyticsController;
use App\Http\Controllers\SalesTransactionController;
use App\Http\Controllers\RefundController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('roles', [RolesController::class, 'index'])->name('roles.index');
    Route::get('roles/create', [RolesController::class, 'create'])->name('roles.create');
    Route::post('roles', [RolesController::class, 'store'])->name('roles.store');
    Route::get('roles/{role}/edit', [RolesController::class, 'edit'])->name('roles.edit');
    Route::put('roles/{role}', [RolesController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RolesController::class, 'destroy'])->name('roles.destroy');

    // User CRUD routes
    Route::get('users', [UsersController::class, 'index'])->name('users.index');
    Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
    Route::post('users', [UsersController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::post('users/{user}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');

    // Category CRUD routes
    Route::get('categories', [\App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/create', [\App\Http\Controllers\CategoryController::class, 'create'])->name('categories.create');
    Route::post('categories', [\App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{category}/edit', [\App\Http\Controllers\CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('categories/{category}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');

    // Product CRUD routes
    Route::resource('products', ProductController::class)->except(['update']);
    Route::post('products/{product}', [ProductController::class, 'update'])->name('products.update');

    // Customer CRUD routes
    Route::resource('customers', CustomerController::class)->except(['update']);
    Route::post('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    // Invoice CRUD routes
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class)->except(['update']);
    Route::post('invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');

    // Refund CRUD routes
    Route::resource('refunds', RefundController::class)->except(['update']);
    Route::post('refunds/{refund}', [RefundController::class, 'update'])->name('refunds.update');
    
    // Refund action routes
    Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
    Route::post('refunds/{refund}/process', [RefundController::class, 'process'])->name('refunds.process');
    Route::post('refunds/{refund}/complete', [RefundController::class, 'complete'])->name('refunds.complete');
    Route::post('refunds/{refund}/cancel', [RefundController::class, 'cancel'])->name('refunds.cancel');
    
    // Refund API routes
    Route::get('refunds/invoice-items', [RefundController::class, 'getInvoiceItems'])->name('refunds.invoice-items');
    Route::get('refunds/stats', [RefundController::class, 'getStats'])->name('refunds.stats');

    // Delivery CRUD routes
    Route::resource('deliveries', DeliveryController::class)->except(['update']);
    Route::post('deliveries/{delivery}', [DeliveryController::class, 'update'])->name('deliveries.update');

    // Sales Analytics routes
    Route::get('sales/analytics', [SalesAnalyticsController::class, 'index'])->name('sales.analytics');
    Route::get('sales/transactions', [SalesTransactionController::class, 'index'])->name('sales.transactions');

    // Demo Expenses routes
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class)->except(['update']);
    Route::post('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('expenses.update');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
