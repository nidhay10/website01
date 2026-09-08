<?php
require "db.php";

$prescription_id = $_POST['prescription_id'] ?? '';
$medication = trim($_POST['medication'] ?? '');
$dosage = trim($_POST['dosage'] ?? '');
$frequency = trim($_POST['frequency'] ?? '');
$duration = trim($_POST['duration'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');

if ($prescription_id === '' || $medication === '' || $dosage === '' || $frequency === '' || $duration === '') {
    header("Location: admin-dashboard.php?view=prescriptions");
    exit;
}

$stmt = $conn->prepare("
UPDATE prescriptions
SET medication = ?, dosage = ?, frequency = ?, duration = ?, instructions = ?
WHERE prescription_id = ?
");
$stmt->bind_param("sssssi", $medication, $dosage, $frequency, $duration, $instructions, $prescription_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=prescriptions&updated=1");
exit;
?>
