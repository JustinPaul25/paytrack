### Cash flow graphs overview

This guide explains the graphs on the Cash Flow page: what each shows, how to read them, and typical decisions they support.

Source: `resources/js/pages/finance/CashFlow.vue`


### Net Cash Flow & Runway
- **What it shows**: Historical net cash flow, projected net cash flow, and the running cash position (balance) across the selected horizon.
- **Type**: Multi‑series line chart with dual Y‑axes.
  - Series 1: Net Cash Flow (₱) — historical actuals
  - Series 2: Projected Net Cash Flow (₱) — forecast (dashed line)
  - Series 3: Running Cash Position (₱) — cumulative cash on a second axis
  - Left Y‑axis: Net cash flow (₱)
  - Right Y‑axis: Cash position (₱)
- **How to read**:
  - X‑axis labels are month labels (e.g., “Sep 2025”). Hover to compare actual net, projected net, and cash position for the same period.
  - The dashed “Projected Net Cash Flow” begins after the last historical month; use it to assess near‑term trajectory.
  - The “Running Cash Position” line shows your cumulative cash level; downward periods indicate burn, upward periods indicate surplus.
- **Useful for**: Assessing liquidity health, validating runway, and understanding if upcoming months likely improve or worsen cash.
- **Notes**: The forecast depends on historical patterns and assumptions; if history is short or anomalous, treat projections with care.


### Historical Performance (table context for the chart)
- **What it shows**: Monthly actuals for Income, Expenses, Net, and Running Cash.
- **Type**: Data table (supports the chart, not a separate graph).
- **How to read**: Use it to reconcile chart points with exact values and to spot months that deviate from trend.
- **Useful for**: Auditing the chart, exporting numbers, and sanity‑checking anomalies.


### Projection Outlook (table context for the chart)
- **What it shows**: Month‑by‑month projected Income, Expenses, Net, and Running Cash for the forecast horizon.
- **Type**: Data table (supports the chart, not a separate graph).
- **How to read**: Review magnitude and direction of change; ensure runway aligns with operational plans.
- **Useful for**: Cash planning, scenario communication, and short‑term financing decisions.


### Filters and interactions
- **History range**: Choose how many past months (e.g., 6/12/18/24). The chart and tables update accordingly.
- **Forecast range**: Choose projection length (e.g., 3/6/9/12 months). The dashed series extends to this horizon.
- **Hover tooltips**: Hover chart points to see exact values for each series in the same month.
- **Legends**: Toggle series visibility where enabled to focus on specific lines.


### Behind the charts (tech note)
- The primary visualization is powered by Chart.js via the shared `BaseChart` component.
- Dual‑axis is used so monthly net (flow) and running cash (stock) remain readable on their natural scales.


