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
        <h1>Admin Login</h1>
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


// Timer logic using update.php
if (isset($_POST['timer_action'])) {
    $poll_file = 'data.txt';
    $lines = file($poll_file, FILE_IGNORE_NEW_LINES);
    $timer = json_decode($lines[9], true);
    $action = $_POST['timer_action'];
    if ($action === 'set') {
        $timer['duration'] = intval($_POST['timer_duration']);
        $timer['remaining'] = $timer['duration'];
        $timer['state'] = 'stopped';
        $timer['end_time'] = null;
    } elseif ($action === 'start') {
        if ($timer['remaining'] > 0) {
            $timer['state'] = 'running';
            $timer['end_time'] = time() + $timer['remaining'];
        }
    } elseif ($action === 'pause') {
        if ($timer['state'] === 'running') {
            $timer['remaining'] = max(0, $timer['end_time'] - time());
            $timer['state'] = 'paused';
            $timer['end_time'] = null;
        }
    } elseif ($action === 'stop') {
        $timer['state'] = 'stopped';
        $timer['remaining'] = $timer['duration'];
        $timer['end_time'] = null;
    }
    $lines[9] = json_encode($timer);
    file_put_contents($poll_file, implode("\n", $lines));
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_passcode']) && !isset($_POST['timer_action'])) {
    $poll_file = 'data.txt';
    $lines = file($poll_file, FILE_IGNORE_NEW_LINES);
    $lines[7] = '0,0,0,0,0,0';
    $lines[8] = time();
    file_put_contents($poll_file, implode("\n", $lines));
    setcookie('reset_message', '1', 0, '/');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
// Get timer state from data.txt
$poll_file = 'data.txt';
$timer = [
    'duration' => 0,
    'remaining' => 0,
    'state' => 'stopped',
    'end_time' => null
];
if (file_exists($poll_file)) {
    $lines = file($poll_file, FILE_IGNORE_NEW_LINES);
    $timer = json_decode($lines[9], true);
    if ($timer['state'] === 'running' && $timer['end_time']) {
        $timer['remaining'] = max(0, $timer['end_time'] - time());
        if ($timer['remaining'] <= 0) {
            $timer['state'] = 'stopped';
            $timer['remaining'] = 0;
            $timer['end_time'] = null;
        }
        $lines[9] = json_encode($timer);
        file_put_contents($poll_file, implode("\n", $lines));
    }
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
        <form method="get" style="float:right;display:inline;">
            <button type="submit" name="logout" value="1">Logout</button>
        </form>
        <h1>Live Poll: Admin Panel</h1>
        <form method="post">
            <button type="submit">Clear All Votes</button>
        </form>
        <?php
        if ($reset_message) {
            echo '<div id="reset-msg">' . htmlspecialchars($reset_message) . '</div>';
        }
        ?>
        <hr>
        <h2>Timer Controls</h2>
        <form method="post" class="timer-controls" style="display: flex; gap: 0.5em; align-items: center;">
            <div style="display: flex; flex-direction: column; gap: 0.5em; width: 100%;">
                <label style="margin: 0; flex: 1;">
                    Set time (seconds):
                    <input type="number" name="timer_duration" min="1" value="<?php echo $timer['duration']; ?>" required style="width: 100%;">
                </label>
                <div style="display: flex; gap: 0.5em;">
                    <button type="submit" name="timer_action" value="set">Set</button>
                    <button type="submit" name="timer_action" value="start" <?php if ($timer['state']==='running') echo 'disabled'; ?>>Start</button>
                    <button type="submit" name="timer_action" value="pause" <?php if ($timer['state']!=='running') echo 'disabled'; ?>>Pause</button>
                    <button type="submit" name="timer_action" value="stop">Stop</button>
                </div>
            </div>
        </form>
        <div>Timer: <span id="timer-display">00:00</span> (<?php echo htmlspecialchars($timer['state']); ?>)</div>
        <hr>
        <h2>Current Results</h2>
        <div id="admin-results"></div>
        <script>
        function fetchAdminResults() {
            fetch('update.php')
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
        function formatTime(secs) {
            secs = Math.max(0, parseInt(secs, 10) || 0);
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }
        function fetchTimer() {
            fetch('update.php')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('timer-display').textContent = formatTime(data.timer.remaining);
                });
        }
        fetchAdminResults();
        setInterval(fetchAdminResults, 2000);
        setInterval(fetchTimer, 500);
        </script>
    </div>
    </main>
</body>
</html>
