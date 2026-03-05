<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];

$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$old || !$new || !$confirm) {
    die("All fields required");
}

if ($new !== $confirm) {
    die("New passwords do not match");
}


/* get current password */
$stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}

/* verify old password */
if (!password_verify($old, $user['password'])) {
    die("Old password incorrect");
}

/* hash new password */
$newHash = password_hash($new, PASSWORD_DEFAULT);

/* update */
$update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
$update->bind_param("si", $newHash, $user_id);
$update->execute();

session_destroy();
header("Location: login.php");
exit;
