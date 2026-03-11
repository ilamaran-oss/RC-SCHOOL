<?php
session_start();
require "db.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = "Username"; // default text

if (isset($_SESSION['user_name']) && $_SESSION['user_name'] != "") {
    $username = $_SESSION['user_name'];
}

// Define a fallback for the attendance layout to prevent it from breaking
$default_attendance_html = '<div class="info-grid">
    <div class="info-card" style="text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center;">
        <h3>Attendance Percentage</h3>
        <div class="stat-big" style="font-size: 4rem; font-weight: bold; color: #00d4ff;">95.8%</div>
        <p>Excellent Record!</p>
    </div>
    <div class="info-card">
        <h3>Summary</h3>
        <p><strong>Total Working Days:</strong> 120</p>
        <p><strong>Days Present:</strong> 115</p>
        <p><strong>Days Absent:</strong> 5</p>
        <p><strong>Last Absent:</strong> 14 Feb 2026 (Sick Leave)</p>
    </div>
</div>';

$default_subjects_html = '<div class="subjects-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
    <div class="info-card">
        <h3>Mathematics</h3>
        <p>Algebra, Geometry, Trigonometry</p>
        <p><em>Teacher: Mrs. Smith</em></p>
    </div>
    <div class="info-card">
        <h3>Science</h3>
        <p>Physics, Chemistry, Biology</p>
        <p><em>Teacher: Mr. White</em></p>
    </div>
    <div class="info-card">
        <h3>English</h3>
        <p>Literature, Grammar, Creative Writing</p>
        <p><em>Teacher: Ms. Davis</em></p>
    </div>
    <div class="info-card">
        <h3>History</h3>
        <p>World History, Civics</p>
        <p><em>Teacher: Mr. Brown</em></p>
    </div>
</div>';

$default_notices_html = '<div class="info-card" style="text-align: center; color: rgba(255,255,255,0.7);">
    <p>No announcements at the moment.</p>
</div>';

$default_fees_html = '<div class="info-card">
    <h3 style="color: #ffd700; font-size: 1.3rem; margin-bottom: 15px;">Current Status</h3>
    <div style="margin-bottom: 20px; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
        <p style="margin-bottom: 8px;"><strong>Status:</strong> <span style="color: #2ed573; font-weight: bold;">Paid</span></p>
        <p style="margin-bottom: 8px;"><strong>Due Date:</strong> 12th January 2026</p>
        <p style="margin-bottom: 8px;"><strong>Amount Due:</strong> ₹0.00</p>
    </div>

    <h3 style="color: #ffd700; font-size: 1.1rem; margin-bottom: 10px;">Payment History</h3>
    <ul style="list-style: none; padding: 0; font-size: 0.9rem; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
        <li style="margin-bottom: 5px;">Term 1 Fees - ₹5000 (Paid)</li>
        <li style="margin-bottom: 5px;">Book Fees - ₹2500 (Paid)</li>
        <li style="margin-bottom: 5px;">Transport Fee - ₹1000 (Paid)</li>
        <li style="margin-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;"><strong>Total - ₹8500 (Paid)</strong></li>
    </ul>

    <h3 style="color: #ffd700; font-size: 1.1rem; margin-bottom: 10px;">Next Payment</h3>
    <ul style="list-style: none; padding: 0; font-size: 0.9rem; margin-bottom: 10px;">
        <li style="margin-bottom: 5px;">Term 2 Fees - ₹5000 (Not Paid)</li>
        <li style="margin-bottom: 5px;">Transport Fee - ₹1000 (Not Paid)</li>
        <li style="margin-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;"><strong>Total - ₹6000 (Not Paid)</strong></li>
    </ul>
    <p style="font-size: 1rem;"><strong>Due Date :</strong> <span style="color: #ffd700;">26th April 2026</span></p>

    <a href="fee_payment.php" class="action-btn" style="text-align: center; text-decoration: none; display: block; background: #2ed573; color: white; font-size: 1rem; padding: 10px; margin-top: 20px;">Proceed to Pay</a>
    
    <div style="margin-top: 15px; text-align: center;">
        <p style="font-size: 0.8rem; margin-bottom: 5px;">We accept payments via:</p>
        <div style="display: flex; justify-content: center; gap: 8px;">
            <img src="STYLE/axis-bank.png" alt="AXIS" style="height: 25px; background: white; padding: 2px; border-radius: 3px;">
            <img src="STYLE/sbi.png" alt="SBI" style="height: 25px; background: white; padding: 2px; border-radius: 3px;">
            <img src="STYLE/cbi.png" alt="CBI" style="height: 25px; background: white; padding: 2px; border-radius: 3px;">
            <img src="STYLE/indian-bank.png" alt="INDIAN BANK" style="height: 25px; background: white; padding: 2px; border-radius: 3px;">
        </div>
    </div>
