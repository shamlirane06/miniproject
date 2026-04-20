<?php
session_start();
include "db.php";

if(!isset($_SESSION['student_roll'])){
    header("Location: student_login.php");
    exit();
}

$roll = $_SESSION['student_roll'];
$student_name = $_SESSION['student_name'] ?? '';

// FETCH REGISTRATIONS with certificate info
$stmt = $conn->prepare("
    SELECT e.title, e.event_date, r.payment_type, r.payment_status, e.event_id
    FROM event_registrations r
    JOIN events e ON r.event_id = e.event_id
    WHERE r.roll_no = ?
");
$stmt->bind_param("s", $roll);
$stmt->execute();
$result = $stmt->get_result();

// Pre-fetch certificates for this student (to show download links)
$certificates = [];
$certQuery = $conn->prepare("SELECT event_name, file_path FROM certificates WHERE student_roll = ?");
$certQuery->bind_param("s", $roll);
$certQuery->execute();
$certResult = $certQuery->get_result();
while($cert = $certResult->fetch_assoc()) {
    $certificates[$cert['event_name']] = $cert['file_path'];
}
$certQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registrations | College Event Hub</title>
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
        /* Glass navigation */
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
        .nav-right a {
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nav-right a:hover { color: #4f46e5; }
        .logout-btn {
            background: linear-gradient(95deg, #ef4444, #dc2626);
            padding: 6px 16px;
            border-radius: 40px;
            color: white !important;
        }
        .logout-btn:hover { background: #b91c1c; transform: translateY(-1px); }
        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 28px;
        }
        /* Card style for table wrapper */
        .table-wrapper {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(4px);
            border-radius: 40px;
            padding: 24px;
            border: 1px solid rgba(79,70,229,0.15);
            box-shadow: 0 12px 24px -12px rgba(0,0,0,0.08);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        th {
            text-align: left;
            padding: 16px 12px;
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
        }
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .status {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 30px;
            display: inline-block;
            font-size: 0.75rem;
        }
        .pending { background: #fef3c7; color: #b45309; }
        .approved { background: #dcfce7; color: #15803d; }
        .rejected { background: #fee2e2; color: #b91c1c; }
        .download-btn {
            background: #eef2ff;
            padding: 6px 14px;
            border-radius: 40px;
            text-decoration: none;
            color: #4f46e5;
            font-weight: 500;
            font-size: 0.75rem;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .download-btn:hover {
            background: #4f46e5;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255,255,255,0.7);
            border-radius: 40px;
        }
        .empty-state i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 16px;
        }
        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            background: linear-gradient(95deg, #4f46e5, #06b6d4);
            padding: 10px 24px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        @media (max-width: 720px) {
            nav { padding: 12px 20px; }
            .container { padding: 0 16px; }
            th, td { padding: 10px 8px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>

<nav>
    <h2><i class="fas fa-ticket-alt"></i> My Registrations</h2>
    <div class="nav-right">
        <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
    <?php if($result->num_rows > 0): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): 
                        $event_name = $row['title'];
                        $cert_path = isset($certificates[$event_name]) ? $certificates[$event_name] : null;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($event_name); ?></strong></td>
                            <td><?php echo date("d M Y", strtotime($row['event_date'])); ?></td>
                            <td><?php echo ucfirst($row['payment_type'] ?? 'Free'); ?></td>
                            <td><span class="status <?php echo $row['payment_status']; ?>"><?php echo ucfirst($row['payment_status']); ?></span></td>
                            <td>
                                <?php if($cert_path): ?>
                                    <a href="<?php echo htmlspecialchars($cert_path); ?>" class="download-btn" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.75rem;">Not yet</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>No registrations found</h3>
            <p>You haven't registered for any events yet.</p>
            <a href="view_events.php"><i class="fas fa-calendar-alt"></i> Browse Events</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>