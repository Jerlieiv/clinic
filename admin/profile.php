<?php
// Include configuration file
require_once '../config/config.php';
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
                /* ================== Profile Page Styles ============== */
                .profile-section {
                    padding: 30px;
                    max-width: 1200px;
                    margin: 0 auto;
                }

                .page-header {
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid var(--primary-light);
                }

                .page-header h1 {
                    color: var(--primary);
                    font-size: 2.5rem;
                    margin: 0 0 10px 0;
                    font-weight: 600;
                }

                .page-header p {
                    color: var(--dark-gray);
                    margin: 0;
                    font-size: 1.1rem;
                }

                /* Profile Layout */
                .profile-layout {
                    display: grid;
                    grid-template-columns: 300px 1fr;
                    gap: 30px;
                }

                @media (max-width: 992px) {
                    .profile-layout {
                        grid-template-columns: 1fr;
                        gap: 40px;
                    }
                }

                /* Profile Card - Cleaner Design */
                .profile-card {
                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                    border-radius: 20px;
                    box-shadow: 0 10px 30px rgba(42, 92, 139, 0.08);
                    padding: 35px 25px;
                    text-align: center;
                    position: sticky;
                    top: 30px;
                    border: 1px solid rgba(42, 92, 139, 0.1);
                }

                .profile-header {
                    position: relative;
                    margin-bottom: 30px;
                }

                .profile-picture-container {
                    width: 160px;
                    height: 160px;
                    margin: 0 auto 20px;
                    position: relative;
                }

                .profile-picture {
                    width: 100%;
                    height: 100%;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 4px solid white;
                    box-shadow: 0 8px 25px rgba(42, 92, 139, 0.15);
                    transition: all 0.3s ease;
                }

                .profile-picture:hover {
                    transform: scale(1.03);
                    box-shadow: 0 12px 30px rgba(42, 92, 139, 0.25);
                }

                .change-photo-btn {
                    position: absolute;
                    bottom: 10px;
                    right: 10px;
                    width: 45px;
                    height: 45px;
                    border-radius: 50%;
                    background: var(--primary);
                    color: white;
                    border: none;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.3rem;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(42, 92, 139, 0.3);
                }

                .change-photo-btn:hover {
                    background: var(--secondary);
                    transform: scale(1.1);
                    box-shadow: 0 6px 20px rgba(42, 92, 139, 0.4);
                }

                .profile-info h2 {
                    margin: 0 0 8px 0;
                    color: var(--black);
                    font-size: 1.6rem;
                    font-weight: 600;
                }

                .profile-title {
                    color: var(--primary);
                    font-weight: 500;
                    margin: 0 0 25px 0;
                    padding: 8px 20px;
                    background: linear-gradient(135deg, rgba(42, 92, 139, 0.1) 0%, rgba(42, 92, 139, 0.05) 100%);
                    border-radius: 25px;
                    display: inline-block;
                    font-size: 0.95rem;
                }

                /* Profile Details - Clean List */
                .profile-details {
                    text-align: left;
                    margin: 30px 0;
                }

                .profile-detail-item {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    margin-bottom: 18px;
                    padding: 12px 15px;
                    border-radius: 12px;
                    transition: all 0.3s ease;
                    border: 1px solid transparent;
                }

                .profile-detail-item:hover {
                    background: rgba(42, 92, 139, 0.05);
                    border-color: rgba(42, 92, 139, 0.1);
                    transform: translateX(5px);
                }

                .detail-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 10px;
                    background: linear-gradient(135deg, rgba(42, 92, 139, 0.1) 0%, rgba(42, 92, 139, 0.2) 100%);
                    color: var(--primary);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.2rem;
                    flex-shrink: 0;
                }

                .detail-text {
                    flex: 1;
                    min-width: 0;
                }

                .detail-label {
                    font-size: 0.85rem;
                    color: var(--dark-gray);
                    margin-bottom: 4px;
                    font-weight: 500;
                }

                .detail-value {
                    color: var(--black);
                    font-weight: 500;
                    font-size: 1rem;
                    word-break: break-word;
                }

                /* Profile Stats - Minimal */
                .profile-stats {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                    margin-top: 30px;
                    padding-top: 25px;
                    border-top: 1px solid rgba(42, 92, 139, 0.1);
                }

                .stat-item {
                    text-align: center;
                    padding: 15px;
                    border-radius: 12px;
                    background: rgba(42, 92, 139, 0.03);
                    transition: all 0.3s ease;
                }

                .stat-item:hover {
                    background: rgba(42, 92, 139, 0.08);
                    transform: translateY(-3px);
                }

                .stat-number {
                    display: block;
                    font-size: 1.8rem;
                    font-weight: 700;
                    color: var(--primary);
                    margin-bottom: 5px;
                }

                .stat-label {
                    font-size: 0.9rem;
                    color: var(--dark-gray);
                    font-weight: 500;
                }

                /* Profile Content - Clean Cards */
                .profile-content {
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                    overflow: hidden;
                    border: 1px solid rgba(0, 0, 0, 0.05);
                }

                /* Tabs - Clean Design */
                .profile-tabs {
                    display: flex;
                    background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
                    border-bottom: 2px solid rgba(42, 92, 139, 0.1);
                    padding: 0 30px;
                    overflow-x: auto;
                }

                .tab-btn {
                    padding: 22px 30px;
                    background: none;
                    border: none;
                    cursor: pointer;
                    font-weight: 500;
                    color: var(--dark-gray);
                    position: relative;
                    white-space: nowrap;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    font-size: 1rem;
                    border-bottom: 3px solid transparent;
                }

                .tab-btn:hover {
                    color: var(--primary);
                    background: rgba(255, 255, 255, 0.8);
                }

                .tab-btn.active {
                    color: var(--primary);
                    background: white;
                    font-weight: 600;
                    border-bottom: 3px solid var(--primary);
                }

                .tab-btn ion-icon {
                    font-size: 1.2rem;
                }

                /* Tab Content - Clean Layout */
                .tab-content {
                    display: none;
                    padding: 40px;
                }

                .tab-content.active {
                    display: block;
                    animation: slideIn 0.4s ease;
                }

                @keyframes slideIn {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .tab-header {
                    margin-bottom: 35px;
                }

                .tab-header h3 {
                    color: var(--primary);
                    margin: 0 0 12px 0;
                    font-size: 1.8rem;
                    font-weight: 600;
                }

                .tab-header p {
                    color: var(--dark-gray);
                    margin: 0;
                    font-size: 1rem;
                    line-height: 1.6;
                }

                /* Form Styles - Clean */
                .profile-form {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 25px;
                }

                @media (max-width: 768px) {
                    .profile-form {
                        grid-template-columns: 1fr;
                    }
                }

                .form-group {
                    display: flex;
                    flex-direction: column;
                }

                .form-group.full-width {
                    grid-column: 1 / -1;
                }

                .form-group label {
                    margin-bottom: 10px;
                    font-weight: 600;
                    color: var(--black);
                    font-size: 0.95rem;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                .form-group label.required::after {
                    content: " *";
                    color: var(--danger);
                }

                .form-group input,
                .form-group select,
                .form-group textarea {
                    padding: 14px 18px;
                    border: 2px solid rgba(42, 92, 139, 0.1);
                    border-radius: 10px;
                    font-size: 1rem;
                    transition: all 0.3s ease;
                    background: white;
                }

                .form-group input:focus,
                .form-group select:focus,
                .form-group textarea:focus {
                    outline: none;
                    border-color: var(--primary);
                    box-shadow: 0 0 0 4px rgba(42, 92, 139, 0.1);
                }

                .form-group textarea {
                    min-height: 120px;
                    resize: vertical;
                    line-height: 1.5;
                }

                .input-with-icon {
                    position: relative;
                }

                .input-with-icon input {
                    padding-right: 50px;
                }

                .input-icon {
                    position: absolute;
                    right: 18px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: var(--dark-gray);
                    cursor: pointer;
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    padding: 5px;
                    border-radius: 5px;
                    transition: all 0.3s ease;
                }

                .input-icon:hover {
                    background: rgba(42, 92, 139, 0.1);
                    color: var(--primary);
                }

                /* Security Tab - Password Change Only */
                .security-section {
                    max-width: 600px;
                    margin: 0 auto;
                }

                .password-card {
                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                    border-radius: 15px;
                    padding: 40px;
                    border: 2px solid rgba(42, 92, 139, 0.1);
                    box-shadow: 0 8px 25px rgba(42, 92, 139, 0.05);
                }

                .password-header {
                    text-align: center;
                    margin-bottom: 40px;
                }

                .password-header ion-icon {
                    font-size: 3.5rem;
                    color: var(--primary);
                    margin-bottom: 20px;
                    display: block;
                }

                .password-header h4 {
                    margin: 0 0 10px 0;
                    color: var(--primary);
                    font-size: 1.5rem;
                }

                .password-header p {
                    color: var(--dark-gray);
                    margin: 0;
                    line-height: 1.6;
                }

                .password-form {
                    display: flex;
                    flex-direction: column;
                    gap: 25px;
                }

                .password-strength {
                    margin-top: 10px;
                }

                .strength-bar {
                    height: 8px;
                    background: #e0e0e0;
                    border-radius: 4px;
                    overflow: hidden;
                    margin-top: 8px;
                }

                .strength-fill {
                    height: 100%;
                    width: 0%;
                    background: var(--danger);
                    border-radius: 4px;
                    transition: all 0.3s ease;
                }

                .strength-fill.weak { width: 33%; background: var(--danger); }
                .strength-fill.medium { width: 66%; background: var(--warning); }
                .strength-fill.strong { width: 100%; background: var(--success); }

                .password-requirements {
                    margin-top: 30px;
                    padding-top: 25px;
                    border-top: 1px solid rgba(42, 92, 139, 0.1);
                }

                .requirements-title {
                    font-weight: 600;
                    color: var(--black);
                    margin-bottom: 15px;
                    font-size: 1rem;
                }

                .requirements-list {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 12px;
                }

                @media (max-width: 768px) {
                    .requirements-list {
                        grid-template-columns: 1fr;
                    }
                }

                .requirement-item {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 0.9rem;
                    color: var(--dark-gray);
                }

                .requirement-item.valid {
                    color: var(--success);
                }

                .requirement-item ion-icon {
                    font-size: 1.1rem;
                }

                /* Action Buttons - Clean */
                .action-buttons {
                    display: flex;
                    gap: 20px;
                    justify-content: flex-end;
                    margin-top: 40px;
                    padding-top: 30px;
                    border-top: 1px solid rgba(42, 92, 139, 0.1);
                }

                @media (max-width: 768px) {
                    .action-buttons {
                        flex-direction: column;
                    }
                }

                .action-btn {
                    padding: 15px 30px;
                    border-radius: 12px;
                    border: none;
                    cursor: pointer;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    transition: all 0.3s ease;
                    font-size: 1rem;
                }

                .action-btn.primary {
                    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
                    color: white;
                    box-shadow: 0 6px 20px rgba(42, 92, 139, 0.3);
                }

                .action-btn.primary:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 10px 25px rgba(42, 92, 139, 0.4);
                }

                .action-btn.secondary {
                    background: rgba(42, 92, 139, 0.05);
                    color: var(--primary);
                    border: 2px solid rgba(42, 92, 139, 0.2);
                }

                .action-btn.secondary:hover {
                    background: rgba(42, 92, 139, 0.1);
                    transform: translateY(-3px);
                }

                .action-btn.danger {
                    background: linear-gradient(135deg, var(--danger) 0%, #c0392b 100%);
                    color: white;
                    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.3);
                }

                .action-btn.danger:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 10px 25px rgba(231, 76, 60, 0.4);
                }

                /* Modal Styles */
                .modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.6);
                    display: none;
                    align-items: center;
                    justify-content: center;
                    z-index: 2000;
                    backdrop-filter: blur(4px);
                }

                .modal-overlay.active {
                    display: flex;
                    animation: fadeIn 0.3s ease;
                }

                .modal-content {
                    background: white;
                    border-radius: 20px;
                    padding: 0;
                    width: 90%;
                    max-width: 500px;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
                }

                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 30px;
                    border-bottom: 1px solid rgba(42, 92, 139, 0.1);
                }

                .modal-header h3 {
                    margin: 0;
                    color: var(--primary);
                    font-size: 1.8rem;
                }

                .modal-close {
                    background: none;
                    border: none;
                    font-size: 2rem;
                    cursor: pointer;
                    color: var(--dark-gray);
                    width: 45px;
                    height: 45px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                }

                .modal-close:hover {
                    background: rgba(42, 92, 139, 0.1);
                }

                .modal-body {
                    padding: 30px;
                    text-align: center;
                }

                .modal-body ion-icon {
                    font-size: 4rem;
                    color: var(--success);
                    margin-bottom: 20px;
                    display: block;
                }

                .modal-body h4 {
                    margin: 0 0 15px 0;
                    color: var(--black);
                    font-size: 1.5rem;
                }

                .modal-body p {
                    margin: 0 0 25px 0;
                    color: var(--dark-gray);
                    line-height: 1.6;
                }

                .modal-footer {
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    padding: 25px 30px;
                    border-top: 1px solid rgba(42, 92, 139, 0.1);
                }

                /* Empty State */
                .empty-state {
                    text-align: center;
                    padding: 80px 30px;
                }

                .empty-state ion-icon {
                    font-size: 5rem;
                    color: rgba(42, 92, 139, 0.2);
                    margin-bottom: 25px;
                    display: block;
                }

                .empty-state h3 {
                    color: var(--dark-gray);
                    margin: 0 0 15px 0;
                    font-size: 1.5rem;
                }

                .empty-state p {
                    color: var(--dark-gray);
                    margin: 0;
                    max-width: 400px;
                    margin: 0 auto;
                    line-height: 1.6;
                }

                /* Responsive Design */
                @media (max-width: 768px) {
                    .profile-section {
                        padding: 20px;
                    }

                    .page-header h1 {
                        font-size: 2rem;
                    }

                    .profile-tabs {
                        padding: 0 15px;
                    }

                    .tab-btn {
                        padding: 18px 20px;
                        font-size: 0.95rem;
                    }

                    .tab-content {
                        padding: 25px;
                    }

                    .password-card {
                        padding: 25px;
                    }

                    .modal-content {
                        width: 95%;
                        margin: 10px;
                    }
                }

                @media (max-width: 480px) {
                    .profile-picture-container {
                        width: 130px;
                        height: 130px;
                    }

                    .profile-stats {
                        grid-template-columns: 1fr;
                    }

                    .profile-tabs {
                        flex-direction: column;
                    }

                    .tab-btn {
                        justify-content: center;
                        border-bottom: none;
                        border-left: 3px solid transparent;
                    }

                    .tab-btn.active {
                        border-bottom: none;
                        border-left: 3px solid var(--primary);
                    }

                    .action-btn {
                        padding: 12px 20px;
                        font-size: 0.95rem;
                        justify-content: center;
                    }
                }
            </style>

            <!-- ================== Profile Page Content ============== -->
            <div class="profile-section">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>My Profile</h1>
                    <p>Manage your personal information and account settings</p>
                </div>

                <!-- Profile Layout -->
                <div class="profile-layout">
                    <!-- Left Sidebar - Profile Card -->
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-picture-container">
                                <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=500&q=80" 
                                     alt="Profile Picture" class="profile-picture" id="profilePicture">
                                <button class="change-photo-btn" onclick="changeProfilePicture()">
                                    <ion-icon name="camera-outline"></ion-icon>
                                </button>
                            </div>
                            
                            <div class="profile-info">
                                <h2 id="userName">John Doe</h2>
                                <div class="profile-title" id="userTitle">Senior Pharmacist</div>
                            </div>
                        </div>
                        
                        <div class="profile-details">
                            <div class="profile-detail-item">
                                <div class="detail-icon">
                                    <ion-icon name="person-circle-outline"></ion-icon>
                                </div>
                                <div class="detail-text">
                                    <div class="detail-label">Username</div>
                                    <div class="detail-value" id="userUsername">john.doe</div>
                                </div>
                            </div>
                            
                            <div class="profile-detail-item">
                                <div class="detail-icon">
                                    <ion-icon name="mail-outline"></ion-icon>
                                </div>
                                <div class="detail-text">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value" id="userEmail">john.doe@pharmacare.com</div>
                                </div>
                            </div>
                            
                            <div class="profile-detail-item">
                                <div class="detail-icon">
                                    <ion-icon name="business-outline"></ion-icon>
                                </div>
                                <div class="detail-text">
                                    <div class="detail-label">Branch</div>
                                    <div class="detail-value" id="userBranch">PharmaCare Main</div>
                                </div>
                            </div>
                            
                            <div class="profile-detail-item">
                                <div class="detail-icon">
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                                <div class="detail-text">
                                    <div class="detail-label">Member Since</div>
                                    <div class="detail-value" id="memberSince">January 15, 2020</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-number" id="totalTasks">47</span>
                                <span class="stat-label">Total Tasks</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="completionRate">89%</span>
                                <span class="stat-label">Completion Rate</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content - Profile Tabs -->
                    <div class="profile-content">
                        <!-- Tabs Navigation -->
                        <div class="profile-tabs">
                            <button class="tab-btn active" onclick="switchTab('personal')">
                                <ion-icon name="person-outline"></ion-icon>
                                Personal Info
                            </button>
                            <button class="tab-btn" onclick="switchTab('account')">
                                <ion-icon name="settings-outline"></ion-icon>
                                Account Settings
                            </button>
                            <button class="tab-btn" onclick="switchTab('security')">
                                <ion-icon name="lock-closed-outline"></ion-icon>
                                Security
                            </button>
                        </div>

                        <!-- Personal Info Tab -->
                        <div class="tab-content active" id="personalTab">
                            <div class="tab-header">
                                <h3>Personal Information</h3>
                                <p>Update your personal details and contact information</p>
                            </div>
                            
                            <form id="personalForm" class="profile-form">
                                <div class="form-group">
                                    <label for="firstName" class="required">First Name</label>
                                    <input type="text" id="firstName" value="John" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="lastName" class="required">Last Name</label>
                                    <input type="text" id="lastName" value="Doe" required>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="email" class="required">Email Address</label>
                                    <input type="email" id="email" value="john.doe@pharmacare.com" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone" class="required">Phone Number</label>
                                    <input type="tel" id="phone" value="+265 123 456 789" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="dob">Date of Birth</label>
                                    <input type="date" id="dob" value="1990-01-15">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="address">Physical Address</label>
                                    <input type="text" id="address" value="M1 Road, Balaka, Malawi">
                                </div>
                                
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender">
                                        <option value="male" selected>Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                        <option value="prefer-not-to-say">Prefer not to say</option>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="bio">Professional Bio</label>
                                    <textarea id="bio">Senior Pharmacist with 8+ years of experience in pharmaceutical care and management. Specialized in clinical pharmacy and patient counseling.</textarea>
                                </div>
                                
                                <div class="action-buttons">
                                    <button type="button" class="action-btn secondary" onclick="resetPersonalForm()">
                                        <ion-icon name="refresh-outline"></ion-icon>
                                        Reset Changes
                                    </button>
                                    <button type="button" class="action-btn primary" onclick="savePersonalInfo()">
                                        <ion-icon name="checkmark-outline"></ion-icon>
                                        Save Information
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Account Settings Tab -->
                        <div class="tab-content" id="accountTab">
                            <div class="tab-header">
                                <h3>Account Settings</h3>
                                <p>Configure your account preferences and notification settings</p>
                            </div>
                            
                            <div class="profile-form">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <div class="input-with-icon">
                                        <input type="text" id="username" value="john.doe" readonly>
                                        <span class="input-icon">
                                            <ion-icon name="lock-closed-outline"></ion-icon>
                                        </span>
                                    </div>
                                    <small style="color: var(--dark-gray); margin-top: 8px; display: block;">
                                        Username cannot be changed
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="language">Preferred Language</label>
                                    <select id="language">
                                        <option value="en" selected>English</option>
                                        <option value="ny">Chichewa</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="timezone">Timezone</label>
                                    <select id="timezone">
                                        <option value="Africa/Blantyre" selected>Africa/Blantyre (GMT+2)</option>
                                        <option value="Africa/Lusaka">Africa/Lusaka</option>
                                        <option value="Africa/Johannesburg">Africa/Johannesburg</option>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="notifications">Email Notifications</label>
                                    <select id="notifications">
                                        <option value="all" selected>All notifications</option>
                                        <option value="important">Important only</option>
                                        <option value="none">No email notifications</option>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="communication">Preferred Communication</label>
                                    <select id="communication">
                                        <option value="email" selected>Email</option>
                                        <option value="sms">SMS</option>
                                        <option value="both">Both Email & SMS</option>
                                    </select>
                                </div>
                                
                                <div class="action-buttons">
                                    <button type="button" class="action-btn primary" onclick="saveAccountSettings()">
                                        <ion-icon name="save-outline"></ion-icon>
                                        Save Settings
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Security Tab - Password Change Only -->
                        <div class="tab-content" id="securityTab">
                            <div class="tab-header">
                                <h3>Security Settings</h3>
                                <p>Change your password to keep your account secure</p>
                            </div>
                            
                            <div class="security-section">
                                <div class="password-card">
                                    <div class="password-header">
                                        <ion-icon name="key-outline"></ion-icon>
                                        <h4>Update Password</h4>
                                        <p>Create a strong password with at least 8 characters including letters, numbers, and symbols</p>
                                    </div>
                                    
                                    <form id="passwordForm" class="password-form">
                                        <div class="form-group">
                                            <label for="currentPassword" class="required">Current Password</label>
                                            <div class="input-with-icon">
                                                <input type="password" id="currentPassword" required>
                                                <button type="button" class="input-icon" onclick="togglePassword('currentPassword', this)">
                                                    <ion-icon name="eye-outline"></ion-icon>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="newPassword" class="required">New Password</label>
                                            <div class="input-with-icon">
                                                <input type="password" id="newPassword" required oninput="checkPasswordStrength()">
                                                <button type="button" class="input-icon" onclick="togglePassword('newPassword', this)">
                                                    <ion-icon name="eye-outline"></ion-icon>
                                                </button>
                                            </div>
                                            <div class="password-strength">
                                                <div class="strength-bar">
                                                    <div class="strength-fill" id="strengthFill"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="confirmPassword" class="required">Confirm New Password</label>
                                            <div class="input-with-icon">
                                                <input type="password" id="confirmPassword" required>
                                                <button type="button" class="input-icon" onclick="togglePassword('confirmPassword', this)">
                                                    <ion-icon name="eye-outline"></ion-icon>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="password-requirements">
                                            <div class="requirements-title">Password Requirements:</div>
                                            <ul class="requirements-list" id="requirementsList">
                                                <li class="requirement-item" id="reqLength">
                                                    <ion-icon name="ellipse-outline"></ion-icon>
                                                    At least 8 characters
                                                </li>
                                                <li class="requirement-item" id="reqUppercase">
                                                    <ion-icon name="ellipse-outline"></ion-icon>
                                                    One uppercase letter
                                                </li>
                                                <li class="requirement-item" id="reqLowercase">
                                                    <ion-icon name="ellipse-outline"></ion-icon>
                                                    One lowercase letter
                                                </li>
                                                <li class="requirement-item" id="reqNumber">
                                                    <ion-icon name="ellipse-outline"></ion-icon>
                                                    One number
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <button type="button" class="action-btn primary" onclick="changePassword()">
                                                <ion-icon name="checkmark-circle-outline"></ion-icon>
                                                Update Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Modal -->
            <div class="modal-overlay" id="successModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Success</h3>
                        <button class="modal-close" onclick="closeModal()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <ion-icon name="checkmark-circle-outline"></ion-icon>
                        <h4 id="successTitle">Update Successful</h4>
                        <p id="successMessage">Your changes have been saved successfully.</p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="action-btn primary" onclick="closeModal()">
                            Continue
                        </button>
                    </div>
                </div>
            </div>

            <script>
                // User Data
                const userData = {
                    name: "John Doe",
                    title: "Senior Pharmacist",
                    username: "john.doe",
                    email: "john.doe@pharmacare.com",
                    branch: "PharmaCare Main",
                    memberSince: "January 15, 2020",
                    tasks: {
                        total: 47,
                        completed: 42
                    }
                };

                // Initialize
                document.addEventListener('DOMContentLoaded', function() {
                    loadUserProfile();
                    initFormValidation();
                    checkPasswordStrength(); // Initial check
                });

                // Load User Profile
                function loadUserProfile() {
                    document.getElementById('userName').textContent = userData.name;
                    document.getElementById('userTitle').textContent = userData.title;
                    document.getElementById('userUsername').textContent = userData.username;
                    document.getElementById('userEmail').textContent = userData.email;
                    document.getElementById('userBranch').textContent = userData.branch;
                    document.getElementById('memberSince').textContent = userData.memberSince;
                    document.getElementById('totalTasks').textContent = userData.tasks.total;
                    
                    // Calculate completion rate
                    const completionRate = Math.round((userData.tasks.completed / userData.tasks.total) * 100);
                    document.getElementById('completionRate').textContent = `${completionRate}%`;
                }

                // Tab Switching
                function switchTab(tabName) {
                    // Hide all tabs
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.classList.remove('active');
                    });
                    
                    // Remove active class from all tab buttons
                    document.querySelectorAll('.tab-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Show selected tab
                    document.getElementById(tabName + 'Tab').classList.add('active');
                    
                    // Activate selected tab button
                    event.currentTarget.classList.add('active');
                    
                    // Scroll to top of tab content
                    document.getElementById(tabName + 'Tab').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                // Form Validation
                function initFormValidation() {
                    const forms = document.querySelectorAll('form');
                    forms.forEach(form => {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                        });
                    });
                }

                // Save Personal Info
                function savePersonalInfo() {
                    const firstName = document.getElementById('firstName').value.trim();
                    const lastName = document.getElementById('lastName').value.trim();
                    const email = document.getElementById('email').value.trim();
                    const phone = document.getElementById('phone').value.trim();
                    
                    if (!firstName || !lastName || !email || !phone) {
                        showNotification('Please fill in all required fields.', 'error');
                        return;
                    }
                    
                    if (!validateEmail(email)) {
                        showNotification('Please enter a valid email address.', 'error');
                        return;
                    }
                    
                    // Update user data
                    userData.name = `${firstName} ${lastName}`;
                    userData.email = email;
                    
                    // Update UI
                    loadUserProfile();
                    
                    // Show success
                    showSuccessModal('Personal Information Updated', 'Your personal information has been updated successfully.');
                }

                // Reset Personal Form
                function resetPersonalForm() {
                    if (confirm('Are you sure you want to reset all changes?')) {
                        document.getElementById('firstName').value = 'John';
                        document.getElementById('lastName').value = 'Doe';
                        document.getElementById('email').value = 'john.doe@pharmacare.com';
                        document.getElementById('phone').value = '+265 123 456 789';
                        document.getElementById('dob').value = '1990-01-15';
                        document.getElementById('address').value = 'M1 Road, Balaka, Malawi';
                        document.getElementById('gender').value = 'male';
                        document.getElementById('bio').value = 'Senior Pharmacist with 8+ years of experience in pharmaceutical care and management. Specialized in clinical pharmacy and patient counseling.';
                        
                        showNotification('Form has been reset to original values', 'info');
                    }
                }

                // Save Account Settings
                function saveAccountSettings() {
                    const language = document.getElementById('language').value;
                    const timezone = document.getElementById('timezone').value;
                    const notifications = document.getElementById('notifications').value;
                    const communication = document.getElementById('communication').value;
                    
                    // Save settings
                    localStorage.setItem('userSettings', JSON.stringify({
                        language,
                        timezone,
                        notifications,
                        communication
                    }));
                    
                    showSuccessModal('Settings Saved', 'Your account settings have been updated successfully.');
                }

                // Change Password
                function changePassword() {
                    const currentPassword = document.getElementById('currentPassword').value;
                    const newPassword = document.getElementById('newPassword').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;
                    
                    // Validation
                    if (!currentPassword || !newPassword || !confirmPassword) {
                        showNotification('Please fill in all password fields.', 'error');
                        return;
                    }
                    
                    if (newPassword !== confirmPassword) {
                        showNotification('New passwords do not match.', 'error');
                        return;
                    }
                    
                    if (!isPasswordStrong(newPassword)) {
                        showNotification('Please choose a stronger password.', 'error');
                        return;
                    }
                    
                    // In a real app, this would be an API call
                    console.log('Password change request:', {
                        currentPassword,
                        newPassword
                    });
                    
                    // Show success
                    showSuccessModal('Password Updated', 'Your password has been changed successfully. Please use your new password for future logins.');
                    
                    // Clear form
                    document.getElementById('passwordForm').reset();
                    checkPasswordStrength(); // Reset strength indicator
                }

                // Toggle Password Visibility
                function togglePassword(inputId, button) {
                    const input = document.getElementById(inputId);
                    const icon = button.querySelector('ion-icon');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.name = 'eye-off-outline';
                    } else {
                        input.type = 'password';
                        icon.name = 'eye-outline';
                    }
                }

                // Check Password Strength
                function checkPasswordStrength() {
                    const password = document.getElementById('newPassword').value;
                    const strengthFill = document.getElementById('strengthFill');
                    
                    // Check requirements
                    const hasLength = password.length >= 8;
                    const hasUppercase = /[A-Z]/.test(password);
                    const hasLowercase = /[a-z]/.test(password);
                    const hasNumber = /[0-9]/.test(password);
                    
                    // Update requirement indicators
                    updateRequirement('reqLength', hasLength);
                    updateRequirement('reqUppercase', hasUppercase);
                    updateRequirement('reqLowercase', hasLowercase);
                    updateRequirement('reqNumber', hasNumber);
                    
                    // Calculate strength
                    let strength = 0;
                    if (hasLength) strength += 25;
                    if (hasUppercase) strength += 25;
                    if (hasLowercase) strength += 25;
                    if (hasNumber) strength += 25;
                    
                    // Update strength bar
                    strengthFill.style.width = `${strength}%`;
                    strengthFill.className = 'strength-fill';
                    
                    if (strength < 33) {
                        strengthFill.classList.add('weak');
                    } else if (strength < 66) {
                        strengthFill.classList.add('medium');
                    } else {
                        strengthFill.classList.add('strong');
                    }
                }

                // Update requirement indicator
                function updateRequirement(elementId, isValid) {
                    const element = document.getElementById(elementId);
                    const icon = element.querySelector('ion-icon');
                    
                    if (isValid) {
                        element.classList.add('valid');
                        icon.name = 'checkmark-circle-outline';
                    } else {
                        element.classList.remove('valid');
                        icon.name = 'ellipse-outline';
                    }
                }

                // Check if password is strong
                function isPasswordStrong(password) {
                    return password.length >= 8 &&
                           /[A-Z]/.test(password) &&
                           /[a-z]/.test(password) &&
                           /[0-9]/.test(password);
                }

                // Validate email
                function validateEmail(email) {
                    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                }

                // Change Profile Picture
                function changeProfilePicture() {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    
                    input.onchange = function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            // Check file size (5MB limit)
                            if (file.size > 5 * 1024 * 1024) {
                                showNotification('File size must be less than 5MB.', 'error');
                                return;
                            }
                            
                            // Check file type
                            if (!file.type.match('image.*')) {
                                showNotification('Please select an image file.', 'error');
                                return;
                            }
                            
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                // Update profile picture
                                document.getElementById('profilePicture').src = e.target.result;
                                
                                // Show success
                                showSuccessModal('Profile Picture Updated', 'Your profile picture has been updated successfully.');
                            };
                            reader.readAsDataURL(file);
                        }
                    };
                    
                    input.click();
                }

                // Show Success Modal
                function showSuccessModal(title, message) {
                    document.getElementById('successTitle').textContent = title;
                    document.getElementById('successMessage').textContent = message;
                    document.getElementById('successModal').classList.add('active');
                }

                // Close Modal
                function closeModal() {
                    document.getElementById('successModal').classList.remove('active');
                }

                // Show Notification
                function showNotification(message, type) {
                    // Create notification element
                    const notification = document.createElement('div');
                    notification.className = 'notification';
                    
                    const backgroundColor = type === 'success' ? 'var(--success)' : 
                                          type === 'error' ? 'var(--danger)' : 'var(--primary)';
                    
                    notification.innerHTML = `
                        <div style="
                            position: fixed;
                            top: 30px;
                            right: 30px;
                            background: ${backgroundColor};
                            color: white;
                            padding: 18px 25px;
                            border-radius: 12px;
                            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                            z-index: 10000;
                            display: flex;
                            align-items: center;
                            gap: 12px;
                            animation: slideInRight 0.3s ease, fadeOut 0.3s ease 2.7s;
                            max-width: 400px;
                        ">
                            <ion-icon name="${type === 'success' ? 'checkmark-circle' : 
                                           type === 'error' ? 'alert-circle' : 'information-circle'}-outline"></ion-icon>
                            <span style="flex: 1;">${message}</span>
                        </div>
                    `;
                    
                    document.body.appendChild(notification);
                    
                    // Add CSS animations
                    const style = document.createElement('style');
                    style.textContent = `
                        @keyframes slideInRight {
                            from { transform: translateX(100%); opacity: 0; }
                            to { transform: translateX(0); opacity: 1; }
                        }
                        @keyframes fadeOut {
                            from { opacity: 1; }
                            to { opacity: 0; }
                        }
                    `;
                    document.head.appendChild(style);
                    
                    // Remove after 3 seconds
                    setTimeout(() => {
                        notification.remove();
                        style.remove();
                    }, 3000);
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
                    if (e.ctrlKey && e.key === 's') {
                        e.preventDefault();
                        const activeTab = document.querySelector('.tab-content.active');
                        if (activeTab.id === 'personalTab') {
                            savePersonalInfo();
                        } else if (activeTab.id === 'accountTab') {
                            saveAccountSettings();
                        }
                    }
                });

                // Add smooth scrolling for anchor links
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
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