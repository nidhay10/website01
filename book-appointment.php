<?php
session_start();
require "db.php";

$errors = [];
$success_message = "";

$prefill = [
    "service_id" => "",
    "vet_id" => "",
    "pet_id" => "",
    "appointment_date" => "",
    "appointment_time" => "",
    "owner_name" => "",
    "phone" => "",
    "email" => "",
    "pet_type" => "",
    "pet_age" => "",
    "owner_notes" => ""
];

function add_error(&$errors, $key, $message) {
    if (empty($errors[$key])) {
        $errors[$key] = $message;
    }
}

function input_error_class($errors, $key) {
    return !empty($errors[$key]) ? "input-error" : "";
}

function valid_date($date) {
    $dt = DateTime::createFromFormat("Y-m-d", $date);
    return $dt && $dt->format("Y-m-d") === $date;
}

function valid_time($time) {
    $dt = DateTime::createFromFormat("H:i", $time);
    return $dt && $dt->format("H:i") === $time;
}

function valid_email($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitize_phone($phone) {
    return preg_replace("/\D+/", "", $phone);
}

$user_profile = null;
$user_id = isset($_SESSION["user_id"]) ? (int) $_SESSION["user_id"] : 0;
if ($user_id > 0) {
    $profile_rs = $conn->query("SELECT name, email, phone FROM users WHERE user_id='{$user_id}' LIMIT 1");
    if ($profile_rs) {
        $user_profile = $profile_rs->fetch_assoc();
        if ($user_profile) {
            $prefill["owner_name"] = $user_profile["name"] ?? "";
            $prefill["email"] = $user_profile["email"] ?? "";
            if (!empty($user_profile["phone"])) {
                $prefill["phone"] = $user_profile["phone"];
            }
        }
    }
}

// Detect pet column names
$pet_type_col = null;
$pet_age_col = null;
$type_species_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'species'");
if ($type_species_check && $type_species_check->num_rows > 0) {
    $pet_type_col = "species";
}
$type_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'type'");
if (!$pet_type_col && $type_check && $type_check->num_rows > 0) {
    $pet_type_col = "type";
}
$type_check_alt = $conn->query("SHOW COLUMNS FROM pets LIKE 'pet_type'");
if (!$pet_type_col && $type_check_alt && $type_check_alt->num_rows > 0) {
    $pet_type_col = "pet_type";
}

$age_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'age'");
if ($age_check && $age_check->num_rows > 0) {
    $pet_age_col = "age";
}
$age_check_alt = $conn->query("SHOW COLUMNS FROM pets LIKE 'pet_age'");
if (!$pet_age_col && $age_check_alt && $age_check_alt->num_rows > 0) {
    $pet_age_col = "pet_age";
}

$pet_type_select = $pet_type_col ? "pets.$pet_type_col AS pet_type" : "NULL AS pet_type";
$pet_age_select = $pet_age_col ? "pets.$pet_age_col AS pet_age" : "NULL AS pet_age";

// AJAX endpoints for auto-fill
if (isset($_GET["ajax"])) {
    header("Content-Type: application/json");

    if ($_GET["ajax"] === "vet") {
        $vet_id = (int) ($_GET["vet_id"] ?? 0);
        $vet_rs = $conn->query("SELECT clinic_name, clinic_address, doctor_name, name FROM users WHERE user_id='{$vet_id}' AND role='vet' LIMIT 1");
        $vet_row = $vet_rs ? $vet_rs->fetch_assoc() : null;
        $displayName = $vet_row ? (!empty($vet_row["doctor_name"]) ? $vet_row["doctor_name"] : $vet_row["name"]) : "";
        echo json_encode([
            "clinic_name" => $vet_row["clinic_name"] ?? "",
            "clinic_address" => $vet_row["clinic_address"] ?? "",
            "vet_name" => $displayName ? "Dr. " . $displayName : ""
        ]);
        exit;
    }

    if ($_GET["ajax"] === "pet" && $user_id > 0) {
        $pet_id = (int) ($_GET["pet_id"] ?? 0);
        $pet_rs = $conn->query("SELECT $pet_type_select, $pet_age_select, pet_name FROM pets WHERE pet_id='{$pet_id}' AND user_id='{$user_id}' LIMIT 1");
        $pet_row = $pet_rs ? $pet_rs->fetch_assoc() : null;
        echo json_encode([
            "pet_type" => $pet_row["pet_type"] ?? "",
            "pet_age" => $pet_row["pet_age"] ?? "",
            "pet_name" => $pet_row["pet_name"] ?? ""
        ]);
        exit;
    }

    echo json_encode([]);
    exit;
}

// Load vets and services
$vets_data = [];
$vets_result = $conn->query("SELECT user_id, doctor_name, name, clinic_name, clinic_address FROM users WHERE role='vet'");
if ($vets_result) {
    while ($vet_row = $vets_result->fetch_assoc()) {
        $vets_data[] = $vet_row;
    }
}

$services_data = [];
$service_ids = [];
$services_result = $conn->query("SELECT service_id, service_name FROM services ORDER BY service_name");
if ($services_result) {
    while ($service_row = $services_result->fetch_assoc()) {
        $services_data[] = $service_row;
        $service_ids[] = (string) $service_row["service_id"];
    }
}

$pets = [];
if ($user_id > 0) {
    $pets_rs = $conn->query("SELECT pet_id, pet_name, $pet_type_select, $pet_age_select FROM pets WHERE user_id='{$user_id}' ORDER BY pet_name");
    if ($pets_rs) {
        while ($row = $pets_rs->fetch_assoc()) {
            $pets[] = $row;
        }
    }
}

// Handle form submit
if (isset($_POST["book_btn"])) {
    $prefill["service_id"] = $_POST["service_id"] ?? "";
    $prefill["vet_id"] = $_POST["vet_id"] ?? "";
    $prefill["pet_id"] = $_POST["pet_id"] ?? "";
    $prefill["appointment_date"] = $_POST["appointment_date"] ?? "";
    $prefill["appointment_time"] = $_POST["appointment_time"] ?? "";
    $prefill["owner_name"] = trim($_POST["owner_name"] ?? "");
    $prefill["phone"] = trim($_POST["phone"] ?? "");
    $prefill["email"] = trim($_POST["email"] ?? "");
    $prefill["pet_type"] = trim($_POST["pet_type"] ?? "");
    $prefill["pet_age"] = trim($_POST["pet_age"] ?? "");
    $prefill["owner_notes"] = trim($_POST["owner_notes"] ?? "");

    if ($user_id <= 0) {
        add_error($errors, "auth", "Please login before booking an appointment.");
    }

    if (empty($prefill["appointment_date"])) {
        add_error($errors, "appointment_date", "Please select an appointment date.");
    } elseif (!valid_date($prefill["appointment_date"])) {
        add_error($errors, "appointment_date", "Please use a valid date (YYYY-MM-DD).");
    } else {
        $selected_date = DateTime::createFromFormat("Y-m-d", $prefill["appointment_date"]);
        $today = new DateTime("today");
        if ($selected_date < $today) {
            add_error($errors, "appointment_date", "Appointment date cannot be in the past.");
        }
    }

    if (empty($prefill["appointment_time"])) {
        add_error($errors, "appointment_time", "Please select an appointment time.");
    } elseif (!valid_time($prefill["appointment_time"])) {
        add_error($errors, "appointment_time", "Please use a valid time (24-hour format).");
    }

    if (empty($prefill["service_id"]) || !in_array((string) $prefill["service_id"], $service_ids, true)) {
        add_error($errors, "service_id", "Please select a valid service.");
    }

    if (empty($prefill["vet_id"])) {
        add_error($errors, "vet_id", "Please choose a veterinarian.");
    }

    if (empty($prefill["pet_id"])) {
        add_error($errors, "pet_id", "Please select a pet.");
    }

    if ($prefill["owner_name"] === "") {
        add_error($errors, "owner_name", "Owner name is required.");
    }

    $clean_phone = sanitize_phone($prefill["phone"]);
    if ($clean_phone === "" || strlen($clean_phone) !== 10) {
        add_error($errors, "phone", "Please enter a valid 10-digit phone number.");
    }

    if ($prefill["email"] === "") {
        add_error($errors, "email", "Email is required.");
    } elseif (!valid_email($prefill["email"])) {
        add_error($errors, "email", "Please enter a valid email address.");
    }

    if (!empty($prefill["pet_id"]) && $user_id > 0) {
        $pet_rs = $conn->query("SELECT $pet_type_select, $pet_age_select FROM pets WHERE pet_id='{$prefill["pet_id"]}' AND user_id='{$user_id}' LIMIT 1");
        $pet_row = $pet_rs ? $pet_rs->fetch_assoc() : null;
        if ($pet_row) {
            if ($prefill["pet_type"] === "") {
                $prefill["pet_type"] = $pet_row["pet_type"] ?? "";
            }
            if ($prefill["pet_age"] === "") {
                $prefill["pet_age"] = $pet_row["pet_age"] ?? "";
            }
        }
    }

    if (empty($errors)) {
        $has_user_id = false;
        $has_owner_notes = false;
        $user_col = $conn->query("SHOW COLUMNS FROM appointments LIKE 'user_id'");
        if ($user_col && $user_col->num_rows > 0) {
            $has_user_id = true;
        }
        $owner_notes_col = $conn->query("SHOW COLUMNS FROM appointments LIKE 'owner_notes'");
        if ($owner_notes_col && $owner_notes_col->num_rows > 0) {
            $has_owner_notes = true;
        }

        $fields = ["pet_id", "vet_id", "service_id", "appointment_date", "appointment_time", "status"];
        $values = [
            (int) $prefill["pet_id"],
            (int) $prefill["vet_id"],
            (int) $prefill["service_id"],
            $prefill["appointment_date"],
            $prefill["appointment_time"],
            "pending"
        ];
        $types = "iiisss";

        if ($has_user_id) {
            $fields[] = "user_id";
            $values[] = $user_id;
            $types .= "i";
        }
        if ($has_owner_notes) {
            $fields[] = "owner_notes";
            $values[] = $prefill["owner_notes"];
            $types .= "s";
        }

        $field_list = implode(", ", $fields);
        $placeholders = implode(", ", array_fill(0, count($fields), "?"));

        $stmt = $conn->prepare("INSERT INTO appointments ($field_list) VALUES ($placeholders)");
        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute()) {
                header("Location: payments.php");
                exit;
            } else {
                add_error($errors, "db", "Unable to save appointment. Please try again.");
            }
            $stmt->close();
        } else {
            add_error($errors, "db", "Unable to prepare appointment. Please try again.");
        }
    }
}

