<?php
session_start();
require_once "config.php";

// Check if user is logged in and is a faculty member
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "faculty"){
    header("location: index.php");
    exit;
}

// Fetch assigned tasks for the faculty member
$sql = "SELECT t.*, ta.assigned_date, ta.completion_date 
        FROM tasks t 
        LEFT JOIN task_assignments ta ON t.id = ta.task_id 
        WHERE ta.user_id = ? 
        ORDER BY t.due_date ASC";

$tasks = array();
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $tasks[] = $row;
    }
}

// Get task statistics
$stats = array(
    'total' => count($tasks),
    'pending' => count(array_filter($tasks, function($task) { return $task['status'] == 'Pending'; })),
    'in_progress' => count(array_filter($tasks, function($task) { return $task['status'] == 'In Progress'; })),
    'completed' => count(array_filter($tasks, function($task) { return $task['status'] == 'Completed'; })),
    'overdue' => count(array_filter($tasks, function($task) { 
        return $task['status'] != 'Completed' && strtotime($task['due_date']) < time(); 
    }))
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard - Task Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .header-section {
            background: #fff;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
            min-height: calc(100vh - 100px);
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
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #28a745; }
        .status-pending { color: #ffc107; }
        .status-in-progress { color: #17a2b8; }
        .status-completed { color: #28a745; }
        .overdue { color: #dc3545; }
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
                    <a href="faculty_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="my_tasks.php"><i class="fas fa-tasks"></i> My Tasks</a>
                    <a href="completed_tasks.php"><i class="fas fa-check-circle"></i> Completed Tasks</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["faculty_name"]); ?></h2>
                    <div>
                        <span class="badge badge-info mr-2"><?php echo htmlspecialchars($_SESSION["department"]); ?></span>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5>Total Tasks</h5>
                            <h2><?php echo $stats['total']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5>Pending Tasks</h5>
                            <h2><?php echo $stats['pending']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5>In Progress</h5>
                            <h2><?php echo $stats['in_progress']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5>Completed Tasks</h5>
                            <h2><?php echo $stats['completed']; ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Task Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Priority</label>
                                    <select name="priority" class="form-control">
                                        <option value="">All</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All</option>
                                        <option value="Pending">Pending</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Due Date</label>
                                    <select name="due_date" class="form-control">
                                        <option value="">All</option>
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                        <option value="overdue">Overdue</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Filter Tasks</button>
                                </div>
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
                                    <p class="card-text"><?php echo htmlspecialchars($task['description']); ?></p>
                                    
                                    <div class="mb-3">
                                        <span class="badge badge-<?php echo $task['priority'] == 'High' ? 'danger' : ($task['priority'] == 'Medium' ? 'warning' : 'success'); ?>">
                                            <?php echo htmlspecialchars($task['priority']); ?>
                                        </span>
                                        <span class="badge badge-<?php echo $task['status'] == 'Completed' ? 'success' : ($task['status'] == 'In Progress' ? 'info' : 'warning'); ?>">
                                            <?php echo htmlspecialchars($task['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> Due: 
                                            <?php 
                                            $due_date = strtotime($task['due_date']);
                                            if($due_date < time() && $task['status'] != 'Completed') {
                                                echo '<span class="overdue">' . date('M d, Y', $due_date) . '</span>';
                                            } else {
                                                echo date('M d, Y', $due_date);
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <?php if($task['status'] != 'Completed'): ?>
                                            <form action="update_status.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <input type="hidden" name="status" value="Completed">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Mark Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
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