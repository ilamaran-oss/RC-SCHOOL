<?php
require "db.php";

$t = $_POST['title'];
$c = $_POST['content'];
$o = $_POST['display_order'];

$stmt = $conn->prepare(
    "INSERT INTO about_cards(title,content,display_order) VALUES (?,?,?)"
);
$stmt->bind_param("ssi", $t, $c, $o);
$stmt->execute();

header("Location: admin_about.php");
