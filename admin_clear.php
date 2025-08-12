<?php
// admin_clear.php: Resets all votes to zero for the 6 options
$PASSCODE = 'letmein123'; // Change this to your desired passcode
session_start();
$reset_message = '';

// Handle passcode form
if (isset($_POST['admin_passcode'])) {
    if ($_POST['admin_passcode'] === $PASSCODE) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        $login_error = 'Incorrect passcode.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authenticated']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Only allow access if authenticated
if (empty($_SESSION['admin_authenticated'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Login</title>
        <link rel="stylesheet" type="text/css" href="css/pico.classless.yellow.min.css">
        <style>body { font-family: Arial, sans-serif; margin: 2em; }</style>
    </head>
    <body>
    <main>
    <div class="admin">
        <h2>Admin Login</h2>
        <?php if (!empty($login_error)) echo '<div style="color:red;">' . htmlspecialchars($login_error) . '</div>'; ?>
        <form method="post">
            <input type="password" name="admin_passcode" placeholder="Enter passcode" required autofocus>
            <button type="submit">Login</button>
        </form>
    </div>
    </main>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_passcode'])) {
    $reset_votes = array_fill(0, 6, '0');
    file_put_contents('votes.txt', implode("\n", $reset_votes) . "\n");
    file_put_contents('reset.txt', time());
    // Only show the message for this POST request, not on reload
    setcookie('reset_message', '1', 0, '/');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
if (isset($_COOKIE['reset_message'])) {
    $reset_message = 'Votes have been reset.';
    setcookie('reset_message', '', time() - 3600, '/'); // Clear cookie
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <link rel="stylesheet" type="text/css" href="css/pico.classless.yellow.min.css">
    <title>Live Poll: Admin Panel</title>
    <style>
        .poll {
            max-width: min(100vw, 400px);
            margin: auto;
        }

        .results {
            margin-top: 2em;
        }
    </style>
</head>

<body>
    <main>
    <div class="admin">
        <h2>Live Poll: Admin Panel</h2>
            <form method="get" style="float:right;display:inline;">
                <button type="submit" name="logout" value="1">Logout</button>
            </form>
        <form method="post">
            <button type="submit">Clear All Votes</button>
        </form>
        <?php
        if ($reset_message) {
            echo '<div id="reset-msg">' . htmlspecialchars($reset_message) . '</div>';
        }
        ?>
        <hr>
        <h3>Current Results</h3>
        <div id="admin-results"></div>
        <script>
        function fetchAdminResults() {
            fetch('get_poll.php')
                .then(res => res.json())
                .then(data => {
                    let html = `<strong>${data.question}</strong>`;
                    html += '<div class="results">';
                    const total = data.options.reduce((sum, o) => sum + o.votes, 0);
                    let anyVotes = false;
                    data.options.forEach(opt => {
                        if (opt.votes > 0) anyVotes = true;
                        const percent = total ? Math.round((opt.votes / total) * 100) : 0;
                        const voteLabel = opt.votes === 1 ? 'vote' : 'votes';
                        html += `<div>${opt.text}: ${opt.votes} ${voteLabel} (${percent}%) <progress value="${percent}" max="100"></progress></div>`;
                    });
                    html += '</div>';
                    document.getElementById('admin-results').innerHTML = html;
                    // Hide reset message if any votes are cast
                    const resetMsg = document.getElementById('reset-msg');
                    if (resetMsg && anyVotes) {
                        resetMsg.style.display = 'none';
                    }
                });
        }
        fetchAdminResults();
        setInterval(fetchAdminResults, 2000);
        </script>
    </div>
    </main>
</body>
</html>
