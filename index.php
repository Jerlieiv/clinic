<?php
// ============================================
// PHP BACKEND CODE
// ============================================
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'master_clinic');

// Connect to database function
function connectDB() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

// Sanitize input function
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to get client IP address
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Function to log activity
function logActivity($user_id, $user_name, $action, $description, $branch_id = null, $branch_name = null) {
    $conn = connectDB();
    if (!$conn) return false;
    
    $ip_address = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_name, action, description, branch_id, branch_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("isssiiss", $user_id, $user_name, $action, $description, $branch_id, $branch_name, $ip_address, $user_agent);
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $result;
    }
    
    $conn->close();
    return false;
}

// Check if user is already logged in and redirect
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: admin/index.php');
            exit();
        case 'receptionist':
            header('Location: receptionist/index.php');
            exit();
        case 'clinician':
            header('Location: clinician/index.php');
            exit();
        case 'dental_staff':
            header('Location: dental_staff/index.php');
            exit();
    }
}

// Process login if form is submitted
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    // Validate input
    if (empty($username) || empty($password)) {
        $login_error = 'Please enter both username/email and password';
    } else {
        // Connect to database
        $conn = connectDB();
        if ($conn) {
            // Prepare SQL statement to find user by username or email
            $stmt = $conn->prepare("SELECT user_id, username, password, full_name, email, role, status, branch_id FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
            
            if ($stmt) {
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Verify password (assuming passwords are hashed with password_hash())
                    if (password_verify($password, $user['password'])) {
                        // Password is correct, set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['branch_id'] = $user['branch_id'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        
                        // Update last login time
                        $update_stmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE user_id = ?");
                        $update_stmt->bind_param("i", $user['user_id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Get branch name if branch_id exists
                        $branch_name = null;
                        if ($user['branch_id']) {
                            $branch_stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = ?");
                            $branch_stmt->bind_param("i", $user['branch_id']);
                            $branch_stmt->execute();
                            $branch_result = $branch_stmt->get_result();
                            if ($branch_result->num_rows === 1) {
                                $branch_data = $branch_result->fetch_assoc();
                                $branch_name = $branch_data['branch_name'];
                                $_SESSION['branch_name'] = $branch_name;
                            }
                            $branch_stmt->close();
                        }
                        
                        // Log login activity
                        logActivity(
                            $user['user_id'],
                            $user['full_name'],
                            'LOGIN',
                            'User logged into the system',
                            $user['branch_id'],
                            $branch_name
                        );
                        
                        // Set remember me cookie
                        if ($remember_me) {
                            setcookie('remember_user', $user['username'], time() + (30 * 24 * 60 * 60), '/');
                        }
                        
                        // Close connections
                        $stmt->close();
                        $conn->close();
                        
                        // Redirect based on role
                        switch ($user['role']) {
                            case 'admin':
                                header('Location: admin/index.php');
                                exit();
                            case 'receptionist':
                                header('Location: receptionist/index.php');
                                exit();
                            case 'clinician':
                                header('Location: clinician/index.php');
                                exit();
                            case 'dental_staff':
                                header('Location: dental_staff/index.php');
                                exit();
                            default:
                                $login_error = 'Unknown user role';
                        }
                    } else {
                        // Log failed login attempt
                        $conn2 = connectDB();
                        if ($conn2) {
                            // Try to get user info for logging
                            $failed_stmt = $conn2->prepare("SELECT user_id, full_name FROM users WHERE username = ? OR email = ?");
                            $failed_stmt->bind_param("ss", $username, $username);
                            $failed_stmt->execute();
                            $failed_result = $failed_stmt->get_result();
                            
                            if ($failed_result->num_rows === 1) {
                                $failed_user = $failed_result->fetch_assoc();
                                logActivity(
                                    $failed_user['user_id'],
                                    $failed_user['full_name'],
                                    'LOGIN_FAILED',
                                    'Invalid password attempt'
                                );
                            } else {
                                logActivity(
                                    0,
                                    'Unknown User',
                                    'LOGIN_FAILED',
                                    'Invalid username/email attempt: ' . $username
                                );
                            }
                            $failed_stmt->close();
                            $conn2->close();
                        }
                        
                        $login_error = 'Invalid username/email or password';
                    }
                } else {
                    // Log invalid username attempt
                    logActivity(
                        0,
                        'Unknown User',
                        'LOGIN_FAILED',
                        'Invalid username/email attempt: ' . $username
                    );
                    
                    $login_error = 'Invalid username/email or password';
                }
                $stmt->close();
            } else {
                $login_error = 'Database query failed';
            }
            $conn->close();
        } else {
            $login_error = 'Database connection failed';
        }
    }
}

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $logout_message = 'You have been successfully logged out.';
}

