<?php
require "db.php";

$feedback_id = $_POST['feedback_id'] ?? '';
$visit_type = trim($_POST['visit_type'] ?? '');
$vet_name = trim($_POST['vet_name'] ?? '');
$visit_date = $_POST['visit_date'] ?? '';
$rating = $_POST['rating'] ?? '';
$comments = trim($_POST['comments'] ?? '');
$contact_ok = isset($_POST['contact_ok']) ? 1 : 0;

if ($feedback_id === '' || $visit_type === '' || $vet_name === '' || $visit_date === '' || $rating === '' || $comments === '') {
    header("Location: admin-dashboard.php?view=feedback");
    exit();
}

$stmt = $conn->prepare("
UPDATE feedbacks
SET visit_type = ?, vet_name = ?, visit_date = ?, rating = ?, comments = ?, contact_ok = ?
WHERE feedback_id = ?
");
$rating_int = (int)$rating;
$stmt->bind_param("sssissi", $visit_type, $vet_name, $visit_date, $rating_int, $comments, $contact_ok, $feedback_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=feedback&feedback_updated=1");
exit();
?>
