<?php
session_start();
require "db.php";

// Helper to extract attendance percentage safely
if (!function_exists('extract_attendance_pct')) {
    function extract_attendance_pct($html)
    {
        if (empty($html)) return 0;
        // 1. Try specific class "stat-big" (High confidence)
        if (preg_match('/class=["\']stat-big["\'][^>]*>\s*([\d\.]+)\s*%/i', $html, $m)) {
            return (float)$m[1];
        }
        // 2. Fallback: First percentage in visible text (strips tags to ignore CSS width:100%)
        $text = strip_tags($html);
        if (preg_match('/([\d\.]+)\s*%/', $text, $m)) {
            return (float)$m[1];
        }
        return 0;
    }
}

// Helper to extract email/phone/grade from HTML content
function extract_info_from_html($html, $label) {
    if (empty($html)) return null;
    // Replace block tags with newlines to prevent text merging
    $html = preg_replace('/<(p|div|br|tr|h[1-6])[^>]*>/i', "\n", $html);
    $text = strip_tags($html);
    // Look for "Label: Value" pattern
    if (preg_match('/' . preg_quote($label) . ':\s*([^\n\r]+)/i', $text, $m)) {
        return trim($m[1]);
    }
    return null;
}

// Auth check — only teachers allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_name = $_SESSION['user_name'] ?? 'Teacher';
$teacher_username = '';
$profile_pic = $_SESSION['profile_pic'] ?? '';

// Fetch latest profile pic if not in session or to be sure
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) $profile_pic = $row['profile_pic'];

// Get teacher's username from DB
$u_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$u_stmt->bind_param("i", $_SESSION['user_id']);
$u_stmt->execute();
$u_res = $u_stmt->get_result();
if ($row = $u_res->fetch_assoc()) {
    $teacher_username = $row['username'];
}

// ── Fetch students from admissions ──────────────────────────────────────────
$students = [];
// We need the username to fetch student-specific data.
// We join users with admissions on name to get all details.
$s_res = $conn->query("SELECT u.name as student_name, u.username, a.email, a.phone FROM users u LEFT JOIN admissions a ON u.name = a.student_name WHERE u.role = 'student' ORDER BY u.name ASC");
if ($s_res && $s_res->num_rows > 0) {
    while ($row = $s_res->fetch_assoc()) {
        $students[] = $row;
    }
}
$total_students = count($students);

// ── Fetch attendance data ────────────────────────────────────────────────────
// Try to get attendance from student_section_content (section_id = 'attendance')
$attendance_map = [];
$att_stmt = $conn->prepare("SELECT username, content FROM student_section_content WHERE section_id = 'attendance'");
if ($att_stmt) {
    $att_stmt->execute();
    $att_res = $att_stmt->get_result();
    while ($row = $att_res->fetch_assoc()) {
        $attendance_map[$row['username']] = $row['content'];
    }
}

// ── Fetch grades data ────────────────────────────────────────────────────────
$grades_map = [];
$gr_stmt = $conn->prepare("SELECT username, content FROM student_section_content WHERE section_id = 'subjects'");
if ($gr_stmt) {
    $gr_stmt->execute();
    $gr_res = $gr_stmt->get_result();
    while ($row = $gr_res->fetch_assoc()) {
        $grades_map[$row['username']] = $row['content'];
    }
}

// ── Fetch profile data (for overrides) ──────────────────────────────────────
$profile_map = [];
$prof_stmt = $conn->prepare("SELECT username, content FROM student_section_content WHERE section_id = 'profile'");
if ($prof_stmt) {
    $prof_stmt->execute();
    $prof_res = $prof_stmt->get_result();
    while ($row = $prof_res->fetch_assoc()) {
        $profile_map[$row['username']] = $row['content'];
    }
}

// ── Fetch announcements / notices ───────────────────────────────────────────
$notices = [];
$n_res = $conn->query("SELECT * FROM dashboard_cards WHERE section_id = 'notices' ORDER BY id DESC LIMIT 5");
if ($n_res && $n_res->num_rows > 0) {
    while ($row = $n_res->fetch_assoc()) {
        $notices[] = $row;
    }
}

// ── Fetch Events (from Admin) ───────────────────────────────────────────────
$events = [];
$e_res = $conn->query("SELECT * FROM events ORDER BY event_date DESC LIMIT 5");
if ($e_res && $e_res->num_rows > 0) {
    while ($row = $e_res->fetch_assoc()) {
        $events[] = $row;
    }
}

// ── Fetch Teacher Schedule ──────────────────────────────────────────────────
$schedule_html = '';
$sch_stmt = $conn->prepare("SELECT content FROM student_section_content WHERE username = ? AND section_id = 'schedule'");
$sch_stmt->bind_param("s", $teacher_username);
$sch_stmt->execute();
$sch_res = $sch_stmt->get_result();
if ($row = $sch_res->fetch_assoc()) {
    $schedule_html = $row['content'];
}