$selected_clinic_name = "";
$selected_clinic_address = "";
$selected_vet_name = "";
$selected_pet_name = "";
$selected_service_label = "";

$service_labels = [];
foreach ($services_data as $service_row) {
    $service_labels[(string) $service_row["service_id"]] = $service_row["service_name"];
}
$selected_service_label = $service_labels[$prefill["service_id"] ?? ""] ?? "";

if (!empty($prefill["vet_id"])) {
    foreach ($vets_data as $vet_row) {
        if ((string) $vet_row["user_id"] === (string) $prefill["vet_id"]) {
            $selected_clinic_name = $vet_row["clinic_name"] ?? "";
            $selected_clinic_address = $vet_row["clinic_address"] ?? "";
            $displayName = !empty($vet_row["doctor_name"]) ? $vet_row["doctor_name"] : $vet_row["name"];
            $selected_vet_name = $displayName ? "Dr. " . $displayName : "";
            break;
        }
    }
}

if (!empty($prefill["pet_id"]) && $user_id > 0) {
    $pet_row = $conn->query("SELECT pet_name FROM pets WHERE pet_id='{$prefill["pet_id"]}' AND user_id='{$user_id}' LIMIT 1")->fetch_assoc();
    if ($pet_row && !empty($pet_row["pet_name"])) {
        $selected_pet_name = $pet_row["pet_name"];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAMPER YOUR PET - Book Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --brand-900: #0f2b46;
            --brand-700: #1f4d78;
            --brand-500: #2f7ec7;
            --accent-400: #f2b48d;
            --accent-100: #fff3e9;
            --ink-900: #0f172a;
            --ink-700: #1f2937;
            --ink-600: #475569;
            --ink-500: #64748b;
            --surface: #ffffff;
            --surface-alt: #f7f9fc;
            --border: #e2e8f0;
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.12);
            --shadow-card: 0 10px 25px rgba(15, 23, 42, 0.08);
            --radius-lg: 22px;
            --radius-md: 16px;
            --radius-sm: 12px;
            --error: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            color: var(--ink-900);
            background: radial-gradient(circle at 15% 20%, #eff6ff 0%, #f8fafc 45%, #ffffff 100%);
            min-height: 100vh;
        }

        a { color: inherit; text-decoration: none; }

        .container {
            max-width: 1150px;
            margin: 0 auto;
            padding: 0 24px;
        }

        header {
            background: var(--surface);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border);
        }

        .nav-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--brand-900);
        }

        .brand i {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand-500), var(--accent-400));
            display: grid;
            place-items: center;
            color: #ffffff;
            font-size: 1rem;
        }

        .main-nav ul {
            list-style: none;
            display: flex;
            gap: 22px;
        }

        .main-nav a {
            color: var(--ink-600);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .main-nav a:hover,
        .main-nav a.active { color: var(--brand-700); }

        .nav-actions { display: flex; gap: 10px; align-items: center; }

        .btn {
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--border);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            background: var(--surface-alt);
            color: var(--brand-900);
        }

        .btn-outline:hover {
            border-color: var(--brand-500);
            color: var(--brand-700);
            transform: translateY(-1px);
        }

        .btn-solid {
            border-color: transparent;
            background: var(--brand-700);
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(31, 77, 120, 0.25);
        }

        .btn-solid:hover { background: #173c5b; transform: translateY(-1px); }

        .page-header {
            background: linear-gradient(120deg, rgba(31, 77, 120, 0.92), rgba(47, 126, 199, 0.75));
            color: #ffffff;
            padding: 36px 0;
        }

        .breadcrumb {
            font-size: 0.9rem;
            opacity: 0.85;
            margin-bottom: 8px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .booking-section {
            padding: 28px 0 36px;
        }

        .booking-container {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 20px;
            background: var(--surface);
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--border);
        }

        .summary-panel {
            display: grid;
            gap: 16px;
        }

        .summary-card {
            background: var(--surface-alt);
            border-radius: var(--radius-md);
            padding: 18px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
        }

        .section-title {
            margin-bottom: 12px;
            color: var(--brand-900);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }

        .details-form {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 4px;
        }

        .form-group { margin-bottom: 16px; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--ink-700);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface-alt);
            font-size: 0.95rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px rgba(47, 126, 199, 0.15);
            background: #ffffff;
        }

        .input-error {
            border-color: var(--error) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        }

        .error-msg {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 6px;
            display: block;
        }

        .submit-btn {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: none;
            background: var(--brand-700);
            color: #ffffff;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 12px 25px rgba(31, 77, 120, 0.2);
        }

        .submit-btn:hover { transform: translateY(-1px); }

        .summary-list {
            display: grid;
            gap: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 0.9rem;
            padding-bottom: 8px;
            border-bottom: 1px dashed rgba(148, 163, 184, 0.5);
        }

        .summary-item:last-child { border-bottom: none; padding-bottom: 0; }

        .summary-label { color: var(--ink-500); font-weight: 600; }
        .summary-value { color: var(--ink-900); font-weight: 600; text-align: right; }

        .notice {
            background: #ecfdf3;
            border: 1px solid #86efac;
            color: #166534;
            padding: 10px 12px;
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .alert {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
            padding: 10px 12px;
            border-radius: 12px;
            margin-bottom: 12px;
        }

        @media (max-width: 980px) {
            .booking-container { grid-template-columns: 1fr; }
            .summary-panel { order: 2; }
        }

        @media (max-width: 640px) {
            .main-nav ul { display: none; }
            .form-row { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 2rem; }
        }
    /* Unified Logo */
.logo-mark {
    border-radius: 50% !important;
    background:
        radial-gradient(circle at 30% 32%, rgba(255,255,255,0.95) 0 10%, transparent 11%),
        radial-gradient(circle at 50% 20%, rgba(255,255,255,0.95) 0 8%, transparent 9%),
        radial-gradient(circle at 70% 32%, rgba(255,255,255,0.95) 0 10%, transparent 11%),
        radial-gradient(circle at 40% 62%, rgba(255,255,255,0.95) 0 14%, transparent 15%),
        radial-gradient(circle at 60% 62%, rgba(255,255,255,0.95) 0 14%, transparent 15%),
        linear-gradient(135deg, #5f8fb8, #f0c1a0) !important;
    color: transparent !important;
    font-size: 0 !important;
    position: relative;
}
.brand i {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: inline-block;
    background:
        radial-gradient(circle at 30% 32%, rgba(255,255,255,0.95) 0 10%, transparent 11%),
        radial-gradient(circle at 50% 20%, rgba(255,255,255,0.95) 0 8%, transparent 9%),
        radial-gradient(circle at 70% 32%, rgba(255,255,255,0.95) 0 10%, transparent 11%),
        radial-gradient(circle at 40% 62%, rgba(255,255,255,0.95) 0 14%, transparent 15%),
        radial-gradient(circle at 60% 62%, rgba(255,255,255,0.95) 0 14%, transparent 15%),
        linear-gradient(135deg, #5f8fb8, #f0c1a0);
    color: transparent;
    font-size: 0;
    line-height: 1;
}
.brand i::before { content: "" !important; }
</style>
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>
    <header>
        <div class="container nav-wrapper">
            <a href="pet-parent.php" class="brand">
                <i class="fa-solid fa-paw"></i> PAMPER YOUR PET
            </a>

            <nav class="main-nav">
                <ul>
                    <li><a href="pet-parent.php">Services</a></li>
                    <li><a href="book-appointment.php" class="active">Book Appointment</a></li>
                    <li><a href="pet-parent-dashboard.php">Dashboard</a></li>
                </ul>
            </nav>

            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-outline" href="pet-parent-dashboard.php">My Dashboard</a>
                    <a class="btn btn-solid" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-outline" href="login.php">Login</a>
                    <a class="btn btn-solid" href="signup.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="page-header">
        <div class="container">
            <div class="breadcrumb">Home / Book Appointment</div>
            <h1>Book an Appointment</h1>
        </div>
    </section>

    <main class="booking-section">
        <div class="container">
            <div class="booking-container">
                <aside class="summary-panel">
                    <div class="summary-card">
                        <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> Appointment Summary</h3>
                        <div class="summary-list">
                            <div class="summary-item">
                                <span class="summary-label">Date</span>
                                <span class="summary-value" id="summaryDate"><?php echo htmlspecialchars($prefill["appointment_date"] !== "" ? $prefill["appointment_date"] : "Not selected"); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Time</span>
                                <span class="summary-value" id="summaryTime"><?php echo htmlspecialchars($prefill["appointment_time"] !== "" ? $prefill["appointment_time"] : "Not selected"); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Service</span>
                                <span class="summary-value" id="summaryService"><?php echo htmlspecialchars($selected_service_label !== "" ? $selected_service_label : "Not selected"); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Vet</span>
                                <span class="summary-value" id="summaryVet"><?php echo htmlspecialchars($selected_vet_name !== "" ? $selected_vet_name : "Not selected"); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Pet</span>
                                <span class="summary-value" id="summaryPet"><?php echo htmlspecialchars($selected_pet_name !== "" ? $selected_pet_name : "Not selected"); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <h3 class="section-title"><i class="fa-solid fa-location-dot"></i> Clinic Details</h3>
                        <div class="summary-list">
                            <div class="summary-item">
                                <span class="summary-label">Clinic</span>
                                <span class="summary-value" id="summaryClinicName"><?php echo htmlspecialchars($selected_clinic_name !== "" ? $selected_clinic_name : "Not selected"); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Address</span>
                                <span class="summary-value" id="summaryClinicAddress"><?php echo htmlspecialchars($selected_clinic_address !== "" ? $selected_clinic_address : "Not selected"); ?></span>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="details-form">
                    <?php if (!empty($success_message)) { ?>
                        <div class="notice"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php } ?>
                    <?php if (!empty($errors["auth"])) { ?>
                        <div class="alert"><?php echo htmlspecialchars($errors["auth"]); ?></div>
                    <?php } ?>

                    <form method="POST" action="book-appointment.php" id="appointmentForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Appointment Date <span style="color:red">*</span></label>
                                <input type="text" id="datePicker" name="appointment_date" class="<?php echo input_error_class($errors, "appointment_date"); ?>" value="<?php echo htmlspecialchars($prefill["appointment_date"]); ?>" required>
                                <span class="error-msg"><?php echo $errors["appointment_date"] ?? ""; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Appointment Time <span style="color:red">*</span></label>
                                <input type="text" id="timePicker" name="appointment_time" class="<?php echo input_error_class($errors, "appointment_time"); ?>" value="<?php echo htmlspecialchars($prefill["appointment_time"]); ?>" required>
                                <span class="error-msg"><?php echo $errors["appointment_time"] ?? ""; ?></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Service Type <span style="color:red">*</span></label>
                            <select id="service" name="service_id" class="<?php echo input_error_class($errors, "service_id"); ?>" required>
                                <option value="">Select a Service...</option>
                                <?php foreach ($services_data as $service_row) { ?>
                                    <option value="<?php echo htmlspecialchars($service_row["service_id"]); ?>" <?php echo ($prefill["service_id"] == $service_row["service_id"]) ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($service_row["service_name"]); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <span class="error-msg"><?php echo $errors["service_id"] ?? ""; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Preferred Veterinarian <span style="color:red">*</span></label>
                            <select name="vet_id" id="vet" class="<?php echo input_error_class($errors, "vet_id"); ?>" required>
                                <option value="">Select a vet...</option>
                                <?php foreach ($vets_data as $vet) { ?>
                                    <?php $displayName = !empty($vet["doctor_name"]) ? $vet["doctor_name"] : $vet["name"]; ?>
                                    <option value="<?php echo htmlspecialchars($vet["user_id"]); ?>" <?php echo ($prefill["vet_id"] == $vet["user_id"]) ? "selected" : ""; ?>
                                        data-clinic-name="<?php echo htmlspecialchars($vet["clinic_name"] ?? "", ENT_QUOTES); ?>"
                                        data-clinic-address="<?php echo htmlspecialchars($vet["clinic_address"] ?? "", ENT_QUOTES); ?>">
                                        <?php echo htmlspecialchars($displayName ? "Dr. " . $displayName : "Vet"); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <span class="error-msg"><?php echo $errors["vet_id"] ?? ""; ?></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Clinic Name</label>
                                <input type="text" id="clinicName" name="clinic_name" value="<?php echo htmlspecialchars($selected_clinic_name); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Clinic Address</label>
                                <input type="text" id="clinicAddress" name="clinic_address" value="<?php echo htmlspecialchars($selected_clinic_address); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Select Pet <span style="color:red">*</span></label>
                            <select name="pet_id" id="pet" class="<?php echo input_error_class($errors, "pet_id"); ?>" required>
                                <option value="">Select a pet...</option>
                                <?php foreach ($pets as $pet) { ?>
                                    <option value="<?php echo htmlspecialchars($pet["pet_id"]); ?>" <?php echo ($prefill["pet_id"] == $pet["pet_id"]) ? "selected" : ""; ?>
                                        data-species="<?php echo htmlspecialchars($pet["pet_type"] ?? "", ENT_QUOTES); ?>"
                                        data-age="<?php echo htmlspecialchars($pet["pet_age"] ?? "", ENT_QUOTES); ?>">
                                        <?php echo htmlspecialchars($pet["pet_name"]); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <span class="error-msg"><?php echo $errors["pet_id"] ?? ""; ?></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Pet Type</label>
                                <input type="text" id="petType" name="pet_type" class="<?php echo input_error_class($errors, "pet_type"); ?>" value="<?php echo htmlspecialchars($prefill["pet_type"]); ?>" placeholder="Auto-filled" readonly>
                                <span class="error-msg"><?php echo $errors["pet_type"] ?? ""; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Age (Years)</label>
                                <input type="number" id="petAge" name="pet_age" min="0" max="50" class="<?php echo input_error_class($errors, "pet_age"); ?>" value="<?php echo htmlspecialchars($prefill["pet_age"]); ?>" placeholder="Auto-filled" readonly>
                                <span class="error-msg"><?php echo $errors["pet_age"] ?? ""; ?></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Owner Name <span style="color:red">*</span></label>
                            <input type="text" id="ownerName" name="owner_name" class="<?php echo input_error_class($errors, "owner_name"); ?>" value="<?php echo htmlspecialchars($prefill["owner_name"]); ?>" placeholder="Your Full Name" required>
                            <span class="error-msg"><?php echo $errors["owner_name"] ?? ""; ?></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number <span style="color:red">*</span></label>
                                <input type="tel" id="phone" name="phone" class="<?php echo input_error_class($errors, "phone"); ?>" value="<?php echo htmlspecialchars($prefill["phone"]); ?>" placeholder="e.g. 9876543210" maxlength="10" inputmode="numeric" pattern="\d{10}" required>
                                <span class="error-msg"><?php echo $errors["phone"] ?? ""; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Email Address <span style="color:red">*</span></label>
                                <input type="email" id="email" name="email" class="<?php echo input_error_class($errors, "email"); ?>" value="<?php echo htmlspecialchars($prefill["email"]); ?>" placeholder="you@example.com" required>
                                <span class="error-msg"><?php echo $errors["email"] ?? ""; ?></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Additional Notes</label>
                            <textarea rows="4" id="ownerNotes" name="owner_notes" placeholder="Describe symptoms or special requests..."><?php echo htmlspecialchars($prefill["owner_notes"] ?? ""); ?></textarea>
                        </div>

                        <?php if (!empty($errors["db"])) { ?>
                            <div class="alert"><?php echo htmlspecialchars($errors["db"]); ?></div>
                        <?php } ?>

                        <button type="submit" name="book_btn" class="submit-btn">Proceed to Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script>
        const vetSelect = document.getElementById('vet');
        const clinicNameEl = document.getElementById('clinicName');
        const clinicAddressEl = document.getElementById('clinicAddress');

        async function fetchVetDetails(vetId) {
            if (!vetId) return null;
            try {
                const res = await fetch(`book-appointment.php?ajax=vet&vet_id=${encodeURIComponent(vetId)}`, { credentials: 'same-origin' });
                if (!res.ok) return null;
                return await res.json();
            } catch (err) {
                return null;
            }
        }

        function updateClinicFields() {
            if (!vetSelect || !clinicNameEl || !clinicAddressEl) return;
            const option = vetSelect.options[vetSelect.selectedIndex];
            const clinicName = option && option.dataset ? option.dataset.clinicName || "" : "";
            const clinicAddress = option && option.dataset ? option.dataset.clinicAddress || "" : "";
            clinicNameEl.value = clinicName;
            clinicAddressEl.value = clinicAddress;

            if (vetSelect.value && clinicName === "" && clinicAddress === "") {
                fetchVetDetails(vetSelect.value).then(data => {
                    if (!data) return;
                    if (clinicNameEl.value === "") clinicNameEl.value = data.clinic_name || "";
                    if (clinicAddressEl.value === "") clinicAddressEl.value = data.clinic_address || "";
                    updateSummary();
                });
            } else {
                updateSummary();
            }
        }

        const petSelect = document.getElementById('pet');
        const petTypeEl = document.getElementById('petType');
        const petAgeEl = document.getElementById('petAge');

        async function fetchPetDetails(petId) {
            if (!petId) return null;
            try {
                const res = await fetch(`book-appointment.php?ajax=pet&pet_id=${encodeURIComponent(petId)}`, { credentials: 'same-origin' });
                if (!res.ok) return null;
                return await res.json();
            } catch (err) {
                return null;
            }
        }

        function updatePetFields() {
            if (!petSelect || !petTypeEl || !petAgeEl) return;
            const option = petSelect.options[petSelect.selectedIndex];
            const species = option && option.dataset ? option.dataset.species || "" : "";
            const age = option && option.dataset ? option.dataset.age || "" : "";
            petTypeEl.value = species;
            petAgeEl.value = age;

            if (petSelect.value && species === "" && age === "") {
                fetchPetDetails(petSelect.value).then(data => {
                    if (!data) return;
                    if (petTypeEl.value === "") petTypeEl.value = data.pet_type || "";
                    if (petAgeEl.value === "") petAgeEl.value = data.pet_age || "";
                    updateSummary();
                });
            } else {
                updateSummary();
            }
        }

        const summaryDate = document.getElementById('summaryDate');
        const summaryTime = document.getElementById('summaryTime');
        const summaryService = document.getElementById('summaryService');
        const summaryVet = document.getElementById('summaryVet');
        const summaryPet = document.getElementById('summaryPet');
        const summaryClinicName = document.getElementById('summaryClinicName');
        const summaryClinicAddress = document.getElementById('summaryClinicAddress');

        function safeText(value) {
            return value && value.trim() !== "" ? value : "Not selected";
        }

        function updateSummary() {
            const dateEl = document.getElementById('datePicker');
            const timeEl = document.getElementById('timePicker');
            const serviceEl = document.getElementById('service');
            const vetEl = document.getElementById('vet');
            const petEl = document.getElementById('pet');

            if (summaryDate && dateEl) summaryDate.textContent = safeText(dateEl.value);
            if (summaryTime && timeEl) summaryTime.textContent = safeText(timeEl.value);
            if (summaryService && serviceEl) summaryService.textContent = safeText(serviceEl.options[serviceEl.selectedIndex]?.text || "");
            if (summaryVet && vetEl) summaryVet.textContent = safeText(vetEl.options[vetEl.selectedIndex]?.text || "");
            if (summaryPet && petEl) summaryPet.textContent = safeText(petEl.options[petEl.selectedIndex]?.text || "");
            if (summaryClinicName && clinicNameEl) summaryClinicName.textContent = safeText(clinicNameEl.value);
            if (summaryClinicAddress && clinicAddressEl) summaryClinicAddress.textContent = safeText(clinicAddressEl.value);
        }

        ['change', 'input'].forEach(evt => {
            const dateEl = document.getElementById('datePicker');
            const timeEl = document.getElementById('timePicker');
            const serviceEl = document.getElementById('service');
            const vetEl = document.getElementById('vet');
            const petEl = document.getElementById('pet');

            if (dateEl) dateEl.addEventListener(evt, updateSummary);
            if (timeEl) timeEl.addEventListener(evt, updateSummary);
            if (serviceEl) serviceEl.addEventListener(evt, updateSummary);
            if (vetEl) vetEl.addEventListener(evt, updateSummary);
            if (petEl) petEl.addEventListener(evt, updateSummary);
        });

        if (vetSelect) {
            vetSelect.addEventListener('change', updateClinicFields);
            updateClinicFields();
        }
        if (petSelect) {
            petSelect.addEventListener('change', updatePetFields);
            updatePetFields();
        }

        updateSummary();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#datePicker", {
            minDate: "today",
            dateFormat: "Y-m-d"
        });

        flatpickr("#timePicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i"
        });
    </script>
</body>
</html>


