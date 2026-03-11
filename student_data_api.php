<?php
require "db.php";
header('Content-Type: application/json');
session_start();

// Allow admin or principal
if (!isset($_SESSION['admin_logged_in']) && ($_SESSION['role'] ?? '') !== 'principle') {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// POST: Save data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $section_id = $_POST['section_id'] ?? null;
    $content = $_POST['content'] ?? '';

    if (!$username || !$section_id) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both new and existing records
    $stmt = $conn->prepare("INSERT INTO student_section_content (username, section_id, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE content = ?");
    $stmt->bind_param("ssss", $username, $section_id, $content, $content);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    exit;
}

// GET: Fetch data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $username = $_GET['username'] ?? null;
    $section_id = $_GET['section_id'] ?? null;

    if (!$username || !$section_id) {
        echo json_encode(['content' => '']);
        exit;
    }

    // 1. Check for student-specific content
    $stmt = $conn->prepare("SELECT content FROM student_section_content WHERE username = ? AND section_id = ?");
    $stmt->bind_param("ss", $username, $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty(trim($row['content']))) {
            echo json_encode(['content' => $row['content']]);
            exit;
        }
    }

    // 2. Check for generic card content from dashboard_cards
    $stmt = $conn->prepare("SELECT detailed_content FROM dashboard_cards WHERE section_id = ?");
    $stmt->bind_param("s", $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty(trim($row['detailed_content']))) {
            echo json_encode(['content' => $row['detailed_content']]);
            exit;
        }
    }

    // 3. Fallback to hardcoded defaults (copied from dash.php)
    $default_content = '';

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

    $default_subjects_html = '<div class="subjects-grid">
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
    $schedule_html = '<h3 class="schedule-title">Weekly Class Schedule</h3>
<div class="schedule-wrapper">
    <table>
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
            <tr>
                <td>08:00 - 09:00</td>
                <td>Mathematics</td>
                <td>Science</td>
                <td>English</td>
                <td>Mathematics</td>
                <td>History</td>
            </tr>
            <tr>
                <td>09:00 - 10:00</td>
                <td>Science</td>
                <td>English</td>
                <td>Mathematics</td>
                <td>History</td>
                <td>Science</td>
            </tr>
            <tr>
                <td>10:00 - 10:30</td>
                <td style="padding: 12px; text-align: center; background: rgba(255,255,255,0.05); font-style: italic;" colspan="5">Morning Break</td>
            </tr>
            <tr>
                <td>10:30 - 11:30</td>
                <td>English</td>
                <td>History</td>
                <td>Science</td>
                <td>English</td>
                <td>Mathematics</td>
            </tr>
             <tr>
                <td>11:30 - 12:30</td>
                <td>History</td>
                <td>Mathematics</td>
                <td>History</td>
                <td>Science</td>
                <td>English</td>
            </tr>
        </tbody>
    </table>
</div>';

    if ($section_id === 'subjects') {
        $default_content = $default_subjects_html;
    } elseif ($section_id === 'attendance') {
        $default_content = $default_attendance_html;
    } elseif ($section_id === 'schedule') {
        $is_teacher = $username && preg_match('/^RCT[0-9]{7,}$/', $username);
        $is_principal = $username && preg_match('/^RCP[0-9]{7,}$/', $username);

        if ($is_teacher) {
            // Teacher's default schedule (div-based from tdash.php)
            $default_content = '<div class="sch-row"><span class="sch-time">7:30 AM</span><span class="sch-dot"></span><div><div class="sch-subj">Mathematics 5-A</div><div class="sch-room">Room 101</div></div></div>
<div class="sch-row"><span class="sch-time">9:00 AM</span><span class="sch-dot b"></span><div><div class="sch-subj">Mathematics 5-B</div><div class="sch-room">Room 102</div></div></div>
<div class="sch-row"><span class="sch-time">1:00 PM</span><span class="sch-dot g"></span><div><div class="sch-subj">Math Club</div><div class="sch-room">Library</div></div></div>
<div class="sch-row"><span class="sch-time">3:00 PM</span><span class="sch-dot"></span><div><div class="sch-subj">Parent Meeting</div><div class="sch-room">Conference</div></div></div>';
        } elseif ($is_principal) {
            // Principal's default schedule (table-based)
            $default_content = '<table class="p-table">
    <thead>
        <tr><th>Day</th><th>Time</th><th>Activity</th><th>Venue</th></tr>
    </thead>
    <tbody>
        <tr><td>Monday</td><td>7:00 AM</td><td>Flag Ceremony</td><td>Quadrangle</td></tr>
        <tr><td>Monday</td><td>8:30 AM</td><td>Faculty Briefing</td><td>Principal\'s Office</td></tr>
        <tr><td>Tuesday</td><td>10:00 AM</td><td>Class Observation</td><td>Room 203</td></tr>
        <tr><td>Wednesday</td><td>1:00 PM</td><td>Parent Meeting</td><td>Conference Room</td></tr>
        <tr><td>Friday</td><td>3:30 PM</td><td>Dept Review</td><td>AVR</td></tr>
    </tbody>
</table>';
        } else {
            // Student's default schedule (table-based)
            $default_content = $schedule_html;
        }
    } elseif ($section_id === 'profile' && $username) {
        // Fetch user's real name to find admission record
        $name_stmt = $conn->prepare("SELECT name FROM users WHERE username = ?");
        $name_stmt->bind_param("s", $username);
        $name_stmt->execute();
        $name_res = $name_stmt->get_result();

        if ($u_row = $name_res->fetch_assoc()) {
            $adm_stmt = $conn->prepare("SELECT * FROM admissions WHERE student_name = ?");
            $adm_stmt->bind_param("s", $u_row['name']);
            $adm_stmt->execute();
            $adm_res = $adm_stmt->get_result();

            if ($adm_row = $adm_res->fetch_assoc()) {
                $default_content = '<div class="info-grid">
    <div class="info-card">
        <h3>Personal Information</h3>
        <p><strong>Name:</strong> ' . htmlspecialchars($adm_row['student_name']) . '</p>
        <p><strong>Email:</strong> ' . htmlspecialchars($adm_row['email']) . '</p>
        <p><strong>Phone:</strong> ' . htmlspecialchars($adm_row['phone']) . '</p>
        <p><strong>ID:</strong> ' . htmlspecialchars($adm_row['id']) . '</p>
    </div>
    <div class="info-card">
        <h3>Academic Status</h3>
        <p><strong>Status:</strong> Active</p>
        <p><strong>Role:</strong> Student</p>
    </div>
</div>';
            }
        }
    }

    echo json_encode(['content' => $default_content]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
