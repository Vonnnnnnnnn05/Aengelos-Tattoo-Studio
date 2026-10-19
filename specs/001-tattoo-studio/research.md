# Technical Research & Architecture Decisions: Aengelos Tattoo Studio

**Feature**: `001-tattoo-studio`  
**Date**: 2026-10-19  
**Status**: Completed  

---

## 1. Database & RBAC Session Security Architecture

### Decision
Implement a native PHP 8 Object-Oriented PDO connection wrapper (`config/db.php`) utilizing prepared statements, strict parameter binding, UTF-8mb4 charset, and transactional error handling. Enforce Role-Based Access Control (RBAC) via session-backed guards with granular role checks (`client`, `artist`, `admin`).

### Rationale
- **Zero Third-Party Bloat**: For a 1-day blitz build on XAMPP/PHP 8, native PDO provides maximum performance, minimal attack surface, and direct control over MySQL 8.0 transactions.
- **Session Security**: Session fixation is prevented via `session_regenerate_id(true)` upon authentication. Passwords hashed using `password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])`.
- **RBAC Guard Pattern**: A centralized static helper `Auth::check()`, `Auth::user()`, and `Auth::requireRole(['artist', 'admin'])` allows declarative route guarding at the top of each portal and within `api.php`.

### Alternatives Considered
- *Full-stack Framework (Laravel/Symfony)*: Rejected due to blitz build timeline and overhead. A native PHP 8 OOP architecture matches the directory structure without vendor compilation or deployment friction.
- *JWT (JSON Web Tokens)*: Rejected. Because all modules are hosted under the same Apache/PHP domain, secure HTTP-only cookie-based PHP sessions provide superior revocation control and simpler CSRF protection.

---

## 2. 2D Anatomical Placement Visualizer & Canvas Flattening

### Decision
Integrate **Fabric.js (v5.3.0)** via CDN on `client_portal.php` coupled with 5 anatomical flat outline SVGs/PNGs (forearm, calf, shoulder, back, chest) stored in `public/assets/templates/`. The canvas supports dragging, multi-axis scaling, rotating, and opacity adjustment of the user's uploaded tattoo reference. Flattened composites are exported client-side via `canvas.toDataURL({ format: 'png', quality: 0.95, multiplier: 1.5 })` and sent via multipart POST to `api.php?action=save_mockup`.

### Rationale
- **Fabric.js Capabilities**: Provides robust object-model abstractions over HTML5 `<canvas>`, built-in rotation handles, bounding boxes, coordinate constraints, and z-index layer management (locking the template outline as the unselectable bottom layer while keeping the uploaded artwork freely transformable).
- **Client-Side Rendering Performance**: Offloads graphical compositing to the client GPU, minimizing server CPU load. The server only receives the final PNG composite and saves it directly to `uploads/mockups/`.
- **Fallback / Autosave**: Canvas JSON state is saved in `localStorage` so client placement work is preserved even if the browser reloads unexpectedly.

### Alternatives Considered
- *Three.js 3D Mesh Mapping*: Evaluated for 3D body models, but rejected due to the blitz build constraints, heavy UV unwrapping complexity, and user friction on mobile devices. 2D flat silhouette templates provide 95% of visual clarity with zero 3D rendering overhead.
- *Server-side ImageMagick/GD Compositing*: Rejected for the interactive placement preview because server round-trips for every rotation/scale adjustment would introduce severe latency.

---

## 3. Digital Touch Signature & Immutable Medical Waiver Archiving

### Decision
Use **Signature Pad (v4.1.7)** HTML5 touch/stylus canvas library for digital signature capture, combined with an 8-question medical questionnaire. Upon client submission, `public/api.php` decodes the signature PNG and feeds the structured responses, legal disclaimer, client metadata, and signature into **FPDF** (`lib/fpdf.php`). The resulting PDF is written to `uploads/waivers_pdf/`, and a cryptographic **SHA-256 hash** of the file is calculated and stored in `medical_waivers.sha256_hash`.

### Rationale
- **Legal Enforceability & Non-Repudiation**: Electronic medical consent waivers must be immutable. Computing an immediate SHA-256 digest at generation time ensures any subsequent file tampering or corruption can be immediately detected during an audit.
- **Pure PHP PDF Generation (FPDF)**: `fpdf.php` has zero system binary dependencies, works seamlessly out of the box in XAMPP on Windows, executes in < 150ms, and creates compact, vector-sharp PDF documents.
- **Touch & Desktop Compatibility**: Signature Pad handles pointer events, touch events, and mouse events with high DPI canvas scaling, smoothing curves for realistic pen stroke reproduction.

### Alternatives Considered
- *wkhtmltopdf / headless Chrome*: Rejected due to external Windows binary installation requirements and process execution overhead.
- *Storing Signatures purely as database blobs without PDF*: Rejected because legal audits and health inspections require standardized, printable, standalone consent certificates.

---

