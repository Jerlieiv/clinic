<?php
// Include configuration file
require_once '../config/db.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get current user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'staff';

// Create database connection
$conn = connectDB();

// Check if connection was successful
if (!$conn || $conn->connect_error) {
    // Handle connection error
    $connection_error = $conn ? $conn->connect_error : 'Failed to create database connection';
    die("Database connection failed: " . $connection_error);
}

// Get initial data for page load
function getInitialData($conn) {
    // Check if connection is valid
    if (!$conn || !($conn instanceof mysqli)) {
        return [
            'services' => [],
            'stats' => [
                'total_services' => 0,
                'active_services' => 0,
                'total_value' => '0.00',
                'avg_price' => '0.00'
            ],
            'categories' => []
        ];
    }
    
    // Get services
    $sql = "SELECT s.*, 
                   sc.category_name,
                   DATE_FORMAT(s.created_at, '%d/%m/%Y') as created_date
            FROM services s
            LEFT JOIN service_categories sc ON s.service_category = sc.category_name
            ORDER BY s.service_name ASC
            LIMIT 50";
    
    $result = $conn->query($sql);
    $services = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['price_formatted'] = 'MWK ' . number_format($row['price_mwk'], 2);
            $services[] = $row;
        }
    }
    
    // Get stats
    $stats = [
        'total_services' => 0,
        'active_services' => 0,
        'total_value' => '0.00',
        'avg_price' => '0.00'
    ];
    
    $sql = "SELECT COUNT(*) as total FROM services";
    $result = $conn->query($sql);
    if ($result) {
        $stats['total_services'] = $result->fetch_assoc()['total'];
    }
    
    $sql = "SELECT COUNT(*) as active FROM services WHERE is_active = 1";
    $result = $conn->query($sql);
    if ($result) {
        $stats['active_services'] = $result->fetch_assoc()['active'];
    }
    
    $sql = "SELECT SUM(price_mwk) as total FROM services WHERE is_active = 1";
    $result = $conn->query($sql);
    if ($result) {
        $total = $result->fetch_assoc()['total'];
        $stats['total_value'] = number_format($total ?? 0, 2);
    }
    
    $sql = "SELECT AVG(price_mwk) as avg FROM services WHERE is_active = 1";
    $result = $conn->query($sql);
    if ($result) {
        $avg = $result->fetch_assoc()['avg'];
        $stats['avg_price'] = number_format($avg ?? 0, 2);
    }
    
    // Get categories
    $sql = "SELECT category_name FROM service_categories WHERE is_active = 1 ORDER BY category_name";
    $result = $conn->query($sql);
    $categories = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category_name'];
        }
    }
    
    return [
        'services' => $services,
        'stats' => $stats,
        'categories' => $categories
    ];
}

