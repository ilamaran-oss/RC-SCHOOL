<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>About Us | RC Middle School</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

</head>

<body class="sheader">

    <div class="about-hero">

        <header class="main-header">
            <div class="logo">
                <div class="logo-circle">
                    <img src="pngwing.com (1).png" alt="" class="logo-img">
                </div>
                <div class="font">
                    <span class="logo-text">RC MIDDLE SCHOOL</span>
                </div>
            </div>

            <nav>
                <ul>
                    <ul>
                        <li><a href="home.php" class="nav-link"><span class="material-symbols-outlined nav-icon">school</span>Home</a></li>
                        <li><a href="about.php" class="nav-link active"><span class="material-symbols-outlined nav-icon">language</span>About</a></li>
                        <li><a href="admission.php" class="nav-link"><span class="material-symbols-outlined nav-icon">assignment</span>Admissions</a></li>
                        <li><a href="event.php" class="nav-link"><span class="material-symbols-outlined nav-icon">event_available</span>Events</a></li>
                        <li><a href="contact.php" class="nav-link"><span class="material-symbols-outlined nav-icon">call</span>Contact</a></li>
                        <li><a href="login.php" class="nav-link"><span class="material-symbols-outlined nav-icon">login</span>Login</a></li>
                    </ul>

                </ul>
            </nav>
        </header>

        <section class="about-banner">
            <h1 class="uh">About Our School</h1>
        </section>

    </div>

    <!-- ABOUT CONTENT -->
    <!-- ABOUT CONTENT -->
    <section class="about-page">
        <?php require "db.php"; ?>

        <?php
        $r = $conn->query("SELECT * FROM about_cards ORDER BY display_order ASC");

        while ($a = $r->fetch_assoc()) {
            echo "
    <div class='about-box'>
        <h2>{$a['title']}</h2>
        <p>{$a['content']}</p>
    </div>";
        }
        ?>

    </section>

    </section>
    </section>

    <!-- FOOTER -->
    <footer>
        <p>© 2026 RC Middle School. All Rights Reserved.</p>
    </footer>

</body>

</html>