<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Change Password</title>

    <link rel="stylesheet" href="style1.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        .icon {
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="wrapper">

        <!-- same box style -->
        <div class="login-box change-pass-box">

            <form action="changeprocess.php" method="post">

                <h2>Change Password</h2>

                <!-- OLD PASSWORD -->
                <div class="input-box">
                    <input type="password" name="old_password" required placeholder=" ">
                    <label>Current Password</label>
                    <span class="material-symbols-outlined icon" onclick="togglePass(this)">lock</span>
                </div>

                <!-- NEW PASSWORD -->
                <div class="input-box">
                    <input type="password" name="new_password" required placeholder=" ">
                    <label>New Password</label>
                    <span class="material-symbols-outlined icon" onclick="togglePass(this)">key</span>
                </div>

                <!-- CONFIRM -->
                <div class="input-box">
                    <input type="password" name="confirm_password" required placeholder=" ">
                    <label>Confirm Password</label>
                    <span class="material-symbols-outlined icon" onclick="togglePass(this)">verified_user</span>
                </div>

                <button type="submit">Update Password</button>

                <div class="register-link">
                    <p><a href="dash.php">← Back to Dashboard</a></p>
                </div>

            </form>

        </div>
    </div>

    <script>
        function togglePass(icon) {
            let input = icon.parentElement.querySelector('input');
            if (input.type === "password") {
                input.type = "text";
                icon.setAttribute("data-icon", icon.innerText);
                icon.innerText = "lock_open";
            } else {
                input.type = "password";
                icon.innerText = icon.getAttribute("data-icon") || "lock";
            }
        }
    </script>

</body>

</html>