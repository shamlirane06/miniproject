<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "db.php";

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin'];

if(!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])){
    die("Invalid event ID");
}
$event_id = intval($_GET['event_id']);

// Verify event belongs to this admin
$check = $conn->prepare("SELECT title FROM events WHERE event_id = ? AND admin_id = ?");
$check->bind_param("ii", $event_id, $admin_id);
$check->execute();
$event = $check->get_result()->fetch_assoc();
if(!$event){
    die("Event not found or you don't have permission.");
}
$event_title = $event['title'];

// Handle approve/reject actions
if(isset($_GET['action']) && isset($_GET['reg_id'])){
    $action = $_GET['action'];
    $reg_id = intval($_GET['reg_id']);
    $new_status = ($action == 'approve') ? 'approved' : 'rejected';

    // First, verify this registration belongs to this event
    $verify = $conn->prepare("SELECT roll_no FROM event_registrations WHERE id = ? AND event_id = ?");
    $verify->bind_param("ii", $reg_id, $event_id);
    $verify->execute();
    $verify->bind_result($roll);
    if($verify->fetch()){
        $verify->close();
        
        // Update registration status
        $update = $conn->prepare("UPDATE event_registrations SET payment_status = ? WHERE id = ?");
        $update->bind_param("si", $new_status, $reg_id);
        if($update->execute()){
            // Send notification (if notifications table exists)
            $message = ($action == 'approve') 
                ? "Your registration for '$event_title' has been approved."
                : "Your registration for '$event_title' has been rejected.";
            
            $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
            if($table_check && $table_check->num_rows > 0){
                $notif = $conn->prepare("INSERT INTO notifications (student_roll, message, is_read) VALUES (?, ?, 0)");
                $notif->bind_param("ss", $roll, $message);
                $notif->execute();
                $notif->close();
            }
            $success_msg = "Registration $new_status successfully!";
        } else {
            $error_msg = "Database update failed: " . $update->error;
        }
        $update->close();
    } else {
        $error_msg = "Registration not found for this event.";
    }
    
    // Redirect with message
    $redirect_url = "view_participants.php?event_id=$event_id";
    if(isset($success_msg)) $redirect_url .= "&msg=" . urlencode($success_msg);
    if(isset($error_msg)) $redirect_url .= "&error=" . urlencode($error_msg);
    header("Location: $redirect_url");
    exit();
}

// Get message from redirect
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Fetch all registered participants for this event
$participants = $conn->prepare("
    SELECT r.id, r.roll_no, r.payment_type, r.payment_status, r.created_at, r.payment_screenshot,
           s.name as student_name
    FROM event_registrations r
    JOIN students s ON r.roll_no = s.roll_no
    WHERE r.event_id = ?
    ORDER BY r.created_at DESC
");
$participants->bind_param("i", $event_id);
$participants->execute();
$result = $participants->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants - <?php echo htmlspecialchars($event_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">

<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-2">
                <i class="fas fa-calendar-alt text-indigo-600 text-2xl"></i>
                <span class="font-bold text-xl text-gray-800">College Event Hub</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="event_registrations.php" class="text-gray-600 hover:text-indigo-600"><i class="fas fa-arrow-left"></i> Back to Events</a>
                <a href="admin_dashboard.php" class="text-gray-600 hover:text-indigo-600"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="logout.php" class="text-red-500 hover:text-red-700"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Success/Error Messages -->
    <?php if($msg): ?>
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-800 border border-green-200">
            <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-800 border border-red-200">
            <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">🎯 <?php echo htmlspecialchars($event_title); ?></h1>
        <p class="text-gray-600">Manage registrations for this event</p>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Proof</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered On</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars($row['roll_no']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo ucfirst($row['payment_type'] ?? 'Free'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if(!empty($row['payment_screenshot'])): ?>
                                    <a href="uploads/payments/<?php echo $row['payment_screenshot']; ?>" target="_blank" class="text-indigo-600 hover:underline">View</a>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">No proof</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    <?php echo $row['payment_status'] == 'approved' ? 'bg-green-100 text-green-800' : ($row['payment_status'] == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                    <?php echo ucfirst($row['payment_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if($row['payment_status'] == 'pending'): ?>
                                    <div class="flex space-x-2">
                                        <a href="?event_id=<?php echo $event_id; ?>&action=approve&reg_id=<?php echo $row['id']; ?>" 
                                           onclick="return confirm('Approve registration for <?php echo htmlspecialchars($row['student_name']); ?>?')"
                                           class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-xs font-medium transition">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                        <a href="?event_id=<?php echo $event_id; ?>&action=reject&reg_id=<?php echo $row['id']; ?>" 
                                           onclick="return confirm('Reject registration for <?php echo htmlspecialchars($row['student_name']); ?>?')"
                                           class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-xs font-medium transition">
                                            <i class="fas fa-times"></i> Reject
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-user-slash text-4xl mb-2 block"></i>
                                No students have registered for this event yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 text-center">
        <a href="event_registrations.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800">
            <i class="fas fa-arrow-left mr-2"></i> Back to all events
        </a>
    </div>
</div>

</body>
</html>