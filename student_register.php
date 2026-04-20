<?php
include "db.php";
$message = "";

if(isset($_POST['submit'])) {
    $roll = trim($_POST['roll_no']);
    $name = trim($_POST['name']);
    $branch = trim($_POST['branch']);
    $year = trim($_POST['year']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    // Phone validation
    if(!preg_match("/^[0-9]{10}$/", $phone)){
        $message = "Enter valid 10-digit phone number";
    } else {
        $stmt = $conn->prepare("SELECT * FROM students WHERE roll_no = ?");
        $stmt->bind_param("s", $roll);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows > 0){
            $message = "You are already registered. Please login.";
        } else {
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO students (roll_no, name, branch, year, email, phone, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssisss", $roll, $name, $branch, $year, $email, $phone, $password);

            if($stmt->execute()){
                $message = "Registered Successfully. You can login now.";
            } else {
                $message = "Error: " . $stmt->error;
            }
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
    <title>Student Registration - College Event Hub</title>
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
        }
        /* Navbar Styles */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 32px;
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
        .logo i {
            font-size: 28px;
            color: #4f46e5;
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
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-links a:hover {
            color: #4f46e5;
        }
        .login-btn {
            background: #4f46e5;
            padding: 6px 14px;
            border-radius: 30px;
            color: white !important;
        }
        .login-btn:hover {
            background: #4338ca;
            transform: scale(1.02);
        }
        /* Registration Card */
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        .register-card {
            background: white;
            max-width: 500px;
            width: 100%;
            border-radius: 32px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: transform 0.2s;
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
            margin-bottom: 18px;
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
        input, select {
            width: 100%;
            padding: 12px 12px 12px 42px;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            transition: 0.2s;
            background: #f9fafb;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-register {
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
        .btn-register:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.3);
        }
        .message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 40px;
            text-align: center;
            font-size: 0.85rem;
            margin-top: 15px;
            font-weight: 500;
        }
        .message.success {
            background: #dcfce7;
            color: #166534;
        }
        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.85rem;
            color: #4b5563;
        }
        .login-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 640px) {
            nav {
                padding: 12px 20px;
            }
            .nav-links {
                gap: 12px;
            }
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

<nav>
    <div class="logo">
        <i class="fas fa-calendar-alt"></i>
        <h2>EventPortal</h2>
    </div>
    <div class="nav-links">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="student_login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
    </div>
</nav>

<div class="container">
    <div class="register-card">
        <div class="card-header">
            <h2><i class="fas fa-user-graduate"></i> Join Us</h2>
            <p>Create your account to explore events</p>
        </div>

        <div class="card-body">
            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="roll_no" placeholder="Roll Number" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-code-branch"></i>
                    <input type="text" name="branch" placeholder="Branch (e.g., CE, IT)" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-calendar-alt"></i>
                    <input type="number" name="year" placeholder="Year (1-4)" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-phone-alt"></i>
                    <input type="tel" name="phone" placeholder="10-digit Phone Number" required pattern="[0-9]{10}" title="Enter exactly 10 digits">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" name="submit" class="btn-register">
                    <i class="fas fa-arrow-right"></i> Register
                </button>
            </form>

            <?php if($message): 
                $is_success = (strpos($message, 'Successfully') !== false);
            ?>
                <div class="message <?php echo $is_success ? 'success' : ''; ?>">
                    <i class="fas <?php echo $is_success ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="login-link">
                Already have an account? <a href="student_login.php">Login here</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>