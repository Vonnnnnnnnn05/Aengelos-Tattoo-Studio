<?php
// public/avail.php - Dedicated Full-Page Tattoo Flash & Custom Design Reservation Portal
require_once __DIR__ . '/../config/db.php';

$currentUser = currentUser();
$catalogId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$initialCategory = trim($_GET['category'] ?? '');

// 1. Fetch Reference Tattoo Design if ID provided
$item = null;
if ($catalogId > 0) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name AS artist_name, u.role AS uploader_role, u.phone AS artist_phone, u.email AS artist_email
        FROM tattoo_catalog c
        JOIN users u ON c.uploader_id = u.id
        WHERE c.id = ? AND c.status != 'archived'
    ");
    $stmt->execute([$catalogId]);
    $item = $stmt->fetch();
}

if ($item) {
    $selectedCategory = $item['category'];
    $defaultTitle = $item['title'];
    $defaultArtistId = $item['uploader_id'];
} else {
    $selectedCategory = $initialCategory ?: 'Minimalist';
    $defaultTitle = '';
    $defaultArtistId = 0;
}

// 2. Fetch Artists for Selection
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
    <title>Avail & Decide Custom Design — Aengelos Tattoo Studio</title>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .placement-pill.active {
            background-color: rgba(245, 158, 11, 0.18) !important;
            border-color: #f59e0b !important;
            color: #d97706 !important;
            box-shadow: 0 4px 14px 0 rgba(245, 158, 11, 0.2);
        }

        .dark .placement-pill.active {
            color: #fbbf24 !important;
        }

        .side-btn.active {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            color: #0f172a !important;
            border-color: #f59e0b !important;
            font-weight: 800;
        }
    </style>
</head>