// ── Handle Announcement Post ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announce_title'])) {
    $title = $_POST['announce_title'];
    $desc = $_POST['announce_desc'];
    $date = date('Y-m-d');
    $time = date('H:i');
    $type = 'Announcement';
    $status = 'active';
    $location = 'Teacher: ' . $teacher_name;

    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_time, type, status, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $title, $desc, $date, $time, $type, $status, $location);

    if ($stmt->execute()) {
        echo "<script>alert('Announcement posted successfully!'); window.location.href='tdash.php';</script>";
    } else {
        echo "<script>alert('Error posting announcement.');</script>";
    }
}

// ── Count pending grades (students with no grade content) ───────────────────
$pending_grades = 0;
foreach ($students as $st) {
    if (empty($grades_map[$st['username'] ?? ''] ?? '')) {
        $pending_grades++;
    }
}

// ── Notification Logic ──────────────────────────────────────────────────────
$all_notifications = [];

// Add events to notifications
foreach ($events as $event) {
    $all_notifications[] = [
        'icon' => 'event',
        'title' => $event['title'],
        'description' => 'On ' . date("M d, Y", strtotime($event['event_date'])),
    ];
}

// Add notices to notifications
foreach ($notices as $notice) {
    $all_notifications[] = [
        'icon' => 'campaign',
        'title' => $notice['title'],
        'description' => mb_strimwidth(strip_tags($notice['description'] ?? $notice['detailed_content'] ?? ''), 0, 50, "..."),
    ];
}

$notification_count = count($all_notifications);

