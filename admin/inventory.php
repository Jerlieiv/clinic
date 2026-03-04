<<<<<<< HEAD
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
function generateMedicineCode($conn) {
    $prefix = 'MED';
    $year = date('Y');
    
    // Get the last medicine code for this year
    $stmt = $conn->prepare("SELECT medicine_code FROM medicines WHERE medicine_code LIKE ? ORDER BY medicine_id DESC LIMIT 1");
    $like_pattern = $prefix . '-' . $year . '-%';
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_code = $result->fetch_assoc()['medicine_code'];
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

function getSupplierName($conn, $supplier_id) {
    if (!$supplier_id) return null;
    
    $stmt = $conn->prepare("SELECT company_name FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();
    
    return $supplier['company_name'] ?? null;
}

function getCategoryName($conn, $category_id) {
    if (!$category_id) return null;
    
    $stmt = $conn->prepare("SELECT category_name FROM medicine_categories WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
    
    return $category['category_name'] ?? null;
}

function getStockStatus($current_stock, $min_stock, $expiry_date) {
    $today = new DateTime();
    $expiry = new DateTime($expiry_date);
    
    // Check if expired
    if ($expiry < $today) {
        return 'expired';
    }
    
    // Check stock levels
    if ($current_stock <= 0) {
        return 'outofstock';
    } elseif ($current_stock <= $min_stock) {
        return 'lowstock';
    } else {
        return 'instock';
    }
}

function getStatusText($status) {
    $status_map = [
        'instock' => 'In Stock',
        'lowstock' => 'Low Stock',
        'outofstock' => 'Out of Stock',
        'expired' => 'Expired'
    ];
    
    return $status_map[$status] ?? ucfirst($status);
}

function getDosageFormText($form) {
    $form_map = [
        'tablet' => 'Tablet',
        'capsule' => 'Capsule',
        'syrup' => 'Syrup',
        'injection' => 'Injection',
        'ointment' => 'Ointment',
        'cream' => 'Cream',
        'drops' => 'Drops',
        'inhaler' => 'Inhaler',
        'other' => 'Other'
    ];
    
    return $form_map[$form] ?? ucfirst($form);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'add_medicine':
                // Validate required fields
                $required = ['medicine_name', 'generic_name', 'category_id', 'batch_number', 'unit_price', 'selling_price', 'current_stock', 'expiry_date'];
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
                
                // Validate stock levels
                $current_stock = intval($_POST['current_stock']);
                $min_stock = intval($_POST['min_stock_level'] ?? 10);
                $max_stock = intval($_POST['max_stock_level'] ?? 100);
                
                if ($current_stock < 0) {
                    $response['message'] = "Current stock cannot be negative!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($min_stock < 0) {
                    $response['message'] = "Minimum stock level cannot be negative!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($max_stock < $min_stock) {
                    $response['message'] = "Maximum stock level must be greater than minimum stock level!";
                    echo json_encode($response);
                    exit;
                }
                
                // Validate prices
                $unit_price = floatval($_POST['unit_price']);
                $selling_price = floatval($_POST['selling_price']);
                
                if ($unit_price <= 0 || $selling_price <= 0) {
                    $response['message'] = "Prices must be greater than zero!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($selling_price < $unit_price) {
                    $response['message'] = "Selling price must be greater than or equal to unit price!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if batch number already exists
                $check_stmt = $conn->prepare("SELECT medicine_id FROM medicines WHERE batch_number = ?");
                $check_stmt->bind_param("s", $_POST['batch_number']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $response['message'] = "Batch number already exists!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
                
                // Generate medicine code
                $medicine_code = generateMedicineCode($conn);
                
                // Prepare parameters
                $category_id = intval($_POST['category_id']);
                $dosage_form = $_POST['dosage_form'] ?? 'tablet';
                $strength = $_POST['strength'] ?? null;
                $manufacturer = $_POST['manufacturer'] ?? null;
                $description = $_POST['description'] ?? null;
                $storage_instructions = $_POST['storage_instructions'] ?? null;
                $requires_prescription = $_POST['requires_prescription'] ?? 'no';
                $unit_of_measure = $_POST['unit_of_measure'] ?? 'unit';
                $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
                $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
                $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : $current_branch_id;
                $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
                $status = $_POST['status'] ?? 'active';
                
                // Calculate stock status
                $expiry_date = $_POST['expiry_date'];
                $stock_status = getStockStatus($current_stock, $min_stock, $expiry_date);
                
                // Insert medicine
                $stmt = $conn->prepare("INSERT INTO medicines (
                    medicine_code, medicine_name, generic_name, category_id, dosage_form, strength,
                    manufacturer, batch_number, description, storage_instructions, requires_prescription,
                    unit_of_measure, unit_price, selling_price, discount_percentage, min_stock_level,
                    max_stock_level, current_stock, status, stock_status, expiry_date, purchase_date,
                    supplier_id, branch_id, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("sssissssssssdddiiisssssii", 
                    $medicine_code,
                    $_POST['medicine_name'],
                    $_POST['generic_name'],
                    $category_id,
                    $dosage_form,
                    $strength,
                    $manufacturer,
                    $_POST['batch_number'],
                    $description,
                    $storage_instructions,
                    $requires_prescription,
                    $unit_of_measure,
                    $unit_price,
                    $selling_price,
                    $discount_percentage,
                    $min_stock,
                    $max_stock,
                    $current_stock,
                    $status,
                    $stock_status,
                    $expiry_date,
                    $purchase_date,
                    $supplier_id,
                    $branch_id,
                    $user_id
                );
                
                if ($stmt->execute()) {
                    $new_medicine_id = $stmt->insert_id;
                    
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'ADD_MEDICINE', 
                        "Added new medicine: {$_POST['medicine_name']} ({$medicine_code})", 
                        $branch_id, getBranchName($conn, $branch_id));
                    
                    $response['success'] = true;
                    $response['message'] = "Medicine added successfully!";
                    $response['medicine_id'] = $new_medicine_id;
                    $response['medicine_code'] = $medicine_code;
                } else {
                    $response['message'] = "Error adding medicine: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'edit_medicine':
                $medicine_id = intval($_POST['medicine_id']);
                
                if ($medicine_id <= 0) {
                    $response['message'] = "Invalid medicine ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if medicine exists
                $check_stmt = $conn->prepare("SELECT medicine_id, batch_number FROM medicines WHERE medicine_id = ?");
                $check_stmt->bind_param("i", $medicine_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "Medicine not found!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $medicine_data = $check_result->fetch_assoc();
                $check_stmt->close();
                
                // Check if batch number changed and if new batch number exists for another medicine
                if ($medicine_data['batch_number'] !== $_POST['batch_number']) {
                    $check_stmt = $conn->prepare("SELECT medicine_id FROM medicines WHERE batch_number = ? AND medicine_id != ?");
                    $check_stmt->bind_param("si", $_POST['batch_number'], $medicine_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $response['message'] = "Batch number already exists for another medicine!";
                        $check_stmt->close();
                        echo json_encode($response);
                        exit;
                    }
                    $check_stmt->close();
                }
                
                // Validate required fields - ADDED 'current_stock' to required fields
                $required = ['medicine_name', 'generic_name', 'category_id', 'batch_number', 'unit_price', 'selling_price', 'current_stock', 'expiry_date'];
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
                
                // Validate stock levels - GET FROM POST, NOT FROM DATABASE
                $current_stock = intval($_POST['current_stock']);
                $min_stock = intval($_POST['min_stock_level'] ?? 10);
                $max_stock = intval($_POST['max_stock_level'] ?? 100);
                
                if ($current_stock < 0) {
                    $response['message'] = "Current stock cannot be negative!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($min_stock < 0) {
                    $response['message'] = "Minimum stock level cannot be negative!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($max_stock < $min_stock) {
                    $response['message'] = "Maximum stock level must be greater than minimum stock level!";
                    echo json_encode($response);
                    exit;
                }
                
                // Validate prices
                $unit_price = floatval($_POST['unit_price']);
                $selling_price = floatval($_POST['selling_price']);
                
                if ($unit_price <= 0 || $selling_price <= 0) {
                    $response['message'] = "Prices must be greater than zero!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($selling_price < $unit_price) {
                    $response['message'] = "Selling price must be greater than or equal to unit price!";
                    echo json_encode($response);
                    exit;
                }
                
                // Prepare parameters
                $category_id = intval($_POST['category_id']);
                $dosage_form = $_POST['dosage_form'] ?? 'tablet';
                $strength = $_POST['strength'] ?? null;
                $manufacturer = $_POST['manufacturer'] ?? null;
                $description = $_POST['description'] ?? null;
                $storage_instructions = $_POST['storage_instructions'] ?? null;
                $requires_prescription = $_POST['requires_prescription'] ?? 'no';
                $unit_of_measure = $_POST['unit_of_measure'] ?? 'unit';
                $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
                $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
                $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : $current_branch_id;
                $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
                $status = $_POST['status'] ?? 'active';
                $expiry_date = $_POST['expiry_date'];
                
                // Calculate stock status using the NEW stock value from POST
                $stock_status = getStockStatus($current_stock, $min_stock, $expiry_date);
                
                // Update medicine - INCLUDING current_stock in UPDATE
                $stmt = $conn->prepare("UPDATE medicines SET 
                    medicine_name = ?, generic_name = ?, category_id = ?, dosage_form = ?, strength = ?,
                    manufacturer = ?, batch_number = ?, description = ?, storage_instructions = ?, requires_prescription = ?,
                    unit_of_measure = ?, unit_price = ?, selling_price = ?, discount_percentage = ?, min_stock_level = ?,
                    max_stock_level = ?, current_stock = ?, status = ?, stock_status = ?, expiry_date = ?, purchase_date = ?,
                    supplier_id = ?, branch_id = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE medicine_id = ?");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                // Parameter types breakdown:
                // s: medicine_name (string)
                // s: generic_name (string)
                // i: category_id (integer)
                // s: dosage_form (string)
                // s: strength (string)
                // s: manufacturer (string)
                // s: batch_number (string)
                // s: description (string)
                // s: storage_instructions (string)
                // s: requires_prescription (string)
                // s: unit_of_measure (string)
                // d: unit_price (double)
                // d: selling_price (double)
                // d: discount_percentage (double)
                // i: min_stock_level (integer)
                // i: max_stock_level (integer)
                // i: current_stock (integer) - ADDED THIS
                // s: status (string)
                // s: stock_status (string)
                // s: expiry_date (string)
                // s: purchase_date (string)
                // i: supplier_id (integer)
                // i: branch_id (integer)
                // i: medicine_id (integer)
                
                $stmt->bind_param("ssissssssssdddiiiissssii", 
                    $_POST['medicine_name'],
                    $_POST['generic_name'],
                    $category_id,
                    $dosage_form,
                    $strength,
                    $manufacturer,
                    $_POST['batch_number'],
                    $description,
                    $storage_instructions,
                    $requires_prescription,
                    $unit_of_measure,
                    $unit_price,
                    $selling_price,
                    $discount_percentage,
                    $min_stock,
                    $max_stock,
                    $current_stock,  // ADDED THIS - current stock from POST
                    $status,
                    $stock_status,
                    $expiry_date,
                    $purchase_date,
                    $supplier_id,
                    $branch_id,
                    $medicine_id
                );
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'EDIT_MEDICINE', 
                        "Updated medicine: {$_POST['medicine_name']}", 
                        $branch_id, getBranchName($conn, $branch_id));
                    
                    $response['success'] = true;
                    $response['message'] = "Medicine updated successfully!";
                } else {
                    $response['message'] = "Error updating medicine: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'delete_medicine':
                $medicine_id = intval($_POST['medicine_id']);
                
                if ($medicine_id <= 0) {
                    $response['message'] = "Invalid medicine ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Get medicine info for logging
                $medicine_stmt = $conn->prepare("SELECT medicine_name, medicine_code FROM medicines WHERE medicine_id = ?");
                $medicine_stmt->bind_param("i", $medicine_id);
                $medicine_stmt->execute();
                $medicine_result = $medicine_stmt->get_result();
                
                if ($medicine_result->num_rows === 0) {
                    $response['message'] = "Medicine not found!";
                    $medicine_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $medicine_data = $medicine_result->fetch_assoc();
                $medicine_stmt->close();
                
                // Check if medicine has stock
                $stock_check = $conn->prepare("SELECT current_stock FROM medicines WHERE medicine_id = ?");
                $stock_check->bind_param("i", $medicine_id);
                $stock_check->execute();
                $stock_result = $stock_check->get_result()->fetch_assoc();
                $stock_check->close();
                
                if ($stock_result['current_stock'] > 0) {
                    $response['message'] = "Cannot delete medicine with existing stock. Please adjust stock to zero first.";
                    echo json_encode($response);
                    exit;
                }
                
                // Delete medicine
                $stmt = $conn->prepare("DELETE FROM medicines WHERE medicine_id = ?");
                $stmt->bind_param("i", $medicine_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'DELETE_MEDICINE', 
                        "Deleted medicine: {$medicine_data['medicine_name']} ({$medicine_data['medicine_code']})");
                    
                    $response['success'] = true;
                    $response['message'] = "Medicine deleted successfully!";
                } else {
                    $response['message'] = "Error deleting medicine: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'restock_medicine':
                $medicine_id = intval($_POST['medicine_id']);
                $quantity = intval($_POST['quantity']);
                
                if ($medicine_id <= 0) {
                    $response['message'] = "Invalid medicine ID!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($quantity <= 0) {
                    $response['message'] = "Quantity must be greater than zero!";
                    echo json_encode($response);
                    exit;
                }
                
                // Get medicine info
                $medicine_stmt = $conn->prepare("SELECT medicine_name, current_stock, min_stock_level FROM medicines WHERE medicine_id = ?");
                $medicine_stmt->bind_param("i", $medicine_id);
                $medicine_stmt->execute();
                $medicine_result = $medicine_stmt->get_result();
                
                if ($medicine_result->num_rows === 0) {
                    $response['message'] = "Medicine not found!";
                    $medicine_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $medicine_data = $medicine_result->fetch_assoc();
                $medicine_stmt->close();
                
                // Update medicine stock
                $new_stock = $medicine_data['current_stock'] + $quantity;
                $expiry_date = $_POST['expiry_date'] ?? null;
                
                // Calculate new stock status
                $stock_status = getStockStatus($new_stock, $medicine_data['min_stock_level'], $expiry_date);
                
                $stmt = $conn->prepare("UPDATE medicines SET current_stock = ?, stock_status = ?, updated_at = CURRENT_TIMESTAMP WHERE medicine_id = ?");
                $stmt->bind_param("isi", $new_stock, $stock_status, $medicine_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'RESTOCK_MEDICINE', 
                        "Restocked {$quantity} units of {$medicine_data['medicine_name']}", 
                        $current_branch_id, getBranchName($conn, $current_branch_id));
                    
                    $response['success'] = true;
                    $response['message'] = "Medicine restocked successfully!";
                    $response['new_stock'] = $new_stock;
                    $response['stock_status'] = $stock_status;
                } else {
                    $response['message'] = "Error restocking medicine: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'get_medicine':
                $medicine_id = intval($_POST['medicine_id']);
                
                if ($medicine_id <= 0) {
                    $response['message'] = "Invalid medicine ID!";
                    echo json_encode($response);
                    exit;
                }
                
                $stmt = $conn->prepare("
                    SELECT m.*, 
                    c.category_name,
                    s.company_name as supplier_name,
                    b.branch_name
                    FROM medicines m
                    LEFT JOIN medicine_categories c ON m.category_id = c.category_id
                    LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
                    LEFT JOIN branches b ON m.branch_id = b.branch_id
                    WHERE m.medicine_id = ?
                ");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("i", $medicine_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $medicine = $result->fetch_assoc();
                    
                    // Format text values
                    $medicine['stock_status_text'] = getStatusText($medicine['stock_status']);
                    $medicine['dosage_form_text'] = getDosageFormText($medicine['dosage_form']);
                    $medicine['requires_prescription_text'] = $medicine['requires_prescription'] === 'yes' ? 'Yes' : 'No';
                    
                    // Calculate days to expiry
                    $today = new DateTime();
                    $expiry_date = new DateTime($medicine['expiry_date']);
                    $medicine['days_to_expiry'] = $expiry_date->diff($today)->days;
                    $medicine['is_expired'] = $expiry_date < $today;
                    $medicine['is_expiring_soon'] = !$medicine['is_expired'] && $medicine['days_to_expiry'] <= 30;
                    
                    // Calculate stock percentage
                    $medicine['stock_percentage'] = $medicine['max_stock_level'] > 0 
                        ? round(($medicine['current_stock'] / $medicine['max_stock_level']) * 100, 1)
                        : 0;
                    
                    $response['success'] = true;
                    $response['data'] = $medicine;
                } else {
                    $response['message'] = "Medicine not found!";
                }
                $stmt->close();
                break;
                
            case 'get_medicines':
                // Build query based on filters
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Apply filters
                if (!empty($_POST['search'])) {
                    $where_clauses[] = "(m.medicine_name LIKE ? OR m.generic_name LIKE ? OR m.medicine_code LIKE ? OR m.batch_number LIKE ?)";
                    $search_term = "%" . trim($_POST['search']) . "%";
                    for ($i = 0; $i < 4; $i++) {
                        $params[] = $search_term;
                        $types .= "s";
                    }
                }
                
                if (!empty($_POST['category'])) {
                    $where_clauses[] = "m.category_id = ?";
                    $params[] = intval($_POST['category']);
                    $types .= "i";
                }
                
                if (!empty($_POST['status'])) {
                    $where_clauses[] = "m.stock_status = ?";
                    $params[] = $_POST['status'];
                    $types .= "s";
                }
                
                if (!empty($_POST['supplier'])) {
                    $where_clauses[] = "m.supplier_id = ?";
                    $params[] = intval($_POST['supplier']);
                    $types .= "i";
                }
                
                // Filter by branch if user is not admin
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "m.branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                } elseif (!empty($_POST['branch'])) {
                    $where_clauses[] = "m.branch_id = ?";
                    $params[] = intval($_POST['branch']);
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get total count
                $count_sql = "SELECT COUNT(*) as total FROM medicines m WHERE $where_sql";
                $count_stmt = $conn->prepare($count_sql);
                if (!empty($params)) {
                    $count_stmt->bind_param($types, ...$params);
                }
                $count_stmt->execute();
                $count_result = $count_stmt->get_result()->fetch_assoc();
                $total_medicines = $count_result['total'] ?? 0;
                $count_stmt->close();
                
                // Get sorting
                $sort_options = [
                    'name_asc' => 'm.medicine_name ASC',
                    'name_desc' => 'm.medicine_name DESC',
                    'stock_low' => 'm.current_stock ASC',
                    'stock_high' => 'm.current_stock DESC',
                    'price_low' => 'm.selling_price ASC',
                    'price_high' => 'm.selling_price DESC',
                    'expiry_soon' => 'm.expiry_date ASC',
                    'expiry_late' => 'm.expiry_date DESC',
                    'recent' => 'm.created_at DESC'
                ];
                
                $sort_by = $_POST['sort'] ?? 'name_asc';
                $sort_sql = $sort_options[$sort_by] ?? 'm.medicine_name ASC';
                
                // Pagination
                $page = max(1, intval($_POST['page'] ?? 1));
                $limit = max(1, intval($_POST['limit'] ?? 10));
                $offset = ($page - 1) * $limit;
                
                $sql = "SELECT m.*, 
                       c.category_name,
                       s.company_name as supplier_name,
                       b.branch_name
                       FROM medicines m
                       LEFT JOIN medicine_categories c ON m.category_id = c.category_id
                       LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
                       LEFT JOIN branches b ON m.branch_id = b.branch_id
                       WHERE $where_sql 
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
                
                $medicines = [];
                $sn = $offset + 1;
                while ($row = $result->fetch_assoc()) {
                    $row['sn'] = $sn++;
                    
                    // Format text values
                    $row['stock_status_text'] = getStatusText($row['stock_status']);
                    $row['dosage_form_text'] = getDosageFormText($row['dosage_form']);
                    
                    // Calculate stock percentage
                    $row['stock_percentage'] = $row['max_stock_level'] > 0 
                        ? round(($row['current_stock'] / $row['max_stock_level']) * 100, 1)
                        : 0;
                    
                    // Determine stock level
                    if ($row['stock_percentage'] <= 30) {
                        $row['stock_level'] = 'low';
                    } elseif ($row['stock_percentage'] <= 60) {
                        $row['stock_level'] = 'medium';
                    } else {
                        $row['stock_level'] = 'high';
                    }
                    
                    // Format dates
                    $row['expiry_date_formatted'] = date('M d, Y', strtotime($row['expiry_date']));
                    $row['purchase_date_formatted'] = $row['purchase_date'] ? date('M d, Y', strtotime($row['purchase_date'])) : 'Not set';
                    
                    // Calculate days to expiry
                    $today = new DateTime();
                    $expiry_date = new DateTime($row['expiry_date']);
                    $days_to_expiry = $expiry_date->diff($today)->days;
                    
                    if ($expiry_date < $today) {
                        $row['expiry_status'] = 'expired';
                        $row['expiry_text'] = 'Expired';
                    } elseif ($days_to_expiry <= 30) {
                        $row['expiry_status'] = 'expiring_soon';
                        $row['expiry_text'] = $days_to_expiry . ' days';
                    } else {
                        $row['expiry_status'] = 'good';
                        $row['expiry_text'] = 'Good';
                    }
                    
                    $medicines[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $medicines;
                $response['total'] = $total_medicines;
                $response['page'] = $page;
                $response['pages'] = ceil($total_medicines / $limit);
                
                $stmt->close();
                break;
                
            case 'get_inventory_stats':
                // Build where clause
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Filter by branch if user is not admin
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get overall statistics
                $stats_sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN stock_status = 'instock' THEN 1 ELSE 0 END) as in_stock_items,
                    SUM(CASE WHEN stock_status = 'lowstock' THEN 1 ELSE 0 END) as low_stock_items,
                    SUM(CASE WHEN stock_status = 'outofstock' THEN 1 ELSE 0 END) as out_of_stock_items,
                    SUM(CASE WHEN stock_status = 'expired' THEN 1 ELSE 0 END) as expired_items,
                    SUM(current_stock * selling_price) as total_inventory_value
                    FROM medicines
                    WHERE $where_sql";
                
                $stmt = $conn->prepare($stats_sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $stats_result = $stmt->fetch_assoc();
                $stmt->close();
                
                // Get expiring soon count (within 30 days)
                $today = date('Y-m-d');
                $next_month = date('Y-m-d', strtotime('+30 days'));
                $expiring_sql = "SELECT COUNT(*) as expiring_soon 
                               FROM medicines 
                               WHERE expiry_date BETWEEN ? AND ? 
                               AND current_stock > 0 
                               AND $where_sql";
                
                $exp_stmt = $conn->prepare($expiring_sql);
                if (!empty($params)) {
                    $all_params = array_merge([$today, $next_month], $params);
                    $all_types = "ss" . $types;
                    $exp_stmt->bind_param($all_types, ...$all_params);
                } else {
                    $exp_stmt->bind_param("ss", $today, $next_month);
                }
                $exp_stmt->execute();
                $expiring_result = $exp_stmt->get_result()->fetch_assoc();
                $exp_stmt->close();
                
                $response['success'] = true;
                $response['stats'] = [
                    'total_items' => $stats_result['total_items'] ?? 0,
                    'in_stock_items' => $stats_result['in_stock_items'] ?? 0,
                    'low_stock_items' => $stats_result['low_stock_items'] ?? 0,
                    'out_of_stock_items' => $stats_result['out_of_stock_items'] ?? 0,
                    'expired_items' => $stats_result['expired_items'] ?? 0,
                    'expiring_soon' => $expiring_result['expiring_soon'] ?? 0,
                    'total_inventory_value' => $stats_result['total_inventory_value'] ?? 0
                ];
                break;
                
            case 'get_categories':
                $stmt = $conn->prepare("SELECT category_id, category_name FROM medicine_categories WHERE status = 'active' ORDER BY category_name");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $categories = [];
                while ($row = $result->fetch_assoc()) {
                    $categories[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $categories;
                $stmt->close();
                break;
                
            case 'get_suppliers':
                $stmt = $conn->prepare("SELECT supplier_id, company_name FROM suppliers WHERE status = 'active' ORDER BY company_name");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $suppliers = [];
                while ($row = $result->fetch_assoc()) {
                    $suppliers[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $suppliers;
                $stmt->close();
                break;
                
            default:
                $response['message'] = "Invalid action!";
        }
    } catch (Exception $e) {
        $response['message'] = "System Error: " . $e->getMessage();
        error_log("Inventory API Error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Get initial data for page load
$initial_stats = [
    'total_items' => 0,
    'in_stock_items' => 0,
    'low_stock_items' => 0,
    'out_of_stock_items' => 0,
    'expired_items' => 0,
    'expiring_soon' => 0,
    'total_inventory_value' => 0
];

// Get statistics
try {
    $stats_sql = "SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN stock_status = 'instock' THEN 1 ELSE 0 END) as in_stock_items,
        SUM(CASE WHEN stock_status = 'lowstock' THEN 1 ELSE 0 END) as low_stock_items,
        SUM(CASE WHEN stock_status = 'outofstock' THEN 1 ELSE 0 END) as out_of_stock_items,
        SUM(CASE WHEN stock_status = 'expired' THEN 1 ELSE 0 END) as expired_items,
        SUM(current_stock * selling_price) as total_inventory_value
        FROM medicines";
    
    if ($user_role !== 'admin' && $current_branch_id) {
        $stats_sql .= " WHERE branch_id = ?";
        $stmt = $conn->prepare($stats_sql);
        $stmt->bind_param("i", $current_branch_id);
        $stmt->execute();
        $stats_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $stats_result = $conn->query($stats_sql)->fetch_assoc();
    }
    
    if ($stats_result) {
        $initial_stats = array_merge($initial_stats, $stats_result);
    }
    
    // Get expiring soon count
    $today = date('Y-m-d');
    $next_month = date('Y-m-d', strtotime('+30 days'));
    $expiring_sql = "SELECT COUNT(*) as expiring_soon 
                     FROM medicines 
                     WHERE expiry_date BETWEEN ? AND ? 
                     AND current_stock > 0";
    
    if ($user_role !== 'admin' && $current_branch_id) {
        $expiring_sql .= " AND branch_id = ?";
        $stmt = $conn->prepare($expiring_sql);
        $stmt->bind_param("ssi", $today, $next_month, $current_branch_id);
    } else {
        $stmt = $conn->prepare($expiring_sql);
        $stmt->bind_param("ss", $today, $next_month);
    }
    
    $stmt->execute();
    $expiring_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $initial_stats['expiring_soon'] = $expiring_result['expiring_soon'] ?? 0;
} catch (Exception $e) {
    error_log("Error loading initial stats: " . $e->getMessage());
}

// Get categories for filter
$categories = [];
try {
    $categories_result = $conn->query("SELECT category_id, category_name FROM medicine_categories WHERE status = 'active' ORDER BY category_name");
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
} catch (Exception $e) {
    error_log("Error loading categories: " . $e->getMessage());
}

// Get suppliers for filter
$suppliers = [];
try {
    $suppliers_result = $conn->query("SELECT supplier_id, company_name FROM suppliers WHERE status = 'active' ORDER BY company_name");
    while ($row = $suppliers_result->fetch_assoc()) {
        $suppliers[] = $row;
    }
} catch (Exception $e) {
    error_log("Error loading suppliers: " . $e->getMessage());
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

// Get initial medicines list
$initial_medicines = [];
try {
    $medicines_sql = "SELECT m.*, c.category_name, s.company_name as supplier_name, b.branch_name 
                      FROM medicines m
                      LEFT JOIN medicine_categories c ON m.category_id = c.category_id
                      LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
                      LEFT JOIN branches b ON m.branch_id = b.branch_id
                      WHERE 1=1";
    
    if ($user_role !== 'admin' && $current_branch_id) {
        $medicines_sql .= " AND m.branch_id = ?";
        $stmt = $conn->prepare($medicines_sql);
        $stmt->bind_param("i", $current_branch_id);
    } else {
        $stmt = $conn->prepare($medicines_sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sn = 1;
    while ($row = $result->fetch_assoc()) {
        $row['sn'] = $sn++;
        $row['stock_status_text'] = getStatusText($row['stock_status']);
        $row['dosage_form_text'] = getDosageFormText($row['dosage_form']);
        
        // Calculate stock percentage
        $row['stock_percentage'] = $row['max_stock_level'] > 0 
            ? round(($row['current_stock'] / $row['max_stock_level']) * 100, 1)
            : 0;
        
        // Determine stock level
        if ($row['stock_percentage'] <= 30) {
            $row['stock_level'] = 'low';
        } elseif ($row['stock_percentage'] <= 60) {
            $row['stock_level'] = 'medium';
        } else {
            $row['stock_level'] = 'high';
        }
        
        $initial_medicines[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error loading initial medicines: " . $e->getMessage());
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Master Clinic</title>
    <!-- Include SweetAlert -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ================== Inventory Management Styles ============== */
        .inventory-management-section {
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
        .inventory-stats {
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
            border-color: var(--danger);
        }

        .stat-card:nth-child(5) {
            border-color: var(--success);
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
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .stat-card:nth-child(5) .stat-icon {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
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

        /* Inventory Table */
        .table-container {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--gray);
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

        .export-btn {
            padding: 8px 20px;
            background: var(--light-gray);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .export-btn:hover {
            background: #e0e0e0;
        }

        /* RESPONSIVE AND SCROLLABLE TABLE CONTAINER */
        .table-responsive-container {
            width: 100%;
            overflow-x: auto;
            position: relative;
        }

        .table-responsive-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive-container::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 4px;
        }

        .table-responsive-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .table-responsive-container::-webkit-scrollbar-thumb:hover {
            background: var(--secondary);
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .inventory-table thead {
            background: var(--light-gray);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .inventory-table th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--black);
            font-size: 0.95rem;
            border-bottom: 2px solid var(--gray);
            white-space: nowrap;
        }

        .inventory-table tbody tr {
            border-bottom: 1px solid var(--gray);
            transition: background 0.3s ease;
        }

        .inventory-table tbody tr:hover {
            background: rgba(42, 92, 139, 0.05);
        }

        .inventory-table td {
            padding: 15px 20px;
            color: var(--black);
            font-size: 0.95rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Medicine Image */
        .medicine-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--gray);
            flex-shrink: 0;
        }

        .medicine-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Status Badges */
        .medicine-status {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-instock {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .status-lowstock {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .status-outofstock {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .status-expired {
            background: rgba(149, 165, 166, 0.1);
            color: #7f8c8d;
        }

        /* Category Badges */
        .category-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .category-antibiotic {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .category-analgesic {
            background: rgba(41, 128, 185, 0.1);
            color: var(--primary);
        }

        .category-antihistamine {
            background: rgba(26, 188, 156, 0.1);
            color: var(--accent);
        }

        .category-antacid {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .category-vitamin {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .category-cough {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        /* Stock Level Indicator */
        .stock-level {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 150px;
        }

        .stock-bar {
            flex: 1;
            height: 6px;
            background: var(--light-gray);
            border-radius: 3px;
            overflow: hidden;
            min-width: 60px;
        }

        .stock-fill {
            height: 100%;
            border-radius: 3px;
        }

        .stock-fill.high {
            background: var(--success);
        }

        .stock-fill.medium {
            background: var(--warning);
        }

        .stock-fill.low {
            background: var(--danger);
        }

        /* Price Styling */
        .medicine-price {
            font-weight: 600;
            color: var(--primary);
            white-space: nowrap;
        }

        /* Supplier Info */
        .supplier-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 150px;
        }

        .supplier-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        /* Expiry Date */
        .expiry-date {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .expiry-icon {
            color: var(--warning);
            font-size: 1.1rem;
        }

        .expiry-near {
            color: var(--warning);
            font-weight: 600;
        }

        .expiry-passed {
            color: var(--danger);
            font-weight: 600;
        }

        /* Actions */
        .medicine-actions {
            display: flex;
            gap: 8px;
            min-width: 160px;
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
            flex-shrink: 0;
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

        .action-icon.restock {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .action-icon.restock:hover {
            background: var(--success);
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
            max-width: 800px;
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
            grid-template-columns: repeat(2, 1fr);
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

        /* Medicine Image Upload */
        .image-upload-section {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 10px;
        }

        .image-preview {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            border: 3px solid var(--gray);
            position: relative;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-controls {
            flex: 1;
        }

        .upload-btn {
            padding: 12px 25px;
            background: var(--light-gray);
            border: 2px dashed var(--primary);
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            color: var(--primary);
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .upload-btn:hover {
            background: var(--primary);
            color: white;
        }

        .upload-btn input[type="file"] {
            display: none;
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

        .confirmation-icon.warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .confirmation-icon.success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
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

        .medicine-details {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .medicine-image-large {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            overflow: hidden;
            border: 3px solid var(--gray);
        }

        .medicine-image-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .medicine-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .medicine-info h4 {
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

        /* Stock Alerts */
        .stock-alerts {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .alerts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .alerts-header h3 {
            margin: 0;
            color: var(--warning);
            font-size: 1.3rem;
        }

        .alert-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            background: rgba(243, 156, 18, 0.05);
            margin-bottom: 10px;
            border-left: 4px solid var(--warning);
        }

        .alert-item.critical {
            background: rgba(231, 76, 60, 0.05);
            border-left-color: var(--danger);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .alert-item .alert-icon {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .alert-item.critical .alert-icon {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .alert-content {
            flex: 1;
        }

        .alert-content h5 {
            margin: 0 0 5px 0;
            font-size: 0.95rem;
        }

        .alert-content p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--dark-gray);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .modal-form {
                grid-template-columns: 1fr;
            }
            
            .inventory-table {
                min-width: 1000px;
            }
        }

        @media (max-width: 768px) {
            .inventory-management-section {
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

            .inventory-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .image-upload-section {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .modal-content {
                width: 95%;
                margin: 10px;
            }

            .medicine-actions {
                flex-direction: column;
                gap: 5px;
            }

            .action-icon {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }

            .medicine-details {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .pagination {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .pagination-controls {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 10px;
            }
        }

        @media (max-width: 480px) {
            .inventory-stats {
                grid-template-columns: 1fr;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .action-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }

            .modal-body {
                padding: 20px;
            }
            
            .inventory-table th,
            .inventory-table td {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            
            .medicine-actions {
                min-width: 140px;
            }
        }
    </style>
</head>
<?php include 'includes/header.php'; ?>
<body>
    <!-- =============== Navigation ================ -->
    <div class="container">
        <!-- Overlay for closing sidebar on mobile -->
        <div class="overlay" id="overlay"></div>
        
        <?php include 'includes/sidebar.php'; ?>

        <!-- ========================= Main ==================== -->
        <div class="main">
            <?php include 'includes/navigation.php'; ?>

            <!-- ================== Inventory Management Content ============== -->
            <div class="inventory-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Inventory Management</h1>
                        <p>Manage pharmacy stock, monitor levels, and track expiry dates</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshInventory()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <button class="action-btn primary" onclick="openAddMedicineModal()" id="addMedicineBtn">
                            <ion-icon name="add-circle-outline"></ion-icon>
                            Add New Medicine
                        </button>
                    </div>
                </div>

                <!-- Inventory Statistics -->
                <div class="inventory-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="medical-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalItems"><?php echo $initial_stats['total_items']; ?></h3>
                            <p>Total Items</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="inStockItems"><?php echo $initial_stats['in_stock_items']; ?></h3>
                            <p>In Stock</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="warning-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="lowStockItems"><?php echo $initial_stats['low_stock_items']; ?></h3>
                            <p>Low Stock</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="close-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="outOfStockItems"><?php echo $initial_stats['out_of_stock_items']; ?></h3>
                            <p>Out of Stock</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="expiringItems"><?php echo $initial_stats['expiring_soon']; ?></h3>
                            <p>Expiring Soon</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search by medicine name, generic name, or batch number..." 
                               onkeyup="filterInventory()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Category</label>
                            <select id="categoryFilter" onchange="filterInventory()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Stock Status</label>
                            <select id="statusFilter" onchange="filterInventory()">
                                <option value="">All Status</option>
                                <option value="instock">In Stock</option>
                                <option value="lowstock">Low Stock</option>
                                <option value="outofstock">Out of Stock</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Supplier</label>
                            <select id="supplierFilter" onchange="filterInventory()">
                                <option value="">All Suppliers</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>">
                                        <?php echo htmlspecialchars($supplier['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterInventory()">
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="stock_low">Stock (Low to High)</option>
                                <option value="stock_high">Stock (High to Low)</option>
                                <option value="price_low">Price (Low to High)</option>
                                <option value="price_high">Price (High to Low)</option>
                                <option value="expiry_soon">Expiry (Soonest)</option>
                                <option value="expiry_late">Expiry (Latest)</option>
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

                <!-- Inventory Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Medicine Inventory</h2>
                        <div class="table-actions">
                            <!-- Table actions can be added here if needed -->
                        </div>
                    </div>
                    
                    <!-- Responsive and Scrollable Table Container -->
                    <div class="table-responsive-container">
                        <table class="inventory-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>MEDICINE DETAILS</th>
                                    <th>CATEGORY</th>
                                    <th>STOCK LEVEL</th>
                                    <th>PRICE (MWK)</th>
                                    <th>SUPPLIER</th>
                                    <th>EXPIRY DATE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                <?php if (empty($initial_medicines)): ?>
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <ion-icon name="medical-outline"></ion-icon>
                                        <h3>No medicines found</h3>
                                        <p>Add your first medicine to get started</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_medicines as $medicine): ?>
                                <?php 
                                    $statusClass = 'status-' . $medicine['stock_status'];
                                    $statusText = $medicine['stock_status_text'];
                                    $categoryName = strtolower(preg_replace('/[^a-z]/', '', $medicine['category_name']));
                                    $categoryClass = 'category-' . $categoryName;
                                ?>
                                <tr>
                                    <td><?php echo $medicine['sn']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <div class="medicine-image">
                                                <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="<?php echo htmlspecialchars($medicine['medicine_name']); ?>">
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($medicine['medicine_name']); ?></strong><br>
                                                <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($medicine['generic_name']); ?></small><br>
                                                <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($medicine['dosage_form_text']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="category-badge <?php echo $categoryClass; ?>"><?php echo htmlspecialchars($medicine['category_name']); ?></span></td>
                                    <td>
                                        <div class="stock-level">
                                            <span><?php echo $medicine['current_stock']; ?></span>
                                            <div class="stock-bar">
                                                <div class="stock-fill <?php echo $medicine['stock_level']; ?>" style="width: <?php echo $medicine['stock_percentage']; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="medicine-price">MWK <?php echo number_format($medicine['selling_price'], 2); ?></td>
                                    <td>
                                        <div class="supplier-info">
                                            <div class="supplier-avatar"><?php echo substr($medicine['supplier_name'] ?? 'N/A', 0, 1); ?></div>
                                            <span><?php echo htmlspecialchars($medicine['supplier_name'] ?? 'Not assigned'); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="expiry-date">
                                            <ion-icon name="calendar-outline" class="expiry-icon"></ion-icon>
                                            <?php echo date('M d, Y', strtotime($medicine['expiry_date'])); ?>
                                        </div>
                                    </td>
                                    <td><span class="medicine-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td>
                                        <div class="medicine-actions">
                                            <button class="action-icon view" title="View Details" onclick="viewMedicine(<?php echo $medicine['medicine_id']; ?>)">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon edit" title="Edit" onclick="editMedicine(<?php echo $medicine['medicine_id']; ?>)">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon delete" title="Delete" onclick="deleteMedicine(<?php echo $medicine['medicine_id']; ?>)">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon restock" title="Restock" onclick="restockMedicine(<?php echo $medicine['medicine_id']; ?>)">
                                                <ion-icon name="add-circle-outline"></ion-icon>
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
                            Showing <?php echo count($initial_medicines) > 0 ? 1 : 0; ?> to <?php echo count($initial_medicines); ?> of <?php echo $initial_stats['total_items']; ?> entries
                        </div>
                        <?php if ($initial_stats['total_items'] > 10): ?>
                        <div class="pagination-controls">
                            <button class="pagination-btn" onclick="changePage('prev')" disabled>
                                <ion-icon name="chevron-back-outline"></ion-icon>
                            </button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn" onclick="changePage('next')">
                                <ion-icon name="chevron-forward-outline"></ion-icon>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Medicine Modal -->
            <div class="modal-overlay" id="medicineModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add New Medicine</h3>
                        <button class="modal-close" onclick="closeMedicineModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="medicineForm" class="modal-form">
                            <input type="hidden" id="medicineId">
                            
                            <!-- Image Upload -->
                            <div class="image-upload-section">
                                <div class="image-preview">
                                    <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" 
                                         alt="Medicine Image" id="imagePreview">
                                </div>
                                <div class="upload-controls">
                                    <label class="upload-btn">
                                        <ion-icon name="cloud-upload-outline"></ion-icon>
                                        Upload Medicine Image
                                        <input type="file" id="imageUpload" accept="image/*" onchange="previewImage(this)">
                                    </label>
                                    <p style="margin-top: 10px; color: var(--dark-gray); font-size: 0.9rem;">
                                        Recommended: 400x400px, max 2MB. PNG or JPG format.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Basic Information -->
                            <div class="form-group">
                                <label for="medicineName" class="required">Medicine Name</label>
                                <input type="text" id="medicineName" placeholder="e.g., Panadol Extra" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="genericName" class="required">Generic Name</label>
                                <input type="text" id="genericName" placeholder="e.g., Paracetamol" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="categoryId" class="required">Category</label>
                                <select id="categoryId" required>
                                    <option value="">-- select --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="dosageForm">Dosage Form</label>
                                <select id="dosageForm">
                                    <option value="">-- select --</option>
                                    <option value="tablet">Tablet</option>
                                    <option value="capsule">Capsule</option>
                                    <option value="syrup">Syrup</option>
                                    <option value="injection">Injection</option>
                                    <option value="ointment">Ointment</option>
                                    <option value="cream">Cream</option>
                                    <option value="drops">Drops</option>
                                    <option value="inhaler">Inhaler</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="strength">Strength</label>
                                <input type="text" id="strength" placeholder="e.g., 500mg">
                            </div>
                            
                            <div class="form-group">
                                <label for="manufacturer">Manufacturer</label>
                                <input type="text" id="manufacturer" placeholder="e.g., GlaxoSmithKline">
                            </div>
                            
                            <!-- Stock Information -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Stock Information</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="batchNumber" class="required">Batch Number</label>
                                <input type="text" id="batchNumber" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="currentStock" class="required">Current Stock</label>
                                <input type="number" id="currentStock" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="minStockLevel">Minimum Stock Level</label>
                                <input type="number" id="minStockLevel" min="0" value="10">
                            </div>
                            
                            <div class="form-group">
                                <label for="maxStockLevel">Maximum Stock Level</label>
                                <input type="number" id="maxStockLevel" min="0" value="100">
                            </div>
                            
                            <!-- Pricing -->
                            <div class="form-group">
                                <label for="unitPrice" class="required">Unit Price (MWK)</label>
                                <input type="number" id="unitPrice" min="0" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="sellingPrice" class="required">Selling Price (MWK)</label>
                                <input type="number" id="sellingPrice" min="0" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="discountPercentage">Discount (%)</label>
                                <input type="number" id="discountPercentage" min="0" max="100" step="0.1" value="0">
                            </div>
                            
                            <!-- Supplier & Expiry -->
                            <div class="form-group">
                                <label for="supplierId">Supplier</label>
                                <select id="supplierId">
                                    <option value="">-- select --</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>">
                                            <?php echo htmlspecialchars($supplier['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="purchaseDate">Purchase Date</label>
                                <input type="date" id="purchaseDate">
                            </div>
                            
                            <div class="form-group">
                                <label for="expiryDate" class="required">Expiry Date</label>
                                <input type="date" id="expiryDate" required>
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="form-group full-width">
                                <label for="storageInstructions">Storage Instructions</label>
                                <textarea id="storageInstructions" placeholder="e.g., Store in a cool dry place below 25°C"></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description / Notes</label>
                                <textarea id="description" placeholder="Additional information about this medicine..."></textarea>
                            </div>
                            
                            <!-- Prescription Info -->
                            <div class="form-group">
                                <label for="requiresPrescription">Requires Prescription</label>
                                <select id="requiresPrescription">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="discontinued">Discontinued</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="unitOfMeasure">Unit of Measure</label>
                                <select id="unitOfMeasure">
                                    <option value="unit">Unit</option>
                                    <option value="tablet">Tablet</option>
                                    <option value="capsule">Capsule</option>
                                    <option value="bottle">Bottle</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack</option>
                                    <option value="tube">Tube</option>
                                    <option value="ml">ml</option>
                                    <option value="mg">mg</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeMedicineModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveMedicine()">
                            Save Medicine
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Medicine Details Modal -->
            <div class="modal-overlay view-details-modal" id="viewMedicineModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="viewModalTitle">Medicine Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="medicine-details">
                            <div class="medicine-image-large">
                                <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="Medicine" id="viewImage">
                            </div>
                            <div class="medicine-info">
                                <h4 id="viewMedicineName">Loading...</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Generic Name:</span>
                                        <span class="info-value" id="viewGenericName">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Category:</span>
                                        <span class="info-value" id="viewCategory">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Dosage Form:</span>
                                        <span class="info-value" id="viewDosageForm">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Strength:</span>
                                        <span class="info-value" id="viewStrength">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Manufacturer:</span>
                                        <span class="info-value" id="viewManufacturer">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Batch Number:</span>
                                        <span class="info-value" id="viewBatchNumber">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Current Stock:</span>
                                        <span class="info-value" id="viewCurrentStock">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Stock Status:</span>
                                        <span class="info-value" id="viewStockStatus">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Unit Price:</span>
                                        <span class="info-value" id="viewUnitPrice">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Selling Price:</span>
                                        <span class="info-value" id="viewSellingPrice">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Supplier:</span>
                                        <span class="info-value" id="viewSupplier">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Expiry Date:</span>
                                        <span class="info-value" id="viewExpiryDate">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Storage Instructions:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px;" id="viewStorageInstructions">
                                Loading...
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Description:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px;" id="viewDescription">
                                Loading...
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                        <button type="button" class="action-btn primary" onclick="editCurrentMedicine()">
                            <ion-icon name="create-outline"></ion-icon>
                            Edit Medicine
                        </button>
                        <button type="button" class="action-btn success" onclick="restockCurrentMedicine()">
                            <ion-icon name="add-circle-outline"></ion-icon>
                            Restock
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="deleteMedicineModal">
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
                        <h4>Delete Medicine</h4>
                        <p>Are you sure you want to delete <strong id="deleteMedicineName">[Medicine Name]</strong>? This action cannot be undone and all medicine data will be permanently removed.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="action-btn danger" onclick="confirmDelete()">
                                <ion-icon name="trash-outline"></ion-icon>
                                Delete Medicine
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restock Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="restockModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Restock Medicine</h3>
                        <button class="modal-close" onclick="closeRestockModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="restockForm">
                            <div class="form-group">
                                <label for="restockQuantity" class="required">Quantity to Add</label>
                                <input type="number" id="restockQuantity" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="restockSupplier">Supplier</label>
                                <select id="restockSupplier">
                                    <option value="">-- select --</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>">
                                            <?php echo htmlspecialchars($supplier['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="restockBatch">Batch Number</label>
                                <input type="text" id="restockBatch">
                            </div>
                            
                            <div class="form-group">
                                <label for="restockExpiry">Expiry Date</label>
                                <input type="date" id="restockExpiry">
                            </div>
                            
                            <div class="form-group">
                                <label for="restockPrice">Unit Price (MWK)</label>
                                <input type="number" id="restockPrice" min="0" step="0.01">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="restockNotes">Notes</label>
                                <textarea id="restockNotes" placeholder="Additional information about this restock..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeRestockModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn success" onclick="confirmRestock()">
                            <ion-icon name="checkmark-outline"></ion-icon>
                            Confirm Restock
                        </button>
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
        let filteredInventory = <?php echo json_encode($initial_medicines); ?>;
        let totalMedicines = <?php echo $initial_stats['total_items']; ?>;
        let medicineToDelete = null;
        let medicineToView = null;
        let medicineToRestock = null;

        // User role from PHP
        const userRole = '<?php echo $user_role; ?>';
        const currentBranchId = <?php echo $current_branch_id ?: 'null'; ?>;
        const isAdmin = userRole === 'admin';

        // DOM Elements
        const tableBody = document.getElementById('inventoryTableBody');
        const medicineModal = document.getElementById('medicineModal');
        const viewMedicineModal = document.getElementById('viewMedicineModal');
        const deleteMedicineModal = document.getElementById('deleteMedicineModal');
        const restockModal = document.getElementById('restockModal');

        // Initialize Page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Inventory Management System Initialized');
            
            // Set default dates
            const purchaseDateInput = document.getElementById('purchaseDate');
            const expiryDateInput = document.getElementById('expiryDate');
            if (purchaseDateInput) {
                const today = new Date().toISOString().split('T')[0];
                purchaseDateInput.value = today;
            }
            if (expiryDateInput) {
                const nextYear = new Date();
                nextYear.setFullYear(nextYear.getFullYear() + 1);
                expiryDateInput.value = nextYear.toISOString().split('T')[0];
            }
            
            // Add event listeners for modals
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.remove('active');
                    }
                });
            });
            
            console.log('Page initialization complete');
        });

        // Image Preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (!preview) return;
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            
            if (input.files && input.files[0]) {
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Modal Functions
        function openAddMedicineModal() {
            document.getElementById('modalTitle').textContent = 'Add New Medicine';
            document.getElementById('medicineForm').reset();
            document.getElementById('medicineId').value = '';
            
            // Set default values
            document.getElementById('minStockLevel').value = '10';
            document.getElementById('maxStockLevel').value = '100';
            document.getElementById('discountPercentage').value = '0';
            document.getElementById('requiresPrescription').value = 'no';
            document.getElementById('status').value = 'active';
            document.getElementById('unitOfMeasure').value = 'unit';
            
            // Set default dates
            const purchaseDateInput = document.getElementById('purchaseDate');
            const expiryDateInput = document.getElementById('expiryDate');
            if (purchaseDateInput) {
                const today = new Date().toISOString().split('T')[0];
                purchaseDateInput.value = today;
            }
            if (expiryDateInput) {
                const nextYear = new Date();
                nextYear.setFullYear(nextYear.getFullYear() + 1);
                expiryDateInput.value = nextYear.toISOString().split('T')[0];
            }
            
            // Reset image preview
            document.getElementById('imagePreview').src = 'https://cdn-icons-png.flaticon.com/512/3144/3144456.png';
            
            medicineModal.classList.add('active');
        }

        function closeMedicineModal() {
            medicineModal.classList.remove('active');
        }

        function closeViewModal() {
            viewMedicineModal.classList.remove('active');
        }

        function closeDeleteModal() {
            deleteMedicineModal.classList.remove('active');
            medicineToDelete = null;
        }

        function closeRestockModal() {
            restockModal.classList.remove('active');
            medicineToRestock = null;
        }

        // Load Medicines
        function loadMedicines() {
            const formData = new FormData();
            formData.append('action', 'get_medicines');
            formData.append('search', document.getElementById('searchInput').value);
            formData.append('category', document.getElementById('categoryFilter').value);
            formData.append('status', document.getElementById('statusFilter').value);
            formData.append('supplier', document.getElementById('supplierFilter').value);
            formData.append('sort', document.getElementById('sortFilter').value);
            formData.append('page', currentPage);
            formData.append('limit', itemsPerPage);

            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    filteredInventory = data.data;
                    totalMedicines = data.total;
                    renderInventoryTable();
                    updateStatistics();
                    updatePaginationInfo();
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicines');
            });
        }

        // Update Statistics
        function updateStatistics() {
            const formData = new FormData();
            formData.append('action', 'get_inventory_stats');

            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.stats;
                    document.getElementById('totalItems').textContent = stats.total_items;
                    document.getElementById('inStockItems').textContent = stats.in_stock_items;
                    document.getElementById('lowStockItems').textContent = stats.low_stock_items;
                    document.getElementById('outOfStockItems').textContent = stats.out_of_stock_items;
                    document.getElementById('expiringItems').textContent = stats.expiring_soon;
                }
            })
            .catch(error => {
                console.error('Error updating statistics:', error);
            });
        }

        // Render Inventory Table
        function renderInventoryTable() {
            if (!tableBody) return;
            
            if (filteredInventory.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <ion-icon name="medical-outline"></ion-icon>
                            <h3>No medicines found</h3>
                            <p>${isAdmin ? 'Try adjusting your search or filters' : 'No medicines in your branch.'}</p>
                        </td>
                    </tr>
                `;
                updatePaginationInfo();
                return;
            }

            let html = '';
            filteredInventory.forEach(medicine => {
                const statusClass = `status-${medicine.stock_status}`;
                const categoryName = medicine.category_name ? medicine.category_name.toLowerCase().replace(/[^a-z]/g, '') : 'other';
                const categoryClass = `category-${categoryName}`;
                
                // Determine stock fill class based on stock level
                let stockFillClass = medicine.stock_level || 'high';
                
                // Format price
                const sellingPrice = parseFloat(medicine.selling_price).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                // Determine expiry display
                let expiryDisplay = medicine.expiry_date_formatted;
                let expiryClass = '';
                if (medicine.expiry_status === 'expired') {
                    expiryClass = 'expiry-passed';
                    expiryDisplay = `<span class="${expiryClass}">${expiryDisplay}</span>`;
               } else if (medicine.expiry_status === 'expiring_soon') {
                    expiryClass = 'expiry-near';
                    expiryDisplay = `<span class="${expiryClass}">${expiryDisplay} (${medicine.expiry_text})</span>`;
                }

                html += `
                <tr>
                    <td>${medicine.sn}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="medicine-image">
                                <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="${medicine.medicine_name}">
                            </div>
                            <div>
                                <strong>${medicine.medicine_name}</strong><br>
                                <small style="color: var(--dark-gray);">${medicine.generic_name}</small><br>
                                <small style="color: var(--dark-gray);">${medicine.dosage_form_text}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="category-badge ${categoryClass}">${medicine.category_name || 'Uncategorized'}</span></td>
                    <td>
                        <div class="stock-level">
                            <span>${medicine.current_stock}</span>
                            <div class="stock-bar">
                                <div class="stock-fill ${stockFillClass}" style="width: ${medicine.stock_percentage}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="medicine-price">MWK ${sellingPrice}</td>
                    <td>
                        <div class="supplier-info">
                            <div class="supplier-avatar">${medicine.supplier_name ? medicine.supplier_name.charAt(0) : 'N'}</div>
                            <span>${medicine.supplier_name || 'Not assigned'}</span>
                        </div>
                    </td>
                    <td>
                        <div class="expiry-date">
                            <ion-icon name="calendar-outline" class="expiry-icon"></ion-icon>
                            ${expiryDisplay}
                        </div>
                    </td>
                    <td><span class="medicine-status ${statusClass}">${medicine.stock_status_text}</span></td>
                    <td>
                        <div class="medicine-actions">
                            <button class="action-icon view" title="View Details" onclick="viewMedicine(${medicine.medicine_id})">
                                <ion-icon name="eye-outline"></ion-icon>
                            </button>
                            <button class="action-icon edit" title="Edit" onclick="editMedicine(${medicine.medicine_id})">
                                <ion-icon name="create-outline"></ion-icon>
                            </button>
                            <button class="action-icon delete" title="Delete" onclick="deleteMedicine(${medicine.medicine_id})">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                            <button class="action-icon restock" title="Restock" onclick="restockMedicine(${medicine.medicine_id})">
                                <ion-icon name="add-circle-outline"></ion-icon>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            });

            tableBody.innerHTML = html;
            updatePaginationInfo();
        }

        // Update Pagination Info
        function updatePaginationInfo() {
            const start = (currentPage - 1) * itemsPerPage + 1;
            const end = Math.min(currentPage * itemsPerPage, totalMedicines);
            
            document.getElementById('paginationInfo').textContent = 
                `Showing ${start} to ${end} of ${totalMedicines} entries`;
            
            // Update pagination buttons
            const paginationControls = document.querySelector('.pagination-controls');
            if (paginationControls && totalMedicines > itemsPerPage) {
                const totalPages = Math.ceil(totalMedicines / itemsPerPage);
                let paginationHTML = `
                    <button class="pagination-btn" onclick="changePage('prev')" ${currentPage === 1 ? 'disabled' : ''}>
                        <ion-icon name="chevron-back-outline"></ion-icon>
                    </button>
                `;
                
                // Show page numbers
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                
                if (endPage - startPage + 1 < maxVisiblePages) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }
                
                if (startPage > 1) {
                    paginationHTML += `<button class="pagination-btn" onclick="changePage(1)">1</button>`;
                    if (startPage > 2) paginationHTML += `<span class="pagination-dots">...</span>`;
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `
                        <button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">
                            ${i}
                        </button>
                    `;
                }
                
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) paginationHTML += `<span class="pagination-dots">...</span>`;
                    paginationHTML += `<button class="pagination-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
                }
                
                paginationHTML += `
                    <button class="pagination-btn" onclick="changePage('next')" ${currentPage === totalPages ? 'disabled' : ''}>
                        <ion-icon name="chevron-forward-outline"></ion-icon>
                    </button>
                `;
                
                paginationControls.innerHTML = paginationHTML;
            }
        }

        // Change Page
        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < Math.ceil(totalMedicines / itemsPerPage)) {
                currentPage++;
            } else if (typeof direction === 'number') {
                currentPage = direction;
            }
            
            loadMedicines();
        }

        // Filter Inventory
        function filterInventory() {
            currentPage = 1;
            loadMedicines();
        }

        // Apply Filters
        function applyFilters() {
            filterInventory();
        }

        // Reset Filters
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('supplierFilter').value = '';
            document.getElementById('sortFilter').value = 'name_asc';
            currentPage = 1;
            
            loadMedicines();
        }

        // Refresh Inventory
        function refreshInventory() {
            Swal.fire({
                title: 'Refreshing...',
                text: 'Loading latest inventory data',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            currentPage = 1;
            loadMedicines();
            
            setTimeout(() => {
                Swal.close();
                showSuccess('Inventory refreshed successfully!');
            }, 1000);
        }

        // View Medicine Details
        function viewMedicine(medicineId) {
            const formData = new FormData();
            formData.append('action', 'get_medicine');
            formData.append('medicine_id', medicineId);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    medicineToView = data.data;
                    
                    // Populate view modal
                    document.getElementById('viewMedicineName').textContent = medicineToView.medicine_name;
                    document.getElementById('viewModalTitle').textContent = `Details: ${medicineToView.medicine_name}`;
                    document.getElementById('viewGenericName').textContent = medicineToView.generic_name || 'N/A';
                    document.getElementById('viewCategory').textContent = medicineToView.category_name || 'Uncategorized';
                    document.getElementById('viewDosageForm').textContent = medicineToView.dosage_form_text || 'N/A';
                    document.getElementById('viewStrength').textContent = medicineToView.strength || 'N/A';
                    document.getElementById('viewManufacturer').textContent = medicineToView.manufacturer || 'N/A';
                    document.getElementById('viewBatchNumber').textContent = medicineToView.batch_number;
                    document.getElementById('viewCurrentStock').textContent = medicineToView.current_stock;
                    document.getElementById('viewStockStatus').textContent = medicineToView.stock_status_text;
                    document.getElementById('viewUnitPrice').textContent = `MWK ${parseFloat(medicineToView.unit_price).toFixed(2)}`;
                    document.getElementById('viewSellingPrice').textContent = `MWK ${parseFloat(medicineToView.selling_price).toFixed(2)}`;
                    document.getElementById('viewSupplier').textContent = medicineToView.supplier_name || 'Not assigned';
                    document.getElementById('viewExpiryDate').textContent = new Date(medicineToView.expiry_date).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    document.getElementById('viewStorageInstructions').textContent = medicineToView.storage_instructions || 'No special instructions';
                    document.getElementById('viewDescription').textContent = medicineToView.description || 'No description available';
                    
                    // Show modal
                    viewMedicineModal.classList.add('active');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicine details');
            });
        }

        // Edit Medicine
        function editMedicine(medicineId) {
            const formData = new FormData();
            formData.append('action', 'get_medicine');
            formData.append('medicine_id', medicineId);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const medicine = data.data;
                    
                    // Populate form
                    document.getElementById('modalTitle').textContent = 'Edit Medicine';
                    document.getElementById('medicineId').value = medicine.medicine_id;
                    document.getElementById('medicineName').value = medicine.medicine_name;
                    document.getElementById('genericName').value = medicine.generic_name || '';
                    document.getElementById('categoryId').value = medicine.category_id;
                    document.getElementById('dosageForm').value = medicine.dosage_form;
                    document.getElementById('strength').value = medicine.strength || '';
                    document.getElementById('manufacturer').value = medicine.manufacturer || '';
                    document.getElementById('batchNumber').value = medicine.batch_number;
                    document.getElementById('currentStock').value = medicine.current_stock;
                    document.getElementById('minStockLevel').value = medicine.min_stock_level;
                    document.getElementById('maxStockLevel').value = medicine.max_stock_level;
                    document.getElementById('unitPrice').value = medicine.unit_price;
                    document.getElementById('sellingPrice').value = medicine.selling_price;
                    document.getElementById('discountPercentage').value = medicine.discount_percentage;
                    document.getElementById('supplierId').value = medicine.supplier_id || '';
                    document.getElementById('purchaseDate').value = medicine.purchase_date || '';
                    document.getElementById('expiryDate').value = medicine.expiry_date;
                    document.getElementById('storageInstructions').value = medicine.storage_instructions || '';
                    document.getElementById('description').value = medicine.description || '';
                    document.getElementById('requiresPrescription').value = medicine.requires_prescription;
                    document.getElementById('status').value = medicine.status;
                    document.getElementById('unitOfMeasure').value = medicine.unit_of_measure;
                    
                    // Show modal
                    medicineModal.classList.add('active');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicine for editing');
            });
        }

        // Edit Current Medicine (from view modal)
        function editCurrentMedicine() {
            if (medicineToView) {
                viewMedicineModal.classList.remove('active');
                setTimeout(() => {
                    editMedicine(medicineToView.medicine_id);
                }, 300);
            }
        }

        // Save Medicine (Add/Edit)
        function saveMedicine() {
            // Validate form
            const requiredFields = ['medicineName', 'genericName', 'categoryId', 'batchNumber', 'unitPrice', 'sellingPrice', 'currentStock', 'expiryDate'];
            let isValid = true;
            let errorMessage = '';
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field || !field.value.trim()) {
                    isValid = false;
                    errorMessage = `Please fill in all required fields`;
                    field.style.borderColor = 'var(--danger)';
                } else {
                    if (field.style) field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                showError(errorMessage);
                return;
            }
            
            // Validate prices
            const unitPrice = parseFloat(document.getElementById('unitPrice').value);
            const sellingPrice = parseFloat(document.getElementById('sellingPrice').value);
            
            if (unitPrice <= 0 || sellingPrice <= 0) {
                showError('Prices must be greater than zero');
                return;
            }
            
            if (sellingPrice < unitPrice) {
                showError('Selling price must be greater than or equal to unit price');
                return;
            }
            
            // Prepare form data
            const formData = new FormData();
            const medicineId = document.getElementById('medicineId').value;
            formData.append('action', medicineId ? 'edit_medicine' : 'add_medicine');
            
            if (medicineId) {
                formData.append('medicine_id', medicineId);
            }
            
            formData.append('medicine_name', document.getElementById('medicineName').value);
            formData.append('generic_name', document.getElementById('genericName').value);
            formData.append('category_id', document.getElementById('categoryId').value);
            formData.append('dosage_form', document.getElementById('dosageForm').value);
            formData.append('strength', document.getElementById('strength').value);
            formData.append('manufacturer', document.getElementById('manufacturer').value);
            formData.append('batch_number', document.getElementById('batchNumber').value);
            formData.append('current_stock', document.getElementById('currentStock').value);
            formData.append('min_stock_level', document.getElementById('minStockLevel').value);
            formData.append('max_stock_level', document.getElementById('maxStockLevel').value);
            formData.append('unit_price', document.getElementById('unitPrice').value);
            formData.append('selling_price', document.getElementById('sellingPrice').value);
            formData.append('discount_percentage', document.getElementById('discountPercentage').value);
            formData.append('supplier_id', document.getElementById('supplierId').value);
            formData.append('purchase_date', document.getElementById('purchaseDate').value);
            formData.append('expiry_date', document.getElementById('expiryDate').value);
            formData.append('storage_instructions', document.getElementById('storageInstructions').value);
            formData.append('description', document.getElementById('description').value);
            formData.append('requires_prescription', document.getElementById('requiresPrescription').value);
            formData.append('status', document.getElementById('status').value);
            formData.append('unit_of_measure', document.getElementById('unitOfMeasure').value);
            
            // Show loading
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send request
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success) {
                    showSuccess(data.message);
                    closeMedicineModal();
                    loadMedicines();
                    updateStatistics();
                    
                    if (medicineToView) {
                        viewMedicine(medicineToView.medicine_id);
                    }
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                showError('Failed to save medicine');
            });
        }

        // Delete Medicine
        function deleteMedicine(medicineId) {
            const formData = new FormData();
            formData.append('action', 'get_medicine');
            formData.append('medicine_id', medicineId);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    medicineToDelete = data.data;
                    document.getElementById('deleteMedicineName').textContent = medicineToDelete.medicine_name;
                    deleteMedicineModal.classList.add('active');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicine for deletion');
            });
        }

        // Confirm Delete
        function confirmDelete() {
            if (!medicineToDelete) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_medicine');
            formData.append('medicine_id', medicineToDelete.medicine_id);
            
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success) {
                    showSuccess(data.message);
                    closeDeleteModal();
                    loadMedicines();
                    updateStatistics();
                    
                    if (medicineToView && medicineToView.medicine_id === medicineToDelete.medicine_id) {
                        closeViewModal();
                    }
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                showError('Failed to delete medicine');
            });
        }

        // Restock Medicine
        function restockMedicine(medicineId) {
            const formData = new FormData();
            formData.append('action', 'get_medicine');
            formData.append('medicine_id', medicineId);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    medicineToRestock = data.data;
                    document.getElementById('restockForm').reset();
                    restockModal.classList.add('active');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicine for restocking');
            });
        }

        // Restock Current Medicine (from view modal)
        function restockCurrentMedicine() {
            if (medicineToView) {
                viewMedicineModal.classList.remove('active');
                setTimeout(() => {
                    restockMedicine(medicineToView.medicine_id);
                }, 300);
            }
        }

        // Confirm Restock
        function confirmRestock() {
            if (!medicineToRestock) return;
            
            const quantity = document.getElementById('restockQuantity').value;
            if (!quantity || quantity <= 0) {
                showError('Please enter a valid quantity');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'restock_medicine');
            formData.append('medicine_id', medicineToRestock.medicine_id);
            formData.append('quantity', quantity);
            formData.append('expiry_date', document.getElementById('restockExpiry').value);
            
            Swal.fire({
                title: 'Restocking...',
                text: 'Please wait',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success) {
                    showSuccess(data.message);
                    closeRestockModal();
                    loadMedicines();
                    updateStatistics();
                    
                    if (medicineToView) {
                        viewMedicine(medicineToView.medicine_id);
                    }
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                showError('Failed to restock medicine');
            });
        }

        // Utility Functions
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message,
                timer: 3000,
                showConfirmButton: true
            });
        }

        // Export Inventory
        function exportInventory(format) {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            const supplier = document.getElementById('supplierFilter').value;
            
            let url = `export_inventory.php?format=${format}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (category) url += `&category=${category}`;
            if (status) url += `&status=${status}`;
            if (supplier) url += `&supplier=${supplier}`;
            if (!isAdmin && currentBranchId) url += `&branch_id=${currentBranchId}`;
            
            window.open(url, '_blank');
        }

        // Handle Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + F for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            
            // Ctrl + N for new medicine
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                if (!medicineModal.classList.contains('active')) {
                    openAddMedicineModal();
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                if (medicineModal.classList.contains('active')) {
                    closeMedicineModal();
                } else if (viewMedicineModal.classList.contains('active')) {
                    closeViewModal();
                } else if (deleteMedicineModal.classList.contains('active')) {
                    closeDeleteModal();
                } else if (restockModal.classList.contains('active')) {
                    closeRestockModal();
                }
            }
        });

        // Real-time Search
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterInventory();
            }, 500);
        });

        // Initialize Auto-generated Code
        document.getElementById('medicineName').addEventListener('blur', function() {
            if (this.value && !document.getElementById('genericName').value) {
                document.getElementById('genericName').value = this.value;
            }
        });

        // Auto-calculate selling price with margin
        document.getElementById('unitPrice').addEventListener('input', function() {
            const unitPrice = parseFloat(this.value) || 0;
            const sellingPriceInput = document.getElementById('sellingPrice');
            const currentSellingPrice = parseFloat(sellingPriceInput.value) || 0;
            
            // Only auto-calculate if selling price is empty or less than unit price
            if (unitPrice > 0 && (currentSellingPrice === 0 || currentSellingPrice < unitPrice)) {
                // Add 20% margin
                const sellingPrice = unitPrice * 1.2;
                sellingPriceInput.value = sellingPrice.toFixed(2);
            }
        });

        // Auto-set expiry date (1 year from purchase date)
        document.getElementById('purchaseDate').addEventListener('change', function() {
            if (this.value) {
                const purchaseDate = new Date(this.value);
                const expiryDate = new Date(purchaseDate);
                expiryDate.setFullYear(expiryDate.getFullYear() + 1);
                
                const expiryDateInput = document.getElementById('expiryDate');
                const currentExpiryDate = expiryDateInput.value;
                
                // Only set if not already set or if set to a date before purchase date
                if (!currentExpiryDate || new Date(currentExpiryDate) <= purchaseDate) {
                    expiryDateInput.value = expiryDate.toISOString().split('T')[0];
                }
            }
        });

        // Check expiry date validity
        document.getElementById('expiryDate').addEventListener('change', function() {
            if (this.value) {
                const expiryDate = new Date(this.value);
                const purchaseDateInput = document.getElementById('purchaseDate');
                const purchaseDate = purchaseDateInput.value ? new Date(purchaseDateInput.value) : new Date();
                
                if (expiryDate <= purchaseDate) {
                    showError('Expiry date must be after purchase date');
                    this.value = '';
                    this.focus();
                }
                
                // Check if expiry is within 30 days (warning)
                const today = new Date();
                const daysToExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
                
                if (daysToExpiry <= 30 && daysToExpiry > 0) {
                    showWarning(`Warning: Medicine will expire in ${daysToExpiry} days`);
                } else if (expiryDate < today) {
                    showError('Warning: Medicine is already expired');
                }
            }
        });

        function showWarning(message) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: message,
                timer: 3000,
                showConfirmButton: true
            });
        }

        // Stock level validation
        document.getElementById('currentStock').addEventListener('input', function() {
            const currentStock = parseInt(this.value) || 0;
            const minStock = parseInt(document.getElementById('minStockLevel').value) || 0;
            const maxStock = parseInt(document.getElementById('maxStockLevel').value) || 0;
            
            if (maxStock > 0 && currentStock > maxStock) {
                showWarning(`Current stock (${currentStock}) exceeds maximum stock level (${maxStock})`);
            }
            
            if (minStock > 0 && currentStock < minStock) {
                const needed = minStock - currentStock;
                showWarning(`Low stock alert! Need ${needed} more units to reach minimum stock level`);
            }
        });

        // Initialize tooltips for action buttons
        document.addEventListener('mouseover', function(e) {
            if (e.target.closest('.action-icon')) {
                const button = e.target.closest('.action-icon');
                const title = button.getAttribute('title');
                if (title) {
                    // You can implement custom tooltip here if needed
                }
            }
        });

        // Auto-refresh inventory every 5 minutes
        setInterval(() => {
            // Only refresh if no modals are open and user is active
            const modalsOpen = document.querySelectorAll('.modal-overlay.active').length > 0;
            if (!modalsOpen) {
                loadMedicines();
                updateStatistics();
                console.log('Auto-refreshed inventory data');
            }
        }, 300000); // 5 minutes

        // Initialize on page load
        window.onload = function() {
            console.log('Inventory Management System Ready');
            
            // Check for low stock items and show alert
            const lowStockItems = parseInt(document.getElementById('lowStockItems').textContent) || 0;
            const outOfStockItems = parseInt(document.getElementById('outOfStockItems').textContent) || 0;
            const expiringItems = parseInt(document.getElementById('expiringItems').textContent) || 0;
            
            if (lowStockItems > 0 || outOfStockItems > 0 || expiringItems > 0) {
                let alertMessage = '';
                if (outOfStockItems > 0) {
                    alertMessage += `${outOfStockItems} item(s) are out of stock. `;
                }
                if (lowStockItems > 0) {
                    alertMessage += `${lowStockItems} item(s) are low on stock. `;
                }
                if (expiringItems > 0) {
                    alertMessage += `${expiringItems} item(s) are expiring soon.`;
                }
                
                if (alertMessage.trim()) {
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock Alerts',
                            text: alertMessage,
                            showConfirmButton: true,
                            confirmButtonText: 'View Inventory',
                            showCancelButton: true,
                            cancelButtonText: 'Dismiss'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Highlight low stock items
                                document.getElementById('statusFilter').value = 'lowstock';
                                filterInventory();
                            }
                        });
                    }, 1000);
                }
            }
        };
    </script>
</body>
=======
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
function generateMedicineCode($conn) {
    $prefix = 'MED';
    $year = date('Y');
    
    // Get the last medicine code for this year
    $stmt = $conn->prepare("SELECT medicine_code FROM medicines WHERE medicine_code LIKE ? ORDER BY medicine_id DESC LIMIT 1");
    $like_pattern = $prefix . '-' . $year . '-%';
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_code = $result->fetch_assoc()['medicine_code'];
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

function getSupplierName($conn, $supplier_id) {
    if (!$supplier_id) return null;
    
    $stmt = $conn->prepare("SELECT company_name FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();
    
    return $supplier['company_name'] ?? null;
}

function getCategoryName($conn, $category_id) {
    if (!$category_id) return null;
    
    $stmt = $conn->prepare("SELECT category_name FROM medicine_categories WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
    
    return $category['category_name'] ?? null;
}

function getStockStatus($current_stock, $min_stock, $expiry_date) {
    $today = new DateTime();
    $expiry = new DateTime($expiry_date);
    
    // Check if expired
    if ($expiry < $today) {
        return 'expired';
    }
    
    // Check stock levels
    if ($current_stock <= 0) {
        return 'outofstock';
    } elseif ($current_stock <= $min_stock) {
        return 'lowstock';
    } else {
        return 'instock';
    }
}

function getStatusText($status) {
    $status_map = [
        'instock' => 'In Stock',
        'lowstock' => 'Low Stock',
        'outofstock' => 'Out of Stock',
        'expired' => 'Expired'
    ];
    
    return $status_map[$status] ?? ucfirst($status);
}

function getDosageFormText($form) {
    $form_map = [
        'tablet' => 'Tablet',
        'capsule' => 'Capsule',
        'syrup' => 'Syrup',
        'injection' => 'Injection',
        'ointment' => 'Ointment',
        'cream' => 'Cream',
        'drops' => 'Drops',
        'inhaler' => 'Inhaler',
        'other' => 'Other'
    ];
    
    return $form_map[$form] ?? ucfirst($form);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'add_medicine':
                // Validate required fields
                $required = ['medicine_name', 'generic_name', 'category_id', 'batch_number', 'unit_price', 'selling_price', 'current_stock', 'expiry_date'];
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
                
                // Validate stock levels
                $current_stock = intval($_POST['current_stock']);
                $min_stock = intval($_POST['min_stock_level'] ?? 10);
                $max_stock = intval($_POST['max_stock_level'] ?? 100);
                
                if ($current_stock < 0) {
                    $response['message'] = "Current stock cannot be negative!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($min_stock < 0) {
                    $response['message'] = "Minimum stock level cannot be negative!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($max_stock < $min_stock) {
                    $response['message'] = "Maximum stock level must be greater than minimum stock level!";
                    echo json_encode($response);
                    exit;
                }
                
                // Validate prices
                $unit_price = floatval($_POST['unit_price']);
                $selling_price = floatval($_POST['selling_price']);
                
                if ($unit_price <= 0 || $selling_price <= 0) {
                    $response['message'] = "Prices must be greater than zero!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($selling_price < $unit_price) {
                    $response['message'] = "Selling price must be greater than or equal to unit price!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if batch number already exists
                $check_stmt = $conn->prepare("SELECT medicine_id FROM medicines WHERE batch_number = ?");
                $check_stmt->bind_param("s", $_POST['batch_number']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $response['message'] = "Batch number already exists!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
                
                // Generate medicine code
                $medicine_code = generateMedicineCode($conn);
                
                // Prepare parameters
                $category_id = intval($_POST['category_id']);
                $dosage_form = $_POST['dosage_form'] ?? 'tablet';
                $strength = $_POST['strength'] ?? null;
                $manufacturer = $_POST['manufacturer'] ?? null;
                $description = $_POST['description'] ?? null;
                $storage_instructions = $_POST['storage_instructions'] ?? null;
                $requires_prescription = $_POST['requires_prescription'] ?? 'no';
                $unit_of_measure = $_POST['unit_of_measure'] ?? 'unit';
                $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
                $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
                $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : $current_branch_id;
                $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
                $status = $_POST['status'] ?? 'active';
                
                // Calculate stock status
                $expiry_date = $_POST['expiry_date'];
                $stock_status = getStockStatus($current_stock, $min_stock, $expiry_date);
                
                // Insert medicine
                $stmt = $conn->prepare("INSERT INTO medicines (
                    medicine_code, medicine_name, generic_name, category_id, dosage_form, strength,
                    manufacturer, batch_number, description, storage_instructions, requires_prescription,
                    unit_of_measure, unit_price, selling_price, discount_percentage, min_stock_level,
                    max_stock_level, current_stock, status, stock_status, expiry_date, purchase_date,
                    supplier_id, branch_id, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("sssissssssssdddiiisssssii", 
                    $medicine_code,
                    $_POST['medicine_name'],
                    $_POST['generic_name'],
                    $category_id,
                    $dosage_form,
                    $strength,
                    $manufacturer,
                    $_POST['batch_number'],
                    $description,
                    $storage_instructions,
                    $requires_prescription,
                    $unit_of_measure,
                    $unit_price,
                    $selling_price,
                    $discount_percentage,
                    $min_stock,
                    $max_stock,
                    $current_stock,
                    $status,
                    $stock_status,
                    $expiry_date,
                    $purchase_date,
                    $supplier_id,
                    $branch_id,
                    $user_id
                );
                
                if ($stmt->execute()) {
                    $new_medicine_id = $stmt->insert_id;
                    
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'ADD_MEDICINE', 
                        "Added new medicine: {$_POST['medicine_name']} ({$medicine_code})", 
                        $branch_id, getBranchName($conn, $branch_id));
                    
                    $response['success'] = true;
                    $response['message'] = "Medicine added successfully!";
                    $response['medicine_id'] = $new_medicine_id;
                    $response['medicine_code'] = $medicine_code;
                } else {
                    $response['message'] = "Error adding medicine: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'edit_medicine':
                $medicine_id = intval($_POST['medicine_id']);
                
                if ($medicine_id <= 0) {
                    $response['message'] = "Invalid medicine ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if medicine exists
                $check_stmt = $conn->prepare("SELECT medicine_id, batch_number FROM medicines WHERE medicine_id = ?");
                $check_stmt->bind_param("i", $medicine_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "Medicine not found!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $medicine_data = $check_result->fetch_assoc();
                $check_stmt->close();
                
                // Check if batch number changed and if new batch number exists for another medicine
                if ($medicine_data['batch_number'] !== $_POST['batch_number']) {
                    $check_stmt = $conn->prepare("SELECT medicine_id FROM medicines WHERE batch_number = ? AND medicine_id != ?");
                    $check_stmt->bind_param("si", $_POST['batch_number'], $medicine_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $response['message'] = "Batch number already exists for another medicine!";
                        $check_stmt->close();
                        echo json_encode($response);
                        exit;
                    }
                    $check_stmt->close();
                }
                
                // Validate required fields - ADDED 'current_stock' to required fields
                $required = ['medicine_name', 'generic_name', 'category_id', 'batch_number', 'unit_price', 'selling_price', 'current_stock', 'expiry_date'];
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
                
                // Validate stock levels - GET FROM POST, NOT FROM DATABASE
                $current_stock = intval($_POST['current_stock']);
                $min_stock = intval($_POST['min_stock_level'] ?? 10);
                $max_stock = intval($_POST['max_stock_level'] ?? 100);
                
                if ($current_stock < 0) {
                    $response['message'] = "Current stock cannot be negative!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($min_stock < 0) {
                    $response['message'] = "Minimum stock level cannot be negative!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($max_stock < $min_stock) {
                    $response['message'] = "Maximum stock level must be greater than minimum stock level!";
                    echo json_encode($response);
                    exit;
                }
                
                // Validate prices
                $unit_price = floatval($_POST['unit_price']);
                $selling_price = floatval($_POST['selling_price']);
                
                if ($unit_price <= 0 || $selling_price <= 0) {
                    $response['message'] = "Prices must be greater than zero!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($selling_price < $unit_price) {
                    $response['message'] = "Selling price must be greater than or equal to unit price!";
                    echo json_encode($response);
                    exit;
                }
                
                // Prepare parameters
                $category_id = intval($_POST['category_id']);
                $dosage_form = $_POST['dosage_form'] ?? 'tablet';
                $strength = $_POST['strength'] ?? null;
                $manufacturer = $_POST['manufacturer'] ?? null;
                $description = $_POST['description'] ?? null;
                $storage_instructions = $_POST['storage_instructions'] ?? null;
                $requires_prescription = $_POST['requires_prescription'] ?? 'no';
                $unit_of_measure = $_POST['unit_of_measure'] ?? 'unit';
                $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
                $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
                $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : $current_branch_id;
                $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
                $status = $_POST['status'] ?? 'active';
                $expiry_date = $_POST['expiry_date'];
                
                // Calculate stock status using the NEW stock value from POST
                $stock_status = getStockStatus($current_stock, $min_stock, $expiry_date);
                
                // Update medicine - INCLUDING current_stock in UPDATE
                $stmt = $conn->prepare("UPDATE medicines SET 
                    medicine_name = ?, generic_name = ?, category_id = ?, dosage_form = ?, strength = ?,
                    manufacturer = ?, batch_number = ?, description = ?, storage_instructions = ?, requires_prescription = ?,
                    unit_of_measure = ?, unit_price = ?, selling_price = ?, discount_percentage = ?, min_stock_level = ?,
                    max_stock_level = ?, current_stock = ?, status = ?, stock_status = ?, expiry_date = ?, purchase_date = ?,
                    supplier_id = ?, branch_id = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE medicine_id = ?");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                // Parameter types breakdown:
                // s: medicine_name (string)
                // s: generic_name (string)
                // i: category_id (integer)
                // s: dosage_form (string)
                // s: strength (string)
                // s: manufacturer (string)
                // s: batch_number (string)
                // s: description (string)
                // s: storage_instructions (string)
                // s: requires_prescription (string)
                // s: unit_of_measure (string)
                // d: unit_price (double)
                // d: selling_price (double)
                // d: discount_percentage (double)
                // i: min_stock_level (integer)
                // i: max_stock_level (integer)
                // i: current_stock (integer) - ADDED THIS
                // s: status (string)
                // s: stock_status (string)
                // s: expiry_date (string)
                // s: purchase_date (string)
                // i: supplier_id (integer)
                // i: branch_id (integer)
                // i: medicine_id (integer)
                
                $stmt->bind_param("ssissssssssdddiiiissssii", 
                    $_POST['medicine_name'],
                    $_POST['generic_name'],
                    $category_id,
                    $dosage_form,
                    $strength,
                    $manufacturer,
                    $_POST['batch_number'],
                    $description,
                    $storage_instructions,
                    $requires_prescription,
                    $unit_of_measure,
                    $unit_price,
                    $selling_price,
                    $discount_percentage,
                    $min_stock,
                    $max_stock,
                    $current_stock,  // ADDED THIS - current stock from POST
                    $status,
                    $stock_status,
                    $expiry_date,
                    $purchase_date,
                    $supplier_id,
                    $branch_id,
                    $medicine_id
                );
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'EDIT_MEDICINE', 
                        "Updated medicine: {$_POST['medicine_name']}", 
                        $branch_id, getBranchName($conn, $branch_id));
                    
                    $response['success'] = true;
                    $response['message'] = "Medicine updated successfully!";
                } else {
                    $response['message'] = "Error updating medicine: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'delete_medicine':
                $medicine_id = intval($_POST['medicine_id']);
                
                if ($medicine_id <= 0) {
                    $response['message'] = "Invalid medicine ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Get medicine info for logging
                $medicine_stmt = $conn->prepare("SELECT medicine_name, medicine_code FROM medicines WHERE medicine_id = ?");
                $medicine_stmt->bind_param("i", $medicine_id);
                $medicine_stmt->execute();
                $medicine_result = $medicine_stmt->get_result();
                
                if ($medicine_result->num_rows === 0) {
                    $response['message'] = "Medicine not found!";
                    $medicine_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $medicine_data = $medicine_result->fetch_assoc();
                $medicine_stmt->close();
                
                // Check if medicine has stock
                $stock_check = $conn->prepare("SELECT current_stock FROM medicines WHERE medicine_id = ?");
                $stock_check->bind_param("i", $medicine_id);
                $stock_check->execute();
                $stock_result = $stock_check->get_result()->fetch_assoc();
                $stock_check->close();
                
                if ($stock_result['current_stock'] > 0) {
                    $response['message'] = "Cannot delete medicine with existing stock. Please adjust stock to zero first.";
                    echo json_encode($response);
                    exit;
                }
                
                // Delete medicine
                $stmt = $conn->prepare("DELETE FROM medicines WHERE medicine_id = ?");
                $stmt->bind_param("i", $medicine_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'DELETE_MEDICINE', 
                        "Deleted medicine: {$medicine_data['medicine_name']} ({$medicine_data['medicine_code']})");
                    
                    $response['success'] = true;
                    $response['message'] = "Medicine deleted successfully!";
                } else {
                    $response['message'] = "Error deleting medicine: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'restock_medicine':
                $medicine_id = intval($_POST['medicine_id']);
                $quantity = intval($_POST['quantity']);
                
                if ($medicine_id <= 0) {
                    $response['message'] = "Invalid medicine ID!";
                    echo json_encode($response);
                    exit;
                }
                
                if ($quantity <= 0) {
                    $response['message'] = "Quantity must be greater than zero!";
                    echo json_encode($response);
                    exit;
                }
                
                // Get medicine info
                $medicine_stmt = $conn->prepare("SELECT medicine_name, current_stock, min_stock_level FROM medicines WHERE medicine_id = ?");
                $medicine_stmt->bind_param("i", $medicine_id);
                $medicine_stmt->execute();
                $medicine_result = $medicine_stmt->get_result();
                
                if ($medicine_result->num_rows === 0) {
                    $response['message'] = "Medicine not found!";
                    $medicine_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $medicine_data = $medicine_result->fetch_assoc();
                $medicine_stmt->close();
                
                // Update medicine stock
                $new_stock = $medicine_data['current_stock'] + $quantity;
                $expiry_date = $_POST['expiry_date'] ?? null;
                
                // Calculate new stock status
                $stock_status = getStockStatus($new_stock, $medicine_data['min_stock_level'], $expiry_date);
                
                $stmt = $conn->prepare("UPDATE medicines SET current_stock = ?, stock_status = ?, updated_at = CURRENT_TIMESTAMP WHERE medicine_id = ?");
                $stmt->bind_param("isi", $new_stock, $stock_status, $medicine_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'RESTOCK_MEDICINE', 
                        "Restocked {$quantity} units of {$medicine_data['medicine_name']}", 
                        $current_branch_id, getBranchName($conn, $current_branch_id));
                    
                    $response['success'] = true;
                    $response['message'] = "Medicine restocked successfully!";
                    $response['new_stock'] = $new_stock;
                    $response['stock_status'] = $stock_status;
                } else {
                    $response['message'] = "Error restocking medicine: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'get_medicine':
                $medicine_id = intval($_POST['medicine_id']);
                
                if ($medicine_id <= 0) {
                    $response['message'] = "Invalid medicine ID!";
                    echo json_encode($response);
                    exit;
                }
                
                $stmt = $conn->prepare("
                    SELECT m.*, 
                    c.category_name,
                    s.company_name as supplier_name,
                    b.branch_name
                    FROM medicines m
                    LEFT JOIN medicine_categories c ON m.category_id = c.category_id
                    LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
                    LEFT JOIN branches b ON m.branch_id = b.branch_id
                    WHERE m.medicine_id = ?
                ");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("i", $medicine_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $medicine = $result->fetch_assoc();
                    
                    // Format text values
                    $medicine['stock_status_text'] = getStatusText($medicine['stock_status']);
                    $medicine['dosage_form_text'] = getDosageFormText($medicine['dosage_form']);
                    $medicine['requires_prescription_text'] = $medicine['requires_prescription'] === 'yes' ? 'Yes' : 'No';
                    
                    // Calculate days to expiry
                    $today = new DateTime();
                    $expiry_date = new DateTime($medicine['expiry_date']);
                    $medicine['days_to_expiry'] = $expiry_date->diff($today)->days;
                    $medicine['is_expired'] = $expiry_date < $today;
                    $medicine['is_expiring_soon'] = !$medicine['is_expired'] && $medicine['days_to_expiry'] <= 30;
                    
                    // Calculate stock percentage
                    $medicine['stock_percentage'] = $medicine['max_stock_level'] > 0 
                        ? round(($medicine['current_stock'] / $medicine['max_stock_level']) * 100, 1)
                        : 0;
                    
                    $response['success'] = true;
                    $response['data'] = $medicine;
                } else {
                    $response['message'] = "Medicine not found!";
                }
                $stmt->close();
                break;
                
            case 'get_medicines':
                // Build query based on filters
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Apply filters
                if (!empty($_POST['search'])) {
                    $where_clauses[] = "(m.medicine_name LIKE ? OR m.generic_name LIKE ? OR m.medicine_code LIKE ? OR m.batch_number LIKE ?)";
                    $search_term = "%" . trim($_POST['search']) . "%";
                    for ($i = 0; $i < 4; $i++) {
                        $params[] = $search_term;
                        $types .= "s";
                    }
                }
                
                if (!empty($_POST['category'])) {
                    $where_clauses[] = "m.category_id = ?";
                    $params[] = intval($_POST['category']);
                    $types .= "i";
                }
                
                if (!empty($_POST['status'])) {
                    $where_clauses[] = "m.stock_status = ?";
                    $params[] = $_POST['status'];
                    $types .= "s";
                }
                
                if (!empty($_POST['supplier'])) {
                    $where_clauses[] = "m.supplier_id = ?";
                    $params[] = intval($_POST['supplier']);
                    $types .= "i";
                }
                
                // Filter by branch if user is not admin
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "m.branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                } elseif (!empty($_POST['branch'])) {
                    $where_clauses[] = "m.branch_id = ?";
                    $params[] = intval($_POST['branch']);
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get total count
                $count_sql = "SELECT COUNT(*) as total FROM medicines m WHERE $where_sql";
                $count_stmt = $conn->prepare($count_sql);
                if (!empty($params)) {
                    $count_stmt->bind_param($types, ...$params);
                }
                $count_stmt->execute();
                $count_result = $count_stmt->get_result()->fetch_assoc();
                $total_medicines = $count_result['total'] ?? 0;
                $count_stmt->close();
                
                // Get sorting
                $sort_options = [
                    'name_asc' => 'm.medicine_name ASC',
                    'name_desc' => 'm.medicine_name DESC',
                    'stock_low' => 'm.current_stock ASC',
                    'stock_high' => 'm.current_stock DESC',
                    'price_low' => 'm.selling_price ASC',
                    'price_high' => 'm.selling_price DESC',
                    'expiry_soon' => 'm.expiry_date ASC',
                    'expiry_late' => 'm.expiry_date DESC',
                    'recent' => 'm.created_at DESC'
                ];
                
                $sort_by = $_POST['sort'] ?? 'name_asc';
                $sort_sql = $sort_options[$sort_by] ?? 'm.medicine_name ASC';
                
                // Pagination
                $page = max(1, intval($_POST['page'] ?? 1));
                $limit = max(1, intval($_POST['limit'] ?? 10));
                $offset = ($page - 1) * $limit;
                
                $sql = "SELECT m.*, 
                       c.category_name,
                       s.company_name as supplier_name,
                       b.branch_name
                       FROM medicines m
                       LEFT JOIN medicine_categories c ON m.category_id = c.category_id
                       LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
                       LEFT JOIN branches b ON m.branch_id = b.branch_id
                       WHERE $where_sql 
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
                
                $medicines = [];
                $sn = $offset + 1;
                while ($row = $result->fetch_assoc()) {
                    $row['sn'] = $sn++;
                    
                    // Format text values
                    $row['stock_status_text'] = getStatusText($row['stock_status']);
                    $row['dosage_form_text'] = getDosageFormText($row['dosage_form']);
                    
                    // Calculate stock percentage
                    $row['stock_percentage'] = $row['max_stock_level'] > 0 
                        ? round(($row['current_stock'] / $row['max_stock_level']) * 100, 1)
                        : 0;
                    
                    // Determine stock level
                    if ($row['stock_percentage'] <= 30) {
                        $row['stock_level'] = 'low';
                    } elseif ($row['stock_percentage'] <= 60) {
                        $row['stock_level'] = 'medium';
                    } else {
                        $row['stock_level'] = 'high';
                    }
                    
                    // Format dates
                    $row['expiry_date_formatted'] = date('M d, Y', strtotime($row['expiry_date']));
                    $row['purchase_date_formatted'] = $row['purchase_date'] ? date('M d, Y', strtotime($row['purchase_date'])) : 'Not set';
                    
                    // Calculate days to expiry
                    $today = new DateTime();
                    $expiry_date = new DateTime($row['expiry_date']);
                    $days_to_expiry = $expiry_date->diff($today)->days;
                    
                    if ($expiry_date < $today) {
                        $row['expiry_status'] = 'expired';
                        $row['expiry_text'] = 'Expired';
                    } elseif ($days_to_expiry <= 30) {
                        $row['expiry_status'] = 'expiring_soon';
                        $row['expiry_text'] = $days_to_expiry . ' days';
                    } else {
                        $row['expiry_status'] = 'good';
                        $row['expiry_text'] = 'Good';
                    }
                    
                    $medicines[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $medicines;
                $response['total'] = $total_medicines;
                $response['page'] = $page;
                $response['pages'] = ceil($total_medicines / $limit);
                
                $stmt->close();
                break;
                
            case 'get_inventory_stats':
                // Build where clause
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Filter by branch if user is not admin
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get overall statistics
                $stats_sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN stock_status = 'instock' THEN 1 ELSE 0 END) as in_stock_items,
                    SUM(CASE WHEN stock_status = 'lowstock' THEN 1 ELSE 0 END) as low_stock_items,
                    SUM(CASE WHEN stock_status = 'outofstock' THEN 1 ELSE 0 END) as out_of_stock_items,
                    SUM(CASE WHEN stock_status = 'expired' THEN 1 ELSE 0 END) as expired_items,
                    SUM(current_stock * selling_price) as total_inventory_value
                    FROM medicines
                    WHERE $where_sql";
                
                $stmt = $conn->prepare($stats_sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $stats_result = $stmt->fetch_assoc();
                $stmt->close();
                
                // Get expiring soon count (within 30 days)
                $today = date('Y-m-d');
                $next_month = date('Y-m-d', strtotime('+30 days'));
                $expiring_sql = "SELECT COUNT(*) as expiring_soon 
                               FROM medicines 
                               WHERE expiry_date BETWEEN ? AND ? 
                               AND current_stock > 0 
                               AND $where_sql";
                
                $exp_stmt = $conn->prepare($expiring_sql);
                if (!empty($params)) {
                    $all_params = array_merge([$today, $next_month], $params);
                    $all_types = "ss" . $types;
                    $exp_stmt->bind_param($all_types, ...$all_params);
                } else {
                    $exp_stmt->bind_param("ss", $today, $next_month);
                }
                $exp_stmt->execute();
                $expiring_result = $exp_stmt->get_result()->fetch_assoc();
                $exp_stmt->close();
                
                $response['success'] = true;
                $response['stats'] = [
                    'total_items' => $stats_result['total_items'] ?? 0,
                    'in_stock_items' => $stats_result['in_stock_items'] ?? 0,
                    'low_stock_items' => $stats_result['low_stock_items'] ?? 0,
                    'out_of_stock_items' => $stats_result['out_of_stock_items'] ?? 0,
                    'expired_items' => $stats_result['expired_items'] ?? 0,
                    'expiring_soon' => $expiring_result['expiring_soon'] ?? 0,
                    'total_inventory_value' => $stats_result['total_inventory_value'] ?? 0
                ];
                break;
                
            case 'get_categories':
                $stmt = $conn->prepare("SELECT category_id, category_name FROM medicine_categories WHERE status = 'active' ORDER BY category_name");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $categories = [];
                while ($row = $result->fetch_assoc()) {
                    $categories[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $categories;
                $stmt->close();
                break;
                
            case 'get_suppliers':
                $stmt = $conn->prepare("SELECT supplier_id, company_name FROM suppliers WHERE status = 'active' ORDER BY company_name");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $suppliers = [];
                while ($row = $result->fetch_assoc()) {
                    $suppliers[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $suppliers;
                $stmt->close();
                break;
                
            default:
                $response['message'] = "Invalid action!";
        }
    } catch (Exception $e) {
        $response['message'] = "System Error: " . $e->getMessage();
        error_log("Inventory API Error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Get initial data for page load
$initial_stats = [
    'total_items' => 0,
    'in_stock_items' => 0,
    'low_stock_items' => 0,
    'out_of_stock_items' => 0,
    'expired_items' => 0,
    'expiring_soon' => 0,
    'total_inventory_value' => 0
];

// Get statistics
try {
    $stats_sql = "SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN stock_status = 'instock' THEN 1 ELSE 0 END) as in_stock_items,
        SUM(CASE WHEN stock_status = 'lowstock' THEN 1 ELSE 0 END) as low_stock_items,
        SUM(CASE WHEN stock_status = 'outofstock' THEN 1 ELSE 0 END) as out_of_stock_items,
        SUM(CASE WHEN stock_status = 'expired' THEN 1 ELSE 0 END) as expired_items,
        SUM(current_stock * selling_price) as total_inventory_value
        FROM medicines";
    
    if ($user_role !== 'admin' && $current_branch_id) {
        $stats_sql .= " WHERE branch_id = ?";
        $stmt = $conn->prepare($stats_sql);
        $stmt->bind_param("i", $current_branch_id);
        $stmt->execute();
        $stats_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $stats_result = $conn->query($stats_sql)->fetch_assoc();
    }
    
    if ($stats_result) {
        $initial_stats = array_merge($initial_stats, $stats_result);
    }
    
    // Get expiring soon count
    $today = date('Y-m-d');
    $next_month = date('Y-m-d', strtotime('+30 days'));
    $expiring_sql = "SELECT COUNT(*) as expiring_soon 
                     FROM medicines 
                     WHERE expiry_date BETWEEN ? AND ? 
                     AND current_stock > 0";
    
    if ($user_role !== 'admin' && $current_branch_id) {
        $expiring_sql .= " AND branch_id = ?";
        $stmt = $conn->prepare($expiring_sql);
        $stmt->bind_param("ssi", $today, $next_month, $current_branch_id);
    } else {
        $stmt = $conn->prepare($expiring_sql);
        $stmt->bind_param("ss", $today, $next_month);
    }
    
    $stmt->execute();
    $expiring_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $initial_stats['expiring_soon'] = $expiring_result['expiring_soon'] ?? 0;
} catch (Exception $e) {
    error_log("Error loading initial stats: " . $e->getMessage());
}

// Get categories for filter
$categories = [];
try {
    $categories_result = $conn->query("SELECT category_id, category_name FROM medicine_categories WHERE status = 'active' ORDER BY category_name");
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
} catch (Exception $e) {
    error_log("Error loading categories: " . $e->getMessage());
}

// Get suppliers for filter
$suppliers = [];
try {
    $suppliers_result = $conn->query("SELECT supplier_id, company_name FROM suppliers WHERE status = 'active' ORDER BY company_name");
    while ($row = $suppliers_result->fetch_assoc()) {
        $suppliers[] = $row;
    }
} catch (Exception $e) {
    error_log("Error loading suppliers: " . $e->getMessage());
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

// Get initial medicines list
$initial_medicines = [];
try {
    $medicines_sql = "SELECT m.*, c.category_name, s.company_name as supplier_name, b.branch_name 
                      FROM medicines m
                      LEFT JOIN medicine_categories c ON m.category_id = c.category_id
                      LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
                      LEFT JOIN branches b ON m.branch_id = b.branch_id
                      WHERE 1=1";
    
    if ($user_role !== 'admin' && $current_branch_id) {
        $medicines_sql .= " AND m.branch_id = ?";
        $stmt = $conn->prepare($medicines_sql);
        $stmt->bind_param("i", $current_branch_id);
    } else {
        $stmt = $conn->prepare($medicines_sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sn = 1;
    while ($row = $result->fetch_assoc()) {
        $row['sn'] = $sn++;
        $row['stock_status_text'] = getStatusText($row['stock_status']);
        $row['dosage_form_text'] = getDosageFormText($row['dosage_form']);
        
        // Calculate stock percentage
        $row['stock_percentage'] = $row['max_stock_level'] > 0 
            ? round(($row['current_stock'] / $row['max_stock_level']) * 100, 1)
            : 0;
        
        // Determine stock level
        if ($row['stock_percentage'] <= 30) {
            $row['stock_level'] = 'low';
        } elseif ($row['stock_percentage'] <= 60) {
            $row['stock_level'] = 'medium';
        } else {
            $row['stock_level'] = 'high';
        }
        
        $initial_medicines[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error loading initial medicines: " . $e->getMessage());
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Master Clinic</title>
    <!-- Include SweetAlert -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ================== Inventory Management Styles ============== */
        .inventory-management-section {
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
        .inventory-stats {
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
            border-color: var(--danger);
        }

        .stat-card:nth-child(5) {
            border-color: var(--success);
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
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .stat-card:nth-child(5) .stat-icon {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
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

        /* Inventory Table */
        .table-container {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--gray);
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

        .export-btn {
            padding: 8px 20px;
            background: var(--light-gray);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .export-btn:hover {
            background: #e0e0e0;
        }

        /* RESPONSIVE AND SCROLLABLE TABLE CONTAINER */
        .table-responsive-container {
            width: 100%;
            overflow-x: auto;
            position: relative;
        }

        .table-responsive-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive-container::-webkit-scrollbar-track {
            background: var(--light-gray);
            border-radius: 4px;
        }

        .table-responsive-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .table-responsive-container::-webkit-scrollbar-thumb:hover {
            background: var(--secondary);
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .inventory-table thead {
            background: var(--light-gray);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .inventory-table th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--black);
            font-size: 0.95rem;
            border-bottom: 2px solid var(--gray);
            white-space: nowrap;
        }

        .inventory-table tbody tr {
            border-bottom: 1px solid var(--gray);
            transition: background 0.3s ease;
        }

        .inventory-table tbody tr:hover {
            background: rgba(42, 92, 139, 0.05);
        }

        .inventory-table td {
            padding: 15px 20px;
            color: var(--black);
            font-size: 0.95rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* Medicine Image */
        .medicine-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--gray);
            flex-shrink: 0;
        }

        .medicine-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Status Badges */
        .medicine-status {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-instock {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .status-lowstock {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .status-outofstock {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .status-expired {
            background: rgba(149, 165, 166, 0.1);
            color: #7f8c8d;
        }

        /* Category Badges */
        .category-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .category-antibiotic {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .category-analgesic {
            background: rgba(41, 128, 185, 0.1);
            color: var(--primary);
        }

        .category-antihistamine {
            background: rgba(26, 188, 156, 0.1);
            color: var(--accent);
        }

        .category-antacid {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .category-vitamin {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .category-cough {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        /* Stock Level Indicator */
        .stock-level {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 150px;
        }

        .stock-bar {
            flex: 1;
            height: 6px;
            background: var(--light-gray);
            border-radius: 3px;
            overflow: hidden;
            min-width: 60px;
        }

        .stock-fill {
            height: 100%;
            border-radius: 3px;
        }

        .stock-fill.high {
            background: var(--success);
        }

        .stock-fill.medium {
            background: var(--warning);
        }

        .stock-fill.low {
            background: var(--danger);
        }

        /* Price Styling */
        .medicine-price {
            font-weight: 600;
            color: var(--primary);
            white-space: nowrap;
        }

        /* Supplier Info */
        .supplier-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 150px;
        }

        .supplier-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        /* Expiry Date */
        .expiry-date {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .expiry-icon {
            color: var(--warning);
            font-size: 1.1rem;
        }

        .expiry-near {
            color: var(--warning);
            font-weight: 600;
        }

        .expiry-passed {
            color: var(--danger);
            font-weight: 600;
        }

        /* Actions */
        .medicine-actions {
            display: flex;
            gap: 8px;
            min-width: 160px;
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
            flex-shrink: 0;
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

        .action-icon.restock {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .action-icon.restock:hover {
            background: var(--success);
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
            max-width: 800px;
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
            grid-template-columns: repeat(2, 1fr);
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

        /* Medicine Image Upload */
        .image-upload-section {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 10px;
        }

        .image-preview {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            border: 3px solid var(--gray);
            position: relative;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-controls {
            flex: 1;
        }

        .upload-btn {
            padding: 12px 25px;
            background: var(--light-gray);
            border: 2px dashed var(--primary);
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            color: var(--primary);
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .upload-btn:hover {
            background: var(--primary);
            color: white;
        }

        .upload-btn input[type="file"] {
            display: none;
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

        .confirmation-icon.warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .confirmation-icon.success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
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

        .medicine-details {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .medicine-image-large {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            overflow: hidden;
            border: 3px solid var(--gray);
        }

        .medicine-image-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .medicine-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .medicine-info h4 {
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

        /* Stock Alerts */
        .stock-alerts {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .alerts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .alerts-header h3 {
            margin: 0;
            color: var(--warning);
            font-size: 1.3rem;
        }

        .alert-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            background: rgba(243, 156, 18, 0.05);
            margin-bottom: 10px;
            border-left: 4px solid var(--warning);
        }

        .alert-item.critical {
            background: rgba(231, 76, 60, 0.05);
            border-left-color: var(--danger);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .alert-item .alert-icon {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .alert-item.critical .alert-icon {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .alert-content {
            flex: 1;
        }

        .alert-content h5 {
            margin: 0 0 5px 0;
            font-size: 0.95rem;
        }

        .alert-content p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--dark-gray);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .modal-form {
                grid-template-columns: 1fr;
            }
            
            .inventory-table {
                min-width: 1000px;
            }
        }

        @media (max-width: 768px) {
            .inventory-management-section {
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

            .inventory-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .image-upload-section {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .modal-content {
                width: 95%;
                margin: 10px;
            }

            .medicine-actions {
                flex-direction: column;
                gap: 5px;
            }

            .action-icon {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }

            .medicine-details {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .pagination {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .pagination-controls {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 10px;
            }
        }

        @media (max-width: 480px) {
            .inventory-stats {
                grid-template-columns: 1fr;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .action-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }

            .modal-body {
                padding: 20px;
            }
            
            .inventory-table th,
            .inventory-table td {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            
            .medicine-actions {
                min-width: 140px;
            }
        }
    </style>
</head>
<?php include 'includes/header.php'; ?>
<body>
    <!-- =============== Navigation ================ -->
    <div class="container">
        <!-- Overlay for closing sidebar on mobile -->
        <div class="overlay" id="overlay"></div>
        
        <?php include 'includes/sidebar.php'; ?>

        <!-- ========================= Main ==================== -->
        <div class="main">
            <?php include 'includes/navigation.php'; ?>

            <!-- ================== Inventory Management Content ============== -->
            <div class="inventory-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Inventory Management</h1>
                        <p>Manage pharmacy stock, monitor levels, and track expiry dates</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshInventory()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <button class="action-btn primary" onclick="openAddMedicineModal()" id="addMedicineBtn">
                            <ion-icon name="add-circle-outline"></ion-icon>
                            Add New Medicine
                        </button>
                    </div>
                </div>

                <!-- Inventory Statistics -->
                <div class="inventory-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="medical-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalItems"><?php echo $initial_stats['total_items']; ?></h3>
                            <p>Total Items</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="inStockItems"><?php echo $initial_stats['in_stock_items']; ?></h3>
                            <p>In Stock</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="warning-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="lowStockItems"><?php echo $initial_stats['low_stock_items']; ?></h3>
                            <p>Low Stock</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="close-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="outOfStockItems"><?php echo $initial_stats['out_of_stock_items']; ?></h3>
                            <p>Out of Stock</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="expiringItems"><?php echo $initial_stats['expiring_soon']; ?></h3>
                            <p>Expiring Soon</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search by medicine name, generic name, or batch number..." 
                               onkeyup="filterInventory()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Category</label>
                            <select id="categoryFilter" onchange="filterInventory()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Stock Status</label>
                            <select id="statusFilter" onchange="filterInventory()">
                                <option value="">All Status</option>
                                <option value="instock">In Stock</option>
                                <option value="lowstock">Low Stock</option>
                                <option value="outofstock">Out of Stock</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Supplier</label>
                            <select id="supplierFilter" onchange="filterInventory()">
                                <option value="">All Suppliers</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>">
                                        <?php echo htmlspecialchars($supplier['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterInventory()">
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="stock_low">Stock (Low to High)</option>
                                <option value="stock_high">Stock (High to Low)</option>
                                <option value="price_low">Price (Low to High)</option>
                                <option value="price_high">Price (High to Low)</option>
                                <option value="expiry_soon">Expiry (Soonest)</option>
                                <option value="expiry_late">Expiry (Latest)</option>
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

                <!-- Inventory Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Medicine Inventory</h2>
                        <div class="table-actions">
                            <!-- Table actions can be added here if needed -->
                        </div>
                    </div>
                    
                    <!-- Responsive and Scrollable Table Container -->
                    <div class="table-responsive-container">
                        <table class="inventory-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>MEDICINE DETAILS</th>
                                    <th>CATEGORY</th>
                                    <th>STOCK LEVEL</th>
                                    <th>PRICE (MWK)</th>
                                    <th>SUPPLIER</th>
                                    <th>EXPIRY DATE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                <?php if (empty($initial_medicines)): ?>
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <ion-icon name="medical-outline"></ion-icon>
                                        <h3>No medicines found</h3>
                                        <p>Add your first medicine to get started</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_medicines as $medicine): ?>
                                <?php 
                                    $statusClass = 'status-' . $medicine['stock_status'];
                                    $statusText = $medicine['stock_status_text'];
                                    $categoryName = strtolower(preg_replace('/[^a-z]/', '', $medicine['category_name']));
                                    $categoryClass = 'category-' . $categoryName;
                                ?>
                                <tr>
                                    <td><?php echo $medicine['sn']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <div class="medicine-image">
                                                <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="<?php echo htmlspecialchars($medicine['medicine_name']); ?>">
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($medicine['medicine_name']); ?></strong><br>
                                                <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($medicine['generic_name']); ?></small><br>
                                                <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($medicine['dosage_form_text']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="category-badge <?php echo $categoryClass; ?>"><?php echo htmlspecialchars($medicine['category_name']); ?></span></td>
                                    <td>
                                        <div class="stock-level">
                                            <span><?php echo $medicine['current_stock']; ?></span>
                                            <div class="stock-bar">
                                                <div class="stock-fill <?php echo $medicine['stock_level']; ?>" style="width: <?php echo $medicine['stock_percentage']; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="medicine-price">MWK <?php echo number_format($medicine['selling_price'], 2); ?></td>
                                    <td>
                                        <div class="supplier-info">
                                            <div class="supplier-avatar"><?php echo substr($medicine['supplier_name'] ?? 'N/A', 0, 1); ?></div>
                                            <span><?php echo htmlspecialchars($medicine['supplier_name'] ?? 'Not assigned'); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="expiry-date">
                                            <ion-icon name="calendar-outline" class="expiry-icon"></ion-icon>
                                            <?php echo date('M d, Y', strtotime($medicine['expiry_date'])); ?>
                                        </div>
                                    </td>
                                    <td><span class="medicine-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td>
                                        <div class="medicine-actions">
                                            <button class="action-icon view" title="View Details" onclick="viewMedicine(<?php echo $medicine['medicine_id']; ?>)">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon edit" title="Edit" onclick="editMedicine(<?php echo $medicine['medicine_id']; ?>)">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon delete" title="Delete" onclick="deleteMedicine(<?php echo $medicine['medicine_id']; ?>)">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon restock" title="Restock" onclick="restockMedicine(<?php echo $medicine['medicine_id']; ?>)">
                                                <ion-icon name="add-circle-outline"></ion-icon>
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
                            Showing <?php echo count($initial_medicines) > 0 ? 1 : 0; ?> to <?php echo count($initial_medicines); ?> of <?php echo $initial_stats['total_items']; ?> entries
                        </div>
                        <?php if ($initial_stats['total_items'] > 10): ?>
                        <div class="pagination-controls">
                            <button class="pagination-btn" onclick="changePage('prev')" disabled>
                                <ion-icon name="chevron-back-outline"></ion-icon>
                            </button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn" onclick="changePage('next')">
                                <ion-icon name="chevron-forward-outline"></ion-icon>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Medicine Modal -->
            <div class="modal-overlay" id="medicineModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add New Medicine</h3>
                        <button class="modal-close" onclick="closeMedicineModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="medicineForm" class="modal-form">
                            <input type="hidden" id="medicineId">
                            
                            <!-- Image Upload -->
                            <div class="image-upload-section">
                                <div class="image-preview">
                                    <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" 
                                         alt="Medicine Image" id="imagePreview">
                                </div>
                                <div class="upload-controls">
                                    <label class="upload-btn">
                                        <ion-icon name="cloud-upload-outline"></ion-icon>
                                        Upload Medicine Image
                                        <input type="file" id="imageUpload" accept="image/*" onchange="previewImage(this)">
                                    </label>
                                    <p style="margin-top: 10px; color: var(--dark-gray); font-size: 0.9rem;">
                                        Recommended: 400x400px, max 2MB. PNG or JPG format.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Basic Information -->
                            <div class="form-group">
                                <label for="medicineName" class="required">Medicine Name</label>
                                <input type="text" id="medicineName" placeholder="e.g., Panadol Extra" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="genericName" class="required">Generic Name</label>
                                <input type="text" id="genericName" placeholder="e.g., Paracetamol" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="categoryId" class="required">Category</label>
                                <select id="categoryId" required>
                                    <option value="">-- select --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="dosageForm">Dosage Form</label>
                                <select id="dosageForm">
                                    <option value="">-- select --</option>
                                    <option value="tablet">Tablet</option>
                                    <option value="capsule">Capsule</option>
                                    <option value="syrup">Syrup</option>
                                    <option value="injection">Injection</option>
                                    <option value="ointment">Ointment</option>
                                    <option value="cream">Cream</option>
                                    <option value="drops">Drops</option>
                                    <option value="inhaler">Inhaler</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="strength">Strength</label>
                                <input type="text" id="strength" placeholder="e.g., 500mg">
                            </div>
                            
                            <div class="form-group">
                                <label for="manufacturer">Manufacturer</label>
                                <input type="text" id="manufacturer" placeholder="e.g., GlaxoSmithKline">
                            </div>
                            
                            <!-- Stock Information -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Stock Information</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="batchNumber" class="required">Batch Number</label>
                                <input type="text" id="batchNumber" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="currentStock" class="required">Current Stock</label>
                                <input type="number" id="currentStock" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="minStockLevel">Minimum Stock Level</label>
                                <input type="number" id="minStockLevel" min="0" value="10">
                            </div>
                            
                            <div class="form-group">
                                <label for="maxStockLevel">Maximum Stock Level</label>
                                <input type="number" id="maxStockLevel" min="0" value="100">
                            </div>
                            
                            <!-- Pricing -->
                            <div class="form-group">
                                <label for="unitPrice" class="required">Unit Price (MWK)</label>
                                <input type="number" id="unitPrice" min="0" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="sellingPrice" class="required">Selling Price (MWK)</label>
                                <input type="number" id="sellingPrice" min="0" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="discountPercentage">Discount (%)</label>
                                <input type="number" id="discountPercentage" min="0" max="100" step="0.1" value="0">
                            </div>
                            
                            <!-- Supplier & Expiry -->
                            <div class="form-group">
                                <label for="supplierId">Supplier</label>
                                <select id="supplierId">
                                    <option value="">-- select --</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>">
                                            <?php echo htmlspecialchars($supplier['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="purchaseDate">Purchase Date</label>
                                <input type="date" id="purchaseDate">
                            </div>
                            
                            <div class="form-group">
                                <label for="expiryDate" class="required">Expiry Date</label>
                                <input type="date" id="expiryDate" required>
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="form-group full-width">
                                <label for="storageInstructions">Storage Instructions</label>
                                <textarea id="storageInstructions" placeholder="e.g., Store in a cool dry place below 25°C"></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description / Notes</label>
                                <textarea id="description" placeholder="Additional information about this medicine..."></textarea>
                            </div>
                            
                            <!-- Prescription Info -->
                            <div class="form-group">
                                <label for="requiresPrescription">Requires Prescription</label>
                                <select id="requiresPrescription">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="discontinued">Discontinued</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="unitOfMeasure">Unit of Measure</label>
                                <select id="unitOfMeasure">
                                    <option value="unit">Unit</option>
                                    <option value="tablet">Tablet</option>
                                    <option value="capsule">Capsule</option>
                                    <option value="bottle">Bottle</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack</option>
                                    <option value="tube">Tube</option>
                                    <option value="ml">ml</option>
                                    <option value="mg">mg</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeMedicineModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveMedicine()">
                            Save Medicine
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Medicine Details Modal -->
            <div class="modal-overlay view-details-modal" id="viewMedicineModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="viewModalTitle">Medicine Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="medicine-details">
                            <div class="medicine-image-large">
                                <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="Medicine" id="viewImage">
                            </div>
                            <div class="medicine-info">
                                <h4 id="viewMedicineName">Loading...</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Generic Name:</span>
                                        <span class="info-value" id="viewGenericName">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Category:</span>
                                        <span class="info-value" id="viewCategory">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Dosage Form:</span>
                                        <span class="info-value" id="viewDosageForm">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Strength:</span>
                                        <span class="info-value" id="viewStrength">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Manufacturer:</span>
                                        <span class="info-value" id="viewManufacturer">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Batch Number:</span>
                                        <span class="info-value" id="viewBatchNumber">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Current Stock:</span>
                                        <span class="info-value" id="viewCurrentStock">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Stock Status:</span>
                                        <span class="info-value" id="viewStockStatus">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Unit Price:</span>
                                        <span class="info-value" id="viewUnitPrice">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Selling Price:</span>
                                        <span class="info-value" id="viewSellingPrice">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Supplier:</span>
                                        <span class="info-value" id="viewSupplier">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Expiry Date:</span>
                                        <span class="info-value" id="viewExpiryDate">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Storage Instructions:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px;" id="viewStorageInstructions">
                                Loading...
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Description:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px;" id="viewDescription">
                                Loading...
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                        <button type="button" class="action-btn primary" onclick="editCurrentMedicine()">
                            <ion-icon name="create-outline"></ion-icon>
                            Edit Medicine
                        </button>
                        <button type="button" class="action-btn success" onclick="restockCurrentMedicine()">
                            <ion-icon name="add-circle-outline"></ion-icon>
                            Restock
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="deleteMedicineModal">
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
                        <h4>Delete Medicine</h4>
                        <p>Are you sure you want to delete <strong id="deleteMedicineName">[Medicine Name]</strong>? This action cannot be undone and all medicine data will be permanently removed.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="action-btn danger" onclick="confirmDelete()">
                                <ion-icon name="trash-outline"></ion-icon>
                                Delete Medicine
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restock Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="restockModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Restock Medicine</h3>
                        <button class="modal-close" onclick="closeRestockModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="restockForm">
                            <div class="form-group">
                                <label for="restockQuantity" class="required">Quantity to Add</label>
                                <input type="number" id="restockQuantity" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="restockSupplier">Supplier</label>
                                <select id="restockSupplier">
                                    <option value="">-- select --</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>">
                                            <?php echo htmlspecialchars($supplier['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="restockBatch">Batch Number</label>
                                <input type="text" id="restockBatch">
                            </div>
                            
                            <div class="form-group">
                                <label for="restockExpiry">Expiry Date</label>
                                <input type="date" id="restockExpiry">
                            </div>
                            
                            <div class="form-group">
                                <label for="restockPrice">Unit Price (MWK)</label>
                                <input type="number" id="restockPrice" min="0" step="0.01">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="restockNotes">Notes</label>
                                <textarea id="restockNotes" placeholder="Additional information about this restock..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeRestockModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn success" onclick="confirmRestock()">
                            <ion-icon name="checkmark-outline"></ion-icon>
                            Confirm Restock
                        </button>
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
        let filteredInventory = <?php echo json_encode($initial_medicines); ?>;
        let totalMedicines = <?php echo $initial_stats['total_items']; ?>;
        let medicineToDelete = null;
        let medicineToView = null;
        let medicineToRestock = null;

        // User role from PHP
        const userRole = '<?php echo $user_role; ?>';
        const currentBranchId = <?php echo $current_branch_id ?: 'null'; ?>;
        const isAdmin = userRole === 'admin';

        // DOM Elements
        const tableBody = document.getElementById('inventoryTableBody');
        const medicineModal = document.getElementById('medicineModal');
        const viewMedicineModal = document.getElementById('viewMedicineModal');
        const deleteMedicineModal = document.getElementById('deleteMedicineModal');
        const restockModal = document.getElementById('restockModal');

        // Initialize Page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Inventory Management System Initialized');
            
            // Set default dates
            const purchaseDateInput = document.getElementById('purchaseDate');
            const expiryDateInput = document.getElementById('expiryDate');
            if (purchaseDateInput) {
                const today = new Date().toISOString().split('T')[0];
                purchaseDateInput.value = today;
            }
            if (expiryDateInput) {
                const nextYear = new Date();
                nextYear.setFullYear(nextYear.getFullYear() + 1);
                expiryDateInput.value = nextYear.toISOString().split('T')[0];
            }
            
            // Add event listeners for modals
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.remove('active');
                    }
                });
            });
            
            console.log('Page initialization complete');
        });

        // Image Preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (!preview) return;
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            
            if (input.files && input.files[0]) {
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Modal Functions
        function openAddMedicineModal() {
            document.getElementById('modalTitle').textContent = 'Add New Medicine';
            document.getElementById('medicineForm').reset();
            document.getElementById('medicineId').value = '';
            
            // Set default values
            document.getElementById('minStockLevel').value = '10';
            document.getElementById('maxStockLevel').value = '100';
            document.getElementById('discountPercentage').value = '0';
            document.getElementById('requiresPrescription').value = 'no';
            document.getElementById('status').value = 'active';
            document.getElementById('unitOfMeasure').value = 'unit';
            
            // Set default dates
            const purchaseDateInput = document.getElementById('purchaseDate');
            const expiryDateInput = document.getElementById('expiryDate');
            if (purchaseDateInput) {
                const today = new Date().toISOString().split('T')[0];
                purchaseDateInput.value = today;
            }
            if (expiryDateInput) {
                const nextYear = new Date();
                nextYear.setFullYear(nextYear.getFullYear() + 1);
                expiryDateInput.value = nextYear.toISOString().split('T')[0];
            }
            
            // Reset image preview
            document.getElementById('imagePreview').src = 'https://cdn-icons-png.flaticon.com/512/3144/3144456.png';
            
            medicineModal.classList.add('active');
        }

        function closeMedicineModal() {
            medicineModal.classList.remove('active');
        }

        function closeViewModal() {
            viewMedicineModal.classList.remove('active');
        }

        function closeDeleteModal() {
            deleteMedicineModal.classList.remove('active');
            medicineToDelete = null;
        }

        function closeRestockModal() {
            restockModal.classList.remove('active');
            medicineToRestock = null;
        }

        // Load Medicines
        function loadMedicines() {
            const formData = new FormData();
            formData.append('action', 'get_medicines');
            formData.append('search', document.getElementById('searchInput').value);
            formData.append('category', document.getElementById('categoryFilter').value);
            formData.append('status', document.getElementById('statusFilter').value);
            formData.append('supplier', document.getElementById('supplierFilter').value);
            formData.append('sort', document.getElementById('sortFilter').value);
            formData.append('page', currentPage);
            formData.append('limit', itemsPerPage);

            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    filteredInventory = data.data;
                    totalMedicines = data.total;
                    renderInventoryTable();
                    updateStatistics();
                    updatePaginationInfo();
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicines');
            });
        }

        // Update Statistics
        function updateStatistics() {
            const formData = new FormData();
            formData.append('action', 'get_inventory_stats');

            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.stats;
                    document.getElementById('totalItems').textContent = stats.total_items;
                    document.getElementById('inStockItems').textContent = stats.in_stock_items;
                    document.getElementById('lowStockItems').textContent = stats.low_stock_items;
                    document.getElementById('outOfStockItems').textContent = stats.out_of_stock_items;
                    document.getElementById('expiringItems').textContent = stats.expiring_soon;
                }
            })
            .catch(error => {
                console.error('Error updating statistics:', error);
            });
        }

        // Render Inventory Table
        function renderInventoryTable() {
            if (!tableBody) return;
            
            if (filteredInventory.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <ion-icon name="medical-outline"></ion-icon>
                            <h3>No medicines found</h3>
                            <p>${isAdmin ? 'Try adjusting your search or filters' : 'No medicines in your branch.'}</p>
                        </td>
                    </tr>
                `;
                updatePaginationInfo();
                return;
            }

            let html = '';
            filteredInventory.forEach(medicine => {
                const statusClass = `status-${medicine.stock_status}`;
                const categoryName = medicine.category_name ? medicine.category_name.toLowerCase().replace(/[^a-z]/g, '') : 'other';
                const categoryClass = `category-${categoryName}`;
                
                // Determine stock fill class based on stock level
                let stockFillClass = medicine.stock_level || 'high';
                
                // Format price
                const sellingPrice = parseFloat(medicine.selling_price).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                // Determine expiry display
                let expiryDisplay = medicine.expiry_date_formatted;
                let expiryClass = '';
                if (medicine.expiry_status === 'expired') {
                    expiryClass = 'expiry-passed';
                    expiryDisplay = `<span class="${expiryClass}">${expiryDisplay}</span>`;
               } else if (medicine.expiry_status === 'expiring_soon') {
                    expiryClass = 'expiry-near';
                    expiryDisplay = `<span class="${expiryClass}">${expiryDisplay} (${medicine.expiry_text})</span>`;
                }

                html += `
                <tr>
                    <td>${medicine.sn}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="medicine-image">
                                <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="${medicine.medicine_name}">
                            </div>
                            <div>
                                <strong>${medicine.medicine_name}</strong><br>
                                <small style="color: var(--dark-gray);">${medicine.generic_name}</small><br>
                                <small style="color: var(--dark-gray);">${medicine.dosage_form_text}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="category-badge ${categoryClass}">${medicine.category_name || 'Uncategorized'}</span></td>
                    <td>
                        <div class="stock-level">
                            <span>${medicine.current_stock}</span>
                            <div class="stock-bar">
                                <div class="stock-fill ${stockFillClass}" style="width: ${medicine.stock_percentage}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="medicine-price">MWK ${sellingPrice}</td>
                    <td>
                        <div class="supplier-info">
                            <div class="supplier-avatar">${medicine.supplier_name ? medicine.supplier_name.charAt(0) : 'N'}</div>
                            <span>${medicine.supplier_name || 'Not assigned'}</span>
                        </div>
                    </td>
                    <td>
                        <div class="expiry-date">
                            <ion-icon name="calendar-outline" class="expiry-icon"></ion-icon>
                            ${expiryDisplay}
                        </div>
                    </td>
                    <td><span class="medicine-status ${statusClass}">${medicine.stock_status_text}</span></td>
                    <td>
                        <div class="medicine-actions">
                            <button class="action-icon view" title="View Details" onclick="viewMedicine(${medicine.medicine_id})">
                                <ion-icon name="eye-outline"></ion-icon>
                            </button>
                            <button class="action-icon edit" title="Edit" onclick="editMedicine(${medicine.medicine_id})">
                                <ion-icon name="create-outline"></ion-icon>
                            </button>
                            <button class="action-icon delete" title="Delete" onclick="deleteMedicine(${medicine.medicine_id})">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                            <button class="action-icon restock" title="Restock" onclick="restockMedicine(${medicine.medicine_id})">
                                <ion-icon name="add-circle-outline"></ion-icon>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            });

            tableBody.innerHTML = html;
            updatePaginationInfo();
        }

        // Update Pagination Info
        function updatePaginationInfo() {
            const start = (currentPage - 1) * itemsPerPage + 1;
            const end = Math.min(currentPage * itemsPerPage, totalMedicines);
            
            document.getElementById('paginationInfo').textContent = 
                `Showing ${start} to ${end} of ${totalMedicines} entries`;
            
            // Update pagination buttons
            const paginationControls = document.querySelector('.pagination-controls');
            if (paginationControls && totalMedicines > itemsPerPage) {
                const totalPages = Math.ceil(totalMedicines / itemsPerPage);
                let paginationHTML = `
                    <button class="pagination-btn" onclick="changePage('prev')" ${currentPage === 1 ? 'disabled' : ''}>
                        <ion-icon name="chevron-back-outline"></ion-icon>
                    </button>
                `;
                
                // Show page numbers
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                
                if (endPage - startPage + 1 < maxVisiblePages) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }
                
                if (startPage > 1) {
                    paginationHTML += `<button class="pagination-btn" onclick="changePage(1)">1</button>`;
                    if (startPage > 2) paginationHTML += `<span class="pagination-dots">...</span>`;
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `
                        <button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">
                            ${i}
                        </button>
                    `;
                }
                
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) paginationHTML += `<span class="pagination-dots">...</span>`;
                    paginationHTML += `<button class="pagination-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
                }
                
                paginationHTML += `
                    <button class="pagination-btn" onclick="changePage('next')" ${currentPage === totalPages ? 'disabled' : ''}>
                        <ion-icon name="chevron-forward-outline"></ion-icon>
                    </button>
                `;
                
                paginationControls.innerHTML = paginationHTML;
            }
        }

        // Change Page
        function changePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                currentPage--;
            } else if (direction === 'next' && currentPage < Math.ceil(totalMedicines / itemsPerPage)) {
                currentPage++;
            } else if (typeof direction === 'number') {
                currentPage = direction;
            }
            
            loadMedicines();
        }

        // Filter Inventory
        function filterInventory() {
            currentPage = 1;
            loadMedicines();
        }

        // Apply Filters
        function applyFilters() {
            filterInventory();
        }

        // Reset Filters
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('supplierFilter').value = '';
            document.getElementById('sortFilter').value = 'name_asc';
            currentPage = 1;
            
            loadMedicines();
        }

        // Refresh Inventory
        function refreshInventory() {
            Swal.fire({
                title: 'Refreshing...',
                text: 'Loading latest inventory data',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            currentPage = 1;
            loadMedicines();
            
            setTimeout(() => {
                Swal.close();
                showSuccess('Inventory refreshed successfully!');
            }, 1000);
        }

        // View Medicine Details
        function viewMedicine(medicineId) {
            const formData = new FormData();
            formData.append('action', 'get_medicine');
            formData.append('medicine_id', medicineId);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    medicineToView = data.data;
                    
                    // Populate view modal
                    document.getElementById('viewMedicineName').textContent = medicineToView.medicine_name;
                    document.getElementById('viewModalTitle').textContent = `Details: ${medicineToView.medicine_name}`;
                    document.getElementById('viewGenericName').textContent = medicineToView.generic_name || 'N/A';
                    document.getElementById('viewCategory').textContent = medicineToView.category_name || 'Uncategorized';
                    document.getElementById('viewDosageForm').textContent = medicineToView.dosage_form_text || 'N/A';
                    document.getElementById('viewStrength').textContent = medicineToView.strength || 'N/A';
                    document.getElementById('viewManufacturer').textContent = medicineToView.manufacturer || 'N/A';
                    document.getElementById('viewBatchNumber').textContent = medicineToView.batch_number;
                    document.getElementById('viewCurrentStock').textContent = medicineToView.current_stock;
                    document.getElementById('viewStockStatus').textContent = medicineToView.stock_status_text;
                    document.getElementById('viewUnitPrice').textContent = `MWK ${parseFloat(medicineToView.unit_price).toFixed(2)}`;
                    document.getElementById('viewSellingPrice').textContent = `MWK ${parseFloat(medicineToView.selling_price).toFixed(2)}`;
                    document.getElementById('viewSupplier').textContent = medicineToView.supplier_name || 'Not assigned';
                    document.getElementById('viewExpiryDate').textContent = new Date(medicineToView.expiry_date).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    document.getElementById('viewStorageInstructions').textContent = medicineToView.storage_instructions || 'No special instructions';
                    document.getElementById('viewDescription').textContent = medicineToView.description || 'No description available';
                    
                    // Show modal
                    viewMedicineModal.classList.add('active');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicine details');
            });
        }

        // Edit Medicine
        function editMedicine(medicineId) {
            const formData = new FormData();
            formData.append('action', 'get_medicine');
            formData.append('medicine_id', medicineId);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const medicine = data.data;
                    
                    // Populate form
                    document.getElementById('modalTitle').textContent = 'Edit Medicine';
                    document.getElementById('medicineId').value = medicine.medicine_id;
                    document.getElementById('medicineName').value = medicine.medicine_name;
                    document.getElementById('genericName').value = medicine.generic_name || '';
                    document.getElementById('categoryId').value = medicine.category_id;
                    document.getElementById('dosageForm').value = medicine.dosage_form;
                    document.getElementById('strength').value = medicine.strength || '';
                    document.getElementById('manufacturer').value = medicine.manufacturer || '';
                    document.getElementById('batchNumber').value = medicine.batch_number;
                    document.getElementById('currentStock').value = medicine.current_stock;
                    document.getElementById('minStockLevel').value = medicine.min_stock_level;
                    document.getElementById('maxStockLevel').value = medicine.max_stock_level;
                    document.getElementById('unitPrice').value = medicine.unit_price;
                    document.getElementById('sellingPrice').value = medicine.selling_price;
                    document.getElementById('discountPercentage').value = medicine.discount_percentage;
                    document.getElementById('supplierId').value = medicine.supplier_id || '';
                    document.getElementById('purchaseDate').value = medicine.purchase_date || '';
                    document.getElementById('expiryDate').value = medicine.expiry_date;
                    document.getElementById('storageInstructions').value = medicine.storage_instructions || '';
                    document.getElementById('description').value = medicine.description || '';
                    document.getElementById('requiresPrescription').value = medicine.requires_prescription;
                    document.getElementById('status').value = medicine.status;
                    document.getElementById('unitOfMeasure').value = medicine.unit_of_measure;
                    
                    // Show modal
                    medicineModal.classList.add('active');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicine for editing');
            });
        }

        // Edit Current Medicine (from view modal)
        function editCurrentMedicine() {
            if (medicineToView) {
                viewMedicineModal.classList.remove('active');
                setTimeout(() => {
                    editMedicine(medicineToView.medicine_id);
                }, 300);
            }
        }

        // Save Medicine (Add/Edit)
        function saveMedicine() {
            // Validate form
            const requiredFields = ['medicineName', 'genericName', 'categoryId', 'batchNumber', 'unitPrice', 'sellingPrice', 'currentStock', 'expiryDate'];
            let isValid = true;
            let errorMessage = '';
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field || !field.value.trim()) {
                    isValid = false;
                    errorMessage = `Please fill in all required fields`;
                    field.style.borderColor = 'var(--danger)';
                } else {
                    if (field.style) field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                showError(errorMessage);
                return;
            }
            
            // Validate prices
            const unitPrice = parseFloat(document.getElementById('unitPrice').value);
            const sellingPrice = parseFloat(document.getElementById('sellingPrice').value);
            
            if (unitPrice <= 0 || sellingPrice <= 0) {
                showError('Prices must be greater than zero');
                return;
            }
            
            if (sellingPrice < unitPrice) {
                showError('Selling price must be greater than or equal to unit price');
                return;
            }
            
            // Prepare form data
            const formData = new FormData();
            const medicineId = document.getElementById('medicineId').value;
            formData.append('action', medicineId ? 'edit_medicine' : 'add_medicine');
            
            if (medicineId) {
                formData.append('medicine_id', medicineId);
            }
            
            formData.append('medicine_name', document.getElementById('medicineName').value);
            formData.append('generic_name', document.getElementById('genericName').value);
            formData.append('category_id', document.getElementById('categoryId').value);
            formData.append('dosage_form', document.getElementById('dosageForm').value);
            formData.append('strength', document.getElementById('strength').value);
            formData.append('manufacturer', document.getElementById('manufacturer').value);
            formData.append('batch_number', document.getElementById('batchNumber').value);
            formData.append('current_stock', document.getElementById('currentStock').value);
            formData.append('min_stock_level', document.getElementById('minStockLevel').value);
            formData.append('max_stock_level', document.getElementById('maxStockLevel').value);
            formData.append('unit_price', document.getElementById('unitPrice').value);
            formData.append('selling_price', document.getElementById('sellingPrice').value);
            formData.append('discount_percentage', document.getElementById('discountPercentage').value);
            formData.append('supplier_id', document.getElementById('supplierId').value);
            formData.append('purchase_date', document.getElementById('purchaseDate').value);
            formData.append('expiry_date', document.getElementById('expiryDate').value);
            formData.append('storage_instructions', document.getElementById('storageInstructions').value);
            formData.append('description', document.getElementById('description').value);
            formData.append('requires_prescription', document.getElementById('requiresPrescription').value);
            formData.append('status', document.getElementById('status').value);
            formData.append('unit_of_measure', document.getElementById('unitOfMeasure').value);
            
            // Show loading
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send request
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success) {
                    showSuccess(data.message);
                    closeMedicineModal();
                    loadMedicines();
                    updateStatistics();
                    
                    if (medicineToView) {
                        viewMedicine(medicineToView.medicine_id);
                    }
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                showError('Failed to save medicine');
            });
        }

        // Delete Medicine
        function deleteMedicine(medicineId) {
            const formData = new FormData();
            formData.append('action', 'get_medicine');
            formData.append('medicine_id', medicineId);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    medicineToDelete = data.data;
                    document.getElementById('deleteMedicineName').textContent = medicineToDelete.medicine_name;
                    deleteMedicineModal.classList.add('active');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicine for deletion');
            });
        }

        // Confirm Delete
        function confirmDelete() {
            if (!medicineToDelete) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_medicine');
            formData.append('medicine_id', medicineToDelete.medicine_id);
            
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success) {
                    showSuccess(data.message);
                    closeDeleteModal();
                    loadMedicines();
                    updateStatistics();
                    
                    if (medicineToView && medicineToView.medicine_id === medicineToDelete.medicine_id) {
                        closeViewModal();
                    }
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                showError('Failed to delete medicine');
            });
        }

        // Restock Medicine
        function restockMedicine(medicineId) {
            const formData = new FormData();
            formData.append('action', 'get_medicine');
            formData.append('medicine_id', medicineId);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    medicineToRestock = data.data;
                    document.getElementById('restockForm').reset();
                    restockModal.classList.add('active');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load medicine for restocking');
            });
        }

        // Restock Current Medicine (from view modal)
        function restockCurrentMedicine() {
            if (medicineToView) {
                viewMedicineModal.classList.remove('active');
                setTimeout(() => {
                    restockMedicine(medicineToView.medicine_id);
                }, 300);
            }
        }

        // Confirm Restock
        function confirmRestock() {
            if (!medicineToRestock) return;
            
            const quantity = document.getElementById('restockQuantity').value;
            if (!quantity || quantity <= 0) {
                showError('Please enter a valid quantity');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'restock_medicine');
            formData.append('medicine_id', medicineToRestock.medicine_id);
            formData.append('quantity', quantity);
            formData.append('expiry_date', document.getElementById('restockExpiry').value);
            
            Swal.fire({
                title: 'Restocking...',
                text: 'Please wait',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success) {
                    showSuccess(data.message);
                    closeRestockModal();
                    loadMedicines();
                    updateStatistics();
                    
                    if (medicineToView) {
                        viewMedicine(medicineToView.medicine_id);
                    }
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                showError('Failed to restock medicine');
            });
        }

        // Utility Functions
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message,
                timer: 3000,
                showConfirmButton: true
            });
        }

        // Export Inventory
        function exportInventory(format) {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            const supplier = document.getElementById('supplierFilter').value;
            
            let url = `export_inventory.php?format=${format}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (category) url += `&category=${category}`;
            if (status) url += `&status=${status}`;
            if (supplier) url += `&supplier=${supplier}`;
            if (!isAdmin && currentBranchId) url += `&branch_id=${currentBranchId}`;
            
            window.open(url, '_blank');
        }

        // Handle Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + F for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            
            // Ctrl + N for new medicine
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                if (!medicineModal.classList.contains('active')) {
                    openAddMedicineModal();
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                if (medicineModal.classList.contains('active')) {
                    closeMedicineModal();
                } else if (viewMedicineModal.classList.contains('active')) {
                    closeViewModal();
                } else if (deleteMedicineModal.classList.contains('active')) {
                    closeDeleteModal();
                } else if (restockModal.classList.contains('active')) {
                    closeRestockModal();
                }
            }
        });

        // Real-time Search
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterInventory();
            }, 500);
        });

        // Initialize Auto-generated Code
        document.getElementById('medicineName').addEventListener('blur', function() {
            if (this.value && !document.getElementById('genericName').value) {
                document.getElementById('genericName').value = this.value;
            }
        });

        // Auto-calculate selling price with margin
        document.getElementById('unitPrice').addEventListener('input', function() {
            const unitPrice = parseFloat(this.value) || 0;
            const sellingPriceInput = document.getElementById('sellingPrice');
            const currentSellingPrice = parseFloat(sellingPriceInput.value) || 0;
            
            // Only auto-calculate if selling price is empty or less than unit price
            if (unitPrice > 0 && (currentSellingPrice === 0 || currentSellingPrice < unitPrice)) {
                // Add 20% margin
                const sellingPrice = unitPrice * 1.2;
                sellingPriceInput.value = sellingPrice.toFixed(2);
            }
        });

        // Auto-set expiry date (1 year from purchase date)
        document.getElementById('purchaseDate').addEventListener('change', function() {
            if (this.value) {
                const purchaseDate = new Date(this.value);
                const expiryDate = new Date(purchaseDate);
                expiryDate.setFullYear(expiryDate.getFullYear() + 1);
                
                const expiryDateInput = document.getElementById('expiryDate');
                const currentExpiryDate = expiryDateInput.value;
                
                // Only set if not already set or if set to a date before purchase date
                if (!currentExpiryDate || new Date(currentExpiryDate) <= purchaseDate) {
                    expiryDateInput.value = expiryDate.toISOString().split('T')[0];
                }
            }
        });

        // Check expiry date validity
        document.getElementById('expiryDate').addEventListener('change', function() {
            if (this.value) {
                const expiryDate = new Date(this.value);
                const purchaseDateInput = document.getElementById('purchaseDate');
                const purchaseDate = purchaseDateInput.value ? new Date(purchaseDateInput.value) : new Date();
                
                if (expiryDate <= purchaseDate) {
                    showError('Expiry date must be after purchase date');
                    this.value = '';
                    this.focus();
                }
                
                // Check if expiry is within 30 days (warning)
                const today = new Date();
                const daysToExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
                
                if (daysToExpiry <= 30 && daysToExpiry > 0) {
                    showWarning(`Warning: Medicine will expire in ${daysToExpiry} days`);
                } else if (expiryDate < today) {
                    showError('Warning: Medicine is already expired');
                }
            }
        });

        function showWarning(message) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: message,
                timer: 3000,
                showConfirmButton: true
            });
        }

        // Stock level validation
        document.getElementById('currentStock').addEventListener('input', function() {
            const currentStock = parseInt(this.value) || 0;
            const minStock = parseInt(document.getElementById('minStockLevel').value) || 0;
            const maxStock = parseInt(document.getElementById('maxStockLevel').value) || 0;
            
            if (maxStock > 0 && currentStock > maxStock) {
                showWarning(`Current stock (${currentStock}) exceeds maximum stock level (${maxStock})`);
            }
            
            if (minStock > 0 && currentStock < minStock) {
                const needed = minStock - currentStock;
                showWarning(`Low stock alert! Need ${needed} more units to reach minimum stock level`);
            }
        });

        // Initialize tooltips for action buttons
        document.addEventListener('mouseover', function(e) {
            if (e.target.closest('.action-icon')) {
                const button = e.target.closest('.action-icon');
                const title = button.getAttribute('title');
                if (title) {
                    // You can implement custom tooltip here if needed
                }
            }
        });

        // Auto-refresh inventory every 5 minutes
        setInterval(() => {
            // Only refresh if no modals are open and user is active
            const modalsOpen = document.querySelectorAll('.modal-overlay.active').length > 0;
            if (!modalsOpen) {
                loadMedicines();
                updateStatistics();
                console.log('Auto-refreshed inventory data');
            }
        }, 300000); // 5 minutes

        // Initialize on page load
        window.onload = function() {
            console.log('Inventory Management System Ready');
            
            // Check for low stock items and show alert
            const lowStockItems = parseInt(document.getElementById('lowStockItems').textContent) || 0;
            const outOfStockItems = parseInt(document.getElementById('outOfStockItems').textContent) || 0;
            const expiringItems = parseInt(document.getElementById('expiringItems').textContent) || 0;
            
            if (lowStockItems > 0 || outOfStockItems > 0 || expiringItems > 0) {
                let alertMessage = '';
                if (outOfStockItems > 0) {
                    alertMessage += `${outOfStockItems} item(s) are out of stock. `;
                }
                if (lowStockItems > 0) {
                    alertMessage += `${lowStockItems} item(s) are low on stock. `;
                }
                if (expiringItems > 0) {
                    alertMessage += `${expiringItems} item(s) are expiring soon.`;
                }
                
                if (alertMessage.trim()) {
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock Alerts',
                            text: alertMessage,
                            showConfirmButton: true,
                            confirmButtonText: 'View Inventory',
                            showCancelButton: true,
                            cancelButtonText: 'Dismiss'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Highlight low stock items
                                document.getElementById('statusFilter').value = 'lowstock';
                                filterInventory();
                            }
                        });
                    }, 1000);
                }
            }
        };
    </script>
</body>
>>>>>>> ebf5f55ccd0a1b48a75b40abdbae6c5de9fe43f4
</html>