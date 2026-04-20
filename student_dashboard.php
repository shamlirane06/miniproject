<?php
session_start();
if(!isset($_SESSION['student_roll'])){
    header("Location: student_login.php");
    exit();
}

require_once 'config.php';

$student_roll = $_SESSION['student_roll'];
$student_name = $_SESSION['student_name'] ?? '';

$conn = getConnection();

// --- Notifications (if table exists) ---
$unread_count = 0;
$notifications = [];
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if($table_check && $table_check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id, message, created_at, is_read FROM notifications WHERE student_roll = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->bind_param("s", $student_roll);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
        if(!$row['is_read']) $unread_count++;
    }
    $stmt->close();
}

// --- Certificates (if table exists) ---
$certificates = [];
$table_check = $conn->query("SHOW TABLES LIKE 'certificates'");
if($table_check && $table_check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id, event_name, issue_date, file_path FROM certificates WHERE student_roll = ? ORDER BY issue_date DESC");
    $stmt->bind_param("s", $student_roll);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $certificates[] = $row;
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
    <title>Student Dashboard | College Event Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgba(79,70,229,0.08), rgba(6,182,212,0.05)),
                        linear-gradient(145deg, #f8fafc 0%, #eff6ff 100%);
            min-height: 100vh;
            color: #0f172a;
        }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #818cf8; border-radius: 10px; }
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
        nav h2 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(120deg, #4f46e5, #0d9488);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        nav h2 i { color: #4f46e5; font-size: 1.6rem; }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
            background: rgba(255,255,255,0.6);
            padding: 5px 16px 5px 20px;
            border-radius: 60px;
            backdrop-filter: blur(4px);
        }
        .nav-right span {
            font-size: 0.9rem;
            font-weight: 500;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .home-link {
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .home-link:hover {
            color: #4f46e5;
        }
        .logout-btn {
            background: linear-gradient(95deg, #ef4444, #dc2626);
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;  /* Push logout to the right corner */
        }
        .logout-btn:hover {
            background: linear-gradient(95deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
        }
        .bell-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.3rem;
            color: #1e293b;
            transition: 0.2s;
        }
        .bell-icon:hover { color: #4f46e5; }
        .badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 30px;
            min-width: 18px;
            text-align: center;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 32px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: 0 25px 40px rgba(0,0,0,0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 1.3rem;
        }
        .close-modal { cursor: pointer; font-size: 1.5rem; }
        .notification-item {
            padding: 12px 0;
            border-bottom: 1px solid #eef2ff;
            display: flex;
            gap: 12px;
        }
        .notification-item.unread {
            background: #f0f9ff;
            margin: 0 -12px;
            padding: 12px;
            border-radius: 20px;
        }
        .notif-icon { color: #4f46e5; }
        .notif-text { flex: 1; font-size: 0.9rem; }
        .notif-time { font-size: 0.7rem; color: #6c757d; }
        .dashboard-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 40px 28px;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        .action-card {
            background: white;
            border-radius: 40px;
            padding: 34px 24px;
            text-align: center;
            text-decoration: none;
            color: #0f172a;
            transition: all 0.35s;
            box-shadow: 0 12px 24px -12px rgba(0,0,0,0.08);
            border: 1px solid #eef2ff;
        }
        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 40px -18px rgba(79,70,229,0.35);
            border-color: #c7d2fe;
        }
        .card-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            background: linear-gradient(145deg, #4f46e5, #14b8a6);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        .action-card h3 { font-size: 1.6rem; font-weight: 700; margin-bottom: 12px; }
        .action-card p { font-size: 0.9rem; color: #5b6e8c; }
        .certificate-section {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(4px);
            border-radius: 40px;
            padding: 28px 30px;
            margin-top: 20px;
            border: 1px solid rgba(79,70,229,0.15);
        }
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cert-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .cert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 12px 20px;
            border-radius: 60px;
            border: 1px solid #eef2ff;
        }
        .cert-info { display: flex; align-items: center; gap: 12px; }
        .cert-info i { font-size: 1.4rem; color: #4f46e5; }
        .cert-name { font-weight: 600; }
        .cert-date { font-size: 0.75rem; color: #6c757d; }
        .download-btn {
            background: #eef2ff;
            padding: 8px 16px;
            border-radius: 40px;
            text-decoration: none;
            color: #4f46e5;
            font-weight: 500;
            font-size: 0.8rem;
            transition: 0.2s;
        }
        .download-btn:hover { background: #4f46e5; color: white; }
        .empty-message { color: #6c757d; font-style: italic; padding: 12px; }
        @media (max-width: 720px) {
            .dashboard-container { padding: 24px 20px; }
            nav { padding: 12px 20px; }
            .action-card h3 { font-size: 1.4rem; }
            .cert-item { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>

<nav>
    <h2><i class="fas fa-sparkles"></i> College Event Hub</h2>
    <div class="nav-right">
        <!-- Student name appears first -->
        <span><i class="fas fa-circle-user"></i> <?php echo htmlspecialchars($student_name); ?></span>
        <div class="bell-icon" id="bellIcon">
            <i class="fas fa-bell"></i>
            <?php if($unread_count > 0): ?>
                <span class="badge" id="notifBadge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        <a href="student_dashboard.php" class="home-link"><i class="fas fa-home"></i> Home</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
    </div>
</nav>

<!-- Notifications Modal -->
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>🔔 Notifications</span>
            <span class="close-modal">&times;</span>
        </div>
        <div id="notificationsList">
            <?php if(empty($notifications)): ?>
                <p class="empty-message">No notifications yet.</p>
            <?php else: ?>
                <?php foreach($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notif['id']; ?>">
                        <div class="notif-icon"><i class="fas fa-info-circle"></i></div>
                        <div class="notif-text">
                            <?php echo htmlspecialchars($notif['message']); ?>
                            <div class="notif-time"><?php echo date("d M, h:i A", strtotime($notif['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dashboard-container">
    <!-- Main Action Cards -->
    <div class="action-grid">
        <a href="view_events.php" class="action-card">
            <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
            <h3>Browse Events</h3>
            <p>Explore all upcoming workshops, fests & seminars</p>
        </a>
        <a href="my_registrations.php" class="action-card">
            <div class="card-icon"><i class="fas fa-ticket-alt"></i></div>
            <h3>My Registrations</h3>
            <p>Track your bookings, certificates & history</p>
        </a>
        <a href="change_password1.php" class="action-card">
            <div class="card-icon"><i class="fas fa-lock"></i></div>
            <h3>Security</h3>
            <p>Update password & keep your account safe</p>
        </a>
    </div>

    <!-- Certificate Download Section -->
    <div class="certificate-section">
        <div class="section-title">
            <i class="fas fa-award"></i> My Certificates
        </div>
        <div class="cert-list">
            <?php if(empty($certificates)): ?>
                <p class="empty-message">No certificates earned yet. Attend events to get certified!</p>
            <?php else: ?>
                <?php foreach($certificates as $cert): ?>
                    <div class="cert-item">
                        <div class="cert-info">
                            <i class="fas fa-certificate"></i>
                            <div>
                                <div class="cert-name"><?php echo htmlspecialchars($cert['event_name']); ?></div>
                                <div class="cert-date">Issued: <?php echo date("d M Y", strtotime($cert['issue_date'])); ?></div>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars($cert['file_path']); ?>" class="download-btn" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('notificationModal');
    const bell = document.getElementById('bellIcon');
    const closeModal = document.querySelector('.close-modal');
    
    bell.addEventListener('click', () => {
        modal.style.display = 'flex';
        fetch('mark_notifications_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_roll: '<?php echo $student_roll; ?>' })
        }).then(() => {
            const badge = document.querySelector('.badge');
            if(badge) badge.style.display = 'none';
            document.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
        }).catch(err => console.log(err));
    });
    
    closeModal.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => { if(e.target === modal) modal.style.display = 'none'; });
</script>
</body>
</html>