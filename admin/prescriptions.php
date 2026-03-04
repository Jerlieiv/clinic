<<<<<<< HEAD
<?php
// prescriptions.php - Frontend with Backend Integration

// Start session and check login
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get current user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? 'Unknown User';
$user_role = $_SESSION['user_role'] ?? 'staff';
$current_branch_id = $_SESSION['branch_id'] ?? null;

// Include configuration file
require_once '../config/db.php';
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
                /* ================== Prescriptions Management Styles ============== */
                .prescriptions-management-section {
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

                .action-btn.info {
                    background: var(--info);
                    color: white;
                }

                .action-btn.info:hover {
                    background: #2980b9;
                    transform: translateY(-2px);
                }

                /* Statistics Cards */
                .prescriptions-stats {
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

                /* Prescriptions Table */
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

                /* ================== RESPONSIVE TABLE STYLES ================== */
                .table-responsive {
                    width: 100%;
                    overflow-x: auto;
                    position: relative;
                }

                .table-responsive::-webkit-scrollbar {
                    height: 8px;
                }

                .table-responsive::-webkit-scrollbar-track {
                    background: var(--light-gray);
                    border-radius: 4px;
                }

                .table-responsive::-webkit-scrollbar-thumb {
                    background: var(--primary);
                    border-radius: 4px;
                }

                .table-responsive::-webkit-scrollbar-thumb:hover {
                    background: var(--secondary);
                }

                .prescriptions-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 1200px;
                }

                .prescriptions-table thead {
                    background: var(--light-gray);
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }

                .prescriptions-table th {
                    padding: 18px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    border-bottom: 2px solid var(--gray);
                    white-space: nowrap;
                }

                .prescriptions-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.3s ease;
                }

                .prescriptions-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .prescriptions-table td {
                    padding: 15px 20px;
                    color: var(--black);
                    font-size: 0.95rem;
                    vertical-align: middle;
                    min-width: 120px;
                }

                /* Prescription Status Badges */
                .prescription-status {
                    display: inline-block;
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    white-space: nowrap;
                }

                .status-active {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .status-completed {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
                }

                .status-pending {
                    background: rgba(243, 156, 18, 0.1);
                    color: var(--warning);
                }

                .status-expired {
                    background: rgba(231, 76, 60, 0.1);
                    color: var(--danger);
                }

                .status-cancelled {
                    background: rgba(149, 165, 166, 0.1);
                    color: #7f8c8d;
                }

                /* Urgency Badges */
                .urgency-badge {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    white-space: nowrap;
                }

                .urgency-low {
                    background: rgba(46, 204, 113, 0.1);
                    color: #2ecc71;
                }

                .urgency-medium {
                    background: rgba(241, 196, 15, 0.1);
                    color: #f1c40f;
                }

                .urgency-high {
                    background: rgba(231, 76, 60, 0.1);
                    color: #e74c3c;
                }

                /* Prescription Type Badges */
                .prescription-type {
                    display: inline-block;
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    white-space: nowrap;
                }

                .type-new {
                    background: rgba(26, 188, 156, 0.1);
                    color: var(--accent);
                }

                .type-refill {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .type-renewal {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
                }

                .type-emergency {
                    background: rgba(231, 76, 60, 0.1);
                    color: #e74c3c;
                }

                /* Customer Info */
                .customer-info-cell {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    min-width: 200px;
                }

                .customer-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 2px solid var(--gray);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 1rem;
                    color: white;
                    background: linear-gradient(135deg, var(--primary), var(--secondary));
                    flex-shrink: 0;
                }

                .customer-avatar img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .customer-details {
                    display: flex;
                    flex-direction: column;
                    min-width: 150px;
                }

                .customer-name {
                    font-weight: 600;
                    color: var(--black);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .customer-id {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    white-space: nowrap;
                }

                /* Prescription Info */
                .prescription-info {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    min-width: 150px;
                }

                .prescription-number {
                    font-weight: 600;
                    color: var(--primary);
                    white-space: nowrap;
                }

                .prescription-date {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    white-space: nowrap;
                }

                /* Doctor Info */
                .doctor-info {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    min-width: 180px;
                }

                .doctor-name {
                    font-weight: 500;
                    color: var(--black);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .doctor-specialty {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    white-space: nowrap;
                }

                /* Medications List */
                .medications-list {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    min-width: 200px;
                }

                .medication-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 5px 0;
                    border-bottom: 1px solid var(--light-gray);
                }

                .medication-item:last-child {
                    border-bottom: none;
                }

                .medication-name {
                    font-weight: 500;
                    color: var(--black);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    flex: 1;
                }

                .medication-dosage {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    white-space: nowrap;
                    margin-left: 10px;
                }

                /* Actions */
                .prescription-actions {
                    display: flex;
                    gap: 8px;
                    min-width: 180px;
                    flex-wrap: wrap;
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

                .action-icon.print {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
                }

                .action-icon.print:hover {
                    background: #3498db;
                    color: white;
                    transform: translateY(-2px);
                }

                .action-icon.dispense {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .action-icon.dispense:hover {
                    background: var(--success);
                    color: white;
                    transform: translateY(-2px);
                }

                /* Loading States */
                .loading {
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    border: 3px solid rgba(255,255,255,.3);
                    border-radius: 50%;
                    border-top-color: white;
                    animation: spin 1s ease-in-out infinite;
                }

                @keyframes spin {
                    to { transform: rotate(360deg); }
                }

                .loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 3000;
                    display: none;
                }

                .loading-spinner {
                    width: 50px;
                    height: 50px;
                    border: 5px solid rgba(255,255,255,.3);
                    border-radius: 50%;
                    border-top-color: var(--primary);
                    animation: spin 1s ease-in-out infinite;
                }

                .skeleton {
                    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                    background-size: 200% 100%;
                    animation: loading 1.5s infinite;
                }

                @keyframes loading {
                    0% { background-position: 200% 0; }
                    100% { background-position: -200% 0; }
                }

                .skeleton-text {
                    height: 12px;
                    width: 100%;
                    margin-bottom: 8px;
                    border-radius: 4px;
                }

                .skeleton-button {
                    height: 35px;
                    width: 35px;
                    border-radius: 8px;
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
                    max-width: 900px;
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

                /* Date Inputs */
                .date-input {
                    position: relative;
                }

                .date-input ion-icon {
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: var(--dark-gray);
                }

                /* Medications Section */
                .medications-section {
                    grid-column: 1 / -1;
                    background: var(--light-gray);
                    padding: 20px;
                    border-radius: 10px;
                    margin-top: 10px;
                }

                .medications-section h4 {
                    color: var(--primary);
                    margin: 0 0 15px 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .add-medication-btn {
                    padding: 8px 15px;
                    background: var(--primary);
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                .medications-list-form {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .medication-row {
                    display: grid;
                    grid-template-columns: 2fr 1fr 1fr 1fr auto;
                    gap: 10px;
                    align-items: center;
                    padding: 15px;
                    background: white;
                    border-radius: 8px;
                    border: 1px solid var(--gray);
                }

                .remove-medication {
                    background: none;
                    border: none;
                    color: var(--danger);
                    cursor: pointer;
                    font-size: 1.2rem;
                    padding: 5px;
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

                /* View Prescription Modal */
                .view-details-modal .modal-content {
                    max-width: 800px;
                }

                .prescription-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 1px solid var(--gray);
                }

                .prescription-info-header {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .prescription-id {
                    font-size: 1.8rem;
                    font-weight: 700;
                    color: var(--primary);
                }

                .prescription-date {
                    color: var(--dark-gray);
                }

                .status-badge-large {
                    padding: 8px 20px;
                    border-radius: 20px;
                    font-weight: 600;
                    font-size: 1rem;
                }

                .prescription-details-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                    margin-bottom: 30px;
                }

                .detail-card {
                    background: var(--light-gray);
                    padding: 20px;
                    border-radius: 10px;
                    border-left: 4px solid var(--primary);
                }

                .detail-card h5 {
                    margin: 0 0 15px 0;
                    color: var(--primary);
                }

                .detail-item {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }

                .detail-label {
                    color: var(--dark-gray);
                }

                .detail-value {
                    font-weight: 500;
                    color: var(--black);
                }

                /* Medications Table in View Modal */
                .medications-table-view {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }

                .medications-table-view th {
                    background: var(--light-gray);
                    padding: 12px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                }

                .medications-table-view td {
                    padding: 12px;
                    border-bottom: 1px solid var(--gray);
                }

                /* Instructions Section */
                .instructions-section {
                    background: var(--light-gray);
                    padding: 20px;
                    border-radius: 10px;
                    margin-top: 20px;
                }

                .instructions-section h5 {
                    margin: 0 0 15px 0;
                    color: var(--primary);
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

                /* Error States */
                .error-message {
                    color: var(--danger);
                    background: rgba(231, 76, 60, 0.1);
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    display: none;
                }

                .error-message.show {
                    display: block;
                }

                /* Success Message */
                .success-message {
                    color: var(--success);
                    background: rgba(39, 174, 96, 0.1);
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    display: none;
                }

                .success-message.show {
                    display: block;
                }

                /* Responsive Design */
                @media (max-width: 1200px) {
                    .modal-form {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 992px) {
                    .prescriptions-stats {
                        grid-template-columns: repeat(3, 1fr);
                    }
                    
                    .prescription-actions {
                        min-width: 150px;
                    }
                }

                @media (max-width: 768px) {
                    .prescriptions-management-section {
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

                    .prescriptions-stats {
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

                    .prescriptions-table {
                        min-width: 900px;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }

                    .prescription-actions {
                        flex-direction: row;
                        flex-wrap: wrap;
                        min-width: 120px;
                    }

                    .action-icon {
                        width: 30px;
                        height: 30px;
                        font-size: 1rem;
                    }

                    .prescription-details-grid {
                        grid-template-columns: 1fr;
                    }

                    .medication-row {
                        grid-template-columns: 1fr;
                        gap: 10px;
                    }

                    .remove-medication {
                        align-self: flex-end;
                    }
                    
                    .customer-info-cell {
                        min-width: 150px;
                    }
                }

                @media (max-width: 480px) {
                    .prescriptions-stats {
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
                    
                    .table-header h2 {
                        font-size: 1.2rem;
                    }
                    
                    .prescription-actions {
                        gap: 5px;
                    }
                }
            </style>

            <!-- Loading Overlay -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
            </div>

            <!-- Error Message -->
            <div class="error-message" id="errorMessage"></div>

            <!-- Success Message -->
            <div class="success-message" id="successMessage"></div>

            <!-- ================== Prescriptions Management Content ============== -->
            <div class="prescriptions-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Prescriptions Management</h1>
                        <p>Manage and dispense medical prescriptions for patients</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshPrescriptions()" id="refreshBtn">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <button class="action-btn primary" onclick="openAddPrescriptionModal()" id="addPrescriptionBtn">
                            <ion-icon name="medical-outline"></ion-icon>
                            Create New Prescription
                        </button>
                    </div>
                </div>

                <!-- Prescriptions Statistics -->
                <div class="prescriptions-stats" id="prescriptionsStats">
                    <!-- Statistics will be loaded here -->
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search by patient name, prescription number, or doctor..." 
                               onkeyup="filterPrescriptions()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterPrescriptions()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Prescription Type</label>
                            <select id="typeFilter" onchange="filterPrescriptions()">
                                <option value="">All Types</option>
                                <option value="new">New Prescription</option>
                                <option value="refill">Refill</option>
                                <option value="renewal">Renewal</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Urgency</label>
                            <select id="urgencyFilter" onchange="filterPrescriptions()">
                                <option value="">All Urgency</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterPrescriptions()">
                                <option value="date_desc">Date (Newest First)</option>
                                <option value="date_asc">Date (Oldest First)</option>
                                <option value="urgency_high">Urgency (High to Low)</option>
                                <option value="patient_asc">Patient Name (A-Z)</option>
                                <option value="patient_desc">Patient Name (Z-A)</option>
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

                <!-- Prescriptions Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Medical Prescriptions</h2>
                    </div>
                    
                    <!-- Responsive Table Container with Horizontal Scroll -->
                    <div class="table-responsive">
                        <table class="prescriptions-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>PRESCRIPTION DETAILS</th>
                                    <th>PATIENT INFORMATION</th>
                                    <th>DOCTOR INFORMATION</th>
                                    <th>MEDICATIONS</th>
                                    <th>URGENCY</th>
                                    <th>VALID UNTIL</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="prescriptionsTableBody">
                                <!-- Prescriptions will be populated here by JavaScript -->
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 50px;">
                                        <div class="loading">Loading prescriptions...</div>
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
                            <button class="pagination-btn" onclick="changePage('prev')" disabled>
                                <ion-icon name="chevron-back-outline"></ion-icon>
                            </button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn" onclick="changePage('next')">
                                <ion-icon name="chevron-forward-outline"></ion-icon>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Prescription Modal -->
            <div class="modal-overlay" id="prescriptionModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Create New Prescription</h3>
                        <button class="modal-close" onclick="closePrescriptionModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="prescriptionForm" class="modal-form">
                            <input type="hidden" id="prescriptionId">
                            
                            <!-- Prescription Information -->
                            <div class="form-group">
                                <label for="prescriptionNumber">Prescription Number</label>
                                <input type="text" id="prescriptionNumber" placeholder="Auto-generated" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="prescriptionDate" class="required">Prescription Date</label>
                                <div class="date-input">
                                    <input type="date" id="prescriptionDate" value="<?php echo date('Y-m-d'); ?>" required>
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="prescriptionType" class="required">Prescription Type</label>
                                <select id="prescriptionType" required>
                                    <option value="">-- select --</option>
                                    <option value="new">New Prescription</option>
                                    <option value="refill">Refill</option>
                                    <option value="renewal">Renewal</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="urgencyLevel" class="required">Urgency Level</label>
                                <select id="urgencyLevel" required>
                                    <option value="">-- select --</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            
                            <!-- Patient Information -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Patient Information</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="customerId" class="required">Patient</label>
                                <select id="customerId" required onchange="loadCustomerInfo()">
                                    <option value="">-- select patient --</option>
                                    <!-- Patients will be loaded dynamically -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="patientAge">Age</label>
                                <input type="text" id="patientAge" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="patientGender">Gender</label>
                                <input type="text" id="patientGender" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="patientWeight">Weight (kg)</label>
                                <input type="number" id="patientWeight" step="0.1" placeholder="e.g., 70.5">
                            </div>
                            
                            <div class="form-group">
                                <label for="patientHeight">Height (cm)</label>
                                <input type="number" id="patientHeight" placeholder="e.g., 175">
                            </div>
                            
                            <!-- Doctor Information -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Doctor Information</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctorId" class="required">Doctor</label>
                                <select id="doctorId" required onchange="loadDoctorInfo()">
                                    <option value="">-- select doctor --</option>
                                    <!-- Doctors will be loaded dynamically -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctorName">Doctor Name</label>
                                <input type="text" id="doctorName" readonly>
                            </div>
                            
                            <!-- Diagnosis -->
                            <div class="form-group full-width">
                                <label for="diagnosis" class="required">Diagnosis</label>
                                <textarea id="diagnosis" placeholder="Enter primary diagnosis..." required></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="symptoms">Symptoms & Clinical Notes</label>
                                <textarea id="symptoms" placeholder="Describe symptoms and clinical findings..."></textarea>
                            </div>
                            
                            <!-- Medications Section -->
                            <div class="medications-section">
                                <h4>
                                    Medications
                                    <button type="button" class="add-medication-btn" onclick="addMedicationRow()">
                                        <ion-icon name="add-outline"></ion-icon>
                                        Add Medication
                                    </button>
                                </h4>
                                
                                <div class="medications-list-form" id="medicationsList">
                                    <!-- Medication rows will be added here dynamically -->
                                    <div class="medication-row">
                                        <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px;">
                                            <select class="medicine-select" onchange="loadMedicineInfo(this)" required>
                                                <option value="">-- Select Medicine --</option>
                                            </select>
                                            <input type="text" placeholder="Dosage (e.g., 500mg)" class="medication-dosage" required>
                                            <input type="text" placeholder="Frequency (e.g., 3 times daily)" class="medication-frequency" required>
                                            <input type="number" placeholder="Duration (days)" class="medication-duration" min="1" required>
                                            <button type="button" class="remove-medication" onclick="removeMedicationRow(this)">
                                                <ion-icon name="close-outline"></ion-icon>
                                            </button>
                                        </div>
                                        <div style="grid-column: 1 / -1;">
                                            <textarea class="medication-instructions" placeholder="Instructions for this medication..." rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prescription Details -->
                            <div class="form-group full-width">
                                <label for="instructions" class="required">Instructions for Use</label>
                                <textarea id="instructions" placeholder="Enter detailed instructions for the patient..." required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="validFrom" class="required">Valid From</label>
                                <div class="date-input">
                                    <input type="date" id="validFrom" value="<?php echo date('Y-m-d'); ?>" required>
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="validUntil" class="required">Valid Until</label>
                                <div class="date-input">
                                    <input type="date" id="validUntil" required>
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="refillsAllowed">Refills Allowed</label>
                                <input type="number" id="refillsAllowed" min="0" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status">
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="form-group full-width">
                                <label for="notes">Additional Notes</label>
                                <textarea id="notes" placeholder="Any additional information about this prescription..."></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="allergiesWarning">Allergies Warning</label>
                                <textarea id="allergiesWarning" placeholder="List any allergy warnings or contraindications..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closePrescriptionModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="savePrescription()" id="savePrescriptionBtn">
                            Save Prescription
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Prescription Details Modal -->
            <div class="modal-overlay view-details-modal" id="viewPrescriptionModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="viewModalTitle">Prescription Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="prescription-header">
                            <div class="prescription-info-header">
                                <div class="prescription-id" id="viewPrescriptionId">Loading...</div>
                                <div class="prescription-date" id="viewPrescriptionDate">Date: Loading...</div>
                            </div>
                            <div class="status-badge-large" id="viewStatusBadge">Loading...</div>
                        </div>
                        
                        <div class="prescription-details-grid">
                            <!-- Patient Information -->
                            <div class="detail-card">
                                <h5>Patient Information</h5>
                                <div class="detail-item">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value" id="viewPatientName">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Age/Gender:</span>
                                    <span class="detail-value" id="viewPatientAgeGender">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Patient ID:</span>
                                    <span class="detail-value" id="viewPatientId">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Weight/Height:</span>
                                    <span class="detail-value" id="viewPatientWeightHeight">Loading...</span>
                                </div>
                            </div>
                            
                            <!-- Doctor Information -->
                            <div class="detail-card">
                                <h5>Doctor Information</h5>
                                <div class="detail-item">
                                    <span class="detail-label">Doctor:</span>
                                    <span class="detail-value" id="viewDoctorName">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Specialty:</span>
                                    <span class="detail-value" id="viewDoctorSpecialty">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Diagnosis:</span>
                                    <span class="detail-value" id="viewDiagnosis">Loading...</span>
                                </div>
                            </div>
                            
                            <!-- Prescription Details -->
                            <div class="detail-card">
                                <h5>Prescription Details</h5>
                                <div class="detail-item">
                                    <span class="detail-label">Type:</span>
                                    <span class="detail-value" id="viewPrescriptionType">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Urgency:</span>
                                    <span class="detail-value" id="viewUrgencyLevel">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Valid From:</span>
                                    <span class="detail-value" id="viewValidFrom">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Valid Until:</span>
                                    <span class="detail-value" id="viewValidUntil">Loading...</span>
                                </div>
                            </div>
                            
                            <!-- Medical Information -->
                            <div class="detail-card">
                                <h5>Medical Information</h5>
                                <div class="detail-item">
                                    <span class="detail-label">Allergies:</span>
                                    <span class="detail-value" id="viewAllergies">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Refills Allowed:</span>
                                    <span class="detail-value" id="viewRefillsAllowed">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Refills Used:</span>
                                    <span class="detail-value" id="viewRefillsUsed">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value" id="viewStatus">Loading...</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medications List -->
                        <h5 style="margin-bottom: 15px; color: var(--primary);">Prescribed Medications</h5>
                        <table class="medications-table-view">
                            <thead>
                                <tr>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Instructions</th>
                                </tr>
                            </thead>
                            <tbody id="viewMedicationsTable">
                                <tr><td colspan="5">Loading medications...</td></tr>
                            </tbody>
                        </table>
                        
                        <!-- Instructions -->
                        <div class="instructions-section">
                            <h5>Instructions for Use</h5>
                            <div id="viewInstructions">Loading...</div>
                        </div>
                        
                        <!-- Additional Notes -->
                        <div class="form-group full-width" style="margin-top: 20px;">
                            <label>Additional Notes:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px;" id="viewNotes">
                                Loading...
                            </div>
                        </div>
                        
                        <!-- Dispensing History -->
                        <div style="margin-top: 30px;">
                            <h5 style="margin-bottom: 15px; color: var(--primary);">Dispensing History</h5>
                            <table class="medications-table-view">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Pharmacist</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="viewDispensingHistory">
                                    <tr><td colspan="4">Loading dispensing history...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                        <button type="button" class="action-btn primary" onclick="editCurrentPrescription()">
                            <ion-icon name="create-outline"></ion-icon>
                            Edit Prescription
                        </button>
                        <button type="button" class="action-btn print" onclick="printPrescription()">
                            <ion-icon name="print-outline"></ion-icon>
                            Print
                        </button>
                        <button type="button" class="action-btn success" onclick="dispensePrescription()">
                            <ion-icon name="cart-outline"></ion-icon>
                            Dispense
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="deletePrescriptionModal">
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
                        <h4>Delete Prescription</h4>
                        <p>Are you sure you want to delete prescription <strong id="deletePrescriptionId">[Loading...]</strong>? This action cannot be undone and all prescription data will be permanently removed.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="action-btn danger" onclick="confirmDelete()" id="confirmDeleteBtn">
                                <ion-icon name="trash-outline"></ion-icon>
                                Delete Prescription
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dispense Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="dispensePrescriptionModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Confirm Dispensing</h3>
                        <button class="modal-close" onclick="closeDispenseModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="confirmation-body">
                        <div class="confirmation-icon success">
                            <ion-icon name="cart-outline"></ion-icon>
                        </div>
                        <h4>Dispense Prescription</h4>
                        <p>Are you ready to dispense prescription <strong id="dispensePrescriptionId">[Loading...]</strong> for <strong id="dispensePatientName">[Loading...]</strong>?</p>
                        
                        <div class="form-group" style="margin: 20px 0;">
                            <label for="dispensingNotes">Dispensing Notes (Optional)</label>
                            <textarea id="dispensingNotes" placeholder="Enter any notes about the dispensing process..."></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDispenseModal()">
                                Cancel
                            </button>
                            <button class="action-btn success" onclick="confirmDispense()" id="confirmDispenseBtn">
                                <ion-icon name="checkmark-outline"></ion-icon>
                                Confirm Dispensing
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Include jQuery -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <script>
                // Global Variables
                let prescriptions = [];
                let filteredPrescriptions = [];
                let currentPage = 1;
                const itemsPerPage = 10;
                let prescriptionToDelete = null;
                let prescriptionToView = null;
                let prescriptionToDispense = null;
                let doctorsList = [];
                let customersList = [];
                let medicinesList = [];

                // DOM Elements
                const tableBody = document.getElementById('prescriptionsTableBody');
                const prescriptionModal = document.getElementById('prescriptionModal');
                const viewPrescriptionModal = document.getElementById('viewPrescriptionModal');
                const deletePrescriptionModal = document.getElementById('deletePrescriptionModal');
                const dispensePrescriptionModal = document.getElementById('dispensePrescriptionModal');
                const loadingOverlay = document.getElementById('loadingOverlay');
                const errorMessage = document.getElementById('errorMessage');
                const successMessage = document.getElementById('successMessage');
                const prescriptionsStats = document.getElementById('prescriptionsStats');

                // API Configuration
                const API_BASE = 'prescriptions_backend.php';

                // Utility Functions
                function showLoading(button = null) {
                    loadingOverlay.style.display = 'flex';
                    if (button) {
                        const originalText = button.innerHTML;
                        button.setAttribute('data-original-text', originalText);
                        button.innerHTML = '<span class="loading"></span>';
                        button.disabled = true;
                    }
                }

                function hideLoading(button = null) {
                    loadingOverlay.style.display = 'none';
                    if (button && button.hasAttribute('data-original-text')) {
                        button.innerHTML = button.getAttribute('data-original-text');
                        button.disabled = false;
                    }
                }

                function showError(message) {
                    errorMessage.textContent = message;
                    errorMessage.classList.add('show');
                    
                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        errorMessage.classList.remove('show');
                    }, 5000);
                }

                function showSuccess(message) {
                    successMessage.textContent = message;
                    successMessage.classList.add('show');
                    
                    // Auto-hide after 3 seconds
                    setTimeout(() => {
                        successMessage.classList.remove('show');
                    }, 3000);
                }

                function clearMessages() {
                    errorMessage.classList.remove('show');
                    successMessage.classList.remove('show');
                }

                function formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }

                // API Functions
                async function apiCall(action, data = {}) {
                    const formData = new FormData();
                    formData.append('action', action);
                    
                    for (const key in data) {
                        if (Array.isArray(data[key])) {
                            // Handle arrays (like medications)
                            data[key].forEach((item, index) => {
                                for (const subKey in item) {
                                    formData.append(`${key}[${index}][${subKey}]`, item[subKey]);
                                }
                            });
                        } else {
                            formData.append(key, data[key]);
                        }
                    }

                    try {
                        const response = await fetch(API_BASE, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();
                        
                        if (!result.success) {
                            throw new Error(result.message || 'An error occurred');
                        }
                        
                        return result;
                    } catch (error) {
                        console.error('API Error:', error);
                        throw error;
                    }
                }

                // Data Loading Functions
                async function loadPrescriptions() {
                    showLoading();
                    clearMessages();
                    
                    try {
                        const filters = {
                            search: document.getElementById('searchInput').value,
                            status: document.getElementById('statusFilter').value,
                            type: document.getElementById('typeFilter').value,
                            urgency: document.getElementById('urgencyFilter').value,
                            sort: document.getElementById('sortFilter').value,
                            page: currentPage,
                            limit: itemsPerPage
                        };

                        const result = await apiCall('get_prescriptions', filters);
                        
                        prescriptions = result.data;
                        filteredPrescriptions = result.data;
                        
                        renderPrescriptionsTable();
                        updatePaginationInfo(result.total, result.page, result.pages);
                        
                    } catch (error) {
                        showError('Failed to load prescriptions: ' + error.message);
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <ion-icon name="alert-circle-outline"></ion-icon>
                                    <h3>Error Loading Data</h3>
                                    <p>${error.message}</p>
                                    <button class="action-btn secondary" onclick="loadPrescriptions()" style="margin-top: 15px;">
                                        <ion-icon name="refresh-outline"></ion-icon>
                                        Try Again
                                    </button>
                                </td>
                            </tr>
                        `;
                    } finally {
                        hideLoading();
                    }
                }

                async function loadStatistics() {
                    try {
                        const result = await apiCall('get_prescription_stats');
                        
                        const stats = result.stats;
                        const statsHtml = `
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="document-text-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.total_prescriptions}</h3>
                                    <p>Total Prescriptions</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="time-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.pending_prescriptions}</h3>
                                    <p>Pending Dispensing</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.today_prescriptions}</h3>
                                    <p>Today's Prescriptions</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="warning-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.expiring_soon}</h3>
                                    <p>Expiring Soon</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="cash-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>MK${Math.round(stats.today_revenue / 1000)}K</h3>
                                    <p>Revenue Today</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="alert-circle-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.refill_prescriptions}</h3>
                                    <p>Refill Requests</p>
                                </div>
                            </div>
                        `;
                        
                        prescriptionsStats.innerHTML = statsHtml;
                        
                    } catch (error) {
                        console.error('Failed to load statistics:', error);
                    }
                }

                async function loadDoctors() {
                    try {
                        const result = await apiCall('get_doctors');
                        doctorsList = result.doctors;
                        
                        const doctorSelect = document.getElementById('doctorId');
                        doctorSelect.innerHTML = '<option value="">-- select doctor --</option>';
                        
                        doctorsList.forEach(doctor => {
                            const option = document.createElement('option');
                            option.value = doctor.user_id;
                            option.textContent = `${doctor.full_name} (${doctor.phone || 'No phone'})`;
                            doctorSelect.appendChild(option);
                        });
                        
                    } catch (error) {
                        console.error('Failed to load doctors:', error);
                    }
                }

                async function loadCustomers() {
                    try {
                        // This would need a separate API endpoint for customers
                        // For now, we'll use a generic search
                        const customerSelect = document.getElementById('customerId');
                        customerSelect.innerHTML = '<option value="">-- select patient --</option>';
                        
                        // We'll load customers on demand when the select is clicked
                        customerSelect.onfocus = async function() {
                            if (customersList.length === 0) {
                                try {
                                    const result = await apiCall('get_customers_autocomplete', { search: '' });
                                    customersList = result.customers || [];
                                    
                                    customerSelect.innerHTML = '<option value="">-- select patient --</option>';
                                    customersList.forEach(customer => {
                                        const option = document.createElement('option');
                                        option.value = customer.id;
                                        option.textContent = `${customer.name} (${customer.code}) - ${customer.phone || 'No phone'}`;
                                        option.setAttribute('data-customer', JSON.stringify(customer));
                                        customerSelect.appendChild(option);
                                    });
                                } catch (error) {
                                    console.error('Failed to load customers:', error);
                                }
                            }
                        };
                        
                    } catch (error) {
                        console.error('Failed to load customers:', error);
                    }
                }

                async function loadMedicines() {
                    try {
                        const result = await apiCall('get_medicines_autocomplete', { search: '' });
                        medicinesList = result.medicines || [];
                        
                        // Update all medicine selects
                        document.querySelectorAll('.medicine-select').forEach(select => {
                            if (select.options.length <= 1) {
                                select.innerHTML = '<option value="">-- Select Medicine --</option>';
                                medicinesList.forEach(medicine => {
                                    const option = document.createElement('option');
                                    option.value = medicine.id;
                                    option.textContent = `${medicine.name} (${medicine.strength || 'N/A'}) - Stock: ${medicine.current_stock}`;
                                    option.setAttribute('data-medicine', JSON.stringify(medicine));
                                    select.appendChild(option);
                                });
                            }
                        });
                        
                    } catch (error) {
                        console.error('Failed to load medicines:', error);
                    }
                }

                // Form Functions
                function loadCustomerInfo() {
                    const customerId = document.getElementById('customerId').value;
                    const selectedOption = document.getElementById('customerId').selectedOptions[0];
                    
                    if (selectedOption && selectedOption.getAttribute('data-customer')) {
                        const customer = JSON.parse(selectedOption.getAttribute('data-customer'));
                        document.getElementById('patientAge').value = customer.age || '';
                        document.getElementById('patientGender').value = customer.gender || '';
                        document.getElementById('allergiesWarning').value = customer.allergies || '';
                    }
                }

                function loadDoctorInfo() {
                    const doctorId = document.getElementById('doctorId').value;
                    const selectedOption = document.getElementById('doctorId').selectedOptions[0];
                    
                    if (selectedOption) {
                        document.getElementById('doctorName').value = selectedOption.textContent.split(' (')[0];
                    }
                }

                function loadMedicineInfo(selectElement) {
                    const selectedOption = selectElement.selectedOptions[0];
                    if (selectedOption && selectedOption.getAttribute('data-medicine')) {
                        const medicine = JSON.parse(selectedOption.getAttribute('data-medicine'));
                        const row = selectElement.closest('.medication-row');
                        const dosageInput = row.querySelector('.medication-dosage');
                        
                        // Auto-fill dosage if not already set
                        if (!dosageInput.value && medicine.strength) {
                            dosageInput.value = medicine.strength;
                        }
                        
                        // Check stock
                        if (medicine.current_stock <= 0) {
                            showError(`${medicine.name} is out of stock!`);
                        }
                    }
                }

                // Medication Management
                function addMedicationRow() {
                    const medicationsList = document.getElementById('medicationsList');
                    const medicationRow = document.createElement('div');
                    medicationRow.className = 'medication-row';
                    medicationRow.innerHTML = `
                        <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px;">
                            <select class="medicine-select" onchange="loadMedicineInfo(this)" required>
                                <option value="">-- Select Medicine --</option>
                            </select>
                            <input type="text" placeholder="Dosage (e.g., 500mg)" class="medication-dosage" required>
                            <input type="text" placeholder="Frequency (e.g., 3 times daily)" class="medication-frequency" required>
                            <input type="number" placeholder="Duration (days)" class="medication-duration" min="1" required>
                            <button type="button" class="remove-medication" onclick="removeMedicationRow(this)">
                                <ion-icon name="close-outline"></ion-icon>
                            </button>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <textarea class="medication-instructions" placeholder="Instructions for this medication..." rows="2"></textarea>
                        </div>
                    `;
                    
                    medicationsList.appendChild(medicationRow);
                    
                    // Load medicines into the new select
                    const select = medicationRow.querySelector('.medicine-select');
                    select.innerHTML = '<option value="">-- Select Medicine --</option>';
                    medicinesList.forEach(medicine => {
                        const option = document.createElement('option');
                        option.value = medicine.id;
                        option.textContent = `${medicine.name} (${medicine.strength || 'N/A'}) - Stock: ${medicine.current_stock}`;
                        option.setAttribute('data-medicine', JSON.stringify(medicine));
                        select.appendChild(option);
                    });
                }

                function removeMedicationRow(button) {
                    const row = button.closest('.medication-row');
                    if (row && document.querySelectorAll('.medication-row').length > 1) {
                        row.remove();
                    }
                }

                function getMedicationsData() {
                    const medications = [];
                    document.querySelectorAll('.medication-row').forEach(row => {
                        const medicineSelect = row.querySelector('.medicine-select');
                        const medicineId = medicineSelect.value;
                        const selectedOption = medicineSelect.selectedOptions[0];
                        const medicineName = selectedOption ? selectedOption.textContent.split(' (')[0] : '';
                        
                        medications.push({
                            medicine_id: medicineId || null,
                            medicine_name: medicineName || row.querySelector('.medication-dosage').previousSibling?.value || '',
                            dosage: row.querySelector('.medication-dosage').value,
                            frequency: row.querySelector('.medication-frequency').value,
                            duration: row.querySelector('.medication-duration').value,
                            instructions: row.querySelector('.medication-instructions')?.value || '',
                            quantity: 1 // Default quantity
                        });
                    });
                    return medications;
                }

                // Modal Functions
                async function openAddPrescriptionModal() {
                    clearMessages();
                    document.getElementById('modalTitle').textContent = 'Create New Prescription';
                    document.getElementById('prescriptionForm').reset();
                    document.getElementById('prescriptionId').value = '';
                    
                    // Set default dates
                    const today = new Date().toISOString().split('T')[0];
                    const nextMonth = new Date();
                    nextMonth.setMonth(nextMonth.getMonth() + 1);
                    const nextMonthStr = nextMonth.toISOString().split('T')[0];
                    
                    document.getElementById('prescriptionDate').value = today;
                    document.getElementById('validFrom').value = today;
                    document.getElementById('validUntil').value = nextMonthStr;
                    
                    // Clear patient info
                    document.getElementById('patientAge').value = '';
                    document.getElementById('patientGender').value = '';
                    document.getElementById('patientWeight').value = '';
                    document.getElementById('patientHeight').value = '';
                    document.getElementById('doctorName').value = '';
                    
                    // Reset medications list
                    const medicationsList = document.getElementById('medicationsList');
                    medicationsList.innerHTML = `
                        <div class="medication-row">
                            <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px;">
                                <select class="medicine-select" onchange="loadMedicineInfo(this)" required>
                                    <option value="">-- Select Medicine --</option>
                                </select>
                                <input type="text" placeholder="Dosage (e.g., 500mg)" class="medication-dosage" required>
                                <input type="text" placeholder="Frequency (e.g., 3 times daily)" class="medication-frequency" required>
                                <input type="number" placeholder="Duration (days)" class="medication-duration" min="1" required>
                                <button type="button" class="remove-medication" onclick="removeMedicationRow(this)">
                                    <ion-icon name="close-outline"></ion-icon>
                                </button>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <textarea class="medication-instructions" placeholder="Instructions for this medication..." rows="2"></textarea>
                            </div>
                        </div>
                    `;
                    
                    // Load medicines into the select
                    await loadMedicines();
                    
                    prescriptionModal.classList.add('active');
                }

                async function editPrescription(id) {
                    clearMessages();
                    showLoading();
                    
                    try {
                        const result = await apiCall('get_prescription', { prescription_id: id });
                        const prescription = result.data;
                        
                        document.getElementById('modalTitle').textContent = 'Edit Prescription';
                        document.getElementById('prescriptionId').value = prescription.prescription_id;
                        document.getElementById('prescriptionNumber').value = prescription.prescription_number;
                        document.getElementById('prescriptionDate').value = prescription.prescription_date;
                        document.getElementById('prescriptionType').value = prescription.prescription_type;
                        document.getElementById('urgencyLevel').value = prescription.urgency_level;
                        document.getElementById('customerId').value = prescription.customer_id;
                        document.getElementById('patientAge').value = prescription.patient_age || '';
                        document.getElementById('patientGender').value = prescription.patient_gender || '';
                        document.getElementById('patientWeight').value = prescription.patient_weight || '';
                        document.getElementById('patientHeight').value = prescription.patient_height || '';
                        document.getElementById('doctorId').value = prescription.created_by || '';
                        document.getElementById('doctorName').value = prescription.doctor_name || '';
                        document.getElementById('diagnosis').value = prescription.diagnosis || '';
                        document.getElementById('symptoms').value = prescription.symptoms || '';
                        document.getElementById('instructions').value = prescription.instructions || '';
                        document.getElementById('validFrom').value = prescription.valid_from || '';
                        document.getElementById('validUntil').value = prescription.valid_until || '';
                        document.getElementById('refillsAllowed').value = prescription.refills_allowed || 0;
                        document.getElementById('status').value = prescription.status || 'active';
                        document.getElementById('notes').value = prescription.notes || '';
                        document.getElementById('allergiesWarning').value = prescription.allergies_warning || '';
                        
                        // Load medications
                        const medicationsList = document.getElementById('medicationsList');
                        medicationsList.innerHTML = '';
                        
                        if (result.medications && result.medications.length > 0) {
                            result.medications.forEach(med => {
                                const medicationRow = document.createElement('div');
                                medicationRow.className = 'medication-row';
                                medicationRow.innerHTML = `
                                    <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px;">
                                        <select class="medicine-select" onchange="loadMedicineInfo(this)" required>
                                            <option value="">-- Select Medicine --</option>
                                        </select>
                                        <input type="text" placeholder="Dosage" class="medication-dosage" value="${med.dosage || ''}" required>
                                        <input type="text" placeholder="Frequency" class="medication-frequency" value="${med.frequency || ''}" required>
                                        <input type="number" placeholder="Duration" class="medication-duration" value="${med.duration || ''}" min="1" required>
                                        <button type="button" class="remove-medication" onclick="removeMedicationRow(this)">
                                            <ion-icon name="close-outline"></ion-icon>
                                        </button>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <textarea class="medication-instructions" placeholder="Instructions for this medication..." rows="2">${med.instructions || ''}</textarea>
                                    </div>
                                `;
                                medicationsList.appendChild(medicationRow);
                                
                                // Load medicines and select the right one
                                const select = medicationRow.querySelector('.medicine-select');
                                select.innerHTML = '<option value="">-- Select Medicine --</option>';
                                medicinesList.forEach(medicine => {
                                    const option = document.createElement('option');
                                    option.value = medicine.id;
                                    option.textContent = `${medicine.name} (${medicine.strength || 'N/A'}) - Stock: ${medicine.current_stock}`;
                                    option.setAttribute('data-medicine', JSON.stringify(medicine));
                                    select.appendChild(option);
                                });
                                
                                // Select the medicine if medicine_id exists
                                if (med.medicine_id) {
                                    setTimeout(() => {
                                        select.value = med.medicine_id;
                                    }, 100);
                                }
                            });
                        } else {
                            addMedicationRow();
                        }
                        
                        prescriptionModal.classList.add('active');
                        
                    } catch (error) {
                        showError('Failed to load prescription: ' + error.message);
                    } finally {
                        hideLoading();
                    }
                }

                async function viewPrescription(id) {
                    clearMessages();
                    showLoading();
                    
                    try {
                        const result = await apiCall('get_prescription', { prescription_id: id });
                        const prescription = result.data;
                        
                        prescriptionToView = prescription;
                        
                        // Update view modal
                        document.getElementById('viewModalTitle').textContent = `Prescription ${prescription.prescription_number}`;
                        document.getElementById('viewPrescriptionId').textContent = prescription.prescription_number;
                        document.getElementById('viewPrescriptionDate').textContent = `Date: ${formatDate(prescription.prescription_date)}`;
                        document.getElementById('viewPatientName').textContent = prescription.first_name + ' ' + prescription.last_name;
                        document.getElementById('viewPatientAgeGender').textContent = `${prescription.patient_age || 'N/A'} / ${prescription.patient_gender || 'N/A'}`;
                        document.getElementById('viewPatientId').textContent = prescription.customer_code || 'N/A';
                        document.getElementById('viewPatientWeightHeight').textContent = `${prescription.patient_weight || 'N/A'} kg / ${prescription.patient_height || 'N/A'} cm`;
                        document.getElementById('viewDoctorName').textContent = prescription.doctor_name || 'N/A';
                        document.getElementById('viewDoctorSpecialty').textContent = 'General Practitioner'; // You might want to add specialty to users table
                        document.getElementById('viewDiagnosis').textContent = prescription.diagnosis || 'N/A';
                        document.getElementById('viewPrescriptionType').textContent = prescription.prescription_type?.charAt(0).toUpperCase() + prescription.prescription_type?.slice(1) || 'N/A';
                        document.getElementById('viewUrgencyLevel').textContent = prescription.urgency_level?.charAt(0).toUpperCase() + prescription.urgency_level?.slice(1) || 'N/A';
                        document.getElementById('viewValidFrom').textContent = formatDate(prescription.valid_from);
                        document.getElementById('viewValidUntil').textContent = formatDate(prescription.valid_until);
                        document.getElementById('viewAllergies').textContent = prescription.allergies || 'None reported';
                        document.getElementById('viewRefillsAllowed').textContent = prescription.refills_allowed || 0;
                        document.getElementById('viewRefillsUsed').textContent = prescription.refills_used || 0;
                        document.getElementById('viewStatus').textContent = prescription.status?.charAt(0).toUpperCase() + prescription.status?.slice(1) || 'N/A';
                        document.getElementById('viewInstructions').textContent = prescription.instructions || 'N/A';
                        document.getElementById('viewNotes').textContent = prescription.notes || 'No additional notes';
                        
                        // Status badge
                        const statusBadge = document.getElementById('viewStatusBadge');
                        statusBadge.textContent = prescription.status?.charAt(0).toUpperCase() + prescription.status?.slice(1) || 'N/A';
                        statusBadge.className = 'status-badge-large';
                        statusBadge.classList.add(`status-${prescription.status}`);
                        
                        // Medications table
                        const medicationsTable = document.getElementById('viewMedicationsTable');
                        medicationsTable.innerHTML = '';
                        
                        if (result.medications && result.medications.length > 0) {
                            result.medications.forEach(med => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${med.medicine_name || med.full_name || 'N/A'}</td>
                                    <td>${med.dosage || 'N/A'}</td>
                                    <td>${med.frequency || 'N/A'}</td>
                                    <td>${med.duration || 'N/A'} days</td>
                                    <td>${med.instructions || 'N/A'}</td>
                                `;
                                medicationsTable.appendChild(row);
                            });
                        } else {
                            medicationsTable.innerHTML = '<tr><td colspan="5">No medications found</td></tr>';
                        }
                        
                        // Dispensing history
                        const dispensingHistory = document.getElementById('viewDispensingHistory');
                        dispensingHistory.innerHTML = '';
                        
                        if (result.dispensing_history && result.dispensing_history.length > 0) {
                            result.dispensing_history.forEach(dispense => {
                                const row = document.createElement('tr');
                                const statusClass = dispense.status === 'dispensed' ? 'status-completed' : 'status-pending';
                                row.innerHTML = `
                                    <td>${formatDate(dispense.dispensing_date)}</td>
                                    <td>${dispense.pharmacist_name || dispense.pharmacist_id || 'N/A'}</td>
                                    <td><span class="${statusClass}">${dispense.status?.charAt(0).toUpperCase() + dispense.status?.slice(1) || 'N/A'}</span></td>
                                    <td>${dispense.dispensing_notes || 'No notes'}</td>
                                `;
                                dispensingHistory.appendChild(row);
                            });
                        } else {
                            dispensingHistory.innerHTML = '<tr><td colspan="4">No dispensing history available</td></tr>';
                        }
                        
                        viewPrescriptionModal.classList.add('active');
                        
                    } catch (error) {
                        showError('Failed to load prescription details: ' + error.message);
                    } finally {
                        hideLoading();
                    }
                }

                function closePrescriptionModal() {
                    prescriptionModal.classList.remove('active');
                }

                function closeViewModal() {
                    viewPrescriptionModal.classList.remove('active');
                }

                // Save Prescription
                async function savePrescription() {
                    const form = document.getElementById('prescriptionForm');
                    const prescriptionId = document.getElementById('prescriptionId').value;
                    const isEdit = !!prescriptionId;
                    const saveBtn = document.getElementById('savePrescriptionBtn');
                    
                    if (!form.checkValidity()) {
                        showError('Please fill in all required fields.');
                        return;
                    }
                    
                    const medications = getMedicationsData();
                    if (medications.length === 0) {
                        showError('Please add at least one medication.');
                        return;
                    }
                    
                    // Validate medication dosages
                    for (const med of medications) {
                        if (!med.medicine_id && !med.medicine_name) {
                            showError('Please select a medicine or enter a medicine name for all medications.');
                            return;
                        }
                    }
                    
                    const prescriptionData = {
                        customer_id: document.getElementById('customerId').value,
                        doctor_name: document.getElementById('doctorName').value || 
                                   document.getElementById('doctorId').selectedOptions[0]?.textContent.split(' (')[0],
                        diagnosis: document.getElementById('diagnosis').value,
                        prescription_date: document.getElementById('prescriptionDate').value,
                        prescription_type: document.getElementById('prescriptionType').value,
                        urgency_level: document.getElementById('urgencyLevel').value,
                        valid_from: document.getElementById('validFrom').value,
                        valid_until: document.getElementById('validUntil').value,
                        refills_allowed: document.getElementById('refillsAllowed').value || 0,
                        patient_weight: document.getElementById('patientWeight').value || null,
                        patient_height: document.getElementById('patientHeight').value || null,
                        symptoms: document.getElementById('symptoms').value || '',
                        instructions: document.getElementById('instructions').value,
                        notes: document.getElementById('notes').value || '',
                        allergies_warning: document.getElementById('allergiesWarning').value || '',
                        status: document.getElementById('status').value,
                        medications: medications
                    };
                    
                    if (isEdit) {
                        prescriptionData.prescription_id = prescriptionId;
                    }
                    
                    try {
                        showLoading(saveBtn);
                        
                        const action = isEdit ? 'update_prescription' : 'add_prescription';
                        const result = await apiCall(action, prescriptionData);
                        
                        showSuccess(result.message);
                        closePrescriptionModal();
                        loadPrescriptions();
                        loadStatistics();
                        
                    } catch (error) {
                        showError('Failed to save prescription: ' + error.message);
                    } finally {
                        hideLoading(saveBtn);
                    }
                }

                // Delete Functions
                function deletePrescription(id) {
                    const prescription = prescriptions.find(p => p.prescription_id === id);
                    if (!prescription) return;

                    prescriptionToDelete = id;
                    document.getElementById('deletePrescriptionId').textContent = prescription.prescription_number;
                    deletePrescriptionModal.classList.add('active');
                }

                function closeDeleteModal() {
                    deletePrescriptionModal.classList.remove('active');
                    prescriptionToDelete = null;
                }

                async function confirmDelete() {
                    if (!prescriptionToDelete) return;
                    
                    const confirmBtn = document.getElementById('confirmDeleteBtn');
                    
                    try {
                        showLoading(confirmBtn);
                        
                        await apiCall('delete_prescription', { prescription_id: prescriptionToDelete });
                        
                        showSuccess('Prescription deleted successfully!');
                        closeDeleteModal();
                        loadPrescriptions();
                        loadStatistics();
                        
                    } catch (error) {
                        showError('Failed to delete prescription: ' + error.message);
                    } finally {
                        hideLoading(confirmBtn);
                    }
                }

                // Dispensing Functions
                function openDispenseModal(id) {
                    const prescription = prescriptions.find(p => p.prescription_id === id);
                    if (!prescription) return;

                    prescriptionToDispense = id;
                    document.getElementById('dispensePrescriptionId').textContent = prescription.prescription_number;
                    document.getElementById('dispensePatientName').textContent = prescription.patient_name;
                    
                    dispensePrescriptionModal.classList.add('active');
                }

                function closeDispenseModal() {
                    dispensePrescriptionModal.classList.remove('active');
                    prescriptionToDispense = null;
                    document.getElementById('dispensingNotes').value = '';
                }

                async function confirmDispense() {
                    if (!prescriptionToDispense) return;
                    
                    const confirmBtn = document.getElementById('confirmDispenseBtn');
                    const dispensingNotes = document.getElementById('dispensingNotes').value;
                    
                    try {
                        showLoading(confirmBtn);
                        
                        await apiCall('dispense_prescription', {
                            prescription_id: prescriptionToDispense,
                            dispense_items: [],
                            dispensing_notes: dispensingNotes
                        });
                        
                        showSuccess('Prescription dispensed successfully!');
                        closeDispenseModal();
                        
                        // Reload the viewed prescription if it's open
                        if (prescriptionToView && prescriptionToView.prescription_id === prescriptionToDispense) {
                            await viewPrescription(prescriptionToDispense);
                        }
                        
                        loadPrescriptions();
                        loadStatistics();
                        
                    } catch (error) {
                        showError('Failed to dispense prescription: ' + error.message);
                    } finally {
                        hideLoading(confirmBtn);
                    }
                }

                // Table Rendering - UPDATED to remove Clear Filters button from empty state
                function renderPrescriptionsTable() {
                    if (!tableBody) return;
                    
                    if (filteredPrescriptions.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <ion-icon name="document-text-outline"></ion-icon>
                                    <h3>No prescriptions found</h3>
                                    <p>Try adjusting your search or filters</p>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    filteredPrescriptions.forEach(prescription => {
                        const statusClass = `status-${prescription.status}`;
                        const typeClass = `type-${prescription.prescription_type}`;
                        const urgencyClass = `urgency-${prescription.urgency_level}`;
                        
                        // Format medications preview
                        const medicationsHtml = prescription.medications_preview?.map(med => 
                            `<div class="medication-item">
                                <span class="medication-name">${med.medicine_name || 'N/A'}</span>
                                <span class="medication-dosage">${med.dosage || 'N/A'}</span>
                            </div>`
                        ).join('') || '<div class="medication-item">No medications</div>';
                        
                        html += `
                            <tr>
                                <td>${prescription.sn}</td>
                                <td>
                                    <div class="prescription-info">
                                        <div class="prescription-number">${prescription.prescription_number}</div>
                                        <div class="prescription-date">${prescription.prescription_date_formatted || formatDate(prescription.prescription_date)}</div>
                                        <div><span class="prescription-type ${typeClass}">${prescription.prescription_type_text}</span></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info-cell">
                                        <div class="customer-avatar">
                                            ${(prescription.patient_name || '').split(' ').map(n => n[0]).join('').toUpperCase() || '?'}
                                        </div>
                                        <div class="customer-details">
                                            <div class="customer-name">${prescription.patient_name || 'N/A'}</div>
                                            <div class="customer-id">${prescription.customer_code || 'N/A'}</div>
                                            <div class="customer-id">${prescription.patient_age || 'N/A'}y, ${prescription.patient_gender || 'N/A'}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="doctor-info">
                                        <div class="doctor-name">${prescription.doctor_name || 'N/A'}</div>
                                        <div class="doctor-specialty">General Practitioner</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="medications-list">
                                        ${medicationsHtml}
                                    </div>
                                </td>
                                <td><span class="urgency-badge ${urgencyClass}">${prescription.urgency_level_text}</span></td>
                                <td>${prescription.valid_until_formatted || formatDate(prescription.valid_until)}</td>
                                <td><span class="prescription-status ${statusClass}">${prescription.status_text}</span></td>
                                <td>
                                    <div class="prescription-actions">
                                        <button class="action-icon view" title="View Details" onclick="viewPrescription(${prescription.prescription_id})">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon edit" title="Edit" onclick="editPrescription(${prescription.prescription_id})">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon delete" title="Delete" onclick="deletePrescription(${prescription.prescription_id})">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon print" title="Print" onclick="printSinglePrescription(${prescription.prescription_id})">
                                            <ion-icon name="print-outline"></ion-icon>
                                        </button>
                                        ${prescription.status === 'pending' ? 
                                            `<button class="action-icon dispense" title="Dispense" onclick="openDispenseModal(${prescription.prescription_id})">
                                                <ion-icon name="cart-outline"></ion-icon>
                                            </button>` : ''
                                        }
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = html;
                }

                // Pagination
                function updatePaginationInfo(total, page, pages) {
                    const start = (currentPage - 1) * itemsPerPage + 1;
                    const end = Math.min(currentPage * itemsPerPage, total);
                    
                    document.getElementById('paginationInfo').textContent = 
                        `Showing ${start} to ${end} of ${total} entries`;
                    
                    // Update pagination controls
                    const paginationControls = document.getElementById('paginationControls');
                    paginationControls.innerHTML = '';
                    
                    // Previous button
                    const prevBtn = document.createElement('button');
                    prevBtn.className = 'pagination-btn';
                    prevBtn.innerHTML = '<ion-icon name="chevron-back-outline"></ion-icon>';
                    prevBtn.onclick = () => changePage('prev');
                    prevBtn.disabled = currentPage === 1;
                    paginationControls.appendChild(prevBtn);
                    
                    // Page numbers
                    const maxPagesToShow = 5;
                    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
                    let endPage = Math.min(pages, startPage + maxPagesToShow - 1);
                    
                    if (endPage - startPage + 1 < maxPagesToShow) {
                        startPage = Math.max(1, endPage - maxPagesToShow + 1);
                    }
                    
                    if (startPage > 1) {
                        const firstBtn = document.createElement('button');
                        firstBtn.className = 'pagination-btn';
                        firstBtn.textContent = '1';
                        firstBtn.onclick = () => changePage(1);
                        paginationControls.appendChild(firstBtn);
                        
                        if (startPage > 2) {
                            const ellipsis = document.createElement('span');
                            ellipsis.textContent = '...';
                            ellipsis.style.padding = '8px 15px';
                            paginationControls.appendChild(ellipsis);
                        }
                    }
                    
                    for (let i = startPage; i <= endPage; i++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.className = 'pagination-btn';
                        if (i === currentPage) pageBtn.classList.add('active');
                        pageBtn.textContent = i;
                        pageBtn.onclick = () => changePage(i);
                        paginationControls.appendChild(pageBtn);
                    }
                    
                    if (endPage < pages) {
                        if (endPage < pages - 1) {
                            const ellipsis = document.createElement('span');
                            ellipsis.textContent = '...';
                            ellipsis.style.padding = '8px 15px';
                            paginationControls.appendChild(ellipsis);
                        }
                        
                        const lastBtn = document.createElement('button');
                        lastBtn.className = 'pagination-btn';
                        lastBtn.textContent = pages;
                        lastBtn.onclick = () => changePage(pages);
                        paginationControls.appendChild(lastBtn);
                    }
                    
                    // Next button
                    const nextBtn = document.createElement('button');
                    nextBtn.className = 'pagination-btn';
                    nextBtn.innerHTML = '<ion-icon name="chevron-forward-outline"></ion-icon>';
                    nextBtn.onclick = () => changePage('next');
                    nextBtn.disabled = currentPage === pages;
                    paginationControls.appendChild(nextBtn);
                }

                function changePage(page) {
                    if (page === 'prev' && currentPage > 1) {
                        currentPage--;
                    } else if (page === 'next' && currentPage < Math.ceil(filteredPrescriptions.length / itemsPerPage)) {
                        currentPage++;
                    } else if (typeof page === 'number') {
                        currentPage = page;
                    }
                    
                    loadPrescriptions();
                }

                // Filter Functions - UPDATED
                function filterPrescriptions() {
                    currentPage = 1;
                    loadPrescriptions();
                }

                function resetFilters() {
                    document.getElementById('searchInput').value = '';
                    document.getElementById('statusFilter').value = '';
                    document.getElementById('typeFilter').value = '';
                    document.getElementById('urgencyFilter').value = '';
                    document.getElementById('sortFilter').value = 'date_desc';
                    currentPage = 1;
                    loadPrescriptions();
                }

                function applyFilters() {
                    currentPage = 1;
                    loadPrescriptions();
                }

                // Refresh Function
                function refreshPrescriptions() {
                    const refreshBtn = document.getElementById('refreshBtn');
                    showLoading(refreshBtn);
                    
                    Promise.all([
                        loadPrescriptions(),
                        loadStatistics(),
                        loadDoctors(),
                        loadCustomers(),
                        loadMedicines()
                    ]).then(() => {
                        hideLoading(refreshBtn);
                        showSuccess('Data refreshed successfully!');
                    }).catch(error => {
                        hideLoading(refreshBtn);
                        showError('Failed to refresh data: ' + error.message);
                    });
                }

                // Print Function
                async function printSinglePrescription(id) {
                    try {
                        showLoading();
                        
                        const result = await apiCall('print_prescription', { prescription_id: id });
                        const prescription = result.print_data.prescription;
                        const medications = result.print_data.medications;
                        
                        const printContent = `
                            <html>
                            <head>
                                <title>Prescription ${prescription.prescription_number}</title>
                                <style>
                                    body { font-family: Arial, sans-serif; margin: 40px; }
                                    .prescription-header { border-bottom: 2px solid #2a5c8b; padding-bottom: 20px; margin-bottom: 30px; }
                                    .header-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
                                    .pharmacy-name { font-size: 24px; font-weight: bold; color: #2a5c8b; }
                                    .prescription-number { font-size: 18px; font-weight: bold; color: #333; }
                                    .section { margin-bottom: 25px; }
                                    .section-title { background: #f5f7fa; padding: 8px 12px; font-weight: bold; color: #2a5c8b; margin-bottom: 10px; }
                                    .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
                                    .info-item { margin-bottom: 8px; }
                                    .info-label { font-weight: bold; color: #666; }
                                    .medications-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                                    .medications-table th { background: #f5f7fa; padding: 10px; text-align: left; border: 1px solid #ddd; }
                                    .medications-table td { padding: 10px; border: 1px solid #ddd; }
                                    .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
                                    .signature-area { margin-top: 40px; display: flex; justify-content: space-between; }
                                    .signature-box { width: 200px; border-top: 1px solid #333; padding-top: 5px; text-align: center; }
                                    @media print {
                                        body { margin: 20px; }
                                        .no-print { display: none !important; }
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="prescription-header">
                                    <div class="header-row">
                                        <div class="pharmacy-name">${prescription.branch_name || 'PharmaCare Pharmacy'}</div>
                                        <div class="prescription-number">Prescription: ${prescription.prescription_number}</div>
                                    </div>
                                    <div>Date: ${formatDate(prescription.prescription_date)}</div>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Patient Information</div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Name:</div>
                                            <div>${prescription.patient_name}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Patient ID:</div>
                                            <div>${prescription.customer_code}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Age/Gender:</div>
                                            <div>${prescription.patient_age || 'N/A'} years / ${prescription.patient_gender || 'N/A'}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Allergies:</div>
                                            <div>${prescription.allergies || 'None reported'}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Doctor Information</div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Doctor:</div>
                                            <div>${prescription.doctor_name}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Diagnosis:</div>
                                            <div>${prescription.diagnosis}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Prescribed Medications</div>
                                    <table class="medications-table">
                                        <thead>
                                            <tr>
                                                <th>Medication</th>
                                                <th>Dosage</th>
                                                <th>Frequency</th>
                                                <th>Duration</th>
                                                <th>Instructions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${medications.map(med => `
                                                <tr>
                                                    <td>${med.medicine_name || med.full_name || 'N/A'}</td>
                                                    <td>${med.dosage || 'N/A'}</td>
                                                    <td>${med.frequency || 'N/A'}</td>
                                                    <td>${med.duration || 'N/A'} days</td>
                                                    <td>${med.instructions || 'N/A'}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Instructions for Use</div>
                                    <div style="padding: 10px; background: #f9f9f9; border-radius: 5px;">
                                        ${prescription.instructions || 'N/A'}
                                    </div>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Prescription Details</div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Valid From:</div>
                                            <div>${formatDate(prescription.valid_from)}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Valid Until:</div>
                                            <div>${formatDate(prescription.valid_until)}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Refills Allowed:</div>
                                            <div>${prescription.refills_allowed || 0}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Status:</div>
                                            <div>${prescription.status?.charAt(0).toUpperCase() + prescription.status?.slice(1) || 'N/A'}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="signature-area">
                                    <div class="signature-box">
                                        <div>Doctor's Signature</div>
                                        <div style="margin-top: 40px;">${prescription.doctor_name}</div>
                                    </div>
                                    <div class="signature-box">
                                        <div>Pharmacist's Signature</div>
                                        <div style="margin-top: 40px;">${result.print_data.pharmacist_name || '___________________'}</div>
                                    </div>
                                </div>
                                
                                <div class="footer">
                                    <p><strong>${prescription.branch_name || 'PharmaCare Pharmacy'}</strong> - ${prescription.branch_address || 'M1 Road, Balaka, Malawi'} | Phone: ${prescription.branch_phone || '+265 123 456 789'}</p>
                                    <p>This is a computer-generated prescription. Valid only with original doctor's signature.</p>
                                    <p>Printed: ${result.print_data.print_date}</p>
                                </div>
                                
                                <div style="margin-top: 20px; text-align: center;" class="no-print">
                                    <button onclick="window.print()" style="padding: 10px 20px; background: #2a5c8b; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                        Print Prescription
                                    </button>
                                    <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                                        Close
                                    </button>
                                </div>
                            </body>
                            </html>
                        `;

                        const printWindow = window.open('', '_blank');
                        printWindow.document.write(printContent);
                        printWindow.document.close();
                        
                    } catch (error) {
                        showError('Failed to print prescription: ' + error.message);
                    } finally {
                        hideLoading();
                    }
                }

                function printPrescription() {
                    if (!prescriptionToView) return;
                    printSinglePrescription(prescriptionToView.prescription_id);
                }

                function dispensePrescription() {
                    if (!prescriptionToView) return;
                    openDispenseModal(prescriptionToView.prescription_id);
                }

                function editCurrentPrescription() {
                    if (!prescriptionToView) return;
                    
                    closeViewModal();
                    setTimeout(() => {
                        editPrescription(prescriptionToView.prescription_id);
                    }, 300);
                }

                // Initialize
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('Initializing prescriptions management system...');
                    
                    // Set default valid until date (30 days from today)
                    const validUntilInput = document.getElementById('validUntil');
                    if (validUntilInput) {
                        const nextMonth = new Date();
                        nextMonth.setMonth(nextMonth.getMonth() + 1);
                        validUntilInput.value = nextMonth.toISOString().split('T')[0];
                    }
                    
                    // Add Enter key support for search
                    document.getElementById('searchInput').addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            applyFilters();
                        }
                    });
                    
                    // Initialize data
                    Promise.all([
                        loadPrescriptions(),
                        loadStatistics(),
                        loadDoctors(),
                        loadCustomers(),
                        loadMedicines()
                    ]).then(() => {
                        console.log('All data loaded successfully');
                    }).catch(error => {
                        console.error('Failed to load initial data:', error);
                        showError('Failed to load initial data: ' + error.message);
                    });
                    
                    // Add event listeners for modals
                    document.querySelectorAll('.modal-overlay').forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('active');
                            }
                        });
                    });
                    
                    // Add event listener for customer search
                    const customerSelect = document.getElementById('customerId');
                    if (customerSelect) {
                        customerSelect.addEventListener('input', async function(e) {
                            if (e.target.value.length >= 2) {
                                try {
                                    const result = await apiCall('get_customers_autocomplete', { search: e.target.value });
                                    customersList = result.customers || [];
                                    
                                    customerSelect.innerHTML = '<option value="">-- select patient --</option>';
                                    customersList.forEach(customer => {
                                        const option = document.createElement('option');
                                        option.value = customer.id;
                                        option.textContent = `${customer.name} (${customer.code}) - ${customer.phone || 'No phone'}`;
                                        option.setAttribute('data-customer', JSON.stringify(customer));
                                        customerSelect.appendChild(option);
                                    });
                                } catch (error) {
                                    console.error('Failed to search customers:', error);
                                }
                            }
                        });
                    }
                    
                    // Add keyboard shortcuts
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            closePrescriptionModal();
                            closeViewModal();
                            closeDeleteModal();
                            closeDispenseModal();
                        }
                        if (e.ctrlKey && e.key === 'f') {
                            e.preventDefault();
                            document.getElementById('searchInput').focus();
                        }
                        if (e.ctrlKey && e.key === 'n') {
                            e.preventDefault();
                            openAddPrescriptionModal();
                        }
                        if (e.ctrlKey && e.key === 'r') {
                            e.preventDefault();
                            refreshPrescriptions();
                        }
                    });
                    
                    console.log('Prescriptions management system initialized');
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
// prescriptions.php - Frontend with Backend Integration

// Start session and check login
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get current user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? 'Unknown User';
$user_role = $_SESSION['user_role'] ?? 'staff';
$current_branch_id = $_SESSION['branch_id'] ?? null;

// Include configuration file
require_once '../config/db.php';
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
                /* ================== Prescriptions Management Styles ============== */
                .prescriptions-management-section {
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

                .action-btn.info {
                    background: var(--info);
                    color: white;
                }

                .action-btn.info:hover {
                    background: #2980b9;
                    transform: translateY(-2px);
                }

                /* Statistics Cards */
                .prescriptions-stats {
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

                /* Prescriptions Table */
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

                /* ================== RESPONSIVE TABLE STYLES ================== */
                .table-responsive {
                    width: 100%;
                    overflow-x: auto;
                    position: relative;
                }

                .table-responsive::-webkit-scrollbar {
                    height: 8px;
                }

                .table-responsive::-webkit-scrollbar-track {
                    background: var(--light-gray);
                    border-radius: 4px;
                }

                .table-responsive::-webkit-scrollbar-thumb {
                    background: var(--primary);
                    border-radius: 4px;
                }

                .table-responsive::-webkit-scrollbar-thumb:hover {
                    background: var(--secondary);
                }

                .prescriptions-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 1200px;
                }

                .prescriptions-table thead {
                    background: var(--light-gray);
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }

                .prescriptions-table th {
                    padding: 18px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    border-bottom: 2px solid var(--gray);
                    white-space: nowrap;
                }

                .prescriptions-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.3s ease;
                }

                .prescriptions-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .prescriptions-table td {
                    padding: 15px 20px;
                    color: var(--black);
                    font-size: 0.95rem;
                    vertical-align: middle;
                    min-width: 120px;
                }

                /* Prescription Status Badges */
                .prescription-status {
                    display: inline-block;
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    white-space: nowrap;
                }

                .status-active {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .status-completed {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
                }

                .status-pending {
                    background: rgba(243, 156, 18, 0.1);
                    color: var(--warning);
                }

                .status-expired {
                    background: rgba(231, 76, 60, 0.1);
                    color: var(--danger);
                }

                .status-cancelled {
                    background: rgba(149, 165, 166, 0.1);
                    color: #7f8c8d;
                }

                /* Urgency Badges */
                .urgency-badge {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    white-space: nowrap;
                }

                .urgency-low {
                    background: rgba(46, 204, 113, 0.1);
                    color: #2ecc71;
                }

                .urgency-medium {
                    background: rgba(241, 196, 15, 0.1);
                    color: #f1c40f;
                }

                .urgency-high {
                    background: rgba(231, 76, 60, 0.1);
                    color: #e74c3c;
                }

                /* Prescription Type Badges */
                .prescription-type {
                    display: inline-block;
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    white-space: nowrap;
                }

                .type-new {
                    background: rgba(26, 188, 156, 0.1);
                    color: var(--accent);
                }

                .type-refill {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .type-renewal {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
                }

                .type-emergency {
                    background: rgba(231, 76, 60, 0.1);
                    color: #e74c3c;
                }

                /* Customer Info */
                .customer-info-cell {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    min-width: 200px;
                }

                .customer-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 2px solid var(--gray);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 1rem;
                    color: white;
                    background: linear-gradient(135deg, var(--primary), var(--secondary));
                    flex-shrink: 0;
                }

                .customer-avatar img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .customer-details {
                    display: flex;
                    flex-direction: column;
                    min-width: 150px;
                }

                .customer-name {
                    font-weight: 600;
                    color: var(--black);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .customer-id {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    white-space: nowrap;
                }

                /* Prescription Info */
                .prescription-info {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    min-width: 150px;
                }

                .prescription-number {
                    font-weight: 600;
                    color: var(--primary);
                    white-space: nowrap;
                }

                .prescription-date {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    white-space: nowrap;
                }

                /* Doctor Info */
                .doctor-info {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    min-width: 180px;
                }

                .doctor-name {
                    font-weight: 500;
                    color: var(--black);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .doctor-specialty {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    white-space: nowrap;
                }

                /* Medications List */
                .medications-list {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    min-width: 200px;
                }

                .medication-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 5px 0;
                    border-bottom: 1px solid var(--light-gray);
                }

                .medication-item:last-child {
                    border-bottom: none;
                }

                .medication-name {
                    font-weight: 500;
                    color: var(--black);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    flex: 1;
                }

                .medication-dosage {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    white-space: nowrap;
                    margin-left: 10px;
                }

                /* Actions */
                .prescription-actions {
                    display: flex;
                    gap: 8px;
                    min-width: 180px;
                    flex-wrap: wrap;
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

                .action-icon.print {
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
                }

                .action-icon.print:hover {
                    background: #3498db;
                    color: white;
                    transform: translateY(-2px);
                }

                .action-icon.dispense {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .action-icon.dispense:hover {
                    background: var(--success);
                    color: white;
                    transform: translateY(-2px);
                }

                /* Loading States */
                .loading {
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    border: 3px solid rgba(255,255,255,.3);
                    border-radius: 50%;
                    border-top-color: white;
                    animation: spin 1s ease-in-out infinite;
                }

                @keyframes spin {
                    to { transform: rotate(360deg); }
                }

                .loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 3000;
                    display: none;
                }

                .loading-spinner {
                    width: 50px;
                    height: 50px;
                    border: 5px solid rgba(255,255,255,.3);
                    border-radius: 50%;
                    border-top-color: var(--primary);
                    animation: spin 1s ease-in-out infinite;
                }

                .skeleton {
                    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                    background-size: 200% 100%;
                    animation: loading 1.5s infinite;
                }

                @keyframes loading {
                    0% { background-position: 200% 0; }
                    100% { background-position: -200% 0; }
                }

                .skeleton-text {
                    height: 12px;
                    width: 100%;
                    margin-bottom: 8px;
                    border-radius: 4px;
                }

                .skeleton-button {
                    height: 35px;
                    width: 35px;
                    border-radius: 8px;
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
                    max-width: 900px;
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

                /* Date Inputs */
                .date-input {
                    position: relative;
                }

                .date-input ion-icon {
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: var(--dark-gray);
                }

                /* Medications Section */
                .medications-section {
                    grid-column: 1 / -1;
                    background: var(--light-gray);
                    padding: 20px;
                    border-radius: 10px;
                    margin-top: 10px;
                }

                .medications-section h4 {
                    color: var(--primary);
                    margin: 0 0 15px 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .add-medication-btn {
                    padding: 8px 15px;
                    background: var(--primary);
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                .medications-list-form {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .medication-row {
                    display: grid;
                    grid-template-columns: 2fr 1fr 1fr 1fr auto;
                    gap: 10px;
                    align-items: center;
                    padding: 15px;
                    background: white;
                    border-radius: 8px;
                    border: 1px solid var(--gray);
                }

                .remove-medication {
                    background: none;
                    border: none;
                    color: var(--danger);
                    cursor: pointer;
                    font-size: 1.2rem;
                    padding: 5px;
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

                /* View Prescription Modal */
                .view-details-modal .modal-content {
                    max-width: 800px;
                }

                .prescription-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 1px solid var(--gray);
                }

                .prescription-info-header {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .prescription-id {
                    font-size: 1.8rem;
                    font-weight: 700;
                    color: var(--primary);
                }

                .prescription-date {
                    color: var(--dark-gray);
                }

                .status-badge-large {
                    padding: 8px 20px;
                    border-radius: 20px;
                    font-weight: 600;
                    font-size: 1rem;
                }

                .prescription-details-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                    margin-bottom: 30px;
                }

                .detail-card {
                    background: var(--light-gray);
                    padding: 20px;
                    border-radius: 10px;
                    border-left: 4px solid var(--primary);
                }

                .detail-card h5 {
                    margin: 0 0 15px 0;
                    color: var(--primary);
                }

                .detail-item {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }

                .detail-label {
                    color: var(--dark-gray);
                }

                .detail-value {
                    font-weight: 500;
                    color: var(--black);
                }

                /* Medications Table in View Modal */
                .medications-table-view {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }

                .medications-table-view th {
                    background: var(--light-gray);
                    padding: 12px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                }

                .medications-table-view td {
                    padding: 12px;
                    border-bottom: 1px solid var(--gray);
                }

                /* Instructions Section */
                .instructions-section {
                    background: var(--light-gray);
                    padding: 20px;
                    border-radius: 10px;
                    margin-top: 20px;
                }

                .instructions-section h5 {
                    margin: 0 0 15px 0;
                    color: var(--primary);
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

                /* Error States */
                .error-message {
                    color: var(--danger);
                    background: rgba(231, 76, 60, 0.1);
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    display: none;
                }

                .error-message.show {
                    display: block;
                }

                /* Success Message */
                .success-message {
                    color: var(--success);
                    background: rgba(39, 174, 96, 0.1);
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    display: none;
                }

                .success-message.show {
                    display: block;
                }

                /* Responsive Design */
                @media (max-width: 1200px) {
                    .modal-form {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 992px) {
                    .prescriptions-stats {
                        grid-template-columns: repeat(3, 1fr);
                    }
                    
                    .prescription-actions {
                        min-width: 150px;
                    }
                }

                @media (max-width: 768px) {
                    .prescriptions-management-section {
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

                    .prescriptions-stats {
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

                    .prescriptions-table {
                        min-width: 900px;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }

                    .prescription-actions {
                        flex-direction: row;
                        flex-wrap: wrap;
                        min-width: 120px;
                    }

                    .action-icon {
                        width: 30px;
                        height: 30px;
                        font-size: 1rem;
                    }

                    .prescription-details-grid {
                        grid-template-columns: 1fr;
                    }

                    .medication-row {
                        grid-template-columns: 1fr;
                        gap: 10px;
                    }

                    .remove-medication {
                        align-self: flex-end;
                    }
                    
                    .customer-info-cell {
                        min-width: 150px;
                    }
                }

                @media (max-width: 480px) {
                    .prescriptions-stats {
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
                    
                    .table-header h2 {
                        font-size: 1.2rem;
                    }
                    
                    .prescription-actions {
                        gap: 5px;
                    }
                }
            </style>

            <!-- Loading Overlay -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
            </div>

            <!-- Error Message -->
            <div class="error-message" id="errorMessage"></div>

            <!-- Success Message -->
            <div class="success-message" id="successMessage"></div>

            <!-- ================== Prescriptions Management Content ============== -->
            <div class="prescriptions-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Prescriptions Management</h1>
                        <p>Manage and dispense medical prescriptions for patients</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshPrescriptions()" id="refreshBtn">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <button class="action-btn primary" onclick="openAddPrescriptionModal()" id="addPrescriptionBtn">
                            <ion-icon name="medical-outline"></ion-icon>
                            Create New Prescription
                        </button>
                    </div>
                </div>

                <!-- Prescriptions Statistics -->
                <div class="prescriptions-stats" id="prescriptionsStats">
                    <!-- Statistics will be loaded here -->
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search by patient name, prescription number, or doctor..." 
                               onkeyup="filterPrescriptions()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterPrescriptions()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Prescription Type</label>
                            <select id="typeFilter" onchange="filterPrescriptions()">
                                <option value="">All Types</option>
                                <option value="new">New Prescription</option>
                                <option value="refill">Refill</option>
                                <option value="renewal">Renewal</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Urgency</label>
                            <select id="urgencyFilter" onchange="filterPrescriptions()">
                                <option value="">All Urgency</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select id="sortFilter" onchange="filterPrescriptions()">
                                <option value="date_desc">Date (Newest First)</option>
                                <option value="date_asc">Date (Oldest First)</option>
                                <option value="urgency_high">Urgency (High to Low)</option>
                                <option value="patient_asc">Patient Name (A-Z)</option>
                                <option value="patient_desc">Patient Name (Z-A)</option>
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

                <!-- Prescriptions Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Medical Prescriptions</h2>
                    </div>
                    
                    <!-- Responsive Table Container with Horizontal Scroll -->
                    <div class="table-responsive">
                        <table class="prescriptions-table">
                            <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>PRESCRIPTION DETAILS</th>
                                    <th>PATIENT INFORMATION</th>
                                    <th>DOCTOR INFORMATION</th>
                                    <th>MEDICATIONS</th>
                                    <th>URGENCY</th>
                                    <th>VALID UNTIL</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="prescriptionsTableBody">
                                <!-- Prescriptions will be populated here by JavaScript -->
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 50px;">
                                        <div class="loading">Loading prescriptions...</div>
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
                            <button class="pagination-btn" onclick="changePage('prev')" disabled>
                                <ion-icon name="chevron-back-outline"></ion-icon>
                            </button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn" onclick="changePage('next')">
                                <ion-icon name="chevron-forward-outline"></ion-icon>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Prescription Modal -->
            <div class="modal-overlay" id="prescriptionModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Create New Prescription</h3>
                        <button class="modal-close" onclick="closePrescriptionModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="prescriptionForm" class="modal-form">
                            <input type="hidden" id="prescriptionId">
                            
                            <!-- Prescription Information -->
                            <div class="form-group">
                                <label for="prescriptionNumber">Prescription Number</label>
                                <input type="text" id="prescriptionNumber" placeholder="Auto-generated" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="prescriptionDate" class="required">Prescription Date</label>
                                <div class="date-input">
                                    <input type="date" id="prescriptionDate" value="<?php echo date('Y-m-d'); ?>" required>
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="prescriptionType" class="required">Prescription Type</label>
                                <select id="prescriptionType" required>
                                    <option value="">-- select --</option>
                                    <option value="new">New Prescription</option>
                                    <option value="refill">Refill</option>
                                    <option value="renewal">Renewal</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="urgencyLevel" class="required">Urgency Level</label>
                                <select id="urgencyLevel" required>
                                    <option value="">-- select --</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            
                            <!-- Patient Information -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Patient Information</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="customerId" class="required">Patient</label>
                                <select id="customerId" required onchange="loadCustomerInfo()">
                                    <option value="">-- select patient --</option>
                                    <!-- Patients will be loaded dynamically -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="patientAge">Age</label>
                                <input type="text" id="patientAge" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="patientGender">Gender</label>
                                <input type="text" id="patientGender" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="patientWeight">Weight (kg)</label>
                                <input type="number" id="patientWeight" step="0.1" placeholder="e.g., 70.5">
                            </div>
                            
                            <div class="form-group">
                                <label for="patientHeight">Height (cm)</label>
                                <input type="number" id="patientHeight" placeholder="e.g., 175">
                            </div>
                            
                            <!-- Doctor Information -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Doctor Information</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctorId" class="required">Doctor</label>
                                <select id="doctorId" required onchange="loadDoctorInfo()">
                                    <option value="">-- select doctor --</option>
                                    <!-- Doctors will be loaded dynamically -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctorName">Doctor Name</label>
                                <input type="text" id="doctorName" readonly>
                            </div>
                            
                            <!-- Diagnosis -->
                            <div class="form-group full-width">
                                <label for="diagnosis" class="required">Diagnosis</label>
                                <textarea id="diagnosis" placeholder="Enter primary diagnosis..." required></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="symptoms">Symptoms & Clinical Notes</label>
                                <textarea id="symptoms" placeholder="Describe symptoms and clinical findings..."></textarea>
                            </div>
                            
                            <!-- Medications Section -->
                            <div class="medications-section">
                                <h4>
                                    Medications
                                    <button type="button" class="add-medication-btn" onclick="addMedicationRow()">
                                        <ion-icon name="add-outline"></ion-icon>
                                        Add Medication
                                    </button>
                                </h4>
                                
                                <div class="medications-list-form" id="medicationsList">
                                    <!-- Medication rows will be added here dynamically -->
                                    <div class="medication-row">
                                        <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px;">
                                            <select class="medicine-select" onchange="loadMedicineInfo(this)" required>
                                                <option value="">-- Select Medicine --</option>
                                            </select>
                                            <input type="text" placeholder="Dosage (e.g., 500mg)" class="medication-dosage" required>
                                            <input type="text" placeholder="Frequency (e.g., 3 times daily)" class="medication-frequency" required>
                                            <input type="number" placeholder="Duration (days)" class="medication-duration" min="1" required>
                                            <button type="button" class="remove-medication" onclick="removeMedicationRow(this)">
                                                <ion-icon name="close-outline"></ion-icon>
                                            </button>
                                        </div>
                                        <div style="grid-column: 1 / -1;">
                                            <textarea class="medication-instructions" placeholder="Instructions for this medication..." rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prescription Details -->
                            <div class="form-group full-width">
                                <label for="instructions" class="required">Instructions for Use</label>
                                <textarea id="instructions" placeholder="Enter detailed instructions for the patient..." required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="validFrom" class="required">Valid From</label>
                                <div class="date-input">
                                    <input type="date" id="validFrom" value="<?php echo date('Y-m-d'); ?>" required>
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="validUntil" class="required">Valid Until</label>
                                <div class="date-input">
                                    <input type="date" id="validUntil" required>
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="refillsAllowed">Refills Allowed</label>
                                <input type="number" id="refillsAllowed" min="0" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status">
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="form-group full-width">
                                <label for="notes">Additional Notes</label>
                                <textarea id="notes" placeholder="Any additional information about this prescription..."></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="allergiesWarning">Allergies Warning</label>
                                <textarea id="allergiesWarning" placeholder="List any allergy warnings or contraindications..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closePrescriptionModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="savePrescription()" id="savePrescriptionBtn">
                            Save Prescription
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Prescription Details Modal -->
            <div class="modal-overlay view-details-modal" id="viewPrescriptionModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="viewModalTitle">Prescription Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="prescription-header">
                            <div class="prescription-info-header">
                                <div class="prescription-id" id="viewPrescriptionId">Loading...</div>
                                <div class="prescription-date" id="viewPrescriptionDate">Date: Loading...</div>
                            </div>
                            <div class="status-badge-large" id="viewStatusBadge">Loading...</div>
                        </div>
                        
                        <div class="prescription-details-grid">
                            <!-- Patient Information -->
                            <div class="detail-card">
                                <h5>Patient Information</h5>
                                <div class="detail-item">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value" id="viewPatientName">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Age/Gender:</span>
                                    <span class="detail-value" id="viewPatientAgeGender">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Patient ID:</span>
                                    <span class="detail-value" id="viewPatientId">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Weight/Height:</span>
                                    <span class="detail-value" id="viewPatientWeightHeight">Loading...</span>
                                </div>
                            </div>
                            
                            <!-- Doctor Information -->
                            <div class="detail-card">
                                <h5>Doctor Information</h5>
                                <div class="detail-item">
                                    <span class="detail-label">Doctor:</span>
                                    <span class="detail-value" id="viewDoctorName">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Specialty:</span>
                                    <span class="detail-value" id="viewDoctorSpecialty">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Diagnosis:</span>
                                    <span class="detail-value" id="viewDiagnosis">Loading...</span>
                                </div>
                            </div>
                            
                            <!-- Prescription Details -->
                            <div class="detail-card">
                                <h5>Prescription Details</h5>
                                <div class="detail-item">
                                    <span class="detail-label">Type:</span>
                                    <span class="detail-value" id="viewPrescriptionType">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Urgency:</span>
                                    <span class="detail-value" id="viewUrgencyLevel">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Valid From:</span>
                                    <span class="detail-value" id="viewValidFrom">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Valid Until:</span>
                                    <span class="detail-value" id="viewValidUntil">Loading...</span>
                                </div>
                            </div>
                            
                            <!-- Medical Information -->
                            <div class="detail-card">
                                <h5>Medical Information</h5>
                                <div class="detail-item">
                                    <span class="detail-label">Allergies:</span>
                                    <span class="detail-value" id="viewAllergies">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Refills Allowed:</span>
                                    <span class="detail-value" id="viewRefillsAllowed">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Refills Used:</span>
                                    <span class="detail-value" id="viewRefillsUsed">Loading...</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value" id="viewStatus">Loading...</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medications List -->
                        <h5 style="margin-bottom: 15px; color: var(--primary);">Prescribed Medications</h5>
                        <table class="medications-table-view">
                            <thead>
                                <tr>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Instructions</th>
                                </tr>
                            </thead>
                            <tbody id="viewMedicationsTable">
                                <tr><td colspan="5">Loading medications...</td></tr>
                            </tbody>
                        </table>
                        
                        <!-- Instructions -->
                        <div class="instructions-section">
                            <h5>Instructions for Use</h5>
                            <div id="viewInstructions">Loading...</div>
                        </div>
                        
                        <!-- Additional Notes -->
                        <div class="form-group full-width" style="margin-top: 20px;">
                            <label>Additional Notes:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px;" id="viewNotes">
                                Loading...
                            </div>
                        </div>
                        
                        <!-- Dispensing History -->
                        <div style="margin-top: 30px;">
                            <h5 style="margin-bottom: 15px; color: var(--primary);">Dispensing History</h5>
                            <table class="medications-table-view">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Pharmacist</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="viewDispensingHistory">
                                    <tr><td colspan="4">Loading dispensing history...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                        <button type="button" class="action-btn primary" onclick="editCurrentPrescription()">
                            <ion-icon name="create-outline"></ion-icon>
                            Edit Prescription
                        </button>
                        <button type="button" class="action-btn print" onclick="printPrescription()">
                            <ion-icon name="print-outline"></ion-icon>
                            Print
                        </button>
                        <button type="button" class="action-btn success" onclick="dispensePrescription()">
                            <ion-icon name="cart-outline"></ion-icon>
                            Dispense
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="deletePrescriptionModal">
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
                        <h4>Delete Prescription</h4>
                        <p>Are you sure you want to delete prescription <strong id="deletePrescriptionId">[Loading...]</strong>? This action cannot be undone and all prescription data will be permanently removed.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="action-btn danger" onclick="confirmDelete()" id="confirmDeleteBtn">
                                <ion-icon name="trash-outline"></ion-icon>
                                Delete Prescription
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dispense Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="dispensePrescriptionModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Confirm Dispensing</h3>
                        <button class="modal-close" onclick="closeDispenseModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="confirmation-body">
                        <div class="confirmation-icon success">
                            <ion-icon name="cart-outline"></ion-icon>
                        </div>
                        <h4>Dispense Prescription</h4>
                        <p>Are you ready to dispense prescription <strong id="dispensePrescriptionId">[Loading...]</strong> for <strong id="dispensePatientName">[Loading...]</strong>?</p>
                        
                        <div class="form-group" style="margin: 20px 0;">
                            <label for="dispensingNotes">Dispensing Notes (Optional)</label>
                            <textarea id="dispensingNotes" placeholder="Enter any notes about the dispensing process..."></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDispenseModal()">
                                Cancel
                            </button>
                            <button class="action-btn success" onclick="confirmDispense()" id="confirmDispenseBtn">
                                <ion-icon name="checkmark-outline"></ion-icon>
                                Confirm Dispensing
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Include jQuery -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <script>
                // Global Variables
                let prescriptions = [];
                let filteredPrescriptions = [];
                let currentPage = 1;
                const itemsPerPage = 10;
                let prescriptionToDelete = null;
                let prescriptionToView = null;
                let prescriptionToDispense = null;
                let doctorsList = [];
                let customersList = [];
                let medicinesList = [];

                // DOM Elements
                const tableBody = document.getElementById('prescriptionsTableBody');
                const prescriptionModal = document.getElementById('prescriptionModal');
                const viewPrescriptionModal = document.getElementById('viewPrescriptionModal');
                const deletePrescriptionModal = document.getElementById('deletePrescriptionModal');
                const dispensePrescriptionModal = document.getElementById('dispensePrescriptionModal');
                const loadingOverlay = document.getElementById('loadingOverlay');
                const errorMessage = document.getElementById('errorMessage');
                const successMessage = document.getElementById('successMessage');
                const prescriptionsStats = document.getElementById('prescriptionsStats');

                // API Configuration
                const API_BASE = 'prescriptions_backend.php';

                // Utility Functions
                function showLoading(button = null) {
                    loadingOverlay.style.display = 'flex';
                    if (button) {
                        const originalText = button.innerHTML;
                        button.setAttribute('data-original-text', originalText);
                        button.innerHTML = '<span class="loading"></span>';
                        button.disabled = true;
                    }
                }

                function hideLoading(button = null) {
                    loadingOverlay.style.display = 'none';
                    if (button && button.hasAttribute('data-original-text')) {
                        button.innerHTML = button.getAttribute('data-original-text');
                        button.disabled = false;
                    }
                }

                function showError(message) {
                    errorMessage.textContent = message;
                    errorMessage.classList.add('show');
                    
                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        errorMessage.classList.remove('show');
                    }, 5000);
                }

                function showSuccess(message) {
                    successMessage.textContent = message;
                    successMessage.classList.add('show');
                    
                    // Auto-hide after 3 seconds
                    setTimeout(() => {
                        successMessage.classList.remove('show');
                    }, 3000);
                }

                function clearMessages() {
                    errorMessage.classList.remove('show');
                    successMessage.classList.remove('show');
                }

                function formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }

                // API Functions
                async function apiCall(action, data = {}) {
                    const formData = new FormData();
                    formData.append('action', action);
                    
                    for (const key in data) {
                        if (Array.isArray(data[key])) {
                            // Handle arrays (like medications)
                            data[key].forEach((item, index) => {
                                for (const subKey in item) {
                                    formData.append(`${key}[${index}][${subKey}]`, item[subKey]);
                                }
                            });
                        } else {
                            formData.append(key, data[key]);
                        }
                    }

                    try {
                        const response = await fetch(API_BASE, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();
                        
                        if (!result.success) {
                            throw new Error(result.message || 'An error occurred');
                        }
                        
                        return result;
                    } catch (error) {
                        console.error('API Error:', error);
                        throw error;
                    }
                }

                // Data Loading Functions
                async function loadPrescriptions() {
                    showLoading();
                    clearMessages();
                    
                    try {
                        const filters = {
                            search: document.getElementById('searchInput').value,
                            status: document.getElementById('statusFilter').value,
                            type: document.getElementById('typeFilter').value,
                            urgency: document.getElementById('urgencyFilter').value,
                            sort: document.getElementById('sortFilter').value,
                            page: currentPage,
                            limit: itemsPerPage
                        };

                        const result = await apiCall('get_prescriptions', filters);
                        
                        prescriptions = result.data;
                        filteredPrescriptions = result.data;
                        
                        renderPrescriptionsTable();
                        updatePaginationInfo(result.total, result.page, result.pages);
                        
                    } catch (error) {
                        showError('Failed to load prescriptions: ' + error.message);
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <ion-icon name="alert-circle-outline"></ion-icon>
                                    <h3>Error Loading Data</h3>
                                    <p>${error.message}</p>
                                    <button class="action-btn secondary" onclick="loadPrescriptions()" style="margin-top: 15px;">
                                        <ion-icon name="refresh-outline"></ion-icon>
                                        Try Again
                                    </button>
                                </td>
                            </tr>
                        `;
                    } finally {
                        hideLoading();
                    }
                }

                async function loadStatistics() {
                    try {
                        const result = await apiCall('get_prescription_stats');
                        
                        const stats = result.stats;
                        const statsHtml = `
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="document-text-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.total_prescriptions}</h3>
                                    <p>Total Prescriptions</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="time-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.pending_prescriptions}</h3>
                                    <p>Pending Dispensing</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.today_prescriptions}</h3>
                                    <p>Today's Prescriptions</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="warning-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.expiring_soon}</h3>
                                    <p>Expiring Soon</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="cash-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>MK${Math.round(stats.today_revenue / 1000)}K</h3>
                                    <p>Revenue Today</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <ion-icon name="alert-circle-outline"></ion-icon>
                                </div>
                                <div class="stat-content">
                                    <h3>${stats.refill_prescriptions}</h3>
                                    <p>Refill Requests</p>
                                </div>
                            </div>
                        `;
                        
                        prescriptionsStats.innerHTML = statsHtml;
                        
                    } catch (error) {
                        console.error('Failed to load statistics:', error);
                    }
                }

                async function loadDoctors() {
                    try {
                        const result = await apiCall('get_doctors');
                        doctorsList = result.doctors;
                        
                        const doctorSelect = document.getElementById('doctorId');
                        doctorSelect.innerHTML = '<option value="">-- select doctor --</option>';
                        
                        doctorsList.forEach(doctor => {
                            const option = document.createElement('option');
                            option.value = doctor.user_id;
                            option.textContent = `${doctor.full_name} (${doctor.phone || 'No phone'})`;
                            doctorSelect.appendChild(option);
                        });
                        
                    } catch (error) {
                        console.error('Failed to load doctors:', error);
                    }
                }

                async function loadCustomers() {
                    try {
                        // This would need a separate API endpoint for customers
                        // For now, we'll use a generic search
                        const customerSelect = document.getElementById('customerId');
                        customerSelect.innerHTML = '<option value="">-- select patient --</option>';
                        
                        // We'll load customers on demand when the select is clicked
                        customerSelect.onfocus = async function() {
                            if (customersList.length === 0) {
                                try {
                                    const result = await apiCall('get_customers_autocomplete', { search: '' });
                                    customersList = result.customers || [];
                                    
                                    customerSelect.innerHTML = '<option value="">-- select patient --</option>';
                                    customersList.forEach(customer => {
                                        const option = document.createElement('option');
                                        option.value = customer.id;
                                        option.textContent = `${customer.name} (${customer.code}) - ${customer.phone || 'No phone'}`;
                                        option.setAttribute('data-customer', JSON.stringify(customer));
                                        customerSelect.appendChild(option);
                                    });
                                } catch (error) {
                                    console.error('Failed to load customers:', error);
                                }
                            }
                        };
                        
                    } catch (error) {
                        console.error('Failed to load customers:', error);
                    }
                }

                async function loadMedicines() {
                    try {
                        const result = await apiCall('get_medicines_autocomplete', { search: '' });
                        medicinesList = result.medicines || [];
                        
                        // Update all medicine selects
                        document.querySelectorAll('.medicine-select').forEach(select => {
                            if (select.options.length <= 1) {
                                select.innerHTML = '<option value="">-- Select Medicine --</option>';
                                medicinesList.forEach(medicine => {
                                    const option = document.createElement('option');
                                    option.value = medicine.id;
                                    option.textContent = `${medicine.name} (${medicine.strength || 'N/A'}) - Stock: ${medicine.current_stock}`;
                                    option.setAttribute('data-medicine', JSON.stringify(medicine));
                                    select.appendChild(option);
                                });
                            }
                        });
                        
                    } catch (error) {
                        console.error('Failed to load medicines:', error);
                    }
                }

                // Form Functions
                function loadCustomerInfo() {
                    const customerId = document.getElementById('customerId').value;
                    const selectedOption = document.getElementById('customerId').selectedOptions[0];
                    
                    if (selectedOption && selectedOption.getAttribute('data-customer')) {
                        const customer = JSON.parse(selectedOption.getAttribute('data-customer'));
                        document.getElementById('patientAge').value = customer.age || '';
                        document.getElementById('patientGender').value = customer.gender || '';
                        document.getElementById('allergiesWarning').value = customer.allergies || '';
                    }
                }

                function loadDoctorInfo() {
                    const doctorId = document.getElementById('doctorId').value;
                    const selectedOption = document.getElementById('doctorId').selectedOptions[0];
                    
                    if (selectedOption) {
                        document.getElementById('doctorName').value = selectedOption.textContent.split(' (')[0];
                    }
                }

                function loadMedicineInfo(selectElement) {
                    const selectedOption = selectElement.selectedOptions[0];
                    if (selectedOption && selectedOption.getAttribute('data-medicine')) {
                        const medicine = JSON.parse(selectedOption.getAttribute('data-medicine'));
                        const row = selectElement.closest('.medication-row');
                        const dosageInput = row.querySelector('.medication-dosage');
                        
                        // Auto-fill dosage if not already set
                        if (!dosageInput.value && medicine.strength) {
                            dosageInput.value = medicine.strength;
                        }
                        
                        // Check stock
                        if (medicine.current_stock <= 0) {
                            showError(`${medicine.name} is out of stock!`);
                        }
                    }
                }

                // Medication Management
                function addMedicationRow() {
                    const medicationsList = document.getElementById('medicationsList');
                    const medicationRow = document.createElement('div');
                    medicationRow.className = 'medication-row';
                    medicationRow.innerHTML = `
                        <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px;">
                            <select class="medicine-select" onchange="loadMedicineInfo(this)" required>
                                <option value="">-- Select Medicine --</option>
                            </select>
                            <input type="text" placeholder="Dosage (e.g., 500mg)" class="medication-dosage" required>
                            <input type="text" placeholder="Frequency (e.g., 3 times daily)" class="medication-frequency" required>
                            <input type="number" placeholder="Duration (days)" class="medication-duration" min="1" required>
                            <button type="button" class="remove-medication" onclick="removeMedicationRow(this)">
                                <ion-icon name="close-outline"></ion-icon>
                            </button>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <textarea class="medication-instructions" placeholder="Instructions for this medication..." rows="2"></textarea>
                        </div>
                    `;
                    
                    medicationsList.appendChild(medicationRow);
                    
                    // Load medicines into the new select
                    const select = medicationRow.querySelector('.medicine-select');
                    select.innerHTML = '<option value="">-- Select Medicine --</option>';
                    medicinesList.forEach(medicine => {
                        const option = document.createElement('option');
                        option.value = medicine.id;
                        option.textContent = `${medicine.name} (${medicine.strength || 'N/A'}) - Stock: ${medicine.current_stock}`;
                        option.setAttribute('data-medicine', JSON.stringify(medicine));
                        select.appendChild(option);
                    });
                }

                function removeMedicationRow(button) {
                    const row = button.closest('.medication-row');
                    if (row && document.querySelectorAll('.medication-row').length > 1) {
                        row.remove();
                    }
                }

                function getMedicationsData() {
                    const medications = [];
                    document.querySelectorAll('.medication-row').forEach(row => {
                        const medicineSelect = row.querySelector('.medicine-select');
                        const medicineId = medicineSelect.value;
                        const selectedOption = medicineSelect.selectedOptions[0];
                        const medicineName = selectedOption ? selectedOption.textContent.split(' (')[0] : '';
                        
                        medications.push({
                            medicine_id: medicineId || null,
                            medicine_name: medicineName || row.querySelector('.medication-dosage').previousSibling?.value || '',
                            dosage: row.querySelector('.medication-dosage').value,
                            frequency: row.querySelector('.medication-frequency').value,
                            duration: row.querySelector('.medication-duration').value,
                            instructions: row.querySelector('.medication-instructions')?.value || '',
                            quantity: 1 // Default quantity
                        });
                    });
                    return medications;
                }

                // Modal Functions
                async function openAddPrescriptionModal() {
                    clearMessages();
                    document.getElementById('modalTitle').textContent = 'Create New Prescription';
                    document.getElementById('prescriptionForm').reset();
                    document.getElementById('prescriptionId').value = '';
                    
                    // Set default dates
                    const today = new Date().toISOString().split('T')[0];
                    const nextMonth = new Date();
                    nextMonth.setMonth(nextMonth.getMonth() + 1);
                    const nextMonthStr = nextMonth.toISOString().split('T')[0];
                    
                    document.getElementById('prescriptionDate').value = today;
                    document.getElementById('validFrom').value = today;
                    document.getElementById('validUntil').value = nextMonthStr;
                    
                    // Clear patient info
                    document.getElementById('patientAge').value = '';
                    document.getElementById('patientGender').value = '';
                    document.getElementById('patientWeight').value = '';
                    document.getElementById('patientHeight').value = '';
                    document.getElementById('doctorName').value = '';
                    
                    // Reset medications list
                    const medicationsList = document.getElementById('medicationsList');
                    medicationsList.innerHTML = `
                        <div class="medication-row">
                            <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px;">
                                <select class="medicine-select" onchange="loadMedicineInfo(this)" required>
                                    <option value="">-- Select Medicine --</option>
                                </select>
                                <input type="text" placeholder="Dosage (e.g., 500mg)" class="medication-dosage" required>
                                <input type="text" placeholder="Frequency (e.g., 3 times daily)" class="medication-frequency" required>
                                <input type="number" placeholder="Duration (days)" class="medication-duration" min="1" required>
                                <button type="button" class="remove-medication" onclick="removeMedicationRow(this)">
                                    <ion-icon name="close-outline"></ion-icon>
                                </button>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <textarea class="medication-instructions" placeholder="Instructions for this medication..." rows="2"></textarea>
                            </div>
                        </div>
                    `;
                    
                    // Load medicines into the select
                    await loadMedicines();
                    
                    prescriptionModal.classList.add('active');
                }

                async function editPrescription(id) {
                    clearMessages();
                    showLoading();
                    
                    try {
                        const result = await apiCall('get_prescription', { prescription_id: id });
                        const prescription = result.data;
                        
                        document.getElementById('modalTitle').textContent = 'Edit Prescription';
                        document.getElementById('prescriptionId').value = prescription.prescription_id;
                        document.getElementById('prescriptionNumber').value = prescription.prescription_number;
                        document.getElementById('prescriptionDate').value = prescription.prescription_date;
                        document.getElementById('prescriptionType').value = prescription.prescription_type;
                        document.getElementById('urgencyLevel').value = prescription.urgency_level;
                        document.getElementById('customerId').value = prescription.customer_id;
                        document.getElementById('patientAge').value = prescription.patient_age || '';
                        document.getElementById('patientGender').value = prescription.patient_gender || '';
                        document.getElementById('patientWeight').value = prescription.patient_weight || '';
                        document.getElementById('patientHeight').value = prescription.patient_height || '';
                        document.getElementById('doctorId').value = prescription.created_by || '';
                        document.getElementById('doctorName').value = prescription.doctor_name || '';
                        document.getElementById('diagnosis').value = prescription.diagnosis || '';
                        document.getElementById('symptoms').value = prescription.symptoms || '';
                        document.getElementById('instructions').value = prescription.instructions || '';
                        document.getElementById('validFrom').value = prescription.valid_from || '';
                        document.getElementById('validUntil').value = prescription.valid_until || '';
                        document.getElementById('refillsAllowed').value = prescription.refills_allowed || 0;
                        document.getElementById('status').value = prescription.status || 'active';
                        document.getElementById('notes').value = prescription.notes || '';
                        document.getElementById('allergiesWarning').value = prescription.allergies_warning || '';
                        
                        // Load medications
                        const medicationsList = document.getElementById('medicationsList');
                        medicationsList.innerHTML = '';
                        
                        if (result.medications && result.medications.length > 0) {
                            result.medications.forEach(med => {
                                const medicationRow = document.createElement('div');
                                medicationRow.className = 'medication-row';
                                medicationRow.innerHTML = `
                                    <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px;">
                                        <select class="medicine-select" onchange="loadMedicineInfo(this)" required>
                                            <option value="">-- Select Medicine --</option>
                                        </select>
                                        <input type="text" placeholder="Dosage" class="medication-dosage" value="${med.dosage || ''}" required>
                                        <input type="text" placeholder="Frequency" class="medication-frequency" value="${med.frequency || ''}" required>
                                        <input type="number" placeholder="Duration" class="medication-duration" value="${med.duration || ''}" min="1" required>
                                        <button type="button" class="remove-medication" onclick="removeMedicationRow(this)">
                                            <ion-icon name="close-outline"></ion-icon>
                                        </button>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <textarea class="medication-instructions" placeholder="Instructions for this medication..." rows="2">${med.instructions || ''}</textarea>
                                    </div>
                                `;
                                medicationsList.appendChild(medicationRow);
                                
                                // Load medicines and select the right one
                                const select = medicationRow.querySelector('.medicine-select');
                                select.innerHTML = '<option value="">-- Select Medicine --</option>';
                                medicinesList.forEach(medicine => {
                                    const option = document.createElement('option');
                                    option.value = medicine.id;
                                    option.textContent = `${medicine.name} (${medicine.strength || 'N/A'}) - Stock: ${medicine.current_stock}`;
                                    option.setAttribute('data-medicine', JSON.stringify(medicine));
                                    select.appendChild(option);
                                });
                                
                                // Select the medicine if medicine_id exists
                                if (med.medicine_id) {
                                    setTimeout(() => {
                                        select.value = med.medicine_id;
                                    }, 100);
                                }
                            });
                        } else {
                            addMedicationRow();
                        }
                        
                        prescriptionModal.classList.add('active');
                        
                    } catch (error) {
                        showError('Failed to load prescription: ' + error.message);
                    } finally {
                        hideLoading();
                    }
                }

                async function viewPrescription(id) {
                    clearMessages();
                    showLoading();
                    
                    try {
                        const result = await apiCall('get_prescription', { prescription_id: id });
                        const prescription = result.data;
                        
                        prescriptionToView = prescription;
                        
                        // Update view modal
                        document.getElementById('viewModalTitle').textContent = `Prescription ${prescription.prescription_number}`;
                        document.getElementById('viewPrescriptionId').textContent = prescription.prescription_number;
                        document.getElementById('viewPrescriptionDate').textContent = `Date: ${formatDate(prescription.prescription_date)}`;
                        document.getElementById('viewPatientName').textContent = prescription.first_name + ' ' + prescription.last_name;
                        document.getElementById('viewPatientAgeGender').textContent = `${prescription.patient_age || 'N/A'} / ${prescription.patient_gender || 'N/A'}`;
                        document.getElementById('viewPatientId').textContent = prescription.customer_code || 'N/A';
                        document.getElementById('viewPatientWeightHeight').textContent = `${prescription.patient_weight || 'N/A'} kg / ${prescription.patient_height || 'N/A'} cm`;
                        document.getElementById('viewDoctorName').textContent = prescription.doctor_name || 'N/A';
                        document.getElementById('viewDoctorSpecialty').textContent = 'General Practitioner'; // You might want to add specialty to users table
                        document.getElementById('viewDiagnosis').textContent = prescription.diagnosis || 'N/A';
                        document.getElementById('viewPrescriptionType').textContent = prescription.prescription_type?.charAt(0).toUpperCase() + prescription.prescription_type?.slice(1) || 'N/A';
                        document.getElementById('viewUrgencyLevel').textContent = prescription.urgency_level?.charAt(0).toUpperCase() + prescription.urgency_level?.slice(1) || 'N/A';
                        document.getElementById('viewValidFrom').textContent = formatDate(prescription.valid_from);
                        document.getElementById('viewValidUntil').textContent = formatDate(prescription.valid_until);
                        document.getElementById('viewAllergies').textContent = prescription.allergies || 'None reported';
                        document.getElementById('viewRefillsAllowed').textContent = prescription.refills_allowed || 0;
                        document.getElementById('viewRefillsUsed').textContent = prescription.refills_used || 0;
                        document.getElementById('viewStatus').textContent = prescription.status?.charAt(0).toUpperCase() + prescription.status?.slice(1) || 'N/A';
                        document.getElementById('viewInstructions').textContent = prescription.instructions || 'N/A';
                        document.getElementById('viewNotes').textContent = prescription.notes || 'No additional notes';
                        
                        // Status badge
                        const statusBadge = document.getElementById('viewStatusBadge');
                        statusBadge.textContent = prescription.status?.charAt(0).toUpperCase() + prescription.status?.slice(1) || 'N/A';
                        statusBadge.className = 'status-badge-large';
                        statusBadge.classList.add(`status-${prescription.status}`);
                        
                        // Medications table
                        const medicationsTable = document.getElementById('viewMedicationsTable');
                        medicationsTable.innerHTML = '';
                        
                        if (result.medications && result.medications.length > 0) {
                            result.medications.forEach(med => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${med.medicine_name || med.full_name || 'N/A'}</td>
                                    <td>${med.dosage || 'N/A'}</td>
                                    <td>${med.frequency || 'N/A'}</td>
                                    <td>${med.duration || 'N/A'} days</td>
                                    <td>${med.instructions || 'N/A'}</td>
                                `;
                                medicationsTable.appendChild(row);
                            });
                        } else {
                            medicationsTable.innerHTML = '<tr><td colspan="5">No medications found</td></tr>';
                        }
                        
                        // Dispensing history
                        const dispensingHistory = document.getElementById('viewDispensingHistory');
                        dispensingHistory.innerHTML = '';
                        
                        if (result.dispensing_history && result.dispensing_history.length > 0) {
                            result.dispensing_history.forEach(dispense => {
                                const row = document.createElement('tr');
                                const statusClass = dispense.status === 'dispensed' ? 'status-completed' : 'status-pending';
                                row.innerHTML = `
                                    <td>${formatDate(dispense.dispensing_date)}</td>
                                    <td>${dispense.pharmacist_name || dispense.pharmacist_id || 'N/A'}</td>
                                    <td><span class="${statusClass}">${dispense.status?.charAt(0).toUpperCase() + dispense.status?.slice(1) || 'N/A'}</span></td>
                                    <td>${dispense.dispensing_notes || 'No notes'}</td>
                                `;
                                dispensingHistory.appendChild(row);
                            });
                        } else {
                            dispensingHistory.innerHTML = '<tr><td colspan="4">No dispensing history available</td></tr>';
                        }
                        
                        viewPrescriptionModal.classList.add('active');
                        
                    } catch (error) {
                        showError('Failed to load prescription details: ' + error.message);
                    } finally {
                        hideLoading();
                    }
                }

                function closePrescriptionModal() {
                    prescriptionModal.classList.remove('active');
                }

                function closeViewModal() {
                    viewPrescriptionModal.classList.remove('active');
                }

                // Save Prescription
                async function savePrescription() {
                    const form = document.getElementById('prescriptionForm');
                    const prescriptionId = document.getElementById('prescriptionId').value;
                    const isEdit = !!prescriptionId;
                    const saveBtn = document.getElementById('savePrescriptionBtn');
                    
                    if (!form.checkValidity()) {
                        showError('Please fill in all required fields.');
                        return;
                    }
                    
                    const medications = getMedicationsData();
                    if (medications.length === 0) {
                        showError('Please add at least one medication.');
                        return;
                    }
                    
                    // Validate medication dosages
                    for (const med of medications) {
                        if (!med.medicine_id && !med.medicine_name) {
                            showError('Please select a medicine or enter a medicine name for all medications.');
                            return;
                        }
                    }
                    
                    const prescriptionData = {
                        customer_id: document.getElementById('customerId').value,
                        doctor_name: document.getElementById('doctorName').value || 
                                   document.getElementById('doctorId').selectedOptions[0]?.textContent.split(' (')[0],
                        diagnosis: document.getElementById('diagnosis').value,
                        prescription_date: document.getElementById('prescriptionDate').value,
                        prescription_type: document.getElementById('prescriptionType').value,
                        urgency_level: document.getElementById('urgencyLevel').value,
                        valid_from: document.getElementById('validFrom').value,
                        valid_until: document.getElementById('validUntil').value,
                        refills_allowed: document.getElementById('refillsAllowed').value || 0,
                        patient_weight: document.getElementById('patientWeight').value || null,
                        patient_height: document.getElementById('patientHeight').value || null,
                        symptoms: document.getElementById('symptoms').value || '',
                        instructions: document.getElementById('instructions').value,
                        notes: document.getElementById('notes').value || '',
                        allergies_warning: document.getElementById('allergiesWarning').value || '',
                        status: document.getElementById('status').value,
                        medications: medications
                    };
                    
                    if (isEdit) {
                        prescriptionData.prescription_id = prescriptionId;
                    }
                    
                    try {
                        showLoading(saveBtn);
                        
                        const action = isEdit ? 'update_prescription' : 'add_prescription';
                        const result = await apiCall(action, prescriptionData);
                        
                        showSuccess(result.message);
                        closePrescriptionModal();
                        loadPrescriptions();
                        loadStatistics();
                        
                    } catch (error) {
                        showError('Failed to save prescription: ' + error.message);
                    } finally {
                        hideLoading(saveBtn);
                    }
                }

                // Delete Functions
                function deletePrescription(id) {
                    const prescription = prescriptions.find(p => p.prescription_id === id);
                    if (!prescription) return;

                    prescriptionToDelete = id;
                    document.getElementById('deletePrescriptionId').textContent = prescription.prescription_number;
                    deletePrescriptionModal.classList.add('active');
                }

                function closeDeleteModal() {
                    deletePrescriptionModal.classList.remove('active');
                    prescriptionToDelete = null;
                }

                async function confirmDelete() {
                    if (!prescriptionToDelete) return;
                    
                    const confirmBtn = document.getElementById('confirmDeleteBtn');
                    
                    try {
                        showLoading(confirmBtn);
                        
                        await apiCall('delete_prescription', { prescription_id: prescriptionToDelete });
                        
                        showSuccess('Prescription deleted successfully!');
                        closeDeleteModal();
                        loadPrescriptions();
                        loadStatistics();
                        
                    } catch (error) {
                        showError('Failed to delete prescription: ' + error.message);
                    } finally {
                        hideLoading(confirmBtn);
                    }
                }

                // Dispensing Functions
                function openDispenseModal(id) {
                    const prescription = prescriptions.find(p => p.prescription_id === id);
                    if (!prescription) return;

                    prescriptionToDispense = id;
                    document.getElementById('dispensePrescriptionId').textContent = prescription.prescription_number;
                    document.getElementById('dispensePatientName').textContent = prescription.patient_name;
                    
                    dispensePrescriptionModal.classList.add('active');
                }

                function closeDispenseModal() {
                    dispensePrescriptionModal.classList.remove('active');
                    prescriptionToDispense = null;
                    document.getElementById('dispensingNotes').value = '';
                }

                async function confirmDispense() {
                    if (!prescriptionToDispense) return;
                    
                    const confirmBtn = document.getElementById('confirmDispenseBtn');
                    const dispensingNotes = document.getElementById('dispensingNotes').value;
                    
                    try {
                        showLoading(confirmBtn);
                        
                        await apiCall('dispense_prescription', {
                            prescription_id: prescriptionToDispense,
                            dispense_items: [],
                            dispensing_notes: dispensingNotes
                        });
                        
                        showSuccess('Prescription dispensed successfully!');
                        closeDispenseModal();
                        
                        // Reload the viewed prescription if it's open
                        if (prescriptionToView && prescriptionToView.prescription_id === prescriptionToDispense) {
                            await viewPrescription(prescriptionToDispense);
                        }
                        
                        loadPrescriptions();
                        loadStatistics();
                        
                    } catch (error) {
                        showError('Failed to dispense prescription: ' + error.message);
                    } finally {
                        hideLoading(confirmBtn);
                    }
                }

                // Table Rendering - UPDATED to remove Clear Filters button from empty state
                function renderPrescriptionsTable() {
                    if (!tableBody) return;
                    
                    if (filteredPrescriptions.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <ion-icon name="document-text-outline"></ion-icon>
                                    <h3>No prescriptions found</h3>
                                    <p>Try adjusting your search or filters</p>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    filteredPrescriptions.forEach(prescription => {
                        const statusClass = `status-${prescription.status}`;
                        const typeClass = `type-${prescription.prescription_type}`;
                        const urgencyClass = `urgency-${prescription.urgency_level}`;
                        
                        // Format medications preview
                        const medicationsHtml = prescription.medications_preview?.map(med => 
                            `<div class="medication-item">
                                <span class="medication-name">${med.medicine_name || 'N/A'}</span>
                                <span class="medication-dosage">${med.dosage || 'N/A'}</span>
                            </div>`
                        ).join('') || '<div class="medication-item">No medications</div>';
                        
                        html += `
                            <tr>
                                <td>${prescription.sn}</td>
                                <td>
                                    <div class="prescription-info">
                                        <div class="prescription-number">${prescription.prescription_number}</div>
                                        <div class="prescription-date">${prescription.prescription_date_formatted || formatDate(prescription.prescription_date)}</div>
                                        <div><span class="prescription-type ${typeClass}">${prescription.prescription_type_text}</span></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info-cell">
                                        <div class="customer-avatar">
                                            ${(prescription.patient_name || '').split(' ').map(n => n[0]).join('').toUpperCase() || '?'}
                                        </div>
                                        <div class="customer-details">
                                            <div class="customer-name">${prescription.patient_name || 'N/A'}</div>
                                            <div class="customer-id">${prescription.customer_code || 'N/A'}</div>
                                            <div class="customer-id">${prescription.patient_age || 'N/A'}y, ${prescription.patient_gender || 'N/A'}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="doctor-info">
                                        <div class="doctor-name">${prescription.doctor_name || 'N/A'}</div>
                                        <div class="doctor-specialty">General Practitioner</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="medications-list">
                                        ${medicationsHtml}
                                    </div>
                                </td>
                                <td><span class="urgency-badge ${urgencyClass}">${prescription.urgency_level_text}</span></td>
                                <td>${prescription.valid_until_formatted || formatDate(prescription.valid_until)}</td>
                                <td><span class="prescription-status ${statusClass}">${prescription.status_text}</span></td>
                                <td>
                                    <div class="prescription-actions">
                                        <button class="action-icon view" title="View Details" onclick="viewPrescription(${prescription.prescription_id})">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon edit" title="Edit" onclick="editPrescription(${prescription.prescription_id})">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon delete" title="Delete" onclick="deletePrescription(${prescription.prescription_id})">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon print" title="Print" onclick="printSinglePrescription(${prescription.prescription_id})">
                                            <ion-icon name="print-outline"></ion-icon>
                                        </button>
                                        ${prescription.status === 'pending' ? 
                                            `<button class="action-icon dispense" title="Dispense" onclick="openDispenseModal(${prescription.prescription_id})">
                                                <ion-icon name="cart-outline"></ion-icon>
                                            </button>` : ''
                                        }
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = html;
                }

                // Pagination
                function updatePaginationInfo(total, page, pages) {
                    const start = (currentPage - 1) * itemsPerPage + 1;
                    const end = Math.min(currentPage * itemsPerPage, total);
                    
                    document.getElementById('paginationInfo').textContent = 
                        `Showing ${start} to ${end} of ${total} entries`;
                    
                    // Update pagination controls
                    const paginationControls = document.getElementById('paginationControls');
                    paginationControls.innerHTML = '';
                    
                    // Previous button
                    const prevBtn = document.createElement('button');
                    prevBtn.className = 'pagination-btn';
                    prevBtn.innerHTML = '<ion-icon name="chevron-back-outline"></ion-icon>';
                    prevBtn.onclick = () => changePage('prev');
                    prevBtn.disabled = currentPage === 1;
                    paginationControls.appendChild(prevBtn);
                    
                    // Page numbers
                    const maxPagesToShow = 5;
                    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
                    let endPage = Math.min(pages, startPage + maxPagesToShow - 1);
                    
                    if (endPage - startPage + 1 < maxPagesToShow) {
                        startPage = Math.max(1, endPage - maxPagesToShow + 1);
                    }
                    
                    if (startPage > 1) {
                        const firstBtn = document.createElement('button');
                        firstBtn.className = 'pagination-btn';
                        firstBtn.textContent = '1';
                        firstBtn.onclick = () => changePage(1);
                        paginationControls.appendChild(firstBtn);
                        
                        if (startPage > 2) {
                            const ellipsis = document.createElement('span');
                            ellipsis.textContent = '...';
                            ellipsis.style.padding = '8px 15px';
                            paginationControls.appendChild(ellipsis);
                        }
                    }
                    
                    for (let i = startPage; i <= endPage; i++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.className = 'pagination-btn';
                        if (i === currentPage) pageBtn.classList.add('active');
                        pageBtn.textContent = i;
                        pageBtn.onclick = () => changePage(i);
                        paginationControls.appendChild(pageBtn);
                    }
                    
                    if (endPage < pages) {
                        if (endPage < pages - 1) {
                            const ellipsis = document.createElement('span');
                            ellipsis.textContent = '...';
                            ellipsis.style.padding = '8px 15px';
                            paginationControls.appendChild(ellipsis);
                        }
                        
                        const lastBtn = document.createElement('button');
                        lastBtn.className = 'pagination-btn';
                        lastBtn.textContent = pages;
                        lastBtn.onclick = () => changePage(pages);
                        paginationControls.appendChild(lastBtn);
                    }
                    
                    // Next button
                    const nextBtn = document.createElement('button');
                    nextBtn.className = 'pagination-btn';
                    nextBtn.innerHTML = '<ion-icon name="chevron-forward-outline"></ion-icon>';
                    nextBtn.onclick = () => changePage('next');
                    nextBtn.disabled = currentPage === pages;
                    paginationControls.appendChild(nextBtn);
                }

                function changePage(page) {
                    if (page === 'prev' && currentPage > 1) {
                        currentPage--;
                    } else if (page === 'next' && currentPage < Math.ceil(filteredPrescriptions.length / itemsPerPage)) {
                        currentPage++;
                    } else if (typeof page === 'number') {
                        currentPage = page;
                    }
                    
                    loadPrescriptions();
                }

                // Filter Functions - UPDATED
                function filterPrescriptions() {
                    currentPage = 1;
                    loadPrescriptions();
                }

                function resetFilters() {
                    document.getElementById('searchInput').value = '';
                    document.getElementById('statusFilter').value = '';
                    document.getElementById('typeFilter').value = '';
                    document.getElementById('urgencyFilter').value = '';
                    document.getElementById('sortFilter').value = 'date_desc';
                    currentPage = 1;
                    loadPrescriptions();
                }

                function applyFilters() {
                    currentPage = 1;
                    loadPrescriptions();
                }

                // Refresh Function
                function refreshPrescriptions() {
                    const refreshBtn = document.getElementById('refreshBtn');
                    showLoading(refreshBtn);
                    
                    Promise.all([
                        loadPrescriptions(),
                        loadStatistics(),
                        loadDoctors(),
                        loadCustomers(),
                        loadMedicines()
                    ]).then(() => {
                        hideLoading(refreshBtn);
                        showSuccess('Data refreshed successfully!');
                    }).catch(error => {
                        hideLoading(refreshBtn);
                        showError('Failed to refresh data: ' + error.message);
                    });
                }

                // Print Function
                async function printSinglePrescription(id) {
                    try {
                        showLoading();
                        
                        const result = await apiCall('print_prescription', { prescription_id: id });
                        const prescription = result.print_data.prescription;
                        const medications = result.print_data.medications;
                        
                        const printContent = `
                            <html>
                            <head>
                                <title>Prescription ${prescription.prescription_number}</title>
                                <style>
                                    body { font-family: Arial, sans-serif; margin: 40px; }
                                    .prescription-header { border-bottom: 2px solid #2a5c8b; padding-bottom: 20px; margin-bottom: 30px; }
                                    .header-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
                                    .pharmacy-name { font-size: 24px; font-weight: bold; color: #2a5c8b; }
                                    .prescription-number { font-size: 18px; font-weight: bold; color: #333; }
                                    .section { margin-bottom: 25px; }
                                    .section-title { background: #f5f7fa; padding: 8px 12px; font-weight: bold; color: #2a5c8b; margin-bottom: 10px; }
                                    .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
                                    .info-item { margin-bottom: 8px; }
                                    .info-label { font-weight: bold; color: #666; }
                                    .medications-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                                    .medications-table th { background: #f5f7fa; padding: 10px; text-align: left; border: 1px solid #ddd; }
                                    .medications-table td { padding: 10px; border: 1px solid #ddd; }
                                    .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
                                    .signature-area { margin-top: 40px; display: flex; justify-content: space-between; }
                                    .signature-box { width: 200px; border-top: 1px solid #333; padding-top: 5px; text-align: center; }
                                    @media print {
                                        body { margin: 20px; }
                                        .no-print { display: none !important; }
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="prescription-header">
                                    <div class="header-row">
                                        <div class="pharmacy-name">${prescription.branch_name || 'PharmaCare Pharmacy'}</div>
                                        <div class="prescription-number">Prescription: ${prescription.prescription_number}</div>
                                    </div>
                                    <div>Date: ${formatDate(prescription.prescription_date)}</div>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Patient Information</div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Name:</div>
                                            <div>${prescription.patient_name}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Patient ID:</div>
                                            <div>${prescription.customer_code}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Age/Gender:</div>
                                            <div>${prescription.patient_age || 'N/A'} years / ${prescription.patient_gender || 'N/A'}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Allergies:</div>
                                            <div>${prescription.allergies || 'None reported'}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Doctor Information</div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Doctor:</div>
                                            <div>${prescription.doctor_name}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Diagnosis:</div>
                                            <div>${prescription.diagnosis}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Prescribed Medications</div>
                                    <table class="medications-table">
                                        <thead>
                                            <tr>
                                                <th>Medication</th>
                                                <th>Dosage</th>
                                                <th>Frequency</th>
                                                <th>Duration</th>
                                                <th>Instructions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${medications.map(med => `
                                                <tr>
                                                    <td>${med.medicine_name || med.full_name || 'N/A'}</td>
                                                    <td>${med.dosage || 'N/A'}</td>
                                                    <td>${med.frequency || 'N/A'}</td>
                                                    <td>${med.duration || 'N/A'} days</td>
                                                    <td>${med.instructions || 'N/A'}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Instructions for Use</div>
                                    <div style="padding: 10px; background: #f9f9f9; border-radius: 5px;">
                                        ${prescription.instructions || 'N/A'}
                                    </div>
                                </div>
                                
                                <div class="section">
                                    <div class="section-title">Prescription Details</div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Valid From:</div>
                                            <div>${formatDate(prescription.valid_from)}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Valid Until:</div>
                                            <div>${formatDate(prescription.valid_until)}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Refills Allowed:</div>
                                            <div>${prescription.refills_allowed || 0}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Status:</div>
                                            <div>${prescription.status?.charAt(0).toUpperCase() + prescription.status?.slice(1) || 'N/A'}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="signature-area">
                                    <div class="signature-box">
                                        <div>Doctor's Signature</div>
                                        <div style="margin-top: 40px;">${prescription.doctor_name}</div>
                                    </div>
                                    <div class="signature-box">
                                        <div>Pharmacist's Signature</div>
                                        <div style="margin-top: 40px;">${result.print_data.pharmacist_name || '___________________'}</div>
                                    </div>
                                </div>
                                
                                <div class="footer">
                                    <p><strong>${prescription.branch_name || 'PharmaCare Pharmacy'}</strong> - ${prescription.branch_address || 'M1 Road, Balaka, Malawi'} | Phone: ${prescription.branch_phone || '+265 123 456 789'}</p>
                                    <p>This is a computer-generated prescription. Valid only with original doctor's signature.</p>
                                    <p>Printed: ${result.print_data.print_date}</p>
                                </div>
                                
                                <div style="margin-top: 20px; text-align: center;" class="no-print">
                                    <button onclick="window.print()" style="padding: 10px 20px; background: #2a5c8b; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                        Print Prescription
                                    </button>
                                    <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                                        Close
                                    </button>
                                </div>
                            </body>
                            </html>
                        `;

                        const printWindow = window.open('', '_blank');
                        printWindow.document.write(printContent);
                        printWindow.document.close();
                        
                    } catch (error) {
                        showError('Failed to print prescription: ' + error.message);
                    } finally {
                        hideLoading();
                    }
                }

                function printPrescription() {
                    if (!prescriptionToView) return;
                    printSinglePrescription(prescriptionToView.prescription_id);
                }

                function dispensePrescription() {
                    if (!prescriptionToView) return;
                    openDispenseModal(prescriptionToView.prescription_id);
                }

                function editCurrentPrescription() {
                    if (!prescriptionToView) return;
                    
                    closeViewModal();
                    setTimeout(() => {
                        editPrescription(prescriptionToView.prescription_id);
                    }, 300);
                }

                // Initialize
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('Initializing prescriptions management system...');
                    
                    // Set default valid until date (30 days from today)
                    const validUntilInput = document.getElementById('validUntil');
                    if (validUntilInput) {
                        const nextMonth = new Date();
                        nextMonth.setMonth(nextMonth.getMonth() + 1);
                        validUntilInput.value = nextMonth.toISOString().split('T')[0];
                    }
                    
                    // Add Enter key support for search
                    document.getElementById('searchInput').addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            applyFilters();
                        }
                    });
                    
                    // Initialize data
                    Promise.all([
                        loadPrescriptions(),
                        loadStatistics(),
                        loadDoctors(),
                        loadCustomers(),
                        loadMedicines()
                    ]).then(() => {
                        console.log('All data loaded successfully');
                    }).catch(error => {
                        console.error('Failed to load initial data:', error);
                        showError('Failed to load initial data: ' + error.message);
                    });
                    
                    // Add event listeners for modals
                    document.querySelectorAll('.modal-overlay').forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('active');
                            }
                        });
                    });
                    
                    // Add event listener for customer search
                    const customerSelect = document.getElementById('customerId');
                    if (customerSelect) {
                        customerSelect.addEventListener('input', async function(e) {
                            if (e.target.value.length >= 2) {
                                try {
                                    const result = await apiCall('get_customers_autocomplete', { search: e.target.value });
                                    customersList = result.customers || [];
                                    
                                    customerSelect.innerHTML = '<option value="">-- select patient --</option>';
                                    customersList.forEach(customer => {
                                        const option = document.createElement('option');
                                        option.value = customer.id;
                                        option.textContent = `${customer.name} (${customer.code}) - ${customer.phone || 'No phone'}`;
                                        option.setAttribute('data-customer', JSON.stringify(customer));
                                        customerSelect.appendChild(option);
                                    });
                                } catch (error) {
                                    console.error('Failed to search customers:', error);
                                }
                            }
                        });
                    }
                    
                    // Add keyboard shortcuts
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            closePrescriptionModal();
                            closeViewModal();
                            closeDeleteModal();
                            closeDispenseModal();
                        }
                        if (e.ctrlKey && e.key === 'f') {
                            e.preventDefault();
                            document.getElementById('searchInput').focus();
                        }
                        if (e.ctrlKey && e.key === 'n') {
                            e.preventDefault();
                            openAddPrescriptionModal();
                        }
                        if (e.ctrlKey && e.key === 'r') {
                            e.preventDefault();
                            refreshPrescriptions();
                        }
                    });
                    
                    console.log('Prescriptions management system initialized');
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