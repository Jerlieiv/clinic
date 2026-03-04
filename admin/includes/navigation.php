<!-- =============== Top Navigation ================ -->
<div class="topbar">
    <div class="toggle" id="menuToggle">
        <ion-icon name="menu-outline"></ion-icon>
    </div>

    <div class="search">
        <label>
            <input type="text" placeholder="Search drugs, customers...">
            <ion-icon name="search-outline"></ion-icon>
        </label>
    </div>

    <div class="user-info">
        <div class="liveStats">
            <div class="liveStat users">
                <span class="liveDot"></span>
                <span>3 Online</span>
            </div>
            <div class="liveStat sales">
                <span class="liveDot"></span>
                <span>5 Sales/Min</span>
            </div>
        </div>
        
        <div class="notifications tooltip" data-tooltip="Notifications">
            <ion-icon name="notifications-outline"></ion-icon>
            <span class="notification-count">3</span>
        </div>
        <div class="user" id="userProfile">
            <img src="https://ui-avatars.com/api/?name=Dr+John+Phiri&background=2a5c8b&color=fff" alt="Admin">
        </div>
        
        <!-- User Modal -->
        <div class="user-modal" id="userModal" style="display: none;">
            <div class="user-modal-header">
                <img src="https://ui-avatars.com/api/?name=Dr+John+Phiri&background=ffffff&color=2a5c8b" alt="User">
                <div>
                    <h4>Dr. John Phiri</h4>
                    <p>Pharmacy Manager</p>
                </div>
            </div>
            <div class="user-modal-options">
                <div class="user-modal-option" onclick="window.location.href='profile.php'">
                    <ion-icon name="person-outline"></ion-icon>
                    <span>My Profile</span>
                </div>
                <div class="user-modal-option" onclick="window.location.href='settings.php'">
                    <ion-icon name="settings-outline"></ion-icon>
                    <span>System Settings</span>
                </div>
                <div class="user-modal-option" onclick="window.location.href='change-password.php'">
                    <ion-icon name="lock-closed-outline"></ion-icon>
                    <span>Change Password</span>
                </div>
                <div class="user-modal-option" onclick="window.location.href='security.php'">
                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                    <span>Security Settings</span>
                </div>
                <div class="user-modal-option" onclick="window.location.href='user-management.php'">
                    <ion-icon name="people-circle-outline"></ion-icon>
                    <span>User Management</span>
                </div>
                <div class="user-modal-option" onclick="window.location.href='branch-configuration.php'">
                    <ion-icon name="business-outline"></ion-icon>
                    <span>Branch Configuration</span>
                </div>
                <div class="user-modal-option logout">
                    <a href="../logout.php" class="logout-link" id="logoutButton">
                        <ion-icon name="log-out-outline"></ion-icon>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
/* User Modal Styles */
.user-modal {
    position: absolute;
    top: 60px;
    right: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    width: 280px;
    z-index: 1000;
    overflow: hidden;
}

.user-modal-header {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}

.user-modal-header img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 15px;
    border: 3px solid #2a5c8b;
}

.user-modal-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.user-modal-header p {
    margin: 5px 0 0 0;
    font-size: 14px;
    color: #666;
}

.user-modal-options {
    padding: 10px 0;
}

.user-modal-option {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.user-modal-option:hover {
    background-color: #f8f9fa;
}

.user-modal-option ion-icon {
    font-size: 20px;
    color: #666;
    margin-right: 12px;
    min-width: 24px;
    text-align: center;
}

.user-modal-option span {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

/* Logout option specific styling */
.user-modal-option.logout {
    border-top: 1px solid #eee;
    margin-top: 5px;
    padding-top: 15px;
}

.user-modal-option.logout:hover {
    background-color: #fff5f5;
}

.user-modal-option.logout ion-icon {
    color: #e74c3c;
}

.user-modal-option.logout span {
    color: #e74c3c;
    font-weight: 600;
}

/* Logout link styling */
.logout-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    width: 100%;
    color: inherit;
}

.logout-link:hover {
    text-decoration: none;
    color: inherit;
}

/* User profile image styling */
.user {
    cursor: pointer;
    position: relative;
}

.user img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #2a5c8b;
    transition: transform 0.2s;
}

