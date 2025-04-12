<?php
session_start();
require_once "config.php";

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Check if user ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: manage_users.php");
    exit;
}

$user_id = $_GET["id"];

// Initialize variables
$username = $email = $faculty_name = $phone_number = $department = "";
$username_err = $email_err = $faculty_name_err = $phone_number_err = $department_err = "";

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ? AND role = 'faculty'";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($user = mysqli_fetch_assoc($result)){
        $username = $user["username"];
        $email = $user["email"];
        $faculty_name = $user["faculty_name"];
        $phone_number = $user["phone_number"];
        $department = $user["department"];
    } else {
        header("location: manage_users.php");
        exit;
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        $username = trim($_POST["username"]);
        // Check if username exists for other users
        $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $check_sql)){
            mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) > 0){
                $username_err = "This username is already taken.";
            }
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else{
        $email = trim($_POST["email"]);
        // Check if email exists for other users
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $check_sql)){
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) > 0){
                $email_err = "This email is already registered.";
            }
        }
    }
    
    // Validate faculty name
    if(empty(trim($_POST["faculty_name"]))){
        $faculty_name_err = "Please enter faculty name.";
    } else{
        $faculty_name = trim($_POST["faculty_name"]);
    }
    
    // Validate phone number
    if(empty(trim($_POST["phone_number"]))){
        $phone_number_err = "Please enter phone number.";
    } else{
        $phone_number = trim($_POST["phone_number"]);
    }
    
    // Validate department
    if(empty(trim($_POST["department"]))){
        $department_err = "Please select department.";
    } else{
        $department = trim($_POST["department"]);
    }
    
    // Check input errors before updating database
    if(empty($username_err) && empty($email_err) && empty($faculty_name_err) && 
       empty($phone_number_err) && empty($department_err)){
        
        // Prepare an update statement
        $sql = "UPDATE users SET username = ?, email = ?, faculty_name = ?, phone_number = ?, department = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssssi", $username, $email, $faculty_name, $phone_number, $department, $user_id);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to manage users page
                header("location: manage_users.php");
                exit();
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
    <title>Edit Faculty Member - Task Management System</title>
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
                                <h3 class="text-center">Edit Faculty Member</h3>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $user_id; ?>" method="post">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Faculty Name</label>
                                        <input type="text" name="faculty_name" class="form-control <?php echo (!empty($faculty_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $faculty_name; ?>">
                                        <span class="invalid-feedback"><?php echo $faculty_name_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone_number" class="form-control <?php echo (!empty($phone_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone_number; ?>">
                                        <span class="invalid-feedback"><?php echo $phone_number_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Department</label>
                                        <select name="department" class="form-control <?php echo (!empty($department_err)) ? 'is-invalid' : ''; ?>">
                                            <option value="">Select Department</option>
                                            <option value="BCA" <?php echo $department == "BCA" ? "selected" : ""; ?>>BCA</option>
                                            <option value="BBA" <?php echo $department == "BBA" ? "selected" : ""; ?>>BBA</option>
                                            <option value="MCA" <?php echo $department == "MCA" ? "selected" : ""; ?>>MCA</option>
                                            <option value="MBA" <?php echo $department == "MBA" ? "selected" : ""; ?>>MBA</option>
                                        </select>
                                        <span class="invalid-feedback"><?php echo $department_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group text-center">
                                        <input type="submit" class="btn btn-primary" value="Update Faculty Member">
                                        <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
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