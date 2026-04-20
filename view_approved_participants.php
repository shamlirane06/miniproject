<?php
session_start();
include "db.php";

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin'];

// Export approved participants to Excel
if(isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="approved_participants_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Student Name', 'Roll Number', 'Event Title', 'Event Date', 'Payment Type', 'Approved On']);
    
    $query = "
        SELECT s.name as student_name, r.roll_no, e.title, e.event_date, 
               r.payment_type, r.created_at as approved_on
        FROM event_registrations r
        JOIN events e ON r.event_id = e.event_id
        JOIN students s ON r.roll_no = s.roll_no
        WHERE e.admin_id = ? AND r.payment_status = 'approved'
        ORDER BY e.event_date DESC, s.name ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['student_name'],
            $row['roll_no'],
            $row['title'],
            date('d M Y', strtotime($row['event_date'])),
            ucfirst($row['payment_type'] ?? 'Free'),
            date('d M Y, h:i A', strtotime($row['approved_on']))
        ]);
    }
    fclose($output);
    $stmt->close();
    exit();
}

// Fetch only approved registrations
$query = "
    SELECT s.name as student_name, r.roll_no, e.title, e.event_date, 
           r.payment_type, r.created_at as approved_on
    FROM event_registrations r
    JOIN events e ON r.event_id = e.event_id
    JOIN students s ON r.roll_no = s.roll_no
    WHERE e.admin_id = ? AND r.payment_status = 'approved'
    ORDER BY e.event_date DESC, s.name ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$participants = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Participants | Admin Panel</title>
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
        .logo img {
            width: 45px;
            height: 45px;
            object-fit: contain;
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
        }
        .nav-links a:hover {
            color: #4f46e5;
        }
        .logout-btn {
            background: #dc2626;
            padding: 6px 14px;
            border-radius: 30px;
            color: white !important;
            font-weight: 500;
            transition: 0.2s;
        }
        .logout-btn:hover {
            background: #b91c1c;
            transform: scale(1.02);
            color: white !important;
        }
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 28px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .page-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1e293b, #334155);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        .btn-group {
            display: flex;
            gap: 12px;
        }
        .export-btn {
            background: linear-gradient(95deg, #10b981, #059669);
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }
        .back-link {
            background: white;
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            color: #4f46e5;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            transition: 0.2s;
        }
        .back-link:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
        }
        .table-wrapper {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(4px);
            border-radius: 32px;
            padding: 20px;
            border: 1px solid rgba(79,70,229,0.15);
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
        .empty-row td {
            text-align: center;
            padding: 40px;
            color: #6c757d;
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
    <div class="logo">
        <img src="logo.png" alt="Logo">
        <h2>EventPortal</h2>
    </div>
    <div class="nav-links">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="admin_dashboard.php"><i class="fas fa-chalkboard"></i> Dashboard</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-check-circle"></i> Approved Participants</h2>
        <div class="btn-group">
            <a href="?export=excel" class="export-btn">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
            <a href="admin_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Event Title</th>
                    <th>Event Date</th>
                    <th>Payment Type</th>
                    <th>Approved On</th>
                </tr>
            </thead>
            <tbody>
                <?php if($participants->num_rows > 0): ?>
                    <?php while($row = $participants->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo date("d M Y", strtotime($row['event_date'])); ?></td>
                            <td><?php echo ucfirst($row['payment_type'] ?? 'Free'); ?></td>
                            <td><?php echo date("d M Y, h:i A", strtotime($row['approved_on'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr class="empty-row"><td colspan="6">No approved participants yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>