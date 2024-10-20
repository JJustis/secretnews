<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['username'];

// Load user profile from JSON file
$profile_file = "profiles/{$user_id}.json";
if (!file_exists($profile_file)) {
    // Handle the case if the profile file doesn't exist
    die("Profile not found.");
}

// Fetch user profile from JSON
$user = json_decode(file_get_contents($profile_file), true);

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES['profile_image']['name']);

    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
        // Update the profile JSON with the new image path
        $user['profile_image'] = $target_file;
        file_put_contents($profile_file, json_encode($user, JSON_PRETTY_PRINT));

        // Reload the page to reflect the change
        header("Location: profile.php");
    } else {
        echo "Error uploading file!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="text-center">Profile of <?= htmlspecialchars($user['username']) ?></h1>
        <img src="<?= $user['profile_image'] ?: 'default.png' ?>" alt="Profile Image" width="100">
        
        <!-- Profile Image Upload -->
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="profile_image" class="form-control mb-2">
            <button type="submit" class="btn btn-primary">Upload Image</button>
        </form>

        <!-- Decryption Key Sharing (conceptual) -->
        <h4>Profile Decryption Key</h4>
        <p>Your decryption key (updated every 24 minutes): <strong>KEY123</strong></p>
        <button class="btn btn-success" onclick="alert('Decryption key shared successfully!')">Share Key</button>
    </div>
</body>
</html>
