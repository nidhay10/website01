<?php
require "db.php";

$pet_id = $_POST['pet_id'] ?? '';
$pet_name = trim($_POST['pet_name'] ?? '');
$pet_type = trim($_POST['pet_type'] ?? '');
$pet_age = $_POST['pet_age'] ?? '';
$breed = trim($_POST['breed'] ?? '');
$vet_notes = trim($_POST['vet_notes'] ?? '');

if ($pet_id === '' || $pet_name === '') {
    header("Location: admin-dashboard.php?view=pets");
    exit();
}

$type_col = null;
$type_alt_col = null;
$type_species_col = null;
$type_species_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'species'");
if ($type_species_check && $type_species_check->num_rows > 0) {
    $type_species_col = "species";
}
$type_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'type'");
if ($type_check && $type_check->num_rows > 0) {
    $type_col = "type";
}
$type_check_alt = $conn->query("SHOW COLUMNS FROM pets LIKE 'pet_type'");
if ($type_check_alt && $type_check_alt->num_rows > 0) {
    $type_alt_col = "pet_type";
}

$age_col = null;
$age_alt_col = null;
$age_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'age'");
if ($age_check && $age_check->num_rows > 0) {
    $age_col = "age";
}
$age_check_alt = $conn->query("SHOW COLUMNS FROM pets LIKE 'pet_age'");
if ($age_check_alt && $age_check_alt->num_rows > 0) {
    $age_alt_col = "pet_age";
}

$fields = "pet_name = ?, breed = ?, vet_notes = ?";
$types = "sss";
$values = [$pet_name, $breed, $vet_notes];

if ($type_col) {
    $fields .= ", {$type_col} = ?";
    $types .= "s";
    $values[] = $pet_type;
}
if ($type_alt_col) {
    $fields .= ", {$type_alt_col} = ?";
    $types .= "s";
    $values[] = $pet_type;
}
if ($type_species_col) {
    $fields .= ", {$type_species_col} = ?";
    $types .= "s";
    $values[] = $pet_type;
}

if ($age_col) {
    $fields .= ", {$age_col} = ?";
    $types .= "i";
    $values[] = ($pet_age === '' ? null : (int)$pet_age);
}
if ($age_alt_col) {
    $fields .= ", {$age_alt_col} = ?";
    $types .= "i";
    $values[] = ($pet_age === '' ? null : (int)$pet_age);
}

$fields .= " WHERE pet_id = ?";
$types .= "i";
$values[] = (int)$pet_id;

$stmt = $conn->prepare("UPDATE pets SET {$fields}");
$stmt->bind_param($types, ...$values);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin-dashboard.php?view=pets&pet_updated=1");
exit();
?>
