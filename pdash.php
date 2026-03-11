<?php
session_start();
require "db.php";

// Auth check — only principal/admin allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'principle') {
    header("Location: login.php");
    exit;
}

$principal_name = $_SESSION['user_name'] ?? 'Principal';
$profile_pic = $_SESSION['profile_pic'] ?? '';

// Fetch latest profile pic if not in session or to be sure
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) $profile_pic = $row['profile_pic'];

// Helper to extract attendance percentage safely
if (!function_exists('extract_attendance_pct')) {
    function extract_attendance_pct($html)
    {
        if (empty($html)) return 0;
        if (preg_match('/class=["\']stat-big["\'][^>]*>\s*([\d\.]+)\s*%/i', $html, $m)) {
            return (float)$m[1];
        }
        $text = strip_tags($html);
        if (preg_match('/([\d\.]+)\s*%/', $text, $m)) {
            return (float)$m[1];
        }
        return 0;
    }
}

// Helper to extract email/phone from HTML content
function extract_info_from_html($html, $label)
{
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

// ── School-wide stats ────────────────────────────────────────────────────────

// Total students
$total_students = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM admissions");
if ($r) $total_students = $r->fetch_assoc()['cnt'];

// Total teachers
$total_teachers = 0;
$r2 = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'teacher'");
if ($r2) $total_teachers = $r2->fetch_assoc()['cnt'];

// Total classes
$total_classes = 0;
$r3 = $conn->query("SELECT COUNT(DISTINCT student_class) AS cnt FROM admissions");
if ($r3) $total_classes = $r3->fetch_assoc()['cnt'];

// School-wide average attendance
$total_pct = 0;
$count_with_data = 0;
$att_res = $conn->query("SELECT ssc.content FROM student_section_content ssc JOIN users u ON ssc.username = u.username WHERE ssc.section_id = 'attendance' AND u.role = 'student'");
if ($att_res) {
    while ($row = $att_res->fetch_assoc()) {
        $val = extract_attendance_pct($row['content']);
        if ($val > 0) {
            $total_pct += $val;
            $count_with_data++;
        }
    }
}
$avg_attendance = $count_with_data > 0 ? round($total_pct / $count_with_data) : 0;

// Attendance by Grade
$grades_att = [];
$sql = "SELECT a.student_class, ssc.content 
        FROM admissions a 
        JOIN users u ON a.student_name = u.name 
        JOIN student_section_content ssc ON u.username = ssc.username 
        WHERE u.role = 'student' AND ssc.section_id = 'attendance'";
$att_by_grade_res = $conn->query($sql);
if ($att_by_grade_res) {
    $class_data = [];
    while ($row = $att_by_grade_res->fetch_assoc()) {
        $class = $row['student_class'];
        $pct = extract_attendance_pct($row['content']);
        if ($pct > 0) {
            if (!isset($class_data[$class])) {
                $class_data[$class] = ['total_pct' => 0, 'count' => 0];
            }
            $class_data[$class]['total_pct'] += $pct;
            $class_data[$class]['count']++;
        }
    }
    foreach ($class_data as $class_name => $data) {
        if ($data['count'] > 0) {
            $grades_att[$class_name] = round($data['total_pct'] / $data['count']);
        }
    }
    arsort($grades_att);
}

// Principal's Schedule
$schedule_html = '';
$sch_stmt = $conn->prepare("SELECT content FROM student_section_content WHERE username = (SELECT username FROM users WHERE id = ?) AND section_id = 'schedule'");
$sch_stmt->bind_param("i", $_SESSION['user_id']);
$sch_stmt->execute();
$sch_res = $sch_stmt->get_result();
if ($row = $sch_res->fetch_assoc()) {
    $schedule_html = $row['content'];
}

// All teachers list
$teachers = [];
$t_res = $conn->query("SELECT id, name, username FROM users WHERE role = 'teacher' ORDER BY name ASC");
if ($t_res) {
    while ($row = $t_res->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// All students list
$students = [];
// We join users with admissions on name to get all details.
$s_res = $conn->query("SELECT
    u.name as student_name, u.username, a.email, a.phone
    FROM users u
    LEFT JOIN admissions a ON u.name = a.student_name
    WHERE u.role = 'student' ORDER BY u.name ASC");
if ($s_res) {
    while ($row = $s_res->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch profile data (for overrides)
$profile_map = [];
$prof_stmt = $conn->prepare("SELECT username, content FROM student_section_content WHERE section_id = 'profile'");
if ($prof_stmt) {
    $prof_stmt->execute();
    $prof_res = $prof_stmt->get_result();
    while ($row = $prof_res->fetch_assoc()) {
        $profile_map[$row['username']] = $row['content'];
    }
}

// Announcements from dashboard_cards
$notices = [];
$n_res = $conn->query("SELECT * FROM dashboard_cards WHERE section_id = 'notices' ORDER BY id DESC LIMIT 10");
if ($n_res) {
    while ($row = $n_res->fetch_assoc()) {
        $notices[] = $row;
    }
}

// ── Fetch Events (from Admin) ───────────────────────────────────────────────
$events = [];
$e_res = $conn->query("SELECT * FROM events ORDER BY event_date DESC LIMIT 10");
if ($e_res) {
    while ($row = $e_res->fetch_assoc()) {
        $events[] = $row;
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

// Post new announcement (principal only)
$post_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ann_title'], $_POST['ann_body'])) {
    $title = trim($_POST['ann_title']);
    $desc  = trim($_POST['ann_body']);

    if ($title && $desc) {
        $date = date('Y-m-d');
        $time = date('H:i');
        $type = 'Announcement';
        $status = 'active';
        $location = 'Principal: ' . ($principal_name ?? 'Principal');

        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_time, type, status, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $title, $desc, $date, $time, $type, $status, $location);

        if ($stmt->execute()) {
            header("Location: pdash.php?tab=announcements&posted=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Principal Dashboard | RC Middle School</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
    <link rel="stylesheet" href="style.css" />
    <style>
        :root {
            --primary: #1f4e79;
            --gold: #f5a623;
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
        }

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
            background: var(--gold);
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
            border: 3px solid var(--gold);
        }

        .sidebar-profile .p-avatar-initials {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #e8821a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 28px;
            color: #fff;
            margin: 0 auto 10px;
            border: 3px solid var(--gold);
        }

        .sidebar-profile .p-name {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--text);
        }

        .sidebar-profile .p-role {
            font-size: 0.9rem;
            color: #777;
        }

        /* ── Main ── */
        .p-main {
            margin-left: 240px;
            padding: 36px 40px;
            min-height: 100vh;
        }

        .p-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .p-topbar h1 {
            font-size: 26px;
            font-weight: 300;
            letter-spacing: 3px;
        }

        .p-topbar h1 span {
            color: var(--gold);
            font-weight: 600;
        }

        /* ── KPI ── */
        .p-kpis {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 26px;
        }

        .p-kpi {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 20px 18px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: .3s;
            animation: pFade .5s ease both;
            overflow: hidden;
        }

        .p-kpi:nth-child(1) {
            animation-delay: .05s
        }

        .p-kpi:nth-child(2) {
            animation-delay: .10s
        }

        .p-kpi:nth-child(3) {
            animation-delay: .15s
        }

        .p-kpi:nth-child(4) {
            animation-delay: .20s
        }

        .p-kpi:nth-child(5) {
            animation-delay: .25s
        }

        .p-kpi:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: rgba(245, 166, 35, .3);
        }

        .p-kpi-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .p-kpi-num {
            font-size: 30px;
            font-weight: 700;
            line-height: 1;
            color: var(--primary);
        }

        .p-kpi-label {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .p-tag {
            display: inline-block;
            margin-top: 8px;
            font-size: 11px;
            padding: 3px 9px;
            border-radius: 20px;
            background: rgba(245, 166, 35, .15);
            color: #f1c40f;
        }

        .p-tag.green {
            background: rgba(46, 213, 115, .15);
            color: #2ed573;
        }

        .p-tag.red {
            background: rgba(233, 69, 96, .18);
            color: #ff9090;
        }

        /* Notification Button & Dropdown */
        .p-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .1);
            color: #555;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: .2s;
            font-size: 17px;
            position: relative;
        }

        .p-icon-btn:hover {
            background: #eee;
            transform: translateY(-2px);
        }

        .p-notif-wrapper {
            position: relative;
        }

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

        .p-notif-dropdown {
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

        .p-notif-dropdown.show {
            display: block;
        }

        .p-notif-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }

        .p-notif-body {
            max-height: 350px;
            overflow-y: auto;
        }

        .p-notif-item {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #f5f5f5;
        }

        .p-notif-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f4f8;
        }

        .p-notif-title {
            font-size: 13px;
            font-weight: 600;
        }

        .p-notif-desc {
            font-size: 12px;
            color: #777;
        }

        .p-notif-empty {
            padding: 20px;
            text-align: center;
            color: #999;
            font-size: 13px;
        }

        /* ── Panels ── */
        .p-grid-3 {
            display: grid;
            grid-template-columns: auto-fit, minmax(300px, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .p-grid-2 {
            display: grid;

            gap: 20px;
            margin-bottom: 20px;
        }

        .p-panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            animation: pFade .6s ease both;
        }

        .p-panel-title {
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

        .content-section {
            background: transparent;
            box-shadow: none;
            padding: 0;
            margin: 0;
            color: var(--text);
        }

        .content-section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* ── Table ── */
        .p-table,
        #schedule .p-panel table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .p-table th,
        #schedule .p-panel table th {

            padding: 12px 15px;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #888;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }

        .p-table td,
        #schedule .p-panel table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f5f5f5;
            color: #444;
            vertical-align: middle;
        }

        .p-table tr:last-child td,
        #schedule .p-panel table tr:last-child td {
            border-bottom: none;
        }

        .p-table tr:hover td,
        #schedule .p-panel table tr:hover td {
            background: #f9f9f9;
        }

        .pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .pill-green {
            background: rgba(46, 213, 115, .15);
            color: #2ed573;
        }

        .pill-red {
            background: rgba(233, 69, 96, .18);
            color: #ff9090;
        }

        .pill-gold {
            background: rgba(245, 166, 35, .18);
            color: #f1c40f;
        }

        /* -- Action Button in Table -- */
        .p-btn-action {
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid var(--gold);
            background: transparent;
            color: var(--gold);
            font-size: 12px;
            cursor: pointer;
            transition: .2s;
        }

        .p-btn-action:hover {
            background: var(--gold);
            color: white;
        }

        /* -- Modal Styles -- */
        .p-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 2000;
            animation: pFade .3s ease-out;
        }

        .p-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .p-modal-content {
            background: var(--white);
            padding: 0;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 95%;
            animation: pFade .4s ease-out;
        }

        .p-modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ── Attendance bars ── */
        .bar-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 13px;
        }

        .bar-lbl {
            font-size: 13px;
            width: 65px;
            flex-shrink: 0;
            color: #444;
        }

        .bar-wrap {
            flex: 1;
            height: 10px;
            background: #eee;
            border-radius: 99px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--gold), #ff6b6b);
        }

        .bar-fill.good {
            background: linear-gradient(90deg, #2ed573, #7effc8);
        }

        .bar-pct {
            font-size: 12px;
            color: #666;
            width: 38px;
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
            color: var(--gold);
            width: 72px;
            flex-shrink: 0;
            font-weight: 600;
        }

        .sch-marker {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--gold);
            box-shadow: 0 0 7px rgba(245, 166, 35, .5);
            flex-shrink: 0;
        }

        .sch-subj {
            font-size: 13.5px;
            font-weight: 600;
            color: #333;
        }

        .sch-room {
            font-size: 12px;
            color: #888;
        }

        /* ── Ann ── */
        .ann-row {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .ann-row:last-child {
            border-bottom: none;
        }

        .ann-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--gold);
            box-shadow: 0 0 8px rgba(245, 166, 35, .5);
            margin-top: 5px;
            flex-shrink: 0;
        }

        .ann-dot.r {
            background: var(--accent);
            box-shadow: 0 0 8px rgba(233, 69, 96, .4);
        }

        .ann-dot.b {
            background: #7dd3fc;
            box-shadow: 0 0 8px rgba(125, 211, 252, .35);
        }

        .ann-title {
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 3px;
            color: #333;
        }

        .ann-meta {
            font-size: 12px;
            color: #666;
        }

        .ann-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .ann-item {
            display: flex;
            gap: 15px;
            padding: 16px;
            border-radius: 12px;
            background: #fdfdfd;
            border: 1px solid #f0f0f0;
            align-items: flex-start;
        }

        .ann-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        /* ── Form ── */
        .p-input {
            width: 100%;
            padding: 11px 14px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
            color: #333;
            font-size: 14px;
            font-family: inherit;
            margin-bottom: 12px;
            outline: none;
            transition: .2s;
        }

        .p-input:focus {
            border-color: var(--gold);
            background: #fff;
        }

        .p-input::placeholder {
            color: #999;
        }

        .p-btn-gold {
            padding: 10px 24px;
            border-radius: 10px;
            background: var(--gold);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
            transition: .2s;
            width: 100%;
        }

        .p-btn-gold:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* ── Tab Buttons (Like tdash) ── */
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
            background: var(--gold);
            border-color: var(--gold);
            color: #fff;
            transform: none;
        }

        /* ── Keyframes ── */
        @keyframes pFade {
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
        @media (max-width:1200px) {
            .p-kpis {
                grid-template-columns: repeat(3, 1fr);
            }

            .p-grid-3 {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width:900px) {
            .p-kpis {
                grid-template-columns: repeat(2, 1fr);
            }

            .p-grid-3,
            .p-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width:700px) {
            .custom-sidebar {
                width: 200px;
            }

            .dashboard {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
            }

            .p-main {
                margin-left: 200px;
                padding: 18px 14px;
            }

            .p-kpis {
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
                <li><a href="pdash.php" class="nav-link active" style="text-decoration: none; color: var(--primary); font-weight: bold; display: flex; align-items: center; gap: 5px;"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="profile-dropdown" style="position: relative;">
                    <a href="javascript:void(0);" onclick="toggleProfileMenu()" style="text-decoration: none; color: #555; display: flex; align-items: center; gap: 5px;">
                        <?php if (!empty($profile_pic)): ?>
                            <img src="uploads/<?= htmlspecialchars($profile_pic) ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <span class="material-symbols-outlined">account_circle</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($principal_name) ?>
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
                <div class="p-avatar-initials"><?= strtoupper(substr($principal_name, 0, 1)) ?></div>
            <?php endif; ?>
            <div class="p-name"><?= htmlspecialchars($principal_name) ?></div>
            <div class="p-role">Principal</div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item active" data-section="home" onclick="scrollToSection('home')"><span class="material-symbols-outlined">dashboard</span> Overview</li>
            <li class="sidebar-item" data-section="teachers" onclick="scrollToSection('teachers')"><span class="material-symbols-outlined">people</span> Teachers</li>
            <li class="sidebar-item" data-section="students" onclick="scrollToSection('students')"><span class="material-symbols-outlined">school</span> Students</li>
            <li class="sidebar-item" data-section="attendance" onclick="scrollToSection('attendance')"><span class="material-symbols-outlined">fact_check</span> Attendance</li>
            <li class="sidebar-item" data-section="schedule" onclick="scrollToSection('schedule')"><span class="material-symbols-outlined">calendar_month</span> Schedule</li>
            <li class="sidebar-item" data-section="announcements" onclick="scrollToSection('announcements')"><span class="material-symbols-outlined">campaign</span> Announcements</li>
        </ul>
    </div>

    <!-- MAIN -->
    <main class="p-main">
        <div class="p-topbar">
            <h1>PRINCIPAL <span>DASHBOARD</span></h1>
            <div style="display:flex;gap:15px;align-items:center">
                <div class="p-notif-wrapper">
                    <button class="p-icon-btn" id="notifBtn">
                        <span class="material-symbols-outlined" style="font-size:18px">notifications</span>
                        <?php if ($notification_count > 0): ?>
                            <span class="notif-dot"></span>
                        <?php endif; ?>
                    </button>
                    <!-- NOTIFICATION DROPDOWN -->
                    <div class="p-notif-dropdown" id="notifDropdown">
                        <div class="p-notif-header">
                            <h3>Notifications</h3>
                        </div>
                        <div class="p-notif-body">
                            <?php if ($notification_count > 0): ?>
                                <?php foreach ($all_notifications as $notif): ?>
                                    <div class="p-notif-item">
                                        <div class="p-notif-icon">
                                            <span class="material-symbols-outlined">
                                                <?= htmlspecialchars($notif['icon']) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="p-notif-title"><?= htmlspecialchars($notif['title']) ?></p>
                                            <p class="p-notif-desc"><?= htmlspecialchars($notif['description']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-notif-empty">
                                    <p>No new notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a href="login.php" style="color:#555;font-size:13px;text-decoration:none;display:flex;align-items:center;gap:6px">
                    <span class="material-symbols-outlined" style="font-size:18px">logout</span> Logout
                </a>
            </div>
        </div>
        <section class="dashboard">
            <h1 style="color: #444;">Welcome, <?= htmlspecialchars($principal_name) ?></h1>
            <p style="color: #666; margin-bottom: 30px;">School Overview & Management</p>

            <div id="home" class="content-section">
                <h2>Overview</h2>
                <div class="p-kpis">
                    <div class="p-kpi">
                        <div class="p-kpi-icon">🏫</div>
                        <div class="p-kpi-num"><?= $total_classes ?></div>
                        <div class="p-kpi-label">Total Classes</div>
                        <span class="p-tag green">Active</span>
                    </div>
                    <div class="p-kpi">
                        <div class="p-kpi-icon">👨‍🏫</div>
                        <div class="p-kpi-num"><?= $total_teachers ?></div>
                        <div class="p-kpi-label">Teachers</div>
                        <span class="p-tag green">Registered</span>
                    </div>
                    <div class="p-kpi">
                        <div class="p-kpi-icon">👨‍🎓</div>
                        <div class="p-kpi-num"><?= $total_students ?></div>
                        <div class="p-kpi-label">Students</div>
                        <span class="p-tag green">Enrolled</span>
                    </div>
                    <div class="p-kpi">
                        <div class="p-kpi-icon">✅</div>
                        <div class="p-kpi-num"><?= $avg_attendance ?><span style="font-size:15px">%</span></div>
                        <div class="p-kpi-label">Avg. Attendance</div>
                        <span class="p-tag green">School-wide</span>
                    </div>
                    <div class="p-kpi">
                        <div class="p-kpi-icon">📢</div>
                        <div class="p-kpi-num"><?= count($notices) + count($events) ?></div>
                        <div class="p-kpi-label">Announcements</div>
                        <span class="p-tag">Posted</span>
                    </div>
                </div>
            </div>

            <div id="teachers" class="content-section">
                <div class="p-panel">
                    <div class="p-panel-title"><span class="material-symbols-outlined" style="font-size:16px">people</span> All Teachers</div>
                    <div class="tab-bar">
                        <button class="tab-btn active" onclick="filterRows('all', this, 'teachersTable')">All</button>
                        <button class="tab-btn" onclick="filterRows('active', this, 'teachersTable')">Active</button>
                    </div>
                    <table class="p-table" id="teachersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Actions</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($teachers)): ?>
                                <?php foreach ($teachers as $i => $t): ?>
                                    <tr data-status="active">
                                        <td style="color:#999"><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($t['name']) ?></td>
                                        <td><?= htmlspecialchars($t['username'] ?? '—') ?></td>
                                        <td><button class='p-btn-action manage-user-btn' data-username='<?= htmlspecialchars($t['username']) ?>'>Manage Data</button></td>
                                        <td><span class="pill pill-green">Active</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#999;padding:20px">No teachers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="students" class="content-section">
                <div class="p-panel">
                    <div class="p-panel-title"><span class="material-symbols-outlined" style="font-size:16px">school</span> All Students</div>
                    <div class="tab-bar">
                        <button class="tab-btn active" onclick="filterRows('all', this, 'studentsTable')">All</button>
                        <button class="tab-btn" onclick="filterRows('active', this, 'studentsTable')">Active</button>
                    </div>
                    <table class="p-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Name</th>
                                <th>User ID</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Academic Status</th>
                                <th width="120" style="text-align:center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $i => $st): ?>
                                    <?php
                                    $uname = $st['username'] ?? '';
                                    $prof_html = $profile_map[$uname] ?? '';
                                    $display_email = extract_info_from_html($prof_html, 'Email') ?? $st['email'] ?? '—';
                                    $display_phone = extract_info_from_html($prof_html, 'Phone') ?? $st['phone'] ?? '—';
                                    $display_id = extract_info_from_html($prof_html, 'ID') ?? '—';
                                    $display_status = extract_info_from_html($prof_html, 'Status') ?? 'Active';
                                    ?>
                                    <tr data-status="active">
                                        <td style="color:#999"><?= $i + 1 ?></td>
                                        <td>
                                            <div style="font-weight:600"><?= htmlspecialchars($st['student_name']) ?></div>
                                        </td>
                                        <td style="font-size:13px;color:#666"><?= htmlspecialchars($display_id) ?></td>
                                        <td style="font-size:13px;color:#666"><?= htmlspecialchars($display_email) ?></td>
                                        <td style="font-size:13px;color:#666"><?= htmlspecialchars($display_phone) ?></td>
                                        <td style="font-size:13px;color:#666"><?= htmlspecialchars($display_status) ?></td>
                                        <td style="text-align:center"><button class='p-btn-action manage-user-btn' data-username='<?= htmlspecialchars($st['username']) ?>'>Manage Data</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;color:#999;padding:20px">No students found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="attendance" class="content-section">
                <div class="p-panel">
                    <div class="p-panel-title"><span class="material-symbols-outlined" style="font-size:16px">fact_check</span> School-wide Attendance</div>
                    <h2>School-wide Attendance</h2>
                    <?php foreach ($grades_att as $g => $pct): $good = $pct >= 90; ?>
                        <div class="bar-row" style="margin-bottom:18px">
                            <span class="bar-lbl" style="width:80px"><?= $g ?></span>
                            <div class="bar-wrap">
                                <div class="bar-fill <?= $good ? 'good' : '' ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="bar-pct"><?= $pct ?>%</span>
                            <span style="font-size:11px;color:<?= $good ? '#2ed573' : '#f1c40f' ?>;margin-left:10px"><?= $good ? 'Excellent' : 'Needs Attention' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="schedule" class="content-section">
                <div class="p-panel">
                    <div class="p-panel-title" style="justify-content: space-between;">
                        <span><span class="material-symbols-outlined" style="font-size:16px">calendar_month</span> Weekly Schedule</span>
                        <button class='p-btn-action' id='editScheduleBtn'>Edit</button>
                    </div>
                    <?php if (!empty($schedule_html)): ?>
                        <?= $schedule_html ?>
                    <?php else: ?>
                        <table class="p-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Activity</th>
                                    <th>Venue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Monday</td>
                                    <td>7:00 AM</td>
                                    <td>Flag Ceremony</td>
                                    <td>Quadrangle</td>
                                </tr>
                                <tr>
                                    <td>Monday</td>
                                    <td>8:30 AM</td>
                                    <td>Faculty Briefing</td>
                                    <td>Principal's Office</td>
                                </tr>
                                <tr>
                                    <td>Tuesday</td>
                                    <td>10:00 AM</td>
                                    <td>Class Observation</td>
                                    <td>Room 203</td>
                                </tr>
                                <tr>
                                    <td>Wednesday</td>
                                    <td>1:00 PM</td>
                                    <td>Parent Meeting</td>
                                    <td>Conference Room</td>
                                </tr>
                                <tr>
                                    <td>Friday</td>
                                    <td>3:30 PM</td>
                                    <td>Dept Review</td>
                                    <td>AVR</td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div id="announcements" class="content-section">
                <div class="p-panel">
                    <div class="p-panel-title"><span class="material-symbols-outlined" style="font-size:16px">campaign</span> Announcements</div>
                    <!-- Post Announcement Form -->
                    <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                        <?php if (isset($_GET['posted'])): ?>
                            <p style="color:#2ed573;margin-bottom:14px;font-size:13px">✅ Announcement posted successfully!</p>
                        <?php endif; ?>
                        <form method="POST" action="pdash.php?tab=announcements">
                            <input class="p-input" type="text" name="ann_title" placeholder="Announcement Title" required>
                            <textarea class="p-input" name="ann_body" rows="4" placeholder="Write announcement for students..." required style="resize:vertical"></textarea>
                            <button type="submit" class="p-btn-gold" style="width:auto; padding: 8px 20px;">Post to Students</button>
                        </form>
                    </div>

                    <!-- List -->
                    <h3>All Announcements</h3>
                    <?php if (!empty($notices) || !empty($events)): ?>
                        <div class="ann-grid">
                            <?php foreach ($notices as $n): ?>
                                <div class="ann-item">
                                    <div class="ann-icon" style="background: rgba(245, 166, 35, .18); color: var(--gold);">
                                        <span class="material-symbols-outlined">campaign</span>
                                    </div>
                                    <div>
                                        <div class="ann-title"><?= htmlspecialchars($n['title']) ?></div>
                                        <div class="ann-meta"><?= $n['description'] ?? $n['detailed_content'] ?? '' ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($events as $e): ?>
                                <div class="ann-item">
                                    <div class="ann-icon" style="background: rgba(52, 152, 219, .18); color: #3498db;">
                                        <span class="material-symbols-outlined">event</span>
                                    </div>
                                    <div>
                                        <div class="ann-title"><?= htmlspecialchars($e['title']) ?></div>
                                        <div class="ann-meta"><?= $e['description'] ?><span style="font-size:11px; color:#888; display:block; margin-top:2px;">📅 <?= date("M d, Y", strtotime($e['event_date'])) ?> at <?= htmlspecialchars($e['event_time']) ?></span></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:#999;font-size:13px">No announcements yet.</p>
                    <?php endif; ?>
                </div>
            </div>

    </main>

    <!-- MODAL FOR EDITING USER DATA -->
    <div class="p-modal" id="userDataModal">
        <div class="p-modal-content">
            <div class="p-modal-header">
                <h3 id="userDataModalTitle">Manage Data</h3>
                <button class="p-modal-close" data-modal="userDataModal" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <form id="userDataForm">
                <input type="hidden" id="dataUsername" name="username">
                <div style="padding: 25px; display: flex; flex-direction: column; gap: 15px;">
                    <label>Select Section to Edit:</label>
                    <select id="dataSectionId" name="section_id" required class="p-input">
                        <option value="">-- Select Section --</option>
                        <?php
                        // Re-use the query from admin.php to populate sections
                        $sec_res = $conn->query("SELECT section_id, title FROM dashboard_cards WHERE section_id NOT IN ('feedback', 'complaints')");
                        while ($sec = $sec_res->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($sec['section_id']) . "'>" . htmlspecialchars($sec['title']) . "</option>";
                        }
                        ?>
                    </select>
                    <textarea id="dataContent" name="content" rows="10" placeholder="Content will be loaded here..."></textarea>
                    <button type="submit" class="p-btn-gold">Save Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Profile menu toggle
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        let isScrolling = false;

        // Scroll to section
        function scrollToSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                isScrolling = true;
                section.scrollIntoView({
                    behavior: "smooth"
                });

                // Manually update active class
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.dataset.section === sectionId) item.classList.add('active');
                });

                setTimeout(() => {
                    isScrolling = false;
                }, 1000);
            }
        }

        // Scroll Spy Logic (Like tdash)
        const sections = document.querySelectorAll(".content-section");
        const sidebarItems = document.querySelectorAll(".sidebar-item");

        window.addEventListener("scroll", () => {
            if (isScrolling) return;
            let current = "";
            sections.forEach(section => {
                if (section.offsetParent !== null) { // Only check visible sections
                    if (window.scrollY >= section.offsetTop - 150) current = section.getAttribute("id");
                }
            });
            document.querySelectorAll('.sidebar-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.section === current) item.classList.add('active');
            });
        });

        // Filter Table Rows
        function filterRows(status, btn, tableId) {
            const container = btn.parentElement;
            container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const rows = document.querySelectorAll(`#${tableId} tbody tr`);
            rows.forEach(row => row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none');
        }

        // Edit own schedule
        const editScheduleBtn = document.getElementById('editScheduleBtn');
        if (editScheduleBtn) {
            editScheduleBtn.addEventListener('click', function() {
                const username = "<?php echo $_SESSION['username']; ?>";
                const modal = document.getElementById('userDataModal');

                document.getElementById('dataUsername').value = username;
                document.getElementById('userDataModalTitle').textContent = 'Edit My Weekly Schedule';

                const sectionSelect = document.getElementById('dataSectionId');
                sectionSelect.value = 'schedule';
                sectionSelect.dispatchEvent(new Event('change')); // Trigger data load
                modal.classList.add('show');
            });
        }

        // --- User Data Modal Logic ---
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('userDataModal');
            const form = document.getElementById('userDataForm');
            const sectionSelect = document.getElementById('dataSectionId');
            let editorInstance;

            // Init CKEditor
            ClassicEditor.create(document.querySelector('#dataContent'), {
                toolbar: [
                    'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
                    'horizontalLine', '|', 'undo', 'redo'
                ]
            }).then(editor => {
                editorInstance = editor;
            }).catch(error => console.error(error));

            // Open modal
            document.querySelectorAll('.manage-user-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const username = this.dataset.username;
                    document.getElementById('dataUsername').value = username;
                    document.getElementById('userDataModalTitle').textContent = 'Manage Data: ' + username;
                    modal.classList.add('show');
                });
            });

            // Close modal
            document.querySelector('.p-modal-close').addEventListener('click', () => modal.classList.remove('show'));
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('show');
            });

            // Fetch content on section change
            sectionSelect.addEventListener('change', function() {
                const username = document.getElementById('dataUsername').value;
                if (!username || !this.value) return;
                fetch(`student_data_api.php?username=${username}&section_id=${this.value}&t=${new Date().getTime()}`)
                    .then(res => res.json())
                    .then(data => editorInstance.setData(data.content || ''));
            });

            // Save content
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.set('content', editorInstance.getData());

                fetch('student_data_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Data saved successfully!');
                            modal.classList.remove('show');
                        } else {
                            alert('Error: ' + (data.message || 'Could not save data.'));
                        }
                    });
            });
        });

        // Auto-open tab from URL param (e.g. after posting announcement)
        const urlTab = new URLSearchParams(window.location.search).get('tab');
        if (urlTab) {
            const navLink = [...document.querySelectorAll('.p-nav a')]
                .find(a => a.getAttribute('onclick')?.includes(`'${urlTab}'`));
            if (navLink) showTab(urlTab, navLink);
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