<?php
// Set persistent cookie parameters BEFORE session start
$lifetime = 30 * 24 * 60 * 60; // 30 days
session_set_cookie_params($lifetime, '/', '', false, true);

// Now start the session
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard | College Event Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            width: 48px;
            height: 48px;
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
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nav-links a:hover {
            color: #4f46e5;
        }
        .logout-btn {
            background: linear-gradient(95deg, #ef4444, #dc2626);
            padding: 6px 16px;
            border-radius: 40px;
            color: white !important;
        }
        .logout-btn:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }
        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 28px;
        }
        .welcome-section {
            text-align: center;
            margin-bottom: 48px;
        }
        .welcome-section h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1e293b, #334155);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 8px;
        }
        .welcome-section p {
            color: #5b6e8c;
        }
        /* Dashboard grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 28px;
            margin-bottom: 50px;
        }
        .dashboard-card {
            background: white;
            border-radius: 32px;
            padding: 32px 20px;
            text-align: center;
            text-decoration: none;
            color: #0f172a;
            transition: all 0.3s ease;
            box-shadow: 0 12px 24px -12px rgba(0,0,0,0.08);
            border: 1px solid #eef2ff;
        }
        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 25px 35px -16px rgba(79,70,229,0.25);
            border-color: #c7d2fe;
        }
        .card-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            background: linear-gradient(145deg, #4f46e5, #14b8a6);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        .dashboard-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .dashboard-card p {
            font-size: 0.85rem;
            color: #5b6e8c;
            margin-bottom: 20px;
        }
        .btn-card {
            background: linear-gradient(95deg, #4f46e5, #06b6d4);
            border: none;
            padding: 8px 20px;
            border-radius: 40px;
            color: white;
            font-weight: 500;
            font-size: 0.8rem;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .dashboard-card:hover .btn-card {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(79,70,229,0.3);
        }
        footer {
            text-align: center;
            color: #6c757d;
            font-size: 0.8rem;
            padding: 24px;
            border-top: 1px solid rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        @media (max-width: 640px) {
            nav {
                padding: 12px 20px;
            }
            .container {
                padding: 0 16px;
            }
            .welcome-section h1 {
                font-size: 1.6rem;
            }
            .dashboard-card h3 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">
        <img src="logo.png" alt="College Logo">
        <h2>EventPortal</h2>
    </div>
    <div class="nav-links">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
    <div class="welcome-section">
        <h1>Welcome, Admin <?php echo htmlspecialchars($admin_id); ?> 👋</h1>
        <p>Manage events, registrations, and keep your portal running smoothly.</p>
    </div>

    <div class="dashboard-grid">
        <!-- Add Event -->
        <a href="add_event.php" class="dashboard-card">
            <div class="card-icon"><i class="fas fa-plus-circle"></i></div>
            <h3>Add Event</h3>
            <p>Create a new event with details, fees, and QR code</p>
            <span class="btn-card"><i class="fas fa-arrow-right"></i> Create</span>
        </a>

        <!-- Registrations (View events and participants) -->
        <a href="view_registrations.php" class="dashboard-card">
            <div class="card-icon"><i class="fas fa-clipboard-list"></i></div>
            <h3>Registrations</h3>
            <p>View your events and manage participants</p>
            <span class="btn-card"><i class="fas fa-users"></i> Manage</span>
        </a>

        <!-- Delete Event -->
        <a href="delete_events.php" class="dashboard-card">
            <div class="card-icon"><i class="fas fa-trash-alt"></i></div>
            <h3>Delete Event</h3>
            <p>Remove an event and notify registered students</p>
            <span class="btn-card"><i class="fas fa-trash"></i> Delete</span>
        </a>

        <!-- Change Password -->
        <a href="change_password.php" class="dashboard-card">
            <div class="card-icon"><i class="fas fa-key"></i></div>
            <h3>Change Password</h3>
            <p>Update your admin account password</p>
            <span class="btn-card"><i class="fas fa-lock"></i> Update</span>
        </a>
    </div>
</div>

<footer>
    <p>© <?php echo date('Y'); ?> College Event Hub | Admin Dashboard</p>
</footer>

</body>
</html>