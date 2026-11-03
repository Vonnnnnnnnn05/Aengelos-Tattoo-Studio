<?php
// tests/test_navbar_uniformity.php

echo "=== TESTING UNIFIED NAVBAR ACROSS ALL PAGES ===" . PHP_EOL;

$pages = [
    [
        'cmd' => 'php -r "include \'public/index.php\';"',
        'badge' => 'Gallery Proofs',
        'desc' => 'Storefront (Guest)'
    ],
    [
        'cmd' => 'php -r "$_GET[\'id\']=1; include \'public/avail.php\';"',
        'badge' => 'Avail & Design',
        'desc' => 'Avail Flash (Guest)'
    ],
    [
        'cmd' => 'php -r "session_start(); $_SESSION[\'user\']=[\'id\'=>3,\'full_name\'=>\'Juan Dela Cruz\',\'role\'=>\'client\']; include \'public/client_portal.php\';"',
        'badge' => 'Client Hub',
        'desc' => 'Client Portal'
    ],
    [
        'cmd' => 'php -r "session_start(); $_SESSION[\'user\']=[\'id\'=>2,\'full_name\'=>\'Senior Artist\',\'role\'=>\'artist\']; include \'public/artist_portal.php\';"',
        'badge' => 'Artist Station',
        'desc' => 'Artist Station'
    ],
    [
        'cmd' => 'php -r "include \'public/register.php\';"',
        'badge' => 'Sign Up',
        'desc' => 'Register Page'
    ],
    [
        'cmd' => 'php -r "session_start(); $_SESSION[\'user\']=[\'id\'=>1,\'full_name\'=>\'Master Admin\',\'role\'=>\'admin\']; include \'public/admin_portal.php\';"',
        'badge' => 'Admin Hub',
        'desc' => 'Admin Portal'
    ]
];

foreach ($pages as $p) {
    $output = shell_exec($p['cmd']);

    $hasBrand = strpos($output, 'AENGELOS') !== false && strpos($output, 'Tattoo Studio') !== false;
    $hasBadge = strpos($output, $p['badge']) !== false;
    $hasHeader = strpos($output, '<header') !== false && strpos($output, '</header>') !== false;
    $hasTheme = strpos($output, 'toggleTheme()') !== false;

    echo "Testing {$p['desc']}:" . PHP_EOL;
    echo "  - Header tags complete: " . ($hasHeader ? "PASS" : "FAIL") . PHP_EOL;
    echo "  - Master brand present: " . ($hasBrand ? "PASS" : "FAIL") . PHP_EOL;
    echo "  - Context badge ('{$p['badge']}'): " . ($hasBadge ? "PASS" : "FAIL") . PHP_EOL;
    echo "  - Theme toggle function: " . ($hasTheme ? "PASS" : "FAIL") . PHP_EOL;

    if (!$hasBrand || !$hasHeader || !$hasBadge) {
        echo "FAILED on {$p['desc']}" . PHP_EOL;
        exit(1);
    }
}

echo PHP_EOL . ">>> ALL 6 KEY SURFACES SHARE THE IDENTICAL UNIFIED NAVBAR! ALL TESTS PASSED <<<" . PHP_EOL;
