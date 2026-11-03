<?php
// public/navbar.php - Unified Master Navigation Component
// Included across: index.php, avail.php, client_portal.php, artist_portal.php, register.php, and admin suite

if (!isset($pdo) && file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}

$navUser = $currentUser ?? (function_exists('currentUser') ? currentUser() : ($_SESSION['user'] ?? null));
$navCurrentFile = basename($_SERVER['PHP_SELF'] ?? '');

// Determine navigation context if not explicitly set
if (!isset($navContext)) {
    if (str_starts_with($navCurrentFile, 'admin_')) {
        $navContext = 'admin';
    } elseif ($navCurrentFile === 'artist_portal.php') {
        $navContext = 'artist';
    } elseif ($navCurrentFile === 'client_portal.php') {
        $navContext = 'client';
    } elseif ($navCurrentFile === 'avail.php') {
        $navContext = 'avail';
    } elseif ($navCurrentFile === 'register.php' || $navCurrentFile === 'signup.php') {
        $navContext = 'register';
    } else {
        $navContext = 'storefront';
    }
}

// Admin active tab detection
$navAdminPage = $currentPage ?? match ($navCurrentFile) {
    'admin_catalog.php' => 'catalog',
    'admin_reports.php' => 'reports',
    'admin_pricing.php' => 'pricing',
    'admin_users.php' => 'users',
    default => 'overview'
};

// Pending deposits count for badge
if (isset($pendingDepositsCount)) {
    $navPendingDeposits = (int)$pendingDepositsCount;
} elseif (isset($pendingDeposits)) {
    $navPendingDeposits = is_array($pendingDeposits) ? count($pendingDeposits) : (int)$pendingDeposits;
} elseif (isset($pdo)) {
    try {
        $navPendingDeposits = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE deposit_status = 'pending'")->fetchColumn();
    } catch (Exception $e) {
        $navPendingDeposits = 0;
    }
} else {
    $navPendingDeposits = 0;
}

// Client bookings count for badge
if (isset($bookings)) {
    $navBookingsCount = is_array($bookings) ? count($bookings) : (int)$bookings;
} elseif ($navUser && ($navUser['role'] ?? '') === 'client' && isset($pdo)) {
    try {
        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE client_id = ?");
        $stmtC->execute([$navUser['id']]);
        $navBookingsCount = (int)$stmtC->fetchColumn();
    } catch (Exception $e) {
        $navBookingsCount = 0;
    }
} else {
    $navBookingsCount = 0;
}

// Artist confirmed queue count for badge
if (isset($confirmedAppts)) {
    $navConfirmedCount = is_array($confirmedAppts) ? count($confirmedAppts) : (int)$confirmedAppts;
} elseif ($navUser && in_array($navUser['role'] ?? '', ['artist', 'admin']) && isset($pdo)) {
    try {
        $stmtQ = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE artist_id = ? AND booking_status = 'confirmed'");
        $stmtQ->execute([$navUser['id']]);
        $navConfirmedCount = (int)$stmtQ->fetchColumn();
    } catch (Exception $e) {
        $navConfirmedCount = 0;
    }
} else {
    $navConfirmedCount = 0;
}

$navSearchQuery = $searchQuery ?? trim($_GET['q'] ?? '');

// User portal URL and label
$navPortalUrl = 'client_portal.php';
$navRoleLabel = 'My Portal';
if ($navUser) {
    $navPortalUrl = match ($navUser['role'] ?? 'client') {
        'admin' => 'admin_portal.php',
        'artist' => 'artist_portal.php',
        default => 'client_portal.php'
    };
    $navRoleLabel = match ($navUser['role'] ?? 'client') {
        'admin' => 'Admin Hub',
        'artist' => 'Artist Station',
        default => 'My Portal'
    };
}

