<?php
// prescriptions_backend.php - Backend API for Prescriptions Management

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting - turn off for production
error_reporting(0);
ini_set('display_errors', 0);

// Include configuration file
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

// Get current user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? 'Unknown User';
$user_role = $_SESSION['user_role'] ?? 'staff';
$current_branch_id = $_SESSION['branch_id'] ?? null;

// Database connection
try {
    $conn = connectDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Set header for JSON response
header('Content-Type: application/json');

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
function generatePrescriptionNumber($conn) {
    $prefix = 'RX';
    $year = date('Y');
    $month = date('m');
    
    // Get the last prescription number for this month
    $stmt = $conn->prepare("SELECT prescription_number FROM customer_prescriptions WHERE prescription_number LIKE ? ORDER BY prescription_id DESC LIMIT 1");
    $like_pattern = $prefix . '-' . $year . $month . '-%';
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_number = $result->fetch_assoc()['prescription_number'];
        $parts = explode('-', $last_number);
        $last_seq = intval(end($parts));
        $new_seq = str_pad($last_seq + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_seq = '0001';
    }
    
    $stmt->close();
    return $prefix . '-' . $year . $month . '-' . $new_seq;
}

function getStatusText($status) {
    $status_map = [
        'active' => 'Active',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled'
    ];
    
    return $status_map[$status] ?? ucfirst($status);
}

function getPrescriptionTypeText($type) {
    $type_map = [
        'new' => 'New Prescription',
        'refill' => 'Refill',
        'renewal' => 'Renewal',
        'emergency' => 'Emergency',
        'general' => 'General'
    ];
    
    return $type_map[$type] ?? ucfirst($type);
}

function getUrgencyText($urgency) {
    $urgency_map = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High'
    ];
    
    return $urgency_map[$urgency] ?? ucfirst($urgency);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'get_prescriptions':
                // Build query based on filters
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Apply filters
                if (!empty($_POST['search'])) {
                    $where_clauses[] = "(cp.prescription_number LIKE ? OR COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Unknown Patient') LIKE ? OR cp.doctor_name LIKE ? OR cp.diagnosis LIKE ? OR COALESCE(cp.symptoms, '') LIKE ?)";
                    $search_term = "%" . trim($_POST['search']) . "%";
                    for ($i = 0; $i < 5; $i++) {
                        $params[] = $search_term;
                        $types .= "s";
                    }
                }
                
                if (!empty($_POST['status'])) {
                    $where_clauses[] = "cp.status = ?";
                    $params[] = $_POST['status'];
                    $types .= "s";
                }
                
                if (!empty($_POST['type'])) {
                    $where_clauses[] = "cp.prescription_type = ?";
                    $params[] = $_POST['type'];
                    $types .= "s";
                }
                
                if (!empty($_POST['urgency'])) {
                    $where_clauses[] = "cp.urgency_level = ?";
                    $params[] = $_POST['urgency'];
                    $types .= "s";
                }
                
                // Filter by branch through customers
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "COALESCE(c.branch_id, 0) = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get total count
                $count_sql = "SELECT COUNT(*) as total 
                            FROM customer_prescriptions cp
                            LEFT JOIN customers c ON cp.customer_id = c.customer_id
                            WHERE $where_sql";
                
                $count_stmt = $conn->prepare($count_sql);
                if (!empty($params)) {
                    $count_stmt->bind_param($types, ...$params);
                }
                $count_stmt->execute();
                $count_result = $count_stmt->get_result()->fetch_assoc();
                $total_prescriptions = $count_result['total'] ?? 0;
                $count_stmt->close();
                
                // Get sorting
                $sort_options = [
                    'date_desc' => 'cp.prescription_date DESC',
                    'date_asc' => 'cp.prescription_date ASC',
                    'urgency_high' => 'FIELD(cp.urgency_level, "high", "medium", "low")',
                    'patient_asc' => 'COALESCE(c.first_name, "Unknown Patient") ASC, COALESCE(c.last_name, "") ASC',
                    'patient_desc' => 'COALESCE(c.first_name, "Unknown Patient") DESC, COALESCE(c.last_name, "") DESC'
                ];
                
                $sort_by = $_POST['sort'] ?? 'date_desc';
                $sort_sql = $sort_options[$sort_by] ?? 'cp.prescription_date DESC';
                
                // Pagination
                $page = max(1, intval($_POST['page'] ?? 1));
                $limit = max(1, intval($_POST['limit'] ?? 10));
                $offset = ($page - 1) * $limit;
                
                // Main query with all columns
                $sql = "SELECT cp.*, 
                       COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Unknown Patient') as patient_name,
                       COALESCE(c.customer_code, 'N/A') as customer_code,
                       COALESCE(c.phone, 'N/A') as phone,
                       c.date_of_birth,
                       COALESCE(c.gender, 'N/A') as patient_gender,
                       COALESCE(c.allergies, 'No allergies reported') as allergies,
                       COALESCE(c.medical_conditions, 'No medical conditions') as medical_conditions,
                       (SELECT COUNT(*) FROM prescription_items WHERE prescription_id = cp.prescription_id) as medication_count,
                       cp.medications_total,
                       cp.services_total,
                       cp.total_amount,
                       cp.vat_amount,
                       cp.grand_total
                       FROM customer_prescriptions cp
                       LEFT JOIN customers c ON cp.customer_id = c.customer_id
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
                
                $prescriptions = [];
                $sn = $offset + 1;
                while ($row = $result->fetch_assoc()) {
                    $row['sn'] = $sn++;
                    
                    // Format text values
                    $row['prescription_type_text'] = getPrescriptionTypeText($row['prescription_type']);
                    $row['urgency_level_text'] = getUrgencyText($row['urgency_level']);
                    $row['status_text'] = getStatusText($row['status']);
                    
                    // Format dates
                    if ($row['prescription_date']) {
                        $row['prescription_date_formatted'] = date('d/m/Y', strtotime($row['prescription_date']));
                    }
                    if ($row['valid_from']) {
                        $row['valid_from_formatted'] = date('d/m/Y', strtotime($row['valid_from']));
                    }
                    if ($row['valid_until']) {
                        $row['valid_until_formatted'] = date('d/m/Y', strtotime($row['valid_until']));
                    }
                    
                    // Calculate patient age
                    if ($row['date_of_birth']) {
                        $birth_date = new DateTime($row['date_of_birth']);
                        $today = new DateTime();
                        $age = $today->diff($birth_date)->y;
                        $row['patient_age'] = $age;
                    } else {
                        $row['patient_age'] = 'N/A';
                    }
                    
                    // Format monetary values
                    $row['medications_total_formatted'] = number_format($row['medications_total'] ?? 0, 2);
                    $row['services_total_formatted'] = number_format($row['services_total'] ?? 0, 2);
                    $row['total_amount_formatted'] = number_format($row['total_amount'] ?? 0, 2);
                    $row['grand_total_formatted'] = number_format($row['grand_total'] ?? 0, 2);
                    
                    // Get medications for this prescription
                    $meds_stmt = $conn->prepare("SELECT medicine_name, dosage, frequency, duration FROM prescription_items WHERE prescription_id = ? LIMIT 3");
                    $meds_stmt->bind_param("i", $row['prescription_id']);
                    $meds_stmt->execute();
                    $meds_result = $meds_stmt->get_result();
                    
                    $row['medications_preview'] = [];
                    while ($med = $meds_result->fetch_assoc()) {
                        $row['medications_preview'][] = $med;
                    }
                    $meds_stmt->close();
                    
                    $prescriptions[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $prescriptions;
                $response['total'] = $total_prescriptions;
                $response['page'] = $page;
                $response['pages'] = ceil($total_prescriptions / $limit);
                
                $stmt->close();
                break;
                
            case 'get_prescription_stats':
                // Build where clause
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Filter by date range
                if (!empty($_POST['date_from'])) {
                    $where_clauses[] = "cp.prescription_date >= ?";
                    $params[] = $_POST['date_from'];
                    $types .= "s";
                }
                
                if (!empty($_POST['date_to'])) {
                    $where_clauses[] = "cp.prescription_date <= ?";
                    $params[] = $_POST['date_to'];
                    $types .= "s";
                }
                
                // Filter by branch
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "COALESCE(c.branch_id, 0) = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
                
                // Get overall statistics
                $stats_sql = "SELECT 
                    COUNT(*) as total_prescriptions,
                    SUM(CASE WHEN cp.status = 'active' THEN 1 ELSE 0 END) as active_prescriptions,
                    SUM(CASE WHEN cp.status = 'pending' THEN 1 ELSE 0 END) as pending_prescriptions,
                    SUM(CASE WHEN cp.status = 'completed' THEN 1 ELSE 0 END) as completed_prescriptions,
                    SUM(CASE WHEN cp.status = 'expired' THEN 1 ELSE 0 END) as expired_prescriptions,
                    SUM(CASE WHEN cp.prescription_type = 'new' THEN 1 ELSE 0 END) as new_prescriptions,
                    SUM(CASE WHEN cp.prescription_type = 'refill' THEN 1 ELSE 0 END) as refill_prescriptions,
                    SUM(CASE WHEN cp.prescription_type = 'renewal' THEN 1 ELSE 0 END) as renewal_prescriptions,
                    SUM(CASE WHEN cp.prescription_type = 'emergency' THEN 1 ELSE 0 END) as emergency_prescriptions,
                    SUM(CASE WHEN cp.prescription_type = 'general' THEN 1 ELSE 0 END) as general_prescriptions,
                    SUM(CASE WHEN cp.urgency_level = 'high' THEN 1 ELSE 0 END) as high_urgency,
                    SUM(CASE WHEN cp.urgency_level = 'medium' THEN 1 ELSE 0 END) as medium_urgency,
                    SUM(CASE WHEN cp.urgency_level = 'low' THEN 1 ELSE 0 END) as low_urgency,
                    SUM(COALESCE(cp.grand_total, 0)) as total_revenue
                    FROM customer_prescriptions cp
                    LEFT JOIN customers c ON cp.customer_id = c.customer_id
                    $where_sql";
                
                $stmt = $conn->prepare($stats_sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $stats_result = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                // Get today's statistics
                $today = date('Y-m-d');
                $today_sql = "SELECT 
                    COUNT(*) as today_prescriptions,
                    SUM(COALESCE(cp.grand_total, 0)) as today_revenue
                    FROM customer_prescriptions cp
                    LEFT JOIN customers c ON cp.customer_id = c.customer_id
                    WHERE DATE(cp.prescription_date) = ?
                    " . ($user_role !== 'admin' && $current_branch_id ? " AND COALESCE(c.branch_id, 0) = ?" : "");
                
                $today_stmt = $conn->prepare($today_sql);
                if ($user_role !== 'admin' && $current_branch_id) {
                    $today_stmt->bind_param("si", $today, $current_branch_id);
                } else {
                    $today_stmt->bind_param("s", $today);
                }
                $today_stmt->execute();
                $today_result = $today_stmt->get_result()->fetch_assoc();
                $today_stmt->close();
                
                // Count prescriptions expiring in next 7 days
                $next_week = date('Y-m-d', strtotime('+7 days'));
                $expiring_sql = "SELECT COUNT(*) as expiring_soon
                                FROM customer_prescriptions cp
                                LEFT JOIN customers c ON cp.customer_id = c.customer_id
                                WHERE cp.valid_until BETWEEN ? AND ?
                                AND cp.status = 'active'
                                " . ($user_role !== 'admin' && $current_branch_id ? " AND COALESCE(c.branch_id, 0) = ?" : "");
                
                $expiring_stmt = $conn->prepare($expiring_sql);
                if ($user_role !== 'admin' && $current_branch_id) {
                    $expiring_stmt->bind_param("ssi", $today, $next_week, $current_branch_id);
                } else {
                    $expiring_stmt->bind_param("ss", $today, $next_week);
                }
                $expiring_stmt->execute();
                $expiring_result = $expiring_stmt->get_result()->fetch_assoc();
                $expiring_stmt->close();
                
                $response['success'] = true;
                $response['stats'] = [
                    'total_prescriptions' => $stats_result['total_prescriptions'] ?? 0,
                    'active_prescriptions' => $stats_result['active_prescriptions'] ?? 0,
                    'pending_prescriptions' => $stats_result['pending_prescriptions'] ?? 0,
                    'completed_prescriptions' => $stats_result['completed_prescriptions'] ?? 0,
                    'expired_prescriptions' => $stats_result['expired_prescriptions'] ?? 0,
                    'new_prescriptions' => $stats_result['new_prescriptions'] ?? 0,
                    'refill_prescriptions' => $stats_result['refill_prescriptions'] ?? 0,
                    'renewal_prescriptions' => $stats_result['renewal_prescriptions'] ?? 0,
                    'emergency_prescriptions' => $stats_result['emergency_prescriptions'] ?? 0,
                    'general_prescriptions' => $stats_result['general_prescriptions'] ?? 0,
                    'high_urgency' => $stats_result['high_urgency'] ?? 0,
                    'medium_urgency' => $stats_result['medium_urgency'] ?? 0,
                    'low_urgency' => $stats_result['low_urgency'] ?? 0,
                    'total_revenue' => $stats_result['total_revenue'] ?? 0,
                    'today_prescriptions' => $today_result['today_prescriptions'] ?? 0,
                    'today_revenue' => $today_result['today_revenue'] ?? 0,
                    'expiring_soon' => $expiring_result['expiring_soon'] ?? 0
                ];
                break;
                
            case 'get_prescription':
                $prescription_id = intval($_POST['prescription_id'] ?? 0);
                
                if ($prescription_id <= 0) {
                    $response['message'] = "Invalid prescription ID!";
                    break;
                }
                
                // Get prescription details with NULL handling
                $stmt = $conn->prepare("
                    SELECT cp.*, 
                    COALESCE(c.first_name, 'Unknown') as first_name, 
                    COALESCE(c.last_name, 'Patient') as last_name, 
                    COALESCE(c.customer_code, 'N/A') as customer_code, 
                    COALESCE(c.phone, 'N/A') as phone, 
                    COALESCE(c.email, 'N/A') as email, 
                    c.date_of_birth,
                    COALESCE(c.gender, 'N/A') as patient_gender, 
                    COALESCE(c.allergies, 'No allergies reported') as allergies, 
                    COALESCE(c.medical_conditions, 'No medical conditions') as medical_conditions,
                    COALESCE(u.full_name, 'Unknown User') as created_by_name
                    FROM customer_prescriptions cp
                    LEFT JOIN customers c ON cp.customer_id = c.customer_id
                    LEFT JOIN users u ON cp.created_by = u.user_id
                    WHERE cp.prescription_id = ?
                ");
                
                $stmt->bind_param("i", $prescription_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $prescription = $result->fetch_assoc();
                    
                    // Get prescription items with NULL handling
                    $items_stmt = $conn->prepare("
                        SELECT pi.*, 
                        COALESCE(m.medicine_name, pi.medicine_name, 'Unknown Medicine') as full_name, 
                        COALESCE(m.strength, 'N/A') as strength, 
                        COALESCE(m.dosage_form, 'N/A') as dosage_form, 
                        COALESCE(m.selling_price, 0) as selling_price, 
                        COALESCE(m.unit_price, 0) as medicine_unit_price
                        FROM prescription_items pi
                        LEFT JOIN medicines m ON pi.medicine_id = m.medicine_id
                        WHERE pi.prescription_id = ?
                    ");
                    
                    $items_stmt->bind_param("i", $prescription_id);
                    $items_stmt->execute();
                    $items_result = $items_stmt->get_result();
                    
                    $medications = [];
                    while ($item = $items_result->fetch_assoc()) {
                        $medications[] = $item;
                    }
                    $items_stmt->close();
                    
                    // Get prescription services
                    $services_stmt = $conn->prepare("
                        SELECT ps.*, COALESCE(s.service_description, 'No description') as service_description
                        FROM prescription_services ps
                        LEFT JOIN services s ON ps.service_id = s.service_id
                        WHERE ps.prescription_id = ?
                    ");
                    
                    $services_stmt->bind_param("i", $prescription_id);
                    $services_stmt->execute();
                    $services_result = $services_stmt->get_result();
                    
                    $services = [];
                    while ($service = $services_result->fetch_assoc()) {
                        $services[] = $service;
                    }
                    $services_stmt->close();
                    
                    // Get dispensing history with NULL handling
                    $dispensing_stmt = $conn->prepare("
                        SELECT pd.*, COALESCE(u.full_name, 'Unknown Pharmacist') as pharmacist_name
                        FROM prescription_dispensing pd
                        LEFT JOIN users u ON pd.pharmacist_id = u.user_id
                        WHERE pd.prescription_id = ?
                        ORDER BY pd.dispensing_date DESC
                    ");
                    
                    $dispensing_stmt->bind_param("i", $prescription_id);
                    $dispensing_stmt->execute();
                    $dispensing_result = $dispensing_stmt->get_result();
                    
                    $dispensing_history = [];
                    while ($dispense = $dispensing_result->fetch_assoc()) {
                        $dispensing_history[] = $dispense;
                    }
                    $dispensing_stmt->close();
                    
                    // Calculate patient age
                    if ($prescription['date_of_birth']) {
                        $birth_date = new DateTime($prescription['date_of_birth']);
                        $today = new DateTime();
                        $age = $today->diff($birth_date)->y;
                        $prescription['patient_age'] = $age;
                    } else {
                        $prescription['patient_age'] = 'N/A';
                    }
                    
                    // Format data
                    $prescription['prescription_type_text'] = getPrescriptionTypeText($prescription['prescription_type']);
                    $prescription['urgency_level_text'] = getUrgencyText($prescription['urgency_level']);
                    $prescription['status_text'] = getStatusText($prescription['status']);
                    
                    // Ensure all fields have proper values
                    $prescription['doctor_name'] = $prescription['doctor_name'] ?? 'Unknown Doctor';
                    $prescription['diagnosis'] = $prescription['diagnosis'] ?? 'No diagnosis provided';
                    $prescription['symptoms'] = $prescription['symptoms'] ?? '';
                    $prescription['instructions'] = $prescription['instructions'] ?? 'No instructions provided';
                    $prescription['notes'] = $prescription['notes'] ?? 'No additional notes';
                    $prescription['allergies_warning'] = $prescription['allergies_warning'] ?? '';
                    $prescription['valid_from'] = $prescription['valid_from'] ?? $prescription['prescription_date'];
                    
                    $response['success'] = true;
                    $response['data'] = $prescription;
                    $response['medications'] = $medications;
                    $response['services'] = $services;
                    $response['dispensing_history'] = $dispensing_history;
                    
                } else {
                    $response['message'] = "Prescription not found!";
                }
                $stmt->close();
                break;
                
            case 'get_all_customers':
                // Get all active customers for dropdown
                $search = $_POST['search'] ?? '';
                $where = "c.status = 'active'";
                $params = [];
                $types = "";
                
                if (!empty($search)) {
                    $where .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.customer_code LIKE ? OR c.phone LIKE ?)";
                    $search_term = "%" . $search . "%";
                    for ($i = 0; $i < 4; $i++) {
                        $params[] = $search_term;
                        $types .= "s";
                    }
                }
                
                // Filter by branch for non-admin users
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where .= " AND c.branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $sql = "SELECT c.customer_id, c.customer_code, 
                               CONCAT(c.first_name, ' ', c.last_name) as full_name,
                               c.first_name, c.last_name, c.phone, c.email,
                               c.date_of_birth, c.gender, c.allergies, c.medical_conditions
                        FROM customers c
                        WHERE $where
                        ORDER BY c.first_name, c.last_name
                        LIMIT 100";
                
                $stmt = $conn->prepare($sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                $customers = [];
                while ($row = $result->fetch_assoc()) {
                    // Calculate age
                    $age = 'N/A';
                    if ($row['date_of_birth']) {
                        $birth_date = new DateTime($row['date_of_birth']);
                        $today = new DateTime();
                        $age = $today->diff($birth_date)->y;
                    }
                    
                    $customers[] = [
                        'id' => $row['customer_id'],
                        'code' => $row['customer_code'],
                        'full_name' => $row['full_name'],
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'phone' => $row['phone'],
                        'email' => $row['email'],
                        'age' => $age,
                        'gender' => $row['gender'],
                        'allergies' => $row['allergies'],
                        'medical_conditions' => $row['medical_conditions']
                    ];
                }
                
                $response['success'] = true;
                $response['customers'] = $customers;
                $stmt->close();
                break;
                
            case 'get_all_services':
                // Get all active services
                $search = $_POST['search'] ?? '';
                $where = "s.is_active = 1";
                $params = [];
                $types = "";
                
                if (!empty($search)) {
                    $where .= " AND (s.service_name LIKE ? OR s.service_category LIKE ?)";
                    $search_term = "%" . $search . "%";
                    $params[] = $search_term;
                    $params[] = $search_term;
                    $types = "ss";
                }
                
                $sql = "SELECT s.service_id, s.service_name, s.service_description, 
                               s.service_category, s.price_mwk, s.is_active
                        FROM services s
                        WHERE $where
                        ORDER BY s.service_category, s.service_name";
                
                $stmt = $conn->prepare($sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                $services = [];
                while ($row = $result->fetch_assoc()) {
                    $services[] = [
                        'id' => $row['service_id'],
                        'name' => $row['service_name'],
                        'description' => $row['service_description'],
                        'category' => $row['service_category'],
                        'price' => $row['price_mwk'],
                        'is_active' => $row['is_active']
                    ];
                }
                
                $response['success'] = true;
                $response['services'] = $services;
                $stmt->close();
                break;
                
            case 'get_vat_rate':
                // Get default VAT rate
                $sql = "SELECT vat_id, rate_name, percentage, rate_type, is_default 
                        FROM vat_rates 
                        WHERE is_active = 1 AND is_default = 1 
                        LIMIT 1";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $vat_rate = $result->fetch_assoc();
                    $response['success'] = true;
                    $response['vat_rate'] = $vat_rate;
                } else {
                    // Use default VAT if none set
                    $response['success'] = true;
                    $response['vat_rate'] = [
                        'vat_id' => 1,
                        'rate_name' => 'Standard VAT',
                        'percentage' => 16.50,
                        'rate_type' => 'standard',
                        'is_default' => 1
                    ];
                }
                $stmt->close();
                break;
                
            case 'get_doctors':
                // Get doctors from users table
                $search_term = $_POST['search'] ?? '';
                
                if ($search_term) {
                    $stmt = $conn->prepare("SELECT user_id, COALESCE(full_name, 'Unknown Doctor') as full_name, COALESCE(phone, 'N/A') as phone, COALESCE(email, 'N/A') as email FROM users WHERE role IN ('clinician', 'dental_staff') AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?) AND status = 'active' ORDER BY full_name");
                    $search_param = "%{$search_term}%";
                    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
                } else {
                    $stmt = $conn->prepare("SELECT user_id, COALESCE(full_name, 'Unknown Doctor') as full_name, COALESCE(phone, 'N/A') as phone, COALESCE(email, 'N/A') as email FROM users WHERE role IN ('clinician', 'dental_staff') AND status = 'active' ORDER BY full_name");
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                $doctors = [];
                while ($row = $result->fetch_assoc()) {
                    $doctors[] = $row;
                }
                
                $response['success'] = true;
                $response['doctors'] = $doctors;
                $stmt->close();
                break;
                
            case 'get_customers_autocomplete':
                $search = $_POST['search'] ?? '';
                
                if (strlen($search) < 2) {
                    $response['success'] = true;
                    $response['customers'] = [];
                    break;
                }
                
                $search_term = "%" . $search . "%";
                $stmt = $conn->prepare("
                    SELECT customer_id, 
                    COALESCE(customer_code, 'N/A') as customer_code, 
                    COALESCE(first_name, '') as first_name, 
                    COALESCE(last_name, '') as last_name, 
                    COALESCE(phone, 'N/A') as phone, 
                    COALESCE(email, 'N/A') as email,
                    date_of_birth, 
                    COALESCE(gender, 'N/A') as gender, 
                    COALESCE(allergies, 'No allergies reported') as allergies, 
                    COALESCE(medical_conditions, 'No medical conditions') as medical_conditions
                    FROM customers 
                    WHERE (first_name LIKE ? OR last_name LIKE ? OR customer_code LIKE ? OR phone LIKE ? OR email LIKE ?)
                    AND status = 'active'
                    ORDER BY first_name, last_name
                    LIMIT 20
                ");
                
                $stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $search_term);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $customers = [];
                while ($row = $result->fetch_assoc()) {
                    // Calculate age
                    $age = 'N/A';
                    if ($row['date_of_birth']) {
                        $birth_date = new DateTime($row['date_of_birth']);
                        $today = new DateTime();
                        $age = $today->diff($birth_date)->y;
                    }
                    
                    $customers[] = [
                        'id' => $row['customer_id'],
                        'code' => $row['customer_code'],
                        'name' => trim($row['first_name'] . ' ' . $row['last_name']) ?: 'Unknown Customer',
                        'phone' => $row['phone'],
                        'email' => $row['email'],
                        'age' => $age,
                        'gender' => $row['gender'],
                        'allergies' => $row['allergies'],
                        'medical_conditions' => $row['medical_conditions']
                    ];
                }
                
                $response['success'] = true;
                $response['customers'] = $customers;
                $stmt->close();
                break;
                
            case 'get_medicines_autocomplete':
                $search = $_POST['search'] ?? '';
                
                if (strlen($search) < 2) {
                    $response['success'] = true;
                    $response['medicines'] = [];
                    break;
                }
                
                $search_term = "%" . $search . "%";
                
                // Build query with branch filtering
                $sql = "SELECT medicine_id, 
                        COALESCE(medicine_code, 'N/A') as medicine_code, 
                        COALESCE(medicine_name, 'Unknown Medicine') as medicine_name, 
                        COALESCE(generic_name, 'N/A') as generic_name, 
                        COALESCE(dosage_form, 'N/A') as dosage_form, 
                        COALESCE(strength, 'N/A') as strength, 
                        COALESCE(selling_price, 0) as selling_price, 
                        COALESCE(unit_price, 0) as unit_price, 
                        COALESCE(current_stock, 0) as current_stock, 
                        expiry_date
                        FROM medicines 
                        WHERE (medicine_name LIKE ? OR generic_name LIKE ? OR medicine_code LIKE ?)
                        AND status = 'active' AND stock_status != 'expired'";
                
                $params = [];
                $types = "sss";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                
                // Filter by branch for non-admin users
                if ($user_role !== 'admin' && $current_branch_id) {
                    $sql .= " AND branch_id = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $sql .= " ORDER BY medicine_name LIMIT 20";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $medicines = [];
                while ($row = $result->fetch_assoc()) {
                    $medicines[] = [
                        'id' => $row['medicine_id'],
                        'code' => $row['medicine_code'],
                        'name' => $row['medicine_name'],
                        'generic_name' => $row['generic_name'],
                        'dosage_form' => $row['dosage_form'],
                        'strength' => $row['strength'],
                        'selling_price' => $row['selling_price'],
                        'unit_price' => $row['unit_price'],
                        'current_stock' => $row['current_stock'],
                        'expiry_date' => $row['expiry_date']
                    ];
                }
                
                $response['success'] = true;
                $response['medicines'] = $medicines;
                $stmt->close();
                break;
                
            case 'add_prescription':
                // Validate required fields
                $required = ['customer_id', 'doctor_name', 'diagnosis', 'prescription_type', 'urgency_level'];
                $missing = [];
                foreach ($required as $field) {
                    if (empty(trim($_POST[$field] ?? ''))) {
                        $missing[] = str_replace('_', ' ', $field);
                    }
                }
                
                if (!empty($missing)) {
                    $response['message'] = "Please fill in all required fields: " . implode(', ', $missing);
                    break;
                }
                
                // Generate prescription number
                $prescription_number = generatePrescriptionNumber($conn);
                
                // Get VAT rate
                $vat_stmt = $conn->prepare("SELECT percentage, vat_id FROM vat_rates WHERE is_default = 1 AND is_active = 1 LIMIT 1");
                $vat_stmt->execute();
                $vat_result = $vat_stmt->get_result();
                $vat_percentage = 16.50;
                $vat_id = 1;
                if ($vat_result->num_rows > 0) {
                    $vat_data = $vat_result->fetch_assoc();
                    $vat_percentage = floatval($vat_data['percentage']);
                    $vat_id = intval($vat_data['vat_id']);
                }
                $vat_stmt->close();
                
                // Prepare prescription data
                $customer_id = intval($_POST['customer_id']);
                $doctor_name = trim($_POST['doctor_name'] ?? 'Unknown Doctor');
                $diagnosis = trim($_POST['diagnosis'] ?? 'No diagnosis provided');
                $prescription_date = $_POST['prescription_date'] ?? date('Y-m-d');
                $prescription_type = $_POST['prescription_type'] ?? 'new';
                $urgency_level = $_POST['urgency_level'] ?? 'medium';
                $valid_from = $_POST['valid_from'] ?? date('Y-m-d');
                $valid_until = $_POST['valid_until'] ?? date('Y-m-d', strtotime('+30 days'));
                $refills_allowed = intval($_POST['refills_allowed'] ?? 0);
                $patient_weight = !empty($_POST['patient_weight']) ? floatval($_POST['patient_weight']) : null;
                $patient_height = !empty($_POST['patient_height']) ? intval($_POST['patient_height']) : null;
                $symptoms = trim($_POST['symptoms'] ?? '');
                $instructions = trim($_POST['instructions'] ?? 'No instructions provided');
                $notes = trim($_POST['notes'] ?? '');
                $allergies_warning = trim($_POST['allergies_warning'] ?? '');
                $status = $_POST['status'] ?? 'active';
                
                // Check if customer exists
                $check_customer = $conn->prepare("SELECT customer_id FROM customers WHERE customer_id = ?");
                $check_customer->bind_param("i", $customer_id);
                $check_customer->execute();
                if ($check_customer->get_result()->num_rows === 0) {
                    $response['message'] = "Customer not found!";
                    $check_customer->close();
                    break;
                }
                $check_customer->close();
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert prescription with all columns
                    $stmt = $conn->prepare("INSERT INTO customer_prescriptions (
                        prescription_number, customer_id, doctor_name, diagnosis, prescription_date,
                        prescription_type, urgency_level, valid_from, valid_until, refills_allowed,
                        patient_weight, patient_height, symptoms, instructions, notes, allergies_warning,
                        status, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->bind_param("sississssiissssssi",
                        $prescription_number,
                        $customer_id,
                        $doctor_name,
                        $diagnosis,
                        $prescription_date,
                        $prescription_type,
                        $urgency_level,
                        $valid_from,
                        $valid_until,
                        $refills_allowed,
                        $patient_weight,
                        $patient_height,
                        $symptoms,
                        $instructions,
                        $notes,
                        $allergies_warning,
                        $status,
                        $user_id
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error saving prescription: " . $stmt->error);
                    }
                    
                    $prescription_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // Save prescription items if provided
                    $total_amount = 0;
                    $medications_total = 0;
                    $services_total = 0;
                    
                    if (!empty($_POST['medications']) && is_array($_POST['medications'])) {
                        foreach ($_POST['medications'] as $index => $medication) {
                            $medicine_id = !empty($medication['medicine_id']) ? intval($medication['medicine_id']) : null;
                            $medicine_name = trim($medication['medicine_name'] ?? 'Unknown Medicine');
                            $dosage = trim($medication['dosage'] ?? 'N/A');
                            $frequency = trim($medication['frequency'] ?? 'N/A');
                            $duration = intval($medication['duration'] ?? 1);
                            $quantity = intval($medication['quantity'] ?? 1);
                            $med_instructions = trim($medication['instructions'] ?? '');
                            
                            // Get medicine price if medicine_id exists
                            $unit_price = 0;
                            $selling_price = 0;
                            
                            if ($medicine_id) {
                                $price_stmt = $conn->prepare("SELECT unit_price, selling_price FROM medicines WHERE medicine_id = ?");
                                $price_stmt->bind_param("i", $medicine_id);
                                $price_stmt->execute();
                                $price_result = $price_stmt->get_result();
                                
                                if ($price_result->num_rows > 0) {
                                    $price_data = $price_result->fetch_assoc();
                                    $unit_price = floatval($price_data['unit_price'] ?? 0);
                                    $selling_price = floatval($price_data['selling_price'] ?? 0);
                                }
                                $price_stmt->close();
                            }
                            
                            // Calculate item total
                            $item_total = $selling_price * $quantity;
                            $medications_total += $item_total;
                            $total_amount += $item_total;
                            
                            // Insert prescription item
                            $item_stmt = $conn->prepare("INSERT INTO prescription_items (
                                prescription_id, medicine_id, medicine_name, dosage, frequency, 
                                duration, quantity, unit_price, selling_price, total_price, 
                                instructions, dispensed_quantity
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                            
                            $item_stmt->bind_param("iisssiiddds",
                                $prescription_id,
                                $medicine_id,
                                $medicine_name,
                                $dosage,
                                $frequency,
                                $duration,
                                $quantity,
                                $unit_price,
                                $selling_price,
                                $item_total,
                                $med_instructions
                            );
                            
                            if (!$item_stmt->execute()) {
                                throw new Exception("Error saving prescription item: " . $item_stmt->error);
                            }
                            $item_stmt->close();
                        }
                    }
                    
                    // Save prescription services if provided
                    if (!empty($_POST['services']) && is_array($_POST['services'])) {
                        foreach ($_POST['services'] as $index => $service) {
                            $service_id = !empty($service['service_id']) ? intval($service['service_id']) : null;
                            $service_name = trim($service['service_name'] ?? 'Unknown Service');
                            $service_price = floatval($service['service_price'] ?? 0);
                            $quantity = intval($service['quantity'] ?? 1);
                            $service_notes = trim($service['notes'] ?? '');
                            
                            // Calculate service total
                            $service_total = $service_price * $quantity;
                            $services_total += $service_total;
                            $total_amount += $service_total;
                            
                            // Insert prescription service
                            $service_stmt = $conn->prepare("INSERT INTO prescription_services (
                                prescription_id, service_id, service_name, service_price, 
                                quantity, total_price, notes
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            
                            $service_stmt->bind_param("iisidds",
                                $prescription_id,
                                $service_id,
                                $service_name,
                                $service_price,
                                $quantity,
                                $service_total,
                                $service_notes
                            );
                            
                            if (!$service_stmt->execute()) {
                                throw new Exception("Error saving prescription service: " . $service_stmt->error);
                            }
                            $service_stmt->close();
                        }
                    }
                    
                    // Calculate VAT and update totals
                    $vat_amount = ($total_amount * $vat_percentage) / 100;
                    $grand_total = $total_amount + $vat_amount;
                    
                    // Update prescription with calculated amounts
                    $update_stmt = $conn->prepare("UPDATE customer_prescriptions SET 
                        total_amount = ?, 
                        vat_amount = ?, 
                        grand_total = ?, 
                        medications_total = ?, 
                        services_total = ?,
                        vat_id = ?
                        WHERE prescription_id = ?");
                    
                    $update_stmt->bind_param("ddddidi", 
                        $total_amount, 
                        $vat_amount, 
                        $grand_total, 
                        $medications_total, 
                        $services_total,
                        $vat_id,
                        $prescription_id
                    );
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Log activity
                    $customer_name = "Unknown Customer";
                    $cust_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM customers WHERE customer_id = ?");
                    $cust_stmt->bind_param("i", $customer_id);
                    $cust_stmt->execute();
                    $cust_result = $cust_stmt->get_result();
                    if ($cust_result->num_rows > 0) {
                        $customer_name = $cust_result->fetch_assoc()['name'];
                    }
                    $cust_stmt->close();
                    
                    logActivity($conn, $user_id, $user_name, 'add_prescription', 
                        "Added new prescription #{$prescription_number} for {$customer_name}", 
                        $current_branch_id, null);
                    
                    $response['success'] = true;
                    $response['message'] = "Prescription added successfully!";
                    $response['prescription_id'] = $prescription_id;
                    $response['prescription_number'] = $prescription_number;
                    $response['total_amount'] = $total_amount;
                    $response['vat_amount'] = $vat_amount;
                    $response['grand_total'] = $grand_total;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $response['message'] = "Failed to add prescription: " . $e->getMessage();
                }
                break;
                
            case 'update_prescription':
                $prescription_id = intval($_POST['prescription_id'] ?? 0);
                
                if ($prescription_id <= 0) {
                    $response['message'] = "Invalid prescription ID!";
                    break;
                }
                
                // Check if prescription exists
                $check_stmt = $conn->prepare("SELECT prescription_id FROM customer_prescriptions WHERE prescription_id = ?");
                $check_stmt->bind_param("i", $prescription_id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows === 0) {
                    $response['message'] = "Prescription not found!";
                    $check_stmt->close();
                    break;
                }
                $check_stmt->close();
                
                // Get VAT rate
                $vat_stmt = $conn->prepare("SELECT percentage, vat_id FROM vat_rates WHERE is_default = 1 AND is_active = 1 LIMIT 1");
                $vat_stmt->execute();
                $vat_result = $vat_stmt->get_result();
                $vat_percentage = 16.50;
                $vat_id = 1;
                if ($vat_result->num_rows > 0) {
                    $vat_data = $vat_result->fetch_assoc();
                    $vat_percentage = floatval($vat_data['percentage']);
                    $vat_id = intval($vat_data['vat_id']);
                }
                $vat_stmt->close();
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Update prescription details
                    $doctor_name = trim($_POST['doctor_name'] ?? '');
                    $diagnosis = trim($_POST['diagnosis'] ?? '');
                    $prescription_date = $_POST['prescription_date'] ?? '';
                    $prescription_type = $_POST['prescription_type'] ?? '';
                    $urgency_level = $_POST['urgency_level'] ?? '';
                    $valid_from = $_POST['valid_from'] ?? '';
                    $valid_until = $_POST['valid_until'] ?? '';
                    $refills_allowed = isset($_POST['refills_allowed']) ? intval($_POST['refills_allowed']) : null;
                    $patient_weight = !empty($_POST['patient_weight']) ? floatval($_POST['patient_weight']) : null;
                    $patient_height = !empty($_POST['patient_height']) ? intval($_POST['patient_height']) : null;
                    $symptoms = trim($_POST['symptoms'] ?? '');
                    $instructions = trim($_POST['instructions'] ?? '');
                    $notes = trim($_POST['notes'] ?? '');
                    $allergies_warning = trim($_POST['allergies_warning'] ?? '');
                    $status = $_POST['status'] ?? '';
                    
                    // Build update query dynamically
                    $update_fields = [];
                    $update_params = [];
                    $update_types = "";
                    
                    if (!empty($doctor_name)) {
                        $update_fields[] = "doctor_name = ?";
                        $update_params[] = $doctor_name;
                        $update_types .= "s";
                    }
                    
                    if (!empty($diagnosis)) {
                        $update_fields[] = "diagnosis = ?";
                        $update_params[] = $diagnosis;
                        $update_types .= "s";
                    }
                    
                    if (!empty($prescription_date)) {
                        $update_fields[] = "prescription_date = ?";
                        $update_params[] = $prescription_date;
                        $update_types .= "s";
                    }
                    
                    if (!empty($prescription_type)) {
                        $update_fields[] = "prescription_type = ?";
                        $update_params[] = $prescription_type;
                        $update_types .= "s";
                    }
                    
                    if (!empty($urgency_level)) {
                        $update_fields[] = "urgency_level = ?";
                        $update_params[] = $urgency_level;
                        $update_types .= "s";
                    }
                    
                    if (!empty($valid_from)) {
                        $update_fields[] = "valid_from = ?";
                        $update_params[] = $valid_from;
                        $update_types .= "s";
                    }
                    
                    if (!empty($valid_until)) {
                        $update_fields[] = "valid_until = ?";
                        $update_params[] = $valid_until;
                        $update_types .= "s";
                    }
                    
                    if (isset($refills_allowed)) {
                        $update_fields[] = "refills_allowed = ?";
                        $update_params[] = $refills_allowed;
                        $update_types .= "i";
                    }
                    
                    if (isset($patient_weight)) {
                        $update_fields[] = "patient_weight = ?";
                        $update_params[] = $patient_weight;
                        $update_types .= "d";
                    }
                    
                    if (isset($patient_height)) {
                        $update_fields[] = "patient_height = ?";
                        $update_params[] = $patient_height;
                        $update_types .= "i";
                    }
                    
                    if ($symptoms !== '') {
                        $update_fields[] = "symptoms = ?";
                        $update_params[] = $symptoms;
                        $update_types .= "s";
                    }
                    
                    if ($instructions !== '') {
                        $update_fields[] = "instructions = ?";
                        $update_params[] = $instructions;
                        $update_types .= "s";
                    }
                    
                    if ($notes !== '') {
                        $update_fields[] = "notes = ?";
                        $update_params[] = $notes;
                        $update_types .= "s";
                    }
                    
                    if ($allergies_warning !== '') {
                        $update_fields[] = "allergies_warning = ?";
                        $update_params[] = $allergies_warning;
                        $update_types .= "s";
                    }
                    
                    if (!empty($status)) {
                        $update_fields[] = "status = ?";
                        $update_params[] = $status;
                        $update_types .= "s";
                    }
                    
                    // Add updated_by and updated_at
                    $update_fields[] = "updated_by = ?";
                    $update_fields[] = "updated_at = CURRENT_TIMESTAMP";
                    $update_params[] = $user_id;
                    $update_types .= "i";
                    
                    if (!empty($update_fields)) {
                        $update_sql = "UPDATE customer_prescriptions SET " . implode(", ", $update_fields) . " WHERE prescription_id = ?";
                        $update_params[] = $prescription_id;
                        $update_types .= "i";
                        
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param($update_types, ...$update_params);
                        
                        if (!$update_stmt->execute()) {
                            throw new Exception("Error updating prescription: " . $update_stmt->error);
                        }
                        $update_stmt->close();
                    }
                    
                    // Handle medications and services update if provided
                    $total_amount = 0;
                    $medications_total = 0;
                    $services_total = 0;
                    
                    // Delete existing items
                    $delete_items_stmt = $conn->prepare("DELETE FROM prescription_items WHERE prescription_id = ?");
                    $delete_items_stmt->bind_param("i", $prescription_id);
                    $delete_items_stmt->execute();
                    $delete_items_stmt->close();
                    
                    // Delete existing services
                    $delete_services_stmt = $conn->prepare("DELETE FROM prescription_services WHERE prescription_id = ?");
                    $delete_services_stmt->bind_param("i", $prescription_id);
                    $delete_services_stmt->execute();
                    $delete_services_stmt->close();
                    
                    // Insert new medications
                    if (isset($_POST['medications']) && is_array($_POST['medications'])) {
                        foreach ($_POST['medications'] as $medication) {
                            $medicine_id = !empty($medication['medicine_id']) ? intval($medication['medicine_id']) : null;
                            $medicine_name = trim($medication['medicine_name'] ?? 'Unknown Medicine');
                            $dosage = trim($medication['dosage'] ?? 'N/A');
                            $frequency = trim($medication['frequency'] ?? 'N/A');
                            $duration = intval($medication['duration'] ?? 1);
                            $quantity = intval($medication['quantity'] ?? 1);
                            $med_instructions = trim($medication['instructions'] ?? '');
                            
                            // Get medicine price if medicine_id exists
                            $unit_price = 0;
                            $selling_price = 0;
                            
                            if ($medicine_id) {
                                $price_stmt = $conn->prepare("SELECT unit_price, selling_price FROM medicines WHERE medicine_id = ?");
                                $price_stmt->bind_param("i", $medicine_id);
                                $price_stmt->execute();
                                $price_result = $price_stmt->get_result();
                                
                                if ($price_result->num_rows > 0) {
                                    $price_data = $price_result->fetch_assoc();
                                    $unit_price = floatval($price_data['unit_price'] ?? 0);
                                    $selling_price = floatval($price_data['selling_price'] ?? 0);
                                }
                                $price_stmt->close();
                            }
                            
                            // Calculate item total
                            $item_total = $selling_price * $quantity;
                            $medications_total += $item_total;
                            $total_amount += $item_total;
                            
                            // Insert prescription item
                            $item_stmt = $conn->prepare("INSERT INTO prescription_items (
                                prescription_id, medicine_id, medicine_name, dosage, frequency, 
                                duration, quantity, unit_price, selling_price, total_price, 
                                instructions, dispensed_quantity
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                            
                            $item_stmt->bind_param("iisssiiddds",
                                $prescription_id,
                                $medicine_id,
                                $medicine_name,
                                $dosage,
                                $frequency,
                                $duration,
                                $quantity,
                                $unit_price,
                                $selling_price,
                                $item_total,
                                $med_instructions
                            );
                            
                            if (!$item_stmt->execute()) {
                                throw new Exception("Error saving prescription item: " . $item_stmt->error);
                            }
                            $item_stmt->close();
                        }
                    }
                    
                    // Insert new services
                    if (isset($_POST['services']) && is_array($_POST['services'])) {
                        foreach ($_POST['services'] as $service) {
                            $service_id = !empty($service['service_id']) ? intval($service['service_id']) : null;
                            $service_name = trim($service['service_name'] ?? 'Unknown Service');
                            $service_price = floatval($service['service_price'] ?? 0);
                            $quantity = intval($service['quantity'] ?? 1);
                            $service_notes = trim($service['notes'] ?? '');
                            
                            // Calculate service total
                            $service_total = $service_price * $quantity;
                            $services_total += $service_total;
                            $total_amount += $service_total;
                            
                            // Insert prescription service
                            $service_stmt = $conn->prepare("INSERT INTO prescription_services (
                                prescription_id, service_id, service_name, service_price, 
                                quantity, total_price, notes
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            
                            $service_stmt->bind_param("iisidds",
                                $prescription_id,
                                $service_id,
                                $service_name,
                                $service_price,
                                $quantity,
                                $service_total,
                                $service_notes
                            );
                            
                            if (!$service_stmt->execute()) {
                                throw new Exception("Error saving prescription service: " . $service_stmt->error);
                            }
                            $service_stmt->close();
                        }
                    }
                    
                    // Calculate VAT and update totals
                    $vat_amount = ($total_amount * $vat_percentage) / 100;
                    $grand_total = $total_amount + $vat_amount;
                    
                    // Update financial totals
                    $amount_stmt = $conn->prepare("UPDATE customer_prescriptions SET 
                        total_amount = ?, 
                        vat_amount = ?, 
                        grand_total = ?, 
                        medications_total = ?, 
                        services_total = ?,
                        vat_id = ?
                        WHERE prescription_id = ?");
                    
                    $amount_stmt->bind_param("ddddidi", 
                        $total_amount, 
                        $vat_amount, 
                        $grand_total, 
                        $medications_total, 
                        $services_total,
                        $vat_id,
                        $prescription_id
                    );
                    $amount_stmt->execute();
                    $amount_stmt->close();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Log activity
                    $prescription_stmt = $conn->prepare("SELECT prescription_number FROM customer_prescriptions WHERE prescription_id = ?");
                    $prescription_stmt->bind_param("i", $prescription_id);
                    $prescription_stmt->execute();
                    $prescription_result = $prescription_stmt->get_result();
                    $prescription_number = "Unknown";
                    if ($prescription_result->num_rows > 0) {
                        $prescription_number = $prescription_result->fetch_assoc()['prescription_number'];
                    }
                    $prescription_stmt->close();
                    
                    logActivity($conn, $user_id, $user_name, 'update_prescription', 
                        "Updated prescription #{$prescription_number}", 
                        $current_branch_id, null);
                    
                    $response['success'] = true;
                    $response['message'] = "Prescription updated successfully!";
                    $response['total_amount'] = $total_amount;
                    $response['vat_amount'] = $vat_amount;
                    $response['grand_total'] = $grand_total;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $response['message'] = "Failed to update prescription: " . $e->getMessage();
                }
                break;
                
            case 'dispense_prescription':
                $prescription_id = intval($_POST['prescription_id'] ?? 0);
                $dispense_items = $_POST['dispense_items'] ?? [];
                
                if ($prescription_id <= 0) {
                    $response['message'] = "Invalid prescription ID!";
                    break;
                }
                
                if (empty($dispense_items) || !is_array($dispense_items)) {
                    $response['message'] = "No items to dispense!";
                    break;
                }
                
                // Check if prescription exists and is active
                $check_stmt = $conn->prepare("SELECT prescription_number, status FROM customer_prescriptions WHERE prescription_id = ?");
                $check_stmt->bind_param("i", $prescription_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "Prescription not found!";
                    $check_stmt->close();
                    break;
                }
                
                $prescription_data = $check_result->fetch_assoc();
                if ($prescription_data['status'] !== 'active') {
                    $response['message'] = "Prescription is not active! Current status: " . getStatusText($prescription_data['status']);
                    $check_stmt->close();
                    break;
                }
                $check_stmt->close();
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    $total_dispensed = 0;
                    $dispense_id = null;
                    
                    // Create dispensing record
                    $dispense_stmt = $conn->prepare("INSERT INTO prescription_dispensing (prescription_id, pharmacist_id, dispensing_date, total_amount, notes) VALUES (?, ?, NOW(), 0, ?)");
                    $notes = trim($_POST['dispensing_notes'] ?? '');
                    $dispense_stmt->bind_param("iis", $prescription_id, $user_id, $notes);
                    
                    if (!$dispense_stmt->execute()) {
                        throw new Exception("Error creating dispensing record: " . $dispense_stmt->error);
                    }
                    
                    $dispense_id = $dispense_stmt->insert_id;
                    $dispense_stmt->close();
                    
                    // Process each item
                    foreach ($dispense_items as $item) {
                        $item_id = intval($item['item_id'] ?? 0);
                        $quantity_to_dispense = intval($item['quantity'] ?? 0);
                        
                        if ($item_id <= 0 || $quantity_to_dispense <= 0) {
                            continue;
                        }
                        
                        // Get item details
                        $item_stmt = $conn->prepare("SELECT pi.*, m.current_stock FROM prescription_items pi LEFT JOIN medicines m ON pi.medicine_id = m.medicine_id WHERE pi.item_id = ?");
                        $item_stmt->bind_param("i", $item_id);
                        $item_stmt->execute();
                        $item_result = $item_stmt->get_result();
                        
                        if ($item_result->num_rows === 0) {
                            $item_stmt->close();
                            continue;
                        }
                        
                        $item_data = $item_result->fetch_assoc();
                        $item_stmt->close();
                        
                        // Check if enough stock for medicine
                        if ($item_data['medicine_id'] && $item_data['current_stock'] < $quantity_to_dispense) {
                            throw new Exception("Insufficient stock for " . ($item_data['medicine_name'] ?? 'medicine') . ". Available: " . $item_data['current_stock']);
                        }
                        
                        // Check if not exceeding prescribed quantity
                        $already_dispensed = intval($item_data['dispensed_quantity'] ?? 0);
                        $prescribed_quantity = intval($item_data['quantity'] ?? 0);
                        
                        if ($already_dispensed + $quantity_to_dispense > $prescribed_quantity) {
                            throw new Exception("Cannot dispense more than prescribed quantity for " . ($item_data['medicine_name'] ?? 'medicine') . ". Prescribed: $prescribed_quantity, Already dispensed: $already_dispensed");
                        }
                        
                        // Update item's dispensed quantity
                        $update_item_stmt = $conn->prepare("UPDATE prescription_items SET dispensed_quantity = dispensed_quantity + ? WHERE item_id = ?");
                        $update_item_stmt->bind_param("ii", $quantity_to_dispense, $item_id);
                        $update_item_stmt->execute();
                        $update_item_stmt->close();
                        
                        // Update medicine stock if applicable
                        if ($item_data['medicine_id']) {
                            $update_stock_stmt = $conn->prepare("UPDATE medicines SET current_stock = current_stock - ?, updated_at = NOW() WHERE medicine_id = ?");
                            $update_stock_stmt->bind_param("ii", $quantity_to_dispense, $item_data['medicine_id']);
                            $update_stock_stmt->execute();
                            $update_stock_stmt->close();
                        }
                        
                        // Calculate item total
                        $item_total = floatval($item_data['selling_price'] ?? 0) * $quantity_to_dispense;
                        $total_dispensed += $item_total;
                        
                        // Create dispensing item record
                        $dispense_item_stmt = $conn->prepare("INSERT INTO dispensing_items (dispensing_id, item_id, quantity_dispensed, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                        $dispense_item_stmt->bind_param("iiidd", $dispense_id, $item_id, $quantity_to_dispense, $item_data['selling_price'], $item_total);
                        $dispense_item_stmt->execute();
                        $dispense_item_stmt->close();
                    }
                    
                    // Update dispensing total
                    $update_dispense_stmt = $conn->prepare("UPDATE prescription_dispensing SET total_amount = ? WHERE dispensing_id = ?");
                    $update_dispense_stmt->bind_param("di", $total_dispensed, $dispense_id);
                    $update_dispense_stmt->execute();
                    $update_dispense_stmt->close();
                    
                    // Check if all items are fully dispensed
                    $check_complete_stmt = $conn->prepare("
                        SELECT COUNT(*) as pending_items 
                        FROM prescription_items 
                        WHERE prescription_id = ? AND dispensed_quantity < quantity
                    ");
                    $check_complete_stmt->bind_param("i", $prescription_id);
                    $check_complete_stmt->execute();
                    $check_complete_result = $check_complete_stmt->get_result();
                    $pending_items = $check_complete_result->fetch_assoc()['pending_items'] ?? 0;
                    $check_complete_stmt->close();
                    
                    // Update prescription status if fully dispensed
                    if ($pending_items === 0) {
                        $update_status_stmt = $conn->prepare("UPDATE customer_prescriptions SET status = 'completed', updated_at = NOW(), updated_by = ? WHERE prescription_id = ?");
                        $update_status_stmt->bind_param("ii", $user_id, $prescription_id);
                        $update_status_stmt->execute();
                        $update_status_stmt->close();
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'dispense_prescription', 
                        "Dispensed prescription #{$prescription_data['prescription_number']}", 
                        $current_branch_id, null);
                    
                    $response['success'] = true;
                    $response['message'] = "Prescription dispensed successfully!";
                    $response['dispensing_id'] = $dispense_id;
                    $response['total_amount'] = $total_dispensed;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $response['message'] = "Failed to dispense prescription: " . $e->getMessage();
                }
                break;
                
            case 'update_status':
                $prescription_id = intval($_POST['prescription_id'] ?? 0);
                $new_status = trim($_POST['status'] ?? '');
                
                if ($prescription_id <= 0) {
                    $response['message'] = "Invalid prescription ID!";
                    break;
                }
                
                if (empty($new_status)) {
                    $response['message'] = "No status provided!";
                    break;
                }
                
                $valid_statuses = ['active', 'pending', 'completed', 'expired', 'cancelled'];
                if (!in_array($new_status, $valid_statuses)) {
                    $response['message'] = "Invalid status!";
                    break;
                }
                
                // Check if prescription exists
                $check_stmt = $conn->prepare("SELECT prescription_number, status FROM customer_prescriptions WHERE prescription_id = ?");
                $check_stmt->bind_param("i", $prescription_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "Prescription not found!";
                    $check_stmt->close();
                    break;
                }
                
                $prescription_data = $check_result->fetch_assoc();
                $old_status = $prescription_data['status'];
                $check_stmt->close();
                
                // Update status
                $update_stmt = $conn->prepare("UPDATE customer_prescriptions SET status = ?, updated_by = ?, updated_at = NOW() WHERE prescription_id = ?");
                $update_stmt->bind_param("sii", $new_status, $user_id, $prescription_id);
                
                if ($update_stmt->execute()) {
                    // Log activity
                    logActivity($conn, $user_id, $user_name, 'update_prescription_status', 
                        "Updated prescription #{$prescription_data['prescription_number']} status from " . 
                        getStatusText($old_status) . " to " . getStatusText($new_status), 
                        $current_branch_id, null);
                    
                    $response['success'] = true;
                    $response['message'] = "Status updated successfully!";
                } else {
                    $response['message'] = "Failed to update status: " . $update_stmt->error;
                }
                $update_stmt->close();
                break;
                
            case 'delete_prescription':
                $prescription_id = intval($_POST['prescription_id'] ?? 0);
                
                if ($prescription_id <= 0) {
                    $response['message'] = "Invalid prescription ID!";
                    break;
                }
                
                // Check if prescription exists and can be deleted
                $check_stmt = $conn->prepare("SELECT prescription_number, status FROM customer_prescriptions WHERE prescription_id = ?");
                $check_stmt->bind_param("i", $prescription_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    $response['message'] = "Prescription not found!";
                    $check_stmt->close();
                    break;
                }
                
                $prescription_data = $check_result->fetch_assoc();
                $check_stmt->close();
                
                // Don't allow deletion of dispensed prescriptions
                $dispensed_check = $conn->prepare("SELECT COUNT(*) as dispensed_count FROM prescription_items WHERE prescription_id = ? AND dispensed_quantity > 0");
                $dispensed_check->bind_param("i", $prescription_id);
                $dispensed_check->execute();
                $dispensed_result = $dispensed_check->get_result();
                $dispensed_count = $dispensed_result->fetch_assoc()['dispensed_count'] ?? 0;
                $dispensed_check->close();
                
                if ($dispensed_count > 0) {
                    $response['message'] = "Cannot delete prescription with dispensed medications!";
                    break;
                }
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Delete prescription items first
                    $delete_items_stmt = $conn->prepare("DELETE FROM prescription_items WHERE prescription_id = ?");
                    $delete_items_stmt->bind_param("i", $prescription_id);
                    $delete_items_stmt->execute();
                    $delete_items_stmt->close();
                    
                    // Delete prescription services
                    $delete_services_stmt = $conn->prepare("DELETE FROM prescription_services WHERE prescription_id = ?");
                    $delete_services_stmt->bind_param("i", $prescription_id);
                    $delete_services_stmt->execute();
                    $delete_services_stmt->close();
                    
                    // Delete prescription
                    $delete_stmt = $conn->prepare("DELETE FROM customer_prescriptions WHERE prescription_id = ?");
                    $delete_stmt->bind_param("i", $prescription_id);
                    
                    if ($delete_stmt->execute()) {
                        // Log activity
                        logActivity($conn, $user_id, $user_name, 'delete_prescription', 
                            "Deleted prescription #{$prescription_data['prescription_number']}", 
                                                        $current_branch_id, null);
                        
                        $conn->commit();
                        
                        $response['success'] = true;
                        $response['message'] = "Prescription deleted successfully!";
                    } else {
                        throw new Exception("Error deleting prescription: " . $delete_stmt->error);
                    }
                    $delete_stmt->close();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $response['message'] = "Failed to delete prescription: " . $e->getMessage();
                }
                break;
                
            case 'export_prescriptions':
                // Build query based on filters
                $where_clauses = ["1=1"];
                $params = [];
                $types = "";
                
                // Apply filters
                if (!empty($_POST['search'])) {
                    $where_clauses[] = "(cp.prescription_number LIKE ? OR COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Unknown Patient') LIKE ? OR cp.doctor_name LIKE ? OR cp.diagnosis LIKE ? OR COALESCE(cp.symptoms, '') LIKE ?)";
                    $search_term = "%" . trim($_POST['search']) . "%";
                    for ($i = 0; $i < 5; $i++) {
                        $params[] = $search_term;
                        $types .= "s";
                    }
                }
                
                if (!empty($_POST['status'])) {
                    $where_clauses[] = "cp.status = ?";
                    $params[] = $_POST['status'];
                    $types .= "s";
                }
                
                if (!empty($_POST['type'])) {
                    $where_clauses[] = "cp.prescription_type = ?";
                    $params[] = $_POST['type'];
                    $types .= "s";
                }
                
                if (!empty($_POST['urgency'])) {
                    $where_clauses[] = "cp.urgency_level = ?";
                    $params[] = $_POST['urgency'];
                    $types .= "s";
                }
                
                // Filter by date range for export
                if (!empty($_POST['export_date_from'])) {
                    $where_clauses[] = "cp.prescription_date >= ?";
                    $params[] = $_POST['export_date_from'];
                    $types .= "s";
                }
                
                if (!empty($_POST['export_date_to'])) {
                    $where_clauses[] = "cp.prescription_date <= ?";
                    $params[] = $_POST['export_date_to'];
                    $types .= "s";
                }
                
                // Filter by branch through customers
                if ($user_role !== 'admin' && $current_branch_id) {
                    $where_clauses[] = "COALESCE(c.branch_id, 0) = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $where_sql = implode(" AND ", $where_clauses);
                
                // Get data for export
                $sql = "SELECT 
                    cp.prescription_number,
                    COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Unknown Patient') as patient_name,
                    COALESCE(c.customer_code, 'N/A') as customer_code,
                    COALESCE(c.phone, 'N/A') as phone,
                    cp.doctor_name,
                    cp.diagnosis,
                    cp.prescription_type,
                    cp.urgency_level,
                    cp.status,
                    cp.prescription_date,
                    cp.valid_from,
                    cp.valid_until,
                    cp.total_amount,
                    cp.vat_amount,
                    cp.grand_total,
                    (SELECT COUNT(*) FROM prescription_items WHERE prescription_id = cp.prescription_id) as medication_count,
                    COALESCE(cp.notes, '') as notes
                    FROM customer_prescriptions cp
                    LEFT JOIN customers c ON cp.customer_id = c.customer_id
                    WHERE $where_sql 
                    ORDER BY cp.prescription_date DESC";
                
                $stmt = $conn->prepare($sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    // Format data for export
                    $row['prescription_type'] = getPrescriptionTypeText($row['prescription_type']);
                    $row['urgency_level'] = getUrgencyText($row['urgency_level']);
                    $row['status'] = getStatusText($row['status']);
                    
                    // Format dates
                    $row['prescription_date'] = date('d/m/Y', strtotime($row['prescription_date']));
                    $row['valid_from'] = date('d/m/Y', strtotime($row['valid_from']));
                    $row['valid_until'] = date('d/m/Y', strtotime($row['valid_until']));
                    
                    // Format monetary values
                    $row['total_amount'] = number_format($row['total_amount'], 2);
                    $row['vat_amount'] = number_format($row['vat_amount'], 2);
                    $row['grand_total'] = number_format($row['grand_total'], 2);
                    
                    $data[] = $row;
                }
                
                $stmt->close();
                
                // Prepare CSV export
                if (!empty($data)) {
                    $filename = 'prescriptions_export_' . date('Y-m-d_H-i-s') . '.csv';
                    
                    // Set headers for CSV download
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    
                    // Open output stream
                    $output = fopen('php://output', 'w');
                    
                    // Add UTF-8 BOM for Excel compatibility
                    fwrite($output, "\xEF\xBB\xBF");
                    
                    // Add headers
                    $headers = [
                        'Prescription Number',
                        'Patient Name',
                        'Customer Code',
                        'Phone',
                        'Doctor Name',
                        'Diagnosis',
                        'Prescription Type',
                        'Urgency Level',
                        'Status',
                        'Prescription Date',
                        'Valid From',
                        'Valid Until',
                        'Total Amount',
                        'VAT Amount',
                        'Grand Total',
                        'Medication Count',
                        'Notes'
                    ];
                    
                    fputcsv($output, $headers);
                    
                    // Add data rows
                    foreach ($data as $row) {
                        fputcsv($output, [
                            $row['prescription_number'],
                            $row['patient_name'],
                            $row['customer_code'],
                            $row['phone'],
                            $row['doctor_name'],
                            $row['diagnosis'],
                            $row['prescription_type'],
                            $row['urgency_level'],
                            $row['status'],
                            $row['prescription_date'],
                            $row['valid_from'],
                            $row['valid_until'],
                            $row['total_amount'],
                            $row['vat_amount'],
                            $row['grand_total'],
                            $row['medication_count'],
                            $row['notes']
                        ]);
                    }
                    
                    fclose($output);
                    exit();
                } else {
                    $response['message'] = "No data to export!";
                }
                break;
                
            default:
                $response['message'] = "Invalid action requested!";
                break;
        }
        
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $action = $_GET['action'];
        
        switch ($action) {
            case 'get_recent_prescriptions':
                // Get recent prescriptions for dashboard
                $limit = intval($_GET['limit'] ?? 10);
                
                $sql = "SELECT 
                    cp.prescription_id,
                    cp.prescription_number,
                    COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Unknown Patient') as patient_name,
                    cp.doctor_name,
                    cp.diagnosis,
                    cp.prescription_type,
                    cp.urgency_level,
                    cp.status,
                    cp.prescription_date,
                    cp.grand_total
                    FROM customer_prescriptions cp
                    LEFT JOIN customers c ON cp.customer_id = c.customer_id
                    WHERE 1=1";
                
                $params = [];
                $types = "";
                
                // Filter by branch
                if ($user_role !== 'admin' && $current_branch_id) {
                    $sql .= " AND COALESCE(c.branch_id, 0) = ?";
                    $params[] = $current_branch_id;
                    $types .= "i";
                }
                
                $sql .= " ORDER BY cp.prescription_date DESC, cp.prescription_id DESC LIMIT ?";
                $params[] = $limit;
                $types .= "i";
                
                $stmt = $conn->prepare($sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                $prescriptions = [];
                while ($row = $result->fetch_assoc()) {
                    $row['prescription_type_text'] = getPrescriptionTypeText($row['prescription_type']);
                    $row['urgency_level_text'] = getUrgencyText($row['urgency_level']);
                    $row['status_text'] = getStatusText($row['status']);
                    $row['prescription_date_formatted'] = date('d/m/Y', strtotime($row['prescription_date']));
                    $row['grand_total_formatted'] = number_format($row['grand_total'], 2);
                    
                    $prescriptions[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $prescriptions;
                $stmt->close();
                break;
                
            case 'get_prescription_print':
                $prescription_id = intval($_GET['prescription_id'] ?? 0);
                
                if ($prescription_id <= 0) {
                    $response['message'] = "Invalid prescription ID!";
                    break;
                }
                
                // Get prescription details for printing
                $stmt = $conn->prepare("
                    SELECT cp.*, 
                    COALESCE(c.first_name, 'Unknown') as first_name, 
                    COALESCE(c.last_name, 'Patient') as last_name, 
                    COALESCE(c.customer_code, 'N/A') as customer_code, 
                    COALESCE(c.phone, 'N/A') as phone, 
                    COALESCE(c.email, 'N/A') as email, 
                    c.date_of_birth,
                    COALESCE(c.gender, 'N/A') as patient_gender, 
                    COALESCE(c.allergies, 'No allergies reported') as allergies, 
                    COALESCE(c.medical_conditions, 'No medical conditions') as medical_conditions,
                    COALESCE(b.branch_name, 'Main Branch') as branch_name,
                    COALESCE(b.address, '') as branch_address,
                    COALESCE(b.phone, '') as branch_phone,
                    COALESCE(u.full_name, 'Unknown User') as created_by_name
                    FROM customer_prescriptions cp
                    LEFT JOIN customers c ON cp.customer_id = c.customer_id
                    LEFT JOIN branches b ON c.branch_id = b.branch_id
                    LEFT JOIN users u ON cp.created_by = u.user_id
                    WHERE cp.prescription_id = ?
                ");
                
                $stmt->bind_param("i", $prescription_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $prescription = $result->fetch_assoc();
                    
                    // Get prescription items
                    $items_stmt = $conn->prepare("
                        SELECT pi.*, 
                        COALESCE(m.medicine_name, pi.medicine_name, 'Unknown Medicine') as full_name, 
                        COALESCE(m.strength, 'N/A') as strength, 
                        COALESCE(m.dosage_form, 'N/A') as dosage_form
                        FROM prescription_items pi
                        LEFT JOIN medicines m ON pi.medicine_id = m.medicine_id
                        WHERE pi.prescription_id = ?
                        ORDER BY pi.item_id
                    ");
                    
                    $items_stmt->bind_param("i", $prescription_id);
                    $items_stmt->execute();
                    $items_result = $items_stmt->get_result();
                    
                    $medications = [];
                    while ($item = $items_result->fetch_assoc()) {
                        $medications[] = $item;
                    }
                    $items_stmt->close();
                    
                    // Calculate patient age
                    if ($prescription['date_of_birth']) {
                        $birth_date = new DateTime($prescription['date_of_birth']);
                        $today = new DateTime();
                        $age = $today->diff($birth_date)->y;
                        $prescription['patient_age'] = $age;
                    } else {
                        $prescription['patient_age'] = 'N/A';
                    }
                    
                    // Format data
                    $prescription['prescription_type_text'] = getPrescriptionTypeText($prescription['prescription_type']);
                    $prescription['urgency_level_text'] = getUrgencyText($prescription['urgency_level']);
                    $prescription['status_text'] = getStatusText($prescription['status']);
                    
                    $response['success'] = true;
                    $response['data'] = $prescription;
                    $response['medications'] = $medications;
                    $response['print_date'] = date('d/m/Y H:i:s');
                    
                } else {
                    $response['message'] = "Prescription not found!";
                }
                $stmt->close();
                break;
                
            default:
                $response['message'] = "Invalid action requested!";
                break;
        }
        
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Close connection
$conn->close();
?>