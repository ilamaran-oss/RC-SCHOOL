<?php
require "db.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "ID required";
    exit;
}

$stmt = $conn->prepare("DELETE FROM about_cards WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Deleted successfully";
} else {
    echo "Error deleting";
}
?>