<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Ensure no output before JSON response
ob_start();

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
$current_branch_id = $_SESSION['branch_id'] ?? null;

// Database connection
$conn = connectDB();

// Log activity function
function logActivity($conn, $user_id, $user_name, $action, $description, $branch_id = null, $branch_name = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_name, action, description, branch_id, branch_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssisss", $user_id, $user_name, $action, $description, $branch_id, $branch_name, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Helper Functions
function generateSupplierCode($conn) {
    $prefix = 'SUP';
    $year = date('Y');
    
    // Get the last supplier code for this year
    $stmt = $conn->prepare("SELECT supplier_code FROM suppliers WHERE supplier_code LIKE ? ORDER BY supplier_id DESC LIMIT 1");
    $like_pattern = $prefix . '-' . $year . '-%';
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_code = $result->fetch_assoc()['supplier_code'];
        $parts = explode('-', $last_code);
        $last_number = intval(end($parts));
        $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_number = '0001';
    }
    
    $stmt->close();
    return $prefix . '-' . $year . '-' . $new_number;
}

function getBranchName($conn, $branch_id) {
    if (!$branch_id) return null;
    
    $stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $branch = $result->fetch_assoc();
    $stmt->close();
    
    return $branch['branch_name'] ?? null;
}

function getInitials($company_name) {
    $words = preg_split('/\s+/', trim($company_name));
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
    
    return substr($initials, 0, 2);
}

function getLogoColor($supplier_id) {
    $colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#d35400'];
    $color_index = $supplier_id % count($colors);
    return $colors[$color_index];
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Custom ucwords function for JavaScript compatibility
function formatStatusText($status) {
    $status_map = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
        'on_hold' => 'On Hold'
    ];
    
    return $status_map[$status] ?? ucwords(str_replace('_', ' ', $status));
}

function formatCategoryText($category) {
    $category_map = [
        'pharmaceutical' => 'Pharmaceutical',
        'medical' => 'Medical Equipment',
        'laboratory' => 'Laboratory Supplies',
        'consumables' => 'Medical Consumables',
        'generic' => 'Generic Medicines',
        'branded' => 'Branded Medicines',
        'other' => 'Other'
    ];
    
    return $category_map[$category] ?? ucfirst($category);
}

