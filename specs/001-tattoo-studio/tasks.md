# Implementation Tasks: Aengelos Tattoo Studio Management System

**Branch**: `001-tattoo-studio` | **Spec**: [spec.md](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/spec.md) | **Plan**: [plan.md](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/plan.md)

---

## Phase 1: Setup & Environment Initialization

**Purpose**: Database provisioning, writable storage directories, and shared libraries.

- [x] T001 Initialize MySQL 8.0 database `aengelos_studio` with schema and seed data in `database/schema.sql` and `database/seeds.sql`
- [x] T002 Implement database connection singleton, session RBAC guard, and directory provisioning in `config/db.php`
- [x] T003 Deploy standalone pure PHP FPDF library in `lib/fpdf.php` for digital waiver and report generation
- [x] T004 Create anatomical body template outlines (forearm, calf, shoulder, back, chest) in `public/assets/templates/`

---

## Phase 2: Foundational (Backend Controller & Core Routing)

**Purpose**: Unified API action dispatcher handling auth, booking, FIFO deduction, and batch operations.

- [x] T005 Implement `public/api.php` action routing for `login`, `register`, and `logout`
- [x] T006 Implement unified booking pipeline `create_booking_with_visualizer` in `public/api.php` (artwork upload, Fabric.js composite save, appointment creation, touchscreen signature capture, and FPDF timestamped waiver generation)
- [x] T007 Implement artist deposit review action `verify_deposit` in `public/api.php`
- [x] T008 Implement transactional FIFO sterile inventory deduction `complete_session_fifo` in `public/api.php` with `SELECT ... FOR UPDATE` row locking
- [x] T009 Implement administrative batch registry action `add_supply_batch` in `public/api.php`

---

## Phase 3: User Story 1 - Client Management & 2D Visualizer Portal (Priority: P1)

**Purpose**: Client-facing interactive visualization, booking schedule, deposit proof upload, and digital waiver signing.

- [x] T010 Build unified authentication and registration portal in `public/index.php`
- [x] T011 Build client portal in `public/client_portal.php` featuring Fabric.js 2D anatomical canvas, artwork scaling/rotation, pricing matrix selection, and down payment upload
- [x] T012 Integrate Signature Pad API and medical disclosure questionnaire on `public/client_portal.php` for touch consent capture
- [x] T013 Implement client booking history list with mockup image and waiver PDF access links on `public/client_portal.php`

---

## Phase 4: User Story 2 - Staff Operations & Session Tracking (Priority: P2)

**Purpose**: Artist schedule management, deposit verification, mockup/waiver modal inspection, and session execution logging.

- [x] T014 Build `public/artist_portal.php` dashboard with schedule overview and status tabs (Pending Deposits, Today's Schedule, Completed)
- [x] T015 Implement receipt inspection modal with one-click approve/reject actions on `public/artist_portal.php`
- [x] T016 Implement high-res 2D visualizer composite and signed PDF waiver viewer modals on `public/artist_portal.php`
- [x] T017 Implement completed session logging form with chair duration, artist notes, and sterile consumable selection on `public/artist_portal.php`

---

## Phase 5: User Story 3 - Administrative & FIFO Inventory Management (Priority: P3)

**Purpose**: Service pricing configuration, sterile lot registry, FIFO batch tracking, and real-time clinical alerts.

- [x] T018 Build `public/admin_portal.php` with clinical safety alert banners: Low-Stock (`total_stock <= reorder_threshold`) and Expiring/Expired Batches (within 30 days)
- [x] T019 Implement supply batch intake form (supplier batch number, lot number, quantity, arrival date, expiry date) in `public/admin_portal.php`
- [x] T020 Implement service pricing matrix editor in `public/admin_portal.php` (Custom Design, Flash Art, Cover-Up)
- [x] T021 Implement user account & role management table in `public/admin_portal.php`

---

## Phase 6: Reporting & Quality Benchmark Verification (Priority: P4)

**Purpose**: Multi-category streaming export engine and ISO/IEC 25010 compliance verification.

- [x] T022 Build `public/export.php` supporting CSV streaming across appointments, sessions, FIFO inventory batches, and signed medical waivers
- [x] T023 Add direct PDF report generation in `public/export.php` using FPDF table layouts
- [x] T024 Perform end-to-end ISO/IEC 25010 verification walkthrough across all 3 user roles
