<?php
require "db.php";

$prescription_id = $_POST['prescription_id'] ?? '';
if ($prescription_id === '') {
    header("Location: admin-dashboard.php?view=prescriptions");
    exit;
}

$stmt = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=prescriptions&deleted=1");
exit;
?>
