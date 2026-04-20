<?php
session_start();
include "db.php";

if(!isset($_SESSION['student_roll'])){
    header("Location: student_login.php");
    exit();
}

$roll = $_SESSION['student_roll'];
$student_name = $_SESSION['student_name'] ?? '';

$event_id = $_GET['event_id'];
$type = $_GET['type'] ?? 'single';

// FETCH EVENT
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id=?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

$message = "";
$screenshot_uploaded = false;
$screenshot_name = "";

// PRICE
$amount = ($type == 'single') ? $event['price_single'] : $event['price_double'];

/* ======================
   STEP 1: UPLOAD IMAGE
====================== */
if(isset($_POST['upload'])){

    if(isset($_FILES['screenshot']) && $_FILES['screenshot']['name'] != ""){

        $target_dir = "uploads/payments/";

        if(!is_dir($target_dir)){
            mkdir($target_dir, 0777, true);
        }

        // Unique filename
        $screenshot_name = time() . "_" . basename($_FILES["screenshot"]["name"]);
        move_uploaded_file($_FILES["screenshot"]["tmp_name"], $target_dir.$screenshot_name);

        // Save in session to prevent multiple uploads
        $_SESSION['uploaded_ss'] = $screenshot_name;
        $screenshot_uploaded = true;
        $message = "📸 Screenshot uploaded! Now click Register.";
    }
}

// CHECK SESSION
if(isset($_SESSION['uploaded_ss'])){
    $screenshot_uploaded = true;
    $screenshot_name = $_SESSION['uploaded_ss'];
}

