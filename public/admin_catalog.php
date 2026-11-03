<?php
// public/admin_catalog.php - E-Commerce Tattoo Catalog & Flash Art Management
require_once __DIR__ . '/../config/db.php';
enforceRole(['admin']);

$currentPage = 'catalog';

// Fetch all catalog items with uploader name
$items = $pdo->query("
    SELECT c.*, u.full_name AS uploader_name, u.role AS uploader_role
    FROM tattoo_catalog c
    JOIN users u ON c.uploader_id = u.id
    ORDER BY c.id DESC
")->fetchAll();

$artists = $pdo->query("SELECT id, full_name FROM users WHERE role = 'artist'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Catalog & Flash Management — Studio Administration</title>
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
<body class="bg-slate-50 text-slate-800 dark:bg-zinc-950 dark:text-zinc-100 min-h-screen flex flex-col justify-between transition-colors duration-200">
    <div>
        <!-- TOP NAVBAR -->
        <?php require_once __DIR__ . '/admin_nav.php'; ?>

        <!-- MAIN CONTENT -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 sm:space-y-8">
            
            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">Shop Catalog & Flash Management</h1>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Upload, price, and manage tattoo flash pieces displayed on the public Shopee-style storefront.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="index.php" target="_blank" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs uppercase tracking-wider transition-colors inline-flex items-center space-x-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>View Live Storefront</span>
                    </a>
                </div>
            </div>

            <!-- SYSTEM NOTIFICATIONS -->
            <?php if (!empty($_GET['msg'])): ?>
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center space-x-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= htmlspecialchars($_GET['msg']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div class="p-4 rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-500/40 text-red-800 dark:text-red-300 text-xs sm:text-sm flex items-center space-x-3 shadow-sm">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span><?= htmlspecialchars($_GET['error']) ?></span>
                </div>
            <?php endif; ?>

            <!-- UPLOAD NEW DESIGN CARD -->
            <div class="bg-white dark:bg-zinc-900/80 p-5 sm:p-7 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Publish New Tattoo Design to Storefront</h2>
                    </div>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-bold bg-amber-500/10 px-2.5 py-1 rounded-lg">Shopee Storefront Live Sync</span>
                </div>

                <form action="api.php" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    <input type="hidden" name="action" value="upload_catalog_item">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Tattoo Title / Design Name:</label>
                            <input type="text" name="title" required placeholder="e.g. Japanese Ryu Dragon of the Eastern Storm" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-slate-900 dark:text-zinc-200 font-semibold focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Design Category:</label>
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
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Total Price (₱):</label>
                            <input type="number" step="0.01" name="price" id="newDesignPrice" oninput="autoCalcDeposit(this.value)" required placeholder="3500.00" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-amber-600 dark:text-amber-400 font-mono font-bold focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Required Deposit (₱):</label>
                            <input type="number" step="0.01" name="deposit_required" id="newDesignDeposit" required placeholder="1000.00" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-emerald-600 dark:text-emerald-400 font-mono font-bold focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Est. Duration (Mins):</label>
                            <input type="number" name="est_duration_mins" value="120" min="30" step="15" required class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Upload Artwork File:</label>
                            <input type="file" name="catalog_image" accept=".png,.jpg,.jpeg,.webp" required class="block w-full text-xs text-slate-600 dark:text-zinc-400 file:mr-2 file:py-2 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 dark:file:bg-zinc-800 file:text-amber-600 dark:file:text-amber-400 cursor-pointer">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Description & Stencil Notes:</label>
                        <textarea name="description" rows="2" placeholder="Detail the placement recommendation, color options, linework details..." class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" class="rounded accent-amber-500 w-4 h-4">
                            <span class="font-semibold text-slate-700 dark:text-zinc-300">Feature this design on Shopee storefront homepage header</span>
                        </label>

                        <button type="submit" class="py-2.5 px-6 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black rounded-xl text-xs uppercase tracking-wider shadow-md shadow-amber-500/20 transition-all">
                            Publish Design to Storefront
                        </button>
                    </div>
                </form>
            </div>

            <!-- EXISTING DESIGNS IN CATALOG TABLE -->
            <div class="bg-white dark:bg-zinc-900/80 p-5 sm:p-7 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Active Storefront Inventory</h2>
                        <span class="text-xs text-slate-500 dark:text-zinc-400"><?= count($items) ?> designs currently visible to clients</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="uppercase bg-slate-100 dark:bg-zinc-950 text-slate-600 dark:text-zinc-400 border-b border-slate-200 dark:border-zinc-800">
                            <tr>
                                <th class="py-3 px-3">Artwork</th>
                                <th class="py-3 px-3">Title & Category</th>
                                <th class="py-3 px-3">Uploader</th>
                                <th class="py-3 px-3">Price & Deposit</th>
                                <th class="py-3 px-3">Duration</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/40 transition-colors">
                                    <td class="py-3 px-3">
                                        <img src="../<?= htmlspecialchars($item['image_path']) ?>" alt="" class="w-12 h-12 object-cover rounded-xl border border-slate-200 dark:border-zinc-700 shadow-sm">
                                    </td>
                                    <td class="py-3 px-3">
                                        <strong class="text-slate-900 dark:text-white block font-bold text-sm"><?= htmlspecialchars($item['title']) ?></strong>
                                        <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold uppercase"><?= htmlspecialchars($item['category']) ?></span>
                                        <?php if ($item['is_featured']): ?>
                                            <span class="ml-1 text-[9px] bg-amber-500 text-slate-950 font-bold px-1.5 py-0.2 rounded">Featured</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 font-medium text-slate-700 dark:text-zinc-300">
                                        <?= htmlspecialchars($item['uploader_name']) ?>
                                        <span class="text-[9px] uppercase px-1 rounded bg-slate-100 dark:bg-zinc-800 text-slate-500 block"><?= $item['uploader_role'] ?></span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-mono font-bold text-amber-600 dark:text-amber-400 text-sm">₱<?= number_format($item['price'], 2) ?></div>
                                        <div class="text-[10px] text-slate-400">Dep: ₱<?= number_format($item['deposit_required'], 2) ?></div>
                                    </td>
                                    <td class="py-3 px-3 font-mono text-slate-600 dark:text-zinc-400">
                                        <?= $item['est_duration_mins'] ?> mins
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $item['status'] === 'available' ? 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/20' : 'bg-slate-200 dark:bg-zinc-800 text-slate-600' ?>">
                                            <?= htmlspecialchars($item['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <form action="api.php" method="POST" onsubmit="return confirm('Remove this tattoo design from storefront?')" class="inline">
                                            <input type="hidden" name="action" value="delete_catalog_item">
                                            <input type="hidden" name="catalog_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 font-semibold text-xs transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- FOOTER -->
    <footer class="py-6 px-4 sm:px-8 border-t border-slate-200 dark:border-zinc-900 bg-white dark:bg-transparent text-center text-xs text-slate-500 dark:text-zinc-600 transition-colors mt-8">
        &copy; <?= date('Y') ?> Aengelos Tattoo Studio. Shopee-Style Catalog Architecture.
    </footer>

    <script>
        function autoCalcDeposit(val) {
            const num = parseFloat(val);
            if (!isNaN(num) && num > 0) {
                document.getElementById('newDesignDeposit').value = (num * 0.30).toFixed(2);
            }
        }
    </script>
</body>
</html>
