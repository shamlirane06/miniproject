<?php
session_start();
require_once 'config.php';
if(!isset($_SESSION['student_roll'])) exit;
$data = json_decode(file_get_contents('php://input'), true);
$roll = $data['student_roll'] ?? '';
if($roll) {
    $conn = getConnection();
    $conn->query("UPDATE notifications SET is_read = 1 WHERE student_roll = '$roll' AND is_read = 0");
    $conn->close();
}
echo "ok";
?>