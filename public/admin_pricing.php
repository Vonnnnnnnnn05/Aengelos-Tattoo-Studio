<?php
// public/admin_pricing.php - Service & Pricing Matrix Configuration
require_once __DIR__ . '/../config/db.php';
enforceRole(['admin']);

$currentPage = 'pricing';

$pricing = $pdo->query("SELECT * FROM service_pricing_matrix ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Pricing Matrix — Studio Administration</title>
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Service Pricing Matrix</h1>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Configure baseline session rates, mandatory client down payment deposits, and estimated durations.</p>
                </div>
                <div>
                    <span class="text-xs bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 px-3 py-1.5 rounded-full border border-slate-200 dark:border-zinc-700 font-semibold inline-flex items-center space-x-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?= count($pricing) ?> Studio Services Active</span>
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

            <!-- PRICING MATRIX CARDS GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <?php foreach ($pricing as $p): ?>
                    <form action="api.php" method="POST" class="bg-white dark:bg-zinc-900/70 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm space-y-4 transition-colors">
                        <input type="hidden" name="action" value="update_pricing">
                        <input type="hidden" name="service_id" value="<?= $p['id'] ?>">

                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 dark:border-zinc-800 pb-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md"><?= htmlspecialchars($p['service_category']) ?></span>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1"><?= htmlspecialchars($p['service_name']) ?></h3>
                            </div>
                            <span class="text-xs font-mono font-bold text-slate-400 dark:text-zinc-500">ID #<?= $p['id'] ?></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Base Price (₱):</label>
                                <input type="number" step="0.01" name="base_price" value="<?= $p['base_price'] ?>" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 font-mono font-bold focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Deposit (₱):</label>
                                <input type="number" step="0.01" name="deposit_required" value="<?= $p['deposit_required'] ?>" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 font-mono font-bold focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Duration (mins):</label>
                                <input type="number" name="est_duration_mins" value="<?= $p['est_duration_mins'] ?>" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 font-mono focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-[11px] text-slate-400 dark:text-zinc-500">
                                Down payment: <?= round(($p['deposit_required'] / max(1, $p['base_price'])) * 100) ?>% of baseline
                            </span>
                            <button type="submit" class="py-2 px-5 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition-colors shadow-sm">
                                Save Changes
                            </button>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>

            <!-- STUDIO DOWN PAYMENT POLICY EXPLAINER -->
            <div class="bg-amber-500/5 dark:bg-zinc-900/40 p-5 rounded-2xl border border-amber-500/20 text-xs space-y-2">
                <strong class="font-bold text-amber-700 dark:text-amber-400 block text-sm">Studio Down Payment Verification Policy</strong>
                <p class="text-slate-600 dark:text-zinc-400 leading-relaxed">
                    Client reservations require an audited GCash or bank down payment upload. Once uploaded, tattoo artists on duty inspect transaction screenshots in real time before confirming the studio chair. Changing rates here automatically updates client visualizer quotation totals.
                </p>
            </div>
        </main>
    </div>

    <!-- FOOTER -->
    <footer class="py-6 px-4 sm:px-8 border-t border-slate-200 dark:border-zinc-900 bg-white dark:bg-transparent text-center text-xs text-slate-500 dark:text-zinc-600 transition-colors mt-8">
        &copy; <?= date('Y') ?> Aengelos Tattoo Studio. ISO/IEC 25010 Evaluated Architecture. All Rights Reserved.
    </footer>
</body>
</html>
