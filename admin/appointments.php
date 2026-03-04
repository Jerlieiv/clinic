<<<<<<< HEAD
<?php
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
                /* ================== Appointments Management Styles ============== */
                .appointments-management-section {
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
                    background: #3498db;
                    color: white;
                }

                .action-btn.info:hover {
                    background: #2980b9;
                    transform: translateY(-2px);
                }

                .action-btn.prescription {
                    background: #9b59b6;
                    color: white;
                }

                .action-btn.prescription:hover {
                    background: #8e44ad;
                    transform: translateY(-2px);
                }

                /* Statistics Cards */
                .appointments-stats {
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
                    border-color: #3498db;
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
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
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

                /* Table Responsive Container */
                .table-responsive-container {
                    background: var(--white);
                    border-radius: 15px;
                    overflow: hidden;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                    margin-bottom: 30px;
                    overflow-x: auto;
                    position: relative;
                }

                .table-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px 30px;
                    border-bottom: 1px solid var(--gray);
                    position: sticky;
                    left: 0;
                    background: var(--white);
                    z-index: 10;
                    min-width: 1200px; /* Minimum width for header to match table */
                }

                .table-header h2 {
                    color: var(--primary);
                    margin: 0;
                    font-size: 1.5rem;
                    white-space: nowrap;
                }

                .table-actions {
                    display: flex;
                    gap: 15px;
                    white-space: nowrap;
                }

                /* Appointments Table */
                .appointments-table-wrapper {
                    overflow-x: auto;
                    max-height: 600px;
                    overflow-y: auto;
                    position: relative;
                }

                .appointments-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 1200px; /* Minimum width for table */
                }

                .appointments-table thead {
                    background: var(--light-gray);
                    position: sticky;
                    top: 0;
                    z-index: 20;
                }

                .appointments-table th {
                    padding: 18px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    border-bottom: 2px solid var(--gray);
                    white-space: nowrap;
                    min-width: 150px;
                }

                .appointments-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.3s ease;
                }

                .appointments-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .appointments-table td {
                    padding: 15px 20px;
                    color: var(--black);
                    font-size: 0.95rem;
                    vertical-align: middle;
                    white-space: nowrap;
                    min-width: 150px;
                }

                /* Patient Avatar */
                .patient-avatar {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 3px solid var(--gray);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 1.2rem;
                    color: white;
                    background: var(--primary);
                    flex-shrink: 0;
                }

                .patient-avatar img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                /* Status Badges */
                .appointment-status {
                    display: inline-block;
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    white-space: nowrap;
                }

                .status-scheduled {
                    background: rgba(42, 92, 139, 0.1);
                    color: var(--primary);
                }

                .status-confirmed {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .status-completed {
                    background: rgba(26, 188, 156, 0.1);
                    color: var(--accent);
                }

                .status-pending {
                    background: rgba(243, 156, 18, 0.1);
                    color: var(--warning);
                }

                .status-cancelled {
                    background: rgba(231, 76, 60, 0.1);
                    color: var(--danger);
                }

                .status-no-show {
                    background: rgba(149, 165, 166, 0.1);
                    color: #7f8c8d;
                }

                /* Priority Indicators */
                .priority-indicator {
                    display: inline-block;
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    margin-right: 8px;
                    flex-shrink: 0;
                }

                .priority-high {
                    background: var(--danger);
                }

                .priority-medium {
                    background: var(--warning);
                }

                .priority-low {
                    background: var(--success);
                }

                /* Doctor Info */
                .doctor-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    white-space: nowrap;
                }

                .doctor-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 2px solid var(--gray);
                    flex-shrink: 0;
                }

                .doctor-avatar img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                /* Time Slot */
                .time-slot {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    white-space: nowrap;
                }

                .time-slot ion-icon {
                    color: var(--primary);
                    flex-shrink: 0;
                }

                /* Actions */
                .appointment-actions {
                    display: flex;
                    gap: 8px;
                    flex-wrap: nowrap;
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

                .action-icon.view {
                    background: rgba(41, 128, 185, 0.1);
                    color: var(--primary);
                }

                .action-icon.view:hover {
                    background: var(--primary);
                    color: white;
                    transform: translateY(-2px);
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

                .action-icon.checkin {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .action-icon.checkin:hover {
                    background: var(--success);
                    color: white;
                    transform: translateY(-2px);
                }

                .action-icon.message {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .action-icon.message:hover {
                    background: #9b59b6;
                    color: white;
                    transform: translateY(-2px);
                }

                .action-icon.prescription {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .action-icon.prescription:hover {
                    background: #9b59b6;
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
                    position: sticky;
                    left: 0;
                    background: var(--white);
                    min-width: 1200px; /* Minimum width for pagination to match table */
                }

                .pagination-info {
                    color: var(--dark-gray);
                    font-size: 0.9rem;
                    white-space: nowrap;
                }

                .pagination-controls {
                    display: flex;
                    gap: 10px;
                    white-space: nowrap;
                }

                .pagination-btn {
                    padding: 8px 15px;
                    background: var(--white);
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    white-space: nowrap;
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

                /* Scrollbar Styling */
                .appointments-table-wrapper::-webkit-scrollbar {
                    width: 8px;
                    height: 8px;
                }

                .appointments-table-wrapper::-webkit-scrollbar-track {
                    background: var(--light-gray);
                    border-radius: 4px;
                }

                .appointments-table-wrapper::-webkit-scrollbar-thumb {
                    background: var(--gray);
                    border-radius: 4px;
                }

                .appointments-table-wrapper::-webkit-scrollbar-thumb:hover {
                    background: var(--dark-gray);
                }

                /* Mobile Table Styles */
                .mobile-appointments-list {
                    display: none;
                    padding: 15px;
                }

                .mobile-appointment-card {
                    background: var(--white);
                    border-radius: 10px;
                    padding: 20px;
                    margin-bottom: 15px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    border-left: 4px solid var(--primary);
                }

                .mobile-appointment-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 15px;
                }

                .mobile-patient-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .mobile-appointment-details {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                    margin-bottom: 15px;
                }

                .mobile-detail-item {
                    display: flex;
                    flex-direction: column;
                }

                .mobile-detail-label {
                    font-size: 0.8rem;
                    color: var(--dark-gray);
                    margin-bottom: 3px;
                }

                .mobile-detail-value {
                    font-size: 0.9rem;
                    font-weight: 500;
                    color: var(--black);
                }

                .mobile-appointment-actions {
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                    padding-top: 15px;
                    border-top: 1px solid var(--light-gray);
                }

                /* Scroll indicators for mobile */
                .scroll-indicator {
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(0, 0, 0, 0.5);
                    color: white;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.2rem;
                    z-index: 30;
                    display: none;
                }

                .scroll-indicator.left {
                    left: 10px;
                    right: auto;
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
                    position: relative;
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
                    width: 100%;
                    box-sizing: border-box;
                    background-color: white;
                    cursor: pointer;
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

                /* Enhanced Dropdown Styles */
                .enhanced-dropdown {
                    position: relative;
                }

                .enhanced-dropdown select {
                    appearance: none;
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    padding-right: 40px;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
                    background-repeat: no-repeat;
                    background-position: right 12px center;
                    background-size: 16px;
                }

                .enhanced-dropdown select:focus {
                    border-color: var(--primary);
                    box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1);
                }

                .enhanced-dropdown select option {
                    padding: 12px;
                    font-size: 0.95rem;
                }

                .enhanced-dropdown select option:checked {
                    background-color: var(--primary);
                    color: white;
                }

                /* Expandable Dropdown */
                .expandable-dropdown {
                    transition: all 0.3s ease;
                }

                .expandable-dropdown:focus {
                    position: relative;
                    z-index: 100;
                    transform: translateY(-5px);
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                }

                /* Patient Search */
                .patient-search {
                    position: relative;
                }

                .patient-search-results {
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background: white;
                    border: 1px solid var(--gray);
                    border-radius: 8px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                    max-height: 200px;
                    overflow-y: auto;
                    z-index: 1000;
                    display: none;
                }

                .patient-search-result {
                    padding: 12px 15px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .patient-search-result:hover {
                    background: var(--light-gray);
                }

                /* Time Slots */
                .time-slots-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                    gap: 10px;
                    margin-top: 10px;
                }

                .time-slot-btn {
                    padding: 10px;
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    background: white;
                    cursor: pointer;
                    text-align: center;
                    transition: all 0.3s ease;
                }

                .time-slot-btn:hover {
                    border-color: var(--primary);
                    background: rgba(42, 92, 139, 0.05);
                }

                .time-slot-btn.selected {
                    background: var(--primary);
                    color: white;
                    border-color: var(--primary);
                }

                /* Duration Selector */
                .duration-selector {
                    display: flex;
                    gap: 10px;
                }

                .duration-option {
                    padding: 10px 15px;
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .duration-option:hover {
                    border-color: var(--primary);
                }

                .duration-option.selected {
                    background: var(--primary);
                    color: white;
                    border-color: var(--primary);
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

                /* View Details Modal */
                .view-details-modal .modal-content {
                    max-width: 700px;
                }

                .appointment-details {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin-bottom: 30px;
                }

                .patient-info, .appointment-info {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr;
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
                @media (max-width: 1200px) {
                    .modal-form {
                        grid-template-columns: 1fr;
                    }
                    
                    .appointment-details {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 992px) {
                    .appointments-stats {
                        grid-template-columns: repeat(3, 1fr);
                    }
                    
                    .table-header {
                        padding: 15px 20px;
                        flex-wrap: wrap;
                        gap: 10px;
                    }
                    
                    .table-actions {
                        flex-wrap: wrap;
                    }
                }

                @media (max-width: 768px) {
                    .appointments-management-section {
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

                    .appointments-stats {
                        grid-template-columns: repeat(2, 1fr);
                    }

                    .stat-card {
                        flex-direction: column;
                        text-align: center;
                        gap: 15px;
                    }

                    .table-header {
                        padding: 15px;
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 15px;
                    }

                    .table-actions {
                        width: 100%;
                        justify-content: flex-start;
                        flex-wrap: wrap;
                    }

                    /* Hide desktop table, show mobile list */
                    .appointments-table-wrapper {
                        display: none;
                    }
                    
                    .mobile-appointments-list {
                        display: block;
                    }
                    
                    .appointments-table {
                        min-width: 800px;
                    }
                    
                    .pagination {
                        padding: 15px;
                        flex-direction: column;
                        gap: 15px;
                        align-items: flex-start;
                    }

                    .action-btn {
                        padding: 8px 15px;
                        font-size: 0.9rem;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }

                    .appointment-actions {
                        flex-wrap: wrap;
                    }

                    .action-icon {
                        width: 30px;
                        height: 30px;
                        font-size: 1rem;
                    }
                }

                @media (max-width: 576px) {
                    .appointments-stats {
                        grid-template-columns: 1fr;
                    }

                    .filter-grid {
                        grid-template-columns: 1fr;
                    }

                    .filter-actions {
                        flex-direction: column;
                    }
                    
                    .mobile-appointment-details {
                        grid-template-columns: 1fr;
                    }
                    
                    .mobile-appointment-actions {
                        flex-wrap: wrap;
                    }
                    
                    .pagination-controls {
                        flex-wrap: wrap;
                    }
                }
            </style>

            <!-- ================== Appointments Management Content ============== -->
            <div class="appointments-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Appointments Management</h1>
                        <p>Schedule, manage, and track patient appointments</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshAppointments()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <button class="action-btn primary" onclick="openAddAppointmentModal()" id="addAppointmentBtn">
                            <ion-icon name="calendar-outline"></ion-icon>
                            Schedule Appointment
                        </button>
                    </div>
                </div>

                <!-- Appointments Statistics -->
                <div class="appointments-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalAppointments">48</h3>
                            <p>Total Today</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="confirmedAppointments">36</h3>
                            <p>Confirmed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="time-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="pendingAppointments">8</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="close-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="cancelledAppointments">4</h3>
                            <p>Cancelled</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="person-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="waitingPatients">12</h3>
                            <p>Waiting Room</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="medkit-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="availableDoctors">8</h3>
                            <p>Available Doctors</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search by patient name, ID, or doctor..." 
                               onkeyup="filterAppointments()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterAppointments()" class="enhanced-dropdown">
                                <option value="">All Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="no-show">No Show</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Date Range</label>
                            <input type="date" id="dateFrom" onchange="filterAppointments()">
                        </div>
                        
                        <div class="filter-group">
                            <label>To</label>
                            <input type="date" id="dateTo" onchange="filterAppointments()">
                        </div>
                        
                        <div class="filter-group">
                            <label>Doctor</label>
                            <select id="doctorFilter" onchange="filterAppointments()" class="enhanced-dropdown">
                                <option value="">All Doctors</option>
                                <option value="dr_smith">Dr. Smith</option>
                                <option value="dr_johnson">Dr. Johnson</option>
                                <option value="dr_williams">Dr. Williams</option>
                                <option value="dr_brown">Dr. Brown</option>
                                <option value="dr_jones">Dr. Jones</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Department</label>
                            <select id="departmentFilter" onchange="filterAppointments()" class="enhanced-dropdown">
                                <option value="">All Departments</option>
                                <option value="general">General Medicine</option>
                                <option value="pediatrics">Pediatrics</option>
                                <option value="surgery">Surgery</option>
                                <option value="cardiology">Cardiology</option>
                                <option value="orthopedics">Orthopedics</option>
                                <option value="dermatology">Dermatology</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Priority</label>
                            <select id="priorityFilter" onchange="filterAppointments()" class="enhanced-dropdown">
                                <option value="">All Priorities</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
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

                <!-- Appointments Table -->
                <div class="table-responsive-container">
                    <div class="table-header">
                        <h2>Today's Appointments</h2>
                        <!-- Removed table-actions div containing Export CSV, Print, Send Reminders buttons -->
                        <div class="table-actions">
                            <!-- Buttons removed as requested -->
                        </div>
                    </div>
                    
                    <!-- Desktop Table View -->
                    <div class="appointments-table-wrapper">
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>PATIENT</th>
                                    <th>APPOINTMENT DETAILS</th>
                                    <th>DOCTOR</th>
                                    <th>STATUS</th>
                                    <th>TIME SLOT</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="appointmentsTableBody">
                                <!-- Appointments will be populated here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div class="mobile-appointments-list" id="mobileAppointmentsList">
                        <!-- Mobile cards will be populated by JavaScript -->
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="pagination-info" id="paginationInfo">
                            Showing 1 to 10 of 48 entries
                        </div>
                        <div class="pagination-controls">
                            <button class="pagination-btn" onclick="changePage('prev')" disabled>
                                <ion-icon name="chevron-back-outline"></ion-icon>
                            </button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                            <button class="pagination-btn">4</button>
                            <button class="pagination-btn">5</button>
                            <button class="pagination-btn" onclick="changePage('next')">
                                <ion-icon name="chevron-forward-outline"></ion-icon>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Appointment Modal -->
            <div class="modal-overlay" id="appointmentModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Schedule New Appointment</h3>
                        <button class="modal-close" onclick="closeAppointmentModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="appointmentForm" class="modal-form">
                            <input type="hidden" id="appointmentId">
                            
                            <!-- Patient Selection -->
                            <div class="form-group full-width">
                                <label for="patientSearch" class="required">Patient</label>
                                <div class="patient-search">
                                    <input type="text" id="patientSearch" placeholder="Search for patient by name or ID..." required>
                                    <div class="patient-search-results" id="patientResults">
                                        <!-- Search results will appear here -->
                                    </div>
                                </div>
                                <input type="hidden" id="patientId">
                                <input type="hidden" id="patientName">
                            </div>
                            
                            <div class="form-group">
                                <label for="patientAge">Age</label>
                                <input type="number" id="patientAge" placeholder="Auto-filled" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="patientGender">Gender</label>
                                <input type="text" id="patientGender" placeholder="Auto-filled" readonly>
                            </div>
                            
                            <!-- Appointment Details -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Appointment Details</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="appointmentDate" class="required">Date</label>
                                <input type="date" id="appointmentDate" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="appointmentTime" class="required">Time</label>
                                <select id="appointmentTime" class="enhanced-dropdown expandable-dropdown" required>
                                    <option value="">-- select time --</option>
                                    <option value="08:00">08:00 AM</option>
                                    <option value="08:30">08:30 AM</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="09:30">09:30 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="10:30">10:30 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="11:30">11:30 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="12:30">12:30 PM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="13:30">01:30 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                    <option value="14:30">02:30 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="15:30">03:30 PM</option>
                                    <option value="16:00">04:00 PM</option>
                                    <option value="16:30">04:30 PM</option>
                                    <option value="17:00">05:00 PM</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="duration" class="required">Duration</label>
                                <div class="duration-selector">
                                    <span class="duration-option" data-minutes="15">15 min</span>
                                    <span class="duration-option selected" data-minutes="30">30 min</span>
                                    <span class="duration-option" data-minutes="45">45 min</span>
                                    <span class="duration-option" data-minutes="60">60 min</span>
                                </div>
                                <input type="hidden" id="duration" value="30">
                            </div>
                            
                            <div class="form-group">
                                <label for="priority" class="required">Priority</label>
                                <select id="priority" class="enhanced-dropdown expandable-dropdown" required>
                                    <option value="">-- select priority --</option>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            
                            <!-- Doctor -->
                            <div class="form-group">
                                <label for="doctor" class="required">Doctor</label>
                                <input type="text" id="doctor" placeholder="Enter doctor's name" required>
                            </div>
                            
                            <!-- Appointment Type -->
                            <div class="form-group">
                                <label for="appointmentType" class="required">Appointment Type</label>
                                <select id="appointmentType" class="enhanced-dropdown expandable-dropdown" required>
                                    <option value="">-- select appointment type --</option>
                                    <option value="consultation">Consultation</option>
                                    <option value="followup">Follow-up</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="routine">Routine Check-up</option>
                                    <option value="vaccination">Vaccination</option>
                                    <option value="test">Lab Test</option>
                                    <option value="procedure">Procedure</option>
                                    <option value="surgery">Surgery</option>
                                </select>
                            </div>
                            
                            <!-- Reason & Symptoms -->
                            <div class="form-group full-width">
                                <label for="reason" class="required">Reason for Visit</label>
                                <textarea id="reason" placeholder="Describe symptoms or reason for appointment..." required></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="symptoms">Symptoms</label>
                                <textarea id="symptoms" placeholder="List any symptoms..."></textarea>
                            </div>
                            
                            <!-- Insurance & Payment -->
                            <div class="form-group">
                                <label for="insurance">Insurance Provider</label>
                                <input type="text" id="insurance" placeholder="e.g., NHIMA">
                            </div>
                            
                            <div class="form-group">
                                <label for="insuranceNumber">Insurance Number</label>
                                <input type="text" id="insuranceNumber" placeholder="Policy number">
                            </div>
                            
                            <div class="form-group">
                                <label for="paymentStatus">Payment Status</label>
                                <select id="paymentStatus" class="enhanced-dropdown expandable-dropdown">
                                    <option value="">-- select payment status --</option>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="insurance">Insurance</option>
                                    <option value="partial">Partial</option>
                                    <option value="waived">Waived</option>
                                </select>
                            </div>
                            
                            <!-- Status -->
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" class="enhanced-dropdown expandable-dropdown">
                                    <option value="">-- select status --</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            
                            <!-- Notes -->
                            <div class="form-group full-width">
                                <label for="notes">Notes</label>
                                <textarea id="notes" placeholder="Additional notes for the appointment..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeAppointmentModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveAppointment()">
                            <ion-icon name="save-outline"></ion-icon>
                            Save Appointment
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Appointment Details Modal -->
            <div class="modal-overlay view-details-modal" id="viewAppointmentModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="viewModalTitle">Appointment Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="appointment-details">
                            <div class="patient-info">
                                <h4 style="color: var(--primary); margin-bottom: 10px;">Patient Information</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Patient Name:</span>
                                        <span class="info-value" id="viewPatientName">John Doe</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Patient ID:</span>
                                        <span class="info-value" id="viewPatientId">PAT-001234</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Age & Gender:</span>
                                        <span class="info-value" id="viewPatientAgeGender">45, Male</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Phone:</span>
                                        <span class="info-value" id="viewPatientPhone">+265 123 456 789</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value" id="viewPatientEmail">john.doe@email.com</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Address:</span>
                                        <span class="info-value" id="viewPatientAddress">123 Main St, Lilongwe</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="appointment-info">
                                <h4 style="color: var(--primary); margin-bottom: 10px;">Appointment Information</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Appointment ID:</span>
                                        <span class="info-value" id="viewAppointmentId">APT-20231223-001</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Date & Time:</span>
                                        <span class="info-value" id="viewDateTime">Dec 23, 2023 - 10:00 AM</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Duration:</span>
                                        <span class="info-value" id="viewDuration">30 minutes</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Doctor:</span>
                                        <span class="info-value" id="viewDoctor">Dr. John Smith</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Type:</span>
                                        <span class="info-value" id="viewAppointmentType">Consultation</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value" id="viewStatus">Confirmed</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Priority:</span>
                                        <span class="info-value" id="viewPriority">Medium</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Payment Status:</span>
                                        <span class="info-value" id="viewPaymentStatus">Paid</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Information -->
                        <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                            <h4 style="margin-bottom: 15px; color: var(--primary);">Medical Information</h4>
                            <div class="info-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="info-item">
                                    <span class="info-label">Reason for Visit:</span>
                                    <div style="padding: 12px; background: var(--light-gray); border-radius: 8px; margin-top: 5px;" id="viewReason">
                                        Routine check-up and follow-up for hypertension
                                    </div>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Symptoms:</span>
                                    <div style="padding: 12px; background: var(--light-gray); border-radius: 8px; margin-top: 5px;" id="viewSymptoms">
                                        Mild headache, occasional dizziness
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Insurance Information -->
                        <div class="form-group full-width">
                            <h4 style="margin-bottom: 15px; color: var(--primary);">Insurance Information</h4>
                            <div class="info-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="info-item">
                                    <span class="info-label">Insurance Provider:</span>
                                    <span class="info-value" id="viewInsurance">NHIMA</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Insurance Number:</span>
                                    <span class="info-value" id="viewInsuranceNumber">NH-789456123</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="form-group full-width">
                            <label>Notes:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px; margin-top: 5px;" id="viewNotes">
                                Patient requires regular monitoring for hypertension. Last check-up was 3 months ago.
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <!-- Only Close button remains -->
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="deleteAppointmentModal">
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
                        <h4>Delete Appointment</h4>
                        <p>Are you sure you want to delete this appointment? This action cannot be undone.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="action-btn danger" onclick="confirmDelete()">
                                <ion-icon name="trash-outline"></ion-icon>
                                Delete Appointment
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Check-in Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="checkInModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Patient Check-in</h3>
                        <button class="modal-close" onclick="closeCheckInModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="confirmation-body">
                        <div class="confirmation-icon success">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <h4>Check-in Patient</h4>
                        <p>Mark <strong id="checkInPatientName">[Patient Name]</strong> as checked-in for their appointment?</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeCheckInModal()">
                                Cancel
                            </button>
                            <button class="action-btn success" onclick="confirmCheckIn()">
                                <ion-icon name="checkmark-circle-outline"></ion-icon>
                                Confirm Check-in
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Removed Select2 CDN links -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <script>
                // Sample Appointments Data
                const appointments = [
                    {
                        id: 1,
                        patientId: "PAT-001234",
                        patientName: "John Doe",
                        patientAge: 45,
                        patientGender: "Male",
                        patientPhone: "+265 123 456 789",
                        patientEmail: "john.doe@email.com",
                        patientAddress: "123 Main St, Lilongwe",
                        appointmentId: "APT-20231223-001",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "10:00",
                        timeDisplay: "10:00 AM",
                        duration: 30,
                        durationDisplay: "30 minutes",
                        doctor: "Dr. John Smith",
                        doctorId: "dr_smith",
                        department: "General Medicine",
                        departmentId: "general",
                        appointmentType: "Consultation",
                        room: "Room 101",
                        status: "confirmed",
                        statusText: "Confirmed",
                        priority: "medium",
                        priorityText: "Medium",
                        reason: "Routine check-up and follow-up for hypertension",
                        symptoms: "Mild headache, occasional dizziness",
                        insurance: "NHIMA",
                        insuranceNumber: "NH-789456123",
                        paymentStatus: "paid",
                        paymentStatusText: "Paid",
                        notes: "Patient requires regular monitoring for hypertension. Last check-up was 3 months ago.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: true,
                        createdAt: "2023-12-20T09:30:00"
                    },
                    {
                        id: 2,
                        patientId: "PAT-002345",
                        patientName: "Mary Johnson",
                        patientAge: 32,
                        patientGender: "Female",
                        patientPhone: "+265 234 567 890",
                        patientEmail: "mary.johnson@email.com",
                        patientAddress: "456 Lake Rd, Blantyre",
                        appointmentId: "APT-20231223-002",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "10:30",
                        timeDisplay: "10:30 AM",
                        duration: 45,
                        durationDisplay: "45 minutes",
                        doctor: "Dr. Sarah Johnson",
                        doctorId: "dr_johnson",
                        department: "Gynecology",
                        departmentId: "gynecology",
                        appointmentType: "Follow-up",
                        room: "Room 205",
                        status: "confirmed",
                        statusText: "Confirmed",
                        priority: "medium",
                        priorityText: "Medium",
                        reason: "Postnatal check-up",
                        symptoms: "Routine follow-up",
                        insurance: "Private",
                        insuranceNumber: "PRV-456789",
                        paymentStatus: "paid",
                        paymentStatusText: "Paid",
                        notes: "6 weeks postnatal check-up. Normal delivery.",
                        checkedIn: true,
                        checkInTime: "2023-12-23T10:15:00",
                        reminderSent: true,
                        createdAt: "2023-12-18T14:20:00"
                    },
                    {
                        id: 3,
                        patientId: "PAT-003456",
                        patientName: "Robert Brown",
                        patientAge: 58,
                        patientGender: "Male",
                        patientPhone: "+265 345 678 901",
                        patientEmail: "robert.brown@email.com",
                        patientAddress: "789 Hill View, Mzuzu",
                        appointmentId: "APT-20231223-003",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "11:00",
                        timeDisplay: "11:00 AM",
                        duration: 60,
                        durationDisplay: "60 minutes",
                        doctor: "Dr. Michael Williams",
                        doctorId: "dr_williams",
                        department: "Cardiology",
                        departmentId: "cardiology",
                        appointmentType: "Consultation",
                        room: "Room 302",
                        status: "scheduled",
                        statusText: "Scheduled",
                        priority: "high",
                        priorityText: "High",
                        reason: "Chest pain and shortness of breath",
                        symptoms: "Chest pain, shortness of breath, fatigue",
                        insurance: "NHIMA",
                        insuranceNumber: "NH-123456789",
                        paymentStatus: "insurance",
                        paymentStatusText: "Insurance",
                        notes: "History of hypertension. Referred from general medicine.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: true,
                        createdAt: "2023-12-22T11:45:00"
                    },
                    {
                        id: 4,
                        patientId: "PAT-004567",
                        patientName: "Sarah Wilson",
                        patientAge: 28,
                        patientGender: "Female",
                        patientPhone: "+265 456 789 012",
                        patientEmail: "sarah.wilson@email.com",
                        patientAddress: "321 Valley Rd, Zomba",
                        appointmentId: "APT-20231223-004",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "11:30",
                        timeDisplay: "11:30 AM",
                        duration: 30,
                        durationDisplay: "30 minutes",
                        doctor: "Dr. Emily Brown",
                        doctorId: "dr_brown",
                        department: "Pediatrics",
                        departmentId: "pediatrics",
                        appointmentType: "Vaccination",
                        room: "Room 105",
                        status: "pending",
                        statusText: "Pending",
                        priority: "low",
                        priorityText: "Low",
                        reason: "Child vaccination - 6 months",
                        symptoms: "Routine vaccination",
                        insurance: "Private",
                        insuranceNumber: "PRV-789123",
                        paymentStatus: "pending",
                        paymentStatusText: "Pending",
                        notes: "Bringing 6-month old child for routine vaccinations.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: false,
                        createdAt: "2023-12-21T16:30:00"
                    },
                    {
                        id: 5,
                        patientId: "PAT-005678",
                        patientName: "David Miller",
                        patientAge: 65,
                        patientGender: "Male",
                        patientPhone: "+265 567 890 123",
                        patientEmail: "david.miller@email.com",
                        patientAddress: "654 Mountain View, Karonga",
                        appointmentId: "APT-20231223-005",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "13:00",
                        timeDisplay: "01:00 PM",
                        duration: 45,
                        durationDisplay: "45 minutes",
                        doctor: "Dr. David Jones",
                        doctorId: "dr_jones",
                        department: "Orthopedics",
                        departmentId: "orthopedics",
                        appointmentType: "Follow-up",
                        room: "Room 208",
                        status: "confirmed",
                        statusText: "Confirmed",
                        priority: "medium",
                        priorityText: "Medium",
                        reason: "Knee surgery follow-up",
                        symptoms: "Knee pain, limited mobility",
                        insurance: "NHIMA",
                        insuranceNumber: "NH-987654321",
                        paymentStatus: "paid",
                        paymentStatusText: "Paid",
                        notes: "Post-op check-up after knee replacement surgery.",
                        checkedIn: true,
                        checkInTime: "2023-12-23T12:45:00",
                        reminderSent: true,
                        createdAt: "2023-12-19T10:15:00"
                    },
                    {
                        id: 6,
                        patientId: "PAT-006789",
                        patientName: "Lisa Garcia",
                        patientAge: 40,
                        patientGender: "Female",
                        patientPhone: "+265 678 901 234",
                        patientEmail: "lisa.garcia@email.com",
                        patientAddress: "987 Beach Rd, Mangochi",
                        appointmentId: "APT-20231223-006",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "13:30",
                        timeDisplay: "01:30 PM",
                        duration: 30,
                        durationDisplay: "30 minutes",
                        doctor: "Dr. Lisa Miller",
                        doctorId: "dr_miller",
                        department: "Dermatology",
                        departmentId: "dermatology",
                        appointmentType: "Consultation",
                        room: "Room 304",
                        status: "cancelled",
                        statusText: "Cancelled",
                        priority: "low",
                        priorityText: "Low",
                        reason: "Skin rash consultation",
                        symptoms: "Red rash on arms and legs, itching",
                        insurance: "Private",
                        insuranceNumber: "PRV-456123",
                        paymentStatus: "cancelled",
                        paymentStatusText: "Cancelled",
                        notes: "Patient called to cancel due to travel.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: true,
                        createdAt: "2023-12-17T15:45:00"
                    },
                    {
                        id: 7,
                        patientId: "PAT-007890",
                        patientName: "Thomas Anderson",
                        patientAge: 50,
                        patientGender: "Male",
                        patientPhone: "+265 789 012 345",
                        patientEmail: "thomas.anderson@email.com",
                        patientAddress: "147 Garden Ave, Salima",
                        appointmentId: "APT-20231223-007",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "14:00",
                        timeDisplay: "02:00 PM",
                        duration: 60,
                        durationDisplay: "60 minutes",
                        doctor: "Dr. Robert Davis",
                        doctorId: "dr_davis",
                        department: "Surgery",
                        departmentId: "surgery",
                        appointmentType: "Consultation",
                        room: "Room 401",
                        status: "pending",
                        statusText: "Pending",
                        priority: "high",
                        priorityText: "High",
                        reason: "Appendicitis evaluation",
                        symptoms: "Abdominal pain, fever, nausea",
                        insurance: "NHIMA",
                        insuranceNumber: "NH-654789123",
                        paymentStatus: "pending",
                        paymentStatusText: "Pending",
                        notes: "Possible emergency surgery required.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: false,
                        createdAt: "2023-12-22T20:15:00"
                    },
                    {
                        id: 8,
                        patientId: "PAT-008901",
                        patientName: "Emily Wilson",
                        patientAge: 35,
                        patientGender: "Female",
                        patientPhone: "+265 890 123 456",
                        patientEmail: "emily.wilson@email.com",
                        patientAddress: "258 River Side, Balaka",
                        appointmentId: "APT-20231223-008",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "14:30",
                        timeDisplay: "02:30 PM",
                        duration: 45,
                        durationDisplay: "45 minutes",
                        doctor: "Dr. Maria Garcia",
                        doctorId: "dr_garcia",
                        department: "Ophthalmology",
                        departmentId: "ophthalmology",
                        appointmentType: "Test",
                        room: "Room 306",
                        status: "confirmed",
                        statusText: "Confirmed",
                        priority: "medium",
                        priorityText: "Medium",
                        reason: "Eye examination and vision test",
                        symptoms: "Blurred vision, eye strain",
                        insurance: "Private",
                        insuranceNumber: "PRV-789456",
                        paymentStatus: "paid",
                        paymentStatusText: "Paid",
                        notes: "Follow-up after recent eye surgery.",
                        checkedIn: true,
                        checkInTime: "2023-12-23T14:20:00",
                        reminderSent: true,
                        createdAt: "2023-12-20T11:30:00"
                    }
                ];

                // Sample Patients for Search
                const patients = [
                    { id: 1, patientId: "PAT-001234", name: "John Doe", age: 45, gender: "Male", phone: "+265 123 456 789" },
                    { id: 2, patientId: "PAT-002345", name: "Mary Johnson", age: 32, gender: "Female", phone: "+265 234 567 890" },
                    { id: 3, patientId: "PAT-003456", name: "Robert Brown", age: 58, gender: "Male", phone: "+265 345 678 901" },
                    { id: 4, patientId: "PAT-004567", name: "Sarah Wilson", age: 28, gender: "Female", phone: "+265 456 789 012" },
                    { id: 5, patientId: "PAT-005678", name: "David Miller", age: 65, gender: "Male", phone: "+265 567 890 123" },
                    { id: 6, patientId: "PAT-006789", name: "Lisa Garcia", age: 40, gender: "Female", phone: "+265 678 901 234" },
                    { id: 7, patientId: "PAT-007890", name: "Thomas Anderson", age: 50, gender: "Male", phone: "+265 789 012 345" },
                    { id: 8, patientId: "PAT-008901", name: "Emily Wilson", age: 35, gender: "Female", phone: "+265 890 123 456" },
                    { id: 9, patientId: "PAT-009012", name: "James Taylor", age: 42, gender: "Male", phone: "+265 901 234 567" },
                    { id: 10, patientId: "PAT-010123", name: "Maria Rodriguez", age: 29, gender: "Female", phone: "+265 012 345 678" }
                ];

                // Initialize Variables
                let currentPage = 1;
                const itemsPerPage = 10;
                let filteredAppointments = [...appointments];
                let appointmentToDelete = null;
                let appointmentToView = null;
                let appointmentToCheckIn = null;

                // DOM Elements
                const tableBody = document.getElementById('appointmentsTableBody');
                const mobileList = document.getElementById('mobileAppointmentsList');
                const appointmentModal = document.getElementById('appointmentModal');
                const viewAppointmentModal = document.getElementById('viewAppointmentModal');
                const deleteAppointmentModal = document.getElementById('deleteAppointmentModal');
                const checkInModal = document.getElementById('checkInModal');

                // Initialize Page
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM loaded - Initializing appointments management');
                    
                    // Set default date to today
                    const appointmentDateInput = document.getElementById('appointmentDate');
                    if (appointmentDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        appointmentDateInput.value = today;
                        appointmentDateInput.min = today;
                    }
                    
                    // Set default date filters to today
                    const dateFromInput = document.getElementById('dateFrom');
                    const dateToInput = document.getElementById('dateTo');
                    if (dateFromInput && dateToInput) {
                        dateFromInput.value = new Date().toISOString().split('T')[0];
                        dateToInput.value = new Date().toISOString().split('T')[0];
                    }
                    
                    // Initialize duration selector
                    initializeDurationSelector();
                    
                    // Initialize patient search
                    initializePatientSearch();
                    
                    // Initialize enhanced dropdowns
                    initializeEnhancedDropdowns();
                    
                    // Initialize table and statistics
                    renderAppointmentsTable();
                    updateStatistics();
                    
                    // Check screen size and adjust view
                    checkScreenSize();
                    window.addEventListener('resize', checkScreenSize);
                    
                    // Add event listeners for modals
                    document.querySelectorAll('.modal-overlay').forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('active');
                            }
                        });
                    });
                    
                    // Add event listener for add appointment button
                    const addAppointmentBtn = document.getElementById('addAppointmentBtn');
                    if (addAppointmentBtn) {
                        addAppointmentBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            e.preventDefault();
                            openAddAppointmentModal();
                        });
                    }
                });

                // Initialize Enhanced Dropdowns
                function initializeEnhancedDropdowns() {
                    // Add event listeners to all enhanced dropdowns
                    const enhancedDropdowns = document.querySelectorAll('.enhanced-dropdown select');
                    
                    enhancedDropdowns.forEach(dropdown => {
                        // Add focus effect
                        dropdown.addEventListener('focus', function() {
                            if (this.classList.contains('expandable-dropdown')) {
                                this.size = Math.min(this.options.length, 8); // Show up to 8 options
                            }
                        });
                        
                        // Add blur effect
                        dropdown.addEventListener('blur', function() {
                            if (this.classList.contains('expandable-dropdown')) {
                                this.size = 1;
                            }
                        });
                        
                        // Add change effect
                        dropdown.addEventListener('change', function() {
                            if (this.classList.contains('expandable-dropdown')) {
                                this.size = 1;
                            }
                        });
                        
                        // Add hover effect
                        dropdown.addEventListener('mouseenter', function() {
                            this.style.borderColor = 'var(--primary)';
                        });
                        
                        dropdown.addEventListener('mouseleave', function() {
                            if (!this.matches(':focus')) {
                                this.style.borderColor = 'var(--gray)';
                            }
                        });
                    });
                }

                // Check screen size and adjust view
                function checkScreenSize() {
                    const isMobile = window.innerWidth <= 768;
                    const tableWrapper = document.querySelector('.appointments-table-wrapper');
                    const mobileList = document.querySelector('.mobile-appointments-list');
                    
                    if (isMobile) {
                        if (tableWrapper) tableWrapper.style.display = 'none';
                        if (mobileList) mobileList.style.display = 'block';
                    } else {
                        if (tableWrapper) tableWrapper.style.display = 'block';
                        if (mobileList) mobileList.style.display = 'none';
                    }
                }

                // Initialize Duration Selector
                function initializeDurationSelector() {
                    const durationOptions = document.querySelectorAll('.duration-option');
                    durationOptions.forEach(option => {
                        option.addEventListener('click', function() {
                            // Remove selected class from all options
                            durationOptions.forEach(opt => opt.classList.remove('selected'));
                            
                            // Add selected class to clicked option
                            this.classList.add('selected');
                            
                            // Update hidden input value
                            const minutes = this.getAttribute('data-minutes');
                            document.getElementById('duration').value = minutes;
                        });
                    });
                }

                // Initialize Patient Search
                function initializePatientSearch() {
                    const patientSearchInput = document.getElementById('patientSearch');
                    const patientResults = document.getElementById('patientResults');
                    
                    if (!patientSearchInput || !patientResults) return;
                    
                    patientSearchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        
                        if (searchTerm.length < 2) {
                            patientResults.style.display = 'none';
                            return;
                        }
                        
                        // Filter patients
                        const filteredPatients = patients.filter(patient => 
                            patient.name.toLowerCase().includes(searchTerm) ||
                            patient.patientId.toLowerCase().includes(searchTerm)
                        );
                        
                        // Display results
                        if (filteredPatients.length > 0) {
                            patientResults.innerHTML = filteredPatients.map(patient => `
                                <div class="patient-search-result" 
                                     data-id="${patient.id}"
                                     data-patient-id="${patient.patientId}"
                                     data-name="${patient.name}"
                                     data-age="${patient.age}"
                                     data-gender="${patient.gender}">
                                    <div class="patient-avatar">${patient.name.split(' ').map(n => n[0]).join('')}</div>
                                    <div>
                                        <strong>${patient.name}</strong><br>
                                        <small>${patient.patientId} • ${patient.age} yrs • ${patient.gender}</small>
                                    </div>
                                </div>
                            `).join('');
                            
                            patientResults.style.display = 'block';
                            
                            // Add click event listeners to results
                            document.querySelectorAll('.patient-search-result').forEach(result => {
                                result.addEventListener('click', function() {
                                    const patientId = this.getAttribute('data-patient-id');
                                    const patientName = this.getAttribute('data-name');
                                    const patientAge = this.getAttribute('data-age');
                                    const patientGender = this.getAttribute('data-gender');
                                    
                                    // Update form fields
                                    document.getElementById('patientSearch').value = `${patientName} (${patientId})`;
                                    document.getElementById('patientId').value = patientId;
                                    document.getElementById('patientName').value = patientName;
                                    document.getElementById('patientAge').value = patientAge;
                                    document.getElementById('patientGender').value = patientGender;
                                    
                                    // Hide results
                                    patientResults.style.display = 'none';
                                });
                            });
                        } else {
                            patientResults.innerHTML = '<div class="patient-search-result">No patients found</div>';
                            patientResults.style.display = 'block';
                        }
                    });
                    
                    // Hide results when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!patientSearchInput.contains(e.target) && !patientResults.contains(e.target)) {
                            patientResults.style.display = 'none';
                        }
                    });
                }

                // Modal Functions
                function openAddAppointmentModal() {
                    console.log('Opening Add Appointment Modal');
                    
                    document.getElementById('modalTitle').textContent = 'Schedule New Appointment';
                    
                    // Reset the form
                    const form = document.getElementById('appointmentForm');
                    if (form) {
                        form.reset();
                    }
                    
                    // Clear appointment ID
                    document.getElementById('appointmentId').value = '';
                    
                    // Reset patient fields
                    document.getElementById('patientSearch').value = '';
                    document.getElementById('patientId').value = '';
                    document.getElementById('patientName').value = '';
                    document.getElementById('patientAge').value = '';
                    document.getElementById('patientGender').value = '';
                    
                    // Reset doctor field
                    document.getElementById('doctor').value = '';
                    
                    // Reset other fields
                    document.getElementById('reason').value = '';
                    document.getElementById('symptoms').value = '';
                    document.getElementById('insurance').value = '';
                    document.getElementById('insuranceNumber').value = '';
                    document.getElementById('notes').value = '';
                    
                    // Reset dropdowns to default values
                    document.getElementById('appointmentType').value = '';
                    document.getElementById('paymentStatus').value = '';
                    document.getElementById('status').value = 'scheduled';
                    document.getElementById('priority').value = 'medium';
                    document.getElementById('appointmentTime').value = '';
                    
                    // Set default date to today
                    const appointmentDateInput = document.getElementById('appointmentDate');
                    if (appointmentDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        appointmentDateInput.value = today;
                    }
                    
                    // Set default time to next available slot
                    const now = new Date();
                    const currentHour = now.getHours();
                    const nextHour = currentHour < 17 ? (currentHour + 1) : 9;
                    const nextTime = `${nextHour.toString().padStart(2, '0')}:00`;
                    document.getElementById('appointmentTime').value = nextTime;
                    
                    // Reset duration selector
                    document.querySelectorAll('.duration-option').forEach(option => {
                        option.classList.remove('selected');
                        if (option.getAttribute('data-minutes') === '30') {
                            option.classList.add('selected');
                        }
                    });
                    document.getElementById('duration').value = '30';
                    
                    // Generate appointment ID
                    document.getElementById('appointmentId').value = generateAppointmentId();
                    
                    // Focus on patient search field
                    setTimeout(() => {
                        document.getElementById('patientSearch').focus();
                    }, 100);
                    
                    // Show modal
                    if (appointmentModal) {
                        appointmentModal.classList.add('active');
                    }
                    
                    return false;
                }

                function generateAppointmentId() {
                    const date = new Date();
                    const year = date.getFullYear();
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    const day = date.getDate().toString().padStart(2, '0');
                    const random = Math.floor(100 + Math.random() * 900);
                    return `APT-${year}${month}${day}-${random}`;
                }

                function editAppointment(id) {
                    console.log('Edit Appointment clicked for ID:', id);
                    
                    const appointment = appointments.find(a => a.id === id);
                    if (!appointment) {
                        console.error('Appointment not found with ID:', id);
                        return;
                    }

                    document.getElementById('modalTitle').textContent = 'Edit Appointment';
                    
                    // Fill form with appointment data
                    document.getElementById('appointmentId').value = appointment.appointmentId;
                    document.getElementById('patientSearch').value = `${appointment.patientName} (${appointment.patientId})`;
                    document.getElementById('patientId').value = appointment.patientId;
                    document.getElementById('patientName').value = appointment.patientName;
                    document.getElementById('patientAge').value = appointment.patientAge;
                    document.getElementById('patientGender').value = appointment.patientGender;
                    document.getElementById('appointmentDate').value = appointment.appointmentDate;
                    document.getElementById('appointmentTime').value = appointment.appointmentTime;
                    document.getElementById('reason').value = appointment.reason;
                    document.getElementById('symptoms').value = appointment.symptoms;
                    document.getElementById('insurance').value = appointment.insurance;
                    document.getElementById('insuranceNumber').value = appointment.insuranceNumber;
                    document.getElementById('notes').value = appointment.notes;
                    
                    // Fill doctor field
                    document.getElementById('doctor').value = appointment.doctor;
                    
                    // Set dropdown values
                    document.getElementById('appointmentType').value = appointment.appointmentType;
                    document.getElementById('paymentStatus').value = appointment.paymentStatus;
                    document.getElementById('status').value = appointment.status;
                    document.getElementById('priority').value = appointment.priority;
                    
                    // Set duration selector
                    document.querySelectorAll('.duration-option').forEach(option => {
                        option.classList.remove('selected');
                        if (option.getAttribute('data-minutes') === appointment.duration.toString()) {
                            option.classList.add('selected');
                        }
                    });
                    document.getElementById('duration').value = appointment.duration;
                    
                    // Focus on first field
                    setTimeout(() => {
                        document.getElementById('patientSearch').focus();
                    }, 100);
                    
                    // Show modal
                    if (appointmentModal) {
                        appointmentModal.classList.add('active');
                    }
                }

                function viewAppointment(id) {
                    console.log('View Appointment clicked for ID:', id);
                    
                    const appointment = appointments.find(a => a.id === id);
                    if (!appointment) {
                        console.error('Appointment not found with ID:', id);
                        return;
                    }

                    appointmentToView = appointment;
                    
                    // Fill view modal with appointment data
                    document.getElementById('viewModalTitle').textContent = `Appointment: ${appointment.appointmentId}`;
                    document.getElementById('viewPatientName').textContent = appointment.patientName;
                    document.getElementById('viewPatientId').textContent = appointment.patientId;
                    document.getElementById('viewPatientAgeGender').textContent = `${appointment.patientAge} yrs, ${appointment.patientGender}`;
                    document.getElementById('viewPatientPhone').textContent = appointment.patientPhone;
                    document.getElementById('viewPatientEmail').textContent = appointment.patientEmail;
                    document.getElementById('viewPatientAddress').textContent = appointment.patientAddress;
                    document.getElementById('viewAppointmentId').textContent = appointment.appointmentId;
                    
                    // Format date and time
                    const appDate = new Date(appointment.appointmentDate);
                    const dateStr = appDate.toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    document.getElementById('viewDateTime').textContent = `${dateStr} - ${appointment.timeDisplay}`;
                    
                    document.getElementById('viewDuration').textContent = appointment.durationDisplay;
                    document.getElementById('viewDoctor').textContent = appointment.doctor;
                    document.getElementById('viewAppointmentType').textContent = appointment.appointmentType;
                    
                    // Status with badge
                    const statusElement = document.getElementById('viewStatus');
                    statusElement.innerHTML = `<span class="appointment-status status-${appointment.status}">${appointment.statusText}</span>`;
                    
                    // Priority with indicator
                    const priorityElement = document.getElementById('viewPriority');
                    priorityElement.innerHTML = `
                        <span class="priority-indicator priority-${appointment.priority}"></span>
                        ${appointment.priorityText}
                    `;
                    
                    document.getElementById('viewPaymentStatus').textContent = appointment.paymentStatusText;
                    document.getElementById('viewReason').textContent = appointment.reason;
                    document.getElementById('viewSymptoms').textContent = appointment.symptoms;
                    document.getElementById('viewInsurance').textContent = appointment.insurance;
                    document.getElementById('viewInsuranceNumber').textContent = appointment.insuranceNumber;
                    document.getElementById('viewNotes').textContent = appointment.notes;
                    
                    // Show modal
                    if (viewAppointmentModal) {
                        viewAppointmentModal.classList.add('active');
                    }
                }

                function closeAppointmentModal() {
                    if (appointmentModal) {
                        appointmentModal.classList.remove('active');
                    }
                }

                function closeViewModal() {
                    if (viewAppointmentModal) {
                        viewAppointmentModal.classList.remove('active');
                    }
                }

                // Save Appointment Function
                function saveAppointment() {
                    // Get form values
                    const appointmentId = document.getElementById('appointmentId').value;
                    const patientId = document.getElementById('patientId').value;
                    const patientName = document.getElementById('patientName').value;
                    const patientAge = document.getElementById('patientAge').value;
                    const patientGender = document.getElementById('patientGender').value;
                    const appointmentDate = document.getElementById('appointmentDate').value;
                    const appointmentTime = document.getElementById('appointmentTime').value;
                    const duration = document.getElementById('duration').value;
                    const priority = document.getElementById('priority').value;
                    const doctor = document.getElementById('doctor').value;
                    const appointmentType = document.getElementById('appointmentType').value;
                    const reason = document.getElementById('reason').value;
                    const symptoms = document.getElementById('symptoms').value;
                    const insurance = document.getElementById('insurance').value;
                    const insuranceNumber = document.getElementById('insuranceNumber').value;
                    const paymentStatus = document.getElementById('paymentStatus').value;
                    const status = document.getElementById('status').value;
                    const notes = document.getElementById('notes').value;
                    
                    // Validation
                    if (!patientId || !patientName) {
                        alert('Please select a patient');
                        return;
                    }
                    
                    if (!appointmentDate || !appointmentTime) {
                        alert('Please select appointment date and time');
                        return;
                    }
                    
                    if (!doctor) {
                        alert('Please enter doctor name');
                        return;
                    }
                    
                    if (!appointmentType) {
                        alert('Please select appointment type');
                        return;
                    }
                    
                    if (!priority) {
                        alert('Please select priority');
                        return;
                    }
                    
                    // Create or update appointment
                    const existingAppointment = appointments.find(a => a.appointmentId === appointmentId);
                    
                    if (existingAppointment) {
                        // Update existing appointment
                        existingAppointment.patientId = patientId;
                        existingAppointment.patientName = patientName;
                        existingAppointment.patientAge = parseInt(patientAge);
                        existingAppointment.patientGender = patientGender;
                        existingAppointment.appointmentDate = appointmentDate;
                        existingAppointment.appointmentTime = appointmentTime;
                        existingAppointment.timeDisplay = formatTime(appointmentTime);
                        existingAppointment.duration = parseInt(duration);
                        existingAppointment.durationDisplay = `${duration} minutes`;
                        existingAppointment.priority = priority;
                        existingAppointment.priorityText = priority.charAt(0).toUpperCase() + priority.slice(1);
                        existingAppointment.doctor = doctor;
                        existingAppointment.doctorId = doctor.toLowerCase().replace(/[^a-z0-9]/g, '_');
                        existingAppointment.appointmentType = appointmentType;
                        existingAppointment.reason = reason;
                        existingAppointment.symptoms = symptoms;
                        existingAppointment.insurance = insurance;
                        existingAppointment.insuranceNumber = insuranceNumber;
                        existingAppointment.paymentStatus = paymentStatus;
                        existingAppointment.paymentStatusText = paymentStatus ? paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1) : 'Pending';
                        existingAppointment.status = status;
                        existingAppointment.statusText = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Scheduled';
                        existingAppointment.notes = notes;
                        
                        alert('Appointment updated successfully!');
                    } else {
                        // Create new appointment
                        const newAppointment = {
                            id: appointments.length > 0 ? Math.max(...appointments.map(a => a.id)) + 1 : 1,
                            patientId: patientId,
                            patientName: patientName,
                            patientAge: parseInt(patientAge),
                            patientGender: patientGender,
                            patientPhone: "+265 XXX XXX XXX", // Default phone
                            patientEmail: "patient@email.com", // Default email
                            patientAddress: "Address not specified",
                            appointmentId: appointmentId,
                            appointmentDate: appointmentDate,
                            appointmentTime: appointmentTime,
                            timeDisplay: formatTime(appointmentTime),
                            duration: parseInt(duration),
                            durationDisplay: `${duration} minutes`,
                            doctor: doctor,
                            doctorId: doctor.toLowerCase().replace(/[^a-z0-9]/g, '_'),
                            appointmentType: appointmentType,
                            status: status || 'scheduled',
                            statusText: status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Scheduled',
                            priority: priority,
                            priorityText: priority.charAt(0).toUpperCase() + priority.slice(1),
                            reason: reason,
                            symptoms: symptoms,
                            insurance: insurance,
                            insuranceNumber: insuranceNumber,
                            paymentStatus: paymentStatus || 'pending',
                            paymentStatusText: paymentStatus ? paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1) : 'Pending',
                            notes: notes,
                            checkedIn: false,
                            checkInTime: null,
                            reminderSent: false,
                            createdAt: new Date().toISOString()
                        };
                        
                        appointments.push(newAppointment);
                        alert('Appointment scheduled successfully!');
                    }
                    
                    // Update UI
                    filterAppointments();
                    updateStatistics();
                    closeAppointmentModal();
                }

                function formatTime(timeString) {
                    const [hours, minutes] = timeString.split(':');
                    const hour = parseInt(hours);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    return `${displayHour}:${minutes} ${ampm}`;
                }

                // Render Appointments Table - UPDATED for both desktop and mobile
                function renderAppointmentsTable() {
                    if (!tableBody || !mobileList) return;
                    
                    if (filteredAppointments.length === 0) {
                        // Desktop table empty state
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <ion-icon name="calendar-outline"></ion-icon>
                                    <h3>No appointments found</h3>
                                    <p>Try adjusting your search or filters</p>
                                </td>
                            </tr>
                        `;
                        
                        // Mobile list empty state
                        mobileList.innerHTML = `
                            <div class="empty-state">
                                <ion-icon name="calendar-outline"></ion-icon>
                                <h3>No appointments found</h3>
                                <p>Try adjusting your search or filters</p>
                            </div>
                        `;
                        return;
                    }

                    // Render Desktop Table
                    let desktopHtml = '';
                    filteredAppointments.forEach(appointment => {
                        const statusClass = `status-${appointment.status}`;
                        const priorityClass = `priority-${appointment.priority}`;
                        
                        // Get patient initials for avatar
                        const initials = appointment.patientName.split(' ').map(n => n[0]).join('');
                        
                        desktopHtml += `
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px; min-width: 200px;">
                                        <div class="patient-avatar">${initials}</div>
                                        <div>
                                            <strong>${appointment.patientName}</strong><br>
                                            <small style="color: var(--dark-gray);">${appointment.patientId}</small><br>
                                            <small style="color: var(--dark-gray);">${appointment.patientAge} yrs • ${appointment.patientGender}</small>
                                        </div>
                                    </div>
                                </td>
                                <td style="min-width: 180px;">
                                    <div>
                                        <strong>${appointment.appointmentType}</strong><br>
                                        <small style="color: var(--dark-gray);">${appointment.durationDisplay}</small>
                                    </div>
                                </td>
                                <td style="min-width: 200px;">
                                    <div class="doctor-info">
                                        <div class="doctor-avatar">
                                            ${appointment.doctor.split(' ').map(n => n[0]).join('')}
                                        </div>
                                        <div>
                                            <strong>${appointment.doctor}</strong><br>
                                            <small style="color: var(--dark-gray);">${appointment.department}</small>
                                        </div>
                                    </div>
                                </td>
                                <td style="min-width: 150px;">
                                    <span class="appointment-status ${statusClass}">${appointment.statusText}</span>
                                    <div style="margin-top: 5px;">
                                        <span class="priority-indicator ${priorityClass}"></span>
                                        <small style="color: var(--dark-gray);">${appointment.priorityText}</small>
                                    </div>
                                </td>
                                <td style="min-width: 150px;">
                                    <div class="time-slot">
                                        <ion-icon name="time-outline"></ion-icon>
                                        <div>
                                            <div><strong>${appointment.timeDisplay}</strong></div>
                                            <div class="delivery-time">${appointment.durationDisplay}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="min-width: 200px;">
                                    <div class="appointment-actions">
                                        <button class="action-icon view" title="View Details" onclick="viewAppointment(${appointment.id})">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon edit" title="Edit" onclick="editAppointment(${appointment.id})">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        ${!appointment.checkedIn ? `
                                            <button class="action-icon checkin" title="Check-in" onclick="openCheckInModal(${appointment.id})">
                                                <ion-icon name="checkmark-circle-outline"></ion-icon>
                                            </button>
                                        ` : `
                                            <button class="action-icon checkin" title="Checked In" style="background: var(--success); color: white;" disabled>
                                                <ion-icon name="checkmark-done-outline"></ion-icon>
                                            </button>
                                        `}
                                        <button class="action-icon delete" title="Delete" onclick="deleteAppointment(${appointment.id})">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = desktopHtml;
                    
                    // Render Mobile List
                    let mobileHtml = '';
                    filteredAppointments.forEach(appointment => {
                        const statusClass = `status-${appointment.status}`;
                        const priorityClass = `priority-${appointment.priority}`;
                        const initials = appointment.patientName.split(' ').map(n => n[0]).join('');
                        
                        mobileHtml += `
                            <div class="mobile-appointment-card">
                                <div class="mobile-appointment-header">
                                    <div class="mobile-patient-info">
                                        <div class="patient-avatar">${initials}</div>
                                        <div>
                                            <strong>${appointment.patientName}</strong><br>
                                            <small>${appointment.patientId}</small>
                                        </div>
                                    </div>
                                    <span class="appointment-status ${statusClass}">${appointment.statusText}</span>
                                </div>
                                
                                <div class="mobile-appointment-details">
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Time</span>
                                        <span class="mobile-detail-value">${appointment.timeDisplay}</span>
                                    </div>
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Duration</span>
                                        <span class="mobile-detail-value">${appointment.durationDisplay}</span>
                                    </div>
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Doctor</span>
                                        <span class="mobile-detail-value">${appointment.doctor}</span>
                                    </div>
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Type</span>
                                        <span class="mobile-detail-value">${appointment.appointmentType}</span>
                                    </div>
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Priority</span>
                                        <span class="mobile-detail-value">
                                            <span class="priority-indicator ${priorityClass}"></span>
                                            ${appointment.priorityText}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mobile-appointment-actions">
                                    <button class="action-icon view" title="View Details" onclick="viewAppointment(${appointment.id})">
                                        <ion-icon name="eye-outline"></ion-icon>
                                    </button>
                                    <button class="action-icon edit" title="Edit" onclick="editAppointment(${appointment.id})">
                                        <ion-icon name="create-outline"></ion-icon>
                                    </button>
                                    ${!appointment.checkedIn ? `
                                        <button class="action-icon checkin" title="Check-in" onclick="openCheckInModal(${appointment.id})">
                                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                                        </button>
                                    ` : `
                                        <button class="action-icon checkin" title="Checked In" style="background: var(--success); color: white;" disabled>
                                            <ion-icon name="checkmark-done-outline"></ion-icon>
                                        </button>
                                    `}
                                    <button class="action-icon delete" title="Delete" onclick="deleteAppointment(${appointment.id})">
                                        <ion-icon name="trash-outline"></ion-icon>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    mobileList.innerHTML = mobileHtml;
                    updatePaginationInfo();
                }

                // Update Statistics
                function updateStatistics() {
                    const today = new Date().toISOString().split('T')[0];
                    const todayAppointments = appointments.filter(a => a.appointmentDate === today);
                    
                    const totalAppointments = todayAppointments.length;
                    const confirmedAppointments = todayAppointments.filter(a => a.status === 'confirmed').length;
                    const pendingAppointments = todayAppointments.filter(a => a.status === 'pending').length;
                    const cancelledAppointments = todayAppointments.filter(a => a.status === 'cancelled').length;
                    const waitingPatients = todayAppointments.filter(a => a.checkedIn && a.status === 'confirmed').length;
                    const availableDoctors = 8; // This would normally come from an API
                    
                    document.getElementById('totalAppointments').textContent = totalAppointments;
                    document.getElementById('confirmedAppointments').textContent = confirmedAppointments;
                    document.getElementById('pendingAppointments').textContent = pendingAppointments;
                    document.getElementById('cancelledAppointments').textContent = cancelledAppointments;
                    document.getElementById('waitingPatients').textContent = waitingPatients;
                    document.getElementById('availableDoctors').textContent = availableDoctors;
                }

                // Filter and Search Functions
                function filterAppointments() {
                    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                    const status = document.getElementById('statusFilter').value;
                    const dateFrom = document.getElementById('dateFrom').value;
                    const dateTo = document.getElementById('dateTo').value;
                    const doctor = document.getElementById('doctorFilter').value;
                    const department = document.getElementById('departmentFilter').value;
                    const priority = document.getElementById('priorityFilter').value;

                    filteredAppointments = appointments.filter(appointment => {
                        const matchesSearch = 
                            appointment.patientName.toLowerCase().includes(searchTerm) ||
                            appointment.patientId.toLowerCase().includes(searchTerm) ||
                            appointment.doctor.toLowerCase().includes(searchTerm) ||
                            appointment.appointmentId.toLowerCase().includes(searchTerm);

                        const matchesStatus = !status || appointment.status === status;
                        const matchesDoctor = !doctor || appointment.doctorId === doctor;
                        const matchesDepartment = !department || appointment.departmentId === department;
                        const matchesPriority = !priority || appointment.priority === priority;
                        
                        const appointmentDate = new Date(appointment.appointmentDate);
                        const fromDate = dateFrom ? new Date(dateFrom) : null;
                        const toDate = dateTo ? new Date(dateTo) : null;
                        
                        let matchesDate = true;
                        if (fromDate && toDate) {
                            matchesDate = appointmentDate >= fromDate && appointmentDate <= toDate;
                        } else if (fromDate) {
                            matchesDate = appointmentDate >= fromDate;
                        } else if (toDate) {
                            matchesDate = appointmentDate <= toDate;
                        }

                        return matchesSearch && matchesStatus && matchesDoctor && matchesDepartment && matchesPriority && matchesDate;
                    });

                    // Sort by time
                    filteredAppointments.sort((a, b) => {
                        return a.appointmentTime.localeCompare(b.appointmentTime);
                    });

                    renderAppointmentsTable();
                    updatePaginationInfo();
                }

                function resetFilters() {
                    document.getElementById('searchInput').value = '';
                    document.getElementById('statusFilter').value = '';
                    document.getElementById('doctorFilter').value = '';
                    document.getElementById('departmentFilter').value = '';
                    document.getElementById('priorityFilter').value = '';
                    
                    // Reset dates to today
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('dateFrom').value = today;
                    document.getElementById('dateTo').value = today;
                    
                    filterAppointments();
                }

                function applyFilters() {
                    filterAppointments();
                }

                // Pagination Functions
                function updatePaginationInfo() {
                    const total = filteredAppointments.length;
                    const start = (currentPage - 1) * itemsPerPage + 1;
                    const end = Math.min(currentPage * itemsPerPage, total);
                    
                    document.getElementById('paginationInfo').textContent = 
                        `Showing ${start} to ${end} of ${total} entries`;
                }

                function changePage(direction) {
                    const totalPages = Math.ceil(filteredAppointments.length / itemsPerPage);
                    
                    if (direction === 'prev' && currentPage > 1) {
                        currentPage--;
                    } else if (direction === 'next' && currentPage < totalPages) {
                        currentPage++;
                    }
                    
                    renderAppointmentsTable();
                }

                // Delete Appointment Functions
                function deleteAppointment(id) {
                    const appointment = appointments.find(a => a.id === id);
                    if (!appointment) return;

                    appointmentToDelete = id;
                    document.getElementById('deleteAppointmentModal').classList.add('active');
                }

                function closeDeleteModal() {
                    if (deleteAppointmentModal) {
                        deleteAppointmentModal.classList.remove('active');
                    }
                    appointmentToDelete = null;
                }

                function confirmDelete() {
                    if (!appointmentToDelete) return;

                    const index = appointments.findIndex(a => a.id === appointmentToDelete);
                    if (index !== -1) {
                        appointments.splice(index, 1);
                        
                        // Update UI
                        filterAppointments();
                        updateStatistics();
                        closeDeleteModal();
                        
                        alert('Appointment deleted successfully!');
                    }
                }

                // Check-in Functions
                function openCheckInModal(id) {
                    const appointment = appointments.find(a => a.id === id);
                    if (!appointment) return;

                    appointmentToCheckIn = id;
                    document.getElementById('checkInPatientName').textContent = appointment.patientName;
                    checkInModal.classList.add('active');
                }

                function closeCheckInModal() {
                    if (checkInModal) {
                        checkInModal.classList.remove('active');
                    }
                    appointmentToCheckIn = null;
                }

                function confirmCheckIn() {
                    if (!appointmentToCheckIn) return;

                    const index = appointments.findIndex(a => a.id === appointmentToCheckIn);
                    if (index !== -1) {
                        appointments[index].checkedIn = true;
                        appointments[index].checkInTime = new Date().toISOString();
                        appointments[index].status = 'confirmed';
                        appointments[index].statusText = 'Confirmed';
                        
                        // Update UI
                        filterAppointments();
                        updateStatistics();
                        closeCheckInModal();
                        
                        alert('Patient checked in successfully!');
                    }
                }

                // Utility Functions
                function refreshAppointments() {
                    filterAppointments();
                }

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeAppointmentModal();
                        closeViewModal();
                        closeDeleteModal();
                        closeCheckInModal();
                    }
                    if (e.ctrlKey && e.key === 'f') {
                        e.preventDefault();
                        document.getElementById('searchInput').focus();
                    }
                    if (e.ctrlKey && e.key === 'n') {
                        e.preventDefault();
                        openAddAppointmentModal();
                    }
                });

                console.log('Appointments Management Page Initialized');
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
                /* ================== Appointments Management Styles ============== */
                .appointments-management-section {
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
                    background: #3498db;
                    color: white;
                }

                .action-btn.info:hover {
                    background: #2980b9;
                    transform: translateY(-2px);
                }

                .action-btn.prescription {
                    background: #9b59b6;
                    color: white;
                }

                .action-btn.prescription:hover {
                    background: #8e44ad;
                    transform: translateY(-2px);
                }

                /* Statistics Cards */
                .appointments-stats {
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
                    border-color: #3498db;
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
                    background: rgba(52, 152, 219, 0.1);
                    color: #3498db;
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

                /* Table Responsive Container */
                .table-responsive-container {
                    background: var(--white);
                    border-radius: 15px;
                    overflow: hidden;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                    margin-bottom: 30px;
                    overflow-x: auto;
                    position: relative;
                }

                .table-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px 30px;
                    border-bottom: 1px solid var(--gray);
                    position: sticky;
                    left: 0;
                    background: var(--white);
                    z-index: 10;
                    min-width: 1200px; /* Minimum width for header to match table */
                }

                .table-header h2 {
                    color: var(--primary);
                    margin: 0;
                    font-size: 1.5rem;
                    white-space: nowrap;
                }

                .table-actions {
                    display: flex;
                    gap: 15px;
                    white-space: nowrap;
                }

                /* Appointments Table */
                .appointments-table-wrapper {
                    overflow-x: auto;
                    max-height: 600px;
                    overflow-y: auto;
                    position: relative;
                }

                .appointments-table {
                    width: 100%;
                    border-collapse: collapse;
                    min-width: 1200px; /* Minimum width for table */
                }

                .appointments-table thead {
                    background: var(--light-gray);
                    position: sticky;
                    top: 0;
                    z-index: 20;
                }

                .appointments-table th {
                    padding: 18px 20px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    border-bottom: 2px solid var(--gray);
                    white-space: nowrap;
                    min-width: 150px;
                }

                .appointments-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.3s ease;
                }

                .appointments-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .appointments-table td {
                    padding: 15px 20px;
                    color: var(--black);
                    font-size: 0.95rem;
                    vertical-align: middle;
                    white-space: nowrap;
                    min-width: 150px;
                }

                /* Patient Avatar */
                .patient-avatar {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 3px solid var(--gray);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 1.2rem;
                    color: white;
                    background: var(--primary);
                    flex-shrink: 0;
                }

                .patient-avatar img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                /* Status Badges */
                .appointment-status {
                    display: inline-block;
                    padding: 6px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    white-space: nowrap;
                }

                .status-scheduled {
                    background: rgba(42, 92, 139, 0.1);
                    color: var(--primary);
                }

                .status-confirmed {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .status-completed {
                    background: rgba(26, 188, 156, 0.1);
                    color: var(--accent);
                }

                .status-pending {
                    background: rgba(243, 156, 18, 0.1);
                    color: var(--warning);
                }

                .status-cancelled {
                    background: rgba(231, 76, 60, 0.1);
                    color: var(--danger);
                }

                .status-no-show {
                    background: rgba(149, 165, 166, 0.1);
                    color: #7f8c8d;
                }

                /* Priority Indicators */
                .priority-indicator {
                    display: inline-block;
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    margin-right: 8px;
                    flex-shrink: 0;
                }

                .priority-high {
                    background: var(--danger);
                }

                .priority-medium {
                    background: var(--warning);
                }

                .priority-low {
                    background: var(--success);
                }

                /* Doctor Info */
                .doctor-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    white-space: nowrap;
                }

                .doctor-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    overflow: hidden;
                    border: 2px solid var(--gray);
                    flex-shrink: 0;
                }

                .doctor-avatar img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                /* Time Slot */
                .time-slot {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    white-space: nowrap;
                }

                .time-slot ion-icon {
                    color: var(--primary);
                    flex-shrink: 0;
                }

                /* Actions */
                .appointment-actions {
                    display: flex;
                    gap: 8px;
                    flex-wrap: nowrap;
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

                .action-icon.view {
                    background: rgba(41, 128, 185, 0.1);
                    color: var(--primary);
                }

                .action-icon.view:hover {
                    background: var(--primary);
                    color: white;
                    transform: translateY(-2px);
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

                .action-icon.checkin {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .action-icon.checkin:hover {
                    background: var(--success);
                    color: white;
                    transform: translateY(-2px);
                }

                .action-icon.message {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .action-icon.message:hover {
                    background: #9b59b6;
                    color: white;
                    transform: translateY(-2px);
                }

                .action-icon.prescription {
                    background: rgba(155, 89, 182, 0.1);
                    color: #9b59b6;
                }

                .action-icon.prescription:hover {
                    background: #9b59b6;
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
                    position: sticky;
                    left: 0;
                    background: var(--white);
                    min-width: 1200px; /* Minimum width for pagination to match table */
                }

                .pagination-info {
                    color: var(--dark-gray);
                    font-size: 0.9rem;
                    white-space: nowrap;
                }

                .pagination-controls {
                    display: flex;
                    gap: 10px;
                    white-space: nowrap;
                }

                .pagination-btn {
                    padding: 8px 15px;
                    background: var(--white);
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    white-space: nowrap;
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

                /* Scrollbar Styling */
                .appointments-table-wrapper::-webkit-scrollbar {
                    width: 8px;
                    height: 8px;
                }

                .appointments-table-wrapper::-webkit-scrollbar-track {
                    background: var(--light-gray);
                    border-radius: 4px;
                }

                .appointments-table-wrapper::-webkit-scrollbar-thumb {
                    background: var(--gray);
                    border-radius: 4px;
                }

                .appointments-table-wrapper::-webkit-scrollbar-thumb:hover {
                    background: var(--dark-gray);
                }

                /* Mobile Table Styles */
                .mobile-appointments-list {
                    display: none;
                    padding: 15px;
                }

                .mobile-appointment-card {
                    background: var(--white);
                    border-radius: 10px;
                    padding: 20px;
                    margin-bottom: 15px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    border-left: 4px solid var(--primary);
                }

                .mobile-appointment-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 15px;
                }

                .mobile-patient-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .mobile-appointment-details {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                    margin-bottom: 15px;
                }

                .mobile-detail-item {
                    display: flex;
                    flex-direction: column;
                }

                .mobile-detail-label {
                    font-size: 0.8rem;
                    color: var(--dark-gray);
                    margin-bottom: 3px;
                }

                .mobile-detail-value {
                    font-size: 0.9rem;
                    font-weight: 500;
                    color: var(--black);
                }

                .mobile-appointment-actions {
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                    padding-top: 15px;
                    border-top: 1px solid var(--light-gray);
                }

                /* Scroll indicators for mobile */
                .scroll-indicator {
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(0, 0, 0, 0.5);
                    color: white;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.2rem;
                    z-index: 30;
                    display: none;
                }

                .scroll-indicator.left {
                    left: 10px;
                    right: auto;
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
                    position: relative;
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
                    width: 100%;
                    box-sizing: border-box;
                    background-color: white;
                    cursor: pointer;
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

                /* Enhanced Dropdown Styles */
                .enhanced-dropdown {
                    position: relative;
                }

                .enhanced-dropdown select {
                    appearance: none;
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    padding-right: 40px;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
                    background-repeat: no-repeat;
                    background-position: right 12px center;
                    background-size: 16px;
                }

                .enhanced-dropdown select:focus {
                    border-color: var(--primary);
                    box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1);
                }

                .enhanced-dropdown select option {
                    padding: 12px;
                    font-size: 0.95rem;
                }

                .enhanced-dropdown select option:checked {
                    background-color: var(--primary);
                    color: white;
                }

                /* Expandable Dropdown */
                .expandable-dropdown {
                    transition: all 0.3s ease;
                }

                .expandable-dropdown:focus {
                    position: relative;
                    z-index: 100;
                    transform: translateY(-5px);
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                }

                /* Patient Search */
                .patient-search {
                    position: relative;
                }

                .patient-search-results {
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background: white;
                    border: 1px solid var(--gray);
                    border-radius: 8px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                    max-height: 200px;
                    overflow-y: auto;
                    z-index: 1000;
                    display: none;
                }

                .patient-search-result {
                    padding: 12px 15px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .patient-search-result:hover {
                    background: var(--light-gray);
                }

                /* Time Slots */
                .time-slots-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                    gap: 10px;
                    margin-top: 10px;
                }

                .time-slot-btn {
                    padding: 10px;
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    background: white;
                    cursor: pointer;
                    text-align: center;
                    transition: all 0.3s ease;
                }

                .time-slot-btn:hover {
                    border-color: var(--primary);
                    background: rgba(42, 92, 139, 0.05);
                }

                .time-slot-btn.selected {
                    background: var(--primary);
                    color: white;
                    border-color: var(--primary);
                }

                /* Duration Selector */
                .duration-selector {
                    display: flex;
                    gap: 10px;
                }

                .duration-option {
                    padding: 10px 15px;
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .duration-option:hover {
                    border-color: var(--primary);
                }

                .duration-option.selected {
                    background: var(--primary);
                    color: white;
                    border-color: var(--primary);
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

                /* View Details Modal */
                .view-details-modal .modal-content {
                    max-width: 700px;
                }

                .appointment-details {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin-bottom: 30px;
                }

                .patient-info, .appointment-info {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr;
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
                @media (max-width: 1200px) {
                    .modal-form {
                        grid-template-columns: 1fr;
                    }
                    
                    .appointment-details {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 992px) {
                    .appointments-stats {
                        grid-template-columns: repeat(3, 1fr);
                    }
                    
                    .table-header {
                        padding: 15px 20px;
                        flex-wrap: wrap;
                        gap: 10px;
                    }
                    
                    .table-actions {
                        flex-wrap: wrap;
                    }
                }

                @media (max-width: 768px) {
                    .appointments-management-section {
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

                    .appointments-stats {
                        grid-template-columns: repeat(2, 1fr);
                    }

                    .stat-card {
                        flex-direction: column;
                        text-align: center;
                        gap: 15px;
                    }

                    .table-header {
                        padding: 15px;
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 15px;
                    }

                    .table-actions {
                        width: 100%;
                        justify-content: flex-start;
                        flex-wrap: wrap;
                    }

                    /* Hide desktop table, show mobile list */
                    .appointments-table-wrapper {
                        display: none;
                    }
                    
                    .mobile-appointments-list {
                        display: block;
                    }
                    
                    .appointments-table {
                        min-width: 800px;
                    }
                    
                    .pagination {
                        padding: 15px;
                        flex-direction: column;
                        gap: 15px;
                        align-items: flex-start;
                    }

                    .action-btn {
                        padding: 8px 15px;
                        font-size: 0.9rem;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }

                    .appointment-actions {
                        flex-wrap: wrap;
                    }

                    .action-icon {
                        width: 30px;
                        height: 30px;
                        font-size: 1rem;
                    }
                }

                @media (max-width: 576px) {
                    .appointments-stats {
                        grid-template-columns: 1fr;
                    }

                    .filter-grid {
                        grid-template-columns: 1fr;
                    }

                    .filter-actions {
                        flex-direction: column;
                    }
                    
                    .mobile-appointment-details {
                        grid-template-columns: 1fr;
                    }
                    
                    .mobile-appointment-actions {
                        flex-wrap: wrap;
                    }
                    
                    .pagination-controls {
                        flex-wrap: wrap;
                    }
                }
            </style>

            <!-- ================== Appointments Management Content ============== -->
            <div class="appointments-management-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Appointments Management</h1>
                        <p>Schedule, manage, and track patient appointments</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn secondary" onclick="refreshAppointments()">
                            <ion-icon name="refresh-outline"></ion-icon>
                            Refresh
                        </button>
                        <button class="action-btn primary" onclick="openAddAppointmentModal()" id="addAppointmentBtn">
                            <ion-icon name="calendar-outline"></ion-icon>
                            Schedule Appointment
                        </button>
                    </div>
                </div>

                <!-- Appointments Statistics -->
                <div class="appointments-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalAppointments">48</h3>
                            <p>Total Today</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="confirmedAppointments">36</h3>
                            <p>Confirmed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="time-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="pendingAppointments">8</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="close-circle-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="cancelledAppointments">4</h3>
                            <p>Cancelled</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="person-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="waitingPatients">12</h3>
                            <p>Waiting Room</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <ion-icon name="medkit-outline"></ion-icon>
                        </div>
                        <div class="stat-content">
                            <h3 id="availableDoctors">8</h3>
                            <p>Available Doctors</p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <div class="search-box">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="searchInput" placeholder="Search by patient name, ID, or doctor..." 
                               onkeyup="filterAppointments()">
                    </div>
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="statusFilter" onchange="filterAppointments()" class="enhanced-dropdown">
                                <option value="">All Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="no-show">No Show</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Date Range</label>
                            <input type="date" id="dateFrom" onchange="filterAppointments()">
                        </div>
                        
                        <div class="filter-group">
                            <label>To</label>
                            <input type="date" id="dateTo" onchange="filterAppointments()">
                        </div>
                        
                        <div class="filter-group">
                            <label>Doctor</label>
                            <select id="doctorFilter" onchange="filterAppointments()" class="enhanced-dropdown">
                                <option value="">All Doctors</option>
                                <option value="dr_smith">Dr. Smith</option>
                                <option value="dr_johnson">Dr. Johnson</option>
                                <option value="dr_williams">Dr. Williams</option>
                                <option value="dr_brown">Dr. Brown</option>
                                <option value="dr_jones">Dr. Jones</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Department</label>
                            <select id="departmentFilter" onchange="filterAppointments()" class="enhanced-dropdown">
                                <option value="">All Departments</option>
                                <option value="general">General Medicine</option>
                                <option value="pediatrics">Pediatrics</option>
                                <option value="surgery">Surgery</option>
                                <option value="cardiology">Cardiology</option>
                                <option value="orthopedics">Orthopedics</option>
                                <option value="dermatology">Dermatology</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Priority</label>
                            <select id="priorityFilter" onchange="filterAppointments()" class="enhanced-dropdown">
                                <option value="">All Priorities</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
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

                <!-- Appointments Table -->
                <div class="table-responsive-container">
                    <div class="table-header">
                        <h2>Today's Appointments</h2>
                        <!-- Removed table-actions div containing Export CSV, Print, Send Reminders buttons -->
                        <div class="table-actions">
                            <!-- Buttons removed as requested -->
                        </div>
                    </div>
                    
                    <!-- Desktop Table View -->
                    <div class="appointments-table-wrapper">
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>PATIENT</th>
                                    <th>APPOINTMENT DETAILS</th>
                                    <th>DOCTOR</th>
                                    <th>STATUS</th>
                                    <th>TIME SLOT</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="appointmentsTableBody">
                                <!-- Appointments will be populated here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div class="mobile-appointments-list" id="mobileAppointmentsList">
                        <!-- Mobile cards will be populated by JavaScript -->
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="pagination-info" id="paginationInfo">
                            Showing 1 to 10 of 48 entries
                        </div>
                        <div class="pagination-controls">
                            <button class="pagination-btn" onclick="changePage('prev')" disabled>
                                <ion-icon name="chevron-back-outline"></ion-icon>
                            </button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                            <button class="pagination-btn">4</button>
                            <button class="pagination-btn">5</button>
                            <button class="pagination-btn" onclick="changePage('next')">
                                <ion-icon name="chevron-forward-outline"></ion-icon>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Appointment Modal -->
            <div class="modal-overlay" id="appointmentModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Schedule New Appointment</h3>
                        <button class="modal-close" onclick="closeAppointmentModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <form id="appointmentForm" class="modal-form">
                            <input type="hidden" id="appointmentId">
                            
                            <!-- Patient Selection -->
                            <div class="form-group full-width">
                                <label for="patientSearch" class="required">Patient</label>
                                <div class="patient-search">
                                    <input type="text" id="patientSearch" placeholder="Search for patient by name or ID..." required>
                                    <div class="patient-search-results" id="patientResults">
                                        <!-- Search results will appear here -->
                                    </div>
                                </div>
                                <input type="hidden" id="patientId">
                                <input type="hidden" id="patientName">
                            </div>
                            
                            <div class="form-group">
                                <label for="patientAge">Age</label>
                                <input type="number" id="patientAge" placeholder="Auto-filled" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="patientGender">Gender</label>
                                <input type="text" id="patientGender" placeholder="Auto-filled" readonly>
                            </div>
                            
                            <!-- Appointment Details -->
                            <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px; color: var(--primary);">Appointment Details</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="appointmentDate" class="required">Date</label>
                                <input type="date" id="appointmentDate" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="appointmentTime" class="required">Time</label>
                                <select id="appointmentTime" class="enhanced-dropdown expandable-dropdown" required>
                                    <option value="">-- select time --</option>
                                    <option value="08:00">08:00 AM</option>
                                    <option value="08:30">08:30 AM</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="09:30">09:30 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="10:30">10:30 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="11:30">11:30 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="12:30">12:30 PM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="13:30">01:30 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                    <option value="14:30">02:30 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="15:30">03:30 PM</option>
                                    <option value="16:00">04:00 PM</option>
                                    <option value="16:30">04:30 PM</option>
                                    <option value="17:00">05:00 PM</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="duration" class="required">Duration</label>
                                <div class="duration-selector">
                                    <span class="duration-option" data-minutes="15">15 min</span>
                                    <span class="duration-option selected" data-minutes="30">30 min</span>
                                    <span class="duration-option" data-minutes="45">45 min</span>
                                    <span class="duration-option" data-minutes="60">60 min</span>
                                </div>
                                <input type="hidden" id="duration" value="30">
                            </div>
                            
                            <div class="form-group">
                                <label for="priority" class="required">Priority</label>
                                <select id="priority" class="enhanced-dropdown expandable-dropdown" required>
                                    <option value="">-- select priority --</option>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            
                            <!-- Doctor -->
                            <div class="form-group">
                                <label for="doctor" class="required">Doctor</label>
                                <input type="text" id="doctor" placeholder="Enter doctor's name" required>
                            </div>
                            
                            <!-- Appointment Type -->
                            <div class="form-group">
                                <label for="appointmentType" class="required">Appointment Type</label>
                                <select id="appointmentType" class="enhanced-dropdown expandable-dropdown" required>
                                    <option value="">-- select appointment type --</option>
                                    <option value="consultation">Consultation</option>
                                    <option value="followup">Follow-up</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="routine">Routine Check-up</option>
                                    <option value="vaccination">Vaccination</option>
                                    <option value="test">Lab Test</option>
                                    <option value="procedure">Procedure</option>
                                    <option value="surgery">Surgery</option>
                                </select>
                            </div>
                            
                            <!-- Reason & Symptoms -->
                            <div class="form-group full-width">
                                <label for="reason" class="required">Reason for Visit</label>
                                <textarea id="reason" placeholder="Describe symptoms or reason for appointment..." required></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="symptoms">Symptoms</label>
                                <textarea id="symptoms" placeholder="List any symptoms..."></textarea>
                            </div>
                            
                            <!-- Insurance & Payment -->
                            <div class="form-group">
                                <label for="insurance">Insurance Provider</label>
                                <input type="text" id="insurance" placeholder="e.g., NHIMA">
                            </div>
                            
                            <div class="form-group">
                                <label for="insuranceNumber">Insurance Number</label>
                                <input type="text" id="insuranceNumber" placeholder="Policy number">
                            </div>
                            
                            <div class="form-group">
                                <label for="paymentStatus">Payment Status</label>
                                <select id="paymentStatus" class="enhanced-dropdown expandable-dropdown">
                                    <option value="">-- select payment status --</option>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="insurance">Insurance</option>
                                    <option value="partial">Partial</option>
                                    <option value="waived">Waived</option>
                                </select>
                            </div>
                            
                            <!-- Status -->
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" class="enhanced-dropdown expandable-dropdown">
                                    <option value="">-- select status --</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            
                            <!-- Notes -->
                            <div class="form-group full-width">
                                <label for="notes">Notes</label>
                                <textarea id="notes" placeholder="Additional notes for the appointment..."></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeAppointmentModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveAppointment()">
                            <ion-icon name="save-outline"></ion-icon>
                            Save Appointment
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Appointment Details Modal -->
            <div class="modal-overlay view-details-modal" id="viewAppointmentModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="viewModalTitle">Appointment Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="appointment-details">
                            <div class="patient-info">
                                <h4 style="color: var(--primary); margin-bottom: 10px;">Patient Information</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Patient Name:</span>
                                        <span class="info-value" id="viewPatientName">John Doe</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Patient ID:</span>
                                        <span class="info-value" id="viewPatientId">PAT-001234</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Age & Gender:</span>
                                        <span class="info-value" id="viewPatientAgeGender">45, Male</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Phone:</span>
                                        <span class="info-value" id="viewPatientPhone">+265 123 456 789</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value" id="viewPatientEmail">john.doe@email.com</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Address:</span>
                                        <span class="info-value" id="viewPatientAddress">123 Main St, Lilongwe</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="appointment-info">
                                <h4 style="color: var(--primary); margin-bottom: 10px;">Appointment Information</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Appointment ID:</span>
                                        <span class="info-value" id="viewAppointmentId">APT-20231223-001</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Date & Time:</span>
                                        <span class="info-value" id="viewDateTime">Dec 23, 2023 - 10:00 AM</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Duration:</span>
                                        <span class="info-value" id="viewDuration">30 minutes</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Doctor:</span>
                                        <span class="info-value" id="viewDoctor">Dr. John Smith</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Type:</span>
                                        <span class="info-value" id="viewAppointmentType">Consultation</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value" id="viewStatus">Confirmed</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Priority:</span>
                                        <span class="info-value" id="viewPriority">Medium</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Payment Status:</span>
                                        <span class="info-value" id="viewPaymentStatus">Paid</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Medical Information -->
                        <div class="form-group full-width" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray);">
                            <h4 style="margin-bottom: 15px; color: var(--primary);">Medical Information</h4>
                            <div class="info-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="info-item">
                                    <span class="info-label">Reason for Visit:</span>
                                    <div style="padding: 12px; background: var(--light-gray); border-radius: 8px; margin-top: 5px;" id="viewReason">
                                        Routine check-up and follow-up for hypertension
                                    </div>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Symptoms:</span>
                                    <div style="padding: 12px; background: var(--light-gray); border-radius: 8px; margin-top: 5px;" id="viewSymptoms">
                                        Mild headache, occasional dizziness
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Insurance Information -->
                        <div class="form-group full-width">
                            <h4 style="margin-bottom: 15px; color: var(--primary);">Insurance Information</h4>
                            <div class="info-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="info-item">
                                    <span class="info-label">Insurance Provider:</span>
                                    <span class="info-value" id="viewInsurance">NHIMA</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Insurance Number:</span>
                                    <span class="info-value" id="viewInsuranceNumber">NH-789456123</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="form-group full-width">
                            <label>Notes:</label>
                            <div style="padding: 12px; background: var(--light-gray); border-radius: 8px; margin-top: 5px;" id="viewNotes">
                                Patient requires regular monitoring for hypertension. Last check-up was 3 months ago.
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <!-- Only Close button remains -->
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="deleteAppointmentModal">
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
                        <h4>Delete Appointment</h4>
                        <p>Are you sure you want to delete this appointment? This action cannot be undone.</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeDeleteModal()">
                                Cancel
                            </button>
                            <button class="action-btn danger" onclick="confirmDelete()">
                                <ion-icon name="trash-outline"></ion-icon>
                                Delete Appointment
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Check-in Confirmation Modal -->
            <div class="modal-overlay confirmation-modal" id="checkInModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Patient Check-in</h3>
                        <button class="modal-close" onclick="closeCheckInModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="confirmation-body">
                        <div class="confirmation-icon success">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        </div>
                        <h4>Check-in Patient</h4>
                        <p>Mark <strong id="checkInPatientName">[Patient Name]</strong> as checked-in for their appointment?</p>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button class="action-btn secondary" onclick="closeCheckInModal()">
                                Cancel
                            </button>
                            <button class="action-btn success" onclick="confirmCheckIn()">
                                <ion-icon name="checkmark-circle-outline"></ion-icon>
                                Confirm Check-in
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Removed Select2 CDN links -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <script>
                // Sample Appointments Data
                const appointments = [
                    {
                        id: 1,
                        patientId: "PAT-001234",
                        patientName: "John Doe",
                        patientAge: 45,
                        patientGender: "Male",
                        patientPhone: "+265 123 456 789",
                        patientEmail: "john.doe@email.com",
                        patientAddress: "123 Main St, Lilongwe",
                        appointmentId: "APT-20231223-001",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "10:00",
                        timeDisplay: "10:00 AM",
                        duration: 30,
                        durationDisplay: "30 minutes",
                        doctor: "Dr. John Smith",
                        doctorId: "dr_smith",
                        department: "General Medicine",
                        departmentId: "general",
                        appointmentType: "Consultation",
                        room: "Room 101",
                        status: "confirmed",
                        statusText: "Confirmed",
                        priority: "medium",
                        priorityText: "Medium",
                        reason: "Routine check-up and follow-up for hypertension",
                        symptoms: "Mild headache, occasional dizziness",
                        insurance: "NHIMA",
                        insuranceNumber: "NH-789456123",
                        paymentStatus: "paid",
                        paymentStatusText: "Paid",
                        notes: "Patient requires regular monitoring for hypertension. Last check-up was 3 months ago.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: true,
                        createdAt: "2023-12-20T09:30:00"
                    },
                    {
                        id: 2,
                        patientId: "PAT-002345",
                        patientName: "Mary Johnson",
                        patientAge: 32,
                        patientGender: "Female",
                        patientPhone: "+265 234 567 890",
                        patientEmail: "mary.johnson@email.com",
                        patientAddress: "456 Lake Rd, Blantyre",
                        appointmentId: "APT-20231223-002",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "10:30",
                        timeDisplay: "10:30 AM",
                        duration: 45,
                        durationDisplay: "45 minutes",
                        doctor: "Dr. Sarah Johnson",
                        doctorId: "dr_johnson",
                        department: "Gynecology",
                        departmentId: "gynecology",
                        appointmentType: "Follow-up",
                        room: "Room 205",
                        status: "confirmed",
                        statusText: "Confirmed",
                        priority: "medium",
                        priorityText: "Medium",
                        reason: "Postnatal check-up",
                        symptoms: "Routine follow-up",
                        insurance: "Private",
                        insuranceNumber: "PRV-456789",
                        paymentStatus: "paid",
                        paymentStatusText: "Paid",
                        notes: "6 weeks postnatal check-up. Normal delivery.",
                        checkedIn: true,
                        checkInTime: "2023-12-23T10:15:00",
                        reminderSent: true,
                        createdAt: "2023-12-18T14:20:00"
                    },
                    {
                        id: 3,
                        patientId: "PAT-003456",
                        patientName: "Robert Brown",
                        patientAge: 58,
                        patientGender: "Male",
                        patientPhone: "+265 345 678 901",
                        patientEmail: "robert.brown@email.com",
                        patientAddress: "789 Hill View, Mzuzu",
                        appointmentId: "APT-20231223-003",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "11:00",
                        timeDisplay: "11:00 AM",
                        duration: 60,
                        durationDisplay: "60 minutes",
                        doctor: "Dr. Michael Williams",
                        doctorId: "dr_williams",
                        department: "Cardiology",
                        departmentId: "cardiology",
                        appointmentType: "Consultation",
                        room: "Room 302",
                        status: "scheduled",
                        statusText: "Scheduled",
                        priority: "high",
                        priorityText: "High",
                        reason: "Chest pain and shortness of breath",
                        symptoms: "Chest pain, shortness of breath, fatigue",
                        insurance: "NHIMA",
                        insuranceNumber: "NH-123456789",
                        paymentStatus: "insurance",
                        paymentStatusText: "Insurance",
                        notes: "History of hypertension. Referred from general medicine.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: true,
                        createdAt: "2023-12-22T11:45:00"
                    },
                    {
                        id: 4,
                        patientId: "PAT-004567",
                        patientName: "Sarah Wilson",
                        patientAge: 28,
                        patientGender: "Female",
                        patientPhone: "+265 456 789 012",
                        patientEmail: "sarah.wilson@email.com",
                        patientAddress: "321 Valley Rd, Zomba",
                        appointmentId: "APT-20231223-004",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "11:30",
                        timeDisplay: "11:30 AM",
                        duration: 30,
                        durationDisplay: "30 minutes",
                        doctor: "Dr. Emily Brown",
                        doctorId: "dr_brown",
                        department: "Pediatrics",
                        departmentId: "pediatrics",
                        appointmentType: "Vaccination",
                        room: "Room 105",
                        status: "pending",
                        statusText: "Pending",
                        priority: "low",
                        priorityText: "Low",
                        reason: "Child vaccination - 6 months",
                        symptoms: "Routine vaccination",
                        insurance: "Private",
                        insuranceNumber: "PRV-789123",
                        paymentStatus: "pending",
                        paymentStatusText: "Pending",
                        notes: "Bringing 6-month old child for routine vaccinations.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: false,
                        createdAt: "2023-12-21T16:30:00"
                    },
                    {
                        id: 5,
                        patientId: "PAT-005678",
                        patientName: "David Miller",
                        patientAge: 65,
                        patientGender: "Male",
                        patientPhone: "+265 567 890 123",
                        patientEmail: "david.miller@email.com",
                        patientAddress: "654 Mountain View, Karonga",
                        appointmentId: "APT-20231223-005",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "13:00",
                        timeDisplay: "01:00 PM",
                        duration: 45,
                        durationDisplay: "45 minutes",
                        doctor: "Dr. David Jones",
                        doctorId: "dr_jones",
                        department: "Orthopedics",
                        departmentId: "orthopedics",
                        appointmentType: "Follow-up",
                        room: "Room 208",
                        status: "confirmed",
                        statusText: "Confirmed",
                        priority: "medium",
                        priorityText: "Medium",
                        reason: "Knee surgery follow-up",
                        symptoms: "Knee pain, limited mobility",
                        insurance: "NHIMA",
                        insuranceNumber: "NH-987654321",
                        paymentStatus: "paid",
                        paymentStatusText: "Paid",
                        notes: "Post-op check-up after knee replacement surgery.",
                        checkedIn: true,
                        checkInTime: "2023-12-23T12:45:00",
                        reminderSent: true,
                        createdAt: "2023-12-19T10:15:00"
                    },
                    {
                        id: 6,
                        patientId: "PAT-006789",
                        patientName: "Lisa Garcia",
                        patientAge: 40,
                        patientGender: "Female",
                        patientPhone: "+265 678 901 234",
                        patientEmail: "lisa.garcia@email.com",
                        patientAddress: "987 Beach Rd, Mangochi",
                        appointmentId: "APT-20231223-006",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "13:30",
                        timeDisplay: "01:30 PM",
                        duration: 30,
                        durationDisplay: "30 minutes",
                        doctor: "Dr. Lisa Miller",
                        doctorId: "dr_miller",
                        department: "Dermatology",
                        departmentId: "dermatology",
                        appointmentType: "Consultation",
                        room: "Room 304",
                        status: "cancelled",
                        statusText: "Cancelled",
                        priority: "low",
                        priorityText: "Low",
                        reason: "Skin rash consultation",
                        symptoms: "Red rash on arms and legs, itching",
                        insurance: "Private",
                        insuranceNumber: "PRV-456123",
                        paymentStatus: "cancelled",
                        paymentStatusText: "Cancelled",
                        notes: "Patient called to cancel due to travel.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: true,
                        createdAt: "2023-12-17T15:45:00"
                    },
                    {
                        id: 7,
                        patientId: "PAT-007890",
                        patientName: "Thomas Anderson",
                        patientAge: 50,
                        patientGender: "Male",
                        patientPhone: "+265 789 012 345",
                        patientEmail: "thomas.anderson@email.com",
                        patientAddress: "147 Garden Ave, Salima",
                        appointmentId: "APT-20231223-007",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "14:00",
                        timeDisplay: "02:00 PM",
                        duration: 60,
                        durationDisplay: "60 minutes",
                        doctor: "Dr. Robert Davis",
                        doctorId: "dr_davis",
                        department: "Surgery",
                        departmentId: "surgery",
                        appointmentType: "Consultation",
                        room: "Room 401",
                        status: "pending",
                        statusText: "Pending",
                        priority: "high",
                        priorityText: "High",
                        reason: "Appendicitis evaluation",
                        symptoms: "Abdominal pain, fever, nausea",
                        insurance: "NHIMA",
                        insuranceNumber: "NH-654789123",
                        paymentStatus: "pending",
                        paymentStatusText: "Pending",
                        notes: "Possible emergency surgery required.",
                        checkedIn: false,
                        checkInTime: null,
                        reminderSent: false,
                        createdAt: "2023-12-22T20:15:00"
                    },
                    {
                        id: 8,
                        patientId: "PAT-008901",
                        patientName: "Emily Wilson",
                        patientAge: 35,
                        patientGender: "Female",
                        patientPhone: "+265 890 123 456",
                        patientEmail: "emily.wilson@email.com",
                        patientAddress: "258 River Side, Balaka",
                        appointmentId: "APT-20231223-008",
                        appointmentDate: "2023-12-23",
                        appointmentTime: "14:30",
                        timeDisplay: "02:30 PM",
                        duration: 45,
                        durationDisplay: "45 minutes",
                        doctor: "Dr. Maria Garcia",
                        doctorId: "dr_garcia",
                        department: "Ophthalmology",
                        departmentId: "ophthalmology",
                        appointmentType: "Test",
                        room: "Room 306",
                        status: "confirmed",
                        statusText: "Confirmed",
                        priority: "medium",
                        priorityText: "Medium",
                        reason: "Eye examination and vision test",
                        symptoms: "Blurred vision, eye strain",
                        insurance: "Private",
                        insuranceNumber: "PRV-789456",
                        paymentStatus: "paid",
                        paymentStatusText: "Paid",
                        notes: "Follow-up after recent eye surgery.",
                        checkedIn: true,
                        checkInTime: "2023-12-23T14:20:00",
                        reminderSent: true,
                        createdAt: "2023-12-20T11:30:00"
                    }
                ];

                // Sample Patients for Search
                const patients = [
                    { id: 1, patientId: "PAT-001234", name: "John Doe", age: 45, gender: "Male", phone: "+265 123 456 789" },
                    { id: 2, patientId: "PAT-002345", name: "Mary Johnson", age: 32, gender: "Female", phone: "+265 234 567 890" },
                    { id: 3, patientId: "PAT-003456", name: "Robert Brown", age: 58, gender: "Male", phone: "+265 345 678 901" },
                    { id: 4, patientId: "PAT-004567", name: "Sarah Wilson", age: 28, gender: "Female", phone: "+265 456 789 012" },
                    { id: 5, patientId: "PAT-005678", name: "David Miller", age: 65, gender: "Male", phone: "+265 567 890 123" },
                    { id: 6, patientId: "PAT-006789", name: "Lisa Garcia", age: 40, gender: "Female", phone: "+265 678 901 234" },
                    { id: 7, patientId: "PAT-007890", name: "Thomas Anderson", age: 50, gender: "Male", phone: "+265 789 012 345" },
                    { id: 8, patientId: "PAT-008901", name: "Emily Wilson", age: 35, gender: "Female", phone: "+265 890 123 456" },
                    { id: 9, patientId: "PAT-009012", name: "James Taylor", age: 42, gender: "Male", phone: "+265 901 234 567" },
                    { id: 10, patientId: "PAT-010123", name: "Maria Rodriguez", age: 29, gender: "Female", phone: "+265 012 345 678" }
                ];

                // Initialize Variables
                let currentPage = 1;
                const itemsPerPage = 10;
                let filteredAppointments = [...appointments];
                let appointmentToDelete = null;
                let appointmentToView = null;
                let appointmentToCheckIn = null;

                // DOM Elements
                const tableBody = document.getElementById('appointmentsTableBody');
                const mobileList = document.getElementById('mobileAppointmentsList');
                const appointmentModal = document.getElementById('appointmentModal');
                const viewAppointmentModal = document.getElementById('viewAppointmentModal');
                const deleteAppointmentModal = document.getElementById('deleteAppointmentModal');
                const checkInModal = document.getElementById('checkInModal');

                // Initialize Page
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM loaded - Initializing appointments management');
                    
                    // Set default date to today
                    const appointmentDateInput = document.getElementById('appointmentDate');
                    if (appointmentDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        appointmentDateInput.value = today;
                        appointmentDateInput.min = today;
                    }
                    
                    // Set default date filters to today
                    const dateFromInput = document.getElementById('dateFrom');
                    const dateToInput = document.getElementById('dateTo');
                    if (dateFromInput && dateToInput) {
                        dateFromInput.value = new Date().toISOString().split('T')[0];
                        dateToInput.value = new Date().toISOString().split('T')[0];
                    }
                    
                    // Initialize duration selector
                    initializeDurationSelector();
                    
                    // Initialize patient search
                    initializePatientSearch();
                    
                    // Initialize enhanced dropdowns
                    initializeEnhancedDropdowns();
                    
                    // Initialize table and statistics
                    renderAppointmentsTable();
                    updateStatistics();
                    
                    // Check screen size and adjust view
                    checkScreenSize();
                    window.addEventListener('resize', checkScreenSize);
                    
                    // Add event listeners for modals
                    document.querySelectorAll('.modal-overlay').forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('active');
                            }
                        });
                    });
                    
                    // Add event listener for add appointment button
                    const addAppointmentBtn = document.getElementById('addAppointmentBtn');
                    if (addAppointmentBtn) {
                        addAppointmentBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            e.preventDefault();
                            openAddAppointmentModal();
                        });
                    }
                });

                // Initialize Enhanced Dropdowns
                function initializeEnhancedDropdowns() {
                    // Add event listeners to all enhanced dropdowns
                    const enhancedDropdowns = document.querySelectorAll('.enhanced-dropdown select');
                    
                    enhancedDropdowns.forEach(dropdown => {
                        // Add focus effect
                        dropdown.addEventListener('focus', function() {
                            if (this.classList.contains('expandable-dropdown')) {
                                this.size = Math.min(this.options.length, 8); // Show up to 8 options
                            }
                        });
                        
                        // Add blur effect
                        dropdown.addEventListener('blur', function() {
                            if (this.classList.contains('expandable-dropdown')) {
                                this.size = 1;
                            }
                        });
                        
                        // Add change effect
                        dropdown.addEventListener('change', function() {
                            if (this.classList.contains('expandable-dropdown')) {
                                this.size = 1;
                            }
                        });
                        
                        // Add hover effect
                        dropdown.addEventListener('mouseenter', function() {
                            this.style.borderColor = 'var(--primary)';
                        });
                        
                        dropdown.addEventListener('mouseleave', function() {
                            if (!this.matches(':focus')) {
                                this.style.borderColor = 'var(--gray)';
                            }
                        });
                    });
                }

                // Check screen size and adjust view
                function checkScreenSize() {
                    const isMobile = window.innerWidth <= 768;
                    const tableWrapper = document.querySelector('.appointments-table-wrapper');
                    const mobileList = document.querySelector('.mobile-appointments-list');
                    
                    if (isMobile) {
                        if (tableWrapper) tableWrapper.style.display = 'none';
                        if (mobileList) mobileList.style.display = 'block';
                    } else {
                        if (tableWrapper) tableWrapper.style.display = 'block';
                        if (mobileList) mobileList.style.display = 'none';
                    }
                }

                // Initialize Duration Selector
                function initializeDurationSelector() {
                    const durationOptions = document.querySelectorAll('.duration-option');
                    durationOptions.forEach(option => {
                        option.addEventListener('click', function() {
                            // Remove selected class from all options
                            durationOptions.forEach(opt => opt.classList.remove('selected'));
                            
                            // Add selected class to clicked option
                            this.classList.add('selected');
                            
                            // Update hidden input value
                            const minutes = this.getAttribute('data-minutes');
                            document.getElementById('duration').value = minutes;
                        });
                    });
                }

                // Initialize Patient Search
                function initializePatientSearch() {
                    const patientSearchInput = document.getElementById('patientSearch');
                    const patientResults = document.getElementById('patientResults');
                    
                    if (!patientSearchInput || !patientResults) return;
                    
                    patientSearchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        
                        if (searchTerm.length < 2) {
                            patientResults.style.display = 'none';
                            return;
                        }
                        
                        // Filter patients
                        const filteredPatients = patients.filter(patient => 
                            patient.name.toLowerCase().includes(searchTerm) ||
                            patient.patientId.toLowerCase().includes(searchTerm)
                        );
                        
                        // Display results
                        if (filteredPatients.length > 0) {
                            patientResults.innerHTML = filteredPatients.map(patient => `
                                <div class="patient-search-result" 
                                     data-id="${patient.id}"
                                     data-patient-id="${patient.patientId}"
                                     data-name="${patient.name}"
                                     data-age="${patient.age}"
                                     data-gender="${patient.gender}">
                                    <div class="patient-avatar">${patient.name.split(' ').map(n => n[0]).join('')}</div>
                                    <div>
                                        <strong>${patient.name}</strong><br>
                                        <small>${patient.patientId} • ${patient.age} yrs • ${patient.gender}</small>
                                    </div>
                                </div>
                            `).join('');
                            
                            patientResults.style.display = 'block';
                            
                            // Add click event listeners to results
                            document.querySelectorAll('.patient-search-result').forEach(result => {
                                result.addEventListener('click', function() {
                                    const patientId = this.getAttribute('data-patient-id');
                                    const patientName = this.getAttribute('data-name');
                                    const patientAge = this.getAttribute('data-age');
                                    const patientGender = this.getAttribute('data-gender');
                                    
                                    // Update form fields
                                    document.getElementById('patientSearch').value = `${patientName} (${patientId})`;
                                    document.getElementById('patientId').value = patientId;
                                    document.getElementById('patientName').value = patientName;
                                    document.getElementById('patientAge').value = patientAge;
                                    document.getElementById('patientGender').value = patientGender;
                                    
                                    // Hide results
                                    patientResults.style.display = 'none';
                                });
                            });
                        } else {
                            patientResults.innerHTML = '<div class="patient-search-result">No patients found</div>';
                            patientResults.style.display = 'block';
                        }
                    });
                    
                    // Hide results when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!patientSearchInput.contains(e.target) && !patientResults.contains(e.target)) {
                            patientResults.style.display = 'none';
                        }
                    });
                }

                // Modal Functions
                function openAddAppointmentModal() {
                    console.log('Opening Add Appointment Modal');
                    
                    document.getElementById('modalTitle').textContent = 'Schedule New Appointment';
                    
                    // Reset the form
                    const form = document.getElementById('appointmentForm');
                    if (form) {
                        form.reset();
                    }
                    
                    // Clear appointment ID
                    document.getElementById('appointmentId').value = '';
                    
                    // Reset patient fields
                    document.getElementById('patientSearch').value = '';
                    document.getElementById('patientId').value = '';
                    document.getElementById('patientName').value = '';
                    document.getElementById('patientAge').value = '';
                    document.getElementById('patientGender').value = '';
                    
                    // Reset doctor field
                    document.getElementById('doctor').value = '';
                    
                    // Reset other fields
                    document.getElementById('reason').value = '';
                    document.getElementById('symptoms').value = '';
                    document.getElementById('insurance').value = '';
                    document.getElementById('insuranceNumber').value = '';
                    document.getElementById('notes').value = '';
                    
                    // Reset dropdowns to default values
                    document.getElementById('appointmentType').value = '';
                    document.getElementById('paymentStatus').value = '';
                    document.getElementById('status').value = 'scheduled';
                    document.getElementById('priority').value = 'medium';
                    document.getElementById('appointmentTime').value = '';
                    
                    // Set default date to today
                    const appointmentDateInput = document.getElementById('appointmentDate');
                    if (appointmentDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        appointmentDateInput.value = today;
                    }
                    
                    // Set default time to next available slot
                    const now = new Date();
                    const currentHour = now.getHours();
                    const nextHour = currentHour < 17 ? (currentHour + 1) : 9;
                    const nextTime = `${nextHour.toString().padStart(2, '0')}:00`;
                    document.getElementById('appointmentTime').value = nextTime;
                    
                    // Reset duration selector
                    document.querySelectorAll('.duration-option').forEach(option => {
                        option.classList.remove('selected');
                        if (option.getAttribute('data-minutes') === '30') {
                            option.classList.add('selected');
                        }
                    });
                    document.getElementById('duration').value = '30';
                    
                    // Generate appointment ID
                    document.getElementById('appointmentId').value = generateAppointmentId();
                    
                    // Focus on patient search field
                    setTimeout(() => {
                        document.getElementById('patientSearch').focus();
                    }, 100);
                    
                    // Show modal
                    if (appointmentModal) {
                        appointmentModal.classList.add('active');
                    }
                    
                    return false;
                }

                function generateAppointmentId() {
                    const date = new Date();
                    const year = date.getFullYear();
                    const month = (date.getMonth() + 1).toString().padStart(2, '0');
                    const day = date.getDate().toString().padStart(2, '0');
                    const random = Math.floor(100 + Math.random() * 900);
                    return `APT-${year}${month}${day}-${random}`;
                }

                function editAppointment(id) {
                    console.log('Edit Appointment clicked for ID:', id);
                    
                    const appointment = appointments.find(a => a.id === id);
                    if (!appointment) {
                        console.error('Appointment not found with ID:', id);
                        return;
                    }

                    document.getElementById('modalTitle').textContent = 'Edit Appointment';
                    
                    // Fill form with appointment data
                    document.getElementById('appointmentId').value = appointment.appointmentId;
                    document.getElementById('patientSearch').value = `${appointment.patientName} (${appointment.patientId})`;
                    document.getElementById('patientId').value = appointment.patientId;
                    document.getElementById('patientName').value = appointment.patientName;
                    document.getElementById('patientAge').value = appointment.patientAge;
                    document.getElementById('patientGender').value = appointment.patientGender;
                    document.getElementById('appointmentDate').value = appointment.appointmentDate;
                    document.getElementById('appointmentTime').value = appointment.appointmentTime;
                    document.getElementById('reason').value = appointment.reason;
                    document.getElementById('symptoms').value = appointment.symptoms;
                    document.getElementById('insurance').value = appointment.insurance;
                    document.getElementById('insuranceNumber').value = appointment.insuranceNumber;
                    document.getElementById('notes').value = appointment.notes;
                    
                    // Fill doctor field
                    document.getElementById('doctor').value = appointment.doctor;
                    
                    // Set dropdown values
                    document.getElementById('appointmentType').value = appointment.appointmentType;
                    document.getElementById('paymentStatus').value = appointment.paymentStatus;
                    document.getElementById('status').value = appointment.status;
                    document.getElementById('priority').value = appointment.priority;
                    
                    // Set duration selector
                    document.querySelectorAll('.duration-option').forEach(option => {
                        option.classList.remove('selected');
                        if (option.getAttribute('data-minutes') === appointment.duration.toString()) {
                            option.classList.add('selected');
                        }
                    });
                    document.getElementById('duration').value = appointment.duration;
                    
                    // Focus on first field
                    setTimeout(() => {
                        document.getElementById('patientSearch').focus();
                    }, 100);
                    
                    // Show modal
                    if (appointmentModal) {
                        appointmentModal.classList.add('active');
                    }
                }

                function viewAppointment(id) {
                    console.log('View Appointment clicked for ID:', id);
                    
                    const appointment = appointments.find(a => a.id === id);
                    if (!appointment) {
                        console.error('Appointment not found with ID:', id);
                        return;
                    }

                    appointmentToView = appointment;
                    
                    // Fill view modal with appointment data
                    document.getElementById('viewModalTitle').textContent = `Appointment: ${appointment.appointmentId}`;
                    document.getElementById('viewPatientName').textContent = appointment.patientName;
                    document.getElementById('viewPatientId').textContent = appointment.patientId;
                    document.getElementById('viewPatientAgeGender').textContent = `${appointment.patientAge} yrs, ${appointment.patientGender}`;
                    document.getElementById('viewPatientPhone').textContent = appointment.patientPhone;
                    document.getElementById('viewPatientEmail').textContent = appointment.patientEmail;
                    document.getElementById('viewPatientAddress').textContent = appointment.patientAddress;
                    document.getElementById('viewAppointmentId').textContent = appointment.appointmentId;
                    
                    // Format date and time
                    const appDate = new Date(appointment.appointmentDate);
                    const dateStr = appDate.toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    document.getElementById('viewDateTime').textContent = `${dateStr} - ${appointment.timeDisplay}`;
                    
                    document.getElementById('viewDuration').textContent = appointment.durationDisplay;
                    document.getElementById('viewDoctor').textContent = appointment.doctor;
                    document.getElementById('viewAppointmentType').textContent = appointment.appointmentType;
                    
                    // Status with badge
                    const statusElement = document.getElementById('viewStatus');
                    statusElement.innerHTML = `<span class="appointment-status status-${appointment.status}">${appointment.statusText}</span>`;
                    
                    // Priority with indicator
                    const priorityElement = document.getElementById('viewPriority');
                    priorityElement.innerHTML = `
                        <span class="priority-indicator priority-${appointment.priority}"></span>
                        ${appointment.priorityText}
                    `;
                    
                    document.getElementById('viewPaymentStatus').textContent = appointment.paymentStatusText;
                    document.getElementById('viewReason').textContent = appointment.reason;
                    document.getElementById('viewSymptoms').textContent = appointment.symptoms;
                    document.getElementById('viewInsurance').textContent = appointment.insurance;
                    document.getElementById('viewInsuranceNumber').textContent = appointment.insuranceNumber;
                    document.getElementById('viewNotes').textContent = appointment.notes;
                    
                    // Show modal
                    if (viewAppointmentModal) {
                        viewAppointmentModal.classList.add('active');
                    }
                }

                function closeAppointmentModal() {
                    if (appointmentModal) {
                        appointmentModal.classList.remove('active');
                    }
                }

                function closeViewModal() {
                    if (viewAppointmentModal) {
                        viewAppointmentModal.classList.remove('active');
                    }
                }

                // Save Appointment Function
                function saveAppointment() {
                    // Get form values
                    const appointmentId = document.getElementById('appointmentId').value;
                    const patientId = document.getElementById('patientId').value;
                    const patientName = document.getElementById('patientName').value;
                    const patientAge = document.getElementById('patientAge').value;
                    const patientGender = document.getElementById('patientGender').value;
                    const appointmentDate = document.getElementById('appointmentDate').value;
                    const appointmentTime = document.getElementById('appointmentTime').value;
                    const duration = document.getElementById('duration').value;
                    const priority = document.getElementById('priority').value;
                    const doctor = document.getElementById('doctor').value;
                    const appointmentType = document.getElementById('appointmentType').value;
                    const reason = document.getElementById('reason').value;
                    const symptoms = document.getElementById('symptoms').value;
                    const insurance = document.getElementById('insurance').value;
                    const insuranceNumber = document.getElementById('insuranceNumber').value;
                    const paymentStatus = document.getElementById('paymentStatus').value;
                    const status = document.getElementById('status').value;
                    const notes = document.getElementById('notes').value;
                    
                    // Validation
                    if (!patientId || !patientName) {
                        alert('Please select a patient');
                        return;
                    }
                    
                    if (!appointmentDate || !appointmentTime) {
                        alert('Please select appointment date and time');
                        return;
                    }
                    
                    if (!doctor) {
                        alert('Please enter doctor name');
                        return;
                    }
                    
                    if (!appointmentType) {
                        alert('Please select appointment type');
                        return;
                    }
                    
                    if (!priority) {
                        alert('Please select priority');
                        return;
                    }
                    
                    // Create or update appointment
                    const existingAppointment = appointments.find(a => a.appointmentId === appointmentId);
                    
                    if (existingAppointment) {
                        // Update existing appointment
                        existingAppointment.patientId = patientId;
                        existingAppointment.patientName = patientName;
                        existingAppointment.patientAge = parseInt(patientAge);
                        existingAppointment.patientGender = patientGender;
                        existingAppointment.appointmentDate = appointmentDate;
                        existingAppointment.appointmentTime = appointmentTime;
                        existingAppointment.timeDisplay = formatTime(appointmentTime);
                        existingAppointment.duration = parseInt(duration);
                        existingAppointment.durationDisplay = `${duration} minutes`;
                        existingAppointment.priority = priority;
                        existingAppointment.priorityText = priority.charAt(0).toUpperCase() + priority.slice(1);
                        existingAppointment.doctor = doctor;
                        existingAppointment.doctorId = doctor.toLowerCase().replace(/[^a-z0-9]/g, '_');
                        existingAppointment.appointmentType = appointmentType;
                        existingAppointment.reason = reason;
                        existingAppointment.symptoms = symptoms;
                        existingAppointment.insurance = insurance;
                        existingAppointment.insuranceNumber = insuranceNumber;
                        existingAppointment.paymentStatus = paymentStatus;
                        existingAppointment.paymentStatusText = paymentStatus ? paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1) : 'Pending';
                        existingAppointment.status = status;
                        existingAppointment.statusText = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Scheduled';
                        existingAppointment.notes = notes;
                        
                        alert('Appointment updated successfully!');
                    } else {
                        // Create new appointment
                        const newAppointment = {
                            id: appointments.length > 0 ? Math.max(...appointments.map(a => a.id)) + 1 : 1,
                            patientId: patientId,
                            patientName: patientName,
                            patientAge: parseInt(patientAge),
                            patientGender: patientGender,
                            patientPhone: "+265 XXX XXX XXX", // Default phone
                            patientEmail: "patient@email.com", // Default email
                            patientAddress: "Address not specified",
                            appointmentId: appointmentId,
                            appointmentDate: appointmentDate,
                            appointmentTime: appointmentTime,
                            timeDisplay: formatTime(appointmentTime),
                            duration: parseInt(duration),
                            durationDisplay: `${duration} minutes`,
                            doctor: doctor,
                            doctorId: doctor.toLowerCase().replace(/[^a-z0-9]/g, '_'),
                            appointmentType: appointmentType,
                            status: status || 'scheduled',
                            statusText: status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Scheduled',
                            priority: priority,
                            priorityText: priority.charAt(0).toUpperCase() + priority.slice(1),
                            reason: reason,
                            symptoms: symptoms,
                            insurance: insurance,
                            insuranceNumber: insuranceNumber,
                            paymentStatus: paymentStatus || 'pending',
                            paymentStatusText: paymentStatus ? paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1) : 'Pending',
                            notes: notes,
                            checkedIn: false,
                            checkInTime: null,
                            reminderSent: false,
                            createdAt: new Date().toISOString()
                        };
                        
                        appointments.push(newAppointment);
                        alert('Appointment scheduled successfully!');
                    }
                    
                    // Update UI
                    filterAppointments();
                    updateStatistics();
                    closeAppointmentModal();
                }

                function formatTime(timeString) {
                    const [hours, minutes] = timeString.split(':');
                    const hour = parseInt(hours);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    return `${displayHour}:${minutes} ${ampm}`;
                }

                // Render Appointments Table - UPDATED for both desktop and mobile
                function renderAppointmentsTable() {
                    if (!tableBody || !mobileList) return;
                    
                    if (filteredAppointments.length === 0) {
                        // Desktop table empty state
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <ion-icon name="calendar-outline"></ion-icon>
                                    <h3>No appointments found</h3>
                                    <p>Try adjusting your search or filters</p>
                                </td>
                            </tr>
                        `;
                        
                        // Mobile list empty state
                        mobileList.innerHTML = `
                            <div class="empty-state">
                                <ion-icon name="calendar-outline"></ion-icon>
                                <h3>No appointments found</h3>
                                <p>Try adjusting your search or filters</p>
                            </div>
                        `;
                        return;
                    }

                    // Render Desktop Table
                    let desktopHtml = '';
                    filteredAppointments.forEach(appointment => {
                        const statusClass = `status-${appointment.status}`;
                        const priorityClass = `priority-${appointment.priority}`;
                        
                        // Get patient initials for avatar
                        const initials = appointment.patientName.split(' ').map(n => n[0]).join('');
                        
                        desktopHtml += `
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px; min-width: 200px;">
                                        <div class="patient-avatar">${initials}</div>
                                        <div>
                                            <strong>${appointment.patientName}</strong><br>
                                            <small style="color: var(--dark-gray);">${appointment.patientId}</small><br>
                                            <small style="color: var(--dark-gray);">${appointment.patientAge} yrs • ${appointment.patientGender}</small>
                                        </div>
                                    </div>
                                </td>
                                <td style="min-width: 180px;">
                                    <div>
                                        <strong>${appointment.appointmentType}</strong><br>
                                        <small style="color: var(--dark-gray);">${appointment.durationDisplay}</small>
                                    </div>
                                </td>
                                <td style="min-width: 200px;">
                                    <div class="doctor-info">
                                        <div class="doctor-avatar">
                                            ${appointment.doctor.split(' ').map(n => n[0]).join('')}
                                        </div>
                                        <div>
                                            <strong>${appointment.doctor}</strong><br>
                                            <small style="color: var(--dark-gray);">${appointment.department}</small>
                                        </div>
                                    </div>
                                </td>
                                <td style="min-width: 150px;">
                                    <span class="appointment-status ${statusClass}">${appointment.statusText}</span>
                                    <div style="margin-top: 5px;">
                                        <span class="priority-indicator ${priorityClass}"></span>
                                        <small style="color: var(--dark-gray);">${appointment.priorityText}</small>
                                    </div>
                                </td>
                                <td style="min-width: 150px;">
                                    <div class="time-slot">
                                        <ion-icon name="time-outline"></ion-icon>
                                        <div>
                                            <div><strong>${appointment.timeDisplay}</strong></div>
                                            <div class="delivery-time">${appointment.durationDisplay}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="min-width: 200px;">
                                    <div class="appointment-actions">
                                        <button class="action-icon view" title="View Details" onclick="viewAppointment(${appointment.id})">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        <button class="action-icon edit" title="Edit" onclick="editAppointment(${appointment.id})">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        ${!appointment.checkedIn ? `
                                            <button class="action-icon checkin" title="Check-in" onclick="openCheckInModal(${appointment.id})">
                                                <ion-icon name="checkmark-circle-outline"></ion-icon>
                                            </button>
                                        ` : `
                                            <button class="action-icon checkin" title="Checked In" style="background: var(--success); color: white;" disabled>
                                                <ion-icon name="checkmark-done-outline"></ion-icon>
                                            </button>
                                        `}
                                        <button class="action-icon delete" title="Delete" onclick="deleteAppointment(${appointment.id})">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = desktopHtml;
                    
                    // Render Mobile List
                    let mobileHtml = '';
                    filteredAppointments.forEach(appointment => {
                        const statusClass = `status-${appointment.status}`;
                        const priorityClass = `priority-${appointment.priority}`;
                        const initials = appointment.patientName.split(' ').map(n => n[0]).join('');
                        
                        mobileHtml += `
                            <div class="mobile-appointment-card">
                                <div class="mobile-appointment-header">
                                    <div class="mobile-patient-info">
                                        <div class="patient-avatar">${initials}</div>
                                        <div>
                                            <strong>${appointment.patientName}</strong><br>
                                            <small>${appointment.patientId}</small>
                                        </div>
                                    </div>
                                    <span class="appointment-status ${statusClass}">${appointment.statusText}</span>
                                </div>
                                
                                <div class="mobile-appointment-details">
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Time</span>
                                        <span class="mobile-detail-value">${appointment.timeDisplay}</span>
                                    </div>
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Duration</span>
                                        <span class="mobile-detail-value">${appointment.durationDisplay}</span>
                                    </div>
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Doctor</span>
                                        <span class="mobile-detail-value">${appointment.doctor}</span>
                                    </div>
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Type</span>
                                        <span class="mobile-detail-value">${appointment.appointmentType}</span>
                                    </div>
                                    <div class="mobile-detail-item">
                                        <span class="mobile-detail-label">Priority</span>
                                        <span class="mobile-detail-value">
                                            <span class="priority-indicator ${priorityClass}"></span>
                                            ${appointment.priorityText}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mobile-appointment-actions">
                                    <button class="action-icon view" title="View Details" onclick="viewAppointment(${appointment.id})">
                                        <ion-icon name="eye-outline"></ion-icon>
                                    </button>
                                    <button class="action-icon edit" title="Edit" onclick="editAppointment(${appointment.id})">
                                        <ion-icon name="create-outline"></ion-icon>
                                    </button>
                                    ${!appointment.checkedIn ? `
                                        <button class="action-icon checkin" title="Check-in" onclick="openCheckInModal(${appointment.id})">
                                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                                        </button>
                                    ` : `
                                        <button class="action-icon checkin" title="Checked In" style="background: var(--success); color: white;" disabled>
                                            <ion-icon name="checkmark-done-outline"></ion-icon>
                                        </button>
                                    `}
                                    <button class="action-icon delete" title="Delete" onclick="deleteAppointment(${appointment.id})">
                                        <ion-icon name="trash-outline"></ion-icon>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    mobileList.innerHTML = mobileHtml;
                    updatePaginationInfo();
                }

                // Update Statistics
                function updateStatistics() {
                    const today = new Date().toISOString().split('T')[0];
                    const todayAppointments = appointments.filter(a => a.appointmentDate === today);
                    
                    const totalAppointments = todayAppointments.length;
                    const confirmedAppointments = todayAppointments.filter(a => a.status === 'confirmed').length;
                    const pendingAppointments = todayAppointments.filter(a => a.status === 'pending').length;
                    const cancelledAppointments = todayAppointments.filter(a => a.status === 'cancelled').length;
                    const waitingPatients = todayAppointments.filter(a => a.checkedIn && a.status === 'confirmed').length;
                    const availableDoctors = 8; // This would normally come from an API
                    
                    document.getElementById('totalAppointments').textContent = totalAppointments;
                    document.getElementById('confirmedAppointments').textContent = confirmedAppointments;
                    document.getElementById('pendingAppointments').textContent = pendingAppointments;
                    document.getElementById('cancelledAppointments').textContent = cancelledAppointments;
                    document.getElementById('waitingPatients').textContent = waitingPatients;
                    document.getElementById('availableDoctors').textContent = availableDoctors;
                }

                // Filter and Search Functions
                function filterAppointments() {
                    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                    const status = document.getElementById('statusFilter').value;
                    const dateFrom = document.getElementById('dateFrom').value;
                    const dateTo = document.getElementById('dateTo').value;
                    const doctor = document.getElementById('doctorFilter').value;
                    const department = document.getElementById('departmentFilter').value;
                    const priority = document.getElementById('priorityFilter').value;

                    filteredAppointments = appointments.filter(appointment => {
                        const matchesSearch = 
                            appointment.patientName.toLowerCase().includes(searchTerm) ||
                            appointment.patientId.toLowerCase().includes(searchTerm) ||
                            appointment.doctor.toLowerCase().includes(searchTerm) ||
                            appointment.appointmentId.toLowerCase().includes(searchTerm);

                        const matchesStatus = !status || appointment.status === status;
                        const matchesDoctor = !doctor || appointment.doctorId === doctor;
                        const matchesDepartment = !department || appointment.departmentId === department;
                        const matchesPriority = !priority || appointment.priority === priority;
                        
                        const appointmentDate = new Date(appointment.appointmentDate);
                        const fromDate = dateFrom ? new Date(dateFrom) : null;
                        const toDate = dateTo ? new Date(dateTo) : null;
                        
                        let matchesDate = true;
                        if (fromDate && toDate) {
                            matchesDate = appointmentDate >= fromDate && appointmentDate <= toDate;
                        } else if (fromDate) {
                            matchesDate = appointmentDate >= fromDate;
                        } else if (toDate) {
                            matchesDate = appointmentDate <= toDate;
                        }

                        return matchesSearch && matchesStatus && matchesDoctor && matchesDepartment && matchesPriority && matchesDate;
                    });

                    // Sort by time
                    filteredAppointments.sort((a, b) => {
                        return a.appointmentTime.localeCompare(b.appointmentTime);
                    });

                    renderAppointmentsTable();
                    updatePaginationInfo();
                }

                function resetFilters() {
                    document.getElementById('searchInput').value = '';
                    document.getElementById('statusFilter').value = '';
                    document.getElementById('doctorFilter').value = '';
                    document.getElementById('departmentFilter').value = '';
                    document.getElementById('priorityFilter').value = '';
                    
                    // Reset dates to today
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('dateFrom').value = today;
                    document.getElementById('dateTo').value = today;
                    
                    filterAppointments();
                }

                function applyFilters() {
                    filterAppointments();
                }

                // Pagination Functions
                function updatePaginationInfo() {
                    const total = filteredAppointments.length;
                    const start = (currentPage - 1) * itemsPerPage + 1;
                    const end = Math.min(currentPage * itemsPerPage, total);
                    
                    document.getElementById('paginationInfo').textContent = 
                        `Showing ${start} to ${end} of ${total} entries`;
                }

                function changePage(direction) {
                    const totalPages = Math.ceil(filteredAppointments.length / itemsPerPage);
                    
                    if (direction === 'prev' && currentPage > 1) {
                        currentPage--;
                    } else if (direction === 'next' && currentPage < totalPages) {
                        currentPage++;
                    }
                    
                    renderAppointmentsTable();
                }

                // Delete Appointment Functions
                function deleteAppointment(id) {
                    const appointment = appointments.find(a => a.id === id);
                    if (!appointment) return;

                    appointmentToDelete = id;
                    document.getElementById('deleteAppointmentModal').classList.add('active');
                }

                function closeDeleteModal() {
                    if (deleteAppointmentModal) {
                        deleteAppointmentModal.classList.remove('active');
                    }
                    appointmentToDelete = null;
                }

                function confirmDelete() {
                    if (!appointmentToDelete) return;

                    const index = appointments.findIndex(a => a.id === appointmentToDelete);
                    if (index !== -1) {
                        appointments.splice(index, 1);
                        
                        // Update UI
                        filterAppointments();
                        updateStatistics();
                        closeDeleteModal();
                        
                        alert('Appointment deleted successfully!');
                    }
                }

                // Check-in Functions
                function openCheckInModal(id) {
                    const appointment = appointments.find(a => a.id === id);
                    if (!appointment) return;

                    appointmentToCheckIn = id;
                    document.getElementById('checkInPatientName').textContent = appointment.patientName;
                    checkInModal.classList.add('active');
                }

                function closeCheckInModal() {
                    if (checkInModal) {
                        checkInModal.classList.remove('active');
                    }
                    appointmentToCheckIn = null;
                }

                function confirmCheckIn() {
                    if (!appointmentToCheckIn) return;

                    const index = appointments.findIndex(a => a.id === appointmentToCheckIn);
                    if (index !== -1) {
                        appointments[index].checkedIn = true;
                        appointments[index].checkInTime = new Date().toISOString();
                        appointments[index].status = 'confirmed';
                        appointments[index].statusText = 'Confirmed';
                        
                        // Update UI
                        filterAppointments();
                        updateStatistics();
                        closeCheckInModal();
                        
                        alert('Patient checked in successfully!');
                    }
                }

                // Utility Functions
                function refreshAppointments() {
                    filterAppointments();
                }

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeAppointmentModal();
                        closeViewModal();
                        closeDeleteModal();
                        closeCheckInModal();
                    }
                    if (e.ctrlKey && e.key === 'f') {
                        e.preventDefault();
                        document.getElementById('searchInput').focus();
                    }
                    if (e.ctrlKey && e.key === 'n') {
                        e.preventDefault();
                        openAddAppointmentModal();
                    }
                });

                console.log('Appointments Management Page Initialized');
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