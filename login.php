<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" type="text/css" href="pico.yellow.min.css">
</head>

<body>
    <main class="container">
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
