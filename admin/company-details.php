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
                /* ================== Company Details Styles ============== */
                .company-details-section {
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

                .action-btn.save {
                    background: var(--primary);
                    color: white;
                }

                .action-btn.save:hover {
                    background: var(--secondary);
                    transform: translateY(-2px);
                }

                .action-btn.edit {
                    background: var(--accent);
                    color: white;
                }

                .action-btn.edit:hover {
                    background: #16a085;
                    transform: translateY(-2px);
                }

                .action-btn.cancel {
                    background: var(--light-gray);
                    color: var(--dark-gray);
                }

                .action-btn.cancel:hover {
                    background: #e0e0e0;
                    transform: translateY(-2px);
                }

                .company-details-grid {
                    display: grid;
                    grid-template-columns: 2fr 1fr;
                    gap: 30px;
                }

                .details-card {
                    background: var(--white);
                    border-radius: 15px;
                    padding: 25px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                }

                .card-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 25px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid var(--gray);
                }

                .card-header h2 {
                    color: var(--primary);
                    font-size: 1.5rem;
                    margin: 0;
                }

                .edit-toggle {
                    background: none;
                    border: none;
                    color: var(--primary);
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    font-size: 0.9rem;
                }

                /* Form Styles */
                .details-form {
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
                    color: var(--black);
                    font-weight: 500;
                    font-size: 0.9rem;
                }

                .form-group input,
                .form-group select,
                .form-group textarea {
                    padding: 12px 15px;
                    border: 1px solid var(--gray);
                    border-radius: 8px;
                    font-size: 0.95rem;
                    transition: border 0.3s ease;
                    background: var(--white);
                }

                .form-group input:focus,
                .form-group select:focus,
                .form-group textarea:focus {
                    outline: none;
                    border-color: var(--primary);
                    box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1);
                }

                .form-group input:disabled,
                .form-group select:disabled,
                .form-group textarea:disabled {
                    background: var(--light-gray);
                    cursor: not-allowed;
                }

                .form-group textarea {
                    min-height: 100px;
                    resize: vertical;
                }

                /* Logo Upload */
                .logo-section {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 20px;
                }

                .logo-preview {
                    width: 150px;
                    height: 150px;
                    border-radius: 10px;
                    overflow: hidden;
                    border: 2px solid var(--gray);
                    position: relative;
                }

                .logo-preview img {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    padding: 10px;
                }

                .logo-upload-btn {
                    padding: 10px 25px;
                    background: var(--light-gray);
                    border: 2px dashed var(--primary);
                    border-radius: 8px;
                    cursor: pointer;
                    text-align: center;
                    color: var(--primary);
                    font-weight: 500;
                    transition: all 0.3s ease;
                }

                .logo-upload-btn:hover {
                    background: var(--primary);
                    color: white;
                }

                .logo-upload-btn input[type="file"] {
                    display: none;
                }

                /* Contact Persons */
                .contact-persons {
                    margin-top: 30px;
                }

                .person-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 15px;
                    border: 1px solid var(--gray);
                    border-radius: 8px;
                    margin-bottom: 15px;
                    background: var(--light-gray);
                }

                .person-info {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .person-avatar {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    background: var(--primary);
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 1.2rem;
                }

                .person-details h4 {
                    margin: 0 0 5px 0;
                    color: var(--black);
                }

                .person-details p {
                    margin: 0;
                    color: var(--dark-gray);
                    font-size: 0.9rem;
                }

                .person-actions {
                    display: flex;
                    gap: 10px;
                }

                .person-btn {
                    padding: 5px 15px;
                    border-radius: 5px;
                    border: none;
                    cursor: pointer;
                    font-size: 0.85rem;
                }

                .person-btn.edit {
                    background: var(--accent);
                    color: white;
                }

                .person-btn.delete {
                    background: var(--danger);
                    color: white;
                }

                /* Status Badge */
                .status-badge {
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .status-badge.active {
                    background: rgba(39, 174, 96, 0.1);
                    color: var(--success);
                }

                .status-badge.inactive {
                    background: rgba(231, 76, 60, 0.1);
                    color: var(--danger);
                }

                /* Tab Navigation */
                .details-tabs {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 25px;
                    border-bottom: 1px solid var(--gray);
                    padding-bottom: 0;
                }

                .tab-btn {
                    padding: 12px 25px;
                    background: none;
                    border: none;
                    border-bottom: 3px solid transparent;
                    cursor: pointer;
                    font-weight: 500;
                    color: var(--dark-gray);
                    transition: all 0.3s ease;
                }

                .tab-btn.active {
                    color: var(--primary);
                    border-bottom-color: var(--primary);
                }

                .tab-btn:hover:not(.active) {
                    color: var(--primary);
                }

                /* Business Hours */
                .hours-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                }

                .hour-item {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    padding: 15px;
                    background: var(--light-gray);
                    border-radius: 8px;
                }

                .day-label {
                    min-width: 100px;
                    font-weight: 500;
                    color: var(--black);
                }

                .time-inputs {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .time-input {
                    padding: 8px 12px;
                    border: 1px solid var(--gray);
                    border-radius: 5px;
                    width: 120px;
                }

                .closed-checkbox {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                /* Social Media */
                .social-media-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 20px;
                }

                .social-item {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    padding: 15px;
                    border: 1px solid var(--gray);
                    border-radius: 8px;
                }

                .social-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: var(--light-gray);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--primary);
                    font-size: 1.2rem;
                }

                /* Statistics */
                .stats-cards {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                    margin-top: 30px;
                }

                .stat-card {
                    background: var(--white);
                    border-radius: 10px;
                    padding: 20px;
                    border-left: 4px solid var(--primary);
                }

                .stat-card h4 {
                    margin: 0 0 10px 0;
                    color: var(--dark-gray);
                    font-size: 0.9rem;
                }

                .stat-card .stat-value {
                    font-size: 1.8rem;
                    font-weight: 700;
                    color: var(--primary);
                    margin: 0;
                }

                /* Modal */
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
                }

                .modal-overlay.active {
                    display: flex;
                }

                .modal-content {
                    background: var(--white);
                    border-radius: 15px;
                    padding: 30px;
                    width: 90%;
                    max-width: 500px;
                    max-height: 90vh;
                    overflow-y: auto;
                }

                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 25px;
                }

                .modal-header h3 {
                    margin: 0;
                    color: var(--primary);
                }

                .modal-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: var(--dark-gray);
                }

                /* Responsive Design */
                @media (max-width: 1200px) {
                    .company-details-grid {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 768px) {
                    .company-details-section {
                        padding: 20px;
                    }

                    .page-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 15px;
                    }

                    .page-header .page-actions {
                        width: 100%;
                        justify-content: flex-start;
                    }

                    .details-form {
                        grid-template-columns: 1fr;
                    }

                    .stats-cards {
                        grid-template-columns: repeat(2, 1fr);
                    }

                    .hours-grid {
                        grid-template-columns: 1fr;
                    }

                    .person-item {
                        flex-direction: column;
                        gap: 15px;
                    }

                    .person-info {
                        width: 100%;
                    }

                    .person-actions {
                        width: 100%;
                        justify-content: flex-end;
                    }
                }

                @media (max-width: 480px) {
                    .stats-cards {
                        grid-template-columns: 1fr;
                    }

                    .page-header h1 {
                        font-size: 1.5rem;
                    }

                    .action-btn {
                        padding: 8px 15px;
                        font-size: 0.9rem;
                    }
                }
            </style>

            <!-- ================== Company Details Content ============== -->
            <div class="company-details-section">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1>Company Details</h1>
                        <p>Manage your pharmacy's information and settings</p>
                    </div>
                    <div class="page-actions">
                        <button class="action-btn cancel" onclick="cancelEdit()">
                            <ion-icon name="close-outline"></ion-icon>
                            Cancel
                        </button>
                        <button class="action-btn edit" onclick="toggleEditMode()">
                            <ion-icon name="create-outline"></ion-icon>
                            Edit Details
                        </button>
                        <button class="action-btn save" onclick="saveChanges()" disabled>
                            <ion-icon name="save-outline"></ion-icon>
                            Save Changes
                        </button>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="details-tabs">
                    <button class="tab-btn active" onclick="switchTab('basic')">Basic Information</button>
                    <button class="tab-btn" onclick="switchTab('contact')">Contact Information</button>
                    <button class="tab-btn" onclick="switchTab('hours')">Business Hours</button>
                    <button class="tab-btn" onclick="switchTab('social')">Social Media</button>
                    <button class="tab-btn" onclick="switchTab('settings')">Settings</button>
                </div>

                <div class="company-details-grid">
                    <!-- Left Column - Basic Information -->
                    <div class="details-section">
                        <!-- Basic Information Card -->
                        <div class="details-card">
                            <div class="card-header">
                                <h2>Basic Information</h2>
                                <button class="edit-toggle" onclick="toggleEditMode()">
                                    <ion-icon name="create-outline"></ion-icon>
                                    Edit
                                </button>
                            </div>
                            
                            <form id="basicInfoForm" class="details-form">
                                <div class="form-group">
                                    <label for="companyName">Company Name *</label>
                                    <input type="text" id="companyName" name="companyName" 
                                           value="PharmaCare Pharmacy" disabled required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="companyType">Business Type</label>
                                    <select id="companyType" name="companyType" disabled>
                                        <option value="pharmacy">Pharmacy</option>
                                        <option value="clinic">Clinic & Pharmacy</option>
                                        <option value="hospital">Hospital Pharmacy</option>
                                        <option value="retail">Retail Pharmacy</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="registrationNumber">Registration Number</label>
                                    <input type="text" id="registrationNumber" name="registrationNumber" 
                                           value="BP/2023/001" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label for="taxId">Tax ID (TIN)</label>
                                    <input type="text" id="taxId" name="taxId" 
                                           value="1000-1234-5678" disabled>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="description">Company Description</label>
                                    <textarea id="description" name="description" disabled>
