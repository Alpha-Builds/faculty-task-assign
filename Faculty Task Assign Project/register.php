<?php
session_start();
require_once "config.php";

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: " . ($_SESSION["role"] == "admin" ? "admin_dashboard.php" : "faculty_dashboard.php"));
    exit;
}

// Initialize variables
$username = $email = $faculty_name = $phone_number = $department = $password = $confirm_password = $role = "";
$username_err = $email_err = $faculty_name_err = $phone_number_err = $department_err = $password_err = $confirm_password_err = $role_err = "";

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate role
    if(empty(trim($_POST["role"]))){
        $role_err = "Please select a role.";
    } else{
        $role = trim($_POST["role"]);
    }
    
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        $sql = "SELECT id FROM users WHERE username = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else{
        $sql = "SELECT id FROM users WHERE email = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate faculty name and department only for faculty role
    if($role == "faculty"){
        if(empty(trim($_POST["faculty_name"]))){
            $faculty_name_err = "Please enter faculty name.";
        } else{
            $faculty_name = trim($_POST["faculty_name"]);
        }
        
        if(empty(trim($_POST["phone_number"]))){
            $phone_number_err = "Please enter phone number.";
        } else{
            $phone_number = trim($_POST["phone_number"]);
        }
        
        if(empty(trim($_POST["department"]))){
            $department_err = "Please select department.";
        } else{
            $department = trim($_POST["department"]);
        }
    }
    
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
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err) &&
       ($role == "admin" || (empty($faculty_name_err) && empty($phone_number_err) && empty($department_err)))){
        
        // Prepare an insert statement
        if($role == "admin"){
            $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ssss", $param_username, $param_password, $param_email, $param_role);
            }
        } else {
            $sql = "INSERT INTO users (username, password, email, role, faculty_name, phone_number, department) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sssssss", $param_username, $param_password, $param_email, $param_role, $param_faculty_name, $param_phone_number, $param_department);
            }
        }
        
        if($stmt){
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_email = $email;
            $param_role = $role;
            if($role == "faculty"){
                $param_faculty_name = $faculty_name;
                $param_phone_number = $phone_number;
                $param_department = $department;
            }
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to login page
                header("location: index.php");
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
    <title>Register - Task Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
        }
        .btn-primary {
            background: #667eea;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .btn-primary:hover {
            background: #764ba2;
        }
        .role-selector {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .role-option {
            text-align: center;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-option:hover {
            border-color: #667eea;
        }
        .role-option.selected {
            border-color: #667eea;
            background: #f8f9fa;
        }
        .role-option i {
            font-size: 24px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-form">
            <h2 class="text-center mb-4">Register</h2>
            
            <div class="role-selector">
                <div class="role-option" onclick="selectRole('admin')">
                    <i class="fas fa-user-shield"></i>
                    <h5>Admin</h5>
                </div>
                <div class="role-option" onclick="selectRole('faculty')">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h5>Faculty</h5>
                </div>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="role" id="role" value="<?php echo $role; ?>">
                
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
                
                <div id="faculty-fields" style="display: none;">
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
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>
                
                <div class="form-group text-center">
                    <input type="submit" class="btn btn-primary" value="Register">
                </div>
                
                <p class="text-center">Already have an account? <a href="index.php">Login here</a></p>
            </form>
        </div>
    </div>

    <script>
        function selectRole(role) {
            document.getElementById('role').value = role;
            document.getElementById('faculty-fields').style.display = role === 'faculty' ? 'block' : 'none';
            
            // Update visual selection
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }
    </script>
</body>
</html> 