/* ======================
   STEP 2: REGISTER
====================== */
if(isset($_POST['register']) && $screenshot_uploaded){

    // Check if already registered
    $check = $conn->prepare("SELECT * FROM event_registrations WHERE roll_no=? AND event_id=? AND payment_type=?");
    $check->bind_param("sis", $roll, $event_id, $type);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows == 0){
        // INSERT new registration
        $stmt2 = $conn->prepare("INSERT INTO event_registrations 
        (roll_no, event_id, payment_type, payment_screenshot, payment_status) 
        VALUES (?, ?, ?, ?, 'pending')");
        $stmt2->bind_param("siss", $roll, $event_id, $type, $screenshot_name);
        $stmt2->execute();
    } else {
        // UPDATE existing registration
        $stmt2 = $conn->prepare("UPDATE event_registrations 
        SET payment_screenshot=?, payment_status='pending' 
        WHERE roll_no=? AND event_id=? AND payment_type=?");
        $stmt2->bind_param("ssis", $screenshot_name, $roll, $event_id, $type);
        $stmt2->execute();
    }

    // --- INSERT NOTIFICATION ---
    $event_name = $event['title'];  // assuming column 'title' holds event name
    $notif_message = "Your registration for \"$event_name\" (₹$amount) is pending approval. We'll notify you once confirmed.";
    $notif_stmt = $conn->prepare("INSERT INTO notifications (student_roll, message, is_read) VALUES (?, ?, 0)");
    $notif_stmt->bind_param("ss", $roll, $notif_message);
    $notif_stmt->execute();
    $notif_stmt->close();

    // Clear session screenshot
    unset($_SESSION['uploaded_ss']);

    // Redirect back to events after registration
    header("Location: view_events.php?registered=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Payment - College Event Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef2ff, #e0f7fa);
            min-height: 100vh;
            transition: background 0.3s ease;
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4);
            color: white;
            flex-wrap: wrap;
            gap: 12px;
            transition: background 0.3s;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            color: white;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 10px;
            transition: 0.2s;
        }
        .home-btn {
            background: rgba(255,255,255,0.2);
        }
        .home-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .logout-btn {
            background: #dc2626;
        }
        .logout-btn:hover {
            background: #b91c1c;
        }
        /* Theme toggle button */
        .theme-toggle {
            background: rgba(255,255,255,0.2);
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 9999px;
            transition: all 0.2s;
            color: white;
            backdrop-filter: blur(4px);
        }
        .theme-toggle:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        .box {
            background: white;
            padding: 30px;
            margin: 50px auto;
            width: 90%;
            max-width: 420px;
            border-radius: 24px;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.15);
            text-align: center;
            transition: background 0.3s, box-shadow 0.3s;
        }
        .box h2 {
            font-size: 1.6rem;
            margin-bottom: 15px;
            color: #1e293b;
        }
        .box p {
            margin: 10px 0;
            color: #334155;
        }
        img {
            width: 220px;
            margin: 15px 0;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        input[type="file"] {
            margin: 10px 0;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 30px;
            width: 100%;
            background: #f9fafb;
        }
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 40px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4);
            color: white;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: 0.2s;
            width: 100%;
        }
        button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        button:not(:disabled):hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(79,70,229,0.3);
        }
        .success {
            color: #16a34a;
            font-weight: 600;
            margin-top: 15px;
            background: #dcfce7;
            padding: 10px;
            border-radius: 40px;
        }
        @media (max-width: 640px) {
            nav { padding: 12px 20px; }
            .box { margin: 30px auto; padding: 25px; width: 95%; }
        }

        /* ---------- DARK MODE STYLES ---------- */
        html.dark body {
            background: #0f172a !important;
        }
        html.dark nav {
            background: #1e1b4b !important;
        }
        html.dark .box {
            background: #1e293b !important;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.4);
        }
        html.dark .box h2 {
            color: #f1f5f9 !important;
        }
        html.dark .box p {
            color: #cbd5e1 !important;
        }
        html.dark input[type="file"] {
            background: #0f172a;
            border-color: #475569;
            color: #e2e8f0;
        }
        html.dark .success {
            background: #064e3b;
            color: #a7f3d0;
        }
        html.dark .theme-toggle {
            background: rgba(0,0,0,0.3);
            color: #facc15;
        }
        html.dark .theme-toggle:hover {
            background: rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>

<nav>
    <h2>🎟️ Event Portal</h2>
    <div class="nav-links">
        <span>👋 <?php echo htmlspecialchars($student_name); ?></span>
        <button id="darkModeToggle" class="theme-toggle" aria-label="Dark mode">
            <i class="fas fa-moon"></i>
        </button>
        <a href="view_events.php" class="home-btn"><i class="fas fa-home"></i> Home</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="box">
    <h2><?php echo htmlspecialchars($event['title']); ?></h2>

    <?php if($event['is_paid'] == 1){ ?>
        <p><b>Type:</b> <?php echo ucfirst($type); ?></p>
        <p><b>Amount:</b> ₹<?php echo $amount; ?></p>

        <?php if($event['qr_code'] != ""){ ?>
            <img src="uploads/<?php echo $event['qr_code']; ?>" alt="Payment QR Code">
        <?php } else { ?>
            <p style="color:red;">⚠ No QR Code Uploaded</p>
        <?php } ?>

        <p>Scan & upload payment screenshot</p>

        <?php if(!$screenshot_uploaded): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="screenshot" accept="image/*" required>
                <button name="upload">📤 Upload Screenshot</button>
            </form>
        <?php else: ?>
            <p class="success">📸 Screenshot uploaded. ✅ Now click Register.</p>
        <?php endif; ?>

        <form method="POST">
            <button name="register" <?php echo !$screenshot_uploaded ? 'disabled' : ''; ?>>
                ✅ Register
            </button>
        </form>

    <?php } else { ?>
        <p>This is a Free Event</p>
        <form method="POST">
            <button name="register">
                🎉 Register Now
            </button>
        </form>
    <?php } ?>

    <?php if($message!=""){ ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
    <?php } ?>
</div>

<!-- Dark mode self-contained script -->
<script>
    (function() {
        const STORAGE_KEY = 'theme';
        const htmlElement = document.documentElement;

        function applyDarkMode(isDark) {
            if (isDark) {
                htmlElement.classList.add('dark');
            } else {
                htmlElement.classList.remove('dark');
            }
        }

        function getInitialMode() {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'dark') return true;
            if (saved === 'light') return false;
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function updateToggleIcon(button, isDark) {
            const icon = button.querySelector('i');
            if (icon) {
                if (isDark) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
        }

        const isDark = getInitialMode();
        applyDarkMode(isDark);

        const toggleBtn = document.getElementById('darkModeToggle');
        if (toggleBtn) {
            updateToggleIcon(toggleBtn, isDark);
            toggleBtn.addEventListener('click', function() {
                const nowDark = !htmlElement.classList.contains('dark');
                applyDarkMode(nowDark);
                localStorage.setItem(STORAGE_KEY, nowDark ? 'dark' : 'light');
                updateToggleIcon(toggleBtn, nowDark);
            });
        }
    })();
</script>

</body>
</html>