PharmaCare Pharmacy is a leading healthcare provider in Malawi, offering comprehensive pharmaceutical services, medical consultations, and health products. We are committed to providing quality healthcare solutions to our community with professional care and advanced medical technology.
                                    </textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="establishedYear">Established Year</label>
                                    <input type="number" id="establishedYear" name="establishedYear" 
                                           value="2010" disabled min="1900" max="2024">
                                </div>
                                
                                <div class="form-group">
                                    <label for="employees">Number of Employees</label>
                                    <input type="number" id="employees" name="employees" 
                                           value="25" disabled min="1">
                                </div>
                            </form>
                        </div>

                        <!-- Contact Information Card (Tab Content) -->
                        <div class="details-card" id="contactTab" style="display: none;">
                            <div class="card-header">
                                <h2>Contact Information</h2>
                            </div>
                            
                            <form class="details-form">
                                <div class="form-group">
                                    <label for="phone">Primary Phone *</label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="+265 123 456 789" disabled required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" 
                                           value="info@pharmacare.com" disabled required>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="address">Physical Address</label>
                                    <input type="text" id="address" name="address" 
                                           value="M1 Road, Balaka, Malawi" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label for="city">City/Town</label>
                                    <input type="text" id="city" name="city" 
                                           value="Balaka" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" id="country" name="country" 
                                           value="Malawi" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label for="website">Website</label>
                                    <input type="url" id="website" name="website" 
                                           value="https://www.pharmacare.com" disabled>
                                </div>
                            </form>
                            
                            <!-- Contact Persons -->
                            <div class="contact-persons">
                                <h3 style="margin-top: 30px; margin-bottom: 20px;">Contact Persons</h3>
                                
                                <div class="person-item">
                                    <div class="person-info">
                                        <div class="person-avatar">JD</div>
                                        <div class="person-details">
                                            <h4>John Doe</h4>
                                            <p>Pharmacy Manager</p>
                                            <p>Phone: +265 888 123 456 | Email: john@pharmacare.com</p>
                                        </div>
                                    </div>
                                    <div class="person-actions">
                                        <button class="person-btn edit" onclick="editPerson(1)">Edit</button>
                                        <button class="person-btn delete" onclick="deletePerson(1)">Remove</button>
                                    </div>
                                </div>
                                
                                <div class="person-item">
                                    <div class="person-info">
                                        <div class="person-avatar">MS</div>
                                        <div class="person-details">
                                            <h4>Mary Smith</h4>
                                            <p>Customer Service Manager</p>
                                            <p>Phone: +265 999 456 789 | Email: mary@pharmacare.com</p>
                                        </div>
                                    </div>
                                    <div class="person-actions">
                                        <button class="person-btn edit" onclick="editPerson(2)">Edit</button>
                                        <button class="person-btn delete" onclick="deletePerson(2)">Remove</button>
                                    </div>
                                </div>
                                
                                <button class="action-btn edit" onclick="addPerson()" style="margin-top: 15px;">
                                    <ion-icon name="person-add-outline"></ion-icon>
                                    Add Contact Person
                                </button>
                            </div>
                        </div>

                        <!-- Business Hours Card (Tab Content) -->
                        <div class="details-card" id="hoursTab" style="display: none;">
                            <div class="card-header">
                                <h2>Business Hours</h2>
                            </div>
                            
                            <div class="hours-grid">
                                <div class="hour-item">
                                    <div class="day-label">Monday</div>
                                    <div class="time-inputs">
                                        <input type="time" class="time-input" value="08:00" disabled>
                                        <span>to</span>
                                        <input type="time" class="time-input" value="18:00" disabled>
                                    </div>
                                    <div class="closed-checkbox">
                                        <input type="checkbox" id="mondayClosed" disabled>
                                        <label for="mondayClosed">Closed</label>
                                    </div>
                                </div>
                                
                                <div class="hour-item">
                                    <div class="day-label">Tuesday</div>
                                    <div class="time-inputs">
                                        <input type="time" class="time-input" value="08:00" disabled>
                                        <span>to</span>
                                        <input type="time" class="time-input" value="18:00" disabled>
                                    </div>
                                    <div class="closed-checkbox">
                                        <input type="checkbox" id="tuesdayClosed" disabled>
                                        <label for="tuesdayClosed">Closed</label>
                                    </div>
                                </div>
                                
                                <div class="hour-item">
                                    <div class="day-label">Wednesday</div>
                                    <div class="time-inputs">
                                        <input type="time" class="time-input" value="08:00" disabled>
                                        <span>to</span>
                                        <input type="time" class="time-input" value="18:00" disabled>
                                    </div>
                                    <div class="closed-checkbox">
                                        <input type="checkbox" id="wednesdayClosed" disabled>
                                        <label for="wednesdayClosed">Closed</label>
                                    </div>
                                </div>
                                
                                <div class="hour-item">
                                    <div class="day-label">Thursday</div>
                                    <div class="time-inputs">
                                        <input type="time" class="time-input" value="08:00" disabled>
                                        <span>to</span>
                                        <input type="time" class="time-input" value="18:00" disabled>
                                    </div>
                                    <div class="closed-checkbox">
                                        <input type="checkbox" id="thursdayClosed" disabled>
                                        <label for="thursdayClosed">Closed</label>
                                    </div>
                                </div>
                                
                                <div class="hour-item">
                                    <div class="day-label">Friday</div>
                                    <div class="time-inputs">
                                        <input type="time" class="time-input" value="08:00" disabled>
                                        <span>to</span>
                                        <input type="time" class="time-input" value="18:00" disabled>
                                    </div>
                                    <div class="closed-checkbox">
                                        <input type="checkbox" id="fridayClosed" disabled>
                                        <label for="fridayClosed">Closed</label>
                                    </div>
                                </div>
                                
                                <div class="hour-item">
                                    <div class="day-label">Saturday</div>
                                    <div class="time-inputs">
                                        <input type="time" class="time-input" value="09:00" disabled>
                                        <span>to</span>
                                        <input type="time" class="time-input" value="16:00" disabled>
                                    </div>
                                    <div class="closed-checkbox">
                                        <input type="checkbox" id="saturdayClosed" disabled>
                                        <label for="saturdayClosed">Closed</label>
                                    </div>
                                </div>
                                
                                <div class="hour-item">
                                    <div class="day-label">Sunday</div>
                                    <div class="time-inputs">
                                        <input type="time" class="time-input" value="09:00" disabled>
                                        <span>to</span>
                                        <input type="time" class="time-input" value="14:00" disabled>
                                    </div>
                                    <div class="closed-checkbox">
                                        <input type="checkbox" id="sundayClosed" checked disabled>
                                        <label for="sundayClosed">Closed</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 25px;">
                                <h4 style="margin-bottom: 10px;">Holiday Schedule</h4>
                                <textarea style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--gray);" 
                                          rows="3" disabled>
