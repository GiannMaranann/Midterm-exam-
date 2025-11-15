<?php
session_start();
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submissions
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Login process
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (!empty($username) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['user_name'] = $user['fullname'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password!";
            }
        } else {
            $error = "Please fill in all fields!";
        }
    } elseif (isset($_POST['register'])) {
        // Registration process
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
            $error = "Please fill in all fields!";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long!";
        } else {
            // Check if email or username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email or username already exists!";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$fullname, $email, $username, $hashed_password])) {
                    $success = "Registration successful! You can now login.";
                    // Clear form data after successful registration
                    $_POST = array();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Login & Register</title>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Poppins', 'sans-serif'],
          },
          colors: {
            primary: '#4F46E5',
            secondary: '#10B981',
            danger: '#EF4444',
            success: '#10B981',
            warning: '#F59E0B',
            info: '#3B82F6',
          },
          borderRadius: {
            none: '0px',
            sm: '4px',
            DEFAULT: '8px',
            md: '12px',
            lg: '16px',
            xl: '20px',
            '2xl': '24px',
            '3xl': '32px',
            full: '9999px',
            button: '8px',
          },
        },
      },
    };
  </script>

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" />

  <!-- Animate.css for smooth animations -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background-color: #f8fafc;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .main-container {
      width: 100%;
      max-width: 420px;
      margin: 0 auto;
    }

    .container {
      width: 100%;
      background-color: white;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15), 0 5px 15px rgba(0, 0, 0, 0.1);
      position: relative;
      transition: all 0.4s ease;
      overflow: hidden;
      min-height: 480px;
    }

    .welcome-screen, .form-container {
      padding: 40px 35px;
      transition: all 0.5s ease-in-out;
      display: flex;
      flex-direction: column;
      width: 100%;
      position: absolute;
      top: 0;
      left: 0;
    }

    .welcome-screen {
      text-align: center;
      justify-content: center;
      align-items: center;
      opacity: 1;
      transform: translateY(0);
      min-height: 480px;
    }

    .welcome-screen h1 {
      font-size: 1.9rem;
      margin-bottom: 15px;
      color: #1f2937;
      font-weight: 700;
    }

    .welcome-screen p {
      font-size: 0.95rem;
      color: #6b7280;
      margin-bottom: 35px;
      line-height: 1.6;
    }

    .welcome-buttons {
      display: flex;
      flex-direction: column;
      gap: 12px;
      width: 100%;
      max-width: 280px;
    }

    .btn {
      padding: 14px 20px;
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-primary {
      background: linear-gradient(135deg, #4F46E5, #7C73E6);
      color: white;
    }

    .btn-secondary {
      background: white;
      color: #4F46E5;
      border: 2px solid #4F46E5;
    }

    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .form-container {
      opacity: 0;
      transform: translateY(20px);
      pointer-events: none;
      justify-content: flex-start;
      height: auto;
    }

    .login-container {
      min-height: 480px;
    }

    .register-container {
      min-height: 580px;
    }

    .container.show-login .welcome-screen {
      opacity: 0;
      transform: translateY(-20px);
      pointer-events: none;
    }

    .container.show-login .login-container {
      opacity: 1;
      transform: translateY(0);
      pointer-events: all;
      position: relative;
    }

    .container.show-register .welcome-screen {
      opacity: 0;
      transform: translateY(-20px);
      pointer-events: none;
    }

    .container.show-register .register-container {
      opacity: 1;
      transform: translateY(0);
      pointer-events: all;
      position: relative;
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #1f2937;
      font-weight: 600;
      font-size: 1.7rem;
    }

    .bold-heading {
      font-weight: 700;
    }

    .input-group {
      position: relative;
      margin-bottom: 20px;
    }

    .input-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #374151;
      font-size: 0.95rem;
    }

    .input-group input {
      width: 100%;
      padding: 14px 45px 14px 14px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      background-color: #f9fafb;
      font-size: 15px;
      transition: all 0.3s ease;
      outline: none;
    }

    .input-group input:focus {
      border-color: #4F46E5;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
      background-color: white;
    }

    .forgot-password {
      text-align: right;
      margin-bottom: 25px;
    }

    .forgot-password a {
      color: #4F46E5;
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.3s ease;
      font-weight: 500;
    }

    .forgot-password a:hover {
      text-decoration: underline;
    }

    .form-btn {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #4F46E5, #7C73E6);
      color: white;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }

    .form-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
    }

    .toggle-text {
      text-align: center;
      margin-top: 25px;
      color: #6b7280;
      font-size: 0.95rem;
    }

    .toggle-text a {
      color: #4F46E5;
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .toggle-text a:hover {
      color: #7C73E6;
      text-decoration: underline;
    }

    .back-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      background: transparent;
      color: #6b7280;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      padding: 8px;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      font-size: 1.2rem;
    }

    .back-btn:hover {
      color: #4F46E5;
      background-color: #f3f4f6;
    }

    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      border-radius: 12px;
      color: white;
      font-weight: 500;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      transform: translateX(150%);
      transition: transform 0.4s ease;
      z-index: 1000;
      max-width: 320px;
      font-size: 0.95rem;
    }

    .notification.show {
      transform: translateX(0);
    }

    .success {
      background: linear-gradient(135deg, #10B981, #34D399);
    }

    .error {
      background: linear-gradient(135deg, #EF4444, #F87171);
    }

    .password-toggle {
      position: absolute;
      right: 15px;
      top: 42px;
      transform: translateY(-50%);
      cursor: pointer;
      color: #9ca3af;
      background: none;
      border: none;
      padding: 6px;
      border-radius: 6px;
      font-size: 1rem;
      transition: all 0.2s ease;
    }

    .password-toggle:hover {
      color: #4F46E5;
      background-color: #f3f4f6;
    }

    .password-strength {
      margin-top: 8px;
      height: 6px;
      border-radius: 3px;
      background-color: #e5e7eb;
      overflow: hidden;
    }

    .password-strength-bar {
      height: 100%;
      width: 0%;
      transition: all 0.3s ease;
      border-radius: 3px;
    }

    .strength-weak {
      background-color: #EF4444;
      width: 25%;
    }

    .strength-medium {
      background-color: #F59E0B;
      width: 50%;
    }

    .strength-strong {
      background-color: #10B981;
      width: 75%;
    }

    .strength-very-strong {
      background-color: #059669;
      width: 100%;
    }

    .password-strength-text {
      font-size: 0.8rem;
      margin-top: 4px;
      text-align: right;
    }

    .social-login {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 25px;
    }

    .social-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .social-icon:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .fb {
      background: linear-gradient(135deg, #3b5998, #4a6fbe);
    }

    .google {
      background: linear-gradient(135deg, #DB4437, #e57373);
    }

    .github {
      background: linear-gradient(135deg, #333333, #555555);
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .shake {
      animation: shake 0.5s ease-in-out;
    }

    @media (max-width: 480px) {
      body {
        padding: 15px;
      }
      
      .main-container {
        max-width: 360px;
      }
      
      .welcome-screen, .form-container {
        padding: 35px 25px;
      }
      
      .welcome-screen h1 {
        font-size: 1.7rem;
      }
      
      .container {
        min-height: 440px;
      }
      
      .login-container {
        min-height: 440px;
      }
      
      .register-container {
        min-height: 540px;
      }
    }

    /* Particle background */
    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      z-index: -1;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
  </style>
</head>
<body>
  <!-- Particle Background -->
  <div id="particles-js"></div>
  
  <!-- PHP Notifications -->
  <?php if ($error): ?>
    <div class="notification error show" id="phpError">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>
  
  <?php if ($success): ?>
    <div class="notification success show" id="phpSuccess">
      <?php echo htmlspecialchars($success); ?>
    </div>
  <?php endif; ?>
  
  <!-- Main Wrapper -->
  <div class="main-container">
    <!-- Main Container -->
    <div class="container" id="container">
      <!-- Welcome Screen -->
      <div class="welcome-screen">
        <h1>Welcome to Our Platform</h1>
        <p>Join our community to access exclusive features and connect with like-minded people.</p>
        <div class="welcome-buttons">
          <button class="btn btn-primary" id="showLogin">Login</button>
          <button class="btn btn-secondary" id="showRegister">Register</button>
        </div>
      </div>
      
      <!-- Login Form -->
      <div class="form-container login-container">
        <button class="back-btn" id="backFromLogin">
          <i class="ri-close-line"></i>
        </button>
        <h2 class="bold-heading">Welcome Back</h2>
        <form id="loginForm" method="POST" action="">
          <input type="hidden" name="login" value="1">
          <div class="input-group">
            <label for="loginUsername">Username</label>
            <input type="text" id="loginUsername" name="username" placeholder="Enter your username" required 
                   value="<?php echo (isset($_POST['username']) && !$success) ? htmlspecialchars($_POST['username']) : ''; ?>">
          </div>
          <div class="input-group">
            <label for="loginPassword">Password</label>
            <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
            <button type="button" class="password-toggle" id="loginPasswordToggle">
              <i class="ri-eye-line"></i>
            </button>
          </div>
          <div class="forgot-password">
            <a href="#" id="forgotPassword">Forgot password?</a>
          </div>
          <button type="submit" class="form-btn">Sign In</button>
        </form>
        <div class="social-login">
          <div class="social-icon fb">
            <i class="ri-facebook-fill"></i>
          </div>
          <div class="social-icon google">
            <i class="ri-google-fill"></i>
          </div>
          <div class="social-icon github">
            <i class="ri-github-fill"></i>
          </div>
        </div>
        <div class="toggle-text">
          Don't have an account? <a id="showRegisterFromLogin">Register here</a>
        </div>
      </div>

      <!-- Register Form -->
      <div class="form-container register-container">
        <button class="back-btn" id="backFromRegister">
          <i class="ri-close-line"></i>
        </button>
        <h2 class="bold-heading">Create Account</h2>
        <form id="registerForm" method="POST" action="">
          <input type="hidden" name="register" value="1">
          <div class="input-group">
            <label for="registerName">Full Name</label>
            <input type="text" id="registerName" name="fullname" placeholder="Enter your full name" required
                   value="<?php echo (isset($_POST['fullname']) && !$success) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
          </div>
          <div class="input-group">
            <label for="registerEmail">Email</label>
            <input type="email" id="registerEmail" name="email" placeholder="Enter your email" required
                   value="<?php echo (isset($_POST['email']) && !$success) ? htmlspecialchars($_POST['email']) : ''; ?>">
          </div>
          <div class="input-group">
            <label for="registerUsername">Username</label>
            <input type="text" id="registerUsername" name="username" placeholder="Enter your username" required
                   value="<?php echo (isset($_POST['username']) && !$success) ? htmlspecialchars($_POST['username']) : ''; ?>">
          </div>
          <div class="input-group">
            <label for="registerPassword">Password</label>
            <input type="password" id="registerPassword" name="password" placeholder="Enter your password" required>
            <button type="button" class="password-toggle" id="registerPasswordToggle">
              <i class="ri-eye-line"></i>
            </button>
            <div class="password-strength">
              <div class="password-strength-bar" id="passwordStrengthBar"></div>
            </div>
            <div class="password-strength-text" id="passwordStrengthText"></div>
          </div>
          <div class="input-group">
            <label for="registerConfirmPassword">Confirm Password</label>
            <input type="password" id="registerConfirmPassword" name="confirm_password" placeholder="Confirm your password" required>
            <button type="button" class="password-toggle" id="registerConfirmPasswordToggle">
              <i class="ri-eye-line"></i>
            </button>
          </div>
          <button type="submit" class="form-btn">Register</button>
        </form>
        <div class="social-login">
          <div class="social-icon fb">
            <i class="ri-facebook-fill"></i>
          </div>
          <div class="social-icon google">
            <i class="ri-google-fill"></i>
          </div>
          <div class="social-icon github">
            <i class="ri-github-fill"></i>
          </div>
        </div>
        <div class="toggle-text">
          Already have an account? <a id="showLoginFromRegister">Login here</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification -->
  <div class="notification" id="notification"></div>

  <!-- Particles.js -->
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

  <script>
    // Initialize particles.js
    particlesJS("particles-js", {
      particles: {
        number: { 
          value: 80, 
          density: { 
            enable: true, 
            value_area: 800 
          } 
        },
        color: { 
          value: "#ffffff" 
        },
        shape: { 
          type: "circle",
          stroke: {
            width: 0,
            color: "#000000"
          }
        },
        opacity: { 
          value: 0.5, 
          random: true,
          anim: {
            enable: true,
            speed: 1,
            opacity_min: 0.2,
            sync: false
          }
        },
        size: { 
          value: 3, 
          random: true,
          anim: {
            enable: true,
            speed: 2,
            size_min: 1,
            sync: false
          }
        },
        line_linked: { 
          enable: true,
          distance: 150,
          color: "#ffffff",
          opacity: 0.3,
          width: 1
        },
        move: { 
          enable: true, 
          speed: 1.5, 
          direction: "none", 
          random: true,
          out_mode: "bounce",
          bounce: false
        }
      },
      interactivity: {
        detect_on: "canvas",
        events: { 
          onhover: { 
            enable: true, 
            mode: "grab" 
          }, 
          onclick: { 
            enable: true, 
            mode: "push" 
          } 
        },
        modes: {
          grab: {
            distance: 150,
            line_linked: {
              opacity: 0.4
            }
          },
          push: {
            particles_nb: 4
          }
        }
      },
      retina_detect: true
    });

    // DOM Elements
    const container = document.getElementById('container');
    const showLogin = document.getElementById('showLogin');
    const showRegister = document.getElementById('showRegister');
    const showRegisterFromLogin = document.getElementById('showRegisterFromLogin');
    const showLoginFromRegister = document.getElementById('showLoginFromRegister');
    const backFromLogin = document.getElementById('backFromLogin');
    const backFromRegister = document.getElementById('backFromRegister');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const notification = document.getElementById('notification');
    const forgotPassword = document.getElementById('forgotPassword');
    
    // Password toggle elements
    const loginPasswordToggle = document.getElementById('loginPasswordToggle');
    const registerPasswordToggle = document.getElementById('registerPasswordToggle');
    const registerConfirmPasswordToggle = document.getElementById('registerConfirmPasswordToggle');
    
    // Password strength elements
    const passwordStrengthBar = document.getElementById('passwordStrengthBar');
    const passwordStrengthText = document.getElementById('passwordStrengthText');

    // Auto-show forms if there are PHP errors/success and auto-redirect on successful registration
    <?php if ($error || $success): ?>
      <?php if (isset($_POST['login'])): ?>
        showLoginForm();
      <?php elseif (isset($_POST['register'])): ?>
        <?php if ($success): ?>
          // Auto-redirect to login form after successful registration
          setTimeout(() => {
            showLoginForm();
          }, 100);
        <?php else: ?>
          showRegisterForm();
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>

    // Function to adjust container height based on active form
    function adjustContainerHeight() {
      if (container.classList.contains('show-login')) {
        container.style.minHeight = '480px';
      } else if (container.classList.contains('show-register')) {
        container.style.minHeight = '580px';
      } else {
        container.style.minHeight = '480px';
      }
      
      // Mobile adjustments
      if (window.innerWidth <= 480) {
        if (container.classList.contains('show-login')) {
          container.style.minHeight = '440px';
        } else if (container.classList.contains('show-register')) {
          container.style.minHeight = '540px';
        } else {
          container.style.minHeight = '440px';
        }
      }
    }

    // Toggle between welcome and forms
    function showLoginForm() {
      container.classList.add('show-login');
      container.classList.remove('show-register');
      setTimeout(adjustContainerHeight, 10);
    }

    function showRegisterForm() {
      container.classList.add('show-register');
      container.classList.remove('show-login');
      setTimeout(adjustContainerHeight, 10);
    }

    function showWelcomeScreen() {
      container.classList.remove('show-login', 'show-register');
      setTimeout(adjustContainerHeight, 10);
    }

    // Event listeners
    showLogin.addEventListener('click', showLoginForm);
    showRegister.addEventListener('click', showRegisterForm);
    showRegisterFromLogin.addEventListener('click', showRegisterForm);
    showLoginFromRegister.addEventListener('click', showLoginForm);
    backFromLogin.addEventListener('click', showWelcomeScreen);
    backFromRegister.addEventListener('click', showWelcomeScreen);

    // Initialize container height
    adjustContainerHeight();

    // Password visibility toggle
    function setupPasswordToggle(toggleElement, passwordFieldId) {
      toggleElement.addEventListener('click', () => {
        const passwordField = document.getElementById(passwordFieldId);
        const icon = toggleElement.querySelector('i');
        
        if (passwordField.type === 'password') {
          passwordField.type = 'text';
          icon.classList.remove('ri-eye-line');
          icon.classList.add('ri-eye-off-line');
        } else {
          passwordField.type = 'password';
          icon.classList.remove('ri-eye-off-line');
          icon.classList.add('ri-eye-line');
        }
      });
    }

    // Set up password toggles
    setupPasswordToggle(loginPasswordToggle, 'loginPassword');
    setupPasswordToggle(registerPasswordToggle, 'registerPassword');
    setupPasswordToggle(registerConfirmPasswordToggle, 'registerConfirmPassword');

    // Password strength checker
    function checkPasswordStrength(password) {
      let strength = 0;
      let text = '';
      
      if (password.length >= 8) strength++;
      if (password.match(/[a-z]+/)) strength++;
      if (password.match(/[A-Z]+/)) strength++;
      if (password.match(/[0-9]+/)) strength++;
      if (password.match(/[$@#&!]+/)) strength++;
      
      // Update strength bar and text
      switch(strength) {
        case 0:
        case 1:
          passwordStrengthBar.className = 'password-strength-bar strength-weak';
          text = 'Weak';
          break;
        case 2:
          passwordStrengthBar.className = 'password-strength-bar strength-weak';
          text = 'Weak';
          break;
        case 3:
          passwordStrengthBar.className = 'password-strength-bar strength-medium';
          text = 'Medium';
          break;
        case 4:
          passwordStrengthBar.className = 'password-strength-bar strength-strong';
          text = 'Strong';
          break;
        case 5:
          passwordStrengthBar.className = 'password-strength-bar strength-very-strong';
          text = 'Very Strong';
          break;
      }
      
      passwordStrengthText.textContent = text;
    }

    // Add password strength checker to register password field
    document.getElementById('registerPassword').addEventListener('input', function() {
      checkPasswordStrength(this.value);
    });

    // Show notification
    function showNotification(message, type) {
      notification.textContent = message;
      notification.className = 'notification';
      notification.classList.add(type, 'show');
      
      setTimeout(() => {
        notification.classList.remove('show');
      }, 4000);
    }

    // Auto-hide PHP notifications
    setTimeout(() => {
      const phpError = document.getElementById('phpError');
      const phpSuccess = document.getElementById('phpSuccess');
      
      if (phpError) phpError.classList.remove('show');
      if (phpSuccess) phpSuccess.classList.remove('show');
    }, 4000);

    // Forgot password functionality
    forgotPassword.addEventListener('click', (e) => {
      e.preventDefault();
      showNotification('Password reset feature would be implemented here!', 'success');
    });

    // Social login functionality
    document.querySelectorAll('.social-icon').forEach(icon => {
      icon.addEventListener('click', function() {
        const platform = this.classList.contains('fb') ? 'Facebook' : 
                        this.classList.contains('google') ? 'Google' : 'GitHub';
        showNotification(`${platform} login would be implemented here!`, 'success');
      });
    });

    // Adjust container height on window resize
    window.addEventListener('resize', adjustContainerHeight);
  </script>
</body>
</html>