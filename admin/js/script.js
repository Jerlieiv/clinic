<<<<<<< HEAD
// Main Dashboard JavaScript

// Get current year for footer
function updateFooterYear() {
    const currentYear = new Date().getFullYear();
    const footerText = document.getElementById('footerText');
    if (footerText) {
        footerText.innerHTML = `© ${currentYear} Ndaona Clinic Management System • Balaka, Malawi`;
    }
}

// Call function on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFooterYear();
    
    // Add hovered class to selected list item
    let list = document.querySelectorAll(".navigation li");

    function activeLink() {
        list.forEach((item) => {
            item.classList.remove("hovered");
        });
        this.classList.add("hovered");
    }

    list.forEach((item) => item.addEventListener("mouseover", activeLink));

    // Menu Toggle - Fixed for mobile
    let toggle = document.querySelector(".toggle");
    let navigation = document.querySelector(".navigation");
    let main = document.querySelector(".main");
    let overlay = document.getElementById("overlay");

    function toggleSidebar() {
        if (toggle && navigation && main && overlay) {
            const isActive = navigation.classList.toggle("active");
            main.classList.toggle("active");
            overlay.classList.toggle("active");
            
            // Change icon when sidebar is open
            if (isActive) {
                toggle.innerHTML = '<ion-icon name="close-outline"></ion-icon>';
                // Prevent body scrolling when sidebar is open on mobile
                if (window.innerWidth <= 1080) {
                    document.body.style.overflow = 'hidden';
                }
            } else {
                toggle.innerHTML = '<ion-icon name="menu-outline"></ion-icon>';
                // Restore body scrolling
                document.body.style.overflow = 'auto';
            }
        }
    }

    if (toggle) {
        toggle.onclick = toggleSidebar;
    }

    // Close sidebar when clicking on overlay
    if (overlay) {
        overlay.onclick = function() {
            if (navigation && main && toggle) {
                navigation.classList.remove("active");
                main.classList.remove("active");
                overlay.classList.remove("active");
                toggle.innerHTML = '<ion-icon name="menu-outline"></ion-icon>';
                document.body.style.overflow = 'auto'; // Restore scrolling
            }
        };
    }

    // Close sidebar when clicking on any navigation item on mobile
    document.querySelectorAll('.navigation a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1080 && navigation && main && toggle) {
                navigation.classList.remove("active");
                main.classList.remove("active");
                overlay.classList.remove("active");
                toggle.innerHTML = '<ion-icon name="menu-outline"></ion-icon>';
                document.body.style.overflow = 'auto';
            }
        });
    });

    // User modal functionality
    const userProfile = document.getElementById('userProfile');
    const userModal = document.getElementById('userModal');

    if (userProfile && userModal) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            userModal.classList.toggle('active');
        });

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (userProfile && userModal) {
                if (!userProfile.contains(e.target) && !userModal.contains(e.target)) {
                    userModal.classList.remove('active');
                }
            }
        });
    }

    // Chart controls
    document.querySelectorAll('.chartControl').forEach(control => {
        control.addEventListener('click', function() {
            document.querySelectorAll('.chartControl').forEach(c => {
                c.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Bar value display on hover
    document.querySelectorAll('.bar').forEach(bar => {
        bar.addEventListener('mouseenter', function() {
            const value = this.getAttribute('data-value');
            if (!this.querySelector('.bar-value')) {
                const valueSpan = document.createElement('span');
                valueSpan.className = 'bar-value';
                valueSpan.textContent = value;
                this.parentElement.appendChild(valueSpan);
            }
        });
        
        bar.addEventListener('mouseleave', function() {
            const valueSpan = this.parentElement.querySelector('.bar-value');
            if (valueSpan) {
                valueSpan.remove();
            }
        });
    });

    // Add event listeners to sidebar navigation
    setTimeout(() => {
        const allLinks = document.querySelectorAll('.navigation li a');
        
        allLinks.forEach(link => {
            const title = link.querySelector('.title');
            if (title) {
                // User Management
                if (title.textContent === 'User Management') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openUserManagementPage();
                    });
                }
                // Branch Configuration
                if (title.textContent === 'Branch Configuration') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openBranchConfigurationPage();
                    });
                }
                // Settings
                if (title.textContent === 'Settings') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openSettings();
                    });
                }
                // Help & Support
                if (title.textContent === 'Help & Support') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openHelp();
                    });
                }
                // Documentation
                if (title.textContent === 'Documentation') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openDocumentation();
                    });
                }
                // Security
                if (title.textContent === 'Security') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openSecurity();
                    });
                }
            }
        });
    }, 100);

    // Close sidebar when window is resized to desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1080 && navigation && main && overlay && toggle) {
            // Restore normal state on desktop
            navigation.classList.remove("active");
            main.classList.remove("active");
            overlay.classList.remove("active");
            toggle.innerHTML = '<ion-icon name="menu-outline"></ion-icon>';
            document.body.style.overflow = 'auto';
        }
    });

    // Initialize real-time data
    updateLiveStats();
    setInterval(updateLiveStats, 30000);
});

