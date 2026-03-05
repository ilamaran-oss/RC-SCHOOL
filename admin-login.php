<?php
session_start();
require "db.php";

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (!empty($username) && !empty($password)) {

        $stmt = $conn->prepare("SELECT id, username, password, role FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin["password"])) {

                session_regenerate_id(true);

                $_SESSION["admin_logged_in"] = true;
                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_name"] = $admin["username"];
                $_SESSION["admin_role"] = $admin["role"];

                header("Location: admin.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }

        $stmt->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style1.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        button {
            width: 100%;
            padding: 15px;
            background: #1f4e79;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(31, 78, 121, 0.5);
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h2>Admin Login</h2>
        <?php if (isset($error)) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
        <form method="post">
            <div class="input-box">
                <span class="icon">
                    <ion-icon name="mail"></ion-icon>
                </span>
                <input type="text" name="username" required placeholder=" ">
                <label>Username</label>
            </div>

            <div class="input-box">
                <span class="icon toggle-password" id="toggle-password">
                    <ion-icon name="lock-closed"></ion-icon>
                </span>
                <input type="password" id="password" name="password" required placeholder=" ">
                <label>Password</label>
            </div>

            <button type="submit">Login</button>
        </form>
    </div>
    <script>
        const passwordInput = document.getElementById("password");
        const toggleIcon = document.getElementById("toggle-password");
        const iconElement = toggleIcon.querySelector("ion-icon");

        toggleIcon.addEventListener("click", () => {
            if (passwordInput.type === "password") {
                passwordInput.type = "text"; // show password
                iconElement.setAttribute("name", "lock-open"); // change ion-icon
            } else {
                passwordInput.type = "password"; // hide password
                iconElement.setAttribute("name", "lock-closed"); // back to locked
            }
        });
    </script>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>

</html>