<?php
session_start();

// Retrieve the current number of panels (increment based on the number of files in /panels/)
$panel_count = count(array_diff(scandir('panels'), array('..', '.'))) + 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $panel_title = $_POST['title']; // Title input
    $panel_content = $_POST['content']; // Article content input
    $hmac_passphrase = $_POST['hmac_passphrase']; // User-chosen HMAC passphrase
    $store_key = isset($_POST['store_key']) ? $_POST['store_key'] : false; // Store key decision

    // Encrypt the article content using HMAC and the chosen passphrase
    $encrypted_content = base64_encode(hash_hmac('sha256', $panel_content, $hmac_passphrase, true));

    // Create the panel JSON file
    $panel_data = [
        'panel_id' => $panel_count,
        'title' => $panel_title,
        'encrypted_data' => $encrypted_content, // Store encrypted content here
        'content' => $panel_content // Store original content for testing purposes
    ];
    file_put_contents("panels/panel_$panel_count.json", json_encode($panel_data));

    // Store the key in keys.txt if the user chooses to
    if ($store_key) {
        file_put_contents('keys.txt', $hmac_passphrase . PHP_EOL, FILE_APPEND);
    }

    echo "Panel $panel_count article with title '$panel_title' encrypted and stored successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Encrypt Articles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="text-center">Admin Panel - Encrypt Articles</h1>

        <form method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Article Title:</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Article Content:</label>
                <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="hmac_passphrase" class="form-label">HMAC Passphrase:</label>
                <input type="text" class="form-control" id="hmac_passphrase" name="hmac_passphrase" required>
            </div>
            <div class="mb-3">
                <input type="checkbox" id="store_key" name="store_key">
                <label for="store_key" class="form-label">Store HMAC Passphrase in keys.txt</label>
            </div>
            <button type="submit" class="btn btn-primary">Encrypt and Store Article</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