// Check for session timeout
if (isset($_GET['session']) && $_GET['session'] == 'expired') {
    $login_error = 'Your session has expired. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PharmaCare - Medical Login</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    />
    
    <style>
        .medical-gradient {
            background: linear-gradient(135deg, #1a283d 0%, #0f1a2d 100%);
        }
        
        .login-btn {
            background: linear-gradient(135deg, #2a5c8b 0%, #1abc9c 100%);
            transition: all 0.3s ease;
        }
        
        .login-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(42, 92, 139, 0.3);
        }
        
        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Loading overlay */
        .loading-overlay {
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
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .spinner-container {
            text-align: center;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top-color: #2a5c8b;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        .loading-text {
            color: white;
            font-size: 18px;
            font-weight: 500;
        }
    </style>
</head>
<body class="min-h-screen bg-cover bg-center flex items-center justify-center medical-gradient">

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <div class="loading-text">Authenticating...</div>
        </div>
    </div>

    <div class="bg-black bg-opacity-50 w-full min-h-screen flex items-center justify-center px-6 py-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 w-full max-w-5xl">

            <!-- Left Section -->
            <div class="text-white flex flex-col justify-center">
                <h1 class="text-5xl font-extrabold mb-4">Welcome<br />Back</h1>
                <p class="max-w-md opacity-80 mb-6">
                    Access your professional pharmacy dashboard to manage prescriptions, 
                    handle patient records, and coordinate with healthcare providers 
                    through our secure medical platform.
                </p>

                <div class="flex gap-4 text-xl">
                    <a href="#" class="hover:text-[#1abc9c]"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="hover:text-[#1abc9c]"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-[#1abc9c]"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="hover:text-[#1abc9c]"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Right Section (Login Form) -->
            <div class="bg-white rounded-xl shadow-xl p-8">
                <h2 class="text-2xl font-bold mb-6 text-center">Medical Sign In</h2>
                
                <!-- Display Error/Success Messages -->
                <?php if (!empty($login_error)): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($logout_message)): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($logout_message); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="login" value="1">
                    
                    <div class="mb-4">
                        <label class="font-semibold block mb-2">
                            <i class="fas fa-user-md mr-2 text-[#2a5c8b]"></i>
                            Username or Email
                        </label>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            class="form-control mt-1" 
                            placeholder="Enter username or email"
                            value="<?php echo isset($_COOKIE['remember_user']) ? htmlspecialchars($_COOKIE['remember_user']) : ''; ?>"
                            required
                        />
                        <div class="text-sm text-red-500 mt-1 hidden" id="usernameError"></div>
                    </div>

                    <div class="mb-4">
                        <label class="font-semibold block mb-2">
                            <i class="fas fa-lock mr-2 text-[#2a5c8b]"></i>
                            Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                class="form-control mt-1" 
                                placeholder="Enter password" 
                                required
                            />
                            <button 
                                type="button" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 bg-transparent border-0"
                                id="togglePassword"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="text-sm text-red-500 mt-1 hidden" id="passwordError"></div>
                    </div>

                    <div class="flex items-center mb-4">
                        <input 
                            type="checkbox" 
                            id="remember_me" 
                            name="remember_me" 
                            class="me-2 h-4 w-4" 
                            <?php echo isset($_COOKIE['remember_user']) ? 'checked' : ''; ?>
                        />
                        <span class="text-gray-700">Remember Me</span>
                    </div>

                    <button type="submit" class="w-full py-2 login-btn text-white rounded-lg mb-4" id="loginButton">
                        <span id="buttonText">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign in now
                        </span>
                    </button>
                    
                    <!-- Activity Log Notice -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-xs text-gray-500 text-center">
                            <i class="fas fa-shield-alt mr-1"></i>
                            All login activities are logged for security auditing
                        </p>
                        <p class="text-xs text-gray-400 text-center mt-2">
                            IP Address: <?php echo htmlspecialchars(getClientIP()); ?>
                        </p>
                    </div>

                    <div class="text-center mt-4">
                        <a href="forgot_password.php" class="text-[#2a5c8b] hover:underline font-medium">
                            <i class="fas fa-question-circle mr-1"></i>
                            Forgot your password?
                        </a>
                    </div>

                    <p class="text-xs text-gray-600 text-center mt-6">
                        By clicking on "Sign in now" you agree to our
                        <a href="#" class="underline">Terms of Service</a> |
                        <a href="#" class="underline">Privacy Policy</a> |
                        <a href="#" class="underline">HIPAA Compliance</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- FontAwesome Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const loginButton = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            const loadingOverlay = document.getElementById('loadingOverlay');

            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                    this.setAttribute('title', 'Hide password');
                } else {
                    passwordInput.type = 'password';
                    icon.className = 'fas fa-eye';
                    this.setAttribute('title', 'Show password');
                }
            });

            // Show/hide loading overlay
            function showLoading() {
                loadingOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            function hideLoading() {
                loadingOverlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            }

            // Form validation
            function validateForm() {
                let isValid = true;
                
                // Clear previous errors
                document.getElementById('usernameError').classList.add('hidden');
                document.getElementById('passwordError').classList.add('hidden');
                
                // Validate username/email
                if (!usernameInput.value.trim()) {
                    document.getElementById('usernameError').textContent = 'Username or email is required';
                    document.getElementById('usernameError').classList.remove('hidden');
                    usernameInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    usernameInput.classList.remove('is-invalid');
                }
                
                // Validate password
                if (!passwordInput.value) {
                    document.getElementById('passwordError').textContent = 'Password is required';
                    document.getElementById('passwordError').classList.remove('hidden');
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else if (passwordInput.value.length < 6) {
                    document.getElementById('passwordError').textContent = 'Password must be at least 6 characters';
                    document.getElementById('passwordError').classList.remove('hidden');
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    passwordInput.classList.remove('is-invalid');
                }
                
                return isValid;
            }

            // Form submission
            loginForm.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }
                
                // Show loading overlay
                showLoading();
                
                // Disable button and show loading text
                loginButton.disabled = true;
                buttonText.innerHTML = '<span class="loading-spinner"></span>Authenticating...';
                
                // The form will submit normally to PHP backend
            });

            // Clear errors on input
            usernameInput.addEventListener('input', function() {
                document.getElementById('usernameError').classList.add('hidden');
                this.classList.remove('is-invalid');
            });
            
            passwordInput.addEventListener('input', function() {
                document.getElementById('passwordError').classList.add('hidden');
                this.classList.remove('is-invalid');
            });
            
            // Auto-focus on username field
            if (!usernameInput.value) {
                setTimeout(() => {
                    usernameInput.focus();
                }, 100);
            }
            
            // If page loads with errors, hide loading overlay
            hideLoading();
        });
    </script>
</body>
</html>