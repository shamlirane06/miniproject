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

    $stmt = $conn->prepare("UPDATE event_registrations SET payment_status='rejected' WHERE id=?");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        $message = "Registration has been rejected successfully.";
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
    <title>Reject Registration - College Event Hub</title>
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
            transition: background 0.3s ease;
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
            transition: background 0.3s, box-shadow 0.3s;
        }
        /* Theme toggle button */
        .theme-toggle {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.05);
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 9999px;
            transition: all 0.2s;
            color: #1e293b;
            backdrop-filter: blur(4px);
            z-index: 10;
        }
        .theme-toggle:hover {
            background: rgba(100, 116, 139, 0.2);
            transform: scale(1.05);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .icon-circle i {
            font-size: 2.5rem;
            color: #dc2626;
        }
        .success-icon {
            background: #dcfce7;
        }
        .success-icon i {
            color: #16a34a;
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
            .theme-toggle {
                top: 12px;
                right: 12px;
            }
        }

        /* ---------- DARK MODE STYLES ---------- */
        html.dark body {
            background: #0f172a !important;
        }
        html.dark .message-card {
            background: #1e293b !important;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.4);
        }
        html.dark .icon-circle {
            background: #450a0a !important;
        }
        html.dark .success-icon {
            background: #064e3b !important;
        }
        html.dark .success-icon i {
            color: #a7f3d0 !important;
        }
        html.dark .icon-circle i {
            color: #fca5a5 !important;
        }
        html.dark h2 {
            color: #f1f5f9 !important;
        }
        html.dark p {
            color: #cbd5e1 !important;
        }
        html.dark .btn {
            background: linear-gradient(90deg, #6366f1, #0891b2);
        }
        html.dark .redirect-note {
            color: #94a3b8 !important;
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

<div class="message-card">
    <button id="darkModeToggle" class="theme-toggle" aria-label="Dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <?php if($message): ?>
        <div class="icon-circle success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Rejected</h2>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php elseif($error): ?>
        <div class="icon-circle">
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

    // Dark mode self-contained script
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