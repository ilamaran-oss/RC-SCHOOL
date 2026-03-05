<?php
require "db.php";
$id = (int)$_GET['id'];
$conn->query("DELETE FROM about_cards WHERE id=$id");
header("Location: admin_about.php");
