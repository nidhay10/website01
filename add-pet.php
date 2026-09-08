<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require "db.php";

$user_id = $_SESSION['user_id'];

$pet_name = $_POST['pet_name'];
$species = $_POST['species'];
$breed = $_POST['breed'];
$age = $_POST['age'];

$filename = '';
if (isset($_FILES['pet_image']) && !empty($_FILES['pet_image']['name'])) {
    $filename = $_FILES['pet_image']['name'];
    $tmpname = $_FILES['pet_image']['tmp_name'];
    $folder = "uploads/" . $filename;
    move_uploaded_file($tmpname, $folder);
}

$sql = "INSERT INTO pets (user_id, pet_name, species, breed, age)
VALUES ('$user_id', '$pet_name', '$species', '$breed', '$age')";

$conn->query("INSERT INTO pets (user_id, pet_name, species, breed, age)
VALUES ('$user_id','$pet_name','$species','$breed','$age')");

header("Location: pet-parent-dashboard.php");
exit();
?>
