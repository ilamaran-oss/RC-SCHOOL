<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login | RC School</title>
  <link rel="stylesheet" href="style1.css">

</head>

<body>

  <div class="wrapper">
    <div class="img">
      <div class="login-box">
        <form action="login_process.php" method="post">
          <h2>Login</h2>

          <div class="input-box">
            <span class="icon">
              <ion-icon name="person"></ion-icon>
            </span>
            <input type="text" name="username"
              required
              placeholder=" "
              pattern="^(RCS|RCT|RCP)[0-9]{7,}$"
              title="Enter RCS[ID] for students, RCT[ID] for teachers, or RCP[ID] for principals">
            <label>Username</label>
          </div>

          <div class="input-box">
            <!-- Left icon -->
            <span class="icon toggle-password" id="toggle-password">
              <ion-icon name="lock-closed"></ion-icon>
            </span>

            <!-- Password input -->
            <input type="password" id="password" name="password" required placeholder=" ">

            <!-- Label -->
            <label>Password</label>
          </div>

          <button type="submit">Login</button>

          <div class="register-link">
            <p>See Yourself in here !</p>
          </div>
        </form>
      </div>

    </div>
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