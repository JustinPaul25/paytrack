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
use App\Http\Controllers\Admin\AdminSettingsController;


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
        Route::get('users/staff', [UsersController::class, 'staff'])->name('users.staff');
        Route::get('users/customers', [UsersController::class, 'customers'])->name('users.customers');
        Route::get('users/archives', [UsersController::class, 'archives'])->name('users.archives');
        Route::post('users/{id}/restore', [UsersController::class, 'restore'])->name('users.restore');
        Route::delete('users/{id}/force-delete', [UsersController::class, 'forceDelete'])->name('users.forceDelete');
        Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
        Route::post('users', [UsersController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::post('users/{user}', [UsersController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
        
        // Admin Settings
        Route::get('admin/settings', [AdminSettingsController::class, 'edit'])->name('admin.settings.edit');
        Route::post('admin/settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
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

    // Order routes (Customers can create and view their orders, Staff can view all and approve/reject)
    Route::middleware('role:Customer')->group(function () {
        Route::get('orders/create', [\App\Http\Controllers\OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [\App\Http\Controllers\OrderController::class, 'store'])->name('orders.store');
        Route::post('orders/{order}/cancel', [\App\Http\Controllers\OrderController::class, 'cancel'])->name('orders.cancel');
    });
    
    // Order routes (all authenticated can view, but scope differs by role)
    Route::get('orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    
    // Order approval/rejection (Admin|Staff only)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::post('orders/{order}/approve', [\App\Http\Controllers\OrderController::class, 'approve'])->name('orders.approve');
        Route::post('orders/{order}/reject', [\App\Http\Controllers\OrderController::class, 'reject'])->name('orders.reject');
        Route::post('orders/{order}/cancel', [\App\Http\Controllers\OrderController::class, 'cancel'])->name('orders.cancel');
    });

    // Order comments (all authenticated users can comment on their orders)
    Route::post('orders/{order}/comments', [\App\Http\Controllers\OrderController::class, 'addComment'])->name('orders.comments.store');

    // Notification routes
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');

    // Invoice CRUD routes (all authenticated can view/manage per your policy)
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class)->except(['update']);
    Route::post('invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('invoices/{invoice}/mark-paid', [\App\Http\Controllers\InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::post('invoices/{invoice}/send-payment-reminder', [\App\Http\Controllers\InvoiceController::class, 'sendPaymentReminder'])->name('invoices.sendPaymentReminder');

    // Refund Requests (Admin, Staff, and Customer)
    Route::get('refund-requests', [\App\Http\Controllers\RefundRequestController::class, 'index'])
        ->middleware('role:Admin|Staff|Customer')
        ->name('refundRequests.index');
    Route::get('invoices/{invoice}/refund-request', [\App\Http\Controllers\RefundRequestController::class, 'create'])->name('refundRequests.create');
    Route::post('invoices/{invoice}/refund-request', [\App\Http\Controllers\RefundRequestController::class, 'store'])->name('refundRequests.store');
    Route::post('refund-requests/{refundRequest}/approve', [\App\Http\Controllers\RefundRequestController::class, 'approve'])
        ->middleware('role:Admin|Staff')
        ->name('refundRequests.approve');
    Route::post('refund-requests/{refundRequest}/reject', [\App\Http\Controllers\RefundRequestController::class, 'reject'])
        ->middleware('role:Admin|Staff')
        ->name('refundRequests.reject');

    // Refunds (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::get('refunds', [\App\Http\Controllers\RefundController::class, 'index'])->name('refunds.index');
        Route::post('refunds/{refund}/process', [\App\Http\Controllers\RefundController::class, 'process'])->name('refunds.process');
        Route::post('refunds/{refund}/complete', [\App\Http\Controllers\RefundController::class, 'complete'])->name('refunds.complete');
        Route::post('refunds/{refund}/cancel', [\App\Http\Controllers\RefundController::class, 'cancel'])->name('refunds.cancel');
    });
    // (refund routes removed)

    // Customer Deliveries (Customer role)
    Route::middleware('role:Customer')->group(function () {
        Route::get('my-deliveries', [DeliveryController::class, 'customerDeliveries'])->name('deliveries.customer');
        Route::get('my-deliveries/{delivery}', [DeliveryController::class, 'customerShow'])->name('deliveries.customer.show');
    });

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

    // Expenses routes (Admin only)
    Route::middleware('role:Admin')->group(function () {
        Route::resource('expenses', \App\Http\Controllers\ExpenseController::class)->except(['update']);
        Route::post('expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update'])->name('expenses.update');
    });

    // Cash Flow (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::get('finance/cash-flow', [CashFlowController::class, 'index'])->name('finance.cash-flow');
        Route::get('finance/reports', [FinancialReportController::class, 'index'])->name('finance.reports');
        Route::get('finance/reports/export', [FinancialReportController::class, 'export'])->name('finance.reports.export');
    });

    // Reports (Admin only)
    Route::middleware('role:Admin')->group(function () {
        Route::get('reports', [\App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');
        Route::get('reports/print-all', [\App\Http\Controllers\ReportsController::class, 'printAll'])->name('reports.print-all');
    });

    // Reminders (Admin|Staff)
    Route::middleware('role:Admin|Staff')->group(function () {
        Route::get('reminders', [\App\Http\Controllers\ReminderController::class, 'index'])->name('reminders.index');
        Route::get('reminders/create', [\App\Http\Controllers\ReminderController::class, 'create'])->name('reminders.create');
        Route::post('reminders', [\App\Http\Controllers\ReminderController::class, 'store'])->name('reminders.store');
        Route::post('reminders/{reminder}/mark-read', [\App\Http\Controllers\ReminderController::class, 'markAsRead'])->name('reminders.mark-read');
        Route::post('reminders/{reminder}/mark-completed', [\App\Http\Controllers\ReminderController::class, 'markAsCompleted'])->name('reminders.mark-completed');
        Route::post('reminders/{reminder}/dismiss', [\App\Http\Controllers\ReminderController::class, 'dismiss'])->name('reminders.dismiss');
        Route::post('reminders/mark-all-read', [\App\Http\Controllers\ReminderController::class, 'markAllAsRead'])->name('reminders.mark-all-read');
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
