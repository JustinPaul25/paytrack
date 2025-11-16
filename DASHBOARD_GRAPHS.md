### Dashboard graphs overview

This guide explains each graph on the dashboard: what it shows, how to read it, and the typical questions it answers.


### Your Sales Trend
- **What it shows**: Daily sales revenue and invoice count over time.
- **Type**: Line chart with two Y-axes.
  - Left Y-axis: Sales Amount (₱)
  - Right Y-axis: Number of Invoices
- **How to read**: Use the date along the X-axis. The blue line tracks revenue; the green line tracks invoices. Hover to compare exact values for the same day. Spikes suggest promos, seasonality, or bulk orders.
- **Useful for**: Detecting growth trends, seasonality, and whether revenue moves with transaction volume or with average order value.


### Sales Prediction
- **What it shows**: Forecasted sales for the next 7 days, with a confidence indicator and a monthly projection.
- **Type**: Line chart (predicted sales) plus KPI blocks.
  - Predicted daily values (solid line)
  - Confidence range (dashed line as a visual guide)
- **How to read**: Hover the line to see daily predicted revenue. The confidence indicator summarizes overall certainty. The monthly section aggregates near‑term predictions to a monthly estimate with a growth indicator.
- **Useful for**: Staffing, inventory planning, and expectation setting.
- **Notes**: Forecasts are model-driven; if recent history is sparse or atypical, interpret cautiously.


### Customer Churn Analysis
- **What it shows**: Churn and retention over time, plus segments and at‑risk customers.
- **Type**: Line chart (churn vs. retention) and segment distributions.
  - Churn Rate (%) over recent months
  - Retention Rate (%) derived as 100 − churn
- **How to read**: Lower churn and higher retention are better. Review segment distribution to see where customers cluster (high/medium/low value) and “churn by segment” to spot trouble groups. The at‑risk list highlights customers to proactively engage.
- **Useful for**: Targeted retention campaigns and measuring lifecycle health.


### Product Sales Trend
- **What it shows**: Last 7 days of daily sales units, top selling products, and performance mix.
- **Type**: Line chart (daily units) with supporting lists/bars.
- **How to read**: Look for day‑to‑day changes and weekend/weekday effects. The “Top Selling Products” list ranks by units and revenue. Performance bars summarize the share of high/medium/low performers.
- **Useful for**: Short‑term merchandising, replenishment, and promo follow‑ups.


### Sales by Product Category
- **What it shows**: Revenue share by category for the selected period.
- **Type**: Doughnut chart.
- **How to read**: Each slice’s size indicates that category’s share of total revenue. Hover to view exact amounts or percentages.
- **Useful for**: Portfolio mix, category focus, and assortment decisions.


### Filters and interactions
- **Date range**: Change the period (7/30/90/365 days or custom). All charts update accordingly.
- **Hover tooltips**: Hover points/slices to see precise values.
- **Legends**: Click legend items to toggle series visibility (where enabled).


### Behind the charts (tech note)
- Charts are rendered with Chart.js via the `BaseChart` component (line, bar, doughnut).
- Dual‑axis charts are used when units differ (₱ vs. count) to keep trends comparable without rescaling.


