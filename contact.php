<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Us</title>
<link rel="stylesheet" href="style1.css">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<style>
  .phone-icon{
  color: #4da6ff;
  vertical-align: middle;
}
</style>
</head>

<body>

<div class="wrapper">

  <div class="login-box">

    <form action="contact_save.php" method="post">

      <h2>Contact Us</h2>

      <!-- Name -->
      <div class="input-box">
        <span class="material-symbols-outlined icon">person</span>
        <input type="text" name="name" required placeholder=" ">
        <label>Your Name</label>
      </div>

      <!-- Email -->
      <div class="input-box">
        <span class="material-symbols-outlined icon">mail</span>
        <input type="email" name="email" required placeholder=" ">
        <label>Email Address</label>
      </div>

      <!-- Message -->
      <div class="input-box">
        <span class="material-symbols-outlined icon">chat</span>
        <input type="text" name="message" required placeholder=" ">
        <label>Your Message</label>
      </div>

      <button type="submit">Send Message</button>

      <div class="register-link">
        <p>📧 sjcrcm@gmail.com<br>
        <span class="material-symbols-outlined phone-icon">call</span> +91 XXXXX XXXXX</p>
      </div>

    </form>

  </div>

</div>

</body>
</html>