// Enhanced Modal Actions
function openProfile() {
    alert('Opening Profile Settings');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openSettings() {
    alert('Opening System Settings');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openChangePassword() {
    alert('Opening Change Password Form');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openSecurity() {
    alert('Opening Security Settings');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openUserManagement() {
    alert('Opening User Management Module');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openBranchConfig() {
    alert('Opening Branch Configuration Module');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function logout() {
    if (confirm('Are you sure you want to logout from PharmaCare?')) {
        alert('Logging out... Redirecting to login page.');
        const userModal = document.getElementById('userModal');
        if (userModal) userModal.classList.remove('active');
        // In production: window.location.href = '/login';
    }
}

// Sidebar Navigation Actions
function openUserManagementPage() {
    alert('Opening User Management Page - Manage users, roles, and permissions');
}

function openBranchConfigurationPage() {
    alert('Opening Branch Configuration - Configure pharmacy branches and settings');
}

// Enhanced Quick Actions
function startNewSale() {
    alert('Opening Point of Sale Interface with barcode scanner');
}

function registerCustomer() {
    alert('Opening Patient Registration Form with medical history');
}

function createPrescription() {
    alert('Opening Digital Prescription Creation with templates');
}

function scheduleAppointment() {
    alert('Opening Appointment Scheduler with doctor availability');
}

function manageInventory() {
    alert('Opening Inventory Management with batch tracking');
}

function generateReports() {
    alert('Generating Comprehensive Daily Reports');
}

function processReturns() {
    alert('Opening Returns Processing with receipt validation');
}

function manageSuppliers() {
    alert('Opening Supplier Management with purchase orders');
}

// Card Click Actions
function viewSalesReport() {
    alert('Opening Detailed Sales Report');
}

function viewTransactions() {
    alert('Viewing All Transactions');
}

function viewLowStock() {
    alert('Viewing Critical Stock Items');
}

function viewStockStatus() {
    alert('Opening Complete Stock Status');
}

// Product Details
function viewProductDetails(product) {
    alert(`Viewing details for: ${product}`);
}

// Chart Period Change
function changeChartPeriod(period) {
    alert(`Loading ${period} data...`);
    document.querySelectorAll('.chartControl').forEach(c => {
        c.classList.remove('active');
        if (c.textContent.toLowerCase().includes(period)) {
            c.classList.add('active');
        }
    });
    
    // Simulate chart data change
    const bars = document.querySelectorAll('.bar');
    const values = {
        week: [80, 65, 90, 75, 85, 70, 95],
        month: [60, 75, 85, 70, 90, 65, 80, 75, 85, 90, 70, 80, 85, 75, 65, 90, 80, 75, 85, 90, 70, 80, 85, 75, 65, 90, 80, 75, 85, 70],
        year: [65, 70, 75, 80, 85, 90, 95, 85, 80, 75, 70, 65]
    };
    
    if (values[period]) {
        bars.forEach((bar, index) => {
            if (index < values[period].length) {
                bar.style.height = values[period][index] + '%';
                bar.setAttribute('data-value', `MWK ${(values[period][index] * 2000).toLocaleString()}`);
            }
        });
    }
}

// Alert Actions
function reorderStock(item) {
    alert(`Creating automatic purchase order for: ${item}`);
}

function manageExpiry(item) {
    alert(`Managing expiry stock for: ${item}`);
}

function viewAppointment(id) {
    alert(`Viewing appointment details: ${id}`);
}

function viewPrescription(id) {
    alert(`Processing and dispensing prescription: ${id}`);
}

// Recent Activities
function viewActivityDetails(id) {
    alert(`Viewing activity details #${id}`);
}

// Footer Actions
function openHelp() {
    alert('Opening Help & Support Center');
}

function openDocumentation() {
    alert('Opening System Documentation');
}

function openFeedback() {
    alert('Opening Feedback Form');
}

// Real-time data simulation
function updateLiveStats() {
    const users = Math.floor(Math.random() * 5) + 3;
    const sales = Math.floor(Math.random() * 5) + 1;
    
    const userElements = document.querySelectorAll('.liveStat.users span:last-child');
    const salesElements = document.querySelectorAll('.liveStat.sales span:last-child');
    
    userElements.forEach(el => el.textContent = `${users} Online`);
    salesElements.forEach(el => el.textContent = `${sales} Sales/Min`);
=======
// Main Dashboard JavaScript

// Get current year for footer
function updateFooterYear() {
    const currentYear = new Date().getFullYear();
    const footerText = document.getElementById('footerText');
    if (footerText) {
        footerText.innerHTML = `© ${currentYear} Ndaona Clinic Management System • Balaka, Malawi`;
    }
}

// Call function on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFooterYear();
    
    // Add hovered class to selected list item
    let list = document.querySelectorAll(".navigation li");

    function activeLink() {
        list.forEach((item) => {
            item.classList.remove("hovered");
        });
        this.classList.add("hovered");
    }

    list.forEach((item) => item.addEventListener("mouseover", activeLink));

    // Menu Toggle - Fixed for mobile
    let toggle = document.querySelector(".toggle");
    let navigation = document.querySelector(".navigation");
    let main = document.querySelector(".main");
    let overlay = document.getElementById("overlay");

    function toggleSidebar() {
        if (toggle && navigation && main && overlay) {
            const isActive = navigation.classList.toggle("active");
            main.classList.toggle("active");
            overlay.classList.toggle("active");
            
            // Change icon when sidebar is open
            if (isActive) {
                toggle.innerHTML = '<ion-icon name="close-outline"></ion-icon>';
                // Prevent body scrolling when sidebar is open on mobile
                if (window.innerWidth <= 1080) {
                    document.body.style.overflow = 'hidden';
                }
            } else {
                toggle.innerHTML = '<ion-icon name="menu-outline"></ion-icon>';
                // Restore body scrolling
                document.body.style.overflow = 'auto';
            }
        }
    }

    if (toggle) {
        toggle.onclick = toggleSidebar;
    }

    // Close sidebar when clicking on overlay
    if (overlay) {
        overlay.onclick = function() {
            if (navigation && main && toggle) {
                navigation.classList.remove("active");
                main.classList.remove("active");
                overlay.classList.remove("active");
                toggle.innerHTML = '<ion-icon name="menu-outline"></ion-icon>';
                document.body.style.overflow = 'auto'; // Restore scrolling
            }
        };
    }

    // Close sidebar when clicking on any navigation item on mobile
    document.querySelectorAll('.navigation a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1080 && navigation && main && toggle) {
                navigation.classList.remove("active");
                main.classList.remove("active");
                overlay.classList.remove("active");
                toggle.innerHTML = '<ion-icon name="menu-outline"></ion-icon>';
                document.body.style.overflow = 'auto';
            }
        });
    });

    // User modal functionality
    const userProfile = document.getElementById('userProfile');
    const userModal = document.getElementById('userModal');

    if (userProfile && userModal) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            userModal.classList.toggle('active');
        });

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (userProfile && userModal) {
                if (!userProfile.contains(e.target) && !userModal.contains(e.target)) {
                    userModal.classList.remove('active');
                }
            }
        });
    }

    // Chart controls
    document.querySelectorAll('.chartControl').forEach(control => {
        control.addEventListener('click', function() {
            document.querySelectorAll('.chartControl').forEach(c => {
                c.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Bar value display on hover
    document.querySelectorAll('.bar').forEach(bar => {
        bar.addEventListener('mouseenter', function() {
            const value = this.getAttribute('data-value');
            if (!this.querySelector('.bar-value')) {
                const valueSpan = document.createElement('span');
                valueSpan.className = 'bar-value';
                valueSpan.textContent = value;
                this.parentElement.appendChild(valueSpan);
            }
        });
        
        bar.addEventListener('mouseleave', function() {
            const valueSpan = this.parentElement.querySelector('.bar-value');
            if (valueSpan) {
                valueSpan.remove();
            }
        });
    });

    // Add event listeners to sidebar navigation
    setTimeout(() => {
        const allLinks = document.querySelectorAll('.navigation li a');
        
        allLinks.forEach(link => {
            const title = link.querySelector('.title');
            if (title) {
                // User Management
                if (title.textContent === 'User Management') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openUserManagementPage();
                    });
                }
                // Branch Configuration
                if (title.textContent === 'Branch Configuration') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openBranchConfigurationPage();
                    });
                }
                // Settings
                if (title.textContent === 'Settings') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openSettings();
                    });
                }
                // Help & Support
                if (title.textContent === 'Help & Support') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openHelp();
                    });
                }
                // Documentation
                if (title.textContent === 'Documentation') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openDocumentation();
                    });
                }
                // Security
                if (title.textContent === 'Security') {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        openSecurity();
                    });
                }
            }
        });
    }, 100);

    // Close sidebar when window is resized to desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1080 && navigation && main && overlay && toggle) {
            // Restore normal state on desktop
            navigation.classList.remove("active");
            main.classList.remove("active");
            overlay.classList.remove("active");
            toggle.innerHTML = '<ion-icon name="menu-outline"></ion-icon>';
            document.body.style.overflow = 'auto';
        }
    });

    // Initialize real-time data
    updateLiveStats();
    setInterval(updateLiveStats, 30000);
});

