<?php
session_start();

// Path to profiles directory
$profiles_dir = 'profiles/';

// Check if the user is logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Path to the user's profile JSON file
    $profile_file = $profiles_dir . $username . '.json';

    if (file_exists($profile_file)) {
        $profile_data = json_decode(file_get_contents($profile_file), true);

        // Set user as offline
        $profile_data['online'] = 0;
        file_put_contents($profile_file, json_encode($profile_data, JSON_PRETTY_PRINT));
    }

    // Destroy the session
    session_destroy();
}

// Redirect to login.php or home page
header('Location: login.php');
exit();
?>
