<?php
// customers.php - Frontend with Backend Integration

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

// Get initial statistics
$initial_stats = [
    'total_customers' => 0,
    'active_customers' => 0,
    'premium_customers' => 0,
    'new_this_month' => 0,
    'total_revenue' => 0,
    'chronic_patients' => 0
];

try {
    // Build where clause based on user role
    $where_sql = "1=1";
    $params = [];
    $types = "";
    
    if ($user_role !== 'admin' && $current_branch_id) {
        $where_sql .= " AND branch_id = ?";
        $params[] = $current_branch_id;
        $types .= "i";
    }
    
    // Get basic statistics
    $stats_sql = "SELECT 
        COUNT(*) as total_customers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
        SUM(CASE WHEN customer_type = 'premium' THEN 1 ELSE 0 END) as premium_customers,
        SUM(CASE WHEN medical_conditions IS NOT NULL AND medical_conditions != '' THEN 1 ELSE 0 END) as chronic_patients,
        SUM(total_spent) as total_revenue
        FROM customers
        WHERE $where_sql";
    
    $stmt = $conn->prepare($stats_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $stats_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($stats_result) {
        $initial_stats = array_merge($initial_stats, $stats_result);
    }
    
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
    
    $initial_stats['new_this_month'] = $new_result['new_this_month'] ?? 0;
    
    // Get unique cities for filter
    $cities = [];
    $cities_result = $conn->query("SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city");
    while ($row = $cities_result->fetch_assoc()) {
        $cities[] = $row['city'];
    }
    
    // Get branches for filter (if admin)
    $branches = [];
    if ($user_role === 'admin') {
        $branches_result = $conn->query("SELECT branch_id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name");
        while ($row = $branches_result->fetch_assoc()) {
            $branches[] = $row;
        }
    } elseif ($current_branch_id) {
        $branches_stmt = $conn->prepare("SELECT branch_id, branch_name FROM branches WHERE branch_id = ? AND status = 'active'");
        $branches_stmt->bind_param("i", $current_branch_id);
        $branches_stmt->execute();
        $branches_result = $branches_stmt->get_result();
        while ($row = $branches_result->fetch_assoc()) {
            $branches[] = $row;
        }
    }
    
} catch (Exception $e) {
    error_log("Error loading initial data: " . $e->getMessage());
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Management - Master Clinic</title>
    <!-- Include SweetAlert -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
         /* ================== Customers Management Styles ============== */
                .customers-management-section {
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

                .action-btn.secondary {
                    background: var(--light-gray);
                    color: var(--dark-gray);
                }

                .action-btn.secondary:hover {
                    background: #e0e0e0;
                    transform: translateY(-2px);
                }

                /* Statistics Cards */
                .customers-stats {
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

                .stat-card:nth-child(6) {
                    border-color: #e74c3c;
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

                .stat-card:nth-child(6) .stat-icon {
                    background: rgba(231, 76, 60, 0.1);
                    color: #e74c3c;
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

                /* Customers Table */
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

                .customers-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .customers-table thead {
                    background: var(--light-gray);
                }

                .customers-table th {
                    padding: 18px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    border-bottom: 2px solid var(--gray);
                }

                .customers-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.3s ease;
                }

                .customers-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .customers-table td {
                    padding: 15px 20px;
                    color: var(--black);
                    font-size: 0.95rem;
                    vertical-align: middle;
                }

                /* Customer Avatar */
                .customer-avatar {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 2px solid var(--gray);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 1.2rem;
                    color: white;
                }

                .customer-avatar img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                /* Status Badges */
                .customer-status {
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

                .status-suspended {
                    background: rgba(149, 165, 166, 0.1);
                    color: #7f8c8d;
                }

                /* Customer Type Badges */
                .customer-type {
                    display: inline-block;
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .type-regular {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
                }

                .type-premium {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .type-wholesale {
                    background: rgba(230, 126, 34, 0.1);
                    color: #e67e22;
                }

                .type-corporate {
                    background: rgba(41, 128, 185, 0.1);
                    color: #2980b9;
                }

                .type-government {
                    background: rgba(46, 204, 113, 0.1);
                    color: #2ecc71;
                }

                /* Total Spent */
                .total-spent {
                    font-weight: 600;
                    color: var(--primary);
                }

                .currency {
                    color: var(--dark-gray);
                    font-size: 0.85rem;
                    margin-left: 2px;
                }

                /* Location Info */
                .location-info {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }

                .location-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 0.9rem;
                    color: var(--dark-gray);
                }

                .location-item ion-icon {
                    font-size: 1rem;
                    color: var(--primary);
                }

                /* Last Visit */
                .last-visit {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .last-visit-icon {
                    color: var(--primary);
                    font-size: 1.1rem;
                }

                .last-visit-text {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                }

                /* Actions */
                .customer-actions {
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

                .action-icon.prescription {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
                }

                .action-icon.prescription:hover {
                    background: #3498db;
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

                /* Improved Date Input Styling */
                input[type="date"] {
                    width: 100%;
                    padding: 12px 15px;
                    border: 1px solid var(--gray);
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                    background-color: white;
                    font-family: inherit;
                }

                input[type="date"]:focus {
                    outline: none;
                    border-color: var(--primary);
                    box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1);
                }

                /* Form Section Headers */
                .form-section-header {
                    grid-column: 1 / -1;
                    margin: 25px 0 15px 0;
                    padding-bottom: 10px;
                    border-bottom: 2px solid var(--primary);
                }

                .form-section-header h4 {
                    color: var(--primary);
                    margin: 0;
                    font-size: 1.1rem;
                    font-weight: 600;
                }

                /* Avatar Upload */
                .avatar-upload-section {
                    grid-column: 1 / -1;
                    display: flex;
                    align-items: center;
                    gap: 30px;
                    margin-bottom: 10px;
                }

                .avatar-preview {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 3px solid var(--primary);
                    position: relative;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 2.5rem;
                    font-weight: bold;
                    color: white;
                    background: linear-gradient(135deg, var(--primary), var(--secondary));
                }

                .avatar-preview img {
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

                /* Medical Info */
                .medical-info-section {
                    grid-column: 1 / -1;
                    background: var(--light-gray);
                    padding: 20px;
                    border-radius: 10px;
                    margin-top: 10px;
                }

                .medical-info-section h4 {
                    color: var(--primary);
                    margin: 0 0 15px 0;
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

                .customer-details {
                    display: grid;
                    grid-template-columns: 150px 1fr;
                    gap: 30px;
                    margin-bottom: 30px;
                }

                .customer-avatar-large {
                    width: 150px;
                    height: 150px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 3px solid var(--primary);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 3.5rem;
                    font-weight: bold;
                    color: white;
                    background: linear-gradient(135deg, var(--primary), var(--secondary));
                }

                .customer-avatar-large img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .customer-info {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .customer-info h4 {
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

                /* Purchase History */
                .purchase-history {
                    background: var(--light-gray);
                    border-radius: 10px;
                    padding: 20px;
                    margin-top: 20px;
                }

                .purchase-history h5 {
                    margin: 0 0 15px 0;
                    color: var(--primary);
                }

                .purchase-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .purchase-table th {
                    padding: 12px;
                    background: white;
                    border-bottom: 1px solid var(--gray);
                    font-weight: 600;
                    color: var(--black);
                }

                .purchase-table td {
                    padding: 12px;
                    border-bottom: 1px solid var(--gray);
                }

                /* Medical History */
                .medical-history {
                    margin-top: 20px;
                }

                .allergies-list,
                .medications-list {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    margin-top: 10px;
                }

                .allergy-tag,
                .medication-tag {
                    background: rgba(231, 76, 60, 0.1);
                    color: var(--danger);
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                }

                .medication-tag {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
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

                @media (max-width: 992px) {
                    .customers-stats {
                        grid-template-columns: repeat(3, 1fr);
                    }
                }

                @media (max-width: 768px) {
                    .customers-management-section {
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

                    .customers-stats {
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

                    .customers-table {
                        display: block;
                        overflow-x: auto;
                    }

                    .avatar-upload-section {
                        flex-direction: column;
                        text-align: center;
                        gap: 20px;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }

                    .customer-actions {
                        flex-direction: column;
                        gap: 5px;
                    }

                    .action-icon {
                        width: 30px;
                        height: 30px;
                        font-size: 1rem;
                    }

                    .customer-details {
                        grid-template-columns: 1fr;
                    }

                    .info-grid {
                        grid-template-columns: 1fr;
                    }

                    .purchase-table {
                        display: block;
                        overflow-x: auto;
                    }
                }

                @media (max-width: 480px) {
                    .customers-stats {
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
        
        .customers-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
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
        
        .performance-metrics {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .metric-card {
            background: var(--white);
            border-radius: 10px;
            padding: 15px;
            flex: 1;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .metric-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: var(--dark-gray);
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

            <!-- ================== Customers Management Content ============== -->
            <div class="customers-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Customers Management</h1>
                        <p>Manage customer profiles, purchase history, and medical information</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshCustomers()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <button class="action-btn primary" onclick="openAddCustomerModal()" id="addCustomerBtn">
                            <ion-icon name="person-add-outline"></ion-icon>
                            Add New Customer
                        </button>
                    </div>
                </div>

                <!-- Customers Statistics -->
                <div class="customers-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalCustomers"><?php echo $initial_stats['total_customers']; ?></h3>
                            <p>Total Customers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeCustomers"><?php echo $initial_stats['active_customers']; ?></h3>
                            <p>Active Customers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="trophy-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="premiumCustomers"><?php echo $initial_stats['premium_customers']; ?></h3>
                            <p>Premium Members</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="cash-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="monthlyRevenue"><?php echo number_format($initial_stats['total_revenue'], 0); ?></h3>
                            <p>Total Revenue (MWK)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="newThisMonth"><?php echo $initial_stats['new_this_month']; ?></h3>
                            <p>New This Month</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="medical-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="chronicPatients"><?php echo $initial_stats['chronic_patients']; ?></h3>
                            <p>Chronic Patients</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search by name, phone, email, or customer ID..." 
                               onkeyup="filterCustomers()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterCustomers()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Customer Type</label>
                            <select id="typeFilter" onchange="filterCustomers()">
                                <option value="">All Types</option>
                                <option value="regular">Regular</option>
                                <option value="premium">Premium</option>
                                <option value="wholesale">Wholesale</option>
                                <option value="corporate">Corporate</option>
                                <option value="government">Government</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Location</label>
                            <select id="locationFilter" onchange="filterCustomers()">
                                <option value="">All Locations</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterCustomers()">
                                <option value="name_asc">Name (A-Z)</option>
                                <option value="name_desc">Name (Z-A)</option>
                                <option value="spent_high">Total Spent (High to Low)</option>
                                <option value="spent_low">Total Spent (Low to High)</option>
                                <option value="recent">Recently Visited</option>
                                <option value="newest">Newest First</option>
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

                <!-- Customers Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Pharmacy Customers</h2>
                        <div class="table-actions">
                          <!--   <button class="action-btn secondary" onclick="exportCustomers('excel')">
                                <ion-icon name="download-outline"></ion-icon>
                                Export Excel
                            </button>
                            <button class="action-btn secondary" onclick="exportCustomers('pdf')">
                                <ion-icon name="document-outline"></ion-icon>
                                Export PDF
                            </button> -->
                        </div>
                    </div>
                    
                    <!-- Responsive and Scrollable Table Container -->
                    <div class="table-responsive-container">
                        <table class="customers-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>CUSTOMER DETAILS</th>
                                    <th>CONTACT INFO</th>
                                    <th>LOCATION</th>
                                    <th>LAST VISIT</th>
                                    <th>TOTAL SPENT (MWK)</th>
                                    <th>CUSTOMER TYPE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="customersTableBody">
                                <!-- Customers will be populated here by JavaScript -->
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        <ion-icon name="people-outline"></ion-icon>
                                        <h3>Loading customers...</h3>
                                        <p>Please wait while we load customer data</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="pagination-info" id="paginationInfo">
                            Loading...
                        </div>
                        <div class="pagination-controls" id="paginationControls">
                            <!-- Pagination will be populated here by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Customer Modal -->
            <div class="modal-overlay" id="customerModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add New Customer</h3>
                        <button class="modal-close" onclick="closeCustomerModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="customerForm" class="modal-form" onsubmit="return false;">
                            <input type="hidden" id="customerId">
                            
                            <!-- Avatar Upload -->
                            <div class="avatar-upload-section">
                                <div class="avatar-preview" id="avatarPreview">
                                    <!-- Avatar will be displayed here -->
                                    <span id="avatarInitials">JD</span>
                                </div>
                                <div class="upload-controls">
                                    <label class="upload-btn">
                                        <ion-icon name="cloud-upload-outline"></ion-icon>
                                        Upload Profile Photo
                                        <input type="file" id="avatarUpload" accept="image/*" onchange="previewAvatar(this)">
                                    </label>
                                    <p style="margin-top: 10px; color: var(--dark-gray); font-size: 0.9rem;">
                                        Recommended: 400x400px, max 2MB. PNG or JPG format.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Personal Information -->
                            <div class="form-section-header">
                                <h4>Personal Information</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="firstName" class="required">First Name</label>
                                <input type="text" id="firstName" placeholder="e.g., John" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="lastName" class="required">Last Name</label>
                                <input type="text" id="lastName" placeholder="e.g., Doe" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="customerCode">Customer ID</label>
                                <input type="text" id="customerCode" placeholder="Auto-generated" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender">
                                    <option value="">-- select --</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                    <option value="prefer-not-to-say">Prefer not to say</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="dateOfBirth">Date of Birth</label>
                                <input type="date" id="dateOfBirth">
                            </div>
                            
                            <div class="form-group">
                                <label for="nationalId">National ID/Passport</label>
                                <input type="text" id="nationalId" placeholder="e.g., MW-12345678">
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="form-section-header">
                                <h4>Contact Information</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="required">Email Address</label>
                                <input type="email" id="email" placeholder="john.doe@example.com" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="required">Phone Number</label>
                                <input type="tel" id="phone" placeholder="+265 123 456 789" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="alternatePhone">Alternate Phone</label>
                                <input type="tel" id="alternatePhone" placeholder="+265 987 654 321">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="address" class="required">Residential Address</label>
                                <textarea id="address" placeholder="Street, City, Region" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="city" class="required">City</label>
                                <input type="text" id="city" list="cityList" placeholder="e.g., Lilongwe" required>
                                <datalist id="cityList">
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            
                            <div class="form-group">
                                <label for="region">Region</label>
                                <select id="region">
                                    <option value="">-- select --</option>
                                    <option value="central">Central Region</option>
                                    <option value="southern">Southern Region</option>
                                    <option value="northern">Northern Region</option>
                                </select>
                            </div>
                            
                            <!-- Medical Information -->
                            <div class="medical-info-section">
                                <h4>Medical Information</h4>
                                <div class="form-group full-width">
                                    <label for="bloodGroup">Blood Group</label>
                                    <select id="bloodGroup">
                                        <option value="">-- select --</option>
                                        <option value="a+">A+</option>
                                        <option value="a-">A-</option>
                                        <option value="b+">B+</option>
                                        <option value="b-">B-</option>
                                        <option value="ab+">AB+</option>
                                        <option value="ab-">AB-</option>
                                        <option value="o+">O+</option>
                                        <option value="o-">O-</option>
                                        <option value="unknown">Unknown</option>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="allergies">Known Allergies</label>
                                    <textarea id="allergies" placeholder="List any known allergies (separate with commas)"></textarea>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="medicalConditions">Medical Conditions</label>
                                    <textarea id="medicalConditions" placeholder="List any chronic or medical conditions"></textarea>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="currentMedications">Current Medications</label>
                                    <textarea id="currentMedications" placeholder="List current medications (separate with commas)"></textarea>
                                </div>
                            </div>
                            
                            <!-- Customer Details -->
                            <div class="form-section-header">
                                <h4>Customer Details</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="customerType" class="required">Customer Type</label>
                                <select id="customerType" required>
                                    <option value="">-- select --</option>
                                    <option value="regular">Regular</option>
                                    <option value="premium">Premium</option>
                                    <option value="wholesale">Wholesale</option>
                                    <option value="corporate">Corporate</option>
                                    <option value="government">Government</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="registrationDate">Registration Date</label>
                                <input type="date" id="registrationDate" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="referredBy">Referred By</label>
                                <input type="text" id="referredBy" placeholder="e.g., Dr. Smith or Customer ID">
                            </div>
                            
                            <div class="form-group">
                                <label for="loyaltyPoints">Loyalty Points</label>
                                <input type="number" id="loyaltyPoints" min="0" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="form-section-header">
                                <h4>Additional Information</h4>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="notes">Notes</label>
                                <textarea id="notes" placeholder="Any additional information about this customer..."></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="preferredCommunication">Preferred Communication</label>
                                <select id="preferredCommunication">
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="phone">Phone Call</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="all">All Channels</option>
                                </select>
                            </div>
                            
                            <!-- Branch (for admin only) -->
                            <?php if ($user_role === 'admin'): ?>
                            <div class="form-group">
                                <label for="branchId">Branch</label>
                                <select id="branchId">
                                    <option value="">-- select --</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>">
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeCustomerModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveCustomer()">
                            Save Customer
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Customer Details Modal -->
            <div class="modal-overlay view-details-modal" id="viewCustomerModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="viewModalTitle">Customer Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="customer-details">
                            <div class="customer-avatar-large" id="viewAvatar">
                                <!-- Avatar will be displayed here -->
                            </div>
                            <div class="customer-info">
                                <h4 id="viewCustomerName">Loading...</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Customer ID:</span>
                                        <span class="info-value" id="viewCustomerCode">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Gender:</span>
                                        <span class="info-value" id="viewGender">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Date of Birth:</span>
                                        <span class="info-value" id="viewDateOfBirth">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">National ID:</span>
                                        <span class="info-value" id="viewNationalId">Loading...</span>
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
                                        <span class="info-label">Alternate Phone:</span>
                                        <span class="info-value" id="viewAlternatePhone">Loading...</span>
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
                                        <span class="info-label">Region:</span>
                                        <span class="info-value" id="viewRegion">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Customer Type:</span>
                                        <span class="info-value" id="viewCustomerType">Loading...</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value" id="viewStatus">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Information -->
                        <div class="medical-history">
                            <h5 style="margin-bottom: 15px; color: var(--primary);">Medical Information</h5>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Blood Group:</span>
                                    <span class="info-value" id="viewBloodGroup">Loading...</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Known Allergies:</span>
                                    <div class="allergies-list" id="viewAllergies">
                                        Loading...
                                    </div>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Medical Conditions:</span>
                                    <span class="info-value" id="viewMedicalConditions">Loading...</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Current Medications:</span>
                                    <div class="medications-list" id="viewMedications">
                                        Loading...
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Visits -->
                        <div class="purchase-history">
                            <h5>Recent Visits</h5>
                            <table class="purchase-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice #</th>
                                        <th>Total (MWK)</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="viewPurchaseHistory">
                                    <!-- Purchase history will be populated here -->
                                    <tr><td colspan="5">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Customer Stats -->
                        <div class="performance-metrics">
                            <div class="metric-card">
                                <div class="metric-value" id="viewTotalSpent">0</div>
                                <div class="metric-label">Total Spent</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value" id="viewVisitCount">0</div>
                                <div class="metric-label">Total Visits</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value" id="viewLoyaltyPoints">0</div>
                                <div class="metric-label">Loyalty Points</div>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="form-group full-width" style="margin-top: 20px;">
                            <label>Additional Notes:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px;" id="viewNotes">
                                Loading...
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                        <button type="button" class="action-btn primary" onclick="editCurrentCustomer()">
                            <ion-icon name="create-outline"></ion-icon>
                            Edit Customer
                        </button>
                        <button type="button" class="action-btn info" onclick="createPrescription()">
                            <ion-icon name="medical-outline"></ion-icon>
                            Create Prescription
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="deleteCustomerModal">
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
                        <h4>Delete Customer</h4>
                        <p>Are you sure you want to delete <strong id="deleteCustomerName">[Customer Name]</strong>? This action cannot be undone and all customer data including purchase history will be permanently removed.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="action-btn danger" onclick="confirmDelete()">
                                <ion-icon name="trash-outline"></ion-icon>
                                Delete Customer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Include jQuery -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <!-- Include SweetAlert -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            <script>
                // Global variables
                let currentPage = 1;
                const itemsPerPage = 10;
                let filteredCustomers = [];
                let totalCustomers = <?php echo $initial_stats['total_customers']; ?>;
                let customerToDelete = null;
                let customerToView = null;

                // User role from PHP
                const userRole = '<?php echo $user_role; ?>';
                const currentBranchId = <?php echo $current_branch_id ?: 'null'; ?>;
                const isAdmin = userRole === 'admin';

                // DOM Elements
                const tableBody = document.getElementById('customersTableBody');
                const customerModal = document.getElementById('customerModal');
                const viewCustomerModal = document.getElementById('viewCustomerModal');
                const deleteCustomerModal = document.getElementById('deleteCustomerModal');

                // Initialize Page
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('Customers Management System Initialized');
                    
                    // Set default registration date
                    const registrationDateInput = document.getElementById('registrationDate');
                    if (registrationDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        registrationDateInput.value = today;
                    }
                    
                    // Add event listeners for modals
                    document.querySelectorAll('.modal-overlay').forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('active');
                            }
                        });
                    });
                    
                    // Load initial data
                    loadCustomers();
                    console.log('Page initialization complete');
                });

                // Avatar Preview
                function previewAvatar(input) {
                    const preview = document.getElementById('avatarPreview');
                    const initials = document.getElementById('avatarInitials');
                    if (!preview) return;
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.innerHTML = '';
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        preview.appendChild(img);
                    }
                    
                    if (input.files && input.files[0]) {
                        reader.readAsDataURL(input.files[0]);
                    } else {
                        preview.innerHTML = '';
                        const initialsSpan = document.createElement('span');
                        initialsSpan.id = 'avatarInitials';
                        initialsSpan.textContent = initials ? initials.textContent : 'JD';
                        preview.appendChild(initialsSpan);
                    }
                }

                // Modal Functions
                function openAddCustomerModal() {
                    document.getElementById('modalTitle').textContent = 'Add New Customer';
                    document.getElementById('customerForm').reset();
                    document.getElementById('customerId').value = '';
                    document.getElementById('customerCode').value = 'Auto-generated on save';
                    
                    // Set default values
                    document.getElementById('loyaltyPoints').value = '0';
                    document.getElementById('status').value = 'active';
                    document.getElementById('preferredCommunication').value = 'email';
                    document.getElementById('bloodGroup').value = 'unknown';
                    
                    // Set default dates
                    const registrationDateInput = document.getElementById('registrationDate');
                    if (registrationDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        registrationDateInput.value = today;
                    }
                    
                    // Reset avatar preview
                    const avatarPreview = document.getElementById('avatarPreview');
                    avatarPreview.innerHTML = '';
                    avatarPreview.style.background = 'linear-gradient(135deg, var(--primary), var(--secondary))';
                    const initialsSpan = document.createElement('span');
                    initialsSpan.id = 'avatarInitials';
                    initialsSpan.textContent = 'JD';
                    initialsSpan.style.color = 'white';
                    avatarPreview.appendChild(initialsSpan);
                    
                    // Set branch if not admin
                    if (!isAdmin && currentBranchId) {
                        document.getElementById('branchId').value = currentBranchId;
                    }
                    
                    customerModal.classList.add('active');
                }

                function closeCustomerModal() {
                    customerModal.classList.remove('active');
                }

                function closeViewModal() {
                    viewCustomerModal.classList.remove('active');
                }

                function closeDeleteModal() {
                    deleteCustomerModal.classList.remove('active');
                    customerToDelete = null;
                }

                // Load Customers
                function loadCustomers() {
                    const formData = new FormData();
                    formData.append('action', 'get_customers');
                    formData.append('search', document.getElementById('searchInput').value);
                    formData.append('status', document.getElementById('statusFilter').value);
                    formData.append('type', document.getElementById('typeFilter').value);
                    formData.append('city', document.getElementById('locationFilter').value);
                    formData.append('sort', document.getElementById('sortFilter').value);
                    formData.append('page', currentPage);
                    formData.append('limit', itemsPerPage);

                    fetch('customers_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            filteredCustomers = data.data;
                            totalCustomers = data.total;
                            renderCustomersTable();
                            updateStatistics();
                            updatePaginationInfo();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load customers');
                    });
                }

                // Update Statistics
                function updateStatistics() {
                    const formData = new FormData();
                    formData.append('action', 'get_customer_stats');

                    fetch('customers_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const stats = data.stats;
                            document.getElementById('totalCustomers').textContent = stats.total_customers;
                            document.getElementById('activeCustomers').textContent = stats.active_customers;
                            document.getElementById('premiumCustomers').textContent = stats.premium_customers;
                            document.getElementById('monthlyRevenue').textContent = formatCurrency(stats.total_revenue);
                            document.getElementById('newThisMonth').textContent = stats.new_this_month;
                            document.getElementById('chronicPatients').textContent = stats.chronic_patients;
                        }
                    })
                    .catch(error => {
                        console.error('Error updating statistics:', error);
                    });
                }

                // Render Customers Table
                function renderCustomersTable() {
                    if (!tableBody) return;
                    
                    if (filteredCustomers.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <ion-icon name="people-outline"></ion-icon>
                                    <h3>No customers found</h3>
                                    <p>${isAdmin ? 'Try adjusting your search or filters' : 'No customers in your branch.'}</p>
                                </td>
                            </tr>
                        `;
                        updatePaginationInfo();
                        return;
                    }

                    let html = '';
                    filteredCustomers.forEach(customer => {
                        const statusClass = `status-${customer.status}`;
                        const typeClass = `type-${customer.customer_type}`;
                        
                        // Format total spent
                        const totalSpent = parseFloat(customer.total_spent).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        
                        html += `
                            <tr>
                                <td>${customer.sn}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <div class="customer-avatar" style="background-color: ${customer.avatar_color}">
                                            ${customer.avatar_initials}
                                        </div>
                                        <div>
                                            <strong>${customer.first_name} ${customer.last_name}</strong><br>
                                            <small style="color: var(--dark-gray);">${customer.customer_code}</small><br>
                                            <small style="color: var(--dark-gray);">${customer.age ? customer.age + ' years' : 'Age not set'}, ${customer.gender || 'Not specified'}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <div class="contact-item">
                                            <ion-icon name="mail-outline"></ion-icon>
                                            <span>${customer.email}</span>
                                        </div>
                                        <div class="contact-item">
                                            <ion-icon name="call-outline"></ion-icon>
                                            <span>${customer.phone_formatted}</span>
                                        </div>
                                        <div class="contact-item">
                                            <ion-icon name="home-outline"></ion-icon>
                                            <span>${customer.city || 'Not specified'}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="location-info">
                                        <div class="location-item">
                                            <ion-icon name="location-outline"></ion-icon>
                                            <span>${customer.city || 'Not specified'}</span>
                                        </div>
                                        <div class="location-item">
                                            <ion-icon name="navigate-outline"></ion-icon>
                                            <span>${customer.region || 'Region not set'}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="last-visit">
                                        <ion-icon name="calendar-outline" class="last-visit-icon"></ion-icon>
                                        <div>
                                            <div>${customer.last_visit_text}</div>
                                            <div class="last-visit-text">${customer.last_visit_formatted}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="total-spent">${totalSpent}<span class="currency"> MWK</span></span>
                                </td>
                                <td><span class="customer-type ${typeClass}">${customer.customer_type_text}</span></td>
                                <td><span class="customer-status ${statusClass}">${customer.status_text}</span></td>
                                <td>
                                    <div class="customer-actions">
                                        <button class="action-icon view" title="View Details" onclick="viewCustomer(${customer.customer_id})">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon edit" title="Edit" onclick="editCustomer(${customer.customer_id})">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon delete" title="Delete" onclick="deleteCustomer(${customer.customer_id})">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon prescription" title="Create Prescription" onclick="createPrescription(${customer.customer_id})">
                                            <ion-icon name="medical-outline"></ion-icon>
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
                    const end = Math.min(currentPage * itemsPerPage, totalCustomers);
                    
                    document.getElementById('paginationInfo').textContent = 
                        `Showing ${start} to ${end} of ${totalCustomers} entries`;
                    
                    // Update pagination buttons
                    const paginationControls = document.getElementById('paginationControls');
                    if (paginationControls && totalCustomers > itemsPerPage) {
                        const totalPages = Math.ceil(totalCustomers / itemsPerPage);
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
                    } else {
                        paginationControls.innerHTML = '';
                    }
                }

                // Change Page
                function changePage(direction) {
                    if (direction === 'prev' && currentPage > 1) {
                        currentPage--;
                    } else if (direction === 'next' && currentPage < Math.ceil(totalCustomers / itemsPerPage)) {
                        currentPage++;
                    } else if (typeof direction === 'number') {
                        currentPage = direction;
                    }
                    
                    loadCustomers();
                }

                // Filter Customers
                function filterCustomers() {
                    currentPage = 1;
                    loadCustomers();
                }

                // Apply Filters
                function applyFilters() {
                    filterCustomers();
                }

                // Reset Filters
                function resetFilters() {
                    document.getElementById('searchInput').value = '';
                    document.getElementById('statusFilter').value = '';
                    document.getElementById('typeFilter').value = '';
                    document.getElementById('locationFilter').value = '';
                    document.getElementById('sortFilter').value = 'name_asc';
                    currentPage = 1;
                    
                    loadCustomers();
                }

                // Refresh Customers
                function refreshCustomers() {
                    Swal.fire({
                        title: 'Refreshing...',
                        text: 'Loading latest customer data',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    currentPage = 1;
                    loadCustomers();
                    
                    setTimeout(() => {
                        Swal.close();
                        showSuccess('Customers refreshed successfully!');
                    }, 1000);
                }

                // View Customer Details
                function viewCustomer(customerId) {
                    const formData = new FormData();
                    formData.append('action', 'get_customer');
                    formData.append('customer_id', customerId);
                    
                    fetch('customers_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            customerToView = data.data;
                            
                            // Populate view modal
                            document.getElementById('viewCustomerName').textContent = `${customerToView.first_name} ${customerToView.last_name}`;
                            document.getElementById('viewModalTitle').textContent = `Details: ${customerToView.first_name} ${customerToView.last_name}`;
                            document.getElementById('viewCustomerCode').textContent = customerToView.customer_code;
                            document.getElementById('viewGender').textContent = customerToView.gender ? customerToView.gender.charAt(0).toUpperCase() + customerToView.gender.slice(1) : 'Not specified';
                            document.getElementById('viewDateOfBirth').textContent = customerToView.date_of_birth_formatted ? 
                                `${customerToView.date_of_birth_formatted} (${customerToView.age} years)` : 'Not set';
                            document.getElementById('viewNationalId').textContent = customerToView.national_id || 'Not provided';
                            document.getElementById('viewEmail').textContent = customerToView.email;
                            document.getElementById('viewPhone').textContent = customerToView.phone_formatted;
                            document.getElementById('viewAlternatePhone').textContent = customerToView.alternate_phone_formatted || 'Not provided';
                            document.getElementById('viewAddress').textContent = customerToView.address;
                            document.getElementById('viewCity').textContent = customerToView.city || 'Not specified';
                            document.getElementById('viewRegion').textContent = customerToView.region || 'Not specified';
                            document.getElementById('viewCustomerType').textContent = customerToView.customer_type_text;
                            document.getElementById('viewStatus').textContent = customerToView.status_text;
                            document.getElementById('viewBloodGroup').textContent = customerToView.blood_group_text;
                            document.getElementById('viewMedicalConditions').textContent = customerToView.medical_conditions || 'None';
                            document.getElementById('viewNotes').textContent = customerToView.notes || 'No additional notes';
                            
                            // Set avatar
                            const viewAvatar = document.getElementById('viewAvatar');
                            viewAvatar.innerHTML = '';
                            viewAvatar.style.background = getAvatarColor(customerToView.customer_type);
                            const avatarText = document.createElement('span');
                            avatarText.textContent = customerToView.avatar_initials || 
                                (customerToView.first_name.charAt(0) + customerToView.last_name.charAt(0)).toUpperCase();
                            avatarText.style.color = 'white';
                            avatarText.style.fontSize = '3.5rem';
                            avatarText.style.fontWeight = 'bold';
                            viewAvatar.appendChild(avatarText);
                            
                            // Set allergies
                            const allergiesList = document.getElementById('viewAllergies');
                            allergiesList.innerHTML = '';
                            if (customerToView.allergies_array && customerToView.allergies_array.length > 0) {
                                customerToView.allergies_array.forEach(allergy => {
                                    const tag = document.createElement('span');
                                    tag.className = 'allergy-tag';
                                    tag.textContent = allergy;
                                    allergiesList.appendChild(tag);
                                });
                            } else {
                                allergiesList.textContent = 'None';
                            }
                            
                            // Set medications
                            const medicationsList = document.getElementById('viewMedications');
                            medicationsList.innerHTML = '';
                            if (customerToView.medications_array && customerToView.medications_array.length > 0) {
                                customerToView.medications_array.forEach(med => {
                                    const tag = document.createElement('span');
                                    tag.className = 'medication-tag';
                                    tag.textContent = med;
                                    medicationsList.appendChild(tag);
                                });
                            } else {
                                medicationsList.textContent = 'None';
                            }
                            
                            // Load purchase history
                            loadCustomerVisits(customerId);
                            
                            // Set customer stats
                            document.getElementById('viewTotalSpent').textContent = formatCurrency(customerToView.total_spent);
                            document.getElementById('viewVisitCount').textContent = customerToView.total_visits;
                            document.getElementById('viewLoyaltyPoints').textContent = customerToView.loyalty_points;
                            
                            // Show modal
                            viewCustomerModal.classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load customer details');
                    });
                }

                // Load Customer Visits
                function loadCustomerVisits(customerId) {
                    const formData = new FormData();
                    formData.append('action', 'get_customer_visits');
                    formData.append('customer_id', customerId);
                    formData.append('limit', 5);
                    
                    fetch('customers_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const purchaseHistory = document.getElementById('viewPurchaseHistory');
                            purchaseHistory.innerHTML = '';
                            
                            if (data.data.length === 0) {
                                purchaseHistory.innerHTML = `
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--dark-gray);">
                                            No purchase history found
                                        </td>
                                    </tr>
                                `;
                                return;
                            }
                            
                            data.data.forEach(visit => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${visit.visit_date_formatted}</td>
                                    <td>${visit.invoice_number || 'N/A'}</td>
                                    <td>${visit.total_amount_formatted}</td>
                                    <td>${visit.payment_method || 'Cash'}</td>
                                    <td><span class="status-active">${visit.payment_status_text}</span></td>
                                `;
                                purchaseHistory.appendChild(row);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading visits:', error);
                    });
                }

                // Edit Customer
                function editCustomer(customerId) {
                    const formData = new FormData();
                    formData.append('action', 'get_customer');
                    formData.append('customer_id', customerId);
                    
                    fetch('customers_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const customer = data.data;
                            
                            // Populate form
                            document.getElementById('modalTitle').textContent = 'Edit Customer';
                            document.getElementById('customerId').value = customer.customer_id;
                            document.getElementById('firstName').value = customer.first_name;
                            document.getElementById('lastName').value = customer.last_name;
                            document.getElementById('customerCode').value = customer.customer_code;
                            document.getElementById('gender').value = customer.gender;
                            document.getElementById('dateOfBirth').value = customer.date_of_birth;
                            document.getElementById('nationalId').value = customer.national_id;
                            document.getElementById('email').value = customer.email;
                            document.getElementById('phone').value = customer.phone;
                            document.getElementById('alternatePhone').value = customer.alternate_phone;
                            document.getElementById('address').value = customer.address;
                            document.getElementById('city').value = customer.city;
                            document.getElementById('region').value = customer.region;
                            document.getElementById('bloodGroup').value = customer.blood_group;
                            document.getElementById('allergies').value = customer.allergies;
                            document.getElementById('medicalConditions').value = customer.medical_conditions;
                            document.getElementById('currentMedications').value = customer.current_medications;
                            document.getElementById('customerType').value = customer.customer_type;
                            document.getElementById('registrationDate').value = customer.registration_date;
                            document.getElementById('referredBy').value = customer.referred_by;
                            document.getElementById('loyaltyPoints').value = customer.loyalty_points;
                            document.getElementById('status').value = customer.status;
                            document.getElementById('preferredCommunication').value = customer.preferred_communication;
                            document.getElementById('notes').value = customer.notes;
                            
                            if (isAdmin && customer.branch_id) {
                                document.getElementById('branchId').value = customer.branch_id;
                            }
                            
                            // Set avatar preview
                            const avatarPreview = document.getElementById('avatarPreview');
                            avatarPreview.innerHTML = '';
                            avatarPreview.style.background = getAvatarColor(customer.customer_type);
                            const initialsSpan = document.createElement('span');
                            initialsSpan.id = 'avatarInitials';
                            initialsSpan.textContent = (customer.first_name.charAt(0) + customer.last_name.charAt(0)).toUpperCase();
                            initialsSpan.style.color = 'white';
                            avatarPreview.appendChild(initialsSpan);
                            
                            // Show modal
                            customerModal.classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load customer for editing');
                    });
                }

                // Edit Current Customer (from view modal)
                function editCurrentCustomer() {
                    if (customerToView) {
                        viewCustomerModal.classList.remove('active');
                        setTimeout(() => {
                            editCustomer(customerToView.customer_id);
                        }, 300);
                    }
                }

                // Save Customer (Add/Edit)
                function saveCustomer() {
                    // Validate form
                    const requiredFields = ['firstName', 'lastName', 'phone', 'email', 'address', 'city', 'customerType'];
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
                    
                    // Validate phone number format
                    const phone = document.getElementById('phone').value;
                    const phoneRegex = /^(\+265|265|0)?(88|99|98|97)\d{7}$/;
                    const cleanPhone = phone.replace(/\D/g, '');
                    
                    if (!phoneRegex.test(cleanPhone)) {
                        showError('Please enter a valid Malawi phone number (format: 088XXXXXXX or +26588XXXXXXX)');
                        return;
                    }
                    
                    // Validate email
                    const email = document.getElementById('email').value;
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        showError('Please enter a valid email address');
                        return;
                    }
                    
                    // Prepare form data
                    const formData = new FormData();
                    const customerId = document.getElementById('customerId').value;
                    formData.append('action', customerId ? 'edit_customer' : 'add_customer');
                    
                    if (customerId) {
                        formData.append('customer_id', customerId);
                    }
                    
                    formData.append('first_name', document.getElementById('firstName').value);
                    formData.append('last_name', document.getElementById('lastName').value);
                    formData.append('gender', document.getElementById('gender').value);
                    formData.append('date_of_birth', document.getElementById('dateOfBirth').value);
                    formData.append('national_id', document.getElementById('nationalId').value);
                    formData.append('email', document.getElementById('email').value);
                    formData.append('phone', document.getElementById('phone').value);
                    formData.append('alternate_phone', document.getElementById('alternatePhone').value);
                    formData.append('address', document.getElementById('address').value);
                    formData.append('city', document.getElementById('city').value);
                    formData.append('region', document.getElementById('region').value);
                    formData.append('blood_group', document.getElementById('bloodGroup').value);
                    formData.append('allergies', document.getElementById('allergies').value);
                    formData.append('medical_conditions', document.getElementById('medicalConditions').value);
                    formData.append('current_medications', document.getElementById('currentMedications').value);
                    formData.append('customer_type', document.getElementById('customerType').value);
                    formData.append('registration_date', document.getElementById('registrationDate').value);
                    formData.append('referred_by', document.getElementById('referredBy').value);
                    formData.append('loyalty_points', document.getElementById('loyaltyPoints').value);
                    formData.append('status', document.getElementById('status').value);
                    formData.append('preferred_communication', document.getElementById('preferredCommunication').value);
                    formData.append('notes', document.getElementById('notes').value);
                    
                    if (isAdmin) {
                        formData.append('branch_id', document.getElementById('branchId').value);
                    }
                    
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
                    fetch('customers_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            showSuccess(data.message);
                            closeCustomerModal();
                            loadCustomers();
                            updateStatistics();
                            
                            if (customerToView) {
                                viewCustomer(customerToView.customer_id);
                            }
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        showError('Failed to save customer');
                    });
                }

                // Delete Customer
                function deleteCustomer(customerId) {
                    const formData = new FormData();
                    formData.append('action', 'get_customer');
                    formData.append('customer_id', customerId);
                    
                    fetch('customers_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            customerToDelete = data.data;
                            document.getElementById('deleteCustomerName').textContent = `${customerToDelete.first_name} ${customerToDelete.last_name}`;
                            deleteCustomerModal.classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load customer for deletion');
                    });
                }

                // Confirm Delete
                function confirmDelete() {
                    if (!customerToDelete) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_customer');
                    formData.append('customer_id', customerToDelete.customer_id);
                    
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('customers_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            showSuccess(data.message);
                            closeDeleteModal();
                            loadCustomers();
                            updateStatistics();
                            
                            if (customerToView && customerToView.customer_id === customerToDelete.customer_id) {
                                closeViewModal();
                            }
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        showError('Failed to delete customer');
                    });
                }

                // Create Prescription
                function createPrescription(customerId = null) {
                    const targetCustomerId = customerId || (customerToView ? customerToView.customer_id : null);
                    
                    if (!targetCustomerId) {
                        showError('No customer selected');
                        return;
                    }
                    
                    // Redirect to prescription page or open prescription modal
                    window.location.href = `prescriptions.php?action=create&customer_id=${targetCustomerId}`;
                }

                // Export Customers
                function exportCustomers(format) {
                    const search = document.getElementById('searchInput').value;
                    const status = document.getElementById('statusFilter').value;
                    const type = document.getElementById('typeFilter').value;
                    const city = document.getElementById('locationFilter').value;
                    
                    let url = `export_customers.php?format=${format}`;
                    if (search) url += `&search=${encodeURIComponent(search)}`;
                    if (status) url += `&status=${status}`;
                    if (type) url += `&type=${type}`;
                    if (city) url += `&city=${encodeURIComponent(city)}`;
                    if (!isAdmin && currentBranchId) url += `&branch_id=${currentBranchId}`;
                    
                    window.open(url, '_blank');
                }

                // Utility Functions
                function formatCurrency(amount) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(amount);
                }

                function getAvatarColor(customerType) {
                    const colorMap = {
                        'regular': '#3498db',
                        'premium': '#9b59b6',
                        'wholesale': '#e67e22',
                        'corporate': '#2980b9',
                        'government': '#2ecc71'
                    };
                    return colorMap[customerType] || '#3498db';
                }

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

                // Handle Keyboard Shortcuts
                document.addEventListener('keydown', function(e) {
                    // Ctrl + F for search
                    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                        e.preventDefault();
                        document.getElementById('searchInput').focus();
                    }
                    
                    // Ctrl + N for new customer
                    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                        e.preventDefault();
                        if (!customerModal.classList.contains('active')) {
                            openAddCustomerModal();
                        }
                    }
                    
                    // Escape to close modals
                    if (e.key === 'Escape') {
                        if (customerModal.classList.contains('active')) {
                            closeCustomerModal();
                        } else if (viewCustomerModal.classList.contains('active')) {
                            closeViewModal();
                        } else if (deleteCustomerModal.classList.contains('active')) {
                            closeDeleteModal();
                        }
                    }
                });

                // Real-time Search
                let searchTimeout;
                document.getElementById('searchInput').addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        filterCustomers();
                    }, 500);
                });

                // Auto-refresh customers every 5 minutes
                setInterval(() => {
                    // Only refresh if no modals are open and user is active
                    const modalsOpen = document.querySelectorAll('.modal-overlay.active').length > 0;
                    if (!modalsOpen) {
                        loadCustomers();
                        updateStatistics();
                        console.log('Auto-refreshed customer data');
                    }
                }, 300000); // 5 minutes

                // Initialize on page load
                window.onload = function() {
                    console.log('Customers Management System Ready');
                };
            </script>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>