.user:hover img {
    transform: scale(1.05);
}

/* SweetAlert customization */
.swal2-popup {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    border-radius: 12px !important;
    padding: 1.5rem !important;
}

.swal2-title {
    font-size: 1.5rem !important;
    font-weight: 600 !important;
    color: #333 !important;
    margin-bottom: 0.5rem !important;
}

.swal2-html-container {
    font-size: 1rem !important;
    color: #666 !important;
    margin: 0.5rem 0 1rem 0 !important;
}

.swal2-actions {
    margin: 1.5rem auto 0 auto !important;
    gap: 12px !important; /* Space between buttons */
}

.swal2-confirm {
    background: linear-gradient(135deg, #2a5c8b 0%, #1abc9c 100%) !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 10px 24px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    min-width: 120px !important;
}

.swal2-confirm:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(42, 92, 139, 0.3) !important;
}

.swal2-cancel {
    background: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    color: #495057 !important;
    border-radius: 8px !important;
    padding: 10px 24px !important;
    font-weight: 500 !important;
    transition: all 0.3s ease !important;
    min-width: 120px !important;
}

.swal2-cancel:hover {
    background: #e9ecef !important;
}

/* Button spacing in the logout modal - Improved */
.swal2-styled.swal2-confirm {
    margin-left: 0 !important;
}

.swal2-styled.swal2-cancel {
    margin-right: 0 !important;
}

/* SweetAlert2 icon styling */
.swal2-icon {
    margin: 1.5rem auto 1rem auto !important;
}

.swal2-icon.swal2-warning {
    border-color: #ffcc00 !important;
    color: #ffcc00 !important;
}

