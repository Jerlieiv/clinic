<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PharmaCare - Reset Password</title>

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
            background: linear-gradient(rgba(26, 40, 61, 0.9), rgba(26, 40, 61, 0.95));
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.3;
            z-index: -1;
        }

        .reset-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .logo-icon {
            background: linear-gradient(135deg, #2a5c8b 0%, #1abc9c 100%);
            width: 70px;
            height: 70px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 10px 20px rgba(42, 92, 139, 0.3);
        }

        .form-control {
            padding: 18px 22px;
            border-radius: 14px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
            font-size: 1.05rem;
            background: white;
        }

        .form-control:focus {
            border-color: #2a5c8b;
            box-shadow: 0 0 0 4px rgba(42, 92, 139, 0.15);
        }

        .reset-btn {
            background: linear-gradient(135deg, #2a5c8b 0%, #1e3a5f 100%);
            color: white;
            padding: 20px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .reset-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(42, 92, 139, 0.4);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            z-index: 10;
            font-size: 1.2rem;
        }

        .input-icon + .form-control {
            padding-left: 60px;
        }

        .security-badge {
            background: rgba(26, 188, 156, 0.1);
            border: 2px solid rgba(26, 188, 156, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 40px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            background: #e0e0e0;
            color: #7f8c8d;
            position: relative;
        }

        .step.active {
            background: #2a5c8b;
            color: white;
            box-shadow: 0 5px 15px rgba(42, 92, 139, 0.3);
        }

        .step.completed {
            background: #1abc9c;
            color: white;
        }

        .step-line {
            width: 60px;
            height: 3px;
            background: #e0e0e0;
        }

        .step-line.active {
            background: #2a5c8b;
        }

        /* Loading animation */
        .spinner {
            display: inline-block;
            width: 22px;
            height: 22px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: middle;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success animation */
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .success-pulse {
            animation: successPulse 2s infinite;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .logo-container {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            
            .step-line {
                width: 40px;
            }
            
            .form-control {
                padding: 16px 20px;
            }
        }

        @media (max-width: 480px) {
            .step {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
            
            .step-line {
                width: 30px;
            }
            
            .input-icon {
                left: 16px;
            }
            
            .input-icon + .form-control {
                padding-left: 50px;
            }
        }

        .countdown {
            font-family: monospace;
            font-weight: bold;
            color: #2a5c8b;
            font-size: 1.2rem;
        }

        .password-strength {
            height: 6px;
            border-radius: 3px;
            background: #e0e0e0;
            margin-top: 10px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #e74c3c; width: 30%; }
        .strength-medium { background: #f39c12; width: 60%; }
        .strength-strong { background: #27ae60; width: 90%; }
        .strength-very-strong { background: #1abc9c; width: 100%; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 sm:px-6 py-10">

    <div class="w-full max-w-2xl">
        <div class="reset-card p-8 md:p-10">
            <!-- Logo and Header -->
            <div class="text-center mb-10">
                <div class="logo-container justify-center">
                    <div class="logo-icon">
                        <i class="fas fa-clinic-medical"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-800">Pharma<span class="text-[#1abc9c]">Care</span></h1>
                        <p class="text-gray-600">Medical Security Portal</p>
                    </div>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mt-6">Reset Your Password</h2>
                <p class="text-gray-600 mt-2">Secure password recovery for medical staff</p>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step completed">
                    <i class="fas fa-check"></i>
                </div>
                <div class="step-line active"></div>
                <div class="step active" id="step2">2</div>
                <div class="step-line" id="line23"></div>
                <div class="step" id="step3">3</div>
            </div>

            <!-- Current Step Content -->
            <div id="stepContent">
                <!-- Step 1: Email Verification -->
                <div id="step1Content" class="active-step">
                    <div class="security-badge">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-shield-alt text-[#1abc9c] text-xl mt-1"></i>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-2">Security Verification Required</h3>
                                <p class="text-gray-600 text-sm">
                                    For security reasons, we need to verify your identity before resetting your password.
                                    Enter your registered medical email address below.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form id="emailForm">
                        <div class="mb-6">
                            <label class="font-semibold text-gray-700 mb-2 block">
                                <i class="fas fa-envelope mr-2 text-[#2a5c8b]"></i>
                                Medical Email Address
                            </label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-hospital-user"></i>
                                </span>
                                <input 
                                    type="email" 
                                    id="email" 
                                    class="form-control" 
                                    placeholder="name@medical-institution.com" 
                                    required
                                />
                            </div>
                            <div class="text-sm text-red-500 mt-2 hidden" id="emailError"></div>
                            <div class="text-sm text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Use your institutional email address
                            </div>
                        </div>

                        <button type="submit" class="w-full reset-btn mb-4" id="sendCodeBtn">
                            <span id="buttonText">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Send Verification Code
                            </span>
                        </button>

                        <div class="text-center">
                            <a href="#" class="text-[#2a5c8b] hover:underline font-medium transition-colors" id="backToLogin">
                                <i class="fas fa-arrow-left mr-1"></i>
                                Back to Login
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Enter Verification Code -->
                <div id="step2Content" class="hidden">
                    <div class="security-badge">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-mail-bulk text-[#1abc9c] text-xl mt-1"></i>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-2">Check Your Email</h3>
                                <p class="text-gray-600 text-sm">
                                    We've sent a 6-digit verification code to your email address.
                                    Please enter it below to continue.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form id="codeForm">
                        <div class="mb-6">
                            <label class="font-semibold text-gray-700 mb-2 block">
                                <i class="fas fa-key mr-2 text-[#2a5c8b]"></i>
                                6-Digit Verification Code
                            </label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-fingerprint"></i>
                                </span>
                                <input 
                                    type="text" 
                                    id="verificationCode" 
                                    class="form-control text-center tracking-widest" 
                                    placeholder="_ _ _ _ _ _" 
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    required
                                />
                            </div>
                            <div class="text-sm text-red-500 mt-2 hidden" id="codeError"></div>
                            
                            <div class="flex justify-between items-center mt-4">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-clock mr-1"></i>
                                    Code expires in: <span class="countdown" id="countdown">05:00</span>
                                </div>
                                <button type="button" class="text-sm text-[#2a5c8b] hover:underline font-medium" id="resendCode">
                                    <i class="fas fa-redo mr-1"></i>
                                    Resend Code
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <button type="button" class="flex-1 py-4 border-2 border-[#2a5c8b] text-[#2a5c8b] rounded-xl font-semibold hover:bg-[#2a5c8b] hover:text-white transition-all" id="backToEmail">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back
                            </button>
                            <button type="submit" class="flex-1 reset-btn" id="verifyCodeBtn">
                                <span id="verifyButtonText">
                                    Verify Code
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Create New Password -->
                <div id="step3Content" class="hidden">
                    <div class="security-badge">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-lock text-[#1abc9c] text-xl mt-1"></i>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-2">Create New Password</h3>
                                <p class="text-gray-600 text-sm">
                                    Please create a strong, secure password for your medical account.
                                    Ensure it meets our security requirements.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form id="passwordForm">
                        <div class="mb-6">
                            <label class="font-semibold text-gray-700 mb-2 block">
                                <i class="fas fa-key mr-2 text-[#2a5c8b]"></i>
                                New Password
                            </label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-unlock-alt"></i>
                                </span>
                                <input 
                                    type="password" 
                                    id="newPassword" 
                                    class="form-control" 
                                    placeholder="Enter new password" 
                                    required
                                />
                                <button type="button" class="password-toggle" id="togglePassword1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="text-sm text-red-500 mt-2 hidden" id="passwordError"></div>
                        </div>

                        <div class="mb-6">
                            <label class="font-semibold text-gray-700 mb-2 block">
                                <i class="fas fa-key mr-2 text-[#2a5c8b]"></i>
                                Confirm New Password
                            </label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input 
                                    type="password" 
                                    id="confirmPassword" 
                                    class="form-control" 
                                    placeholder="Confirm new password" 
                                    required
                                />
                                <button type="button" class="password-toggle" id="togglePassword2">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="text-sm text-red-500 mt-2 hidden" id="confirmError"></div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-4 mb-6">
                            <h4 class="font-bold text-gray-800 mb-3">
                                <i class="fas fa-shield-alt mr-2 text-[#2a5c8b]"></i>
                                Password Requirements
                            </h4>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li class="flex items-center" id="reqLength">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    At least 8 characters long
                                </li>
                                <li class="flex items-center" id="reqUppercase">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    Contains uppercase letter
                                </li>
                                <li class="flex items-center" id="reqLowercase">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    Contains lowercase letter
                                </li>
                                <li class="flex items-center" id="reqNumber">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    Contains number
                                </li>
                                <li class="flex items-center" id="reqSpecial">
                                    <i class="fas fa-circle text-xs mr-2"></i>
                                    Contains special character
                                </li>
                            </ul>
                        </div>

                        <div class="flex gap-4">
                            <button type="button" class="flex-1 py-4 border-2 border-[#2a5c8b] text-[#2a5c8b] rounded-xl font-semibold hover:bg-[#2a5c8b] hover:text-white transition-all" id="backToCode">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back
                            </button>
                            <button type="submit" class="flex-1 reset-btn" id="resetPasswordBtn">
                                <span id="resetButtonText">
                                    <i class="fas fa-sync-alt mr-2"></i>
                                    Reset Password
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Success Message -->
                <div id="successMessage" class="hidden">
                    <div class="text-center py-10">
                        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 success-pulse">
                            <i class="fas fa-check-circle text-green-500 text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3">Password Reset Successful!</h3>
                        <p class="text-gray-600 mb-8 max-w-md mx-auto">
                            Your password has been successfully reset. You can now log in to your medical account with your new password.
                        </p>
                        <a href="#" class="reset-btn inline-block px-12" id="goToLogin">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Go to Login
                        </a>
                        <div class="text-sm text-gray-500 mt-8">
                            <i class="fas fa-info-circle mr-1"></i>
                            For security reasons, we recommend logging out of all other devices.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-10 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-600">
                    <i class="fas fa-headset mr-1"></i>
                    Need help? <a href="#" class="text-[#2a5c8b] hover:underline font-medium">Contact Medical Support</a>
                </p>
                <p class="text-xs text-gray-500 mt-4">
                    <i class="fas fa-shield-alt mr-1"></i>
                    All reset activities are logged and monitored for security compliance
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // State management
            let currentStep = 1;
            let countdownTimer;
            let countdownSeconds = 300; // 5 minutes
            let generatedCode = '';
            let userEmail = '';

            // DOM Elements
            const stepElements = {
                step2: document.getElementById('step2'),
                step3: document.getElementById('step3'),
                line23: document.getElementById('line23'),
                step1Content: document.getElementById('step1Content'),
                step2Content: document.getElementById('step2Content'),
                step3Content: document.getElementById('step3Content'),
                successMessage: document.getElementById('successMessage')
            };

            // Forms
            const emailForm = document.getElementById('emailForm');
            const codeForm = document.getElementById('codeForm');
            const passwordForm = document.getElementById('passwordForm');

            // Buttons
            const sendCodeBtn = document.getElementById('sendCodeBtn');
            const verifyCodeBtn = document.getElementById('verifyCodeBtn');
            const resetPasswordBtn = document.getElementById('resetPasswordBtn');
            const backToLogin = document.getElementById('backToLogin');
            const backToEmail = document.getElementById('backToEmail');
            const backToCode = document.getElementById('backToCode');
            const goToLogin = document.getElementById('goToLogin');
            const resendCode = document.getElementById('resendCode');

            // Inputs
            const emailInput = document.getElementById('email');
            const codeInput = document.getElementById('verificationCode');
            const newPasswordInput = document.getElementById('newPassword');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const togglePassword1 = document.getElementById('togglePassword1');
            const togglePassword2 = document.getElementById('togglePassword2');

            // Password requirements
            const reqElements = {
                length: document.getElementById('reqLength'),
                uppercase: document.getElementById('reqUppercase'),
                lowercase: document.getElementById('reqLowercase'),
                number: document.getElementById('reqNumber'),
                special: document.getElementById('reqSpecial')
            };

            // Initialize
            updateStepIndicator();
            startCountdown();

            // Generate random 6-digit code
            function generateVerificationCode() {
                return Math.floor(100000 + Math.random() * 900000).toString();
            }

            // Update step indicator
            function updateStepIndicator() {
                // Reset all steps
                document.querySelectorAll('.step').forEach((step, index) => {
                    if (index + 1 < currentStep) {
                        step.className = 'step completed';
                    } else if (index + 1 === currentStep) {
                        step.className = 'step active';
                    } else {
                        step.className = 'step';
                    }
                });

                // Update connecting lines
                stepElements.line23.className = currentStep >= 2 ? 'step-line active' : 'step-line';

                // Show current step content
                Object.keys(stepElements).forEach(key => {
                    if (key.includes('Content') || key === 'successMessage') {
                        stepElements[key].classList.add('hidden');
                        stepElements[key].classList.remove('active-step');
                    }
                });

                if (currentStep === 1) {
                    stepElements.step1Content.classList.remove('hidden');
                    stepElements.step1Content.classList.add('active-step');
                } else if (currentStep === 2) {
                    stepElements.step2Content.classList.remove('hidden');
                    stepElements.step2Content.classList.add('active-step');
                } else if (currentStep === 3) {
                    stepElements.step3Content.classList.remove('hidden');
                    stepElements.step3Content.classList.add('active-step');
                }
            }

            // Start countdown timer
            function startCountdown() {
                clearInterval(countdownTimer);
                countdownSeconds = 300; // Reset to 5 minutes

                countdownTimer = setInterval(() => {
                    countdownSeconds--;
                    const minutes = Math.floor(countdownSeconds / 60);
                    const seconds = countdownSeconds % 60;
                    document.getElementById('countdown').textContent = 
                        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                    if (countdownSeconds <= 0) {
                        clearInterval(countdownTimer);
                        document.getElementById('countdown').textContent = '00:00';
                        document.getElementById('countdown').style.color = '#e74c3c';
                    }
                }, 1000);
            }

            // Show error message
            function showError(elementId, message) {
                const errorElement = document.getElementById(elementId + 'Error');
                const inputElement = document.getElementById(elementId);
                
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
                inputElement.style.borderColor = '#e74c3c';
            }

            // Clear error
            function clearError(elementId) {
                const errorElement = document.getElementById(elementId + 'Error');
                const inputElement = document.getElementById(elementId);
                
                errorElement.classList.add('hidden');
                inputElement.style.borderColor = '';
            }

            // Validate email
            function validateEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Check password strength
            function checkPasswordStrength(password) {
                let strength = 0;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /\d/.test(password),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                };

                // Update requirement indicators
                Object.keys(requirements).forEach(key => {
                    const icon = reqElements[key].querySelector('i');
                    if (requirements[key]) {
                        icon.style.color = '#1abc9c';
                        strength += 1;
                    } else {
                        icon.style.color = '#e0e0e0';
                    }
                });

                // Update strength bar
                const strengthBar = document.getElementById('strengthBar');
                const strengthClasses = ['strength-weak', 'strength-medium', 'strength-strong', 'strength-very-strong'];
                strengthBar.className = 'password-strength-bar';

                if (strength === 0) {
                    strengthBar.style.width = '0%';
                } else if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                } else if (strength === 3) {
                    strengthBar.classList.add('strength-medium');
                } else if (strength === 4) {
                    strengthBar.classList.add('strength-strong');
                } else {
                    strengthBar.classList.add('strength-very-strong');
                }

                return requirements;
            }

            // Step 1: Email submission
            emailForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const email = emailInput.value.trim();
                
                if (!email) {
                    showError('email', 'Email address is required');
                    return;
                }
                
                if (!validateEmail(email)) {
                    showError('email', 'Please enter a valid medical email address');
                    return;
                }

                // Show loading state
                const originalText = sendCodeBtn.querySelector('#buttonText').innerHTML;
                sendCodeBtn.querySelector('#buttonText').innerHTML = '<span class="spinner"></span> Sending Code...';
                sendCodeBtn.disabled = true;

                // Simulate API call
                setTimeout(() => {
                    userEmail = email;
                    generatedCode = generateVerificationCode();
                    
                    // For demo purposes, show the code in an alert
                    alert(`DEMO: Verification code sent to ${email}\n\nCode: ${generatedCode}\n\n(This alert is for demo purposes only. In production, the code would be sent via email.)`);

                    // Move to step 2
                    currentStep = 2;
                    updateStepIndicator();
                    startCountdown();
                    codeInput.value = '';
                    codeInput.focus();

                    // Reset button
                    sendCodeBtn.querySelector('#buttonText').innerHTML = originalText;
                    sendCodeBtn.disabled = false;
                }, 1500);
            });

            // Step 2: Code verification
            codeForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const code = codeInput.value.trim();
                
                if (!code) {
                    showError('code', 'Verification code is required');
                    return;
                }
                
                if (code.length !== 6 || !/^\d{6}$/.test(code)) {
                    showError('code', 'Please enter a valid 6-digit code');
                    return;
                }

                // Show loading state
                const originalText = verifyCodeBtn.querySelector('#verifyButtonText').innerHTML;
                verifyCodeBtn.querySelector('#verifyButtonText').innerHTML = '<span class="spinner"></span> Verifying...';
                verifyCodeBtn.disabled = true;

                // Simulate verification
                setTimeout(() => {
                    if (code === generatedCode) {
                        // Code verified successfully
                        currentStep = 3;
                        updateStepIndicator();
                        clearInterval(countdownTimer);
                    } else {
                        showError('code', 'Invalid verification code. Please try again.');
                    }

                    // Reset button
                    verifyCodeBtn.querySelector('#verifyButtonText').innerHTML = originalText;
                    verifyCodeBtn.disabled = false;
                }, 1000);
            });

            // Step 3: Password reset
            passwordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const requirements = checkPasswordStrength(newPassword);
                
                // Validate password
                let isValid = true;
                
                if (!newPassword) {
                    showError('password', 'New password is required');
                    isValid = false;
                } else if (newPassword.length < 8) {
                    showError('password', 'Password must be at least 8 characters long');
                    isValid = false;
                }
                
                if (!confirmPassword) {
                    showError('confirm', 'Please confirm your password');
                    isValid = false;
                } else if (newPassword !== confirmPassword) {
                    showError('confirm', 'Passwords do not match');
                    isValid = false;
                }
                
                if (!isValid) return;

                // Check if all requirements are met
                const allRequirementsMet = Object.values(requirements).every(req => req);
                if (!allRequirementsMet) {
                    showError('password', 'Please meet all password requirements');
                    return;
                }

                // Show loading state
                const originalText = resetPasswordBtn.querySelector('#resetButtonText').innerHTML;
                resetPasswordBtn.querySelector('#resetButtonText').innerHTML = '<span class="spinner"></span> Resetting...';
                resetPasswordBtn.disabled = true;

                // Simulate password reset API call
                setTimeout(() => {
                    // Show success message
                    stepElements.successMessage.classList.remove('hidden');
                    stepElements.step3Content.classList.add('hidden');
                    
                    // Reset button
                    resetPasswordBtn.querySelector('#resetButtonText').innerHTML = originalText;
                    resetPasswordBtn.disabled = false;
                }, 2000);
            });

            // Back to login
            backToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'login.html';
            });

            // Back to email step
            backToEmail.addEventListener('click', function() {
                currentStep = 1;
                updateStepIndicator();
                clearInterval(countdownTimer);
            });

            // Back to code step
            backToCode.addEventListener('click', function() {
                currentStep = 2;
                updateStepIndicator();
                startCountdown();
            });

            // Go to login from success
            goToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'login.html';
            });

            // Resend code
            resendCode.addEventListener('click', function() {
                if (userEmail) {
                    generatedCode = generateVerificationCode();
                    alert(`DEMO: New verification code sent to ${userEmail}\n\nCode: ${generatedCode}`);
                    startCountdown();
                }
            });

            // Toggle password visibility
            [togglePassword1, togglePassword2].forEach((toggle, index) => {
                toggle.addEventListener('click', function() {
                    const input = index === 0 ? newPasswordInput : confirmPasswordInput;
                    const icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.className = 'fas fa-eye-slash';
                    } else {
                        input.type = 'password';
                        icon.className = 'fas fa-eye';
                    }
                });
            });

            // Real-time password strength check
            newPasswordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                clearError('password');
            });

            // Real-time confirm password check
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== newPasswordInput.value) {
                    document.getElementById('confirmError').textContent = 'Passwords do not match';
                    document.getElementById('confirmError').classList.remove('hidden');
                } else {
                    document.getElementById('confirmError').classList.add('hidden');
                }
            });

            // Clear errors on input
            emailInput.addEventListener('input', () => clearError('email'));
            codeInput.addEventListener('input', () => clearError('code'));
            newPasswordInput.addEventListener('input', () => clearError('password'));
            confirmPasswordInput.addEventListener('input', () => clearError('confirm'));

            // Auto-focus email on load
            emailInput.focus();
        });
    </script>
</body>
</html>