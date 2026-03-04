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

            <!-- ======================= Cards ================== -->
            <div class="cardBox">
                <div class="card" onclick="viewSalesReport()">
                    <div>
                        <div class="numbers"><span class="currency">MWK</span> 1,245,800</div>
                        <div class="cardName">Today's Revenue</div>
                        <div class="cardFooter up">↑ 8.5% from yesterday</div>
                    </div>
                    <div class="iconBx">
                        <ion-icon name="cash-outline"></ion-icon>
                    </div>
                </div>

                <div class="card" onclick="viewTransactions()">
                    <div>
                        <div class="numbers">127</div>
                        <div class="cardName">Transactions</div>
                        <div class="cardFooter up">↑ 12 transactions</div>
                    </div>
                    <div class="iconBx">
                        <ion-icon name="receipt-outline"></ion-icon>
                    </div>
                </div>

                <div class="card" onclick="viewLowStock()">
                    <div>
                        <div class="numbers">38</div>
                        <div class="cardName">Critical Stock</div>
                        <div class="cardFooter down">↓ Needs reorder</div>
                    </div>
                    <div class="iconBx">
                        <ion-icon name="warning-outline"></ion-icon>
                    </div>
                </div>

                <div class="card" onclick="viewStockStatus()">
                    <div>
                        <div class="numbers">89%</div>
                        <div class="cardName">Stock Availability</div>
                        <div class="cardFooter up">↑ 2% increase</div>
                    </div>
                    <div class="iconBx">
                        <ion-icon name="checkmark-done-outline"></ion-icon>
                    </div>
                </div>
            </div>

            <!-- ================== Pharmacy Statistics ============== -->
            <div class="statsSection">
                <div class="card">
                    <div class="cardHeader">
                        <h2>Pharmacy Performance Analytics</h2>
                        <a href="#" class="viewAll">Detailed Analytics <ion-icon name="arrow-forward-outline"></ion-icon></a>
                    </div>
                    <div class="statsGrid">
                        <div class="chartContainer">
                            <div class="chartHeader">
                                <div class="chartTitle">Weekly Revenue Trend (MWK)</div>
                                <div class="chartControls">
                                    <div class="chartControl active" onclick="changeChartPeriod('week')">Week</div>
                                    <div class="chartControl" onclick="changeChartPeriod('month')">Month</div>
                                    <div class="chartControl" onclick="changeChartPeriod('year')">Year</div>
                                </div>
                            </div>
                            <!-- Fixed Chart Visualization -->
                            <div class="chart-visualization">
                                <div class="chart-bar">
                                    <div class="bar-container">
                                        <div class="bar" style="height: 80%;" data-value="MWK 180K"></div>
                                    </div>
                                    <div class="day-label">Mon</div>
                                </div>
                                <div class="chart-bar">
                                    <div class="bar-container">
                                        <div class="bar" style="height: 65%;" data-value="MWK 150K"></div>
                                    </div>
                                    <div class="day-label">Tue</div>
                                </div>
                                <div class="chart-bar">
                                    <div class="bar-container">
                                        <div class="bar" style="height: 90%;" data-value="MWK 210K"></div>
                                    </div>
                                    <div class="day-label">Wed</div>
                                </div>
                                <div class="chart-bar">
                                    <div class="bar-container">
                                        <div class="bar" style="height: 75%;" data-value="MWK 165K"></div>
                                    </div>
                                    <div class="day-label">Thu</div>
                                </div>
                                <div class="chart-bar">
                                    <div class="bar-container">
                                        <div class="bar" style="height: 85%;" data-value="MWK 195K"></div>
                                    </div>
                                    <div class="day-label">Fri</div>
                                </div>
                                <div class="chart-bar">
                                    <div class="bar-container">
                                        <div class="bar" style="height: 70%;" data-value="MWK 155K"></div>
                                    </div>
                                    <div class="day-label">Sat</div>
                                </div>
                                <div class="chart-bar">
                                    <div class="bar-container">
                                        <div class="bar" style="height: 95%;" data-value="MWK 225K"></div>
                                    </div>
                                    <div class="day-label">Sun</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="topItems">
                            <div class="cardHeader">
                                <h3>Top Selling Medications</h3>
                            </div>
                            <div class="topItem" onclick="viewProductDetails('Paracetamol')">
                                <div class="itemInfo">
                                    <div class="itemIcon">
                                        <ion-icon name="medical-outline"></ion-icon>
                                    </div>
                                    <div class="itemDetails">
                                        <h4>Paracetamol 500mg</h4>
                                        <p>Analgesic • Generic</p>
                                    </div>
                                </div>
                                <div class="itemSales">
                                    <div class="amount">MWK 850,000</div>
                                    <div class="quantity">1,250 units</div>
                                </div>
                            </div>
                            <div class="topItem" onclick="viewProductDetails('Amoxicillin')">
                                <div class="itemInfo">
                                    <div class="itemIcon">
                                        <ion-icon name="flask-outline"></ion-icon>
                                    </div>
                                    <div class="itemDetails">
                                        <h4>Amoxicillin 250mg</h4>
                                        <p>Antibiotic • Capsule</p>
                                    </div>
                                </div>
                                <div class="itemSales">
                                    <div class="amount">MWK 720,500</div>
                                    <div class="quantity">980 units</div>
                                </div>
                            </div>
                            <div class="topItem" onclick="viewProductDetails('Vitamin C')">
                                <div class="itemInfo">
                                    <div class="itemIcon">
                                        <ion-icon name="bandage-outline"></ion-icon>
                                    </div>
                                    <div class="itemDetails">
                                        <h4>Vitamin C 1000mg</h4>
                                        <p>Supplement • Tablet</p>
                                    </div>
                                </div>
                                <div class="itemSales">
                                    <div class="amount">MWK 610,300</div>
                                    <div class="quantity">1,520 units</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================== Quick Actions ============== -->
            <div class="quickActions">
                <div class="card">
                    <div class="cardHeader">
                        <h2>Quick Pharmacy Actions</h2>
                    </div>
                    <div class="actionsGrid">
                        <div class="actionBtn" onclick="startNewSale()">
                            <ion-icon name="cart-outline" class="actionIcon"></ion-icon>
                            <div class="actionText">New POS Sale</div>
                        </div>
                        <div class="actionBtn" onclick="registerCustomer()">
                            <ion-icon name="person-add-outline" class="actionIcon"></ion-icon>
                            <div class="actionText">Register Patient</div>
                        </div>
                        <div class="actionBtn" onclick="createPrescription()">
                            <ion-icon name="document-text-outline" class="actionIcon"></ion-icon>
                            <div class="actionText">New Prescription</div>
                        </div>
                        <div class="actionBtn" onclick="scheduleAppointment()">
                            <ion-icon name="calendar-outline" class="actionIcon"></ion-icon>
                            <div class="actionText">Schedule Visit</div>
                        </div>
                        <div class="actionBtn" onclick="manageInventory()">
                            <ion-icon name="archive-outline" class="actionIcon"></ion-icon>
                            <div class="actionText">Stock Count</div>
                        </div>
                        <div class="actionBtn" onclick="generateReports()">
                            <ion-icon name="pie-chart-outline" class="actionIcon"></ion-icon>
                            <div class="actionText">Daily Reports</div>
                        </div>
                        <div class="actionBtn" onclick="processReturns()">
                            <ion-icon name="return-down-back-outline" class="actionIcon"></ion-icon>
                            <div class="actionText">Process Returns</div>
                        </div>
                        <div class="actionBtn" onclick="manageSuppliers()">
                            <ion-icon name="business-outline" class="actionIcon"></ion-icon>
                            <div class="actionText">Supplier Orders</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================== System Alerts ============== -->
            <div class="alertsSection">
                <div class="alertCard">
                    <div class="cardHeader">
                        <h3>Stock & Inventory Alerts</h3>
                        <span class="badge">5 New</span>
                    </div>
                    <div class="alertItem">
                        <div class="alertIcon lowstock">
                            <ion-icon name="warning-outline"></ion-icon>
                        </div>
                        <div class="alertContent">
                            <h4>Critical Stock Alert</h4>
                            <p>Paracetamol 500mg - Only 12 units left</p>
                            <div class="stockStatus">
                                <div class="statusBar">
                                    <div class="statusFill danger"></div>
                                </div>
                                <div class="statusText">
                                    <span>Stock: 12/100</span>
                                    <span>Reorder: 50</span>
                                </div>
                            </div>
                            <button class="alertAction" onclick="reorderStock('Paracetamol 500mg')">Reorder Now</button>
                        </div>
                    </div>
                    <div class="alertItem">
                        <div class="alertIcon expiry">
                            <ion-icon name="calendar-outline"></ion-icon>
                        </div>
                        <div class="alertContent">
                            <h4>Expiry Warning</h4>
                            <p>Amoxicillin 250mg expires in 30 days</p>
                            <p>Batch: AMX-2024-01, Qty: 150 units</p>
                            <div class="alertTime">Expires: 15/06/2024</div>
                            <button class="alertAction" onclick="manageExpiry('Amoxicillin 250mg')">Manage Stock</button>
                        </div>
                    </div>
                </div>

                <div class="alertCard">
                    <div class="cardHeader">
                        <h3>Today's Schedule</h3>
                        <span class="badge">8 Appointments</span>
                    </div>
                    <div class="alertItem">
                        <div class="alertIcon appointment">
                            <ion-icon name="time-outline"></ion-icon>
                        </div>
                        <div class="alertContent">
                            <h4>Dental Checkup</h4>
                            <p>Patient: Mary Banda</p>
                            <p>Doctor: Dr. James Ngoma</p>
                            <div class="alertTime">10:30 AM - Room 3</div>
                            <button class="alertAction" onclick="viewAppointment('A-2345')">View Details</button>
                        </div>
                    </div>
                    <div class="alertItem">
                        <div class="alertIcon prescription">
                            <ion-icon name="document-outline"></ion-icon>
                        </div>
                        <div class="alertContent">
                            <h4>Prescription Ready</h4>
                            <p>Patient: John Mwale</p>
                            <p>Medication: Antibiotics + Painkillers</p>
                            <div class="alertTime">Waiting for pickup</div>
                            <button class="alertAction" onclick="viewPrescription('RX-5678')">Dispense Now</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================== Recent Activities ============== -->
            <div class="recentActivities">
                <div class="card">
                    <div class="cardHeader">
                        <h2>Recent Activities</h2>
                        <a href="#" class="viewAll">View All Activities <ion-icon name="arrow-forward-outline"></ion-icon></a>
                    </div>
                    <div class="activityList">
                        <div class="activityItem" onclick="viewActivityDetails(1)">
                            <div class="activityIcon sale">
                                <ion-icon name="cash-outline"></ion-icon>
                            </div>
                            <div class="activityDetails">
                                <h4>Sale Completed</h4>
                                <p>Transaction #POS-2345 for MWK 45,000</p>
                                <div class="activityUser">
                                    <img src="https://ui-avatars.com/api/?name=Sarah+M&background=1abc9c&color=fff" alt="User">
                                    <span>Sarah M. (Cashier)</span>
                                </div>
                            </div>
                            <div class="activityTime">10:25 AM</div>
                        </div>
                        
                        <div class="activityItem" onclick="viewActivityDetails(2)">
                            <div class="activityIcon prescription">
                                <ion-icon name="document-text-outline"></ion-icon>
                            </div>
                            <div class="activityDetails">
                                <h4>Prescription Created</h4>
                                <p>For Kwame Mensah - Amoxicillin 500mg</p>
                                <div class="activityUser">
                                    <img src="https://ui-avatars.com/api/?name=Dr+James&background=2a5c8b&color=fff" alt="User">
                                    <span>Dr. James Ngoma</span>
                                </div>
                            </div>
                            <div class="activityTime">09:45 AM</div>
                        </div>
                        
                        <div class="activityItem" onclick="viewActivityDetails(3)">
                            <div class="activityIcon stock">
                                <ion-icon name="archive-outline"></ion-icon>
                            </div>
                            <div class="activityDetails">
                                <h4>Stock Received</h4>
                                <p>From MedSupplies Ltd - 150 items</p>
                                <div class="activityUser">
                                    <img src="https://ui-avatars.com/api/?name=David+K&background=27ae60&color=fff" alt="User">
                                    <span>David K. (Store)</span>
                                </div>
                            </div>
                            <div class="activityTime">Yesterday, 3:15 PM</div>
                        </div>
                        
                        <div class="activityItem" onclick="viewActivityDetails(4)">
                            <div class="activityIcon appointment">
                                <ion-icon name="calendar-outline"></ion-icon>
                            </div>
                            <div class="activityDetails">
                                <h4>Appointment Scheduled</h4>
                                <p>Dental checkup for Abena Kwakye</p>
                                <div class="activityUser">
                                    <img src="https://ui-avatars.com/api/?name=Grace+T&background=9b59b6&color=fff" alt="User">
                                    <span>Grace T. (Reception)</span>
                                </div>
                            </div>
                            <div class="activityTime">Yesterday, 2:30 PM</div>
                        </div>
                        
                        <div class="activityItem" onclick="viewActivityDetails(5)">
                            <div class="activityIcon payment">
                                <ion-icon name="card-outline"></ion-icon>
                            </div>
                            <div class="activityDetails">
                                <h4>Payment Received</h4>
                                <p>From ABC Company - MWK 1,250,000</p>
                                <div class="activityUser">
                                    <img src="https://ui-avatars.com/api/?name=John+P&background=e74c3c&color=fff" alt="User">
                                    <span>John P. (Accountant)</span>
                                </div>
                            </div>
                            <div class="activityTime">Yesterday, 11:20 AM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================== Footer ==================== -->
        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- Dashboard Functions Script Only -->
    <script>
    // Dashboard Functions Only - Keep these separate
    function updateFooterYear() {
        const currentYear = new Date().getFullYear();
        const footerText = document.getElementById('footerText');
        if (footerText) {
            footerText.innerHTML = `© ${currentYear} Ndaona Clinic Management System • Balaka, Malawi`;
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateFooterYear();
        
        // Add hover effect to navigation items
        let list = document.querySelectorAll(".navigation li");
        function activeLink() {
            list.forEach((item) => item.classList.remove("hovered"));
            this.classList.add("hovered");
        }
        list.forEach((item) => item.addEventListener("mouseover", activeLink));
        
        // Initialize chart hover effects
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
                if (valueSpan) valueSpan.remove();
            });
        });
        
        // Initialize real-time stats
        updateLiveStats();
        setInterval(updateLiveStats, 30000);
    });
    
    // Enhanced Modal Actions
    function openProfile() {
        alert('Opening Profile Settings');
        const userModal = document.getElementById('userModal');
        if (userModal) userModal.style.display = 'none';
    }

    function openSettings() {
        alert('Opening System Settings');
        const userModal = document.getElementById('userModal');
        if (userModal) userModal.style.display = 'none';
    }

    function openChangePassword() {
        alert('Opening Change Password Form');
        const userModal = document.getElementById('userModal');
        if (userModal) userModal.style.display = 'none';
    }

    function openSecurity() {
        alert('Opening Security Settings');
        const userModal = document.getElementById('userModal');
        if (userModal) userModal.style.display = 'none';
    }

    function openUserManagement() {
        alert('Opening User Management Module');
        const userModal = document.getElementById('userModal');
        if (userModal) userModal.style.display = 'none';
    }

    function openBranchConfig() {
        alert('Opening Branch Configuration Module');
        const userModal = document.getElementById('userModal');
        if (userModal) userModal.style.display = 'none';
    }

    function logout() {
        if (confirm('Are you sure you want to logout from PharmaCare?')) {
            alert('Logging out... Redirecting to login page.');
            const userModal = document.getElementById('userModal');
            if (userModal) userModal.style.display = 'none';
        }
    }
    
    // Dashboard action functions
    function startNewSale() { alert('Opening Point of Sale Interface with barcode scanner'); }
    function registerCustomer() { alert('Opening Patient Registration Form with medical history'); }
    function createPrescription() { alert('Opening Digital Prescription Creation with templates'); }
    function scheduleAppointment() { alert('Opening Appointment Scheduler with doctor availability'); }
    function manageInventory() { alert('Opening Inventory Management with batch tracking'); }
    function generateReports() { alert('Generating Comprehensive Daily Reports'); }
    function processReturns() { alert('Opening Returns Processing with receipt validation'); }
    function manageSuppliers() { alert('Opening Supplier Management with purchase orders'); }
    function viewSalesReport() { alert('Opening Detailed Sales Report'); }
    function viewTransactions() { alert('Viewing All Transactions'); }
    function viewLowStock() { alert('Viewing Critical Stock Items'); }
    function viewStockStatus() { alert('Opening Complete Stock Status'); }
    function viewProductDetails(product) { alert(`Viewing details for: ${product}`); }
    function reorderStock(item) { alert(`Creating automatic purchase order for: ${item}`); }
    function manageExpiry(item) { alert(`Managing expiry stock for: ${item}`); }
    function viewAppointment(id) { alert(`Viewing appointment details: ${id}`); }
    function viewPrescription(id) { alert(`Processing and dispensing prescription: ${id}`); }
    function viewActivityDetails(id) { alert(`Viewing activity details #${id}`); }
    function openHelp() { alert('Opening Help & Support Center'); }
    function openDocumentation() { alert('Opening System Documentation'); }
    function openFeedback() { alert('Opening Feedback Form'); }

    function changeChartPeriod(period) {
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

    function updateLiveStats() {
        const users = Math.floor(Math.random() * 5) + 3;
        const sales = Math.floor(Math.random() * 5) + 1;
        
        document.querySelectorAll('.liveStat.users span:last-child').forEach(el => {
            el.textContent = `${users} Online`;
        });
        document.querySelectorAll('.liveStat.sales span:last-child').forEach(el => {
            el.textContent = `${sales} Sales/Min`;
        });
    }
    </script>
    
    <!-- ====== ionicons ======= -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>