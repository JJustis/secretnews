<?php
session_start();

// Load available keys from keys.txt
$keys = file('keys.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Combine session key and cookie key for AES-256 decryption
if (!isset($_SESSION['session_key']) || !isset($_COOKIE['cookie_key'])) {
    header("Location: key_setup.php");
    exit();
}
$combined_key = hash('sha256', $_SESSION['session_key'] . $_COOKIE['cookie_key']); // Hashed combination

// Retrieve all panel JSON files from the server (stored in /panels/ directory)
$panel_files = array_diff(scandir('panels'), array('..', '.'));

// Prepare the panels data
$panels = [];
foreach ($panel_files as $panel_file) {
    $panel_data = json_decode(file_get_contents("panels/$panel_file"), true);
    if ($panel_data) {
        $panels[] = $panel_data; // Add panel data to the array
    }
}

// Check if the user is logged in and load user messages if logged in
$logged_in_user = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$user_profile = null;
$user_messages = [];

if ($logged_in_user) {
    // Fetch user profile details (assuming profile is stored as JSON)
    $profile_file = "profiles/{$logged_in_user}.json";
    if (file_exists($profile_file)) {
        $user_profile = json_decode(file_get_contents($profile_file), true);
    } else {
        // If no profile found, set defaults
        $user_profile = [
            'username' => $logged_in_user,
            'profile_image' => 'default.png',
            'decryption_key' => 'ABC123'
        ];
    }

    // Load encrypted messages from messages/username.txt
    $message_file = "messages/{$logged_in_user}.txt";
    if (file_exists($message_file)) {
        $encrypted_messages = file($message_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Decrypt messages (replace with actual decryption logic)
        foreach ($encrypted_messages as $encrypted_message) {
            $decrypted_message = base64_decode($encrypted_message); // Replace with real decryption
            $user_messages[] = json_decode($decrypted_message, true); // Assuming messages are stored in JSON format
        }
    }
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient'], $_POST['message'])) {
    $recipient = $_POST['recipient'];
    $message_content = $_POST['message'];

    // Sanitize input
    $recipient = htmlspecialchars($recipient);
    $message_content = htmlspecialchars($message_content);

    // Prepare the message
    $message = [
        'from' => $logged_in_user,
        'content' => $message_content,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Encode message to JSON
    $encoded_message = base64_encode(json_encode($message));

    // Save message to recipient's file
    $recipient_file = "messages/{$recipient}.txt";
    file_put_contents($recipient_file, $encoded_message . PHP_EOL, FILE_APPEND);

    // Reload the page after sending the message
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encrypted News Panels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Styling for the right sidebar */
        .right-sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 300px;
            height: 100%;
            background-color: #f8f9fa;
            padding: 20px;
            box-shadow: -2px 0px 5px rgba(0, 0, 0, 0.1);
        }

        .message-box {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
        }

        .message-box h5 {
            margin: 0 0 10px 0;
        }

        .navbar {
            margin-bottom: 20px;
        }

        .decryption-panel {
            padding: 20px;
        }
    </style>

    <script>
        // Function to decrypt panel JSON data using multiple keys from the user-submitted list
        function tryKeysOnPanel(panelJson, panelId, keys) {
            const encryptedData = Uint8Array.from(atob(panelJson.encrypted_data), c => c.charCodeAt(0));  // Decode base64
            let keyAttemptMessage = document.getElementById(panelId + '_key_attempt');

            function tryKey(keyIndex) {
                if (keyIndex >= keys.length) {
                    document.getElementById(panelId).textContent = "Decryption failed for all keys.";
                    return;
                }

                const currentKey = keys[keyIndex];
                keyAttemptMessage.textContent = `Trying key: ${currentKey}`; // Show the current key attempt to the user
                const encodedKey = new TextEncoder().encode(currentKey);

                window.crypto.subtle.importKey(
                    "raw",
                    encodedKey,
                    { name: "HMAC", hash: "SHA-256" },
                    false,
                    ["verify"]
                ).then(key => {
                    return window.crypto.subtle.verify(
                        "HMAC",
                        key,
                        encryptedData,
                        new TextEncoder().encode(panelJson.content)  // Use original content as input for verification
                    );
                }).then(isValid => {
                    if (isValid) {
                        document.getElementById(panelId).textContent = `Decrypted: ${panelJson.content}`;
                        keyAttemptMessage.textContent = ""; // Clear key attempt message on success
                    } else {
                        tryKey(keyIndex + 1); // Try the next key
                    }
                }).catch(err => {
                    console.error(err);
                    tryKey(keyIndex + 1); // Try the next key on error
                });
            }

            tryKey(0); // Start with the first key
        }

        // Set event listeners to decrypt panels when clicked
        function setupPanelDecryption(keys, panels) {
            panels.forEach(panel => {
                document.getElementById(panel.panel_id + '_button').onclick = function () {
                    tryKeysOnPanel(panel, panel.panel_id, keys);
                };
            });
        }

        window.onload = function () {
            const keys = <?= json_encode($keys); ?>;
            const panels = <?= json_encode($panels); ?>;

            // Setup decryption for each panel
            setupPanelDecryption(keys, panels);
        };
    </script>
</head>
<body class="bg-light">

    <!-- Navbar with Login and Register -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Encrypted Site</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav ml-auto">
                <?php if ($logged_in_user): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inbox.php">Inbox</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="loginDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Login
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="loginDropdown">
                            <form action="login.php" method="POST" class="px-4 py-3">
                                <div class="form-group">
                                    <label for="loginEmail">Email address</label>
                                    <input type="email" class="form-control" id="loginEmail" name="email" placeholder="email@example.com" required>
                                </div>
                                <div class="form-group">
                                    <label for="loginPassword">Password</label>
                                    <input type="password" class="form-control" id="loginPassword" name="password" placeholder="Password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Login</button>
                            </form>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="registerDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Register
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="registerDropdown">
                            <form action="register.php" method="POST" class="px-4 py-3">
                                <div class="form-group">
                                    <label for="registerUsername">Username</label>
                                    <input type="text" class="form-control" id="registerUsername" name="username" placeholder="Username" required>
                                </div>
                                <div class="form-group">
                                    <label for="registerEmail">Email address</label>
                                    <input type="email" class="form-control" id="registerEmail" name="email" placeholder="email@example.com" required>
                                </div>
                                <div class="form-group">
                                    <label for="registerPassword">Password</label>
                                    <input type="password" class="form-control" id="registerPassword" name="password" placeholder="Password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Register</button>
                            </form>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content: Decryption Panels and Sidebar -->
    <div class="container mt-5">
        <div class="row">
            <!-- Decryption Panels -->
            <div style="width: 80.667% !important;" class="col-lg-8 decryption-panel">
                <h1 class="text-center">Encrypted News Panels</h1>

                <?php foreach ($panels as $panel_data) : ?>
                    <div class="card mb-4">
                        <div class="card-header"><?= $panel_data['title'] ?></div> <!-- Show the title -->
                        <div class="card-body">
                            <p id="<?= $panel_data['panel_id'] ?>" class="text-muted">[Encrypted Panel <?= $panel_data['panel_id'] ?>]</p>
                            <p id="<?= $panel_data['panel_id'] ?>_key_attempt" class="text-muted"></p> <!-- Key attempt message -->
                            <button id="<?= $panel_data['panel_id'] ?>_button" class="btn btn-primary">Decrypt Panel <?= $panel_data['panel_id'] ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sidebar for user profile and inbox -->
            <div class="col-lg-4 right-sidebar">
                <?php if ($logged_in_user): ?>
                    <h4>User Control Panel</h4>
                    <div class="profile-section">
                        <h5>Profile</h5>
                        <img src="<?= $user_profile['profile_image']; ?>" alt="Profile Image" width="100">
                        <p>Username: <?= $user_profile['username']; ?></p>
                        <p>Decryption Key: <?= $user_profile['decryption_key']; ?></p>
                    </div>
                    <div class="messages-section">
                        <h5>Messages</h5>
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

                    <!-- Messaging Form -->
                    <div class="messaging-section mt-4">
                        <h5>Send Message</h5>
                        <form action="index.php" method="POST">
                            <div class="form-group">
                                <input type="text" name="recipient" class="form-control mb-2" placeholder="Recipient Username" required>
                            </div>
                            <textarea name="message" class="form-control mb-2" placeholder="Write your message here..." required></textarea>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
