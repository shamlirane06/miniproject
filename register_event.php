<?php
session_start();
include "db.php";  // your database connection file

if(!isset($_SESSION['student_roll'])){
    header("Location: student_login.php");
    exit();
}

$roll = $_SESSION['student_roll'];
$message = "";
$error = "";

if(!isset($_GET['event_id'])){
    $error = "No event selected.";
} else {
    $event_id = intval($_GET['event_id']);

    // Check if already registered
    $stmt = $conn->prepare("SELECT * FROM event_registrations WHERE roll_no = ? AND event_id = ?");
    $stmt->bind_param("si", $roll, $event_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $error = "You have already registered for this event.";
    } else {
        // Insert registration
        $stmt2 = $conn->prepare("INSERT INTO event_registrations (roll_no, event_id) VALUES (?, ?)");
        $stmt2->bind_param("si", $roll, $event_id);

        if($stmt2->execute()){
            // Get event name for a meaningful message
            $event_query = $conn->prepare("SELECT title FROM events WHERE event_id = ?");
            $event_query->bind_param("i", $event_id);
            $event_query->execute();
            $event_res = $event_query->get_result();
            $event_name = "Event";
            if($event_res->num_rows > 0){
                $event_row = $event_res->fetch_assoc();
                $event_name = $event_row['title'];
            }
            $event_query->close();

            $notif_message = "You have successfully registered for \"$event_name\". Keep an eye on your dashboard for updates.";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (student_roll, message, is_read) VALUES (?, ?, 0)");
            $notif_stmt->bind_param("ss", $roll, $notif_message);
            $notif_stmt->execute();
            $notif_stmt->close();

            $message = "Registration Successful! You are now registered for '$event_name'.";
        } else {
            $error = "Database error: " . $stmt2->error;
        }
        $stmt2->close();
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Registration Status - College Event Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgba(79,70,229,0.08), rgba(6,182,212,0.05)),
                        linear-gradient(145deg, #f8fafc 0%, #eff6ff 100%);
            min-height: 100vh;
        }
        /* Navbar Styles */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 32px;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(79,70,229,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
            gap: 12px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo i {
            font-size: 28px;
            color: #4f46e5;
        }
        nav h2 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(120deg, #4f46e5, #0d9488);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-links a {
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-links a:hover {
            color: #4f46e5;
        }
        .logout-btn {
            background: linear-gradient(95deg, #ef4444, #dc2626);
            padding: 8px 20px;
            border-radius: 40px;
            color: white !important;
        }
        .logout-btn:hover {
            background: linear-gradient(95deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
        }
        /* Message Card */
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            min-height: calc(100vh - 80px);
        }
        .message-card {
            background: white;
            max-width: 450px;
            width: 100%;
            border-radius: 32px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            text-align: center;
            padding: 35px 25px;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .success-icon {
            background: #dcfce7;
        }
        .success-icon i {
            color: #16a34a;
            font-size: 2.5rem;
        }
        .error-icon {
            background: #fee2e2;
        }
        .error-icon i {
            color: #dc2626;
            font-size: 2.5rem;
        }
        h2 {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: #1e293b;
        }
        p {
            color: #4b5563;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(90deg, #4f46e5, #06b6d4);
            color: white;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .redirect-note {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 20px;
        }
        @media (max-width: 640px) {
            nav {
                padding: 12px 20px;
            }
            .nav-links {
                gap: 12px;
            }
            .message-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">
        <i class="fas fa-calendar-alt"></i>
        <h2>College Event Hub</h2>
    </div>
    <div class="nav-links">
        <a href="view_events.php"><i class="fas fa-calendar-alt"></i> Events</a>
        <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
    <div class="message-card">
        <?php if($message): ?>
            <div class="icon-circle success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Success!</h2>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php elseif($error): ?>
            <div class="icon-circle error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <a href="view_events.php" class="btn">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>
        <div class="redirect-note">
            Redirecting automatically in <span id="countdown">3</span> seconds...
        </div>
    </div>
</div>

<script>
    // Countdown redirect
    let seconds = 3;
    const countdownEl = document.getElementById('countdown');
    const interval = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(interval);
            window.location.href = 'view_events.php';
        }
    }, 1000);
</script>

</body>
</html>