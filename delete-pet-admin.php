<?php
require "db.php";

$pet_id = $_POST['pet_id'] ?? '';
if ($pet_id === '') {
    header("Location: admin-dashboard.php?view=pets");
    exit();
}

$pet_id_safe = $conn->real_escape_string($pet_id);

// Remove related data safely
$conn->query("DELETE FROM payments WHERE appointment_id IN (SELECT appointment_id FROM appointments WHERE pet_id='$pet_id_safe')");
$conn->query("DELETE FROM appointments WHERE pet_id='$pet_id_safe'");
$conn->query("DELETE FROM prescriptions WHERE pet_id='$pet_id_safe'");
$conn->query("DELETE FROM pets WHERE pet_id='$pet_id_safe'");

header("Location: admin-dashboard.php?view=pets&deleted=1");
exit();
?>
