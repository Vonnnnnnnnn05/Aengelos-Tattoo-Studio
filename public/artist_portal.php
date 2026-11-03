<?php
// public/artist_portal.php - Artist Operations: Deposit Verification, Flash Upload & Session Completion
require_once __DIR__ . '/../config/db.php';
enforceRole(['artist', 'admin']);

$artistId = $_SESSION['user']['id'];
$isMasterAdmin = $_SESSION['user']['role'] === 'admin';

// Query appointments assigned to artist (or all if admin)
$apptQuery = "
    SELECT a.*, u.full_name AS client_name, u.phone AS client_phone, u.email AS client_email,
           COALESCE(cat.title, s.service_name, 'Tattoo Design') AS service_name,
           COALESCE(cat.price, s.base_price, a.deposit_amount * 3) AS base_price,
           cat.image_path AS catalog_image,
           COALESCE(a.body_region, m.body_region, 'Forearm') AS body_region,
           a.placement_notes,
           m.mockup_image_path, m.raw_artwork_path,
           w.pdf_archive_path, w.signature_image_path, w.skin_allergies, w.keloid_history, w.medical_conditions
    FROM appointments a
    JOIN users u ON a.client_id = u.id
    LEFT JOIN service_pricing_matrix s ON a.service_id = s.id
    LEFT JOIN tattoo_catalog cat ON a.catalog_id = cat.id
    LEFT JOIN visualizer_mockups m ON a.mockup_id = m.id
    LEFT JOIN digital_waivers w ON a.id = w.appointment_id
";

if (!$isMasterAdmin) {
    $apptQuery .= " WHERE a.artist_id = " . (int)$artistId;
}
$apptQuery .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$allAppts = $pdo->query($apptQuery)->fetchAll();

// Separate appointments by status
$pendingDeposits = array_filter($allAppts, fn($a) => $a['deposit_status'] === 'pending');
$confirmedAppts = array_filter($allAppts, fn($a) => $a['booking_status'] === 'confirmed' || $a['booking_status'] === 'in_session');
$completedAppts = array_filter($allAppts, fn($a) => $a['booking_status'] === 'completed');

