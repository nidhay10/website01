<?php
require "db.php";

$service_id = $_POST['service_id'] ?? '';
$service_name = trim($_POST['service_name'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($service_id === '' || $service_name === '' || $description === '') {
    header("Location: admin-dashboard.php?view=services");
    exit();
}

$stmt = $conn->prepare("UPDATE services SET service_name = ?, description = ? WHERE service_id = ?");
$stmt->bind_param("ssi", $service_name, $description, $service_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=services&service_updated=1");
exit();
?>