<body
    class="bg-slate-50 text-slate-800 dark:bg-zinc-950 dark:text-zinc-100 min-h-screen transition-colors duration-200">

    <!-- MASTER FIXED TOP HEADER -->
    <?php 
    $navContext = 'avail';
    require_once __DIR__ . '/navbar.php'; 
    ?>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <!-- SYSTEM NOTIFICATIONS -->
        <?php if (!empty($_GET['error'])): ?>
            <div
                class="mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-500/40 text-red-800 dark:text-red-300 text-xs sm:text-sm flex items-center space-x-3 shadow-sm">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['msg'])): ?>
            <div
                class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center space-x-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?= htmlspecialchars($_GET['msg']) ?></span>
            </div>
        <?php endif; ?>

        <!-- PAGE HEADER (CENTERED, CLEAN) -->
        <div class="mb-8 text-center max-w-2xl mx-auto pb-4 border-b border-slate-200 dark:border-zinc-800">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-black uppercase tracking-wider mb-2 border border-amber-500/20">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Custom Tattoo Reservation</span>
            </div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                Avail & Decide Your Design
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mt-1.5">
                Choose your style category, specify or upload your custom design, test anatomical placement on the 2D mannequin, and reserve your studio session with zero upfront charge.
            </p>
        </div>

        <!-- MAIN SINGLE-COLUMN WORKFLOW CONTAINER -->
        <div class="max-w-3xl mx-auto space-y-6">

            <?php if (!$currentUser): ?>
                <!-- GUEST SIGN-IN / QUICK ACCESS CARD -->
                <div id="authSection"
                    class="bg-white dark:bg-zinc-900 rounded-3xl border-2 border-amber-500/40 p-6 sm:p-8 space-y-6 shadow-xl relative overflow-hidden">
                    <div
                        class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none">
                    </div>

                    <div class="space-y-1">
                        <span
                            class="text-[10px] font-black text-amber-600 dark:text-amber-500 uppercase tracking-widest block">Client
                            Identification Required</span>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                            Sign In to Reserve This Tattoo
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">
                            Please sign in to lock in your booking and generate your digital consent waiver.
                        </p>
                    </div>

                    <!-- 1-CLICK DEMO ACCOUNTS -->
                    <div
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 space-y-2.5">
                        <span
                            class="text-[11px] font-bold text-slate-600 dark:text-zinc-400 uppercase tracking-wider block">1-Click
                            Fast Sign-In:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <button type="button" onclick="fillQuickLogin('client@aengelos.com', 'password123')"
                                class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 hover:border-amber-500 text-left transition-all shadow-sm">
                                <span class="block text-xs font-bold text-slate-900 dark:text-white">Juan Dela
                                    Cruz</span>
                                <span class="block text-[10px] text-amber-600 dark:text-amber-400">Client Profile</span>
                            </button>
                            <button type="button" onclick="fillQuickLogin('artist@aengelos.com', 'password123')"
                                class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 hover:border-amber-500 text-left transition-all shadow-sm">
                                <span class="block text-xs font-bold text-slate-900 dark:text-white">Ricky Sta. Ana</span>
                                <span class="block text-[10px] text-slate-500 dark:text-zinc-400">Master Artist</span>
                            </button>
                            <button type="button" onclick="fillQuickLogin('admin@aengelos.com', 'password123')"
                                class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 hover:border-amber-500 text-left transition-all shadow-sm">
                                <span class="block text-xs font-bold text-slate-900 dark:text-white">Studio Admin</span>
                                <span class="block text-[10px] text-slate-500 dark:text-zinc-400">Master Control</span>
                            </button>
                        </div>
                    </div>

                    <!-- SIGN IN FORM -->
                    <form action="api.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="return_to" value="avail.php<?= $catalogId > 0 ? '?id=' . $catalogId : '' ?>">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">Email
                                    Address:</label>
                                <input type="email" name="email" id="quickEmail" required
                                    placeholder="name@aengelos.com"
                                    class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">Password:</label>
                                <input type="password" name="password" id="quickPassword" required
                                    placeholder="••••••••"
                                    class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                            <button type="submit"
                                class="w-full sm:w-auto flex-1 py-3.5 px-6 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs sm:text-sm uppercase tracking-wider shadow-lg shadow-amber-500/25 transition-all">
                                Sign In & Continue Booking
                            </button>
                            <a href="register.php"
                                class="w-full sm:w-auto py-3.5 px-6 rounded-xl border border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500 text-center text-xs font-bold transition-colors">
                                Create New Client Account
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- DEDICATED BOOKING WORKFLOW FORM -->
            <form id="availBookingForm" action="api.php" method="POST" enctype="multipart/form-data"
                onsubmit="return handleBookingSubmission(event)" class="space-y-6">
                <input type="hidden" name="action" value="avail_catalog_item">
                <input type="hidden" name="catalog_id" value="<?= $catalogId > 0 ? $catalogId : '' ?>">
                <input type="hidden" name="deposit_amount" value="0.00">
                <input type="hidden" name="tattoo_category" id="tattooCategoryInput" value="<?= htmlspecialchars($selectedCategory) ?>">
                <input type="hidden" name="signature_base64" id="signature_base64">
                <input type="hidden" name="mockup_base64" id="mockup_base64">
                <input type="hidden" name="canvas_json" id="canvas_json">

                <!-- STEP 1: TATTOO CATEGORIES & CLIENT CUSTOM DESIGN -->
                <div
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-200 dark:border-zinc-800 p-5 sm:p-7 space-y-5 shadow-sm">
                    <div class="flex items-center space-x-2.5">
                        <span
                            class="w-7 h-7 rounded-lg bg-amber-500 text-slate-950 font-black text-xs flex items-center justify-center">1</span>
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white">Tattoo Category & Your Custom Design</h3>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">Select your tattoo style category and decide your own custom design details.</p>
                        </div>
                    </div>

                    <!-- CATEGORY SELECTOR PILLS -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300">
                            Select Tattoo Style Category:
                        </label>
                        <div class="flex flex-wrap gap-2" id="categoryPillsContainer">
                            <?php 
                            $studioCategories = [
                                'Flash Art',
                                'Japanese / Irezumi',
                                'Minimalist',
                                'Realism',
                                'Custom Design',
                                'Traditional / Old School',
                                'Fine Line & Geometric'
                            ];
                            foreach ($studioCategories as $scat): 
                                $isCurCat = (strcasecmp($selectedCategory, $scat) === 0);
                            ?>
                                <button type="button" onclick="selectTattooCategory('<?= addslashes($scat) ?>', this)"
                                    class="cat-pill px-3.5 py-2 rounded-xl text-xs font-bold border transition-all <?= $isCurCat ? 'bg-amber-500 text-slate-950 border-amber-500 shadow-md shadow-amber-500/20' : 'bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300 border-slate-300 dark:border-zinc-700' ?>">
                                    <?= htmlspecialchars($scat) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- CLIENT CUSTOM DESIGN TITLE / CONCEPT -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">
                            Your Custom Design Title or Concept Name: <span class="text-amber-500">*</span>
                        </label>
                        <input type="text" name="design_title" id="designTitleInput" required
                            value="<?= htmlspecialchars($defaultTitle) ?>"
                            placeholder="e.g. Minimalist Mountain & Moon, Phoenix Japanese Sleeve, Sacred Geometry"
                            class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 font-semibold focus:border-amber-500 focus:outline-none">
                        <span class="text-[10px] text-slate-400 dark:text-zinc-500 mt-1 block">Give your design or idea a descriptive name for your consultation.</span>
                    </div>

                    <!-- UPLOAD YOUR OWN CUSTOM ARTWORK / REFERENCE PHOTO -->
                    <div class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/30 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-900 dark:text-white">
                                Upload Your Custom Design / Sketch Artwork (Optional):
                            </label>
                            <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Canvas Mockup</span>
                        </div>
                        <input type="file" name="custom_artwork" id="customArtworkInput" accept="image/png,image/jpeg,image/webp"
                            onchange="loadCustomArtworkToCanvas(this)"
                            class="block w-full text-xs text-slate-600 dark:text-zinc-400 file:mr-3 file:py-2.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-400 cursor-pointer">
                        <p class="text-[10px] text-slate-500 dark:text-zinc-400 leading-normal">
                            Got an artwork file, sketch, or stencil? Upload it here to preview it interactively on the 2D mannequin below. (The mannequin mockup is strictly for your custom uploaded design, not finished human photos).
                        </p>
                        <!-- UPLOADED ARTWORK PREVIEW THUMBNAIL -->
                        <div id="customArtworkPreviewContainer" class="hidden p-2.5 rounded-xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 flex items-center space-x-3 shadow-xs">
                            <img id="customArtworkPreviewImg" src="" alt="Custom Artwork Preview" class="w-14 h-14 object-contain rounded-lg bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 p-1">
                            <div>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>Custom Artwork Ready</span>
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-zinc-400 block">Rendered on 2D Mannequin Visualizer in Step 2 below</span>
                            </div>
                        </div>
                    </div>

                    <!-- CLIENT DESIGN NOTES & SPECIFICATIONS -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">
                            Design Notes & Specifications:
                        </label>
                        <textarea name="design_notes" id="designNotesInput" rows="2"
                            placeholder="Describe preferred size (e.g. 4x3 inches), black & grey or color, linework thickness, lettering, or any modifications..."
                            class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 placeholder-slate-400 dark:placeholder-zinc-600 focus:border-amber-500 focus:outline-none"></textarea>
                    </div>
                </div>

                <!-- STEP 2: TARGET BODY PLACEMENT CARD -->
                <div id="placementStepAnchor"
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-200 dark:border-zinc-800 p-5 sm:p-7 space-y-4 shadow-sm">
                    <div class="flex items-center space-x-2.5">
                        <span
                            class="w-7 h-7 rounded-lg bg-amber-500 text-slate-950 font-black text-xs flex items-center justify-center">2</span>
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white">Target Body
                                Placement & 2D Mannequin Mockup</h3>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">Select your placement region
                                and interactively position this tattoo on the 2D mannequin mockup.</p>
                        </div>
                    </div>

                    <!-- 12 EMOJI QUICK-PLACEMENT PILLS -->
                    <div class="space-y-1.5">
                        <span
                            class="text-[10px] uppercase font-bold text-slate-500 dark:text-zinc-400 block tracking-wider">Quick
                            1-Tap Placement Options:</span>
                        <div class="flex flex-wrap gap-1.5" id="placementPillsContainer">
                            <button type="button" onclick="selectPlacement('Forearm', this)"
                                class="placement-pill active px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all">
                                🦾 Forearm
                            </button>
                            <button type="button" onclick="selectPlacement('Bicep / Arm', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                💪 Bicep
                            </button>
                            <button type="button" onclick="selectPlacement('Shoulder / Deltoid', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                🥋 Shoulder
                            </button>
                            <button type="button" onclick="selectPlacement('Chest / Pectoral', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                🫀 Chest
                            </button>
                            <button type="button" onclick="selectPlacement('Upper / Full Back', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                🛡️ Back
                            </button>
                            <button type="button" onclick="selectPlacement('Ribs / Torso Side', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                🩻 Ribs
                            </button>
                            <button type="button" onclick="selectPlacement('Thigh / Upper Leg', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                🦵 Thigh
                            </button>
                            <button type="button" onclick="selectPlacement('Calf / Lower Leg', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                🦿 Calf
                            </button>
                            <button type="button" onclick="selectPlacement('Wrist / Hand', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                ⌚ Wrist
                            </button>
                            <button type="button" onclick="selectPlacement('Ankle / Foot', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                👟 Ankle
                            </button>
                            <button type="button" onclick="selectPlacement('Neck / Behind Ear', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                👤 Neck
                            </button>
                            <button type="button" onclick="selectPlacement('Custom Placement', this)"
                                class="placement-pill px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all bg-slate-50 dark:bg-zinc-950 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:border-amber-500">
                                ✨ Custom
                            </button>
                        </div>
                    </div>

                    <!-- BODY REGION SELECT & SIDE / ORIENTATION -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">Selected
                                Body Region:</label>
                            <select name="body_region" id="bodyRegionInput"
                                onchange="syncPlacementFromDropdown(this.value)" required
                                class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 font-semibold focus:border-amber-500 focus:outline-none">
                                <option value="Forearm">Forearm (Inner / Outer)</option>
                                <option value="Bicep / Arm">Bicep / Upper Arm</option>
                                <option value="Shoulder / Deltoid">Shoulder / Deltoid</option>
                                <option value="Chest / Pectoral">Chest / Pectoral</option>
                                <option value="Upper / Full Back">Upper / Full Back</option>
                                <option value="Ribs / Torso Side">Ribs / Torso Side</option>
                                <option value="Thigh / Upper Leg">Thigh / Upper Leg</option>
                                <option value="Calf / Lower Leg">Calf / Lower Leg</option>
                                <option value="Wrist / Hand">Wrist / Hand</option>
                                <option value="Ankle / Foot">Ankle / Foot</option>
                                <option value="Neck / Behind Ear">Neck / Behind Ear</option>
                                <option value="Custom Placement">Custom / Other Placement</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">Side /
                                Orientation:</label>
                            <div class="grid grid-cols-3 gap-1.5">
                                <button type="button" onclick="selectSide('Left Side', this)"
                                    class="side-btn active py-2.5 px-2 rounded-xl text-center font-bold text-xs border border-slate-300 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-950 text-slate-700 dark:text-zinc-300 hover:border-amber-500 transition-all">Left</button>
                                <button type="button" onclick="selectSide('Right Side', this)"
                                    class="side-btn py-2.5 px-2 rounded-xl text-center font-bold text-xs border border-slate-300 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-950 text-slate-700 dark:text-zinc-300 hover:border-amber-500 transition-all">Right</button>
                                <button type="button" onclick="selectSide('Center / Symmetrical', this)"
                                    class="side-btn py-2.5 px-2 rounded-xl text-center font-bold text-xs border border-slate-300 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-950 text-slate-700 dark:text-zinc-300 hover:border-amber-500 transition-all">Center</button>
                            </div>
                        </div>
                    </div>

                    <!-- LIVE 2D ANATOMICAL CANVAS MOCKUP (FABRIC.JS 2D VISUALIZER) -->
                    <div
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span
                                    class="font-bold text-xs text-slate-900 dark:text-white uppercase tracking-wider">Live
                                    2D Anatomical Mockup Visualizer</span>
                            </div>
                            <span
                                class="text-[10px] text-amber-600 dark:text-amber-400 font-bold bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">Fabric.js
                                Mockup</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                            Upload your custom design sketch in Step 1 above to preview anatomical placement on this 2D mannequin outline. Drag, zoom, and rotate your custom artwork!
                        </p>

                        <!-- CANVAS CONTAINER -->
                        <div
                            class="flex flex-col items-center justify-center bg-slate-100 dark:bg-zinc-900/80 p-2 sm:p-3 rounded-xl border border-slate-200 dark:border-zinc-800 shadow-inner">
                            <div id="canvasViewport"
                                class="relative w-full max-w-[340px] flex justify-center overflow-hidden rounded-lg shadow-sm"
                                style="touch-action: none;">
                                <canvas id="tattooMockupCanvas" width="340" height="380" class="block"></canvas>
                                <!-- AWAITING UPLOAD OVERLAY (Only hidden once user uploads a custom design file) -->
                                <div id="mockupEmptyState" class="absolute inset-0 flex flex-col items-center justify-center p-4 text-center pointer-events-none bg-slate-50/70 dark:bg-zinc-950/70 backdrop-blur-[1px] rounded-lg">
                                    <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 text-amber-500 flex items-center justify-center mb-2 shadow-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    </div>
                                    <span class="text-xs font-black text-slate-800 dark:text-zinc-100">Awaiting Custom Design Upload</span>
                                    <span class="text-[10px] text-slate-500 dark:text-zinc-400 mt-1 max-w-[210px] leading-tight">Upload your sketch or stencil in Step 1 to test placement on this mannequin</span>
                                </div>
                            </div>
                        </div>

                        <!-- MOCKUP CONTROLS -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 text-xs">
                            <div>
                                <label
                                    class="text-slate-600 dark:text-zinc-400 block mb-1 text-[11px] font-semibold">Tattoo
                                    Skin Opacity: <span id="opacityVal">90%</span></label>
                                <input type="range" id="opacitySlider" min="20" max="100" value="90"
                                    class="w-full accent-amber-500">
                            </div>
                            <div class="flex items-center justify-end space-x-1.5">
                                <button type="button" onclick="nudgeMockup('zoomIn')" title="Zoom In"
                                    class="p-2 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 hover:border-amber-500 font-bold text-xs shadow-sm flex items-center space-x-1 text-slate-800 dark:text-zinc-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Zoom In</span>
                                </button>
                                <button type="button" onclick="nudgeMockup('zoomOut')" title="Zoom Out"
                                    class="p-2 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 hover:border-amber-500 font-bold text-xs shadow-sm flex items-center space-x-1 text-slate-800 dark:text-zinc-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 12H4" />
                                    </svg>
                                    <span>Zoom Out</span>
                                </button>
                                <button type="button" onclick="nudgeMockup('rotate')" title="Rotate 45°"
                                    class="p-2 rounded-xl bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 hover:border-amber-500 font-bold text-xs shadow-sm flex items-center space-x-1 text-slate-800 dark:text-zinc-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Rotate</span>
                                </button>
                                <button type="button" onclick="resetMockupArtwork()" title="Reset Placement"
                                    class="p-2 rounded-xl bg-slate-200 dark:bg-zinc-800 hover:bg-amber-500 hover:text-slate-950 font-bold text-xs shadow-sm text-slate-800 dark:text-zinc-200">
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SPECIFIC PLACEMENT NOTES -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">Specific
                            Placement Details & Instructions:</label>
                        <input type="text" name="placement_notes" id="placementNotesInput"
                            placeholder="e.g. Left inner forearm, 2 inches below elbow crease, facing inwards"
                            class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 placeholder-slate-400 dark:placeholder-zinc-600 focus:border-amber-500 focus:outline-none">
                        <span class="text-[10px] text-slate-400 dark:text-zinc-500 mt-1 block">Specify distance from
                            joints, horizontal/vertical orientation, or angle to help your artist prepare the
                            stencil.</span>
                    </div>
                </div>

                <!-- STEP 3: PREFERRED ARTIST & SCHEDULE SELECTION CARD -->
                <div
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-200 dark:border-zinc-800 p-5 sm:p-7 space-y-4 shadow-sm">
                    <div class="flex items-center space-x-2.5">
                        <span
                            class="w-7 h-7 rounded-lg bg-amber-500 text-slate-950 font-black text-xs flex items-center justify-center">3</span>
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white">Resident Artist & Appointment Schedule</h3>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">Lock in your preferred studio chair date and resident tattoo artist.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- ARTIST SELECT -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">Tattoo
                                Artist:</label>
                            <select name="artist_id" required
                                class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 font-semibold focus:border-amber-500 focus:outline-none">
                                <?php foreach ($artists as $art): ?>
                                    <option value="<?= $art['id'] ?>" <?= ($defaultArtistId > 0 && $art['id'] == $defaultArtistId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($art['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- DATE PICKER -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">Session
                                Date:</label>
                            <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>"
                                value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required
                                class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 font-semibold focus:border-amber-500 focus:outline-none">
                        </div>

                        <!-- TIME PICKER -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 mb-1">Session
                                Time Window:</label>
                            <select name="appointment_time" required
                                class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-xs sm:text-sm text-slate-900 dark:text-zinc-200 font-semibold focus:border-amber-500 focus:outline-none">
                                <option value="10:00">10:00 AM (Morning Slot)</option>
                                <option value="13:00" selected>01:00 PM (Afternoon Slot)</option>
                                <option value="15:30">03:30 PM (Late Afternoon Slot)</option>
                                <option value="18:00">06:00 PM (Evening Slot)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: STUDIO CONSULTATION BOOKING (ZERO UPFRONT CHARGE) -->
                <div
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-200 dark:border-zinc-800 p-5 sm:p-7 space-y-4 shadow-sm">
                    <div class="flex items-center space-x-2.5">
                        <span
                            class="w-7 h-7 rounded-lg bg-emerald-500 text-slate-950 font-black text-xs flex items-center justify-center">4</span>
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white">Studio Consultation & Booking</h3>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">Zero upfront charges. Your custom tattoo quote is determined with your artist during your consultation.</p>
                        </div>
                    </div>

                    <!-- CONSULTATION ZERO PRICE GUARANTEE -->
                    <div
                        class="p-4 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-500/30 flex items-start space-x-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-zinc-300 leading-relaxed space-y-1">
                            <strong class="text-slate-900 dark:text-white block font-bold">100% Free Consultation Booking (₱0.00 Upfront)</strong>
                            <p>You have full freedom over your design. Our artists evaluate custom designs, sizing, and stencils at the studio to give you an honest, transparent quotation.</p>
                        </div>
                    </div>

                    <!-- OPTIONAL GCASH RESERVATION (IF CLIENT WISHES TO LEAVE AN ADVANCE DEPOSIT) -->
                    <details class="rounded-2xl border border-slate-200 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-950/50 overflow-hidden transition-all group">
                        <summary class="p-3.5 text-xs font-bold text-slate-700 dark:text-zinc-300 cursor-pointer flex items-center justify-between select-none hover:text-amber-500">
                            <span class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                <span>Optional: Provide GCash Reference if you have a priority studio voucher</span>
                            </span>
                            <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="p-4 pt-1 border-t border-slate-200 dark:border-zinc-800/80 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-zinc-300 mb-1">GCash Reference No. (Optional):</label>
                                    <input type="text" name="payment_reference" id="paymentReferenceInput" placeholder="Optional (e.g. 902184920194)"
                                        class="w-full bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-xs text-slate-900 dark:text-zinc-200 font-mono focus:border-amber-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-zinc-300 mb-1">Receipt Screenshot (Optional):</label>
                                    <input type="file" name="receipt_image" id="receiptImageInput" accept="image/*" onchange="previewReceipt(this)"
                                        class="block w-full text-xs text-slate-600 dark:text-zinc-400 file:mr-3 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-slate-200 dark:file:bg-zinc-800 file:text-slate-800 dark:file:text-zinc-200 cursor-pointer">
                                    <div id="receiptPreviewContainer" class="hidden mt-2 p-1.5 rounded-lg bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 flex items-center space-x-2">
                                        <img id="receiptPreviewImg" src="" alt="Receipt" class="w-10 h-10 object-cover rounded-md">
                                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Screenshot attached</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>
                </div>

                <!-- STEP 5: HEALTH QUESTIONNAIRE & DIGITAL WAIVER CARD -->
                <div
                    class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-200 dark:border-zinc-800 p-5 sm:p-7 space-y-4 shadow-sm">
                    <div class="flex items-center space-x-2.5">
                        <span
                            class="w-7 h-7 rounded-lg bg-blue-500 text-white font-black text-xs flex items-center justify-center">5</span>
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white">Medical Consent & Digital Signature</h3>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">Required legal health disclosures and timestamped digital waiver signature.</p>
                        </div>
                    </div>

                    <!-- HEALTH QUESTIONNAIRE -->
                    <div
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Skin,
                                    Latex, or Pigment Allergies:</label>
                                <input type="text" name="skin_allergies" value="None"
                                    class="w-full bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-xs text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-700 dark:text-zinc-300 mb-1">Pre-existing
                                    Medical Conditions:</label>
                                <input type="text" name="medical_conditions" value="None"
                                    class="w-full bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 rounded-xl p-2.5 text-xs text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none">
                            </div>
                        </div>

                        <label class="flex items-center space-x-2 cursor-pointer pt-1">
                            <input type="checkbox" name="keloid_history" value="1"
                                class="rounded text-amber-500 focus:ring-amber-500 w-4 h-4">
                            <span class="text-xs text-slate-700 dark:text-zinc-300 font-medium">I have a personal
                                history of keloid or hypertrophic scarring</span>
                        </label>
                    </div>

                    <!-- TERMS DISCLOSURE -->
                    <div
                        class="p-3.5 rounded-2xl bg-slate-100 dark:bg-zinc-950 text-[11px] text-slate-600 dark:text-zinc-400 leading-relaxed border border-slate-200 dark:border-zinc-800">
                        <strong>Informed Consent Declaration:</strong> I certify that I am at least 18 years of age
                        and not under the influence of drugs or alcohol. I acknowledge that tattooing involves skin
                        puncture and permanent pigmentation, and I agree to follow the studio's professional
                        aftercare instructions.
                    </div>

                    <!-- TOUCHSCREEN SIGNATURE PAD -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300">
                                Sign Here with Touchscreen or Mouse: <span class="text-red-500">*</span>
                            </label>
                            <button type="button" onclick="clearSignaturePad()"
                                class="text-[11px] font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                Clear Signature
                            </button>
                        </div>

                        <div
                            class="border-2 border-dashed border-slate-300 dark:border-zinc-700 rounded-2xl bg-white dark:bg-zinc-950 p-2 shadow-inner">
                            <canvas id="signatureCanvas"
                                class="w-full h-32 touch-none cursor-crosshair rounded-xl block bg-white dark:bg-zinc-950"></canvas>
                        </div>
                        <span class="text-[10px] text-slate-400 dark:text-zinc-500 block">Sign above to authenticate
                            your digital reservation waiver.</span>
                    </div>
                </div>

                <!-- SUBMIT ACTION BUTTON -->
                <div class="space-y-3 pt-2">
                    <?php if ($currentUser): ?>
                        <button type="submit" id="submitBookingBtn"
                            class="w-full py-4 sm:py-5 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-sm sm:text-base uppercase tracking-wider shadow-xl shadow-amber-500/30 transition-all flex items-center justify-center space-x-2 active:scale-[0.99] cursor-pointer">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Confirm & Reserve Tattoo Consultation (Zero Upfront Fee)</span>
                        </button>
                    <?php else: ?>
                        <a href="#authSection"
                            class="w-full py-4 sm:py-5 rounded-2xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-sm sm:text-base uppercase tracking-wider shadow-xl shadow-amber-500/30 transition-all flex items-center justify-center space-x-2 text-center">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span>Sign In Above to Reserve Your Custom Tattoo</span>
                        </a>
                    <?php endif; ?>

                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400 px-1">
                        <a href="index.php"
                            class="hover:text-amber-500 transition-colors flex items-center space-x-1 font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Cancel & Return to Catalog</span>
                        </a>
                        <span>100% Encrypted & Authenticated Waiver</span>
                    </div>
                </div>

            </form>

        </div>

    </main>

    <!-- FOOTER -->
    <footer
        class="mt-16 border-t border-slate-200 dark:border-zinc-800 bg-white/80 dark:bg-zinc-950/80 py-6 px-4 text-center text-xs text-slate-500 dark:text-zinc-500">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <p>© <?= date('Y') ?> Aengelos Tattoo Studio Management System. All rights reserved.</p>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="hover:text-amber-500 transition-colors">Catalog Gallery</a>
                <a href="client_portal.php" class="hover:text-amber-500 transition-colors">Client Portal</a>
            </div>
        </div>
    </footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // 1. Theme Toggle
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon(isDark);
        }

        function updateThemeIcon(isDark) {
            const icon = document.getElementById('themeIcon');
            if (!icon) return;
            if (isDark) {
                icon.innerHTML = `<svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>`;
            } else {
                icon.innerHTML = `<svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>`;
            }
        }
        updateThemeIcon(document.documentElement.classList.contains('dark'));

        // 2. Demo Login Helper
        function fillQuickLogin(email, pass) {
            const emailInput = document.getElementById('quickEmail');
            const passInput = document.getElementById('quickPassword');
            if (emailInput && passInput) {
                emailInput.value = email;
                passInput.value = pass;
                passInput.focus();
            }
        }

        // 3. Body Placement Controls & Visualizer Sync
        function selectPlacement(region, btn) {
            const regionSelect = document.getElementById('bodyRegionInput');
            if (regionSelect) {
                regionSelect.value = region;
            }
            document.querySelectorAll('.placement-pill').forEach(p => {
                p.classList.remove('active', 'bg-amber-500/18', 'border-amber-500', 'text-amber-700', 'dark:text-amber-400');
                p.classList.add('bg-slate-50', 'dark:bg-zinc-950', 'border-slate-300', 'dark:border-zinc-700', 'text-slate-700', 'dark:text-zinc-300');
            });
            if (btn) {
                btn.classList.add('active');
                btn.classList.remove('bg-slate-50', 'dark:bg-zinc-950', 'border-slate-300', 'dark:border-zinc-700', 'text-slate-700', 'dark:text-zinc-300');
            }
            syncMannequinFromRegion(region);
        }

        function syncPlacementFromDropdown(val) {
            document.querySelectorAll('.placement-pill').forEach(p => {
                const text = p.textContent.toLowerCase();
                const target = val.split(' ')[0].toLowerCase();
                if (text.includes(target)) {
                    p.classList.add('active');
                    p.classList.remove('bg-slate-50', 'dark:bg-zinc-950', 'border-slate-300', 'dark:border-zinc-700', 'text-slate-700', 'dark:text-zinc-300');
                } else {
                    p.classList.remove('active');
                    p.classList.add('bg-slate-50', 'dark:bg-zinc-950', 'border-slate-300', 'dark:border-zinc-700', 'text-slate-700', 'dark:text-zinc-300');
                }
            });
            syncMannequinFromRegion(val);
        }

        function syncMannequinFromRegion(regionStr) {
            let manRegion = 'forearm';
            const lower = regionStr.toLowerCase();
            if (lower.includes('calf') || lower.includes('leg') || lower.includes('thigh') || lower.includes('ankle')) manRegion = 'calf';
            else if (lower.includes('shoulder') || lower.includes('neck')) manRegion = 'shoulder';
            else if (lower.includes('back') || lower.includes('ribs')) manRegion = 'back';
            else if (lower.includes('chest')) manRegion = 'chest';
            else manRegion = 'forearm';
            drawMannequinRegion(manRegion);
        }

        function selectSide(side, btn) {
            const notesInput = document.getElementById('placementNotesInput');
            if (notesInput) {
                let current = notesInput.value.trim();
                current = current.replace(/^(Left Side|Right Side|Center \/ Symmetrical|Left|Right|Center)[,\s-]*/i, '');
                notesInput.value = side + (current ? ' - ' + current : '');
            }
            document.querySelectorAll('.side-btn').forEach(b => {
                b.classList.remove('active');
            });
            if (btn) {
                btn.classList.add('active');
            }
        }

        // 4. Fabric.js 2D Anatomical Mockup Visualizer
        let visualizerCanvas = null;
        let activeTattooObj = null;
        let currentBodyOutline = null;

        function initVisualizer() {
            const canvasEl = document.getElementById('tattooMockupCanvas');
            if (!canvasEl || typeof fabric === 'undefined') return;

            visualizerCanvas = new fabric.Canvas('tattooMockupCanvas', {
                selection: false,
                preserveObjectStacking: true,
                hoverCursor: 'grab',
                moveCursor: 'grabbing'
            });

            // Draw clean anatomical mannequin outline without pasting gallery human photos
            drawMannequinRegion('forearm');

            // Responsive visualizer sizing for mobile screens
            resizeVisualizerCanvas();

            // Opacity slider
            const slider = document.getElementById('opacitySlider');
            if (slider) {
                slider.addEventListener('input', function (e) {
                    const val = e.target.value;
                    const opVal = document.getElementById('opacityVal');
                    if (opVal) opVal.textContent = val + '%';
                    if (activeTattooObj) {
                        activeTattooObj.set('opacity', parseInt(val) / 100);
                        visualizerCanvas.renderAll();
                    }
                });
            }
        }

        // Dynamic Responsive Visualizer Scaling for Mobile Devices
        function resizeVisualizerCanvas() {
            const viewport = document.getElementById('canvasViewport');
            if (!viewport || !visualizerCanvas) return;

            const availableWidth = viewport.clientWidth;
            if (availableWidth <= 0) return;

            const baseWidth = 340;
            const baseHeight = 380;

            const targetWidth = Math.min(baseWidth, availableWidth);
            const scale = targetWidth / baseWidth;
            const targetHeight = Math.round(baseHeight * scale);

            visualizerCanvas.setDimensions({
                width: targetWidth,
                height: targetHeight
            });
            visualizerCanvas.setZoom(scale);
            visualizerCanvas.renderAll();
        }

        function drawMannequinRegion(region) {
            if (!visualizerCanvas) return;
            if (currentBodyOutline) visualizerCanvas.remove(currentBodyOutline);

            const isDark = document.documentElement.classList.contains('dark');
            const options = {
                fill: isDark ? '#27272a' : '#e2e8f0',
                stroke: isDark ? '#52525b' : '#cbd5e1',
                strokeWidth: 2,
                selectable: false,
                evented: false
            };

            switch (region.toLowerCase()) {
                case 'forearm':
                    currentBodyOutline = new fabric.Path('M 120 25 L 220 25 L 195 355 L 145 355 Z', options);
                    break;
                case 'calf':
                    currentBodyOutline = new fabric.Path('M 120 25 Q 235 155 190 355 L 150 355 Q 105 155 120 25 Z', options);
                    break;
                case 'shoulder':
                    currentBodyOutline = new fabric.Circle({ radius: 105, left: 65, top: 75, ...options });
                    break;
                case 'back':
                    currentBodyOutline = new fabric.Rect({ left: 65, top: 30, width: 210, height: 320, rx: 20, ry: 20, ...options });
                    break;
                case 'chest':
                    currentBodyOutline = new fabric.Rect({ left: 55, top: 50, width: 230, height: 240, rx: 18, ry: 18, ...options });
                    break;
                default:
                    currentBodyOutline = new fabric.Path('M 120 25 L 220 25 L 195 355 L 145 355 Z', options);
            }

            visualizerCanvas.add(currentBodyOutline);
            visualizerCanvas.sendToBack(currentBodyOutline);
            visualizerCanvas.renderAll();
        }

        function nudgeMockup(action) {
            if (!activeTattooObj || !visualizerCanvas) return;
            if (action === 'zoomIn') {
                activeTattooObj.scale(activeTattooObj.scaleX * 1.15);
            } else if (action === 'zoomOut') {
                activeTattooObj.scale(Math.max(0.1, activeTattooObj.scaleX * 0.85));
            } else if (action === 'rotate') {
                activeTattooObj.rotate(((activeTattooObj.angle || 0) + 45) % 360);
            }
            activeTattooObj.setCoords();
            visualizerCanvas.renderAll();
        }

        function resetMockupArtwork() {
            if (activeTattooObj && visualizerCanvas) {
                activeTattooObj.set({ left: 105, top: 130, angle: 0 });
                activeTattooObj.scaleToWidth(130);
                activeTattooObj.setCoords();
                visualizerCanvas.renderAll();
            }
        }

        // 5. Receipt Preview
        function previewReceipt(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.getElementById('receiptPreviewImg');
                    const container = document.getElementById('receiptPreviewContainer');
                    if (img && container) {
                        img.src = e.target.result;
                        container.classList.remove('hidden');
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 6. Signature Pad & Visualizer Setup on Load
        let signaturePad = null;
        function initSignaturePad() {
            const canvas = document.getElementById('signatureCanvas');
            if (canvas) {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                const ctx = canvas.getContext('2d');
                ctx.scale(ratio, ratio);

                if (typeof SignaturePad !== 'undefined') {
                    signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgba(255, 255, 255, 0)',
                        penColor: document.documentElement.classList.contains('dark') ? '#ffffff' : '#0f172a'
                    });
                }
            }
        }

        function resizeSignaturePad() {
            const canvas = document.getElementById('signatureCanvas');
            if (!canvas || !signaturePad) return;
            const container = canvas.parentElement;
            if (!container) return;

            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const width = container.clientWidth - 16;
            if (width <= 0) return;

            const existingData = !signaturePad.isEmpty() ? signaturePad.toData() : null;

            canvas.width = width * ratio;
            canvas.height = 130 * ratio;

            const ctx = canvas.getContext('2d');
            ctx.scale(ratio, ratio);

            signaturePad.clear();
            if (existingData && existingData.length) {
                signaturePad.fromData(existingData);
            }
        }

        window.addEventListener('DOMContentLoaded', function () {
            // Initialize 2D Mockup Visualizer
            initVisualizer();
            // Initialize Signature Pad
            initSignaturePad();
        });

        // Global Responsive Event Handlers for Mobile & Orientation
        window.addEventListener('resize', () => {
            resizeVisualizerCanvas();
            resizeSignaturePad();
        });
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                resizeVisualizerCanvas();
                resizeSignaturePad();
            }, 150);
        });

        function clearSignaturePad() {
            if (signaturePad) {
                signaturePad.clear();
            }
        }

        // Tattoo Category Selection Handler
        function selectTattooCategory(categoryName, btn) {
            const catInput = document.getElementById('tattooCategoryInput');
            if (catInput) catInput.value = categoryName;

            const titleInput = document.getElementById('designTitleInput');
            if (titleInput && (!titleInput.value || titleInput.value.startsWith('Custom '))) {
                titleInput.value = 'Custom ' + categoryName + ' Tattoo';
            }

            document.querySelectorAll('.cat-pill').forEach(b => {
                b.classList.remove('bg-amber-500', 'text-slate-950', 'border-amber-500', 'shadow-md', 'shadow-amber-500/20');
                b.classList.add('bg-slate-100', 'hover:bg-slate-200', 'dark:bg-zinc-800', 'dark:hover:bg-zinc-700', 'text-slate-700', 'dark:text-zinc-300', 'border-slate-300', 'dark:border-zinc-700');
            });
            if (btn) {
                btn.classList.remove('bg-slate-100', 'hover:bg-slate-200', 'dark:bg-zinc-800', 'dark:hover:bg-zinc-700', 'text-slate-700', 'dark:text-zinc-300', 'border-slate-300', 'dark:border-zinc-700');
                btn.classList.add('bg-amber-500', 'text-slate-950', 'border-amber-500', 'shadow-md', 'shadow-amber-500/20');
            }
        }

        // Live Custom Artwork Canvas & Preview Loader (Strictly for user uploaded design)
        function loadCustomArtworkToCanvas(input) {
            if (input.files && input.files[0] && visualizerCanvas) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update preview thumbnail in Step 1
                    const previewContainer = document.getElementById('customArtworkPreviewContainer');
                    const previewImg = document.getElementById('customArtworkPreviewImg');
                    if (previewContainer && previewImg) {
                        previewImg.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                    }

                    // Hide empty state overlay on canvas
                    const emptyState = document.getElementById('mockupEmptyState');
                    if (emptyState) emptyState.classList.add('hidden');

                    // Load user's uploaded artwork on Fabric canvas
                    fabric.Image.fromURL(e.target.result, function(img) {
                        if (activeTattooObj) {
                            visualizerCanvas.remove(activeTattooObj);
                        }
                        img.scaleToWidth(140);
                        img.set({
                            left: 100,
                            top: 120,
                            opacity: 0.9,
                            cornerColor: '#d97706',
                            cornerStrokeColor: '#ffffff',
                            borderColor: '#d97706',
                            cornerSize: 12,
                            touchCornerSize: 28,
                            transparentCorners: false,
                            cornerStyle: 'circle'
                        });
                        activeTattooObj = img;
                        visualizerCanvas.add(img);
                        visualizerCanvas.setActiveObject(img);
                        visualizerCanvas.renderAll();
                    });
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 7. Form Submission Handler with Mockup & Signature Capture
        function handleBookingSubmission(e) {
            // A. Capture 2D Anatomical Visualizer Mockup (Only if user uploaded custom design artwork)
            if (visualizerCanvas && activeTattooObj) {
                const mockupData = visualizerCanvas.toDataURL('image/png');
                document.getElementById('mockup_base64').value = mockupData;
                document.getElementById('canvas_json').value = JSON.stringify(visualizerCanvas.toJSON());
            } else {
                document.getElementById('mockup_base64').value = '';
                document.getElementById('canvas_json').value = '';
            }

            // B. Capture Digital Signature
            let sigData = '';
            if (signaturePad && !signaturePad.isEmpty()) {
                sigData = signaturePad.toDataURL('image/png');
            } else {
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = 400;
                tempCanvas.height = 120;
                const tCtx = tempCanvas.getContext('2d');
                tCtx.fillStyle = '#0f172a';
                tCtx.font = 'italic bold 28px sans-serif';
                tCtx.fillText('<?= !empty($currentUser['full_name']) ? addslashes($currentUser['full_name']) : "Studio Client" ?>', 20, 65);
                tCtx.font = '11px sans-serif';
                tCtx.fillStyle = '#64748b';
                tCtx.fillText('Digitally Authenticated: <?= date("Y-m-d H:i:s") ?>', 20, 95);
                sigData = tempCanvas.toDataURL('image/png');
            }

            document.getElementById('signature_base64').value = sigData;

            const submitBtn = document.getElementById('submitBookingBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-slate-950" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Saving Visualizer Mockup & Reserving Consultation...</span>
                `;
            }
            return true;
        }
    </script>
</body>

</html>
