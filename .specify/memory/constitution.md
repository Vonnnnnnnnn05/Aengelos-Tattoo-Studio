<!--
=== Sync Impact Report ===
Version change: [CONSTITUTION_VERSION] (Uninitialized Template) → 1.0.0 (Initial Ratification)
Modified Principles:
  - [PRINCIPLE_1_NAME] → I. Strict Technology Stack Lock
  - [PRINCIPLE_2_NAME] → II. 2D Canvas Placement & Delimited Visualizer Scope
  - [PRINCIPLE_3_NAME] → III. Clinical Safety & Strict FIFO Sterile Batch Traceability
  - [PRINCIPLE_4_NAME] → IV. Tamper-Evident Digital Waivers & Legal Non-Repudiation
  - [PRINCIPLE_5_NAME] → V. Strict Role-Based Access Control (RBAC) & Single-Branch Isolation
Added Sections:
  - Non-Negotiable System Boundaries & Operational Rules
  - Quality Standards & ISO/IEC 25010 Compliance Gates
Removed Sections: None (Replaced unpopulated placeholders)
Follow-up TODOs: None
===========================
-->

# Aengelos Tattoo Studio Constitution

## Core Mandate
Replace Aengelos Tattoo Studio's manual social media chat consultations, paper desk calendars, unverified payment screenshots, vulnerable physical paper waivers, and unmonitored supply bins with an integrated, secure, and compliant web platform.

---

## Core Principles

### I. Strict Technology Stack Lock
The system architecture MUST strictly adhere to the approved technology stack without unvetted frameworks, external binary compilers, or incompatible runtimes:
- **Frontend**: HTML5 semantic markup, CSS3 (responsive studio dark-mode styling via Vanilla CSS / Tailwind / Bootstrap), modern JavaScript (ES6+), HTML5 Canvas with Fabric.js (v5.3+), and Signature Pad API (v4.1+).
- **Backend**: PHP 8+ utilizing strict Object-Oriented Programming (OOP) and structured MVC patterns with RESTful routing. Standalone pure PHP PDF generation strictly via FPDF or TCPDF without external OS headless browser binaries.
- **Database**: MySQL 8.0 relational database engine running InnoDB with foreign key integrity and ACID transactional safety.
- **Rationale**: Eliminates runtime fragmentation, ensures fast, native execution on standard Apache/XAMPP environments, and avoids fragile external process dependencies.

### II. 2D Canvas Placement & Delimited Visualizer Scope
The tattoo placement visualizer MUST operate strictly as a 2D raster canvas overlay (`.png`, `.jpg`) positioned over flat anatomical body templates (`forearm`, `calf`, `shoulder`, `back`, `chest`):
- **Forbidden Technologies**: Implementing 3D polygonal mesh morphing, skin depth mapping, or live camera augmented reality (AR) tracking is strictly prohibited.
- **Composite Output**: The visualizer MUST produce a flattened 2D composite image (`uploads/mockups/`) preserving rotation, scale, and opacity for artist review.
- **Rationale**: Preserves sub-second rendering speeds, guarantees 100% compatibility across mobile and desktop devices, and prevents unconstrained scope expansion during delivery.

### III. Clinical Safety & Strict FIFO Sterile Batch Traceability
Sterile supply management MUST satisfy clinical health and safety standards:
- **Batch Tracking**: Single-use sterile needle cartridges, certified pigments, and protective barrier consumables MUST be registered with unique manufacturer batch numbers, lot numbers, and sterilization expiration dates.
- **Automated FIFO Deduction**: When an artist completes a session log, supply quantities MUST be decremented automatically using a First-In, First-Out (FIFO) algorithm, consuming from the oldest active, unexpired batch first.
- **Expired Stock Blocking**: The system MUST proactively block and reject any attempt to assign or deduct supplies from expired batches, raising prominent visual warning banners on administrator dashboards.
- **Rationale**: Ensures compliance with public health and biohazard sterilization standards, protecting clients from infected or degraded consumables.

### IV. Tamper-Evident Digital Waivers & Legal Non-Repudiation
Medical disclosure and liability consent workflows MUST ensure permanent, unalterable proof of informed consent:
- **Comprehensive Disclosures**: The client MUST complete an 8-question medical questionnaire covering allergies, bloodborne pathogens, skin conditions, bleeding disorders, pregnancy, and intoxicant status.
- **Immediate PDF Archival**: Client disclosures and touch/stylus signature inputs MUST compile immediately upon submission into an immutable, timestamped PDF archive in `uploads/waivers_pdf/`.
- **Cryptographic Hash Stamp**: Each generated waiver PDF MUST have a SHA-256 cryptographic digest calculated and stored in the database at the time of creation. Any post-signing tampering or file modification MUST fail audit checksum verification.
- **Rationale**: Eliminates physical paper loss, protects studio artists against malpractice or breach-of-consent litigation, and provides health inspectors with immediate digital audit records.

