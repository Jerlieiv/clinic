<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Include configuration file
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get current user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? 'Unknown User';
$user_role = $_SESSION['user_role'] ?? 'staff';

// Database connection
$conn = connectDB();

// Log activity function
function logActivity($conn, $user_id, $user_name, $action, $description) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_name, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $user_name, $action, $description, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Helper Functions
function getVATTypeText($type) {
    $type_map = [
        'standard' => 'Standard',
        'reduced' => 'Reduced',
        'zero' => 'Zero Rated',
        'exempt' => 'Exempt'
    ];
    
    return $type_map[$type] ?? ucfirst($type);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'add_vat_rate':
                // Validate required fields
                $required = ['rate_name', 'rate_percentage'];
                $missing = [];
                foreach ($required as $field) {
                    if (empty(trim($_POST[$field] ?? ''))) {
                        $missing[] = str_replace('_', ' ', $field);
                    }
                }
                
                if (!empty($missing)) {
                    $response['message'] = "Please fill in all required fields: " . implode(', ', $missing);
                    echo json_encode($response);
                    exit;
                }
                
                // Validate percentage
                $percentage = floatval($_POST['rate_percentage']);
                if ($percentage < 0 || $percentage > 100) {
                    $response['message'] = "Percentage must be between 0 and 100!";
                    echo json_encode($response);
                    exit;
                }
                
                // Prepare parameters
                $rate_name = $_POST['rate_name'];
                $rate_type = $_POST['rate_type'] ?? 'standard';
                $is_default = isset($_POST['is_default']) && $_POST['is_default'] == '1' ? 1 : 0;
                $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 1;
                
                // If setting as default, unset other defaults
                if ($is_default) {
                    $stmt = $conn->prepare("UPDATE vat_rates SET is_default = 0 WHERE is_default = 1");
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Check if rate name already exists
                $check_stmt = $conn->prepare("SELECT vat_id FROM vat_rates WHERE rate_name = ?");
                $check_stmt->bind_param("s", $rate_name);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $response['message'] = "VAT rate with this name already exists!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
                
                // Insert VAT rate
                $stmt = $conn->prepare("INSERT INTO vat_rates (rate_name, percentage, rate_type, is_default, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("sdsiii", 
                    $rate_name,
                    $percentage,
                    $rate_type,
                    $is_default,
                    $is_active,
                    $user_id
                );
                
                if ($stmt->execute()) {
                    $new_vat_id = $stmt->insert_id;
                    
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'ADD_VAT_RATE', 
                        "Added new VAT rate: {$rate_name} ({$percentage}%)");
                    
                    $response['success'] = true;
                    $response['message'] = "VAT rate added successfully!";
                    $response['vat_id'] = $new_vat_id;
                } else {
                    $response['message'] = "Error adding VAT rate: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'edit_vat_rate':
                $vat_id = intval($_POST['vat_id']);
                
                if ($vat_id <= 0) {
                    $response['message'] = "Invalid VAT rate ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if VAT rate exists
                $check_stmt = $conn->prepare("SELECT vat_id FROM vat_rates WHERE vat_id = ?");
                $check_stmt->bind_param("i", $vat_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "VAT rate not found!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
                
                // Validate required fields
                $required = ['rate_name', 'rate_percentage'];
                $missing = [];
                foreach ($required as $field) {
                    if (empty(trim($_POST[$field] ?? ''))) {
                        $missing[] = str_replace('_', ' ', $field);
                    }
                }
                
                if (!empty($missing)) {
                    $response['message'] = "Please fill in all required fields: " . implode(', ', $missing);
                    echo json_encode($response);
                    exit;
                }
                
                // Validate percentage
                $percentage = floatval($_POST['rate_percentage']);
                if ($percentage < 0 || $percentage > 100) {
                    $response['message'] = "Percentage must be between 0 and 100!";
                    echo json_encode($response);
                    exit;
                }
                
                // Prepare parameters
                $rate_name = $_POST['rate_name'];
                $rate_type = $_POST['rate_type'] ?? 'standard';
                $is_default = isset($_POST['is_default']) && $_POST['is_default'] == '1' ? 1 : 0;
                $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;
                
                // Check if rate name already exists for another rate
                $check_stmt = $conn->prepare("SELECT vat_id FROM vat_rates WHERE rate_name = ? AND vat_id != ?");
                $check_stmt->bind_param("si", $rate_name, $vat_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $response['message'] = "VAT rate with this name already exists for another rate!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
                
                // If setting as default, unset other defaults
                if ($is_default) {
                    $stmt = $conn->prepare("UPDATE vat_rates SET is_default = 0 WHERE is_default = 1 AND vat_id != ?");
                    $stmt->bind_param("i", $vat_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Update VAT rate
                $stmt = $conn->prepare("UPDATE vat_rates SET 
                    rate_name = ?, percentage = ?, rate_type = ?, 
                    is_default = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE vat_id = ?");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("sdsiii", 
                    $rate_name,
                    $percentage,
                    $rate_type,
                    $is_default,
                    $is_active,
                    $vat_id
                );
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'EDIT_VAT_RATE', 
                        "Updated VAT rate: {$rate_name} ({$percentage}%)");
                    
                    $response['success'] = true;
                    $response['message'] = "VAT rate updated successfully!";
                } else {
                    $response['message'] = "Error updating VAT rate: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'delete_vat_rate':
                $vat_id = intval($_POST['vat_id']);
                
                if ($vat_id <= 0) {
                    $response['message'] = "Invalid VAT rate ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if VAT rate exists and get info for logging
                $check_stmt = $conn->prepare("SELECT rate_name, percentage, is_default FROM vat_rates WHERE vat_id = ?");
                $check_stmt->bind_param("i", $vat_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "VAT rate not found!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $vat_data = $check_result->fetch_assoc();
                $check_stmt->close();
                
                // Check if trying to delete default rate
                if ($vat_data['is_default'] == 1) {
                    $response['message'] = "Cannot delete the default VAT rate. Please set another rate as default first.";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if VAT rate is used in invoices
                $usage_check = $conn->prepare("SELECT COUNT(*) as usage_count FROM invoice_items WHERE vat_rate_id = ?");
                $usage_check->bind_param("i", $vat_id);
                $usage_check->execute();
                $usage_result = $usage_check->get_result()->fetch_assoc();
                $usage_check->close();
                
                if ($usage_result['usage_count'] > 0) {
                    $response['message'] = "Cannot delete VAT rate that is being used in invoices. Deactivate it instead.";
                    echo json_encode($response);
                    exit;
                }
                
                // Delete VAT rate
                $stmt = $conn->prepare("DELETE FROM vat_rates WHERE vat_id = ?");
                $stmt->bind_param("i", $vat_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'DELETE_VAT_RATE', 
                        "Deleted VAT rate: {$vat_data['rate_name']} ({$vat_data['percentage']}%)");
                    
                    $response['success'] = true;
                    $response['message'] = "VAT rate deleted successfully!";
                } else {
                    $response['message'] = "Error deleting VAT rate: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'get_vat_rate':
                $vat_id = intval($_POST['vat_id']);
                
                if ($vat_id <= 0) {
                    $response['message'] = "Invalid VAT rate ID!";
                    echo json_encode($response);
                    exit;
                }
                
                $stmt = $conn->prepare("SELECT * FROM vat_rates WHERE vat_id = ?");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("i", $vat_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $vat_rate = $result->fetch_assoc();
                    
                    // Format boolean values
                    $vat_rate['is_default'] = (bool)$vat_rate['is_default'];
                    $vat_rate['is_active'] = (bool)$vat_rate['is_active'];
                    $vat_rate['rate_type_text'] = getVATTypeText($vat_rate['rate_type']);
                    
                    $response['success'] = true;
                    $response['data'] = $vat_rate;
                } else {
                    $response['message'] = "VAT rate not found!";
                }
                $stmt->close();
                break;
                
            case 'get_vat_rates':
                $stmt = $conn->prepare("SELECT * FROM vat_rates ORDER BY is_default DESC, rate_name ASC");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $vat_rates = [];
                while ($row = $result->fetch_assoc()) {
                    // Format data
                    $row['is_default'] = (bool)$row['is_default'];
                    $row['is_active'] = (bool)$row['is_active'];
                    $row['rate_type_text'] = getVATTypeText($row['rate_type']);
                    $row['percentage_formatted'] = number_format($row['percentage'], 2) . '%';
                    
                    $vat_rates[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $vat_rates;
                $stmt->close();
                break;
                
            case 'get_vat_stats':
                // Get statistics
                $stats_stmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as total_rates,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_rates,
                        SUM(CASE WHEN is_default = 1 THEN 1 ELSE 0 END) as default_rates,
                        (SELECT percentage FROM vat_rates WHERE is_default = 1 LIMIT 1) as default_percentage
                    FROM vat_rates
                ");
                
                $stats_stmt->execute();
                $stats_result = $stats_stmt->get_result()->fetch_assoc();
                $stats_stmt->close();
                
                $response['success'] = true;
                $response['stats'] = [
                    'total_rates' => $stats_result['total_rates'] ?? 0,
                    'active_rates' => $stats_result['active_rates'] ?? 0,
                    'default_rates' => $stats_result['default_rates'] ?? 0,
                    'default_percentage' => $stats_result['default_percentage'] ?? 0
                ];
                break;
                
            default:
                $response['message'] = "Invalid action!";
        }
    } catch (Exception $e) {
        $response['message'] = "System Error: " . $e->getMessage();
        error_log("VAT API Error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Get initial data for page load
$initial_stats = [
    'total_rates' => 0,
    'active_rates' => 0,
    'default_rates' => 0,
    'default_percentage' => 0
];

$initial_rates = [];

// Get statistics and rates
try {
    // Get statistics
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_rates,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_rates,
            SUM(CASE WHEN is_default = 1 THEN 1 ELSE 0 END) as default_rates,
            (SELECT percentage FROM vat_rates WHERE is_default = 1 LIMIT 1) as default_percentage
        FROM vat_rates
    ");
    
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result()->fetch_assoc();
    $stats_stmt->close();
    
    if ($stats_result) {
        $initial_stats = array_merge($initial_stats, $stats_result);
    }
    
    // Get VAT rates
    $rates_stmt = $conn->prepare("SELECT * FROM vat_rates ORDER BY is_default DESC, rate_name ASC");
    $rates_stmt->execute();
    $rates_result = $rates_stmt->get_result();
    
    while ($row = $rates_result->fetch_assoc()) {
        $row['is_default'] = (bool)$row['is_default'];
        $row['is_active'] = (bool)$row['is_active'];
        $row['rate_type_text'] = getVATTypeText($row['rate_type']);
        $row['percentage_formatted'] = number_format($row['percentage'], 2) . '%';
        
        $initial_rates[] = $row;
    }
    $rates_stmt->close();
} catch (Exception $e) {
    error_log("Error loading initial VAT data: " . $e->getMessage());
}

// Close connection
$conn->close();
?>