<?php
include "db.php";

// Set persistent cookie parameters BEFORE session start
$lifetime = 30 * 24 * 60 * 60; // 30 days
session_set_cookie_params($lifetime, '/', '', false, true);

// Now start the session
session_start();

$message = "";
$message_type = "";

if(isset($_POST['login'])){
    $admin_id = trim($_POST['admin_id']);
    $password = trim($_POST['password']);

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ? AND password = ?");
    $stmt->bind_param("ss", $admin_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $_SESSION['admin'] = $admin_id;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $message = "Invalid Admin ID or Password";
        $message_type = "error";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Login | College Event Hub</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1), 0 0 0 1px rgba(79,70,229,0.1);
            overflow: hidden;
            transition: transform 0.2s;
            position: relative;
        }
        .card-header {
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            padding: 32px 25px;
            text-align: center;
            color: white;
        }
        .card-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .card-header p {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .card-body {
            padding: 32px 28px;
        }
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        .input-group i {
            position: absolute;
            left: 15px;
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
        .btn-login {
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
        .btn-login:hover {
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
        .message.error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
        }
        .back-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 480px) {
            .card-header {
                padding: 25px 20px;
            }
            .card-body {
                padding: 28px 20px;
            }
            .card-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="card-header">
        <h2><i class="fas fa-shield-alt"></i> Admin Login</h2>
        <p>Access the event management panel</p>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-id-badge"></i>
                <input type="text" name="admin_id" placeholder="Admin ID" inputmode="numeric" pattern="[0-9]+" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn-login">
                <i class="fas fa-arrow-right"></i> Login
            </button>
        </form>

        <?php if($message): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="back-link">
            <a href="index.php"><i class="fas fa-home"></i> Back to Home</a>
        </div>
    </div>
</div>

</body>
</html>