<?php
session_start();
include "db.php";

/* ✅ STUDENT LOGIN CHECK */
if(!isset($_SESSION['student_roll'])){
    header("Location: student_login.php");
    exit();
}

$roll = $_SESSION['student_roll'];
$student_name = $_SESSION['student_name'] ?? '';
$message = "";
$message_type = ""; // 'success' or 'error'

if(isset($_POST['change_password'])){

    $old_pass = trim($_POST['old_password']);
    $new_pass = trim($_POST['new_password']);
    $confirm_pass = trim($_POST['confirm_password']);

    // GET CURRENT PASSWORD
    $stmt = $conn->prepare("SELECT password FROM students WHERE roll_no=?");
    $stmt->bind_param("s", $roll);
    $stmt->execute();
    $stmt->bind_result($db_pass);
    $stmt->fetch();
    $stmt->close();

    // VALIDATIONS
    if($old_pass !== $db_pass){
        $message = "❌ Old password is incorrect!";
        $message_type = "error";
    }
    elseif($new_pass != $confirm_pass){
        $message = "❌ New passwords do not match!";
        $message_type = "error";
    }
    elseif(strlen($new_pass) < 4){
        $message = "❌ Password must be at least 4 characters!";
        $message_type = "error";
    }
    else{
        // UPDATE PASSWORD
        $stmt = $conn->prepare("UPDATE students SET password=? WHERE roll_no=?");
        $stmt->bind_param("ss", $new_pass, $roll);

        if($stmt->execute()){
            $message = "✅ Password changed successfully!";
            $message_type = "success";
        } else {
            $message = "❌ Error updating password!";
            $message_type = "error";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Change Password | College Event Hub</title>
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
        nav h2 i {
            background: none;
            color: #4f46e5;
            font-size: 1.6rem;
        }
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
        .nav-right span i {
            color: #4f46e5;
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
        .nav-right a:hover {
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
            color: white !important;
        }
        /* Container */
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
            padding: 40px 20px;
        }
        .box {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(8px);
            border-radius: 48px;
            padding: 40px 32px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1), 0 0 0 1px rgba(79,70,229,0.1);
            transition: all 0.3s;
        }
        .box h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
            background: linear-gradient(135deg, #1e293b, #334155);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        .subtitle {
            text-align: center;
            color: #5b6e8c;
            margin-bottom: 28px;
            font-size: 0.85rem;
        }
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
        }
        input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border: 1px solid #e2e8f0;
            border-radius: 60px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            transition: 0.2s;
            background: white;
        }
        input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }
        button {
            width: 100%;
            background: linear-gradient(95deg, #4f46e5, #06b6d4);
            border: none;
            padding: 14px;
            border-radius: 60px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(79,70,229,0.3);
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 40px;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .message.success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .message.error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .password-hint {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 5px;
            text-align: left;
            padding-left: 12px;
        }
        @media (max-width: 640px) {
            nav {
                padding: 12px 20px;
            }
            .box {
                padding: 30px 24px;
            }
            .box h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<nav>
    <h2><i class="fas fa-key"></i> Change Password</h2>
    <div class="nav-right">
        <span><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($student_name); ?></span>
        <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
    <div class="box">
        <h3>Update Password</h3>
        <div class="subtitle">Keep your account secure</div>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="old_password" placeholder="Current Password" required>
            </div>
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="password" name="new_password" id="new_pass" placeholder="New Password" required>
            </div>
            <div class="input-group">
                <i class="fas fa-check-circle"></i>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            </div>
            <div class="password-hint">
                <i class="fas fa-info-circle"></i> Password must be at least 4 characters
            </div>
            <button type="submit" name="change_password">
                <i class="fas fa-save"></i> Change Password
            </button>
        </form>

        <?php if($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>