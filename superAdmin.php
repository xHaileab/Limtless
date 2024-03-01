<?php
require_once 'config.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Convert to PDO if needed, example uses mysqli for consistency with connection
function fetchDashboardStats($conn) {
    $stats = [];

    // Fetch total number of admins
    $result = $conn->query("SELECT COUNT(*) AS count FROM Users WHERE Role = 'admin'");
    if ($row = $result->fetch_assoc()) {
        $stats['numAdmins'] = $row['count'];
    }

    // Fetch total number of students
    $result = $conn->query("SELECT COUNT(*) AS count FROM Users WHERE Role = 'student'");
    if ($row = $result->fetch_assoc()) {
        $stats['numStudents'] = $row['count'];
    }

    return $stats;
}

function addNewAdmin($conn, $username, $password, $email, $role = 'admin') {
    $stmt = $conn->prepare("INSERT INTO Users (Username, Password, Email, Role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, password_hash($password, PASSWORD_DEFAULT), $email, $role);
    $stmt->execute();
}

function fetchAllAdmins($conn) {
    $admins = [];
    $result = $conn->query("SELECT UserID, Username, Email, Role FROM Users WHERE Role = 'admin' OR Role = 'super admin'");
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    return $admins;
}

function updateAdmin($conn, $userID, $username, $email) {
    $stmt = $conn->prepare("UPDATE Users SET Username = ?, Email = ? WHERE UserID = ?");
    $stmt->bind_param("ssi", $username, $email, $userID);
    $stmt->execute();
}

function deleteAdmin($conn, $userID) {
    $stmt = $conn->prepare("DELETE FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle form submission for adding, updating, and deleting admins
    // For simplicity, actions are identified by a hidden input named 'action'
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'addNewAdmin':
            addNewAdmin($conn, $_POST['username'], $_POST['password'], $_POST['email']);
            break;
        case 'updateAdmin':
            updateAdmin($conn, $_POST['userID'], $_POST['username'], $_POST['email']);
            break;
        case 'deleteAdmin':
            deleteAdmin($conn, $_POST['userID']);
            break;
    }

    // Redirect to prevent form resubmission
    header("Location: superAdmin.php");
    exit();
}

$admins = fetchAllAdmins($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
</head>
<body>
    <h2>Admin Management</h2>
    <form action="superAdmin.php" method="post">
        <input type="hidden" name="action" value="addNewAdmin">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit">Add Admin</button>
    </form>

    <h3>Current Admins</h3>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Actions</th>
                <th>Full Name</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?= htmlspecialchars($admin['Username']) ?></td>
                <td><?= htmlspecialchars($admin['Email']) ?></td>
                <td>
                    <form action="superAdmin.php" method="post" style="display: inline;">
                        <input type="hidden" name="action" value="deleteAdmin">
                        <input type="hidden" name="userID" value="<?= $admin['UserID'] ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
