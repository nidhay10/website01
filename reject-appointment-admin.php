<?php
require "db.php";

if (!isset($_GET['id'])) {
    header("Location: admin-dashboard.php?view=appointments");
    exit();
}

$appointment_id = $_GET['id'];

$conn->query("UPDATE appointments SET status='cancelled' WHERE appointment_id='$appointment_id'");

header("Location: admin-dashboard.php?view=appointments");
exit();
?>
