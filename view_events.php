<?php
session_start();
include "db.php";

// Check if student is logged in
$roll = isset($_SESSION['student_roll']) ? $_SESSION['student_roll'] : null;
$student_name = isset($_SESSION['student_name']) ? $_SESSION['student_name'] : "Guest";

// Get category filter
$filter_cat = isset($_GET['cat']) ? strtoupper($_GET['cat']) : 'ALL';

// Fetch all events
$all_events_result = $conn->query("SELECT * FROM events ORDER BY event_date ASC");

// Fetch student registrations if logged in
$registrations = [];
if($roll){
    $stmt = $conn->prepare("SELECT event_id, event_type FROM event_registrations WHERE roll_no=?");
    $stmt->bind_param("s", $roll);
    $stmt->execute();
    $registrations_res = $stmt->get_result();
    while($reg = $registrations_res->fetch_assoc()){
        $registrations[$reg['event_id']] = $reg['event_type'];
    }
    $stmt->close();
}

// Define all category tabs
$all_tabs = ['ALL','CE','IT','AIDS','AIML','ELEC','MECH','COMMON'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#eef2ff;margin:0;}
nav{display:flex;justify-content:space-between;align-items:center;padding:10px 20px;background:#4f46e5;color:white;position:sticky;top:0;flex-wrap:wrap;}
.nav-links{display:flex;align-items:center;gap:15px;}
.home-btn{background:#06b6d4;padding:8px 15px;border-radius:10px;color:white;text-decoration:none;font-weight:600;}
.logout-btn{background:red;padding:8px 15px;border-radius:10px;color:white;text-decoration:none;font-weight:600;}
.tabs{text-align:center;margin:15px;overflow-x:auto;white-space:nowrap;}
.tabs a{display:inline-block;margin:0 8px;padding:8px 15px;border-radius:20px;text-decoration:none;background:#ddd;color:#333;transition:0.3s;}
.tabs a.active{background:#4f46e5;color:white;}
.tabs a:hover{background:#06b6d4;color:white;}

/* GRID LAYOUT - Vertical scroll with multiple columns */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Card style - unchanged */
.card{
    background:white;
    border-radius:10px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
    overflow:hidden;
    transition:0.3s;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.card:hover{transform:translateY(-5px);}
.card-top{background:linear-gradient(90deg,#4f46e5,#06b6d4);color:white;padding:15px;}
.card-body{padding:15px;flex:1;display:flex;flex-direction:column;}
.badge{padding:4px 8px;border-radius:10px;font-size:12px;color:white;margin-right:5px;display:inline-block;}
.badge.free{background:green;}
.badge.paid{background:red;}
.badge.option{background:#06b6d4;}
.btn{display:block;text-align:center;margin-top:10px;padding:10px;background:#4f46e5;color:white;text-decoration:none;border-radius:20px;transition:0.3s;cursor:pointer;}
.btn:hover{transform:scale(1.05);}
.btn.disabled{background:gray;pointer-events:none;}

/* Responsive: 1 column on very small phones, 2 columns on tablets, 3-4 on desktop */
@media (max-width: 640px) {
    .events-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        padding: 15px;
    }
}
@media (min-width: 641px) and (max-width: 900px) {
    .events-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (min-width: 901px) and (max-width: 1200px) {
    .events-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (min-width: 1201px) {
    .events-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>
<script>
function checkLogin(eventId, loggedIn) {
    if(loggedIn) {
        window.location.href = "view_event_detail.php?event_id=" + eventId;
    } else {
        alert("Please login to view details!");
    }
}
</script>
</head>
<body>

<nav>
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="index.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:white;">
            <img src="logo.png" alt="Event Portal Logo" style="height:40px;">
            <h2>Event Portal</h2>
        </a>
    </div>
    <div class="nav-links">
        <span><?php echo $roll ? "Welcome " . htmlspecialchars($student_name) : "Welcome Guest"; ?></span>
        <a href="index.php" class="home-btn">Home</a>
        <?php if($roll): ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="student_login.php" class="logout-btn">Login</a>
        <?php endif; ?>
    </div>
</nav>

<header style="text-align:center;padding:20px;">
    <h1>Student Dashboard</h1>
    <p>Browse events – scroll down to see more</p>
</header>

<!-- Category Tabs -->
<div class="tabs">
<?php foreach($all_tabs as $tab): ?>
    <a href="?cat=<?php echo $tab; ?>" class="<?php echo ($filter_cat==$tab)?'active':''; ?>"><?php echo $tab; ?></a>
<?php endforeach; ?>
</div>

<!-- Events Grid (vertical scroll, 3-4 per row) -->
<div class="events-grid">
<?php 
$has_events = false;
while($row = $all_events_result->fetch_assoc()):
    $event_cats = array_map('trim', explode(',', strtoupper($row['category'])));
    if($filter_cat != 'ALL' && !in_array($filter_cat, $event_cats)) continue;
    $has_events = true;
    
    $event_id = $row['event_id'];
    $registered = $roll && isset($registrations[$event_id]);
    $option = $registered ? ucfirst($registrations[$event_id]) : '';
    $is_paid = $row['is_paid'];
?>
<div class="card">
    <div class="card-top">
        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
        <span class="badge <?php echo $is_paid?'paid':'free'; ?>"><?php echo $is_paid?'Paid':'Free'; ?></span>
        <?php if($registered): ?>
            <span class="badge option"><?php echo htmlspecialchars($option); ?></span>
        <?php endif; ?>
        <?php foreach($event_cats as $c): ?>
            <span class="badge"><?php echo htmlspecialchars($c); ?></span>
        <?php endforeach; ?>
    </div>
    <div class="card-body">
        <p><b>📅 Date:</b> <?php echo htmlspecialchars($row['event_date']); ?></p>
        <p><b>📍 Venue:</b> <?php echo htmlspecialchars($row['venue']); ?></p>
        <p><?php echo htmlspecialchars(substr($row['description'],0,100)); ?>...</p>

        <?php if($registered): ?>
            <span class="btn disabled">Already Registered</span>
        <?php else: ?>
            <span class="btn" onclick="checkLogin(<?php echo $event_id; ?>, <?php echo $roll? 'true':'false'; ?>)">View & Register</span>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>
</div>

<?php if(!$has_events): ?>
<div style="text-align:center; padding:40px;">No events in this category.</div>
<?php endif; ?>

<!-- Font Awesome for icons (optional, kept for potential future use) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</body>
</html>