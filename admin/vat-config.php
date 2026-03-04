<<<<<<< HEAD
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

// Include the backend handler to get initial data
require_once 'vat_backend.php';
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
                /* ================== Simple VAT Configuration Styles ============== */
                .vat-simple-section {
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

                /* VAT Rates Section */
                .vat-rates-simple {
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

                /* Default Badge */
                .default-badge {
                    display: inline-block;
                    padding: 2px 8px;
                    background: var(--warning);
                    color: white;
                    border-radius: 10px;
                    font-size: 0.75rem;
                    margin-left: 5px;
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
                    max-width: 500px;
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
                .form-group select {
                    padding: 10px 12px;
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    font-size: 1rem;
                    width: 100%;
                    box-sizing: border-box;
                }

                .form-group input:focus,
                .form-group select:focus {
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
                }

                @media (max-width: 480px) {
                    .vat-simple-section {
                        padding: 15px;
                    }
                    
                    .simple-modal-overlay {
                        padding: 10px;
                    }
                    
                    .simple-modal {
                        max-height: 95vh;
                    }
                }
            </style>

            <!-- ================== Simple VAT Configuration ============== -->
            <div class="vat-simple-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>VAT Rates</h1>
                        <p>Manage your VAT rates and settings</p>
                    </div>
                    <button class="action-btn primary" onclick="openAddModal()">
                        <ion-icon name="add-outline"></ion-icon>
                        Add VAT Rate
                    </button>
                </div>

                <!-- Quick Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: var(--white); padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid var(--primary);">
                        <div style="font-size: 1.8rem; font-weight: bold; color: var(--primary);" id="activeCount"><?php echo $initial_stats['active_rates']; ?></div>
                        <div style="color: var(--dark-gray); font-size: 0.9rem;">Active Rates</div>
                    </div>
                    <div style="background: var(--white); padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid var(--success);">
                        <div style="font-size: 1.8rem; font-weight: bold; color: var(--primary);" id="defaultRate"><?php echo number_format($initial_stats['default_percentage'], 2); ?>%</div>
                        <div style="color: var(--dark-gray); font-size: 0.9rem;">Default Rate</div>
                    </div>
                </div>

                <!-- VAT Rates Table -->
                <div class="vat-rates-simple">
                    <div class="section-header">
                        <h3>Current VAT Rates</h3>
                        <div style="color: var(--dark-gray); font-size: 0.9rem;">
                            Showing <span id="totalRates"><?php echo $initial_stats['total_rates']; ?></span> rates
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>RATE NAME</th>
                                    <th>PERCENTAGE</th>
                                    <th>TYPE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="ratesTableBody">
                                <?php if (empty($initial_rates)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <ion-icon name="receipt-outline"></ion-icon>
                                        <div>No VAT rates found</div>
                                        <div style="font-size: 0.9rem; margin-top: 10px;">Click "Add VAT Rate" to create your first rate</div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_rates as $rate): ?>
                                <?php 
                                    $statusClass = $rate['is_active'] ? 'status-active' : 'status-inactive';
                                    $statusText = $rate['is_active'] ? 'Active' : 'Inactive';
                                    $defaultBadge = $rate['is_default'] ? '<span class="default-badge">Default</span>' : '';
                                    $typeText = $rate['rate_type_text'] ?? ucfirst($rate['rate_type']);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rate['rate_name']); ?></strong>
                                        <?php echo $defaultBadge; ?>
                                    </td>
                                    <td><strong><?php echo $rate['percentage_formatted'] ?? number_format($rate['percentage'], 2) . '%'; ?></strong></td>
                                    <td><?php echo $typeText; ?></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td>
                                        <div class="simple-actions">
                                            <button class="simple-action-btn edit" onclick="editRate(<?php echo $rate['vat_id']; ?>)" title="Edit">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="simple-action-btn delete" onclick="deleteRate(<?php echo $rate['vat_id']; ?>)" title="Delete">
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
            <div class="simple-modal-overlay" id="vatModal">
                <div class="simple-modal">
                    <div class="simple-modal-header">
                        <h3 id="modalTitle">Add VAT Rate</h3>
                        <button class="modal-close" onclick="closeModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="simple-modal-body">
                        <form id="vatForm" class="simple-form">
                            <input type="hidden" id="rateId">
                            
                            <div class="form-group">
                                <label for="rateName">Rate Name</label>
                                <input type="text" id="rateName" placeholder="e.g., Standard VAT" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="ratePercentage">Percentage (%)</label>
                                <input type="number" id="ratePercentage" step="0.01" min="0" max="100" placeholder="e.g., 16.5" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="rateType">Rate Type</label>
                                <select id="rateType">
                                    <option value="standard">Standard</option>
                                    <option value="reduced">Reduced</option>
                                    <option value="zero">Zero Rated</option>
                                    <option value="exempt">Exempt</option>
                                </select>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="isDefault">
                                <label for="isDefault">Set as default VAT rate</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="isActive" checked>
                                <label for="isActive">Active</label>
                            </div>
                        </form>
                    </div>
                    
                    <div class="simple-modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveRate()">
                            Save Rate
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
                            <p id="deleteMessage">Are you sure you want to delete this VAT rate?</p>
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
                let rateToDelete = null;

                // Initialize
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('VAT Management System Initialized');
                    
                    // Add event listeners for modals
                    document.querySelectorAll('.simple-modal-overlay').forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('active');
                            }
                        });
                    });
                });

                // Modal Functions
                function openAddModal() {
                    document.getElementById('modalTitle').textContent = 'Add VAT Rate';
                    document.getElementById('rateId').value = '';
                    document.getElementById('rateName').value = '';
                    document.getElementById('ratePercentage').value = '';
                    document.getElementById('rateType').value = 'standard';
                    document.getElementById('isDefault').checked = false;
                    document.getElementById('isActive').checked = true;
                    
                    document.getElementById('vatModal').classList.add('active');
                }

                function editRate(vatId) {
                    const formData = new FormData();
                    formData.append('action', 'get_vat_rate');
                    formData.append('vat_id', vatId);
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const rate = data.data;
                            
                            document.getElementById('modalTitle').textContent = 'Edit VAT Rate';
                            document.getElementById('rateId').value = rate.vat_id;
                            document.getElementById('rateName').value = rate.rate_name;
                            document.getElementById('ratePercentage').value = rate.percentage;
                            document.getElementById('rateType').value = rate.rate_type;
                            document.getElementById('isDefault').checked = rate.is_default;
                            document.getElementById('isActive').checked = rate.is_active;
                            
                            document.getElementById('vatModal').classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load VAT rate for editing');
                    });
                }

                function closeModal() {
                    document.getElementById('vatModal').classList.remove('active');
                }

                function closeDeleteModal() {
                    document.getElementById('deleteModal').classList.remove('active');
                    rateToDelete = null;
                }

                function saveRate() {
                    const rateId = document.getElementById('rateId').value;
                    const isEdit = !!rateId;
                    
                    // Validate
                    const name = document.getElementById('rateName').value.trim();
                    const percentage = parseFloat(document.getElementById('ratePercentage').value);
                    
                    if (!name) {
                        showError('Please enter a rate name');
                        return;
                    }
                    
                    if (!percentage || isNaN(percentage)) {
                        showError('Please enter a valid percentage');
                        return;
                    }
                    
                    if (percentage < 0 || percentage > 100) {
                        showError('Percentage must be between 0 and 100');
                        return;
                    }
                    
                    // Prepare form data
                    const formData = new FormData();
                    formData.append('action', isEdit ? 'edit_vat_rate' : 'add_vat_rate');
                    
                    if (isEdit) {
                        formData.append('vat_id', rateId);
                    }
                    
                    formData.append('rate_name', name);
                    formData.append('rate_percentage', percentage);
                    formData.append('rate_type', document.getElementById('rateType').value);
                    formData.append('is_default', document.getElementById('isDefault').checked ? '1' : '0');
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
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            showSuccess(data.message);
                            closeModal();
                            loadVatRates();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        showError('Failed to save VAT rate');
                    });
                }

                // Delete Functions
                function deleteRate(vatId) {
                    const formData = new FormData();
                    formData.append('action', 'get_vat_rate');
                    formData.append('vat_id', vatId);
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            rateToDelete = data.data;
                            document.getElementById('deleteMessage').textContent = 
                                `Are you sure you want to delete "${rateToDelete.rate_name}" (${rateToDelete.percentage}%)?`;
                            document.getElementById('deleteModal').classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load VAT rate for deletion');
                    });
                }

                function confirmDelete() {
                    if (!rateToDelete) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_vat_rate');
                    formData.append('vat_id', rateToDelete.vat_id);
                    
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            showSuccess(data.message);
                            closeDeleteModal();
                            loadVatRates();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        showError('Failed to delete VAT rate');
                    });
                }

                // Load VAT Rates
                function loadVatRates() {
                    const formData = new FormData();
                    formData.append('action', 'get_vat_rates');
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderRatesTable(data.data);
                            updateStats();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load VAT rates');
                    });
                }

                // Update Stats
                function updateStats() {
                    const formData = new FormData();
                    formData.append('action', 'get_vat_stats');
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const stats = data.stats;
                            document.getElementById('activeCount').textContent = stats.active_rates;
                            document.getElementById('defaultRate').textContent = stats.default_percentage + '%';
                            document.getElementById('totalRates').textContent = stats.total_rates;
                        }
                    })
                    .catch(error => {
                        console.error('Error updating stats:', error);
                    });
                }

                // Render Rates Table
                function renderRatesTable(rates) {
                    const tbody = document.getElementById('ratesTableBody');
                    
                    if (!rates || rates.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <ion-icon name="receipt-outline"></ion-icon>
                                    <div>No VAT rates found</div>
                                    <div style="font-size: 0.9rem; margin-top: 10px;">Click "Add VAT Rate" to create your first rate</div>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    rates.forEach(rate => {
                        const status = rate.is_active ? 'Active' : 'Inactive';
                        const statusClass = rate.is_active ? 'status-active' : 'status-inactive';
                        const defaultBadge = rate.is_default ? '<span class="default-badge">Default</span>' : '';
                        const typeText = rate.rate_type_text || rate.rate_type.charAt(0).toUpperCase() + rate.rate_type.slice(1);
                        
                        html += `
                            <tr>
                                <td>
                                    <strong>${rate.rate_name}</strong>
                                    ${defaultBadge}
                                </td>
                                <td><strong>${rate.percentage_formatted || rate.percentage + '%'}</strong></td>
                                <td>${typeText}</td>
                                <td><span class="status-badge ${statusClass}">${status}</span></td>
                                <td>
                                    <div class="simple-actions">
                                        <button class="simple-action-btn edit" onclick="editRate(${rate.vat_id})" title="Edit">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="simple-action-btn delete" onclick="deleteRate(${rate.vat_id})" title="Delete">
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
                        if (document.getElementById('vatModal').classList.contains('active')) {
                            closeModal();
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
=======
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

// Include the backend handler to get initial data
require_once 'vat_backend.php';
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
                /* ================== Simple VAT Configuration Styles ============== */
                .vat-simple-section {
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

                /* VAT Rates Section */
                .vat-rates-simple {
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

                /* Default Badge */
                .default-badge {
                    display: inline-block;
                    padding: 2px 8px;
                    background: var(--warning);
                    color: white;
                    border-radius: 10px;
                    font-size: 0.75rem;
                    margin-left: 5px;
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
                    max-width: 500px;
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
                .form-group select {
                    padding: 10px 12px;
                    border: 1px solid var(--gray);
                    border-radius: 6px;
                    font-size: 1rem;
                    width: 100%;
                    box-sizing: border-box;
                }

                .form-group input:focus,
                .form-group select:focus {
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
                }

                @media (max-width: 480px) {
                    .vat-simple-section {
                        padding: 15px;
                    }
                    
                    .simple-modal-overlay {
                        padding: 10px;
                    }
                    
                    .simple-modal {
                        max-height: 95vh;
                    }
                }
            </style>

            <!-- ================== Simple VAT Configuration ============== -->
            <div class="vat-simple-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>VAT Rates</h1>
                        <p>Manage your VAT rates and settings</p>
                    </div>
                    <button class="action-btn primary" onclick="openAddModal()">
                        <ion-icon name="add-outline"></ion-icon>
                        Add VAT Rate
                    </button>
                </div>

                <!-- Quick Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: var(--white); padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid var(--primary);">
                        <div style="font-size: 1.8rem; font-weight: bold; color: var(--primary);" id="activeCount"><?php echo $initial_stats['active_rates']; ?></div>
                        <div style="color: var(--dark-gray); font-size: 0.9rem;">Active Rates</div>
                    </div>
                    <div style="background: var(--white); padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid var(--success);">
                        <div style="font-size: 1.8rem; font-weight: bold; color: var(--primary);" id="defaultRate"><?php echo number_format($initial_stats['default_percentage'], 2); ?>%</div>
                        <div style="color: var(--dark-gray); font-size: 0.9rem;">Default Rate</div>
                    </div>
                </div>

                <!-- VAT Rates Table -->
                <div class="vat-rates-simple">
                    <div class="section-header">
                        <h3>Current VAT Rates</h3>
                        <div style="color: var(--dark-gray); font-size: 0.9rem;">
                            Showing <span id="totalRates"><?php echo $initial_stats['total_rates']; ?></span> rates
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>RATE NAME</th>
                                    <th>PERCENTAGE</th>
                                    <th>TYPE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="ratesTableBody">
                                <?php if (empty($initial_rates)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <ion-icon name="receipt-outline"></ion-icon>
                                        <div>No VAT rates found</div>
                                        <div style="font-size: 0.9rem; margin-top: 10px;">Click "Add VAT Rate" to create your first rate</div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($initial_rates as $rate): ?>
                                <?php 
                                    $statusClass = $rate['is_active'] ? 'status-active' : 'status-inactive';
                                    $statusText = $rate['is_active'] ? 'Active' : 'Inactive';
                                    $defaultBadge = $rate['is_default'] ? '<span class="default-badge">Default</span>' : '';
                                    $typeText = $rate['rate_type_text'] ?? ucfirst($rate['rate_type']);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rate['rate_name']); ?></strong>
                                        <?php echo $defaultBadge; ?>
                                    </td>
                                    <td><strong><?php echo $rate['percentage_formatted'] ?? number_format($rate['percentage'], 2) . '%'; ?></strong></td>
                                    <td><?php echo $typeText; ?></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td>
                                        <div class="simple-actions">
                                            <button class="simple-action-btn edit" onclick="editRate(<?php echo $rate['vat_id']; ?>)" title="Edit">
                                                <ion-icon name="create-outline"></ion-icon>
                                            </button>
                                            <button class="simple-action-btn delete" onclick="deleteRate(<?php echo $rate['vat_id']; ?>)" title="Delete">
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
            <div class="simple-modal-overlay" id="vatModal">
                <div class="simple-modal">
                    <div class="simple-modal-header">
                        <h3 id="modalTitle">Add VAT Rate</h3>
                        <button class="modal-close" onclick="closeModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="simple-modal-body">
                        <form id="vatForm" class="simple-form">
                            <input type="hidden" id="rateId">
                            
                            <div class="form-group">
                                <label for="rateName">Rate Name</label>
                                <input type="text" id="rateName" placeholder="e.g., Standard VAT" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="ratePercentage">Percentage (%)</label>
                                <input type="number" id="ratePercentage" step="0.01" min="0" max="100" placeholder="e.g., 16.5" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="rateType">Rate Type</label>
                                <select id="rateType">
                                    <option value="standard">Standard</option>
                                    <option value="reduced">Reduced</option>
                                    <option value="zero">Zero Rated</option>
                                    <option value="exempt">Exempt</option>
                                </select>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="isDefault">
                                <label for="isDefault">Set as default VAT rate</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="isActive" checked>
                                <label for="isActive">Active</label>
                            </div>
                        </form>
                    </div>
                    
                    <div class="simple-modal-footer">
                        <button type="button" class="action-btn secondary" onclick="closeModal()">
                            Cancel
                        </button>
                        <button type="button" class="action-btn primary" onclick="saveRate()">
                            Save Rate
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
                            <p id="deleteMessage">Are you sure you want to delete this VAT rate?</p>
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
                let rateToDelete = null;

                // Initialize
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('VAT Management System Initialized');
                    
                    // Add event listeners for modals
                    document.querySelectorAll('.simple-modal-overlay').forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === this) {
                                this.classList.remove('active');
                            }
                        });
                    });
                });

                // Modal Functions
                function openAddModal() {
                    document.getElementById('modalTitle').textContent = 'Add VAT Rate';
                    document.getElementById('rateId').value = '';
                    document.getElementById('rateName').value = '';
                    document.getElementById('ratePercentage').value = '';
                    document.getElementById('rateType').value = 'standard';
                    document.getElementById('isDefault').checked = false;
                    document.getElementById('isActive').checked = true;
                    
                    document.getElementById('vatModal').classList.add('active');
                }

                function editRate(vatId) {
                    const formData = new FormData();
                    formData.append('action', 'get_vat_rate');
                    formData.append('vat_id', vatId);
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const rate = data.data;
                            
                            document.getElementById('modalTitle').textContent = 'Edit VAT Rate';
                            document.getElementById('rateId').value = rate.vat_id;
                            document.getElementById('rateName').value = rate.rate_name;
                            document.getElementById('ratePercentage').value = rate.percentage;
                            document.getElementById('rateType').value = rate.rate_type;
                            document.getElementById('isDefault').checked = rate.is_default;
                            document.getElementById('isActive').checked = rate.is_active;
                            
                            document.getElementById('vatModal').classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load VAT rate for editing');
                    });
                }

                function closeModal() {
                    document.getElementById('vatModal').classList.remove('active');
                }

                function closeDeleteModal() {
                    document.getElementById('deleteModal').classList.remove('active');
                    rateToDelete = null;
                }

                function saveRate() {
                    const rateId = document.getElementById('rateId').value;
                    const isEdit = !!rateId;
                    
                    // Validate
                    const name = document.getElementById('rateName').value.trim();
                    const percentage = parseFloat(document.getElementById('ratePercentage').value);
                    
                    if (!name) {
                        showError('Please enter a rate name');
                        return;
                    }
                    
                    if (!percentage || isNaN(percentage)) {
                        showError('Please enter a valid percentage');
                        return;
                    }
                    
                    if (percentage < 0 || percentage > 100) {
                        showError('Percentage must be between 0 and 100');
                        return;
                    }
                    
                    // Prepare form data
                    const formData = new FormData();
                    formData.append('action', isEdit ? 'edit_vat_rate' : 'add_vat_rate');
                    
                    if (isEdit) {
                        formData.append('vat_id', rateId);
                    }
                    
                    formData.append('rate_name', name);
                    formData.append('rate_percentage', percentage);
                    formData.append('rate_type', document.getElementById('rateType').value);
                    formData.append('is_default', document.getElementById('isDefault').checked ? '1' : '0');
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
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            showSuccess(data.message);
                            closeModal();
                            loadVatRates();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        showError('Failed to save VAT rate');
                    });
                }

                // Delete Functions
                function deleteRate(vatId) {
                    const formData = new FormData();
                    formData.append('action', 'get_vat_rate');
                    formData.append('vat_id', vatId);
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            rateToDelete = data.data;
                            document.getElementById('deleteMessage').textContent = 
                                `Are you sure you want to delete "${rateToDelete.rate_name}" (${rateToDelete.percentage}%)?`;
                            document.getElementById('deleteModal').classList.add('active');
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load VAT rate for deletion');
                    });
                }

                function confirmDelete() {
                    if (!rateToDelete) return;
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_vat_rate');
                    formData.append('vat_id', rateToDelete.vat_id);
                    
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            showSuccess(data.message);
                            closeDeleteModal();
                            loadVatRates();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Error:', error);
                        showError('Failed to delete VAT rate');
                    });
                }

                // Load VAT Rates
                function loadVatRates() {
                    const formData = new FormData();
                    formData.append('action', 'get_vat_rates');
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderRatesTable(data.data);
                            updateStats();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Failed to load VAT rates');
                    });
                }

                // Update Stats
                function updateStats() {
                    const formData = new FormData();
                    formData.append('action', 'get_vat_stats');
                    
                    fetch('vat_backend.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const stats = data.stats;
                            document.getElementById('activeCount').textContent = stats.active_rates;
                            document.getElementById('defaultRate').textContent = stats.default_percentage + '%';
                            document.getElementById('totalRates').textContent = stats.total_rates;
                        }
                    })
                    .catch(error => {
                        console.error('Error updating stats:', error);
                    });
                }

                // Render Rates Table
                function renderRatesTable(rates) {
                    const tbody = document.getElementById('ratesTableBody');
                    
                    if (!rates || rates.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <ion-icon name="receipt-outline"></ion-icon>
                                    <div>No VAT rates found</div>
                                    <div style="font-size: 0.9rem; margin-top: 10px;">Click "Add VAT Rate" to create your first rate</div>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let html = '';
                    rates.forEach(rate => {
                        const status = rate.is_active ? 'Active' : 'Inactive';
                        const statusClass = rate.is_active ? 'status-active' : 'status-inactive';
                        const defaultBadge = rate.is_default ? '<span class="default-badge">Default</span>' : '';
                        const typeText = rate.rate_type_text || rate.rate_type.charAt(0).toUpperCase() + rate.rate_type.slice(1);
                        
                        html += `
                            <tr>
                                <td>
                                    <strong>${rate.rate_name}</strong>
                                    ${defaultBadge}
                                </td>
                                <td><strong>${rate.percentage_formatted || rate.percentage + '%'}</strong></td>
                                <td>${typeText}</td>
                                <td><span class="status-badge ${statusClass}">${status}</span></td>
                                <td>
                                    <div class="simple-actions">
                                        <button class="simple-action-btn edit" onclick="editRate(${rate.vat_id})" title="Edit">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </button>
                                        <button class="simple-action-btn delete" onclick="deleteRate(${rate.vat_id})" title="Delete">
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
                        if (document.getElementById('vatModal').classList.contains('active')) {
                            closeModal();
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
>>>>>>> ebf5f55ccd0a1b48a75b40abdbae6c5de9fe43f4
</html>