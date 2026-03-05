<?php
require "db.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = trim($_POST['location']);
    $type = $_POST['type'];
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'active';
    $image = $_POST['image'] ?? '';

    if (empty($title) || empty($event_date) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Title, date, and description are required']);
        exit;
    }

    if ($id) {
        // Update
        $stmt = $conn->prepare("UPDATE events SET title = ?, event_date = ?, event_time = ?, location = ?, type = ?, description = ?, image = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $title, $event_date, $event_time, $location, $type, $description, $image, $status, $id);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO events (title, event_date, event_time, location, type, description, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $title, $event_date, $event_time, $location, $type, $description, $image, $status);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>