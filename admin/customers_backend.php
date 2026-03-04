<?php
// customers_backend.php - Backend API for Customers Management

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
function generateCustomerCode($conn) {
    $prefix = 'CUST';
    $year = date('Y');
    
    // Get the last customer code for this year
    $stmt = $conn->prepare("SELECT customer_code FROM customers WHERE customer_code LIKE ? ORDER BY customer_id DESC LIMIT 1");
    $like_pattern = $prefix . '-' . $year . '-%';
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_code = $result->fetch_assoc()['customer_code'];
        $parts = explode('-', $last_code);
        $last_number = intval(end($parts));
        $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_number = '0001';
    }
    
    $stmt->close();
    return $prefix . '-' . $year . '-' . $new_number;
}

function getCustomerAge($date_of_birth) {
    if (!$date_of_birth) return null;
    
    $birth_date = new DateTime($date_of_birth);
    $today = new DateTime();
    $age = $today->diff($birth_date)->y;
    
    return $age;
}

function formatPhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If it starts with 265 (Malawi country code), format it
    if (substr($phone, 0, 3) === '265') {
        return '+265 ' . substr($phone, 3, 2) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8);
    }
    
    // If it's 10 digits (Malawi local format), add +265
    if (strlen($phone) === 10 && substr($phone, 0, 2) === '08') {
        return '+265 ' . substr($phone, 1, 2) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    }
    
    return $phone;
}

function validateMalawiPhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Malawi phone number
    // Formats: 088XXXXXXX, +26588XXXXXXX, 26588XXXXXXX
    if (preg_match('/^(265|0)?(88|99|98|97)\d{7}$/', $phone)) {
        return true;
    }
    
    return false;
}

function getStatusText($status) {
    $status_map = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
        'suspended' => 'Suspended'
    ];
    
    return $status_map[$status] ?? ucfirst($status);
}

function getCustomerTypeText($type) {
    $type_map = [
        'regular' => 'Regular',
        'premium' => 'Premium',
        'wholesale' => 'Wholesale',
        'corporate' => 'Corporate',
        'government' => 'Government'
    ];
    
    return $type_map[$type] ?? ucfirst($type);
}

