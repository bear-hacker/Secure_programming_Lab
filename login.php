<?php
// login.php
// 1. MUST BE FIRST: Start a session to store the message
session_start(); 

// 2. Include required files
require_once 'db_connect.php'; // Includes config.php and sets up $pdo

/**
 * Checks if the user is currently locked out based on recent failed attempts.
 */
function check_lockout($username, $pdo) {
    // Calculate the cutoff time (LOCKOUT_TIME_MINUTES from config.php)
    $cutoff_time = date('Y-m-d H:i:s', strtotime('-' . LOCKOUT_TIME_MINUTES . ' minutes'));
    
    // Clean up attempts older than the lockout window
    $sql_cleanup = "DELETE FROM login_attempts WHERE `time` < :cutoff_time_cleanup";
    $stmt_cleanup = $pdo->prepare($sql_cleanup);
    $stmt_cleanup->execute([':cutoff_time_cleanup' => $cutoff_time]);

    // Count recent failed attempts for this username
    $sql_count = "SELECT COUNT(*) FROM login_attempts WHERE username = :username AND `time` >= :cutoff_time";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute([':username' => $username, ':cutoff_time' => $cutoff_time]);
    $attempts = $stmt_count->fetchColumn();
    
    // Check against MAX_ATTEMPTS (from config.php)
    if ($attempts >= MAX_ATTEMPTS) {
        return true; // Locked out
    }
    return false; // Not locked out
}

/**
 * Records a single failed login attempt in the database.
 */
function record_failed_attempt($username, $pdo) {
    $ip = $_SERVER['REMOTE_ADDR']; 
    $time = date('Y-m-d H:i:s');
    
    $sql_insert = "INSERT INTO login_attempts (username, ip_address, `time`) VALUES (:username, :ip, :time)";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([':username' => $username, ':ip' => $ip, ':time' => $time]);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize user input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $message = '';
    $message_type = 'error'; 

    // --- 1. PRE-AUTH LOCKOUT CHECK ---
    if (check_lockout($username, $pdo)) {
        $lockout_duration = LOCKOUT_TIME_MINUTES; 
        $message = "Too many failed login attempts. Please wait " . $lockout_duration . " minutes before trying again.";
        $message_type = 'alert';
    } else {
        // --- 2. AUTHENTICATION ---
        $sql_user = "SELECT password FROM users WHERE username = :username";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([':username' => $username]);
        $user_hash = $stmt_user->fetchColumn();

        if ($user_hash && password_verify($password, $user_hash)) {
            // *** LOGIN SUCCESSFUL ***
            
            // Clear all failed attempts for this user upon success
            $sql_clear = "DELETE FROM login_attempts WHERE username = :username";
            $stmt_clear = $pdo->prepare($sql_clear);
            $stmt_clear->execute([':username' => $username]);

            // Fetch user role and ID
            $sql_role = "SELECT id, role FROM users WHERE username = :username";
            $stmt_role = $pdo->prepare($sql_role);
            $stmt_role->execute([':username' => $username]);
            $user_data = $stmt_role->fetch();

            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['role'] = $user_data['role'];

            // Role-based redirection
            if ($user_data['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: welcome.php");
            }
            exit;
            
        } else {
            // *** LOGIN FAILED ***
            
            record_failed_attempt($username, $pdo);
            
            // Re-check lockout status *after* the final failed attempt
            if (check_lockout($username, $pdo)) {
                $lockout_duration = LOCKOUT_TIME_MINUTES; 
                $message = "Too many failed login attempts. Your account is now locked for " . $lockout_duration . " minutes."; 
                $message_type = 'alert';
            } else {
                 $message = "Invalid username or password. Please try again."; 
                 $message_type = 'error';
            }
        }
    }

    // --- 3. REDIRECT BACK TO LOGIN PAGE WITH MESSAGE ---
    $_SESSION['login_message'] = $message;
    $_SESSION['login_message_type'] = $message_type; 

    // Redirect to the login page
    header("Location: index.php"); 
    exit; // Crucial to prevent header output errors
} else {
    // Handle GET requests (direct access)
    echo "Access via POST form only.";
}
?>