<?php
require "db.php";
$id = (int)$_GET['id'];
$conn->query("DELETE FROM events WHERE id=$id");
header("Location:events.php");
