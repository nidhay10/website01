<?php
require "db.php";

$pet_id = $_POST['pet_id'] ?? '';
$notes = $_POST['vet_notes'] ?? '';
$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

$pet_id = $conn->real_escape_string($pet_id);
$notes = $conn->real_escape_string($notes);

$has_updated_at = false;
$col_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'vet_notes_updated_at'");
if ($col_check && $col_check->num_rows > 0) {
    $has_updated_at = true;
} else {
    $conn->query("ALTER TABLE pets ADD COLUMN vet_notes_updated_at DATETIME NULL");
    $col_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'vet_notes_updated_at'");
    if ($col_check && $col_check->num_rows > 0) {
        $has_updated_at = true;
    }
}

if ($has_updated_at) {
    $conn->query("UPDATE pets SET vet_notes='$notes', vet_notes_updated_at=NOW() WHERE pet_id='$pet_id'");
} else {
    $conn->query("UPDATE pets SET vet_notes='$notes' WHERE pet_id='$pet_id'");
}

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(["ok" => true]);
    exit();
}

header("Location: vet-dashboard.php");
?>
