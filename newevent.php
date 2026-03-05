<?php
require "db.php";

$title = $_POST['title'];
$date  = $_POST['event_date'];
$time  = $_POST['event_time'];
$loc   = $_POST['location'];
$type  = $_POST['type'];
$desc  = $_POST['description'];
$status = $_POST['status'];

$img = "";
if (!empty($_FILES['event_image']['name'])) {
    $img = time() . $_FILES['event_image']['name'];
    move_uploaded_file(
        $_FILES['event_image']['tmp_name'],
        "uploads/" . $img
    );
}

$stmt = $conn->prepare(
    "INSERT INTO events(title,event_date,event_time,location,type,description,image,status)
VALUES (?,?,?,?,?,?,?,?)"
);

$stmt->bind_param(
    "ssssssss",
    $title,
    $date,
    $time,
    $loc,
    $type,
    $desc,
    $img,
    $status
);

$stmt->execute();

header("Location:events.php");
