# Feature Specification: Aengelos Tattoo Studio Management System

**Feature Branch**: `001-tattoo-studio`  
**Created**: 2026-10-19  
**Status**: Ready for Planning / Implementation  
**Input**: Comprehensive Functional Requirements Matrix, Role-Based Access Control, 2D Anatomical Canvas Visualizer, Staff Operations & Session Tracking, Administrative & Sterile Supply FIFO Inventory Management, Automated Notifications, and Audited Export Reporting.

---

## 1. Executive Summary & Core Mandate

Aengelos Tattoo Studio is transitioning its entire studio workflow from fragmented social media chats, paper desk calendars, manual payment screenshots, fragile physical paper waivers, and unmonitored sterile supply bins to a unified, secure web platform.

The system encompasses five integrated modules:
1. **Client Management & Interactive Visualization Module**: Account registration/login, 2D raster artwork positioning and transformation over 5 body-region canvas templates (forearm, calf, shoulder, back, chest), appointment scheduling, deposit screenshot submission, and touchscreen medical consent signing.
2. **Staff Operations & Session Tracking Module**: Artist review of appointments, visualizer mockups, and deposit receipts; pre-session verification of signed medical waivers; recording of post-session procedure durations and consumable supplies used.
3. **Administrative & Inventory Management Module**: RBAC permission management, multi-tier service pricing (Custom Design, Flash Art, Cover-Up), supplier batch and lot tracking with automated First-In, First-Out (FIFO) deduction, and low-stock/expiration alerts.
4. **Database & Automated Notification Module**: Centralized relational schema maintaining records for users, bookings, session logs, PDF waiver archives, artwork assets, and transaction audits, accompanied by automated dashboard threshold alerts.
5. **Comprehensive Reporting Module**: One-click streaming export of PDF and CSV reports across appointments, completed sessions, client transactions, reservation deposits, sterile supply status, batch consumption history, and signed legal waivers.

---

## 2. User Scenarios & Personas *(mandatory)*

### User Story 1 - Client Visualizer Placement, Online Booking & Digital Waiver Signing (Priority: P1)

**Persona**: *Client (Juan)* wants to preview his custom tattoo design on his forearm, choose an appointment slot from the studio calendar, submit his reservation deposit receipt, and sign his medical waiver from his device.

**Why this priority**: Direct customer acquisition, revenue initiation, and baseline legal liability protection.

**Independent Test**: A client registers, uploads a `.png` design, manipulates it on the forearm 2D body canvas, books an appointment slot for a "Custom Design", uploads a deposit receipt, answers medical disclosures, signs on the touchscreen signature pad, and receives confirmation.

**Acceptance Scenarios**:
1. **Given** an authenticated Client on the visualizer, **When** they select any of the 5 body regions (`forearm`, `calf`, `shoulder`, `back`, `chest`) and upload an artwork (`.png`, `.jpg`), **Then** the canvas loads the template and allows interactive repositioning, scaling, rotation, and opacity adjustment using Fabric.js.
2. **Given** an approved placement layout, **When** the client submits the booking form, **Then** the canvas composite image is flattened and saved, linked to a new appointment record with `deposit_status = 'pending'` and `booking_status = 'pending'`.
3. **Given** the deposit prompt, **When** the client uploads their GCash or bank transfer payment screenshot with transaction reference, **Then** the receipt is stored and the client is directed to the medical consent waiver.
4. **Given** the medical waiver disclosure, **When** the client reports skin allergies, keloid propensity, and medical conditions, and provides a signature via touch or mouse, **Then** a timestamped immutable PDF waiver is generated, archived, and linked to the appointment.

---

### User Story 2 - Staff Operations, Receipt Verification & Session Supply Logging (Priority: P2)

**Persona**: *Senior Tattoo Artist* needs to inspect the visualizer mockup, verify the client's deposit receipt, verify the signed waiver before inking, and record session duration and sterile supplies consumed after completing the piece.

**Why this priority**: Essential for clinical safety, preventing unauthorized work on unverified deposits, and maintaining precise inventory depletion records.