function formatSupplierTypeText($type) {
    $type_map = [
        'manufacturer' => 'Manufacturer',
        'distributor' => 'Distributor',
        'wholesaler' => 'Wholesaler',
        'retailer' => 'Retailer',
        'importer' => 'Importer'
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
            case 'add_supplier':
                // Validate required fields
                $required = ['company_name', 'category', 'supplier_type', 'contact_person', 'email', 'phone', 'address', 'city'];
                $missing = [];
                foreach ($required as $field) {
                    if (empty(trim($_POST[$field] ?? ''))) {
                        $missing[] = $field;
                    }
                }
                
                if (!empty($missing)) {
                    $response['message'] = "Please fill in all required fields: " . implode(', ', array_map(function($field) {
                        return str_replace('_', ' ', ucfirst($field));
                    }, $missing));
                    echo json_encode($response);
                    exit;
                }
                
                // Validate email
                if (!validateEmail($_POST['email'])) {
                    $response['message'] = "Invalid email address format!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if email already exists
                $check_stmt = $conn->prepare("SELECT company_name FROM suppliers WHERE email = ?");
                $check_stmt->bind_param("s", $_POST['email']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $existing_supplier = $check_result->fetch_assoc();
                    $response['message'] = "Email already exists for supplier: " . $existing_supplier['company_name'];
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
                
                // Check if phone already exists
                $check_phone = $conn->prepare("SELECT company_name FROM suppliers WHERE phone = ?");
                $check_phone->bind_param("s", $_POST['phone']);
                $check_phone->execute();
                $phone_result = $check_phone->get_result();
                
                if ($phone_result->num_rows > 0) {
                    $existing_supplier = $phone_result->fetch_assoc();
                    $response['message'] = "Phone number already exists for supplier: " . $existing_supplier['company_name'];
                    $check_phone->close();
                    echo json_encode($response);
                    exit;
                }
                $check_phone->close();
                
                // Generate supplier code
                $supplier_code = generateSupplierCode($conn);
                
                // Prepare parameters with defaults
                $country = !empty($_POST['country']) ? $_POST['country'] : 'Malawi';
                $payment_terms = !empty($_POST['payment_terms']) ? $_POST['payment_terms'] : null;
                $avg_delivery_time_days = !empty($_POST['avg_delivery_time_days']) ? intval($_POST['avg_delivery_time_days']) : 3;
                $products_supplied = !empty($_POST['products_supplied']) ? $_POST['products_supplied'] : null;
                $additional_notes = !empty($_POST['additional_notes']) ? $_POST['additional_notes'] : null;
                $status = !empty($_POST['status']) ? $_POST['status'] : 'active';
                $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
                
               // Just verify session is still valid
                if (!$user_id) {
                    $response['message'] = "Session expired. Please log in again.";
                    echo json_encode($response);
                    exit;
                }
                                
                // Insert supplier
                $stmt = $conn->prepare("INSERT INTO suppliers (
                    supplier_code, company_name, supplier_type, category, contact_person, email, phone, 
                    address, city, country, payment_terms, avg_delivery_time_days, products_supplied, 
                    additional_notes, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("sssssssssssssssi", 
                    $supplier_code,
                    $_POST['company_name'],
                    $_POST['supplier_type'],
                    $_POST['category'],
                    $_POST['contact_person'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['city'],
                    $country,
                    $payment_terms,
                    $avg_delivery_time_days,
                    $products_supplied,
                    $additional_notes,
                    $status,
                    $user_id
                );
                
                if ($stmt->execute()) {
                    $new_supplier_id = $stmt->insert_id;
                    
                    // If branch is specified, link supplier to branch
                    if ($branch_id) {
                        $branch_stmt = $conn->prepare("INSERT INTO supplier_branches (supplier_id, branch_id, is_primary) VALUES (?, ?, ?)");
                        $is_primary = 1;
                        $branch_stmt->bind_param("iii", $new_supplier_id, $branch_id, $is_primary);
                        if (!$branch_stmt->execute()) {
                            error_log("Failed to link supplier to branch: " . $branch_stmt->error);
                        }
                        $branch_stmt->close();
                    }
                    
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'ADD_SUPPLIER', 
                        "Added new supplier: {$_POST['company_name']} ({$supplier_code})", 
                        $branch_id, getBranchName($conn, $branch_id));
                    
                    $response['success'] = true;
                    $response['message'] = "Supplier added successfully!";
                    $response['supplier_id'] = $new_supplier_id;
                    $response['supplier_code'] = $supplier_code;
                } else {
                    $error_message = $stmt->error;
                    if (strpos($error_message, 'Duplicate entry') !== false) {
                        if (strpos($error_message, 'email') !== false) {
                            $response['message'] = "Email already exists for another supplier!";
                        } elseif (strpos($error_message, 'phone') !== false) {
                            $response['message'] = "Phone number already exists for another supplier!";
                        } else {
                            $response['message'] = "Duplicate entry detected. Please check your input.";
                        }
                    } else {
                        $response['message'] = "Error adding supplier: " . $error_message;
                    }
                }
                $stmt->close();
                break;
                
            case 'edit_supplier':
                $supplier_id_edit = intval($_POST['supplier_id']);
                
                if ($supplier_id_edit <= 0) {
                    $response['message'] = "Invalid supplier ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if supplier exists
                $check_stmt = $conn->prepare("SELECT company_name, email, phone FROM suppliers WHERE supplier_id = ?");
                $check_stmt->bind_param("i", $supplier_id_edit);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "Supplier not found!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $supplier_data = $check_result->fetch_assoc();
                $check_stmt->close();
                
                // Check if email changed and if new email exists
                if ($supplier_data['email'] !== $_POST['email']) {
                    $check_stmt = $conn->prepare("SELECT company_name FROM suppliers WHERE email = ? AND supplier_id != ?");
                    $check_stmt->bind_param("si", $_POST['email'], $supplier_id_edit);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $existing_supplier = $check_result->fetch_assoc();
                        $response['message'] = "Email already exists for supplier: " . $existing_supplier['company_name'];
                        $check_stmt->close();
                        echo json_encode($response);
                        exit;
                    }
                    $check_stmt->close();
                }
                
                // Check if phone changed and if new phone exists
                if ($supplier_data['phone'] !== $_POST['phone']) {
                    $check_stmt = $conn->prepare("SELECT company_name FROM suppliers WHERE phone = ? AND supplier_id != ?");
                    $check_stmt->bind_param("si", $_POST['phone'], $supplier_id_edit);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $existing_supplier = $check_result->fetch_assoc();
                        $response['message'] = "Phone number already exists for supplier: " . $existing_supplier['company_name'];
                        $check_stmt->close();
                        echo json_encode($response);
                        exit;
                    }
                    $check_stmt->close();
                }
                
                // Validate email
                if (!validateEmail($_POST['email'])) {
                    $response['message'] = "Invalid email address format!";
                    echo json_encode($response);
                    exit;
                }
                
                // Update supplier
                $stmt = $conn->prepare("UPDATE suppliers SET 
                    company_name = ?, 
                    supplier_type = ?, 
                    category = ?, 
                    contact_person = ?, 
                    email = ?, 
                    phone = ?, 
                    address = ?, 
                    city = ?, 
                    country = ?, 
                    payment_terms = ?, 
                    avg_delivery_time_days = ?, 
                    products_supplied = ?, 
                    additional_notes = ?, 
                    status = ?,
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE supplier_id = ?");
                
                $country = $_POST['country'] ?? 'Malawi';
                $payment_terms = $_POST['payment_terms'] ?? null;
                $avg_delivery_time_days = !empty($_POST['avg_delivery_time_days']) ? intval($_POST['avg_delivery_time_days']) : 3;
                $products_supplied = !empty($_POST['products_supplied']) ? $_POST['products_supplied'] : null;
                $additional_notes = !empty($_POST['additional_notes']) ? $_POST['additional_notes'] : null;
                $status = $_POST['status'] ?? 'active';
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("ssssssssssssssi", 
                    $_POST['company_name'],
                    $_POST['supplier_type'],
                    $_POST['category'],
                    $_POST['contact_person'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['city'],
                    $country,
                    $payment_terms,
                    $avg_delivery_time_days,
                    $products_supplied,
                    $additional_notes,
                    $status,
                    $supplier_id_edit
                );
                
                if ($stmt->execute()) {
                    // Update branch relationship if changed
                    if (!empty($_POST['branch_id'])) {
                        // Remove existing primary branch
                        $remove_stmt = $conn->prepare("DELETE FROM supplier_branches WHERE supplier_id = ? AND is_primary = 1");
                        $remove_stmt->bind_param("i", $supplier_id_edit);
                        $remove_stmt->execute();
                        $remove_stmt->close();
                        
                        // Add new primary branch
                        $branch_stmt = $conn->prepare("INSERT INTO supplier_branches (supplier_id, branch_id, is_primary) VALUES (?, ?, ?)");
                        $is_primary = 1;
                        $branch_stmt->bind_param("iii", $supplier_id_edit, $_POST['branch_id'], $is_primary);
                        $branch_stmt->execute();
                        $branch_stmt->close();
                    }
                    
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'EDIT_SUPPLIER', 
                        "Updated supplier: {$_POST['company_name']}", 
                        $_POST['branch_id'] ?? null, getBranchName($conn, $_POST['branch_id'] ?? null));
                    
                    $response['success'] = true;
                    $response['message'] = "Supplier updated successfully!";
                } else {
                    $error_message = $stmt->error;
                    if (strpos($error_message, 'Duplicate entry') !== false) {
                        if (strpos($error_message, 'email') !== false) {
                            $response['message'] = "Email already exists for another supplier!";
                        } elseif (strpos($error_message, 'phone') !== false) {
                            $response['message'] = "Phone number already exists for another supplier!";
                        } else {
                            $response['message'] = "Duplicate entry detected. Please check your input.";
                        }
                    } else {
                        $response['message'] = "Error updating supplier: " . $error_message;
                    }
                }
                $stmt->close();
                break;
                
            case 'delete_supplier':
                $supplier_id_delete = intval($_POST['supplier_id']);
                
                if ($supplier_id_delete <= 0) {
                    $response['message'] = "Invalid supplier ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Get supplier info for logging
                $supplier_stmt = $conn->prepare("SELECT company_name, supplier_code FROM suppliers WHERE supplier_id = ?");
                $supplier_stmt->bind_param("i", $supplier_id_delete);
                $supplier_stmt->execute();
                $supplier_result = $supplier_stmt->get_result();
                
                if ($supplier_result->num_rows === 0) {
                    $response['message'] = "Supplier not found!";
                    $supplier_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $supplier_data = $supplier_result->fetch_assoc();
                $supplier_stmt->close();
                
                // Check if supplier has any orders
                $order_check = $conn->prepare("SELECT COUNT(*) as order_count FROM supplier_orders WHERE supplier_id = ?");
                $order_check->bind_param("i", $supplier_id_delete);
                $order_check->execute();
                $order_result = $order_check->get_result()->fetch_assoc();
                $order_check->close();
                
                if ($order_result['order_count'] > 0) {
                    $response['message'] = "Cannot delete supplier with existing orders. Please deactivate instead.";
                    echo json_encode($response);
                    exit;
                }
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Delete supplier branches first
                    $delete_branches = $conn->prepare("DELETE FROM supplier_branches WHERE supplier_id = ?");
                    $delete_branches->bind_param("i", $supplier_id_delete);
                    $delete_branches->execute();
                    $delete_branches->close();
                    
                    // Delete supplier products
                    $delete_products = $conn->prepare("DELETE FROM supplier_products WHERE supplier_id = ?");
                    $delete_products->bind_param("i", $supplier_id_delete);
                    $delete_products->execute();
                    $delete_products->close();
                    
                    // Delete supplier
                    $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
                    $stmt->bind_param("i", $supplier_id_delete);
                    
                    if ($stmt->execute()) {
                        // Log activity
                        logActivity($conn, $user_id, $user_name, 'DELETE_SUPPLIER', 
                            "Deleted supplier: {$supplier_data['company_name']} ({$supplier_data['supplier_code']})");
                        
                        $conn->commit();
                        
                        $response['success'] = true;
                        $response['message'] = "Supplier deleted successfully!";
                    } else {
                        throw new Exception("Failed to delete supplier: " . $stmt->error);
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $conn->rollback();
                    $response['message'] = $e->getMessage();
                }
                break;
                
            case 'toggle_supplier_status':
                $supplier_id_toggle = intval($_POST['supplier_id']);
                $new_status = $_POST['status'];
                
                if ($supplier_id_toggle <= 0) {
                    $response['message'] = "Invalid supplier ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Get supplier info for logging
                $supplier_stmt = $conn->prepare("SELECT company_name FROM suppliers WHERE supplier_id = ?");
                $supplier_stmt->bind_param("i", $supplier_id_toggle);
                $supplier_stmt->execute();
                $supplier_result = $supplier_stmt->get_result();
                
                if ($supplier_result->num_rows === 0) {
                    $response['message'] = "Supplier not found!";
                    $supplier_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $supplier_data = $supplier_result->fetch_assoc();
                $supplier_stmt->close();
                
                // Update supplier status
                $stmt = $conn->prepare("UPDATE suppliers SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE supplier_id = ?");
                $stmt->bind_param("si", $new_status, $supplier_id_toggle);
                
                if ($stmt->execute()) {
                    $action = $new_status === 'active' ? 'ENABLE_SUPPLIER' : 'DISABLE_SUPPLIER';
                    $action_text = $new_status === 'active' ? 'Enabled' : 'Disabled';
                    
                    // Log activity
                    logActivity($conn, $user_id, $user_name, $action, 
                        "{$action_text} supplier: {$supplier_data['company_name']}");
                    
                    $response['success'] = true;
                    $response['message'] = "Supplier status updated successfully!";
                } else {
                    $response['message'] = "Error updating status: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'get_supplier':
                $supplier_id_get = intval($_POST['supplier_id']);
                
                if ($supplier_id_get <= 0) {
                    $response['message'] = "Invalid supplier ID!";
                    echo json_encode($response);
                    exit;
                }
                
                $stmt = $conn->prepare("
                    SELECT s.*, 
                    b.branch_id,
                    b.branch_name,
                    (SELECT COUNT(*) FROM supplier_orders WHERE supplier_id = s.supplier_id) as total_orders,
                    (SELECT SUM(total_amount) FROM supplier_orders WHERE supplier_id = s.supplier_id) as total_value
                    FROM suppliers s
                    LEFT JOIN supplier_branches sb ON s.supplier_id = sb.supplier_id AND sb.is_primary = 1
                    LEFT JOIN branches b ON sb.branch_id = b.branch_id
                    WHERE s.supplier_id = ?
                ");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("i", $supplier_id_get);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $supplier = $result->fetch_assoc();
                    
                    // Format text values for display
                    $supplier['category_text'] = formatCategoryText($supplier['category']);
                    $supplier['supplier_type_text'] = formatSupplierTypeText($supplier['supplier_type']);
                    $supplier['status_text'] = formatStatusText($supplier['status']);
                    
                    // Get supplier performance metrics
                    $performance_stmt = $conn->prepare("
                        SELECT 
                            COUNT(CASE WHEN status = 'delivered' AND actual_delivery_date <= expected_delivery_date THEN 1 END) as on_time_deliveries,
                            COUNT(*) as total_deliveries
                        FROM supplier_orders 
                        WHERE supplier_id = ? AND status = 'delivered'
                    ");
                    $performance_stmt->bind_param("i", $supplier_id_get);
                    $performance_stmt->execute();
                    $performance_result = $performance_stmt->get_result()->fetch_assoc();
                    $performance_stmt->close();
                    
                    $supplier['on_time_delivery_rate'] = isset($performance_result['total_deliveries']) && $performance_result['total_deliveries'] > 0 
                        ? round(($performance_result['on_time_deliveries'] / $performance_result['total_deliveries']) * 100, 2)
                        : 0;
                    
                    // Use rating from suppliers table
                    $supplier['avg_rating'] = $supplier['rating'];
                    
                    $response['success'] = true;
                    $response['data'] = $supplier;
                } else {
                    $response['message'] = "Supplier not found!";
                }
                $stmt->close();
                break;
                
            case 'get_suppliers':
                // Build query based on filters
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Apply filters
                if (!empty($_POST['search'])) {
                    $where_clauses[] = "(s.company_name LIKE ? OR s.contact_person LIKE ? OR s.email LIKE ? OR s.supplier_code LIKE ?)";
                    $search_term = "%" . trim($_POST['search']) . "%";
                    for ($i = 0; $i < 4; $i++) {
                        $params[] = $search_term;
                        $types .= "s";
                    }
                }
                
                if (!empty($_POST['status'])) {
                    $where_clauses[] = "s.status = ?";
                    $params[] = $_POST['status'];
                    $types .= "s";
                }
                
                if (!empty($_POST['category'])) {
                    $where_clauses[] = "s.category = ?";
                    $params[] = $_POST['category'];
                    $types .= "s";
                }
                
                if (!empty($_POST['supplier_type'])) {
                    $where_clauses[] = "s.supplier_type = ?";
                    $params[] = $_POST['supplier_type'];
                    $types .= "s";
                }
                
                // Filter by branch if user is not admin and has branch restriction
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "sb.branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                } elseif (!empty($_POST['branch'])) {
                    $where_clauses[] = "sb.branch_id = ?";
                    $params[] = intval($_POST['branch']);
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get total count
                $count_sql = "SELECT COUNT(DISTINCT s.supplier_id) as total 
                             FROM suppliers s
                             LEFT JOIN supplier_branches sb ON s.supplier_id = sb.supplier_id
                             WHERE $where_sql";
                
                $count_stmt = $conn->prepare($count_sql);
                if (!empty($params)) {
                    $count_stmt->bind_param($types, ...$params);
                }
                $count_stmt->execute();
                $count_result = $count_stmt->get_result()->fetch_assoc();
                $total_suppliers = $count_result['total'] ?? 0;
                $count_stmt->close();
                
                // Get sorting
                $sort_options = [
                    'name_asc' => 's.company_name ASC',
                    'name_desc' => 's.company_name DESC',
                    'rating_high' => 's.rating DESC',
                    'rating_low' => 's.rating ASC',
                    'recent' => 's.created_at DESC',
                    'oldest' => 's.created_at ASC'
                ];
                
                $sort_by = $_POST['sort'] ?? 'name_asc';
                $sort_sql = $sort_options[$sort_by] ?? 's.company_name ASC';
                
                // Pagination
                $page = max(1, intval($_POST['page'] ?? 1));
                $limit = max(1, intval($_POST['limit'] ?? 10));
                $offset = ($page - 1) * $limit;
                
                $sql = "SELECT s.*, 
                       b.branch_name,
                       (SELECT COUNT(*) FROM supplier_orders WHERE supplier_id = s.supplier_id) as total_orders
                       FROM suppliers s
                       LEFT JOIN supplier_branches sb ON s.supplier_id = sb.supplier_id AND sb.is_primary = 1
                       LEFT JOIN branches b ON sb.branch_id = b.branch_id
                       WHERE $where_sql 
                       GROUP BY s.supplier_id
                       ORDER BY $sort_sql 
                       LIMIT ? OFFSET ?";
                
                $stmt = $conn->prepare($sql);
                
                // Add pagination parameters
                $all_params = array_merge($params, [$limit, $offset]);
                $all_types = $types . "ii";
                
                if (!empty($all_types)) {
                    $stmt->bind_param($all_types, ...$all_params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                $suppliers = [];
                $sn = $offset + 1;
                while ($row = $result->fetch_assoc()) {
                    $row['sn'] = $sn++;
                    
                    // Generate logo based on company name
                    $initials = getInitials($row['company_name']);
                    $logo_color = getLogoColor($row['supplier_id']);
                    
                    $row['logo_initials'] = $initials;
                    $row['logo_color'] = $logo_color;
                    $row['category_text'] = formatCategoryText($row['category']);
                    $row['supplier_type_text'] = formatSupplierTypeText($row['supplier_type']);
                    $row['status_text'] = formatStatusText($row['status']);
                    
                    // Format rating
                    $row['rating'] = $row['rating'] ? round($row['rating'], 1) : 0;
                    
                    // Get products count
                    $products_sql = "SELECT COUNT(*) as products_count FROM supplier_products WHERE supplier_id = ? AND in_stock = 1";
                    $prod_stmt = $conn->prepare($products_sql);
                    $prod_stmt->bind_param("i", $row['supplier_id']);
                    $prod_stmt->execute();
                    $prod_result = $prod_stmt->get_result()->fetch_assoc();
                    $prod_stmt->close();
                    
                    $row['products_count'] = $prod_result['products_count'] ?? 0;
                    
                    // Get last delivery date
                    $last_delivery_sql = "SELECT MAX(order_date) as last_delivery FROM supplier_orders WHERE supplier_id = ? AND status = 'delivered'";
                    $last_stmt = $conn->prepare($last_delivery_sql);
                    $last_stmt->bind_param("i", $row['supplier_id']);
                    $last_stmt->execute();
                    $last_result = $last_stmt->get_result()->fetch_assoc();
                    $last_stmt->close();
                    
                    $row['last_delivery'] = $last_result['last_delivery'] ?? null;
                    if ($row['last_delivery']) {
                        $last_date = new DateTime($row['last_delivery']);
                        $now = new DateTime();
                        $interval = $last_date->diff($now);
                        
                        if ($interval->days == 0) {
                            $row['last_delivery_text'] = "Today";
                        } elseif ($interval->days == 1) {
                            $row['last_delivery_text'] = "Yesterday";
                        } elseif ($interval->days < 7) {
                            $row['last_delivery_text'] = $interval->days . " days ago";
                        } elseif ($interval->days < 30) {
                            $row['last_delivery_text'] = floor($interval->days / 7) . " weeks ago";
                        } else {
                            $row['last_delivery_text'] = floor($interval->days / 30) . " months ago";
                        }
                    } else {
                        $row['last_delivery_text'] = "No deliveries yet";
                    }
                    
                    $suppliers[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $suppliers;
                $response['total'] = $total_suppliers;
                $response['page'] = $page;
                $response['pages'] = ceil($total_suppliers / $limit);
                
                $stmt->close();
                break;
                
            case 'get_supplier_stats':
                // Build where clause
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Filter by branch if user is not admin
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "sb.branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get overall statistics
                $stats_sql = "SELECT 
                    COUNT(DISTINCT s.supplier_id) as total_suppliers,
                    SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END) as active_suppliers,
                    AVG(s.rating) as avg_rating,
                    SUM(CASE WHEN s.rating >= 4 THEN 1 ELSE 0 END) as top_rated_suppliers
                    FROM suppliers s
                    LEFT JOIN supplier_branches sb ON s.supplier_id = sb.supplier_id
                    WHERE $where_sql";
                
                $stmt = $conn->prepare($stats_sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $stats_result = $stmt->fetch_assoc();
                $stmt->close();
                
                // Get total products count
                $products_sql = "SELECT COUNT(*) as total_products FROM supplier_products sp
                               INNER JOIN suppliers s ON sp.supplier_id = s.supplier_id
                               LEFT JOIN supplier_branches sb ON s.supplier_id = sb.supplier_id
                               WHERE sp.in_stock = 1 AND $where_sql";
                
                $prod_stmt = $conn->prepare($products_sql);
                if (!empty($params)) {
                    $prod_stmt->bind_param($types, ...$params);
                }
                $prod_stmt->execute();
                $products_result = $prod_stmt->get_result()->fetch_assoc();
                $prod_stmt->close();
                
                // Get pending deliveries
                $pending_sql = "SELECT COUNT(*) as pending_deliveries 
                              FROM supplier_orders so
                              LEFT JOIN suppliers s ON so.supplier_id = s.supplier_id
                              LEFT JOIN supplier_branches sb ON s.supplier_id = sb.supplier_id
                              WHERE so.status IN ('pending', 'processing') 
                              AND so.expected_delivery_date < CURDATE()
                              AND $where_sql";
                
                $pend_stmt = $conn->prepare($pending_sql);
                if (!empty($params)) {
                    $pend_stmt->bind_param($types, ...$params);
                }
                $pend_stmt->execute();
                $pending_result = $pend_stmt->get_result()->fetch_assoc();
                $pend_stmt->close();
                
                $response['success'] = true;
                $response['stats'] = [
                    'total_suppliers' => $stats_result['total_suppliers'] ?? 0,
                    'active_suppliers' => $stats_result['active_suppliers'] ?? 0,
                    'top_rated_suppliers' => $stats_result['top_rated_suppliers'] ?? 0,
                    'total_products' => $products_result['total_products'] ?? 0,
                    'pending_deliveries' => $pending_result['pending_deliveries'] ?? 0,
                    'avg_rating' => round($stats_result['avg_rating'] ?? 0, 1)
                ];
                break;
                
            default:
                $response['message'] = "Invalid action!";
        }
    } catch (Exception $e) {
        $response['message'] = "System Error: " . $e->getMessage();
        error_log("Suppliers API Error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Get initial data for page load
$initial_stats = [
    'total_suppliers' => 0,
    'active_suppliers' => 0,
    'top_rated_suppliers' => 0,
    'total_products' => 0,
    'pending_deliveries' => 0,
    'avg_rating' => 0
];

// Get statistics
try {
    $stats_sql = "SELECT 
        COUNT(*) as total_suppliers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_suppliers,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as top_rated_suppliers
        FROM suppliers";
    
    $stats_result = $conn->query($stats_sql);
    if ($stats_row = $stats_result->fetch_assoc()) {
        $initial_stats = array_merge($initial_stats, $stats_row);
    }
} catch (Exception $e) {
    error_log("Error loading initial stats: " . $e->getMessage());
}

// Get branches for filter
$branches = [];
try {
    if ($user_role === 'admin') {
        $branches_result = $conn->query("SELECT branch_id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name");
    } else if ($current_branch_id) {
        $branches_stmt = $conn->prepare("SELECT branch_id, branch_name FROM branches WHERE branch_id = ? AND status = 'active'");
        $branches_stmt->bind_param("i", $current_branch_id);
        $branches_stmt->execute();
        $branches_result = $branches_stmt->get_result();
    } else {
        $branches_result = false;
    }
    
    if ($branches_result) {
        while ($row = $branches_result->fetch_assoc()) {
            $branches[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error loading branches: " . $e->getMessage());
}

// Get initial suppliers list
$initial_suppliers = [];
try {
    $suppliers_sql = "SELECT s.*, b.branch_name FROM suppliers s
                      LEFT JOIN supplier_branches sb ON s.supplier_id = sb.supplier_id AND sb.is_primary = 1
                      LEFT JOIN branches b ON sb.branch_id = b.branch_id
                      ORDER BY s.company_name ASC 
                      LIMIT 10";
    
    $suppliers_result = $conn->query($suppliers_sql);
    $sn = 1;
    while ($row = $suppliers_result->fetch_assoc()) {
        $row['sn'] = $sn++;
        $row['logo_initials'] = getInitials($row['company_name']);
        $row['logo_color'] = getLogoColor($row['supplier_id']);
        $row['category_text'] = formatCategoryText($row['category']);
        $row['supplier_type_text'] = formatSupplierTypeText($row['supplier_type']);
        $row['status_text'] = formatStatusText($row['status']);
        $initial_suppliers[] = $row;
    }
} catch (Exception $e) {
    error_log("Error loading initial suppliers: " . $e->getMessage());
}

// Close connection
$conn->close();

?>
<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers Management - Master Clinic</title>
    <!-- Include SweetAlert -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ================== Suppliers Management Styles ============== */
        .suppliers-management-section {
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray);
        }

        .page-header h1 {
            color: var(--primary);
            font-size: 2rem;
            margin: 0;
        }

        .page-header .page-actions {
            display: flex;
            gap: 15px;
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .action-btn.primary {
            background: var(--primary);
            color: white;
        }

        .action-btn.primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(42, 92, 139, 0.2);
        }

        .action-btn.success {
            background: var(--success);
            color: white;
        }

        .action-btn.success:hover {
            background: #219653;
            transform: translateY(-2px);
        }

        .action-btn.danger {
            background: var(--danger);
            color: white;
        }

        .action-btn.danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .action-btn.secondary {
            background: var(--light-gray);
            color: var(--dark-gray);
        }

        .action-btn.secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        .action-btn.warning {
            background: var(--warning);
            color: white;
        }

        .action-btn.warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        /* Statistics Cards */
        .suppliers-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            border-left: 4px solid;
        }

        .stat-card:nth-child(1) {
            border-color: var(--primary);
        }

        .stat-card:nth-child(2) {
            border-color: var(--accent);
        }

        .stat-card:nth-child(3) {
            border-color: var(--warning);
        }

        .stat-card:nth-child(4) {
            border-color: var(--success);
        }

        .stat-card:nth-child(5) {
            border-color: #9b59b6;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .stat-card:nth-child(1) .stat-icon {
            background: rgba(42, 92, 139, 0.1);
            color: var(--primary);
        }

        .stat-card:nth-child(2) .stat-icon {
            background: rgba(26, 188, 156, 0.1);
            color: var(--accent);
        }

        .stat-card:nth-child(3) .stat-icon {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .stat-card:nth-child(4) .stat-icon {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .stat-card:nth-child(5) .stat-icon {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .stat-content h3 {
            font-size: 2.5rem;
            margin: 0;
            color: var(--black);
        }

        .stat-content p {
            margin: 5px 0 0 0;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        /* Search and Filter Section */
        .search-filter-section {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 50px;
            border: 1px solid var(--gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1);
        }

        .search-box ion-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
            font-size: 1.2rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--black);
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 12px;
            border: 1px solid var(--gray);
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        /* Suppliers Table */
        .table-container {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            height: 600px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--gray);
            flex-shrink: 0;
        }

        .table-header h2 {
            color: var(--primary);
            margin: 0;
            font-size: 1.5rem;
        }

        .table-actions {
            display: flex;
            gap: 15px;
        }

        .table-responsive {
            overflow-y: auto;
            overflow-x: auto;
            flex: 1;
            max-height: calc(600px - 140px);
        }

        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--gray);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--dark-gray);
        }

        .suppliers-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .suppliers-table thead {
            background: var(--light-gray);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .suppliers-table th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--black);
            font-size: 0.95rem;
            border-bottom: 2px solid var(--gray);
            white-space: nowrap;
        }

        .suppliers-table tbody tr {
            border-bottom: 1px solid var(--gray);
            transition: background 0.3s ease;
        }

        .suppliers-table tbody tr:hover {
            background: rgba(42, 92, 139, 0.05);
        }

        .suppliers-table td {
            padding: 15px 20px;
            color: var(--black);
            font-size: 0.95rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Supplier Logo */
        .supplier-logo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: white;
        }

        .supplier-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Status Badges */
        .supplier-status {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .status-inactive {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .status-pending {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .status-onhold {
            background: rgba(149, 165, 166, 0.1);
            color: #7f8c8d;
        }

        /* Rating Stars */
        .rating-stars {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .star {
            color: #ddd;
            font-size: 1.1rem;
        }

        .star.filled {
            color: #f39c12;
        }

        .star.half {
            background: linear-gradient(90deg, #f39c12 50%, #ddd 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .rating-text {
            margin-left: 8px;
            font-weight: 500;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        /* Products Count */
        .products-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(41, 128, 185, 0.1);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .products-count ion-icon {
            font-size: 1rem;
        }

        /* Delivery Status */
        .delivery-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delivery-icon {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .delivery-time {
            font-size: 0.85rem;
            color: var(--dark-gray);
        }

        /* Contact Info */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--dark-gray);
        }

        .contact-item ion-icon {
            font-size: 1rem;
            color: var(--primary);
        }

        /* Actions */
        .supplier-actions {
            display: flex;
            gap: 8px;
        }

        .action-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .action-icon.edit {
            background: rgba(26, 188, 156, 0.1);
            color: var(--accent);
        }

        .action-icon.edit:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-2px);
        }

        .action-icon.delete {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .action-icon.delete:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }

        .action-icon.view {
            background: rgba(41, 128, 185, 0.1);
            color: var(--primary);
        }

        .action-icon.view:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-top: 1px solid var(--gray);
            flex-shrink: 0;
        }

        .pagination-info {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .pagination-controls {
            display: flex;
            gap: 10px;
        }

        .pagination-btn {
            padding: 8px 15px;
            background: var(--white);
            border: 1px solid var(--gray);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover:not(:disabled) {
            background: var(--light-gray);
        }

        .pagination-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(3px);
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--white);
            border-radius: 15px;
            padding: 0;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 1px solid var(--gray);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--dark-gray);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .modal-close:hover {
            background: var(--light-gray);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--black);
            font-size: 0.95rem;
        }

        .form-group label.required::after {
            content: " *";
            color: var(--danger);
        }

        .form-group input:required,
        .form-group select:required,
        .form-group textarea:required {
            border-left: 4px solid var(--primary);
        }

        .form-group input:invalid,
        .form-group select:invalid,
        .form-group textarea:invalid {
            border-color: var(--danger);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 1px solid var(--gray);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1);
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Modal Footer */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding: 20px 30px;
            border-top: 1px solid var(--gray);
        }

        /* Confirmation Modal */
        .confirmation-modal .modal-content {
            max-width: 500px;
        }

        .confirmation-body {
            padding: 30px;
            text-align: center;
        }

        .confirmation-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2.5rem;
        }

        .confirmation-icon.delete {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .confirmation-body h4 {
            margin: 0 0 15px 0;
            color: var(--black);
            font-size: 1.3rem;
        }

        .confirmation-body p {
            margin: 0 0 25px 0;
            color: var(--dark-gray);
            line-height: 1.6;
        }

        /* View Details Modal */
        .view-details-modal .modal-content {
            max-width: 700px;
        }

        .supplier-details {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .supplier-logo-large {
            width: 150px;
            height: 150px;
            border-radius: 20px;
            overflow: hidden;
            border: 3px solid var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            font-weight: bold;
            color: white;
        }

        .supplier-logo-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .supplier-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .supplier-info h4 {
            margin: 0;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--dark-gray);
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 500;
            color: var(--black);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
        }

        .empty-state ion-icon {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--dark-gray);
            margin: 0 0 10px 0;
        }

        .empty-state p {
            color: var(--dark-gray);
            margin: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .suppliers-management-section {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .page-header .page-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .suppliers-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .table-container {
                height: 500px;
            }

            .table-responsive {
                max-height: calc(500px - 140px);
            }

            .suppliers-table {
                min-width: 800px;
            }

            .supplier-actions {
                flex-direction: column;
                gap: 5px;
            }

            .action-icon {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }

            .supplier-details {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .suppliers-stats {
                grid-template-columns: 1fr;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .pagination {
                flex-direction: column;
                gap: 15px;
            }

            .action-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }

            .table-container {
                height: 400px;
            }

            .table-responsive {
                max-height: calc(400px - 140px);
            }
        }

        /* Loader */
        .loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 3000;
            align-items: center;
            justify-content: center;
        }

        .loader.active {
            display: flex;
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Error Message */
        .error-message {
            background: #fee;
            border: 1px solid #f99;
            color: #c00;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }

        .error-message.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- =============== Navigation ================ -->
    <div class="container">
        <!-- Overlay for closing sidebar on mobile -->
        <div class="overlay" id="overlay"></div>
        
        <?php include 'includes/sidebar.php'; ?>

        <!-- ========================= Main ==================== -->
        <div class="main">
            <?php include 'includes/navigation.php'; ?>

            <!-- Loader -->
            <div class="loader" id="loader">
                <div class="loader-spinner"></div>
            </div>

            <!-- ================== Suppliers Management Content ============== -->
            <div class="suppliers-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Suppliers Management</h1>
                        <p>Manage pharmaceutical suppliers, contracts, and performance</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshSuppliers()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <button class="action-btn primary" onclick="openAddSupplierModal()" id="addSupplierBtn">
                            <ion-icon name="business-outline"></ion-icon>
                            Add New Supplier
                        </button>
                    </div>
                </div>

                <!-- Suppliers Statistics -->
                <div class="suppliers-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="business-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalSuppliers"><?php echo $initial_stats['total_suppliers']; ?></h3>
                            <p>Total Suppliers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeSuppliers"><?php echo $initial_stats['active_suppliers']; ?></h3>
                            <p>Active Suppliers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="star-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="topRatedSuppliers"><?php echo $initial_stats['top_rated_suppliers']; ?></h3>
                            <p>Top Rated (4+ Stars)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="cube-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalProducts"><?php echo $initial_stats['total_products']; ?></h3>
                            <p>Total Products</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="pendingDeliveries"><?php echo $initial_stats['pending_deliveries']; ?></h3>
                            <p>Pending Deliveries</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search by supplier name, contact person, or email..." 
                               onkeyup="filterSuppliers()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterSuppliers()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                                <option value="on_hold">On Hold</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Category</label>
                            <select id="categoryFilter" onchange="filterSuppliers()">
                                <option value="">All Categories</option>
                                <option value="pharmaceutical">Pharmaceutical</option>
                                <option value="medical">Medical Equipment</option>
                                <option value="laboratory">Laboratory Supplies</option>
                                <option value="consumables">Medical Consumables</option>
                                <option value="generic">Generic Medicines</option>
                                <option value="branded">Branded Medicines</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Supplier Type</label>
                            <select id="supplierTypeFilter" onchange="filterSuppliers()">
                                <option value="">All Types</option>
                                <option value="manufacturer">Manufacturer</option>
                                <option value="distributor">Distributor</option>
                                <option value="wholesaler">Wholesaler</option>
                                <option value="retailer">Retailer</option>
                                <option value="importer">Importer</option>
                            </select>
                        </div>
                        
                        <?php if ($user_role === 'admin'): ?>
                        <div class="filter-group">
                            <label>Branch</label>
                            <select id="branchFilter" onchange="filterSuppliers()">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo htmlspecialchars($branch['branch_id']); ?>">
                                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterSuppliers()">
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="rating_high">Rating (High to Low)</option>
                                <option value="rating_low">Rating (Low to High)</option>
                                <option value="recent">Recently Added</option>
                                <option value="oldest">Oldest First</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button class="action-btn secondary" onclick="resetFilters()">
                            <ion-icon name="close-circle-outline"></ion-icon>
                            Clear Filters
                        </button>
                        <button class="action-btn primary" onclick="applyFilters()">
                            <ion-icon name="filter-outline"></ion-icon>
                            Apply Filters
                        </button>
                    </div>
                </div>

                <!-- Suppliers Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Pharmaceutical Suppliers</h2>
                        <div class="table-actions">
                            <!-- No table actions needed -->
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="suppliers-table">
                            <thead>
                                <tr>
                                    <th width="50">S/N</th>
                                    <th width="250">SUPPLIER DETAILS</th>
                                    <th width="250">CONTACT INFORMATION</th>
                                    <th width="120">CATEGORY</th>
                                    <th width="120">PRODUCTS</th>
                                    <th width="150">RATING</th>
                                    <th width="150">DELIVERY</th>
                                    <th width="100">STATUS</th>
                                    <th width="120">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="suppliersTableBody">
                                <?php if (empty($initial_suppliers)): ?>
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <ion-icon name="business-outline"></ion-icon>
                                        <h3>No suppliers found</h3>
                                        <p>Add your first supplier to get started</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_suppliers as $supplier): ?>
                                <?php 
                                    $statusClass = 'status-' . str_replace('_', '', $supplier['status']);
                                    $statusText = $supplier['status_text'];
                                    
                                    // Generate star rating
                                    $rating = $supplier['rating'] ?? 0;
                                    $fullStars = floor($rating);
                                    $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                    $starsHTML = '';
                                    
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $fullStars) {
                                            $starsHTML .= '<span class="star filled">★</span>';
                                        } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                                            $starsHTML .= '<span class="star half">★</span>';
                                        } else {
                                            $starsHTML .= '<span class="star">★</span>';
                                        }
                                    }
                                    $starsHTML .= '<span class="rating-text">' . number_format($rating, 1) . '</span>';
                                ?>
                                <tr>
                                    <td><?php echo $supplier['sn']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <div class="supplier-logo" style="background-color: <?php echo $supplier['logo_color']; ?>">
                                                <?php echo $supplier['logo_initials']; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($supplier['company_name']); ?></strong><br>
                                                <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($supplier['supplier_code'] ?? 'N/A'); ?></small><br>
                                                <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($supplier['supplier_type_text']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div class="contact-item">
                                                <ion-icon name="person-outline"></ion-icon>
                                                <span><?php echo htmlspecialchars($supplier['contact_person']); ?></span>
                                            </div>
                                            <div class="contact-item">
                                                <ion-icon name="mail-outline"></ion-icon>
                                                <span><?php echo htmlspecialchars($supplier['email']); ?></span>
                                            </div>
                                            <div class="contact-item">
                                                <ion-icon name="call-outline"></ion-icon>
                                                <span><?php echo htmlspecialchars($supplier['phone']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="padding: 5px 10px; background: rgba(42, 92, 139, 0.1); border-radius: 4px; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($supplier['category_text']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="products-count">
                                            <ion-icon name="cube-outline"></ion-icon>
                                            <?php echo $supplier['products_count'] ?? 0; ?> products
                                        </span>
                                    </td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php echo $starsHTML; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="delivery-status">
                                            <ion-icon name="calendar-outline" class="delivery-icon"></ion-icon>
                                            <div>
                                                <div><?php echo htmlspecialchars($supplier['last_delivery_text'] ?? 'No deliveries yet'); ?></div>
                                                <?php if (!empty($supplier['last_delivery'])): ?>
                                                <div class="delivery-time"><?php echo date('M d, Y', strtotime($supplier['last_delivery'])); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="supplier-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td>
                                        <div class="supplier-actions">
                                            <button class="action-icon view" title="View Details" onclick="viewSupplier(<?php echo $supplier['supplier_id']; ?>)">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon edit" title="Edit" onclick="editSupplier(<?php echo $supplier['supplier_id']; ?>)">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon delete" title="Delete" onclick="deleteSupplier(<?php echo $supplier['supplier_id']; ?>, '<?php echo addslashes($supplier['company_name']); ?>')">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="pagination-info" id="paginationInfo">
                            Showing <?php echo count($initial_suppliers) > 0 ? 1 : 0; ?> to <?php echo count($initial_suppliers); ?> of <?php echo $initial_stats['total_suppliers']; ?> entries
                        </div>
                        <?php if ($initial_stats['total_suppliers'] > 10): ?>
                        <div class="pagination-controls">
                            <button class="pagination-btn" onclick="changePage('prev')" disabled>
                                <ion-icon name="chevron-back-outline"></ion-icon>
                            </button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn" onclick="changePage('next')" disabled>
                                <ion-icon name="chevron-forward-outline"></ion-icon>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Supplier Modal -->
            <div class="modal-overlay" id="supplierModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add New Supplier</h3>
                        <button class="modal-close" onclick="closeSupplierModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="supplierForm" class="modal-form">
                            <input type="hidden" id="supplierId">
                            <div id="formErrors" class="error-message"></div>
                            
                            <!-- Required Fields Section -->
                            <div class="form-group full-width">
                                <h4 style="margin-bottom: 20px; color: var(--primary); padding-bottom: 10px; border-bottom: 2px solid var(--primary);">
                                    <ion-icon name="alert-circle-outline"></ion-icon>
                                    Required Information
                                </h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="companyName" class="required">Supplier Name</label>
                                <input type="text" id="companyName" placeholder="Enter supplier name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category" class="required">Category</label>
                                <select id="category" required>
                                    <option value="">Select Category</option>
                                    <option value="pharmaceutical">Pharmaceutical</option>
                                    <option value="medical">Medical Equipment</option>
                                    <option value="laboratory">Laboratory Supplies</option>
                                    <option value="consumables">Medical Consumables</option>
                                    <option value="generic">Generic Medicines</option>
                                    <option value="branded">Branded Medicines</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="supplierType" class="required">Supplier Type</label>
                                <select id="supplierType" required>
                                    <option value="">Select Type</option>
                                    <option value="manufacturer">Manufacturer</option>
                                    <option value="distributor">Distributor</option>
                                    <option value="wholesaler">Wholesaler</option>
                                    <option value="retailer">Retailer</option>
                                    <option value="importer">Importer</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="required">Phone Number</label>
                                <input type="tel" id="phone" placeholder="+265 XXX XXX XXX" required pattern="^\+?[0-9\s\-\(\)]+$">
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="required">Email Address</label>
                                <input type="email" id="email" placeholder="Enter email address" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="contactPerson" class="required">Contact Person</label>
                                <input type="text" id="contactPerson" placeholder="Enter contact person name" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="address" class="required">Address</label>
                                <textarea id="address" placeholder="Enter full address" required rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="city" class="required">City</label>
                                <input type="text" id="city" placeholder="Enter city" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="country" class="required">Country</label>
                                <input type="text" id="country" placeholder="Enter country" value="Malawi" required>
                            </div>
                            
                            <?php if ($user_role === 'admin'): ?>
                            <div class="form-group">
                                <label for="branch_id">Primary Branch</label>
                                <select id="branch_id">
                                    <option value="">Select Branch (Optional)</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo htmlspecialchars($branch['branch_id']); ?>">
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Optional Fields Section -->
                            <div class="form-group full-width" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 20px; color: var(--dark-gray);">
                                    <ion-icon name="information-circle-outline"></ion-icon>
                                    Additional Information
                                </h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="paymentTerms">Payment Terms</label>
                                <select id="paymentTerms">
                                    <option value="">Select Terms</option>
                                    <option value="net30">Net 30 Days</option>
                                    <option value="net60">Net 60 Days</option>
                                    <option value="cod">Cash on Delivery</option>
                                    <option value="advance">Advance Payment</option>
                                    <option value="installments">Installments</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="avg_delivery_time_days">Avg. Delivery Time (Days)</label>
                                <input type="number" id="avg_delivery_time_days" min="1" value="3" placeholder="3">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="products_supplied">Products Supplied</label>
                                <textarea id="products_supplied" placeholder="List main products supplied by this supplier" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                    <option value="on_hold">On Hold</option>
                                </select>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="additional_notes">Additional Notes</label>
                                <textarea id="additional_notes" placeholder="Enter any additional notes about this supplier" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeSupplierModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveSupplier()">
                            Save Supplier
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Supplier Details Modal -->
            <div class="modal-overlay view-details-modal" id="viewSupplierModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="viewModalTitle">Supplier Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="supplier-details">
                            <div class="supplier-logo-large" id="viewLogo">
                                <!-- Logo will be displayed here -->
                            </div>
                            <div class="supplier-info">
                                <h4 id="viewCompanyName">Loading...</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Supplier Code:</span>
                                        <span class="info-value" id="viewSupplierCode">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Category:</span>
                                        <span class="info-value" id="viewCategory">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Supplier Type:</span>
                                        <span class="info-value" id="viewSupplierType">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Contact Person:</span>
                                        <span class="info-value" id="viewContactPerson">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value" id="viewEmail">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Phone:</span>
                                        <span class="info-value" id="viewPhone">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Address:</span>
                                        <span class="info-value" id="viewAddress">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">City:</span>
                                        <span class="info-value" id="viewCity">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Country:</span>
                                        <span class="info-value" id="viewCountry">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Avg. Delivery Time:</span>
                                        <span class="info-value" id="viewLeadTime">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Payment Terms:</span>
                                        <span class="info-value" id="viewPaymentTerms">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Rating:</span>
                                        <span class="info-value" id="viewRating">
                                            <div class="rating-stars">Loading...</div>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value" id="viewStatus">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Branch:</span>
                                        <span class="info-value" id="viewBranch">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total Orders:</span>
                                        <span class="info-value" id="viewTotalOrders">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total Value:</span>
                                        <span class="info-value" id="viewTotalValue">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">On-Time Delivery:</span>
                                        <span class="info-value" id="viewOnTimeRate">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Products Supplied -->
                        <div class="form-group full-width" style="margin-top: 20px;">
                            <label>Products Supplied:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px; min-height: 60px;" id="viewProductsSupplied">
                                Loading...
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="form-group full-width">
                            <label>Additional Notes:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px; min-height: 60px;" id="viewNotes">
                                Loading...
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                        <button type="button" class="action-btn primary" onclick="editCurrentSupplier()">
                            <ion-icon name="create-outline"></ion-icon>
                            Edit Supplier
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="deleteSupplierModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Confirm Delete</h3>
                        <button class="modal-close" onclick="closeDeleteModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="confirmation-body">
                        <div class="confirmation-icon delete">
                            <ion-icon name="warning-outline"></ion-icon>
                        </div>
                        <h4>Delete Supplier</h4>
                        <p>Are you sure you want to delete <strong id="deleteSupplierName">[Supplier Name]</strong>? This action cannot be undone and all supplier data will be permanently removed.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="action-btn danger" onclick="confirmDelete()">
                                <ion-icon name="trash-outline"></ion-icon>
                                Delete Supplier
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <!-- Include SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   <script>
// Global variables
let currentPage = 1;
const itemsPerPage = 10;
let filteredSuppliers = [];
let totalSuppliers = <?php echo $initial_stats['total_suppliers']; ?>;
let supplierToDelete = null;
let supplierToView = null;

// User role from PHP
const userRole = '<?php echo $user_role; ?>';
const currentBranchId = <?php echo $current_branch_id ?: 'null'; ?>;
const isAdmin = userRole === 'admin';

// DOM Elements
const tableBody = document.getElementById('suppliersTableBody');
const supplierModal = document.getElementById('supplierModal');
const viewSupplierModal = document.getElementById('viewSupplierModal');
const deleteSupplierModal = document.getElementById('deleteSupplierModal');
const loader = document.getElementById('loader');
const formErrors = document.getElementById('formErrors');

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    console.log("Suppliers Management System Initialized");
    
    // Set default filters for non-admin users
    if (!isAdmin && currentBranchId) {
        const branchFilter = document.getElementById('branchFilter');
        if (branchFilter) branchFilter.disabled = true;
    }
    
    // Load initial data
    loadSuppliers();
});

// Show/Hide Loader
function showLoader() {
    if (loader) loader.classList.add('active');
}

function hideLoader() {
    if (loader) loader.classList.remove('active');
}

// Show Error Message
function showError(message, element = null) {
    if (element) {
        element.textContent = message;
        element.classList.add('active');
        setTimeout(() => {
            element.classList.remove('active');
        }, 5000);
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message,
            timer: 5000,
            showConfirmButton: true
        });
    }
}

// Show Success Message
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: message,
        timer: 3000,
        showConfirmButton: false
    });
}

// Clear Error Message
function clearError(element) {
    if (element) {
        element.textContent = '';
        element.classList.remove('active');
    }
}

// Load suppliers from server
function loadSuppliers() {
    showLoader();
    
    const formData = new FormData();
    formData.append('action', 'get_suppliers');
    formData.append('search', document.getElementById('searchInput').value);
    formData.append('status', document.getElementById('statusFilter').value);
    formData.append('category', document.getElementById('categoryFilter').value);
    formData.append('supplier_type', document.getElementById('supplierTypeFilter').value);
    formData.append('branch', document.getElementById('branchFilter').value);
    formData.append('sort', document.getElementById('sortFilter').value);
    formData.append('page', currentPage);
    formData.append('limit', itemsPerPage);

    fetch('suppliers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        hideLoader();
        if (data.success) {
            filteredSuppliers = data.data;
            totalSuppliers = data.total;
            renderSuppliersTable();
            updateStatistics();
            updatePaginationInfo();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoader();
        console.error('Error:', error);
        showError('Failed to load suppliers. Please check your connection.');
    });
}

// Update statistics
function updateStatistics() {
    const formData = new FormData();
    formData.append('action', 'get_supplier_stats');

    fetch('suppliers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const stats = data.stats;
            document.getElementById('totalSuppliers').textContent = stats.total_suppliers;
            document.getElementById('activeSuppliers').textContent = stats.active_suppliers;
            document.getElementById('topRatedSuppliers').textContent = stats.top_rated_suppliers;
            document.getElementById('totalProducts').textContent = stats.total_products;
            document.getElementById('pendingDeliveries').textContent = stats.pending_deliveries;
        }
    })
    .catch(error => {
        console.error('Error updating statistics:', error);
        // Silently fail for statistics
    });
}

// Render Suppliers Table
function renderSuppliersTable() {
    if (!tableBody) return;
    
    if (filteredSuppliers.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">
                    <ion-icon name="business-outline"></ion-icon>
                    <h3>No suppliers found</h3>
                    <p>${isAdmin ? 'Try adjusting your search or filters' : 'No suppliers in your branch.'}</p>
                </td>
            </tr>
        `;
        updatePaginationInfo();
        return;
    }

    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredSuppliers.length);
    const currentSuppliers = filteredSuppliers.slice(startIndex, endIndex);

    let html = '';
    currentSuppliers.forEach((supplier, index) => {
        const statusClass = `status-${supplier.status.replace('_', '')}`;
        const rating = parseFloat(supplier.rating) || 0;
        
        // Generate star rating HTML
        let starsHTML = '';
        const fullStars = Math.floor(rating);
        const hasHalfStar = (rating - fullStars) >= 0.5;
        
        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                starsHTML += '<span class="star filled">★</span>';
            } else if (i === fullStars + 1 && hasHalfStar) {
                starsHTML += '<span class="star half">★</span>';
            } else {
                starsHTML += '<span class="star">★</span>';
            }
        }
        
        starsHTML += `<span class="rating-text">${rating.toFixed(1)}</span>`;
        
        html += `
            <tr>
                <td>${startIndex + index + 1}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="supplier-logo" style="background-color: ${supplier.logo_color || '#3498db'}">
                            ${supplier.logo_initials || 'NA'}
                        </div>
                        <div>
                            <strong>${escapeHtml(supplier.company_name)}</strong><br>
                            <small style="color: var(--dark-gray);">${supplier.supplier_code || 'N/A'}</small><br>
                            <small style="color: var(--dark-gray);">${supplier.supplier_type_text}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="contact-info">
                        <div class="contact-item">
                            <ion-icon name="person-outline"></ion-icon>
                            <span>${escapeHtml(supplier.contact_person)}</span>
                        </div>
                        <div class="contact-item">
                            <ion-icon name="mail-outline"></ion-icon>
                            <span>${escapeHtml(supplier.email)}</span>
                        </div>
                        <div class="contact-item">
                            <ion-icon name="call-outline"></ion-icon>
                            <span>${escapeHtml(supplier.phone)}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="padding: 5px 10px; background: rgba(42, 92, 139, 0.1); border-radius: 4px; font-size: 0.85rem;">
                        ${supplier.category_text}
                    </span>
                </td>
                <td>
                    <span class="products-count">
                        <ion-icon name="cube-outline"></ion-icon>
                        ${supplier.products_count || 0} products
                    </span>
                </td>
                <td>
                    <div class="rating-stars">
                        ${starsHTML}
                    </div>
                </td>
                <td>
                    <div class="delivery-status">
                        <ion-icon name="calendar-outline" class="delivery-icon"></ion-icon>
                        <div>
                            <div>${supplier.last_delivery_text || 'No deliveries yet'}</div>
                            ${supplier.last_delivery ? `
                            <div class="delivery-time">${formatDate(supplier.last_delivery)}</div>
                            ` : ''}
                        </div>
                    </div>
                </td>
                <td><span class="supplier-status ${statusClass}">${supplier.status_text}</span></td>
                <td>
                    <div class="supplier-actions">
                        <button class="action-icon view" title="View Details" onclick="viewSupplier(${supplier.supplier_id})">
                            <ion-icon name="eye-outline"></ion-icon>
                        </button>
                        <button class="action-icon edit" title="Edit" onclick="editSupplier(${supplier.supplier_id})">
                            <ion-icon name="create-outline"></ion-icon>
                        </button>
                        <button class="action-icon delete" title="Delete" onclick="deleteSupplier(${supplier.supplier_id}, '${escapeHtml(supplier.company_name).replace(/'/g, "\\'")}')">
                            <ion-icon name="trash-outline"></ion-icon>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

// Update Pagination Info
function updatePaginationInfo() {
    const total = totalSuppliers;
    const start = (currentPage - 1) * itemsPerPage + 1;
    const end = Math.min(currentPage * itemsPerPage, total);
    
    document.getElementById('paginationInfo').textContent = 
        `Showing ${start} to ${end} of ${total} entries`;
        
    // Update pagination buttons
    const totalPages = Math.ceil(total / itemsPerPage);
    const paginationControls = document.querySelector('.pagination-controls');
    
    if (totalPages > 1) {
        let buttonsHTML = `
            <button class="pagination-btn" onclick="changePage('prev')" ${currentPage === 1 ? 'disabled' : ''}>
                <ion-icon name="chevron-back-outline"></ion-icon>
            </button>
        `;
        
        // Show up to 5 page buttons
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        
        for (let i = startPage; i <= endPage; i++) {
            buttonsHTML += `
                <button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                    ${i}
                </button>
            `;
        }
        
        if (endPage < totalPages) {
            buttonsHTML += `<span style="padding: 8px 5px;">...</span>`;
        }
        
        buttonsHTML += `
            <button class="pagination-btn" onclick="changePage('next')" ${currentPage === totalPages ? 'disabled' : ''}>
                <ion-icon name="chevron-forward-outline"></ion-icon>
            </button>
        `;
        
        if (paginationControls) {
            paginationControls.innerHTML = buttonsHTML;
        }
    }
}

// Pagination Functions
function changePage(direction) {
    const totalPages = Math.ceil(totalSuppliers / itemsPerPage);
    
    if (direction === 'prev' && currentPage > 1) {
        currentPage--;
    } else if (direction === 'next' && currentPage < totalPages) {
        currentPage++;
    }
    
    loadSuppliers();
}

function goToPage(page) {
    const totalPages = Math.ceil(totalSuppliers / itemsPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        loadSuppliers();
    }
}

// Filter and Search Functions
function filterSuppliers() {
    currentPage = 1;
    loadSuppliers();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('supplierTypeFilter').value = '';
    document.getElementById('branchFilter').value = '';
    document.getElementById('sortFilter').value = 'name_asc';
    currentPage = 1;
    loadSuppliers();
}

function applyFilters() {
    loadSuppliers();
    showSuccess('Filters applied successfully!');
}

// Modal Functions
function openAddSupplierModal() {
    document.getElementById('modalTitle').textContent = 'Add New Supplier';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
    document.getElementById('avg_delivery_time_days').value = 3;
    document.getElementById('country').value = 'Malawi';
    document.getElementById('status').value = 'active';
    clearError(formErrors);
    
    // Reset branch selection for non-admin users
    if (!isAdmin && currentBranchId) {
        const branchSelect = document.getElementById('branch_id');
        if (branchSelect) {
            branchSelect.value = currentBranchId;
        }
    }
    
    supplierModal.classList.add('active');
}

function editSupplier(id) {
    showLoader();
    clearError(formErrors);
    
    const formData = new FormData();
    formData.append('action', 'get_supplier');
    formData.append('supplier_id', id);

    fetch('suppliers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        hideLoader();
        if (data.success) {
            const supplier = data.data;
            
            document.getElementById('modalTitle').textContent = 'Edit Supplier';
            document.getElementById('supplierId').value = supplier.supplier_id;
            document.getElementById('companyName').value = supplier.company_name;
            document.getElementById('category').value = supplier.category;
            document.getElementById('supplierType').value = supplier.supplier_type;
            document.getElementById('phone').value = supplier.phone;
            document.getElementById('email').value = supplier.email;
            document.getElementById('contactPerson').value = supplier.contact_person;
            document.getElementById('address').value = supplier.address;
            document.getElementById('city').value = supplier.city;
            document.getElementById('country').value = supplier.country || 'Malawi';
            document.getElementById('paymentTerms').value = supplier.payment_terms || '';
            document.getElementById('avg_delivery_time_days').value = supplier.avg_delivery_time_days || 3;
            document.getElementById('products_supplied').value = supplier.products_supplied || '';
            document.getElementById('status').value = supplier.status;
            document.getElementById('additional_notes').value = supplier.additional_notes || '';
            
            // Set branch if exists
            if (supplier.branch_id) {
                document.getElementById('branch_id').value = supplier.branch_id;
            }
            
            supplierModal.classList.add('active');
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoader();
        console.error('Error:', error);
        showError('Failed to load supplier details');
    });
}

function viewSupplier(id) {
    showLoader();
    
    const formData = new FormData();
    formData.append('action', 'get_supplier');
    formData.append('supplier_id', id);

    fetch('suppliers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        hideLoader();
        if (data.success) {
            const supplier = data.data;
            supplierToView = supplier;
            
            // Update view modal content
            document.getElementById('viewModalTitle').textContent = supplier.company_name;
            document.getElementById('viewCompanyName').textContent = supplier.company_name;
            document.getElementById('viewSupplierCode').textContent = supplier.supplier_code;
            document.getElementById('viewCategory').textContent = supplier.category_text;
            document.getElementById('viewSupplierType').textContent = supplier.supplier_type_text;
            document.getElementById('viewContactPerson').textContent = supplier.contact_person;
            document.getElementById('viewEmail').textContent = supplier.email;
            document.getElementById('viewPhone').textContent = supplier.phone;
            document.getElementById('viewAddress').textContent = supplier.address;
            document.getElementById('viewCity').textContent = supplier.city;
            document.getElementById('viewCountry').textContent = supplier.country || 'Malawi';
            document.getElementById('viewLeadTime').textContent = supplier.avg_delivery_time_days ? `${supplier.avg_delivery_time_days} days` : 'Not specified';
            
            // Set payment terms text
            const paymentTermsMap = {
                'net30': 'Net 30 Days',
                'net60': 'Net 60 Days',
                'cod': 'Cash on Delivery',
                'advance': 'Advance Payment',
                'installments': 'Installments',
                'other': 'Other'
            };
            document.getElementById('viewPaymentTerms').textContent = paymentTermsMap[supplier.payment_terms] || 'Not specified';
            
            document.getElementById('viewStatus').textContent = supplier.status_text;
            document.getElementById('viewBranch').textContent = supplier.branch_name || 'Not assigned';
            document.getElementById('viewTotalOrders').textContent = supplier.total_orders || 0;
            document.getElementById('viewTotalValue').textContent = formatCurrency(supplier.total_value || 0);
            document.getElementById('viewOnTimeRate').textContent = supplier.on_time_delivery_rate ? `${supplier.on_time_delivery_rate}%` : 'No data';
            document.getElementById('viewProductsSupplied').textContent = supplier.products_supplied || 'No products listed';
            document.getElementById('viewNotes').textContent = supplier.additional_notes || 'No additional notes';
            
            // Set logo
            const viewLogo = document.getElementById('viewLogo');
            viewLogo.innerHTML = '';
            viewLogo.style.backgroundColor = getLogoColor(supplier.supplier_id);
            const logoText = document.createElement('span');
            logoText.textContent = getInitials(supplier.company_name);
            logoText.style.color = 'white';
            logoText.style.fontSize = '3.5rem';
            logoText.style.fontWeight = 'bold';
            viewLogo.appendChild(logoText);
            
            // Set rating stars
            const ratingContainer = document.querySelector('#viewRating .rating-stars');
            if (ratingContainer) {
                ratingContainer.innerHTML = '';
                const rating = parseFloat(supplier.rating) || 0;
                const fullStars = Math.floor(rating);
                const hasHalfStar = (rating - fullStars) >= 0.5;
                
                for (let i = 1; i <= 5; i++) {
                    const star = document.createElement('span');
                    star.className = 'star';
                    if (i <= fullStars) {
                        star.className += ' filled';
                    } else if (i === fullStars + 1 && hasHalfStar) {
                        star.className += ' half';
                    }
                    star.innerHTML = '★';
                    ratingContainer.appendChild(star);
                }
                
                const ratingText = document.createElement('span');
                ratingText.className = 'rating-text';
                ratingText.textContent = rating.toFixed(1);
                ratingContainer.appendChild(ratingText);
            }
            
            viewSupplierModal.classList.add('active');
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoader();
        console.error('Error:', error);
        showError('Failed to load supplier details');
    });
}

function closeSupplierModal() {
    supplierModal.classList.remove('active');
    clearError(formErrors);
}

function closeViewModal() {
    viewSupplierModal.classList.remove('active');
    supplierToView = null;
}

// Save Supplier Function
function saveSupplier() {
    const form = document.getElementById('supplierForm');
    const supplierId = document.getElementById('supplierId').value;
    const isEdit = !!supplierId;

    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Clear previous errors
    clearError(formErrors);

    // Collect form data
    const formData = new FormData();
    formData.append('action', isEdit ? 'edit_supplier' : 'add_supplier');
    
    if (isEdit) {
        formData.append('supplier_id', supplierId);
    }
    
    // Required fields
    formData.append('company_name', document.getElementById('companyName').value.trim());
    formData.append('category', document.getElementById('category').value);
    formData.append('supplier_type', document.getElementById('supplierType').value);
    formData.append('phone', document.getElementById('phone').value.trim());
    formData.append('email', document.getElementById('email').value.trim());
    formData.append('contact_person', document.getElementById('contactPerson').value.trim());
    formData.append('address', document.getElementById('address').value.trim());
    formData.append('city', document.getElementById('city').value.trim());
    formData.append('country', document.getElementById('country').value.trim());
    
    // Optional fields
    formData.append('payment_terms', document.getElementById('paymentTerms').value || '');
    formData.append('avg_delivery_time_days', document.getElementById('avg_delivery_time_days').value || 3);
    formData.append('products_supplied', document.getElementById('products_supplied').value.trim() || '');
    formData.append('additional_notes', document.getElementById('additional_notes').value.trim() || '');
    formData.append('status', document.getElementById('status').value);
    
    // Branch ID (optional)
    const branchIdInput = document.getElementById('branch_id');
    if (branchIdInput && branchIdInput.value) {
        formData.append('branch_id', branchIdInput.value);
    } else if (!isAdmin && currentBranchId) {
        formData.append('branch_id', currentBranchId);
    }

    showLoader();

    fetch('suppliers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        if (data.success) {
            showSuccess(data.message);
            closeSupplierModal();
            loadSuppliers();
            updateStatistics();
        } else {
            showError(data.message, formErrors);
        }
    })
    .catch(error => {
        hideLoader();
        console.error('Error:', error);
        showError('Failed to save supplier. Please try again.');
    });
}

// Delete Supplier Functions
function deleteSupplier(id, name) {
    supplierToDelete = id;
    document.getElementById('deleteSupplierName').textContent = name;
    deleteSupplierModal.classList.add('active');
}

function closeDeleteModal() {
    deleteSupplierModal.classList.remove('active');
    supplierToDelete = null;
}

function confirmDelete() {
    if (!supplierToDelete) return;

    showLoader();

    const formData = new FormData();
    formData.append('action', 'delete_supplier');
    formData.append('supplier_id', supplierToDelete);

    fetch('suppliers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        if (data.success) {
            showSuccess(data.message);
            closeDeleteModal();
            loadSuppliers();
            updateStatistics();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoader();
        console.error('Error:', error);
        showError('Failed to delete supplier');
    });
}

// View/Edit from View Modal
function editCurrentSupplier() {
    if (!supplierToView) return;
    
    closeViewModal();
    setTimeout(() => {
        editSupplier(supplierToView.supplier_id);
    }, 300);
}

// Utility Functions
function refreshSuppliers() {
    loadSuppliers();
    showSuccess('Supplier list refreshed!');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'MWK',
        minimumFractionDigits: 2
    }).format(amount);
}

function ucwords(text) {
    if (!text) return '';
    return text.replace(/\b\w/g, char => char.toUpperCase());
}

function getLogoColor(supplierId) {
    const colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#d35400'];
    const colorIndex = supplierId % colors.length;
    return colors[colorIndex];
}

function getInitials(companyName) {
    if (!companyName) return 'NA';
    const words = companyName.trim().split(' ');
    let initials = '';
    
    for (const word of words) {
        if (word.length > 0) {
            initials += word[0].toUpperCase();
        }
        if (initials.length >= 2) break;
    }
    
    return initials.length > 0 ? initials : 'NA';
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeSupplierModal();
        closeViewModal();
        closeDeleteModal();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSupplierModal();
        closeViewModal();
        closeDeleteModal();
    }
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    if (e.ctrlKey && e.key === 'n' && isAdmin) {
        e.preventDefault();
        openAddSupplierModal();
    }
});
</script>
</body>
</html>