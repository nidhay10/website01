<?php
require "db.php";

$user_id = $_POST['user_id'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$clinic_name = trim($_POST['clinic_name'] ?? '');
$clinic_address = trim($_POST['clinic_address'] ?? '');
$vet_registration = trim($_POST['vet_registration'] ?? '');

if ($user_id === '' || $name === '' || $email === '') {
    header("Location: admin-dashboard.php?view=vets");
    exit();
}

$stmt = $conn->prepare("
UPDATE users
SET name = ?, email = ?, clinic_name = ?, clinic_address = ?, vet_registration = ?
WHERE user_id = ? AND role = 'vet'
");
$stmt->bind_param("sssssi", $name, $email, $clinic_name, $clinic_address, $vet_registration, $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=vets");
exit();
?>
