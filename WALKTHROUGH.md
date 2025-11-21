### Paytrack – App Walkthrough

This walkthrough shows how to use the app end‑to‑end, from signing in to generating reports. It’s designed for first‑time users and demo presenters.


### 1) Sign in
- Open the app and sign in with your account.
- Roles change what you see:
  - Admin: full access (Sales, Products, Users, Refunds).
  - Staff: most Sales and Products features.
  - Customer: limited, customer‑centric access.


### 2) Layout basics
- Left sidebar groups features by domain: Sales, Products, Users.
- Top of each page shows breadcrumbs and quick actions (Print, Export, Filters).
- Tables support sorting, filtering, and direct links into detail pages.


### 3) Dashboard overview
- A summary of key metrics and charts:
  - Sales Trend and Sales Prediction
  - Customer Churn Analysis
  - Product Sales Trend
  - Sales by Category doughnut
- Learn how to talk about these charts in `DASHBOARD_GRAPHS.md`.


### 4) Invoices
1. Go to Sales → Invoices.
2. Browse or search invoices. Open any invoice to see line items, totals, and status.
3. Create a new invoice and save as draft or finalize.
4. Print or export invoice if needed.


### 5) Deliveries
1. Go to Sales → Deliveries.
2. View delivery records linked to invoices or orders.
3. Create/update a delivery and track its status.


### 6) Expenses
1. Go to Sales → Expenses.
2. Record expenses with date, category, and amount.
3. These feed into cash flow and financial reports.


### 7) Transactions
1. Go to Sales → Transactions.
2. Use filters: time period, customer, sorting (Date/Amount/Status).
3. Click “Print Report” for a simple, black‑and‑white printout.
4. Values include Cash, Withholding Tax (1%), 5% Tax, Non‑VAT Total, VAT, and Running Balance.


### 8) Cash Flow
1. Go to Sales → Cash Flow.
2. Choose history range (e.g., last 6/12 months) and forecast range (e.g., next 3/6 months).
3. Review:
   - Net Cash Flow (actual) and Projected Net Cash Flow (dashed)
   - Running Cash Position (secondary axis)
   - KPIs: Average Net, Cash Position, Next Month Projection, Runway
   - Historical and Projection tables
4. See `CASH_FLOW_GRAPHS.md` for how to interpret each series.


### 9) Financial Reports
1. Go to Sales → Financial Reports.
2. Select Start Month and End Month to define the reporting window.
3. Review a clean, printable table (black/white). Use Print or Export CSV.


### 10) Products and Categories
1. Go to Products → Categories to create or manage categories.
2. Go to Products → Products to manage product catalog (name, pricing, stock).
3. Catalog changes affect analytics, cash flow, and reports over time.


### 11) Users and Roles (Admin)
1. Go to Users → Users to create accounts.
2. Go to Users → Roles to assign permissions.
3. The UI adapts based on role (sidebar and route access).


### 12) Refunds (Admin)
1. Go to Sales → Refund Requests to review incoming requests.
2. Approve/deny; approved refunds appear in Sales → Refunds.
3. See `REFUND_MODULE.md` for the full lifecycle.


### 13) Printing and exports
- Most print views are simplified for clarity (no colors).
- Reports provide CSV exports for accounting tools.


### 14) Tips & troubleshooting
- If data looks empty: expand the period filters (Dashboard, Transactions, Reports).
- If charts don’t render: ensure assets are built (`npm run dev` or use the existing build).
- Status sorting on Transactions uses the invoice `status` column (Completed/Canceled).
- Check logs for backend errors at `storage/logs/laravel.log`.


### 15) Developer quick start (local)
- Backend: `php artisan serve`
- Frontend: `npm run dev` (or use built assets via Vite build)
- Seeders are available for demo data (see `database/seeders`).


### Related docs
- Dashboard charts: `DASHBOARD_GRAPHS.md`
- Cash flow visuals: `CASH_FLOW_GRAPHS.md`
- Refund process: `REFUND_MODULE.md`
- Presentation script: `PRESENTATION_GUIDE.md`










