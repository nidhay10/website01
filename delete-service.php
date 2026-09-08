<?php
require "db.php";

$id = $_GET['id'];

$conn->query("DELETE FROM services WHERE service_id=$id");

header("Location: admin-dashboard.php?view=services");
?>
