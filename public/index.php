<?php
// public/index.php - Dedicated Sign-In / Login Portal
require_once __DIR__ . '/../config/db.php';

// Auto-redirect if already logged in
if (isset($_SESSION['user'])) {
    $redirect = match($_SESSION['user']['role']) {
        'admin'  => 'admin_portal.php',
        'artist' => 'artist_portal.php',
        default  => 'client_portal.php'
    };
    header("Location: $redirect");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Aengelos Tattoo Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
        // Initialize theme: Default to Light Mode unless explicitly set to dark
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
    <!-- TOP HEADER -->
    <header class="py-3.5 sm:py-5 px-4 sm:px-8 border-b border-slate-200 dark:border-zinc-900 bg-white/90 dark:bg-zinc-950/80 backdrop-blur flex justify-between items-center transition-colors gap-2">
        <a href="index.php" class="flex items-center space-x-2 sm:space-x-3 min-w-0 group">
            <span class="w-3 sm:w-3.5 h-3 sm:h-3.5 rounded-full bg-amber-500 shadow-md shadow-amber-500/50 flex-shrink-0 group-hover:scale-110 transition-transform"></span>
            <span class="text-sm sm:text-xl font-bold tracking-wider sm:tracking-widest text-amber-600 dark:text-amber-400 truncate">AENGELOS TATTOO STUDIO</span>
        </a>
        <div class="flex items-center space-x-3 sm:space-x-4 flex-shrink-0">
            <a href="register.php" class="text-xs font-semibold text-slate-600 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">Create Account</a>
            <!-- THEME TOGGLE BUTTON -->
            <button type="button" onclick="toggleTheme()" class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-slate-300 dark:border-zinc-700 bg-slate-100 dark:bg-zinc-800 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors flex items-center space-x-1.5 shadow-sm">
                <span id="themeIcon" class="flex items-center"></span>
                <span id="themeLabel" class="hidden sm:inline">Dark Mode</span>
            </button>
        </div>
    </header>

    <!-- MAIN ACCESS SECTION -->
    <main class="max-w-lg mx-auto px-4 sm:px-6 py-6 sm:py-12 w-full">
        <!-- SYSTEM NOTIFICATIONS -->
        <?php if (!empty($_GET['msg'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/40 text-emerald-800 dark:text-emerald-300 text-sm flex items-center space-x-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= htmlspecialchars($_GET['msg']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-500/40 text-red-800 dark:text-red-300 text-sm flex items-center space-x-3 shadow-sm">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <!-- DEDICATED LOGIN CARD -->
        <div class="bg-white dark:bg-zinc-900/80 p-5 sm:p-8 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xl space-y-6 transition-colors">
            <div class="text-center sm:text-left">
                <span class="text-xs font-semibold text-amber-600 dark:text-amber-500 uppercase tracking-widest block mb-1">Unified Sign-In</span>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Sign In to Your Portal</h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">Authorized access for Clients, Tattoo Artists, and Studio Administrators.</p>
            </div>

            <form action="api.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="login">

                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">Email Address</label>
                    <input type="email" name="email" id="loginEmail" required placeholder="name@aengelos.com" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-sm text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Password</label>
                    </div>
                    <input type="password" name="password" id="loginPassword" required placeholder="••••••••" class="w-full bg-slate-50 dark:bg-zinc-950 border border-slate-300 dark:border-zinc-700 rounded-xl p-3 text-sm text-slate-900 dark:text-zinc-200 focus:border-amber-500 focus:outline-none transition-colors">
                </div>

                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 text-sm uppercase tracking-wider transition-all">
                    Sign In to Portal
                </button>
            </form>

            <!-- DEMO QUICK LOGIN PRESETS -->
            <div class="pt-4 border-t border-slate-200 dark:border-zinc-800">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-zinc-500 uppercase tracking-wider block mb-2">Quick Test Personas (Click to Fill):</span>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <button type="button" onclick="fillLogin('admin@aengelos.com', 'password123')" class="p-2.5 bg-slate-50 dark:bg-zinc-950 hover:bg-slate-100 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-xl text-left transition-colors shadow-sm">
                        <strong class="text-red-600 dark:text-red-400 block font-bold text-[11px] sm:text-xs">Admin</strong>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-500">Aengelo</span>
                    </button>
                    <button type="button" onclick="fillLogin('artist@aengelos.com', 'password123')" class="p-2.5 bg-slate-50 dark:bg-zinc-950 hover:bg-slate-100 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-xl text-left transition-colors shadow-sm">
                        <strong class="text-amber-600 dark:text-amber-400 block font-bold text-[11px] sm:text-xs">Artist</strong>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-500">Elena</span>
                    </button>
                    <button type="button" onclick="fillLogin('client@aengelos.com', 'password123')" class="p-2.5 bg-slate-50 dark:bg-zinc-950 hover:bg-slate-100 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 rounded-xl text-left transition-colors shadow-sm">
                        <strong class="text-emerald-600 dark:text-emerald-400 block font-bold text-[11px] sm:text-xs">Client</strong>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-500">Juan</span>
                    </button>
                </div>
            </div>

            <!-- SEPARATION LINK: SWITCH TO SIGN UP -->
            <div class="pt-4 border-t border-slate-200 dark:border-zinc-800 text-center">
                <p class="text-xs text-slate-600 dark:text-zinc-400 mb-2">
                    Don't have an account yet?
                </p>
                <a href="register.php" class="inline-flex items-center justify-center space-x-1.5 w-full py-2.5 px-4 rounded-xl border border-amber-500/30 hover:border-amber-500 bg-amber-500/5 hover:bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-xs uppercase tracking-wider transition-all">
                    <span>Create a Client Account / Sign Up</span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="py-6 px-4 sm:px-8 border-t border-slate-200 dark:border-zinc-900 bg-white dark:bg-transparent text-center text-xs text-slate-500 dark:text-zinc-600 transition-colors">
        &copy; <?= date('Y') ?> Aengelos Tattoo Studio. ISO/IEC 25010 Evaluated Architecture. All Rights Reserved.
    </footer>

    <script>
        function fillLogin(email, pass) {
            document.getElementById('loginEmail').value = email;
            document.getElementById('loginPassword').value = pass;
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

        // Set initial label state
        updateThemeButton(document.documentElement.classList.contains('dark'));
    </script>
</body>
</html>
