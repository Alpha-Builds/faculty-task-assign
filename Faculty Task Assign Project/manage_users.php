<?php
session_start();
require_once "config.php";

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Handle user deletion
if(isset($_GET["delete"]) && $_GET["delete"] != $_SESSION["id"]){
    $user_id = $_GET["delete"];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete task assignments
        $delete_assignments = "DELETE FROM task_assignments WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_assignments);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Delete comments
        $delete_comments = "DELETE FROM comments WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_comments);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Delete user
        $delete_user = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_user);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Redirect to refresh the page
        header("location: manage_users.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        echo "Error deleting user: " . $e->getMessage();
    }
}

// Fetch all faculty members with their task statistics
$sql = "SELECT u.*, 
        COUNT(DISTINCT ta.task_id) as total_tasks,
        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM users u
        LEFT JOIN task_assignments ta ON u.id = ta.user_id
        LEFT JOIN tasks t ON ta.task_id = t.id
        WHERE u.role = 'faculty'
        GROUP BY u.id
        ORDER BY u.faculty_name";

$faculty_members = array();
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $faculty_members[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Task Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .user-card {
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <h3 class="text-center py-4">Task Manager</h3>
                <nav>
                    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="create_task.php"><i class="fas fa-plus"></i> Create Task</a>
                    <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                    <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Faculty Members</h2>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New Faculty
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Total Faculty Members</h5>
                            <h2><?php echo count($faculty_members); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Active Tasks</h5>
                            <h2><?php echo array_sum(array_column($faculty_members, 'total_tasks')); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Completed Tasks</h5>
                            <h2><?php echo array_sum(array_column($faculty_members, 'completed_tasks')); ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Faculty Members List -->
                <div class="row">
                    <?php foreach($faculty_members as $faculty): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card user-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($faculty['faculty_name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Department:</strong> <?php echo htmlspecialchars($faculty['department']); ?><br>
                                        <strong>Email:</strong> <?php echo htmlspecialchars($faculty['email']); ?><br>
                                        <strong>Phone:</strong> <?php echo htmlspecialchars($faculty['phone_number']); ?>
                                    </p>
                                    <div class="progress mb-3">
                                        <?php 
                                        $percentage = $faculty['total_tasks'] > 0 
                                            ? round(($faculty['completed_tasks'] / $faculty['total_tasks']) * 100) 
                                            : 0;
                                        ?>
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%"
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo $percentage; ?>%
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">
                                            Tasks: <?php echo $faculty['total_tasks']; ?> | 
                                            Completed: <?php echo $faculty['completed_tasks']; ?>
                                        </small>
                                        <div>
                                            <a href="edit_user.php?id=<?php echo $faculty['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if($faculty['id'] != $_SESSION["id"]): ?>
                                                <a href="manage_users.php?delete=<?php echo $faculty['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 