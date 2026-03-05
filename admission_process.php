<?php
require "db.php";

if (empty($_POST['student_class'])) {
    die("Please select class");
}

$stmt = $conn->prepare(
    "INSERT INTO admissions(
student_name,student_age,dob,father,father_age,father_dob,Father_Occupation,
mother,mother_age,mother_dob,Mother_Occupation,
student_class,phone,email,address
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
);

$stmt->bind_param(
    "sississsissssss",
    $_POST['student_name'],     // s
    $_POST['student_age'],      // i
    $_POST['dob'],              // s
    $_POST['father'],           // s
    $_POST['father_age'],       // i
    $_POST['father_dob'],       // s
    $_POST['Father_Occupation'], // s
    $_POST['mother'],           // s
    $_POST['mother_age'],       // i
    $_POST['mother_dob'],       // s
    $_POST['Mother_Occupation'], // s
    $_POST['student_class'],    // s
    $_POST['phone'],            // s
    $_POST['email'],            // s
    $_POST['address']           // s
);

if ($stmt->execute()) {
    header("Location: home.php");
    exit;
} else {
    echo "Error: " . $stmt->error;
}
