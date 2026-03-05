<?php
require 'db.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM dashboard_cards WHERE id = $id");
    echo json_encode($result->fetch_assoc());
}
?>