<?php
// public/client_portal.php - Client Consultation, 2D Visualizer, Booking & Waiver Pad
require_once __DIR__ . '/../config/db.php';
enforceRole(['client']);

$services = $pdo->query("SELECT * FROM service_pricing_matrix ORDER BY id ASC")->fetchAll();
$artists = $pdo->query("SELECT id, full_name FROM users WHERE role = 'artist'")->fetchAll();

$myBookings = $pdo->prepare("
    SELECT a.*, s.service_name, s.base_price, m.body_region, m.mockup_image_path, w.pdf_archive_path, u.full_name AS artist_name 
    FROM appointments a 
    JOIN service_pricing_matrix s ON a.service_id = s.id 
    JOIN users u ON a.artist_id = u.id
    LEFT JOIN visualizer_mockups m ON a.mockup_id = m.id 
    LEFT JOIN digital_waivers w ON a.id = w.appointment_id 
    WHERE a.client_id = ? 
    ORDER BY a.id DESC
");
$myBookings->execute([$_SESSION['user']['id']]);
$bookings = $myBookings->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal — Aengelos Tattoo Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
        // Initialize theme: Default to Light Mode unless explicitly set to dark
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-zinc-950 dark:text-zinc-100 min-h-screen transition-colors duration-200">
    <!-- TOP NAVIGATION -->
    <nav class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur border-b border-slate-200 dark:border-zinc-800 sticky top-0 z-50 px-3 sm:px-6 py-3 sm:py-4 transition-colors">
        <div class="max-w-7xl mx-auto flex justify-between items-center gap-2">
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                <span class="w-3 sm:w-3.5 h-3 sm:h-3.5 rounded-full bg-amber-500 shadow-md shadow-amber-500/50 flex-shrink-0"></span>
                <span class="text-sm sm:text-xl font-bold tracking-wider text-amber-600 dark:text-amber-400 truncate">AENGELOS TATTOO STUDIO</span>
                <span class="hidden md:inline text-xs bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 px-2.5 py-1 rounded-full border border-slate-200 dark:border-zinc-700 flex-shrink-0">Client Portal</span>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4 text-xs sm:text-sm flex-shrink-0">
                <span class="text-slate-600 dark:text-zinc-300 hidden lg:inline">Welcome, <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span>
                <!-- THEME TOGGLE -->
                <button type="button" onclick="toggleTheme()" class="px-2 sm:px-2.5 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-slate-100 dark:bg-zinc-800 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors flex items-center space-x-1.5 shadow-sm">
                    <span id="themeIcon" class="flex items-center"></span>
                    <span id="themeLabel" class="hidden sm:inline">Dark Mode</span>
                </button>
                <form action="api.php" method="POST" class="inline">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="text-slate-500 hover:text-red-500 dark:text-zinc-400 dark:hover:text-red-400 transition-colors text-xs font-medium">Sign Out</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-3 sm:px-6 py-6 sm:py-8">
        <!-- SYSTEM NOTIFICATIONS -->
        <?php if (!empty($_GET['msg'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-sm flex items-center space-x-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= htmlspecialchars($_GET['msg']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-500/40 text-red-800 dark:text-red-300 text-sm flex items-center space-x-3 shadow-sm">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <!-- CONSULTATION & BOOKING WORKFLOW FORM -->
        <form action="api.php" method="POST" enctype="multipart/form-data" onsubmit="return finalizeSubmission()" class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 mb-12">
            <input type="hidden" name="action" value="create_booking_with_visualizer">
            <input type="hidden" name="mockup_base64" id="mockup_base64">
            <input type="hidden" name="canvas_json" id="canvas_json">
            <input type="hidden" name="signature_base64" id="signature_base64">

            <!-- LEFT COLUMN: 2D ANATOMICAL CANVAS VISUALIZER (5 COLS) -->
            <div class="lg:col-span-5 bg-white dark:bg-zinc-900/70 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl flex flex-col justify-between transition-colors">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base sm:text-lg font-bold text-amber-600 dark:text-amber-400 flex items-center space-x-2">
                            <span>1. Anatomical 2D Visualizer</span>
                        </h2>
                        <span class="text-xs bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded font-semibold">Fabric.js 2D</span>
                    </div>

                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1.5">Select Anatomical Body-Region:</label>
                            <select name="body_region" id="bodyRegionSelect" onchange="drawBodyOutline(this.value)" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-slate-800 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                                <option value="forearm">Forearm Placement</option>
                                <option value="calf">Calf Placement</option>
                                <option value="shoulder">Shoulder / Deltoid</option>
                                <option value="back">Upper / Full Back</option>
                                <option value="chest">Pectoral / Chest</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1.5">Upload Tattoo Reference (.png, .jpg):</label>
                            <input type="file" name="raw_artwork" id="artworkInput" accept=".png,.jpg,.jpeg" required class="block w-full text-xs text-slate-600 dark:text-zinc-400 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-zinc-800 file:text-amber-600 dark:file:text-amber-400 hover:file:bg-slate-200 dark:hover:file:bg-zinc-700 cursor-pointer">
                        </div>

                        <!-- Canvas Controls -->
                        <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                            <div>
                                <label class="text-slate-600 dark:text-zinc-400 block mb-1">Artwork Opacity: <span id="opacityVal">90%</span></label>
                                <input type="range" id="opacitySlider" min="20" max="100" value="90" class="w-full accent-amber-500">
                            </div>
                            <div class="flex items-end">
                                <button type="button" onclick="resetActiveArtwork()" class="w-full py-2 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300 rounded-lg border border-slate-300 dark:border-zinc-700 text-xs font-medium transition-colors">Center Reset</button>
                            </div>
                        </div>
                    </div>

                    <!-- CANVAS CONTAINER WITH FLUID MOBILE VIEWPORT -->
                    <div class="flex flex-col items-center justify-center bg-slate-100 dark:bg-zinc-950 p-2 sm:p-4 rounded-xl border border-slate-200 dark:border-zinc-800 shadow-inner transition-colors">
                        <div id="canvasViewport" class="relative w-full max-w-[360px] flex justify-center overflow-hidden rounded-lg shadow-sm" style="touch-action: none;">
                            <canvas id="tattooCanvas" width="360" height="420" class="block"></canvas>
                        </div>
                    </div>

                    <!-- MOBILE TOUCH PLACEMENT HELPER TOOLBAR -->
                    <div class="mt-3 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 text-xs transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-slate-700 dark:text-zinc-300 flex items-center space-x-1.5">
                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>Touch & Precision Tools:</span>
                            </span>
                            <span class="text-[10px] text-slate-500 dark:text-zinc-400">Drag tattoo to position</span>
                        </div>
                        <div class="grid grid-cols-4 gap-1.5">
                            <button type="button" onclick="nudgeArtwork('zoomIn')" title="Enlarge Tattoo" class="py-2 px-1 bg-white dark:bg-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-800 dark:text-zinc-200 rounded-lg border border-slate-300 dark:border-zinc-700 font-semibold flex flex-col items-center justify-center transition-colors shadow-sm">
                                <svg class="w-4 h-4 mb-0.5 text-slate-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                <span class="text-[10px]">Zoom In</span>
                            </button>
                            <button type="button" onclick="nudgeArtwork('zoomOut')" title="Shrink Tattoo" class="py-2 px-1 bg-white dark:bg-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-800 dark:text-zinc-200 rounded-lg border border-slate-300 dark:border-zinc-700 font-semibold flex flex-col items-center justify-center transition-colors shadow-sm">
                                <svg class="w-4 h-4 mb-0.5 text-slate-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM7 10h6"/></svg>
                                <span class="text-[10px]">Zoom Out</span>
                            </button>
                            <button type="button" onclick="nudgeArtwork('rotate')" title="Rotate by 45°" class="py-2 px-1 bg-white dark:bg-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-800 dark:text-zinc-200 rounded-lg border border-slate-300 dark:border-zinc-700 font-semibold flex flex-col items-center justify-center transition-colors shadow-sm">
                                <svg class="w-4 h-4 mb-0.5 text-slate-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span class="text-[10px]">Rotate 45°</span>
                            </button>
                            <button type="button" onclick="resetActiveArtwork()" title="Center on Anatomical Template" class="py-2 px-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-lg border border-amber-500/30 font-semibold flex flex-col items-center justify-center transition-colors shadow-sm">
                                <svg class="w-4 h-4 mb-0.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v3m0 14v3m-10-10h3m14 0h3"/></svg>
                                <span class="text-[10px]">Center</span>
                            </button>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-3 text-center">
                    Touch & drag artwork to reposition. Use corner circle anchors or touch tools to scale & rotate.
                </p>
            </div>

            <!-- RIGHT COLUMN: BOOKING DETAILS, DEPOSIT & WAIVER PAD (7 COLS) -->
            <div class="lg:col-span-7 bg-white dark:bg-zinc-900/70 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl space-y-6 transition-colors">
                <!-- STEP 2: APPOINTMENT DETAILS & DEPOSIT -->
                <div>
                    <h2 class="text-lg font-bold text-amber-600 dark:text-amber-400 mb-4">2. Appointment Schedule & Down Payment</h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Select Tattoo Artist:</label>
                            <select name="artist_id" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-slate-800 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                                <?php foreach ($artists as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Service & Pricing Tier:</label>
                            <select name="service_id" id="serviceSelect" onchange="updateDepositEstimate(this)" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-slate-800 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-deposit="<?= $s['deposit_required'] ?>" data-base="<?= $s['base_price'] ?>">
                                        <?= htmlspecialchars($s['service_name']) ?> (₱<?= number_format($s['base_price'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Preferred Booking Date:</label>
                            <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-slate-800 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Preferred Time Window:</label>
                            <input type="time" name="appointment_time" value="13:00" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-slate-800 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Required Down Payment (₱):</label>
                            <input type="number" step="0.01" name="deposit_amount" id="depositAmountInput" value="<?= $services[0]['deposit_required'] ?? '500.00' ?>" readonly class="w-full bg-slate-100 dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-amber-600 dark:text-amber-400 font-bold focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">GCash / Bank Transfer Ref No.:</label>
                            <input type="text" name="payment_reference" placeholder="e.g. 902184920194" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-slate-800 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                        </div>
                    </div>

                    <div class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-zinc-950/70 border border-slate-200 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                        <div>
                            <span class="text-xs text-slate-500 dark:text-zinc-400 block font-medium">GCash Studio Payment QR / Account:</span>
                            <span class="text-sm font-bold text-slate-800 dark:text-zinc-200">AENGELOS STUDIO — 0917-111-2222</span>
                        </div>
                        <div class="sm:w-60">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Upload Receipt Screenshot:</label>
                            <input type="file" name="receipt_image" accept="image/*" required class="block w-full text-xs text-slate-600 dark:text-zinc-400 file:mr-2 file:py-1.5 file:px-2.5 file:rounded file:border-0 file:text-xs file:bg-slate-200 dark:file:bg-zinc-800 file:text-amber-600 dark:file:text-amber-400">
                        </div>
                    </div>
                </div>

                <hr class="border-slate-200 dark:border-zinc-800">

                <!-- STEP 3: DIGITAL MEDICAL WAIVER & TOUCHPAD -->
                <div>
                    <h2 class="text-lg font-bold text-amber-600 dark:text-amber-400 mb-3">3. Medical Disclosures & Touchscreen Legal Waiver</h2>
                    
                    <div class="space-y-3 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Skin, Ink, or Latex Allergies:</label>
                            <input type="text" name="skin_allergies" placeholder="Declare any known allergies (or type None)" value="None" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-slate-800 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Pre-Existing Conditions (Cardiac, Diabetes, Bleeding Disorders, Hepatitis):</label>
                            <input type="text" name="medical_conditions" placeholder="Declare conditions (or type None)" value="None" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-sm text-slate-800 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                        </div>

                        <div class="pt-1">
                            <label class="flex items-center space-x-2 text-sm text-slate-700 dark:text-zinc-300 cursor-pointer">
                                <input type="checkbox" name="keloid_history" value="1" class="rounded bg-slate-100 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-amber-500 focus:ring-0">
                                <span>I have a personal or family history of keloid / hypertrophic scarring</span>
                            </label>
                        </div>
                    </div>

                    <!-- SIGNATURE PAD SECTION -->
                    <div id="signaturePadSection" class="p-4 rounded-xl bg-slate-50 dark:bg-zinc-950/80 border border-slate-200 dark:border-zinc-800 transition-colors">
                        <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                            <div class="flex items-center space-x-2">
                                <label class="text-xs font-semibold text-slate-800 dark:text-zinc-200">Legal Touchscreen Signature Pad:</label>
                                <span id="sigStatusBadge" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">Waiting for Signature</span>
                            </div>
                            <div class="flex items-center space-x-3 text-xs">
                                <button type="button" onclick="undoSignature()" class="text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-zinc-200 transition-colors flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2m-15-7l4-4m-4 4l4 4"/></svg>
                                    <span>Undo</span>
                                </button>
                                <span class="text-slate-300 dark:text-zinc-700">|</span>
                                <button type="button" onclick="clearSignature()" class="text-amber-600 dark:text-amber-400 hover:underline">Clear Pad</button>
                            </div>
                        </div>

                        <!-- Canvas Container with Visual Signature Guide Line -->
                        <div id="sigCanvasContainer" class="relative bg-white rounded-xl border border-slate-300 dark:border-zinc-700 overflow-hidden shadow-inner" style="height: 140px;">
                            <canvas id="sigCanvas" class="w-full h-full block cursor-crosshair" style="touch-action: none;"></canvas>
                            <!-- Baseline watermark guideline -->
                            <div class="absolute bottom-4 left-6 right-6 flex items-center pointer-events-none select-none opacity-40">
                                <svg class="w-3.5 h-3.5 text-slate-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                <div class="flex-1 border-b border-dashed border-slate-400"></div>
                                <span class="text-[10px] uppercase tracking-wider text-slate-500 ml-2 font-sans font-semibold">Sign on line</span>
                            </div>
                        </div>
                        <span class="text-[11px] text-slate-500 dark:text-zinc-500 mt-2 block">Draw using stylus, finger, or mouse. Your signature is compiled into an immutable timestamped PDF archived for health inspections.</span>
                    </div>
                </div>

                <!-- SUBMIT ACTION -->
                <button type="submit" class="w-full py-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 transition-all text-base tracking-wide uppercase">
                    Confirm Placement, Submit Booking & Sign Waiver
                </button>
            </div>
        </form>

        <!-- BOOKING HISTORY & ARCHIVES -->
        <div class="bg-white dark:bg-zinc-900/70 p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl transition-colors">
            <h2 class="text-lg font-bold text-amber-600 dark:text-amber-400 mb-4">My Consultation & Booking History</h2>

            <?php if (empty($bookings)): ?>
                <div class="text-center py-8 text-slate-500 dark:text-zinc-500 text-sm">
                    No bookings found. Use the consultation visualizer above to schedule your first custom tattoo!
                </div>
            <?php else: ?>
                <!-- MOBILE CARDS VIEW (md:hidden) -->
                <div class="space-y-4 md:hidden">
                    <?php foreach ($bookings as $b): ?>
                        <div class="bg-slate-50 dark:bg-zinc-950 p-4 rounded-xl border border-slate-200 dark:border-zinc-800 space-y-3 shadow-sm transition-colors">
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <span class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400">#<?= $b['id'] ?></span>
                                    <h3 class="font-bold text-slate-900 dark:text-zinc-200 text-sm"><?= htmlspecialchars($b['service_name']) ?></h3>
                                    <p class="text-xs text-slate-500 dark:text-zinc-400">Artist: <?= htmlspecialchars($b['artist_name']) ?> • <span class="capitalize"><?= htmlspecialchars($b['body_region'] ?? 'Forearm') ?></span></p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-xs font-bold text-slate-900 dark:text-zinc-200 block">₱<?= number_format($b['deposit_amount'], 2) ?></span>
                                    <span class="text-[10px] text-slate-400 dark:text-zinc-500">Deposit</span>
                                </div>
                            </div>

                            <div class="flex items-center text-xs text-slate-600 dark:text-zinc-400">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400 dark:text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span><?= $b['appointment_date'] ?> at <?= $b['appointment_time'] ?></span>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200 dark:border-zinc-800 text-xs">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <?php if ($b['deposit_status'] === 'verified'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/20">Verified</span>
                                    <?php elseif ($b['deposit_status'] === 'rejected'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-300 dark:border-red-500/20">Deposit Rejected</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-500/20">Review Pending</span>
                                    <?php endif; ?>

                                    <?php if ($b['booking_status'] === 'confirmed'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/20">Confirmed</span>
                                    <?php elseif ($b['booking_status'] === 'completed'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-300 dark:border-blue-500/20">Completed</span>
                                    <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-300 dark:border-red-500/20">Cancelled</span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <?php if (!empty($b['mockup_image_path'])): ?>
                                        <button type="button" onclick="openClientMockupModal('../<?= htmlspecialchars($b['mockup_image_path']) ?>', '<?= htmlspecialchars($b['service_name']) ?>', '<?= ucfirst($b['body_region'] ?? 'Body') ?>')" class="px-2.5 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-lg border border-amber-500/30 text-[11px] font-semibold flex items-center space-x-1 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>Mockup</span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (!empty($b['pdf_archive_path'])): ?>
                                        <a href="../<?= htmlspecialchars($b['pdf_archive_path']) ?>" target="_blank" class="px-2.5 py-1 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-lg border border-emerald-500/30 text-[11px] font-semibold flex items-center space-x-1 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>Waiver</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- DESKTOP TABLE VIEW (hidden md:block) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase bg-slate-100 dark:bg-zinc-950/70 text-slate-600 dark:text-zinc-400 border-b border-slate-200 dark:border-zinc-800">
                            <tr>
                                <th class="py-3 px-4">Ref #</th>
                                <th class="py-3 px-4">Service</th>
                                <th class="py-3 px-4">Artist</th>
                                <th class="py-3 px-4">Placement</th>
                                <th class="py-3 px-4">Date & Time</th>
                                <th class="py-3 px-4">Deposit</th>
                                <th class="py-3 px-4">Deposit Status</th>
                                <th class="py-3 px-4">Booking Status</th>
                                <th class="py-3 px-4 text-right">Documents</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                            <?php foreach ($bookings as $b): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="py-3.5 px-4 font-mono font-bold text-amber-600 dark:text-amber-400">#<?= $b['id'] ?></td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-800 dark:text-zinc-200"><?= htmlspecialchars($b['service_name']) ?></td>
                                    <td class="py-3.5 px-4 text-slate-600 dark:text-zinc-300"><?= htmlspecialchars($b['artist_name']) ?></td>
                                    <td class="py-3.5 px-4 capitalize text-slate-500 dark:text-zinc-400"><?= htmlspecialchars($b['body_region'] ?? 'N/A') ?></td>
                                    <td class="py-3.5 px-4 text-slate-700 dark:text-zinc-300"><?= $b['appointment_date'] ?> <span class="text-xs text-slate-400 dark:text-zinc-500"><?= $b['appointment_time'] ?></span></td>
                                    <td class="py-3.5 px-4 text-slate-800 dark:text-zinc-200 font-medium">₱<?= number_format($b['deposit_amount'], 2) ?></td>
                                    <td class="py-3.5 px-4">
                                        <?php if ($b['deposit_status'] === 'verified'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/20">Verified</span>
                                        <?php elseif ($b['deposit_status'] === 'rejected'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-300 dark:border-red-500/20">Rejected</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-500/20">Pending Review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <?php if ($b['booking_status'] === 'confirmed'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/20">Confirmed</span>
                                        <?php elseif ($b['booking_status'] === 'completed'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-300 dark:border-blue-500/20">Completed</span>
                                        <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-300 dark:border-red-500/20">Cancelled</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 border border-slate-300 dark:border-zinc-700">Scheduled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right space-x-2">
                                        <?php if (!empty($b['mockup_image_path'])): ?>
                                            <button type="button" onclick="openClientMockupModal('../<?= htmlspecialchars($b['mockup_image_path']) ?>', '<?= htmlspecialchars($b['service_name']) ?>', '<?= ucfirst($b['body_region'] ?? 'Body') ?>')" class="text-xs font-medium text-amber-600 dark:text-amber-400 hover:underline">Mockup</button>
                                        <?php endif; ?>
                                        <?php if (!empty($b['pdf_archive_path'])): ?>
                                            <a href="../<?= htmlspecialchars($b['pdf_archive_path']) ?>" target="_blank" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">Waiver PDF</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CLIENT MOCKUP PREVIEW LIGHTBOX MODAL -->
    <div id="clientMockupModal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-700 rounded-2xl max-w-sm sm:max-w-md w-full p-5 text-center space-y-4 shadow-2xl transition-colors">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400">2D Anatomical Placement Preview</h3>
                <button type="button" onclick="closeClientMockupModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 p-1 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-slate-600 dark:text-zinc-400" id="clientMockupSubtitle"></p>
            <div class="flex justify-center rounded-xl bg-slate-100 dark:bg-zinc-950 p-2 border border-slate-200 dark:border-zinc-800 shadow-inner">
                <img id="clientMockupModalImg" src="" alt="Placement Mockup" class="max-h-[60vh] max-w-full object-contain rounded-lg">
            </div>
            <div class="flex space-x-2">
                <a id="clientMockupDownloadLink" href="" download="tattoo_mockup_preview.png" class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-lg text-xs font-bold transition-colors shadow-sm">Save Mockup</a>
                <button type="button" onclick="closeClientMockupModal()" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-800 dark:text-zinc-200 rounded-lg text-xs font-bold transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT: THEME TOGGLE, FABRIC.JS CANVAS & SIGNATURE PAD -->
    <script>
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeButton(isDark);
            
            // Update canvas background
            if (canvas) {
                canvas.setBackgroundColor(isDark ? '#121214' : '#f8fafc', canvas.renderAll.bind(canvas));
                drawBodyOutline(document.getElementById('bodyRegionSelect').value);
            }
        }

        function updateThemeButton(isDark) {
            const icon = document.getElementById('themeIcon');
            const label = document.getElementById('themeLabel');
            if (icon && label) {
                icon.innerHTML = isDark 
                    ? '<svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>'
                    : '<svg class="w-3.5 h-3.5 text-slate-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>';
                label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
            }
        }
        updateThemeButton(document.documentElement.classList.contains('dark'));

        // Client Mockup Lightbox Modal Controls
        function openClientMockupModal(imgSrc, serviceName, region) {
            document.getElementById('clientMockupModalImg').src = imgSrc;
            document.getElementById('clientMockupSubtitle').textContent = serviceName + ' — ' + region + ' Placement Mockup';
            document.getElementById('clientMockupDownloadLink').href = imgSrc;
            document.getElementById('clientMockupModal').classList.remove('hidden');
        }
        function closeClientMockupModal() {
            document.getElementById('clientMockupModal').classList.add('hidden');
        }

        // Initialize Fabric.js Canvas with Touch & Mobile Support
        const isCurrentDark = document.documentElement.classList.contains('dark');
        const canvas = new fabric.Canvas('tattooCanvas', { 
            backgroundColor: isCurrentDark ? '#121214' : '#f8fafc',
            preserveObjectStacking: true,
            allowTouchScrolling: false
        });

        if (canvas.upperCanvasEl) {
            canvas.upperCanvasEl.style.touchAction = 'none';
        }

        let currentOutline = null;
        let activeTattooImage = null;
        let currentVisualizerScale = 1;

        // Dynamic Responsive Visualizer Scaling for Mobile Devices
        function resizeVisualizer() {
            const viewport = document.getElementById('canvasViewport');
            if (!viewport || !canvas) return;

            const availableWidth = viewport.clientWidth;
            if (availableWidth <= 0) return;

            const baseWidth = 360;
            const baseHeight = 420;

            const targetWidth = Math.min(baseWidth, availableWidth);
            currentVisualizerScale = targetWidth / baseWidth;
            const targetHeight = baseHeight * currentVisualizerScale;

            canvas.setDimensions({
                width: targetWidth,
                height: targetHeight
            });
            canvas.setZoom(currentVisualizerScale);
            canvas.renderAll();
        }

        // Draw Flat 2D Anatomical Templates
        function drawBodyOutline(region) {
            if (currentOutline) canvas.remove(currentOutline);

            const isDark = document.documentElement.classList.contains('dark');
            const options = { 
                fill: isDark ? '#3f3f46' : '#e2e8f0', 
                stroke: isDark ? '#71717a' : '#94a3b8', 
                strokeWidth: 2, 
                selectable: false, 
                evented: false 
            };
            
            switch (region) {
                case 'forearm':
                    currentOutline = new fabric.Path('M 125 30 L 235 30 L 210 390 L 150 390 Z', options);
                    break;
                case 'calf':
                    currentOutline = new fabric.Path('M 125 30 Q 250 170 205 390 L 155 390 Q 110 170 125 30 Z', options);
                    break;
                case 'shoulder':
                    currentOutline = new fabric.Circle({ radius: 120, left: 60, top: 70, ...options });
                    break;
                case 'back':
                    currentOutline = new fabric.Rect({ left: 65, top: 35, width: 230, height: 350, rx: 25, ry: 25, ...options });
                    break;
                case 'chest':
                    currentOutline = new fabric.Rect({ left: 55, top: 55, width: 250, height: 260, rx: 20, ry: 20, ...options });
                    break;
                default:
                    currentOutline = new fabric.Path('M 125 30 L 235 30 L 210 390 L 150 390 Z', options);
            }

            canvas.add(currentOutline);
            canvas.sendToBack(currentOutline);
            canvas.renderAll();
        }

        // Initialize with forearm template
        drawBodyOutline('forearm');

        // Artwork File Upload Handler with Finger-Friendly Touch Handles
        document.getElementById('artworkInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                fabric.Image.fromURL(event.target.result, function(img) {
                    if (activeTattooImage) canvas.remove(activeTattooImage);

                    img.scaleToWidth(140);
                    img.set({
                        left: 110,
                        top: 140,
                        opacity: parseInt(document.getElementById('opacitySlider').value) / 100,
                        cornerColor: '#d97706',
                        cornerStrokeColor: '#ffffff',
                        borderColor: '#d97706',
                        cornerSize: 14,
                        touchCornerSize: 32,
                        transparentCorners: false,
                        cornerStyle: 'circle'
                    });

                    activeTattooImage = img;
                    canvas.add(img);
                    canvas.setActiveObject(img);
                    canvas.renderAll();
                });
            };
            reader.readAsDataURL(file);
        });

        // Opacity Slider
        document.getElementById('opacitySlider').addEventListener('input', function(e) {
            const val = e.target.value;
            document.getElementById('opacityVal').textContent = val + '%';
            if (activeTattooImage) {
                activeTattooImage.set('opacity', parseInt(val) / 100);
                canvas.renderAll();
            }
        });

        // Touch & Precision Helper Tools
        function nudgeArtwork(action) {
            if (!activeTattooImage) {
                alert('Please upload your tattoo artwork reference first.');
                return;
            }
            if (action === 'zoomIn') {
                activeTattooImage.scale(activeTattooImage.scaleX * 1.15);
            } else if (action === 'zoomOut') {
                activeTattooImage.scale(Math.max(0.1, activeTattooImage.scaleX * 0.85));
            } else if (action === 'rotate') {
                activeTattooImage.rotate(((activeTattooImage.angle || 0) + 45) % 360);
            }
            activeTattooImage.setCoords();
            canvas.renderAll();
        }

        // Reset artwork position
        function resetActiveArtwork() {
            if (activeTattooImage) {
                activeTattooImage.set({ left: 110, top: 140, angle: 0 });
                activeTattooImage.scaleToWidth(140);
                activeTattooImage.setCoords();
                canvas.renderAll();
            }
        }

        // Service deposit sync
        function updateDepositEstimate(sel) {
            const opt = sel.options[sel.selectedIndex];
            const deposit = opt.getAttribute('data-deposit');
            if (deposit) {
                document.getElementById('depositAmountInput').value = parseFloat(deposit).toFixed(2);
            }
        }

        // Initialize Signature Pad with Responsive DPI Scaling & Fallback
        const sigCanvas = document.getElementById('sigCanvas');
        const sigCanvasContainer = document.getElementById('sigCanvasContainer');
        const sigStatusBadge = document.getElementById('sigStatusBadge');
        let sigPad = null;

        function initSignaturePad() {
            if (typeof SignaturePad !== 'undefined') {
                sigPad = new SignaturePad(sigCanvas, {
                    minWidth: 1.2,
                    maxWidth: 3.2,
                    penColor: '#09090b',
                    backgroundColor: 'rgba(255, 255, 255, 1)',
                    throttle: 16,
                    velocityFilterWeight: 0.7
                });

                sigPad.addEventListener('endStroke', updateSignatureStatus);
            } else {
                initCanvasSignatureFallback();
            }
            resizeSignatureCanvas();
            resizeVisualizer();
        }

        function resizeSignatureCanvas() {
            if (!sigCanvas || !sigCanvasContainer) return;
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = sigCanvasContainer.getBoundingClientRect();
            if (rect.width === 0) return;

            const existingData = (sigPad && !sigPad.isEmpty()) ? sigPad.toData() : null;

            sigCanvas.width = rect.width * ratio;
            sigCanvas.height = 140 * ratio;

            const ctx = sigCanvas.getContext('2d');
            ctx.scale(ratio, ratio);

            if (sigPad) {
                sigPad.clear();
                if (existingData && existingData.length) {
                    sigPad.fromData(existingData);
                }
            }
            updateSignatureStatus();
        }

        function updateSignatureStatus() {
            if (!sigPad || sigPad.isEmpty()) {
                sigStatusBadge.className = 'px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
                sigStatusBadge.textContent = 'Waiting for Signature';
            } else {
                sigStatusBadge.className = 'px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
                sigStatusBadge.innerHTML = '<span class="inline-flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Signature Captured</span>';
                document.getElementById('signaturePadSection').classList.remove('border-red-500', 'bg-red-50', 'dark:bg-red-950/20');
            }
        }

        function undoSignature() {
            if (!sigPad) return;
            const data = sigPad.toData();
            if (data && data.length) {
                data.pop();
                sigPad.fromData(data);
                updateSignatureStatus();
            }
        }

        function clearSignature() {
            if (sigPad) {
                sigPad.clear();
                updateSignatureStatus();
            }
        }

        function initCanvasSignatureFallback() {
            const ctx = sigCanvas.getContext('2d');
            let isDrawing = false;
            let hasDrawn = false;

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, sigCanvas.width, sigCanvas.height);
            ctx.strokeStyle = '#09090b';
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            function getPos(e) {
                const rect = sigCanvas.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return { x: clientX - rect.left, y: clientY - rect.top };
            }

            function startDraw(e) {
                e.preventDefault();
                isDrawing = true;
                const p = getPos(e);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            }

            function moveDraw(e) {
                if (!isDrawing) return;
                e.preventDefault();
                const p = getPos(e);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
                hasDrawn = true;
                updateSignatureStatus();
            }

            function stopDraw() { isDrawing = false; }

            sigCanvas.addEventListener('mousedown', startDraw);
            sigCanvas.addEventListener('mousemove', moveDraw);
            window.addEventListener('mouseup', stopDraw);

            sigCanvas.addEventListener('touchstart', startDraw, { passive: false });
            sigCanvas.addEventListener('touchmove', moveDraw, { passive: false });
            window.addEventListener('touchend', stopDraw);

            sigPad = {
                isEmpty: () => !hasDrawn,
                clear: () => {
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, sigCanvas.width, sigCanvas.height);
                    hasDrawn = false;
                    updateSignatureStatus();
                },
                toDataURL: (fmt) => sigCanvas.toDataURL(fmt),
                toData: () => [],
                fromData: () => {}
            };
        }

        // Global Responsive Event Handlers
        window.addEventListener('resize', () => {
            resizeSignatureCanvas();
            resizeVisualizer();
        });
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                resizeSignatureCanvas();
                resizeVisualizer();
            }, 150);
        });
        window.addEventListener('DOMContentLoaded', () => {
            initSignaturePad();
            resizeVisualizer();
        });

        function finalizeSubmission() {
            if (!activeTattooImage) {
                alert('Please upload your tattoo artwork reference to compose your placement preview.');
                return false;
            }

            if (!sigPad || sigPad.isEmpty()) {
                const sec = document.getElementById('signaturePadSection');
                sec.classList.add('border-red-500', 'bg-red-50', 'dark:bg-red-950/20');
                sec.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert('Please sign the digital medical liability waiver using the signature pad before submitting.');
                return false;
            }

            // Restore zoom=1 and dimensions 360x420 before capture to export crisp full-resolution mockup
            canvas.setZoom(1);
            canvas.setDimensions({ width: 360, height: 420 });
            canvas.renderAll();

            document.getElementById('mockup_base64').value = canvas.toDataURL({ format: 'png', quality: 0.95 });
            document.getElementById('canvas_json').value = JSON.stringify(canvas.toJSON());
            document.getElementById('signature_base64').value = sigPad.toDataURL('image/png');

            // Restore mobile responsive scale
            resizeVisualizer();

            return true;
        }
    </script>
</body>
</html>
