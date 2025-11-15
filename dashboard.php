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
    
    try {
        $stmt = $pdo->prepare("INSERT INTO user_records (full_name, email, username, user_role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $username, $role]);
        $message = "User added successfully!";
        $message_type = "success";
    } catch(PDOException $e) {
        $message = "Error adding user: " . $e->getMessage();
        $message_type = "error";
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
        $message = "User updated successfully!";
        $message_type = "success";
    } catch(PDOException $e) {
        $message = "Error updating user: " . $e->getMessage();
        $message_type = "error";
    }
}

// Delete user record
if (isset($_GET['delete'])) {
    $record_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM user_records WHERE id = ?");
        $stmt->execute([$record_id]);
        $message = "User deleted successfully!";
        $message_type = "success";
    } catch(PDOException $e) {
        $message = "Error deleting user: " . $e->getMessage();
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
        }

        .logo {
            display: flex;
            align-items: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            margin-top: 30px;
        }

        .menu-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
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

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .logout-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .logout-btn i {
            margin-right: 8px;
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
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
        }

        .edit-btn:hover {
            background-color: #2980b9;
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

        .edit-btn i, .delete-btn i {
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
            max-width: 600px;
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

        .form-group {
            margin-bottom: 20px;
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
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 15px;
            }
            
            .menu {
                display: flex;
                overflow-x: auto;
            }
            
            .menu-item {
                white-space: nowrap;
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
                <div class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-tachometer-alt"></i> User Management Dashboard</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($current_user['fullname']); ?>!</span>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="logout" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
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
                                        <td><?php echo $record['id']; ?></td>
                                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['email']); ?></td>
                                        <td><?php echo htmlspecialchars($record['username']); ?></td>
                                        <td><?php echo htmlspecialchars($record['user_role']); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($record['date_added'])); ?></td>
                                        <td class="actions">
                                            <button class="edit-btn" onclick="openEditModal(
                                                <?php echo $record['id']; ?>,
                                                '<?php echo addslashes($record['full_name']); ?>',
                                                '<?php echo addslashes($record['email']); ?>',
                                                '<?php echo addslashes($record['username']); ?>',
                                                '<?php echo addslashes($record['user_role']); ?>'
                                            )">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="?delete=<?php echo $record['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
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

    <!-- Modal for Adding/Editing User -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </h3>
                <button class="close-btn" id="closeModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="userForm" method="POST">
                    <input type="hidden" id="record_id" name="record_id">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter full name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter email address" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label for="role">User Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                            <option value="Regular User">Regular User</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-btn" id="submitBtn" name="add_user">
                        <i class="fas fa-plus"></i>
                        Add User
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const modalOverlay = document.getElementById('modalOverlay');
        const userForm = document.getElementById('userForm');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');
        const recordId = document.getElementById('record_id');

        // Initialize the dashboard
        function initDashboard() {
            // Set up event listeners
            openModalBtn.addEventListener('click', openAddModal);
            closeModalBtn.addEventListener('click', closeModal);
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) closeModal();
            });
            
            // Add keyboard event listener to close modal on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeModal();
            });
        }

        // Open modal for adding user
        function openAddModal() {
            modalTitle.innerHTML = '<i class="fas fa-user-plus"></i> Add New User';
            submitBtn.innerHTML = '<i class="fas fa-plus"></i> Add User';
            submitBtn.name = 'add_user';
            userForm.reset();
            recordId.value = '';
            openModal();
        }

        // Open modal for editing user
        function openEditModal(id, fullName, email, username, role) {
            modalTitle.innerHTML = '<i class="fas fa-user-edit"></i> Edit User';
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Update User';
            submitBtn.name = 'update_user';
            
            // Prefill form with user data
            recordId.value = id;
            document.getElementById('full_name').value = fullName;
            document.getElementById('email').value = email;
            document.getElementById('username').value = username;
            document.getElementById('role').value = role;
            
            openModal();
        }

        // Open modal function
        function openModal() {
            modalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Close modal function
        function closeModal() {
            modalOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Auto-hide PHP messages after 5 seconds
        setTimeout(() => {
            const phpMessage = document.querySelector('.php-message');
            if (phpMessage) {
                phpMessage.style.display = 'none';
            }
        }, 5000);

        // Initialize the dashboard when the page loads
        document.addEventListener('DOMContentLoaded', initDashboard);
    </script>
</body>
</html>
