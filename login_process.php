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
} elseif (preg_match('/^RCP[0-9]{7,}$/', $username)) {
    $role = 'principle';
} else {
    die("Invalid username format. Use RCS[ID] for students, RCT[ID] for teachers, or RCP[ID] for principals.");
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
            header("Location: tdash.php"); // Teacher dashboard (can be different if needed)
        } elseif ($user['role'] === 'principle') {
            header("Location: pdash.php"); // Principle dashboard
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
