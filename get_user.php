<?php
require "db.php";
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['error' => 'ID required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, username, role, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'User not found']);
}
?>