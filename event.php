<?php
require "db.php";   // your database connection file
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Events | RC School</title>

    <!-- Main CSS -->
    <link rel="stylesheet" href="style.css">

    <!-- Material Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>

<body>

    <!-- ================= HEADER ================= -->
    <header>
        <div class="logo">
            <img src="pngwing.com (1).png" class="logo-img">
            RC MIDDLE SCHOOL
        </div>

        <nav>
            <ul>
                <li><a href="home.php" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">school</span>
                        Home
                    </a></li>

                <li><a href="about.php" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">language</span>
                        About
                    </a></li>

                <li><a href="event.php" class="nav-link active">
                        <span class="material-symbols-outlined nav-icon">event_available</span>
                        Events
                    </a></li>

                <li><a href="contact.php" class="nav-link">
                        <span class="material-symbols-outlined nav-icon">call</span>
                        Contact
                    </a></li>
            </ul>
        </nav>
    </header>


    <!-- ================= PAGE BANNER ================= -->

    <div class="event-banner">
        <h1 class="uh">School Events</h1>
    </div>


    <!-- ================= EVENTS SECTION ================= -->

    <section class="events">

        <h2 class="events-title">Latest Programs & Activities</h2>

        <div class="event-page">

            <?php
            $result = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date DESC");

            if (mysqli_num_rows($result) > 0):

                while ($row = mysqli_fetch_assoc($result)):
            ?>

                    <div class="event-card">

                        <?php if (!empty($row['image'])): ?>
                            <img src="photos/<?php echo $row['image']; ?>" alt="event">
                        <?php endif; ?><br>

                        <div class="event-badge">PROGRAM</div>

                        <div class="date">
                            <?php echo date("d M Y", strtotime($row['event_date'])); ?>
                        </div>

                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>

                        <p><?php echo htmlspecialchars($row['description']); ?></p>

                        <button type="button"
                            class="event-btn view-btn"
                            data-title="<?php echo htmlspecialchars($row['title']); ?>"
                            data-date="<?php echo $row['event_date']; ?>"
                            data-time="<?php echo $row['event_time']; ?>"
                            data-location="<?php echo htmlspecialchars($row['location']); ?>"
                            data-incharge="<?php echo htmlspecialchars($row['incharge']); ?>">

                            View Details
                            <span class="material-symbols-outlined">info</span>

                        </button>

                    </div>


                <?php
                endwhile;

            else:
                ?>

                <div class="event-card">
                    <h3>No Events Available</h3>
                    <p>Please check back later for upcoming programs.</p>
                </div>

            <?php endif; ?>

        </div>
    </section>

    <!-- EVENT DETAILS MODAL -->

    <div id="eventModal" class="event-modal">
        <div class="event-modal-box">

            <span class="event-close">&times;</span>

            <h2 id="mTitle"></h2>

            <div class="event-modal-grid">
                <div><strong>In-Charge:</strong> <span id="mIncharge"></span></div>
                <div><strong>Date:</strong> <span id="mDate"></span></div>
                <div><strong>Time:</strong> <span id="mTime"></span></div>
                <div><strong>Location:</strong> <span id="mLocation"></span></div>
            </div>

        </div>
    </div>

    <!-- ================= FOOTER ================= -->

    <footer>
        © <?php echo date("Y"); ?> RC School — All Rights Reserved
    </footer>

    <script>
        console.log("JS LOADED");

        window.onload = function() {

            const buttons = document.querySelectorAll(".view-btn");
            const modal = document.getElementById("eventModal");

            console.log("Buttons found:", buttons.length);

            buttons.forEach(function(btn) {

                btn.onclick = function() {

                    console.log("BUTTON CLICKED");

                    document.getElementById("mTitle").innerText = btn.dataset.title;
                    document.getElementById("mDate").innerText = btn.dataset.date;
                    document.getElementById("mTime").innerText = btn.dataset.time;
                    document.getElementById("mLocation").innerText = btn.dataset.location;
                    document.getElementById("mIncharge").innerText = btn.dataset.incharge;

                    modal.classList.add("show");
                };

            });

            document.querySelector(".event-close").onclick = function() {
                modal.classList.remove("show");
            };

            modal.onclick = function(e) {
                if (e.target.id === "eventModal") {
                    modal.classList.remove("show");
                }
            };

        };
    </script>




</body>

</html>