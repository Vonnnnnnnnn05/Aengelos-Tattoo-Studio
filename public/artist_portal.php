<?php
// public/artist_portal.php - Artist Operations: Deposit Verification, Mockup/Waiver Viewer & FIFO Session Log
require_once __DIR__ . '/../config/db.php';
enforceRole(['artist', 'admin']);

$artistId = $_SESSION['user']['id'];
$isMasterAdmin = $_SESSION['user']['role'] === 'admin';

// Query appointments assigned to artist (or all if admin)
$apptQuery = "
    SELECT a.*, u.full_name AS client_name, u.phone AS client_phone, u.email AS client_email,
           s.service_name, s.base_price, m.body_region, m.mockup_image_path, m.raw_artwork_path,
           w.pdf_archive_path, w.signature_image_path, w.skin_allergies, w.keloid_history, w.medical_conditions
    FROM appointments a
    JOIN users u ON a.client_id = u.id
    JOIN service_pricing_matrix s ON a.service_id = s.id
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

// Query supplies for the session logging form
$supplies = $pdo->query("SELECT * FROM sterile_supplies WHERE total_stock > 0 ORDER BY category ASC, supply_name ASC")->fetchAll();
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
        // Initialize theme: Default to Light Mode unless explicitly set to dark
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-zinc-950 dark:text-zinc-100 min-h-screen transition-colors duration-200">
    <!-- NAVIGATION BAR -->
    <nav class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur border-b border-slate-200 dark:border-zinc-800 sticky top-0 z-50 px-3 sm:px-6 py-3 sm:py-4 transition-colors">
        <div class="max-w-7xl mx-auto flex justify-between items-center gap-2">
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                <span class="w-3 sm:w-3.5 h-3 sm:h-3.5 rounded-full bg-amber-500 shadow-md shadow-amber-500/50 flex-shrink-0"></span>
                <span class="text-sm sm:text-xl font-bold tracking-wider text-amber-600 dark:text-amber-400 truncate">AENGELOS TATTOO STUDIO</span>
                <span class="hidden md:inline text-xs bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 px-2.5 py-0.5 rounded-full font-semibold flex-shrink-0">Artist Station</span>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4 text-xs sm:text-sm flex-shrink-0">
                <span class="text-slate-600 dark:text-zinc-300 hidden lg:inline">Artist on Duty: <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></span>
                <!-- THEME TOGGLE -->
                <button type="button" onclick="toggleTheme()" class="px-2 sm:px-2.5 py-1 rounded-lg border border-slate-300 dark:border-zinc-700 bg-slate-100 dark:bg-zinc-800 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors flex items-center space-x-1.5 shadow-sm">
                    <span id="themeIcon" class="flex items-center"></span>
                    <span id="themeLabel" class="hidden sm:inline">Dark Mode</span>
                </button>
                <?php if ($isMasterAdmin): ?>
                    <a href="admin_portal.php" class="text-xs text-red-600 dark:text-red-400 hover:underline font-semibold hidden sm:inline">Admin</a>
                <?php endif; ?>
                <form action="api.php" method="POST" class="inline">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="text-slate-500 hover:text-red-500 dark:text-zinc-400 dark:hover:text-red-400 transition-colors text-xs font-medium">Sign Out</button>
                </form>
            </div>
        </div>
    </nav>

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

        <!-- SECTION 1: PENDING DEPOSIT VERIFICATIONS -->
        <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl transition-colors">
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
                                <p class="text-xs text-slate-600 dark:text-zinc-400"><?= htmlspecialchars($appt['service_name']) ?> (<?= ucfirst($appt['body_region'] ?? 'body') ?>)</p>
                                <p class="text-xs text-slate-600 dark:text-zinc-400 mt-1">Ref Code: <strong class="text-slate-900 dark:text-zinc-200 font-mono"><?= htmlspecialchars($appt['payment_reference']) ?></strong></p>
                                <p class="text-xs text-slate-500 dark:text-zinc-500 mt-0.5">Date: <?= $appt['appointment_date'] ?> @ <?= $appt['appointment_time'] ?></p>
                            </div>

                            <div class="space-y-3">
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
        <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl transition-colors">
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
                <!-- MOBILE CARDS VIEW (lg:hidden) -->
                <div class="space-y-4 lg:hidden">
                    <?php foreach ($confirmedAppts as $appt): ?>
                        <div class="bg-slate-50 dark:bg-zinc-950 p-4 rounded-xl border border-slate-200 dark:border-zinc-800 space-y-3 shadow-sm transition-colors">
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <span class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400">#<?= $appt['id'] ?></span>
                                    <strong class="text-slate-900 dark:text-zinc-200 block text-sm"><?= htmlspecialchars($appt['client_name']) ?></strong>
                                    <span class="text-xs text-slate-500 dark:text-zinc-500"><?= htmlspecialchars($appt['client_phone']) ?></span>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-xs bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded font-bold uppercase block"><?= htmlspecialchars($appt['booking_status']) ?></span>
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase mt-1 block"><?= $appt['body_region'] ?? 'Forearm' ?></span>
                                </div>
                            </div>

                            <div class="text-xs text-slate-600 dark:text-zinc-400 space-y-0.5">
                                <p class="font-medium text-slate-800 dark:text-zinc-200"><?= htmlspecialchars($appt['service_name']) ?></p>
                                <p class="flex items-center text-slate-600 dark:text-zinc-400">
                                    <svg class="w-3.5 h-3.5 mr-1 text-slate-400 dark:text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span><?= $appt['appointment_date'] ?> at <?= $appt['appointment_time'] ?></span>
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-zinc-500">Medical Allergies: <strong class="text-slate-700 dark:text-zinc-300"><?= htmlspecialchars($appt['skin_allergies'] ?? 'None') ?></strong></p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-200 dark:border-zinc-800">
                                <?php if (!empty($appt['mockup_image_path'])): ?>
                                    <button type="button" onclick="openMockupModal('../<?= htmlspecialchars($appt['mockup_image_path']) ?>')" class="px-2.5 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-lg border border-amber-500/30 text-xs font-semibold flex items-center space-x-1.5 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>View 2D Mockup</span>
                                    </button>
                                <?php endif; ?>

                                <?php if (!empty($appt['pdf_archive_path'])): ?>
                                    <a href="../<?= htmlspecialchars($appt['pdf_archive_path']) ?>" target="_blank" class="px-2.5 py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-lg border border-emerald-500/30 text-xs font-semibold flex items-center space-x-1.5 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>View Waiver</span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <button type="button" onclick="openSessionModal(<?= $appt['id'] ?>, '<?= htmlspecialchars($appt['client_name']) ?>', <?= $appt['base_price'] ?>)" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg text-xs shadow-sm transition-colors mt-2">
                                Log Session & Deduct Sterile FIFO
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- DESKTOP TABLE VIEW (hidden lg:block) -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="uppercase bg-slate-100 dark:bg-zinc-950/70 text-slate-600 dark:text-zinc-400 border-b border-slate-200 dark:border-zinc-800">
                            <tr>
                                <th class="py-3 px-3">Ref #</th>
                                <th class="py-3 px-3">Client</th>
                                <th class="py-3 px-3">Service & Region</th>
                                <th class="py-3 px-3">Schedule</th>
                                <th class="py-3 px-3">Placement Mockup</th>
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
                                        <span class="text-slate-800 dark:text-zinc-300 font-semibold"><?= htmlspecialchars($appt['service_name']) ?></span>
                                        <span class="text-amber-600 dark:text-amber-400 block uppercase font-bold text-[10px]"><?= $appt['body_region'] ?? 'Forearm' ?></span>
                                    </td>
                                    <td class="py-3.5 px-3 text-slate-700 dark:text-zinc-300 font-medium"><?= $appt['appointment_date'] ?> @ <?= $appt['appointment_time'] ?></td>
                                    <td class="py-3.5 px-3">
                                        <?php if (!empty($appt['mockup_image_path'])): ?>
                                            <button type="button" onclick="openMockupModal('../<?= htmlspecialchars($appt['mockup_image_path']) ?>')" class="text-xs text-amber-600 dark:text-amber-400 hover:underline flex items-center space-x-1.5 font-semibold">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <span>View 2D Mockup</span>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-slate-400 dark:text-zinc-600">No Mockup</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <?php if (!empty($appt['pdf_archive_path'])): ?>
                                            <a href="../<?= htmlspecialchars($appt['pdf_archive_path']) ?>" target="_blank" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline flex items-center space-x-1.5 font-semibold">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span>Signed PDF Waiver</span>
                                            </a>
                                            <?php if (!empty($appt['signature_image_path'])): ?>
                                                <a href="../<?= htmlspecialchars($appt['signature_image_path']) ?>" target="_blank" class="text-[10px] text-slate-500 dark:text-zinc-400 hover:underline flex items-center space-x-1 mt-0.5">
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    <span>Signature Pad Stamp</span>
                                                </a>
                                            <?php endif; ?>
                                            <span class="text-[10px] text-slate-500 dark:text-zinc-500 block">Allergies: <?= htmlspecialchars($appt['skin_allergies'] ?? 'None') ?></span>
                                        <?php else: ?>
                                            <span class="text-red-600 dark:text-red-400 font-medium">Missing Waiver</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-3 text-right">
                                        <button type="button" onclick="openSessionModal(<?= $appt['id'] ?>, '<?= htmlspecialchars($appt['client_name']) ?>', <?= $appt['base_price'] ?>)" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg text-xs shadow-sm transition-colors">
                                            Log Session & Deduct FIFO
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
                                <th class="py-2.5 px-3">Service</th>
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

    <!-- MODAL 3: SESSION EXECUTION & FIFO SUPPLY DEDUCTION -->
    <div id="sessionModal" class="hidden fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-700 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl transition-colors">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400">Log Completed Tattoo Session</h3>
                <button type="button" onclick="closeSessionModal()" class="text-slate-400 hover:text-slate-800 dark:hover:text-white">&times;</button>
            </div>
            <form action="api.php" method="POST" class="space-y-3.5 text-xs text-slate-700 dark:text-zinc-300">
                <input type="hidden" name="action" value="complete_session_fifo">
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

                <hr class="border-slate-200 dark:border-zinc-800">
                <h4 class="font-bold text-amber-600 dark:text-amber-400">Automated FIFO Sterile Supply Deduction:</h4>

                <div>
                    <label class="block text-slate-600 dark:text-zinc-400 mb-1 font-semibold">Select Sterile Needle / Pigment / Barrier:</label>
                    <select name="supply_id" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200">
                        <?php foreach ($supplies as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['supply_name']) ?> (Stock: <?= $sup['total_stock'] ?> <?= $sup['unit'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-600 dark:text-zinc-400 mb-1 font-semibold">Quantity Consumed:</label>
                    <input type="number" name="qty_used" value="2" min="1" max="100" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2 text-slate-900 dark:text-zinc-200">
                    <span class="text-[10px] text-slate-500 dark:text-zinc-500 mt-0.5 block">Deducts automatically from the oldest active non-expired batch.</span>
                </div>

                <div>
                    <label class="block text-slate-600 dark:text-zinc-400 mb-1 font-semibold">Artist Notes & Skin Tolerance:</label>
                    <textarea name="session_notes" rows="2" placeholder="e.g. Clean line work, client handled session well, no adverse reaction." class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2 text-slate-900 dark:text-zinc-200"></textarea>
                </div>

                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg uppercase tracking-wider text-xs shadow-md">
                    Commit Session & Execute FIFO Deduction
                </button>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT MODAL & THEME HANDLERS -->
    <script>
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
                label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
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
