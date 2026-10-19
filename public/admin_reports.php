<?php
// public/admin_reports.php - Studio Audit, Accounting & Compliance Reports
require_once __DIR__ . '/../config/db.php';
enforceRole(['admin']);

$currentPage = 'reports';

// Report Counts
$totalAppts = (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$totalSessions = (int)$pdo->query("SELECT COUNT(*) FROM session_logs")->fetchColumn();
$totalDeposits = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE deposit_status = 'verified'")->fetchColumn();
$totalBatches = (int)$pdo->query("SELECT COUNT(*) FROM supply_batches_fifo")->fetchColumn();
$totalWaivers = (int)$pdo->query("SELECT COUNT(*) FROM digital_waivers WHERE pdf_archive_path IS NOT NULL AND pdf_archive_path != ''")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit & Accounting Reports — Studio Administration</title>
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
            <!-- PAGE TITLE -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Studio Audit & Compliance Reports</h1>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">One-click immutable PDF & raw CSV generation across all 5 operational categories.</p>
                </div>
                <div>
                    <span class="text-xs bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 px-3 py-1.5 rounded-full border border-slate-200 dark:border-zinc-700 font-semibold inline-flex items-center space-x-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>ISO/IEC 25010 Evaluated Architecture</span>
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

            <!-- 5 REPORT CATEGORIES GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- 1. APPOINTMENTS SCHEDULE -->
                <div class="bg-white dark:bg-zinc-900/70 p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between space-y-4 transition-colors">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md">Category 01</span>
                            <span class="text-xs text-slate-400 dark:text-zinc-500 font-medium"><?= $totalAppts ?> Records</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Appointments Schedule</h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Complete historical and pending booking requests, client details, placement areas, and booking statuses.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 dark:border-zinc-800/80 flex items-center space-x-2">
                        <a href="export.php?type=appointments&format=csv" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs rounded-xl border border-slate-300 dark:border-zinc-700 font-semibold text-center transition-colors">Export CSV</a>
                        <a href="export.php?type=appointments&format=pdf" target="_blank" class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-sm text-center transition-colors">Generate PDF</a>
                    </div>
                </div>

                <!-- 2. COMPLETED SESSIONS -->
                <div class="bg-white dark:bg-zinc-900/70 p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between space-y-4 transition-colors">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-md">Category 02</span>
                            <span class="text-xs text-slate-400 dark:text-zinc-500 font-medium"><?= $totalSessions ?> Logged</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Completed Sessions & Procedures</h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Actual procedure durations, artist post-session clinical notes, final balance fees, and completed dates.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 dark:border-zinc-800/80 flex items-center space-x-2">
                        <a href="export.php?type=sessions&format=csv" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs rounded-xl border border-slate-300 dark:border-zinc-700 font-semibold text-center transition-colors">Export CSV</a>
                        <a href="export.php?type=sessions&format=pdf" target="_blank" class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-sm text-center transition-colors">Generate PDF</a>
                    </div>
                </div>

                <!-- 3. VERIFIED DEPOSITS -->
                <div class="bg-white dark:bg-zinc-900/70 p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between space-y-4 transition-colors">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-md">Category 03</span>
                            <span class="text-xs text-slate-400 dark:text-zinc-500 font-medium"><?= $totalDeposits ?> Audited</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Verified Deposits & Payments</h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">GCash/bank reference numbers, transaction screenshot verification timestamps, and revenue logs.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 dark:border-zinc-800/80 flex items-center space-x-2">
                        <a href="export.php?type=payments&format=csv" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs rounded-xl border border-slate-300 dark:border-zinc-700 font-semibold text-center transition-colors">Export CSV</a>
                        <a href="export.php?type=payments&format=pdf" target="_blank" class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-sm text-center transition-colors">Generate PDF</a>
                    </div>
                </div>

                <!-- 4. FIFO INVENTORY -->
                <div class="bg-white dark:bg-zinc-900/70 p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between space-y-4 transition-colors">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded-md">Category 04</span>
                            <span class="text-xs text-slate-400 dark:text-zinc-500 font-medium"><?= $totalBatches ?> Lots</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">FIFO Inventory Registry</h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Sterile needles, inks, and barriers tracking with batch numbers, lot IDs, expiration dates, and remaining stock.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 dark:border-zinc-800/80 flex items-center space-x-2">
                        <a href="export.php?type=inventory_fifo&format=csv" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs rounded-xl border border-slate-300 dark:border-zinc-700 font-semibold text-center transition-colors">Export CSV</a>
                        <a href="export.php?type=inventory_fifo&format=pdf" target="_blank" class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-sm text-center transition-colors">Generate PDF</a>
                    </div>
                </div>

                <!-- 5. ARCHIVED WAIVERS -->
                <div class="bg-white dark:bg-zinc-900/70 p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between space-y-4 transition-colors">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-md">Category 05</span>
                            <span class="text-xs text-slate-400 dark:text-zinc-500 font-medium"><?= $totalWaivers ?> Signed</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Archived Digital Waivers</h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">Sanitary inspection audit trail containing medical disclosures, client touchscreen signatures, and PDF archive paths.</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 dark:border-zinc-800/80 flex items-center space-x-2">
                        <a href="export.php?type=waivers&format=csv" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs rounded-xl border border-slate-300 dark:border-zinc-700 font-semibold text-center transition-colors">Export CSV</a>
                        <a href="export.php?type=waivers&format=pdf" target="_blank" class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-sm text-center transition-colors">Generate PDF</a>
                    </div>
                </div>

                <!-- ARCHIVE COMPLIANCE NOTICE -->
                <div class="bg-slate-50 dark:bg-zinc-900/30 p-5 rounded-2xl border border-dashed border-slate-300 dark:border-zinc-800 flex flex-col justify-center space-y-2 text-xs">
                    <strong class="font-bold text-slate-800 dark:text-zinc-200 block">Health Inspection Guarantee</strong>
                    <p class="text-slate-500 dark:text-zinc-400">All PDF reports generated adhere to strict regulatory compliance standards for tattoo parlor and clinical sanitation inspections.</p>
                    <span class="text-[11px] font-mono text-amber-600 dark:text-amber-400 mt-2 block">FPDF Engine • ISO 25010 Evaluated</span>
                </div>
            </div>
        </main>
    </div>

    <!-- FOOTER -->
    <footer class="py-6 px-4 sm:px-8 border-t border-slate-200 dark:border-zinc-900 bg-white dark:bg-transparent text-center text-xs text-slate-500 dark:text-zinc-600 transition-colors mt-8">
        &copy; <?= date('Y') ?> Aengelos Tattoo Studio. ISO/IEC 25010 Evaluated Architecture. All Rights Reserved.
    </footer>
</body>
</html>
