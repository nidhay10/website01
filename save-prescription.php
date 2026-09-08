<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$vet_id = $_SESSION['user_id'];

$pet_id = $_POST['pet_id'] ?? '';
$medication = trim($_POST['medication'] ?? '');
$dosage = trim($_POST['dosage'] ?? '');
$frequency = trim($_POST['frequency'] ?? '');
$duration = trim($_POST['duration'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');

if ($pet_id === '' || $medication === '' || $dosage === '' || $frequency === '' || $duration === '') {
    header("Location: vet-dashboard.php?view=prescriptions");
    exit;
}

$conn->query("
CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    vet_id INT NOT NULL,
    medication VARCHAR(120) NOT NULL,
    dosage VARCHAR(120) NOT NULL,
    frequency VARCHAR(120) NOT NULL,
    duration VARCHAR(120) NOT NULL,
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

$stmt = $conn->prepare("INSERT INTO prescriptions (pet_id, vet_id, medication, dosage, frequency, duration, instructions) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iisssss", $pet_id, $vet_id, $medication, $dosage, $frequency, $duration, $instructions);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: vet-dashboard.php?view=prescriptions&prescribed=1");
exit;
?>
