<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Delete
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM dashboard_cards WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit;
    }

    // Handle Save (Insert/Update)
    $id = isset($_POST['cardId']) && !empty($_POST['cardId']) ? intval($_POST['cardId']) : 0;
    $title = $_POST['title'];
    $icon = $_POST['icon'];
    $description = $_POST['description'];
    $section_id = $_POST['section_id'];
    $detailed_content = $_POST['detailed_content'];

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE dashboard_cards SET title=?, icon=?, description=?, section_id=?, detailed_content=? WHERE id=?");
        $stmt->bind_param("sssssi", $title, $icon, $description, $section_id, $detailed_content, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO dashboard_cards (title, icon, description, section_id, detailed_content) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $icon, $description, $section_id, $detailed_content);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
}
?>