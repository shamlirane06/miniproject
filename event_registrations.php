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
$message = "";
$message_type = "";

// Check if we are filtering by a specific event
$event_id_filter = isset($_GET['event_id']) && is_numeric($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$event_title = "";

if($event_id_filter > 0){
    // Verify event belongs to this admin and get its title
    $ev_check = $conn->prepare("SELECT title FROM events WHERE event_id = ? AND admin_id = ?");
    $ev_check->bind_param("ii", $event_id_filter, $admin_id);
    $ev_check->execute();
    $ev_result = $ev_check->get_result();
    if($ev_result->num_rows > 0){
        $event_row = $ev_result->fetch_assoc();
        $event_title = $event_row['title'];
    } else {
        die("Event not found or no permission.");
    }
    $ev_check->close();
}

// Get filter by status (pending/approved/rejected) from URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Export approved participants to CSV (respects event filter if present)
if(isset($_GET['export_approved']) && $_GET['export_approved'] == 'excel'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="approved_participants_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Student Name', 'Roll Number', 'Event Title', 'Event Date', 'Payment Type', 'Approved On']);
    
    $query = "
        SELECT s.name as student_name, r.roll_no, e.title, e.event_date, 
               r.payment_type, r.created_at as approved_on
        FROM event_registrations r
        JOIN events e ON r.event_id = e.event_id
        JOIN students s ON r.roll_no = s.roll_no
        WHERE e.admin_id = ? AND r.payment_status = 'approved'
    ";
    if($event_id_filter > 0){
        $query .= " AND e.event_id = ?";
    }
    $query .= " ORDER BY e.event_date DESC, s.name ASC";
    
    $stmt = $conn->prepare($query);
    if($event_id_filter > 0){
        $stmt->bind_param("ii", $admin_id, $event_id_filter);
    } else {
        $stmt->bind_param("i", $admin_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['student_name'],
            $row['roll_no'],
            $row['title'],
            date('d M Y', strtotime($row['event_date'])),
            ucfirst($row['payment_type'] ?? 'Free'),
            date('d M Y, h:i A', strtotime($row['approved_on']))
        ]);
    }
    fclose($output);
    $stmt->close();
    exit();
}

