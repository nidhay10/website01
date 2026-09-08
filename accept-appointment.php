<?php
session_start();
require "db.php";

if (!isset($_GET['id'])) {
    header("Location: vet-dashboard.php");
    exit();
}

$vet_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'];

$conn->query("UPDATE appointments SET status='confirmed' WHERE appointment_id='$appointment_id' AND vet_id='$vet_id'");

header("Location: vet-dashboard.php");
exit();
?>
