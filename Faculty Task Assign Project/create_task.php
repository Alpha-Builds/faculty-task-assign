<?php
session_start();
require_once "config.php";

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Initialize variables
$title = $description = $priority = $due_date = $assigned_to = "";
$title_err = $description_err = $priority_err = $due_date_err = $assigned_to_err = "";

// Fetch all faculty members for assignment
$faculty_sql = "SELECT id, username, faculty_name, department FROM users WHERE role = 'faculty'";
$faculty_members = array();
if($result = mysqli_query($conn, $faculty_sql)){
    while($row = mysqli_fetch_assoc($result)){
        $faculty_members[] = $row;
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a title.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Validate priority
    if(empty(trim($_POST["priority"]))){
        $priority_err = "Please select a priority.";
    } else{
        $priority = trim($_POST["priority"]);
    }
    
    // Validate due date
    if(empty(trim($_POST["due_date"]))){
        $due_date_err = "Please select a due date.";
    } else{
        $due_date = trim($_POST["due_date"]);
        if(strtotime($due_date) < time()){
            $due_date_err = "Due date cannot be in the past.";
        }
    }
    
    // Validate assigned to
    if(empty(trim($_POST["assigned_to"]))){
        $assigned_to_err = "Please select a faculty member.";
    } else{
        $assigned_to = trim($_POST["assigned_to"]);
    }
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($description_err) && empty($priority_err) && empty($due_date_err) && empty($assigned_to_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO tasks (title, description, priority, due_date, assigned_date, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssssi", $param_title, $param_description, $param_priority, $param_due_date, $param_assigned_date, $param_created_by);
            
            // Set parameters
            $param_title = $title;
            $param_description = $description;
            $param_priority = $priority;
            $param_due_date = $due_date;
            $param_assigned_date = date('Y-m-d'); // Set assigned date to current date
            $param_created_by = $_SESSION["id"];
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Get the task ID of the newly created task
                $task_id = mysqli_insert_id($conn);
                
                // Prepare an insert statement for task assignments
                $sql = "INSERT INTO task_assignments (task_id, user_id, assigned_date) VALUES (?, ?, ?)";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    // Bind variables to the prepared statement as parameters
                    mysqli_stmt_bind_param($stmt, "iis", $param_task_id, $param_user_id, $param_assigned_date);
                    
                    // Set parameters
                    $param_task_id = $task_id;
                    $param_user_id = $assigned_to;
                    $param_assigned_date = date('Y-m-d'); // Set assigned date to current date
                    
                    // Attempt to execute the prepared statement
                    if(mysqli_stmt_execute($stmt)){
                        // Redirect to admin dashboard
                        header("location: admin_dashboard.php");
                        exit();
                    } else{
                        echo "Something went wrong. Please try again later.";
                    }
                    mysqli_stmt_close($stmt);
                }
            } else{
                echo "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Task - Task Management System</title>
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
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="text-center">Create New Task</h3>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                                        <span class="invalid-feedback"><?php echo $title_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="4"><?php echo $description; ?></textarea>
                                        <span class="invalid-feedback"><?php echo $description_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Priority</label>
                                        <select name="priority" class="form-control <?php echo (!empty($priority_err)) ? 'is-invalid' : ''; ?>">
                                            <option value="">Select Priority</option>
                                            <option value="Low" <?php echo $priority == "Low" ? "selected" : ""; ?>>Low</option>
                                            <option value="Medium" <?php echo $priority == "Medium" ? "selected" : ""; ?>>Medium</option>
                                            <option value="High" <?php echo $priority == "High" ? "selected" : ""; ?>>High</option>
                                            <option value="Urgent" <?php echo $priority == "Urgent" ? "selected" : ""; ?>>Urgent</option>
                                        </select>
                                        <span class="invalid-feedback"><?php echo $priority_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Due Date</label>
                                        <input type="date" name="due_date" class="form-control <?php echo (!empty($due_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $due_date; ?>">
                                        <span class="invalid-feedback"><?php echo $due_date_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Assign To</label>
                                        <select name="assigned_to" class="form-control <?php echo (!empty($assigned_to_err)) ? 'is-invalid' : ''; ?>">
                                            <option value="">Select Faculty Member</option>
                                            <?php foreach($faculty_members as $faculty): ?>
                                                <option value="<?php echo $faculty['id']; ?>" <?php echo $assigned_to == $faculty['id'] ? "selected" : ""; ?>>
                                                    <?php echo htmlspecialchars($faculty['faculty_name'] . ' (' . $faculty['department'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="invalid-feedback"><?php echo $assigned_to_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group text-center">
                                        <input type="submit" class="btn btn-primary" value="Create Task">
                                        <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 