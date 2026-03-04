<<<<<<< HEAD
<?php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'add_user':
            // Validate required fields
            $required = ['first_name', 'surname', 'username', 'email', 'role', 'password', 'confirm_password'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $response['message'] = "All required fields must be filled!";
                    echo json_encode($response);
                    exit;
                }
            }
            
            // Check if passwords match
            if ($_POST['password'] !== $_POST['confirm_password']) {
                $response['message'] = "Passwords do not match!";
                echo json_encode($response);
                exit;
            }
            
            // Check password length
            if (strlen($_POST['password']) < 8) {
                $response['message'] = "Password must be at least 8 characters long!";
                echo json_encode($response);
                exit;
            }
            
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $_POST['username']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $response['message'] = "Username already exists!";
                $check_stmt->close();
                echo json_encode($response);
                exit;
            }
            $check_stmt->close();
            
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $_POST['email']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $response['message'] = "Email already exists!";
                $check_stmt->close();
                echo json_encode($response);
                exit;
            }
            $check_stmt->close();
            
            // Hash password
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, gender, email, phone, date_of_birth, address, emergency_contact, emergency_phone, notes, branch_id, role, status, hire_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Build full name
            $full_name = $_POST['first_name'] . ' ' . $_POST['surname'];
            $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
            $status = $_POST['status'] ?? 'active';
            $gender = $_POST['gender'] ?? null;
            $phone = $_POST['phone'] ?? null;
            $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            $address = $_POST['address'] ?? null;
            $emergency_contact = $_POST['emergency_contact'] ?? null;
            $emergency_phone = $_POST['emergency_phone'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : date('Y-m-d');
            
            $stmt->bind_param("sssssssssssisssi", 
                $_POST['username'],
                $hashed_password,
                $full_name,
                $gender,
                $_POST['email'],
                $phone,
                $date_of_birth,
                $address,
                $emergency_contact,
                $emergency_phone,
                $notes,
                $branch_id,
                $_POST['role'],
                $status,
                $hire_date,
                $user_id
            );
            
            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                
                // Log activity
                logActivity($conn, $user_id, $user_name, 'ADD_USER', 
                    "Added new user: {$full_name} ({$_POST['username']})", 
                    $branch_id, getBranchName($conn, $branch_id));
                
                $response['success'] = true;
                $response['message'] = "User added successfully!";
                $response['user_id'] = $new_user_id;
            } else {
                $response['message'] = "Error adding user: " . $stmt->error;
                $response['error'] = $stmt->error;
                $response['errno'] = $stmt->errno;
            }
            $stmt->close();
            break;
            
        case 'edit_user':
            $user_id_edit = intval($_POST['user_id']);
            
            // Check if user exists
            $check_stmt = $conn->prepare("SELECT username, full_name, email, branch_id FROM users WHERE user_id = ?");
            $check_stmt->bind_param("i", $user_id_edit);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $response['message'] = "User not found!";
                $check_stmt->close();
                echo json_encode($response);
                exit;
            }
            
            $user_data = $check_result->fetch_assoc();
            $check_stmt->close();
            
            // Check if username changed and if new username exists
            if ($user_data['username'] !== $_POST['username']) {
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
                $check_stmt->bind_param("si", $_POST['username'], $user_id_edit);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    $response['message'] = "Username already exists!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
            }
            
            // Check if email changed and if new email exists
            if ($user_data['email'] !== $_POST['email']) {
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $check_stmt->bind_param("si", $_POST['email'], $user_id_edit);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    $response['message'] = "Email already exists!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
            }
            
            // Build full name
            $full_name = $_POST['first_name'] . ' ' . $_POST['surname'];
            $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
            $gender = $_POST['gender'] ?? null;
            $phone = $_POST['phone'] ?? null;
            $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            $address = $_POST['address'] ?? null;
            $emergency_contact = $_POST['emergency_contact'] ?? null;
            $emergency_phone = $_POST['emergency_phone'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;
            
            // Check if password is being updated
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $response['message'] = "Passwords do not match!";
                    echo json_encode($response);
                    exit;
                }
                
                if (strlen($_POST['password']) < 8) {
                    $response['message'] = "Password must be at least 8 characters long!";
                    echo json_encode($response);
                    exit;
                }
                
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Update with password
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, full_name = ?, gender = ?, email = ?, phone = ?, date_of_birth = ?, address = ?, emergency_contact = ?, emergency_phone = ?, notes = ?, branch_id = ?, role = ?, status = ?, hire_date = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->bind_param("sssssssssssisssi", 
                    $_POST['username'],
                    $hashed_password,
                    $full_name,
                    $gender,
                    $_POST['email'],
                    $phone,
                    $date_of_birth,
                    $address,
                    $emergency_contact,
                    $emergency_phone,
                    $notes,
                    $branch_id,
                    $_POST['role'],
                    $_POST['status'],
                    $hire_date,
                    $user_id_edit
                );
            } else {
                // Update without password
                $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, gender = ?, email = ?, phone = ?, date_of_birth = ?, address = ?, emergency_contact = ?, emergency_phone = ?, notes = ?, branch_id = ?, role = ?, status = ?, hire_date = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->bind_param("ssssssssssisssi", 
                    $_POST['username'],
                    $full_name,
                    $gender,
                    $_POST['email'],
                    $phone,
                    $date_of_birth,
                    $address,
                    $emergency_contact,
                    $emergency_phone,
                    $notes,
                    $branch_id,
                    $_POST['role'],
                    $_POST['status'],
                    $hire_date,
                    $user_id_edit
                );
            }
            
            if ($stmt->execute()) {
                // Log activity
                logActivity($conn, $user_id, $user_name, 'EDIT_USER', 
                    "Updated user: {$full_name} ({$_POST['username']})", 
                    $branch_id, getBranchName($conn, $branch_id));
                
                $response['success'] = true;
                $response['message'] = "User updated successfully!";
            } else {
                $response['message'] = "Error updating user: " . $stmt->error;
                $response['error'] = $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'delete_user':
            $user_id_delete = intval($_POST['user_id']);
            
            // Check if trying to delete self
            if ($user_id_delete === $user_id) {
                $response['message'] = "You cannot delete your own account!";
                echo json_encode($response);
                exit;
            }
            
            // Get user info for logging
            $user_stmt = $conn->prepare("SELECT username, full_name, branch_id FROM users WHERE user_id = ?");
            $user_stmt->bind_param("i", $user_id_delete);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows === 0) {
                $response['message'] = "User not found!";
                $user_stmt->close();
                echo json_encode($response);
                exit;
            }
            
            $user_data = $user_result->fetch_assoc();
            $user_stmt->close();
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id_delete);
            
            if ($stmt->execute()) {
                // Log activity
                logActivity($conn, $user_id, $user_name, 'DELETE_USER', 
                    "Deleted user: {$user_data['full_name']} ({$user_data['username']})", 
                    $user_data['branch_id'], getBranchName($conn, $user_data['branch_id']));
                
                $response['success'] = true;
                $response['message'] = "User deleted successfully!";
            } else {
                $response['message'] = "Error deleting user: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'toggle_user_status':
            $user_id_toggle = intval($_POST['user_id']);
            $new_status = $_POST['status'];
            
            // Check if trying to toggle self
            if ($user_id_toggle === $user_id) {
                $response['message'] = "You cannot change your own status!";
                echo json_encode($response);
                exit;
            }
            
            // Get user info for logging
            $user_stmt = $conn->prepare("SELECT username, full_name, branch_id FROM users WHERE user_id = ?");
            $user_stmt->bind_param("i", $user_id_toggle);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows === 0) {
                $response['message'] = "User not found!";
                $user_stmt->close();
                echo json_encode($response);
                exit;
            }
            
            $user_data = $user_result->fetch_assoc();
            $user_stmt->close();
            
            // Update user status
            $stmt = $conn->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->bind_param("si", $new_status, $user_id_toggle);
            
            if ($stmt->execute()) {
                $action = $new_status === 'active' ? 'ENABLE_USER' : 'DISABLE_USER';
                $action_text = $new_status === 'active' ? 'Enabled' : 'Disabled';
                
                // Log activity
                logActivity($conn, $user_id, $user_name, $action, 
                    "{$action_text} user: {$user_data['full_name']} ({$user_data['username']})", 
                    $user_data['branch_id'], getBranchName($conn, $user_data['branch_id']));
                
                $response['success'] = true;
                $response['message'] = "User status updated successfully!";
            } else {
                $response['message'] = "Error updating user status: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'get_user':
            $user_id_get = intval($_POST['user_id']);
            
            $stmt = $conn->prepare("SELECT *, DATE(date_of_birth) as date_of_birth, DATE(hire_date) as hire_date FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id_get);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Split full name into first name and surname
                $name_parts = explode(' ', $user['full_name'], 2);
                $user['first_name'] = $name_parts[0] ?? '';
                $user['surname'] = $name_parts[1] ?? '';
                
                $response['success'] = true;
                $response['data'] = $user;
            } else {
                $response['message'] = "User not found!";
            }
            $stmt->close();
            break;
            
        case 'get_users':
            // Build query based on filters and user role
            $where_clauses = [];
            $params = [];
            $types = "";
            
            // If not admin, show only users from same branch
            if ($user_role !== 'admin' && $current_branch_id) {
                $where_clauses[] = "u.branch_id = ?";
                $params[] = $current_branch_id;
                $types .= "i";
            }
            
            // Apply filters
            if (!empty($_POST['search'])) {
                $where_clauses[] = "(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                $search_term = "%{$_POST['search']}%";
                for ($i = 0; $i < 3; $i++) {
                    $params[] = $search_term;
                    $types .= "s";
                }
            }
            
            if (!empty($_POST['status'])) {
                $where_clauses[] = "u.status = ?";
                $params[] = $_POST['status'];
                $types .= "s";
            }
            
            if (!empty($_POST['role'])) {
                $where_clauses[] = "u.role = ?";
                $params[] = $_POST['role'];
                $types .= "s";
            }
            
            if (!empty($_POST['branch'])) {
                $where_clauses[] = "u.branch_id = ?";
                $params[] = intval($_POST['branch']);
                $types .= "i";
            }
            
            $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM users u $where_sql";
            $count_stmt = $conn->prepare($count_sql);
            
            if (!empty($params)) {
                $count_stmt->bind_param($types, ...$params);
            }
            
            $count_stmt->execute();
            $count_result = $count_stmt->get_result()->fetch_assoc();
            $total_users = $count_result['total'];
            $count_stmt->close();
            
            // Get users with sorting
            $sort_by = $_POST['sort'] ?? 'u.full_name ASC';
            $allowed_sorts = [
                'name_asc' => 'u.full_name ASC',
                'name_desc' => 'u.full_name DESC',
                'date_new' => 'u.created_at DESC',
                'date_old' => 'u.created_at ASC',
                'role' => 'u.role ASC'
            ];
            
            $sort_sql = $allowed_sorts[$_POST['sort'] ?? 'name_asc'] ?? 'u.full_name ASC';
            
            // Pagination
            $page = intval($_POST['page'] ?? 1);
            $limit = intval($_POST['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT u.*, b.branch_name, 
                    (SELECT full_name FROM users WHERE user_id = u.created_by) as created_by_name
                    FROM users u
                    LEFT JOIN branches b ON u.branch_id = b.branch_id
                    $where_sql 
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
            
            $users = [];
            $sn = $offset + 1;
            while ($row = $result->fetch_assoc()) {
                $row['sn'] = $sn++;
                
                // Generate photo URL
                $row['photo'] = "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']) . "&background=" . getRoleColor($row['role']) . "&color=fff";
                
                // Format dates
                $row['created_at_formatted'] = date('M d, Y', strtotime($row['created_at']));
                $row['hire_date_formatted'] = !empty($row['hire_date']) ? date('M d, Y', strtotime($row['hire_date'])) : 'Not set';
                
                $users[] = $row;
            }
            
            $response['success'] = true;
            $response['data'] = $users;
            $response['total'] = $total_users;
            $response['page'] = $page;
            $response['pages'] = ceil($total_users / $limit);
            
            $stmt->close();
            break;
            
        default:
            $response['message'] = "Invalid action!";
    }
    
    echo json_encode($response);
    exit;
}

// Helper function to get branch name
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

// Helper function to get role color for avatar - UPDATED WITH ONLY 4 ROLES
function getRoleColor($role) {
    $colors = [
        'admin' => '2a5c8b',
        'receptionist' => '1abc9c',
        'clinician' => '9b59b6',
        'dental_staff' => 'e74c3c'
    ];
    
    return $colors[$role] ?? '2a5c8b'; // Default to admin color if role not found
}

// Get initial users data for page load
$initial_users = [];
$initial_stats = [
    'total' => 0,
    'active' => 0,
    'admin' => 0,
    'branches' => 0
];

// Build query based on user role
if ($user_role === 'admin') {
    // Admin sees all users
    $sql = "SELECT u.*, b.branch_name FROM users u
            LEFT JOIN branches b ON u.branch_id = b.branch_id
            ORDER BY u.full_name ASC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    $sn = 1;
    while ($row = $result->fetch_assoc()) {
        $row['sn'] = $sn++;
        $row['photo'] = "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']) . "&background=" . getRoleColor($row['role']) . "&color=fff";
        $initial_users[] = $row;
    }
    
    // Get statistics
    $stats_sql = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                  SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
                  COUNT(DISTINCT branch_id) as branches
                  FROM users";
    $stats_result = $conn->query($stats_sql);
    if ($stats_row = $stats_result->fetch_assoc()) {
        $initial_stats = $stats_row;
    }
} else {
    // Non-admin users see only users from their branch
    if ($current_branch_id) {
        $sql = "SELECT u.*, b.branch_name FROM users u
                LEFT JOIN branches b ON u.branch_id = b.branch_id
                WHERE u.branch_id = ?
                ORDER BY u.full_name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $current_branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sn = 1;
        while ($row = $result->fetch_assoc()) {
            $row['sn'] = $sn++;
            $row['photo'] = "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']) . "&background=" . getRoleColor($row['role']) . "&color=fff";
            $initial_users[] = $row;
        }
        $stmt->close();
        
        // Get statistics for this branch
        $stats_sql = "SELECT 
                      COUNT(*) as total,
                      SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                      SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
                      1 as branches
                      FROM users WHERE branch_id = ?";
        
        $stmt = $conn->prepare($stats_sql);
        $stmt->bind_param("i", $current_branch_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        if ($stats_row = $stats_result->fetch_assoc()) {
            $initial_stats = $stats_row;
        }
        $stmt->close();
    }
}

// Get branches for filter (admin sees all, others see only their branch)
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

$branches = [];
if ($branches_result) {
    while ($row = $branches_result->fetch_assoc()) {
        $branches[] = $row;
    }
}
?>

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

            <!-- Include SweetAlert -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <!-- Include jQuery and Select2 -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

            <style>
              /* ================== Users Management Styles ============== */
                .users-management-section {
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
                .users-stats {
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

                /* Users Table */
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

                .users-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .users-table thead {
                    background: var(--light-gray);
                }

                .users-table th {
                    padding: 18px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    border-bottom: 2px solid var(--gray);
                }

                .users-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.3s ease;
                }

                .users-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .users-table td {
                    padding: 15px 20px;
                    color: var(--black);
                    font-size: 0.95rem;
                    vertical-align: middle;
                }

                /* User Photo */
                .user-photo {
                    width: 45px;
                    height: 45px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 2px solid var(--gray);
                }

                .user-photo img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                /* Status Badges */
                .user-status {
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

                .status-onleave {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                /* Role Badges - UPDATED FOR 4 ROLES */
                .role-badge {
                    display: inline-block;
                    padding: 5px 12px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .role-admin {
                    background: rgba(42, 92, 139, 0.1);
                    color: #2a5c8b;
                }

                .role-receptionist {
                    background: rgba(26, 188, 156, 0.1);
                    color: #1abc9c;
                }

                .role-clinician {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .role-dental_staff {
                    background: rgba(231, 76, 60, 0.1);
                    color: #e74c3c;
                }

                /* Actions */
                .user-actions {
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

                .action-icon.disable {
                    background: rgba(243, 156, 18, 0.1);
                    color: var(--warning);
                }

                .action-icon.disable:hover {
                    background: var(--warning);
                    color: white;
                    transform: translateY(-2px);
                }

                .action-icon.enable {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .action-icon.enable:hover {
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
                    max-width: 700px;
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

                /* Photo Upload */
                .photo-upload-section {
                    grid-column: 1 / -1;
                    display: flex;
                    align-items: center;
                    gap: 30px;
                    margin-bottom: 10px;
                }

                .photo-preview {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 3px solid var(--gray);
                    position: relative;
                }

                .photo-preview img {
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

                /* Select2 Styling */
                .select2-container {
                    width: 100% !important;
                }

                .select2-selection--multiple {
                    border: 1px solid var(--gray) !important;
                    border-radius: 8px !important;
                    min-height: 46px !important;
                    padding: 6px 10px !important;
                }

                .select2-selection--multiple:focus {
                    outline: none;
                    border-color: var(--primary) !important;
                    box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1) !important;
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
                @media (max-width: 1200px) {
                    .modal-form {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 768px) {
                    .users-management-section {
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

                    .users-stats {
                        grid-template-columns: repeat(2, 1fr);
                    }

                    .stat-card {
                        flex-direction: column;
                        text-align: center;
                        gap: 15px;
                    }

                    .table-header {
                        flex-direction: column;
                        gap: 15px;
                        align-items: flex-start;
                    }

                    .table-actions {
                        width: 100%;
                        justify-content: flex-end;
                    }

                    .users-table {
                        display: block;
                        overflow-x: auto;
                    }

                    .photo-upload-section {
                        flex-direction: column;
                        text-align: center;
                        gap: 20px;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }

                    .user-actions {
                        flex-direction: column;
                        gap: 5px;
                    }

                    .action-icon {
                        width: 30px;
                        height: 30px;
                        font-size: 1rem;
                    }
                }

                @media (max-width: 480px) {
                    .users-stats {
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

                    .modal-body {
                        padding: 20px;
                    }
                }
            </style>

            <!-- ================== Users Management Content ============== -->
            <div class="users-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>User Management</h1>
                        <p>Manage system users, roles, and permissions</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshUsers()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <?php if ($user_role === 'admin'): ?>
                        <button class="action-btn primary" onclick="openUserManagementModal()" id="addUserBtn">
                            <ion-icon name="person-add-outline"></ion-icon>
                            Add New User
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Users Statistics -->
                <div class="users-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalUsers"><?php echo $initial_stats['total']; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeUsers"><?php echo $initial_stats['active']; ?></h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="shield-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="adminUsers"><?php echo $initial_stats['admin']; ?></h3>
                            <p>Admin Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="business-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="branchesCovered"><?php echo $initial_stats['branches']; ?></h3>
                            <p>Branches Covered</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search users by name, email, or role..." 
                               onkeyup="filterUsers()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterUsers()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Role</label>
                            <select id="roleFilter" onchange="filterUsers()">
                                <option value="">All Roles</option>
                                <option value="admin">Administrator</option>
                                <option value="receptionist">Receptionist</option>
                                <option value="clinician">Clinician</option>
                                <option value="dental_staff">Dental Staff</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Branch</label>
                            <select id="branchFilter" onchange="filterUsers()">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['branch_id']; ?>">
                                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterUsers()">
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="date_new">Date Added (Newest)</option>
                                <option value="date_old">Date Added (Oldest)</option>
                                <option value="role">Role</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <?php if ($user_role === 'admin'): ?>
                        <button class="action-btn secondary" onclick="resetFilters()">
                            <ion-icon name="close-circle-outline"></ion-icon>
                            Clear Filters
                        </button>
                        <button class="action-btn primary" onclick="applyFilters()">
                            <ion-icon name="filter-outline"></ion-icon>
                            Apply Filters
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>System Users</h2>
                        <div class="table-actions">
                            <?php if ($user_role === 'admin'): ?>
                            <button class="export-btn" onclick="exportUsers()">
                                <ion-icon name="download-outline"></ion-icon>
                                Export CSV
                            </button>
                            <button class="export-btn" onclick="printUsers()">
                                <ion-icon name="print-outline"></ion-icon>
                                Print
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>PHOTO</th>
                                    <th>FULL NAME</th>
                                    <th>GENDER</th>
                                    <th>ROLE</th>
                                    <th>WORK STATION</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php if (empty($initial_users)): ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <ion-icon name="people-outline"></ion-icon>
                                        <h3>No users found</h3>
                                        <p><?php echo $user_role === 'admin' ? 'Add your first user!' : 'No users in your branch.'; ?></p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_users as $user): ?>
                                <?php 
                                    $statusClass = 'status-' . $user['status'];
                                    $statusText = ucfirst($user['status']);
                                    $roleClass = 'role-' . $user['role'];
                                    $roleText = ucfirst(str_replace('_', ' ', $user['role']));
                                    $workStation = $user['branch_name'] ?? 'Not assigned';
                                ?>
                                <tr>
                                    <td><?php echo $user['sn']; ?></td>
                                    <td>
                                        <div class="user-photo">
                                            <img src="<?php echo $user['photo']; ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                        <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($user['email']); ?></small><br>
                                        <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($user['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['gender'] ?? 'Not set'); ?></td>
                                    <td><span class="role-badge <?php echo $roleClass; ?>"><?php echo $roleText; ?></span></td>
                                    <td><?php echo htmlspecialchars($workStation); ?></td>
                                    <td><span class="user-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="action-icon view" title="View Details" onclick="viewUser(<?php echo $user['user_id']; ?>)">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </button>
                                            <?php if ($user_role === 'admin'): ?>
                                            <button class="action-icon edit" title="Edit" onclick="editUser(<?php echo $user['user_id']; ?>)">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon delete" title="Delete" onclick="showDeleteConfirm(<?php echo $user['user_id']; ?>, '<?php echo addslashes($user['full_name']); ?>')">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </button>
                                            <?php if ($user['status'] === 'active'): ?>
                                            <button class="action-icon disable" title="Disable" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 'inactive', '<?php echo addslashes($user['full_name']); ?>')">
                                                <ion-icon name="lock-closed-outline"></ion-icon>
                                            </button>
                                            <?php else: ?>
                                            <button class="action-icon enable" title="Enable" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 'active', '<?php echo addslashes($user['full_name']); ?>')">
                                                <ion-icon name="lock-open-outline"></ion-icon>
                                            </button>
                                            <?php endif; ?>
                                            <?php endif; ?>
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
                            Showing <?php echo count($initial_users) > 0 ? 1 : 0; ?> to <?php echo count($initial_users); ?> of <?php echo $initial_stats['total']; ?> entries
                        </div>
                        <?php if ($initial_stats['total'] > 10 && $user_role === 'admin'): ?>
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

            <!-- Add/Edit User Modal -->
            <div class="modal-overlay" id="userManagementModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add New User</h3>
                        <button class="modal-close" onclick="closeUserManagementModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="userForm" class="modal-form">
                            <input type="hidden" id="userId">
                            
                            <!-- Photo Upload -->
                            <div class="photo-upload-section">
                                <div class="photo-preview">
                                    <img src="https://ui-avatars.com/api/?name=New+User&background=2a5c8b&color=fff&size=120" 
                                         alt="User Photo" id="photoPreview">
                                </div>
                                <div class="upload-controls">
                                    <label class="upload-btn">
                                        <ion-icon name="cloud-upload-outline"></ion-icon>
                                        Upload Photo
                                        <input type="file" id="photoUpload" accept="image/*" onchange="previewPhoto(this)">
                                    </label>
                                    <p style="margin-top: 10px; color: var(--dark-gray); font-size: 0.9rem;">
                                        Recommended: Square image, max 2MB. PNG or JPG format.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Personal Information -->
                            <div class="form-group">
                                <label for="firstName" class="required">First Name</label>
                                <input type="text" id="firstName" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="surname" class="required">Surname</label>
                                <input type="text" id="surname" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender" class="required">Gender</label>
                                <select id="gender" required>
                                    <option value="">-- select --</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="required">Email Address</label>
                                <input type="email" id="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone">
                            </div>
                            
                            <div class="form-group">
                                <label for="dateOfBirth">Date of Birth</label>
                                <input type="date" id="dateOfBirth">
                            </div>
                            
                            <!-- Account Information -->
                            <div class="form-group">
                                <label for="username" class="required">Username</label>
                                <input type="text" id="username" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role" class="required">Role</label>
                                <select id="role" required>
                                    <option value="">-- select --</option>
                                    <option value="admin">Administrator</option>
                                    <option value="receptionist">Receptionist</option>
                                    <option value="clinician">Clinician</option>
                                    <option value="dental_staff">Dental Staff</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="branch_id" class="required">Branch</label>
                                <select id="branch_id" required>
                                    <option value="">-- select --</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>">
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Password Section -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Password</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" id="passwordLabel" class="required">Password</label>
                                <input type="password" id="password">
                                <div style="margin-top: 5px; font-size: 0.85rem; color: var(--dark-gray);">
                                    Must be at least 8 characters (leave blank to keep existing password)
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmPassword" id="confirmPasswordLabel">Confirm Password</label>
                                <input type="password" id="confirmPassword">
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" placeholder="Physical address..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="emergencyContact">Emergency Contact</label>
                                <input type="text" id="emergencyContact">
                            </div>
                            
                            <div class="form-group">
                                <label for="emergencyPhone">Emergency Phone</label>
                                <input type="tel" id="emergencyPhone">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="hireDate">Hire Date</label>
                                <input type="date" id="hireDate">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="notes">Notes / Additional Information</label>
                                <textarea id="notes" placeholder="Any additional information about this user..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeUserManagementModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveUser()">
                            Save User
                        </button>
                    </div>
                </div>
            </div>

            <script>
                // Initialize Variables
                let currentPage = 1;
                const itemsPerPage = 10;
                let filteredUsers = <?php echo json_encode($initial_users); ?>;
                let totalUsers = <?php echo $initial_stats['total']; ?>;
                
                // User role from PHP
                const userRole = '<?php echo $user_role; ?>';
                const userBranchId = <?php echo $current_branch_id ?: 'null'; ?>;
                const isAdmin = userRole === 'admin';

                // DOM Elements
                const tableBody = document.getElementById('usersTableBody');
                const userManagementModal = document.getElementById('userManagementModal');
                const searchInput = document.getElementById('searchInput');
                const statusFilter = document.getElementById('statusFilter');
                const roleFilter = document.getElementById('roleFilter');
                const branchFilter = document.getElementById('branchFilter');
                const sortFilter = document.getElementById('sortFilter');

                // Initialize Page
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM loaded - Initializing users management');
                    
                    // Disable filters for non-admin users
                    if (!isAdmin) {
                        searchInput.disabled = true;
                        statusFilter.disabled = true;
                        roleFilter.disabled = true;
                        branchFilter.disabled = true;
                        sortFilter.disabled = true;
                    }
                    
                    // Set default hire date
                    const hireDateInput = document.getElementById('hireDate');
                    if (hireDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        hireDateInput.value = today;
                    }
                });

                // Photo Preview
                function previewPhoto(input) {
                    const preview = document.getElementById('photoPreview');
                    if (!preview) return;
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    }
                    
                    if (input.files && input.files[0]) {
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                // View User Details
                function viewUser(id) {
                    const formData = new FormData();
                    formData.append('action', 'get_user');
                    formData.append('user_id', id);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const user = data.data;
                            
                            // Format dates
                            const hireDate = user.hire_date ? new Date(user.hire_date).toLocaleDateString() : 'Not set';
                            const dob = user.date_of_birth ? new Date(user.date_of_birth).toLocaleDateString() : 'Not set';
                            const created = new Date(user.created_at).toLocaleDateString();
                            
                            // Get role display text
                            const roleText = user.role.charAt(0).toUpperCase() + user.role.slice(1).replace('_', ' ');
                            
                            Swal.fire({
                                title: `<strong>${user.full_name}</strong>`,
                                html: `
                                    <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                                        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.full_name)}&background=2a5c8b&color=fff&size=100" 
                                                 alt="${user.full_name}" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #2a5c8b;">
                                            <div>
                                                <p><b>Role:</b> <span style="color: #2a5c8b;">${roleText}</span></p>
                                                <p><b>Status:</b> <span style="color: ${user.status === 'active' ? '#27ae60' : '#e74c3c'}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span></p>
                                                <p><b>Gender:</b> ${user.gender || 'Not set'}</p>
                                            </div>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                            <div>
                                                <p><b>Username:</b> ${user.username}</p>
                                                <p><b>Email:</b> ${user.email}</p>
                                                <p><b>Phone:</b> ${user.phone || 'Not set'}</p>
                                            </div>
                                            <div>
                                                <p><b>Date of Birth:</b> ${dob}</p>
                                                <p><b>Hire Date:</b> ${hireDate}</p>
                                                <p><b>Account Created:</b> ${created}</p>
                                            </div>
                                        </div>
                                        
                                        ${user.address ? `<p><b>Address:</b> ${user.address}</p>` : ''}
                                        
                                        ${user.emergency_contact ? `<p><b>Emergency Contact:</b> ${user.emergency_contact}${user.emergency_phone ? ` (${user.emergency_phone})` : ''}</p>` : ''}
                                        
                                        ${user.notes ? `<hr><p><b>Notes:</b><br>${user.notes}</p>` : ''}
                                    </div>
                                `,
                                width: 600,
                                showCloseButton: true,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to load user details', 'error');
                    });
                }

                // Modal Functions
                function openUserManagementModal() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can add new users.', 'error');
                        return;
                    }
                    
                    console.log('Opening User Management Modal');
                    
                    document.getElementById('modalTitle').textContent = 'Add New User';
                    
                    // Reset the form
                    const form = document.getElementById('userForm');
                    if (form) {
                        form.reset();
                    }
                    
                    // Clear user ID
                    document.getElementById('userId').value = '';
                    
                    // Reset photo preview
                    document.getElementById('photoPreview').src = 'https://ui-avatars.com/api/?name=New+User&background=2a5c8b&color=fff&size=120';
                    
                    // Make password fields required
                    document.getElementById('password').required = true;
                    document.getElementById('confirmPassword').required = true;
                    document.getElementById('passwordLabel').classList.add('required');
                    
                    // Set default hire date
                    const hireDateInput = document.getElementById('hireDate');
                    if (hireDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        hireDateInput.value = today;
                    }
                    
                    // Show modal
                    const modal = document.getElementById('userManagementModal');
                    if (modal) {
                        modal.classList.add('active');
                    }
                }

                function editUser(id) {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can edit users.', 'error');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'get_user');
                    formData.append('user_id', id);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const user = data.data;
                            
                            document.getElementById('modalTitle').textContent = 'Edit User';
                            
                            // Fill form with user data
                            document.getElementById('userId').value = user.user_id;
                            document.getElementById('photoPreview').src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.full_name) + '&background=2a5c8b&color=fff&size=120';
                            document.getElementById('firstName').value = user.first_name;
                            document.getElementById('surname').value = user.surname;
                            document.getElementById('gender').value = user.gender;
                            document.getElementById('email').value = user.email;
                            document.getElementById('phone').value = user.phone;
                            document.getElementById('username').value = user.username;
                            document.getElementById('role').value = user.role;
                            document.getElementById('branch_id').value = user.branch_id || '';
                            document.getElementById('dateOfBirth').value = user.date_of_birth;
                            document.getElementById('hireDate').value = user.hire_date;
                            document.getElementById('address').value = user.address || '';
                            document.getElementById('emergencyContact').value = user.emergency_contact || '';
                            document.getElementById('emergencyPhone').value = user.emergency_phone || '';
                            document.getElementById('status').value = user.status;
                            document.getElementById('notes').value = user.notes || '';
                            
                            // Make password fields optional for editing
                            document.getElementById('password').required = false;
                            document.getElementById('confirmPassword').required = false;
                            document.getElementById('passwordLabel').classList.remove('required');
                            
                            // Show modal
                            const modal = document.getElementById('userManagementModal');
                            if (modal) {
                                modal.classList.add('active');
                            }
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to load user details', 'error');
                    });
                }

                function closeUserManagementModal() {
                    const modal = document.getElementById('userManagementModal');
                    if (modal) {
                        modal.classList.remove('active');
                    }
                    
                    // Reset password requirement for next time
                    document.getElementById('password').required = true;
                    document.getElementById('confirmPassword').required = true;
                    document.getElementById('passwordLabel').classList.add('required');
                }

                // Save User Function
                function saveUser() {
                    const form = document.getElementById('userForm');
                    const userId = document.getElementById('userId').value;
                    const isEdit = !!userId;

                    // For new users, validate all required fields
                    if (!isEdit && !form.checkValidity()) {
                        Swal.fire('Error!', 'Please fill in all required fields.', 'error');
                        return;
                    }

                    // Check if it's edit mode and passwords are filled
                    if (isEdit) {
                        const password = document.getElementById('password').value;
                        const confirmPassword = document.getElementById('confirmPassword').value;
                        
                        // Only validate passwords if they are filled
                        if (password || confirmPassword) {
                            if (password !== confirmPassword) {
                                Swal.fire('Error!', 'Passwords do not match!', 'error');
                                return;
                            }
                            if (password.length < 8) {
                                Swal.fire('Error!', 'Password must be at least 8 characters long!', 'error');
                                return;
                            }
                        }
                    } else {
                        // For new users, passwords are required
                        const password = document.getElementById('password').value;
                        const confirmPassword = document.getElementById('confirmPassword').value;
                        
                        if (password !== confirmPassword) {
                            Swal.fire('Error!', 'Passwords do not match!', 'error');
                            return;
                        }
                        if (password.length < 8) {
                            Swal.fire('Error!', 'Password must be at least 8 characters long!', 'error');
                            return;
                        }
                    }

                    const formData = new FormData();
                    formData.append('action', isEdit ? 'edit_user' : 'add_user');
                    formData.append('user_id', userId);
                    formData.append('first_name', document.getElementById('firstName').value);
                    formData.append('surname', document.getElementById('surname').value);
                    formData.append('username', document.getElementById('username').value);
                    formData.append('email', document.getElementById('email').value);
                    formData.append('role', document.getElementById('role').value);
                    formData.append('branch_id', document.getElementById('branch_id').value);
                    formData.append('gender', document.getElementById('gender').value);
                    formData.append('phone', document.getElementById('phone').value);
                    formData.append('date_of_birth', document.getElementById('dateOfBirth').value);
                    formData.append('hire_date', document.getElementById('hireDate').value);
                    formData.append('address', document.getElementById('address').value);
                    formData.append('emergency_contact', document.getElementById('emergencyContact').value);
                    formData.append('emergency_phone', document.getElementById('emergencyPhone').value);
                    formData.append('status', document.getElementById('status').value);
                    formData.append('notes', document.getElementById('notes').value);
                    
                    // Only include password if filled
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;
                    if (password) {
                        formData.append('password', password);
                        formData.append('confirm_password', confirmPassword);
                    } else if (!isEdit) {
                        formData.append('password', password);
                        formData.append('confirm_password', confirmPassword);
                    }

                    // Show loading
                    Swal.fire({
                        title: isEdit ? 'Updating User...' : 'Adding User...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            closeUserManagementModal();
                            loadUsers();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                            if (data.error) {
                                console.error('Server error:', data.error);
                            }
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to save user', 'error');
                    });
                }

                // Delete User Functions
                function showDeleteConfirm(id, name) {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can delete users.', 'error');
                        return;
                    }
                    
                    Swal.fire({
                        title: 'Delete User?',
                        html: `Are you sure you want to delete <strong>${name}</strong>?<br><br>
                              <span style="color: #e74c3c; font-size: 0.9em;">
                              <ion-icon name="warning-outline"></ion-icon> 
                              This action cannot be undone and all user data will be permanently removed.
                              </span>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c',
                        cancelButtonColor: '#7f8c8d',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel',
                        width: 500
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteUser(id);
                        }
                    });
                }

                function deleteUser(id) {
                    if (!isAdmin) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_user');
                    formData.append('user_id', id);

                    // Show loading
                    Swal.fire({
                        title: 'Deleting User...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadUsers();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to delete user', 'error');
                    });
                }

                // Toggle User Status
                function toggleUserStatus(id, status, name) {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can change user status.', 'error');
                        return;
                    }
                    
                    const action = status === 'active' ? 'enable' : 'disable';
                    const actionText = status === 'active' ? 'Enable' : 'Disable';
                    
                    Swal.fire({
                        title: `${actionText} User?`,
                        html: `Are you sure you want to ${action} <strong>${name}</strong>?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: status === 'active' ? '#27ae60' : '#f39c12',
                        cancelButtonColor: '#7f8c8d',
                        confirmButtonText: `Yes, ${action} it!`,
                        cancelButtonText: 'Cancel',
                        width: 500
                    }).then((result) => {
                        if (result.isConfirmed) {
                            changeUserStatus(id, status);
                        }
                    });
                }

                function changeUserStatus(id, status) {
                    if (!isAdmin) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'toggle_user_status');
                    formData.append('user_id', id);
                    formData.append('status', status);

                    // Show loading
                    Swal.fire({
                        title: 'Updating User Status...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadUsers();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to update user status', 'error');
                    });
                }

                // Load users from server
                function loadUsers() {
                    if (!isAdmin) return; // Non-admin users can't filter
                    
                    const formData = new FormData();
                    formData.append('action', 'get_users');
                    formData.append('search', searchInput.value);
                    formData.append('status', statusFilter.value);
                    formData.append('role', roleFilter.value);
                    formData.append('branch', branchFilter.value);
                    formData.append('sort', sortFilter.value);
                    formData.append('page', currentPage);
                    formData.append('limit', itemsPerPage);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            filteredUsers = data.data;
                            totalUsers = data.total;
                            renderUsersTable();
                            updateStatistics(data.data);
                            updatePaginationInfo();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to load users', 'error');
                    });
                }

                // Render Users Table
                function renderUsersTable() {
                    if (!tableBody) return;
                    
                    if (filteredUsers.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <ion-icon name="people-outline"></ion-icon>
                                    <h3>No users found</h3>
                                    <p>${isAdmin ? 'Try adjusting your search or filters' : 'No users in your branch.'}</p>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    filteredUsers.forEach(user => {
                        const statusClass = `status-${user.status}`;
                        const statusText = user.status.charAt(0).toUpperCase() + user.status.slice(1);
                        const roleClass = `role-${user.role}`;
                        const roleText = user.role.charAt(0).toUpperCase() + user.role.slice(1).replace('_', ' ');
                        
                        html += `
                            <tr>
                                <td>${user.sn}</td>
                                <td>
                                    <div class="user-photo">
                                        <img src="${user.photo}" alt="${user.full_name}">
                                    </div>
                                </td>
                                <td>
                                    <strong>${user.full_name}</strong><br>
                                    <small style="color: var(--dark-gray);">${user.email}</small><br>
                                    <small style="color: var(--dark-gray);">${user.username}</small>
                                </td>
                                <td>${user.gender || 'Not set'}</td>
                                <td><span class="role-badge ${roleClass}">${roleText}</span></td>
                                <td>${user.branch_name || 'Not assigned'}</td>
                                <td><span class="user-status ${statusClass}">${statusText}</span></td>
                                <td>
                                    <div class="user-actions">
                                        <button class="action-icon view" title="View Details" onclick="viewUser(${user.user_id})">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        ${isAdmin ? `
                                        <button class="action-icon edit" title="Edit" onclick="editUser(${user.user_id})">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon delete" title="Delete" onclick="showDeleteConfirm(${user.user_id}, '${user.full_name.replace(/'/g, "\\'")}')">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                        ${user.status === 'active' ? `
                                            <button class="action-icon disable" title="Disable" onclick="toggleUserStatus(${user.user_id}, 'inactive', '${user.full_name.replace(/'/g, "\\'")}')">
                                                <ion-icon name="lock-closed-outline"></ion-icon>
                                            </button>
                                        ` : `
                                            <button class="action-icon enable" title="Enable" onclick="toggleUserStatus(${user.user_id}, 'active', '${user.full_name.replace(/'/g, "\\'")}')">
                                                <ion-icon name="lock-open-outline"></ion-icon>
                                            </button>
                                        `}
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = html;
                }

                // Update Statistics
                function updateStatistics(users = filteredUsers) {
                    const activeUsers = users.filter(u => u.status === 'active').length;
                    const adminUsers = users.filter(u => u.role === 'admin').length;
                    const branchesCovered = [...new Set(users.map(u => u.branch_id).filter(id => id))].length;
                    
                    document.getElementById('totalUsers').textContent = users.length;
                    document.getElementById('activeUsers').textContent = activeUsers;
                    document.getElementById('adminUsers').textContent = adminUsers;
                    document.getElementById('branchesCovered').textContent = branchesCovered;
                }

                // Filter and Search Functions
                function filterUsers() {
                    if (!isAdmin) return;
                    currentPage = 1;
                    loadUsers();
                }

                function resetFilters() {
                    if (!isAdmin) return;
                    searchInput.value = '';
                    statusFilter.value = '';
                    roleFilter.value = '';
                    branchFilter.value = '';
                    sortFilter.value = 'name_asc';
                    currentPage = 1;
                    loadUsers();
                }

                function applyFilters() {
                    if (!isAdmin) return;
                    loadUsers();
                    Swal.fire({
                        icon: 'success',
                        title: 'Filters Applied!',
                        text: 'User list has been filtered.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                // Pagination Functions
                function updatePaginationInfo() {
                    if (!isAdmin) return;
                    
                    const total = totalUsers;
                    const start = (currentPage - 1) * itemsPerPage + 1;
                    const end = Math.min(currentPage * itemsPerPage, total);
                    
                    document.getElementById('paginationInfo').textContent = 
                        `Showing ${start} to ${end} of ${total} entries`;
                        
                    updatePaginationControls();
                }

                function updatePaginationControls() {
                    if (!isAdmin) return;
                    
                    const totalPages = Math.ceil(totalUsers / itemsPerPage);
                    const paginationControls = document.querySelector('.pagination-controls');
                    
                    if (!paginationControls) return;
                    
                    let html = `
                        <button class="pagination-btn" onclick="changePage('prev')" ${currentPage === 1 ? 'disabled' : ''}>
                            <ion-icon name="chevron-back-outline"></ion-icon>
                        </button>
                    `;
                    
                    // Show up to 5 page buttons
                    const startPage = Math.max(1, currentPage - 2);
                    const endPage = Math.min(totalPages, startPage + 4);
                    
                    for (let i = startPage; i <= endPage; i++) {
                        html += `
                            <button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                                ${i}
                            </button>
                        `;
                    }
                    
                    html += `
                        <button class="pagination-btn" onclick="changePage('next')" ${currentPage === totalPages ? 'disabled' : ''}>
                            <ion-icon name="chevron-forward-outline"></ion-icon>
                        </button>
                    `;
                    
                    paginationControls.innerHTML = html;
                }

                function changePage(direction) {
                    if (!isAdmin) return;
                    
                    if (direction === 'prev' && currentPage > 1) {
                        currentPage--;
                    } else if (direction === 'next' && currentPage < Math.ceil(totalUsers / itemsPerPage)) {
                        currentPage++;
                    }
                    loadUsers();
                }

                function goToPage(page) {
                    if (!isAdmin) return;
                    currentPage = page;
                    loadUsers();
                }

                // Utility Functions
                function refreshUsers() {
                    loadUsers();
                    Swal.fire({
                        icon: 'success',
                        title: 'Refreshed!',
                        text: 'User list has been refreshed.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                function exportUsers() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can export users.', 'error');
                        return;
                    }
                    
                    const csvContent = [
                        ['S/N', 'Full Name', 'Email', 'Username', 'Gender', 'Role', 'Branch', 'Status', 'Phone', 'Hire Date'],
                        ...filteredUsers.map(u => [
                            u.sn,
                            u.full_name,
                            u.email,
                            u.username,
                            u.gender,
                            u.role.charAt(0).toUpperCase() + u.role.slice(1).replace('_', ' '),
                            u.branch_name,
                            u.status,
                            u.phone,
                            u.hire_date_formatted
                        ])
                    ].map(row => row.join(',')).join('\n');

                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', `users_${new Date().toISOString().split('T')[0]}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Export Started!',
                        text: 'CSV file download has started.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }

                function printUsers() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can print users.', 'error');
                        return;
                    }
                    
                    const printContent = `
                        <html>
                        <head>
                            <title>Master Clinic Users Report</title>
                            <style>
                                body { font-family: Arial, sans-serif; margin: 20px; }
                                h1 { color: #2a5c8b; }
                                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                                th { background: #f5f7fa; padding: 10px; text-align: left; border: 1px solid #ddd; }
                                td { padding: 10px; border: 1px solid #ddd; }
                                .status-active { color: #27ae60; }
                                .status-inactive { color: #e74c3c; }
                                .print-header { display: flex; justify-content: space-between; margin-bottom: 30px; }
                                .print-date { color: #7f8c8d; }
                            </style>
                        </head>
                        <body>
                            <div class="print-header">
                                <h1>Master Clinic Users Report</h1>
                                <div class="print-date">Generated: ${new Date().toLocaleDateString()}</div>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                        <th>Phone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filteredUsers.map(user => `
                                        <tr>
                                            <td>${user.sn}</td>
                                            <td>${user.full_name}</td>
                                            <td>${user.email}</td>
                                            <td>${user.role.charAt(0).toUpperCase() + user.role.slice(1).replace('_', ' ')}</td>
                                            <td>${user.branch_name}</td>
                                            <td class="status-${user.status}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</td>
                                            <td>${user.phone}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <div style="margin-top: 30px; color: #7f8c8d; font-size: 12px;">
                                Total Users: ${filteredUsers.length} | Active: ${filteredUsers.filter(u => u.status === 'active').length}
                            </div>
                        </body>
                        </html>
                    `;

                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    printWindow.print();
                }

                // Close modals when clicking outside
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('modal-overlay')) {
                        closeUserManagementModal();
                    }
                });

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeUserManagementModal();
                    }
                    if (e.ctrlKey && e.key === 'f' && isAdmin) {
                        e.preventDefault();
                        searchInput.focus();
                    }
                    if (e.ctrlKey && e.key === 'n' && isAdmin) {
                        e.preventDefault();
                        openUserManagementModal();
                    }
                });
            </script>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
=======
<?php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'add_user':
            // Validate required fields
            $required = ['first_name', 'surname', 'username', 'email', 'role', 'password', 'confirm_password'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $response['message'] = "All required fields must be filled!";
                    echo json_encode($response);
                    exit;
                }
            }
            
            // Check if passwords match
            if ($_POST['password'] !== $_POST['confirm_password']) {
                $response['message'] = "Passwords do not match!";
                echo json_encode($response);
                exit;
            }
            
            // Check password length
            if (strlen($_POST['password']) < 8) {
                $response['message'] = "Password must be at least 8 characters long!";
                echo json_encode($response);
                exit;
            }
            
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $_POST['username']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $response['message'] = "Username already exists!";
                $check_stmt->close();
                echo json_encode($response);
                exit;
            }
            $check_stmt->close();
            
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $_POST['email']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $response['message'] = "Email already exists!";
                $check_stmt->close();
                echo json_encode($response);
                exit;
            }
            $check_stmt->close();
            
            // Hash password
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, gender, email, phone, date_of_birth, address, emergency_contact, emergency_phone, notes, branch_id, role, status, hire_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Build full name
            $full_name = $_POST['first_name'] . ' ' . $_POST['surname'];
            $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
            $status = $_POST['status'] ?? 'active';
            $gender = $_POST['gender'] ?? null;
            $phone = $_POST['phone'] ?? null;
            $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            $address = $_POST['address'] ?? null;
            $emergency_contact = $_POST['emergency_contact'] ?? null;
            $emergency_phone = $_POST['emergency_phone'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : date('Y-m-d');
            
            $stmt->bind_param("sssssssssssisssi", 
                $_POST['username'],
                $hashed_password,
                $full_name,
                $gender,
                $_POST['email'],
                $phone,
                $date_of_birth,
                $address,
                $emergency_contact,
                $emergency_phone,
                $notes,
                $branch_id,
                $_POST['role'],
                $status,
                $hire_date,
                $user_id
            );
            
            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                
                // Log activity
                logActivity($conn, $user_id, $user_name, 'ADD_USER', 
                    "Added new user: {$full_name} ({$_POST['username']})", 
                    $branch_id, getBranchName($conn, $branch_id));
                
                $response['success'] = true;
                $response['message'] = "User added successfully!";
                $response['user_id'] = $new_user_id;
            } else {
                $response['message'] = "Error adding user: " . $stmt->error;
                $response['error'] = $stmt->error;
                $response['errno'] = $stmt->errno;
            }
            $stmt->close();
            break;
            
        case 'edit_user':
            $user_id_edit = intval($_POST['user_id']);
            
            // Check if user exists
            $check_stmt = $conn->prepare("SELECT username, full_name, email, branch_id FROM users WHERE user_id = ?");
            $check_stmt->bind_param("i", $user_id_edit);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $response['message'] = "User not found!";
                $check_stmt->close();
                echo json_encode($response);
                exit;
            }
            
            $user_data = $check_result->fetch_assoc();
            $check_stmt->close();
            
            // Check if username changed and if new username exists
            if ($user_data['username'] !== $_POST['username']) {
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
                $check_stmt->bind_param("si", $_POST['username'], $user_id_edit);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    $response['message'] = "Username already exists!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
            }
            
            // Check if email changed and if new email exists
            if ($user_data['email'] !== $_POST['email']) {
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $check_stmt->bind_param("si", $_POST['email'], $user_id_edit);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    $response['message'] = "Email already exists!";
                    $check_stmt->close();
                    echo json_encode($response);
                    exit;
                }
                $check_stmt->close();
            }
            
            // Build full name
            $full_name = $_POST['first_name'] . ' ' . $_POST['surname'];
            $branch_id = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
            $gender = $_POST['gender'] ?? null;
            $phone = $_POST['phone'] ?? null;
            $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            $address = $_POST['address'] ?? null;
            $emergency_contact = $_POST['emergency_contact'] ?? null;
            $emergency_phone = $_POST['emergency_phone'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;
            
            // Check if password is being updated
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $response['message'] = "Passwords do not match!";
                    echo json_encode($response);
                    exit;
                }
                
                if (strlen($_POST['password']) < 8) {
                    $response['message'] = "Password must be at least 8 characters long!";
                    echo json_encode($response);
                    exit;
                }
                
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Update with password
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, full_name = ?, gender = ?, email = ?, phone = ?, date_of_birth = ?, address = ?, emergency_contact = ?, emergency_phone = ?, notes = ?, branch_id = ?, role = ?, status = ?, hire_date = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->bind_param("sssssssssssisssi", 
                    $_POST['username'],
                    $hashed_password,
                    $full_name,
                    $gender,
                    $_POST['email'],
                    $phone,
                    $date_of_birth,
                    $address,
                    $emergency_contact,
                    $emergency_phone,
                    $notes,
                    $branch_id,
                    $_POST['role'],
                    $_POST['status'],
                    $hire_date,
                    $user_id_edit
                );
            } else {
                // Update without password
                $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, gender = ?, email = ?, phone = ?, date_of_birth = ?, address = ?, emergency_contact = ?, emergency_phone = ?, notes = ?, branch_id = ?, role = ?, status = ?, hire_date = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->bind_param("ssssssssssisssi", 
                    $_POST['username'],
                    $full_name,
                    $gender,
                    $_POST['email'],
                    $phone,
                    $date_of_birth,
                    $address,
                    $emergency_contact,
                    $emergency_phone,
                    $notes,
                    $branch_id,
                    $_POST['role'],
                    $_POST['status'],
                    $hire_date,
                    $user_id_edit
                );
            }
            
            if ($stmt->execute()) {
                // Log activity
                logActivity($conn, $user_id, $user_name, 'EDIT_USER', 
                    "Updated user: {$full_name} ({$_POST['username']})", 
                    $branch_id, getBranchName($conn, $branch_id));
                
                $response['success'] = true;
                $response['message'] = "User updated successfully!";
            } else {
                $response['message'] = "Error updating user: " . $stmt->error;
                $response['error'] = $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'delete_user':
            $user_id_delete = intval($_POST['user_id']);
            
            // Check if trying to delete self
            if ($user_id_delete === $user_id) {
                $response['message'] = "You cannot delete your own account!";
                echo json_encode($response);
                exit;
            }
            
            // Get user info for logging
            $user_stmt = $conn->prepare("SELECT username, full_name, branch_id FROM users WHERE user_id = ?");
            $user_stmt->bind_param("i", $user_id_delete);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows === 0) {
                $response['message'] = "User not found!";
                $user_stmt->close();
                echo json_encode($response);
                exit;
            }
            
            $user_data = $user_result->fetch_assoc();
            $user_stmt->close();
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id_delete);
            
            if ($stmt->execute()) {
                // Log activity
                logActivity($conn, $user_id, $user_name, 'DELETE_USER', 
                    "Deleted user: {$user_data['full_name']} ({$user_data['username']})", 
                    $user_data['branch_id'], getBranchName($conn, $user_data['branch_id']));
                
                $response['success'] = true;
                $response['message'] = "User deleted successfully!";
            } else {
                $response['message'] = "Error deleting user: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'toggle_user_status':
            $user_id_toggle = intval($_POST['user_id']);
            $new_status = $_POST['status'];
            
            // Check if trying to toggle self
            if ($user_id_toggle === $user_id) {
                $response['message'] = "You cannot change your own status!";
                echo json_encode($response);
                exit;
            }
            
            // Get user info for logging
            $user_stmt = $conn->prepare("SELECT username, full_name, branch_id FROM users WHERE user_id = ?");
            $user_stmt->bind_param("i", $user_id_toggle);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows === 0) {
                $response['message'] = "User not found!";
                $user_stmt->close();
                echo json_encode($response);
                exit;
            }
            
            $user_data = $user_result->fetch_assoc();
            $user_stmt->close();
            
            // Update user status
            $stmt = $conn->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->bind_param("si", $new_status, $user_id_toggle);
            
            if ($stmt->execute()) {
                $action = $new_status === 'active' ? 'ENABLE_USER' : 'DISABLE_USER';
                $action_text = $new_status === 'active' ? 'Enabled' : 'Disabled';
                
                // Log activity
                logActivity($conn, $user_id, $user_name, $action, 
                    "{$action_text} user: {$user_data['full_name']} ({$user_data['username']})", 
                    $user_data['branch_id'], getBranchName($conn, $user_data['branch_id']));
                
                $response['success'] = true;
                $response['message'] = "User status updated successfully!";
            } else {
                $response['message'] = "Error updating user status: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'get_user':
            $user_id_get = intval($_POST['user_id']);
            
            $stmt = $conn->prepare("SELECT *, DATE(date_of_birth) as date_of_birth, DATE(hire_date) as hire_date FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id_get);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Split full name into first name and surname
                $name_parts = explode(' ', $user['full_name'], 2);
                $user['first_name'] = $name_parts[0] ?? '';
                $user['surname'] = $name_parts[1] ?? '';
                
                $response['success'] = true;
                $response['data'] = $user;
            } else {
                $response['message'] = "User not found!";
            }
            $stmt->close();
            break;
            
        case 'get_users':
            // Build query based on filters and user role
            $where_clauses = [];
            $params = [];
            $types = "";
            
            // If not admin, show only users from same branch
            if ($user_role !== 'admin' && $current_branch_id) {
                $where_clauses[] = "u.branch_id = ?";
                $params[] = $current_branch_id;
                $types .= "i";
            }
            
            // Apply filters
            if (!empty($_POST['search'])) {
                $where_clauses[] = "(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                $search_term = "%{$_POST['search']}%";
                for ($i = 0; $i < 3; $i++) {
                    $params[] = $search_term;
                    $types .= "s";
                }
            }
            
            if (!empty($_POST['status'])) {
                $where_clauses[] = "u.status = ?";
                $params[] = $_POST['status'];
                $types .= "s";
            }
            
            if (!empty($_POST['role'])) {
                $where_clauses[] = "u.role = ?";
                $params[] = $_POST['role'];
                $types .= "s";
            }
            
            if (!empty($_POST['branch'])) {
                $where_clauses[] = "u.branch_id = ?";
                $params[] = intval($_POST['branch']);
                $types .= "i";
            }
            
            $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM users u $where_sql";
            $count_stmt = $conn->prepare($count_sql);
            
            if (!empty($params)) {
                $count_stmt->bind_param($types, ...$params);
            }
            
            $count_stmt->execute();
            $count_result = $count_stmt->get_result()->fetch_assoc();
            $total_users = $count_result['total'];
            $count_stmt->close();
            
            // Get users with sorting
            $sort_by = $_POST['sort'] ?? 'u.full_name ASC';
            $allowed_sorts = [
                'name_asc' => 'u.full_name ASC',
                'name_desc' => 'u.full_name DESC',
                'date_new' => 'u.created_at DESC',
                'date_old' => 'u.created_at ASC',
                'role' => 'u.role ASC'
            ];
            
            $sort_sql = $allowed_sorts[$_POST['sort'] ?? 'name_asc'] ?? 'u.full_name ASC';
            
            // Pagination
            $page = intval($_POST['page'] ?? 1);
            $limit = intval($_POST['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT u.*, b.branch_name, 
                    (SELECT full_name FROM users WHERE user_id = u.created_by) as created_by_name
                    FROM users u
                    LEFT JOIN branches b ON u.branch_id = b.branch_id
                    $where_sql 
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
            
            $users = [];
            $sn = $offset + 1;
            while ($row = $result->fetch_assoc()) {
                $row['sn'] = $sn++;
                
                // Generate photo URL
                $row['photo'] = "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']) . "&background=" . getRoleColor($row['role']) . "&color=fff";
                
                // Format dates
                $row['created_at_formatted'] = date('M d, Y', strtotime($row['created_at']));
                $row['hire_date_formatted'] = !empty($row['hire_date']) ? date('M d, Y', strtotime($row['hire_date'])) : 'Not set';
                
                $users[] = $row;
            }
            
            $response['success'] = true;
            $response['data'] = $users;
            $response['total'] = $total_users;
            $response['page'] = $page;
            $response['pages'] = ceil($total_users / $limit);
            
            $stmt->close();
            break;
            
        default:
            $response['message'] = "Invalid action!";
    }
    
    echo json_encode($response);
    exit;
}

// Helper function to get branch name
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

// Helper function to get role color for avatar - UPDATED WITH ONLY 4 ROLES
function getRoleColor($role) {
    $colors = [
        'admin' => '2a5c8b',
        'receptionist' => '1abc9c',
        'clinician' => '9b59b6',
        'dental_staff' => 'e74c3c'
    ];
    
    return $colors[$role] ?? '2a5c8b'; // Default to admin color if role not found
}

// Get initial users data for page load
$initial_users = [];
$initial_stats = [
    'total' => 0,
    'active' => 0,
    'admin' => 0,
    'branches' => 0
];

// Build query based on user role
if ($user_role === 'admin') {
    // Admin sees all users
    $sql = "SELECT u.*, b.branch_name FROM users u
            LEFT JOIN branches b ON u.branch_id = b.branch_id
            ORDER BY u.full_name ASC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    $sn = 1;
    while ($row = $result->fetch_assoc()) {
        $row['sn'] = $sn++;
        $row['photo'] = "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']) . "&background=" . getRoleColor($row['role']) . "&color=fff";
        $initial_users[] = $row;
    }
    
    // Get statistics
    $stats_sql = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                  SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
                  COUNT(DISTINCT branch_id) as branches
                  FROM users";
    $stats_result = $conn->query($stats_sql);
    if ($stats_row = $stats_result->fetch_assoc()) {
        $initial_stats = $stats_row;
    }
} else {
    // Non-admin users see only users from their branch
    if ($current_branch_id) {
        $sql = "SELECT u.*, b.branch_name FROM users u
                LEFT JOIN branches b ON u.branch_id = b.branch_id
                WHERE u.branch_id = ?
                ORDER BY u.full_name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $current_branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sn = 1;
        while ($row = $result->fetch_assoc()) {
            $row['sn'] = $sn++;
            $row['photo'] = "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']) . "&background=" . getRoleColor($row['role']) . "&color=fff";
            $initial_users[] = $row;
        }
        $stmt->close();
        
        // Get statistics for this branch
        $stats_sql = "SELECT 
                      COUNT(*) as total,
                      SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                      SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin,
                      1 as branches
                      FROM users WHERE branch_id = ?";
        
        $stmt = $conn->prepare($stats_sql);
        $stmt->bind_param("i", $current_branch_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        if ($stats_row = $stats_result->fetch_assoc()) {
            $initial_stats = $stats_row;
        }
        $stmt->close();
    }
}

// Get branches for filter (admin sees all, others see only their branch)
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

$branches = [];
if ($branches_result) {
    while ($row = $branches_result->fetch_assoc()) {
        $branches[] = $row;
    }
}
?>

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

            <!-- Include SweetAlert -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <!-- Include jQuery and Select2 -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

            <style>
              /* ================== Users Management Styles ============== */
                .users-management-section {
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
                .users-stats {
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

                /* Users Table */
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

                .users-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .users-table thead {
                    background: var(--light-gray);
                }

                .users-table th {
                    padding: 18px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    border-bottom: 2px solid var(--gray);
                }

                .users-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.3s ease;
                }

                .users-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .users-table td {
                    padding: 15px 20px;
                    color: var(--black);
                    font-size: 0.95rem;
                    vertical-align: middle;
                }

                /* User Photo */
                .user-photo {
                    width: 45px;
                    height: 45px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 2px solid var(--gray);
                }

                .user-photo img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                /* Status Badges */
                .user-status {
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

                .status-onleave {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                /* Role Badges - UPDATED FOR 4 ROLES */
                .role-badge {
                    display: inline-block;
                    padding: 5px 12px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .role-admin {
                    background: rgba(42, 92, 139, 0.1);
                    color: #2a5c8b;
                }

                .role-receptionist {
                    background: rgba(26, 188, 156, 0.1);
                    color: #1abc9c;
                }

                .role-clinician {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .role-dental_staff {
                    background: rgba(231, 76, 60, 0.1);
                    color: #e74c3c;
                }

                /* Actions */
                .user-actions {
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

                .action-icon.disable {
                    background: rgba(243, 156, 18, 0.1);
                    color: var(--warning);
                }

                .action-icon.disable:hover {
                    background: var(--warning);
                    color: white;
                    transform: translateY(-2px);
                }

                .action-icon.enable {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .action-icon.enable:hover {
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
                    max-width: 700px;
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

                /* Photo Upload */
                .photo-upload-section {
                    grid-column: 1 / -1;
                    display: flex;
                    align-items: center;
                    gap: 30px;
                    margin-bottom: 10px;
                }

                .photo-preview {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 3px solid var(--gray);
                    position: relative;
                }

                .photo-preview img {
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

                /* Select2 Styling */
                .select2-container {
                    width: 100% !important;
                }

                .select2-selection--multiple {
                    border: 1px solid var(--gray) !important;
                    border-radius: 8px !important;
                    min-height: 46px !important;
                    padding: 6px 10px !important;
                }

                .select2-selection--multiple:focus {
                    outline: none;
                    border-color: var(--primary) !important;
                    box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1) !important;
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
                @media (max-width: 1200px) {
                    .modal-form {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 768px) {
                    .users-management-section {
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

                    .users-stats {
                        grid-template-columns: repeat(2, 1fr);
                    }

                    .stat-card {
                        flex-direction: column;
                        text-align: center;
                        gap: 15px;
                    }

                    .table-header {
                        flex-direction: column;
                        gap: 15px;
                        align-items: flex-start;
                    }

                    .table-actions {
                        width: 100%;
                        justify-content: flex-end;
                    }

                    .users-table {
                        display: block;
                        overflow-x: auto;
                    }

                    .photo-upload-section {
                        flex-direction: column;
                        text-align: center;
                        gap: 20px;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }

                    .user-actions {
                        flex-direction: column;
                        gap: 5px;
                    }

                    .action-icon {
                        width: 30px;
                        height: 30px;
                        font-size: 1rem;
                    }
                }

                @media (max-width: 480px) {
                    .users-stats {
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

                    .modal-body {
                        padding: 20px;
                    }
                }
            </style>

            <!-- ================== Users Management Content ============== -->
            <div class="users-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>User Management</h1>
                        <p>Manage system users, roles, and permissions</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshUsers()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <?php if ($user_role === 'admin'): ?>
                        <button class="action-btn primary" onclick="openUserManagementModal()" id="addUserBtn">
                            <ion-icon name="person-add-outline"></ion-icon>
                            Add New User
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Users Statistics -->
                <div class="users-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalUsers"><?php echo $initial_stats['total']; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeUsers"><?php echo $initial_stats['active']; ?></h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="shield-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="adminUsers"><?php echo $initial_stats['admin']; ?></h3>
                            <p>Admin Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="business-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="branchesCovered"><?php echo $initial_stats['branches']; ?></h3>
                            <p>Branches Covered</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search users by name, email, or role..." 
                               onkeyup="filterUsers()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterUsers()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Role</label>
                            <select id="roleFilter" onchange="filterUsers()">
                                <option value="">All Roles</option>
                                <option value="admin">Administrator</option>
                                <option value="receptionist">Receptionist</option>
                                <option value="clinician">Clinician</option>
                                <option value="dental_staff">Dental Staff</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Branch</label>
                            <select id="branchFilter" onchange="filterUsers()">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['branch_id']; ?>">
                                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterUsers()">
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="date_new">Date Added (Newest)</option>
                                <option value="date_old">Date Added (Oldest)</option>
                                <option value="role">Role</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <?php if ($user_role === 'admin'): ?>
                        <button class="action-btn secondary" onclick="resetFilters()">
                            <ion-icon name="close-circle-outline"></ion-icon>
                            Clear Filters
                        </button>
                        <button class="action-btn primary" onclick="applyFilters()">
                            <ion-icon name="filter-outline"></ion-icon>
                            Apply Filters
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>System Users</h2>
                        <div class="table-actions">
                            <?php if ($user_role === 'admin'): ?>
                            <button class="export-btn" onclick="exportUsers()">
                                <ion-icon name="download-outline"></ion-icon>
                                Export CSV
                            </button>
                            <button class="export-btn" onclick="printUsers()">
                                <ion-icon name="print-outline"></ion-icon>
                                Print
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>PHOTO</th>
                                    <th>FULL NAME</th>
                                    <th>GENDER</th>
                                    <th>ROLE</th>
                                    <th>WORK STATION</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php if (empty($initial_users)): ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <ion-icon name="people-outline"></ion-icon>
                                        <h3>No users found</h3>
                                        <p><?php echo $user_role === 'admin' ? 'Add your first user!' : 'No users in your branch.'; ?></p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_users as $user): ?>
                                <?php 
                                    $statusClass = 'status-' . $user['status'];
                                    $statusText = ucfirst($user['status']);
                                    $roleClass = 'role-' . $user['role'];
                                    $roleText = ucfirst(str_replace('_', ' ', $user['role']));
                                    $workStation = $user['branch_name'] ?? 'Not assigned';
                                ?>
                                <tr>
                                    <td><?php echo $user['sn']; ?></td>
                                    <td>
                                        <div class="user-photo">
                                            <img src="<?php echo $user['photo']; ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                        <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($user['email']); ?></small><br>
                                        <small style="color: var(--dark-gray);"><?php echo htmlspecialchars($user['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['gender'] ?? 'Not set'); ?></td>
                                    <td><span class="role-badge <?php echo $roleClass; ?>"><?php echo $roleText; ?></span></td>
                                    <td><?php echo htmlspecialchars($workStation); ?></td>
                                    <td><span class="user-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="action-icon view" title="View Details" onclick="viewUser(<?php echo $user['user_id']; ?>)">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </button>
                                            <?php if ($user_role === 'admin'): ?>
                                            <button class="action-icon edit" title="Edit" onclick="editUser(<?php echo $user['user_id']; ?>)">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon delete" title="Delete" onclick="showDeleteConfirm(<?php echo $user['user_id']; ?>, '<?php echo addslashes($user['full_name']); ?>')">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </button>
                                            <?php if ($user['status'] === 'active'): ?>
                                            <button class="action-icon disable" title="Disable" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 'inactive', '<?php echo addslashes($user['full_name']); ?>')">
                                                <ion-icon name="lock-closed-outline"></ion-icon>
                                            </button>
                                            <?php else: ?>
                                            <button class="action-icon enable" title="Enable" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 'active', '<?php echo addslashes($user['full_name']); ?>')">
                                                <ion-icon name="lock-open-outline"></ion-icon>
                                            </button>
                                            <?php endif; ?>
                                            <?php endif; ?>
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
                            Showing <?php echo count($initial_users) > 0 ? 1 : 0; ?> to <?php echo count($initial_users); ?> of <?php echo $initial_stats['total']; ?> entries
                        </div>
                        <?php if ($initial_stats['total'] > 10 && $user_role === 'admin'): ?>
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

            <!-- Add/Edit User Modal -->
            <div class="modal-overlay" id="userManagementModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add New User</h3>
                        <button class="modal-close" onclick="closeUserManagementModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="userForm" class="modal-form">
                            <input type="hidden" id="userId">
                            
                            <!-- Photo Upload -->
                            <div class="photo-upload-section">
                                <div class="photo-preview">
                                    <img src="https://ui-avatars.com/api/?name=New+User&background=2a5c8b&color=fff&size=120" 
                                         alt="User Photo" id="photoPreview">
                                </div>
                                <div class="upload-controls">
                                    <label class="upload-btn">
                                        <ion-icon name="cloud-upload-outline"></ion-icon>
                                        Upload Photo
                                        <input type="file" id="photoUpload" accept="image/*" onchange="previewPhoto(this)">
                                    </label>
                                    <p style="margin-top: 10px; color: var(--dark-gray); font-size: 0.9rem;">
                                        Recommended: Square image, max 2MB. PNG or JPG format.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Personal Information -->
                            <div class="form-group">
                                <label for="firstName" class="required">First Name</label>
                                <input type="text" id="firstName" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="surname" class="required">Surname</label>
                                <input type="text" id="surname" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender" class="required">Gender</label>
                                <select id="gender" required>
                                    <option value="">-- select --</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="required">Email Address</label>
                                <input type="email" id="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone">
                            </div>
                            
                            <div class="form-group">
                                <label for="dateOfBirth">Date of Birth</label>
                                <input type="date" id="dateOfBirth">
                            </div>
                            
                            <!-- Account Information -->
                            <div class="form-group">
                                <label for="username" class="required">Username</label>
                                <input type="text" id="username" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role" class="required">Role</label>
                                <select id="role" required>
                                    <option value="">-- select --</option>
                                    <option value="admin">Administrator</option>
                                    <option value="receptionist">Receptionist</option>
                                    <option value="clinician">Clinician</option>
                                    <option value="dental_staff">Dental Staff</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="branch_id" class="required">Branch</label>
                                <select id="branch_id" required>
                                    <option value="">-- select --</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>">
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Password Section -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Password</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" id="passwordLabel" class="required">Password</label>
                                <input type="password" id="password">
                                <div style="margin-top: 5px; font-size: 0.85rem; color: var(--dark-gray);">
                                    Must be at least 8 characters (leave blank to keep existing password)
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmPassword" id="confirmPasswordLabel">Confirm Password</label>
                                <input type="password" id="confirmPassword">
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" placeholder="Physical address..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="emergencyContact">Emergency Contact</label>
                                <input type="text" id="emergencyContact">
                            </div>
                            
                            <div class="form-group">
                                <label for="emergencyPhone">Emergency Phone</label>
                                <input type="tel" id="emergencyPhone">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="hireDate">Hire Date</label>
                                <input type="date" id="hireDate">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="notes">Notes / Additional Information</label>
                                <textarea id="notes" placeholder="Any additional information about this user..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeUserManagementModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveUser()">
                            Save User
                        </button>
                    </div>
                </div>
            </div>

            <script>
                // Initialize Variables
                let currentPage = 1;
                const itemsPerPage = 10;
                let filteredUsers = <?php echo json_encode($initial_users); ?>;
                let totalUsers = <?php echo $initial_stats['total']; ?>;
                
                // User role from PHP
                const userRole = '<?php echo $user_role; ?>';
                const userBranchId = <?php echo $current_branch_id ?: 'null'; ?>;
                const isAdmin = userRole === 'admin';

                // DOM Elements
                const tableBody = document.getElementById('usersTableBody');
                const userManagementModal = document.getElementById('userManagementModal');
                const searchInput = document.getElementById('searchInput');
                const statusFilter = document.getElementById('statusFilter');
                const roleFilter = document.getElementById('roleFilter');
                const branchFilter = document.getElementById('branchFilter');
                const sortFilter = document.getElementById('sortFilter');

                // Initialize Page
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM loaded - Initializing users management');
                    
                    // Disable filters for non-admin users
                    if (!isAdmin) {
                        searchInput.disabled = true;
                        statusFilter.disabled = true;
                        roleFilter.disabled = true;
                        branchFilter.disabled = true;
                        sortFilter.disabled = true;
                    }
                    
                    // Set default hire date
                    const hireDateInput = document.getElementById('hireDate');
                    if (hireDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        hireDateInput.value = today;
                    }
                });

                // Photo Preview
                function previewPhoto(input) {
                    const preview = document.getElementById('photoPreview');
                    if (!preview) return;
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    }
                    
                    if (input.files && input.files[0]) {
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                // View User Details
                function viewUser(id) {
                    const formData = new FormData();
                    formData.append('action', 'get_user');
                    formData.append('user_id', id);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const user = data.data;
                            
                            // Format dates
                            const hireDate = user.hire_date ? new Date(user.hire_date).toLocaleDateString() : 'Not set';
                            const dob = user.date_of_birth ? new Date(user.date_of_birth).toLocaleDateString() : 'Not set';
                            const created = new Date(user.created_at).toLocaleDateString();
                            
                            // Get role display text
                            const roleText = user.role.charAt(0).toUpperCase() + user.role.slice(1).replace('_', ' ');
                            
                            Swal.fire({
                                title: `<strong>${user.full_name}</strong>`,
                                html: `
                                    <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                                        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.full_name)}&background=2a5c8b&color=fff&size=100" 
                                                 alt="${user.full_name}" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #2a5c8b;">
                                            <div>
                                                <p><b>Role:</b> <span style="color: #2a5c8b;">${roleText}</span></p>
                                                <p><b>Status:</b> <span style="color: ${user.status === 'active' ? '#27ae60' : '#e74c3c'}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span></p>
                                                <p><b>Gender:</b> ${user.gender || 'Not set'}</p>
                                            </div>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                            <div>
                                                <p><b>Username:</b> ${user.username}</p>
                                                <p><b>Email:</b> ${user.email}</p>
                                                <p><b>Phone:</b> ${user.phone || 'Not set'}</p>
                                            </div>
                                            <div>
                                                <p><b>Date of Birth:</b> ${dob}</p>
                                                <p><b>Hire Date:</b> ${hireDate}</p>
                                                <p><b>Account Created:</b> ${created}</p>
                                            </div>
                                        </div>
                                        
                                        ${user.address ? `<p><b>Address:</b> ${user.address}</p>` : ''}
                                        
                                        ${user.emergency_contact ? `<p><b>Emergency Contact:</b> ${user.emergency_contact}${user.emergency_phone ? ` (${user.emergency_phone})` : ''}</p>` : ''}
                                        
                                        ${user.notes ? `<hr><p><b>Notes:</b><br>${user.notes}</p>` : ''}
                                    </div>
                                `,
                                width: 600,
                                showCloseButton: true,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to load user details', 'error');
                    });
                }

                // Modal Functions
                function openUserManagementModal() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can add new users.', 'error');
                        return;
                    }
                    
                    console.log('Opening User Management Modal');
                    
                    document.getElementById('modalTitle').textContent = 'Add New User';
                    
                    // Reset the form
                    const form = document.getElementById('userForm');
                    if (form) {
                        form.reset();
                    }
                    
                    // Clear user ID
                    document.getElementById('userId').value = '';
                    
                    // Reset photo preview
                    document.getElementById('photoPreview').src = 'https://ui-avatars.com/api/?name=New+User&background=2a5c8b&color=fff&size=120';
                    
                    // Make password fields required
                    document.getElementById('password').required = true;
                    document.getElementById('confirmPassword').required = true;
                    document.getElementById('passwordLabel').classList.add('required');
                    
                    // Set default hire date
                    const hireDateInput = document.getElementById('hireDate');
                    if (hireDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        hireDateInput.value = today;
                    }
                    
                    // Show modal
                    const modal = document.getElementById('userManagementModal');
                    if (modal) {
                        modal.classList.add('active');
                    }
                }

                function editUser(id) {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can edit users.', 'error');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'get_user');
                    formData.append('user_id', id);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const user = data.data;
                            
                            document.getElementById('modalTitle').textContent = 'Edit User';
                            
                            // Fill form with user data
                            document.getElementById('userId').value = user.user_id;
                            document.getElementById('photoPreview').src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.full_name) + '&background=2a5c8b&color=fff&size=120';
                            document.getElementById('firstName').value = user.first_name;
                            document.getElementById('surname').value = user.surname;
                            document.getElementById('gender').value = user.gender;
                            document.getElementById('email').value = user.email;
                            document.getElementById('phone').value = user.phone;
                            document.getElementById('username').value = user.username;
                            document.getElementById('role').value = user.role;
                            document.getElementById('branch_id').value = user.branch_id || '';
                            document.getElementById('dateOfBirth').value = user.date_of_birth;
                            document.getElementById('hireDate').value = user.hire_date;
                            document.getElementById('address').value = user.address || '';
                            document.getElementById('emergencyContact').value = user.emergency_contact || '';
                            document.getElementById('emergencyPhone').value = user.emergency_phone || '';
                            document.getElementById('status').value = user.status;
                            document.getElementById('notes').value = user.notes || '';
                            
                            // Make password fields optional for editing
                            document.getElementById('password').required = false;
                            document.getElementById('confirmPassword').required = false;
                            document.getElementById('passwordLabel').classList.remove('required');
                            
                            // Show modal
                            const modal = document.getElementById('userManagementModal');
                            if (modal) {
                                modal.classList.add('active');
                            }
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to load user details', 'error');
                    });
                }

                function closeUserManagementModal() {
                    const modal = document.getElementById('userManagementModal');
                    if (modal) {
                        modal.classList.remove('active');
                    }
                    
                    // Reset password requirement for next time
                    document.getElementById('password').required = true;
                    document.getElementById('confirmPassword').required = true;
                    document.getElementById('passwordLabel').classList.add('required');
                }

                // Save User Function
                function saveUser() {
                    const form = document.getElementById('userForm');
                    const userId = document.getElementById('userId').value;
                    const isEdit = !!userId;

                    // For new users, validate all required fields
                    if (!isEdit && !form.checkValidity()) {
                        Swal.fire('Error!', 'Please fill in all required fields.', 'error');
                        return;
                    }

                    // Check if it's edit mode and passwords are filled
                    if (isEdit) {
                        const password = document.getElementById('password').value;
                        const confirmPassword = document.getElementById('confirmPassword').value;
                        
                        // Only validate passwords if they are filled
                        if (password || confirmPassword) {
                            if (password !== confirmPassword) {
                                Swal.fire('Error!', 'Passwords do not match!', 'error');
                                return;
                            }
                            if (password.length < 8) {
                                Swal.fire('Error!', 'Password must be at least 8 characters long!', 'error');
                                return;
                            }
                        }
                    } else {
                        // For new users, passwords are required
                        const password = document.getElementById('password').value;
                        const confirmPassword = document.getElementById('confirmPassword').value;
                        
                        if (password !== confirmPassword) {
                            Swal.fire('Error!', 'Passwords do not match!', 'error');
                            return;
                        }
                        if (password.length < 8) {
                            Swal.fire('Error!', 'Password must be at least 8 characters long!', 'error');
                            return;
                        }
                    }

                    const formData = new FormData();
                    formData.append('action', isEdit ? 'edit_user' : 'add_user');
                    formData.append('user_id', userId);
                    formData.append('first_name', document.getElementById('firstName').value);
                    formData.append('surname', document.getElementById('surname').value);
                    formData.append('username', document.getElementById('username').value);
                    formData.append('email', document.getElementById('email').value);
                    formData.append('role', document.getElementById('role').value);
                    formData.append('branch_id', document.getElementById('branch_id').value);
                    formData.append('gender', document.getElementById('gender').value);
                    formData.append('phone', document.getElementById('phone').value);
                    formData.append('date_of_birth', document.getElementById('dateOfBirth').value);
                    formData.append('hire_date', document.getElementById('hireDate').value);
                    formData.append('address', document.getElementById('address').value);
                    formData.append('emergency_contact', document.getElementById('emergencyContact').value);
                    formData.append('emergency_phone', document.getElementById('emergencyPhone').value);
                    formData.append('status', document.getElementById('status').value);
                    formData.append('notes', document.getElementById('notes').value);
                    
                    // Only include password if filled
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;
                    if (password) {
                        formData.append('password', password);
                        formData.append('confirm_password', confirmPassword);
                    } else if (!isEdit) {
                        formData.append('password', password);
                        formData.append('confirm_password', confirmPassword);
                    }

                    // Show loading
                    Swal.fire({
                        title: isEdit ? 'Updating User...' : 'Adding User...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            closeUserManagementModal();
                            loadUsers();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                            if (data.error) {
                                console.error('Server error:', data.error);
                            }
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to save user', 'error');
                    });
                }

                // Delete User Functions
                function showDeleteConfirm(id, name) {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can delete users.', 'error');
                        return;
                    }
                    
                    Swal.fire({
                        title: 'Delete User?',
                        html: `Are you sure you want to delete <strong>${name}</strong>?<br><br>
                              <span style="color: #e74c3c; font-size: 0.9em;">
                              <ion-icon name="warning-outline"></ion-icon> 
                              This action cannot be undone and all user data will be permanently removed.
                              </span>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c',
                        cancelButtonColor: '#7f8c8d',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel',
                        width: 500
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteUser(id);
                        }
                    });
                }

                function deleteUser(id) {
                    if (!isAdmin) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_user');
                    formData.append('user_id', id);

                    // Show loading
                    Swal.fire({
                        title: 'Deleting User...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadUsers();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to delete user', 'error');
                    });
                }

                // Toggle User Status
                function toggleUserStatus(id, status, name) {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can change user status.', 'error');
                        return;
                    }
                    
                    const action = status === 'active' ? 'enable' : 'disable';
                    const actionText = status === 'active' ? 'Enable' : 'Disable';
                    
                    Swal.fire({
                        title: `${actionText} User?`,
                        html: `Are you sure you want to ${action} <strong>${name}</strong>?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: status === 'active' ? '#27ae60' : '#f39c12',
                        cancelButtonColor: '#7f8c8d',
                        confirmButtonText: `Yes, ${action} it!`,
                        cancelButtonText: 'Cancel',
                        width: 500
                    }).then((result) => {
                        if (result.isConfirmed) {
                            changeUserStatus(id, status);
                        }
                    });
                }

                function changeUserStatus(id, status) {
                    if (!isAdmin) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'toggle_user_status');
                    formData.append('user_id', id);
                    formData.append('status', status);

                    // Show loading
                    Swal.fire({
                        title: 'Updating User Status...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadUsers();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to update user status', 'error');
                    });
                }

                // Load users from server
                function loadUsers() {
                    if (!isAdmin) return; // Non-admin users can't filter
                    
                    const formData = new FormData();
                    formData.append('action', 'get_users');
                    formData.append('search', searchInput.value);
                    formData.append('status', statusFilter.value);
                    formData.append('role', roleFilter.value);
                    formData.append('branch', branchFilter.value);
                    formData.append('sort', sortFilter.value);
                    formData.append('page', currentPage);
                    formData.append('limit', itemsPerPage);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            filteredUsers = data.data;
                            totalUsers = data.total;
                            renderUsersTable();
                            updateStatistics(data.data);
                            updatePaginationInfo();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to load users', 'error');
                    });
                }

                // Render Users Table
                function renderUsersTable() {
                    if (!tableBody) return;
                    
                    if (filteredUsers.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <ion-icon name="people-outline"></ion-icon>
                                    <h3>No users found</h3>
                                    <p>${isAdmin ? 'Try adjusting your search or filters' : 'No users in your branch.'}</p>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    filteredUsers.forEach(user => {
                        const statusClass = `status-${user.status}`;
                        const statusText = user.status.charAt(0).toUpperCase() + user.status.slice(1);
                        const roleClass = `role-${user.role}`;
                        const roleText = user.role.charAt(0).toUpperCase() + user.role.slice(1).replace('_', ' ');
                        
                        html += `
                            <tr>
                                <td>${user.sn}</td>
                                <td>
                                    <div class="user-photo">
                                        <img src="${user.photo}" alt="${user.full_name}">
                                    </div>
                                </td>
                                <td>
                                    <strong>${user.full_name}</strong><br>
                                    <small style="color: var(--dark-gray);">${user.email}</small><br>
                                    <small style="color: var(--dark-gray);">${user.username}</small>
                                </td>
                                <td>${user.gender || 'Not set'}</td>
                                <td><span class="role-badge ${roleClass}">${roleText}</span></td>
                                <td>${user.branch_name || 'Not assigned'}</td>
                                <td><span class="user-status ${statusClass}">${statusText}</span></td>
                                <td>
                                    <div class="user-actions">
                                        <button class="action-icon view" title="View Details" onclick="viewUser(${user.user_id})">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        ${isAdmin ? `
                                        <button class="action-icon edit" title="Edit" onclick="editUser(${user.user_id})">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon delete" title="Delete" onclick="showDeleteConfirm(${user.user_id}, '${user.full_name.replace(/'/g, "\\'")}')">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                        ${user.status === 'active' ? `
                                            <button class="action-icon disable" title="Disable" onclick="toggleUserStatus(${user.user_id}, 'inactive', '${user.full_name.replace(/'/g, "\\'")}')">
                                                <ion-icon name="lock-closed-outline"></ion-icon>
                                            </button>
                                        ` : `
                                            <button class="action-icon enable" title="Enable" onclick="toggleUserStatus(${user.user_id}, 'active', '${user.full_name.replace(/'/g, "\\'")}')">
                                                <ion-icon name="lock-open-outline"></ion-icon>
                                            </button>
                                        `}
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = html;
                }

                // Update Statistics
                function updateStatistics(users = filteredUsers) {
                    const activeUsers = users.filter(u => u.status === 'active').length;
                    const adminUsers = users.filter(u => u.role === 'admin').length;
                    const branchesCovered = [...new Set(users.map(u => u.branch_id).filter(id => id))].length;
                    
                    document.getElementById('totalUsers').textContent = users.length;
                    document.getElementById('activeUsers').textContent = activeUsers;
                    document.getElementById('adminUsers').textContent = adminUsers;
                    document.getElementById('branchesCovered').textContent = branchesCovered;
                }

                // Filter and Search Functions
                function filterUsers() {
                    if (!isAdmin) return;
                    currentPage = 1;
                    loadUsers();
                }

                function resetFilters() {
                    if (!isAdmin) return;
                    searchInput.value = '';
                    statusFilter.value = '';
                    roleFilter.value = '';
                    branchFilter.value = '';
                    sortFilter.value = 'name_asc';
                    currentPage = 1;
                    loadUsers();
                }

                function applyFilters() {
                    if (!isAdmin) return;
                    loadUsers();
                    Swal.fire({
                        icon: 'success',
                        title: 'Filters Applied!',
                        text: 'User list has been filtered.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                // Pagination Functions
                function updatePaginationInfo() {
                    if (!isAdmin) return;
                    
                    const total = totalUsers;
                    const start = (currentPage - 1) * itemsPerPage + 1;
                    const end = Math.min(currentPage * itemsPerPage, total);
                    
                    document.getElementById('paginationInfo').textContent = 
                        `Showing ${start} to ${end} of ${total} entries`;
                        
                    updatePaginationControls();
                }

                function updatePaginationControls() {
                    if (!isAdmin) return;
                    
                    const totalPages = Math.ceil(totalUsers / itemsPerPage);
                    const paginationControls = document.querySelector('.pagination-controls');
                    
                    if (!paginationControls) return;
                    
                    let html = `
                        <button class="pagination-btn" onclick="changePage('prev')" ${currentPage === 1 ? 'disabled' : ''}>
                            <ion-icon name="chevron-back-outline"></ion-icon>
                        </button>
                    `;
                    
                    // Show up to 5 page buttons
                    const startPage = Math.max(1, currentPage - 2);
                    const endPage = Math.min(totalPages, startPage + 4);
                    
                    for (let i = startPage; i <= endPage; i++) {
                        html += `
                            <button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                                ${i}
                            </button>
                        `;
                    }
                    
                    html += `
                        <button class="pagination-btn" onclick="changePage('next')" ${currentPage === totalPages ? 'disabled' : ''}>
                            <ion-icon name="chevron-forward-outline"></ion-icon>
                        </button>
                    `;
                    
                    paginationControls.innerHTML = html;
                }

                function changePage(direction) {
                    if (!isAdmin) return;
                    
                    if (direction === 'prev' && currentPage > 1) {
                        currentPage--;
                    } else if (direction === 'next' && currentPage < Math.ceil(totalUsers / itemsPerPage)) {
                        currentPage++;
                    }
                    loadUsers();
                }

                function goToPage(page) {
                    if (!isAdmin) return;
                    currentPage = page;
                    loadUsers();
                }

                // Utility Functions
                function refreshUsers() {
                    loadUsers();
                    Swal.fire({
                        icon: 'success',
                        title: 'Refreshed!',
                        text: 'User list has been refreshed.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                function exportUsers() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can export users.', 'error');
                        return;
                    }
                    
                    const csvContent = [
                        ['S/N', 'Full Name', 'Email', 'Username', 'Gender', 'Role', 'Branch', 'Status', 'Phone', 'Hire Date'],
                        ...filteredUsers.map(u => [
                            u.sn,
                            u.full_name,
                            u.email,
                            u.username,
                            u.gender,
                            u.role.charAt(0).toUpperCase() + u.role.slice(1).replace('_', ' '),
                            u.branch_name,
                            u.status,
                            u.phone,
                            u.hire_date_formatted
                        ])
                    ].map(row => row.join(',')).join('\n');

                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', `users_${new Date().toISOString().split('T')[0]}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Export Started!',
                        text: 'CSV file download has started.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }

                function printUsers() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can print users.', 'error');
                        return;
                    }
                    
                    const printContent = `
                        <html>
                        <head>
                            <title>Master Clinic Users Report</title>
                            <style>
                                body { font-family: Arial, sans-serif; margin: 20px; }
                                h1 { color: #2a5c8b; }
                                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                                th { background: #f5f7fa; padding: 10px; text-align: left; border: 1px solid #ddd; }
                                td { padding: 10px; border: 1px solid #ddd; }
                                .status-active { color: #27ae60; }
                                .status-inactive { color: #e74c3c; }
                                .print-header { display: flex; justify-content: space-between; margin-bottom: 30px; }
                                .print-date { color: #7f8c8d; }
                            </style>
                        </head>
                        <body>
                            <div class="print-header">
                                <h1>Master Clinic Users Report</h1>
                                <div class="print-date">Generated: ${new Date().toLocaleDateString()}</div>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                        <th>Phone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filteredUsers.map(user => `
                                        <tr>
                                            <td>${user.sn}</td>
                                            <td>${user.full_name}</td>
                                            <td>${user.email}</td>
                                            <td>${user.role.charAt(0).toUpperCase() + user.role.slice(1).replace('_', ' ')}</td>
                                            <td>${user.branch_name}</td>
                                            <td class="status-${user.status}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</td>
                                            <td>${user.phone}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <div style="margin-top: 30px; color: #7f8c8d; font-size: 12px;">
                                Total Users: ${filteredUsers.length} | Active: ${filteredUsers.filter(u => u.status === 'active').length}
                            </div>
                        </body>
                        </html>
                    `;

                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    printWindow.print();
                }

                // Close modals when clicking outside
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('modal-overlay')) {
                        closeUserManagementModal();
                    }
                });

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeUserManagementModal();
                    }
                    if (e.ctrlKey && e.key === 'f' && isAdmin) {
                        e.preventDefault();
                        searchInput.focus();
                    }
                    if (e.ctrlKey && e.key === 'n' && isAdmin) {
                        e.preventDefault();
                        openUserManagementModal();
                    }
                });
            </script>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
>>>>>>> ebf5f55ccd0a1b48a75b40abdbae6c5de9fe43f4
</html>