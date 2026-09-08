<?php
session_start();
require "db.php";

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    header("Location: login.php");
    exit();
}

$appointment_id = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;
if ($appointment_id <= 0) {
    header("Location: pet-parent-dashboard.php?view=appointments&refund=error");
    exit();
}

$stmt = $conn->prepare("SELECT status FROM appointments WHERE appointment_id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$stmt->bind_result($status);
if (!$stmt->fetch()) {
    $stmt->close();
    $conn->close();
    header("Location: pet-parent-dashboard.php?view=appointments&refund=error");
    exit();
}
$stmt->close();

if ($status !== 'rejected' && $status !== 'cancelled') {
    $conn->close();
    header("Location: pet-parent-dashboard.php?view=appointments&refund=error");
    exit();
}

// Ensure refund_requested column exists
$refund_col = $conn->query("SHOW COLUMNS FROM appointments LIKE 'refund_requested'");
if (!$refund_col || $refund_col->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN refund_requested TINYINT(1) NOT NULL DEFAULT 0");
}

// Mark appointment as refund requested
$mark = $conn->prepare("UPDATE appointments SET refund_requested = 1 WHERE appointment_id = ? AND user_id = ?");
$mark->bind_param("ii", $appointment_id, $user_id);
$mark->execute();
$mark->close();

// Update payment status if a payment record exists
$pay = $conn->prepare("UPDATE payments SET payment_status='refund_requested' WHERE appointment_id = ?");
if ($pay) {
    $pay->bind_param("i", $appointment_id);
    $pay->execute();
    $pay->close();
}

$conn->close();
header("Location: pet-parent-dashboard.php?view=appointments&refund=requested");
exit();
?>
