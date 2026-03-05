<?php
session_start();
require "db.php";   // your database connection file

// Get form values
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Basic validation
if (!$username || !$password) {
    die("Please fill all fields");
}

// Validate username pattern
$role = null;
if (preg_match('/^RCS[0-9]{7,}$/', $username)) {
    $role = 'student';
} elseif (preg_match('/^RCT[0-9]{7,}$/', $username)) {
    $role = 'teacher';
} else {
    die("Invalid username format. Please use RCS[ID] for students or RCT[ID] for teachers.");
}

// Prepare query for user table
$sql = "SELECT * FROM users WHERE username = ? AND role = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $role);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {

    // If you stored hashed password (recommended)
    if (password_verify($password, $user['password'])) {

        // ✅ Save session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_pic'] = $user['profile_pic'];

        // Redirect based on user type
        if ($user['role'] === 'student') {
            header("Location: dash.php"); // Student dashboard
        } elseif ($user['role'] === 'teacher') {
            header("Location: dash.php"); // Teacher dashboard (can be different if needed)
        } else {
            header("Location: dash.php"); // Default dashboard
        }
        exit;
    } else {
        echo "Wrong password";
    }
} else {
    echo "User not found";
}
