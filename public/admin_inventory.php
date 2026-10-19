<?php
// public/admin_inventory.php - FIFO Sterile Inventory & Shipment Intake
require_once __DIR__ . '/../config/db.php';
enforceRole(['admin']);

$currentPage = 'inventory';

// 1. Clinical Safety Alerts
$lowStockAlerts = $pdo->query("
    SELECT id, supply_name, category, total_stock, reorder_threshold 
    FROM sterile_supplies 
    WHERE total_stock <= reorder_threshold
")->fetchAll();

$expiryAlerts = $pdo->query("
    SELECT s.supply_name, b.batch_number, b.lot_number, b.qty_remaining, b.expiration_date,
           DATEDIFF(b.expiration_date, CURDATE()) AS days_left
    FROM supply_batches_fifo b
    JOIN sterile_supplies s ON b.supply_id = s.id
    WHERE b.qty_remaining > 0 AND b.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY b.expiration_date ASC
")->fetchAll();

$totalAlertsCount = count($lowStockAlerts) + count($expiryAlerts);

// 2. Data Queries
$supplies = $pdo->query("SELECT * FROM sterile_supplies ORDER BY category ASC, supply_name ASC")->fetchAll();
$batches = $pdo->query("
    SELECT b.*, s.supply_name, s.category, s.unit 
    FROM supply_batches_fifo b 
    JOIN sterile_supplies s ON b.supply_id = s.id 
    ORDER BY b.expiration_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIFO Sterile Inventory & Intake — Studio Administration</title>
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">FIFO Sterile Inventory & Intake</h1>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">First-In First-Out sterile batch lot tracking, safety expiration monitors, and supply intake.</p>
                </div>
                <div>
                    <a href="export.php?type=inventory_fifo&format=pdf" target="_blank" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition-colors shadow-sm inline-flex items-center space-x-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Download Inventory PDF</span>
                    </a>
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

            <!-- CLINICAL SAFETY ALERTS SECTION -->
            <?php if (!empty($lowStockAlerts) || !empty($expiryAlerts)): ?>
                <div class="space-y-4">
                    <?php if (!empty($lowStockAlerts)): ?>
                        <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-500/50 rounded-2xl p-4 sm:p-5 shadow-sm">
                            <div class="flex items-center space-x-3 mb-3">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <h3 class="text-sm sm:text-base font-bold text-amber-800 dark:text-amber-300">Sterile Inventory Low-Stock Reorder Warning</h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <?php foreach ($lowStockAlerts as $item): ?>
                                    <div class="bg-white dark:bg-zinc-900/80 p-3 rounded-xl border border-amber-200 dark:border-zinc-800 text-xs flex justify-between items-center shadow-sm">
                                        <div>
                                            <strong class="text-slate-900 dark:text-zinc-200 block"><?= htmlspecialchars($item['supply_name']) ?></strong>
                                            <span class="text-slate-500 dark:text-zinc-400"><?= $item['category'] ?></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-red-600 dark:text-red-400 font-bold"><?= $item['total_stock'] ?> remaining</span>
                                            <span class="text-slate-400 dark:text-zinc-500 block text-[10px]">Threshold: <?= $item['reorder_threshold'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($expiryAlerts)): ?>
                        <div class="bg-red-50 dark:bg-red-950/40 border border-red-300 dark:border-red-500/50 rounded-2xl p-4 sm:p-5 shadow-sm">
                            <div class="flex items-center space-x-3 mb-3">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <h3 class="text-sm sm:text-base font-bold text-red-800 dark:text-red-300">Expiring / Expired Sterile Batch Lots (FIFO Alert)</h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <?php foreach ($expiryAlerts as $b): ?>
                                    <div class="bg-white dark:bg-zinc-900/80 p-3 rounded-xl border border-red-200 dark:border-zinc-800 text-xs flex justify-between items-center shadow-sm">
                                        <div>
                                            <strong class="text-slate-900 dark:text-zinc-200 block"><?= htmlspecialchars($b['supply_name']) ?></strong>
                                            <span class="text-slate-500 dark:text-zinc-400">Lot: <?= $b['lot_number'] ?> (Batch: <?= $b['batch_number'] ?>)</span>
                                        </div>
                                        <div class="text-right">
                                            <?php if ($b['days_left'] < 0): ?>
                                                <span class="text-red-600 dark:text-red-500 font-bold block">EXPIRED (<?= abs($b['days_left']) ?>d ago)</span>
                                            <?php else: ?>
                                                <span class="text-amber-600 dark:text-amber-400 font-bold block"><?= $b['days_left'] ?> days left</span>
                                            <?php endif; ?>
                                            <span class="text-slate-400 dark:text-zinc-400 text-[10px]"><?= $b['qty_remaining'] ?> units in lot</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- FIFO STERILE SUPPLY BATCH REGISTRY & INTAKE -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- FIFO BATCH REGISTRY TABLE (8 COLS) -->
                <div class="lg:col-span-8 bg-white dark:bg-zinc-900/70 p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold text-amber-600 dark:text-amber-400">Sterile Supply Batch & Lot Registry (FIFO)</h2>
                        <span class="text-xs text-slate-500 dark:text-zinc-400"><?= count($batches) ?> Active & Past Lots</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="uppercase bg-slate-50 dark:bg-zinc-950/70 text-slate-600 dark:text-zinc-400 border-b border-slate-200 dark:border-zinc-800 font-medium">
                                <tr>
                                    <th class="py-3 px-3">Item / SKU</th>
                                    <th class="py-3 px-3">Batch & Lot</th>
                                    <th class="py-3 px-3">Recv Qty</th>
                                    <th class="py-3 px-3">Remaining</th>
                                    <th class="py-3 px-3">Expiry Date</th>
                                    <th class="py-3 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                                <?php foreach ($batches as $b): ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/30 transition-colors">
                                        <td class="py-3 px-3">
                                            <strong class="text-slate-900 dark:text-zinc-200 block"><?= htmlspecialchars($b['supply_name']) ?></strong>
                                            <span class="text-slate-500 dark:text-zinc-500"><?= $b['category'] ?></span>
                                        </td>
                                        <td class="py-3 px-3 font-mono">
                                            <span class="text-amber-600 dark:text-amber-400 font-medium"><?= $b['batch_number'] ?></span>
                                            <span class="text-slate-400 dark:text-zinc-500 block text-[10px]">Lot: <?= $b['lot_number'] ?></span>
                                        </td>
                                        <td class="py-3 px-3 text-slate-600 dark:text-zinc-400"><?= $b['qty_received'] ?> <?= $b['unit'] ?></td>
                                        <td class="py-3 px-3 font-bold <?= $b['qty_remaining'] == 0 ? 'text-slate-400 dark:text-zinc-600' : 'text-emerald-600 dark:text-emerald-400' ?>">
                                            <?= $b['qty_remaining'] ?> <?= $b['unit'] ?>
                                        </td>
                                        <td class="py-3 px-3 text-slate-700 dark:text-zinc-300">
                                            <?= $b['expiration_date'] ?>
                                            <?php if (strtotime($b['expiration_date']) < time()): ?>
                                                <span class="text-[10px] text-red-600 dark:text-red-500 block font-bold">EXPIRED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3">
                                            <?php if ($b['batch_status'] === 'active'): ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">Active</span>
                                            <?php elseif ($b['batch_status'] === 'depleted'): ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-500 border border-slate-200 dark:border-zinc-700">Depleted</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/20">Expired</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- LOG NEW INCOMING BATCH FORM (4 COLS) -->
                <div class="lg:col-span-4 bg-white dark:bg-zinc-900/70 p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <h3 class="text-base font-bold text-amber-600 dark:text-amber-400 mb-4">Intake New Sterile Shipment</h3>
                    <form action="api.php" method="POST" class="space-y-3.5 text-xs">
                        <input type="hidden" name="action" value="add_supply_batch">

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Select Sterile Supply Item:</label>
                            <select name="supply_id" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                                <?php foreach ($supplies as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['supply_name']) ?> (<?= $s['category'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Batch Number:</label>
                                <input type="text" name="batch_number" required placeholder="e.g. BATCH-NDL-03" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Lot Number:</label>
                                <input type="text" name="lot_number" required placeholder="e.g. LOT-2026C" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Received Quantity Units:</label>
                            <input type="number" name="qty" min="1" value="25" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Received Date:</label>
                                <input type="date" name="received_date" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 dark:text-zinc-300 mb-1">Expiration Date:</label>
                                <input type="date" name="expiration_date" min="<?= date('Y-m-d') ?>" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-lg p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 bg-amber-500 hover:bg-amber-600 dark:hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs uppercase tracking-wider transition-all mt-2 shadow-sm">
                            Add to FIFO Registry
                        </button>
                    </form>
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
