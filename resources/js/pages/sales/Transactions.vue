<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { type BreadcrumbItem } from '@/types';
import { DollarSign, Receipt, CheckCircle, Clock, BarChart3, CreditCard, AlertCircle, Printer } from 'lucide-vue-next';

interface Transaction {
  id: string
  customer_name: string
  company_name: string | null
  product_name: string
  quantity: number
  unit_price: number
  total_amount: number
  status: string
  transaction_date: string
  invoice_number: string
  notes: string | null
  cash_amount: number
  withholding_tax: number
  tax_5_percent: number
  sale_non_vat_total: number
  vat_amount: number
  running_balance: number
}

interface Customer {
  value: string
  label: string
}

interface Summary {
  total_revenue: number
  total_transactions: number
  completed_transactions: number
  pending_transactions: number
  average_order_value: number
}

interface Filters {
  period: string
  customer: string
  sort_by: string
  sort_order: string
}

const props = defineProps<{
  transactions: Transaction[]
  customers: Customer[]
  summary: Summary
  filters: Filters
}>()

const filters = reactive({ ...props.filters })

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Sales',
    href: '/sales/transactions',
  },
  {
    title: 'Transactions',
    href: '/sales/transactions',
  }
];

const periodOptions = [
  { value: 'week', label: 'Last Week' },
  { value: 'month', label: 'Last Month' },
  { value: 'year', label: 'Last Year' },
];

const sortByOptions = [
  { value: 'created_at', label: 'Date' },
  { value: 'total_amount', label: 'Amount' },
  { value: 'payment_status', label: 'Status' },
];

const sortOrderOptions = [
  { value: 'desc', label: 'Descending' },
  { value: 'asc', label: 'Ascending' },
];

const applyFilters = () => {
  router.get('/sales/transactions', filters, {
    preserveState: true,
    preserveScroll: true,
  })
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Completed':
      return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
    case 'Canceled':
      return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
    case 'Refunded':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
  }
}

