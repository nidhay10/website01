<?php
session_start();
require "db.php";

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    header("Location: login.php");
    exit();
}

$appointment_id = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;
$appointment_date = trim($_POST['appointment_date'] ?? '');
$appointment_time = trim($_POST['appointment_time'] ?? '');
$pet_id = isset($_POST['pet_id']) ? (int) $_POST['pet_id'] : 0;
$service_id = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
$vet_id = isset($_POST['vet_id']) ? (int) $_POST['vet_id'] : 0;
$owner_notes = trim($_POST['owner_notes'] ?? '');

if ($appointment_id <= 0 || $appointment_date === '' || $appointment_time === '' || $pet_id <= 0 || $service_id <= 0 || $vet_id <= 0) {
    header("Location: pet-parent-dashboard.php?view=appointments");
    exit();
}

$date_obj = DateTime::createFromFormat("Y-m-d", $appointment_date);
$time_obj = DateTime::createFromFormat("H:i", $appointment_time);
if (!$date_obj || $date_obj->format("Y-m-d") !== $appointment_date || !$time_obj || $time_obj->format("H:i") !== $appointment_time) {
    header("Location: pet-parent-dashboard.php?view=appointments");
    exit();
}

$today = new DateTime("today");
if ($date_obj < $today) {
    header("Location: pet-parent-dashboard.php?view=appointments");
    exit();
}

$check = $conn->prepare("SELECT appointment_id, status FROM appointments WHERE appointment_id = ? AND user_id = ? LIMIT 1");
$check->bind_param("ii", $appointment_id, $user_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    $conn->close();
    header("Location: pet-parent-dashboard.php?view=appointments");
    exit();
}
$check->bind_result($found_id, $current_status);
$check->fetch();
$check->close();

// Validate pet ownership
$pet_check = $conn->prepare("SELECT pet_id FROM pets WHERE pet_id = ? AND user_id = ? LIMIT 1");
$pet_check->bind_param("ii", $pet_id, $user_id);
$pet_check->execute();
$pet_check->store_result();
if ($pet_check->num_rows === 0) {
    $pet_check->close();
    $conn->close();
    header("Location: pet-parent-dashboard.php?view=appointments");
    exit();
}
$pet_check->close();

// Validate service
$service_check = $conn->prepare("SELECT service_id FROM services WHERE service_id = ? LIMIT 1");
$service_check->bind_param("i", $service_id);
$service_check->execute();
$service_check->store_result();
if ($service_check->num_rows === 0) {
    $service_check->close();
    $conn->close();
    header("Location: pet-parent-dashboard.php?view=appointments");
    exit();
}
$service_check->close();

// Validate vet
$vet_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'vet' LIMIT 1");
$vet_check->bind_param("i", $vet_id);
$vet_check->execute();
$vet_check->store_result();
if ($vet_check->num_rows === 0) {
    $vet_check->close();
    $conn->close();
    header("Location: pet-parent-dashboard.php?view=appointments");
    exit();
}
$vet_check->close();

$has_owner_notes = false;
$owner_notes_col = $conn->query("SHOW COLUMNS FROM appointments LIKE 'owner_notes'");
if ($owner_notes_col && $owner_notes_col->num_rows > 0) {
    $has_owner_notes = true;
}

$reset_status = ($current_status === 'rejected' || $current_status === 'cancelled');
if ($has_owner_notes) {
    if ($reset_status) {
        $stmt = $conn->prepare("
            UPDATE appointments
            SET appointment_date = ?, appointment_time = ?, pet_id = ?, service_id = ?, vet_id = ?, owner_notes = ?, status = 'pending'
            WHERE appointment_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssiiisii", $appointment_date, $appointment_time, $pet_id, $service_id, $vet_id, $owner_notes, $appointment_id, $user_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE appointments
            SET appointment_date = ?, appointment_time = ?, pet_id = ?, service_id = ?, vet_id = ?, owner_notes = ?
            WHERE appointment_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssiiisii", $appointment_date, $appointment_time, $pet_id, $service_id, $vet_id, $owner_notes, $appointment_id, $user_id);
    }
} else {
    if ($reset_status) {
        $stmt = $conn->prepare("
            UPDATE appointments
            SET appointment_date = ?, appointment_time = ?, pet_id = ?, service_id = ?, vet_id = ?, status = 'pending'
            WHERE appointment_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssiiiii", $appointment_date, $appointment_time, $pet_id, $service_id, $vet_id, $appointment_id, $user_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE appointments
            SET appointment_date = ?, appointment_time = ?, pet_id = ?, service_id = ?, vet_id = ?
            WHERE appointment_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssiiiii", $appointment_date, $appointment_time, $pet_id, $service_id, $vet_id, $appointment_id, $user_id);
    }
}
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: pet-parent-dashboard.php?view=appointments");
exit();
?>
