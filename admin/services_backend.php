<?php
// Include configuration file
require_once '../config/db.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Create a new database connection for this backend script
$conn = connectDB();

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get action from POST request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'get_services':
        getServices($conn);
        break;
    
    case 'get_service':
        getService($conn);
        break;
    
    case 'add_service':
        addService($conn);
        break;
    
    case 'edit_service':
        editService($conn);
        break;
    
    case 'delete_service':
        deleteService($conn);
        break;
    
    case 'get_service_stats':
        getServiceStats($conn);
        break;
    
    case 'get_categories':
        getCategories($conn);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}

// Close the connection for this backend script
$conn->close();

// Function to log activity
function logActivity($conn, $action, $description) {
    $user_id = $_SESSION['user_id'] ?? null;
    $user_name = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Unknown User';
    $branch_id = $_SESSION['branch_id'] ?? null;
    $branch_name = $_SESSION['branch_name'] ?? 'Unknown Branch';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO activity_logs 
            (user_id, user_name, action, description, branch_id, branch_name, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssisss", 
        $user_id, 
        $user_name, 
        $action, 
        $description, 
        $branch_id, 
        $branch_name, 
        $ip_address, 
        $user_agent
    );
    
    $stmt->execute();
}

// Function to get all services
function getServices($conn) {
    $sql = "SELECT s.*, 
                   sc.category_name,
                   DATE_FORMAT(s.created_at, '%d/%m/%Y') as created_date,
                   DATE_FORMAT(s.updated_at, '%d/%m/%Y') as updated_date
            FROM services s
            LEFT JOIN service_categories sc ON s.service_category = sc.category_name
            ORDER BY s.service_name ASC";
    
    $result = $conn->query($sql);
    
    $services = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format price with MWK currency
            $row['price_formatted'] = 'MWK ' . number_format($row['price_mwk'], 2);
            $row['status'] = $row['is_active'] ? 'Active' : 'Inactive';
            $services[] = $row;
        }
    }
    
    // Log the activity
    logActivity($conn, 'VIEW_SERVICES', 'Viewed list of all services');
    
    echo json_encode([
        'success' => true,
        'data' => $services
    ]);
}

// Function to get single service
function getService($conn) {
    $service_id = intval($_POST['service_id']);
    
    $sql = "SELECT s.*, 
                   sc.category_name,
                   DATE_FORMAT(s.created_at, '%d/%m/%Y') as created_date,
                   DATE_FORMAT(s.updated_at, '%d/%m/%Y') as updated_date
            FROM services s
            LEFT JOIN service_categories sc ON s.service_category = sc.category_name
            WHERE s.service_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $service = $result->fetch_assoc();
        $service['price_formatted'] = 'MWK ' . number_format($service['price_mwk'], 2);
        
        // Log the activity
        logActivity($conn, 'VIEW_SERVICE', 'Viewed service details: ' . $service['service_name']);
        
        echo json_encode(['success' => true, 'data' => $service]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
    }
}