</div>';

$default_feedback_html = '<div style="max-width: 800px; margin: 0 auto;">
    <p style="margin-bottom: 20px;">We value your suggestions.</p>
    <input type="text" class="form-control" placeholder="Subject">
    <textarea class="form-control" rows="6" placeholder="Write your feedback here..."></textarea>
    <button class="action-btn">Submit Feedback</button>
</div>';

$default_complaints_html = '<div style="max-width: 800px; margin: 0 auto;">
    <p style="margin-bottom: 20px;">Please describe your issue below.</p>
    <select class="form-control">
        <option>Select Category</option>
        <option>Academic</option>
        <option>Infrastructure</option>
    </select>
    <textarea class="form-control" rows="6" placeholder="Describe your complaint..."></textarea>
    <button class="action-btn" style="background-color: #ff4757; color: white;">Submit Complaint</button>
</div>';

// Fetch Logged-in User's Username (for DB lookups)
$db_username = "";
if (isset($_SESSION['user_id'])) {
    $u_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    if ($row = $u_res->fetch_assoc()) {
        $db_username = $row['username'];
    }
}

// Fetch Admission Data for Profile
$admission_details = [];
if (isset($_SESSION['user_name'])) {
    $adm_stmt = $conn->prepare("SELECT * FROM admissions WHERE student_name = ?");
    $adm_stmt->bind_param("s", $_SESSION['user_name']);
    $adm_stmt->execute();
    $adm_res = $adm_stmt->get_result();
    if ($adm_res->num_rows > 0) {
        $admission_details = $adm_res->fetch_assoc();
    }
}

// --- Schedule Logic ---
// 1. Define Static Fallback
$schedule_html = '<h3 style="margin-top: 40px; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 10px;">Weekly Class Schedule</h3>
<div style="overflow-x: auto; background: rgba(0,0,0,0.2); border-radius: 10px; padding: 10px;">
    <table style="width: 100%; border-collapse: collapse; color: white; min-width: 600px;">
        <thead>
            <tr style="background: rgba(255,255,255,0.1);">
                <th style="padding: 12px; text-align: left;">Time</th>
                <th style="padding: 12px; text-align: left;">Monday</th>
                <th style="padding: 12px; text-align: left;">Tuesday</th>
                <th style="padding: 12px; text-align: left;">Wednesday</th>
                <th style="padding: 12px; text-align: left;">Thursday</th>
                <th style="padding: 12px; text-align: left;">Friday</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px; font-weight: bold; color: #ffd700;">08:00 - 09:00</td>
                <td style="padding: 12px;">Mathematics</td><td style="padding: 12px;">Science</td><td style="padding: 12px;">English</td><td style="padding: 12px;">Mathematics</td><td style="padding: 12px;">History</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px; font-weight: bold; color: #ffd700;">09:00 - 10:00</td>
                <td style="padding: 12px;">Science</td><td style="padding: 12px;">English</td><td style="padding: 12px;">Mathematics</td><td style="padding: 12px;">History</td><td style="padding: 12px;">Science</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px; font-weight: bold; color: #ffd700;">10:00 - 10:30</td>
                <td style="padding: 12px; text-align: center; background: rgba(255,255,255,0.05); font-style: italic;" colspan="5">Morning Break</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px; font-weight: bold; color: #ffd700;">10:30 - 11:30</td>
                <td style="padding: 12px;">English</td><td style="padding: 12px;">History</td><td style="padding: 12px;">Science</td><td style="padding: 12px;">English</td><td style="padding: 12px;">Mathematics</td>
            </tr>
        </tbody>
    </table>
</div>';

