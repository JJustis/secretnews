<?php
session_start();

// Path to the profiles directory
$profiles_dir = 'profiles/';

// Assuming users are stored in a JSON file for login credentials
$users_file = 'users.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Load users
    $users = json_decode(file_get_contents($users_file), true) ?? [];

    // Check if email exists and verify password
    $user_found = false;
    foreach ($users as $user) {
        if ($user['email'] === $email && password_verify($password, $user['password'])) {
            $user_found = true;
            $_SESSION['username'] = $user['username']; // Store username in session
            break;
        }
    }

    if ($user_found) {
        // Create or update the user's profile JSON file
        $profile_file = $profiles_dir . $_SESSION['username'] . '.json';

        if (!file_exists($profile_file)) {
            // Default profile structure
            $profile_data = [
                'username' => $_SESSION['username'],
                'profile_image' => 'default.png', // Default profile image
                'decryption_key' => 'KEY123' // Dummy decryption key (update this with real logic)
            ];

            // Save the profile as a JSON file
            file_put_contents($profile_file, json_encode($profile_data, JSON_PRETTY_PRINT));
        } else {
            // Update the profile JSON if needed (e.g., regenerate the decryption key)
            $profile_data = json_decode(file_get_contents($profile_file), true);
            $profile_data['decryption_key'] = 'KEY123'; // Update decryption key logic if needed
            file_put_contents($profile_file, json_encode($profile_data, JSON_PRETTY_PRINT));
        }

        // Redirect to index.php or dashboard
        header('Location: index.php');
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h1>Login</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>

    <div class="mt-3">
        <a href="register.php">Don't have an account? Register here.</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
