<?php
// public/index.php - Shopee-Style Tattoo Design Catalog & Unified Storefront
require_once __DIR__ . '/../config/db.php';

$currentUser = currentUser();

// Query active tattoo designs from catalog
$categoryFilter = $_GET['category'] ?? 'all';
$sortOrder = $_GET['sort'] ?? 'featured';
$searchQuery = trim($_GET['q'] ?? '');

$sql = "
    SELECT c.*, u.full_name AS artist_name, u.role AS uploader_role
    FROM tattoo_catalog c
    JOIN users u ON c.uploader_id = u.id
    WHERE c.status != 'archived'
";
$params = [];

if ($categoryFilter !== 'all') {
    $sql .= " AND c.category = ?";
    $params[] = $categoryFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.tags LIKE ? OR c.category LIKE ? OR u.full_name LIKE ?)";
    $term = "%{$searchQuery}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

switch ($sortOrder) {
    case 'duration':
        $sql .= " ORDER BY c.est_duration_mins ASC";
        break;
    case 'newest':
        $sql .= " ORDER BY c.id DESC";
        break;
    case 'featured':
    default:
        $sql .= " ORDER BY c.is_featured DESC, c.id DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$catalogItems = $stmt->fetchAll();

// Categories available for filtering
$categories = ['all', 'Flash Art', 'Japanese / Irezumi', 'Minimalist', 'Realism', 'Custom Design'];

// Artists available for availment assignment
$artists = $pdo->query("SELECT id, full_name FROM users WHERE role = 'artist'")->fetchAll();
if (empty($artists)) {
    $artists = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('artist', 'admin')")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aengelos Tattoo Studio — Exclusive Tattoo Flash & Masterwork Catalog</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .shopee-badge {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }

        .shopee-price-gradient {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* SLOW CINEMATIC ANIMATIONS */
        @keyframes slowFloatA {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(25px, -20px) scale(1.15);
            }
        }

        @keyframes slowFloatB {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-30px, 15px) scale(1.1);
            }
        }

        @keyframes slowFloatC {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(15px, 25px) scale(0.92);
            }
        }

        @keyframes slowPulseGlow {

            0%,
            100% {
                opacity: 0.25;
                filter: blur(50px);
                transform: scale(1);
            }

            50% {
                opacity: 0.55;
                filter: blur(70px);
                transform: scale(1.15);
            }
        }

        @keyframes slowSpin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes slowShimmer {
            0% {
                transform: translateX(-150%) skewX(-20deg);
            }

            35%,
            100% {
                transform: translateX(250%) skewX(-20deg);
            }
        }

        @keyframes slowFadeUp {
            from {
                opacity: 0;
                transform: translateY(22px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slowBreatheBorder {

            0%,
            100% {
                border-color: rgba(245, 158, 11, 0.25);
                box-shadow: 0 0 20px rgba(245, 158, 11, 0.08);
            }

            50% {
                border-color: rgba(245, 158, 11, 0.6);
                box-shadow: 0 0 35px rgba(245, 158, 11, 0.22);
            }
        }

        @keyframes slowModalEnter {
            from {
                opacity: 0;
                transform: scale(0.96) translateY(12px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .animate-slow-float-a {
            animation: slowFloatA 16s ease-in-out infinite;
        }

        .animate-slow-float-b {
            animation: slowFloatB 20s ease-in-out infinite;
        }

        .animate-slow-float-c {
            animation: slowFloatC 18s ease-in-out infinite;
        }

        .animate-slow-pulse-glow {
            animation: slowPulseGlow 9s ease-in-out infinite;
        }

        .animate-slow-spin {
            animation: slowSpin 60s linear infinite;
        }

        .animate-slow-breathe {
            animation: slowBreatheBorder 7s ease-in-out infinite;
        }

        .animate-slow-shimmer {
            position: relative;
            overflow: hidden;
        }

        .animate-slow-shimmer::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.22), transparent);
            animation: slowShimmer 5.5s ease-in-out infinite;
            pointer-events: none;
        }

        .card-slow-enter {
            animation: slowFadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .modal-slow-enter {
            animation: slowModalEnter 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        /* Smooth slow transitions on hover */
        .slow-zoom-card {
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.6s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.6s ease;
        }

        .slow-zoom-card:hover {
            transform: translateY(-4px);
        }

        .slow-zoom-img {
            transition: transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .slow-zoom-card:hover .slow-zoom-img {
            transform: scale(1.07);
        }
    </style>
</head>

<body
    class="bg-slate-50 text-slate-800 dark:bg-zinc-950 dark:text-zinc-100 min-h-screen flex flex-col justify-between transition-colors duration-200">

    <!-- UNIFIED TOP NAVBAR COMPONENT -->
    <?php 
    $navContext = 'storefront';
    require_once __DIR__ . '/navbar.php'; 
    ?>

    <!-- MAIN STOREFRONT CONTAINER -->
    <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6 w-full space-y-6">

        <!-- SYSTEM NOTIFICATIONS -->
        <?php if (!empty($_GET['msg'])): ?>
            <div
                class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center space-x-3 shadow-sm animate-fade-in">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?= htmlspecialchars($_GET['msg']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div
                class="p-3.5 sm:p-4 rounded-2xl bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-500/40 text-red-800 dark:text-red-300 text-xs sm:text-sm flex items-center space-x-3 shadow-sm animate-fade-in">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <!-- SHOPEE HERO BANNER WITH SLOW ATMOSPHERIC ANIMATIONS -->
        <section
            class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-zinc-950 via-neutral-900 to-amber-950/90 border border-amber-500/30 shadow-2xl p-6 sm:p-10 text-white animate-slow-breathe">

            <!-- SLOW FLOATING AMBIENT GLOW ORBS -->
            <div
                class="absolute -right-20 -top-20 w-96 h-96 rounded-full bg-gradient-to-br from-amber-500/20 to-orange-600/10 blur-3xl pointer-events-none animate-slow-float-a">
            </div>
            <div
                class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full bg-gradient-to-tr from-amber-600/15 to-amber-400/10 blur-3xl pointer-events-none animate-slow-float-b">
            </div>
            <div
                class="absolute right-1/3 top-1/2 -translate-y-1/2 w-64 h-64 rounded-full bg-rose-500/10 blur-3xl pointer-events-none animate-slow-float-c">
            </div>

            <!-- SLOW ROTATING BACKGROUND MANDALA / TATTOO CREST WATERMARK -->
            <div
                class="absolute -right-24 -bottom-24 w-96 h-96 pointer-events-none select-none opacity-5 dark:opacity-10 text-amber-400 animate-slow-spin">
                <svg viewBox="0 0 100 100" class="w-full h-full fill-none stroke-current" stroke-width="0.75">
                    <circle cx="50" cy="50" r="46" />
                    <circle cx="50" cy="50" r="38" />
                    <circle cx="50" cy="50" r="28" />
                    <circle cx="50" cy="50" r="14" />
                    <polygon points="50,4 62,38 96,50 62,62 50,96 38,62 4,50 38,38" />
                    <polygon points="50,14 59,41 86,50 59,59 50,86 41,59 14,50 41,41" />
                    <line x1="50" y1="4" x2="50" y2="96" />
                    <line x1="4" y1="50" x2="96" y2="50" />
                    <line x1="17" y1="17" x2="83" y2="83" />
                    <line x1="17" y1="83" x2="83" y2="17" />
                </svg>
            </div>

            <div class="relative z-10 max-w-2xl space-y-3 sm:space-y-4">
                <div
                    class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-400 text-[10px] sm:text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                    <span class="flex h-2 w-2 relative">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                    </span>
                    <span>Shopee-Style Studio Marketplace</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400/60"></span>
                    <span>Direct Artist Inking</span>
                </div>

                <div class="relative">
                    <div
                        class="absolute -left-6 -top-6 w-48 h-20 bg-amber-500/20 rounded-full blur-2xl pointer-events-none animate-slow-pulse-glow">
                    </div>
                    <h1
                        class="relative text-2xl sm:text-4xl md:text-5xl font-black tracking-tight leading-none text-white drop-shadow-md">
                        Inked Client Works & Proofs of Quality
                    </h1>
                </div>

                <p class="text-xs sm:text-sm text-zinc-300 font-normal leading-relaxed">
                    Browse authentic proof of work from past clients who availed tattoos with our resident artists. Choose your tattoo style, decide your own custom design, select your target body placement, and reserve your studio session with zero upfront charge.
                </p>
                <div
                    class="pt-2 flex flex-wrap items-center gap-2 sm:gap-4 text-[11px] sm:text-xs text-amber-200/90 font-medium">
                    <span
                        class="flex items-center space-x-1.5 bg-black/40 px-3 py-1.5 rounded-xl border border-emerald-500/30 backdrop-blur-sm shadow-sm text-emerald-300">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>100% Real Client Proofs</span>
                    </span>
                    <span
                        class="flex items-center space-x-1.5 bg-black/40 px-3 py-1.5 rounded-xl border border-amber-500/20 backdrop-blur-sm shadow-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Verified Free Booking</span>
                    </span>
                    <span
                        class="flex items-center space-x-1.5 bg-black/40 px-3 py-1.5 rounded-xl border border-amber-500/20 backdrop-blur-sm shadow-sm">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Fast Chair Scheduling</span>
                    </span>
                    <span
                        class="flex items-center space-x-1.5 bg-black/40 px-3 py-1.5 rounded-xl border border-amber-500/20 backdrop-blur-sm shadow-sm">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Automated Digital Waiver</span>
                    </span>
                </div>
            </div>
        </section>

        <!-- SHOPEE CATEGORIES & FILTER BAR -->
        <div
            class="bg-white dark:bg-zinc-900/80 p-3 sm:p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-sm space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">

                <!-- CATEGORY PILLS -->
                <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $isActive = $categoryFilter === $cat;
                        $catLabel = $cat === 'all' ? 'All Pieces' : $cat;
                        ?>
                        <a href="index.php?category=<?= urlencode($cat) ?>&sort=<?= urlencode($sortOrder) ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>"
                            class="whitespace-nowrap px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all <?= $isActive ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300' ?>">
                            <?= htmlspecialchars($catLabel) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- SORT ORDER DROPDOWN -->
                <div class="flex items-center space-x-2 flex-shrink-0 self-end md:self-auto text-xs">
                    <span
                        class="text-slate-500 dark:text-zinc-400 font-semibold text-[11px] uppercase tracking-wider flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5 text-amber-500 inline-block" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                        </svg>
                        <span>Sort:</span>
                    </span>
                    <select onchange="window.location.href = this.value"
                        class="bg-slate-100 dark:bg-zinc-800 border border-slate-300 dark:border-zinc-700 rounded-xl px-3 py-1.5 text-xs text-slate-800 dark:text-zinc-200 font-semibold focus:outline-none focus:border-amber-500">
                        <option
                            value="index.php?category=<?= urlencode($categoryFilter) ?>&sort=featured<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>"
                            <?= $sortOrder === 'featured' ? 'selected' : '' ?>>Featured Pieces</option>
                        <option
                            value="index.php?category=<?= urlencode($categoryFilter) ?>&sort=duration<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>"
                            <?= $sortOrder === 'duration' ? 'selected' : '' ?>>Session Duration</option>
                        <option
                            value="index.php?category=<?= urlencode($categoryFilter) ?>&sort=newest<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>"
                            <?= $sortOrder === 'newest' ? 'selected' : '' ?>>Newest Works</option>
                    </select>
                </div>
            </div>

            <div
                class="flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400 pt-1 border-t border-slate-100 dark:border-zinc-800/80">
                <span>Showing <strong class="text-slate-900 dark:text-white"><?= count($catalogItems) ?></strong> tattoo
                    artwork designs</span>
                <?php if (!empty($searchQuery) || $categoryFilter !== 'all'): ?>
                    <a href="index.php" class="text-amber-600 dark:text-amber-400 font-bold hover:underline">Clear all
                        filters &times;</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- SHOPEE-STYLE PRODUCT GRID -->
        <?php if (empty($catalogItems)): ?>
            <div
                class="bg-white dark:bg-zinc-900/60 p-12 rounded-3xl border border-slate-200 dark:border-zinc-800 text-center space-y-3">
                <div class="w-16 h-16 mx-auto rounded-full bg-amber-500/10 text-amber-500 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">No Tattoo Designs Found</h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400 max-w-sm mx-auto">Try searching for a different keyword
                    or reset the category filter to view all available pieces.</p>
                <a href="index.php"
                    class="inline-block mt-2 px-4 py-2 rounded-xl bg-amber-500 text-slate-950 font-bold text-xs uppercase tracking-wider">Reset
                    Storefront</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 gap-3.5 sm:gap-6">
                <?php foreach ($catalogItems as $idx => $item): ?>
                    <div class="group card-slow-enter slow-zoom-card bg-white dark:bg-zinc-900/80 rounded-2xl sm:rounded-3xl border border-slate-200 dark:border-zinc-800 overflow-hidden shadow-sm hover:shadow-2xl hover:border-amber-500/50 flex flex-col justify-between"
                        style="animation-delay: <?= min($idx * 0.1, 0.8) ?>s;">

                        <!-- CARD IMAGE WITH SLOW ZOOM HOVER -->
                        <a href="avail.php?id=<?= $item['id'] ?>"
                            class="relative aspect-square overflow-hidden bg-slate-100 dark:bg-zinc-950 block cursor-pointer group-hover:opacity-95 transition-opacity">
                            <img src="../<?= htmlspecialchars($item['image_path']) ?>"
                                alt="<?= htmlspecialchars($item['title']) ?>" class="w-full h-full object-cover slow-zoom-img">

                            <!-- CATEGORY & CLIENT PROOF BADGES (TOP LEFT) -->
                            <div class="absolute top-2.5 left-2.5 flex flex-col items-start gap-1">
                                <span
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-black/75 backdrop-blur-md text-amber-400 border border-amber-500/30">
                                    <?= htmlspecialchars($item['category']) ?>
                                </span>
                                <span
                                    class="px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider bg-emerald-950/80 backdrop-blur-md text-emerald-400 border border-emerald-500/30 flex items-center space-x-1">
                                    <svg class="w-2.5 h-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Client Proof</span>
                                </span>
                            </div>

                            <!-- FEATURED / DURATION BADGE (TOP RIGHT) -->
                            <div class="absolute top-2.5 right-2.5 flex flex-col items-end gap-1">
                                <?php if ($item['is_featured']): ?>
                                    <span
                                        class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider bg-amber-500 text-slate-950 shadow-md animate-slow-shimmer flex items-center space-x-1">
                                        <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        <span>Featured</span>
                                    </span>
                                <?php endif; ?>
                                <span
                                    class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-black/60 backdrop-blur-md text-white flex items-center space-x-1">
                                    <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span><?= $item['est_duration_mins'] ?>m</span>
                                </span>
                            </div>

                            <!-- QUICK HOVER OVERLAY WITH SLOW FADE -->
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex items-end p-3">
                                <span class="text-white text-xs font-bold flex items-center space-x-1">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>Click to View & Avail</span>
                                </span>
                            </div>
                        </a>

                        <!-- CARD BODY / DETAILS -->
                        <div class="p-3.5 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
                            <div class="space-y-1">
                                <!-- ARTIST ATTRIBUTION -->
                                <div class="flex items-center space-x-1.5 text-[11px] text-slate-500 dark:text-zinc-400">
                                    <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span class="truncate font-semibold"><?= htmlspecialchars($item['artist_name']) ?></span>
                                    <span
                                        class="text-[9px] uppercase px-1 py-0.2 rounded bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400"><?= $item['uploader_role'] ?></span>
                                </div>

                                <!-- TITLE -->
                                <h3
                                    class="font-bold text-sm sm:text-base text-slate-900 dark:text-white line-clamp-2 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-300">
                                    <a href="avail.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                                </h3>

                                <!-- SHORT DESCRIPTION -->
                                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-zinc-400 line-clamp-2">
                                    <?= htmlspecialchars($item['description'] ?: 'Original bespoke tattoo artwork. Ready for transfer stencil and custom skin placement.') ?>
                                </p>
                            </div>

                            <!-- CATEGORY & CUSTOM CLIENT QUOTE (NO STOREFRONT PRICE) -->
                            <div class="pt-2 border-t border-slate-100 dark:border-zinc-800 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 dark:text-zinc-500 font-semibold block uppercase">Pricing</span>
                                        <span class="text-xs sm:text-sm font-black text-amber-600 dark:text-amber-400">Consultation Quote</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold block uppercase font-mono">Client Design</span>
                                        <span class="text-[11px] font-bold text-slate-500 dark:text-zinc-400">₱0 Online Fee</span>
                                    </div>
                                </div>

                                <!-- PROMINENT CLICK-WORTHY AVAIL & DESIGN BUTTON -->
                                <a href="avail.php?id=<?= $item['id'] ?>"
                                    class="w-full py-2 sm:py-2.5 px-2 sm:px-3 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-[11px] sm:text-xs uppercase tracking-wider shadow-md shadow-amber-500/25 transition-all flex items-center justify-center space-x-1 sm:space-x-1.5 active:scale-95 text-center">
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-950 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span><span class="sm:hidden">Avail Design</span><span class="hidden sm:inline">Avail & Decide Design</span></span>
                                </a>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- STUDIO WORKFLOW EXPLAINER (FOOTER INFO) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4">
            <div
                class="bg-white dark:bg-zinc-900/60 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 text-xs space-y-1.5 shadow-sm">
                <div
                    class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">
                    1</div>
                <h4 class="font-bold text-slate-900 dark:text-white">Choose Category & Custom Design</h4>
                <p class="text-slate-500 dark:text-zinc-400">Select any tattoo category or proof, upload your own custom artwork or ideas, and preview placement on our 2D mannequin.</p>
            </div>
            <div
                class="bg-white dark:bg-zinc-900/60 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 text-xs space-y-1.5 shadow-sm">
                <div
                    class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold">
                    2</div>
                <h4 class="font-bold text-slate-900 dark:text-white">Free Consultation Reservation</h4>
                <p class="text-slate-500 dark:text-zinc-400">Lock in your artist chair with zero online payment. Your final tattoo price is quoted transparently with your artist at the studio.</p>
            </div>
            <div
                class="bg-white dark:bg-zinc-900/60 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 text-xs space-y-1.5 shadow-sm">
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold">
                    3</div>
                <h4 class="font-bold text-slate-900 dark:text-white">Digital Health Consent Waiver</h4>
                <h4 class="font-bold text-slate-900 dark:text-white">Sit in the Chair</h4>
                <p class="text-slate-500 dark:text-zinc-400">Once your artist verifies the receipt, your chair time is
                    locked. You can also customize anatomical placement on the 2D Visualizer anytime.</p>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer
        class="py-6 px-4 sm:px-8 border-t border-slate-200 dark:border-zinc-900 bg-white dark:bg-zinc-950 text-center text-xs text-slate-500 dark:text-zinc-500 transition-colors mt-8">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <div>
                &copy; <?= date('Y') ?> <strong class="text-slate-700 dark:text-zinc-300">Aengelos Tattoo
                    Studio</strong>. Shopee-Style E-Commerce Flash Architecture.
            </div>
            <div class="flex items-center space-x-4 text-xs font-semibold">
                <a href="index.php" class="hover:text-amber-500 transition-colors">Catalog</a>
                <?php if ($currentUser): ?>
                    <a href="<?= $portalUrl ?>" class="hover:text-amber-500 transition-colors">My Portal</a>
                <?php else: ?>
                    <button type="button" onclick="openLoginModal()" class="hover:text-amber-500 transition-colors">Sign In
                        Modal</button>
                    <a href="register.php" class="hover:text-amber-500 transition-colors">Create Account</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>


    <!-- ========================================================================= -->
    <!-- MODAL 1: DEDICATED LOGIN MODAL (MANDATORY REQUIREMENT)                   -->
    <!-- ========================================================================= -->
    <div id="loginModal"
        class="hidden fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
        <div
            class="modal-slow-enter bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-3xl max-w-md w-full p-5 sm:p-8 space-y-5 shadow-2xl relative transition-colors max-h-[95vh] overflow-y-auto">

            <!-- CLOSE BUTTON -->
            <button type="button" onclick="closeLoginModal()" aria-label="Close modal"
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 dark:hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- HEADER -->
            <div class="text-left">
                <span
                    class="text-[10px] font-bold text-amber-600 dark:text-amber-500 uppercase tracking-widest block mb-1">Unified
                    Sign-In Modal</span>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Sign In to Your Portal</h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">Access for Clients, Tattoo Artists, and
                    Studio Administrators.</p>
            </div>

            <div id="loginModalNotice"
                class="hidden p-3 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-500/40 text-amber-800 dark:text-amber-300 text-xs font-semibold">
            </div>

            <form action="api.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="return_to" id="loginReturnTo" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1.5">Email
                        Address</label>
                    <input type="email" name="email" id="modalLoginEmail" required placeholder="name@aengelos.com"
                        class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-sm text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1.5">Password</label>
                    <input type="password" name="password" id="modalLoginPassword" required placeholder="••••••••"
                        class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-sm text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black rounded-xl shadow-lg shadow-amber-500/20 text-xs sm:text-sm uppercase tracking-wider transition-all">
                    Sign In to Portal
                </button>
            </form>

            <!-- DEMO 1-CLICK TEST PERSONAS -->
            <div class="pt-4 border-t border-slate-200 dark:border-zinc-800">
                <span
                    class="text-[11px] font-bold text-slate-500 dark:text-zinc-500 uppercase tracking-wider block mb-2">Quick
                    Test Personas (1-Click Fill):</span>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <button type="button" onclick="fillModalLogin('admin@aengelos.com', 'password123')"
                        class="p-2 bg-slate-50 dark:bg-zinc-950 hover:bg-slate-100 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-xl text-left transition-colors shadow-sm">
                        <strong class="text-red-600 dark:text-red-400 block font-bold text-[11px]">Admin</strong>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-500">Aengelo</span>
                    </button>
                    <button type="button" onclick="fillModalLogin('artist@aengelos.com', 'password123')"
                        class="p-2 bg-slate-50 dark:bg-zinc-950 hover:bg-slate-100 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-xl text-left transition-colors shadow-sm">
                        <strong class="text-amber-600 dark:text-amber-400 block font-bold text-[11px]">Artist</strong>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-500">Ricky</span>
                    </button>
                    <button type="button" onclick="fillModalLogin('client@aengelos.com', 'password123')"
                        class="p-2 bg-slate-50 dark:bg-zinc-950 hover:bg-slate-100 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-xl text-left transition-colors shadow-sm">
                        <strong
                            class="text-emerald-600 dark:text-emerald-400 block font-bold text-[11px]">Client</strong>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-500">Juan</span>
                    </button>
                </div>
            </div>

            <!-- SWITCH TO SIGN UP -->
            <div class="pt-3 border-t border-slate-200 dark:border-zinc-800 text-center">
                <p class="text-xs text-slate-600 dark:text-zinc-400 mb-2">
                    New customer or first-time client?
                </p>
                <a href="register.php"
                    class="inline-flex items-center justify-center space-x-1.5 w-full py-2 px-4 rounded-xl border border-amber-500/30 hover:border-amber-500 bg-amber-500/5 hover:bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-xs uppercase tracking-wider transition-all">
                    <span>Create a Client Account</span>
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>





    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Current pending design for guest login preservation
        let pendingDesignToAvail = null;

        // Theme Toggle
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

        // Modal Controls
        function openLoginModal(notice = '') {
            const noticeEl = document.getElementById('loginModalNotice');
            if (notice) {
                noticeEl.textContent = notice;
                noticeEl.classList.remove('hidden');
            } else {
                noticeEl.classList.add('hidden');
            }
            document.getElementById('loginModal').classList.remove('hidden');
        }

        function closeLoginModal() {
            document.getElementById('loginModal').classList.add('hidden');
        }

        function fillModalLogin(email, pass) {
            document.getElementById('modalLoginEmail').value = email;
            document.getElementById('modalLoginPassword').value = pass;
        }
        // Close login modal on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLoginModal();
            }
        });

        // Auto open login modal if requested via URL query (?open_login=1)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_login') === '1') {
            openLoginModal(urlParams.get('error') || '');
        }
    </script>
</body>

</html>