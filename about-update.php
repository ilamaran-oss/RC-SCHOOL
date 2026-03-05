<?php
require "db.php";

$stmt = $conn->prepare(
    "UPDATE about_cards SET title=?, content=?, display_order=? WHERE id=?"
);

$stmt->bind_param(
    "ssii",
    $_POST['title'],
    $_POST['content'],
    $_POST['display_order'],
    $_POST['id']
);

$stmt->execute();

header("Location: admin_about.php");
