<?php
session_start();
require "db.php";

if (!isset($_GET['id'])) {
    header("Location: vet-dashboard.php");
    exit();
}

$vet_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($vet_id <= 0) {
    header("Location: login.php");
    exit();
}

$appointment_id = (int) $_GET['id'];
if ($appointment_id <= 0) {
    header("Location: vet-dashboard.php");
    exit();
}

$stmt = $conn->prepare("UPDATE appointments SET status='cancelled' WHERE appointment_id=? AND vet_id=?");
$stmt->bind_param("ii", $appointment_id, $vet_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: vet-dashboard.php");
exit();
?>