// Fetch authentic tattoo catalog proofs for fixed Gallery Sidebar
$navGalleryItems = [];
if (isset($pdo)) {
    try {
        $stmtG = $pdo->query("
            SELECT c.*, u.full_name AS artist_name 
            FROM tattoo_catalog c 
            JOIN users u ON c.uploader_id = u.id 
            WHERE c.status != 'archived' 
            ORDER BY c.is_featured DESC, c.id DESC
        ");
        $navGalleryItems = $stmtG->fetchAll();
    } catch (Exception $e) {
        $navGalleryItems = [];
    }
}
?>

<!-- UNIFIED MASTER HEADER / FIXED NAVBAR -->
<header class="fixed top-0 left-0 right-0 z-40 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-md border-b border-slate-200/90 dark:border-zinc-800/90 shadow-xs transition-colors w-full">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2 sm:py-2.5">
        
        <?php if ($navContext === 'client'): ?>
            <!-- ========================================================================= -->
            <!-- CLIENT CONTEXT: DESKTOP SINGLE HIGH-END FIXED BAR (MD+)                   -->
            <!-- ========================================================================= -->
            <div class="hidden md:flex items-center justify-between gap-4 w-full">
                
                <!-- BRAND & BADGE -->
                <div class="flex items-center space-x-3 flex-shrink-0">
                    <a href="index.php" class="flex items-center space-x-2.5 group">
                        <span class="w-3.5 h-3.5 rounded-full bg-amber-500 shadow-md shadow-amber-500/50 group-hover:scale-125 transition-transform flex-shrink-0"></span>
                        <div class="min-w-0">
                            <span class="font-black text-base tracking-wider text-amber-600 dark:text-amber-400 block leading-tight truncate">AENGELOS</span>
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-zinc-500 block -mt-0.5">Tattoo Studio</span>
                        </div>
                    </a>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/25 flex-shrink-0">
                        Client Hub
                    </span>
                </div>

                <!-- ALL CLIENT ACTION BUTTONS IN A CLEAN SEGMENTED DOCK -->
                <nav class="flex items-center p-1 rounded-2xl bg-slate-100/90 dark:bg-zinc-900/90 border border-slate-200/80 dark:border-zinc-800/80 text-xs font-bold text-slate-700 dark:text-zinc-300 shadow-inner">
                    <!-- 1. GALLERY PROOFS (OPENS FIXED SIDEBAR) -->
                    <button type="button" onclick="openGallerySidebar()"
                        class="px-3.5 py-1.5 rounded-xl hover:bg-white dark:hover:bg-zinc-800 hover:text-amber-600 dark:hover:text-amber-400 hover:shadow-xs transition-all flex items-center space-x-1.5 active:scale-95 group"
                        title="Browse Studio Inked Gallery Proofs">
                        <svg class="w-4 h-4 text-amber-500 transition-transform group-hover:scale-110 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Gallery Proofs</span>
                    </button>

                    <!-- 2. AVAIL & DESIGN -->
                    <a href="avail.php"
                        class="px-3.5 py-1.5 rounded-xl hover:bg-white dark:hover:bg-zinc-800 hover:text-amber-600 dark:hover:text-amber-400 hover:shadow-xs transition-all flex items-center space-x-1.5 active:scale-95 group"
                        title="Explore Flash & Reserve Custom Tattoo">
                        <svg class="w-4 h-4 text-amber-500 transition-transform group-hover:scale-110 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Avail & Design</span>
                    </a>

                    <!-- 3. 2D VISUALIZER -->
                    <a href="#visualizerSection"
                        class="px-3.5 py-1.5 rounded-xl hover:bg-white dark:hover:bg-zinc-800 hover:text-amber-600 dark:hover:text-amber-400 hover:shadow-xs transition-all flex items-center space-x-1.5 active:scale-95 group"
                        title="Open Interactive 2D Mannequin Visualizer">
                        <svg class="w-4 h-4 text-amber-500 transition-transform group-hover:scale-110 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        <span>2D Visualizer</span>
                    </a>

                    <!-- 4. MY BOOKINGS -->
                    <a href="#bookingsSection"
                        class="px-3.5 py-1.5 rounded-xl hover:bg-white dark:hover:bg-zinc-800 hover:text-amber-600 dark:hover:text-amber-400 hover:shadow-xs transition-all flex items-center space-x-1.5 active:scale-95 group"
                        title="View My Appointments & Digital Waivers">
                        <svg class="w-4 h-4 text-amber-500 transition-transform group-hover:scale-110 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span>My Bookings</span>
                        <?php if ($navBookingsCount > 0): ?>
                            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-black bg-amber-500 text-slate-950 shadow-xs"><?= $navBookingsCount ?></span>
                        <?php endif; ?>
                    </a>
                </nav>

                <!-- RIGHT CONTROLS: PROFILE, THEME & SIGN OUT -->
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <!-- USER IDENTITY PROFILE PILL -->
                    <div class="flex items-center space-x-2 px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800">
                        <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-amber-500 to-amber-400 text-slate-950 font-black text-xs flex items-center justify-center shadow-xs flex-shrink-0">
                            <?= strtoupper(substr($navUser['full_name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-zinc-200 max-w-[120px] truncate">
                            <?= htmlspecialchars($navUser['full_name'] ?? 'Client') ?>
                        </span>
                    </div>

                    <!-- THEME TOGGLE BUTTON -->
                    <button type="button" onclick="toggleTheme()"
                        class="p-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white hover:bg-slate-100 dark:bg-zinc-900 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300 hover:border-amber-500 transition-all shadow-xs flex items-center justify-center space-x-1 active:scale-95 flex-shrink-0"
                        title="Toggle Light / Dark Mode">
                        <span id="themeIcon" class="flex items-center justify-center"></span>
                    </button>

                    <!-- SIGN OUT BUTTON -->
                    <form action="api.php" method="POST" class="inline m-0 p-0 flex-shrink-0" onsubmit="return confirmLogout(event)">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" title="Sign Out"
                            class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white hover:bg-red-50 dark:bg-zinc-900 dark:hover:bg-red-950/40 text-slate-600 hover:text-red-600 dark:text-zinc-400 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-900/50 text-xs font-bold transition-all shadow-xs flex items-center space-x-1.5 active:scale-95 cursor-pointer">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>

            </div>

                        <!-- ========================================================================= -->
            <!-- CLIENT CONTEXT: MOBILE TOP BAR: CLEAN & UNCLUTTERED (< MD)                -->
            <!-- ========================================================================= -->
            <div class="md:hidden flex items-center justify-between gap-2 w-full py-0.5">
                <!-- BRAND & BADGE -->
                <div class="flex items-center space-x-2 min-w-0">
                    <a href="index.php" class="flex items-center space-x-2 group">
                        <span class="w-3.5 h-3.5 rounded-full bg-amber-500 shadow-md shadow-amber-500/50 group-hover:scale-125 transition-transform flex-shrink-0"></span>
                        <div class="min-w-0">
                            <span class="font-black text-sm tracking-wider text-amber-600 dark:text-amber-400 block leading-tight truncate">AENGELOS</span>
                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-zinc-500 block -mt-0.5">Tattoo Studio</span>
                        </div>
                    </a>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/25 flex-shrink-0">
                        Client Hub
                    </span>
                </div>

                <!-- RIGHT USER CONTROLS: PROFILE, THEME & SIGN OUT -->
                <div class="flex items-center space-x-1.5 flex-shrink-0">
                    <!-- USER IDENTITY PROFILE PILL -->
                    <div class="flex items-center space-x-1.5 px-2 py-1 rounded-xl bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800">
                        <div class="w-5 h-5 rounded-md bg-gradient-to-tr from-amber-500 to-amber-400 text-slate-950 font-black text-[10px] flex items-center justify-center shadow-xs flex-shrink-0">
                            <?= strtoupper(substr($navUser['full_name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-zinc-200 max-w-[85px] truncate">
                            <?= htmlspecialchars(explode(' ', trim($navUser['full_name'] ?? 'Client'))[0]) ?>
                        </span>
                    </div>

                    <!-- THEME TOGGLE BUTTON -->
                    <button type="button" onclick="toggleTheme()"
                        class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white hover:bg-slate-100 dark:bg-zinc-900 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300 flex items-center justify-center active:scale-95 shadow-xs flex-shrink-0"
                        title="Toggle Light / Dark Mode">
                        <span id="themeIconMobile" class="flex items-center justify-center scale-90"></span>
                    </button>

                    <!-- SIGN OUT BUTTON -->
                    <form action="api.php" method="POST" class="inline m-0 p-0 flex-shrink-0" onsubmit="return confirmLogout(event)">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" title="Sign Out"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white hover:bg-red-50 dark:bg-zinc-900 dark:hover:bg-red-950/40 text-slate-500 hover:text-red-500 dark:text-zinc-400 flex items-center justify-center active:scale-95 shadow-xs cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>

            <!-- ========================================================================= -->
            <!-- OTHER CONTEXTS: STOREFRONT, AVAIL, ARTIST, REGISTER, ADMIN                -->
            <!-- ========================================================================= -->
            <div class="flex items-center justify-between gap-1.5 sm:gap-4">
                
                <!-- BRAND LOGO & BADGE -->
                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-shrink-0">
                    <a href="index.php" class="flex items-center space-x-2 sm:space-x-2.5 group">
                        <span class="w-3.5 h-3.5 sm:w-4 sm:h-4 rounded-full bg-amber-500 shadow-lg shadow-amber-500/50 group-hover:scale-125 transition-transform flex-shrink-0"></span>
                        <div class="min-w-0">
                            <span class="font-black text-sm sm:text-lg tracking-wider text-amber-600 dark:text-amber-400 block leading-tight truncate">AENGELOS</span>
                            <span class="text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-zinc-400 block -mt-0.5">Tattoo Studio</span>
                        </div>
                    </a>

                    <?php if ($navContext === 'admin'): ?>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-red-500/15 text-red-700 dark:text-red-400 border border-red-500/30 shadow-xs flex-shrink-0">
                            Admin Hub
                        </span>
                    <?php elseif ($navContext === 'artist'): ?>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30 shadow-xs flex-shrink-0">
                            Artist Station
                        </span>
                    <?php elseif ($navContext === 'avail'): ?>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30 shadow-xs flex-shrink-0">
                            Avail & Design
                        </span>
                    <?php elseif ($navContext === 'register'): ?>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30 shadow-xs flex-shrink-0">
                            Sign Up
                        </span>
                    <?php else: ?>
                        <span class="hidden sm:inline-block px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30 shadow-xs flex-shrink-0">
                            Gallery Proofs
                        </span>
                    <?php endif; ?>
                </div>

                <!-- DESKTOP CENTER NAVIGATION -->
                <?php if ($navContext === 'storefront'): ?>
                    <form action="index.php" method="GET" class="hidden md:flex flex-1 max-w-md mx-2 lg:mx-4">
                        <div class="relative w-full">
                            <input type="text" name="q" value="<?= htmlspecialchars($navSearchQuery) ?>"
                                placeholder="Search categories, minimalist, dragon, irezumi..."
                                class="w-full bg-slate-100 dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 rounded-xl py-2 pl-4 pr-11 text-xs text-slate-900 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-amber-500 focus:bg-white dark:focus:bg-zinc-950 transition-all shadow-inner">
                            <button type="submit" aria-label="Search"
                                class="absolute right-1 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 flex items-center justify-center transition-colors shadow-xs active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </form>

                <?php elseif ($navContext === 'admin'): ?>
                    <nav class="hidden lg:flex items-center space-x-1.5 text-xs font-semibold">
                        <a href="admin_portal.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-all <?= $navAdminPage === 'overview' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 font-bold shadow-xs' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                            <svg class="w-4 h-4 <?= $navAdminPage === 'overview' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span>Overview</span>
                            <?php if ($navPendingDeposits > 0): ?>
                                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-500 text-slate-950"><?= $navPendingDeposits ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="admin_catalog.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-all <?= $navAdminPage === 'catalog' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 font-bold shadow-xs' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                            <svg class="w-4 h-4 <?= $navAdminPage === 'catalog' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span>Shop Catalog</span>
                        </a>
                        <a href="admin_reports.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-all <?= $navAdminPage === 'reports' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 font-bold shadow-xs' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                            <svg class="w-4 h-4 <?= $navAdminPage === 'reports' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Audit Reports</span>
                        </a>
                        <a href="admin_pricing.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-all <?= $navAdminPage === 'pricing' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 font-bold shadow-xs' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                            <svg class="w-4 h-4 <?= $navAdminPage === 'pricing' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Pricing Matrix</span>
                        </a>
                        <a href="admin_users.php" class="px-3 py-2 rounded-xl flex items-center space-x-1.5 transition-all <?= $navAdminPage === 'users' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30 font-bold shadow-xs' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800/60' ?>">
                            <svg class="w-4 h-4 <?= $navAdminPage === 'users' ? 'text-amber-500' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Users & RBAC</span>
                        </a>
                    </nav>

                <?php elseif ($navContext === 'artist'): ?>
                    <div class="hidden lg:flex items-center space-x-2 flex-shrink-0">
                        <button type="button" onclick="openGallerySidebar()" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 hover:bg-white dark:bg-zinc-900/80 dark:hover:bg-zinc-800 text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white font-bold text-xs hover:border-amber-500/50 transition-all shadow-sm hover:shadow flex items-center space-x-1.5 active:scale-95">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Gallery Proofs</span>
                        </button>
                        <a href="avail.php" target="_blank" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 hover:bg-white dark:bg-zinc-900/80 dark:hover:bg-zinc-800 text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white font-bold text-xs hover:border-amber-500/50 transition-all shadow-sm hover:shadow flex items-center space-x-1.5 active:scale-95">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            <span>Avail & Design</span>
                        </a>
                        <a href="#uploadSection" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 hover:bg-white dark:bg-zinc-900/80 dark:hover:bg-zinc-800 text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white font-bold text-xs hover:border-amber-500/50 transition-all shadow-sm hover:shadow flex items-center space-x-1.5 active:scale-95">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Upload Artwork</span>
                        </a>
                        <a href="#depositsSection" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 hover:bg-white dark:bg-zinc-900/80 dark:hover:bg-zinc-800 text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white font-bold text-xs hover:border-amber-500/50 transition-all shadow-sm hover:shadow flex items-center space-x-1.5 active:scale-95">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Deposits</span>
                            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-black bg-amber-500 text-slate-950 shadow-xs"><?= $navPendingDeposits ?></span>
                        </a>
                        <a href="#scheduleSection" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 hover:bg-white dark:bg-zinc-900/80 dark:hover:bg-zinc-800 text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white font-bold text-xs hover:border-amber-500/50 transition-all shadow-sm hover:shadow flex items-center space-x-1.5 active:scale-95">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Studio Queue</span>
                            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-black bg-emerald-500 text-slate-950 shadow-xs"><?= $navConfirmedCount ?></span>
                        </a>
                    </div>

                <?php elseif ($navContext === 'avail'): ?>
                    <div class="hidden md:flex items-center space-x-2 flex-shrink-0">
                        <button type="button" onclick="openGallerySidebar()" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 hover:bg-white dark:bg-zinc-900/80 dark:hover:bg-zinc-800 text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white font-bold text-xs hover:border-amber-500/50 transition-all shadow-sm hover:shadow flex items-center space-x-1.5 active:scale-95">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Gallery Proofs</span>
                        </button>
                        <a href="#placementStepAnchor" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 hover:bg-white dark:bg-zinc-900/80 dark:hover:bg-zinc-800 text-slate-700 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white font-bold text-xs hover:border-amber-500/50 transition-all shadow-sm hover:shadow flex items-center space-x-1.5 active:scale-95">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                            <span>2D Mannequin Visualizer</span>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- RIGHT CONTROLS: ACTION CTA, PROFILE, THEME & SIGN OUT -->
                <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">

                    <?php if ($navContext === 'storefront'): ?>
                        <a href="avail.php" class="px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-[11px] sm:text-xs uppercase tracking-wider shadow-md shadow-amber-500/25 transition-all flex items-center space-x-1 sm:space-x-1.5 active:scale-95 hover:scale-[1.02] flex-shrink-0">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-950 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="sm:hidden">Avail</span>
                            <span class="hidden sm:inline">Avail & Design</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($navUser): ?>
                        <!-- USER IDENTITY PROFILE PILL -->
                        <a href="<?= $navPortalUrl ?>"
                            class="flex items-center space-x-1.5 sm:space-x-2 px-2 sm:px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 hover:border-amber-500 transition-all shadow-sm active:scale-95 flex-shrink-0">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-tr <?= ($navUser['role'] ?? '') === 'admin' ? 'from-red-500 to-amber-500' : 'from-amber-500 to-amber-400' ?> text-slate-950 font-black text-xs flex items-center justify-center shadow-xs flex-shrink-0">
                                <?= strtoupper(substr($navUser['full_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="leading-none text-left hidden sm:block">
                                <span class="block text-xs font-black text-slate-900 dark:text-white max-w-[85px] sm:max-w-[125px] truncate">
                                    <?= htmlspecialchars($navUser['full_name']) ?>
                                </span>
                                <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400 block"><?= $navRoleLabel ?></span>
                            </div>
                        </a>

                        <!-- THEME TOGGLE BUTTON -->
                        <button type="button" onclick="toggleTheme()"
                            class="w-8 h-8 sm:w-auto p-2 sm:px-2.5 sm:py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white hover:bg-slate-100 dark:bg-zinc-900 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold text-xs hover:border-amber-500 transition-all shadow-sm flex items-center justify-center space-x-1 active:scale-95 flex-shrink-0"
                            title="Toggle Light / Dark Mode">
                            <span id="themeIcon" class="flex items-center justify-center"></span>
                            <span id="themeLabel" class="hidden xl:inline text-[11px]">Dark</span>
                        </button>

                        <!-- SIGN OUT RED PILL BUTTON -->
                        <form action="api.php" method="POST" class="inline m-0 p-0 flex-shrink-0" onsubmit="return confirmLogout(event)">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" title="Sign Out"
                                class="p-2 sm:px-3 sm:py-2 rounded-xl bg-red-50 hover:bg-red-500 text-red-600 hover:text-white dark:bg-red-950/40 dark:hover:bg-red-600 dark:text-red-300 dark:hover:text-white border border-red-200 dark:border-red-900/50 hover:border-red-500 font-bold text-xs transition-all shadow-sm flex items-center space-x-1 active:scale-95 cursor-pointer">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="hidden sm:inline">Sign Out</span>
                            </button>
                        </form>

                    <?php else: ?>
                        <!-- GUEST: SIGN IN & REGISTER BUTTONS -->
                        <?php if ($navCurrentFile === 'index.php'): ?>
                            <button type="button" onclick="typeof openLoginModal === 'function' ? openLoginModal() : (window.location.href='index.php?open_login=1')"
                                class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white hover:bg-slate-100 dark:bg-zinc-900 dark:hover:bg-zinc-800 text-slate-800 dark:text-zinc-200 font-bold text-[11px] sm:text-xs hover:border-amber-500 transition-all shadow-sm flex items-center space-x-1 sm:space-x-1.5 active:scale-95 flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                <span>Sign In</span>
                            </button>
                        <?php else: ?>
                            <a href="index.php?open_login=1"
                                class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-xl border border-slate-300 dark:border-zinc-700 bg-white hover:bg-slate-100 dark:bg-zinc-900 dark:hover:bg-zinc-800 text-slate-800 dark:text-zinc-200 font-bold text-[11px] sm:text-xs hover:border-amber-500 transition-all shadow-sm flex items-center space-x-1 sm:space-x-1.5 active:scale-95 flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                <span>Sign In</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($navCurrentFile !== 'register.php' && $navCurrentFile !== 'signup.php'): ?>
                            <a href="register.php"
                                class="hidden sm:inline-flex px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-all shadow-sm items-center space-x-1 active:scale-95 flex-shrink-0">
                                <span>Register</span>
                            </a>
                        <?php endif; ?>

                        <!-- THEME TOGGLE BUTTON -->
                        <button type="button" onclick="toggleTheme()"
                            class="w-8 h-8 sm:w-auto p-2 sm:px-2.5 sm:py-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white hover:bg-slate-100 dark:bg-zinc-900 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300 font-bold text-xs hover:border-amber-500 transition-all shadow-sm flex items-center justify-center space-x-1 active:scale-95 flex-shrink-0"
                            title="Toggle Light / Dark Mode">
                            <span id="themeIcon" class="flex items-center justify-center"></span>
                            <span id="themeLabel" class="hidden xl:inline text-[11px]">Dark</span>
                        </button>
                    <?php endif; ?>

                    <?php if ($navContext === 'admin'): ?>
                        <!-- ADMIN MOBILE HAMBURGER TOGGLE -->
                        <button type="button" onclick="toggleMobileNav()" class="lg:hidden p-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-100 dark:bg-zinc-900 text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-800 transition-colors flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                    <?php endif; ?>

                </div>

            </div>

            <?php if ($navContext === 'storefront'): ?>
                <!-- STOREFRONT MOBILE SEARCH BAR -->
                <form action="index.php" method="GET" class="md:hidden mt-2">
                    <div class="relative w-full">
                        <input type="text" name="q" value="<?= htmlspecialchars($navSearchQuery) ?>"
                            placeholder="Search styles, minimalist, dragon, realism..."
                            class="w-full bg-slate-100 dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 rounded-xl py-2 pl-3 pr-10 text-xs text-slate-900 dark:text-zinc-100 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-inner">
                        <button type="submit" aria-label="Search"
                            class="absolute right-1 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg bg-amber-500 text-slate-950 flex items-center justify-center shadow-xs active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- ADMIN MOBILE DRAWER -->
            <?php if ($navContext === 'admin'): ?>
                <div id="mobileNavDropdown" class="hidden lg:hidden border-t border-slate-200 dark:border-zinc-800 py-3 space-y-1 text-xs font-semibold">
                    <a href="admin_portal.php" class="block px-3 py-2 rounded-lg <?= $navAdminPage === 'overview' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                        Dashboard Overview
                    </a>
                    <a href="admin_catalog.php" class="block px-3 py-2 rounded-lg <?= $navAdminPage === 'catalog' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                        Shop Catalog & Flash Art
                    </a>
                    <a href="admin_reports.php" class="block px-3 py-2 rounded-lg <?= $navAdminPage === 'reports' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                        Audit & Accounting Reports
                    </a>
                    <a href="admin_pricing.php" class="block px-3 py-2 rounded-lg <?= $navAdminPage === 'pricing' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                        Service Pricing Matrix
                    </a>
                    <a href="admin_users.php" class="block px-3 py-2 rounded-lg <?= $navAdminPage === 'users' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800' ?>">
                        User Directory & RBAC
                    </a>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</header>
<!-- FIXED NAVBAR SPACER: Guarantees zero content overlap across all pages -->
<div class="h-14 sm:h-16 w-full flex-shrink-0" aria-hidden="true"></div>

<?php if ($navContext === 'client'): ?>
<!-- ========================================================================= -->
<!-- CLIENT MOBILE BOTTOM NAVIGATION BAR: 100% VISIBLE, THUMB-ACCESSIBLE      -->
<!-- ========================================================================= -->
<nav id="clientMobileBottomNav" aria-label="Mobile Bottom Navigation"
    class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-md border-t-2 border-slate-200 dark:border-zinc-800 shadow-[0_-8px_30px_rgba(0,0,0,0.12)] dark:shadow-[0_-8px_30px_rgba(0,0,0,0.6)] px-3 py-2 transition-transform duration-200">
    <div class="grid grid-cols-4 gap-2 max-w-md mx-auto items-center">
        <!-- 1. GALLERY PROOFS (OPENS FIXED SIDEBAR) -->
        <button type="button" onclick="openGallerySidebar()"
            class="flex flex-col items-center justify-center py-2 px-1 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 text-slate-800 dark:text-zinc-100 shadow-xs transition-all active:scale-95 group"
            title="Browse Studio Gallery Proofs">
            <svg class="w-5 h-5 text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-[11px] font-black tracking-tight mt-1 text-slate-900 dark:text-white">Gallery</span>
        </button>

        <!-- 2. AVAIL & DESIGN (FEATURED PRIMARY GOLD ACTION) -->
        <a href="avail.php"
            class="flex flex-col items-center justify-center py-2 px-1 rounded-2xl bg-gradient-to-tr from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 shadow-md shadow-amber-500/30 border border-amber-400 transition-all active:scale-95 group"
            title="Avail Flash & Custom Designs">
            <svg class="w-5 h-5 text-slate-950 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="text-[11px] font-black uppercase tracking-wider mt-1 text-slate-950">Avail</span>
        </a>

        <!-- 3. 2D VISUALIZER -->
        <a href="#visualizerSection"
            class="flex flex-col items-center justify-center py-2 px-1 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 text-slate-800 dark:text-zinc-100 shadow-xs transition-all active:scale-95 group"
            title="2D Mannequin Visualizer">
            <svg class="w-5 h-5 text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
            </svg>
            <span class="text-[11px] font-black tracking-tight mt-1 text-slate-900 dark:text-white">Visualizer</span>
        </a>

        <!-- 4. MY BOOKINGS -->
        <a href="#bookingsSection"
            class="relative flex flex-col items-center justify-center py-2 px-1 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 text-slate-800 dark:text-zinc-100 shadow-xs transition-all active:scale-95 group"
            title="My Appointments & Bookings">
            <svg class="w-5 h-5 text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="text-[11px] font-black tracking-tight mt-1 text-slate-900 dark:text-white">Bookings</span>
            <?php if ($navBookingsCount > 0): ?>
                <span class="absolute top-1 right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-amber-500 text-slate-950 text-[10px] font-black flex items-center justify-center shadow-md">
                    <?= $navBookingsCount ?>
                </span>
            <?php endif; ?>
        </a>
    </div>
</nav>

<!-- FIXED BOTTOM SPACER: Guarantees page content and submit buttons are never obscured on mobile -->
<div class="md:hidden h-20 sm:h-24 w-full flex-shrink-0" aria-hidden="true"></div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- FIXED GALLERY SIDEBAR DRAWER (OPENS WHEN USER CLICKS 'GALLERY')           -->
<!-- ========================================================================= -->
<div id="gallerySidebarBackdrop" onclick="closeGallerySidebar()"
    class="hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 transition-opacity duration-300 opacity-0 pointer-events-none"></div>

<aside id="gallerySidebar"
    class="fixed inset-y-0 right-0 z-50 w-full sm:w-[440px] md:w-[500px] bg-white dark:bg-zinc-900 border-l border-slate-200 dark:border-zinc-800 shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out">
    
    <!-- SIDEBAR TOP HEADER -->
    <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-zinc-800 flex items-center justify-between bg-slate-50 dark:bg-zinc-950 flex-shrink-0">
        <div class="flex items-center space-x-2.5">
            <span class="w-3.5 h-3.5 rounded-full bg-amber-500 shadow-md shadow-amber-500/50 flex-shrink-0"></span>
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white uppercase tracking-wider">Gallery Proofs</h3>
                <p class="text-[10px] text-slate-500 dark:text-zinc-400">Authentic Client Inked Works & Proof of Quality</p>
            </div>
        </div>
        <button type="button" onclick="closeGallerySidebar()" aria-label="Close Gallery Sidebar"
            class="p-2 rounded-xl text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-zinc-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- SEARCH & CATEGORY FILTER BAR -->
    <div class="p-3 sm:p-4 border-b border-slate-100 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 flex-shrink-0 space-y-2.5">
        <div class="relative">
            <input type="text" id="gallerySidebarSearch" oninput="filterGallerySidebar()" placeholder="Search style, minimalist, dragon, realism..."
                class="w-full bg-slate-100 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl py-2 pl-3 pr-9 text-xs text-slate-900 dark:text-zinc-100 placeholder-slate-400 focus:outline-none focus:border-amber-500">
            <svg class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <!-- CATEGORY PILLS -->
        <div class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none py-0.5">
            <button type="button" onclick="filterGallerySidebarCategory('all')" data-cat="all"
                class="gallery-cat-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-amber-500 text-slate-950 shadow-xs flex-shrink-0 transition-all">All</button>
            <button type="button" onclick="filterGallerySidebarCategory('Minimalist')" data-cat="Minimalist"
                class="gallery-cat-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex-shrink-0 transition-all">Minimalist</button>
            <button type="button" onclick="filterGallerySidebarCategory('Japanese / Irezumi')" data-cat="Japanese / Irezumi"
                class="gallery-cat-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex-shrink-0 transition-all">Japanese</button>
            <button type="button" onclick="filterGallerySidebarCategory('Realism')" data-cat="Realism"
                class="gallery-cat-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex-shrink-0 transition-all">Realism</button>
            <button type="button" onclick="filterGallerySidebarCategory('Flash Art')" data-cat="Flash Art"
                class="gallery-cat-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex-shrink-0 transition-all">Flash Art</button>
            <button type="button" onclick="filterGallerySidebarCategory('Custom Design')" data-cat="Custom Design"
                class="gallery-cat-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex-shrink-0 transition-all">Custom</button>
        </div>
    </div>

    <!-- GALLERY STREAM (SCROLLABLE) -->
    <div id="gallerySidebarStream" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-3">
        <?php if (empty($navGalleryItems)): ?>
            <div class="text-center py-12 text-slate-400 dark:text-zinc-500 text-xs">
                No gallery pieces loaded in catalog.
            </div>
        <?php else: ?>
            <?php foreach ($navGalleryItems as $item): ?>
                <div class="gallery-card bg-slate-50 dark:bg-zinc-950 p-3 rounded-2xl border border-slate-200 dark:border-zinc-800 flex gap-3 transition-all hover:border-amber-500/50 shadow-xs"
                    data-title="<?= strtolower(htmlspecialchars($item['title'])) ?>"
                    data-category="<?= htmlspecialchars($item['category']) ?>"
                    data-tags="<?= strtolower(htmlspecialchars($item['tags'] ?? '')) ?>"
                    data-artist="<?= strtolower(htmlspecialchars($item['artist_name'])) ?>">
                    
                    <a href="javascript:void(0)" onclick="openGalleryProofModal('<?= htmlspecialchars($item['image_path']) ?>', '<?= addslashes(htmlspecialchars($item['title'])) ?>', '<?= addslashes(htmlspecialchars($item['category'])) ?>', '<?= addslashes(htmlspecialchars($item['artist_name'])) ?>')"
                        class="relative w-20 h-20 rounded-xl overflow-hidden bg-slate-200 dark:bg-zinc-900 flex-shrink-0 block group cursor-pointer">
                        <img src="../<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        <span class="absolute inset-0 bg-black/15 group-hover:bg-transparent transition-colors"></span>
                    </a>

                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30">
                                    <?= htmlspecialchars($item['category']) ?>
                                </span>
                                <span class="text-[9px] font-bold text-slate-400 dark:text-zinc-500">
                                    ~<?= (int)($item['est_duration_mins'] ?? 60) ?>m
                                </span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                <?= htmlspecialchars($item['title']) ?>
                            </h4>
                            <p class="text-[10px] text-slate-500 dark:text-zinc-400 truncate">
                                Inked by <?= htmlspecialchars($item['artist_name']) ?>
                            </p>
                        </div>

                        <div class="flex items-center gap-1.5 pt-1.5 border-t border-slate-200/60 dark:border-zinc-800/60">
                            <button type="button" onclick="openGalleryProofModal('<?= htmlspecialchars($item['image_path']) ?>', '<?= addslashes(htmlspecialchars($item['title'])) ?>', '<?= addslashes(htmlspecialchars($item['category'])) ?>', '<?= addslashes(htmlspecialchars($item['artist_name'])) ?>')"
                                class="px-2 py-1 rounded-lg bg-slate-200 dark:bg-zinc-800 hover:bg-slate-300 text-slate-700 dark:text-zinc-300 text-[10px] font-bold transition-colors">
                                View Proof
                            </button>
                            <a href="avail.php?id=<?= $item['id'] ?>"
                                class="px-2 py-1 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-[10px] font-black uppercase tracking-wider transition-colors">
                                Avail Style
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- SIDEBAR FOOTER -->
    <div class="p-3 sm:p-4 border-t border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 flex-shrink-0 flex items-center justify-between gap-2">
        <a href="index.php" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center space-x-1">
            <span>View Full Storefront Page</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
        <button type="button" onclick="closeGallerySidebar()"
            class="px-3 py-1.5 rounded-xl border border-slate-300 dark:border-zinc-700 text-xs font-bold text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-800 transition-colors">
            Close
        </button>
    </div>
</aside>

<!-- PROOF LIGHTBOX MODAL -->
<div id="galleryProofModal" class="hidden fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4" onclick="closeGalleryProofModal()">
    <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-3xl max-w-lg w-full p-4 sm:p-6 space-y-4 shadow-2xl relative" onclick="event.stopPropagation()">
        <button type="button" onclick="closeGalleryProofModal()" aria-label="Close modal"
            class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 dark:hover:text-white p-1 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800">
            <img id="proofModalImg" src="" alt="Proof Preview" class="w-full h-full object-cover">
        </div>
        <div class="flex items-center justify-between gap-2">
            <div>
                <span id="proofModalCategory" class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30"></span>
                <h3 id="proofModalTitle" class="text-base font-black text-slate-900 dark:text-white mt-1"></h3>
                <p id="proofModalArtist" class="text-xs text-slate-500 dark:text-zinc-400"></p>
            </div>
            <a href="avail.php" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider transition-colors shadow-sm">
                Avail Style
            </a>
        </div>
    </div>
</div>

<!-- SWEETALERT2 NOTIFICATION & MODAL SUITE -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- GLOBAL UNIFIED JAVASCRIPT FOR FIXED NAVBAR, THEME TOGGLE & GALLERY SIDEBAR -->
<script>
    // SweetAlert2 Interactive Session Logout Confirmation
    function confirmLogout(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        const isDark = document.documentElement.classList.contains('dark');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Sign Out?',
                text: 'Are you sure you want to end your current session?',
                icon: 'question',
                iconColor: '#f59e0b',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: isDark ? '#3f3f46' : '#94a3b8',
                confirmButtonText: 'Yes, Sign Out',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                background: isDark ? '#18181b' : '#ffffff',
                color: isDark ? '#f4f4f5' : '#0f172a',
                customClass: {
                    popup: 'rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-2xl p-6',
                    title: 'text-lg font-black tracking-tight text-slate-900 dark:text-white',
                    htmlContainer: 'text-xs text-slate-500 dark:text-zinc-400',
                    confirmButton: 'px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider !text-slate-950 shadow-md',
                    cancelButton: 'px-5 py-2.5 rounded-xl font-bold text-xs text-white shadow-md'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Signing Out...',
                        text: 'Safely concluding your session.',
                        timer: 800,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        background: isDark ? '#18181b' : '#ffffff',
                        color: isDark ? '#f4f4f5' : '#0f172a',
                        customClass: {
                            popup: 'rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-2xl p-6'
                        },
                        willClose: () => {
                            performLogoutSubmission();
                        }
                    });
                }
            });
        } else {
            if (confirm('Are you sure you want to sign out?')) {
                performLogoutSubmission();
            }
        }
        return false;
    }

    function performLogoutSubmission() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'api.php';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'action';
        input.value = 'logout';
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
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
        const iconMobile = document.getElementById('themeIconMobile');
        const label = document.getElementById('themeLabel');
        const svgContent = isDark 
            ? '<svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>'
            : '<svg class="w-3.5 h-3.5 text-slate-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>';
        if (icon) {
            icon.innerHTML = svgContent;
        }
        if (iconMobile) {
            iconMobile.innerHTML = svgContent;
        }
        if (label) {
            label.textContent = isDark ? 'Light' : 'Dark';
        }
    }

    // FIXED GALLERY SIDEBAR DRAWER CONTROLS
    function openGallerySidebar() {
        const sb = document.getElementById('gallerySidebar');
        const bd = document.getElementById('gallerySidebarBackdrop');
        if (sb && bd) {
            bd.classList.remove('hidden', 'pointer-events-none');
            requestAnimationFrame(() => {
                bd.classList.remove('opacity-0');
                bd.classList.add('opacity-100');
                sb.classList.remove('translate-x-full');
                sb.classList.add('translate-x-0');
            });
            document.body.style.overflow = 'hidden';
        }
    }

    function closeGallerySidebar() {
        const sb = document.getElementById('gallerySidebar');
        const bd = document.getElementById('gallerySidebarBackdrop');
        if (sb && bd) {
            sb.classList.remove('translate-x-0');
            sb.classList.add('translate-x-full');
            bd.classList.remove('opacity-100');
            bd.classList.add('opacity-0');
            setTimeout(() => {
                bd.classList.add('hidden', 'pointer-events-none');
                document.body.style.overflow = '';
            }, 300);
        }
    }

    function filterGallerySidebarCategory(cat) {
        window.currentGallerySidebarCat = cat;
        document.querySelectorAll('.gallery-cat-btn').forEach(btn => {
            if (btn.getAttribute('data-cat') === cat) {
                btn.className = 'gallery-cat-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-amber-500 text-slate-950 shadow-xs flex-shrink-0 transition-all';
            } else {
                btn.className = 'gallery-cat-btn px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white flex-shrink-0 transition-all';
            }
        });
        filterGallerySidebar();
    }

    function filterGallerySidebar() {
        const q = (document.getElementById('gallerySidebarSearch')?.value || '').toLowerCase().trim();
        const cat = window.currentGallerySidebarCat || 'all';
        const cards = document.querySelectorAll('.gallery-card');
        cards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const cardCat = card.getAttribute('data-category') || '';
            const tags = card.getAttribute('data-tags') || '';
            const artist = card.getAttribute('data-artist') || '';
            
            const matchesCat = (cat === 'all' || cardCat === cat);
            const matchesQuery = (!q || title.includes(q) || tags.includes(q) || artist.includes(q) || cardCat.toLowerCase().includes(q));
            
            if (matchesCat && matchesQuery) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    function openGalleryProofModal(imgSrc, title, category, artist) {
        const modal = document.getElementById('galleryProofModal');
        if (!modal) return;
        const formattedSrc = imgSrc.startsWith('http') || imgSrc.startsWith('../') ? imgSrc : ('../' + imgSrc);
        document.getElementById('proofModalImg').src = formattedSrc;
        document.getElementById('proofModalTitle').textContent = title;
        document.getElementById('proofModalCategory').textContent = category;
        document.getElementById('proofModalArtist').textContent = 'Inked by ' + artist;
        modal.classList.remove('hidden');
    }

    function closeGalleryProofModal() {
        const modal = document.getElementById('galleryProofModal');
        if (modal) modal.classList.add('hidden');
    }

    // Keyboard controls (Esc to dismiss sidebar and modal)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGallerySidebar();
            closeGalleryProofModal();
        }
    });

    // Initialize theme button on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            updateThemeButton(document.documentElement.classList.contains('dark'));
            checkLogoutNotice();
        });
    } else {
        updateThemeButton(document.documentElement.classList.contains('dark'));
        checkLogoutNotice();
    }

    function checkLogoutNotice() {
        const urlParams = new URLSearchParams(window.location.search);
        const urlMsg = urlParams.get('msg');
        if (urlMsg && urlMsg.toLowerCase().includes('logged out') && typeof Swal !== 'undefined') {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Signed Out Successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: isDark ? '#18181b' : '#ffffff',
                color: isDark ? '#f4f4f5' : '#0f172a',
                customClass: {
                    popup: 'rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xl'
                }
            });
        }
    }
</script>