// Fetch Student Specific Content
$student_content = [];
if (!empty($db_username)) {
    try {
        $stmt = $conn->prepare("SELECT section_id, content FROM student_section_content WHERE username = ?");
        $stmt->bind_param("s", $db_username);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $student_content[$row['section_id']] = $row['content'];
        }
    } catch (Exception $e) {
        // Table might not exist yet, ignore to prevent fatal error
    }
}

// Fetch Events for Notices Section (Active & Published)
$db_events = [];
$evt_res = $conn->query("SELECT * FROM events WHERE status IN ('active','published') ORDER BY event_date DESC LIMIT 5");
if ($evt_res) {
    while ($row = $evt_res->fetch_assoc()) $db_events[] = $row;
}

// Fetch Dashboard Cards
$cards = [];
$card_sql = "SELECT * FROM dashboard_cards ORDER BY id ASC";
$card_result = $conn->query($card_sql);
if ($card_result && $card_result->num_rows > 0) {
    while ($row = $card_result->fetch_assoc()) {
        $cards[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | RC Middle School</title>

    <!-- Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <!-- Main CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Slider Styles */
        .slider-container {
            position: relative;
            max-width: 1100px;
            margin: 0 auto;
            -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
            mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
        }

        .dashboard-cards {
            display: flex !important;
            /* Override grid from style.css */
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 25px;
            padding: 20px 10px;
            justify-content: flex-start;
            grid-template-columns: none !important;
            width: 100%;
            max-width: 100%;
            margin: 0;
        }

        /* Hide scrollbar */
        .dashboard-cards::-webkit-scrollbar {
            display: none;
        }

        .dashboard-cards {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .dash-card {
            min-width: 250px;
            flex: 0 0 auto;
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
            opacity: 0;
        }

        .slider-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .prev-btn {
            left: 0;
        }

        .next-btn {
            right: 0;
        }

        /* Section Styles */
        .content-section {
            display: block;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            text-align: left;
            width: 100%;
            max-width: 1100px;
            margin: 20px auto;
            animation: fadeIn 0.4s ease;
            color: white;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 80px;
            z-index: 1000;
        }

        /* SIDEBAR */

        :root {
            --sidebar-width: 240px;
        }

        .custom-sidebar {
            position: fixed;
            top: 80px;
            /* under header */
            left: 0;

            width: var(--sidebar-width);
            height: calc(100vh - 80px);
            overflow-y: auto;

            /* Glass morphism */
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);

            border-right: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);

            padding: 20px;

            z-index: 999;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-item {
            position: relative;
            align-items: center;
            gap: 12px;

            padding: 12px 15px;
            margin-bottom: 10px;

            border-radius: 10px;

            cursor: pointer;

            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.08);

            transition: all .3s ease;

            color: white;
        }

        .sidebar-item:hover {

            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.25);

            transform: translateX(5px);
        }

        .dashboard {
            margin-left: 260px;
        }

        @media(max-width:900px) {

            .custom-sidebar {
                width: 200px;
                box-sizing: border-box;
            }

            .dashboard {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
                padding: 20px;
                box-sizing: border-box;
            }

        }

        .sidebar-item.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #e6eced;
            border-radius: 4px;
        }

        .sidebar-item::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0%;
            height: 2px;
            background: #f8fafa;
            transition: 0.3s ease;
        }

        /* Hover animation */
        .sidebar-item:hover::after {
            width: 100%;
        }

        /* Active item */
        .sidebar-item.active::after {
            width: 100%;
        }

        .back-btn:hover {
            transform: translateX(-5px);
            color: #ddd;
        }

        .dash-card {
            cursor: pointer;
        }

        .card-preview {
            font-size: 0.9rem;
            margin-top: 10px;
            opacity: 0.8;
            line-height: 1.4;
        }

        /* Expanded Content Styles */
        .content-section h2 {
            font-size: 2.5rem;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 15px;
        }

        .content-section p,
        .content-section li {
            font-size: 1.2rem;
            line-height: 1.6;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .info-card {
            background: rgba(0, 0, 0, 0.3);
            padding: 30px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            background: rgba(0, 0, 0, 0.4);
        }

        .info-card h3 {
            font-size: 1.6rem;
            color: #ffd700;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            font-family: inherit;
        }

        .action-btn {
            padding: 15px 40px;
            font-size: 1.2rem;
            background: #00d4ff;
            color: #000;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .action-btn:hover {
            background: #fff;
            transform: scale(1.05);
        }

        .stat-big {
            font-size: 4rem;
            font-weight: bold;
            color: #010405;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="dashboard-page">

    <!-- HEADER -->
    <header>
        <div class="logo">
            <img src="pngwing.com (1).png" class="logo-img">
            <span>RC MIDDLE SCHOOL</span>
        </div>

        <nav>
            <ul>
                <li><a href="home.php" class="nav-link "><span
                            class="material-symbols-outlined nav-icon">school</span>Home</a></li>
                <li><a href="about.php" class="nav-link"><span
                            class="material-symbols-outlined nav-icon">language</span>About</a></li>
                <li><a href="admission.php" class="nav-link"><span
                            class="material-symbols-outlined nav-icon">assignment</span>Admissions</a></li>
                <li><a href="event.php" class="nav-link"><span class="material-symbols-outlined nav-icon">
                            event_available </span>Events</a></li>
                <li><a href="contact.php" class="nav-link"><span
                            class="material-symbols-outlined nav-icon">call</span>Contact</a></li>
                <li><a href="dash.php" class="nav-link active"><span
                            class="material-symbols-outlined nav-icon">Dashboard</span>Dash</a></li>
                <li class="profile-dropdown">
                    <a href="javascript:void(0);" class="nav-link" id="profileTrigger" onclick="toggleProfileMenu()">
                        <?php if (isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" class="nav-icon profile-img">
                        <?php else: ?>
                            <span class="material-symbols-outlined nav-icon">account_circle</span>
                        <?php endif; ?>
                    </a>
                    <div class="profile-menu" id="profileMenu">

                        <p class="username"><?php echo htmlspecialchars($username); ?></p>
                        <a href="changepass.php" class="logout-link">Change Password</a><br>
                        <a href="login.php" class="logout-link">Logout</a>
                    </div>
                </li>


            </ul>
        </nav>
    </header>

    <!-- SIDEBAR -->
    <div class="custom-sidebar">

        <ul class="sidebar-menu">

            <?php if (!empty($cards)): ?>
                <?php foreach ($cards as $card): ?>

                    <li class="sidebar-item"
                        data-section="<?php echo htmlspecialchars($card['section_id']); ?>"
                        onclick="scrollToSection('<?php echo htmlspecialchars($card['section_id']); ?>')">

                        <span class="material-symbols-outlined">
                            <?php echo htmlspecialchars($card['icon']); ?>
                        </span>

                        <?php echo htmlspecialchars($card['title']); ?>

                    </li>

                <?php endforeach; ?>
            <?php endif; ?>

        </ul>

    </div>

    <!-- DASHBOARD -->
    <section class="dashboard">

        <!-- Main Dashboard View -->

        <h1>Welcome to Your Dashboard</h1>
        <p class="subtitle">Manage your school activities</p>


        <!-- Hidden Sections -->
        <?php if (!empty($cards)): ?>
            <?php foreach ($cards as $card): ?>
                <div id="<?php echo htmlspecialchars($card['section_id']); ?>" class="content-section">
                    <h2><?php echo htmlspecialchars($card['title']); ?></h2>

                    <div class="dynamic-content">
                        <?php
                        $section_id = $card['section_id'];
                        $output_content = '';

                        // 1. Prioritize student-specific content
                        if (isset($student_content[$section_id]) && !empty(trim($student_content[$section_id]))) {
                            $output_content = $student_content[$section_id];

                            // Fix for Fees section: If content lacks the button (likely just text was saved), wrap it and add button.
                            if ($section_id === 'fees' && strpos($output_content, 'fee_payment.php') === false) {
                                $footer_html = '<a href="fee_payment.php" class="action-btn" style="text-align: center; text-decoration: none; display: block; background: #2ed573; color: white; font-size: 1rem; padding: 10px; margin-top: 20px;">Proceed to Pay</a>
                                <div style="margin-top: 15px; text-align: center;">
                                    <p style="font-size: 0.8rem; margin-bottom: 5px;">We accept payments via:</p>
                                    <div style="display: flex; justify-content: center; gap: 8px;">
                                        <img src="STYLE/axis-bank.png" alt="AXIS" style="height: 25px; background: white; padding: 2px; border-radius: 3px;">
                                        <img src="STYLE/sbi.png" alt="SBI" style="height: 25px; background: white; padding: 2px; border-radius: 3px;">
                                        <img src="STYLE/cbi.png" alt="CBI" style="height: 25px; background: white; padding: 2px; border-radius: 3px;">
                                        <img src="STYLE/indian-bank.png" alt="INDIAN BANK" style="height: 25px; background: white; padding: 2px; border-radius: 3px;">
                                    </div>
                                </div>';

                                if (strpos($output_content, 'info-card') === false) {
                                    $output_content = '<div class="info-card">' . $output_content . $footer_html . '</div>';
                                } else {
                                    $output_content = preg_replace('/(<\/div>\s*$)/', $footer_html . '$1', $output_content);
                                }
                            }

                            // Fix for Attendance section: Use regex for a more robust check.
                            if ($section_id === 'attendance' && !preg_match('/<div[^>]+class="[^"]*info-grid[^"]*"/', $output_content)) {
                                if (strpos($output_content, 'info-card') !== false) {
                                    $output_content = '<div class="info-grid">' . $output_content . '</div>';
                                } else {
                                    $output_content = '<div class="info-grid"><div class="info-card">' . $output_content . '</div></div>';
                                }
                            }

                            // Fix for Subjects section: Use regex for a more robust check.
                            if ($section_id === 'subjects' && !preg_match('/<div[^>]+class="[^"]*subjects-grid[^"]*"/', $output_content)) {
                                if (strpos($output_content, 'info-card') !== false) {
                                    $output_content = '<div class="subjects-grid">' . $output_content . '</div>';
                                } else {
                                    $output_content = '<div class="subjects-grid"><div class="info-card">' . $output_content . '</div></div>';
                                }
                            }

                            // Fix for Schedule section: Use regex for a more robust check.
                            if ($section_id === 'schedule') {
                                if (!preg_match('/<div[^>]+class="[^"]*schedule-wrapper[^"]*"/', $output_content)) {
                                    // If the h3 title is also missing, rebuild the entire structure
                                    if (strpos($output_content, '<h3') === false) {
                                        $title_html = '<h3 class="schedule-title">Weekly Class Schedule</h3>';
                                        $output_content = $title_html . '<div class="schedule-wrapper">' . $output_content . '</div>';
                                    } else {
                                        // If title exists but wrapper is missing, just wrap the content.
                                        $output_content = '<div class="schedule-wrapper">' . $output_content . '</div>';
                                    }
                                }
                            }

                            // Fix for Profile section: Use regex for a more robust check.
                            if ($section_id === 'profile' && !preg_match('/<div[^>]+class="[^"]*info-grid[^"]*"/', $output_content)) {
                                if (strpos($output_content, 'info-card') !== false) {
                                    $output_content = '<div class="info-grid" style="grid-template-columns: 1fr;">' . $output_content . '</div>';
                                } else {
                                    $output_content = '<div class="info-grid" style="grid-template-columns: 1fr;"><div class="info-card">' . $output_content . '</div></div>';
                                }
                            }
                        }
                        // 2. Fallback to default content from DB
                        else if (isset($card['detailed_content']) && !empty(trim($card['detailed_content']))) {
                            $output_content = $card['detailed_content'];
                        }
                        // 3. Fallback to hardcoded defaults
                        else {
                            switch ($section_id) {
                                case 'attendance':
                                    $output_content = $default_attendance_html;
                                    break;
                                case 'subjects':
                                    $output_content = $default_subjects_html;
                                    break;
                                case 'notices':
                                    $output_content = $default_notices_html;
                                    break;
                                case 'fees':
                                    $output_content = $default_fees_html;
                                    break;
                                case 'feedback':
                                    $output_content = $default_feedback_html;
                                    break;
                                case 'complaints':
                                    $output_content = $default_complaints_html;
                                    break;
                                case 'schedule':
                                    $output_content = $schedule_html;
                                    break;
                                case 'profile':
                                    if (!empty($admission_details)) {
                                        $output_content = '<div class="info-grid">
                                            <div class="info-card">
                                                <h3>Personal Information</h3>
                                                <p><strong>Name:</strong> ' . htmlspecialchars($admission_details['student_name']) . '</p>
                                                <p><strong>Email:</strong> ' . htmlspecialchars($admission_details['email']) . '</p>
                                                <p><strong>Phone:</strong> ' . htmlspecialchars($admission_details['phone']) . '</p>
                                                <p><strong>ID:</strong> ' . htmlspecialchars($admission_details['id']) . '</p>
                                            </div>
                                            <div class="info-card">
                                                <h3>Academic Status</h3>
                                                <p><strong>Status:</strong> Active</p>
                                                <p><strong>Role:</strong> Student</p>
                                            </div>
                                        </div>';
                                    }
                                    break;
                                default:
                                    $output_content = '<p>No content available for this section.</p>';
                                    break;
                            }
                        }

                        // Special handling for Notices to create individual cards
                        if ($section_id === 'notices') {
                            $combined_html = '';

                            // 1. Process Manual Content (from Editor)
                            // Check if content is NOT the default. If custom, add it.
                            $is_default = ($output_content === $default_notices_html);

                            if (!$is_default) {
                                // Remove existing grid wrapper to allow merging
                                $inner_content = $output_content;
                                if (preg_match('/<div[^>]*class="[^"]*info-grid[^"]*"[^>]*>/', $output_content)) {
                                    $inner_content = preg_replace('/^<div[^>]*class="[^"]*info-grid[^"]*"[^>]*>|<\/div>$/', '', trim($output_content));
                                }

                                // Split by <hr> to create cards
                                $parts = preg_split('/<hr\s*\/?>/i', $inner_content);
                                foreach ($parts as $part) {
                                    $t = trim($part);
                                    if (!empty(strip_tags($t))) {
                                        // Wrap in card if not already wrapped
                                        if (strpos($t, 'info-card') === false) {
                                            $combined_html .= '<div class="info-card">' . $t . '</div>';
                                        } else {
                                            $combined_html .= $t;
                                        }
                                    }
                                }
                            }

                            // 2. Append Database Events
                            foreach ($db_events as $evt) {
                                $combined_html .= '<div class="info-card">';
                                $combined_html .= '<h3><span class="material-symbols-outlined" style="vertical-align:bottom; margin-right:8px;">event</span>' . htmlspecialchars($evt['title']) . '</h3>';
                                $combined_html .= '<p>' . $evt['description'] . '</p>';
                                $combined_html .= '<p style="margin-top:10px; font-size:0.9rem; opacity:0.8; padding-top:10px; border-top:1px solid rgba(255,255,255,0.1)">📅 ' . date("M d, Y", strtotime($evt['event_date'])) . ' • ' . htmlspecialchars($evt['event_time']) . '</p>';
                                $combined_html .= '</div>';
                            }

                            // 3. Final Output
                            if (!empty($combined_html)) {
                                $output_content = '<div class="info-grid">' . $combined_html . '</div>';
                            } else {
                                // If both manual content and DB events are empty, show a clean message instead of reverting to default placeholders
                                $output_content = '<div class="info-card" style="text-align:center; color: rgba(255,255,255,0.7);"><p>No announcements at the moment.</p></div>';
                            }
                        }

                        echo $output_content;
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </section>
    <script>
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        window.onclick = function(event) {
            const menu = document.getElementById('profileMenu');
            const icon = document.getElementById('profileTrigger');
            if (icon && !icon.contains(event.target) && !menu.contains(event.target)) {
                menu.style.display = 'none';
            }
        }

        let isScrolling = false;

        // Smooth scroll
        function scrollToSection(sectionId) {

            const section = document.getElementById(sectionId);

            if (section) {
                isScrolling = true;
                section.scrollIntoView({
                    behavior: "smooth"
                });
                // Manually update active class for immediate feedback
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.dataset.section === sectionId) item.classList.add('active');
                });
                setTimeout(() => {
                    isScrolling = false;
                }, 1000);
            }

        }

        const sections = document.querySelectorAll(".content-section");
        const sidebarItems = document.querySelectorAll(".sidebar-item");

        window.addEventListener("scroll", () => {

            if (isScrolling) return;

            let current = "";

            sections.forEach(section => {

                const sectionTop = section.offsetTop;

                if (window.scrollY >= sectionTop - 150) {
                    current = section.getAttribute("id");
                }

            });

            sidebarItems.forEach(item => {

                item.classList.remove("active");

                if (item.dataset.section === current) {
                    item.classList.add("active");
                }

            });

        });
    </script>

</body>

</html>