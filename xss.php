<?php
// --- DATABASE CONFIGURATION ---
// User-provided credentials
$host = 'localhost';
$db   = 'xss'; 
$user = 'root'; 
$pass = 'cadt'; 
$charset = 'utf8mb4';

// PDO connection settings
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Check that the database and table are correctly set up if this fails!
     exit("Database connection failed. Error: " . $e->getMessage()); 
}


// --- INITIALIZE VARIABLE FOR REFLECTED OUTPUT ---
$reflected_input = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Reflected XSS Simulation (Input 1)
    if (isset($_POST['reflected_data'])) {
        // Data is simply stored in a variable for immediate display (Reflected scenario)
        $reflected_input = $_POST['reflected_data'];
    }
    
    // 2. Stored XSS Simulation (Input 2) - Stored Unsafely
    if (!empty($_POST['stored_data'])) {
        $comment = $_POST['stored_data'];
        $stmt = $pdo->prepare("INSERT INTO comments (text, source_tag, created_at) VALUES (?, ?, NOW())");
        // Store raw input for later VULNERABLE display
        $stmt->execute([$comment, 'STORED VULNERABLE PATH']); 
    }
    
    // 3. Secure Input (Input 3) - Stored Safely
    if (!empty($_POST['secure_data'])) {
        $comment = $_POST['secure_data'];
        $stmt = $pdo->prepare("INSERT INTO comments (text, source_tag, created_at) VALUES (?, ?, NOW())");
        // Store raw input; security will rely entirely on the display step
        $stmt->execute([$comment, 'SECURE PATH']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>XSS Comparison: Reflected, Stored, and Secure</title>
    <style>
        .output-box { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .reflected-danger { border: 2px solid orange; background-color: #ffe; }
        .stored-danger { border: 2px solid darkred; background-color: #fee; }
        .secure-safe { border: 2px solid darkgreen; background-color: #eff; }
    </style>
</head>
<body>

    <h1>XSS Comparison: Reflected, Stored, and Secure</h1>
    <p>Payload to test in all fields: <code>&lt;script&gt;alert('XSS!')&lt;/script&gt;</code></p>

    <form method="POST" action="">
        <label>1. Input for **REFLECTED** (Displayed immediately):</label><br>
        <input type="text" name="reflected_data" size="60" value=""><br><br>

        <label>2. Input for **STORED VULNERABLE** (Saved and displayed unsafely):</label><br>
        <input type="text" name="stored_data" size="60" value=""><br><br>
        
        <label>3. Input for **SECURE** (The correct way):</label><br>
        <input type="text" name="secure_data" size="60" value=""><br><br>
        
        <input type="submit" value="Submit All Inputs">
    </form>

    <hr>

    <?php if (!empty($reflected_input)): ?>
        <h2>1. Reflected XSS Display (FIXED WITH ESCAPING)</h2>
        <div class="output-box secure-safe">
            Your raw input was: <?php echo htmlspecialchars($reflected_input, ENT_QUOTES, 'UTF-8'); ?> 
            <p><strong>Result:</strong> Input is safely escaped. No XSS execution possible.</p>
        </div>
    <?php endif; ?>

    
    <h2>2. Stored Data Display Comparison (From Database)</h2>

    <?php
    $stmt = $pdo->query("SELECT text, source_tag FROM comments ORDER BY created_at DESC LIMIT 5");
    
    while ($row = $stmt->fetch()) {
        $comment_text = $row['text'];
        $tag = $row['source_tag'];
        
        echo "<h3>From: " . htmlspecialchars($tag) . "</h3>";
        
        if ($tag == 'STORED VULNERABLE PATH') {
            echo "<div class='output-box stored-danger'>";
            // ⛔ VULNERABLE LINE: No escaping when retrieving from DB. Stored XSS executes here.
            echo $comment_text; 
            echo "</div>";
            
        } elseif ($tag == 'SECURE PATH') {
            // ✅ SECURE LINE: The essential security measure for XSS prevention.
            $safe_comment = htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8');
            echo "<div class='output-box secure-safe'>";
            echo $safe_comment; 
            echo "</div>";
        }
    }
    ?>

</body>
</html>