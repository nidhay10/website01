<?php
require "db.php";

$id = $_GET['id'];

$conn->query("UPDATE users SET status='rejected' WHERE user_id='$id'");

header("Location: admin-dashboard.php");
?>