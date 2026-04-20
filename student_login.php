<?php
// Persistent login: cookie lasts 30 days (even after browser close)
$lifetime = 30 * 24 * 60 * 60; // 30 days in seconds
session_set_cookie_params($lifetime, '/', '', false, true);
session_start();

include "db.php";
$message = "";
$message_type = "error"; // 'error' or 'success'

// Show logout success message if present
if(isset($_GET['logout']) && $_GET['logout'] == 'success'){
    $message = "You have been successfully logged out.";
    $message_type = "success";
}

if(isset($_POST['login'])){
    $roll = trim($_POST['roll_no']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT name, password FROM students WHERE roll_no = ?");
    $stmt->bind_param("s", $roll);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($db_name, $db_pass);

    if($stmt->num_rows > 0){
        $stmt->fetch();
        // ⚠️ For production, use password_verify() with hashed passwords!
        if($password === $db_pass){
            $_SESSION['student_roll'] = $roll;
            $_SESSION['student_name'] = $db_name;
            header("Location: student_dashboard.php");
            exit();
        } else {
            $message = "Invalid Password";
            $message_type = "error";
        }
    } else {
        $message = "Roll Number not found";
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
    <title>Student Login - College Event Hub</title>
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
        .login-card {
            background: white;
            max-width: 420px;
            width: 100%;
            border-radius: 32px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: transform 0.2s;
            position: relative;
        }
        .card-header {
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            padding: 30px 25px;
            text-align: center;
            color: white;
        }
        .card-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .card-header p {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .card-body {
            padding: 30px 25px;
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
            padding: 12px 12px 12px 42px;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            transition: 0.2s;
            background: #f9fafb;
        }
        input:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-login {
            width: 100%;
            background: linear-gradient(90deg, #4f46e5, #06b6d4);
            border: none;
            padding: 12px;
            border-radius: 50px;
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
            transform: scale(1.02);
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.3);
        }
        .message {
            padding: 10px;
            border-radius: 40px;
            text-align: center;
            font-size: 0.85rem;
            margin-top: 20px;
            font-weight: 500;
        }
        .message.error {
            background: #fee2e2;
            color: #b91c1c;
        }
        .message.success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            animation: fadeOut 4s forwards;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; display: none; }
        }
        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.85rem;
            color: #4b5563;
        }
        .register-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 480px) {
            .card-header {
                padding: 25px 20px;
            }
            .card-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="card-header">
        <h2><i class="fas fa-sign-in-alt"></i> Welcome Back</h2>
        <p>Login to access your dashboard</p>
    </div>

    <div class="card-body">
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="roll_no" placeholder="Roll Number" required>
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
            <div class="message <?php echo $message_type; ?>">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="register-link">
            New Student? <a href="student_register.php">Create an account</a>
        </div>
    </div>
</div>

</body>
</html>