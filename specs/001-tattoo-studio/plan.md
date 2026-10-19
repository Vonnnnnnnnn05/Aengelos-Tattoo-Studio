# Implementation Plan: Aengelos Tattoo Studio Management System

**Branch**: `001-tattoo-studio` | **Date**: 2026-10-19 | **Spec**: [spec.md](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/spec.md)

**Input**: Feature specification from `specs/001-tattoo-studio/spec.md` detailing the 1-Day Blitz Build Sequence across 6 execution blocks for Aengelos Tattoo Studio.

---

## 1. Summary

Aengelos Tattoo Studio requires a high-performance, responsive studio operations system built with native PHP 8 OOP, MySQL 8.0, HTML5 Canvas with Fabric.js, and pure PHP PDF generation (FPDF). 

The system unifies:
1. **RBAC Security**: Session-backed authentication across `client`, `artist`, and `admin` roles with password hashing and session regeneration.
2. **2D Anatomical Placement Visualizer**: Interactive artwork positioning, rotation, scaling, and composite flattening over 5 standard anatomical body outlines (forearm, calf, shoulder, back, chest).
3. **Booking & Legal Waiver Engine**: Dynamic price tiering based on a configurable pricing matrix, GCash/bank transfer deposit upload, 8-point medical disclosure checklist, touch signature capture via Signature Pad, and immutable FPDF archiving with SHA-256 integrity digests.
4. **Artist Operations**: Receipt review/verification, mockup/waiver modal inspection, chair session duration logging, and sterile supply consumption capture.
5. **FIFO Sterile Inventory Management**: Batch/lot tracking for needle cartridges, pigments, and barriers with automated First-In, First-Out allocation and visual reorder/expiration alerts.
6. **One-Click Audit & Financial Reporting**: Streaming CSV and PDF report generation for the 5 key categories with ISO/IEC 25010 quality benchmark verification.

---

## 2. Technical Context

- **Language/Version**: PHP 8.1+ / 8.2+ (Strict OOP PDO, typed properties, native match expressions)
- **Primary Frontend Dependencies**: 
  - Fabric.js (v5.3.0 CDN) for 2D Canvas manipulation
  - Signature Pad (v4.1.7 CDN) for digital touch/stylus signature capture
  - Google Fonts (*Outfit* and *Inter*) for typography
  - Vanilla CSS design system (luxury dark aesthetic: deep charcoal `#121214`, gold `#d4af37`, crimson `#e63946`)
- **Primary Backend Dependencies**:
  - Pure PHP FPDF library (`lib/fpdf.php`) for PDF generation with zero OS binary dependencies
  - PHP native PDO extension (`pdo_mysql`) with UTF-8mb4
  - PHP native GD & Fileinfo extensions for image validation and processing
- **Storage**: MySQL 8.0 (InnoDB) with foreign key constraints and ACID transactional locking (`SELECT ... FOR UPDATE`)
- **Target Platform**: Local Windows / XAMPP Apache Environment (`http://localhost/tattoo/public`)
- **Project Type**: Modular Web Application with RESTful controller dispatching (`public/api.php`)
- **Performance Goals**: Page load < 800ms; Fabric.js canvas export < 500ms; FPDF waiver archiving < 200ms; Streaming report export < 1.5s for 1,000 records
- **Constraints**: 1-Day Blitz Build timeline (10 hours across 6 blocks); Zero external runtime dependencies beyond CDN and bundled FPDF; Zero file tampering permitted on archived waivers (verified via SHA-256).

---

## 3. Constitution & Quality Gates Check

| Gate / Principle | Status | Evaluation & Architectural Guarantee |
| :--- | :--- | :--- |
| **I. Modularity & Separation of Concerns** | **PASSED** | Core DB and session logic encapsulated in `config/db.php`; view portals separated by role; all actions cleanly routed through `public/api.php`; exports isolated in `public/export.php`. |
| **II. Zero Vulnerability & Strict RBAC** | **PASSED** | Passwords hashed with BCrypt (cost 12); sessions regenerated on login; all pages guarded with `Auth::requireRole()`; prepared statements used for 100% of SQL queries. |
| **III. Legal Integrity & Non-Repudiation** | **PASSED** | Medical waivers compiled into immutable PDF files with cryptographic SHA-256 hash digests saved directly in MySQL; tamper-evident. |
| **IV. Regulatory Compliance (FIFO)** | **PASSED** | Sterile needle cartridges and pigments tracked by lot number and expiration date; automated FIFO consumption algorithm ensures oldest safe batches are depleted first. |
| **V. Simplicity & YAGNI (Blitz Viability)** | **PASSED** | No heavyweight frameworks or build tools required; native PHP and vanilla web technologies ensure instant local execution in XAMPP without node/npm build friction. |

---

## 4. Project Structure & File Layout

```text
tattoo/
├── config/
│   └── db.php                  # PHP 8 PDO MySQL 8.0 connection & RBAC session guard
├── database/
│   ├── schema.sql              # Complete MySQL 8.0 DDL table definitions & indexes
│   └── seeds.sql               # Seed accounts (client, artist, admin), pricing matrix & inventory batches
├── lib/
│   └── fpdf.php                # FPDF library for immutable PDF waivers & audit reports
├── public/
│   ├── index.php               # Unified Login & Client Registration portal
│   ├── client_portal.php       # Client: Fabric.js 2D Visualizer, Booking & Waiver Pad
│   ├── artist_portal.php       # Artist: Deposit Verifier, Mockup/Waiver Viewer & Session Log
│   ├── admin_portal.php        # Admin: Pricing Matrix, FIFO Batch Inventory, Alerts & Reports
│   ├── api.php                 # RESTful POST/GET controller routing all module actions
│   ├── export.php              # PDF & CSV generator for the 5 required report categories
│   ├── css/
│   │   └── studio.css          # Curated dark luxury studio design system
│   └── assets/
│       └── templates/          # 5 flat 2D body outlines: forearm, calf, shoulder, back, chest
└── uploads/
    ├── artworks/               # Raw client uploaded .png / .jpg tattoo references
    ├── mockups/                # Flattened 2D canvas placement composites
    ├── receipts/               # Uploaded GCash / bank transfer down payment screenshots
    └── waivers_pdf/            # Timestamped, immutable PDF medical consent archives
```

