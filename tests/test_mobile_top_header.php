<?php
// tests/test_mobile_top_header.php
echo "=== TESTING MOBILE TOP HEADER CLEANLINESS & RESPONSIVENESS ===" . PHP_EOL;

// 1. Storefront guest
$html = shell_exec('php -r "include \'public/index.php\';"');

$hasDuplicateSubNav = strpos($html, '<!-- STOREFRONT SUB-NAV STRIP -->') !== false;
echo "1. Storefront Guest:" . PHP_EOL;
echo "  - No duplicate sub-nav strip: " . (!$hasDuplicateSubNav ? "PASS" : "FAIL") . PHP_EOL;

$hasResponsiveAvail = strpos($html, 'sm:hidden">Avail<') !== false && strpos($html, 'hidden sm:inline">Avail & Design<') !== false;
echo "  - Responsive compact 'Avail' CTA on mobile: " . ($hasResponsiveAvail ? "PASS" : "FAIL") . PHP_EOL;

$hasHiddenRegisterMobile = strpos($html, 'hidden sm:inline-flex px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-xl bg-amber-500') !== false;
echo "  - Register button safely deferred to sm+ on mobile: " . ($hasHiddenRegisterMobile ? "PASS" : "FAIL") . PHP_EOL;

$hasSignIn = strpos($html, 'Sign In') !== false;
echo "  - Sign In button present: " . ($hasSignIn ? "PASS" : "FAIL") . PHP_EOL;

$hasTheme = strpos($html, 'toggleTheme()') !== false;
echo "  - Theme toggle button present: " . ($hasTheme ? "PASS" : "FAIL") . PHP_EOL;

if ($hasDuplicateSubNav || !$hasResponsiveAvail || !$hasHiddenRegisterMobile || !$hasSignIn || !$hasTheme) {
    echo "FAILED storefront check!" . PHP_EOL;
    exit(1);
}

// 2. Client Portal logged in
$htmlClient = shell_exec('php -r "session_start(); $_SESSION[\'user\']=[\'id\'=>3,\'full_name\'=>\'Juan Dela Cruz\',\'role\'=>\'client\']; include \'public/client_portal.php\';"');

$hasClientHub = strpos($htmlClient, 'Client Hub') !== false;
$hasVisualizerNav = strpos($htmlClient, 'Visualizer') !== false;
$hasBookingsNav = strpos($htmlClient, 'Bookings') !== false;
echo PHP_EOL . "2. Client Portal (Logged In):" . PHP_EOL;
echo "  - Client Hub badge: " . ($hasClientHub ? "PASS" : "FAIL") . PHP_EOL;
echo "  - Visualizer nav link: " . ($hasVisualizerNav ? "PASS" : "FAIL") . PHP_EOL;
echo "  - Bookings nav link: " . ($hasBookingsNav ? "PASS" : "FAIL") . PHP_EOL;

if (!$hasClientHub || !$hasVisualizerNav || !$hasBookingsNav) {
    echo "FAILED client portal check!" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . ">>> ALL MOBILE TOP HEADER TESTS PASSED PERFECTLY! <<<" . PHP_EOL;