**Independent Test**: The artist logs in, reviews a pending appointment, verifies the deposit receipt, inspects the 2D mockup and signed PDF waiver, marks the booking as confirmed, conducts the session, and logs 180 minutes with 2x needle cartridges and 1x pigment consumed.

**Acceptance Scenarios**:
1. **Given** an appointment with a pending deposit, **When** the artist inspects the uploaded receipt image and clicks "Verify Deposit", **Then** the deposit status changes to `verified` and the booking status advances to `confirmed`.
2. **Given** a confirmed appointment, **When** the artist prepares for the appointment, **Then** the artist can view the high-resolution composite mockup and verify that a signed digital waiver exists and is valid.
3. **Given** a completed tattoo session, **When** the artist inputs duration, total fee, notes, and selects the sterile supplies consumed, **Then** the session log is persisted and the system triggers the automated FIFO inventory deduction logic for the consumed supplies.

---

### User Story 3 - Administrative Control, Pricing Matrix & FIFO Sterile Supply Inventory (Priority: P3)

**Persona**: *Studio Admin* manages service pricing, monitors staff and client accounts, registers incoming sterile supply shipments with lot numbers and expiry dates, and monitors stock alerts.

**Why this priority**: Business profitability, service standardization, and strict clinical sterilization compliance.

**Independent Test**: Admin creates a new batch of 1205RL needles with batch code, lot code, quantity, and expiration date; verifies that session deductions decrement the oldest batch first; and views dashboard alerts when batches are near expiry or stock is below reorder thresholds.

**Acceptance Scenarios**:
1. **Given** the Service Pricing Matrix, **When** the admin modifies base rates or required deposit percentages for Custom Design, Flash Art, or Cover-Up categories, **Then** all future client bookings dynamically reflect the updated prices.
2. **Given** multiple batches of sterile needle cartridges with differing received and expiration dates, **When** supply consumption is logged, **Then** the FIFO engine deducts from the active batch with the earliest expiration date.
3. **Given** total stock for a sterile supply item falling below its `reorder_threshold`, or a batch expiring within 30 days, **When** the admin views the dashboard, **Then** high-visibility alert badges are displayed.

---

### User Story 4 - Comprehensive Reporting & Audit Export (Priority: P4)

**Persona**: *Studio Admin / Auditor* generates comprehensive reports for accounting, health authority compliance, and inventory audits.

**Why this priority**: Regulatory and financial transparency; satisfies ISO/IEC 25010 maintainability and auditability benchmarks.

**Independent Test**: Admin requests PDF and CSV downloads for appointments, completed sessions, client transactions, reservation deposit payments, sterile inventory status, and signed waivers.

**Acceptance Scenarios**:
1. **Given** the Reporting module, **When** the admin selects a report category and clicks "Export CSV" or "Export PDF", **Then** the system streams the generated file with appropriate headers in under 1.5 seconds.
2. **Given** a health inspection audit, **When** the admin exports the Archived Digital Waivers report, **Then** every waiver lists client name, appointment ID, medical condition flags, signature timestamp, and PDF archive location.

---

### User Story 5 - RBAC Security & Client Account Management (Priority: P5)

**Persona**: *All Users* access the unified authentication portal with distinct privileges enforced based on role (`client`, `artist`, `admin`).

**Why this priority**: Foundational security boundary protecting sensitive client health data, business finances, and inventory operations.

**Independent Test**: Attempt unauthorized access to admin and artist routes from a client session and confirm immediate denial with HTTP 403 or redirect.

**Acceptance Scenarios**:
1. **Given** a new visitor, **When** they complete registration on the portal, **Then** an account is created with `role = 'client'` and password securely hashed using BCrypt.
2. **Given** an authenticated user with `role = 'client'`, **When** attempting to access artist or admin API actions, **Then** the session guard rejects the request with HTTP 403 Forbidden.

---

## 3. Edge Cases & Boundary Conditions