// ── Calculate Average Attendance ────────────────────────────────────────────
$total_pct = 0;
$count_with_data = 0;
foreach ($students as $st) {
    $u = $st['username'] ?? '';
    $html = $attendance_map[$u] ?? '';
    $val = extract_attendance_pct($html);
    if ($val > 0) {
        $total_pct += $val;
        $count_with_data++;
    }
}
$avg_attendance = $count_with_data > 0 ? round($total_pct / $count_with_data) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Teacher Dashboard | RC Middle School</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="style.css" />
    <style>
        :root {
            --primary: #1f4e79;
            --accent: #e94560;
            --light-bg: #f4f6f9;
            --white: #ffffff;
            --text: #333333;
            --border: #e1e4e8;
        }

        body {
            min-height: 100vh;
            background: var(--light-bg);
            font-family: "Segoe UI", sans-serif;
            color: var(--text);
            padding-top: 80px;
            /* Space for fixed header */
        }

        /* ── Sidebar ── */
        /* Header from dash.php */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 80px;
            z-index: 1000;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* ── Sidebar ── */
        .custom-sidebar {
            position: fixed;
            top: 80px;
            left: 0;
            width: 240px;
            height: calc(100vh - 80px);
            background: var(--white);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px;
            overflow-y: auto;
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
            background: #f8f9fa;
            color: var(--text);
            transition: all .3s ease;
            display: flex;
        }

        .sidebar-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .sidebar-item::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0%;
            height: 2px;
            background: #00d4ff;
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

        /* Profile in Sidebar */
        .sidebar-profile {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .sidebar-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 3px solid var(--accent);
        }

        .sidebar-profile .t-avatar-initials {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #ff6b6b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 28px;
            color: #fff;
            margin: 0 auto 10px;
            border: 3px solid var(--accent);
        }

        .sidebar-profile .t-info-name {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--text);
        }

        .sidebar-profile .t-info-role {
            font-size: 0.9rem;
            color: #777;
        }

        /* ── Main ── */
        .t-main {
           
            padding: 36px 40px;
            min-height: 100vh;
        }

        .dashboard {
            margin-left: 260px;
            padding: 30px;
        }

        .t-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .t-topbar h1 {
            margin-left: 240px;
            font-size: 26px;
            font-weight: 300;
            letter-spacing: 3px;
        }

        .t-topbar h1 span {
            color: var(--accent);
            font-weight: 600;
        }

        .t-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid rgba(255, 255, 255, .1);
            color: #555;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: .2s;
            font-size: 17px;
            position: relative;
        }

        .t-icon-btn:hover {
            background: #eee;
            transform: translateY(-2px);
        }

        /* Notification wrapper */
        .t-notif-wrapper {
            position: relative;
        }

        /* Red notification dot */
        .notif-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            border: 1.5px solid white;
        }

        /* Dropdown */
        .t-notif-dropdown {
            display: none;
            position: absolute;
            top: 120%;
            right: 0;
            width: 320px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border);
            z-index: 100;
        }

        .t-notif-dropdown.show {
            display: block;
        }

        .t-notif-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }

        .t-notif-body {
            max-height: 350px;
            overflow-y: auto;
        }

        .t-notif-item {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #f5f5f5;
        }

        .t-notif-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f4f8;
        }

        .t-notif-title {
            font-size: 13px;
            font-weight: 600;
        }

        .t-notif-desc {
            font-size: 12px;
            color: #777;
        }

        /* ── Stat Cards ── */
        .t-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 26px;
        }

        .t-stat {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: .3s;
            animation: tFadeUp .5s ease both;
            position: relative;
            overflow: hidden;
        }

        .t-stat:nth-child(1) {
            animation-delay: .05s
        }

        .t-stat:nth-child(2) {
            animation-delay: .10s
        }

        .t-stat:nth-child(3) {
            animation-delay: .15s
        }

        .t-stat:nth-child(4) {
            animation-delay: .20s
        }

        .t-stat:hover {
            transform: translateY(-6px);
            border-color: rgba(233, 69, 96, .3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .t-stat-icon {
            font-size: 26px;
            margin-bottom: 10px;
        }

        .t-stat-num {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
            color: var(--primary);
        }

        .t-stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .t-tag {
            display: inline-block;
            margin-top: 8px;
            font-size: 11px;
            padding: 3px 9px;
            border-radius: 20px;
            background: rgba(233, 69, 96, .2);
            color: #ff9090;
        }

        .t-tag.green {
            background: rgba(46, 213, 115, .15);
            color: #2ed573;
        }

        /* ── Panels ── */
        .t-grid-2 {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        .t-panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            animation: tFadeUp .6s ease both;
        }

        .content-section {
            background: transparent;
            box-shadow: none;
            padding: 0;
            margin: 0;
            color: var(--text);
        }

        .t-panel-title {
            font-size: 13px;
            letter-spacing: 1.5px;
            font-weight: 600;
            color: #888;
            margin-bottom: 16px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .content-section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* ── Table ── */
        .t-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .t-table th {
            text-align: left;
            padding: 12px 15px;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #888;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }

        .t-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f5f5f5;
            vertical-align: middle;
            color: #444;
        }

        .t-table tr:last-child td {
            border-bottom: none;
        }

        .t-table tr:hover td {
            background: #f9f9f9;
        }

        .g-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .g-a {
            background: rgba(46, 213, 115, .15);
            color: #2ed573;
        }

        .g-b {
            background: rgba(52, 152, 219, .18);
            color: #3498db;
        }

        .g-c {
            background: rgba(241, 196, 15, .15);
            color: #f1c40f;
        }

        .g-f {
            background: rgba(233, 69, 96, .2);
            color: #ff9090;
        }

        .g-na {
            background: #eee;
            color: #999;
        }

        /* ── Attendance Bars ── */
        .att-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 13px;
        }

        .att-name {
            font-size: 13px;
            width: 120px;
            flex-shrink: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #444;
        }

        .att-wrap {
            flex: 1;
            height: 8px;
            background: #eee;
            border-radius: 99px;
            overflow: hidden;
        }

        .att-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--accent), #ff6b6b);
        }

        .att-fill.good {
            background: linear-gradient(90deg, #2ed573, #7effc8);
        }

        .att-pct {
            font-size: 12px;
            color: #666;
            width: 36px;
            text-align: right;
        }

        /* ── Schedule ── */
        .sch-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid #eee;
        }

        .sch-row:last-child {
            border-bottom: none;
        }

        .sch-time {
            font-size: 12px;
            color: var(--accent);
            width: 68px;
            flex-shrink: 0;
            font-weight: 600;
        }

        .sch-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 7px rgba(233, 69, 96, .5);
            flex-shrink: 0;
        }

        .sch-dot.b {
            background: #7dd3fc;
            box-shadow: 0 0 7px rgba(125, 211, 252, .4);
        }

        .sch-dot.g {
            background: #7effc8;
            box-shadow: 0 0 7px rgba(126, 255, 200, .4);
        }

        .sch-subj {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .sch-room {
            font-size: 12px;
            color: #888;
        }

        /* ── Announcement ── */
        .ann-item {
            display: flex;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid #eee;
        }

        .ann-item:last-child {
            border-bottom: none;
        }

        .ann-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            background: rgba(233, 69, 96, .18);
        }

        .ann-title {
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 3px;
            color: #333;
        }

        .ann-desc {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
        }

        /* ── Tab Buttons ── */
        .tab-bar {
            display: flex;
            gap: 6px;
            margin-bottom: 14px;
        }

        .tab-btn {
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid #ddd;
            background: transparent;
            color: #666;
            font-size: 12px;
            cursor: pointer;
            transition: .2s;
            width: auto;
        }

        .tab-btn:hover,
        .tab-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            transform: none;
        }

        /* ── Keyframes ── */
        @keyframes tFadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Responsive ── */
        @media (max-width:1100px) {
            .t-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .t-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width:700px) {
            .custom-sidebar {
                width: 200px;
                box-sizing: border-box;
            }

            .dashboard {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
                box-sizing: border-box;
            }

            .t-main {
                margin-left: 200px;
                padding: 18px 14px;
            }

            .t-stats {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header>
        <div class="logo" style="display: flex; align-items: center; gap: 10px; font-weight: bold; font-size: 1.2rem; color: #333;">
            <img src="pngwing.com (1).png" class="logo-img" style="height: 40px;">
            <span>RC MIDDLE SCHOOL</span>
        </div>
        <nav>
            <ul style="display: flex; gap: 20px; list-style: none; align-items: center;">
                <li><a href="home.php" class="nav-link" style="text-decoration: none; color: #555; display: flex; align-items: center; gap: 5px;"><span class="material-symbols-outlined">school</span>Home</a></li>
                <li><a href="tdash.php" class="nav-link active" style="text-decoration: none; color: var(--primary); font-weight: bold; display: flex; align-items: center; gap: 5px;"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="profile-dropdown" style="position: relative;">
                    <a href="javascript:void(0);" onclick="toggleProfileMenu()" style="text-decoration: none; color: #555; display: flex; align-items: center; gap: 5px;">
                        <?php if (!empty($profile_pic)): ?>
                            <img src="uploads/<?= htmlspecialchars($profile_pic) ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <span class="material-symbols-outlined">account_circle</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($teacher_name) ?>
                    </a>
                    <div class="profile-menu" id="profileMenu" style="display: none; position: absolute; right: 0; top: 100%; background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-radius: 8px; padding: 10px; min-width: 150px; z-index: 1001;">
                        <a href="changepass.php" style="display: block; padding: 8px; text-decoration: none; color: #333; font-size: 0.9rem;">Change Password</a>
                        <a href="login.php" style="display: block; padding: 8px; text-decoration: none; color: #d63031; font-size: 0.9rem;">Logout</a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <!-- SIDEBAR -->
    <div class="custom-sidebar">
        <div class="sidebar-profile">
            <?php if (!empty($profile_pic)): ?>
                <img src="uploads/<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
            <?php else: ?>
                <div class="t-avatar-initials"><?= strtoupper(substr($teacher_name, 0, 1)) ?></div>
            <?php endif; ?>
            <div class="t-info-name"><?= htmlspecialchars($teacher_name) ?></div>
            <div class="t-info-role">Teacher</div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item active" data-section="home" onclick="scrollToSection('home')">
                <span class="material-symbols-outlined">dashboard</span> Dashboard
            </li>
            <li class="sidebar-item" data-section="students" onclick="scrollToSection('students')">
                <span class="material-symbols-outlined">school</span> Students
            </li>
            <li class="sidebar-item" data-section="attendance" onclick="scrollToSection('attendance')">
                <span class="material-symbols-outlined">fact_check</span> Attendance
            </li>
            <li class="sidebar-item" data-section="schedule" onclick="scrollToSection('schedule')">
                <span class="material-symbols-outlined">calendar_month</span> Schedule
            </li>
            <li class="sidebar-item" data-section="announcements" onclick="scrollToSection('announcements')">
                <span class="material-symbols-outlined">campaign</span> Announcements
            </li>
        </ul>
    </div>

    <!-- MAIN -->
    <main class="t-main">
        <div class="t-topbar">
            <h1>TEACHER <span>DASHBOARD</span></h1>
            <div style="display:flex;gap:15px;align-items:center;">
                <div class="t-notif-wrapper">
                    <button class="t-icon-btn" id="notifBtn" style="color:#555">
                        <span class="material-symbols-outlined" style="font-size:18px">notifications</span>
                        <?php if ($notification_count > 0): ?>
                            <span class="notif-dot"></span>
                        <?php endif; ?>
                    </button>
                    <!-- NOTIFICATION DROPDOWN -->
                    <div class="t-notif-dropdown" id="notifDropdown">

                        <div class="t-notif-header">
                            <h3>Notifications</h3>
                        </div>

                        <div class="t-notif-body">

                            <?php if ($notification_count > 0): ?>
                                <?php foreach ($all_notifications as $notif): ?>

                                    <div class="t-notif-item">

                                        <div class="t-notif-icon">
                                            <span class="material-symbols-outlined">
                                                <?= htmlspecialchars($notif['icon']) ?>
                                            </span>
                                        </div>

                                        <div>
                                            <p class="t-notif-title"><?= htmlspecialchars($notif['title']) ?></p>
                                            <p class="t-notif-desc"><?= htmlspecialchars($notif['description']) ?></p>
                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <div class="t-notif-empty">
                                    <p>No new notifications</p>
                                </div>

                            <?php endif; ?>

                        </div>

                    </div>
                </div>
                <a href="login.php" style="color:#555;font-size:13px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:18px">logout</span> Logout
                </a>
            </div>
        </div>

        <section class="dashboard">
            <h1 style="color: #444;">Welcome, <?= htmlspecialchars($teacher_name) ?></h1>
            <p style="color: #666; margin-bottom: 30px;">Manage your classes and students</p>

            <!-- ══ HOME TAB ══════════════════════════════════════════ -->
            <div id="tab-home">
                <div id="home" class="content-section section-spy">
                    <h2>Overview</h2>
                    <!-- Stats -->
                    <div class="t-stats">
                        <div class="t-stat">
                            <div class="t-stat-icon">👨‍🎓</div>
                            <div class="t-stat-num"><?= $total_students ?></div>
                            <div class="t-stat-label">Total Students</div>
                            <span class="t-tag green">Enrolled</span>
                        </div>
                        <div class="t-stat">
                            <div class="t-stat-icon">✅</div>
                            <div class="t-stat-num"><?= $avg_attendance ?><span style="font-size:16px">%</span></div>
                            <div class="t-stat-label">Avg Attendance</div>
                            <span class="t-tag <?= $avg_attendance >= 80 ? 'green' : '' ?>"><?= $avg_attendance >= 80 ? 'Good' : 'Average' ?></span>
                        </div>
                        <div class="t-stat">
                            <div class="t-stat-icon">📝</div>
                            <div class="t-stat-num"><?= $pending_grades ?></div>
                            <div class="t-stat-label">Pending Grades</div>
                            <span class="t-tag">To Submit</span>
                        </div>
                        <div class="t-stat">
                            <div class="t-stat-icon">📅</div>
                            <div class="t-stat-num">5</div>
                            <div class="t-stat-label">Classes Today</div>
                            <span class="t-tag green">On Track</span>
                        </div>
                    </div>
                </div>

                <div id="students" class="content-section section-spy">
                    <h2>Student Records & Grades</h2>
                </div>
                <div class="t-grid-2">
                    <!-- Student Records -->
                    <div class="t-panel">
                        <div class="t-panel-title"><span class="material-symbols-outlined" style="font-size:16px">school</span> Student Records &amp; Grades</div>
                        <div class="tab-bar">
                            <button class="tab-btn active" onclick="filterGrades('all',this)">All</button>
                            <button class="tab-btn" onclick="filterGrades('pass',this)">Passing</button>
                            <button class="tab-btn" onclick="filterGrades('risk',this)">At Risk</button>
                        </div>
                        <table class="t-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>User ID</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Academic Status</th>
                                    <th width="100" style="text-align:center">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($students)): ?>
                                    <?php foreach ($students as $st):
                                        // Simulate grade from content or assign placeholder
                                        $uname = $st['username'] ?? '';
                                        
                                        // Check for Profile Overrides (Email/Phone)
                                        $prof_html = $profile_map[$uname] ?? '';
                                        $display_email = extract_info_from_html($prof_html, 'Email') ?? $st['email'] ?? '—';
                                        $display_phone = extract_info_from_html($prof_html, 'Phone') ?? $st['phone'] ?? '—';
                                        $display_id = extract_info_from_html($prof_html, 'ID') ?? '—';
                                        $display_status = extract_info_from_html($prof_html, 'Status') ?? 'Active';

                                        // Extract Grade from Subjects content
                                        $subj_html = $grades_map[$uname] ?? '';
                                        $grade_label = extract_info_from_html($subj_html, 'Grade') ?? ($subj_html ? 'Submitted' : 'N/A');
                                        
                                        // Determine badge color
                                        $grade_class = match ($grade_label) {
                                            'A' => 'g-a',
                                            'B' => 'g-b',
                                            'C' => 'g-c',
                                            'F' => 'g-f',
                                            default => 'g-na'
                                        };
                                    ?>
                                        <tr data-grade="<?= $grade_label ?>">
                                            <td><?= htmlspecialchars($st['student_name']) ?></td>
                                            <td style="font-size:13px;color:#666"><?= htmlspecialchars($display_id) ?></td>
                                            <td style="font-size:13px;color:#666"><?= htmlspecialchars($display_email) ?></td>
                                            <td style="font-size:13px;color:#666"><?= htmlspecialchars($display_phone) ?></td>
                                            <td style="font-size:13px;color:#666"><?= htmlspecialchars($display_status) ?></td>
                                            <td style="text-align:center"><span class="g-badge <?= $grade_class ?>"><?= $grade_label ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:#999;padding:20px">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Right column -->
                    <div style="display:flex;flex-direction:column;gap:22px;">
                        <!-- Attendance -->
                        <div class="t-panel section-spy" id="attendance">
                            <div class="t-panel-title"><span class="material-symbols-outlined" style="font-size:16px">bar_chart</span> Attendance Overview</div>
                            <?php if (!empty($students)): ?>
                                <?php foreach (array_slice($students, 0, 6) as $st):
                                    $pct = 0; // Default percentage
                                    $username = $st['username'] ?? '';
                                    $attendance_html = $attendance_map[$username] ?? '';

                                    $pct = extract_attendance_pct($attendance_html);

                                    $good = $pct >= 80;
                                ?>
                                    <div class="att-row">
                                        <span class="att-name"><?= htmlspecialchars(explode(' ', $st['student_name'])[0]) ?></span>
                                        <div class="att-wrap">
                                            <div class="att-fill <?= $good ? 'good' : '' ?>" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <span class="att-pct"><?= $pct ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:#999;font-size:13px">No attendance data.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Today's Schedule -->
                        <div class="t-panel section-spy" id="schedule">
                            <div class="t-panel-title"><span class="material-symbols-outlined" style="font-size:16px">calendar_today</span> Today's Schedule</div>
                            <?php if (!empty($schedule_html)): ?>
                                <?= $schedule_html ?>
                            <?php else: ?>
                                <div class="sch-row"><span class="sch-time">7:30 AM</span><span class="sch-dot"></span>
                                    <div>
                                        <div class="sch-subj">Mathematics 5-A</div>
                                        <div class="sch-room">Room 101</div>
                                    </div>
                                </div>
                                <div class="sch-row"><span class="sch-time">9:00 AM</span><span class="sch-dot b"></span>
                                    <div>
                                        <div class="sch-subj">Mathematics 5-B</div>
                                        <div class="sch-room">Room 102</div>
                                    </div>
                                </div>
                                <div class="sch-row"><span class="sch-time">1:00 PM</span><span class="sch-dot g"></span>
                                    <div>
                                        <div class="sch-subj">Math Club</div>
                                        <div class="sch-room">Library</div>
                                    </div>
                                </div>
                                <div class="sch-row"><span class="sch-time">3:00 PM</span><span class="sch-dot"></span>
                                    <div>
                                        <div class="sch-subj">Parent Meeting</div>
                                        <div class="sch-room">Conference</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>


                    <!-- Announcements -->
                    <div class="t-panel section-spy" id="announcements">
                        <div class="t-panel-title"><span class="material-symbols-outlined" style="font-size:16px">campaign</span> Announcements</div>

                        <!-- Post Announcement Form -->
                        <form method="POST" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                            <input type="text" name="announce_title" placeholder="Title" required style="width: 100%; padding: 10px; margin-bottom: 10px; background: #f9f9f9; border: 1px solid #ddd; color: #333; border-radius: 5px;">
                            <textarea name="announce_desc" placeholder="Write announcement for students..." required style="width: 100%; padding: 10px; margin-bottom: 10px; background: #f9f9f9; border: 1px solid #ddd; color: #333; border-radius: 5px; min-height: 60px;"></textarea>
                            <button type="submit" style="background: var(--accent); color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">Post to Students</button>
                        </form>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px;">
                            <?php if (!empty($notices) || !empty($events)): ?>
                                <?php if (!empty($notices)): ?>
                                    <?php foreach ($notices as $n): ?>
                                        <div class="ann-item">
                                            <div class="ann-icon">📌</div>
                                            <div>
                                                <div class="ann-title"><?= htmlspecialchars($n['title']) ?></div>
                                                <div class="ann-desc"><?= $n['description'] ?? $n['detailed_content'] ?? '' ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (!empty($events)): ?>
                                    <?php foreach ($events as $e): ?>
                                        <div class="ann-item">
                                            <div class="ann-icon" style="background:rgba(52,152,219,.18)">📅</div>
                                            <div>
                                                <div class="ann-title"><?= htmlspecialchars($e['title']) ?></div>
                                                <div class="ann-desc"><?= $e['description'] ?> <br><small><?= htmlspecialchars($e['event_date']) ?></small></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="ann-item">
                                    <div class="ann-icon">📅</div>
                                    <div>
                                        <div class="ann-title">Quarterly Exam Schedule</div>
                                        <div class="ann-desc">Q3 exams are set for March 15–18. Please prepare review materials.</div>
                                    </div>
                                </div>
                                <div class="ann-item">
                                    <div class="ann-icon" style="background:rgba(52,152,219,.18)">📝</div>
                                    <div>
                                        <div class="ann-title">Grade Submission Deadline</div>
                                        <div class="ann-desc">All grades must be submitted to the registrar by Friday, 5:00 PM.</div>
                                    </div>
                                </div>
                                <div class="ann-item">
                                    <div class="ann-icon" style="background:rgba(46,213,115,.15)">🏫</div>
                                    <div>
                                        <div class="ann-title">Faculty Meeting</div>
                                        <div class="ann-desc">Monthly faculty meeting — Monday, 4:00 PM at the AVR.</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div><!-- end #tab-home -->

                <!-- ══ STUDENTS TAB ══════════════════════════════════════ -->
                <div id="tab-students" style="display:none">
                    <div class="t-panel">
                        <div class="t-panel-title"><span class="material-symbols-outlined" style="font-size:16px">school</span> All Students</div>
                        <table class="t-table">
                            <thead>
                                <tr>
                                    <th width="60">#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th width="100" style="text-align:center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($students)): ?>
                                    <?php foreach ($students as $i => $st): ?>
                                        <tr>
                                            <td style="color:#999"><?= $i + 1 ?></td>
                                            <td><?= htmlspecialchars($st['student_name']) ?></td>
                                            <td style="font-size:13px;color:#666"><?= htmlspecialchars($st['email'] ?? '—') ?></td>
                                            <td style="font-size:13px;color:#666"><?= htmlspecialchars($st['phone'] ?? '—') ?></td>
                                            <td style="text-align:center"><span class="g-badge g-a">Active</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;color:#999;padding:20px">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ══ ATTENDANCE TAB ════════════════════════════════════ -->
                <div id="tab-attendance" style="display:none">
                    <div class="t-panel">
                        <div class="t-panel-title"><span class="material-symbols-outlined" style="font-size:16px">fact_check</span> Attendance — All Students</div>
                        <div class="content-section">
                            <h2>Attendance — All Students</h2>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $st):
                                    $pct = 0; // Default percentage
                                    $username = $st['username'] ?? '';
                                    $attendance_html = $attendance_map[$username] ?? '';

                                    $pct = extract_attendance_pct($attendance_html);

                                    $good = $pct >= 80;
                                ?>
                                    <div class="att-row" style="margin-bottom:16px">
                                        <span class="att-name" style="width:180px"><?= htmlspecialchars($st['student_name']) ?></span>
                                        <div class="att-wrap">
                                            <div class="att-fill <?= $good ? 'good' : '' ?>" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <span class="att-pct"><?= $pct ?>%</span>
                                        <span style="font-size:11px;color:<?= $good ? '#7effc8' : '#ff9090' ?>;margin-left:8px"><?= $good ? 'Good' : 'Low' ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:#999">No attendance data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ══ SCHEDULE TAB ══════════════════════════════════════ -->
                <div id="tab-schedule" style="display:none">
                    <div class="t-panel">
                        <div class="t-panel-title"><span class="material-symbols-outlined" style="font-size:16px">calendar_month</span> Weekly Class Schedule</div>
                        <div class="content-section">
                            <table class="t-table">
                                <h2>Weekly Class Schedule</h2>
                                <?php if (!empty($schedule_html)): ?>
                                    <?= $schedule_html ?>
                                <?php else: ?>
                                    <table class="t-table">
                                        <thead>
                                            <tr>
                                                <th>Day</th>
                                                <th>Time</th>
                                                <th>Subject</th>
                                                <th>Room</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Monday</td>
                                                <td>7:30 AM</td>
                                                <td>Mathematics 5-A</td>
                                                <td>Room 101</td>
                                            </tr>
                                            <tr>
                                                <td>Monday</td>
                                                <td>9:00 AM</td>
                                                <td>Mathematics 5-B</td>
                                                <td>Room 102</td>
                                            </tr>
                                            <tr>
                                                <td>Tuesday</td>
                                                <td>7:30 AM</td>
                                                <td>Mathematics 6-A</td>
                                                <td>Room 103</td>
                                            </tr>
                                            <tr>
                                                <td>Tuesday</td>
                                                <td>9:00 AM</td>
                                                <td>Mathematics 6-B</td>
                                                <td>Room 104</td>
                                            </tr>
                                            <tr>
                                                <td>Wednesday</td>
                                                <td>1:00 PM</td>
                                                <td>Math Club</td>
                                                <td>Library</td>
                                            </tr>
                                            <tr>
                                                <td>Thursday</td>
                                                <td>7:30 AM</td>
                                                <td>Mathematics 5-C</td>
                                                <td>Room 101</td>
                                            </tr>
                                            <tr>
                                                <td>Friday</td>
                                                <td>3:00 PM</td>
                                                <td>Parent Meeting</td>
                                                <td>Conference</td>
                                            </tr>
                                        </tbody>
                                    </table>
                        </div>
                    <?php endif; ?>
                    </div>

                    <!-- ══ ANNOUNCEMENTS TAB ═════════════════════════════════ -->
                    <div id="tab-announcements" style="display:none">
                        <div class="t-panel">
                            <div class="t-panel-title"><span class="material-symbols-outlined" style="font-size:16px">campaign</span> All Announcements</div>
                            <div class="content-section">
                                <h2>Announcements</h2>
                                <!-- Post Announcement Form -->
                                <form method="POST" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                                    <input type="text" name="announce_title" placeholder="Title" required style="width: 100%; padding: 10px; margin-bottom: 10px; background: #f9f9f9; border: 1px solid #ddd; color: #333; border-radius: 5px;">
                                    <textarea name="announce_desc" placeholder="Write announcement for students..." required style="width: 100%; padding: 10px; margin-bottom: 10px; background: #f9f9f9; border: 1px solid #ddd; color: #333; border-radius: 5px; min-height: 60px;"></textarea>
                                    <button type="submit" style="background: var(--accent); color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">Post to Students</button>
                                </form>

                                <?php if (!empty($notices) || !empty($events)): ?>
                                    <?php if (!empty($notices)): ?>
                                        <?php foreach ($notices as $n): ?>
                                            <div class="ann-item" style="padding:16px 0">
                                                <div class="ann-icon">📌</div>
                                                <div>
                                                    <div class="ann-title"><?= htmlspecialchars($n['title']) ?></div>
                                                <div class="ann-desc"><?= $n['description'] ?? $n['detailed_content'] ?? '' ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($events)): ?>
                                        <?php foreach ($events as $e): ?>
                                            <div class="ann-item" style="padding:16px 0">
                                                <div class="ann-icon" style="background:rgba(52,152,219,.18)">📅</div>
                                                <div>
                                                    <div class="ann-title"><?= htmlspecialchars($e['title']) ?></div>
                                                <div class="ann-desc"><?= $e['description'] ?> <br><small><?= htmlspecialchars($e['event_date']) ?></small></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="ann-item">
                                        <div class="ann-icon">📅</div>
                                        <div>
                                            <div class="ann-title">Quarterly Exam Schedule</div>
                                            <div class="ann-desc">Q3 exams set for March 15–18.</div>
                                        </div>
                                    </div>
                                    <div class="ann-item">
                                        <div class="ann-icon">📝</div>
                                        <div>
                                            <div class="ann-title">Grade Submission Deadline</div>
                                            <div class="ann-desc">Registrar deadline: Friday 5:00 PM.</div>
                                        </div>
                                    </div>
                                    <div class="ann-item">
                                        <div class="ann-icon">🏫</div>
                                        <div>
                                            <div class="ann-title">Faculty Meeting</div>
                                            <div class="ann-desc">Monday 4:00 PM — AVR.</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

        </section>
    </main>

    <script>
        function showTab(id, el) {
            document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
            document.getElementById('tab-' + id).style.display = 'block';
            document.querySelectorAll('.sidebar-item').forEach(a => a.classList.remove('active'));
            el.classList.add('active');
        }

        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        let isScrolling = false;

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
                // Prevent scroll spy from overriding during animation
                setTimeout(() => {
                    isScrolling = false;
                }, 1000);
            }
        }

        const sections = document.querySelectorAll(".section-spy");
        const sidebarItems = document.querySelectorAll(".sidebar-item");

        window.addEventListener("scroll", () => {
            if (isScrolling) return;
            let current = "";
            sections.forEach(section => {
                if (section.offsetParent !== null) { // Check visibility
                    if (window.scrollY >= section.offsetTop - 150) current = section.getAttribute("id");
                }
            });
            sidebarItems.forEach(item => {
                item.classList.remove("active");
                if (item.dataset.section === current) item.classList.add("active");
            });
        });

        window.onclick = function(event) {
            if (!event.target.closest('.profile-dropdown')) {
                document.getElementById('profileMenu').style.display = 'none';
            }
        }

        function filterGrades(type, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.t-table tbody tr[data-grade]').forEach(row => {
                const g = row.dataset.grade;
                if (type === 'all') row.style.display = '';
                else if (type === 'pass') row.style.display = ['A', 'B', 'C'].includes(g) ? '' : 'none';
                else if (type === 'risk') row.style.display = ['F', 'N/A'].includes(g) ? '' : 'none';
            });
        }

        // Notification Dropdown Toggle
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');

        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', function(event) {
                event.stopPropagation();
                notifDropdown.classList.toggle('show');
            });

            window.addEventListener('click', function(event) {
                if (!notifDropdown.contains(event.target) && !notifBtn.contains(event.target)) {
                    notifDropdown.classList.remove('show');
                }
            });
        }
    </script>
</body>

</html>