const printTransactions = () => {
  const printWindow = window.open('', '_blank')
  if (!printWindow) return

  const currentDate = new Date().toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })

  const periodText = periodOptions.find(p => p.value === filters.period)?.label || filters.period
  const customerText = filters.customer === 'all' ? 'All Customers' : props.customers.find((c: Customer) => c.value === filters.customer)?.label || filters.customer
  const sortByText = sortByOptions.find(s => s.value === filters.sort_by)?.label || filters.sort_by
  const sortOrderText = sortOrderOptions.find(s => s.value === filters.sort_order)?.label || filters.sort_order

  const printContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Sales Transactions Report - PayTrack</title>
      <style>
        @media print {
          body { margin: 0; padding: 20px; font-family: Arial, sans-serif; color: #000; }
          .no-print { display: none !important; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { border: 1px solid #000; padding: 8px; text-align: left; }
          th { font-weight: bold; }
          .header { text-align: center; margin-bottom: 30px; }
          .summary { margin-bottom: 20px; }
          .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px; }
          .summary-item { border: 1px solid #000; padding: 10px; text-align: center; }
          .summary-label { font-size: 12px; margin-bottom: 5px; }
          .summary-value { font-size: 16px; font-weight: bold; }
          .filters { margin-bottom: 20px; padding: 10px; border: 1px solid #000; border-radius: 0; }
          .filters h3 { margin: 0 0 10px 0; font-size: 14px; }
          .filters p { margin: 5px 0; font-size: 12px; }
          /* Plain status (no colors) */
          .status-completed, .status-canceled, .status-cancelled, .status-refunded { 
            color: #000; 
            background: transparent; 
            padding: 0; 
            border-radius: 0; 
            font-size: 12px; 
            font-weight: 600;
          }
          .footer { margin-top: 30px; text-align: center; font-size: 12px; }
        }
        @media screen {
          body { font-family: Arial, sans-serif; padding: 20px; }
          .no-print { display: block; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #f8f9fa; font-weight: bold; }
          .header { text-align: center; margin-bottom: 30px; }
          .summary { margin-bottom: 20px; }
          .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px; }
          .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
          .summary-label { font-size: 12px; color: #666; margin-bottom: 5px; }
          .summary-value { font-size: 16px; font-weight: bold; }
          .filters { margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; }
          .filters h3 { margin: 0 0 10px 0; font-size: 14px; }
          .filters p { margin: 5px 0; font-size: 12px; }
          .status-completed { background-color: #d4edda; color: #155724; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-canceled { background-color: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-cancelled { background-color: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .status-refunded { background-color: #e2e3e5; color: #383d41; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
          .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
          .print-button { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px;
          }
        }
      </style>
    </head>
    <body>
      <button class="print-button no-print" onclick="window.print()">Print Report</button>
      
      <div class="header">
        <h1>PayTrack - Sales Transactions Report</h1>
        <p>Generated on: ${currentDate}</p>
      </div>

      <div class="summary">
        <h2>Summary</h2>
        <div class="summary-grid">
          <div class="summary-item">
            <div class="summary-label">Total Revenue</div>
            <div class="summary-value">${formatCurrency(props.summary.total_revenue)}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Total Transactions</div>
            <div class="summary-value">${props.summary.total_transactions}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Completed</div>
            <div class="summary-value">${props.summary.completed_transactions}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Canceled</div>
            <div class="summary-value">${props.summary.pending_transactions}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Avg Order Value</div>
            <div class="summary-value">${formatCurrency(props.summary.average_order_value)}</div>
          </div>
        </div>
      </div>

      <div class="filters">
        <h3>Report Filters</h3>
        <p><strong>Time Period:</strong> ${periodText}</p>
        <p><strong>Customer:</strong> ${customerText}</p>
        <p><strong>Sort By:</strong> ${sortByText}</p>
        <p><strong>Sort Order:</strong> ${sortOrderText}</p>
      </div>

      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Invoice # - Reference</th>
            <th>Customer</th>
            <th>Cash</th>
            <th>W/holding TAX 1%</th>
            <th>TAX 5%</th>
            <th>Sale - Non vat total</th>
            <th>VAT</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${props.transactions.map((transaction: Transaction) => `
            <tr>
              <td>${formatDate(transaction.transaction_date)}</td>
              <td>${transaction.invoice_number}</td>
              <td>
                ${transaction.customer_name}
                ${transaction.company_name ? `<br><small>${transaction.company_name}</small>` : ''}
              </td>
              <td>${formatCurrency(transaction.cash_amount)}</td>
              <td>${formatCurrency(transaction.withholding_tax)}</td>
              <td>${formatCurrency(transaction.tax_5_percent)}</td>
              <td>${formatCurrency(transaction.sale_non_vat_total)}</td>
              <td>${formatCurrency(transaction.vat_amount)}</td>
              <td><span class="status-${transaction.status.toLowerCase()}">${transaction.status}</span></td>
            </tr>
          `).join('')}
        </tbody>
      </table>

      <div class="footer">
        <p>This report was generated by PayTrack Sales Management System</p>
        <p>For questions or support, please contact your system administrator</p>
      </div>
    </body>
    </html>
  `

  printWindow.document.write(printContent)
  printWindow.document.close()
  printWindow.focus()
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Sales Transactions" />
    
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">Sales Transactions</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">View and manage all invoice transactions</p>
      </div>
      <Button @click="printTransactions" class="flex items-center gap-2">
        <Printer class="w-4 h-4" />
        Print Report
      </Button>
    </div>



    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
      <Card>
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ formatCurrency(summary.total_revenue) }}
              </p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
              <DollarSign class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Transactions</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ summary.total_transactions }}
              </p>
            </div>
            <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
              <Receipt class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ summary.completed_transactions }}
              </p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
              <CheckCircle class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Canceled</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ summary.pending_transactions }}
              </p>
            </div>
            <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-full">
              <AlertCircle class="w-6 h-6 text-red-600" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Order Value</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ formatCurrency(summary.average_order_value) }}
              </p>
            </div>
            <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full">
              <BarChart3 class="w-6 h-6 text-purple-600" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Filters -->
    <Card class="mb-6">
      <CardHeader>
        <CardTitle>Filters</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Period Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Period</label>
            <Select
              v-model="filters.period"
              :options="periodOptions"
              @update:model-value="applyFilters"
            />
          </div>

          <!-- Customer Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
            <Select
              v-model="filters.customer"
              :options="customers"
              @update:model-value="applyFilters"
            />
          </div>

          <!-- Sort By -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
            <Select
              v-model="filters.sort_by"
              :options="sortByOptions"
              @update:model-value="applyFilters"
            />
          </div>

          <!-- Sort Order -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort Order</label>
            <Select
              v-model="filters.sort_order"
              :options="sortOrderOptions"
              @update:model-value="applyFilters"
            />
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Transactions Table -->
    <Card>
      <CardHeader>
        <CardTitle>Invoice Transactions</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full">
                          <thead>
                <tr class="border-b dark:border-gray-700">
                  <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Date</th>
                  <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Invoice # - Reference</th>
                  <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Customer</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Cash</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100">W/holding TAX 1%</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100">TAX 5%</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Sale - Non vat total</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100">VAT</th>
                  <th class="text-right py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Running Balance</th>
                  <th class="text-left py-3 px-4 font-medium text-gray-900 dark:text-gray-100">Status</th>
                </tr>
              </thead>
                                        <tbody>
                <tr v-for="transaction in transactions" :key="transaction.id" :class="[
                  'border-b dark:border-gray-700',
                  transaction.status === 'Canceled' 
                    ? 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30' 
                    : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                ]">
                  <td class="py-3 px-4 text-sm text-gray-900 dark:text-gray-100">
                    {{ formatDate(transaction.transaction_date) }}
                  </td>
                  <td class="py-3 px-4 text-sm text-gray-900 dark:text-gray-100">
                    {{ transaction.invoice_number }}
                  </td>
                  <td class="py-3 px-4">
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ transaction.customer_name }}</div>
                      <div v-if="transaction.company_name" class="text-sm text-gray-500 dark:text-gray-400">{{ transaction.company_name }}</div>
                    </div>
                  </td>
                  <td class="py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(transaction.cash_amount) }}
                  </td>
                  <td class="py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(transaction.withholding_tax) }}
                  </td>
                  <td class="py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(transaction.tax_5_percent) }}
                  </td>
                  <td class="py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(transaction.sale_non_vat_total) }}
                  </td>
                  <td class="py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(transaction.vat_amount) }}
                  </td>
                  <td class="py-3 px-4 text-right text-sm text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(transaction.running_balance) }}
                  </td>
                  <td class="py-3 px-4">
                    <span :class="getStatusClass(transaction.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ transaction.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
          </table>

          <!-- Empty State -->
          <div v-if="transactions.length === 0" class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
              <Receipt class="h-12 w-12" />
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No invoice transactions found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters to see more results.</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </AppLayout>
</template> 