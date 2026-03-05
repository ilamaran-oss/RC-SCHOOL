<?php
require "db.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $display_order = (int)($_POST['display_order'] ?? 0);

    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Title and content are required']);
        exit;
    }

    if ($id) {
        // Update
        $stmt = $conn->prepare("UPDATE about_cards SET title = ?, content = ?, display_order = ? WHERE id = ?");
        $stmt->bind_param("ssii", $title, $content, $display_order, $id);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO about_cards (title, content, display_order) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $content, $display_order);
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