<?php
require "db.php";

$feedback_id = $_POST['feedback_id'] ?? '';
if ($feedback_id === '') {
    header("Location: admin-dashboard.php?view=feedback");
    exit;
}

$stmt = $conn->prepare("DELETE FROM feedbacks WHERE feedback_id = ?");
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=feedback&deleted=1");
exit;
?>
