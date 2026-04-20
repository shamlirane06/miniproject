<?php
include "db.php";
session_start();

/* Check admin login */
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin'];
$message = "";
$error = "";
$message_type = "";

if(isset($_POST['change'])){
    $old = trim($_POST['old_password']);
    $new = trim($_POST['new_password']);

    // Password strength validation
    if(strlen($new) < 8 ||
       !preg_match("/[A-Z]/", $new) ||
       !preg_match("/[a-z]/", $new) ||
       !preg_match("/[0-9]/", $new)){
        $error = "Password must be at least 8 characters and include uppercase, lowercase & number.";
        $message_type = "error";
    } else {
        // Fetch current admin's password (plain text) using prepared statement
        $stmt = $conn->prepare("SELECT password FROM admins WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->bind_result($db_password);
        $stmt->fetch();
        $stmt->close();

        // Verify old password (plain text comparison)
        if($old === $db_password){
            // Update with new plain text password
            $update = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
            $update->bind_param("si", $new, $admin_id);
            if($update->execute()){
                $message = "Password updated successfully!";
                $message_type = "success";
            } else {
                $error = "Database error: could not update password.";
                $message_type = "error";
            }
            $update->close();
        } else {
            $error = "Old password is incorrect.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Change Password | College Event Hub</title>
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
        .glass-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            padding: 40px 32px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1), 0 0 0 1px rgba(79,70,229,0.1);
            transition: all 0.3s;
            position: relative;
        }
        .glass-card h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #1e293b, #334155);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .subtitle {
            color: #5b6e8c;
            margin-bottom: 28px;
            font-size: 0.85rem;
            text-align: center;
        }
        .input-group {
            margin-bottom: 20px;
            position: relative;
            text-align: left;
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
        .strength {
            font-size: 0.75rem;
            margin-top: 6px;
            margin-left: 12px;
            color: #475569;
        }
        .message, .error {
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
        .error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .back-link {
            margin-top: 25px;
            display: inline-block;
            text-decoration: none;
            color: #4f46e5;
            font-weight: 500;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .back-link:hover {
            color: #06b6d4;
        }
        @media (max-width: 480px) {
            .glass-card {
                padding: 32px 24px;
            }
            .glass-card h2 {
                font-size: 1.5rem;
            }
        }
    </style>
    <script>
        function checkStrength() {
            let pass = document.getElementById("new_password").value;
            let strengthText = document.getElementById("strength");
            let strength = "Weak ❌";
            if(pass.length >= 8 && /[A-Z]/.test(pass) && /[a-z]/.test(pass) && /[0-9]/.test(pass)) {
                strength = "Strong 💪";
            } else if(pass.length >= 6) {
                strength = "Medium ⚠️";
            }
            strengthText.innerHTML = "Strength: " + strength;
        }
    </script>
</head>
<body>

<div class="glass-card">
    <h2><i class="fas fa-lock"></i> Admin Security</h2>
    <div class="subtitle">Update your password</div>

    <form method="POST">
        <div class="input-group">
            <i class="fas fa-key"></i>
            <input type="password" name="old_password" placeholder="Current Password" required>
        </div>
        <div class="input-group">
            <i class="fas fa-pen"></i>
            <input type="password" id="new_password" name="new_password" 
                   placeholder="New Password" onkeyup="checkStrength()" required>
            <div id="strength" class="strength">Strength: Weak ❌</div>
        </div>
        <button type="submit" name="change"><i class="fas fa-save"></i> Update Password</button>
    </form>

    <?php if($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php elseif($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <a href="admin_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

</body>
</html>