// Function to add new service
function addService($conn) {
    $service_name = trim($_POST['service_name']);
    $service_description = trim($_POST['service_description'] ?? '');
    $service_category = trim($_POST['service_category']);
    $price_mwk = floatval($_POST['price_mwk']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    if (empty($service_name) || empty($service_category) || $price_mwk <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields with valid values']);
        return;
    }
    
    $sql = "INSERT INTO services (service_name, service_description, service_category, price_mwk, is_active) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdi", $service_name, $service_description, $service_category, $price_mwk, $is_active);
    
    if ($stmt->execute()) {
        $service_id = $stmt->insert_id;
        
        // Log the activity
        $description = "Added new service: " . $service_name . " (MWK " . number_format($price_mwk, 2) . ")";
        logActivity($conn, 'ADD_SERVICE', $description);
        
        echo json_encode(['success' => true, 'message' => 'Service added successfully', 'service_id' => $service_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add service: ' . $conn->error]);
    }
}

// Function to edit service
function editService($conn) {
    $service_id = intval($_POST['service_id']);
    $service_name = trim($_POST['service_name']);
    $service_description = trim($_POST['service_description'] ?? '');
    $service_category = trim($_POST['service_category']);
    $price_mwk = floatval($_POST['price_mwk']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    if (empty($service_name) || empty($service_category) || $price_mwk <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields with valid values']);
        return;
    }
    
    // Get old service data for logging
    $old_sql = "SELECT service_name, price_mwk FROM services WHERE service_id = ?";
    $old_stmt = $conn->prepare($old_sql);
    $old_stmt->bind_param("i", $service_id);
    $old_stmt->execute();
    $old_result = $old_stmt->get_result();
    $old_service = $old_result->fetch_assoc();
    
    $sql = "UPDATE services 
            SET service_name = ?, 
                service_description = ?, 
                service_category = ?, 
                price_mwk = ?, 
                is_active = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE service_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdii", $service_name, $service_description, $service_category, $price_mwk, $is_active, $service_id);
    
    if ($stmt->execute()) {
        // Log the activity
        $old_name = $old_service['service_name'] ?? 'Unknown';
        $old_price = $old_service['price_mwk'] ?? 0;
        
        $changes = [];
        if ($old_name !== $service_name) {
            $changes[] = "name from '{$old_name}' to '{$service_name}'";
        }
        if ($old_price != $price_mwk) {
            $changes[] = "price from MWK " . number_format($old_price, 2) . " to MWK " . number_format($price_mwk, 2);
        }
        if (!empty($changes)) {
            $description = "Updated service '{$service_name}': " . implode(", ", $changes);
        } else {
            $description = "Updated service '{$service_name}' (no major changes)";
        }
        
        logActivity($conn, 'EDIT_SERVICE', $description);
        
        echo json_encode(['success' => true, 'message' => 'Service updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update service: ' . $conn->error]);
    }
}

// Function to delete service
function deleteService($conn) {
    $service_id = intval($_POST['service_id']);
    
    // Check if service exists
    $check_sql = "SELECT service_name, price_mwk FROM services WHERE service_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $service_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
        return;
    }
    
    $service = $result->fetch_assoc();
    $service_name = $service['service_name'];
    $service_price = $service['price_mwk'];
    
    // Delete service
    $sql = "DELETE FROM services WHERE service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    
    if ($stmt->execute()) {
        // Log the activity
        $description = "Deleted service: " . $service_name . " (MWK " . number_format($service_price, 2) . ")";
        logActivity($conn, 'DELETE_SERVICE', $description);
        
        echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete service: ' . $conn->error]);
    }
}

// Function to get service statistics
function getServiceStats($conn) {
    $stats = [];
    
    // Total services
    $sql = "SELECT COUNT(*) as total_services FROM services";
    $result = $conn->query($sql);
    $stats['total_services'] = $result->fetch_assoc()['total_services'];
    
    // Active services
    $sql = "SELECT COUNT(*) as active_services FROM services WHERE is_active = 1";
    $result = $conn->query($sql);
    $stats['active_services'] = $result->fetch_assoc()['active_services'];
    
    // Total value of all services
    $sql = "SELECT SUM(price_mwk) as total_value FROM services WHERE is_active = 1";
    $result = $conn->query($sql);
    $stats['total_value'] = number_format($result->fetch_assoc()['total_value'] ?? 0, 2);
    
    // Average price
    $sql = "SELECT AVG(price_mwk) as avg_price FROM services WHERE is_active = 1";
    $result = $conn->query($sql);
    $stats['avg_price'] = number_format($result->fetch_assoc()['avg_price'] ?? 0, 2);
    
    // Count by category
    $sql = "SELECT service_category, COUNT(*) as count 
            FROM services 
            WHERE is_active = 1 
            GROUP BY service_category";
    $result = $conn->query($sql);
    $stats['by_category'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['by_category'][] = $row;
    }
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

// Function to get categories
function getCategories($conn) {
    $sql = "SELECT category_name FROM service_categories WHERE is_active = 1 ORDER BY category_name";
    $result = $conn->query($sql);
    
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category_name'];
        }
    }
    
    echo json_encode(['success' => true, 'categories' => $categories]);
}