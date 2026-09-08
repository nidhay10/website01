<?php
require "db.php";

$appointment_id = $_POST['appointment_id'] ?? '';
$appointment_date = $_POST['appointment_date'] ?? '';
$appointment_time = $_POST['appointment_time'] ?? '';
$service_id = $_POST['service_id'] ?? '';
$vet_id = $_POST['vet_id'] ?? '';

if ($appointment_id === '' || $appointment_date === '' || $appointment_time === '' || $service_id === '' || $vet_id === '') {
    header("Location: admin-dashboard.php?view=appointments");
    exit();
}

$stmt = $conn->prepare("
UPDATE appointments
SET appointment_date = ?, appointment_time = ?, service_id = ?, vet_id = ?
WHERE appointment_id = ?
");
$stmt->bind_param("ssiii", $appointment_date, $appointment_time, $service_id, $vet_id, $appointment_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=appointments");
exit();
?>
