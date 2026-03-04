<?php
session_start();

// Get user info if logged in
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
$user_role = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'Unknown';
$attempted_page = $_SERVER['HTTP_REFERER'] ?? 'the requested page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Access Denied - PharmaCare</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Custom Styles -->
    <style>
        body {
            min-height: 100vh;
            position: relative;
            background: linear-gradient(rgba(26, 40, 61, 0.85), rgba(26, 40, 61, 0.9));
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.4;
            z-index: -1;
        }

        .unauthorized-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .logo-icon {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            color: white;
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
        }

        .access-icon {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            margin: 0 auto 30px;
            box-shadow: 0 15px 30px rgba(231, 76, 60, 0.4);
        }

        .action-btn {
            padding: 16px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .action-btn-primary {
            background: linear-gradient(135deg, #2a5c8b 0%, #1e3a5f 100%);
            color: white;
        }

        .action-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(42, 92, 139, 0.4);
            color: white;
        }

        .action-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #2a5c8b;
            border: 2px solid #2a5c8b;
        }

        .action-btn-secondary:hover {
            background: #2a5c8b;
            color: white;
            transform: translateY(-3px);
        }

        .user-info-card {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(41, 128, 185, 0.1) 100%);
            border-radius: 15px;
            padding: 20px;
            border-left: 4px solid #3498db;
        }

        .security-info {
            background: linear-gradient(135deg, rgba(241, 196, 15, 0.1) 0%, rgba(243, 156, 18, 0.1) 100%);
            border-radius: 15px;
            padding: 20px;
            border-left: 4px solid #f1c40f;
        }

        /* Pulse animation for icon */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Shake animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.5s ease;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .logo-container {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .logo-icon {
                width: 70px;
                height: 70px;
                font-size: 2.5rem;
            }
            
            .access-icon {
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }
            
            body::before {
                background-position: 75% center;
            }
        }

        @media (max-width: 480px) {
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            
            .access-icon {
                width: 80px;
                height: 80px;
                font-size: 2.5rem;
            }
        }

        /* Countdown timer */
        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: rgba(255, 255, 255, 0.2);
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 sm:px-6 py-10">

    <div class="w-full max-w-4xl">
        <div class="unauthorized-card p-8 md:p-10">
            <div class="logo-container mb-6">
                <div class="logo-icon">
                    <i class="fas fa-clinic-medical"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-extrabold mb-2">Pharma<span class="text-[#1abc9c]">Care</span></h1>
                    <p class="text-lg opacity-90">Clinic Management System</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="text-center mb-8">
                <div class="access-icon pulse">
                    <i class="fas fa-ban"></i>
                </div>
                
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800 mb-4">
                    Access Restricted
                </h1>
                
                <p class="text-xl text-gray-600 mb-6">
                    <i class="fas fa-exclamation-triangle text-[#e74c3c] mr-2"></i>
                    You don't have permission to access this area
                </p>
            </div>

            <!-- User Information -->
            <div class="user-info-card mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3">
                    <i class="fas fa-user-shield text-[#3498db] mr-2"></i>
                    Current Session Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Logged in as:</p>
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user_name); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">User Role:</p>
                        <p class="font-semibold text-gray-800"><?php echo ucfirst(htmlspecialchars($user_role)); ?></p>
                    </div>
                </div>
            </div>

            <!-- Security Information -->
            <div class="security-info mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-3">
                    <i class="fas fa-shield-alt text-[#f1c40f] mr-2"></i>
                    Security Notice
                </h3>
                <p class="text-gray-700 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>
                    This incident has been logged for security review.
                </p>
                <p class="text-gray-700 text-sm">
                    Unauthorized access attempts are monitored and may result in account suspension.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <a href="dashboard.php" class="action-btn action-btn-primary">
                    <i class="fas fa-home"></i>
                    Go to Dashboard
                </a>
                
                <a href="javascript:history.back()" class="action-btn action-btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Return to Previous Page
                </a>
                
                <a href="logout.php" class="action-btn" style="background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%); color: white;">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>

            <!-- Contact Support -->
            <div class="text-center border-t border-gray-200 pt-6">
                <p class="text-gray-600 mb-4">
                    Need access to this area? Contact your system administrator.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="mailto:admin@pharmacare.com" class="text-[#2a5c8b] hover:underline font-medium">
                        <i class="fas fa-envelope mr-1"></i>
                        Email Admin
                    </a>
                    <span class="text-gray-400">|</span>
                    <a href="tel:+265123456789" class="text-[#2a5c8b] hover:underline font-medium">
                        <i class="fas fa-phone mr-1"></i>
                        Call Support
                    </a>
                    <span class="text-gray-400">|</span>
                    <button onclick="openHelpModal()" class="text-[#2a5c8b] hover:underline font-medium bg-transparent border-none">
                        <i class="fas fa-question-circle mr-1"></i>
                        Request Access
                    </button>
                </div>
            </div>

            <!-- Auto-redirect notice -->
            <div class="text-center mt-6">
                <p class="text-gray-500 text-sm">
                    You will be automatically redirected to your dashboard in 
                    <span id="countdown" class="countdown">10</span> seconds
                </p>
            </div>

            <!-- Footer -->
            <div class="text-center pt-6 border-t border-gray-200 mt-6">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-clock mr-1"></i>
                    Incident logged at <?php echo date('H:i:s'); ?> on <?php echo date('d/m/Y'); ?>
                </p>
                <p class="text-xs text-gray-400 mt-2">Reference ID: UA-<?php echo time(); ?></p>
            </div>
        </div>
    </div>

    <!-- Watermark -->
    <div class="watermark hidden md:block">
        <i class="fas fa-lock mr-1"></i> Security Access Control
    </div>

    <!-- Help Modal -->
    <div id="helpModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Request Access</h3>
                <button onclick="closeHelpModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="accessRequestForm">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Area you need access to:</label>
                    <input type="text" class="form-control w-full" placeholder="e.g., Admin Dashboard" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Reason for access:</label>
                    <select class="form-control w-full">
                        <option value="">Select a reason</option>
                        <option value="job_role">Job Role Requirements</option>
                        <option value="temporary">Temporary Access Needed</option>
                        <option value="training">Training Purposes</option>
                        <option value="emergency">Emergency Situation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">Additional Notes:</label>
                    <textarea class="form-control w-full" rows="3" placeholder="Please provide details about why you need access..."></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeHelpModal()" class="action-btn action-btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="action-btn action-btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-redirect countdown
            let seconds = 10;
            const countdownElement = document.getElementById('countdown');
            const countdownInterval = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = 'dashboard.php';
                }
            }, 1000);

            // Help modal functions
            window.openHelpModal = function() {
                document.getElementById('helpModal').classList.remove('hidden');
                document.getElementById('helpModal').classList.add('flex');
            };

            window.closeHelpModal = function() {
                document.getElementById('helpModal').classList.add('hidden');
                document.getElementById('helpModal').classList.remove('flex');
            };

            // Access request form submission
            document.getElementById('accessRequestForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show success message
                const modal = document.getElementById('helpModal');
                modal.innerHTML = `
                    <div class="bg-white rounded-2xl p-8 max-w-md w-full text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check text-green-500 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Request Submitted</h3>
                        <p class="text-gray-600 mb-6">
                            Your access request has been sent to the system administrator.
                            You will receive an email notification once your request is reviewed.
                        </p>
                        <button onclick="closeHelpModal()" class="action-btn action-btn-primary">
                            <i class="fas fa-check mr-2"></i>
                            OK
                        </button>
                    </div>
                `;
                
                // Auto-close modal after 5 seconds
                setTimeout(() => {
                    closeHelpModal();
                }, 5000);
            });

            // Close modal when clicking outside
            document.getElementById('helpModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeHelpModal();
                }
            });

            // Escape key to close modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeHelpModal();
                }
            });

            // Log unauthorized access attempt (simulated)
            console.warn('Unauthorized access attempt detected:', {
                user: '<?php echo htmlspecialchars($user_name); ?>',
                role: '<?php echo htmlspecialchars($user_role); ?>',
                timestamp: new Date().toISOString(),
                attemptedPage: '<?php echo htmlspecialchars($attempted_page); ?>',
                ip: '<?php echo $_SERVER['REMOTE_ADDR']; ?>'
            });

            // Show a security alert for repeated attempts
            let accessAttempts = parseInt(localStorage.getItem('accessAttempts') || '0');
            accessAttempts++;
            localStorage.setItem('accessAttempts', accessAttempts);
            
            if (accessAttempts > 2) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg z-50 max-w-sm';
                alertDiv.innerHTML = `
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-red-500 mt-1 mr-3"></i>
                        <div>
                            <p class="font-bold">Security Warning</p>
                            <p class="text-sm">Multiple unauthorized access attempts detected. This activity is being monitored.</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(alertDiv);
                
                // Remove alert after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }
        });

        // Add form-control class for modal inputs
        const style = document.createElement('style');
        style.textContent = `
            .form-control {
                padding: 12px 16px;
                border-radius: 8px;
                border: 2px solid #e0e0e0;
                transition: all 0.3s ease;
                font-size: 1rem;
                background: white;
                width: 100%;
            }
            .form-control:focus {
                outline: none;
                border-color: #2a5c8b;
                box-shadow: 0 0 0 3px rgba(42, 92, 139, 0.1);
            }
            select.form-control {
                appearance: none;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 0.75rem center;
                background-size: 16px 12px;
                padding-right: 2.5rem;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>