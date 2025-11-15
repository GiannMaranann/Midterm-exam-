<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get logged in user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Handle user management operations
$message = '';
$message_type = '';

// Add new user record
if (isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    
    // Validate inputs
    if (empty($full_name) || empty($email) || empty($username) || empty($role)) {
        $message = "Please fill in all fields!";
        $message_type = "error";
    } else {
        // Check if email or username already exists
        $check_stmt = $pdo->prepare("SELECT id FROM user_records WHERE email = ? OR username = ?");
        $check_stmt->execute([$email, $username]);
        
        if ($check_stmt->rowCount() > 0) {
            $message = "Email or username already exists!";
            $message_type = "error";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO user_records (full_name, email, username, user_role, profile_image) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $username, $role, 'default-avatar.png']);
                $message = "Record added successfully!";
                $message_type = "success";
            } catch(PDOException $e) {
                $message = "Error adding user: " . $e->getMessage();
                $message_type = "error";
            }
        }
    }
}

// Update user record
if (isset($_POST['update_user'])) {
    $record_id = $_POST['record_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    
    try {
        $stmt = $pdo->prepare("UPDATE user_records SET full_name = ?, email = ?, username = ?, user_role = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $username, $role, $record_id]);
        $message = "Record updated successfully!";
        $message_type = "success";
    } catch(PDOException $e) {
        $message = "Error updating user: " . $e->getMessage();
        $message_type = "error";
    }
}

// Delete user record
if (isset($_GET['delete'])) {
    $record_id = $_GET['delete'];
    
    // Confirmation is handled by JavaScript
    try {
        $stmt = $pdo->prepare("DELETE FROM user_records WHERE id = ?");
        $stmt->execute([$record_id]);
        $message = "Record deleted successfully.";
        $message_type = "success";
    } catch(PDOException $e) {
        $message = "Error deleting user: " . $e->getMessage();
        $message_type = "error";
    }
}

// Handle profile image upload
if (isset($_POST['upload_profile_image'])) {
    $record_id = $_POST['record_id'];
    $upload_dir = 'uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_type = $_FILES['profile_image']['type'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            if ($file_size <= 5000000) { // 5MB max
                // Generate unique file name
                $new_file_name = 'profile_' . $record_id . '_' . time() . '.' . $file_ext;
                $dest_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                    // Update database with new profile image
                    $stmt = $pdo->prepare("UPDATE user_records SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$new_file_name, $record_id]);
                    $message = "Profile image updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error uploading file.";
                    $message_type = "error";
                }
            } else {
                $message = "File size must be less than 5MB.";
                $message_type = "error";
            }
        } else {
            $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
            $message_type = "error";
        }
    } else {
        $message = "Please select a valid image file.";
        $message_type = "error";
    }
}

// Get all user records
$stmt = $pdo->query("SELECT * FROM user_records ORDER BY date_added DESC");
$user_records = $stmt->fetchAll();

