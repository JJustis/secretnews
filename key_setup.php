<?php
session_start();

// Generate session key if not exists
if (!isset($_SESSION['session_key'])) {
    $_SESSION['session_key'] = bin2hex(random_bytes(16));
}

// Generate cookie key if not exists
if (!isset($_COOKIE['cookie_key'])) {
    $cookie_key = bin2hex(random_bytes(16));
    setcookie('cookie_key', $cookie_key, time() + (86400 * 30), "/"); // 30 days expiry
    header("Location: index.php"); // Redirect to the main page after setting the cookie
    exit();
}

// If both keys exist, redirect to the main page
header("Location: index.php");
exit();
