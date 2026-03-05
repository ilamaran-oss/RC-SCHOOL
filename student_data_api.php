<?php
require 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save Data
    $username = $_POST['username'];
    $section_id = $_POST['section_id'];
    $content = $_POST['content'];

    // Check if exists
    $check = $conn->prepare("SELECT id FROM student_section_content WHERE username = ? AND section_id = ?");
    $check->bind_param("ss", $username, $section_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE student_section_content SET content = ? WHERE username = ? AND section_id = ?");
        $stmt->bind_param("sss", $content, $username, $section_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO student_section_content (username, section_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $section_id, $content);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch Data
    $username = $_GET['username'];
    $section_id = $_GET['section_id'];

    // 1. Try to get student-specific content
    $stmt = $conn->prepare("SELECT content FROM student_section_content WHERE username = ? AND section_id = ?");
    $stmt->bind_param("ss", $username, $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data && !empty(trim($data['content']))) {
        echo json_encode(['content' => $data['content']]);
    } else {
        // If content is empty, pre-fill 'fees' with the new single-box template
        if ($section_id === 'fees') {
            $content = '<div class="info-card">
    
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
            echo json_encode(['content' => $content]);
            exit;
        }

        // For other sections, fetch default content from dashboard_cards
        $stmt_def = $conn->prepare("SELECT detailed_content FROM dashboard_cards WHERE section_id = ?");
        $stmt_def->bind_param("s", $section_id);
        $stmt_def->execute();
        $res_def = $stmt_def->get_result();
        $data_def = $res_def->fetch_assoc();

        echo json_encode(['content' => $data_def ? $data_def['detailed_content'] : '']);
    }
}
