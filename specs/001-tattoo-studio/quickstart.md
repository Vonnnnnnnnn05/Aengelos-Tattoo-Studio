# Quickstart & Verification Guide: Aengelos Tattoo Studio

**Feature**: `001-tattoo-studio`  
**Date**: 2026-10-19  
**Target Environment**: Windows / XAMPP (Apache, PHP 8.1+, MySQL 8.0)  

---

## 1. Prerequisites & Environment Verification

Before running the application, verify that your local environment satisfies the runtime requirements:

1. **Web Server & PHP**:
   - Apache running on default port `80` (or configured virtual host).
   - PHP version >= 8.1 (test with `php -v`).
   - Extensions enabled in `php.ini`: `pdo_mysql`, `gd`, `fileinfo`, `mbstring`.
2. **Database**:
   - MySQL 8.0 service running on port `3306` (root with no password or password configured in `config/db.php`).
3. **Directory Write Permissions**:
   - Ensure the server has write permissions to all `uploads/` subdirectories:
     - `uploads/artworks/`
     - `uploads/mockups/`
     - `uploads/receipts/`
     - `uploads/waivers_pdf/`

---

## 2. Database Initialization & Seed Accounts

Execute the schema migration and initial seed data in MySQL:

```bash
# Using MySQL CLI or phpMyAdmin import:
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seeds.sql
```

### Pre-Seeded Test Personas
| Role | Email | Password | Full Name |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@aengelos.com` | `admin123!` | Aengelo Chief Admin |
| **Artist** | `artist@aengelos.com` | `artist123!` | Elena Vance (Lead Artist) |
| **Client** | `client@aengelos.com` | `client123!` | Marco Cruz |

---

## 3. End-to-End Persona Verification Scenarios

### Scenario A: Client Visualizer, Booking & Touch Waiver Submission
1. Navigate to `http://localhost/tattoo/public/index.php`.
2. Login with `client@aengelos.com` / `client123!`. The session guard redirects to `client_portal.php`.
3. In the **2D Placement Visualizer**:
   - Select anatomical template: **Forearm**.
   - Upload sample tattoo artwork (`.png` or `.jpg`).
   - Drag, scale, and rotate the tattoo design over the forearm template.
   - Click **Confirm Placement & Continue**.
4. In the **Booking Form**:
   - Select Size: **Medium (3-6")**, choose preferred date and time slot.
   - Observe automatic price calculation (₱4,500.00 base, ₱1,000.00 required deposit).
   - Submit booking.
5. In the **Deposit Verification** step:
   - Upload a GCash transaction screenshot.
   - Enter reference code (e.g., `GCASH-991283`). Submit.
6. In the **Medical Consent Waiver Pad**:
   - Complete the 8 medical disclosure checkboxes.
   - Sign in the touch signature pad using mouse or touch.
   - Click **Sign & Archive Waiver**.
   - *Expected Outcome*: Appointment status updates to `pending_deposit`. A timestamped immutable PDF is generated in `uploads/waivers_pdf/` and client sees a success confirmation with booking reference.

---

### Scenario B: Artist Receipt Verification & Completed Session Logging
1. Open an incognito window or log out, then navigate to `http://localhost/tattoo/public/index.php`.
2. Login with `artist@aengelos.com` / `artist123!`. Redirected to `artist_portal.php`.
3. In **Pending Deposits**:
   - Locate Marco Cruz's booking.
   - Click **Inspect Receipt** to preview the uploaded GCash screenshot.
   - Click **Verify Deposit**. Status transitions to `confirmed`.
4. In **Today's Schedule**:
   - Click on the booking to view the high-res 2D mockup composite and open the generated signed PDF waiver in a modal.
   - Click **Start Session** (transitions status to `in_session`).
5. In **Log Completed Session**:
   - Input session duration: `180 minutes`.
   - Add notes: `Clean line work, client handled session well`.
   - In Consumed Supplies, select:
     - 2x `03RL Bugpin Needle Cartridges`
     - 1x `15ml True Black Ink`
   - Click **Submit Session Log**.
   - *Expected Outcome*: Appointment status becomes `completed`. The FIFO inventory engine deducts 2 units from the oldest active needle batch and records entries in `session_supplies_consumed`.

---

### Scenario C: Admin FIFO Inventory Monitoring & Threshold Alerts
1. Login with `admin@aengelos.com` / `admin123!`. Redirected to `admin_portal.php`.
2. Inspect the **FIFO Batch Inventory Table**:
   - Verify that the oldest active batch of `03RL Bugpin Needle Cartridges` had its remaining count decremented by 2.
   - Observe that if a batch's remaining count hit 0, its status switched to `depleted`.
3. Test **Restock & Expiration Alerts**:
   - View the alert banners: items below their `reorder_level` or active lots expiring within 30 days display high-visibility warning badges.
   - Click **Register New Batch**: enter item SKU, lot code, quantity `50`, cost `₱45.00`, and expiry date. Verify immediate appearance in the batch table.
4. Test **Pricing Matrix Configuration**:
   - Update base price or deposit requirement for a size category; confirm instant recalculation on client booking flows.

---

### Scenario D: Reporting & One-Click PDF/CSV Audit Export
1. On `admin_portal.php`, navigate to the **Reports & Audit Hub**.
2. Select each of the 5 categories:
   - `Appointments` -> Click **Download CSV** and **Download PDF**.
   - `Completed Sessions` -> Click **Download CSV** and **Download PDF**.
   - `Payment Records` -> Click **Download CSV** and **Download PDF**.
   - `FIFO Inventory Logs` -> Click **Download CSV** and **Download PDF**.
   - `Signed Waivers` -> Click **Download CSV** and **Download PDF**.
3. *Expected Outcome*: Browser streams files immediately without page reloads. CSV files open cleanly in Excel/Google Sheets with correct UTF-8 headers. PDF files render formatted headers, data grids, and SHA-256 verification columns.

---

## 4. ISO/IEC 25010 Quality Benchmark Checklist

- [ ] **Functional Completeness**: All 6 blocks operate without broken links, unhandled exceptions, or missing database fields.
- [ ] **Performance**: Page load < 800ms; Canvas composite rendering < 500ms; Export generation < 1.5s.
- [ ] **Usability**: Fully responsive on mobile (375px) and desktop (1920px), high contrast readability, intuitive step-by-step progress bars.
- [ ] **Reliability**: Database rollbacks prevent orphan records on upload failure; duplicate booking attempts return graceful error messages.
- [ ] **Security**: Tamper-evident SHA-256 checksums verify legal PDF immutability; cross-role URL navigation strictly denied.
