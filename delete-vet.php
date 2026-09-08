<?php
require "db.php";

$user_id = $_POST['user_id'] ?? '';
if ($user_id === '') {
    header("Location: admin-dashboard.php?view=vets");
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'vet'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=vets&deleted=1");
exit;
?>