### V. Strict Role-Based Access Control (RBAC) & Single-Branch Isolation
System security, privacy, and architecture MUST enforce strict organizational boundaries:
- **Privilege Separation**: All HTTP routes, controllers (`public/api.php`), and portal views MUST enforce server-side role validation across three explicit roles:
  1. `client`: Access delimited strictly to personal visualizer sessions, appointment requests, deposit proof uploads, and waiver signing.
  2. `artist`: Access to assigned schedules, deposit receipt verification, mockup/waiver modal inspection, and session/supply logging.
  3. `admin`: Full administrative control over the pricing matrix, inventory batch registry, stock alerts, user management, and audit exports.
- **Manual Payment Verification**: Client reservation down payments MUST be confirmed through manual staff/artist inspection of uploaded payment receipts (GCash, bank transfer screenshots, transaction reference numbers) rather than automated third-party merchant payment gateways.
- **Single-Branch Architecture**: The platform MUST be engineered strictly for the single-branch operations of Aengelos Tattoo Studio. Multi-branch franchise database synchronization, off-site SMS gateway telecommunications hardware, and third-party laboratory spore testing integration are excluded from scope.
- **Rationale**: Guarantees zero transaction fee deductions from payment gateways, minimizes hardware dependencies, and enforces ironclad data confidentiality between clients and studio personnel.

---

## Non-Negotiable System Boundaries & Operational Rules

1. **Storage Integrity & Upload Sanitation**:
   - All client uploads (`artworks/`, `receipts/`) MUST be validated server-side by checking real MIME types via `finfo_file` (restricting to `image/jpeg`, `image/png`).
   - File uploads MUST NOT exceed 8MB. Direct script execution in all `uploads/` subdirectories MUST be blocked via `.htaccess` configuration.
2. **Session Security**:
   - Authentication MUST use PHP sessions with `session_regenerate_id(true)` upon login. Passwords MUST be hashed with `PASSWORD_BCRYPT` (cost factor 12).
   - Plaintext passwords or unsalted hashes MUST NEVER be stored.
3. **Database Transaction Safety**:
   - All inventory deductions and booking state transitions MUST execute within atomic MySQL transactions (`BEGIN ... COMMIT / ROLLBACK`) with row-level locks (`SELECT ... FOR UPDATE`) to prevent concurrent race condition stockouts.

---

## Quality Standards & ISO/IEC 25010 Compliance Gates

All modules and engineering deliverables MUST satisfy the ISO/IEC 25010 System and Software Quality Model across four primary characteristics:

1. **Functional Suitability**:
   - 100% of the 6 execution blocks (Database/RBAC, 2D Visualizer, Booking/Waiver, Artist Dashboard, FIFO Inventory, Reporting) MUST operate as specified without unhandled runtime exceptions.
2. **Performance Efficiency**:
   - Portal page loads MUST complete in under 800ms on standard local web environments.
   - 2D Canvas composite generation and FPDF waiver compilation MUST complete in under 1.2 seconds.
   - Streaming report exports (CSV and PDF) MUST stream directly to `php://output` to prevent memory buffer exhaustion.
3. **Usability**:
   - The user interface MUST feature a cohesive dark luxury tattoo studio design system (high-contrast typography, gold/crimson accents, clear form step indicators) that remains fully responsive across mobile (375px) and desktop (1920px) viewports.
4. **Reliability & Security**:
   - Zero tolerance for unauthorized URL cross-role access (unauthenticated requests bounced immediately).
   - 100% of signed waiver records MUST match their stored SHA-256 digest on demand.

---

## Governance

1. **Supreme Authority**: This Constitution represents the highest engineering standard for the Aengelos Tattoo Studio codebase. All architecture designs, implementation plans, code reviews, and pull requests MUST comply with the principles and laws established herein.
2. **Amendment Procedure**:
   - Proposed amendments require formal documentation, revision of affected design artifacts (`spec.md`, `plan.md`), and explicit user ratification.
   - Principle redefinitions or stack changes require a MAJOR version increment.
   - Adding new principles, constraints, or compliance sections requires a MINOR version increment.
   - Typo corrections, wording clarifications, and non-semantic formatting require a PATCH version increment.
3. **Compliance Review**: Every implementation task generated via Spec Kit MUST be cross-referenced against the laws of this Constitution. Any code introducing prohibited features (e.g., 3D mesh tracking, automated merchant gateways) MUST be rejected at the architectural gate.

**Version**: 1.0.0 | **Ratified**: 2026-10-19 | **Last Amended**: 2026-10-19
