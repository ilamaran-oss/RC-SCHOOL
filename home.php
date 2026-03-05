<?php require "db.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <meta charset="UTF-8">
    <title>My School Website</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="home">

    <!-- HEADER -->
    <header class="main-header">
        <div class="logo">
            <div class="logo-circle">
                <img src="pngwing.com (1).png" class="logo-img">
            </div>
            <span class="logo-text">RC MIDDLE SCHOOL</span>
        </div>

        <nav>
            <ul>
                <ul>
                    <li><a href="home.php" class="nav-link active"><span class="material-symbols-outlined nav-icon">school</span>Home</a></li>
                    <li><a href="about.php" class="nav-link"><span class="material-symbols-outlined nav-icon">language</span>About</a></li>
                    <li><a href="admission.php" class="nav-link"><span class="material-symbols-outlined nav-icon">assignment</span>Admissions</a></li>
                    <li><a href="event.php" class="nav-link"><span class="material-symbols-outlined nav-icon">event_available</span>Events</a></li>
                    <li><a href="contact.php" class="nav-link"><span class="material-symbols-outlined nav-icon">call</span>Contact</a></li>
                    <li><a href="login.php" class="nav-link"><span class="material-symbols-outlined nav-icon">login</span>Login</a></li>
                </ul>

            </ul>
        </nav>
    </header>

    <!-- SLIDER -->
        <section class="slider" style='background-image: url("photos/enhanced/homeschool.jpg")'>
        <div class="slider-text">
            <h1 class="uh">RC MIDDLE SCHOOL</h1>
        </div>
    </section>


    <!-- ABOUT SECTION -->
    <section class="about">

        <!-- LEFT SIDE TEXT -->
        <div class="about-text">
            <h2>Our School</h2>
            <p>
                RC Middle School provides quality education with strong values and discipline.
            </p>

        </div>


        <!-- RIGHT SIDE PHOTO GALLERY -->
        <div class="photo-gallery">

            <div class="photo-card" onclick="toggleInfo(this)">
                <img src="photos/10009565.JPG">
                <div class="photo-info">
                    <div class="notch"></div>
                    <h3>Outdoor Games</h3>
                    <p>Students participate in outdoor games for fitness and teamwork.</p>
                </div>
            </div>

            <div class="photo-card" onclick="toggleInfo(this)">
                <img src="photos/10009500.JPG">
                <div class="photo-info">
                    <div class="notch"></div>
                    <h3>Smart Classroom</h3>
                    <p>Interactive classroom teaching with digital learning tools.</p>
                </div>
            </div>

            <div class="photo-card" onclick="toggleInfo(this)">
                <img src="photos/10009547.JPG">
                <div class="photo-info">
                    <div class="notch"></div>
                    <h3>School Campus</h3>
                    <p>Clean and disciplined campus with peaceful environment.</p>
                </div>
            </div>

        </div>

    </section>


    <!-- NEWS -->
    <section class="news">
        <h2>Latest News</h2>
        <div class="cards">
            <div class="card">Sports Day Celebration</div>
            <div class="card">Annual Day Program</div>
            <div class="card">Science Exhibition</div>
        </div>
    </section>


    <!-- EVENTS -->
    <section class="events">
        <h2>Upcoming Events</h2>
        <ul>
            <li>📅 Jan 31 – Annual Day</li>
            <li>📅 Feb 10 – Parent Meeting</li>
        </ul>
    </section>


    <footer>
        <p>© 2026 RC Middle School. All Rights Reserved.</p>
    </footer>

    <script>
        document.querySelectorAll(".photo-card img").forEach(img => {
            img.addEventListener("click", function() {
                const info = this.parentElement.querySelector(".photo-info");
                info.classList.toggle("show");
            });
        });
    </script>

</body>

</html>