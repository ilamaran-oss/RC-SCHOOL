<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? '');
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $role = trim($_POST["role"] ?? '');

    if (!empty($name) && !empty($username) && !empty($password) && !empty($role)) {
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
        } else {
            // Check if username exists in user table
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                exit;
            } else {
                // Handle Profile Picture Upload
                $profile_pic = null;
                $upload_error = "";

                if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {

                    $uploadDir = __DIR__ . "/uploads/";

                    // Create folder if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
                    $fileName = basename($_FILES['profile_pic']['name']);
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {

                        $newFileName = time() . "_" . $fileName;
                        $destination = $uploadDir . $newFileName;

                        if (move_uploaded_file($fileTmpPath, $destination)) {
                            $profile_pic = $newFileName;
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
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, profile_pic) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $name, $username, $hash, $role, $profile_pic);
                    if ($stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'User is created']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error creating account.']);
                    }
                }
            }
            $stmt->close();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    }
}