// Query artist's own uploaded flash designs
$myDesignsStmt = $pdo->prepare("SELECT * FROM tattoo_catalog WHERE uploader_id = ? ORDER BY id DESC");
$myDesignsStmt->execute([$artistId]);
$myDesigns = $myDesignsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Operations & Schedule — Aengelos Tattoo Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-zinc-950 dark:text-zinc-100 min-h-screen transition-colors duration-200">
    <!-- UNIFIED TOP NAVBAR COMPONENT -->
    <?php 
    $navContext = 'artist';
    require_once __DIR__ . '/navbar.php'; 
    ?>

    <div class="max-w-7xl mx-auto px-3 sm:px-6 py-6 sm:py-8 space-y-6 sm:space-y-8">
        <!-- SYSTEM NOTIFICATIONS -->
        <?php if (!empty($_GET['msg'])): ?>
            <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-sm flex items-center space-x-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= htmlspecialchars($_GET['msg']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="p-4 rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-500/40 text-red-800 dark:text-red-300 text-sm flex items-center space-x-3 shadow-sm">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <!-- SECTION: ARTIST FLASH ART UPLOAD (LIVE ON SHOPEE STOREFRONT) -->
        <div id="uploadSection" class="bg-white dark:bg-zinc-900/80 p-5 sm:p-7 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 dark:border-zinc-800 pb-4">
                <div>
                    <h2 class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 flex items-center space-x-2">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Upload & Publish Client Proofs & Tattoo Designs</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">Upload photos of your completed past client works or original flash pieces directly to the index Shopee storefront so customers can view your healed results, inspect prices, and avail them.</p>
                </div>
                <span class="text-xs text-amber-600 dark:text-amber-400 font-bold bg-amber-500/10 px-2.5 py-1 rounded-lg self-start sm:self-auto">
                    <?= count($myDesigns) ?> Published by You
                </span>
            </div>

            <!-- UPLOAD FORM -->
            <form action="api.php" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                <input type="hidden" name="action" value="upload_catalog_item">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Tattoo Title / Client Proof Name:</label>
                        <input type="text" name="title" required placeholder="e.g. Japanese Dragon Sleeve (Client Proof)" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-slate-900 dark:text-zinc-200 font-semibold focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Style Category:</label>
                        <select name="category" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-slate-900 dark:text-zinc-200 font-semibold focus:border-amber-500 focus:outline-none">
                            <option value="Flash Art">Flash Art</option>
                            <option value="Japanese / Irezumi">Japanese / Irezumi</option>
                            <option value="Minimalist">Minimalist / Fine Line</option>
                            <option value="Realism">Realism / Portrait</option>
                            <option value="Custom Design">Custom Masterpiece</option>
                            <option value="Cover-Up">Cover-Up Specialty</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Full Inking Price (₱):</label>
                        <input type="number" step="0.01" name="price" id="artistPrice" oninput="calcArtistDeposit(this.value)" required placeholder="4000.00" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-amber-600 dark:text-amber-400 font-mono font-bold focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Required Deposit (₱):</label>
                        <input type="number" step="0.01" name="deposit_required" id="artistDeposit" required placeholder="1200.00" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-emerald-600 dark:text-emerald-400 font-mono font-bold focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Estimated Duration (Mins):</label>
                        <input type="number" name="est_duration_mins" value="120" min="30" step="15" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Artwork Image (.png/.jpg/.webp):</label>
                        <input type="file" name="catalog_image" accept=".png,.jpg,.jpeg,.webp" required class="block w-full text-xs text-slate-600 dark:text-zinc-400 file:mr-2 file:py-2 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 dark:file:bg-zinc-800 file:text-amber-600 dark:file:text-amber-400 cursor-pointer">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Piece Description / Placement Recommendations:</label>
                    <textarea name="description" rows="2" placeholder="e.g. Recommended for outer forearm or calf. High-contrast shading with crimson pigment accents." class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="py-2.5 px-6 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black rounded-xl text-xs uppercase tracking-wider shadow-md shadow-amber-500/20 transition-all">
                        Publish to Storefront Catalog
                    </button>
                </div>
            </form>

            <!-- ARTIST'S EXISTING DESIGNS -->
            <?php if (!empty($myDesigns)): ?>
                <div class="pt-4 border-t border-slate-100 dark:border-zinc-800">
                    <h3 class="text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-3">Your Live Designs in Store:</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <?php foreach ($myDesigns as $d): ?>
                            <div class="bg-slate-50 dark:bg-zinc-950 p-2.5 rounded-2xl border border-slate-200 dark:border-zinc-800 space-y-2 flex flex-col justify-between">
                                <div class="aspect-square rounded-xl overflow-hidden bg-slate-200 dark:bg-zinc-900">
                                    <img src="../<?= htmlspecialchars($d['image_path']) ?>" alt="" class="w-full h-full object-cover">
                                </div>
                                <div class="space-y-0.5">
                                    <h4 class="font-bold text-slate-900 dark:text-white truncate text-xs"><?= htmlspecialchars($d['title']) ?></h4>
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold block"><?= htmlspecialchars($d['category']) ?></span>
                                    <div class="font-mono font-bold text-slate-900 dark:text-zinc-200 text-xs">₱<?= number_format($d['price'], 2) ?></div>
                                </div>
                                <form action="api.php" method="POST" onsubmit="return confirm('Remove design from storefront?')" class="pt-1">
                                    <input type="hidden" name="action" value="delete_catalog_item">
                                    <input type="hidden" name="catalog_id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="w-full py-1 text-[10px] font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 rounded transition-colors">
                                        Delete Piece
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECTION 1: PENDING DEPOSIT VERIFICATIONS -->
        <div id="depositsSection" class="bg-white dark:bg-zinc-900/70 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl transition-colors">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4">
                <h2 class="text-base sm:text-lg font-bold text-amber-600 dark:text-amber-400 flex items-center space-x-2">
                    <span>1. Pending Down Payment Verifications</span>
                    <span class="text-xs bg-amber-500/20 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-full font-bold"><?= count($pendingDeposits) ?></span>
                </h2>
                <span class="text-xs text-slate-500 dark:text-zinc-500">Inspect client GCash / bank screenshots before confirming</span>
            </div>

            <?php if (empty($pendingDeposits)): ?>
                <div class="text-center py-6 text-slate-500 dark:text-zinc-500 text-xs">All submitted down payments have been verified!</div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    <?php foreach ($pendingDeposits as $appt): ?>
                        <div class="bg-slate-50 dark:bg-zinc-950 p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-zinc-800 flex flex-col justify-between space-y-4 shadow-sm transition-colors">
                            <div>
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400">#<?= $appt['id'] ?></span>
                                    <span class="text-xs bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded font-bold">₱<?= number_format($appt['deposit_amount'], 2) ?></span>
                                </div>
                                <h3 class="font-bold text-slate-900 dark:text-zinc-200"><?= htmlspecialchars($appt['client_name']) ?></h3>
                                <p class="text-xs text-slate-600 dark:text-zinc-400 font-semibold"><?= htmlspecialchars($appt['service_name']) ?></p>
                                <div class="mt-1.5 p-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs">
                                    <span class="text-slate-500 dark:text-zinc-400 block text-[10px] uppercase font-bold">Target Placement:</span>
                                    <span class="font-bold text-amber-700 dark:text-amber-400 flex items-center space-x-1 mt-0.5">
                                        <svg class="w-3 h-3 text-amber-500 inline-block flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span><?= htmlspecialchars($appt['body_region'] ?? 'Forearm') ?></span>
                                    </span>
                                    <?php if (!empty($appt['placement_notes'])): ?>
                                        <span class="text-[11px] text-slate-600 dark:text-zinc-300 block mt-0.5 italic">"<?= htmlspecialchars($appt['placement_notes']) ?>"</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-zinc-400 mt-1">Ref Code: <strong class="text-slate-900 dark:text-zinc-200 font-mono"><?= htmlspecialchars($appt['payment_reference']) ?></strong></p>
                                <p class="text-xs text-slate-500 dark:text-zinc-500 mt-0.5">Date: <?= $appt['appointment_date'] ?> @ <?= $appt['appointment_time'] ?></p>
                            </div>

                            <div class="space-y-2">
                                <?php if (!empty($appt['mockup_image_path'])): ?>
                                    <button type="button" onclick="openMockupModal('../<?= htmlspecialchars($appt['mockup_image_path']) ?>')" class="w-full py-2 px-3 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 text-xs rounded-lg border border-amber-500/30 flex items-center justify-center space-x-1.5 transition-colors font-semibold">
                                        <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Inspect 2D Placement Mockup</span>
                                    </button>
                                <?php endif; ?>

                                <?php if (!empty($appt['receipt_image_path'])): ?>
                                    <button type="button" onclick="openReceiptModal('../<?= htmlspecialchars($appt['receipt_image_path']) ?>', '<?= htmlspecialchars($appt['payment_reference']) ?>')" class="w-full py-2 px-3 bg-white dark:bg-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs rounded-lg border border-slate-300 dark:border-zinc-700 flex items-center justify-center space-x-1.5 transition-colors shadow-sm font-medium">
                                        <svg class="w-3.5 h-3.5 text-slate-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Inspect Receipt Screenshot</span>
                                    </button>
                                <?php endif; ?>

                                <form action="api.php" method="POST" class="grid grid-cols-2 gap-2">
                                    <input type="hidden" name="action" value="verify_deposit">
                                    <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                    <button type="submit" name="decision" value="approve" class="py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs shadow-sm transition-colors">Verify & Confirm</button>
                                    <button type="submit" name="decision" value="reject" class="py-2 bg-red-600 hover:bg-red-500 text-white font-bold rounded-lg text-xs shadow-sm transition-colors">Reject</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECTION 2: CONFIRMED SCHEDULE & READY FOR TATTOOING -->
        <div id="scheduleSection" class="bg-white dark:bg-zinc-900/70 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl transition-colors">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4">
                <h2 class="text-base sm:text-lg font-bold text-emerald-600 dark:text-emerald-400 flex items-center space-x-2">
                    <span>2. Confirmed Studio Schedule & In-Session Queue</span>
                    <span class="text-xs bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded-full font-bold"><?= count($confirmedAppts) ?></span>
                </h2>
                <span class="text-xs text-slate-500 dark:text-zinc-500">Inspect 2D placement mockups & signed legal waivers before tattooing</span>
            </div>

            <?php if (empty($confirmedAppts)): ?>
                <div class="text-center py-6 text-slate-500 dark:text-zinc-500 text-xs">No confirmed sessions in queue for today.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="uppercase bg-slate-100 dark:bg-zinc-950/70 text-slate-600 dark:text-zinc-400 border-b border-slate-200 dark:border-zinc-800">
                            <tr>
                                <th class="py-3 px-3">Ref #</th>
                                <th class="py-3 px-3">Client</th>
                                <th class="py-3 px-3">Design / Service</th>
                                <th class="py-3 px-3">Schedule</th>
                                <th class="py-3 px-3">Artwork / Mockup</th>
                                <th class="py-3 px-3">Legal Waiver</th>
                                <th class="py-3 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                            <?php foreach ($confirmedAppts as $appt): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="py-3.5 px-3 font-mono font-bold text-amber-600 dark:text-amber-400">#<?= $appt['id'] ?></td>
                                    <td class="py-3.5 px-3">
                                        <strong class="text-slate-900 dark:text-zinc-200 block text-sm"><?= htmlspecialchars($appt['client_name']) ?></strong>
                                        <span class="text-slate-500 dark:text-zinc-500"><?= htmlspecialchars($appt['client_phone']) ?></span>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="text-slate-800 dark:text-zinc-200 font-semibold block"><?= htmlspecialchars($appt['service_name']) ?></span>
                                        <span class="text-amber-600 dark:text-amber-400 uppercase font-bold text-[10px] flex items-center space-x-1 mt-0.5">
                                            <svg class="w-3 h-3 text-amber-500 inline-block flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span><?= htmlspecialchars($appt['body_region'] ?? 'Body Placement') ?></span>
                                        </span>
                                        <?php if (!empty($appt['placement_notes'])): ?>
                                            <span class="text-[10px] text-slate-500 dark:text-zinc-400 block truncate max-w-[180px]"><?= htmlspecialchars($appt['placement_notes']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-3 text-slate-700 dark:text-zinc-300 font-medium"><?= $appt['appointment_date'] ?> @ <?= $appt['appointment_time'] ?></td>
                                    <td class="py-3.5 px-3 space-y-1">
                                        <?php if (!empty($appt['mockup_image_path'])): ?>
                                            <button type="button" onclick="openMockupModal('../<?= htmlspecialchars($appt['mockup_image_path']) ?>')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline flex items-center space-x-1.5 font-bold">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <span>View 2D Mockup</span>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!empty($appt['catalog_image'])): ?>
                                            <a href="../<?= htmlspecialchars($appt['catalog_image']) ?>" target="_blank" class="text-[11px] text-slate-600 dark:text-zinc-400 hover:text-amber-500 flex items-center space-x-1 font-medium">
                                                <img src="../<?= htmlspecialchars($appt['catalog_image']) ?>" class="w-5 h-5 object-cover rounded border border-slate-300 dark:border-zinc-700">
                                                <span>Flash Artwork</span>
                                            </a>
                                        <?php elseif (empty($appt['mockup_image_path'])): ?>
                                            <span class="text-slate-400 dark:text-zinc-600">Direct Inking</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <?php if (!empty($appt['pdf_archive_path'])): ?>
                                            <a href="../<?= htmlspecialchars($appt['pdf_archive_path']) ?>" target="_blank" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline flex items-center space-x-1.5 font-semibold">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span>Signed PDF Waiver</span>
                                            </a>
                                            <?php if (!empty($appt['signature_image_path']) && filesize(__DIR__ . '/../' . $appt['signature_image_path']) > 20): ?>
                                                <a href="../<?= htmlspecialchars($appt['signature_image_path']) ?>" target="_blank" class="text-[10px] text-slate-500 dark:text-zinc-400 hover:underline flex items-center space-x-1 mt-0.5">
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    <span>Signature Stamp</span>
                                                </a>
                                            <?php endif; ?>
                                            <span class="text-[10px] text-slate-500 dark:text-zinc-500 block">Allergies: <?= htmlspecialchars($appt['skin_allergies'] ?? 'None') ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-400 dark:text-zinc-500">Standard Waiver</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-3 text-right">
                                        <button type="button" onclick="openSessionModal(<?= $appt['id'] ?>, '<?= htmlspecialchars($appt['client_name']) ?>', <?= $appt['base_price'] ?>)" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg text-xs shadow-sm transition-colors">
                                            Log Completed Session
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECTION 3: COMPLETED SESSIONS ARCHIVE -->
        <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl transition-colors">
            <h2 class="text-base sm:text-lg font-bold text-slate-700 dark:text-zinc-400 mb-3">3. Completed Sessions Log</h2>
            <?php if (empty($completedAppts)): ?>
                <div class="text-center py-4 text-slate-500 dark:text-zinc-500 text-xs">No completed sessions logged yet.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="uppercase bg-slate-100 dark:bg-zinc-950/70 text-slate-600 dark:text-zinc-400 border-b border-slate-200 dark:border-zinc-800">
                            <tr>
                                <th class="py-2.5 px-3">Ref #</th>
                                <th class="py-2.5 px-3">Client</th>
                                <th class="py-2.5 px-3">Service / Piece</th>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                            <?php foreach ($completedAppts as $appt): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-600 dark:text-zinc-400">#<?= $appt['id'] ?></td>
                                    <td class="py-2.5 px-3 font-medium text-slate-800 dark:text-zinc-200"><?= htmlspecialchars($appt['client_name']) ?></td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-zinc-400"><?= htmlspecialchars($appt['service_name']) ?></td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-zinc-400"><?= $appt['appointment_date'] ?></td>
                                    <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded text-[10px] bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-300 dark:border-blue-500/20 font-bold">Completed</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL 1: RECEIPT INSPECTION LIGHTBOX -->
    <div id="receiptModal" class="hidden fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-700 rounded-2xl max-w-lg w-full max-w-[92vw] p-4 sm:p-6 text-center space-y-4 shadow-2xl transition-colors">
            <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400">Client Payment Receipt Proof</h3>
            <p class="text-xs text-slate-600 dark:text-zinc-400 font-mono" id="receiptRefText"></p>
            <div class="flex justify-center max-h-[60vh] overflow-auto rounded-xl bg-slate-100 dark:bg-zinc-950 p-2 border border-slate-200 dark:border-zinc-800">
                <img id="receiptModalImg" src="" alt="Payment Receipt" class="max-h-[50vh] max-w-full object-contain rounded">
            </div>
            <button type="button" onclick="closeReceiptModal()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-800 dark:text-zinc-200 rounded-lg text-xs font-bold transition-colors">Close</button>
        </div>
    </div>

    <!-- MODAL 2: 2D MOCKUP COMPOSITE LIGHTBOX -->
    <div id="mockupModal" class="hidden fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-700 rounded-2xl max-w-md w-full max-w-[92vw] p-4 sm:p-6 text-center space-y-4 shadow-2xl transition-colors">
            <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400">2D Anatomical Canvas Placement Mockup</h3>
            <div class="flex justify-center rounded-xl bg-slate-100 dark:bg-zinc-950 p-2 border border-slate-200 dark:border-zinc-800">
                <img id="mockupModalImg" src="" alt="Tattoo Mockup" class="max-h-[60vh] max-w-full object-contain rounded-lg">
            </div>
            <button type="button" onclick="closeMockupModal()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-800 dark:text-zinc-200 rounded-lg text-xs font-bold transition-colors">Close</button>
        </div>
    </div>

    <!-- MODAL 3: SESSION EXECUTION LOG (CLEAN SESSION LOGGING, INVENTORY REMOVED) -->
    <div id="sessionModal" class="hidden fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-700 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl transition-colors">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400">Log Completed Tattoo Session</h3>
                <button type="button" onclick="closeSessionModal()" class="text-slate-400 hover:text-slate-800 dark:hover:text-white">&times;</button>
            </div>
            <form action="api.php" method="POST" class="space-y-3.5 text-xs text-slate-700 dark:text-zinc-300">
                <input type="hidden" name="action" value="complete_session">
                <input type="hidden" name="appointment_id" id="modalApptId">

                <div>
                    <label class="block text-slate-600 dark:text-zinc-400 mb-1 font-semibold">Client:</label>
                    <input type="text" id="modalClientName" readonly class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 font-bold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-600 dark:text-zinc-400 mb-1 font-semibold">Duration (Minutes):</label>
                        <input type="number" name="duration_minutes" value="180" min="15" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2 text-slate-900 dark:text-zinc-200">
                    </div>
                    <div>
                        <label class="block text-slate-600 dark:text-zinc-400 mb-1 font-semibold">Total Fee (₱):</label>
                        <input type="number" step="0.01" name="total_fee" id="modalTotalFee" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2 text-amber-600 dark:text-amber-400 font-bold">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-600 dark:text-zinc-400 mb-1 font-semibold">Artist Notes & Skin Tolerance:</label>
                    <textarea name="session_notes" rows="3" placeholder="e.g. Crisp clean linework, client handled session well, no adverse reactions." class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2 text-slate-900 dark:text-zinc-200"></textarea>
                </div>

                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg uppercase tracking-wider text-xs shadow-md transition-colors">
                    Commit Session & Mark Completed
                </button>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function calcArtistDeposit(val) {
            const num = parseFloat(val);
            if (!isNaN(num) && num > 0) {
                document.getElementById('artistDeposit').value = (num * 0.30).toFixed(2);
            }
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeButton(isDark);
        }

        function updateThemeButton(isDark) {
            const icon = document.getElementById('themeIcon');
            const label = document.getElementById('themeLabel');
            if (icon && label) {
                icon.innerHTML = isDark 
                    ? '<svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>'
                    : '<svg class="w-3.5 h-3.5 text-slate-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>';
                label.textContent = isDark ? 'Light' : 'Dark';
            }
        }
        updateThemeButton(document.documentElement.classList.contains('dark'));

        function openReceiptModal(src, ref) {
            document.getElementById('receiptModalImg').src = src;
            document.getElementById('receiptRefText').textContent = 'Transaction Ref: ' + ref;
            document.getElementById('receiptModal').classList.remove('hidden');
        }
        function closeReceiptModal() {
            document.getElementById('receiptModal').classList.add('hidden');
        }

        function openMockupModal(src) {
            document.getElementById('mockupModalImg').src = src;
            document.getElementById('mockupModal').classList.remove('hidden');
        }
        function closeMockupModal() {
            document.getElementById('mockupModal').classList.add('hidden');
        }

        function openSessionModal(apptId, clientName, basePrice) {
            document.getElementById('modalApptId').value = apptId;
            document.getElementById('modalClientName').value = clientName;
            document.getElementById('modalTotalFee').value = parseFloat(basePrice).toFixed(2);
            document.getElementById('sessionModal').classList.remove('hidden');
        }
        function closeSessionModal() {
            document.getElementById('sessionModal').classList.add('hidden');
        }
    </script>
</body>
</html>
