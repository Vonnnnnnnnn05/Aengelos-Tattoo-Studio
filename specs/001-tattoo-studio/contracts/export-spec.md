# Export Specifications & Reporting Schemas: Aengelos Tattoo Studio

**Feature**: `001-tattoo-studio`  
**Endpoint**: `public/export.php`  
**Formats Supported**: `CSV` (`format=csv`) and `PDF` (`format=pdf`)  

---

## 1. Export Routing & Headers

### CSV Streaming Headers
```http
HTTP/1.1 200 OK
Content-Type: text/csv; charset=UTF-8
Content-Disposition: attachment; filename="report_{category}_{YYYYMMDD_His}.csv"
Pragma: no-cache
Expires: 0
```

### PDF Streaming Headers
```http
HTTP/1.1 200 OK
Content-Type: application/pdf
Content-Disposition: inline; filename="report_{category}_{YYYYMMDD_His}.pdf"
Cache-Control: private, max-age=0, must-revalidate
Pragma: public
```

---

## 2. Category 1: Appointments Report (`category=appointments`)

### Description
Audits all client reservations, assigned artists, anatomical placement templates, quoted rates, and booking lifecycle states.

### Column Specification (CSV & PDF Table)
| Index | Header Name | Data Type | Example Value | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `Reference Code` | String | `APT-20261019-001` | Unique booking identifier |
| 2 | `Client Name` | String | `Marco Cruz` | Legal name |
| 3 | `Client Phone` | String | `09171234567` | Contact number |
| 4 | `Artist Name` | String | `Elena Vance` | Assigned artist |
| 5 | `Placement` | String | `Forearm` | Anatomical body part |
| 6 | `Size Tier` | String | `Medium (3-6")` | Dimensions tier |
| 7 | `Date & Slot` | String | `2026-10-25 (10:00 AM)` | Scheduled session slot |
| 8 | `Quoted Price` | Currency | `₱4,500.00` | Estimated fee |
| 9 | `Required Deposit` | Currency | `₱1,000.00` | Mandatory deposit |
| 10 | `Status` | String | `Confirmed` | Lifecycle state |

---

## 3. Category 2: Completed Sessions Report (`category=sessions`)

### Description
Operational chair duration log with notes and supplies consumed during tattoo sessions.

### Column Specification
| Index | Header Name | Data Type | Example Value | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `Session ID` | Integer | `104` | Auto-increment ID |
| 2 | `Booking Ref` | String | `APT-20261019-001` | Appointment ref |
| 3 | `Client Name` | String | `Marco Cruz` | Client served |
| 4 | `Artist Name` | String | `Elena Vance` | Artist on duty |
| 5 | `Start Time` | Datetime | `2026-10-25 10:15:00` | Start chair time |
| 6 | `End Time` | Datetime | `2026-10-25 13:45:00` | End chair time |
| 7 | `Duration (Min)` | Integer | `210` | 3.5 hours |
| 8 | `Supplies Consumed`| String | `2x 03RL, 15ml True Black` | Aggregate summary |
| 9 | `Artist Notes` | Text | `Clean line work, healed well`| Technical notes |

---

## 4. Category 3: Payment Records Report (`category=payments`)

### Description
Fiscal down payment and final transaction logs including payment channel, transaction references, and artist verifiers.

### Column Specification
| Index | Header Name | Data Type | Example Value | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `Payment ID` | Integer | `502` | Transaction ID |
| 2 | `Booking Ref` | String | `APT-20261019-001` | Linked appointment |
| 3 | `Client Name` | String | `Marco Cruz` | Payer |
| 4 | `Amount` | Currency | `₱1,000.00` | Amount paid |
| 5 | `Type` | String | `Deposit` | Deposit or Final |
| 6 | `Method` | String | `GCash` | Channel |
| 7 | `Ref / Trans No` | String | `902184920194` | Manual reference |
| 8 | `Status` | String | `Verified` | Verification state |
| 9 | `Verified By` | String | `Elena Vance` | Artist / Admin name |
| 10 | `Verified At` | Datetime | `2026-10-19 14:30:10` | Approval timestamp |

---

## 5. Category 4: Inventory FIFO Consumption Logs (`category=inventory`)

### Description
Sterile needle and pigment batch registry, showing arrival dates, lot codes, remaining stocks, and FIFO depletion trace.

### Column Specification
| Index | Header Name | Data Type | Example Value | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `Item SKU` | String | `NDL-03RL` | Master catalog code |
| 2 | `Item Name` | String | `03RL Bugpin Cartridges` | Item description |
| 3 | `Lot Number` | String | `LOT-2026A-88` | Manufacturer lot |
| 4 | `Category` | String | `Needle Cartridge` | Supply category |
| 5 | `Received Qty` | Integer | `50` | Original count |
| 6 | `Remaining Qty` | Integer | `12` | Active sterile units |
| 7 | `Expiry Date` | Date | `2027-04-30` | Sterilization expiry |
| 8 | `Batch Status` | String | `Active` | Active / Depleted / Expired |
| 9 | `Total Deductions`| Integer | `38` | Units used via FIFO |

---

## 6. Category 5: Signed Medical Waivers Report (`category=waivers`)

### Description
Legal compliance and health inspection audit index for immutable PDF medical consent documents.

### Column Specification
| Index | Header Name | Data Type | Example Value | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `Waiver ID` | Integer | `78` | Record ID |
| 2 | `Booking Ref` | String | `APT-20261019-001` | Linked appointment |
| 3 | `Client Legal Name`| String | `Marco Cruz` | Signatory |
| 4 | `Signed Timestamp`| Datetime | `2026-10-19 15:42:19` | Electronic signing time |
| 5 | `Medical Flags` | String | `None (All Clean)` | Critical conditions |
| 6 | `SHA-256 Digest` | String (Hex)| `e3b0c44298fc1c14...` | Cryptographic checksum |
| 7 | `Archive File` | String | `uploads/waivers_pdf/waiver_78.pdf` | Disk location |