## 4. FIFO (First-In, First-Out) Sterile Supply Inventory Engine

### Decision
Implement an automated FIFO inventory depletion algorithm in PHP 8 backed by MySQL 8.0 row-level locking (`SELECT ... FOR UPDATE` inside `PDO::beginTransaction()`). Inventory items are tracked via master records (`inventory_items`) and batch records (`inventory_batches`) with lot numbers and expiration dates. When an artist submits a session log consuming supplies, the algorithm queries active non-expired batches for the item, ordered by `expiry_date ASC, received_at ASC, id ASC`, and decrements quantities across batches until the required amount is satisfied.

### Rationale
- **Sterile Regulatory Compliance**: Tattoo studios are strictly governed by health regulations regarding needle cartridge sterilization expiration and ink shelf life. FIFO ensures the oldest safe supplies are utilized first.
- **Multi-Batch Split Handling**: If a session consumes 3 cartridges and Batch A has only 1 remaining unit, the algorithm decrements 1 from Batch A (marking it `depleted`), and decrements the remaining 2 units from Batch B, creating precise tracking records in `session_supplies_consumed`.
- **Race Condition Prevention**: Wrapping the query and update inside a transaction with `FOR UPDATE` prevents race conditions if multiple artists log completed sessions simultaneously.
- **Automated Alerts**: Items where aggregate remaining stock is below `reorder_level` or active batches expiring in <= 30 days trigger immediate visual warning badges in `admin_portal.php`.

### Alternatives Considered
- *LIFO (Last-In, First-Out)*: Dangerous and unsuitable for perishable or sterile medical equipment.
- *Simple aggregate quantity counter without batches*: Rejected because it makes expiration date tracking and lot recall tracking impossible.

---

## 5. Unified RESTful API Architecture (`public/api.php`)

### Decision
Structure `public/api.php` as a single-endpoint controller handling modular actions via standard JSON and multipart form data. Actions are grouped logically:
- `auth`: `login`, `register`, `logout`, `get_session`
- `client`: `save_mockup`, `create_appointment`, `upload_receipt`, `submit_waiver`, `get_client_dashboard`
- `artist`: `get_artist_schedule`, `verify_deposit`, `get_waiver_details`, `log_session`
- `admin`: `get_inventory_dashboard`, `add_inventory_batch`, `update_pricing`, `get_audit_stats`

Responses adhere to standard envelope schema:
```json
{
  "success": true,
  "data": { ... },
  "message": "Action completed successfully",
  "errors": []
}
```

### Rationale
- **Single Point of Routing**: Centralizes session checks, CSRF validation, exception catching, and JSON header emission (`Content-Type: application/json; charset=utf-8`).
- **Clean Separation of Concerns**: Front-end portal pages (`client_portal.php`, `artist_portal.php`, `admin_portal.php`) serve semantic HTML5 views and reactive JavaScript, delegating all state mutations to `api.php`.

---

## 6. One-Click Streaming Export Engine (`public/export.php`)

### Decision
Build a dedicated export streaming handler `public/export.php` supporting both **CSV** (using PHP native `fputcsv` to `php://output`) and **PDF** (using FPDF) across the 5 required categories:
1. `appointments`: Booking schedule, client info, size, placement, and status.
2. `sessions`: Artist duration, notes, and supplies used.
3. `payments`: Down payments, reference codes, verification timestamps, and artist approvers.
4. `inventory`: Batches, initial vs remaining units, expiration dates, and consumption logs.
5. `waivers`: Timestamped medical disclosures, SHA-256 integrity hashes, and archival links.

### Rationale
- **Streaming Delivery**: Writing directly to `php://output` with appropriate HTTP headers (`Content-Disposition: attachment; filename="report.csv"`) prevents server memory exhaustion and bypasses temporary file management issues on Windows.
- **Audit Ready**: Meets health inspection and accounting requirements with one click.

---

## 7. ISO/IEC 25010 Quality Standards Alignment

| Quality Attribute | Architectural Guarantee | Verification Strategy |
| :--- | :--- | :--- |
| **Functional Suitability** | Full coverage of all 6 blitz build blocks: RBAC, 2D Visualizer, Booking/Waiver, Artist Dashboard, FIFO Inventory, Reporting. | End-to-end integration scenario walkthrough. |
| **Performance Efficiency** | Canvas export < 500ms; FPDF generation < 200ms; Database queries indexed on foreign keys and status columns. | Benchmark response times in browser dev tools. |
| **Usability** | High-contrast dark luxury studio theme (CSS design tokens, responsive cards, micro-animations, clear validation errors). | Responsive viewport checks (375px mobile to 1920px desktop). |
| **Reliability & Integrity** | ACID transactions on booking and FIFO deductions; SHA-256 checksum validation on PDF waivers. | Integrity checksum test; zero double-booking simulation. |
| **Security** | BCrypt password hashing, session regeneration, role-based page guards, MIME-type upload validation. | Penetration probe on unauthorized endpoint calls. |
