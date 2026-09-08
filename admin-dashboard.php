<?php
require "db.php";

function detect_date_column($conn, $table, $candidates) {
    foreach ($candidates as $column) {
        $safe_column = $conn->real_escape_string($column);
        $safe_table = $conn->real_escape_string($table);
        $check = $conn->query("SHOW COLUMNS FROM `$safe_table` LIKE '$safe_column'");
        if ($check && $check->num_rows > 0) {
            return $column;
        }
    }
    return null;
}

$users_date_col = detect_date_column($conn, 'users', [
    'created_at',
    'created_on',
    'created_date',
    'registered_at',
    'registration_date',
    'date_created',
    'created'
]);
$pets_date_col = detect_date_column($conn, 'pets', [
    'created_at',
    'created_on',
    'created_date',
    'registration_date',
    'date_created',
    'created'
]);
$payments_date_col = detect_date_column($conn, 'payments', [
    'payment_date',
    'created_at',
    'created_on',
    'created_date',
    'date_created',
    'created'
]);

$services = $conn->query("SELECT * FROM services");

/* GET USERS FOR REGISTRATION TABLE */
$users = $conn->query("SELECT * FROM users WHERE role='vet' AND status='pending'");

/* GET ALL APPROVED VETERINARIANS */
$vets = $conn->query("
SELECT user_id,name,email,clinic_name,clinic_address,vet_registration,status 
FROM users 
WHERE role='vet' AND status='approved'
");

$sql = "SELECT 
appointments.*,
pets.pet_name,
users.name AS owner_name,
vet.name AS vet_name

FROM appointments

JOIN pets ON appointments.pet_id = pets.pet_id
JOIN users ON appointments.user_id = users.user_id
JOIN users vet ON appointments.vet_id = vet.user_id
WHERE vet.role='vet' AND vet.status='approved'

ORDER BY appointment_date DESC";

$result = $conn->query($sql);

/* DASHBOARD STATS */
$total_appointments_count = 0;
$pending_appointments_count = 0;
$total_rows = $conn->query("SELECT COUNT(*) AS total FROM appointments");
if ($total_rows) {
    $row = $total_rows->fetch_assoc();
    $total_appointments_count = (int)($row['total'] ?? 0);
}
$pending_rows = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'pending'");
if ($pending_rows) {
    $row = $pending_rows->fetch_assoc();
    $pending_appointments_count = (int)($row['total'] ?? 0);
}

/* RECENT ACTIVITY (APPOINTMENTS) */
$recent_appointments = $conn->query("
SELECT 
    a.status,
    p.pet_name,
    u.name AS owner_name,
    s.service_name
FROM appointments a
JOIN pets p ON a.pet_id = p.pet_id
JOIN users u ON a.user_id = u.user_id
LEFT JOIN services s ON a.service_id = s.service_id
ORDER BY a.appointment_date DESC, a.appointment_time DESC
LIMIT 5
");

/* GET PAYMENTS */
$payments = $conn->query("
SELECT 
    p.payment_id,
    p.appointment_id,
    p.payment_method,
    p.amount,
    p.payment_status,
    p.payment_date,
    u.name AS owner_name,
    pets.pet_name,
    vet.name AS vet_name
FROM payments p
LEFT JOIN appointments a ON p.appointment_id = a.appointment_id
LEFT JOIN users u ON a.user_id = u.user_id
LEFT JOIN pets ON a.pet_id = pets.pet_id
LEFT JOIN users vet ON a.vet_id = vet.user_id
ORDER BY p.payment_date DESC
");

/* GET PRESCRIPTIONS */
$prescriptions = $conn->query("
SELECT 
    p.prescription_id,
    p.pet_id,
    p.vet_id,
    p.medication,
    p.dosage,
    p.frequency,
    p.duration,
    p.instructions,
    p.created_at,
    pets.pet_name,
    owner.name AS owner_name,
    vet.name AS vet_name
FROM prescriptions p
LEFT JOIN pets ON p.pet_id = pets.pet_id
LEFT JOIN users owner ON pets.user_id = owner.user_id
LEFT JOIN users vet ON p.vet_id = vet.user_id
ORDER BY p.created_at DESC
");

/* GET FEEDBACKS */
$feedbacks = $conn->query("
SELECT
    f.feedback_id,
    f.user_id,
    f.pet_id,
    f.visit_type,
    f.vet_name,
    f.visit_date,
    f.rating,
    f.comments,
    f.contact_ok,
    f.created_at,
    u.name AS user_name,
    u.email AS user_email,
    pets.pet_name,
    vet.clinic_name AS clinic_name
FROM feedbacks f
LEFT JOIN users u ON f.user_id = u.user_id
LEFT JOIN pets ON f.pet_id = pets.pet_id
LEFT JOIN users vet 
    ON vet.role = 'vet' AND (
        LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(f.vet_name, '.', ''), 'Dr', ''), 'dr', ''), '  ', ' '))) 
            = LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(vet.name, '.', ''), 'Dr', ''), 'dr', ''), '  ', ' ')))
        OR
        LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(f.vet_name, '.', ''), 'Dr', ''), 'dr', ''), '  ', ' '))) 
            LIKE CONCAT('%', LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(vet.name, '.', ''), 'Dr', ''), 'dr', ''), '  ', ' '))), '%')
        OR
        LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(vet.name, '.', ''), 'Dr', ''), 'dr', ''), '  ', ' '))) 
            LIKE CONCAT('%', LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(f.vet_name, '.', ''), 'Dr', ''), 'dr', ''), '  ', ' '))), '%')
    )
ORDER BY f.created_at DESC
");

/* REPORT OPTIONS */
$active_report = $_GET['report'] ?? '';

$services_list = [];
$service_rows = $conn->query("SELECT service_id, service_name FROM services ORDER BY service_name ASC");
if ($service_rows) {
    while ($row = $service_rows->fetch_assoc()) {
        $services_list[] = $row;
    }
}

$vets_list = [];
$vet_rows = $conn->query("SELECT user_id, name, clinic_name FROM users WHERE role='vet' AND status='approved' ORDER BY name ASC");
if ($vet_rows) {
    while ($row = $vet_rows->fetch_assoc()) {
        $vets_list[] = $row;
    }
}

$species_list = [];
$species_rows = $conn->query("SELECT DISTINCT species FROM pets WHERE species IS NOT NULL AND species <> '' ORDER BY species ASC");
if ($species_rows) {
    while ($row = $species_rows->fetch_assoc()) {
        $species_list[] = $row['species'];
    }
}

$user_list = [];
$user_rows = $conn->query("SELECT user_id, name, email FROM users WHERE role='pet_parent' ORDER BY name ASC");
if ($user_rows) {
    while ($row = $user_rows->fetch_assoc()) {
        $user_list[] = $row;
    }
}

$report_actions_html = '
    <div class="report-actions">
        <button type="submit" class="btn-report" name="export_format" value="pdf">Download PDF</button>
        <button type="submit" class="btn-report" name="export_format" value="word">Download Word</button>
        <button type="submit" class="btn-report" name="export_format" value="excel">Download Excel</button>
    </div>
';

/* GET PETS */
$pet_notes_col = null;
$pet_notes_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'vet_notes'");
if ($pet_notes_check && $pet_notes_check->num_rows > 0) {
    $pet_notes_col = "vet_notes";
}

