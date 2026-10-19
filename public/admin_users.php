<?php
// public/admin_users.php - Users & Role-Based Access Directory
require_once __DIR__ . '/../config/db.php';
enforceRole(['admin']);

$currentPage = 'users';

$users = $pdo->query("SELECT id, role, full_name, email, phone, created_at FROM users ORDER BY id ASC")->fetchAll();

$totalUsers = count($users);
$adminCount = 0;
$artistCount = 0;
$clientCount = 0;

foreach ($users as $u) {
    if ($u['role'] === 'admin') $adminCount++;
    elseif ($u['role'] === 'artist') $artistCount++;
    else $clientCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users & RBAC Directory — Studio Administration</title>
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Users & Role Directory</h1>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Audit verified staff credentials, registered client accounts, and access permissions.</p>
                </div>
                <div>
                    <span class="text-xs bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 px-3 py-1.5 rounded-full border border-slate-200 dark:border-zinc-700 font-semibold inline-flex items-center space-x-1.5">
                        <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span><?= $totalUsers ?> Registered Users</span>
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

            <!-- ROLE COUNTS SUMMARY ROW -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
                <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <span class="text-[11px] sm:text-xs uppercase font-semibold text-slate-500 dark:text-zinc-400 block mb-1">Total Accounts</span>
                    <span class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white"><?= $totalUsers ?></span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 dark:text-zinc-500 block mt-1">Across all user classes</span>
                </div>
                <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <span class="text-[11px] sm:text-xs uppercase font-semibold text-red-600 dark:text-red-400 block mb-1">Chief Administrators</span>
                    <span class="text-2xl sm:text-3xl font-bold text-red-600 dark:text-red-400"><?= $adminCount ?></span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 dark:text-zinc-500 block mt-1">Full studio operations rights</span>
                </div>
                <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <span class="text-[11px] sm:text-xs uppercase font-semibold text-amber-600 dark:text-amber-400 block mb-1">Tattoo Artists</span>
                    <span class="text-2xl sm:text-3xl font-bold text-amber-600 dark:text-amber-400"><?= $artistCount ?></span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 dark:text-zinc-500 block mt-1">Session & deposit verifiers</span>
                </div>
                <div class="bg-white dark:bg-zinc-900/70 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                    <span class="text-[11px] sm:text-xs uppercase font-semibold text-emerald-600 dark:text-emerald-400 block mb-1">Registered Clients</span>
                    <span class="text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400"><?= $clientCount ?></span>
                    <span class="text-[10px] sm:text-[11px] text-slate-400 dark:text-zinc-500 block mt-1">Visualizer & booking access</span>
                </div>
            </div>

            <!-- USERS DIRECTORY TABLE -->
            <div class="bg-white dark:bg-zinc-900/70 p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">User Accounts & Roles Registry</h2>
                    <span class="text-xs text-slate-500 dark:text-zinc-400">BCrypt Password Hashing • Session Protected</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="uppercase bg-slate-50 dark:bg-zinc-950/70 text-slate-600 dark:text-zinc-400 border-b border-slate-200 dark:border-zinc-800 font-medium">
                            <tr>
                                <th class="py-3 px-3">ID #</th>
                                <th class="py-3 px-3">Role</th>
                                <th class="py-3 px-3">Full Legal Name</th>
                                <th class="py-3 px-3">Email Address</th>
                                <th class="py-3 px-3">Phone Number</th>
                                <th class="py-3 px-3 text-right">Registered On</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                            <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="py-3 px-3 font-mono font-bold text-slate-400 dark:text-zinc-500">#<?= $u['id'] ?></td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase 
                                            <?= $u['role'] === 'admin' ? 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/20' : ($u['role'] === 'artist' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20' : 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 border border-slate-200 dark:border-zinc-700') ?>">
                                            <?= $u['role'] ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-bold text-slate-900 dark:text-zinc-200"><?= htmlspecialchars($u['full_name']) ?></td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-zinc-300 font-mono"><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-zinc-400"><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></td>
                                    <td class="py-3 px-3 text-right text-slate-500 dark:text-zinc-400">
                                        <?= !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : 'Seed Account' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RBAC POLICY MATRIX REFERENCE -->
            <div class="bg-white dark:bg-zinc-900/70 p-6 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm transition-colors space-y-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Role-Based Access Control (RBAC) Permission Matrix</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 space-y-1">
                        <strong class="text-red-600 dark:text-red-400 font-bold block">1. Administrator (Chief)</strong>
                        <p class="text-slate-500 dark:text-zinc-400 text-[11px]">Pricing configuration, sterile FIFO batch intake, stock thresholds, full reports export, and user oversight.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 space-y-1">
                        <strong class="text-amber-600 dark:text-amber-400 font-bold block">2. Tattoo Artist</strong>
                        <p class="text-slate-500 dark:text-zinc-400 text-[11px]">Inspect deposit payment screenshots, approve/reject bookings, view 2D visualizer mockups, review signed waivers, and record session logs.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 space-y-1">
                        <strong class="text-emerald-600 dark:text-emerald-400 font-bold block">3. Registered Client</strong>
                        <p class="text-slate-500 dark:text-zinc-400 text-[11px]">Upload custom tattoo artwork, manipulate 2D anatomical placement canvas, request appointment slots, submit down payments, and sign digital waivers.</p>
                    </div>
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
