<?php
session_start();
include "db.php";

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$message = "";
$error = "";

if(isset($_GET['id'])){
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("UPDATE event_registrations SET payment_status='approved' WHERE id=?");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        $message = "Registration has been approved successfully.";
    } else {
        $error = "Failed to update. Please try again.";
    }
    $stmt->close();
} else {
    $error = "Invalid request. No registration ID provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Approve Registration - College Event Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef2ff 0%, #e0f7fa 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
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
            position: relative;
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
        @media (max-width: 480px) {
            .message-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="message-card">
    <?php if($message): ?>
        <div class="icon-circle success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Approved!</h2>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php elseif($error): ?>
        <div class="icon-circle error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>Error</h2>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <a href="view_registrations.php" class="btn">
        <i class="fas fa-arrow-left"></i> Back to Registrations
    </a>
    <div class="redirect-note">
        Redirecting automatically in <span id="countdown">3</span> seconds...
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
            window.location.href = 'view_registrations.php';
        }
    }, 1000);
</script>

</body>
</html>