---

## 5. 1-Day Blitz Build Sequence (6 Execution Blocks)

### Block 1 (Hours 1–2): Database & RBAC Core
- **Deliverables**:
  - Initialize MySQL 8.0 database `tattoo_studio` with complete schemas (`users`, `pricing_matrix`, `inventory_items`, `inventory_batches`, `appointments`, `mockup_files`, `payments`, `medical_waivers`, `session_logs`, `session_supplies_consumed`).
  - Seed default pricing tiers, initial needle/ink batches, and test accounts (`admin@aengelos.com`, `artist@aengelos.com`, `client@aengelos.com`).
  - Build `config/db.php` providing `DB::getConnection()` and `Auth` session RBAC helper (`login`, `register`, `logout`, `requireRole`, `csrfToken`).
  - Build `public/index.php` unified login/registration view with dark luxury studio styling.

### Block 2 (Hours 3–4): 2D Placement Visualizer
- **Deliverables**:
  - Create the 5 anatomical silhouette templates (forearm, calf, shoulder, back, chest) in `public/assets/templates/`.
  - Integrate Fabric.js on `public/client_portal.php`.
  - Implement interactive canvas controls: artwork file upload, drag-and-drop, bounding-box scaling, 360-degree rotation, opacity slider, template switcher, and reset.
  - Implement canvas export flattening to generate high-resolution PNG composite images and persist them via `api.php?action=save_mockup`.

### Block 3 (Hours 5–6): Booking & Waiver Engine
- **Deliverables**:
  - Implement appointment scheduling step with date/time pickers and automated pricing calculation from `pricing_matrix`.
  - Implement deposit proof upload (GCash / bank transfer screenshot + reference number input).
  - Build the 8-question health checklist and Signature Pad touch drawing canvas.
  - Integrate pure PHP `lib/fpdf.php` to compile the client data, medical questionnaire, and signature into an immutable PDF stored in `uploads/waivers_pdf/`, calculating and storing the SHA-256 digest in `medical_waivers`.

### Block 4 (Hours 7–8): Artist Operations & Session Log
- **Deliverables**:
  - Build `public/artist_portal.php` with schedule tabs: `Pending Deposits`, `Today's Schedule`, `Completed`.
  - Implement deposit receipt viewer with one-click "Verify" and "Reject" actions.
  - Implement lightbox modal for high-res 2D mockup composite and PDF waiver inspection.
  - Build completed session logging form capturing chair duration (minutes), notes, and supplies consumed.

### Block 5 (Hour 9): FIFO Sterile Supply Inventory
- **Deliverables**:
  - Implement FIFO deduction algorithm in `public/api.php` utilizing MySQL transactions and row locking.
  - Ensure multi-batch split allocation works cleanly when an older batch is partially depleted.
  - Build `public/admin_portal.php` batch inventory registry: add batch modal, status badges (`active`, `depleted`, `expired`).
  - Implement visual dashboard alert banners for items below `reorder_level` and lots expiring within 30 days.

### Block 6 (Hour 10): Reporting & ISO 25010 Check
- **Deliverables**:
  - Build `public/export.php` supporting streaming CSV and PDF downloads across the 5 categories (Appointments, Sessions, Payments, Inventory Logs, Waivers).
  - Add date-range filtering to admin export controls.
  - Conduct full ISO/IEC 25010 quality benchmark verification across Functionality, Usability, Reliability, and Performance Efficiency.

---

## 6. Complexity Tracking

| Architectural Choice | Why Needed | Simpler Alternative Rejected Because |
| :--- | :--- | :--- |
| **FIFO Batch Tracking** | Health regulations require traceability of sterile needle lots and ink expiration dates. | Flat inventory counter cannot track shelf-life expiry or enforce lot recalls. |
| **FPDF Pure PHP Engine** | Generates standardized legal consent waivers without external server binaries. | Storing signature purely as database blob fails health audit document standards; headless Chrome requires Node/binary dependencies. |
| **SHA-256 Digest on PDF** | Legal non-repudiation and proof that medical disclosures were not altered post-signing. | Storing unhashed files leaves studio vulnerable to waiver tampering allegations. |
| **Fabric.js Canvas Layering** | Clients need precise, real-time visualization of scale, angle, and anatomical fit. | Static image overlay preview does not allow rotation or scaling. |

---

## 7. Generated Design Artifacts

- **Feature Specification**: [spec.md](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/spec.md)
- **Technical Research**: [research.md](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/research.md)
- **Relational Data Model**: [data-model.md](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/data-model.md)
- **API Contract**: [contracts/api-spec.json](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/contracts/api-spec.json)
- **Export Contract**: [contracts/export-spec.md](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/contracts/export-spec.md)
- **Quickstart & Validation Guide**: [quickstart.md](file:///c:/xampp/htdocs/tattoo/specs/001-tattoo-studio/quickstart.md)
