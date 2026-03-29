# Safe Merge Guide

This project now has **two layers**:

1. The current working PHP portal in the root folder.
2. A new production-oriented Node backend in [`backend`](C:\Users\Administrator\Desktop\Bani%20Tracking\backend).

## The safe rollout path

### Phase 1

Keep the PHP system live.

- Auth stays in PHP.
- Dashboards stay in PHP.
- Current MySQL tables remain the source of truth for the live site.

### Phase 2

Deploy the Node backend separately.

- Use Render, Railway, or a VPS.
- Point it to a fresh MySQL database using `backend/sql/production-schema.sql`.
- Test the API independently from the live site.

### Phase 3

Bridge or sync data.

- If you want to seed the new backend from the live portal data, use `backend/sql/bridge-from-portal.sql`.
- This copies clients, staff, shipments, and invoices from the current `portal_*` tables into the new normalized schema.

### Phase 4

Switch frontend pieces gradually.

- Start with shipment tracking API reads.
- Then move invoice reads.
- Then move create-shipment and create-invoice actions.
- Leave auth last if you want the least risky path.

## Why this avoids breaking the current system

- The current site is not replaced.
- The new backend does not overwrite the PHP code.
- The new backend does not require deleting the existing tables.
- You can test and deploy the API independently before pointing the frontend to it.

## Recommended next move

Build the frontend/API bridge next:

- React or PHP frontend consuming `/api/shipment/:tracking`
- Admin invoice creation page posting to `/api/invoice`
- Staff status updates posting to `/api/shipment/update`

This gives you a real migration path instead of a risky one-step rewrite.