// Handle approve/reject
if(isset($_GET['action']) && isset($_GET['id'])){
    $action = $_GET['action'];
    $reg_id = intval($_GET['id']);
    
    $fetch = $conn->prepare("
        SELECT r.roll_no, e.title 
        FROM event_registrations r
        JOIN events e ON r.event_id = e.event_id
        WHERE r.id = ? AND e.admin_id = ?
    ");
    $fetch->bind_param("ii", $reg_id, $admin_id);
    $fetch->execute();
    $result = $fetch->get_result();
    
    if($result->num_rows === 0){
        $message = "Registration not found or no permission.";
        $message_type = "error";
    } else {
        $reg = $result->fetch_assoc();
        $student_roll = $reg['roll_no'];
        $event_name = $reg['title'];
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        
        $update = $conn->prepare("UPDATE event_registrations SET payment_status = ? WHERE id = ?");
        $update->bind_param("si", $new_status, $reg_id);
        if($update->execute()){
            $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
            if($table_check && $table_check->num_rows > 0){
                $notif_msg = ($action === 'approve') 
                    ? "Your registration for \"$event_name\" has been approved."
                    : "Your registration for \"$event_name\" has been rejected.";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (student_roll, message, is_read) VALUES (?, ?, 0)");
                $notif_stmt->bind_param("ss", $student_roll, $notif_msg);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
            $message = "Registration $new_status successfully!";
            $message_type = "success";
        } else {
            $message = "Database error: " . $update->error;
            $message_type = "error";
        }
        $update->close();
    }
    $fetch->close();
    
    // Redirect back preserving filters
    $redirect = "event_registrations.php?status=$status_filter";
    if($event_id_filter > 0) $redirect .= "&event_id=$event_id_filter";
    $redirect .= "&msg=" . urlencode($message) . "&type=$message_type";
    header("Location: $redirect");
    exit();
}

// Get message from redirect
if(isset($_GET['msg'])){
    $message = urldecode($_GET['msg']);
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Build query to fetch registrations
$sql = "
    SELECT r.id, r.roll_no, r.payment_type, r.payment_status, r.created_at, r.payment_screenshot,
           e.title, e.event_date, e.event_id,
           s.name as student_name
    FROM event_registrations r
    JOIN events e ON r.event_id = e.event_id
    JOIN students s ON r.roll_no = s.roll_no
    WHERE e.admin_id = ?
";
if($event_id_filter > 0){
    $sql .= " AND e.event_id = ?";
}
if($status_filter == 'pending'){
    $sql .= " AND r.payment_status = 'pending'";
} elseif($status_filter == 'approved'){
    $sql .= " AND r.payment_status = 'approved'";
} elseif($status_filter == 'rejected'){
    $sql .= " AND r.payment_status = 'rejected'";
}
$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if($event_id_filter > 0){
    $stmt->bind_param("ii", $admin_id, $event_id_filter);
} else {
    $stmt->bind_param("i", $admin_id);
}
$stmt->execute();
$registrations = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Registrations</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">

<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-2">
                <i class="fas fa-calendar-alt text-indigo-600 text-2xl"></i>
                <span class="font-bold text-xl text-gray-800">College Event Hub</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="view_registrations.php" class="text-gray-600 hover:text-indigo-600"><i class="fas fa-calendar-alt"></i> My Events</a>
                <a href="admin_dashboard.php" class="text-gray-600 hover:text-indigo-600"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="logout.php" class="text-red-500 hover:text-red-700"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-users"></i> 
            <?php if($event_id_filter > 0): ?>
                Registrations for: <?php echo htmlspecialchars($event_title); ?>
            <?php else: ?>
                All Registrations
            <?php endif; ?>
        </h1>
        <div class="flex gap-2">
            <?php if($event_id_filter > 0): ?>
                <a href="event_registrations.php?event_id=<?php echo $event_id_filter; ?>&export_approved=excel" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-file-excel"></i> Export Approved (this event)
                </a>
            <?php else: ?>
                <a href="?export_approved=excel" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-file-excel"></i> Export All Approved
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if($message): ?>
        <div class="mb-4 p-3 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Status Filter Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3">
        <?php
        $base_url = "event_registrations.php";
        if($event_id_filter > 0) $base_url .= "?event_id=$event_id_filter&";
        else $base_url .= "?";
        ?>
        <a href="<?php echo $base_url; ?>status=all" class="px-4 py-2 rounded-full text-sm font-medium transition <?php echo $status_filter == 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            <i class="fas fa-list"></i> All
        </a>
        <a href="<?php echo $base_url; ?>status=pending" class="px-4 py-2 rounded-full text-sm font-medium transition <?php echo $status_filter == 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            <i class="fas fa-clock"></i> Pending
        </a>
        <a href="<?php echo $base_url; ?>status=approved" class="px-4 py-2 rounded-full text-sm font-medium transition <?php echo $status_filter == 'approved' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            <i class="fas fa-check-circle"></i> Approved
        </a>
        <a href="<?php echo $base_url; ?>status=rejected" class="px-4 py-2 rounded-full text-sm font-medium transition <?php echo $status_filter == 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
            <i class="fas fa-times-circle"></i> Rejected
        </a>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proof</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if($registrations->num_rows > 0): ?>
                        <?php while($row = $registrations->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['roll_no']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y', strtotime($row['event_date'])); ?></td>
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if($row['payment_status'] == 'pending'): ?>
                                        <div class="flex gap-2">
                                            <a href="?action=approve&id=<?php echo $row['id']; ?>&event_id=<?php echo $event_id_filter; ?>&status=<?php echo $status_filter; ?>" 
                                               onclick="return confirm('Approve registration for <?php echo htmlspecialchars($row['student_name']); ?>?')" 
                                               class="bg-green-500 text-white px-3 py-1 rounded-md text-xs hover:bg-green-600">Approve</a>
                                            <a href="?action=reject&id=<?php echo $row['id']; ?>&event_id=<?php echo $event_id_filter; ?>&status=<?php echo $status_filter; ?>" 
                                               onclick="return confirm('Reject registration for <?php echo htmlspecialchars($row['student_name']); ?>?')" 
                                               class="bg-red-500 text-white px-3 py-1 rounded-md text-xs hover:bg-red-600">Reject</a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-8 text-gray-500">
                            <?php if($status_filter == 'pending'): ?>
                                No pending registrations.
                            <?php elseif($status_filter == 'approved'): ?>
                                No approved registrations yet.
                            <?php elseif($status_filter == 'rejected'): ?>
                                No rejected registrations.
                            <?php else: ?>
                                No registrations found.
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if($event_id_filter > 0): ?>
        <div class="mt-6 text-center">
            <a href="view_registrations.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to all events
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>