// Logout functionality
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .logo {
            display: flex;
            align-items: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .logo i {
            font-size: 24px;
            margin-right: 10px;
        }

        .logo h1 {
            font-size: 20px;
            font-weight: 600;
        }

        .menu {
            flex: 1;
        }

        .menu-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: rgba(255, 255, 255, 0.3);
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .logout-section {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }

        .logout-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .logout-btn i {
            margin-right: 8px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.8s ease-out;
        }

        .header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
        }

        .header h2 i {
            margin-right: 15px;
            color: #1e3c72;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #1e3c72;
        }

        /* PHP Message Styles */
        .php-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideDown 0.5s ease-out;
        }

        .php-message.success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }

        .php-message.error {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        /* Table Styles */
        .table-container {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            animation: slideUp 0.8s ease-out;
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            font-size: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
        }

        .table-title i {
            margin-right: 10px;
            color: #1e3c72;
        }

        .add-btn {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3);
        }

        .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(30, 60, 114, 0.4);
        }

        .add-btn i {
            margin-right: 10px;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 600;
            color: #555;
            background-color: #f8f9fa;
        }

        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .view-btn, .edit-btn, .delete-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 12px;
            display: flex;
            align-items: center;
        }

        .view-btn {
            background-color: #3498db;
            color: white;
        }

        .view-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .edit-btn {
            background-color: #2ecc71;
            color: white;
        }

        .edit-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .view-btn i, .edit-btn i, .delete-btn i {
            margin-right: 5px;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #777;
        }

        .no-data i {
            font-size: 70px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .no-data h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.4s;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .modal-overlay.active .modal {
            transform: scale(1);
            opacity: 1;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
        }

        .modal-title i {
            margin-right: 10px;
            color: #1e3c72;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #777;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close-btn:hover {
            color: #e74c3c;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
        }

        .form-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            align-items: start;
        }

        .profile-image-section {
            text-align: center;
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-image-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #1e3c72;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .upload-btn {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 60, 114, 0.4);
        }

        .upload-btn i {
            margin-right: 8px;
        }

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #1e3c72;
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }

        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .submit-btn {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(30, 60, 114, 0.4);
        }

        .submit-btn i {
            margin-right: 10px;
        }

        /* Delete Confirmation Modal */
        .delete-modal {
            max-width: 500px;
        }

        .delete-modal .modal-body {
            text-align: center;
        }

        .warning-icon {
            font-size: 60px;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        .delete-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .cancel-btn {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .cancel-btn:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .confirm-delete-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .confirm-delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .person-row {
            animation: fadeIn 0.5s ease-out;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .dashboard-container {
                flex-direction: column;
            }
            
            .form-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 15px;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .add-btn {
                margin-top: 15px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .modal {
                width: 95%;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- PHP Message Display -->
    <?php if ($message): ?>
        <div class="php-message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-user-cog"></i>
                <h1>Admin Dashboard</h1>
            </div>
            <div class="menu">
                <div class="menu-item active">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </div>
            </div>
            <div class="logout-section">
                <form method="POST">
                    <button type="submit" name="logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-tachometer-alt"></i> User Management Dashboard</h2>
                <div class="user-info">
                    <img src="uploads/<?php echo htmlspecialchars($current_user['profile_image'] ?? 'default-avatar.png'); ?>" 
                         alt="Profile" class="user-avatar"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDUiIGhlaWdodD0iNDUiIHZpZXdCb3g9IjAgMCA0NSA0NSIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjIuNSIgY3k9IjIyLjUiIHI9IjIyLjUiIGZpbGw9IiMxZTNjNzIiLz4KPHN2ZyB4PSIxMSIgeT0iMTEiIHdpZHRoPSIyMyIgaGVpZ2h0PSIyMyIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIj4KPHBhdGggZD0iTTEyIDJDMTMuMSAyIDE0IDIuOSAxNCA0QzE0IDUuMSAxMy4xIDYgMTIgNkMxMC45IDYgMTAgNS4xIDEwIDRDMTAgMi45IDEwLjkgMiAxMiAyWk0yMSAxOFYyMEgzVjE4QzMgMTUuOCA2LjEgMTQgMTIgMTRDMTcuOSAxNCAyMSAxNS45IDIxIDE4WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cjwvc3ZnPgo='">
                    <span>Welcome, <?php echo htmlspecialchars($current_user['fullname']); ?>!</span>
                </div>
            </div>

            <!-- User Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-table"></i>
                        User Records
                    </h3>
                    <button class="add-btn" id="openModalBtn">
                        <i class="fas fa-user-plus"></i> Add New User
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="userTable">
                        <thead>
                            <tr>
                                <th>Profile</th>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Date Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if (count($user_records) > 0): ?>
                                <?php foreach ($user_records as $record): ?>
                                    <tr class="person-row">
                                        <td>
                                            <img src="uploads/<?php echo htmlspecialchars($record['profile_image'] ?? 'default-avatar.png'); ?>" 
                                                 alt="Profile" class="user-avatar-small"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiMxZTNjNzIiLz4KPHN2ZyB4PSI4IiB5PSI4IiB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSI+CjxwYXRoIGQ9Ik0xMiAyQzEzLjEgMiAxNCAyLjkgMTQgNEMxNCA1LjEgMTMuMSA2IDEyIDZDMTAgNiAxMCA1LjEgMTAgNEMxMCAyLjkgMTAuOSAyIDEyIDJaTTIxIDE4VjIwSDNWMThDMyAxNS44IDYuMSAxNCAxMiAxNEMxNy45IDE0IDIxIDE1LjkgMjEgMThaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4KPC9zdmc+Cg=='">
                                        </td>
                                        <td><?php echo $record['id']; ?></td>
                                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['email']); ?></td>
                                        <td><?php echo htmlspecialchars($record['username']); ?></td>
                                        <td>
                                            <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; 
                                                background-color: <?php 
                                                    echo $record['user_role'] == 'Admin' ? '#e74c3c' : 
                                                           ($record['user_role'] == 'Staff' ? '#3498db' : '#2ecc71'); 
                                                ?>; color: white;">
                                                <?php echo htmlspecialchars($record['user_role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($record['date_added'])); ?></td>
                                        <td class="actions">
                                            <button class="view-btn" onclick="openViewModal(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="edit-btn" onclick="openEditModal(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="delete-btn" onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo addslashes($record['full_name']); ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="no-data">
                                            <i class="fas fa-users"></i>
                                            <h3>No Users Added Yet</h3>
                                            <p>Add your first user using the button above</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Adding User -->
    <div class="modal-overlay" id="addModalOverlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </h3>
                <button class="close-btn" onclick="closeAddModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" method="POST" enctype="multipart/form-data">
                    <div class="form-layout">
                        <div class="profile-image-section">
                            <div class="profile-image-container">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxjaXJdsZUgY3g9Ijc1IiBjeT0iNzUiIHI9Ijc1IiBmaWxsPSIjZGRkZGRkIi8+CjxzdmcgeD0iNDAiIHk9IjQwIiB3aWR0aD0iNzAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSI+CjxwYXRoIGQ9Ik0xMiAyQzEzLjEgMiAxNCAyLjkgMTQgNEMxNCA1LjEgMTMuMSA2IDEyIDZDMTAgNiAxMCA1LjEgMTAgNEMxMCAyLjkgMTAuOSAyIDEyIDJaTTIxIDE4VjIwSDNWMThDMyAxNS44IDYuMSAxNCAxMiAxNEMxNy45IDE0IDIxIDE1LjkgMjEgMThaIiBmaWxsPSIjOTk5OTk5Ii8+Cjwvc3ZnPgo8L3N2Zz4K" 
                                     alt="Profile" class="profile-image-large" id="addProfileImage">
                            </div>
                            <input type="file" id="addProfileImageInput" name="profile_image" accept="image/*" style="display: none;" onchange="previewAddImage(this)">
                            <button type="button" class="upload-btn" onclick="document.getElementById('addProfileImageInput').click()">
                                <i class="fas fa-upload"></i> Upload Image
                            </button>
                        </div>
                        <div class="form-section">
                            <div class="form-group">
                                <label for="add_full_name">Full Name *</label>
                                <input type="text" id="add_full_name" name="full_name" placeholder="Enter full name" required>
                            </div>
                            <div class="form-group">
                                <label for="add_email">Email Address *</label>
                                <input type="email" id="add_email" name="email" placeholder="Enter email address" required>
                            </div>
                            <div class="form-group">
                                <label for="add_username">Username *</label>
                                <input type="text" id="add_username" name="username" placeholder="Enter username" required>
                            </div>
                            <div class="form-group">
                                <label for="add_role">User Role *</label>
                                <select id="add_role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Staff">Staff</option>
                                    <option value="Regular User">Regular User</option>
                                </select>
                            </div>
                            <button type="submit" class="submit-btn" name="add_user">
                                <i class="fas fa-plus"></i>
                                Add User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Viewing/Editing User -->
    <div class="modal-overlay" id="viewModalOverlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="viewModalTitle">
                    <i class="fas fa-user"></i>
                    User Details
                </h3>
                <button class="close-btn" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="viewUserForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="view_record_id" name="record_id">
                    <div class="form-layout">
                        <div class="profile-image-section">
                            <div class="profile-image-container">
                                <img src="" alt="Profile" class="profile-image-large" id="viewProfileImage">
                            </div>
                            <input type="file" id="viewProfileImageInput" name="profile_image" accept="image/*" style="display: none;" onchange="previewViewImage(this)">
                            <button type="button" class="upload-btn" onclick="document.getElementById('viewProfileImageInput').click()">
                                <i class="fas fa-upload"></i> Change Image
                            </button>
                            <button type="submit" class="upload-btn" name="upload_profile_image" style="margin-top: 10px; background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                                <i class="fas fa-save"></i> Save Image
                            </button>
                        </div>
                        <div class="form-section">
                            <div class="form-group">
                                <label for="view_full_name">Full Name</label>
                                <input type="text" id="view_full_name" name="full_name" placeholder="Enter full name" required>
                            </div>
                            <div class="form-group">
                                <label for="view_email">Email Address</label>
                                <input type="email" id="view_email" name="email" placeholder="Enter email address" required>
                            </div>
                            <div class="form-group">
                                <label for="view_username">Username</label>
                                <input type="text" id="view_username" name="username" placeholder="Enter username" required>
                            </div>
                            <div class="form-group">
                                <label for="view_role">User Role</label>
                                <select id="view_role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Staff">Staff</option>
                                    <option value="Regular User">Regular User</option>
                                </select>
                            </div>
                            <button type="submit" class="submit-btn" name="update_user">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModalOverlay">
        <div class="modal delete-modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirm Delete
                </h3>
                <button class="close-btn" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3 style="margin-bottom: 10px; color: #e74c3c;">Are you sure?</h3>
                <p id="deleteMessage" style="color: #666; line-height: 1.6;">
                    You are about to delete a user record. This action cannot be undone.
                </p>
                <div class="delete-actions">
                    <button class="cancel-btn" onclick="closeDeleteModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <a href="#" id="confirmDeleteBtn" class="confirm-delete-btn">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const addModalOverlay = document.getElementById('addModalOverlay');
        const viewModalOverlay = document.getElementById('viewModalOverlay');
        const deleteModalOverlay = document.getElementById('deleteModalOverlay');

        // Open Add Modal
        document.getElementById('openModalBtn').addEventListener('click', () => {
            addModalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close Add Modal
        function closeAddModal() {
            addModalOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Open View/Edit Modal
        function openViewModal(recordId) {
            // Fetch user data via AJAX
            fetch(`get_user.php?id=${recordId}`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('view_record_id').value = user.id;
                    document.getElementById('view_full_name').value = user.full_name;
                    document.getElementById('view_email').value = user.email;
                    document.getElementById('view_username').value = user.username;
                    document.getElementById('view_role').value = user.user_role;
                    
                    // Set profile image
                    const profileImg = document.getElementById('viewProfileImage');
                    profileImg.src = `uploads/${user.profile_image || 'default-avatar.png'}`;
                    profileImg.onerror = function() {
                        this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxjaXJdsZUgY3g9Ijc1IiBjeT0iNzUiIHI9Ijc1IiBmaWxsPSIjZGRkZGRkIi8+CjxzdmcgeD0iNDAiIHk9IjQwIiB3aWR0aD0iNzAiIGhlaWdodD0iNzAiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSI+CjxwYXRoIGQ9Ik0xMiAyQzEzLjEgMiAxNCAyLjkgMTQgNEMxNCA1LjEgMTMuMSA2IDEyIDZDMTAgNiAxMCA1LjEgMTAgNEMxMCAyLjkgMTAuOSAyIDEyIDJaTTIxIDE4VjIwSDNWMThDMyAxNS44IDYuMSAxNCAxMiAxNEMxNy45IDE0IDIxIDE1LjkgMjEgMThaIiBmaWxsPSIjOTk5OTk5Ii8+Cjwvc3ZnPgo8L3N2Zz4K';
                    };
                    
                    viewModalOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Error fetching user data:', error);
                    alert('Error loading user data');
                });
        }

        // Close View Modal
        function closeViewModal() {
            viewModalOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Open Edit Modal (same as view but with edit mode)
        function openEditModal(recordId) {
            openViewModal(recordId);
        }

        // Confirm Delete
        function confirmDelete(recordId, userName) {
            document.getElementById('deleteMessage').innerHTML = 
                `You are about to delete <strong>"${userName}"</strong>. This action cannot be undone.`;
            document.getElementById('confirmDeleteBtn').href = `?delete=${recordId}`;
            deleteModalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Close Delete Modal
        function closeDeleteModal() {
            deleteModalOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Image Preview Functions
        function previewAddImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('addProfileImage').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewViewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('viewProfileImage').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Close modals on overlay click
        document.addEventListener('click', (e) => {
            if (e.target === addModalOverlay) closeAddModal();
            if (e.target === viewModalOverlay) closeViewModal();
            if (e.target === deleteModalOverlay) closeDeleteModal();
        });

        // Close modals on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAddModal();
                closeViewModal();
                closeDeleteModal();
            }
        });

        // Auto-hide PHP messages after 5 seconds
        setTimeout(() => {
            const phpMessage = document.querySelector('.php-message');
            if (phpMessage) {
                phpMessage.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>