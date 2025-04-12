<?php
session_start();
require_once "config.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Initialize variables
$username = $email = $faculty_name = $phone_number = $department = "";
$username_err = $email_err = $faculty_name_err = $phone_number_err = $department_err = "";
$password_err = $confirm_password_err = "";
$success_msg = "";

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($user = mysqli_fetch_assoc($result)){
        $username = $user["username"];
        $email = $user["email"];
        $faculty_name = $user["faculty_name"];
        $phone_number = $user["phone_number"];
        $department = $user["department"];
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Check if updating profile or password
    if(isset($_POST["update_profile"])){
        // Validate username
        if(empty(trim($_POST["username"]))){
            $username_err = "Please enter a username.";
        } else{
            $username = trim($_POST["username"]);
            // Check if username exists for other users
            $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
            if($stmt = mysqli_prepare($conn, $check_sql)){
                mysqli_stmt_bind_param($stmt, "si", $username, $_SESSION["id"]);
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
                mysqli_stmt_bind_param($stmt, "si", $email, $_SESSION["id"]);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) > 0){
                    $email_err = "This email is already registered.";
                }
            }
        }
        
        // Validate faculty name (for faculty members)
        if($_SESSION["role"] == "faculty"){
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
        }
        
        // Check input errors before updating database
        if(empty($username_err) && empty($email_err) && empty($faculty_name_err) && 
           empty($phone_number_err) && empty($department_err)){
            
            // Prepare an update statement
            if($_SESSION["role"] == "admin"){
                $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $_SESSION["id"]);
            } else {
                $sql = "UPDATE users SET username = ?, email = ?, faculty_name = ?, phone_number = ?, department = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssi", $username, $email, $faculty_name, $phone_number, $department, $_SESSION["id"]);
            }
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Profile updated successfully.";
            } else{
                echo "Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    } elseif(isset($_POST["update_password"])){
        // Validate password
        if(empty(trim($_POST["password"]))){
            $password_err = "Please enter a password.";
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password must have at least 6 characters.";
        } else{
            $password = trim($_POST["password"]);
        }
        
        // Validate confirm password
        if(empty(trim($_POST["confirm_password"]))){
            $confirm_password_err = "Please confirm password.";
        } else{
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($password_err) && ($password != $confirm_password)){
                $confirm_password_err = "Password did not match.";
            }
        }
        
        // Check input errors before updating database
        if(empty($password_err) && empty($confirm_password_err)){
            // Prepare an update statement
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION["id"]);
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Password updated successfully.";
                } else{
                    echo "Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - Task Management System</title>
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
                    <?php if($_SESSION["role"] == "admin"): ?>
                        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="create_task.php"><i class="fas fa-plus"></i> Create Task</a>
                        <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    <?php else: ?>
                        <a href="faculty_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="my_tasks.php"><i class="fas fa-tasks"></i> My Tasks</a>
                        <a href="completed_tasks.php"><i class="fas fa-check-circle"></i> Completed Tasks</a>
                    <?php endif; ?>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <?php if(!empty($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_msg; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Profile Update Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="text-center">Update Profile</h3>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
                                    
                                    <?php if($_SESSION["role"] == "faculty"): ?>
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
                                    <?php endif; ?>
                                    
                                    <div class="form-group text-center">
                                        <input type="submit" name="update_profile" class="btn btn-primary" value="Update Profile">
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Password Update Form -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="text-center">Update Password</h3>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                                    </div>
                                    
                                    <div class="form-group text-center">
                                        <input type="submit" name="update_password" class="btn btn-primary" value="Update Password">
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