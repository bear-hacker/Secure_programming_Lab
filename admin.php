<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Role Check: Only allow 'admin'
if ($_SESSION['role'] !== 'admin') {
    echo "Access Denied: You do not have permission to view this page.";
    exit;
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; background-color: #2c3e50; color: white; }
        .container { background: #34495e; padding: 2rem; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        h1 { color: #ecf0f1; }
        .logout { color: #e74c3c; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($username); ?>!</p>
        <p>This is a restricted area for administrators only.</p>
        <p><a href="logout.php" class="logout">Logout</a></p>
    </div>
</body>
</html>