- **EC-001 (Invalid File Formats)**: If a user uploads non-raster or corrupt files (e.g., SVG scripts, executables) for artwork or receipts, the server checks MIME types via `finfo_file` (allowing only `image/jpeg` and `image/png`) and rejects files exceeding 8MB.
- **EC-002 (Blank Signature Submission)**: If a client attempts to submit the consent waiver with an empty signature pad, client-side and server-side validation blocks the submission before PDF compilation.
- **EC-003 (Concurrent Batch Depletion)**: When two artists simultaneously log sessions that consume stock from the same batch, atomic database transactions (`BEGIN TRANSACTION ... FOR UPDATE`) serialize the deductions, preventing negative inventory.
- **EC-004 (Expired Stock in Inventory)**: If a batch has passed its `expiration_date`, the FIFO engine marks its status as `expired`, excludes it from active consumption, and alerts the admin.
- **EC-005 (Partial Batch Exhaustion)**: If a session consumes 3 units of an item, and the oldest active batch only has 1 unit remaining, the system consumes the 1 unit (marking the batch `depleted`), and deducts the remaining 2 units from the next chronological batch.

---

## 4. Requirements *(mandatory)*

### Functional Requirements

#### Module 1: Client Management & Interactive Visualization
- **FR-001**: System MUST allow clients to register, login, and manage their profile with validated email and phone numbers.
- **FR-002**: System MUST provide an interactive 2D canvas visualizer powered by Fabric.js over 5 predefined body templates: `forearm`, `calf`, `shoulder`, `back`, `chest`.
- **FR-003**: Visualizer MUST support dragging, proportional scaling, 360-degree rotation, and opacity adjustment for uploaded `.png` and `.jpg` artwork files.
- **FR-004**: System MUST export and store flattened 2D placement composites (`mockup_image_path`) along with serialized canvas coordinates (`canvas_coords_json`).
- **FR-005**: System MUST allow clients to submit appointment bookings selecting date, time, and service from `service_pricing_matrix`.
- **FR-006**: System MUST enable clients to upload deposit payment receipts (GCash/bank transfer screenshot) and record payment reference codes.
- **FR-007**: System MUST provide an interactive touchscreen signature pad and medical questionnaire (skin allergies, keloids, medical conditions) to generate and archive an immutable PDF waiver via FPDF.

#### Module 2: Staff Operations & Session Tracking
- **FR-008**: System MUST provide artists with a dedicated dashboard displaying assigned appointments, booking statuses, and calendar slots.
- **FR-009**: Artists MUST be able to inspect 2D visualizer mockups and uploaded deposit receipts to confirm or reject deposits.
- **FR-010**: System MUST enforce that artists inspect and verify signed medical waivers prior to initiating a tattoo session.
- **FR-011**: Artists MUST be able to log completed session execution details: start/end times, actual duration in minutes, total fee, session notes, and specific sterile supplies consumed.

#### Module 3: Administrative & Inventory Management
- **FR-012**: System MUST enforce strict Role-Based Access Control (RBAC) across `client`, `artist`, and `admin` roles.
- **FR-013**: Admin MUST be able to manage the `service_pricing_matrix` across `Custom Design`, `Flash Art`, and `Cover-Up` categories, defining base price, deposit required, and estimated duration.
- **FR-014**: System MUST maintain a master sterile supply registry (`sterile_supplies`) categorized into `Needle Cartridge`, `Certified Pigment`, and `Protective Barrier`.
- **FR-015**: Admin MUST be able to register supply deliveries with supplier batch numbers, lot numbers, received quantities, received dates, and expiration dates in `supply_batches_fifo`.
- **FR-016**: The inventory deduction engine MUST strictly enforce First-In, First-Out (FIFO) logic, exhausting older active batches before newer batches and automatically flagging expired lots.

#### Module 4: Database & Automated Notifications
- **FR-017**: System MUST persist all operational records in MySQL 8.0 using InnoDB tables with strict foreign key constraints and UTF-8mb4 encoding.
- **FR-018**: System MUST monitor aggregate sterile supply quantities against `reorder_threshold` and trigger visual low-stock dashboard alert badges.
- **FR-019**: System MUST automatically detect batches expiring within 30 days or past their expiration date and trigger high-visibility expiration alerts.

