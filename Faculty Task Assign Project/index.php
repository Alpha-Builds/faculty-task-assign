<?php
session_start();
require_once "config.php";

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: " . ($_SESSION["role"] == "admin" ? "admin_dashboard.php" : "faculty_dashboard.php"));
    exit;
}

// Initialize variables
$login = $password = "";
$login_err = $password_err = "";

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate login (username or email)
    if(empty(trim($_POST["login"]))){
        $login_err = "Please enter username or email.";
    } else{
        $login = trim($_POST["login"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($login_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, role, faculty_name, department FROM users WHERE username = ? OR email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ss", $login, $login);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $faculty_name, $department);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            $_SESSION["faculty_name"] = $faculty_name;
                            $_SESSION["department"] = $department;
                            
                            // Redirect user based on role
                            header("location: " . ($role == "admin" ? "admin_dashboard.php" : "faculty_dashboard.php"));
                            exit;
                        } else{
                            $password_err = "Invalid password.";
                        }
                    }
                } else{
                    $login_err = "No account found with that username or email.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
    <title>Login - Task Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .header-section {
            background: #fff;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 100%;
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
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px);
            padding: 20px;
        }
        .login-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .btn-primary {
            background: #667eea;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
        }
        .btn-primary:hover {
            background: #764ba2;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .logo h2 {
            color: #343a40;
            font-weight: 600;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-right: none;
            color: #667eea;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #ddd;
        }
        .invalid-feedback {
            font-size: 14px;
            color: #dc3545;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            color: #764ba2;
        }
        .dms-logo {
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
            border-radius: 50%;
            padding: 10px;
            background: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .login-form {
            text-align: center;
        }
        .login-form h2 {
            font-size: 24px;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 10px;
        }
        .login-form h2:after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: #667eea;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }
        .form-group {
            text-align: left;
        }
    </style>
</head>
<body>
    <!-- Header with College Logo and Name -->
    <div class="header-section">
        <div class="container">
            <div class="d-flex align-items-center">
                <img src="bharati.jpg" alt="Bharati Vidyapeeth Logo" class="college-logo">
                <div class="college-name">
                    <h1>Bharati Vidyapeeth (Deemed To Be University)</h1>
                    <p>Department of Management Studies(Off Campus), Navi Mumbai</p>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="login-form">
            <img src="DMS.jpg" alt="DMS Logo" class="dms-logo">
            <h2 class="mb-4">Task Management System</h2>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                        </div>
                        <input type="text" name="login" class="form-control <?php echo (!empty($login_err)) ? 'is-invalid' : ''; ?>" placeholder="Username or Email" value="<?php echo $login; ?>">
                    </div>
                    <?php if(!empty($login_err)): ?>
                        <div class="invalid-feedback d-block"><?php echo $login_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Password">
                    </div>
                    <?php if(!empty($password_err)): ?>
                        <div class="invalid-feedback d-block"><?php echo $password_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </button>
                </div>
                
                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 