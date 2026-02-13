<?php
// index.php
session_start();

// Retrieve message and type from the session if set
$message = $_SESSION['login_message'] ?? '';
$message_type = $_SESSION['login_message_type'] ?? '';

// Clear the session message so it doesn't reappear on refresh
unset($_SESSION['login_message']);
unset($_SESSION['login_message_type']);

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    header("Location: welcome.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login</title>
    <style>
        /* Basic Reset and Box-sizing */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Body and overall layout */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* Form Container */
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-weight: 600;
        }

        /* Form Group for Label and Input */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block; 
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
        }

        /* Submit Button */
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        
        /* --- MESSAGE BOX STYLING --- */
        .message-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
        }

        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message-box.alert {
            /* Distinct style for lockout alerts */
            background-color: #fff3cd; 
            color: #856404;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>🔒 Account Login</h2>

        <?php if (!empty($message)): ?>
            <div class="message-box <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST"> 
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Enter your username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <button type="submit">Log In</button>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="inventory.php" style="text-decoration: none; color: #007bff;">Browse Inventory (No Login)</a>
            </div>
            
        </form>
    </div>
</body>
</html>