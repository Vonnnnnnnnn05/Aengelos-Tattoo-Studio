<?php
// public/admin_nav.php - Reusable Admin Top Navbar Component
// Expected: $currentPage variable ('overview', 'inventory', 'reports', 'pricing', 'users')
$activePage = $currentPage ?? 'overview';

// Calculate alert count for badge if not already defined
if (!isset($totalAlertsCount)) {
    try {
        $lowCount = (int)$pdo->query("SELECT COUNT(*) FROM sterile_supplies WHERE total_stock <= reorder_threshold")->fetchColumn();
        $expCount = (int)$pdo->query("SELECT COUNT(*) FROM supply_batches_fifo WHERE qty_remaining > 0 AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
        $totalAlertsCount = $lowCount + $expCount;
    } catch (Exception $e) {
        $totalAlertsCount = 0;
    }
}
?>
<!-- TOP NAVBAR COMPONENT -->
<header class="bg-white/95 dark:bg-zinc-900/95 backdrop-blur border-b border-slate-200 dark:border-zinc-800 sticky top-0 z-50 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-3">
            <!-- BRAND LOGO -->
            <div class="flex items-center space-x-3 flex-shrink-0">
                <a href="admin_portal.php" class="flex items-center space-x-2.5 group">
                    <span class="w-3.5 h-3.5 rounded-full bg-amber-500 shadow-md shadow-amber-500/50 flex-shrink-0 group-hover:scale-110 transition-transform"></span>
                    <span class="text-sm sm:text-base font-bold tracking-wider text-amber-600 dark:text-amber-400">AENGELOS STUDIO</span>
                </a>
                <span class="hidden sm:inline-block text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-400 border border-red-200 dark:border-red-800/80 px-2 py-0.5 rounded-md">Admin</span>
            </div>

            <!-- DESKTOP NAVBAR TABS -->
            <nav class="hidden lg:flex items-center space-x-1 text-xs font-semibold">
                <!-- 1. OVERVIEW -->
                <a href="admin_portal.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-colors <?= $activePage === 'overview' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                    <svg class="w-4 h-4 <?= $activePage === 'overview' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Overview</span>
                </a>

                <!-- 2. FIFO INVENTORY -->
                <a href="admin_inventory.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-colors <?= $activePage === 'inventory' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                    <svg class="w-4 h-4 <?= $activePage === 'inventory' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span>FIFO Inventory</span>
                    <?php if ($totalAlertsCount > 0): ?>
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-500 text-slate-950"><?= $totalAlertsCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- 3. AUDIT REPORTS -->
                <a href="admin_reports.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-colors <?= $activePage === 'reports' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                    <svg class="w-4 h-4 <?= $activePage === 'reports' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Audit Reports</span>
                </a>

                <!-- 4. PRICING MATRIX -->
                <a href="admin_pricing.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-colors <?= $activePage === 'pricing' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                    <svg class="w-4 h-4 <?= $activePage === 'pricing' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Pricing Matrix</span>
                </a>

                <!-- 5. USERS & RBAC -->
                <a href="admin_users.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-colors <?= $activePage === 'users' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                    <svg class="w-4 h-4 <?= $activePage === 'users' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Users & RBAC</span>
                </a>
            </nav>

            <!-- RIGHT ACTIONS: ARTIST VIEW, THEME TOGGLE, USER SIGN OUT -->
            <div class="flex items-center space-x-2 sm:space-x-3 flex-shrink-0">
                <a href="artist_portal.php" class="hidden sm:inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-lg border border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 text-xs font-semibold transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span class="hidden md:inline">Artist View</span>
                </a>

                <!-- THEME TOGGLE BUTTON -->
                <button type="button" onclick="toggleTheme()" class="px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-slate-100 dark:bg-zinc-800 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors flex items-center space-x-1 shadow-sm">
                    <span id="themeIcon" class="flex items-center"></span>
                    <span id="themeLabel" class="hidden xl:inline">Dark Mode</span>
                </button>

                <!-- USER SIGN OUT -->
                <form action="api.php" method="POST" class="inline">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" title="Sign Out" class="p-2 sm:px-3 sm:py-1.5 rounded-lg text-slate-500 hover:text-red-600 dark:text-zinc-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors text-xs font-medium flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span class="hidden sm:inline">Sign Out</span>
                    </button>
                </form>

                <!-- MOBILE HAMBURGER BUTTON -->
                <button type="button" onclick="toggleMobileNav()" class="lg:hidden p-2 rounded-xl border border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        <!-- MOBILE COLLAPSIBLE MENU DROPDOWN -->
        <div id="mobileNavDropdown" class="hidden lg:hidden border-t border-slate-200 dark:border-zinc-800 py-3 space-y-1 text-xs font-semibold">
            <a href="admin_portal.php" class="block px-3 py-2 rounded-lg <?= $activePage === 'overview' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                Dashboard Overview
            </a>
            <a href="admin_inventory.php" class="flex justify-between items-center px-3 py-2 rounded-lg <?= $activePage === 'inventory' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                <span>FIFO Sterile Inventory</span>
                <?php if ($totalAlertsCount > 0): ?>
                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-500 text-slate-950"><?= $totalAlertsCount ?> alerts</span>
                <?php endif; ?>
            </a>
            <a href="admin_reports.php" class="block px-3 py-2 rounded-lg <?= $activePage === 'reports' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                Audit & Accounting Reports
            </a>
            <a href="admin_pricing.php" class="block px-3 py-2 rounded-lg <?= $activePage === 'pricing' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                Service Pricing Matrix
            </a>
            <a href="admin_users.php" class="block px-3 py-2 rounded-lg <?= $activePage === 'users' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                User Directory & RBAC
            </a>
            <a href="artist_portal.php" class="block px-3 py-2 rounded-lg text-amber-600 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-zinc-800">
                Switch to Artist Station →
            </a>
        </div>
    </div>
</header>

<script>
    function toggleMobileNav() {
        const menu = document.getElementById('mobileNavDropdown');
        if (menu) {
            menu.classList.toggle('hidden');
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
            label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        }
    }
    updateThemeButton(document.documentElement.classList.contains('dark'));
</script>
