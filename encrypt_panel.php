<?php
session_start();

// Combine session key and cookie key for encryption
if (!isset($_SESSION['session_key']) || !isset($_COOKIE['cookie_key'])) {
    die('Session and cookie keys are required for encryption.');
}

$combined_key = hash('sha256', $_SESSION['session_key'] . $_COOKIE['cookie_key']);

// Example content to encrypt for panel
$panel_content = "This is an encrypted news article for panel 1.";

// Encrypt the panel content using HMAC and save it as a base64 string
$encrypted_content = base64_encode(hash_hmac('sha256', $panel_content, $combined_key));

// Save encrypted content to a file
file_put_contents('panels/panel_1.txt', $encrypted_content);

// Repeat for panel 2
$panel_content_2 = "This is an encrypted news article for panel 2.";
$encrypted_content_2 = base64_encode(hash_hmac('sha256', $panel_content_2, $combined_key));
file_put_contents('panels/panel_2.txt', $encrypted_content_2);

echo "Panels encrypted and stored successfully!";
