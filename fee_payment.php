<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch Student Details
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$student_name = $_SESSION['user_name'];
$student_class = "Not Assigned";
$amount_to_pay = 5000; // Example amount

// Try to fetch class from admissions table
$stmt = $conn->prepare("SELECT student_class FROM admissions WHERE student_name = ?");
$stmt->bind_param("s", $student_name);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $student_class = $row['student_class'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fee Payment | RC Middle School</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('school.jpg');
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .payment-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 600px;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .payment-header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }

        .payment-header h2 {
            color: #1f4e79;
            font-size: 28px;
        }

        .student-info {
            background: #f0f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #1f4e79;
        }

        .student-info p {
            margin: 5px 0;
            font-size: 16px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .payment-details {
            display: none;
            animation: slideDown 0.3s ease-out;
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .captcha-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            background: #e8f0fe;
            padding: 10px;
            border-radius: 6px;
        }

        .captcha-text {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 20px;
            letter-spacing: 3px;
            background: #fff;
            padding: 5px 10px;
            border: 1px solid #ccc;
            user-select: none;
        }

        .pay-btn {
            width: 100%;
            padding: 15px;
            background: #2ed573;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
            opacity: 0.5;
            pointer-events: none;
        }

        .pay-btn.active {
            opacity: 1;
            pointer-events: all;
        }

        .pay-btn:hover.active {
            background: #26af61;
            transform: translateY(-2px);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #555;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="payment-container">
        <div class="payment-header">
            <h2>Secure Fee Payment</h2>
        </div>

        <!-- Student Details -->
        <div class="student-info">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student_name); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($student_class); ?></p>
            <p><strong>Amount to Pay:</strong> <span id="displayAmount" style="color: #d63031; font-weight: bold;">₹<?php echo number_format($amount_to_pay, 2); ?></span></p>
        </div>

        <form id="paymentForm" onsubmit="return processPayment(event)">
            <!-- Fee Type Dropdown -->
            <div class="form-group">
                <label>Select Fee Type</label>
                <select id="feeType" class="form-control" onchange="updateAmount()">
                    <option value="5000" selected>Term 2 Fees - ₹5000</option>
                    <option value="1000">Transport Fee - ₹1000</option>
                    <option value="6000">Total Due - ₹6000</option>
                </select>
            </div>

            <!-- Payment Method Dropdown -->
            <div class="form-group">
                <label>Select Payment Method</label>
                <select id="paymentMethod" class="form-control" onchange="showPaymentFields()">
                    <option value="">-- Choose Option --</option>
                    <option value="card">Credit / Debit Card</option>
                    <option value="netbanking">Net Banking</option>
                    <option value="upi">UPI (GPay / PhonePe)</option>
                </select>
            </div>

            <!-- Dynamic Fields: Card -->
            <div id="cardFields" class="payment-details">
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" class="form-control" placeholder="XXXX XXXX XXXX XXXX" maxlength="19">
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Expiry</label>
                        <input type="text" class="form-control" placeholder="MM/YY">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>CVV</label>
                        <input type="password" class="form-control" placeholder="123" maxlength="3">
                    </div>
                </div>
                <div class="form-group">
                    <label>Card Holder Name</label>
                    <input type="text" class="form-control" placeholder="Name on Card">
                </div>
            </div>

            <!-- Dynamic Fields: Net Banking -->
            <div id="netbankingFields" class="payment-details">
                <div class="form-group">
                    <label>Select Bank</label>
                    <select class="form-control">
                        <option>State Bank of India</option>
                        <option>HDFC Bank</option>
                        <option>ICICI Bank</option>
                        <option>Axis Bank</option>
                        <option>Indian Bank</option>
                        <option>Central Bank of India</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Fields: UPI -->
            <div id="upiFields" class="payment-details">
                <div class="form-group">
                    <label>UPI ID / VPA</label>
                    <input type="text" class="form-control" placeholder="username@upi">
                </div>
            </div>

            <!-- Captcha Verification -->
            <div class="captcha-box">
                <span id="captchaQuestion" class="captcha-text"></span>
                <input type="number" id="captchaInput" class="form-control" placeholder="Enter Answer" style="width: 120px;" oninput="verifyCaptcha()">
                <span id="captchaStatus" class="material-symbols-outlined" style="color: grey;">pending</span>
            </div>

            <button type="submit" id="payButton" class="pay-btn">PAY ₹<?php echo number_format($amount_to_pay); ?></button>
        </form>

        <a href="dash.php" class="back-link">← Cancel and Back to Dashboard</a>
    </div>

    <script>
        let num1, num2, answer;

        // Initialize Page
        window.onload = function() {
            generateCaptcha();
        };

        // Update Amount based on Fee Type
        function updateAmount() {
            const amount = document.getElementById('feeType').value;
            document.getElementById('displayAmount').innerText = '₹' + parseInt(amount).toLocaleString('en-IN') + '.00';
            document.getElementById('payButton').innerText = 'PAY ₹' + parseInt(amount).toLocaleString('en-IN');
        }

        // Show/Hide Fields based on Dropdown
        function showPaymentFields() {
            const method = document.getElementById('paymentMethod').value;

            // Hide all first
            document.getElementById('cardFields').style.display = 'none';
            document.getElementById('netbankingFields').style.display = 'none';
            document.getElementById('upiFields').style.display = 'none';

            // Show selected
            if (method === 'card') document.getElementById('cardFields').style.display = 'block';
            else if (method === 'netbanking') document.getElementById('netbankingFields').style.display = 'block';
            else if (method === 'upi') document.getElementById('upiFields').style.display = 'block';
        }

        // Generate Simple Math Captcha
        function generateCaptcha() {
            num1 = Math.floor(Math.random() * 10) + 1;
            num2 = Math.floor(Math.random() * 10) + 1;
            answer = num1 + num2;
            document.getElementById('captchaQuestion').innerText = `${num1} + ${num2} = ?`;
            document.getElementById('captchaInput').value = '';
            verifyCaptcha(); // Reset status
        }

        // Verify Captcha Logic
        function verifyCaptcha() {
            const userInput = parseInt(document.getElementById('captchaInput').value);
            const btn = document.getElementById('payButton');
            const statusIcon = document.getElementById('captchaStatus');

            if (userInput === answer) {
                btn.classList.add('active');
                statusIcon.innerText = 'check_circle';
                statusIcon.style.color = 'green';
            } else {
                btn.classList.remove('active');
                statusIcon.innerText = 'error';
                statusIcon.style.color = 'red';
            }
        }

        // Process Payment (Simulation)
        function processPayment(e) {
            e.preventDefault();
            if (!document.getElementById('payButton').classList.contains('active')) return;

            const method = document.getElementById('paymentMethod').value;
            if (!method) {
                alert("Please select a payment method.");
                return;
            }

            alert("Payment Successful! Redirecting...");
            window.location.href = "dash.php";
        }
    </script>

</body>

</html>