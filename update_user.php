<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = trim($_POST["userId"] ?? '');
    $name = trim($_POST["name"] ?? '');
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $role = trim($_POST["role"] ?? '');

    if (empty($userId) || empty($name) || empty($username) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Username pattern validation based on user type
    $pattern_error = "";
    if ($role === "student") {
        if (!preg_match('/^RCS[0-9]{7,}$/', $username)) {
            $pattern_error = "Student username must be: RCS[7+ digit ID].";
        }
    } elseif ($role === "teacher") {
        if (!preg_match('/^RCT[0-9]{7,}$/', $username)) {
            $pattern_error = "Teacher username must be: RCT[7+ digit ID].";
        }
    } elseif ($role === "principle") {
        if (!preg_match('/^RCP[0-9]{7,}$/', $username)) {
            $pattern_error = "Principle username must be: RCP[7+ digit ID].";
        }
    }

    if (!empty($pattern_error)) {
        echo json_encode(['success' => false, 'message' => $pattern_error]);
        exit;
    }

    // Check if username is being changed and if the new one already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists.']);
        exit;
    }
    $stmt->close();

    // Base query
    $sql_parts = ["name = ?", "username = ?", "role = ?"];
    $params = [$name, $username, $role];
    $types = "sss";

    // Handle optional password update
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql_parts[] = "password = ?";
        $params[] = $hash;
        $types .= "s";
    }

    // Handle optional profile picture update
    $upload_error = "";
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
        $fileName = basename($_FILES['profile_pic']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Optionally, delete old picture
            $stmt_old_pic = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
            $stmt_old_pic->bind_param("i", $userId);
            $stmt_old_pic->execute();
            $old_pic_res = $stmt_old_pic->get_result();
            if($old_pic_row = $old_pic_res->fetch_assoc()){
                if(!empty($old_pic_row['profile_pic']) && file_exists($uploadDir . $old_pic_row['profile_pic'])){
                    unlink($uploadDir . $old_pic_row['profile_pic']);
                }
            }
            $stmt_old_pic->close();

            $newFileName = time() . "_" . $fileName;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $sql_parts[] = "profile_pic = ?";
                $params[] = $newFileName;
                $types .= "s";
            } else {
                $upload_error = "Failed to upload profile picture.";
            }
        } else {
            $upload_error = "Only JPG, JPEG, PNG & GIF files allowed.";
        }
    }

    if (!empty($upload_error)) {
        echo json_encode(['success' => false, 'message' => $upload_error]);
        exit;
    }

    // Finalize query
    $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>