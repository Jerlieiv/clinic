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
$user_role = $_SESSION['user_role'] ?? 'staff';  // CHANGED FROM 'role' TO 'user_role'
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
        case 'add_branch':
            // Validate required fields
            $required = ['branch_code', 'branch_name', 'district', 'address', 'email', 'phone', 'manager_name'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $response['message'] = "All required fields must be filled!";
                    echo json_encode($response);
                    exit;
                }
            }
            
            // Check if branch code already exists
            $check_stmt = $conn->prepare("SELECT branch_id FROM branches WHERE branch_code = ?");
            $check_stmt->bind_param("s", $_POST['branch_code']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $response['message'] = "Branch code already exists!";
                $check_stmt->close();
                echo json_encode($response);
                exit;
            }
            $check_stmt->close();
            
            // Insert branch
            $stmt = $conn->prepare("INSERT INTO branches (branch_code, branch_name, district, address, email, phone, manager_name, status, opening_date, latitude, longitude, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $status = $_POST['status'] ?? 'active';
            $opening_date = !empty($_POST['opening_date']) ? $_POST['opening_date'] : null;
            $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
            $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
            $description = $_POST['description'] ?? '';
            
            $stmt->bind_param("ssssssssssss", 
                $_POST['branch_code'],
                $_POST['branch_name'],
                $_POST['district'],
                $_POST['address'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['manager_name'],
                $status,
                $opening_date,
                $latitude,
                $longitude,
                $description
            );
            
            if ($stmt->execute()) {
                $new_branch_id = $stmt->insert_id;
                
                // Insert business hours
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                $hours_stmt = $conn->prepare("INSERT INTO branch_hours (branch_id, day_of_week, opening_time, closing_time, is_closed) VALUES (?, ?, ?, ?, ?)");
                
                foreach ($days as $day) {
                    $open_time = $_POST[$day . '_open'] ?? '08:00';
                    $close_time = $_POST[$day . '_close'] ?? '18:00';
                    $is_closed = isset($_POST[$day . '_closed']) ? 1 : 0;
                    
                    $hours_stmt->bind_param("isssi", $new_branch_id, $day, $open_time, $close_time, $is_closed);
                    $hours_stmt->execute();
                }
                $hours_stmt->close();
                
                // Log activity
                logActivity($conn, $user_id, $user_name, 'ADD_BRANCH', 
                    "Added new branch: {$_POST['branch_name']} ({$_POST['branch_code']})", 
                    $new_branch_id, $_POST['branch_name']);
                
                $response['success'] = true;
                $response['message'] = "Branch added successfully!";
                $response['branch_id'] = $new_branch_id;
            } else {
                $response['message'] = "Error adding branch: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'edit_branch':
            $branch_id = intval($_POST['branch_id']);
            
            // Check if branch exists
            $check_stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = ?");
            $check_stmt->bind_param("i", $branch_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $response['message'] = "Branch not found!";
                $check_stmt->close();
                echo json_encode($response);
                exit;
            }
            $check_stmt->close();
            
            // Update branch
            $stmt = $conn->prepare("UPDATE branches SET branch_name = ?, district = ?, address = ?, email = ?, phone = ?, manager_name = ?, status = ?, opening_date = ?, latitude = ?, longitude = ?, description = ? WHERE branch_id = ?");
            
            $opening_date = !empty($_POST['opening_date']) ? $_POST['opening_date'] : null;
            $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
            $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
            
            $stmt->bind_param("sssssssssssi", 
                $_POST['branch_name'],
                $_POST['district'],
                $_POST['address'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['manager_name'],
                $_POST['status'],
                $opening_date,
                $latitude,
                $longitude,
                $_POST['description'],
                $branch_id
            );
            
            if ($stmt->execute()) {
                // Update business hours
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                $delete_hours = $conn->prepare("DELETE FROM branch_hours WHERE branch_id = ?");
                $delete_hours->bind_param("i", $branch_id);
                $delete_hours->execute();
                $delete_hours->close();
                
                $hours_stmt = $conn->prepare("INSERT INTO branch_hours (branch_id, day_of_week, opening_time, closing_time, is_closed) VALUES (?, ?, ?, ?, ?)");
                
                foreach ($days as $day) {
                    $open_time = $_POST[$day . '_open'] ?? '08:00';
                    $close_time = $_POST[$day . '_close'] ?? '18:00';
                    $is_closed = isset($_POST[$day . '_closed']) ? 1 : 0;
                    
                    $hours_stmt->bind_param("isssi", $branch_id, $day, $open_time, $close_time, $is_closed);
                    $hours_stmt->execute();
                }
                $hours_stmt->close();
                
                // Log activity
                logActivity($conn, $user_id, $user_name, 'EDIT_BRANCH', 
                    "Updated branch details: {$_POST['branch_name']}", 
                    $branch_id, $_POST['branch_name']);
                
                $response['success'] = true;
                $response['message'] = "Branch updated successfully!";
            } else {
                $response['message'] = "Error updating branch: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'delete_branch':
            $branch_id = intval($_POST['branch_id']);
            
            // Get branch info for logging
            $branch_stmt = $conn->prepare("SELECT branch_name, branch_code FROM branches WHERE branch_id = ?");
            $branch_stmt->bind_param("i", $branch_id);
            $branch_stmt->execute();
            $branch_result = $branch_stmt->get_result();
            
            if ($branch_result->num_rows === 0) {
                $response['message'] = "Branch not found!";
                $branch_stmt->close();
                echo json_encode($response);
                exit;
            }
            
            $branch_data = $branch_result->fetch_assoc();
            $branch_name = $branch_data['branch_name'];
            $branch_code = $branch_data['branch_code'];
            $branch_stmt->close();
            
            // Delete branch (cascade will delete hours too)
            $stmt = $conn->prepare("DELETE FROM branches WHERE branch_id = ?");
            $stmt->bind_param("i", $branch_id);
            
            if ($stmt->execute()) {
                // Log activity
                logActivity($conn, $user_id, $user_name, 'DELETE_BRANCH', 
                    "Deleted branch: {$branch_name} ({$branch_code})", 
                    $branch_id, $branch_name);
                
                $response['success'] = true;
                $response['message'] = "Branch deleted successfully!";
            } else {
                $response['message'] = "Error deleting branch: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'get_branch':
            $branch_id = intval($_POST['branch_id']);
            
            $stmt = $conn->prepare("SELECT * FROM branches WHERE branch_id = ?");
            $stmt->bind_param("i", $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $branch = $result->fetch_assoc();
                
                // Get business hours
                $hours_stmt = $conn->prepare("SELECT * FROM branch_hours WHERE branch_id = ?");
                $hours_stmt->bind_param("i", $branch_id);
                $hours_stmt->execute();
                $hours_result = $hours_stmt->get_result();
                
                $hours = [];
                while ($hour = $hours_result->fetch_assoc()) {
                    $hours[$hour['day_of_week']] = [
                        'open' => $hour['opening_time'],
                        'close' => $hour['closing_time'],
                        'closed' => (bool)$hour['is_closed']
                    ];
                }
                $hours_stmt->close();
                
                $branch['hours'] = $hours;
                $response['success'] = true;
                $response['data'] = $branch;
            } else {
                $response['message'] = "Branch not found!";
            }
            $stmt->close();
            break;
            
        case 'get_branches':
            // Build query based on filters and user role
            $where_clauses = [];
            $params = [];
            $types = "";
            
            // Only admin can see all branches, others see only their branch
            if ($user_role !== 'admin' && $current_branch_id) {
                $where_clauses[] = "b.branch_id = ?";
                $params[] = $current_branch_id;
                $types .= "i";
            }
            
            // Apply filters
            if (!empty($_POST['search'])) {
                $where_clauses[] = "(b.branch_name LIKE ? OR b.district LIKE ? OR b.email LIKE ? OR b.branch_code LIKE ?)";
                $search_term = "%{$_POST['search']}%";
                for ($i = 0; $i < 4; $i++) {
                    $params[] = $search_term;
                    $types .= "s";
                }
            }
            
            if (!empty($_POST['status'])) {
                $where_clauses[] = "b.status = ?";
                $params[] = $_POST['status'];
                $types .= "s";
            }
            
            if (!empty($_POST['district'])) {
                $where_clauses[] = "b.district = ?";
                $params[] = $_POST['district'];
                $types .= "s";
            }
            
            $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM branches b $where_sql";
            $count_stmt = $conn->prepare($count_sql);
            
            if (!empty($params)) {
                $count_stmt->bind_param($types, ...$params);
            }
            
            $count_stmt->execute();
            $count_result = $count_stmt->get_result()->fetch_assoc();
            $total_branches = $count_result['total'];
            $count_stmt->close();
            
            // Get branches with sorting
            $sort_by = $_POST['sort'] ?? 'branch_name ASC';
            $allowed_sorts = [
                'name_asc' => 'b.branch_name ASC',
                'name_desc' => 'b.branch_name DESC',
                'date_new' => 'b.created_at DESC',
                'date_old' => 'b.created_at ASC',
                'status' => 'b.status ASC'
            ];
            
            $sort_sql = $allowed_sorts[$_POST['sort'] ?? 'name_asc'] ?? 'b.branch_name ASC';
            
            // Pagination
            $page = intval($_POST['page'] ?? 1);
            $limit = intval($_POST['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT b.*, 
                    (SELECT COUNT(*) FROM users WHERE branch_id = b.branch_id) as staff_count
                    FROM branches b 
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
            
            $branches = [];
            $sn = $offset + 1;
            while ($row = $result->fetch_assoc()) {
                $row['sn'] = $sn++;
                $branches[] = $row;
            }
            
            $response['success'] = true;
            $response['data'] = $branches;
            $response['total'] = $total_branches;
            $response['page'] = $page;
            $response['pages'] = ceil($total_branches / $limit);
            
            $stmt->close();
            break;
            
        default:
            $response['message'] = "Invalid action!";
    }
    
    echo json_encode($response);
    exit;
}

// Get initial branches data for page load - DON'T CLOSE CONNECTION YET!
$initial_branches = [];
$initial_stats = [
    'total' => 0,
    'active' => 0,
    'cities' => 0,
    'staff' => 0
];

// Build query based on user role
if ($user_role === 'admin') {
    // Admin sees all branches
    $sql = "SELECT b.*, 
            (SELECT COUNT(*) FROM users WHERE branch_id = b.branch_id) as staff_count
            FROM branches b 
            ORDER BY b.branch_name ASC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    $sn = 1;
    while ($row = $result->fetch_assoc()) {
        $row['sn'] = $sn++;
        $initial_branches[] = $row;
    }
    
    // Get statistics
    $stats_sql = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                  COUNT(DISTINCT district) as cities,
                  SUM((SELECT COUNT(*) FROM users WHERE branch_id = branches.branch_id)) as staff
                  FROM branches";
    $stats_result = $conn->query($stats_sql);
    if ($stats_row = $stats_result->fetch_assoc()) {
        $initial_stats = $stats_row;
    }
} else {
    // Non-admin users see only their branch
    if ($current_branch_id) {
        $sql = "SELECT b.*, 
                (SELECT COUNT(*) FROM users WHERE branch_id = b.branch_id) as staff_count
                FROM branches b 
                WHERE b.branch_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $current_branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sn = 1;
        while ($row = $result->fetch_assoc()) {
            $row['sn'] = $sn++;
            $initial_branches[] = $row;
        }
        $stmt->close();
        
        // Get statistics for this branch
        $stats_sql = "SELECT 
                      1 as total,
                      SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                      1 as cities,
                      (SELECT COUNT(*) FROM users WHERE branch_id = ?) as staff
                      FROM branches WHERE branch_id = ?";
        
        $stmt = $conn->prepare($stats_sql);
        $stmt->bind_param("ii", $current_branch_id, $current_branch_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        if ($stats_row = $stats_result->fetch_assoc()) {
            $initial_stats = $stats_row;
        }
        $stmt->close();
    }
}

// Get unique districts for filter
$districts_result = $conn->query("SELECT DISTINCT district FROM branches ORDER BY district");
$districts = [];
while ($row = $districts_result->fetch_assoc()) {
    $districts[] = $row['district'];
}

// Only close connection after ALL database operations
// $conn->close(); // Don't close here, let it close automatically
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

            <style>
               /* ================== Branch Configuration Styles ============== */
                .branch-config-section {
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

                /* Statistics Cards */
                .branch-stats {
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
                    border-color: var(--success);
                }

                .stat-card:nth-child(3) {
                    border-color: var(--warning);
                }

                .stat-card:nth-child(4) {
                    border-color: var(--accent);
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
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .stat-card:nth-child(3) .stat-icon {
                    background: rgba(243, 156, 18, 0.1);
                    color: var(--warning);
                }

                .stat-card:nth-child(4) .stat-icon {
                    background: rgba(26, 188, 156, 0.1);
                    color: var(--accent);
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

                /* Table Styles */
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

                .branches-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .branches-table thead {
                    background: var(--light-gray);
                }

                .branches-table th {
                    padding: 18px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    border-bottom: 2px solid var(--gray);
                }

                .branches-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.3s ease;
                }

                .branches-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .branches-table td {
                    padding: 18px 20px;
                    color: var(--black);
                    font-size: 0.95rem;
                }

                .branch-status {
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

                .status-maintenance {
                    background: rgba(243, 156, 18, 0.1);
                    color: var(--warning);
                }

                .status-closed {
                    background: rgba(127, 140, 141, 0.1);
                    color: var(--dark-gray);
                }

                .branch-actions {
                    display: flex;
                    gap: 10px;
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
                    min-height: 100px;
                    resize: vertical;
                }

                .form-row {
                    grid-column: 1 / -1;
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                }

                /* ================== Improved Business Hours Styles ============== */
                .hours-section {
                    grid-column: 1 / -1;
                    background: var(--white);
                    border-radius: 10px;
                    padding: 20px;
                    margin-top: 10px;
                    border: 1px solid var(--gray);
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                }

                .hours-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }

                .hours-header h4 {
                    margin: 0;
                    color: var(--primary);
                    font-size: 1.1rem;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .hours-controls {
                    display: flex;
                    gap: 10px;
                }

                .hours-btn {
                    padding: 6px 15px;
                    background: var(--light-gray);
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.85rem;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    transition: all 0.3s ease;
                }

                .hours-btn:hover {
                    background: var(--primary);
                    color: white;
                    border-color: var(--primary);
                }

                .hours-btn:hover ion-icon {
                    color: white;
                }

                .hours-grid {
                    display: grid;
                    gap: 12px;
                }

                .hour-row {
                    display: grid;
                    grid-template-columns: 120px 1fr auto;
                    align-items: center;
                    gap: 15px;
                    padding: 12px 15px;
                    background: var(--light-gray);
                    border-radius: 8px;
                    transition: all 0.3s ease;
                }

                .hour-row:hover {
                    background: rgba(42, 92, 139, 0.05);
                    transform: translateX(2px);
                }

                .day-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .day-label {
                    font-weight: 500;
                    color: var(--black);
                    min-width: 80px;
                }

                .day-abbr {
                    display: none;
                }

                .time-controls {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    flex-wrap: nowrap;
                }

                .time-group {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    background: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    border: 1px solid var(--gray);
                    flex: 1;
                    max-width: 200px;
                }

                .time-group label {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    min-width: 40px;
                }

                .time-input {
                    flex: 1;
                    border: none;
                    background: none;
                    font-size: 0.95rem;
                    min-width: 80px;
                    color: var(--black);
                }

                .time-input:focus {
                    outline: none;
                }

                .time-separator {
                    color: var(--dark-gray);
                    font-weight: 500;
                    padding: 0 5px;
                }

                .closed-toggle {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    cursor: pointer;
                    padding: 8px 15px;
                    background: white;
                    border-radius: 6px;
                    border: 1px solid var(--gray);
                    transition: all 0.3s ease;
                }

                .closed-toggle:hover {
                    background: var(--light-gray);
                }

                .closed-toggle input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    cursor: pointer;
                    accent-color: var(--primary);
                }

                .closed-toggle.closed-active {
                    background: rgba(231, 76, 60, 0.1);
                    border-color: var(--danger);
                    color: var(--danger);
                }

                .closed-toggle.closed-active input[type="checkbox"] {
                    accent-color: var(--danger);
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

                /* Responsive Design for Hours Section */
                @media (max-width: 768px) {
                    .hour-row {
                        grid-template-columns: 1fr;
                        gap: 12px;
                    }

                    .day-info {
                        justify-content: space-between;
                    }

                    .time-controls {
                        flex-direction: column;
                        align-items: stretch;
                        gap: 12px;
                    }

                    .time-group {
                        max-width: none;
                    }

                    .closed-toggle {
                        justify-content: center;
                    }

                    .day-abbr {
                        display: inline;
                        background: var(--primary);
                        color: white;
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 0.85rem;
                        font-weight: 500;
                    }

                    .day-label {
                        min-width: auto;
                    }
                }

                /* Responsive Design */
                @media (max-width: 1200px) {
                    .modal-form {
                        grid-template-columns: 1fr;
                    }
                    
                    .form-row {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 768px) {
                    .branch-config-section {
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

                    .branch-stats {
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

                    .branches-table {
                        display: block;
                        overflow-x: auto;
                    }

                    .hours-grid {
                        grid-template-columns: 1fr;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }
                }

                @media (max-width: 480px) {
                    .branch-stats {
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

            <!-- ================== Branch Configuration Content ============== -->
            <div class="branch-config-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Branch Configuration</h1>
                        <p>Manage all pharmacy branches and their settings</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshBranches()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <?php if ($user_role === 'admin'): ?>
                        <button class="action-btn primary" onclick="openAddBranchModal()">
                            <ion-icon name="add-outline"></ion-icon>
                            Add New Branch
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Branch Statistics -->
                <div class="branch-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="business-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalBranches"><?php echo $initial_stats['total']; ?></h3>
                            <p>Total Branches</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeBranches"><?php echo $initial_stats['active']; ?></h3>
                            <p>Active Branches</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalStaff"><?php echo $initial_stats['staff']; ?></h3>
                            <p>Total Staff</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="location-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalCities"><?php echo $initial_stats['cities']; ?></h3>
                            <p>Cities Covered</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search branches by name, district, or email..." 
                               onkeyup="filterBranches()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterBranches()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>District</label>
                            <select id="districtFilter" onchange="filterBranches()">
                                <option value="">All Districts</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo htmlspecialchars($district); ?>">
                                        <?php echo htmlspecialchars($district); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterBranches()">
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="date_new">Date Added (Newest)</option>
                                <option value="date_old">Date Added (Oldest)</option>
                                <option value="status">Status</option>
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

                <!-- Branches Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>All Branches</h2>
                        <div class="table-actions">
                            <?php if ($user_role === 'admin'): ?>
                            <button class="export-btn" onclick="exportBranches()">
                                <ion-icon name="download-outline"></ion-icon>
                                Export CSV
                            </button>
                            <button class="export-btn" onclick="printBranches()">
                                <ion-icon name="print-outline"></ion-icon>
                                Print
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="branches-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>Branch Name</th>
                                    <th>District</th>
                                    <th>Email</th>
                                    <th>Phone Number</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="branchesTableBody">
                                <?php if (empty($initial_branches)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <ion-icon name="business-outline"></ion-icon>
                                        <h3>No branches found</h3>
                                        <p><?php echo $user_role === 'admin' ? 'Add your first branch!' : 'No branch assigned to you.'; ?></p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_branches as $branch): ?>
                                <tr>
                                    <td><?php echo $branch['sn']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($branch['branch_name']); ?></strong><br>
                                        <small style="color: var(--dark-gray);">Code: <?php echo htmlspecialchars($branch['branch_code']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($branch['district']); ?></td>
                                    <td><?php echo htmlspecialchars($branch['email']); ?></td>
                                    <td><?php echo htmlspecialchars($branch['phone']); ?></td>
                                    <td>
                                        <span class="branch-status status-<?php echo $branch['status']; ?>">
                                            <?php echo ucfirst($branch['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="branch-actions">
                                            <button class="action-icon view" title="View Details" onclick="viewBranch(<?php echo $branch['branch_id']; ?>)">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </button>
                                            <?php if ($user_role === 'admin'): ?>
                                            <button class="action-icon edit" title="Edit" onclick="editBranch(<?php echo $branch['branch_id']; ?>)">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="action-icon delete" title="Delete" onclick="showDeleteConfirm(<?php echo $branch['branch_id']; ?>, '<?php echo addslashes($branch['branch_name']); ?>')">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </button>
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
                            Showing <?php echo count($initial_branches) > 0 ? 1 : 0; ?> to <?php echo count($initial_branches); ?> of <?php echo $initial_stats['total']; ?> entries
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

            <!-- Add/Edit Branch Modal -->
            <div class="modal-overlay" id="branchModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add New Branch</h3>
                        <button class="modal-close" onclick="closeModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="branchForm" class="modal-form">
                            <input type="hidden" id="branchId">
                            
                            <div class="form-group">
                                <label for="branch_code" class="required">Branch Code</label>
                                <input type="text" id="branch_code" placeholder="e.g., PHC-BAL" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="branch_name" class="required">Branch Name</label>
                                <input type="text" id="branch_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="district" class="required">District</label>
                                <select id="district" required>
                                    <option value="">Select District</option>
                                    <option value="Balaka">Balaka</option>
                                    <option value="Blantyre">Blantyre</option>
                                    <option value="Lilongwe">Lilongwe</option>
                                    <option value="Mzuzu">Mzuzu</option>
                                    <option value="Zomba">Zomba</option>
                                    <option value="Mulanje">Mulanje</option>
                                    <option value="Thyolo">Thyolo</option>
                                    <option value="Kasungu">Kasungu</option>
                                    <option value="Salima">Salima</option>
                                    <option value="Mchinji">Mchinji</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="required">Physical Address</label>
                                <input type="text" id="address" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="required">Email Address</label>
                                <input type="email" id="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="required">Phone Number</label>
                                <input type="tel" id="phone" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="manager_name" class="required">Branch Manager</label>
                                <input type="text" id="manager_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="status" class="required">Status</label>
                                <select id="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="opening_date">Opening Date</label>
                                <input type="date" id="opening_date">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="latitude">Latitude</label>
                                    <input type="text" id="latitude" placeholder="e.g., -14.9821">
                                </div>
                                
                                <div class="form-group">
                                    <label for="longitude">Longitude</label>
                                    <input type="text" id="longitude" placeholder="e.g., 34.9561">
                                </div>
                            </div>
                            
                            <!-- Improved Business Hours Section -->
                            <div class="hours-section">
                                <div class="hours-header">
                                    <h4>
                                        <ion-icon name="time-outline"></ion-icon>
                                        Business Hours
                                    </h4>
                                    <div class="hours-controls">
                                        <button type="button" class="hours-btn" onclick="setAllHours('08:00', '18:00')">
                                            <ion-icon name="repeat-outline"></ion-icon>
                                            Set Weekdays
                                        </button>
                                        <button type="button" class="hours-btn" onclick="setAllClosed()">
                                            <ion-icon name="close-circle-outline"></ion-icon>
                                            Set All Closed
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="hours-grid" id="businessHours">
                                    <!-- Hours rows will be generated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description / Notes</label>
                                <textarea id="description" placeholder="Additional information about this branch..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveBranch()">
                            Save Branch
                        </button>
                    </div>
                </div>
            </div>

            <!-- Include SweetAlert -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <script>
                // Initialize Variables
                let currentPage = 1;
                const itemsPerPage = 10;
                let filteredBranches = <?php echo json_encode($initial_branches); ?>;
                let totalBranches = <?php echo $initial_stats['total']; ?>;
                let currentSort = 'name_asc';
                
                // User role from PHP
                const userRole = '<?php echo $user_role; ?>';
                const userBranchId = <?php echo $current_branch_id ?: 'null'; ?>;
                const isAdmin = userRole === 'admin';

                // DOM Elements
                const tableBody = document.getElementById('branchesTableBody');
                const businessHoursContainer = document.getElementById('businessHours');
                const branchModal = document.getElementById('branchModal');
                const searchInput = document.getElementById('searchInput');
                const statusFilter = document.getElementById('statusFilter');
                const districtFilter = document.getElementById('districtFilter');
                const sortFilter = document.getElementById('sortFilter');

                // Initialize Page
                document.addEventListener('DOMContentLoaded', function() {
                    generateBusinessHours();
                    
                    // Disable filters for non-admin users
                    if (!isAdmin) {
                        searchInput.disabled = true;
                        statusFilter.disabled = true;
                        districtFilter.disabled = true;
                        sortFilter.disabled = true;
                    }
                });

                // Generate Business Hours Inputs
                function generateBusinessHours() {
                    const days = [
                        { id: 'monday', label: 'Monday', abbr: 'MON' },
                        { id: 'tuesday', label: 'Tuesday', abbr: 'TUE' },
                        { id: 'wednesday', label: 'Wednesday', abbr: 'WED' },
                        { id: 'thursday', label: 'Thursday', abbr: 'THU' },
                        { id: 'friday', label: 'Friday', abbr: 'FRI' },
                        { id: 'saturday', label: 'Saturday', abbr: 'SAT' },
                        { id: 'sunday', label: 'Sunday', abbr: 'SUN' }
                    ];

                    businessHoursContainer.innerHTML = '';
                    days.forEach(day => {
                        const hourRow = document.createElement('div');
                        hourRow.className = 'hour-row';
                        hourRow.innerHTML = `
                            <div class="day-info">
                                <span class="day-label">${day.label}</span>
                                <span class="day-abbr">${day.abbr}</span>
                            </div>
                            <div class="time-controls">
                                <div class="time-group">
                                    <label>Open:</label>
                                    <input type="time" id="${day.id}_open" class="time-input" value="08:00">
                                </div>
                                <span class="time-separator">-</span>
                                <div class="time-group">
                                    <label>Close:</label>
                                    <input type="time" id="${day.id}_close" class="time-input" value="18:00">
                                </div>
                            </div>
                            <label class="closed-toggle" id="${day.id}_toggle">
                                <input type="checkbox" id="${day.id}_closed" onchange="toggleClosed('${day.id}')">
                                <span>Closed</span>
                            </label>
                        `;
                        businessHoursContainer.appendChild(hourRow);
                    });
                    
                    // Set Sunday as closed by default for new branches
                    toggleClosed('sunday');
                    document.getElementById('sunday_closed').checked = true;
                }

                // Toggle closed state for a day
                function toggleClosed(dayId) {
                    const closedCheckbox = document.getElementById(`${dayId}_closed`);
                    const toggleLabel = document.getElementById(`${dayId}_toggle`);
                    const openInput = document.getElementById(`${dayId}_open`);
                    const closeInput = document.getElementById(`${dayId}_close`);
                    
                    if (closedCheckbox.checked) {
                        toggleLabel.classList.add('closed-active');
                        openInput.disabled = true;
                        closeInput.disabled = true;
                        openInput.value = '00:00';
                        closeInput.value = '00:00';
                        openInput.style.opacity = '0.5';
                        closeInput.style.opacity = '0.5';
                    } else {
                        toggleLabel.classList.remove('closed-active');
                        openInput.disabled = false;
                        closeInput.disabled = false;
                        openInput.style.opacity = '1';
                        closeInput.style.opacity = '1';
                        
                        // Set default times if empty
                        if (!openInput.value) openInput.value = '08:00';
                        if (!closeInput.value) closeInput.value = '18:00';
                    }
                }

                // Set all hours to specific times
                function setAllHours(openTime, closeTime) {
                    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    days.forEach(day => {
                        const openInput = document.getElementById(`${day}_open`);
                        const closeInput = document.getElementById(`${day}_close`);
                        const closedCheckbox = document.getElementById(`${day}_closed`);
                        const toggleLabel = document.getElementById(`${day}_toggle`);
                        
                        if (day === 'saturday') {
                            openInput.value = '09:00';
                            closeInput.value = '17:00';
                        } else if (day === 'sunday') {
                            openInput.value = '09:00';
                            closeInput.value = '14:00';
                        } else {
                            openInput.value = openTime;
                            closeInput.value = closeTime;
                        }
                        
                        // Enable inputs and uncheck closed
                        closedCheckbox.checked = false;
                        toggleLabel.classList.remove('closed-active');
                        openInput.disabled = false;
                        closeInput.disabled = false;
                        openInput.style.opacity = '1';
                        closeInput.style.opacity = '1';
                    });
                }

                // Set all days as closed
                function setAllClosed() {
                    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    days.forEach(day => {
                        const openInput = document.getElementById(`${day}_open`);
                        const closeInput = document.getElementById(`${day}_close`);
                        const closedCheckbox = document.getElementById(`${day}_closed`);
                        const toggleLabel = document.getElementById(`${day}_toggle`);
                        
                        closedCheckbox.checked = true;
                        toggleLabel.classList.add('closed-active');
                        openInput.value = '00:00';
                        closeInput.value = '00:00';
                        openInput.disabled = true;
                        closeInput.disabled = true;
                        openInput.style.opacity = '0.5';
                        closeInput.style.opacity = '0.5';
                    });
                }

                // Load branch hours into form
                function loadBranchHours(branch) {
                    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    days.forEach(day => {
                        const openInput = document.getElementById(`${day}_open`);
                        const closeInput = document.getElementById(`${day}_close`);
                        const closedCheckbox = document.getElementById(`${day}_closed`);
                        const toggleLabel = document.getElementById(`${day}_toggle`);
                        
                        if (branch.hours && branch.hours[day]) {
                            const hours = branch.hours[day];
                            openInput.value = hours.open ? hours.open.substring(0, 5) : '08:00';
                            closeInput.value = hours.close ? hours.close.substring(0, 5) : '18:00';
                            closedCheckbox.checked = hours.closed;
                            
                            if (hours.closed) {
                                toggleLabel.classList.add('closed-active');
                                openInput.disabled = true;
                                closeInput.disabled = true;
                                openInput.style.opacity = '0.5';
                                closeInput.style.opacity = '0.5';
                            } else {
                                toggleLabel.classList.remove('closed-active');
                                openInput.disabled = false;
                                closeInput.disabled = false;
                                openInput.style.opacity = '1';
                                closeInput.style.opacity = '1';
                            }
                        }
                    });
                }

                // Collect hours data from form
                function collectHoursData() {
                    const hoursData = {};
                    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    
                    days.forEach(day => {
                        const closedCheckbox = document.getElementById(`${day}_closed`);
                        const openInput = document.getElementById(`${day}_open`);
                        const closeInput = document.getElementById(`${day}_close`);
                        
                        hoursData[day] = {
                            open: openInput.value ? openInput.value + ':00' : '08:00:00',
                            close: closeInput.value ? closeInput.value + ':00' : '18:00:00',
                            closed: closedCheckbox.checked
                        };
                    });
                    
                    return hoursData;
                }

                // Load branches from server
                function loadBranches() {
                    if (!isAdmin) return; // Non-admin users can't filter
                    
                    const formData = new FormData();
                    formData.append('action', 'get_branches');
                    formData.append('search', searchInput.value);
                    formData.append('status', statusFilter.value);
                    formData.append('district', districtFilter.value);
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
                            filteredBranches = data.data;
                            totalBranches = data.total;
                            renderBranchesTable();
                            updateStatistics(data.data);
                            updatePaginationInfo();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to load branches', 'error');
                    });
                }

                // Render Branches Table
                function renderBranchesTable() {
                    if (filteredBranches.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <ion-icon name="business-outline"></ion-icon>
                                    <h3>No branches found</h3>
                                    <p>${isAdmin ? 'Try adjusting your search or filters' : 'No branch assigned to you.'}</p>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    filteredBranches.forEach(branch => {
                        const statusClass = `status-${branch.status}`;
                        const statusText = branch.status.charAt(0).toUpperCase() + branch.status.slice(1);
                        
                        html += `
                            <tr>
                                <td>${branch.sn}</td>
                                <td>
                                    <strong>${branch.branch_name}</strong><br>
                                    <small style="color: var(--dark-gray);">Code: ${branch.branch_code}</small>
                                </td>
                                <td>${branch.district}</td>
                                <td>${branch.email}</td>
                                <td>${branch.phone}</td>
                                <td><span class="branch-status ${statusClass}">${statusText}</span></td>
                                <td>
                                    <div class="branch-actions">
                                        <button class="action-icon view" title="View Details" onclick="viewBranch(${branch.branch_id})">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        ${isAdmin ? `
                                        <button class="action-icon edit" title="Edit" onclick="editBranch(${branch.branch_id})">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon delete" title="Delete" onclick="showDeleteConfirm(${branch.branch_id}, '${branch.branch_name.replace(/'/g, "\\'")}')">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = html;
                }

                // Update Statistics
                function updateStatistics(branches = filteredBranches) {
                    const activeBranches = branches.filter(b => b.status === 'active').length;
                    const totalDistricts = [...new Set(branches.map(b => b.district))].length;
                    const totalStaff = branches.reduce((sum, b) => sum + (b.staff_count || 0), 0);
                    
                    document.getElementById('totalBranches').textContent = branches.length;
                    document.getElementById('activeBranches').textContent = activeBranches;
                    document.getElementById('totalCities').textContent = totalDistricts;
                    document.getElementById('totalStaff').textContent = totalStaff;
                }

                // Filter and Search Functions
                function filterBranches() {
                    if (!isAdmin) return;
                    currentPage = 1;
                    loadBranches();
                }

                function resetFilters() {
                    if (!isAdmin) return;
                    searchInput.value = '';
                    statusFilter.value = '';
                    districtFilter.value = '';
                    sortFilter.value = 'name_asc';
                    currentPage = 1;
                    loadBranches();
                }

                function applyFilters() {
                    if (!isAdmin) return;
                    loadBranches();
                    Swal.fire({
                        icon: 'success',
                        title: 'Filters Applied!',
                        text: 'Branch list has been filtered.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                // Pagination Functions
                function updatePaginationInfo() {
                    if (!isAdmin) return;
                    
                    const total = totalBranches;
                    const start = (currentPage - 1) * itemsPerPage + 1;
                    const end = Math.min(currentPage * itemsPerPage, total);
                    
                    document.getElementById('paginationInfo').textContent = 
                        `Showing ${start} to ${end} of ${total} entries`;
                        
                    updatePaginationControls();
                }

                function updatePaginationControls() {
                    if (!isAdmin) return;
                    
                    const totalPages = Math.ceil(totalBranches / itemsPerPage);
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
                    } else if (direction === 'next' && currentPage < Math.ceil(totalBranches / itemsPerPage)) {
                        currentPage++;
                    }
                    loadBranches();
                }

                function goToPage(page) {
                    if (!isAdmin) return;
                    currentPage = page;
                    loadBranches();
                }

                // Modal Functions
                function openAddBranchModal() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can add new branches.', 'error');
                        return;
                    }
                    
                    document.getElementById('modalTitle').textContent = 'Add New Branch';
                    document.getElementById('branchForm').reset();
                    document.getElementById('branchId').value = '';
                    
                    // Set default business hours
                    setAllHours('08:00', '18:00');
                    
                    // Set Sunday as closed by default
                    document.getElementById('sunday_closed').checked = true;
                    toggleClosed('sunday');
                    
                    branchModal.classList.add('active');
                }

                function editBranch(id) {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can edit branches.', 'error');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'get_branch');
                    formData.append('branch_id', id);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const branch = data.data;
                            
                            document.getElementById('modalTitle').textContent = 'Edit Branch';
                            document.getElementById('branchForm').reset();
                            document.getElementById('branchId').value = branch.branch_id;
                            document.getElementById('branch_name').value = branch.branch_name;
                            document.getElementById('branch_code').value = branch.branch_code;
                            document.getElementById('district').value = branch.district;
                            document.getElementById('address').value = branch.address;
                            document.getElementById('email').value = branch.email;
                            document.getElementById('phone').value = branch.phone;
                            document.getElementById('manager_name').value = branch.manager_name;
                            document.getElementById('status').value = branch.status;
                            document.getElementById('opening_date').value = branch.opening_date;
                            document.getElementById('latitude').value = branch.latitude || '';
                            document.getElementById('longitude').value = branch.longitude || '';
                            document.getElementById('description').value = branch.description || '';

                            // Load business hours
                            loadBranchHours(branch);

                            branchModal.classList.add('active');
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to load branch details', 'error');
                    });
                }

                function viewBranch(id) {
                    const formData = new FormData();
                    formData.append('action', 'get_branch');
                    formData.append('branch_id', id);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const branch = data.data;
                            
                            let hoursText = '';
                            const days = [
                                { label: 'Monday', key: 'monday' },
                                { label: 'Tuesday', key: 'tuesday' },
                                { label: 'Wednesday', key: 'wednesday' },
                                { label: 'Thursday', key: 'thursday' },
                                { label: 'Friday', key: 'friday' },
                                { label: 'Saturday', key: 'saturday' },
                                { label: 'Sunday', key: 'sunday' }
                            ];
                            
                            days.forEach(day => {
                                if (branch.hours && branch.hours[day.key]) {
                                    const hours = branch.hours[day.key];
                                    hoursText += `<b>${day.label}:</b> ${hours.closed ? 'Closed' : `${hours.open ? hours.open.substring(0,5) : 'N/A'} - ${hours.close ? hours.close.substring(0,5) : 'N/A'}`}<br>`;
                                }
                            });

                            Swal.fire({
                                title: `<strong>${branch.branch_name}</strong>`,
                                html: `
                                    <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                                        <p><b>Branch Code:</b> ${branch.branch_code}</p>
                                        <p><b>District:</b> ${branch.district}</p>
                                        <p><b>Address:</b> ${branch.address}</p>
                                        <p><b>Email:</b> ${branch.email}</p>
                                        <p><b>Phone:</b> ${branch.phone}</p>
                                        <p><b>Manager:</b> ${branch.manager_name}</p>
                                        <p><b>Status:</b> <span style="color: ${branch.status === 'active' ? '#27ae60' : '#e74c3c'}">${branch.status.charAt(0).toUpperCase() + branch.status.slice(1)}</span></p>
                                        <p><b>Opening Date:</b> ${branch.opening_date || 'Not specified'}</p>
                                        ${branch.latitude && branch.longitude ? `<p><b>Location:</b> ${branch.latitude}, ${branch.longitude}</p>` : ''}
                                        <hr>
                                        <p><b>Business Hours:</b><br>${hoursText}</p>
                                        ${branch.description ? `<hr><p><b>Description:</b><br>${branch.description}</p>` : ''}
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
                        Swal.fire('Error!', 'Failed to load branch details', 'error');
                    });
                }

                function closeModal() {
                    branchModal.classList.remove('active');
                }

                // Save Branch Function
                function saveBranch() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can save branches.', 'error');
                        return;
                    }
                    
                    const form = document.getElementById('branchForm');
                    if (!form.checkValidity()) {
                        Swal.fire('Error!', 'Please fill in all required fields marked with *.', 'error');
                        return;
                    }

                    const branchId = document.getElementById('branchId').value;
                    const isEdit = !!branchId;

                    const formData = new FormData();
                    formData.append('action', isEdit ? 'edit_branch' : 'add_branch');
                    formData.append('branch_id', branchId);
                    formData.append('branch_code', document.getElementById('branch_code').value);
                    formData.append('branch_name', document.getElementById('branch_name').value);
                    formData.append('district', document.getElementById('district').value);
                    formData.append('address', document.getElementById('address').value);
                    formData.append('email', document.getElementById('email').value);
                    formData.append('phone', document.getElementById('phone').value);
                    formData.append('manager_name', document.getElementById('manager_name').value);
                    formData.append('status', document.getElementById('status').value);
                    formData.append('opening_date', document.getElementById('opening_date').value);
                    formData.append('latitude', document.getElementById('latitude').value);
                    formData.append('longitude', document.getElementById('longitude').value);
                    formData.append('description', document.getElementById('description').value);

                    // Add business hours
                    const hours = collectHoursData();
                    Object.keys(hours).forEach(day => {
                        formData.append(`${day}_open`, hours[day].open);
                        formData.append(`${day}_close`, hours[day].close);
                        if (hours[day].closed) {
                            formData.append(`${day}_closed`, 'on');
                        }
                    });

                    // Show loading
                    Swal.fire({
                        title: isEdit ? 'Updating Branch...' : 'Adding Branch...',
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
                            closeModal();
                            loadBranches();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to save branch', 'error');
                    });
                }

                // Delete Branch Functions
                function showDeleteConfirm(id, name) {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can delete branches.', 'error');
                        return;
                    }
                    
                    Swal.fire({
                        title: 'Delete Branch?',
                        html: `Are you sure you want to delete <strong>${name}</strong>?<br><br>
                              <span style="color: #e74c3c; font-size: 0.9em;">
                              <ion-icon name="warning-outline"></ion-icon> 
                              This action cannot be undone and all associated data will be permanently removed.
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
                            deleteBranch(id);
                        }
                    });
                }

                function deleteBranch(id) {
                    if (!isAdmin) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_branch');
                    formData.append('branch_id', id);

                    // Show loading
                    Swal.fire({
                        title: 'Deleting Branch...',
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
                            loadBranches();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Failed to delete branch', 'error');
                    });
                }

                // Utility Functions
                function refreshBranches() {
                    loadBranches();
                    Swal.fire({
                        icon: 'success',
                        title: 'Refreshed!',
                        text: 'Branch list has been refreshed.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                function exportBranches() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can export branches.', 'error');
                        return;
                    }
                    
                    const csvContent = [
                        ['S/N', 'Branch Code', 'Branch Name', 'District', 'Email', 'Phone', 'Manager', 'Status', 'Opening Date', 'Address'],
                        ...filteredBranches.map(b => [
                            b.sn,
                            b.branch_code,
                            b.branch_name,
                            b.district,
                            b.email,
                            b.phone,
                            b.manager_name,
                            b.status,
                            b.opening_date,
                            b.address
                        ])
                    ].map(row => row.join(',')).join('\n');

                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', `branches_${new Date().toISOString().split('T')[0]}.csv`);
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

                function printBranches() {
                    if (!isAdmin) {
                        Swal.fire('Access Denied!', 'Only administrators can print branches.', 'error');
                        return;
                    }
                    
                    const printContent = `
                        <html>
                        <head>
                            <title>Master Clinic Branches Report</title>
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
                                <h1>Master Clinic Branches Report</h1>
                                <div class="print-date">Generated: ${new Date().toLocaleDateString()}</div>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Branch Name</th>
                                        <th>District</th>
                                        <th>Email</th>
                                        <th>Phone Number</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filteredBranches.map(branch => `
                                        <tr>
                                            <td>${branch.sn}</td>
                                            <td>${branch.branch_name}<br><small>Code: ${branch.branch_code}</small></td>
                                            <td>${branch.district}</td>
                                            <td>${branch.email}</td>
                                            <td>${branch.phone}</td>
                                            <td class="status-${branch.status}">${branch.status.charAt(0).toUpperCase() + branch.status.slice(1)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <div style="margin-top: 30px; color: #7f8c8d; font-size: 12px;">
                                Total Branches: ${filteredBranches.length} | Active: ${filteredBranches.filter(b => b.status === 'active').length}
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
                        closeModal();
                    }
                });

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeModal();
                    }
                    if (e.ctrlKey && e.key === 'f' && isAdmin) {
                        e.preventDefault();
                        searchInput.focus();
                    }
                    if (e.ctrlKey && e.key === 'n' && isAdmin) {
                        e.preventDefault();
                        openAddBranchModal();
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
</html>