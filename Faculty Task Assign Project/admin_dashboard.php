<?php
session_start();
require_once "config.php";

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Fetch all tasks with assigned user details
$sql = "SELECT t.*, u.username as created_by_username, 
        ta.user_id as assigned_to_id, 
        au.username as assigned_to_username,
        au.faculty_name as assigned_to_name,
        au.department as assigned_to_department
        FROM tasks t 
        LEFT JOIN users u ON t.created_by = u.id 
        LEFT JOIN task_assignments ta ON t.id = ta.task_id 
        LEFT JOIN users au ON ta.user_id = au.id 
        ORDER BY t.created_at DESC";

$tasks = array();
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $tasks[] = $row;
    }
}

// Fetch task statistics
$stats_sql = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_sql));

// Fetch all faculty members
$faculty_sql = "SELECT id, username, faculty_name, department FROM users WHERE role = 'faculty'";
$faculty_members = array();
if($result = mysqli_query($conn, $faculty_sql)){
    while($row = mysqli_fetch_assoc($result)){
        $faculty_members[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Task Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .header-section {
            background: #fff;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .college-logo {
            height: 80px;
            margin-right: 15px;
        }
        .college-name {
            color: #343a40;
            margin: 0;
        }
        .college-name h1 {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            line-height: 1.2;
        }
        .college-name p {
            font-size: 16px;
            margin: 0;
        }
        .sidebar {
            min-height: calc(100vh - 120px);
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
    <!-- Header with College Logo and Name -->
    <div class="header-section">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <img src="bharati.jpg" alt="Bharati Vidyapeeth Logo" class="college-logo">
                <div class="college-name">
                    <h1>Bharati Vidyapeeth (Deemed To Be University)</h1>
                    <p>Department of Management Studies(Off Campus), Navi Mumbai</p>
                </div>
            </div>
        </div>
    </div>

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
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></h2>
                    <a href="create_task.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Task
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5>Total Tasks</h5>
                            <h2><?php echo $stats['total_tasks']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5>Pending Tasks</h5>
                            <h2><?php echo $stats['pending_tasks']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5>In Progress</h5>
                            <h2><?php echo $stats['in_progress_tasks']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5>Completed Tasks</h5>
                            <h2><?php echo $stats['completed_tasks']; ?></h2>
                        </div>
                    </div>
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
                                <select name="department" class="form-control">
                                    <option value="">All Departments</option>
                                    <option value="BCA">BCA</option>
                                    <option value="BBA">BBA</option>
                                    <option value="MCA">MCA</option>
                                    <option value="MBA">MBA</option>
                                </select>
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
                                    <div class="mt-2">
                                        <small>Assigned to: <?php echo htmlspecialchars($task['assigned_to_name']); ?></small>
                                    </div>
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