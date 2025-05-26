<?php
// admin_clear.php: Resets all votes to zero for the 6 options
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reset_votes = array_fill(0, 6, '0');
    file_put_contents('votes.txt', implode("\n", $reset_votes) . "\n");
    echo 'Votes have been reset.';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin: Clear Poll Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .admin { max-width: 400px; margin: auto; }
    </style>
</head>
<body>
    <div class="admin">
        <h2>Admin: Clear Poll Results</h2>
        <form method="post">
            <button type="submit">Clear All Votes</button>
        </form>
    </div>
</body>
</html>