function getBloodGroupText($blood_group) {
    $blood_group_map = [
        'a+' => 'A+',
        'a-' => 'A-',
        'b+' => 'B+',
        'b-' => 'B-',
        'ab+' => 'AB+',
        'ab-' => 'AB-',
        'o+' => 'O+',
        'o-' => 'O-',
        'unknown' => 'Unknown'
    ];
    
    return $blood_group_map[$blood_group] ?? 'Unknown';
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'add_customer':
                // Validate required fields
                $required = ['first_name', 'last_name', 'phone', 'email', 'address', 'city', 'customer_type'];
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
                
                // Validate phone number
                $phone = trim($_POST['phone']);
                if (!validateMalawiPhone($phone)) {
                    $response['message'] = "Please enter a valid Malawi phone number (format: 088XXXXXXX or +26588XXXXXXX)";
                    echo json_encode($response);
                    exit;
                }
                
                // Validate email
                $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $response['message'] = "Please enter a valid email address";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if email already exists
                $check_email = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                if ($check_email->get_result()->num_rows > 0) {
                    $response['message'] = "Email address already exists in the system";
                    $check_email->close();
                    echo json_encode($response);
                    exit;
                }
                $check_email->close();
                
                // Check if phone already exists
                $check_phone = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ?");
                $check_phone->bind_param("s", $phone);
                $check_phone->execute();
                if ($check_phone->get_result()->num_rows > 0) {
                    $response['message'] = "Phone number already exists in the system";
                    $check_phone->close();
                    echo json_encode($response);
                    exit;
                }
                $check_phone->close();
                
                // Validate date of birth if provided
                $date_of_birth = $_POST['date_of_birth'] ?? null;
                if ($date_of_birth) {
                    $dob = new DateTime($date_of_birth);
                    $today = new DateTime();
                    if ($dob > $today) {
                        $response['message'] = "Date of birth cannot be in the future";
                        echo json_encode($response);
                        exit;
                    }
                }
                
                // Generate customer code
                $customer_code = generateCustomerCode($conn);
                
                // Prepare parameters
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $gender = $_POST['gender'] ?? 'other';
                $national_id = $_POST['national_id'] ?? null;
                $alternate_phone = $_POST['alternate_phone'] ?? null;
                $address = trim($_POST['address']);
                $city = trim($_POST['city']);
                $region = $_POST['region'] ?? null;
                $blood_group = $_POST['blood_group'] ?? 'unknown';
                $allergies = $_POST['allergies'] ?? null;
                $medical_conditions = $_POST['medical_conditions'] ?? null;
                $current_medications = $_POST['current_medications'] ?? null;
                $customer_type = $_POST['customer_type'];
                $registration_date = $_POST['registration_date'] ?? date('Y-m-d');
                $referred_by = $_POST['referred_by'] ?? null;
                $loyalty_points = intval($_POST['loyalty_points'] ?? 0);
                $status = $_POST['status'] ?? 'active';
                $preferred_communication = $_POST['preferred_communication'] ?? 'email';
                $notes = $_POST['notes'] ?? null;
                $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : $current_branch_id;
                
                // Calculate age if date of birth is provided
                $age = $date_of_birth ? getCustomerAge($date_of_birth) : null;
                
                // Insert customer
                $stmt = $conn->prepare("INSERT INTO customers (
                    customer_code, first_name, last_name, gender, date_of_birth, age,
                    national_id, email, phone, alternate_phone, address, city, region,
                    blood_group, allergies, medical_conditions, current_medications,
                    customer_type, registration_date, referred_by, loyalty_points,
                    total_visits, total_spent, last_visit, status, preferred_communication,
                    notes, branch_id, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $total_visits = 0;
                $total_spent = 0.00;
                $last_visit = null;
                
                $stmt->bind_param("sssssisssssssssssssiidsssssii", 
                    $customer_code,
                    $first_name,
                    $last_name,
                    $gender,
                    $date_of_birth,
                    $age,
                    $national_id,
                    $email,
                    $phone,
                    $alternate_phone,
                    $address,
                    $city,
                    $region,
                    $blood_group,
                    $allergies,
                    $medical_conditions,
                    $current_medications,
                    $customer_type,
                    $registration_date,
                    $referred_by,
                    $loyalty_points,
                    $total_visits,
                    $total_spent,
                    $last_visit,
                    $status,
                    $preferred_communication,
                    $notes,
                    $branch_id,
                    $user_id
                );
                
                if ($stmt->execute()) {
                    $new_customer_id = $stmt->insert_id;
                    
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'ADD_CUSTOMER', 
                        "Added new customer: {$first_name} {$last_name} ({$customer_code})", 
                        $branch_id, null);
                    
                    $response['success'] = true;
                    $response['message'] = "Customer added successfully!";
                    $response['customer_id'] = $new_customer_id;
                    $response['customer_code'] = $customer_code;
                } else {
                    $response['message'] = "Error adding customer: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'edit_customer':
                $customer_id = intval($_POST['customer_id']);
                
                if ($customer_id <= 0) {
                    $response['message'] = "Invalid customer ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if customer exists
                $check_stmt = $conn->prepare("SELECT customer_id, email, phone FROM customers WHERE customer_id = ?");
                $check_stmt->bind_param("i", $customer_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "Customer not found!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $customer_data = $check_result->fetch_assoc();
                $check_stmt->close();
                
                // Validate required fields
                $required = ['first_name', 'last_name', 'phone', 'email', 'address', 'city', 'customer_type'];
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
                
                // Validate phone number
                $phone = trim($_POST['phone']);
                if (!validateMalawiPhone($phone)) {
                    $response['message'] = "Please enter a valid Malawi phone number (format: 088XXXXXXX or +26588XXXXXXX)";
                    echo json_encode($response);
                    exit;
                }
                
                // Validate email
                $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $response['message'] = "Please enter a valid email address";
                    echo json_encode($response);
                    exit;
                }
                
                // Check if email changed and if new email exists for another customer
                if ($customer_data['email'] !== $email) {
                    $check_email = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? AND customer_id != ?");
                    $check_email->bind_param("si", $email, $customer_id);
                    $check_email->execute();
                    if ($check_email->get_result()->num_rows > 0) {
                        $response['message'] = "Email address already exists for another customer!";
                        $check_email->close();
                        echo json_encode($response);
                        exit;
                    }
                    $check_email->close();
                }
                
                // Check if phone changed and if new phone exists for another customer
                if ($customer_data['phone'] !== $phone) {
                    $check_phone = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ? AND customer_id != ?");
                    $check_phone->bind_param("si", $phone, $customer_id);
                    $check_phone->execute();
                    if ($check_phone->get_result()->num_rows > 0) {
                        $response['message'] = "Phone number already exists for another customer!";
                        $check_phone->close();
                        echo json_encode($response);
                        exit;
                    }
                    $check_phone->close();
                }
                
                // Validate date of birth if provided
                $date_of_birth = $_POST['date_of_birth'] ?? null;
                if ($date_of_birth) {
                    $dob = new DateTime($date_of_birth);
                    $today = new DateTime();
                    if ($dob > $today) {
                        $response['message'] = "Date of birth cannot be in the future";
                        echo json_encode($response);
                        exit;
                    }
                }
                
                // Prepare parameters
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $gender = $_POST['gender'] ?? 'other';
                $national_id = $_POST['national_id'] ?? null;
                $alternate_phone = $_POST['alternate_phone'] ?? null;
                $address = trim($_POST['address']);
                $city = trim($_POST['city']);
                $region = $_POST['region'] ?? null;
                $blood_group = $_POST['blood_group'] ?? 'unknown';
                $allergies = $_POST['allergies'] ?? null;
                $medical_conditions = $_POST['medical_conditions'] ?? null;
                $current_medications = $_POST['current_medications'] ?? null;
                $customer_type = $_POST['customer_type'];
                $registration_date = $_POST['registration_date'] ?? date('Y-m-d');
                $referred_by = $_POST['referred_by'] ?? null;
                $loyalty_points = intval($_POST['loyalty_points'] ?? 0);
                $status = $_POST['status'] ?? 'active';
                $preferred_communication = $_POST['preferred_communication'] ?? 'email';
                $notes = $_POST['notes'] ?? null;
                $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : $current_branch_id;
                
                // Calculate age if date of birth is provided
                $age = $date_of_birth ? getCustomerAge($date_of_birth) : null;
                
                // Update customer
                $stmt = $conn->prepare("UPDATE customers SET 
                    first_name = ?, last_name = ?, gender = ?, date_of_birth = ?, age = ?,
                    national_id = ?, email = ?, phone = ?, alternate_phone = ?, address = ?,
                    city = ?, region = ?, blood_group = ?, allergies = ?, medical_conditions = ?,
                    current_medications = ?, customer_type = ?, registration_date = ?,
                    referred_by = ?, loyalty_points = ?, status = ?, preferred_communication = ?,
                    notes = ?, branch_id = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE customer_id = ?");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("sssssisssssssssssssisssii", 
                    $first_name,
                    $last_name,
                    $gender,
                    $date_of_birth,
                    $age,
                    $national_id,
                    $email,
                    $phone,
                    $alternate_phone,
                    $address,
                    $city,
                    $region,
                    $blood_group,
                    $allergies,
                    $medical_conditions,
                    $current_medications,
                    $customer_type,
                    $registration_date,
                    $referred_by,
                    $loyalty_points,
                    $status,
                    $preferred_communication,
                    $notes,
                    $branch_id,
                    $customer_id
                );
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'EDIT_CUSTOMER', 
                        "Updated customer: {$first_name} {$last_name}", 
                        $branch_id, null);
                    
                    $response['success'] = true;
                    $response['message'] = "Customer updated successfully!";
                } else {
                    $response['message'] = "Error updating customer: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'delete_customer':
                $customer_id = intval($_POST['customer_id']);
                
                if ($customer_id <= 0) {
                    $response['message'] = "Invalid customer ID!";
                    echo json_encode($response);
                    exit;
                }
                
                // Get customer info for logging
                $customer_stmt = $conn->prepare("SELECT first_name, last_name, customer_code FROM customers WHERE customer_id = ?");
                $customer_stmt->bind_param("i", $customer_id);
                $customer_stmt->execute();
                $customer_result = $customer_stmt->get_result();
                
                if ($customer_result->num_rows === 0) {
                    $response['message'] = "Customer not found!";
                    $customer_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                
                $customer_data = $customer_result->fetch_assoc();
                $customer_stmt->close();
                
                // Check if customer has purchase history
                $history_check = $conn->prepare("SELECT COUNT(*) as count FROM customer_visits WHERE customer_id = ?");
                $history_check->bind_param("i", $customer_id);
                $history_check->execute();
                $history_result = $history_check->get_result()->fetch_assoc();
                $history_check->close();
                
                if ($history_result['count'] > 0) {
                    $response['message'] = "Cannot delete customer with purchase history. You can deactivate the customer instead.";
                    echo json_encode($response);
                    exit;
                }
                
                // Delete customer (soft delete - update status to inactive instead of actual deletion)
                $stmt = $conn->prepare("UPDATE customers SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE customer_id = ?");
                $stmt->bind_param("i", $customer_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'DELETE_CUSTOMER', 
                        "Deleted customer: {$customer_data['first_name']} {$customer_data['last_name']} ({$customer_data['customer_code']})");
                    
                    $response['success'] = true;
                    $response['message'] = "Customer deleted successfully!";
                } else {
                    $response['message'] = "Error deleting customer: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'get_customer':
                $customer_id = intval($_POST['customer_id']);
                
                if ($customer_id <= 0) {
                    $response['message'] = "Invalid customer ID!";
                    echo json_encode($response);
                    exit;
                }
                
                $stmt = $conn->prepare("
                    SELECT c.*, 
                    b.branch_name
                    FROM customers c
                    LEFT JOIN branches b ON c.branch_id = b.branch_id
                    WHERE c.customer_id = ?
                ");
                
                if (!$stmt) {
                    $response['message'] = "Database error: " . $conn->error;
                    echo json_encode($response);
                    exit;
                }
                
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $customer = $result->fetch_assoc();
                    
                    // Format text values
                    $customer['status_text'] = getStatusText($customer['status']);
                    $customer['customer_type_text'] = getCustomerTypeText($customer['customer_type']);
                    $customer['blood_group_text'] = getBloodGroupText($customer['blood_group']);
                    
                    // Format phone numbers
                    $customer['phone_formatted'] = formatPhoneNumber($customer['phone']);
                    if ($customer['alternate_phone']) {
                        $customer['alternate_phone_formatted'] = formatPhoneNumber($customer['alternate_phone']);
                    }
                    
                    // Format dates
                    if ($customer['date_of_birth']) {
                        $customer['date_of_birth_formatted'] = date('d/m/Y', strtotime($customer['date_of_birth']));
                    }
                    if ($customer['last_visit']) {
                        $customer['last_visit_formatted'] = date('d/m/Y', strtotime($customer['last_visit']));
                    }
                    $customer['registration_date_formatted'] = date('d/m/Y', strtotime($customer['registration_date']));
                    
                    // Parse allergies and medications into arrays
                    $customer['allergies_array'] = $customer['allergies'] ? 
                        array_map('trim', explode(',', $customer['allergies'])) : [];
                    $customer['medications_array'] = $customer['current_medications'] ? 
                        array_map('trim', explode(',', $customer['current_medications'])) : [];
                    
                    // Calculate customer lifetime value metrics
                    $customer['avg_order_value'] = $customer['total_visits'] > 0 ? 
                        round($customer['total_spent'] / $customer['total_visits'], 2) : 0;
                    
                    $response['success'] = true;
                    $response['data'] = $customer;
                } else {
                    $response['message'] = "Customer not found!";
                }
                $stmt->close();
                break;
                
            case 'get_customers':
                // Build query based on filters
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Apply filters
                if (!empty($_POST['search'])) {
                    $where_clauses[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.customer_code LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
                    $search_term = "%" . trim($_POST['search']) . "%";
                    for ($i = 0; $i < 5; $i++) {
                        $params[] = $search_term;
                        $types .= "s";
                    }
                }
                
                if (!empty($_POST['status'])) {
                    $where_clauses[] = "c.status = ?";
                    $params[] = $_POST['status'];
                    $types .= "s";
                }
                
                if (!empty($_POST['type'])) {
                    $where_clauses[] = "c.customer_type = ?";
                    $params[] = $_POST['type'];
                    $types .= "s";
                }
                
                if (!empty($_POST['city'])) {
                    $where_clauses[] = "c.city = ?";
                    $params[] = $_POST['city'];
                    $types .= "s";
                }
                
                // Filter by branch if user is not admin
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "c.branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                } elseif (!empty($_POST['branch'])) {
                    $where_clauses[] = "c.branch_id = ?";
                    $params[] = intval($_POST['branch']);
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get total count
                $count_sql = "SELECT COUNT(*) as total FROM customers c WHERE $where_sql";
                $count_stmt = $conn->prepare($count_sql);
                if (!empty($params)) {
                    $count_stmt->bind_param($types, ...$params);
                }
                $count_stmt->execute();
                $count_result = $count_stmt->get_result()->fetch_assoc();
                $total_customers = $count_result['total'] ?? 0;
                $count_stmt->close();
                
                // Get sorting
                $sort_options = [
                    'name_asc' => 'c.first_name ASC, c.last_name ASC',
                    'name_desc' => 'c.first_name DESC, c.last_name DESC',
                    'spent_high' => 'c.total_spent DESC',
                    'spent_low' => 'c.total_spent ASC',
                    'recent' => 'c.last_visit DESC',
                    'newest' => 'c.registration_date DESC'
                ];
                
                $sort_by = $_POST['sort'] ?? 'name_asc';
                $sort_sql = $sort_options[$sort_by] ?? 'c.first_name ASC, c.last_name ASC';
                
                // Pagination
                $page = max(1, intval($_POST['page'] ?? 1));
                $limit = max(1, intval($_POST['limit'] ?? 10));
                $offset = ($page - 1) * $limit;
                
                $sql = "SELECT c.*, 
                       b.branch_name
                       FROM customers c
                       LEFT JOIN branches b ON c.branch_id = b.branch_id
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
                
                $customers = [];
                $sn = $offset + 1;
                while ($row = $result->fetch_assoc()) {
                    $row['sn'] = $sn++;
                    
                    // Format text values
                    $row['status_text'] = getStatusText($row['status']);
                    $row['customer_type_text'] = getCustomerTypeText($row['customer_type']);
                    
                    // Format phone number
                    $row['phone_formatted'] = formatPhoneNumber($row['phone']);
                    
                    // Format dates
                    if ($row['date_of_birth']) {
                        $row['age'] = getCustomerAge($row['date_of_birth']);
                        $row['date_of_birth_formatted'] = date('d/m/Y', strtotime($row['date_of_birth']));
                    }
                    
                    if ($row['last_visit']) {
                        $last_visit = new DateTime($row['last_visit']);
                        $today = new DateTime();
                        $interval = $today->diff($last_visit);
                        $row['last_visit_text'] = $interval->days == 0 ? 'Today' : 
                            ($interval->days == 1 ? 'Yesterday' : $interval->days . ' days ago');
                        $row['last_visit_formatted'] = date('d/m/Y', strtotime($row['last_visit']));
                    } else {
                        $row['last_visit_text'] = 'Never';
                        $row['last_visit_formatted'] = 'Never';
                    }
                    
                    // Format registration date
                    $row['registration_date_formatted'] = date('d/m/Y', strtotime($row['registration_date']));
                    
                    // Calculate customer value
                    $row['avg_order_value'] = $row['total_visits'] > 0 ? 
                        round($row['total_spent'] / $row['total_visits'], 2) : 0;
                    
                    // Generate avatar initials
                    $row['avatar_initials'] = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
                    
                    // Generate avatar color based on customer type
                    $color_map = [
                        'regular' => '#3498db',
                        'premium' => '#9b59b6',
                        'wholesale' => '#e67e22',
                        'corporate' => '#2980b9',
                        'government' => '#2ecc71'
                    ];
                    $row['avatar_color'] = $color_map[$row['customer_type']] ?? '#3498db';
                    
                    $customers[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $customers;
                $response['total'] = $total_customers;
                $response['page'] = $page;
                $response['pages'] = ceil($total_customers / $limit);
                
                $stmt->close();
                break;
                
            case 'get_customer_stats':
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
                    COUNT(*) as total_customers,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
                    SUM(CASE WHEN customer_type = 'premium' THEN 1 ELSE 0 END) as premium_customers,
                    SUM(CASE WHEN customer_type = 'wholesale' THEN 1 ELSE 0 END) as wholesale_customers,
                    SUM(CASE WHEN customer_type = 'corporate' THEN 1 ELSE 0 END) as corporate_customers,
                    SUM(CASE WHEN customer_type = 'government' THEN 1 ELSE 0 END) as government_customers,
                    SUM(CASE WHEN medical_conditions IS NOT NULL AND medical_conditions != '' THEN 1 ELSE 0 END) as chronic_patients,
                    SUM(total_spent) as total_revenue,
                    AVG(total_spent) as avg_customer_value,
                    SUM(total_visits) as total_visits,
                    AVG(total_visits) as avg_visits_per_customer
                    FROM customers
                    WHERE $where_sql";
                
                $stmt = $conn->prepare($stats_sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $stats_result = $stmt->fetch_assoc();
                $stmt->close();
                
                // Get new customers this month
                $current_month = date('Y-m');
                $new_customers_sql = "SELECT COUNT(*) as new_this_month 
                                     FROM customers 
                                     WHERE DATE_FORMAT(registration_date, '%Y-%m') = ?
                                     AND $where_sql";
                
                $new_stmt = $conn->prepare($new_customers_sql);
                if (!empty($params)) {
                    $all_params = array_merge([$current_month], $params);
                    $all_types = "s" . $types;
                    $new_stmt->bind_param($all_types, ...$all_params);
                } else {
                    $new_stmt->bind_param("s", $current_month);
                }
                $new_stmt->execute();
                $new_result = $new_stmt->get_result()->fetch_assoc();
                $new_stmt->close();
                
                // Get inactive customers (no visits in last 90 days)
                $ninety_days_ago = date('Y-m-d', strtotime('-90 days'));
                $inactive_sql = "SELECT COUNT(*) as inactive_90_days 
                                FROM customers 
                                WHERE (last_visit IS NULL OR last_visit < ?)
                                AND status = 'active'
                                AND $where_sql";
                
                $inactive_stmt = $conn->prepare($inactive_sql);
                if (!empty($params)) {
                    $all_params = array_merge([$ninety_days_ago], $params);
                    $all_types = "s" . $types;
                    $inactive_stmt->bind_param($all_types, ...$all_params);
                } else {
                    $inactive_stmt->bind_param("s", $ninety_days_ago);
                }
                $inactive_stmt->execute();
                $inactive_result = $inactive_stmt->get_result()->fetch_assoc();
                $inactive_stmt->close();
                
                $response['success'] = true;
                $response['stats'] = [
                    'total_customers' => $stats_result['total_customers'] ?? 0,
                    'active_customers' => $stats_result['active_customers'] ?? 0,
                    'premium_customers' => $stats_result['premium_customers'] ?? 0,
                    'wholesale_customers' => $stats_result['wholesale_customers'] ?? 0,
                    'corporate_customers' => $stats_result['corporate_customers'] ?? 0,
                    'government_customers' => $stats_result['government_customers'] ?? 0,
                    'chronic_patients' => $stats_result['chronic_patients'] ?? 0,
                    'total_revenue' => $stats_result['total_revenue'] ?? 0,
                    'avg_customer_value' => $stats_result['avg_customer_value'] ?? 0,
                    'total_visits' => $stats_result['total_visits'] ?? 0,
                    'avg_visits_per_customer' => $stats_result['avg_visits_per_customer'] ?? 0,
                    'new_this_month' => $new_result['new_this_month'] ?? 0,
                    'inactive_90_days' => $inactive_result['inactive_90_days'] ?? 0
                ];
                break;
                
            case 'get_customer_visits':
                $customer_id = intval($_POST['customer_id']);
                $limit = intval($_POST['limit'] ?? 10);
                
                if ($customer_id <= 0) {
                    $response['message'] = "Invalid customer ID!";
                    echo json_encode($response);
                    exit;
                }
                
                $stmt = $conn->prepare("
                    SELECT cv.* 
                    FROM customer_visits cv
                    WHERE cv.customer_id = ?
                    ORDER BY cv.visit_date DESC
                    LIMIT ?
                ");
                
                $stmt->bind_param("ii", $customer_id, $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $visits = [];
                while ($row = $result->fetch_assoc()) {
                    // Format dates and amounts
                    $row['visit_date_formatted'] = date('d/m/Y', strtotime($row['visit_date']));
                    $row['total_amount_formatted'] = number_format($row['total_amount'], 2);
                    
                    // Format payment status
                    $payment_status_map = [
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'pending' => 'Pending',
                        'cancelled' => 'Cancelled'
                    ];
                    $row['payment_status_text'] = $payment_status_map[$row['payment_status']] ?? $row['payment_status'];
                    
                    $visits[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $visits;
                $stmt->close();
                break;
                
            case 'get_cities':
                $stmt = $conn->prepare("SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $cities = [];
                while ($row = $result->fetch_assoc()) {
                    $cities[] = $row['city'];
                }
                
                $response['success'] = true;
                $response['data'] = $cities;
                $stmt->close();
                break;
                
            default:
                $response['message'] = "Invalid action!";
        }
    } catch (Exception $e) {
        $response['message'] = "System Error: " . $e->getMessage();
        error_log("Customers API Error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Close connection
$conn->close();
?>