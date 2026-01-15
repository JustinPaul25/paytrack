## Refunds & Inventory - Module Overview

### Roles
- Customer: submits refund request from a completed invoice.
- Admin: reviews requests, approves/declines, processes, completes refunds.

### Data Model
- `refund_requests`: intake from customers; statuses: pending → approved/rejected/completed.
- `refunds`: operational refunds created on approval; statuses: approved → processed → completed/cancelled.
- `stock_movements`: immutable audit of inventory changes.
  - Fields: product_id, refund_id?, invoice_id?, user_id?, type (sale|refund|writeoff|adjustment), quantity (+inbound, −outbound), quantity_before, quantity_after, notes, timestamps.

### Flow
1) Customer submits refund request (invoice completed).
2) Admin Approves:
   - System creates `refunds` entry (status `approved`) and links it to the request (`completed_refund_id`).
   - Customer gets an approval email.
3) Admin Processes:
   - Capture refund_method (cash/bank_transfer/e-wallet/credit_note), optional reference number and notes.
   - Refund status → `processed` and `processed_at` set.
4) Admin Completes:
   - Choose "Return to stock" (default on) and add inspection notes.
   - If Return to stock: increment product stock by `quantity_refunded` and create a `stock_movements` row (`type=refund`, positive quantity).
   - If not resaleable: create a `stock_movements` row (`type=writeoff`, negative quantity) without changing product stock.
   - Refund status → `completed` and `completed_at` set.
5) Admin Declines (alternative branch):
   - Request status → `rejected` with review notes; customer gets a decline email.

### UI
- Customer:
  - Invoice page: “Request Refund”, “Refund Requests” card (shows status, reason/notes if rejected), “Refunds” card (approved/processed/completed entries).
- Admin:
  - Sales → Refund Requests: Approve / Decline, “View details” (description and media link).
  - Sales → Refunds: list by status, actions: Process (set method/ref), Complete (return-to-stock toggle + notes), Cancel.

### Inventory Best Practice
- Do not alter stock on approval; only on completion when goods are accepted.
- Record all changes in `stock_movements` for reconciliation and reporting.

### Migrations/Models
- Added `stock_movements` table and `StockMovement` model.
- Updated `RefundController` to handle inventory and logging on completion.

### Notes
- Money values stored in cents; UI normalizes to currency.
- Quantity caps align to purchased amounts.
- Emails sent on approval/decline using `RefundRequestDecision` mailable.










