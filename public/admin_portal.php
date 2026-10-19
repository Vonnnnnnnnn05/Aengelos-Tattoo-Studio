<?php
// public/admin_portal.php - Administrator Control Hub & Studio Overview
require_once __DIR__ . '/../config/db.php';
enforceRole(['admin']);

$currentPage = 'overview';

// 1. Summary Counts & KPIs
$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$verifiedDeposits = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE deposit_status = 'verified'")->fetchColumn();
$completedSessions = (int)$pdo->query("SELECT COUNT(*) FROM session_logs")->fetchColumn();
$totalSuppliesInStock = (int)$pdo->query("SELECT SUM(total_stock) FROM sterile_supplies")->fetchColumn() ?: 0;

// 2. Clinical Safety Alert Counters
$lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM sterile_supplies WHERE total_stock <= reorder_threshold")->fetchColumn();
$expiryCount = (int)$pdo->query("SELECT COUNT(*) FROM supply_batches_fifo WHERE qty_remaining > 0 AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$totalAlertsCount = $lowStockCount + $expiryCount;

// 3. Recent Studio Bookings & Consultation Activity
$recentBookings = $pdo->query("
    SELECT a.*, u.full_name AS client_name, art.full_name AS artist_name, s.service_name 
    FROM appointments a 
    JOIN users u ON a.client_id = u.id 
    JOIN users art ON a.artist_id = art.id 
    JOIN service_pricing_matrix s ON a.service_id = s.id 
    ORDER BY a.id DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Overview — Studio Administration</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-zinc-950 dark:text-zinc-100 min-h-screen flex flex-col justify-between transition-colors duration-200">
    <div>
        <!-- REUSABLE TOP NAVBAR -->
        <?php require_once __DIR__ . '/admin_nav.php'; ?>

        <!-- MAIN PAGE CONTENT -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 sm:space-y-8">
            <!-- PAGE TITLE & BREADCRUMB -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Studio Overview & Activity</h1>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">High-level KPIs, reservation queues, and clinical safety status.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/80 px-3 py-1 rounded-full font-semibold flex items-center space-x-1.5 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Clinical System Operational</span>
                    </span>
                </div>
            </div>

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

            <!-- ACTIVE SAFETY ALERTS BANNER -->
            <?php if ($totalAlertsCount > 0): ?>
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-500/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <strong class="text-sm font-bold text-amber-900 dark:text-amber-200 block">Attention Required: <?= $totalAlertsCount ?> Clinical Inventory Safety Notice(s)</strong>
                            <span class="text-xs text-amber-700 dark:text-amber-400"><?= $lowStockCount ?> item(s) below reorder threshold • <?= $expiryCount ?> batch lot(s) nearing or past expiration.</span>
                        </div>
                    </div>
                    <a href="admin_inventory.php" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition-colors shadow-sm flex items-center justify-center space-x-1 flex-shrink-0">
                        <span>Review Inventory</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            <?php endif; ?>

            <!-- KPI METRICS ROW -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
                <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <span class="text-[11px] sm:text-xs uppercase font-semibold text-slate-500 dark:text-zinc-400 block mb-1">Total Reservations</span>
                    <span class="text-2xl sm:text-3xl font-bold text-amber-600 dark:text-amber-400"><?= $totalBookings ?></span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 dark:text-zinc-500 block mt-1">All-time booking requests</span>
                </div>
                <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <span class="text-[11px] sm:text-xs uppercase font-semibold text-slate-500 dark:text-zinc-400 block mb-1">Verified Deposits</span>
                    <span class="text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400"><?= $verifiedDeposits ?></span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 dark:text-zinc-500 block mt-1">Audited GCash / bank receipts</span>
                </div>
                <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <span class="text-[11px] sm:text-xs uppercase font-semibold text-slate-500 dark:text-zinc-400 block mb-1">Completed Sessions</span>
                    <span class="text-2xl sm:text-3xl font-bold text-blue-600 dark:text-blue-400"><?= $completedSessions ?></span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 dark:text-zinc-500 block mt-1">Logged with supply deductions</span>
                </div>
                <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <span class="text-[11px] sm:text-xs uppercase font-semibold text-slate-500 dark:text-zinc-400 block mb-1">Total Sterile Units</span>
                    <span class="text-2xl sm:text-3xl font-bold text-purple-600 dark:text-purple-400"><?= $totalSuppliesInStock ?></span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 dark:text-zinc-500 block mt-1">Needles, pigments, barriers</span>
                </div>
            </div>

            <!-- QUICK MODULE SHORTCUTS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <a href="admin_inventory.php" class="bg-white dark:bg-zinc-900/70 hover:bg-slate-50 dark:hover:bg-zinc-800/50 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-all group flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 dark:text-zinc-600 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <strong class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white block">FIFO Inventory</strong>
                        <span class="text-[11px] text-slate-500 dark:text-zinc-400">Track lots & intake new supply</span>
                    </div>
                </a>

                <a href="admin_reports.php" class="bg-white dark:bg-zinc-900/70 hover:bg-slate-50 dark:hover:bg-zinc-800/50 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-all group flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 dark:text-zinc-600 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <strong class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white block">Audit Reports</strong>
                        <span class="text-[11px] text-slate-500 dark:text-zinc-400">Export 5 categories to PDF/CSV</span>
                    </div>
                </a>

                <a href="admin_pricing.php" class="bg-white dark:bg-zinc-900/70 hover:bg-slate-50 dark:hover:bg-zinc-800/50 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-all group flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 dark:text-zinc-600 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <strong class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white block">Pricing Matrix</strong>
                        <span class="text-[11px] text-slate-500 dark:text-zinc-400">Configure base fees & deposits</span>
                    </div>
                </a>

                <a href="admin_users.php" class="bg-white dark:bg-zinc-900/70 hover:bg-slate-50 dark:hover:bg-zinc-800/50 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-all group flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 dark:text-zinc-600 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <strong class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white block">Users & RBAC</strong>
                        <span class="text-[11px] text-slate-500 dark:text-zinc-400">Manage client & staff roles</span>
                    </div>
                </a>
            </div>

            <!-- RECENT RESERVATIONS & BOOKING ACTIVITY -->
            <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Recent Studio Reservations</h2>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Latest client appointment requests and deposit verification states.</p>
                    </div>
                    <a href="admin_reports.php" class="text-xs text-amber-600 dark:text-amber-400 font-semibold hover:underline inline-flex items-center space-x-1">
                        <span>Export Full Report</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <?php if (empty($recentBookings)): ?>
                    <div class="py-8 text-center text-xs text-slate-500 dark:text-zinc-500">No appointments recorded yet.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="uppercase bg-slate-50 dark:bg-zinc-950/70 text-slate-600 dark:text-zinc-400 border-b border-slate-200 dark:border-zinc-800 font-medium">
                                <tr>
                                    <th class="py-3 px-3">Ref #</th>
                                    <th class="py-3 px-3">Client</th>
                                    <th class="py-3 px-3">Artist</th>
                                    <th class="py-3 px-3">Service & Region</th>
                                    <th class="py-3 px-3">Date & Time</th>
                                    <th class="py-3 px-3">Deposit</th>
                                    <th class="py-3 px-3">Deposit Status</th>
                                    <th class="py-3 px-3 text-right">Booking Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                                <?php foreach ($recentBookings as $b): ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/30 transition-colors">
                                        <td class="py-3 px-3 font-mono font-bold text-amber-600 dark:text-amber-400">#<?= $b['id'] ?></td>
                                        <td class="py-3 px-3 font-medium text-slate-900 dark:text-zinc-200"><?= htmlspecialchars($b['client_name']) ?></td>
                                        <td class="py-3 px-3 text-slate-600 dark:text-zinc-400"><?= htmlspecialchars($b['artist_name']) ?></td>
                                        <td class="py-3 px-3">
                                            <span class="text-slate-800 dark:text-zinc-300 font-medium"><?= htmlspecialchars($b['service_name']) ?></span>
                                            <span class="text-amber-600 dark:text-amber-400 uppercase text-[10px] block font-bold"><?= $b['body_region'] ?? 'Body' ?></span>
                                        </td>
                                        <td class="py-3 px-3 text-slate-600 dark:text-zinc-400"><?= $b['appointment_date'] ?> @ <?= $b['appointment_time'] ?></td>
                                        <td class="py-3 px-3 font-semibold text-slate-900 dark:text-zinc-200">₱<?= number_format($b['deposit_amount'], 2) ?></td>
                                        <td class="py-3 px-3">
                                            <?php if ($b['deposit_status'] === 'verified'): ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">Verified</span>
                                            <?php elseif ($b['deposit_status'] === 'rejected'): ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/20">Rejected</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3 text-right">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase 
                                                <?= $b['booking_status'] === 'confirmed' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' : ($b['booking_status'] === 'completed' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20' : ($b['booking_status'] === 'cancelled' ? 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/20' : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 border border-slate-200 dark:border-zinc-700')) ?>">
                                                <?= $b['booking_status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- FOOTER -->
    <footer class="py-6 px-4 sm:px-8 border-t border-slate-200 dark:border-zinc-900 bg-white dark:bg-transparent text-center text-xs text-slate-500 dark:text-zinc-600 transition-colors mt-8">
        &copy; <?= date('Y') ?> Aengelos Tattoo Studio. ISO/IEC 25010 Evaluated Architecture. All Rights Reserved.
    </footer>
</body>
</html>
