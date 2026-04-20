<?php
session_start();
include "db.php";

// CHECK ADMIN LOGIN
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin'];

// CHECK IF ID IS PROVIDED
if(isset($_GET['id']) && is_numeric($_GET['id'])){
    $event_id = intval($_GET['id']);

    // OPTIONAL: Fetch event details before deletion (for notifications)
    $event_query = $conn->prepare("SELECT title FROM events WHERE event_id = ? AND admin_id = ?");
    $event_query->bind_param("ii", $event_id, $admin_id);
    $event_query->execute();
    $event_result = $event_query->get_result();
    
    if($event_result->num_rows === 0){
        $_SESSION['delete_error'] = "Event not found or you don't have permission to delete it.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    $event_data = $event_result->fetch_assoc();
    $event_title = $event_data['title'];
    $event_query->close();

    // DELETE REGISTRATIONS FIRST (if you have foreign key without CASCADE)
    $delete_reg = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ?");
    $delete_reg->bind_param("i", $event_id);
    $delete_reg->execute();
    $delete_reg->close();

    // NOTIFY ALL REGISTERED STUDENTS (optional)
    // Fetch all students registered for this event
    $students_stmt = $conn->prepare("SELECT DISTINCT roll_no FROM event_registrations WHERE event_id = ?");
    $students_stmt->bind_param("i", $event_id);
    $students_stmt->execute();
    $students_res = $students_stmt->get_result();
    
    $notif_stmt = $conn->prepare("INSERT INTO notifications (student_roll, message, is_read) VALUES (?, ?, 0)");
    $message = "The event \"$event_title\" has been cancelled by the admin. We regret the inconvenience.";
    
    while($student = $students_res->fetch_assoc()){
        $notif_stmt->bind_param("ss", $student['roll_no'], $message);
        $notif_stmt->execute();
    }
    $students_stmt->close();
    $notif_stmt->close();

    // NOW DELETE THE EVENT
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $event_id, $admin_id);
    
    if($stmt->execute()){
        if($stmt->affected_rows > 0){
            $_SESSION['delete_success'] = "Event \"$event_title\" has been deleted successfully.";
            // Also notify students (already done above)
        } else {
            $_SESSION['delete_error'] = "Event could not be deleted. It may have already been removed.";
        }
    } else {
        $_SESSION['delete_error'] = "Database error: " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['delete_error'] = "Invalid event ID.";
}

header("Location: admin_dashboard.php");
exit();
?>