$pets = $conn->query("
SELECT 
    p.pet_id,
    p.pet_name,
    p.species,
    p.age,
    p.breed,
    u.name AS owner_name,
    u.email AS owner_email" . ($pet_notes_col ? ", p.vet_notes" : "") . "
FROM pets p
LEFT JOIN users u ON p.user_id = u.user_id
ORDER BY p.pet_id DESC
");

/* REPORT DATA */
$report_user_rows = null;
$report_pet_rows = null;
$report_appointment_summary_rows = null;
$report_appointment_status_rows = null;
$report_vet_performance_rows = null;
$report_payment_rows = null;
$report_service_usage_rows = null;
$report_clinic_activity_rows = null;
$report_monthly_growth_rows = null;
$report_user_activity_rows = null;

if ($active_report === 'user-registration') {
    $role = $_GET['role'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $where = [];
    if ($role !== 'all' && $role !== '') $where[] = "role = '" . $conn->real_escape_string($role) . "'";
    if ($status !== 'all' && $status !== '') $where[] = "status = '" . $conn->real_escape_string($status) . "'";
    if ($users_date_col) {
        if ($from !== '') $where[] = $users_date_col . " >= '" . $conn->real_escape_string($from) . "'";
        if ($to !== '') $where[] = $users_date_col . " <= '" . $conn->real_escape_string($to) . "'";
    }
    $sql = "SELECT name, email, role, status, clinic_name, clinic_address, vet_registration FROM users";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY user_id DESC";
    $report_user_rows = $conn->query($sql);
}

if ($active_report === 'pet-registration') {
    $species = $_GET['species'] ?? 'all';
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $where = [];
    if ($species !== 'all' && $species !== '') $where[] = "pets.species = '" . $conn->real_escape_string($species) . "'";
    if ($pets_date_col) {
        if ($from !== '') $where[] = "pets." . $pets_date_col . " >= '" . $conn->real_escape_string($from) . "'";
        if ($to !== '') $where[] = "pets." . $pets_date_col . " <= '" . $conn->real_escape_string($to) . "'";
    }
    $sql = "SELECT pets.pet_id, pets.pet_name, pets.species, pets.age, pets.breed, users.name AS owner_name, users.email AS owner_email
            FROM pets JOIN users ON pets.user_id = users.user_id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY pets.pet_id DESC";
    $report_pet_rows = $conn->query($sql);
}

if ($active_report === 'appointment-summary') {
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $service_id = $_GET['service_id'] ?? 'all';
    $where = [];
    if ($from !== '') $where[] = "appointment_date >= '" . $conn->real_escape_string($from) . "'";
    if ($to !== '') $where[] = "appointment_date <= '" . $conn->real_escape_string($to) . "'";
    if ($service_id !== 'all' && $service_id !== '') $where[] = "service_id = '" . $conn->real_escape_string($service_id) . "'";
    $sql = "SELECT COUNT(*) AS total,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                   SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
            FROM appointments";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $report_appointment_summary_rows = $conn->query($sql);
}

if ($active_report === 'appointment-status') {
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $where = [];
    if ($from !== '') $where[] = "appointment_date >= '" . $conn->real_escape_string($from) . "'";
    if ($to !== '') $where[] = "appointment_date <= '" . $conn->real_escape_string($to) . "'";
    if ($status !== 'all' && $status !== '') $where[] = "status = '" . $conn->real_escape_string($status) . "'";
    $sql = "SELECT status, COUNT(*) AS total FROM appointments";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " GROUP BY status ORDER BY total DESC";
    $report_appointment_status_rows = $conn->query($sql);
}

if ($active_report === 'vet-performance') {
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $vet_id = $_GET['vet_id'] ?? 'all';
    $where = [];
    if ($from !== '') $where[] = "a.appointment_date >= '" . $conn->real_escape_string($from) . "'";
    if ($to !== '') $where[] = "a.appointment_date <= '" . $conn->real_escape_string($to) . "'";
    if ($vet_id !== 'all' && $vet_id !== '') $where[] = "a.vet_id = '" . $conn->real_escape_string($vet_id) . "'";
    $sql = "SELECT vet.name AS vet_name,
                   COUNT(*) AS total_appointments,
                   SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed
            FROM appointments a
            JOIN users vet ON a.vet_id = vet.user_id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " GROUP BY vet.user_id, vet.name ORDER BY total_appointments DESC";
    $report_vet_performance_rows = $conn->query($sql);
}

if ($active_report === 'payment-report') {
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $method = $_GET['payment_method'] ?? 'all';
    $pay_status = $_GET['payment_status'] ?? 'all';
    $where = [];
    if ($from !== '') $where[] = "payment_date >= '" . $conn->real_escape_string($from) . "'";
    if ($to !== '') $where[] = "payment_date <= '" . $conn->real_escape_string($to) . "'";
    if ($method !== 'all' && $method !== '') $where[] = "payment_method = '" . $conn->real_escape_string($method) . "'";
    if ($pay_status !== 'all' && $pay_status !== '') $where[] = "payment_status = '" . $conn->real_escape_string($pay_status) . "'";
    $sql = "SELECT payment_method, payment_status, SUM(amount) AS total_payments
            FROM payments";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " GROUP BY payment_method, payment_status ORDER BY total_payments DESC";
    $report_payment_rows = $conn->query($sql);
}

if ($active_report === 'service-usage') {
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $service_id = $_GET['service_id'] ?? 'all';
    $where = [];
    if ($from !== '') $where[] = "a.appointment_date >= '" . $conn->real_escape_string($from) . "'";
    if ($to !== '') $where[] = "a.appointment_date <= '" . $conn->real_escape_string($to) . "'";
    if ($service_id !== 'all' && $service_id !== '') $where[] = "a.service_id = '" . $conn->real_escape_string($service_id) . "'";
    $sql = "SELECT s.service_name, COUNT(*) AS total_usage
            FROM appointments a
            JOIN services s ON a.service_id = s.service_id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " GROUP BY s.service_id, s.service_name ORDER BY total_usage DESC";
    $report_service_usage_rows = $conn->query($sql);
}

if ($active_report === 'clinic-activity') {
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $clinic = $_GET['clinic_name'] ?? 'all';
    $where = [];
    if ($from !== '') $where[] = "a.appointment_date >= '" . $conn->real_escape_string($from) . "'";
    if ($to !== '') $where[] = "a.appointment_date <= '" . $conn->real_escape_string($to) . "'";
    if ($clinic !== 'all' && $clinic !== '') $where[] = "vet.clinic_name = '" . $conn->real_escape_string($clinic) . "'";
    $sql = "SELECT vet.clinic_name, COUNT(*) AS total_appointments
            FROM appointments a
            JOIN users vet ON a.vet_id = vet.user_id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " GROUP BY vet.clinic_name ORDER BY total_appointments DESC";
    $report_clinic_activity_rows = $conn->query($sql);
}

if ($active_report === 'monthly-growth') {
    $year = $_GET['year'] ?? '';
    $month = $_GET['month'] ?? 'all';
    $report_type = $_GET['report_type'] ?? 'appointments';

    if ($year === '') $year = date('Y');

    $type_config = [
        'appointments' => [
            'table' => 'appointments',
            'date_col' => 'appointment_date',
            'label' => 'Total Appointments',
            'metric' => 'count'
        ],
        'payments' => [
            'table' => 'payments',
            'date_col' => $payments_date_col,
            'label' => 'Total Earnings',
            'metric' => 'sum'
        ],
        'users' => [
            'table' => 'users',
            'date_col' => $users_date_col,
            'label' => 'Total Users',
            'metric' => 'count'
        ],
        'pets' => [
            'table' => 'pets',
            'date_col' => $pets_date_col,
            'label' => 'Total Pets',
            'metric' => 'count'
        ]
    ];

    if (!isset($type_config[$report_type])) $report_type = 'appointments';

    $table = $type_config[$report_type]['table'];
    $date_col = $type_config[$report_type]['date_col'];
    $monthly_label = $type_config[$report_type]['label'];
    $metric = $type_config[$report_type]['metric'];
    $where = [];

    if ($date_col) {
        if ($year !== '') $where[] = "YEAR($date_col) = '" . $conn->real_escape_string($year) . "'";
        if ($month !== 'all' && $month !== '') $where[] = "MONTH($date_col) = '" . $conn->real_escape_string($month) . "'";

        if ($report_type === 'users') {
            $sql = "SELECT 
                        MONTH($date_col) AS month,
                        SUM(CASE WHEN role = 'pet_parent' THEN 1 ELSE 0 END) AS pet_parents,
                        SUM(CASE WHEN role = 'vet' THEN 1 ELSE 0 END) AS veterinarians,
                        SUM(CASE WHEN role IN ('pet_parent','vet') THEN 1 ELSE 0 END) AS total_users
                    FROM $table";
        } elseif ($metric === 'sum') {
            $sql = "SELECT MONTH($date_col) AS month,
                           SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) AS total
                    FROM $table";
        } else {
            $sql = "SELECT MONTH($date_col) AS month, COUNT(*) AS total
                    FROM $table";
        }
        if ($where) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " GROUP BY month ORDER BY month ASC";
        $report_monthly_growth_rows = $conn->query($sql);
    } else {
        // Fallback when no date column exists: show totals without month grouping
        if ($report_type === 'users') {
            $sql = "SELECT 
                        'All' AS month,
                        SUM(CASE WHEN role = 'pet_parent' THEN 1 ELSE 0 END) AS pet_parents,
                        SUM(CASE WHEN role = 'vet' THEN 1 ELSE 0 END) AS veterinarians,
                        SUM(CASE WHEN role IN ('pet_parent','vet') THEN 1 ELSE 0 END) AS total_users
                    FROM $table";
        } elseif ($metric === 'sum') {
            $sql = "SELECT 'All' AS month, SUM(amount) AS total FROM $table";
        } else {
            $sql = "SELECT 'All' AS month, COUNT(*) AS total FROM $table";
        }
        $report_monthly_growth_rows = $conn->query($sql);
    }
}

if ($active_report === 'user-activity') {
    $from = $_GET['from_date'] ?? '';
    $to = $_GET['to_date'] ?? '';
    $user_id = $_GET['user_id'] ?? 'all';
    $where = [];
    if ($from !== '') $where[] = "a.appointment_date >= '" . $conn->real_escape_string($from) . "'";
    if ($to !== '') $where[] = "a.appointment_date <= '" . $conn->real_escape_string($to) . "'";
    if ($user_id !== 'all' && $user_id !== '') $where[] = "a.user_id = '" . $conn->real_escape_string($user_id) . "'";
    $sql = "SELECT u.name, u.email, COUNT(a.appointment_id) AS total_appointments
            FROM users u
            LEFT JOIN appointments a ON a.user_id = u.user_id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " GROUP BY u.user_id, u.name, u.email ORDER BY total_appointments DESC";
    $report_user_activity_rows = $conn->query($sql);
}

function build_word_html($title, $headers, $rows, $meta = []) {
    $generated = $meta['Generated'] ?? date('M d, Y');
    $html = '<html><head><meta charset="utf-8">';
    $html .= '<style>
        body{font-family:Poppins,Arial,sans-serif;color:#0f172a;background:#fff;}
        .header{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #e2e8f0;padding-bottom:12px;margin-bottom:18px;}
        .logo{display:flex;align-items:center;gap:10px;}
        .logo-mark{width:44px;height:44px;border-radius:50%;
            background:
                radial-gradient(circle at 30% 32%, rgba(255,255,255,0.95) 0 10%, transparent 11%),
                radial-gradient(circle at 50% 20%, rgba(255,255,255,0.95) 0 8%, transparent 9%),
                radial-gradient(circle at 70% 32%, rgba(255,255,255,0.95) 0 10%, transparent 11%),
                radial-gradient(circle at 40% 62%, rgba(255,255,255,0.95) 0 14%, transparent 15%),
                radial-gradient(circle at 60% 62%, rgba(255,255,255,0.95) 0 14%, transparent 15%),
                linear-gradient(135deg,#5f8fb8,#f0c1a0);}
        .brand{font-size:20px;font-weight:700;letter-spacing:.3px;}
        .brand-sub{font-size:12px;color:#64748b;}
        .meta{text-align:right;font-size:12px;color:#475569;}
        .title{font-size:16px;font-weight:700;margin:6px 0 2px;}
        .card{border:1px solid #e2e8f0;border-radius:12px;padding:14px;}
        table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;}
        th,td{border:1px solid #e2e8f0;padding:8px 10px;text-align:left;}
        th{background:#eef2f7;font-weight:700;}
        tr:nth-child(even) td{background:#f9fbff;}
    </style></head><body>';
    $html .= '<div class="header">
        <div class="logo">
            <div class="logo-mark"></div>
            <div>
                <div class="brand">Pamper Your Pet</div>
                <div class="brand-sub">Veterinary Care and Wellness</div>
            </div>
        </div>
        <div class="meta">
            <div class="title">' . htmlspecialchars($title) . '</div>
            <div>Date: ' . htmlspecialchars($generated) . '</div>
        </div>
    </div>';
    $html .= '<div class="card">';
    $html .= '<table><thead><tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . htmlspecialchars($h) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div></body></html>';
    return $html;
}

function build_excel_html($title, $headers, $rows, $meta = []) {
    $generated = $meta['Generated'] ?? date('M d, Y');
    $html = '<html><head><meta charset="utf-8">';
    $html .= '<style>
        body{font-family:Calibri,Arial,sans-serif;color:#0f172a;background:#fff;}
        .header{border-bottom:2px solid #e2e8f0;padding-bottom:10px;margin-bottom:14px;}
        .brand{font-size:18px;font-weight:700;}
        .sub{font-size:12px;color:#64748b;}
        .meta{font-size:12px;color:#475569;margin-top:6px;}
        table{width:100%;border-collapse:collapse;font-size:12px;}
        th,td{border:1px solid #e2e8f0;padding:8px 10px;text-align:left;}
        th{background:#eef2f7;font-weight:700;}
        tr:nth-child(even) td{background:#f9fbff;}
    </style></head><body>';
    $html .= '<div class="header"><div class="brand">Pamper Your Pet</div><div class="sub">Veterinary Care and Wellness</div>';
    $html .= '<div class="meta"><strong>' . htmlspecialchars($title) . '</strong> — Date: ' . htmlspecialchars($generated) . '</div></div>';
    $html .= '<table><thead><tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . htmlspecialchars($h) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></body></html>';
    return $html;
}

function pdf_escape($text) {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('(', '\\(', $text);
    $text = str_replace(')', '\\)', $text);
    return $text;
}

function pdf_fit_cell($text, $width) {
    $text = str_replace(["\r", "\n"], ' ', (string)$text);
    if (strlen($text) <= $width) return str_pad($text, $width);
    if ($width <= 3) return substr($text, 0, $width);
    return str_pad(substr($text, 0, $width - 3) . '...', $width);
}

function pdf_truncate_to_width($text, $width_points, $char_width = 5.2) {
    $text = str_replace(["\r", "\n"], ' ', (string)$text);
    $maxChars = (int)floor(max(0, $width_points - 8) / $char_width);
    if ($maxChars <= 0) return '';
    if (strlen($text) <= $maxChars) return $text;
    if ($maxChars <= 3) return substr($text, 0, $maxChars);
    return substr($text, 0, $maxChars - 3) . '...';
}

function pdf_calc_column_widths($headers, $rows, $totalWidth) {
    $colCount = count($headers);
    if ($colCount === 0) return [];
    $minWidth = 60;
    $maxWidth = 220;
    $weights = array_fill(0, $colCount, 1);

    for ($i = 0; $i < $colCount; $i++) {
        $maxLen = strlen((string)$headers[$i]);
        foreach ($rows as $row) {
            $cell = $row[$i] ?? '';
            $maxLen = max($maxLen, strlen((string)$cell));
        }
        $maxLen = min(36, $maxLen);
        $weights[$i] = max(4, $maxLen);
    }

    $sumWeights = array_sum($weights);
    $widths = [];
    for ($i = 0; $i < $colCount; $i++) {
        $widths[$i] = max($minWidth, ($weights[$i] / $sumWeights) * $totalWidth);
        $widths[$i] = min($widths[$i], $maxWidth);
    }

    $sum = array_sum($widths);
    if ($sum > $totalWidth) {
        $scale = $totalWidth / $sum;
        for ($i = 0; $i < $colCount; $i++) {
            $widths[$i] = max(48, $widths[$i] * $scale);
        }
    } elseif ($sum < $totalWidth) {
        $widths[$colCount - 1] += ($totalWidth - $sum);
    }

    return $widths;
}

function pdf_stream_text($x, $y, $text, $font = 'F1', $size = 10) {
    return "BT /{$font} {$size} Tf 1 0 0 1 {$x} {$y} Tm (" . pdf_escape($text) . ") Tj ET\n";
}

function pdf_stream_rect($x, $y, $w, $h, $op = 'S') {
    return "{$x} {$y} {$w} {$h} re {$op}\n";
}

function pdf_stream_line($x1, $y1, $x2, $y2) {
    return "{$x1} {$y1} m {$x2} {$y2} l S\n";
}

function pdf_stream_circle($cx, $cy, $r, $op = 'S') {
    $k = 0.5522847498;
    $c = $r * $k;
    $x0 = $cx - $r;
    $x1 = $cx - $r;
    $x2 = $cx - $c;
    $x3 = $cx;
    $x4 = $cx + $c;
    $x5 = $cx + $r;
    $y0 = $cy - $r;
    $y1 = $cy - $c;
    $y2 = $cy;
    $y3 = $cy + $c;
    $y4 = $cy + $r;
    $path = "{$x3} {$y4} m\n";
    $path .= "{$x4} {$y4} {$x5} {$y3} {$x5} {$y2} c\n";
    $path .= "{$x5} {$y1} {$x4} {$y0} {$x3} {$y0} c\n";
    $path .= "{$x2} {$y0} {$x1} {$y1} {$x1} {$y2} c\n";
    $path .= "{$x1} {$y3} {$x2} {$y4} {$x3} {$y4} c\n";
    $path .= "{$op}\n";
    return $path;
}

function pdf_stream_logo($x, $y, $size) {
    $stream = "";
    $r = $size / 2;
    $cx = $x + $r;
    $cy = $y - $r;

    $stream .= "0.52 0.66 0.78 rg\n";
    $stream .= pdf_stream_circle($cx, $cy, $r, 'f');
    $stream .= "0.94 0.84 0.74 rg\n";
    $stream .= pdf_stream_circle($cx + ($r * 0.22), $cy - ($r * 0.18), $r * 0.78, 'f');

    $stream .= "1 1 1 rg\n";
    $stream .= pdf_stream_circle($cx - ($r * 0.08), $cy - ($r * 0.05), $r * 0.22, 'f');
    $stream .= pdf_stream_circle($cx - ($r * 0.38), $cy + ($r * 0.22), $r * 0.12, 'f');
    $stream .= pdf_stream_circle($cx - ($r * 0.16), $cy + ($r * 0.36), $r * 0.12, 'f');
    $stream .= pdf_stream_circle($cx + ($r * 0.08), $cy + ($r * 0.32), $r * 0.12, 'f');
    $stream .= pdf_stream_circle($cx + ($r * 0.30), $cy + ($r * 0.20), $r * 0.12, 'f');

    return $stream;
}

function pdf_build_table_lines($headers, $rows) {
    $col_count = count($headers);
    $total_chars = 96;
    $widths = array_fill(0, $col_count, 12);

    foreach ($headers as $i => $h) {
        $hLower = strtolower($h);
        if (strpos($hLower, 'email') !== false) $widths[$i] = 34;
        elseif (strpos($hLower, 'name') !== false) $widths[$i] = 20;
        elseif (strpos($hLower, 'status') !== false) $widths[$i] = 12;
        elseif (strpos($hLower, 'role') !== false) $widths[$i] = 12;
        elseif (strpos($hLower, 'breed') !== false) $widths[$i] = 16;
        elseif (strpos($hLower, 'service') !== false) $widths[$i] = 20;
    }
    $sum = array_sum($widths);
    if ($sum > $total_chars) {
        $over = $sum - $total_chars;
        for ($i = $col_count - 1; $i >= 0 && $over > 0; $i--) {
            $reduce = min($over, max(0, $widths[$i] - 8));
            $widths[$i] -= $reduce;
            $over -= $reduce;
        }
    } elseif ($sum < $total_chars) {
        $widths[$col_count - 1] += ($total_chars - $sum);
    }

    $lines = [];
    $headerParts = [];
    foreach ($headers as $i => $h) {
        $headerParts[] = pdf_fit_cell($h, $widths[$i]);
    }
    $lines[] = rtrim(implode(' ', $headerParts));
    $lines[] = str_repeat('-', min($total_chars, strlen($lines[0])));

    foreach ($rows as $row) {
        $parts = [];
        for ($i = 0; $i < $col_count; $i++) {
            $parts[] = pdf_fit_cell($row[$i] ?? '', $widths[$i]);
        }
        $lines[] = rtrim(implode(' ', $parts));
    }

    return $lines;
}

function build_prescription_lines($title, $headers, $rows, $meta = []) {
    $lines = [];
    $lines[] = 'Pamper Your Pet';
    $lines[] = 'Veterinary Care and Wellness';
    $lines[] = '';
    $lines[] = $title;
    $lines[] = 'Date: ' . date('M d, Y');
    $lines[] = str_repeat('-', 96);
    $lines[] = 'REPORT DETAILS';
    if (!empty($meta)) {
        foreach ($meta as $label => $value) {
            $lines[] = str_pad($label . ':', 20) . $value;
        }
    }
    $lines[] = str_repeat('-', 96);
    $lines[] = 'DATA';
    $lines[] = '';
    $lines = array_merge($lines, pdf_build_table_lines($headers, $rows));
    return $lines;
}

function build_simple_pdf($title, $headers, $rows, $meta = []) {
    $pageWidth = 612;
    $pageHeight = 792;
    $margin = 40;
    $contentWidth = $pageWidth - ($margin * 2);

    $colCount = count($headers);
    if ($colCount === 0) {
        $headers = ['Message'];
        $rows = [['No data available for this report.']];
        $colCount = 1;
    }

    $widths = pdf_calc_column_widths($headers, $rows, $contentWidth);

    $fontBody = "F1";
    $fontBold = "F2";

    $streams = [];
    $rowHeight = 18;
    $headerHeight = 24;
    $cardPadding = 14;
    $headerBlockHeight = 88;

    $buildPage = function($pageRows) use ($title, $headers, $rows, $meta, $widths, $pageWidth, $pageHeight, $margin, $contentWidth, $fontBody, $fontBold, $rowHeight, $headerHeight, $cardPadding, $headerBlockHeight) {
        $y = $pageHeight - $margin;
        $x = $margin;
        $stream = "";

        $stream .= pdf_stream_logo($x, $y - 4, 28);
        $stream .= "0 0 0 rg\n";
        $stream .= pdf_stream_text($x + 40, $y - 6, 'Pamper Your Pet', $fontBold, 15);
        $stream .= pdf_stream_text($x + 40, $y - 24, 'Veterinary Care and Wellness', $fontBody, 9);

        $stream .= "0.25 0.31 0.45 rg\n";
        $stream .= pdf_stream_text($x + $contentWidth - 120, $y - 6, 'Report', $fontBold, 12);
        $generated = $meta['Generated'] ?? date('M d, Y');
        $stream .= pdf_stream_text($x + $contentWidth - 160, $y - 24, 'Date: ' . $generated, $fontBody, 9);

        $stream .= "0.86 0.89 0.92 RG\n";
        $stream .= pdf_stream_line($x, $y - 38, $x + $contentWidth, $y - 38);

        $y = $y - $headerBlockHeight;

        $cardX = $x;
        $cardY = $y;
        $cardH = $y - ($margin + 30);
        $stream .= "0.88 0.90 0.95 RG\n1 1 1 rg\n";
        $stream .= pdf_stream_rect($cardX, $margin + 22, $contentWidth, $cardH - ($margin + 22), 'B');

        $titleY = $cardY - 8;
        $stream .= "0 0 0 rg\n";
        $stream .= pdf_stream_text($cardX + $cardPadding, $titleY, $title, $fontBold, 13);

        $watermarkY = ($margin + $cardH) / 2;
        $stream .= "0.9 0.92 0.95 rg\n";
        $stream .= pdf_stream_text($cardX + 120, $watermarkY, 'PAMPER PET', $fontBold, 42);

        $y = $titleY - 18;
        $stream .= "0.90 0.93 0.97 rg\n0.80 0.85 0.90 RG\n";
        $colX = $cardX + $cardPadding;
        for ($i = 0; $i < count($headers); $i++) {
            $stream .= pdf_stream_rect($colX, $y - $headerHeight, $widths[$i], $headerHeight, 'B');
            $text = pdf_truncate_to_width($headers[$i], $widths[$i]);
            $stream .= "0 0 0 rg\n";
            $stream .= pdf_stream_text($colX + 4, $y - 16, $text, $fontBold, 10);
            $stream .= "0.90 0.93 0.97 rg\n0.80 0.85 0.90 RG\n";
            $colX += $widths[$i];
        }

        $y -= $headerHeight;
        $rowIndex = 0;
        foreach ($pageRows as $row) {
            $fill = ($rowIndex % 2 === 0) ? "0.98 0.98 0.99 rg\n" : "1 1 1 rg\n";
            $stream .= $fill . "0.86 0.89 0.92 RG\n";
            $colX = $cardX + $cardPadding;
            for ($i = 0; $i < count($headers); $i++) {
                $stream .= $fill . "0.86 0.89 0.92 RG\n";
                $stream .= pdf_stream_rect($colX, $y - $rowHeight, $widths[$i], $rowHeight, 'B');
                $cell = pdf_truncate_to_width($row[$i] ?? '', $widths[$i]);
                $stream .= "q 0 0 0 rg\n";
                $stream .= pdf_stream_text($colX + 4, $y - 14, $cell, $fontBody, 9);
                $stream .= "Q\n";
                $colX += $widths[$i];
            }
            $y -= $rowHeight;
            $rowIndex++;
        }

        return $stream;
    };

    $availableHeight = $pageHeight - ($margin * 2) - $headerBlockHeight - 48 - $headerHeight;
    $rowsPerPage = max(1, (int)floor($availableHeight / $rowHeight));
    $pages = array_chunk($rows, $rowsPerPage);
    if (count($pages) === 0) $pages = [[]];

    foreach ($pages as $pageRows) {
        $streams[] = $buildPage($pageRows);
    }

    $objects = [];
    $addObj = function($content) use (&$objects) {
        $objects[] = $content;
        return count($objects);
    };

    $fontId = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>");
    $fontBoldId = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>");

    $pageIds = [];
    foreach ($streams as $stream) {
        $contentId = $addObj("<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream");
        $pageId = $addObj("<< /Type /Page /Parent PARENT_ID 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 " . $fontId . " 0 R /F2 " . $fontBoldId . " 0 R >> >> /Contents " . $contentId . " 0 R >>");
        $pageIds[] = $pageId;
    }

    $kids = [];
    foreach ($pageIds as $id) {
        $kids[] = $id . " 0 R";
    }
    $pagesId = $addObj("<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count " . count($pageIds) . " >>");
    $catalogId = $addObj("<< /Type /Catalog /Pages " . $pagesId . " 0 R >>");

    foreach ($objects as $idx => $obj) {
        $objects[$idx] = str_replace("PARENT_ID 0 R", $pagesId . " 0 R", $obj);
    }

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $obj) {
        $offsets[$i + 1] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root " . $catalogId . " 0 R >>\n";
    $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";
    return $pdf;
}

$export_format = strtolower($_GET['export_format'] ?? '');
if ($export_format !== '' && $active_report !== '') {
    $title = '';
    $headers = [];
    $rows = [];

    if ($active_report === 'user-registration') {
        $title = 'User Registration Report';
        $headers = ['Name', 'Email', 'Clinic Name', 'Clinic Address', 'Vet Registration', 'Role', 'Status'];
        if ($report_user_rows) {
            while ($row = $report_user_rows->fetch_assoc()) {
                $rows[] = [
                    $row['name'],
                    $row['email'],
                    $row['clinic_name'] ?? '-',
                    $row['clinic_address'] ?? '-',
                    $row['vet_registration'] ?? '-',
                    $row['role'],
                    $row['status']
                ];
            }
        }
    } elseif ($active_report === 'pet-registration') {
        $title = 'Pet Registration Report';
        $headers = ['ID', 'Pet Name', 'Species', 'Age', 'Breed', 'Owner', 'Owner Email'];
        if ($report_pet_rows) {
            while ($row = $report_pet_rows->fetch_assoc()) {
                $rows[] = [$row['pet_id'], $row['pet_name'], $row['species'], $row['age'], $row['breed'], $row['owner_name'], $row['owner_email']];
            }
        }
    } elseif ($active_report === 'appointment-summary') {
        $title = 'Appointment Summary Report';
        $headers = ['Total', 'Pending', 'Confirmed', 'Completed'];
        if ($report_appointment_summary_rows) {
            if ($row = $report_appointment_summary_rows->fetch_assoc()) {
                $rows[] = [$row['total'], $row['pending'], $row['confirmed'], $row['completed']];
            }
        }
    } elseif ($active_report === 'appointment-status') {
        $title = 'Appointment Status Report';
        $headers = ['Status', 'Total'];
        if ($report_appointment_status_rows) {
            while ($row = $report_appointment_status_rows->fetch_assoc()) {
                $rows[] = [$row['status'], $row['total']];
            }
        }
    } elseif ($active_report === 'vet-performance') {
        $title = 'Vet Performance Report';
        $headers = ['Vet Name', 'Total Appointments', 'Completed'];
        if ($report_vet_performance_rows) {
            while ($row = $report_vet_performance_rows->fetch_assoc()) {
                $rows[] = [$row['vet_name'], $row['total_appointments'], $row['completed']];
            }
        }
    } elseif ($active_report === 'payment-report') {
        $title = 'Payment Report';
        $headers = ['Payment Method', 'Payment Status', 'Total Payments'];
        if ($report_payment_rows) {
            while ($row = $report_payment_rows->fetch_assoc()) {
                $rows[] = [$row['payment_method'], $row['payment_status'], $row['total_payments']];
            }
        }
    } elseif ($active_report === 'service-usage') {
        $title = 'Service Usage Report';
        $headers = ['Service', 'Total Usage'];
        if ($report_service_usage_rows) {
            while ($row = $report_service_usage_rows->fetch_assoc()) {
                $rows[] = [$row['service_name'], $row['total_usage']];
            }
        }
    } elseif ($active_report === 'clinic-activity') {
        $title = 'Clinic Activity Report';
        $headers = ['Clinic', 'Total Appointments'];
        if ($report_clinic_activity_rows) {
            while ($row = $report_clinic_activity_rows->fetch_assoc()) {
                $rows[] = [$row['clinic_name'], $row['total_appointments']];
            }
        }
    } elseif ($active_report === 'monthly-growth') {
        $title = 'Monthly Growth Report';
        $report_type = $_GET['report_type'] ?? 'appointments';
        if ($report_type === 'users') {
            $headers = ['Month', 'Pet Parents', 'Veterinarians', 'Total Users'];
        } elseif ($report_type === 'payments') {
            $headers = ['Month', 'Total Earnings'];
        } else {
            $headers = ['Month', 'Total'];
        }
        if ($report_monthly_growth_rows) {
            while ($row = $report_monthly_growth_rows->fetch_assoc()) {
                if ($report_type === 'users') {
                    $rows[] = [$row['month'], $row['pet_parents'], $row['veterinarians'], $row['total_users']];
                } else {
                    $rows[] = [$row['month'], $row['total']];
                }
            }
        }
    } elseif ($active_report === 'user-activity') {
        $title = 'User Activity Report';
        $headers = ['Name', 'Email', 'Total Appointments'];
        if ($report_user_activity_rows) {
            while ($row = $report_user_activity_rows->fetch_assoc()) {
                $rows[] = [$row['name'], $row['email'], $row['total_appointments']];
            }
        }
    }

    if (!$headers) {
        $title = 'Report Export';
        $headers = ['Message'];
        $rows = [['No data available for this report.']];
    }

    $safeBase = preg_replace('/[^a-z0-9_-]+/i', '-', $active_report) . '-' . date('Ymd_His');
    if ($export_format === 'excel') {
        $meta = [
            'Report' => $title,
            'Generated' => date('Y-m-d H:i')
        ];
        $content = build_excel_html($title, $headers, $rows, $meta);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $safeBase . '.xls"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $content;
        exit;
    }
    if ($export_format === 'word') {
        $meta = [
            'Report' => $title,
            'Generated' => date('Y-m-d H:i')
        ];
        $content = build_word_html($title, $headers, $rows, $meta);
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="' . $safeBase . '.doc"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $content;
        exit;
    }
    if ($export_format === 'pdf') {
        $meta = [
            'Report' => $title,
            'Generated' => date('Y-m-d H:i')
        ];
        $content = build_simple_pdf($title, $headers, $rows, $meta);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeBase . '.pdf"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $content;
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAMPER YOUR PET - Admin Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
            --shadow-soft: 0 16px 35px rgba(15, 23, 42, 0.12);
            --shadow-card: 0 12px 30px rgba(15, 23, 42, 0.08);
            --radius-lg: 22px;
            --radius-md: 16px;
            --radius-sm: 12px;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }

        body {
            display: flex;
            height: 100vh;
            background: radial-gradient(circle at 10% 20%, #eff6ff 0%, #f8fafc 40%, #ffffff 100%);
            overflow: hidden;
            color: var(--ink-900);
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 270px;
            background: linear-gradient(180deg, #0e2d4a, #0b2236 60%, #0a1d2f 100%);
            color: var(--surface);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand {
            padding: 26px 24px 22px;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--surface);
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            letter-spacing: 0.4px;
        }

        .brand i {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #ffffff 0%, #cfe3f7 45%, #88b7ea 100%);
            display: grid;
            place-items: center;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(0,0,0,0.18);
        }

        .brand span { color: #ffffff; font-family: 'Playfair Display', serif; letter-spacing: 0.08em; }

        .menu { flex: 1; padding-top: 20px; overflow-y: auto; }
        
        .menu-item {
            padding: 14px 22px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: 0.3s;
            color: #e7f0fb;
            text-decoration: none;
            border-left: 4px solid transparent;
        }

        .menu-item i { color: #cfe4ff; width: 18px; text-align: center; }
        .menu-item:hover { background-color: rgba(18, 55, 90, 0.65); color: #ffffff; }
        .menu-item.active {
            background-color: rgba(18, 55, 90, 0.9);
            color: #ffffff;
            border-left: 4px solid #f4b59f;
        }

        .menu-group { display: flex; flex-direction: column; }
        .menu-toggle { width: 100%; background: transparent; border: none; text-align: left; }
        .menu-label { display: flex; align-items: center; gap: 15px; }
        .caret { margin-left: auto; font-size: 0.9rem; transition: transform 0.2s ease; color: #cfe4ff; }
        .menu-group.open .caret { transform: rotate(180deg); }
        .submenu {
            display: none;
            flex-direction: column;
            background: rgba(255,255,255,0.04);
        }
        .menu-group.open .submenu { display: flex; }
        .submenu-item {
            padding: 12px 25px 12px 52px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cfe0f5;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .submenu-item:hover { background-color: rgba(18, 55, 90, 0.6); color: #ffffff; }
        .submenu-item.active { background-color: rgba(18, 55, 90, 0.9); color: #ffffff; border-left: 4px solid #f4b59f; }

        .logout { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn {
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid rgba(242, 180, 141, 0.6);
            color: var(--accent-400);
            border-radius: 999px;
            cursor: pointer;
            transition: 0.3s;
        }
        .logout-btn:hover { background: var(--accent-400); color: var(--brand-900); }

        /* --- Main Content --- */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

        .top-bar {
            height: 70px;
            background: var(--surface);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.06);
            border-bottom: 1px solid var(--border);
        }

        .search-bar {
            background: var(--surface-alt);
            padding: 10px 16px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            width: 300px;
            border: 1px solid var(--border);
        }

        .search-bar input {
            border: none;
            background: transparent;
            margin-left: 10px;
            outline: none;
            width: 100%;
        }

        .user-info { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--brand-500), var(--accent-400)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; }

        /* --- Content Views --- */
        .view-container {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
            display: none; 
        }
        
        .view-container.active { display: block; animation: fadeIn 0.3s ease; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        h2 {
            margin-bottom: 20px;
            color: var(--ink-900);
            font-size: 1.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Playfair Display', serif;
        }

        h3 {
            font-family: 'Playfair Display', serif;
            color: var(--ink-900);
            margin-bottom: 12px;
        }

        /* --- Buttons --- */
        .btn-primary {
            background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 20px rgba(47, 126, 199, 0.2);
        }
        .btn-primary:hover { transform: translateY(-1px); }

        /* --- Dashboard Cards --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        
        .stat-card {
            background: var(--surface);
            padding: 25px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-card);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-info h3 { font-size: 2rem; color: var(--ink-900); margin-bottom: 5px; }
        .stat-info p { color: var(--ink-600); font-size: 0.9rem; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .bg-blue { background: #e6f2ff; color: var(--brand-700); }
        .bg-green { background: #dcfce7; color: var(--success); }

        /* --- Tables --- */
        .table-container {
            background: var(--surface);
            padding: 20px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-card);
            overflow-x: auto;
        }


        .report-actions {
            display: flex;
            gap: 10px;
            margin: 8px 0 12px;
            flex-wrap: wrap;
        }
        .btn-report {
            border: 1px solid var(--brand-500);
            background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-report:hover { transform: translateY(-1px); filter: brightness(0.95); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
        th { color: var(--ink-500); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.08em; }

        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0 18px;
        }
        .filter-tag {
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink-700);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .filter-tag:hover { transform: translateY(-1px); }
        .filter-tag.active {
            background: var(--brand-500);
            border-color: var(--brand-500);
            color: #ffffff;
        }
        
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-confirmed { background: #d1fae5; color: #059669; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .status-completed { background: #e5e7eb; color: #4b5563; }

        .action-btn { border: none; padding: 6px 12px; border-radius: 999px; cursor: pointer; font-size: 0.8rem; margin-right: 5px; transition: 0.2s; color: white;}
        .btn-approve { background: var(--brand-700); }
        .btn-reject { background: var(--danger); }
        .btn-complete { background: var(--success); }
        .btn-edit { background: #64748b; }
        .action-btn:hover { opacity: 0.9; }

        /* --- Service List (Dynamic) --- */
        .service-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .service-item {
            background: var(--surface);
            padding: 20px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-card);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--brand-500);
        }
        .service-item h4 { color: var(--ink-900); font-size: 1rem; }
        .btn-delete-service { background: none; border: none; color: var(--text-light); cursor: pointer; font-size: 1.1rem; }
        .btn-delete-service:hover { color: var(--danger); }

        /* --- MODALS --- */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;
        }

        .modal-content {
            background-color: var(--surface);
            padding: 30px;
            border-radius: var(--radius-md);
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-soft);
            animation: fadeIn 0.3s;
        }

        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close-modal { cursor: pointer; font-size: 1.2rem; color: #999; }
        .close-modal:hover { color: var(--danger); }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.9rem; font-weight: 600; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            background: #ffffff;
        }
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            background: #ffffff;
            min-height: 90px;
            resize: vertical;
        }

        .modal-footer { text-align: right; margin-top: 20px; }
        .btn-save {
            background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            margin-right: 10px;
            box-shadow: 0 10px 20px rgba(47, 126, 199, 0.2);
        }
        .btn-cancel { background: #e2e8f0; color: #1f2937; padding: 10px 20px; border: none; border-radius: 999px; cursor: pointer; }

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
</head>
<body>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-paw"></i> <span>PAMPER ADMIN</span>
        </div>
        <div class="menu">
            <a href="#" class="menu-item active" onclick="showView('dashboard')">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="#" class="menu-item" onclick="showView('appointments')">
                <i class="fas fa-calendar-check"></i> Appointments
            </a>
            <a href="#" class="menu-item" onclick="showView('vets')">
                <i class="fas fa-user-md"></i> Veterinarians
            </a>
            <a href="#" class="menu-item" onclick="showView('services')">
                <i class="fas fa-stethoscope"></i> Services
            </a>
            <a href="#" class="menu-item" onclick="showView('payments')">
                <i class="fas fa-receipt"></i> Payments
            </a>
            <a href="#" class="menu-item" onclick="showView('prescriptions')">
                <i class="fas fa-prescription-bottle-medical"></i> Prescriptions
            </a>
            <a href="#" class="menu-item" onclick="showView('feedback')">
                <i class="fas fa-comment-dots"></i> Feedback
            </a>
            <a href="#" class="menu-item" onclick="showView('pets')">
                <i class="fas fa-paw"></i> Pets
            </a>
            <div class="menu-group">
                <button class="menu-item menu-toggle" onclick="toggleReportMenu()" type="button">
                    <span class="menu-label">
                        <i class="fas fa-chart-line"></i> Reports
                    </span>
                    <i class="fas fa-chevron-down caret"></i>
                </button>
                <div class="submenu" id="reportsMenu">
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('user-registration')">
                        <i class="fas fa-user-plus"></i> User Registration Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('pet-registration')">
                        <i class="fas fa-paw"></i> Pet Registration Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('appointment-summary')">
                        <i class="fas fa-clipboard-list"></i> Appointment Summary Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('appointment-status')">
                        <i class="fas fa-list-check"></i> Appointment Status Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('vet-performance')">
                        <i class="fas fa-user-md"></i> Vet Performance Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('payment-report')">
                        <i class="fas fa-receipt"></i> Payment Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('service-usage')">
                        <i class="fas fa-stethoscope"></i> Service Usage Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('clinic-activity')">
                        <i class="fas fa-hospital"></i> Clinic Activity Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('monthly-growth')">
                        <i class="fas fa-chart-line"></i> Monthly Growth Report
                    </a>
                    <a href="#" class="submenu-item" onclick="showView('reports'); setReportFilter('user-activity')">
                        <i class="fas fa-user-check"></i> User Activity Report
                    </a>
                </div>
            </div>
        </div>
        <div class="logout">
            <button class="logout-btn" onclick="window.location.href='login.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="search-bar">
                <i class="fas fa-search" style="color: #9ca3af;"></i>
                <input type="text" id="globalSearch" placeholder="Search appointments..." onkeyup="filterAppointments()">
            </div>
            <div class="user-info">
                <div style="text-align: right; margin-right: 10px;">
                    <h4 style="font-size: 0.9rem;">Admin User</h4>
                    <span style="font-size: 0.75rem; color: #6b7280;">Super Admin</span>
                </div>
                <div class="avatar">AD</div>
            </div>
        </div>

        <!-- VIEW 1: DASHBOARD OVERVIEW -->
        <div id="dashboard" class="view-container active">
            <h2>Dashboard Overview</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3 id="totalAppts" data-db="1"><?php echo $total_appointments_count; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                    <div class="stat-icon bg-blue"><i class="fas fa-calendar-alt"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3 id="pendingAppts" data-db="1"><?php echo $pending_appointments_count; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                    <div class="stat-icon bg-orange"><i class="fas fa-clock"></i></div>
                </div>
            </div>

            <div class="table-container">

<h3>New User Registrations</h3>

<table>

  <thead>
  <tr>
  <th>Name</th>
  <th>Email</th>
  <th>Clinic Name</th>
  <th>Clinic Address</th>
  <th>Vet Registration</th>
  <th>Role</th>
  <th>Status</th>
  <th>Action</th>
  </tr>
  </thead>

<tbody>

<?php
while($row = $users->fetch_assoc()){
?>

  <tr>
  <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
  <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
  <td><?php echo htmlspecialchars($row['clinic_name'] ?? '-'); ?></td>
  <td><?php echo htmlspecialchars($row['clinic_address'] ?? '-'); ?></td>
  <td><?php echo htmlspecialchars($row['vet_registration'] ?? '-'); ?></td>
  <td><?php echo $row['role']; ?></td>

<td>
<?php
if($row['status']=="pending"){
echo "<span class='status-badge status-pending'>Pending</span>";
}
else{
echo "<span class='status-badge status-confirmed'>Approved</span>";
}
?>
</td>

<td>

<?php if($row['status']=="pending"){ ?>

<a href="approve-vet.php?id=<?php echo $row['user_id']; ?>" class="action-btn btn-approve">
Approve
</a>

<a href="reject-vet.php?id=<?php echo $row['user_id']; ?>" class="action-btn btn-reject">
Reject
</a>

<?php } else { echo "-"; } ?>

</td>

</tr>

<?php
}
?>

</tbody>

</table>

</div>

            <div class="table-container">
                <h3>Recent Activity</h3>
                <table id="recentTable">
                    <thead>
                        <tr>
                            <th>Pet Owner</th>
                            <th>Pet Name</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentTableBody" data-db="1">
<?php if ($recent_appointments && $recent_appointments->num_rows > 0) { ?>
<?php while ($row = $recent_appointments->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['owner_name']; ?></td>
                            <td><?php echo $row['pet_name']; ?></td>
                            <td><?php echo $row['service_name'] ?? '-'; ?></td>
                            <td>
<?php
if ($row['status'] === "pending") {
    echo "<span class='status-badge status-pending'>Pending</span>";
} elseif ($row['status'] === "confirmed") {
    echo "<span class='status-badge status-confirmed'>Confirmed</span>";
} elseif ($row['status'] === "cancelled") {
    echo "<span class='status-badge status-cancelled'>Rejected</span>";
} else {
    echo "<span class='status-badge status-completed'>Completed</span>";
}
?>
                            </td>
                        </tr>
<?php } ?>
<?php } else { ?>
                        <tr>
                            <td colspan="4" style="color:#6b7280;">No recent activity</td>
                        </tr>
<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 2: APPOINTMENTS MANAGEMENT -->
        <div id="appointments" class="view-container">
            <h2>
                Manage Appointments 
                <button class="btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> New Appointment
                </button>
            </h2>
            <div class="table-container">
                <table id="appointmentTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Owner</th>
                            <th>Pet</th>
                            <th>Date</th>
                            <th>Service</th>
                            <th>Vet</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

<?php
while($row = $result->fetch_assoc()){
?>

<tr>

<td>#<?php echo $row['appointment_id']; ?></td>

<td><?php echo $row['owner_name']; ?></td>

<td><?php echo $row['pet_name']; ?></td>

<td><?php echo $row['appointment_date']; ?></td>

<td><?php echo $row['service_id']; ?></td>

<td><?php echo $row['vet_name']; ?></td>

<td>

<?php
if($row['status']=="pending"){
echo "<span class='status-badge status-pending'>Pending</span>";
}
elseif($row['status']=="confirmed"){
echo "<span class='status-badge status-confirmed'>Confirmed</span>";
}
elseif($row['status']=="cancelled"){
echo "<span class='status-badge status-cancelled'>Rejected</span>";
}
else{
echo "<span class='status-badge status-completed'>Completed</span>";
}
?>

</td>

<td>

<button class="action-btn btn-edit"
    onclick="openAppointmentEdit(this)"
    data-id="<?php echo $row['appointment_id']; ?>"
    data-owner="<?php echo htmlspecialchars($row['owner_name']); ?>"
    data-pet="<?php echo htmlspecialchars($row['pet_name']); ?>"
    data-date="<?php echo htmlspecialchars($row['appointment_date']); ?>"
    data-time="<?php echo htmlspecialchars($row['appointment_time']); ?>"
    data-service="<?php echo htmlspecialchars($row['service_id']); ?>"
    data-vet="<?php echo htmlspecialchars($row['vet_id']); ?>">
    <i class="fas fa-edit"></i>
</button>

<?php if ($row['status'] === 'pending') { ?>
<a href="approve-appointment.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn btn-approve" title="Approve">
<i class="fas fa-check"></i>
</a>
<a href="reject-appointment-admin.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn btn-reject" title="Reject">
<i class="fas fa-times"></i>
</a>
<?php } elseif ($row['status'] === 'confirmed') { ?>
<a href="complete-appointment-admin.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn btn-complete" title="Complete">
<i class="fas fa-check-double"></i>
</a>
<a href="reject-appointment-admin.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn btn-reject" title="Reject">
<i class="fas fa-times"></i>
</a>
<?php } elseif ($row['status'] === 'cancelled') { ?>
<a href="approve-appointment.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn btn-approve" title="Re-approve">
<i class="fas fa-check"></i>
</a>
<?php } ?>

</td>

</tr>

<?php
}
?>

</tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 3: VETS -->
        <div id="vets" class="view-container">
            <h2>Our Veterinarians</h2>
            <div class="table-container">
                <table>
                      <thead>
                          <tr>
                              <th>Doctor Name</th>
                              <th>Clinic Name</th>
                              <th>Clinic Address</th>
                              <th>Vet Registration</th>
                              <th>Status</th>
                              <th>Contact</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                    <tbody>

<?php
while($row = $vets->fetch_assoc()){
?>

  <tr>
  
  <td>Dr. <?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
  
  <td><?php echo htmlspecialchars($row['clinic_name'] ?? '-'); ?></td>
  <td><?php echo htmlspecialchars($row['clinic_address'] ?? '-'); ?></td>
  <td><?php echo htmlspecialchars($row['vet_registration'] ?? '-'); ?></td>

<td>
<span class="status-badge status-confirmed">
Approved
</span>
</td>

<td><?php echo $row['email']; ?></td>

<td>
    <button class="action-btn btn-edit"
        onclick="openVetEdit(this)"
        data-id="<?php echo $row['user_id']; ?>"
        data-name="<?php echo htmlspecialchars($row['name']); ?>"
        data-email="<?php echo htmlspecialchars($row['email']); ?>"
        data-clinic-name="<?php echo htmlspecialchars($row['clinic_name']); ?>"
        data-clinic-address="<?php echo htmlspecialchars($row['clinic_address']); ?>"
        data-vet-registration="<?php echo htmlspecialchars($row['vet_registration']); ?>">
        Edit
    </button>
</td>

</tr>

<?php
}
?>

</tbody>
                </table>
            </div>
        </div>
        
        <!-- VIEW 4: SERVICES -->
<div id="services" class="view-container">

<h2>
Manage Services
<button class="btn-primary" onclick="openServiceModal()">
<i class="fas fa-plus"></i> Add Service
</button>
</h2>

<div class="table-container">

<table>

<thead>
<tr>
<th>S.No</th>
<th>Service Name</th>
<th>Description</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php $service_index = 0; ?>
<?php while($row = $services->fetch_assoc()){ $service_index++; ?>

<tr>

<td><?php echo $service_index; ?></td>

<td><?php echo $row['service_name']; ?></td>

<td><?php echo $row['description']; ?></td>


<td>
<button class="action-btn btn-edit"
    onclick="openServiceEdit(this)"
    data-id="<?php echo $row['service_id']; ?>"
    data-name="<?php echo htmlspecialchars($row['service_name']); ?>"
    data-description="<?php echo htmlspecialchars($row['description']); ?>">
    Modify
</button>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>
        </div>
        <!-- VIEW 5: PAYMENTS -->
        <div id="payments" class="view-container">
            <h2>Payments</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Owner</th>
                            <th>Pet</th>
                            <th>Vet</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if ($payments && $payments->num_rows > 0) { ?>
<?php while ($row = $payments->fetch_assoc()) { ?>
                        <tr>
                            <td>#<?php echo $row['payment_id']; ?></td>
                            <td><?php echo $row['owner_name'] ?? '-'; ?></td>
                            <td><?php echo $row['pet_name'] ?? '-'; ?></td>
                            <td><?php echo $row['vet_name'] ?? '-'; ?></td>
                            <td><?php echo $row['payment_method'] ?? '-'; ?></td>
                            <td><?php echo $row['amount'] ?? '-'; ?></td>
                            <td><?php echo $row['payment_status'] ?? '-'; ?></td>
                            <td><?php echo $row['payment_date'] ?? '-'; ?></td>
                        </tr>
<?php } ?>
<?php } else { ?>
                        <tr>
                            <td colspan="8" style="color:#6b7280;">No payments found</td>
                        </tr>
<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 6: PRESCRIPTIONS -->
        <div id="prescriptions" class="view-container">
            <h2>Prescriptions</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet</th>
                            <th>Owner</th>
                            <th>Veterinarian</th>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if ($prescriptions && $prescriptions->num_rows > 0) { ?>
<?php while ($row = $prescriptions->fetch_assoc()) { ?>
                        <tr>
                            <td>#<?php echo $row['prescription_id']; ?></td>
                            <td><?php echo $row['pet_name'] ?? '-'; ?></td>
                            <td><?php echo $row['owner_name'] ?? '-'; ?></td>
                            <td><?php echo $row['vet_name'] ?? '-'; ?></td>
                            <td><?php echo $row['medication']; ?></td>
                            <td><?php echo $row['dosage']; ?></td>
                            <td><?php echo $row['frequency']; ?></td>
                            <td><?php echo $row['duration']; ?></td>
                            <td>
                                <button class="action-btn btn-edit"
                                    onclick="openPrescriptionEdit(this)"
                                    data-id="<?php echo $row['prescription_id']; ?>"
                                    data-medication="<?php echo htmlspecialchars($row['medication']); ?>"
                                    data-dosage="<?php echo htmlspecialchars($row['dosage']); ?>"
                                    data-frequency="<?php echo htmlspecialchars($row['frequency']); ?>"
                                    data-duration="<?php echo htmlspecialchars($row['duration']); ?>"
                                    data-instructions="<?php echo htmlspecialchars($row['instructions'] ?? ''); ?>">
                                    Edit
                                </button>
                            </td>
                        </tr>
<?php } ?>
<?php } else { ?>
                        <tr>
                            <td colspan="9" style="color:#6b7280;">No prescriptions found</td>
                        </tr>
<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 7: FEEDBACK -->
        <div id="feedback" class="view-container">
            <h2>Feedback</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Pet</th>
                            <th>Visit Type</th>
                            <th>Vet Name</th>
                            <th>Clinic</th>
                            <th>Comments</th>
                            <th>Visit Date</th>
                            <th>Rating</th>
                            <th>Contact OK</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if ($feedbacks && $feedbacks->num_rows > 0) { ?>
<?php while ($row = $feedbacks->fetch_assoc()) { ?>
                        <tr>
                            <td>#<?php echo $row['feedback_id']; ?></td>
                            <td><?php echo $row['user_name'] ?? '-'; ?></td>
                            <td><?php echo $row['pet_name'] ?? '-'; ?></td>
                            <td><?php echo $row['visit_type']; ?></td>
                            <td><?php echo $row['vet_name']; ?></td>
                            <td><?php echo $row['clinic_name'] ?? '-'; ?></td>
                            <td><?php echo $row['comments']; ?></td>
                            <td><?php echo $row['visit_date']; ?></td>
                            <td><?php echo $row['rating']; ?></td>
                            <td><?php echo ((int)$row['contact_ok'] === 1) ? 'Yes' : 'No'; ?></td>
                            <td>
                                <button class="action-btn btn-edit"
                                    onclick="openFeedbackEdit(this)"
                                    data-id="<?php echo $row['feedback_id']; ?>"
                                    data-visit-type="<?php echo htmlspecialchars($row['visit_type']); ?>"
                                    data-vet-name="<?php echo htmlspecialchars($row['vet_name']); ?>"
                                    data-visit-date="<?php echo htmlspecialchars($row['visit_date']); ?>"
                                    data-rating="<?php echo htmlspecialchars($row['rating']); ?>"
                                    data-comments="<?php echo htmlspecialchars($row['comments']); ?>"
                                    data-contact-ok="<?php echo (int)$row['contact_ok']; ?>">
                                    Edit
                                </button>
                            </td>
                        </tr>
<?php } ?>
<?php } else { ?>
                        <tr>
                            <td colspan="9" style="color:#6b7280;">No feedback found</td>
                        </tr>
<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 8: PETS -->
        <div id="pets" class="view-container">
            <h2>Pets</h2>
            <div class="filter-tags" id="petFilterTags" aria-label="Filter pets by species">
                <button type="button" class="filter-tag active" data-filter="all" onclick="setPetFilterTag(this, 'all')">All</button>
                <button type="button" class="filter-tag" data-filter="cat" onclick="setPetFilterTag(this, 'cat')">Cat</button>
                <button type="button" class="filter-tag" data-filter="dog" onclick="setPetFilterTag(this, 'dog')">Dog</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet Name</th>
                            <th>Species</th>
                            <th>Age</th>
                            <th>Breed</th>
                            <th>Owner</th>
                            <th>Owner Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="petTableBody">
<?php if ($pets && $pets->num_rows > 0) { ?>
<?php while ($row = $pets->fetch_assoc()) { ?>
                        <tr data-species="<?php echo strtolower(trim($row['species'] ?? '')); ?>">
                            <td>#<?php echo $row['pet_id']; ?></td>
                            <td><?php echo $row['pet_name']; ?></td>
                            <td><?php echo $row['species']; ?></td>
                            <td><?php echo $row['age']; ?></td>
                            <td><?php echo $row['breed']; ?></td>
                            <td><?php echo $row['owner_name'] ?? '-'; ?></td>
                            <td><?php echo $row['owner_email'] ?? '-'; ?></td>
                            <td>
                                <button class="action-btn btn-edit"
                                    onclick="openPetEdit(this)"
                                    data-id="<?php echo $row['pet_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($row['pet_name']); ?>"
                                    data-type="<?php echo htmlspecialchars($row['species']); ?>"
                                    data-age="<?php echo htmlspecialchars($row['age']); ?>"
                                    data-breed="<?php echo htmlspecialchars($row['breed']); ?>"
                                    data-notes="<?php echo htmlspecialchars($row['vet_notes'] ?? ''); ?>">
                                    Edit
                                </button>
                            </td>
                        </tr>
<?php } ?>
                        <tr id="petNoMatchRow" style="display:none;">
                            <td colspan="8" style="color:#6b7280;">No pets found for this filter</td>
                        </tr>
<?php } else { ?>
                        <tr>
                            <td colspan="8" style="color:#6b7280;">No pets found</td>
                        </tr>
<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

                                                <!-- VIEW 9: REPORTS -->
        <div id="reports" class="view-container">
            <h2>Reports</h2>

            <select id="reportFilter" onchange="filterReports()" style="display: none;">
                <option value="all">All Reports</option>
                <option value="user-registration">User Registration Report</option>
                <option value="pet-registration">Pet Registration Report</option>
                <option value="appointment-summary">Appointment Summary Report</option>
                <option value="appointment-status">Appointment Status Report</option>
                <option value="vet-performance">Vet Performance Report</option>
                <option value="payment-report">Payment Report</option>
                <option value="service-usage">Service Usage Report</option>
                <option value="clinic-activity">Clinic Activity Report</option>
                <option value="monthly-growth">Monthly Growth Report</option>
                <option value="user-activity">User Activity Report</option>
            </select>

            <div class="table-container report-block" data-report="user-registration">
                <h3>User Registration Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="user-registration">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role">
                            <option value="all">All</option>
                            <option value="pet_parent" <?php echo (($_GET['role'] ?? '') === 'pet_parent') ? 'selected' : ''; ?>>Pet Parent</option>
                            <option value="vet" <?php echo (($_GET['role'] ?? '') === 'vet') ? 'selected' : ''; ?>>Vet</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all">All</option>
                            <option value="pending" <?php echo (($_GET['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo (($_GET['status'] ?? '') === 'approved') ? 'selected' : ''; ?>>Approved</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'user-registration') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                              <tr>
                                  <th>Name</th>
                                  <th>Email</th>
                                  <th>Clinic Name</th>
                                  <th>Clinic Address</th>
                                  <th>Vet Registration</th>
                                  <th>Role</th>
                                  <th>Status</th>
                              </tr>
                        </thead>
                        <tbody>
<?php if ($report_user_rows && $report_user_rows->num_rows > 0) { ?>
<?php while ($row = $report_user_rows->fetch_assoc()) { ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                  <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                  <td><?php echo htmlspecialchars($row['clinic_name'] ?? '-'); ?></td>
                                  <td><?php echo htmlspecialchars($row['clinic_address'] ?? '-'); ?></td>
                                  <td><?php echo htmlspecialchars($row['vet_registration'] ?? '-'); ?></td>
                                  <td><?php echo htmlspecialchars($row['role'] ?? ''); ?></td>
                                  <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                              </tr>
<?php } ?>
<?php } else { ?>
                              <tr>
                                  <td colspan="7" style="color:#6b7280;">No users found</td>
                              </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="pet-registration" style="margin-top: 20px;">
                <h3>Pet Registration Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="pet-registration">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Species</label>
                        <select name="species">
                            <option value="all">All</option>
<?php foreach ($species_list as $species) { ?>
                            <option value="<?php echo htmlspecialchars($species); ?>" <?php echo (($_GET['species'] ?? '') === $species) ? 'selected' : ''; ?>><?php echo htmlspecialchars($species); ?></option>
<?php } ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'pet-registration') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Pet ID</th>
                                <th>Pet Name</th>
                                <th>Species</th>
                                <th>Age</th>
                                <th>Breed</th>
                                <th>Owner</th>
                                <th>Owner Email</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_pet_rows && $report_pet_rows->num_rows > 0) { ?>
<?php while ($row = $report_pet_rows->fetch_assoc()) { ?>
                            <tr>
                                <td>#<?php echo $row['pet_id']; ?></td>
                                <td><?php echo $row['pet_name']; ?></td>
                                <td><?php echo $row['species']; ?></td>
                                <td><?php echo $row['age']; ?></td>
                                <td><?php echo $row['breed']; ?></td>
                                <td><?php echo $row['owner_name']; ?></td>
                                <td><?php echo $row['owner_email']; ?></td>
                            </tr>
<?php } ?>
<?php } else { ?>
                            <tr>
                                <td colspan="7" style="color:#6b7280;">No pets found</td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="appointment-summary" style="margin-top: 20px;">
                <h3>Appointment Summary Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="appointment-summary">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Service</label>
                        <select name="service_id">
                            <option value="all">All</option>
<?php foreach ($services_list as $srv) { ?>
                            <option value="<?php echo $srv['service_id']; ?>" <?php echo (($_GET['service_id'] ?? '') == $srv['service_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($srv['service_name']); ?></option>
<?php } ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'appointment-summary') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Total</th>
                                <th>Pending</th>
                                <th>Confirmed</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_appointment_summary_rows && $report_appointment_summary_rows->num_rows > 0) { ?>
<?php $row = $report_appointment_summary_rows->fetch_assoc(); ?>
                            <tr>
                                <td><?php echo $row['total']; ?></td>
                                <td><?php echo $row['pending']; ?></td>
                                <td><?php echo $row['confirmed']; ?></td>
                                <td><?php echo $row['completed']; ?></td>
                            </tr>
<?php } else { ?>
                            <tr>
                                <td colspan="4" style="color:#6b7280;">No appointment data found</td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="appointment-status" style="margin-top: 20px;">
                <h3>Appointment Status Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="appointment-status">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all">All</option>
                            <option value="pending" <?php echo (($_GET['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo (($_GET['status'] ?? '') === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo (($_GET['status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'appointment-status') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_appointment_status_rows && $report_appointment_status_rows->num_rows > 0) { ?>
<?php while ($row = $report_appointment_status_rows->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['status']; ?></td>
                                <td><?php echo $row['total']; ?></td>
                            </tr>
<?php } ?>
<?php } else { ?>
                            <tr>
                                <td colspan="2" style="color:#6b7280;">No appointment status data found</td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="vet-performance" style="margin-top: 20px;">
                <h3>Vet Performance Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="vet-performance">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Veterinarian</label>
                        <select name="vet_id">
                            <option value="all">All</option>
<?php foreach ($vets_list as $vet) { ?>
                            <option value="<?php echo $vet['user_id']; ?>" <?php echo (($_GET['vet_id'] ?? '') == $vet['user_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($vet['name']); ?></option>
<?php } ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'vet-performance') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Veterinarian</th>
                                <th>Total Appointments</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_vet_performance_rows && $report_vet_performance_rows->num_rows > 0) { ?>
<?php while ($row = $report_vet_performance_rows->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['vet_name']; ?></td>
                                <td><?php echo $row['total_appointments']; ?></td>
                                <td><?php echo $row['completed']; ?></td>
                            </tr>
<?php } ?>
<?php } else { ?>
                            <tr>
                                <td colspan="3" style="color:#6b7280;">No vet performance data found</td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="payment-report" style="margin-top: 20px;">
                <h3>Payment Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="payment-report">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method">
                            <option value="all">All</option>
                            <option value="upi" <?php echo (($_GET['payment_method'] ?? '') === 'upi') ? 'selected' : ''; ?>>UPI</option>
                            <option value="card" <?php echo (($_GET['payment_method'] ?? '') === 'card') ? 'selected' : ''; ?>>Card</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status">
                            <option value="all">All</option>
                            <option value="paid" <?php echo (($_GET['payment_status'] ?? '') === 'paid') ? 'selected' : ''; ?>>Paid</option>
                            <option value="pending" <?php echo (($_GET['payment_status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo (($_GET['payment_status'] ?? '') === 'failed') ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'payment-report') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Total Payments</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_payment_rows && $report_payment_rows->num_rows > 0) { ?>
<?php
$payment_rows = [];
$total_earnings = 0;
while ($row = $report_payment_rows->fetch_assoc()) {
    $payment_rows[] = $row;
    $total_earnings += (float)($row['total_payments'] ?? 0);
}
?>
<?php foreach ($payment_rows as $row) { ?>
                            <tr>
                                <td><?php echo $row['payment_method']; ?></td>
                                <td><?php echo $row['payment_status']; ?></td>
                                <td>₹<?php echo number_format((float)($row['total_payments'] ?? 0), 2); ?></td>
                            </tr>
<?php } ?>
<?php } else { ?>
                            <tr>
                                <td colspan="3" style="color:#6b7280;">No payment data found</td>
                            </tr>
<?php } ?>
                        </tbody>
<?php if (!empty($payment_rows)) { ?>
                        <tfoot>
                            <tr>
                                <th colspan="2" style="text-align:left;">Total Earnings</th>
                                <th>₹<?php echo number_format($total_earnings, 2); ?></th>
                            </tr>
                        </tfoot>
<?php } ?>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="service-usage" style="margin-top: 20px;">
                <h3>Service Usage Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="service-usage">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Service</label>
                        <select name="service_id">
                            <option value="all">All</option>
<?php foreach ($services_list as $srv) { ?>
                            <option value="<?php echo $srv['service_id']; ?>" <?php echo (($_GET['service_id'] ?? '') == $srv['service_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($srv['service_name']); ?></option>
<?php } ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'service-usage') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Total Usage</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_service_usage_rows && $report_service_usage_rows->num_rows > 0) { ?>
<?php while ($row = $report_service_usage_rows->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['service_name']; ?></td>
                                <td><?php echo $row['total_usage']; ?></td>
                            </tr>
<?php } ?>
<?php } else { ?>
                            <tr>
                                <td colspan="2" style="color:#6b7280;">No service usage data found</td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="clinic-activity" style="margin-top: 20px;">
                <h3>Clinic Activity Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="clinic-activity">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Clinic</label>
                        <select name="clinic_name">
                            <option value="all">All</option>
<?php foreach ($vets_list as $vet) { if (!empty($vet['clinic_name'])) { ?>
                            <option value="<?php echo htmlspecialchars($vet['clinic_name']); ?>" <?php echo (($_GET['clinic_name'] ?? '') === $vet['clinic_name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($vet['clinic_name']); ?></option>
<?php } } ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'clinic-activity') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Clinic</th>
                                <th>Total Appointments</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_clinic_activity_rows && $report_clinic_activity_rows->num_rows > 0) { ?>
<?php while ($row = $report_clinic_activity_rows->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['clinic_name'] ?: 'Unknown'; ?></td>
                                <td><?php echo $row['total_appointments']; ?></td>
                            </tr>
<?php } ?>
<?php } else { ?>
                            <tr>
                                <td colspan="2" style="color:#6b7280;">No clinic activity data found</td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="monthly-growth" style="margin-top: 20px;">
                <h3>Monthly Growth Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="monthly-growth">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="year" min="2000" max="2100" value="<?php echo htmlspecialchars($_GET['year'] ?? date('Y')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Month (optional)</label>
                        <select name="month">
                            <option value="all" <?php echo (($_GET['month'] ?? 'all') === 'all') ? 'selected' : ''; ?>>All</option>
                            <option value="1" <?php echo (($_GET['month'] ?? '') === '1') ? 'selected' : ''; ?>>January</option>
                            <option value="2" <?php echo (($_GET['month'] ?? '') === '2') ? 'selected' : ''; ?>>February</option>
                            <option value="3" <?php echo (($_GET['month'] ?? '') === '3') ? 'selected' : ''; ?>>March</option>
                            <option value="4" <?php echo (($_GET['month'] ?? '') === '4') ? 'selected' : ''; ?>>April</option>
                            <option value="5" <?php echo (($_GET['month'] ?? '') === '5') ? 'selected' : ''; ?>>May</option>
                            <option value="6" <?php echo (($_GET['month'] ?? '') === '6') ? 'selected' : ''; ?>>June</option>
                            <option value="7" <?php echo (($_GET['month'] ?? '') === '7') ? 'selected' : ''; ?>>July</option>
                            <option value="8" <?php echo (($_GET['month'] ?? '') === '8') ? 'selected' : ''; ?>>August</option>
                            <option value="9" <?php echo (($_GET['month'] ?? '') === '9') ? 'selected' : ''; ?>>September</option>
                            <option value="10" <?php echo (($_GET['month'] ?? '') === '10') ? 'selected' : ''; ?>>October</option>
                            <option value="11" <?php echo (($_GET['month'] ?? '') === '11') ? 'selected' : ''; ?>>November</option>
                            <option value="12" <?php echo (($_GET['month'] ?? '') === '12') ? 'selected' : ''; ?>>December</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Report Type</label>
                        <select name="report_type">
                            <option value="appointments" <?php echo (($_GET['report_type'] ?? 'appointments') === 'appointments') ? 'selected' : ''; ?>>Appointments</option>
                            <option value="payments" <?php echo (($_GET['report_type'] ?? '') === 'payments') ? 'selected' : ''; ?>>Payments</option>
                            <option value="users" <?php echo (($_GET['report_type'] ?? '') === 'users') ? 'selected' : ''; ?>>Users</option>
                            <option value="pets" <?php echo (($_GET['report_type'] ?? '') === 'pets') ? 'selected' : ''; ?>>Pets</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>
<?php if ($active_report === 'monthly-growth') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
<?php if (($report_type ?? '') === 'users') { ?>
                                <th>Pet Parents</th>
                                <th>Veterinarians</th>
                                <th>Total Users</th>
<?php } else { ?>
                                <th><?php echo htmlspecialchars($monthly_label ?? 'Total'); ?></th>
<?php } ?>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_monthly_growth_rows && $report_monthly_growth_rows->num_rows > 0) { ?>
<?php while ($row = $report_monthly_growth_rows->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['month']; ?></td>
<?php if (($report_type ?? '') === 'users') { ?>
                                <td><?php echo $row['pet_parents']; ?></td>
                                <td><?php echo $row['veterinarians']; ?></td>
                                <td><?php echo $row['total_users']; ?></td>
<?php } else { ?>
                                <td>
<?php if (($report_type ?? '') === 'payments') { ?>
                                    ₹<?php echo number_format((float)$row['total'], 2); ?>
<?php } else { ?>
                                    <?php echo $row['total']; ?>
<?php } ?>
                                </td>
<?php } ?>
                            </tr>
<?php } ?>
<?php } else { ?>
                            <tr>
                                <td colspan="2" style="color:#6b7280;">No monthly growth data found</td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>

            <div class="table-container report-block" data-report="user-activity" style="margin-top: 20px;">
                <h3>User Activity Report</h3>
                <form method="GET" action="admin-dashboard.php">
                    <input type="hidden" name="report" value="user-activity">
                    <?php echo $report_actions_html; ?>
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>User</label>
                        <select name="user_id">
                            <option value="all">All</option>
<?php foreach ($user_list as $u) { ?>
                            <option value="<?php echo $u['user_id']; ?>" <?php echo (($_GET['user_id'] ?? '') == $u['user_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name'] . ' (' . $u['email'] . ')'); ?></option>
<?php } ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-save" type="submit">See Data</button>
                    </div>
                </form>

<?php if ($active_report === 'user-activity') { ?>
                <div class="table-container" style="margin-top: 12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Total Appointments</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if ($report_user_activity_rows && $report_user_activity_rows->num_rows > 0) { ?>
<?php while ($row = $report_user_activity_rows->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['total_appointments']; ?></td>
                            </tr>
<?php } ?>
<?php } else { ?>
                            <tr>
                                <td colspan="3" style="color:#6b7280;">No user activity data found</td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </div>
<?php } ?>
            </div>
        </div>

    </main>

    <!-- EDIT APPOINTMENT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Appointment</h3>
                <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form class="modal-body" method="POST" action="update-appointment.php">
                <input type="hidden" name="appointment_id" id="editAppointmentId">
                <div class="form-group">
                    <label>Owner Name</label>
                    <input type="text" id="editOwner" readonly>
                </div>
                <div class="form-group">
                    <label>Pet Name</label>
                    <input type="text" id="editPet" readonly>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="appointment_date" id="editDate" required>
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="appointment_time" id="editTime" required>
                </div>
                <div class="form-group">
                    <label>Service</label>
                    <select name="service_id" id="editService" data-db="1" required>
                        <option value="">Select service</option>
<?php if (!empty($services_list)) { ?>
<?php foreach ($services_list as $srv) { ?>
                        <option value="<?php echo $srv['service_id']; ?>"><?php echo htmlspecialchars($srv['service_name']); ?></option>
<?php } ?>
<?php } else { ?>
<?php
$service_rows_fallback = $conn->query("SELECT service_id, service_name FROM services ORDER BY service_name ASC");
if ($service_rows_fallback) {
    while ($srv = $service_rows_fallback->fetch_assoc()) {
?>
                        <option value="<?php echo $srv['service_id']; ?>"><?php echo htmlspecialchars($srv['service_name']); ?></option>
<?php
    }
}
?>
<?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Veterinarian</label>
                    <select name="vet_id" id="editVet" required>
                        <option value="">Select vet</option>
<?php foreach ($vets_list as $vet) { ?>
                        <option value="<?php echo $vet['user_id']; ?>">
                            <?php echo htmlspecialchars($vet['name']); ?><?php echo !empty($vet['clinic_name']) ? ' - ' . htmlspecialchars($vet['clinic_name']) : ''; ?>
                        </option>
<?php } ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ADD APPOINTMENT MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Appointment</h3>
                <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Owner Name</label>
                    <input type="text" id="newOwner" placeholder="e.g. John Doe">
                </div>
                <div class="form-group">
                    <label>Pet Name</label>
                    <input type="text" id="newPet" placeholder="e.g. Buddy">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" id="newDate">
                </div>
                <div class="form-group">
                    <label>Service</label>
                    <select id="newService" data-db="1">
                        <option value="">Select service</option>
<?php if (!empty($services_list)) { ?>
<?php foreach ($services_list as $srv) { ?>
                        <option value="<?php echo $srv['service_id']; ?>"><?php echo htmlspecialchars($srv['service_name']); ?></option>
<?php } ?>
<?php } else { ?>
<?php
$service_rows_fallback = $conn->query("SELECT service_id, service_name FROM services ORDER BY service_name ASC");
if ($service_rows_fallback) {
    while ($srv = $service_rows_fallback->fetch_assoc()) {
?>
                        <option value="<?php echo $srv['service_id']; ?>"><?php echo htmlspecialchars($srv['service_name']); ?></option>
<?php
    }
}
?>
<?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vet</label>
                    <select id="newVet">
                        <option value="Any">Any Available Vet</option>
                        <option value="Dr. Sarah Mitchell">Dr. Sarah Mitchell</option>
                        <option value="Dr. James Carter">Dr. James Carter</option>
                        <option value="Dr. Emily Chen">Dr. Emily Chen</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="newStatus">
                        <option value="Pending" selected>Pending (Needs Approval)</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button class="btn-save" onclick="saveNewAppointment()">Add Appointment</button>
            </div>
        </div>
    </div>

    <!-- ADD SERVICE MODAL -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Service</h3>
                <span class="close-modal" onclick="closeModal('serviceModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" id="newServiceName" placeholder="e.g. Grooming">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('serviceModal')">Cancel</button>
                <button class="btn-save" onclick="saveNewService()">Add Service</button>
            </div>
        </div>
    </div>

    <!-- EDIT SERVICE MODAL -->
    <div id="serviceEditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modify Service</h3>
                <span class="close-modal" onclick="closeModal('serviceEditModal')">&times;</span>
            </div>
            <form class="modal-body" method="POST" action="update-service.php">
                <input type="hidden" name="service_id" id="editServiceId">
                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" name="service_name" id="editServiceName" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editServiceDescription" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="deleteService()">Delete</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('serviceEditModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT VET MODAL -->
    <div id="vetEditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Veterinarian</h3>
                <span class="close-modal" onclick="closeModal('vetEditModal')">&times;</span>
            </div>
            <form class="modal-body" method="POST" action="update-vet.php">
                <input type="hidden" name="user_id" id="editVetId">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="editVetName" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="editVetEmail" required>
                </div>
                <div class="form-group">
                    <label>Clinic Name</label>
                    <input type="text" name="clinic_name" id="editVetClinicName">
                </div>
                <div class="form-group">
                    <label>Clinic Address</label>
                    <input type="text" name="clinic_address" id="editVetClinicAddress">
                </div>
                <div class="form-group">
                    <label>Registration No.</label>
                    <input type="text" name="vet_registration" id="editVetRegistration">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="deleteVet()">Delete</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('vetEditModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT PET MODAL -->
    <div id="petEditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Pet</h3>
                <span class="close-modal" onclick="closeModal('petEditModal')">&times;</span>
            </div>
            <form class="modal-body" method="POST" action="update-pet-admin.php">
                <input type="hidden" name="pet_id" id="editPetId">
                <div class="form-group">
                    <label>Pet Name</label>
                    <input type="text" name="pet_name" id="editPetName" required>
                </div>
                <div class="form-group">
                    <label>Pet Type / Species</label>
                    <input type="text" name="pet_type" id="editPetType">
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="pet_age" id="editPetAge" min="0">
                </div>
                <div class="form-group">
                    <label>Breed</label>
                    <input type="text" name="breed" id="editPetBreed">
                </div>
                <div class="form-group">
                    <label>Vet Notes</label>
                    <textarea name="vet_notes" id="editPetNotes"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="deletePet()">Delete</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('petEditModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT PRESCRIPTION MODAL -->
    <div id="prescriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Prescription</h3>
                <span class="close-modal" onclick="closeModal('prescriptionModal')">&times;</span>
            </div>
            <form class="modal-body" method="POST" action="update-prescription.php">
                <input type="hidden" name="prescription_id" id="prescriptionId">
                <div class="form-group">
                    <label>Medication</label>
                    <input type="text" name="medication" id="prescriptionMedication" required>
                </div>
                <div class="form-group">
                    <label>Dosage</label>
                    <input type="text" name="dosage" id="prescriptionDosage" required>
                </div>
                <div class="form-group">
                    <label>Frequency</label>
                    <input type="text" name="frequency" id="prescriptionFrequency" required>
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" name="duration" id="prescriptionDuration" required>
                </div>
                <div class="form-group">
                    <label>Instructions</label>
                    <textarea name="instructions" id="prescriptionInstructions"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="deletePrescription()">Delete</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('prescriptionModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT FEEDBACK MODAL -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Feedback</h3>
                <span class="close-modal" onclick="closeModal('feedbackModal')">&times;</span>
            </div>
            <form class="modal-body" method="POST" action="update-feedback.php">
                <input type="hidden" name="feedback_id" id="feedbackId">
                <div class="form-group">
                    <label>Visit Type</label>
                    <input type="text" name="visit_type" id="feedbackVisitType" required>
                </div>
                <div class="form-group">
                    <label>Vet Name</label>
                    <input type="text" name="vet_name" id="feedbackVetName" required>
                </div>
                <div class="form-group">
                    <label>Visit Date</label>
                    <input type="date" name="visit_date" id="feedbackVisitDate" required>
                </div>
                <div class="form-group">
                    <label>Rating</label>
                    <input type="number" name="rating" id="feedbackRating" min="1" max="5" required>
                </div>
                <div class="form-group">
                    <label>Comments</label>
                    <textarea name="comments" id="feedbackComments" required></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="contact_ok" id="feedbackContactOk">
                        Contact OK
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="deleteFeedback()">Delete</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('feedbackModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <form id="deleteFeedbackForm" method="POST" action="delete-feedback.php" style="display:none;">
        <input type="hidden" name="feedback_id" id="deleteFeedbackId">
    </form>
    <form id="deletePrescriptionForm" method="POST" action="delete-prescription.php" style="display:none;">
        <input type="hidden" name="prescription_id" id="deletePrescriptionId">
    </form>
    <form id="deleteVetForm" method="POST" action="delete-vet.php" style="display:none;">
        <input type="hidden" name="user_id" id="deleteVetId">
    </form>
    <form id="deletePetForm" method="POST" action="delete-pet-admin.php" style="display:none;">
        <input type="hidden" name="pet_id" id="deletePetId">
    </form>

    <!-- JAVASCRIPT LOGIC (FULL SYNC ENABLED) -->
    <script>
        // --- 1. DATA SYNC (LOCAL STORAGE) ---
        const STORAGE_KEY = 'pamper_pet_data';

        // Get Data from LocalStorage or use Default
        function getDB() {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                return JSON.parse(stored);
            } else {
            }
        }



        // --- 2. RENDER FUNCTIONS ---

        function renderStats() {
            const totalEl = document.getElementById('totalAppts');
            const pendingEl = document.getElementById('pendingAppts');
            if (totalEl && totalEl.dataset.db === '1') {
                return;
            }
            const total = appointments.length;
            const pending = appointments.filter(a => a.status === 'Pending').length;
            if (totalEl) totalEl.innerText = total;
            if (pendingEl) pendingEl.innerText = pending;
        }

        function populateServiceSelects() {
            const selects = ['editService', 'newService'];
            selects.forEach(id => {
                const el = document.getElementById(id);
                if(!el) return;
                if (el.dataset && el.dataset.db === '1' && el.options.length > 1) {
                    return;
                }
                if (!Array.isArray(servicesData) || servicesData.length === 0) {
                    return;
                }
                el.innerHTML = ''; // Clear
                servicesData.forEach(srv => {
                    const opt = document.createElement('option');
                    opt.value = srv;
                    opt.innerText = srv;
                    el.appendChild(opt);
                });
            });
        }

        function renderAppointments(filterText = '') {
            // Reload data to ensure sync
            db = getDB();
            appointments = db.appointments;

            const tbody = document.getElementById('appointmentTableBody');
            const recentBody = document.getElementById('recentTableBody');
            tbody.innerHTML = '';
            if (!recentBody || recentBody.dataset.db === '1') {
                // keep server-rendered recent activity
            } else {
                recentBody.innerHTML = '';
            }

            appointments.forEach((app, index) => {
                // Filter
                if (filterText && !app.owner.toLowerCase().includes(filterText.toLowerCase()) && !app.pet.toLowerCase().includes(filterText.toLowerCase())) {
                    return;
                }

                // Status Badge
                let badgeClass = 'status-pending';
                if (app.status === 'Confirmed') badgeClass = 'status-confirmed';
                if (app.status === 'Cancelled' || app.status === 'Rejected') badgeClass = 'status-cancelled';
                if (app.status === 'Completed') badgeClass = 'status-completed';

                // Actions
                let actions = `<button class="action-btn btn-edit" onclick="openEditModal(${index})"><i class="fas fa-edit"></i></button>`;
                
                if (app.status === 'Pending') {
                    actions += `<button class="action-btn btn-approve" onclick="updateStatus(${index}, 'Confirmed')"><i class="fas fa-check"></i></button>
                               <button class="action-btn btn-reject" onclick="updateStatus(${index}, 'Cancelled')"><i class="fas fa-times"></i></button>`;
                } else if (app.status === 'Confirmed') {
                    actions += `<button class="action-btn btn-complete" onclick="updateStatus(${index}, 'Completed')"><i class="fas fa-check-double"></i></button>`;
                }

                // Table Row
                const row = `<tr>
                    <td>#${app.id}</td>
                    <td>${app.owner}</td>
                    <td>${app.pet}</td>
                    <td>${app.date}</td>
                    <td>${app.service}</td>
                    <td>${app.vet}</td>
                    <td><span class="status-badge ${badgeClass}">${app.status}</span></td>
                    <td>${actions}</td>
                </tr>`;
                tbody.innerHTML += row;

                // Recent (Top 3)
                if (recentBody && recentBody.dataset.db !== '1' && index < 3) {
                    const recentRow = `<tr>
                        <td>${app.owner}</td>
                        <td>${app.pet}</td>
                        <td>${app.service}</td>
                        <td><span class="status-badge ${badgeClass}">${app.status}</span></td>
                    </tr>`;
                    recentBody.innerHTML += recentRow;
                }
            });
        }

        function renderServices() {
            const container = document.getElementById('serviceListContainer');
            container.innerHTML = '';
            servicesData.forEach((srv, index) => {
                const div = document.createElement('div');
                div.className = 'service-item';
                div.innerHTML = `
                    <h4>${srv}</h4>
                    <button class="btn-delete-service" onclick="deleteService(${index})"><i class="fas fa-trash"></i></button>
                `;
                container.appendChild(div);
            });
        }

        function renderVets() {
            const tbody = document.getElementById('vetTableBody');
            tbody.innerHTML = '';
            vets.forEach(vet => {
                const statusColor = vet.status === 'Available' ? 'color: var(--success);' : 'color: var(--warning);';
                const row = `<tr>
                    <td><strong>${vet.name}</strong></td>
                    <td>${vet.specialty}</td>
                    <td><i class="fas fa-circle" style="font-size:0.6rem; ${statusColor}"></i> ${vet.status}</td>
                    <td>${vet.contact}</td>
                </tr>`;
                tbody.innerHTML += row;
            });
        }

        // --- 3. INTERACTION ---

        
        function filterReports() {
            const select = document.getElementById('reportFilter');
            if (!select) return;
            const value = select.value;
            const blocks = document.querySelectorAll('.report-block');
            blocks.forEach((block) => {
                if (value === 'all' || block.getAttribute('data-report') === value) {
                    block.style.display = 'block';
                } else {
                    block.style.display = 'none';
                }
            });
        }

        function filterPetsByTag(value) {
            const rows = document.querySelectorAll('#petTableBody tr[data-species]');
            let visibleCount = 0;
            rows.forEach((row) => {
                const species = (row.dataset.species || '').toLowerCase();
                const match = value === 'all' || species === value;
                row.style.display = match ? '' : 'none';
                if (match) visibleCount += 1;
            });
            const noMatch = document.getElementById('petNoMatchRow');
            if (noMatch) {
                noMatch.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        function setPetFilterTag(button, value) {
            const container = document.getElementById('petFilterTags');
            if (container) {
                container.querySelectorAll('.filter-tag').forEach(tag => tag.classList.remove('active'));
            }
            if (button) button.classList.add('active');
            filterPetsByTag(value);
        }

        function addReportDownloadButtons() {
            const blocks = document.querySelectorAll('.report-block');
            blocks.forEach((block) => {
                if (block.querySelector('.report-actions')) return;
                const header = block.querySelector('h3');
                if (!header) return;
                const actions = document.createElement('div');
                actions.className = 'report-actions';
                actions.innerHTML = `
                    <button type="button" class="btn-report" data-format="pdf">Download PDF</button>
                    <button type="button" class="btn-report" data-format="word">Download Word</button>
                    <button type="button" class="btn-report" data-format="excel">Download Excel</button>
                `;
                header.insertAdjacentElement('afterend', actions);
                actions.querySelectorAll('.btn-report').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const format = btn.dataset.format || 'pdf';
                        const form = block.querySelector('form');
                        if (!form) {
                            alert('No form found for this report.');
                            return;
                        }
                        let hidden = form.querySelector('input[name="export_format"]');
                        if (!hidden) {
                            hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'export_format';
                            form.appendChild(hidden);
                        }
                        hidden.value = format;
                        form.submit();
                    });
                });
            });
        }

        function setupPetFilter() {
            const container = document.getElementById('petFilterTags');
            if (!container) return;
            const tags = container.querySelectorAll('.filter-tag');
            tags.forEach((btn) => {
                btn.addEventListener('click', () => {
                    tags.forEach(tag => tag.classList.remove('active'));
                    btn.classList.add('active');
                    filterPetsByTag(btn.dataset.filter || 'all');
                });
            });
            const active = container.querySelector('.filter-tag.active');
            filterPetsByTag(active ? active.dataset.filter : 'all');
        }

        function setReportFilter(value) {
            const select = document.getElementById('reportFilter');
            if (select) {
                select.value = value;
            }
            filterReports();
            const items = document.querySelectorAll('.submenu-item');
            items.forEach((item) => item.classList.remove('active'));
            const active = document.querySelector(`.submenu-item[onclick*=\"${value}\"]`);
            if (active) active.classList.add('active');
        }

        function toggleReportMenu() {
            const group = document.querySelector('.menu-group');
            if (!group) return;
            group.classList.toggle('open');
        }

        const activeReport = "<?php echo htmlspecialchars($active_report ?? '', ENT_QUOTES); ?>";
        if (activeReport) {
            showView('reports');
            setReportFilter(activeReport);
        }

        const activeView = "<?php echo htmlspecialchars($_GET['view'] ?? '', ENT_QUOTES); ?>";
        if (activeView && !activeReport) {
            showView(activeView);
        }

        function showView(viewId) {
            document.querySelectorAll('.view-container').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('active'));
            const viewEl = document.getElementById(viewId);
            if (!viewEl) return;
            viewEl.classList.add('active');
            
            const menuItems = document.querySelectorAll('.menu-item');
            if (viewId === 'dashboard') menuItems[0].classList.add('active');
            if (viewId === 'appointments') menuItems[1].classList.add('active');
            if (viewId === 'vets') menuItems[2].classList.add('active');
            if (viewId === 'services') menuItems[3].classList.add('active');
            if (viewId === 'payments') menuItems[4].classList.add('active');
            if (viewId === 'prescriptions') menuItems[5].classList.add('active');
            if (viewId === 'feedback') menuItems[6].classList.add('active');
            if (viewId === 'pets') menuItems[7].classList.add('active');
            if (viewId === 'reports') menuItems[8].classList.add('active');
            if (viewId === 'reports') filterReports();
            if (viewId === 'pets') {
                const activeTag = document.querySelector('#petFilterTags .filter-tag.active');
                filterPetsByTag(activeTag ? activeTag.dataset.filter : 'all');
            }
            if (viewId === 'reports') {
                const group = document.querySelector('.menu-group');
                if (group) group.classList.add('open');
            }
        }

        function openAppointmentEdit(button) {
            document.getElementById('editAppointmentId').value = button.dataset.id || '';
            document.getElementById('editOwner').value = button.dataset.owner || '';
            document.getElementById('editPet').value = button.dataset.pet || '';
            document.getElementById('editDate').value = button.dataset.date || '';
            document.getElementById('editTime').value = button.dataset.time || '';
            document.getElementById('editService').value = button.dataset.service || '';
            document.getElementById('editVet').value = button.dataset.vet || '';
            document.getElementById('editModal').style.display = 'flex';
        }

        function openPrescriptionEdit(button) {
            document.getElementById('prescriptionId').value = button.dataset.id || '';
            document.getElementById('deletePrescriptionId').value = button.dataset.id || '';
            document.getElementById('prescriptionMedication').value = button.dataset.medication || '';
            document.getElementById('prescriptionDosage').value = button.dataset.dosage || '';
            document.getElementById('prescriptionFrequency').value = button.dataset.frequency || '';
            document.getElementById('prescriptionDuration').value = button.dataset.duration || '';
            document.getElementById('prescriptionInstructions').value = button.dataset.instructions || '';
            document.getElementById('prescriptionModal').style.display = 'flex';
        }

        function deletePrescription() {
            const id = document.getElementById('deletePrescriptionId').value;
            if (!id) return;
            if (confirm('Delete this prescription?')) {
                document.getElementById('deletePrescriptionForm').submit();
            }
        }

        function openFeedbackEdit(button) {
            document.getElementById('feedbackId').value = button.dataset.id || '';
            document.getElementById('deleteFeedbackId').value = button.dataset.id || '';
            document.getElementById('feedbackVisitType').value = button.dataset.visitType || '';
            document.getElementById('feedbackVetName').value = button.dataset.vetName || '';
            document.getElementById('feedbackVisitDate').value = button.dataset.visitDate || '';
            document.getElementById('feedbackRating').value = button.dataset.rating || '';
            document.getElementById('feedbackComments').value = button.dataset.comments || '';
            document.getElementById('feedbackContactOk').checked = button.dataset.contactOk === '1';
            document.getElementById('feedbackModal').style.display = 'flex';
        }

        function deleteFeedback() {
            const id = document.getElementById('deleteFeedbackId').value;
            if (!id) return;
            if (confirm('Delete this feedback?')) {
                document.getElementById('deleteFeedbackForm').submit();
            }
        }

        function openServiceEdit(button) {
            document.getElementById('editServiceId').value = button.dataset.id || '';
            document.getElementById('editServiceName').value = button.dataset.name || '';
            document.getElementById('editServiceDescription').value = button.dataset.description || '';
            document.getElementById('serviceEditModal').style.display = 'flex';
        }

        function deleteService() {
            const id = document.getElementById('editServiceId').value;
            if (!id) return;
            if (confirm('Delete this service?')) {
                window.location.href = `delete-service.php?id=${encodeURIComponent(id)}`;
            }
        }

        function openVetEdit(button) {
            document.getElementById('editVetId').value = button.dataset.id || '';
            document.getElementById('deleteVetId').value = button.dataset.id || '';
            document.getElementById('editVetName').value = button.dataset.name || '';
            document.getElementById('editVetEmail').value = button.dataset.email || '';
            document.getElementById('editVetClinicName').value = button.dataset.clinicName || '';
            document.getElementById('editVetClinicAddress').value = button.dataset.clinicAddress || '';
            document.getElementById('editVetRegistration').value = button.dataset.vetRegistration || '';
            document.getElementById('vetEditModal').style.display = 'flex';
        }

        function deleteVet() {
            const id = document.getElementById('deleteVetId').value;
            if (!id) return;
            if (confirm('Delete this veterinarian?')) {
                document.getElementById('deleteVetForm').submit();
            }
        }

        function openPetEdit(button) {
            document.getElementById('editPetId').value = button.dataset.id || '';
            document.getElementById('deletePetId').value = button.dataset.id || '';
            document.getElementById('editPetName').value = button.dataset.name || '';
            document.getElementById('editPetType').value = button.dataset.type || '';
            document.getElementById('editPetAge').value = button.dataset.age || '';
            document.getElementById('editPetBreed').value = button.dataset.breed || '';
            document.getElementById('editPetNotes').value = button.dataset.notes || '';
            document.getElementById('petEditModal').style.display = 'flex';
        }

        function deletePet() {
            const id = document.getElementById('deletePetId').value;
            if (!id) return;
            if (confirm('Delete this pet?')) {
                document.getElementById('deletePetForm').submit();
            }
        }

        // --- EDIT APPOINTMENT ---
        function openEditModal(index) {
            const app = appointments[index];
            document.getElementById('editIndex').value = index;
            document.getElementById('editOwner').value = app.owner;
            document.getElementById('editPet').value = app.pet;
            document.getElementById('editDate').value = app.date;
            document.getElementById('editService').value = app.service;
            document.getElementById('editVet').value = app.vet;
            
            document.getElementById('editModal').style.display = 'flex';
        }

        function saveChanges() {
            const idx = document.getElementById('editIndex').value;
            db.appointments[idx].owner = document.getElementById('editOwner').value;
            db.appointments[idx].pet = document.getElementById('editPet').value;
            db.appointments[idx].date = document.getElementById('editDate').value;
            db.appointments[idx].service = document.getElementById('editService').value;
            db.appointments[idx].vet = document.getElementById('editVet').value;
            
            saveDB(db);
            closeModal('editModal');
        }

        // --- ADD APPOINTMENT ---
        function openAddModal() {
            document.getElementById('newOwner').value = '';
            document.getElementById('newPet').value = '';
            document.getElementById('newDate').value = '';
            document.getElementById('addModal').style.display = 'flex';
        }

        function saveNewAppointment() {
            const newId = appointments.length > 0 ? appointments[appointments.length - 1].id + 1 : 101;
            const newApp = {
                id: newId,
                owner: document.getElementById('newOwner').value,
                pet: document.getElementById('newPet').value,
                date: document.getElementById('newDate').value,
                service: document.getElementById('newService').value,
                vet: document.getElementById('newVet').value,
                status: document.getElementById('newStatus').value,
                notes: ""
            };

            db.appointments.push(newApp);
            saveDB(db);
            closeModal('addModal');
        }

        // --- SERVICE MANAGEMENT ---
        function openServiceModal() {
            document.getElementById('newServiceName').value = '';
            document.getElementById('serviceModal').style.display = 'flex';
        }

        function saveNewService() {
            const name = document.getElementById('newServiceName').value;
            if(name) {
                db.services.push(name);
                saveDB(db);
                closeModal('serviceModal');
            }
        }

        function deleteService(index) {
            if(confirm('Remove this service?')) {
                db.services.splice(index, 1);
                saveDB(db);
            }
        }

        // --- STATUS UPDATES ---
        function updateStatus(index, status) {
            db.appointments[index].status = status;
            saveDB(db);
        }

        function filterAppointments() {
            const text = document.getElementById('globalSearch').value;
            renderAppointments(text);
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }

        // --- INIT ---
        document.addEventListener('DOMContentLoaded', () => {
            populateServiceSelects();
            renderStats();
            renderAppointments();
            renderServices();
            renderVets();
            setupPetFilter();
            addReportDownloadButtons();
            
            // Auto-refresh every 5 seconds
            setInterval(() => {
                renderAppointments(document.getElementById('globalSearch').value);
                renderStats();
            }, 5000);
        });

    </script>
</body>
</html>