// Enhanced Modal Actions
function openProfile() {
    alert('Opening Profile Settings');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openSettings() {
    alert('Opening System Settings');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openChangePassword() {
    alert('Opening Change Password Form');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openSecurity() {
    alert('Opening Security Settings');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openUserManagement() {
    alert('Opening User Management Module');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function openBranchConfig() {
    alert('Opening Branch Configuration Module');
    const userModal = document.getElementById('userModal');
    if (userModal) userModal.classList.remove('active');
}

function logout() {
    if (confirm('Are you sure you want to logout from PharmaCare?')) {
        alert('Logging out... Redirecting to login page.');
        const userModal = document.getElementById('userModal');
        if (userModal) userModal.classList.remove('active');
        // In production: window.location.href = '/login';
    }
}

// Sidebar Navigation Actions
function openUserManagementPage() {
    alert('Opening User Management Page - Manage users, roles, and permissions');
}

function openBranchConfigurationPage() {
    alert('Opening Branch Configuration - Configure pharmacy branches and settings');
}

// Enhanced Quick Actions
function startNewSale() {
    alert('Opening Point of Sale Interface with barcode scanner');
}

function registerCustomer() {
    alert('Opening Patient Registration Form with medical history');
}

function createPrescription() {
    alert('Opening Digital Prescription Creation with templates');
}

function scheduleAppointment() {
    alert('Opening Appointment Scheduler with doctor availability');
}

function manageInventory() {
    alert('Opening Inventory Management with batch tracking');
}

function generateReports() {
    alert('Generating Comprehensive Daily Reports');
}

function processReturns() {
    alert('Opening Returns Processing with receipt validation');
}

function manageSuppliers() {
    alert('Opening Supplier Management with purchase orders');
}

// Card Click Actions
function viewSalesReport() {
    alert('Opening Detailed Sales Report');
}

function viewTransactions() {
    alert('Viewing All Transactions');
}

function viewLowStock() {
    alert('Viewing Critical Stock Items');
}

function viewStockStatus() {
    alert('Opening Complete Stock Status');
}

// Product Details
function viewProductDetails(product) {
    alert(`Viewing details for: ${product}`);
}

// Chart Period Change
function changeChartPeriod(period) {
    alert(`Loading ${period} data...`);
    document.querySelectorAll('.chartControl').forEach(c => {
        c.classList.remove('active');
        if (c.textContent.toLowerCase().includes(period)) {
            c.classList.add('active');
        }
    });
    
    // Simulate chart data change
    const bars = document.querySelectorAll('.bar');
    const values = {
        week: [80, 65, 90, 75, 85, 70, 95],
        month: [60, 75, 85, 70, 90, 65, 80, 75, 85, 90, 70, 80, 85, 75, 65, 90, 80, 75, 85, 90, 70, 80, 85, 75, 65, 90, 80, 75, 85, 70],
        year: [65, 70, 75, 80, 85, 90, 95, 85, 80, 75, 70, 65]
    };
    
    if (values[period]) {
        bars.forEach((bar, index) => {
            if (index < values[period].length) {
                bar.style.height = values[period][index] + '%';
                bar.setAttribute('data-value', `MWK ${(values[period][index] * 2000).toLocaleString()}`);
            }
        });
    }
}

// Alert Actions
function reorderStock(item) {
    alert(`Creating automatic purchase order for: ${item}`);
}

function manageExpiry(item) {
    alert(`Managing expiry stock for: ${item}`);
}

function viewAppointment(id) {
    alert(`Viewing appointment details: ${id}`);
}

function viewPrescription(id) {
    alert(`Processing and dispensing prescription: ${id}`);
}

// Recent Activities
function viewActivityDetails(id) {
    alert(`Viewing activity details #${id}`);
}

// Footer Actions
function openHelp() {
    alert('Opening Help & Support Center');
}

function openDocumentation() {
    alert('Opening System Documentation');
}

function openFeedback() {
    alert('Opening Feedback Form');
}

// Real-time data simulation
function updateLiveStats() {
    const users = Math.floor(Math.random() * 5) + 3;
    const sales = Math.floor(Math.random() * 5) + 1;
    
    const userElements = document.querySelectorAll('.liveStat.users span:last-child');
    const salesElements = document.querySelectorAll('.liveStat.sales span:last-child');
    
    userElements.forEach(el => el.textContent = `${users} Online`);
    salesElements.forEach(el => el.textContent = `${sales} Sales/Min`);
>>>>>>> ebf5f55ccd0a1b48a75b40abdbae6c5de9fe43f4
}