/* Loading overlay for logout */
.logout-loading {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.logout-loading.active {
    opacity: 1;
    visibility: visible;
}

.logout-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #2a5c8b;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

.logout-text {
    color: white;
    font-size: 18px;
    font-weight: 500;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Custom styling for the logout modal content */
.custom-logout-icon {
    font-size: 48px !important;
    color: #2a5c8b !important;
    margin-bottom: 15px !important;
}

.custom-logout-text {
    font-size: 16px !important;
    color: #666 !important;
    margin-bottom: 5px !important;
    line-height: 1.5 !important;
}

.custom-logout-subtext {
    font-size: 14px !important;
    color: #999 !important;
    line-height: 1.4 !important;
}

.custom-logout-container {
    text-align: center !important;
    padding: 10px 0 !important;
}
</style>

<!-- Loading Overlay for Logout -->
<div class="logout-loading" id="logoutLoading">
    <div class="text-center">
        <div class="logout-spinner"></div>
        <div class="logout-text">Logging out...</div>
    </div>
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Navigation Scripts -->
<script>
// User Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    const userProfile = document.getElementById('userProfile');
    const userModal = document.getElementById('userModal');
    const logoutButton = document.getElementById('logoutButton');
    const logoutLoading = document.getElementById('logoutLoading');
    
    if (userProfile && userModal) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Toggle modal display
            if (userModal.style.display === 'none' || userModal.style.display === '') {
                userModal.style.display = 'block';
            } else {
                userModal.style.display = 'none';
            }
        });
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (userModal.style.display === 'block' && 
                !userProfile.contains(e.target) && 
                !userModal.contains(e.target)) {
                userModal.style.display = 'none';
            }
        });
        
        // Handle logout button click
        if (logoutButton) {
            logoutButton.addEventListener('click', function(e) {
                e.preventDefault();
                const logoutUrl = this.getAttribute('href');
                
                // Close the modal first
                userModal.style.display = 'none';
                
                // Show SweetAlert confirmation with better spacing
                Swal.fire({
                    title: 'Confirm Logout',
                    html: '<div class="custom-logout-container">' +
                          '<i class="fas fa-sign-out-alt custom-logout-icon"></i>' +
                          '<p class="custom-logout-text">Are you sure you want to logout?</p>' +
                          '<p class="custom-logout-subtext">You will be redirected to the login page.</p>' +
                          '</div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<span style="padding: 0 10px;">Yes, Logout</span>',
                    cancelButtonText: '<span style="padding: 0 10px;">Cancel</span>',
                    confirmButtonColor: '#2a5c8b',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'swal2-confirm',
                        cancelButton: 'swal2-cancel',
                        popup: 'swal2-popup',
                        title: 'swal2-title',
                        htmlContainer: 'swal2-html-container',
                        actions: 'swal2-actions'
                    },
                    buttonsStyling: false,
                    showLoaderOnConfirm: false,
                    allowOutsideClick: () => !Swal.isLoading(),
                    showCloseButton: false,
                    preConfirm: () => {
                        return new Promise((resolve) => {
                            // Show loading overlay
                            logoutLoading.classList.add('active');
                            document.body.style.overflow = 'hidden';
                            
                            // Small delay for smooth transition
                            setTimeout(() => {
                                resolve();
                            }, 300);
                        });
                    },
                    didOpen: () => {
                        // Ensure proper button spacing
                        const cancelBtn = Swal.getCancelButton();
                        const confirmBtn = Swal.getConfirmButton();
                        if (cancelBtn) {
                            cancelBtn.style.marginRight = '0';
                        }
                        if (confirmBtn) {
                            confirmBtn.style.marginLeft = '0';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Navigate to logout page after a brief delay
                        setTimeout(() => {
                            window.location.href = logoutUrl;
                        }, 500);
                    } else {
                        // Hide loading overlay if cancelled
                        logoutLoading.classList.remove('active');
                        document.body.style.overflow = 'auto';
                    }
                }).catch(() => {
                    // Hide loading overlay on error
                    logoutLoading.classList.remove('active');
                    document.body.style.overflow = 'auto';
                });
            });
        }
        
        // Add click handlers for modal options (now using onclick attributes)
        document.querySelectorAll('.user-modal-option:not(.logout)').forEach(option => {
            option.style.cursor = 'pointer';
        });
    }
});

// Menu Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('menuToggle');
    const navigation = document.querySelector('.navigation');
    const main = document.querySelector('.main');
    const overlay = document.getElementById('overlay');
    
    if (toggle && navigation && main && overlay) {
        toggle.addEventListener('click', function() {
            const isActive = navigation.classList.toggle('active');
            main.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Change icon
            const icon = toggle.querySelector('ion-icon');
            if (isActive) {
                icon.setAttribute('name', 'close-outline');
                if (window.innerWidth <= 1080) {
                    document.body.style.overflow = 'hidden';
                }
            } else {
                icon.setAttribute('name', 'menu-outline');
                document.body.style.overflow = 'auto';
            }
        });
        
        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            navigation.classList.remove('active');
            main.classList.remove('active');
            overlay.classList.remove('active');
            const icon = toggle.querySelector('ion-icon');
            icon.setAttribute('name', 'menu-outline');
            document.body.style.overflow = 'auto';
        });
        
        // Close sidebar when clicking navigation links on mobile
        document.querySelectorAll('.navigation a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1080) {
                    navigation.classList.remove('active');
                    main.classList.remove('active');
                    overlay.classList.remove('active');
                    const icon = toggle.querySelector('ion-icon');
                    icon.setAttribute('name', 'menu-outline');
                    document.body.style.overflow = 'auto';
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1080) {
                navigation.classList.remove('active');
                main.classList.remove('active');
                overlay.classList.remove('active');
                const icon = toggle.querySelector('ion-icon');
                icon.setAttribute('name', 'menu-outline');
                document.body.style.overflow = 'auto';
            }
        });
    }
});
</script>