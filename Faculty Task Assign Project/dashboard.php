<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$user_role = $_SESSION["role"];
$user_id = $_SESSION["id"];

// Fetch tasks based on user role
if($user_role == "admin") {
    $sql = "SELECT t.*, u.username as assigned_to_name 
            FROM tasks t 
            LEFT JOIN task_assignments ta ON t.id = ta.task_id 
            LEFT JOIN users u ON ta.assigned_to = u.id 
            ORDER BY t.due_date ASC";
} else {
    $sql = "SELECT t.*, u.username as assigned_to_name 
            FROM tasks t 
            JOIN task_assignments ta ON t.id = ta.task_id 
            LEFT JOIN users u ON ta.assigned_to = u.id 
            WHERE ta.assigned_to = ? 
            ORDER BY t.due_date ASC";
}

$tasks = array();
if($stmt = mysqli_prepare($conn, $sql)) {
    if($user_role == "faculty") {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    }
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $tasks[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Task Management System</title>
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
        .task-card {
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #28a745; }
        .priority-urgent { border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <h3 class="text-center py-4">Task Manager</h3>
                <nav>
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <?php if($user_role == "admin"): ?>
                        <a href="create_task.php"><i class="fas fa-plus"></i> Create Task</a>
                        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    <?php endif; ?>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></h2>
                    <?php if($user_role == "admin"): ?>
                        <a href="create_task.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create New Task
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Task Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-3">
                                <select name="priority" class="form-control">
                                    <option value="">All Priorities</option>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Overdue">Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="due_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tasks List -->
                <div class="row">
                    <?php foreach($tasks as $task): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card task-card priority-<?php echo strtolower($task['priority']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($task['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($task['description'], 0, 100)) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge badge-<?php 
                                            echo $task['priority'] == 'High' ? 'danger' : 
                                                ($task['priority'] == 'Medium' ? 'warning' : 
                                                ($task['priority'] == 'Low' ? 'success' : 'danger')); 
                                        ?>">
                                            <?php echo htmlspecialchars($task['priority']); ?>
                                        </span>
                                        <small class="text-muted">
                                            Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                        </small>
                                    </div>
                                    <?php if($user_role == "admin"): ?>
                                        <div class="mt-2">
                                            <small>Assigned to: <?php echo htmlspecialchars($task['assigned_to_name']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-3">
                                        <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                            View Details
                                        </a>
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