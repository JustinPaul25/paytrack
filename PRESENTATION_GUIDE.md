### Paytrack App – Presentation Guide

Use this guide to present the application smoothly. It includes a time‑boxed demo flow, talking points per screen, and tips for handling questions.


### 1) What the app is
- **Purpose**: Manage sales, customers, products, deliveries, expenses, invoices, and financial insights (cash flow and monthly reports).
- **Audience**: Business owners and staff (Admin, non‑admin staff, and Customer role).
- **Tech**: Laravel + Inertia + Vue 3 + Chart.js; server‑rendered with SPA navigation.


### 2) Before you present (prep)
- Seed example data (optional): run the app’s seeders if needed to have realistic lists and charts.
- Ensure mail/printing/exports and charts load (internet not required; charts are local).
- Recommended window: 1366×768+ and system dark/light mode ready (app adapts).


### 3) How to run locally (Windows)
- Backend: `php artisan serve`
- Frontend assets: `npm run dev` (or `npm run build` then rely on built assets)
- Login with an account that has Admin role to see all features.


### 4) Demo flow (≈10–12 minutes)
1. **Login & Layout (30s)**
   - Show the clean sidebar groups: Sales, Products, Users.

2. **Dashboard (1–2 min)**
   - Key metrics: total revenue, invoices, AOV, pending invoices.
   - Tabs: Sales Trend, Sales Prediction, Customer Churn, Product Sales Trend.
   - Category doughnut and “Top Products / Recent Transactions” tables.
   - Defer deep chart details to: `DASHBOARD_GRAPHS.md`.

3. **Invoices (1 min)**
   - Browse list, open an invoice, highlight totals and status badges.
   - Mention print/export support on invoice pages where applicable.

4. **Deliveries & Expenses (1 min)**
   - Briefly show tracking lists and creation flow.

5. **Cash Flow (2–3 min)**
   - Filters: history months and forecast months.
   - Chart: Net Cash Flow (actual), Projected Net Cash Flow (dashed), Running Cash Position (secondary axis).
   - Tables: historical vs projections; KPIs (runway, next month projection).
   - See `CASH_FLOW_GRAPHS.md` for how to explain the visuals.

6. **Financial Reports (1–2 min)**
   - Period selectors; simple printable table (black/white for print).
   - Export CSV and Print actions.

7. **Products & Categories (1 min)**
   - CRUD overview; show how catalogs affect analytics and reports.

8. **Users & Roles (1 min)**
   - Roles and permissions overview; show where to add users/roles.

9. **Refunds (optional, 1 min)**
   - If relevant, outline the request → review → decision flow (see `REFUND_MODULE.md`).


### 5) Talking points and value
- **Single source of truth**: Invoices drive analytics and reports—no double entry.
- **Forecast awareness**: Cash runway and near‑term projections surface risk early.
- **Operator friendly**: Simple tables for print; exports for accounting tools.


### 6) FAQs you may get
- Q: Can we customize charts/periods?  
  A: Yes—filters exist; adding new series is straightforward in Chart.js components.
- Q: Do roles restrict sections?  
  A: Yes—sidebar and routes adapt based on role (Admin/Customer).
- Q: How are forecasts made?  
  A: Sales: model‑driven helpers; Cash Flow: projection points computed from history. Treat short/atypical history with caution.


### 7) Where to read more (internal docs)
- Dashboard charts: `DASHBOARD_GRAPHS.md`
- Cash flow visuals: `CASH_FLOW_GRAPHS.md`
- Refund process: `REFUND_MODULE.md`


### 8) Timeboxed variant (5–6 minutes)
- Dashboard highlights (metrics + Sales Trend) → Cash Flow (chart + runway) → Financial Reports (print/export) → Close with roles & products.


