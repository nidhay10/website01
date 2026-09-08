<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pet_id = $_POST['pet_id'] ?? null;
$visit_type = trim($_POST['visit_type'] ?? '');
$vet_name = trim($_POST['vet_name'] ?? '');
$visit_date = $_POST['visit_date'] ?? '';
$rating = $_POST['rating'] ?? '';
$comments = trim($_POST['comments'] ?? '');
$contact_ok = isset($_POST['contact_ok']) ? 1 : 0;

if ($visit_type === '' || $vet_name === '' || $visit_date === '' || $rating === '' || $comments === '') {
    header("Location: pet-parent-dashboard.php?view=feedback&feedback=error");
    exit();
}

$conn->query("
CREATE TABLE IF NOT EXISTS feedbacks (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NULL,
    visit_type VARCHAR(80) NOT NULL,
    vet_name VARCHAR(120) NOT NULL,
    visit_date DATE NOT NULL,
    rating INT NOT NULL,
    comments TEXT NOT NULL,
    contact_ok TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

$stmt = $conn->prepare("INSERT INTO feedbacks (user_id, pet_id, visit_type, vet_name, visit_date, rating, comments, contact_ok) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$pet_id_param = ($pet_id === '' || $pet_id === null) ? null : (int)$pet_id;
$rating_int = (int)$rating;
$stmt->bind_param("iisssisi", $user_id, $pet_id_param, $visit_type, $vet_name, $visit_date, $rating_int, $comments, $contact_ok);
$stmt->execute();
$stmt->close();

header("Location: pet-parent-dashboard.php?view=feedback&feedback=sent");
exit();
?>
