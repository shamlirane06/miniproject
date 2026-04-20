<?php
session_start();
include "db.php";

$message = "";

// Get event_id
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Require login
if(!isset($_SESSION['student_roll'])){
    echo "<script>alert('Please login first'); window.location='student_login.php';</script>";
    exit();
}

$roll = $_SESSION['student_roll'];
$student_name = $_SESSION['student_name'] ?? "";

// Fetch event
$detail_event = null;
$already_registered_options = [];

if($event_id){
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id=?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $detail_event = $res->fetch_assoc();

        // Get already registered types
        $stmt2 = $conn->prepare("SELECT payment_type FROM event_registrations WHERE roll_no=? AND event_id=?");
        $stmt2->bind_param("si", $roll, $event_id);
        $stmt2->execute();
        $reg_result = $stmt2->get_result();

        while($row = $reg_result->fetch_assoc()){
            $already_registered_options[] = $row['payment_type'];
        }
        $stmt2->close();

    } else {
        echo "<script>alert('Event not found'); window.close();</script>";
        exit();
    }
    $stmt->close();
}

// FREE EVENT REGISTRATION
if(isset($_GET['register']) && $_GET['register'] == 'single'){
    if(!in_array('single', $already_registered_options)){

        $type = 'single';

        $stmt = $conn->prepare("INSERT INTO event_registrations (roll_no,event_id,payment_type) VALUES (?,?,?)");
        $stmt->bind_param("sis", $roll, $event_id, $type);
        $stmt->execute();
        $stmt->close();

        $message = "Successfully registered!";
        $already_registered_options[] = 'single';

    } else {
        $message = "Already registered.";
    }
}

// PAID EVENT → REDIRECT TO PAYMENT
if(isset($_POST['pay'])){
    $event_option = $_POST['event_option'];

    if(!in_array($event_option, $already_registered_options)){
        header("Location: payment.php?event_id=$event_id&type=$event_option");
        exit();
    } else {
        $message = "Already registered for $event_option.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Event Details - College Event Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            padding-bottom: 30px;
        }
        /* Navigation */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4);
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
        }
        nav h2 {
            font-size: 1.3rem;
            font-weight: 600;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .nav-links span {
            font-size: 0.9rem;
        }
        .logout-btn {
            background: #dc2626;
            padding: 6px 14px;
            border-radius: 30px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .logout-btn:hover {
            background: #b91c1c;
            transform: scale(1.02);
        }
        /* Container */
        .container {
            max-width: 800px;
            margin: 25px auto;
            padding: 0 20px;
        }
        /* Card */
        .detail-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.15);
            transition: transform 0.2s;
        }
        .card-header {
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            padding: 20px 25px;
            color: white;
        }
        .card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .badge-group {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            color: white;
        }
        .badge-paid {
            background: #f97316;
            color: white;
        }
        .badge-free {
            background: #22c55e;
            color: white;
        }
        .card-body {
            padding: 25px;
        }
        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            font-size: 1rem;
            color: #334155;
        }
        .info-row i {
            width: 24px;
            color: #4f46e5;
            font-weight: 600;
        }
        .description {
            background: #f8fafc;
            padding: 18px;
            border-radius: 16px;
            margin: 20px 0;
            line-height: 1.6;
            color: #1e293b;
        }
        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 16px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        /* Form & Buttons */
        .form-group {
            margin: 20px 0;
        }
        select {
            width: 100%;
            padding: 12px 16px;
            border-radius: 40px;
            border: 1px solid #cbd5e1;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            background: white;
            margin-top: 8px;
        }
        .btn {
            display: block;
            text-align: center;
            background: #4f46e5;
            color: white;
            padding: 14px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
            border: none;
            width: 100%;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn:hover {
            background: #4338ca;
            transform: scale(1.01);
        }
        .btn.disabled {
            background: #9ca3af;
            pointer-events: none;
            opacity: 0.7;
        }
        hr {
            margin: 20px 0;
            border: 0;
            height: 1px;
            background: linear-gradient(to right, #e2e8f0, transparent);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #4f46e5;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 500px) {
            .card-header h1 {
                font-size: 1.4rem;
            }
            .container {
                padding: 0 16px;
            }
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<nav>
    <h2>📅 Event Portal</h2>
    <div class="nav-links">
        <span>👋 <?php echo htmlspecialchars($student_name); ?></span>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
    <?php if($message): ?>
        <div class="message">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="detail-card">
        <div class="card-header">
            <h1><?php echo htmlspecialchars($detail_event['title']); ?></h1>
            <div class="badge-group">
                <?php
                $cats = explode(",", $detail_event['category']);
                foreach($cats as $c){
                    echo "<span class='badge'>".htmlspecialchars(trim($c))."</span>";
                }
                if($detail_event['is_paid']){
                    echo "<span class='badge badge-paid'><i class='fas fa-rupee-sign'></i> Paid</span>";
                } else {
                    echo "<span class='badge badge-free'><i class='fas fa-gift'></i> Free</span>";
                }
                ?>
            </div>
        </div>

        <div class="card-body">
            <div class="info-row">
                <i class="fas fa-calendar-alt"></i>
                <span><strong>Date:</strong> <?php echo date('d F Y', strtotime($detail_event['event_date'])); ?></span>
            </div>
            <div class="info-row">
                <i class="fas fa-map-marker-alt"></i>
                <span><strong>Venue:</strong> <?php echo htmlspecialchars($detail_event['venue']); ?></span>
            </div>
            <?php if($detail_event['is_paid'] && ($detail_event['price_single'] > 0 || $detail_event['price_double'] > 0)): ?>
                <div class="info-row">
                    <i class="fas fa-tags"></i>
                    <span>
                        <strong>Price:</strong>
                        <?php if($detail_event['price_single'] > 0) echo "Single ₹{$detail_event['price_single']} "; ?>
                        <?php if($detail_event['price_double'] > 0) echo "| Double ₹{$detail_event['price_double']}"; ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="description">
                <i class="fas fa-align-left" style="margin-right: 8px; color:#4f46e5;"></i>
                <?php echo nl2br(htmlspecialchars($detail_event['description'])); ?>
            </div>

            <!-- PAID EVENT SECTION -->
            <?php if($detail_event['is_paid']): ?>
                <form method="post">
                    <div class="form-group">
                        <label style="font-weight: 600;">Select Registration Type</label>
                        <select name="event_option" required>
                            <?php if($detail_event['price_single'] > 0): ?>
                                <option value="single" <?php echo in_array('single',$already_registered_options) ? 'disabled' : ''; ?>>
                                    🎫 Single - ₹<?php echo $detail_event['price_single']; ?>
                                </option>
                            <?php endif; ?>
                            <?php if($detail_event['price_double'] > 0): ?>
                                <option value="double" <?php echo in_array('double',$already_registered_options) ? 'disabled' : ''; ?>>
                                    👥 Double - ₹<?php echo $detail_event['price_double']; ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" name="pay" class="btn">
                        <i class="fas fa-credit-card"></i> Proceed to Payment
                    </button>
                </form>

            <!-- FREE EVENT SECTION -->
            <?php else: ?>
                <?php if(in_array('single',$already_registered_options)): ?>
                    <a class="btn disabled"><i class="fas fa-check"></i> Already Registered</a>
                <?php else: ?>
                    <a class="btn" href="view_event_detail.php?event_id=<?php echo $event_id; ?>&register=single">
                        <i class="fas fa-pen-alt"></i> Register Now (Free)
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <hr>
            <a href="student_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Events
            </a>
        </div>
    </div>
</div>

</body>
</html>