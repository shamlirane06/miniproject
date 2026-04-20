<?php
session_start();
include "db.php";

// CHECK ADMIN LOGIN
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin'];

// FETCH ONLY EVENTS ADDED BY THIS ADMIN (using prepared statement)
$sql = "SELECT * FROM events WHERE admin_id = ? ORDER BY event_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Events | Admin Panel</title>
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
        .container {
            max-width: 1200px;
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
        .alert {
            padding: 14px 20px;
            border-radius: 40px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .alert.error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
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
        .delete-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .delete-btn:hover {
            background: #dc2626;
            transform: scale(1.02);
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
            .page-header h2 { font-size: 1.4rem; }
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
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-trash-alt"></i> Delete Events</h2>
        <a href="admin_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php
    // Display success/error messages from session (set by delete_event.php)
    if(isset($_SESSION['delete_success'])):
    ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['delete_success']; unset($_SESSION['delete_success']); ?>
        </div>
    <?php elseif(isset($_SESSION['delete_error'])): ?>
        <div class="alert error">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['delete_error']; unset($_SESSION['delete_error']); ?>
        </div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Event Title</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td><?php echo strtoupper(htmlspecialchars($row['category'])); ?></td>
                            <td><?php echo date("d M Y", strtotime($row['event_date'])); ?></td>
                            <td>
                                <a href="delete_event.php?id=<?php echo $row['event_id']; ?>" 
                                   onclick="return confirm('⚠️ Are you sure you want to delete “<?php echo htmlspecialchars($row['title']); ?>”? This action cannot be undone.')">
                                    <button class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr class="empty-row">
                        <td colspan="4">No events found. <a href="add_event.php">Create your first event</a></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>