<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$logged_in_user = $_SESSION['username'];
$user_messages = [];

// Load encrypted messages from messages/username.txt
$message_file = "messages/{$logged_in_user}.txt";
if (file_exists($message_file)) {
    $encrypted_messages = file($message_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Decrypt messages (replace with actual decryption logic)
    foreach ($encrypted_messages as $encrypted_message) {
        $decrypted_message = base64_decode($encrypted_message); // Replace with real decryption
        $message_data = json_decode($decrypted_message, true); // Assuming messages are stored in JSON format
        if ($message_data) {
            $user_messages[] = $message_data; // Add to messages array if valid
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h1>Inbox</h1>
    <?php if (empty($user_messages)): ?>
        <p>No messages yet.</p>
    <?php else: ?>
        <?php foreach ($user_messages as $message): ?>
            <div class="message-box">
                <h5>From: <?= htmlspecialchars($message['from']); ?></h5>
                <p><?= htmlspecialchars($message['content']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
