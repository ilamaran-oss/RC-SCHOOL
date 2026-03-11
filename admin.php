<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin-login.php");
    exit;
}

require "db.php";

// Get stats
$about_count = $conn->query("SELECT COUNT(*) as c FROM about_cards")->fetch_assoc()['c'];
$events_count = $conn->query("SELECT COUNT(*) as c FROM events")->fetch_assoc()['c'];
$admissions_count = $conn->query("SELECT COUNT(*) as c FROM admissions")->fetch_assoc()['c'];
$students_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
$teachers_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='teacher'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="stylesheet" href="admin-style.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="menu-item active" data-page="dashboard">
                        <span class="material-symbols-outlined">dashboard</span>
                        Dashboard
                    </a></li>
                <li><a href="#" class="menu-item" data-page="students">
                        <span class="material-symbols-outlined">groups</span>
                        Students
                    </a></li>
                <li><a href="#" class="menu-item" data-page="teachers">
                        <span class="material-symbols-outlined">person_4</span>
                        Teachers
                    </a></li>
                <li><a href="#" class="menu-item" data-page="about">
                        <span class="material-symbols-outlined">info</span>
                        About
                    </a></li>
                <li><a href="#" class="menu-item" data-page="events">
                        <span class="material-symbols-outlined">event</span>
                        Events
                    </a></li>
                <li><a href="#" class="menu-item" data-page="admissions">
                        <span class="material-symbols-outlined">school</span>
                        Admissions
                    </a></li>
                <li><a href="#" class="menu-item" data-page="uploads">
                        <span class="material-symbols-outlined">upload</span>
                        Uploads
                    </a></li>
                <li><a href="#" class="menu-item" data-page="createUser">
                        <span class="material-symbols-outlined">person_add</span>
                        Create User
                    </a></li>
                <li><a href="logout.php" class="menu-item">
                        <span class="material-symbols-outlined">logout</span>
                        Logout
                    </a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="navbar-left">
                    <button class="sidebar-toggle-mobile" id="sidebarToggleMobile">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <h1 id="pageTitle">Dashboard</h1>
                </div>
                <div class="navbar-right">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-container" id="pageContainer">
                <!-- Dashboard Page -->
                <div class="page" id="dashboardPage">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">info</span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $about_count; ?></h3>
                                <p>About Cards</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">event</span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $events_count; ?></h3>
                                <p>Events</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">school</span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $admissions_count; ?></h3>
                                <p>Admissions</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">upload</span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $students_count; ?></h3>
                                <p>Students</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <span class="material-symbols-outlined">person_4</span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $teachers_count; ?></h3>
                                <p>Teachers</p>
                            </div>
                        </div>
                    </div>

                    <div class="recent-activity">
                        <h2>Recent Activity</h2>
                        <div class="activity-list">
                            <div class="activity-item">
                                <span class="activity-icon">📝</span>
                                <div class="activity-content">
                                    <p>New about card added</p>
                                    <small>2 hours ago</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon">🎉</span>
                                <div class="activity-content">
                                    <p>Event updated</p>
                                    <small>1 day ago</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Page -->
                <div class="page hidden" id="studentsPage">
                    <div class="page-header">
                        <h2>Manage Students</h2>
                    </div>
                    <div class="admissions-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stu_res = $conn->query("SELECT * FROM users WHERE role='student'");
                                if ($stu_res && $stu_res->num_rows > 0) {
                                    while ($stu = $stu_res->fetch_assoc()) {
                                        echo "<tr>
                                            <td>" . htmlspecialchars($stu['name']) . "</td>
                                            <td>" . htmlspecialchars($stu['username']) . "</td>
                                            <td>" . htmlspecialchars($stu['role']) . "</td>
                                            <td>
                                                <button class='btn-secondary manage-student-btn' data-username='" . $stu['username'] . "'>Manage Data</button>
                                                <button class='btn-secondary edit-user-btn' data-id='" . $stu['id'] . "'>Edit</button>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4'>No students found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Teachers Page -->
                <div class="page hidden" id="teachersPage">
                    <div class="page-header">
                        <h2>Manage Teachers & Principles</h2>
                    </div>
                    <div class="admissions-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $teach_res = $conn->query("SELECT * FROM users WHERE role IN ('teacher', 'principle') ORDER BY role DESC, name ASC");
                                if ($teach_res && $teach_res->num_rows > 0) {
                                    while ($tch = $teach_res->fetch_assoc()) {
                                        echo "<tr>
                                            <td>" . htmlspecialchars($tch['name']) . "</td>
                                            <td>" . htmlspecialchars($tch['username']) . "</td>
                                            <td>" . ucfirst(htmlspecialchars($tch['role'])) . "</td>
                                            <td>";
                                        if ($tch['role'] === 'teacher' || $tch['role'] === 'principle') {
                                            echo "<button class='btn-secondary manage-student-btn' data-username='" . htmlspecialchars($tch['username']) . "'>Manage Data</button> ";
                                        }
                                        echo "<button class='btn-secondary edit-user-btn' data-id='" . $tch['id'] . "'>Edit</button>";
                                        echo "</td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4'>No teachers or principles found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- About Page -->
                <div class="page hidden" id="aboutPage">
                    <div class="page-header">
                        <h2>Manage About Cards</h2>
                        <button class="btn-primary" id="addAboutBtn">Add New Card</button>
                    </div>
                    <div class="about-cards" id="aboutCards">
                        <?php
                        $result = $conn->query("SELECT * FROM about_cards ORDER BY display_order ASC");
                        while ($card = $result->fetch_assoc()) {
                            echo "<div class='about-card'>
                                <h3>{$card['title']}</h3>
                                <p>" . substr($card['content'], 0, 100) . "...</p>
                                <div class='card-actions'>
                                    <button class='btn-secondary edit-btn' data-id='{$card['id']}'>Edit</button>
                                    <button class='btn-danger delete-btn' data-id='{$card['id']}'>Delete</button>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- Events Page -->
                <div class="page hidden" id="eventsPage">
                    <div class="page-header">
                        <h2>Manage Events</h2>
                        <button class="btn-primary" id="addEventBtn">Add New Event</button>
                    </div>
                    <div class="events-list" id="eventsList">
                        <?php
                        $result = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
                        while ($event = $result->fetch_assoc()) {
                            echo "<div class='event-item'>
                                <h3>{$event['title']}</h3>
                                <p>" . substr($event['description'], 0, 100) . "...</p>
                                <small>{$event['event_date']} at {$event['event_time']} - {$event['location']}</small>
                                <div class='card-actions'>
                                    <button class='btn-secondary edit-btn' data-id='{$event['id']}' data-type='event'>Edit</button>
                                    <button class='btn-danger delete-btn' data-id='{$event['id']}' data-type='event'>Delete</button>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- Admissions Page -->
                <div class="page hidden" id="admissionsPage">
                    <div class="page-header">
                        <h2>Admissions</h2>
                    </div>
                    <div class="admissions-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query("SELECT * FROM admissions");
                                while ($admission = $result->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$admission['student_name']}</td>
                                        <td>{$admission['email']}</td>
                                        <td>{$admission['phone']}</td>
                                        <td>
                                            <button class='btn-danger delete-btn' data-id='{$admission['id']}'>Delete</button>
                                        </td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Create User Page -->
                <div class="page hidden" id="createUserPage">
                    <div class="admin-card user-create-card" style="max-width: 500px; margin: 0 auto;">
                        <h2 style="color: white; text-shadow: 1px 1px 2px black; text-align: center; margin-bottom: 20px;">Create Account</h2>
                        <form id="createUserForm" enctype="multipart/form-data">
                            <div class="input-box">
                                <span class="material-symbols-outlined icon">person</span>
                                <input type="text" name="name" required placeholder=" ">
                                <label>Full Name</label>
                            </div>

                            <div class="input-box">
                                <span class="material-symbols-outlined icon">badge</span>
                                <input type="text" name="username" required placeholder=" ">
                                <label>Username</label>
                            </div>

                            <div class="input-box">
                                <span class="material-symbols-outlined icon toggle-password" style="cursor: pointer;">lock</span>
                                <input type="password" name="password" id="createUserPassword" required placeholder=" ">
                                <label>Password</label>
                            </div>

                            <div class="input-box">
                                <span class="material-symbols-outlined icon">school</span>
                                <select name="role" required>
                                    <option value="" disabled selected>Select Role</option>
                                    <option value="student">Student</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="principle">Principle</option>
                                </select>
                            </div>

                            <div class="input-box">
                                <span class="material-symbols-outlined icon" style="top: 15px;">image</span>
                                <label style="top: -10px; font-size: 0.8em; position: absolute;">Profile Photo</label>
                                <input type="file" name="profile_pic" accept="image/*" style="padding-top: 15px;">
                            </div>

                            <button type="submit" class="btn-primary" style="margin-top: 10px;">Create Account</button>
                        </form>
                    </div>
                </div>

                <!-- Uploads Page -->
                <div class="page hidden" id="uploadsPage">
                    <div class="page-header">
                        <h2>File Uploads</h2>
                    </div>
                    <div class="upload-form">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <input type="file" name="file" id="fileInput" multiple>
                            <button type="submit" class="btn-primary">Upload</button>
                        </form>
                    </div>
                    <div class="uploaded-files" id="uploadedFiles">
                        <!-- Files will be loaded here -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div class="modal" id="aboutModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="aboutModalTitle">Add About Card</h3>
                <button class="modal-close" data-modal="aboutModal">&times;</button>
            </div>
            <form id="aboutForm">
                <input type="hidden" name="id" id="aboutId">
                <input type="text" name="title" id="aboutTitle" placeholder="Title" required>
                <textarea name="content" id="aboutContent" placeholder="Content" required></textarea>
                <input type="number" name="display_order" id="aboutOrder" placeholder="Display Order">
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </div>
    </div>

    <div class="modal" id="eventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="eventModalTitle">Add Event</h3>
                <button class="modal-close" data-modal="eventModal">&times;</button>
            </div>
            <form id="eventForm">
                <input type="hidden" name="id" id="eventId">
                <input type="text" name="title" id="eventTitle" placeholder="Event Title" required>
                <input type="date" name="event_date" id="eventDate" required>
                <input type="time" name="event_time" id="eventTime" required>
                <input type="text" name="location" id="eventLocation" placeholder="Location" required>
                <select name="type" id="eventType" required>
                    <option value="">Select Type</option>
                    <option value="academic">Academic</option>
                    <option value="sports">Sports</option>
                    <option value="cultural">Cultural</option>
                    <option value="other">Other</option>
                </select>
                <textarea name="description" id="eventDescription" placeholder="Description" required></textarea>
                <input type="text" name="image" id="eventImage" placeholder="Image URL (optional)">
                <select name="status" id="eventStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editUserModalTitle">Edit User</h3>
                <button class="modal-close" data-modal="editUserModal">&times;</button>
            </div>
            <form id="editUserForm" enctype="multipart/form-data">
                <div style="padding: 25px; display: flex; flex-direction: column; gap: 15px;">
                    <input type="hidden" name="userId" id="editUserId">

                    <label for="editUserName">Full Name</label>
                    <input type="text" name="name" id="editUserName" class="form-control" required>

                    <label for="editUserUsername">Username</label>
                    <input type="text" name="username" id="editUserUsername" class="form-control" required>

                    <label for="editUserPassword">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" id="editUserPassword" class="form-control">

                    <label for="editUserRole">Role</label>
                    <select name="role" id="editUserRole" class="form-control" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="principle">Principle</option>
                    </select>

                    <label for="editUserProfilePic">Profile Photo (leave blank to keep current)</label>
                    <input type="file" name="profile_pic" id="editUserProfilePic" accept="image/*">
                    <div id="currentUserProfilePic"></div>

                    <button type="submit" class="btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Data Modal -->
    <div class="modal" id="studentDataModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="studentModalTitle">Manage Student Data</h3>
                <button class="modal-close" data-modal="studentDataModal">&times;</button>
            </div>
            <form id="studentDataForm">
                <input type="hidden" id="studentUsername" name="username">
                <div style="padding: 25px; display: flex; flex-direction: column; gap: 15px;">
                    <label>Select Section to Edit:</label>
                    <select id="studentSectionId" name="section_id" required style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                        <option value="">-- Select Section --</option>
                        <?php
                        $sec_res = $conn->query("SELECT section_id, title FROM dashboard_cards WHERE section_id NOT IN ('feedback', 'complaints')");
                        while ($sec = $sec_res->fetch_assoc()) {
                            echo "<option value='" . $sec['section_id'] . "'>" . $sec['title'] . "</option>";
                        }
                        ?>
                    </select>
                    <textarea id="studentContent" name="content" rows="10" placeholder="Enter HTML content for this student..." style="padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;"></textarea>
                    <button type="submit" class="btn-primary">Save Data</button>
                </div>
            </form>
        </div>
    </div>

    <script src="admin-script.js"></script>
</body>

</html>