#### Module 5: Comprehensive Reporting Module
- **FR-020**: System MUST provide one-click CSV and PDF export streaming via `public/export.php` across:
  1. Appointments Schedule & Status
  2. Completed Tattoo Sessions & Duration
  3. Client Financial Transactions & Deposits
  4. Sterile Supply Inventory & Stock Levels
  5. FIFO Batch Consumption Audit Logs
  6. Low-Stock & Expiration Alerts Summary
  7. Archived Digital Waivers & Medical Disclosures
- **FR-021**: All report exports MUST support date range filtering and stream directly to output without storing temporary files on the server.

---

## 5. Key Entities & Domain Structure

- **User (`users`)**: Central authentication entity containing full name, email, phone, BCrypt password hash, and RBAC role (`client`, `artist`, `admin`).
- **ServicePricing (`service_pricing_matrix`)**: Service catalog defining offerings across Custom Design, Flash Art, and Cover-Up, with baseline pricing, required deposit, and estimated chair duration.
- **VisualizerMockup (`visualizer_mockups`)**: 2D canvas composite linking client ID, anatomical body region, raw artwork file path, flattened composite mockup path, and serialized coordinate JSON.
- **Appointment (`appointments`)**: Central booking linking client, assigned artist, service tier, mockup composite, scheduled date/time, deposit details, receipt image, deposit status, and booking status.
- **DigitalWaiver (`digital_waivers`)**: Legal consent record storing skin allergy disclosures, keloid history, medical conditions, digital signature image path, and archived PDF path.
- **SessionLog (`session_logs`)**: Execution audit logged by an artist detailing duration, final fee, procedure notes, and execution timestamp.
- **SterileSupply (`sterile_supplies`)**: Master catalog of consumable equipment classified into Needle Cartridges, Certified Pigments, and Protective Barriers with reorder thresholds.
- **SupplyBatchFIFO (`supply_batches_fifo`)**: Lot-controlled inventory records tracking batch code, lot code, received quantity, remaining quantity, arrival date, expiry date, and batch status (`active`, `depleted`, `expired`).
- **SupplyConsumptionLog (`supply_consumption_logs`)**: Detailed audit trail mapping units of sterile supplies deducted from specific FIFO batches during a recorded session.

---

## 6. Success Criteria *(mandatory)*

### Measurable Outcomes & Quality Benchmarks (ISO/IEC 25010)

- **SC-001 (Functional Completeness)**: 100% of functional requirements across the 5 modules execute successfully with zero unhandled database or runtime exceptions.
- **SC-002 (Usability & Fluidity)**: A new client can complete account registration, 2D visualizer placement, appointment booking, deposit receipt upload, and digital waiver signing in under 4 minutes.
- **SC-003 (Rendering & Archival Performance)**: 2D Canvas composite export and FPDF waiver generation complete in under 1.2 seconds on standard local PHP 8 / MySQL environments.
- **SC-004 (FIFO Compliance)**: 100% of supply deductions accurately decrement the oldest unexpired batch before drawing from subsequent batches, preventing negative balances and blocking expired stock.
- **SC-005 (Export Stream Efficiency)**: CSV and PDF reports containing up to 1,000 records stream and download in under 1.5 seconds without memory exhaustion.
- **SC-006 (Access Control Enforcement)**: 100% of unauthorized or cross-role route requests are intercepted and denied with HTTP 403 or immediate redirect.

---

## 7. Assumptions & System Constraints

- **Scope Boundary**: As defined by the project constitution, visualizer placement is strictly 2D raster canvas manipulation over flat anatomical templates. 3D polygonal mesh morphing, skin depth mapping, and live camera AR are excluded.
- **Payment Processing**: Down payments are manually verified by studio staff through inspection of uploaded transaction receipts (e.g., GCash / bank transfer screenshots); automated external payment gateways are excluded.
- **Architecture**: Single-branch studio deployment on local XAMPP (Apache, PHP 8.1+, MySQL 8.0) on Windows. External SMS hardware gateways and multi-branch database replication are out of scope.
