<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pet-parent-dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pet_id = $_POST['pet_id'] ?? '';

if ($pet_id === '') {
    header("Location: pet-parent-dashboard.php");
    exit();
}

// Ensure pet belongs to the logged-in user
$check = $conn->query("SELECT pet_id FROM pets WHERE pet_id='$pet_id' AND user_id='$user_id'");
if (!$check || $check->num_rows === 0) {
    header("Location: pet-parent-dashboard.php");
    exit();
}

// Remove related data safely
$conn->query("DELETE FROM payments WHERE appointment_id IN (SELECT appointment_id FROM appointments WHERE pet_id='$pet_id' AND user_id='$user_id')");
$conn->query("DELETE FROM appointments WHERE pet_id='$pet_id' AND user_id='$user_id'");
$conn->query("DELETE FROM prescriptions WHERE pet_id='$pet_id'");
$conn->query("DELETE FROM pets WHERE pet_id='$pet_id' AND user_id='$user_id'");

header("Location: pet-parent-dashboard.php?view=pets");
exit();
?>
