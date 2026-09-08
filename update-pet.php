<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pet-parent-dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pet_id = $_POST['pet_id'] ?? '';
$pet_name = trim($_POST['pet_name'] ?? '');
$species = trim($_POST['species'] ?? '');
$breed = trim($_POST['breed'] ?? '');
$age = trim($_POST['age'] ?? '');

if ($pet_id === '' || $pet_name === '') {
    header("Location: pet-parent-dashboard.php");
    exit();
}

// Ensure pet belongs to the logged-in user
$check = $conn->query("SELECT pet_id FROM pets WHERE pet_id='$pet_id' AND user_id='$user_id'");
if (!$check || $check->num_rows === 0) {
    header("Location: pet-parent-dashboard.php");
    exit();
}

$conn->query("
UPDATE pets
SET pet_name='$pet_name',
    species='$species',
    breed='$breed',
    age='$age'
WHERE pet_id='$pet_id' AND user_id='$user_id'
");

header("Location: pet-parent-dashboard.php");
exit();
?>