// Get initial data - PASS THE CONNECTION HERE
$initial_data = getInitialData($conn);
$initial_services = $initial_data['services'];
$initial_stats = $initial_data['stats'];
$initial_categories = $initial_data['categories'];
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
                /* ================== Services Management Styles ============== */
                .services-section {
                    padding: 25px;
                }

                .page-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 25px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid var(--gray);
                }

                .page-header h1 {
                    color: var(--primary);
                    font-size: 1.8rem;
                    margin: 0;
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
                }

                .action-btn.secondary {
                    background: var(--light-gray);
                    color: var(--dark-gray);
                }

                .action-btn.secondary:hover {
                    background: #e0e0e0;
                }

                /* Stats Cards */
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 15px;
                    margin-bottom: 25px;
                }

                .stat-card {
                    background: var(--white);
                    border-radius: 8px;
                    padding: 20px;
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
                    position: relative;
                    overflow: hidden;
                }

                .stat-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 4px;
                    height: 100%;
                }

                .stat-card:nth-child(1)::before { background: var(--primary); }
                .stat-card:nth-child(2)::before { background: var(--success); }
                .stat-card:nth-child(3)::before { background: var(--warning); }
                .stat-card:nth-child(4)::before { background: var(--accent); }

                .stat-value {
                    font-size: 1.8rem;
                    font-weight: bold;
                    color: var(--primary);
                    margin-bottom: 5px;
                }

                .stat-label {
                    color: var(--dark-gray);
                    font-size: 0.9rem;
                }

                /* Services Table */
                .services-table-container {
                    background: var(--white);
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
                    margin-bottom: 25px;
                }

                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }

                .section-header h3 {
                    margin: 0;
                    color: var(--primary);
                    font-size: 1.3rem;
                }

                /* Simple Table */
                .simple-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .simple-table thead {
                    background: var(--light-gray);
                }

                .simple-table th {
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.9rem;
                    border-bottom: 2px solid var(--gray);
                }

                .simple-table tbody tr {
                    border-bottom: 1px solid var(--gray);
                    transition: background 0.2s ease;
                }

                .simple-table tbody tr:hover {
                    background: rgba(42, 92, 139, 0.05);
                }

                .simple-table td {
                    padding: 12px 15px;
                    color: var(--black);
                    font-size: 0.95rem;
                }

                /* Status Badge */
                .status-badge {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 15px;
                    font-size: 0.8rem;
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

                /* Price Display */
                .price-display {
                    font-weight: bold;
                    color: var(--primary);
                }

                /* Simple Actions */
                .simple-actions {
                    display: flex;
                    gap: 5px;
                }

                .simple-action-btn {
                    width: 35px;
                    height: 35px;
                    border-radius: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    border: none;
                    transition: all 0.2s ease;
                    font-size: 1rem;
                    background: none;
                    color: var(--dark-gray);
                }

                .simple-action-btn:hover {
                    background: var(--light-gray);
                }

                .simple-action-btn.view:hover {
                    color: var(--info);
                }

                .simple-action-btn.edit:hover {
                    color: var(--accent);
                }

                .simple-action-btn.delete:hover {
                    color: var(--danger);
                }

                /* Simple Modal */
                .simple-modal-overlay {
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
                    padding: 15px;
                }

                .simple-modal-overlay.active {
                    display: flex;
                }

                .simple-modal {
                    background: var(--white);
                    border-radius: 12px;
                    padding: 0;
                    width: 100%;
                    max-width: 600px;
                    max-height: 90vh;
                    overflow-y: auto;
                }

                .simple-modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    border-bottom: 1px solid var(--gray);
                }

                .simple-modal-header h3 {
                    margin: 0;
                    color: var(--primary);
                    font-size: 1.2rem;
                }

                .modal-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: var(--dark-gray);
                    padding: 5px;
                }

                .simple-modal-body {
                    padding: 20px;
                }

                .simple-form {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .form-group {
                    display: flex;
                    flex-direction: column;
                }

                .form-group label {
                    margin-bottom: 6px;
                    font-weight: 500;
                    color: var(--black);
                    font-size: 0.9rem;
                }

                .form-group input,
                .form-group select,
                .form-group textarea {
                    padding: 10px 12px;
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    font-size: 1rem;
                    width: 100%;
                    box-sizing: border-box;
                    font-family: inherit;
                }

                .form-group textarea {
                    min-height: 100px;
                    resize: vertical;
                }

                .form-group input:focus,
                .form-group select:focus,
                .form-group textarea:focus {
                    outline: none;
                    border-color: var(--primary);
                }

                .checkbox-group {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-top: 5px;
                }

                .checkbox-group input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                }

                .simple-modal-footer {
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                    padding: 15px 20px;
                    border-top: 1px solid var(--gray);
                }

                /* View Modal Specific */
                .view-details {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .detail-row {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid var(--light-gray);
                }

                .detail-row:last-child {
                    border-bottom: none;
                }

                .detail-label {
                    font-weight: 600;
                    color: var(--primary);
                    font-size: 0.9rem;
                }

                .detail-value {
                    color: var(--black);
                    font-size: 1rem;
                }

                .price-large {
                    font-size: 1.5rem;
                    font-weight: bold;
                    color: var(--primary);
                    text-align: center;
                    padding: 10px;
                    background: rgba(42, 92, 139, 0.1);
                    border-radius: 6px;
                }

                /* Empty State */
                .empty-state {
                    text-align: center;
                    padding: 40px 20px;
                    color: var(--dark-gray);
                }

                .empty-state ion-icon {
                    font-size: 3rem;
                    margin-bottom: 15px;
                    opacity: 0.5;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .page-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 15px;
                    }
                    
                    .section-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 10px;
                    }
                    
                    .simple-table {
                        display: block;
                        overflow-x: auto;
                    }
                    
                    .stats-grid {
                        grid-template-columns: repeat(2, 1fr);
                    }
                }

                @media (max-width: 480px) {
                    .services-section {
                        padding: 15px;
                    }
                    
                    .simple-modal-overlay {
                        padding: 10px;
                    }
                    
                    .simple-modal {
                        max-height: 95vh;
                    }
                    
                    .stats-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <!-- ================== Services Management ============== -->
            <div class="services-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Clinic Services</h1>
                        <p>Manage clinic services and pricing</p>
                    </div>
                    <button class="action-btn primary" onclick="openAddModal()">
                        <ion-icon name="add-outline"></ion-icon>
                        Add New Service
                    </button>
                </div>

                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="totalServices"><?php echo $initial_stats['total_services']; ?></div>
                        <div class="stat-label">Total Services</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="activeServices"><?php echo $initial_stats['active_services']; ?></div>
                        <div class="stat-label">Active Services</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">MWK <span id="totalValue"><?php echo $initial_stats['total_value']; ?></span></div>
                        <div class="stat-label">Total Value</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">MWK <span id="avgPrice"><?php echo $initial_stats['avg_price']; ?></span></div>
                        <div class="stat-label">Average Price</div>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="services-table-container">
                    <div class="section-header">
                        <h3>Available Services</h3>
                        <div style="color: var(--dark-gray); font-size: 0.9rem;">
                            Showing <span id="totalCount"><?php echo $initial_stats['total_services']; ?></span> services
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>SERVICE NAME</th>
                                    <th>CATEGORY</th>
                                    <th>PRICE (MWK)</th>
                                    <th>STATUS</th>
                                    <th>CREATED</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="servicesTableBody">
                                <?php if (empty($initial_services)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <ion-icon name="medkit-outline"></ion-icon>
                                        <div>No services found</div>
                                        <div style="font-size: 0.9rem; margin-top: 10px;">Click "Add New Service" to add your first service</div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_services as $service): ?>
                                <?php 
                                    $statusClass = $service['is_active'] ? 'status-active' : 'status-inactive';
                                    $statusText = $service['is_active'] ? 'Active' : 'Inactive';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($service['service_name']); ?></strong>
                                        <?php if (!empty($service['service_description'])): ?>
                                        <div style="font-size: 0.85rem; color: var(--dark-gray); margin-top: 3px;">
                                            <?php echo substr(htmlspecialchars($service['service_description']), 0, 50) . (strlen($service['service_description']) > 50 ? '...' : ''); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($service['category_name'] ?? $service['service_category']); ?></td>
                                    <td class="price-display">MWK <?php echo number_format($service['price_mwk'], 2); ?></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td><?php echo $service['created_date']; ?></td>
                                    <td>
                                        <div class="simple-actions">
                                            <button class="simple-action-btn view" onclick="viewService(<?php echo $service['service_id']; ?>)" title="View Details">
                                                <ion-icon name="eye-outline"></ion-icon>
                                            </button>
                                            <button class="simple-action-btn edit" onclick="editService(<?php echo $service['service_id']; ?>)" title="Edit">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="simple-action-btn delete" onclick="deleteService(<?php echo $service['service_id']; ?>)" title="Delete">
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
                </div>
            </div>

            <!-- Add/Edit Modal -->
            <div class="simple-modal-overlay" id="serviceModal">
                <div class="simple-modal">
                    <div class="simple-modal-header">
                        <h3 id="modalTitle">Add New Service</h3>
                        <button class="modal-close" onclick="closeModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="simple-modal-body">
                        <form id="serviceForm" class="simple-form">
                            <input type="hidden" id="serviceId">
                            
                            <div class="form-group">
                                <label for="serviceName">Service Name *</label>
                                <input type="text" id="serviceName" placeholder="e.g., General Consultation" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="serviceDescription">Description</label>
                                <textarea id="serviceDescription" placeholder="Brief description of the service..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="serviceCategory">Category *</label>
                                <select id="serviceCategory" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($initial_categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="priceMwk">Price (MWK) *</label>
                                <input type="number" id="priceMwk" step="0.01" min="0.01" placeholder="e.g., 5000.00" required>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="isActive" checked>
                                <label for="isActive">Active Service</label>
                            </div>
                        </form>
                    </div>
                    
                    <div class="simple-modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveService()">
                            Save Service
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Details Modal -->
            <div class="simple-modal-overlay" id="viewModal">
                <div class="simple-modal">
                    <div class="simple-modal-header">
                        <h3>Service Details</h3>
                        <button class="modal-close" onclick="closeViewModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="simple-modal-body">
                        <div class="view-details" id="viewDetails">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="simple-modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeViewModal()">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="simple-modal-overlay" id="deleteModal">
                <div class="simple-modal">
                    <div class="simple-modal-header">
                        <h3>Confirm Delete</h3>
                        <button class="modal-close" onclick="closeDeleteModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="simple-modal-body">
                        <div style="text-align: center; padding: 20px;">
                            <ion-icon name="warning-outline" style="font-size: 3rem; color: var(--danger); margin-bottom: 15px;"></ion-icon>
                            <p id="deleteMessage">Are you sure you want to delete this service?</p>
                        </div>
                    </div>
                    
                    <div class="simple-modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeDeleteModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" style="background: var(--danger);" onclick="confirmDelete()">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                // Global variables
                let serviceToDelete = null;
                let currentCategories = <?php echo json_encode($initial_categories); ?>;

                // Initialize
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('Services Management System Initialized');
                    
                    // Add event listeners for modals
                    document.querySelectorAll('.simple-modal-overlay').forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('active');
                            }
                        });
                    });
                    
                    // Load categories for dropdown
                    loadCategories();
                });

                // Modal Functions
                function openAddModal() {
                    document.getElementById('modalTitle').textContent = 'Add New Service';
                    document.getElementById('serviceId').value = '';
                    document.getElementById('serviceName').value = '';
                    document.getElementById('serviceDescription').value = '';
                    document.getElementById('serviceCategory').value = '';
                    document.getElementById('priceMwk').value = '';
                    document.getElementById('isActive').checked = true;
                    
                    // Populate categories
                    populateCategoryDropdown();
                    
                    document.getElementById('serviceModal').classList.add('active');
                }

                function editService(serviceId) {
                    const formData = new FormData();
                    formData.append('action', 'get_service');
                    formData.append('service_id', serviceId);
                    
                    fetch('services_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const service = data.data;
                            
                            document.getElementById('modalTitle').textContent = 'Edit Service';
                            document.getElementById('serviceId').value = service.service_id;
                            document.getElementById('serviceName').value = service.service_name;
                            document.getElementById('serviceDescription').value = service.service_description || '';
                            document.getElementById('serviceCategory').value = service.service_category;
                            document.getElementById('priceMwk').value = service.price_mwk;
                            document.getElementById('isActive').checked = service.is_active;
                            
                            // Populate categories
                            populateCategoryDropdown();
                            
                            document.getElementById('serviceModal').classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load service for editing');
                    });
                }

                function viewService(serviceId) {
                    const formData = new FormData();
                    formData.append('action', 'get_service');
                    formData.append('service_id', serviceId);
                    
                    fetch('services_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const service = data.data;
                            renderViewModal(service);
                            document.getElementById('viewModal').classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load service details');
                    });
                }

                function renderViewModal(service) {
                    const detailsDiv = document.getElementById('viewDetails');
                    
                    const status = service.is_active ? 'Active' : 'Inactive';
                    const statusClass = service.is_active ? 'status-active' : 'status-inactive';
                    
                    detailsDiv.innerHTML = `
                        <div class="detail-row">
                            <div class="detail-label">Service Name</div>
                            <div class="detail-value"><strong>${service.service_name}</strong></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Description</div>
                            <div class="detail-value">${service.service_description || 'No description provided'}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Category</div>
                            <div class="detail-value">${service.service_category}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Status</div>
                            <div class="detail-value"><span class="status-badge ${statusClass}">${status}</span></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Price</div>
                            <div class="price-large">${service.price_formatted || 'MWK ' + Number(service.price_mwk).toFixed(2)}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Created Date</div>
                            <div class="detail-value">${service.created_date}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Last Updated</div>
                            <div class="detail-value">${service.updated_date || service.created_date}</div>
                        </div>
                    `;
                }

                function closeModal() {
                    document.getElementById('serviceModal').classList.remove('active');
                }

                function closeViewModal() {
                    document.getElementById('viewModal').classList.remove('active');
                }

                function closeDeleteModal() {
                    document.getElementById('deleteModal').classList.remove('active');
                    serviceToDelete = null;
                }

                // Save Service
                function saveService() {
                    const serviceId = document.getElementById('serviceId').value;
                    const isEdit = !!serviceId;
                    
                    // Validate
                    const name = document.getElementById('serviceName').value.trim();
                    const category = document.getElementById('serviceCategory').value;
                    const price = parseFloat(document.getElementById('priceMwk').value);
                    
                    if (!name) {
                        showError('Please enter a service name');
                        return;
                    }
                    
                    if (!category) {
                        showError('Please select a category');
                        return;
                    }
                    
                    if (!price || isNaN(price) || price <= 0) {
                        showError('Please enter a valid price');
                        return;
                    }
                    
                    // Prepare form data
                    const formData = new FormData();
                    formData.append('action', isEdit ? 'edit_service' : 'add_service');
                    
                    if (isEdit) {
                        formData.append('service_id', serviceId);
                    }
                    
                    formData.append('service_name', name);
                    formData.append('service_description', document.getElementById('serviceDescription').value.trim());
                    formData.append('service_category', category);
                    formData.append('price_mwk', price);
                    formData.append('is_active', document.getElementById('isActive').checked ? '1' : '0');
                    
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
                    fetch('services_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            showSuccess(data.message);
                            closeModal();
                            loadServices();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        showError('Failed to save service');
                    });
                }

                // Delete Functions
                function deleteService(serviceId) {
                    const formData = new FormData();
                    formData.append('action', 'get_service');
                    formData.append('service_id', serviceId);
                    
                    fetch('services_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            serviceToDelete = data.data;
                            document.getElementById('deleteMessage').textContent = 
                                `Are you sure you want to delete "${serviceToDelete.service_name}" (MWK ${serviceToDelete.price_mwk})?`;
                            document.getElementById('deleteModal').classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load service for deletion');
                    });
                }

                function confirmDelete() {
                    if (!serviceToDelete) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_service');
                    formData.append('service_id', serviceToDelete.service_id);
                    
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('services_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            showSuccess(data.message);
                            closeDeleteModal();
                            loadServices();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        showError('Failed to delete service');
                    });
                }

                // Load Services
                function loadServices() {
                    const formData = new FormData();
                    formData.append('action', 'get_services');
                    
                    fetch('services_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderServicesTable(data.data);
                            updateStats();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load services');
                    });
                }

                // Load Categories
                function loadCategories() {
                    const formData = new FormData();
                    formData.append('action', 'get_categories');
                    
                    fetch('services_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            currentCategories = data.categories;
                            populateCategoryDropdown();
                        }
                    })
                    .catch(error => {
                        console.error('Error loading categories:', error);
                    });
                }

                function populateCategoryDropdown() {
                    const select = document.getElementById('serviceCategory');
                    const currentValue = select.value;
                    
                    // Clear options except first one
                    while (select.options.length > 1) {
                        select.remove(1);
                    }
                    
                    // Add categories
                    currentCategories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category;
                        option.textContent = category;
                        select.appendChild(option);
                    });
                    
                    // Add "Other" option
                    const otherOption = document.createElement('option');
                    otherOption.value = 'Other';
                    otherOption.textContent = 'Other';
                    select.appendChild(otherOption);
                    
                    // Restore previous value if exists
                    if (currentValue) {
                        select.value = currentValue;
                    }
                }

                // Update Stats
                function updateStats() {
                    const formData = new FormData();
                    formData.append('action', 'get_service_stats');
                    
                    fetch('services_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const stats = data.stats;
                            document.getElementById('totalServices').textContent = stats.total_services;
                            document.getElementById('activeServices').textContent = stats.active_services;
                            document.getElementById('totalValue').textContent = stats.total_value;
                            document.getElementById('avgPrice').textContent = stats.avg_price;
                            document.getElementById('totalCount').textContent = stats.total_services;
                        }
                    })
                    .catch(error => {
                        console.error('Error updating stats:', error);
                    });
                }

                // Render Services Table
                function renderServicesTable(services) {
                    const tbody = document.getElementById('servicesTableBody');
                    
                    if (!services || services.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <ion-icon name="medkit-outline"></ion-icon>
                                    <div>No services found</div>
                                    <div style="font-size: 0.9rem; margin-top: 10px;">Click "Add New Service" to add your first service</div>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    services.forEach(service => {
                        const status = service.is_active ? 'Active' : 'Inactive';
                        const statusClass = service.is_active ? 'status-active' : 'status-inactive';
                        const description = service.service_description ? 
                            `<div style="font-size: 0.85rem; color: var(--dark-gray); margin-top: 3px;">
                                ${service.service_description.substring(0, 50)}${service.service_description.length > 50 ? '...' : ''}
                            </div>` : '';
                        
                        html += `
                            <tr>
                                <td>
                                    <strong>${service.service_name}</strong>
                                    ${description}
                                </td>
                                <td>${service.category_name || service.service_category}</td>
                                <td class="price-display">${service.price_formatted || 'MWK ' + Number(service.price_mwk).toFixed(2)}</td>
                                <td><span class="status-badge ${statusClass}">${status}</span></td>
                                <td>${service.created_date}</td>
                                <td>
                                    <div class="simple-actions">
                                        <button class="simple-action-btn view" onclick="viewService(${service.service_id})" title="View Details">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </button>
                                        <button class="simple-action-btn edit" onclick="editService(${service.service_id})" title="Edit">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="simple-action-btn delete" onclick="deleteService(${service.service_id})" title="Delete">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tbody.innerHTML = html;
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

                // Handle Keyboard Shortcuts
                document.addEventListener('keydown', function(e) {
                    // Escape to close modals
                    if (e.key === 'Escape') {
                        if (document.getElementById('serviceModal').classList.contains('active')) {
                            closeModal();
                        } else if (document.getElementById('viewModal').classList.contains('active')) {
                            closeViewModal();
                        } else if (document.getElementById('deleteModal').classList.contains('active')) {
                            closeDeleteModal();
                        }
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