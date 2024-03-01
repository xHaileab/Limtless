<?php
session_start();
require_once 'config.php';

// Initialize variables for error messages
$loginError = '';

// Check if the user is already logged in, redirect to dashboard if true
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dashboardUser.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // SQL to check the username
    $sql = "SELECT UserID, Username, Password, Role FROM Users WHERE Username = ? AND (Role = 'admin' OR Role = 'super admin')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $user['Password'])) {
            // Password is correct, so start a new session
            session_start();
            
            // Store data in session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['Username'] = $username;
            $_SESSION['Role'] = $user['Role'];
            
            // Redirect user to admin dashboard page
            header("Location: dashboardUser.php");
            exit;
        } else {
            $loginError = 'Invalid password.';
        }
    } else {
        $loginError = 'Invalid username.';
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 350px; padding: 20px; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Login</h2>
        <p>Please fill in your credentials to login.</p>
        <form action="dashboardUser.php" method="post">
            <div>
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <input type="submit" value="Login">
            </div>
            <?php if ($loginError): ?>
                <p class="error"><?php echo $loginError; ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
