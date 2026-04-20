<?php
session_start();
include "db.php";

// CHECK ADMIN LOGIN
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin'];

// CHECK IF ID IS PROVIDED AND VALID
if(isset($_GET['id']) && is_numeric($_GET['id'])){
    $event_id = intval($_GET['id']);
    
    // VERIFY EVENT BELONGS TO THIS ADMIN AND FETCH TITLE
    $check = $conn->prepare("SELECT title FROM events WHERE event_id = ? AND admin_id = ?");
    $check->bind_param("ii", $event_id, $admin_id);
    $check->execute();
    $result = $check->get_result();
    
    if($result->num_rows === 0){
        $_SESSION['delete_error'] = "Event not found or you don't have permission to delete it.";
        header("Location: delete_events.php");
        exit();
    }
    
    $event = $result->fetch_assoc();
    $event_title = $event['title'];
    $check->close();
    
    // (Optional) DELETE REGISTRATIONS FIRST – if foreign key doesn't have CASCADE
    $del_reg = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ?");
    $del_reg->bind_param("i", $event_id);
    $del_reg->execute();
    $del_reg->close();
    
    // (Optional) NOTIFY REGISTERED STUDENTS – if notifications table exists
    // Fetch all students who registered for this event
    $students_stmt = $conn->prepare("SELECT DISTINCT roll_no FROM event_registrations WHERE event_id = ?");
    $students_stmt->bind_param("i", $event_id);
    $students_stmt->execute();
    $students_res = $students_stmt->get_result();
    
    // Only if notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if($table_check && $table_check->num_rows > 0) {
        $notif_stmt = $conn->prepare("INSERT INTO notifications (student_roll, message, is_read) VALUES (?, ?, 0)");
        $message = "The event \"$event_title\" has been cancelled by the admin. We regret the inconvenience.";
        while($student = $students_res->fetch_assoc()){
            $notif_stmt->bind_param("ss", $student['roll_no'], $message);
            $notif_stmt->execute();
        }
        $notif_stmt->close();
    }
    $students_stmt->close();
    
    // DELETE THE EVENT
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $event_id, $admin_id);
    
    if($stmt->execute()){
        if($stmt->affected_rows > 0){
            $_SESSION['delete_success'] = "Event \"$event_title\" has been deleted successfully.";
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

header("Location: delete_events.php");
exit();
?>