• Christmas Day: December 25 (Closed)
• New Year's Day: January 1 (Closed)
• Independence Day: July 6 (10:00 AM - 2:00 PM)
• Easter Holidays: Good Friday & Easter Monday (Closed)
                                </textarea>
                            </div>
                        </div>

                        <!-- Social Media Card (Tab Content) -->
                        <div class="details-card" id="socialTab" style="display: none;">
                            <div class="card-header">
                                <h2>Social Media</h2>
                            </div>
                            
                            <div class="social-media-grid">
                                <div class="social-item">
                                    <div class="social-icon">
                                        <ion-icon name="logo-facebook"></ion-icon>
                                    </div>
                                    <input type="url" placeholder="Facebook URL" value="https://facebook.com/pharmacare" 
                                           style="flex: 1; padding: 10px; border: 1px solid var(--gray); border-radius: 5px;" disabled>
                                </div>
                                
                                <div class="social-item">
                                    <div class="social-icon">
                                        <ion-icon name="logo-twitter"></ion-icon>
                                    </div>
                                    <input type="url" placeholder="Twitter URL" value="https://twitter.com/pharmacare" 
                                           style="flex: 1; padding: 10px; border: 1px solid var(--gray); border-radius: 5px;" disabled>
                                </div>
                                
                                <div class="social-item">
                                    <div class="social-icon">
                                        <ion-icon name="logo-instagram"></ion-icon>
                                    </div>
                                    <input type="url" placeholder="Instagram URL" value="https://instagram.com/pharmacare" 
                                           style="flex: 1; padding: 10px; border: 1px solid var(--gray); border-radius: 5px;" disabled>
                                </div>
                                
                                <div class="social-item">
                                    <div class="social-icon">
                                        <ion-icon name="logo-linkedin"></ion-icon>
                                    </div>
                                    <input type="url" placeholder="LinkedIn URL" value="https://linkedin.com/company/pharmacare" 
                                           style="flex: 1; padding: 10px; border: 1px solid var(--gray); border-radius: 5px;" disabled>
                                </div>
                                
                                <div class="social-item">
                                    <div class="social-icon">
                                        <ion-icon name="logo-whatsapp"></ion-icon>
                                    </div>
                                    <input type="text" placeholder="WhatsApp Number" value="+265 888 123 456" 
                                           style="flex: 1; padding: 10px; border: 1px solid var(--gray); border-radius: 5px;" disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Card (Tab Content) -->
                        <div class="details-card" id="settingsTab" style="display: none;">
                            <div class="card-header">
                                <h2>System Settings</h2>
                            </div>
                            
                            <div class="details-form">
                                <div class="form-group full-width">
                                    <label>Company Status</label>
                                    <div style="display: flex; align-items: center; gap: 20px; margin-top: 10px;">
                                        <span class="status-badge active">Active</span>
                                        <button class="action-btn edit" style="padding: 8px 20px;">
                                            Change Status
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="currency">Default Currency</label>
                                    <select id="currency" name="currency" disabled>
                                        <option value="MWK" selected>Malawian Kwacha (MWK)</option>
                                        <option value="USD">US Dollar (USD)</option>
                                        <option value="EUR">Euro (EUR)</option>
                                        <option value="GBP">British Pound (GBP)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="timezone">Time Zone</label>
                                    <select id="timezone" name="timezone" disabled>
                                        <option value="Africa/Blantyre" selected>Africa/Blantyre (GMT+2)</option>
                                        <option value="UTC">UTC</option>
                                        <option value="America/New_York">America/New_York</option>
                                        <option value="Europe/London">Europe/London</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="dateFormat">Date Format</label>
                                    <select id="dateFormat" name="dateFormat" disabled>
                                        <option value="d/m/Y" selected>DD/MM/YYYY</option>
                                        <option value="m/d/Y">MM/DD/YYYY</option>
                                        <option value="Y-m-d">YYYY-MM-DD</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="autoBackup">Auto Backup</label>
                                    <select id="autoBackup" name="autoBackup" disabled>
                                        <option value="daily" selected>Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="none">Disabled</option>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width" style="margin-top: 30px;">
                                    <label style="color: var(--danger); font-weight: bold;">Danger Zone</label>
                                    <div style="padding: 20px; background: rgba(231, 76, 60, 0.1); border-radius: 8px; margin-top: 10px;">
                                        <p style="margin-bottom: 15px; color: var(--danger);">
                                            <strong>Warning:</strong> These actions are irreversible. Please proceed with caution.
                                        </p>
                                        <button class="action-btn cancel" style="background: var(--danger); color: white;" onclick="archiveCompany()">
                                            <ion-icon name="archive-outline"></ion-icon>
                                            Archive Company
                                        </button>
                                        <button class="action-btn cancel" style="background: var(--black); color: white; margin-left: 10px;" onclick="deleteCompany()">
                                            <ion-icon name="trash-outline"></ion-icon>
                                            Delete Company
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Logo & Statistics -->
                    <div class="details-section">
                        <!-- Logo & Branding Card -->
                        <div class="details-card">
                            <div class="card-header">
                                <h2>Logo & Branding</h2>
                            </div>
                            
                            <div class="logo-section">
                                <div class="logo-preview">
                                    <img src="https://via.placeholder.com/150x150/2a5c8b/ffffff?text=PC" 
                                         alt="Company Logo" id="logoPreview">
                                </div>
                                
                                <label class="logo-upload-btn">
                                    <ion-icon name="cloud-upload-outline"></ion-icon>
                                    Upload New Logo
                                    <input type="file" id="logoUpload" accept="image/*" onchange="previewLogo(this)" disabled>
                                </label>
                                
                                <div style="text-align: center; color: var(--dark-gray); font-size: 0.9rem;">
                                    <p>Recommended: 500×500px, PNG format with transparent background</p>
                                    <p>Max file size: 2MB</p>
                                </div>
                                
                                <div style="margin-top: 25px; width: 100%;">
                                    <label for="primaryColor" style="display: block; margin-bottom: 8px;">Primary Color</label>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <input type="color" id="primaryColor" value="#2a5c8b" disabled 
                                               style="width: 60px; height: 40px; border: none;">
                                        <input type="text" id="primaryColorHex" value="#2a5c8b" disabled 
                                               style="flex: 1; padding: 10px; border: 1px solid var(--gray); border-radius: 5px;">
                                    </div>
                                </div>
                                
                                <div style="margin-top: 20px; width: 100%;">
                                    <label for="secondaryColor" style="display: block; margin-bottom: 8px;">Secondary Color</label>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <input type="color" id="secondaryColor" value="#1e3a5f" disabled 
                                               style="width: 60px; height: 40px; border: none;">
                                        <input type="text" id="secondaryColorHex" value="#1e3a5f" disabled 
                                               style="flex: 1; padding: 10px; border: 1px solid var(--gray); border-radius: 5px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Company Statistics Card -->
                        <div class="details-card">
                            <div class="card-header">
                                <h2>Company Statistics</h2>
                            </div>
                            
                            <div class="stats-cards">
                                <div class="stat-card">
                                    <h4>Active Since</h4>
                                    <p class="stat-value">14 years</p>
                                </div>
                                
                                <div class="stat-card">
                                    <h4>Total Employees</h4>
                                    <p class="stat-value">25</p>
                                </div>
                                
                                <div class="stat-card">
                                    <h4>Monthly Revenue</h4>
                                    <p class="stat-value">MWK 15.2M</p>
                                </div>
                                
                                <div class="stat-card">
                                    <h4>Active Customers</h4>
                                    <p class="stat-value">1,245</p>
                                </div>
                                
                                <div class="stat-card">
                                    <h4>Products in Stock</h4>
                                    <p class="stat-value">4,856</p>
                                </div>
                                
                                <div class="stat-card">
                                    <h4>Monthly Orders</h4>
                                    <p class="stat-value">3,428</p>
                                </div>
                            </div>
                            
                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray);">
                                <h4 style="margin-bottom: 15px;">Last Updated</h4>
                                <div style="display: flex; justify-content: space-between; color: var(--dark-gray);">
                                    <div>
                                        <p style="margin: 5px 0;">Information: March 15, 2024</p>
                                        <p style="margin: 5px 0;">Logo: January 10, 2024</p>
                                    </div>
                                    <div>
                                        <p style="margin: 5px 0;">By: Dr. John Phiri</p>
                                        <p style="margin: 5px 0;">Role: Administrator</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Card -->
                        <div class="details-card">
                            <div class="card-header">
                                <h2>Quick Actions</h2>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <button class="action-btn edit" onclick="downloadCompanyInfo()" style="justify-content: flex-start;">
                                    <ion-icon name="download-outline"></ion-icon>
                                    Download Company Info (PDF)
                                </button>
                                
                                <button class="action-btn edit" onclick="printCompanyDetails()" style="justify-content: flex-start;">
                                    <ion-icon name="print-outline"></ion-icon>
                                    Print Company Details
                                </button>
                                
                                <button class="action-btn edit" onclick="generateReport()" style="justify-content: flex-start;">
                                    <ion-icon name="document-text-outline"></ion-icon>
                                    Generate Annual Report
                                </button>
                                
                                <button class="action-btn edit" onclick="exportData()" style="justify-content: flex-start;">
                                    <ion-icon name="share-outline"></ion-icon>
                                    Export Company Data
                                </button>
                                
                                <button class="action-btn edit" onclick="viewAuditLog()" style="justify-content: flex-start;">
                                    <ion-icon name="time-outline"></ion-icon>
                                    View Audit Log
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Contact Person Modal -->
            <div class="modal-overlay" id="personModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add Contact Person</h3>
                        <button class="modal-close" onclick="closeModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <form id="personForm" class="details-form">
                        <input type="hidden" id="personId">
                        
                        <div class="form-group">
                            <label for="personName">Full Name *</label>
                            <input type="text" id="personName" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="personTitle">Title/Role *</label>
                            <input type="text" id="personTitle" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="personPhone">Phone Number *</label>
                            <input type="tel" id="personPhone" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="personEmail">Email Address *</label>
                            <input type="email" id="personEmail" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="personDepartment">Department</label>
                            <select id="personDepartment">
                                <option value="management">Management</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="finance">Finance</option>
                                <option value="operations">Operations</option>
                                <option value="customer_service">Customer Service</option>
                                <option value="marketing">Marketing</option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="personNotes">Notes</label>
                            <textarea id="personNotes" rows="3"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 15px; margin-top: 25px;">
                            <button type="button" class="action-btn cancel" onclick="closeModal()">
                                Cancel
                            </button>
                            <button type="submit" class="action-btn save">
                                Save Person
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                // Tab Switching Functionality
                function switchTab(tabName) {
                    // Hide all tab content
                    document.querySelectorAll('[id$="Tab"]').forEach(tab => {
                        tab.style.display = 'none';
                    });
                    
                    // Remove active class from all tab buttons
                    document.querySelectorAll('.tab-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Show selected tab content
                    document.getElementById(tabName + 'Tab').style.display = 'block';
                    
                    // Add active class to clicked tab button
                    event.target.classList.add('active');
                }

                // Edit Mode Toggle
                let editMode = false;
                
                function toggleEditMode() {
                    editMode = !editMode;
                    const inputs = document.querySelectorAll('.details-form input, .details-form select, .details-form textarea');
                    const fileInput = document.getElementById('logoUpload');
                    const saveBtn = document.querySelector('.action-btn.save');
                    const editBtn = document.querySelector('.action-btn.edit');
                    const cancelBtn = document.querySelector('.action-btn.cancel');
                    
                    if (editMode) {
                        // Enable all inputs
                        inputs.forEach(input => {
                            input.disabled = false;
                            input.style.backgroundColor = 'white';
                        });
                        
                        // Enable file input
                        if (fileInput) fileInput.disabled = false;
                        
                        // Update buttons
                        saveBtn.disabled = false;
                        saveBtn.style.opacity = '1';
                        editBtn.innerHTML = '<ion-icon name="close-outline"></ion-icon> Cancel Edit';
                        cancelBtn.style.display = 'flex';
                        
                        // Show edit indicators
                        inputs.forEach(input => {
                            input.style.borderColor = editMode ? 'var(--primary)' : 'var(--gray)';
                        });
                    } else {
                        // Disable all inputs
                        inputs.forEach(input => {
                            input.disabled = true;
                            input.style.backgroundColor = 'var(--light-gray)';
                            input.style.borderColor = 'var(--gray)';
                        });
                        
                        // Disable file input
                        if (fileInput) fileInput.disabled = true;
                        
                        // Update buttons
                        saveBtn.disabled = true;
                        saveBtn.style.opacity = '0.5';
                        editBtn.innerHTML = '<ion-icon name="create-outline"></ion-icon> Edit Details';
                        cancelBtn.style.display = 'none';
                    }
                }

                function cancelEdit() {
                    if (confirm('Are you sure? All unsaved changes will be lost.')) {
                        editMode = false;
                        toggleEditMode();
                        // Here you would typically reload the original data
                        location.reload();
                    }
                }

                function saveChanges() {
                    const formData = new FormData();
                    
                    // Collect form data
                    const basicForm = document.getElementById('basicInfoForm');
                    const inputs = basicForm.querySelectorAll('input, select, textarea');
                    
                    inputs.forEach(input => {
                        formData.append(input.name, input.value);
                    });
                    
                    // Collect file data if logo was uploaded
                    const logoFile = document.getElementById('logoUpload').files[0];
                    if (logoFile) {
                        formData.append('logo', logoFile);
                    }
                    
                    // Simulate API call
                    console.log('Saving company details...', Object.fromEntries(formData));
                    
                    // Show success message
                    alert('Company details saved successfully!');
                    editMode = false;
                    toggleEditMode();
                }

                // Logo Preview
                function previewLogo(input) {
                    const preview = document.getElementById('logoPreview');
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.objectFit = 'cover';
                    }
                    
                    if (input.files && input.files[0]) {
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                // Contact Person Modal Functions
                function addPerson() {
                    document.getElementById('modalTitle').textContent = 'Add Contact Person';
                    document.getElementById('personForm').reset();
                    document.getElementById('personModal').classList.add('active');
                }

                function editPerson(id) {
                    // In real application, fetch person data by ID
                    document.getElementById('modalTitle').textContent = 'Edit Contact Person';
                    document.getElementById('personId').value = id;
                    
                    // Pre-fill form with person data (simulated)
                    document.getElementById('personName').value = id === 1 ? 'John Doe' : 'Mary Smith';
                    document.getElementById('personTitle').value = id === 1 ? 'Pharmacy Manager' : 'Customer Service Manager';
                    document.getElementById('personPhone').value = id === 1 ? '+265 888 123 456' : '+265 999 456 789';
                    document.getElementById('personEmail').value = id === 1 ? 'john@pharmacare.com' : 'mary@pharmacare.com';
                    document.getElementById('personDepartment').value = id === 1 ? 'management' : 'customer_service';
                    
                    document.getElementById('personModal').classList.add('active');
                }

                function deletePerson(id) {
                    if (confirm('Are you sure you want to remove this contact person?')) {
                        // In real application, delete via API
                        console.log('Deleting person with ID:', id);
                        alert('Contact person removed successfully!');
                        // Reload or update UI
                    }
                }

                function closeModal() {
                    document.getElementById('personModal').classList.remove('active');
                }

                // Form submission for contact person
                document.getElementById('personForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const personData = {
                        id: document.getElementById('personId').value,
                        name: document.getElementById('personName').value,
                        title: document.getElementById('personTitle').value,
                        phone: document.getElementById('personPhone').value,
                        email: document.getElementById('personEmail').value,
                        department: document.getElementById('personDepartment').value,
                        notes: document.getElementById('personNotes').value
                    };
                    
                    console.log('Saving contact person:', personData);
                    alert('Contact person saved successfully!');
                    closeModal();
                    
                    // In real application, update UI or reload data
                });

                // Quick Action Functions
                function downloadCompanyInfo() {
                    alert('Downloading company information as PDF...');
                    // In real application, generate and download PDF
                }

                function printCompanyDetails() {
                    window.print();
                }

                function generateReport() {
                    alert('Generating annual report...');
                    // In real application, generate report
                }

                function exportData() {
                    alert('Exporting company data...');
                    // In real application, export data
                }

                function viewAuditLog() {
                    alert('Opening audit log...');
                    // In real application, show audit log
                }

                // Danger Zone Functions
                function archiveCompany() {
                    if (confirm('WARNING: Archiving will make the company inactive but preserve all data. Continue?')) {
                        alert('Company archived successfully!');
                        // In real application, call archive API
                    }
                }

                function deleteCompany() {
                    const confirmation = confirm('⚠️ DANGER: This will permanently delete the company and ALL associated data. This action cannot be undone!\n\nType "DELETE" to confirm:');
                    if (confirmation) {
                        const userInput = prompt('Please type DELETE to confirm:');
                        if (userInput === 'DELETE') {
                            alert('Company deletion process initiated. This may take a few minutes.');
                            // In real application, call delete API
                        } else {
                            alert('Deletion cancelled.');
                        }
                    }
                }

                // Color picker synchronization
                document.getElementById('primaryColor').addEventListener('input', function() {
                    document.getElementById('primaryColorHex').value = this.value;
                });

                document.getElementById('primaryColorHex').addEventListener('input', function() {
                    document.getElementById('primaryColor').value = this.value;
                });

                document.getElementById('secondaryColor').addEventListener('input', function() {
                    document.getElementById('secondaryColorHex').value = this.value;
                });

                document.getElementById('secondaryColorHex').addEventListener('input', function() {
                    document.getElementById('secondaryColor').value = this.value;
                });

                // Initialize page
                document.addEventListener('DOMContentLoaded', function() {
                    // Set initial states
                    document.querySelector('.action-btn.save').disabled = true;
                    document.querySelector('.action-btn.cancel').style.display = 'none';
                    
                    // Set default values for color inputs
                    document.getElementById('primaryColor').value = '#2a5c8b';
                    document.getElementById('secondaryColor').value = '#1e3a5f';
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