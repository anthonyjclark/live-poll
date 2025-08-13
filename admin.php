<?php
$PASSCODE = '041';
session_start();

$poll_file = 'state.txt';

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

// Login page
if (empty($_SESSION['admin_authenticated'])) {
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Login</title>
        <link rel="stylesheet" type="text/css" href="css/pico.yellow.min.css">
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


// Timer logic: only track remaining and state (0=stopped, 1=running)
if (isset($_POST['timer_action'])) {
    $lines = file($poll_file, FILE_IGNORE_NEW_LINES);
    $timer = json_decode($lines[9], true);
    $action = $_POST['timer_action'];
    if ($action === 'set') {
        $timer['remaining'] = intval($_POST['timer_duration']);
        $timer['state'] = 0; // stopped
    } elseif ($action === 'start') {
        if ($timer['remaining'] > 0) {
            $timer['state'] = 1; // running
        }
    } elseif ($action === 'stop') {
        $timer['state'] = 0; // stopped
    }
    $lines[9] = json_encode($timer);
    file_put_contents($poll_file, implode("\n", $lines));
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Handle number of options change
if (isset($_POST['set_num_options'])) {
    $lines = file($poll_file, FILE_IGNORE_NEW_LINES);
    $num_options = max(2, min(5, intval($_POST['num_options'])));
    $current_options = array_slice($lines, 2, 5);
    $current_count = count($current_options);
    for ($i = $current_count; $i < 5; $i++) {
        $current_options[] = 'Option ' . ($i + 1);
    }
    $current_options = array_slice($current_options, 0, 5);
    $votes = array_map('intval', explode(',', $lines[7]));
    $votes = array_slice($votes, 0, $num_options);
    while (count($votes) < 5) $votes[] = 0;
    $votes = array_slice($votes, 0, 5);
    $lines = array_merge([
        $lines[0],
        $num_options
    ], $current_options, [
        implode(',', $votes),
        time(),
        $lines[9]
    ]);
    file_put_contents($poll_file, implode("\n", $lines));
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_passcode']) && !isset($_POST['timer_action'])) {
    $lines = file($poll_file, FILE_IGNORE_NEW_LINES);
    $num_options = intval($lines[1]);
    $lines[7] = implode(',', array_fill(0, $num_options, 0));
    $lines[8] = time();
    file_put_contents($poll_file, implode("\n", $lines));
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
// Get timer state and number of options from state.txt
$timer = [
    'remaining' => 0,
    'state' => 0 // 0=stopped, 1=running
];
$num_options = 5;
if (file_exists($poll_file)) {
    $lines = file($poll_file, FILE_IGNORE_NEW_LINES);
    $num_options = intval($lines[1]);
    $file_timer = json_decode($lines[9], true);
    if (is_array($file_timer)) {
        $timer['remaining'] = isset($file_timer['remaining']) ? $file_timer['remaining'] : 0;
        $timer['state'] = isset($file_timer['state']) ? $file_timer['state'] : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <link rel="stylesheet" type="text/css" href="css/pico.yellow.min.css">
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
    <main class="container">
    <div class="admin">
        <form method="get" style="float:right;display:inline;">
            <button type="submit" name="logout" value="1">Logout</button>
        </form>

        <h1>Live Poll: Admin Panel</h1>

        <?php
        // Check if there are any votes for active options only
        $votes_disabled = '';
        if (file_exists($poll_file)) {
            $lines = file($poll_file, FILE_IGNORE_NEW_LINES);
            $num_options = intval($lines[1]);
            $votes = array_map('intval', explode(',', $lines[7]));
            $active_votes = array_slice($votes, 0, $num_options);
            $any_votes = array_sum($active_votes) > 0;
            if (!$any_votes) {
                $votes_disabled = 'disabled';
            }
        }
        ?>
        <form method="post">
            <button type="submit" id="clear-votes-btn" <?php echo $votes_disabled; ?>>Clear All Votes</button>
        </form>

        <form method="post">
            <label>Number of options</label>
            <div role="group" style="margin-bottom:1em;">
                <?php for ($i = 2; $i <= 5; $i++): ?>
                    <button type="submit" name="set_num_options" value="1">
                        <input type="hidden" name="num_options" value="<?php echo $i; ?>">
                        <span <?php if ($num_options == $i) echo 'style="font-weight:bold;"'; ?>><?php echo $i; ?></span>
                    </button>
                <?php endfor; ?>
            </div>
        </form>

        <form method="post" class="timer-controls" style="display: flex; gap: 0.5em; align-items: center;">
            <div style="display: flex; flex-direction: column; gap: 0.5em; width: 100%;">
                <label style="margin: 0; flex: 1;">
                    Timer duration
                    <input type="range" name="timer_duration" id="timer-slider" min="0" max="600" step="30" value="<?php echo isset($timer['remaining']) ? $timer['remaining'] : 0; ?>" style="width: 100%;">
                    <span id="slider-value" style="margin-left:1em;font-weight:bold;"></span>
                </label>
                <div role="group">
                    <button type="submit" name="timer_action" value="start" <?php if ($timer['state']===1) echo 'disabled'; ?>>Start</button>
                    <button type="submit" name="timer_action" value="stop">Stop</button>
                </div>
            </div>
        </form>

        <div>Timer: <span id="timer-display">00:00</span></div>
        <div id="admin-results"></div>

        <script>
        function fetchAdminResults() {
            fetch('update.php')
                .then(res => res.json())
                .then(data => {
                    let html = '<div class="results">';
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

                    // Enable/disable Clear All Votes button based on active votes
                    var clearBtn = document.getElementById('clear-votes-btn');
                    if (clearBtn) {
                        // Only check active options
                        let activeVotes = data.options.map(opt => opt.votes);
                        let hasVotes = activeVotes.some(v => v > 0);
                        clearBtn.disabled = !hasVotes;
                    }
                });
        }
        function formatTime(secs) {
            secs = Math.max(0, parseInt(secs, 10) || 0);
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }
        // Slider value display
        document.addEventListener('DOMContentLoaded', function() {
            var slider = document.getElementById('timer-slider');
            var valueSpan = document.getElementById('slider-value');
            if (slider && valueSpan) {
                function updateSliderValue() {
                    valueSpan.textContent = formatTime(slider.value);
                }
                slider.addEventListener('input', updateSliderValue);
                updateSliderValue();
            }
        });
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
