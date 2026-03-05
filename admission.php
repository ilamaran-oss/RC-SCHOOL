<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admissions | RC Middle School</title>

    <!-- Material Symbols (use CDN OR your local font setup) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <!-- Main CSS -->
    <link rel="stylesheet" href="style.css">
</head>

<body class="admission-page">

    <!-- HEADER (same as dashboard) -->
    <header>
        <div class="logo">
            <img src="pngwing.com (1).png" class="logo-img">
            <span>RC MIDDLE SCHOOL</span>
        </div>

        <nav>
            <ul>
                <li><a href="home.php" class="nav-link"><span
                            class="material-symbols-outlined nav-icon">school</span>Home</a></li>
                <li><a href="about.php" class="nav-link"><span
                            class="material-symbols-outlined nav-icon">language</span>About</a></li>
                <li><a href="admission.php" class="nav-link active"><span
                            class="material-symbols-outlined nav-icon">assignment</span>Admissions</a></li>
                <li><a href="event.php" class="nav-link"><span
                            class="material-symbols-outlined nav-icon">event_available</span>Events</a></li>
                <li><a href="contact.php" class="nav-link"><span
                            class="material-symbols-outlined nav-icon">call</span>Contact</a></li>
                <li><a href="login.php" class="nav-link"><span
                            class="material-symbols-outlined nav-icon">login</span>Login</a></li>
            </ul>
        </nav>
    </header>

    <!-- PAGE BANNER -->
    <section class="page-banner">
        <h1 class="uh">Admission</h1>
    </section>

    <!-- ADMISSION CONTENT -->
    <section class="admission-container">

        <!-- INFO -->
        <div class="admission-info">
            <h2>Admission Process</h2>
            <ul>
                <li>✔ Fill the admission form</li>
                <li>✔ Submit required documents</li>
                <li>✔ Attend entrance interaction</li>
                <li>✔ Confirmation from school</li>
            </ul>
        </div>

        <!-- FORM -->
        <div class="admission-form">
            <h2>Admission Form</h2>

            <form action="admission_process.php" method="post">
                <div class="form-group">
                    <span class="material-symbols-outlined">person</span>
                    <input type="text" placeholder="Student Name" name="student_name" required>
                </div>

                <div class="form-group">
                    <input type="number" placeholder="Age" name="student_age" required>
                </div>

                <div class=" form-group">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <input type="date" name="dob" required>
                </div>


                <div class="form-group">
                    <span class="material-symbols-outlined">person</span>
                    <input type="text" placeholder="Father Name" name="father" required>
                </div>

                <div class="form-group">
                    <input type="number" placeholder="Age" name="father_age" required>
                </div>

                <div class="form-group">
                    <input type="text" placeholder="Occupation" name="Father_Occupation" required>
                </div>

                <div class=" form-group">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <input type="date" name="father_dob" required>
                </div>

                <div class=" form-group">
                    <span class="material-symbols-outlined">person</span>
                    <input type="text" placeholder="Mother Name" name="mother" required>
                </div>

                <div class="form-group">
                    <input type="number" placeholder="Age" name="mother_age" required>
                </div>

                <div class="form-group">
                    <input type="text" placeholder="Occupation" name="Mother_Occupation" required>
                </div>

                <div class=" form-group">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <input type="date" name="mother_dob" required>
                </div>

                <div class="form-group custom-select">
                    <span class="material-symbols-outlined ">school</span>

                    <div class="select-selected">Select Class</div><span
                        class="material-symbols-outlined select-arrow">expand_more</span>

                    <!-- real value sent with form -->
                    <input type="hidden" name="student_class">
                    <div class="select-items">
                        <div data-value="Grade 1">Grade 1</div>
                        <div data-value="Grade 2">Grade 2</div>
                        <div data-value="Grade 3">Grade 3</div>
                        <div data-value="Grade 4">Grade 4</div>
                        <div data-value="Grade 5">Grade 5</div>
                        <div data-value="Grade 6">Grade 6</div>
                        <div data-value="Grade 7">Grade 7</div>
                        <div data-value="Grade 8">Grade 8</div>
                        <div data-value="Grade 9">Grade 9</div>
                        <div data-value="Grade 10">Grade 10</div>
                        <div data-value="Grade 11">Grade 11</div>
                        <div data-value="Grade 12">Grade 12</div>
                    </div>

                </div>



                <div class="form-group">
                    <span class="material-symbols-outlined">call</span>
                    <input type="tel" placeholder="Parent Contact Number" name="phone" required>
                </div>

                <div class="form-group">
                    <span class="material-symbols-outlined">mail</span>
                    <input type="email" placeholder="Parent Email" name="email" required>
                </div>

                <div class="form-group">
                    <span class="material-symbols-outlined">home</span>
                    <textarea placeholder="Address" name="address" required></textarea>
                </div>

                <button type="submit">Submit Application</button>
            </form>
        </div>

    </section>
    <script>
        document.querySelectorAll(".custom-select").forEach(select => {
            const selected = select.querySelector(".select-selected");
            const items = select.querySelector(".select-items");
            const hiddenInput = select.querySelector("input[type='hidden']");

            // toggle dropdown
            selected.addEventListener("click", e => {
                e.stopPropagation();

                document.querySelectorAll(".custom-select").forEach(s => {
                    if (s !== select) s.classList.remove("active");
                });

                select.classList.toggle("active");
            });

            // option click
            items.querySelectorAll("div").forEach(option => {
                option.addEventListener("click", () => {
                    selected.textContent = option.textContent;

                    items.querySelectorAll("div").forEach(o =>
                        o.classList.remove("selected")
                    );
                    option.classList.add("selected");

                    // ✅ set value for form submit
                    hiddenInput.value = option.dataset.value;

                    select.classList.remove("active");
                });
            });
        });

        // close on outside click
        document.addEventListener("click", () => {
            document.querySelectorAll(".custom-select")
                .forEach(s => s.classList.remove("active"));
        });
    </script>


</body>

</html>