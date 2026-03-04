<?php
// logout.php
session_start();


// Include database configuration
require_once 'config/db.php';

// Function to get client IP address
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Function to log activity
function logActivity($user_id, $user_name, $action, $description, $branch_id = null, $branch_name = null) {
    $conn = connectDB();
    if (!$conn) return false;
    
    $ip_address = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_name, action, description, branch_id, branch_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("isssiiss", $user_id, $user_name, $action, $description, $branch_id, $branch_name, $ip_address, $user_agent);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $result;
    }
    
    $conn->close();
    return false;
}

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['full_name'] ?? 'Unknown User';
    $branch_id = $_SESSION['branch_id'] ?? null;
    $branch_name = $_SESSION['branch_name'] ?? null;
    
    // Log logout activity
    logActivity(
        $user_id,
        $full_name,
        'LOGOUT',
        'User logged out of the system',
        $branch_id,
        $branch_name
    );
    
    // Update last logout time in users table (only if column exists)
    $conn = connectDB();
    if ($conn) {
        // Check if last_logout column exists
        $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'last_logout'");
        if ($check_column->num_rows > 0) {
            // Column exists, update it
            $update_stmt = $conn->prepare("UPDATE users SET last_logout = NOW() WHERE user_id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("i", $user_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        } else {
            // Column doesn't exist, use updated_at instead
            $update_stmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE user_id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("i", $user_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        $conn->close();
    }
}

// Optional: Log forced logout (session expired or unauthorized access)
elseif (isset($_GET['session']) && $_GET['session'] == 'expired') {
    logActivity(
        0,
        'System',
        'SESSION_EXPIRED',
        'User session expired due to inactivity'
    );
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear remember me cookie
setcookie('remember_user', '', time() - 3600, '/');

// Clear any other application-specific cookies
setcookie('pharmacare_session', '', time() - 3600, '/');

// Optional: Add a small delay to ensure logging completes
usleep(100000); // 0.1 second delay

// Redirect to login page with success message
header('Location: index.php?logout=success');
exit();
?>