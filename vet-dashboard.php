<?php
session_start();
require "db.php";

$tz = $conn->query("SELECT @@session.time_zone AS tz");
if ($tz && $tz_row = $tz->fetch_assoc()) {
    if ($tz_row['tz'] === 'SYSTEM') {
        date_default_timezone_set('Asia/Kolkata');
    }
} else {
    date_default_timezone_set('Asia/Kolkata');
}

$vet_id = $_SESSION['user_id'];

$vet_query = $conn->query("SELECT * FROM users WHERE user_id='$vet_id'");
$vet_data = $vet_query->fetch_assoc();

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

$sql = "SELECT 
appointments.*,
pets.pet_id,
pets.pet_name,
COALESCE(pet_users.name, owner_users.name) AS owner_name,
services.service_name
FROM appointments
JOIN pets ON appointments.pet_id = pets.pet_id
LEFT JOIN users owner_users ON appointments.user_id = owner_users.user_id
LEFT JOIN users pet_users ON pets.user_id = pet_users.user_id
LEFT JOIN services ON appointments.service_id = services.service_id
WHERE appointments.vet_id='$vet_id'";

$result = $conn->query($sql);
$has_notes_updated = false;
$col_check = $conn->query("SHOW COLUMNS FROM pets LIKE 'vet_notes_updated_at'");
if ($col_check && $col_check->num_rows > 0) {
    $has_notes_updated = true;
}

$records_sql = "
SELECT DISTINCT
pets.pet_id,
pets.pet_name,
pets.breed,
pets.vet_notes," .
($has_notes_updated ? "\n" . "pets.vet_notes_updated_at," : "") . "
users.name AS owner_name
FROM appointments
JOIN pets ON appointments.pet_id = pets.pet_id
JOIN users ON pets.user_id = users.user_id
WHERE appointments.vet_id = '{$vet_id}'
AND appointments.status = 'completed'
";

$records_result = $conn->query($records_sql);

$pets_list = $conn->query("
    SELECT DISTINCT pets.pet_id, pets.pet_name, users.name AS owner_name
    FROM appointments
    JOIN pets ON appointments.pet_id = pets.pet_id
    LEFT JOIN users ON pets.user_id = users.user_id
    WHERE appointments.vet_id = '{$vet_id}'
      AND appointments.status <> 'cancelled'
    ORDER BY pets.pet_name
");

$prescriptions_sql = "
SELECT
    prescriptions.prescription_id,
    prescriptions.medication,
    prescriptions.dosage,
    prescriptions.frequency,
    prescriptions.duration,
    prescriptions.instructions,
    prescriptions.created_at,
    pets.pet_name,
    users.name AS owner_name
FROM prescriptions
LEFT JOIN pets ON prescriptions.pet_id = pets.pet_id
LEFT JOIN users ON pets.user_id = users.user_id
WHERE prescriptions.vet_id = '{$vet_id}'
ORDER BY prescriptions.created_at DESC
";
$prescriptions_result = $conn->query($prescriptions_sql);

$history_sql = "
SELECT
appointments.appointment_date,
appointments.service_id,
services.service_name,
pets.pet_name,
pets.breed,
users.name AS owner_name
FROM appointments
JOIN pets ON appointments.pet_id = pets.pet_id
JOIN users ON pets.user_id = users.user_id
LEFT JOIN services ON appointments.service_id = services.service_id
WHERE appointments.vet_id='$vet_id'
AND appointments.status='completed'
ORDER BY appointments.appointment_date DESC
";

$history_result = $conn->query($history_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAMPER YOUR PET - Vet Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --brand-900: #0f2b46;
            --brand-700: #1f4d78;
            --brand-500: #2f7ec7;
            --accent-400: #f2b48d;
            --accent-100: #fff3e9;
            --surface: #ffffff;
            --surface-alt: #f7f9fc;
            --border: #e2e8f0;
            --ink-900: #0f172a;
            --ink-700: #1f2937;
            --ink-600: #475569;
            --ink-500: #64748b;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.12);
            --shadow-card: 0 10px 25px rgba(15, 23, 42, 0.08);
            --radius-lg: 22px;
            --radius-md: 16px;
            --radius-sm: 12px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }

        body {
            display: flex;
            height: 100vh;
            background: radial-gradient(circle at 15% 20%, #eff6ff 0%, #f8fafc 45%, #ffffff 100%);
            overflow: hidden;
            color: var(--ink-900);
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #0b1c2e;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand {
            padding: 22px 24px;
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .brand i {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand-500), var(--accent-400));
            display: grid;
            place-items: center;
            color: #ffffff;
            font-size: 1rem;
        }

        .menu { flex: 1; padding-top: 10px; overflow-y: auto; }

        .menu-item {
            padding: 14px 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: 0.2s ease;
            color: #cbd5f5;
            text-decoration: none;
            border-left: 3px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(47, 126, 199, 0.18);
            color: #ffffff;
            border-left-color: var(--accent-400);
        }

        .logout { padding: 16px 22px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn {
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid var(--danger);
            color: var(--danger);
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s;
        }
        .logout-btn:hover { background: var(--danger); color: #ffffff; }

        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

        .top-bar {
            height: 70px;
            background: var(--surface);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 28px;
            border-bottom: 1px solid var(--border);
        }

        .search-bar {
            background: var(--surface-alt);
            padding: 8px 14px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            width: 320px;
            border: 1px solid var(--border);
        }

        .search-bar input {
            border: none;
            background: transparent;
            margin-left: 8px;
            outline: none;
            width: 100%;
        }

        .user-info { display: flex; align-items: center; gap: 12px; }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand-500), var(--accent-400));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* Views */
        .view-container {
            padding: 26px;
            overflow-y: auto;
            flex: 1;
            display: none;
        }

        .view-container.active { display: block; animation: fadeIn 0.3s ease; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        h2 {
            margin-bottom: 18px;
            color: var(--ink-900);
            font-size: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-primary {
            background: var(--brand-700);
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Dashboard Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 24px; }
        .stat-card { background: var(--surface); padding: 20px; border-radius: var(--radius-md); box-shadow: var(--shadow-card); display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border); }
        .stat-info h3 { font-size: 2rem; color: var(--ink-900); margin-bottom: 4px; }
        .stat-info p { color: var(--ink-500); font-size: 0.9rem; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .bg-blue { background: #e0f2fe; color: var(--brand-700); }
        .bg-green { background: #dcfce7; color: var(--success); }
        .bg-orange { background: #ffedd5; color: var(--warning); }

        /* Tables */
        .table-container { background: var(--surface); padding: 18px; border-radius: var(--radius-md); box-shadow: var(--shadow-card); overflow-x: auto; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #f3f4f6; font-size: 0.92rem; }
        th { color: var(--ink-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.04em; }
        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .table-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--ink-900);
        }
        .table-subtitle {
            color: var(--ink-500);
            font-size: 0.9rem;
        }
        .notes-area {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px;
            font-family: 'DM Sans', sans-serif;
            background: var(--surface-alt);
            min-height: 90px;
            resize: vertical;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            background: rgba(47, 126, 199, 0.12);
            color: var(--brand-700);
            font-weight: 600;
        }

        .status-badge { padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-confirmed { background: #d1fae5; color: #059669; }
        .status-completed { background: #e5e7eb; color: #4b5563; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        .action-btn { border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 0.8rem; margin-right: 6px; transition: 0.2s; color: white; text-decoration: none; display: inline-block; }
        .btn-complete { background: var(--success); }
        .btn-accept { background: var(--brand-700); }
        .btn-reject { background: var(--danger); }
        .btn-notes { background: #64748b; }
        .action-btn:hover { opacity: 0.9; }

        /* Modals */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: var(--surface); padding: 24px; border-radius: var(--radius-md); width: 90%; max-width: 520px; box-shadow: var(--shadow-soft); animation: fadeIn 0.3s; }

        input[type="date"] {
            padding-left: 38px;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='%236b7280' stroke-width='1.8' viewBox='0 0 24 24'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 12px center;
            background-size: 18px 18px;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .close-modal { cursor: pointer; font-size: 1.2rem; color: #999; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 10px; font-family: 'DM Sans', sans-serif; background: var(--surface-alt); }

        .modal-footer { text-align: right; margin-top: 16px; }
        .btn-save { background: var(--brand-700); color: #ffffff; padding: 10px 18px; border: none; border-radius: 10px; cursor: pointer; margin-right: 10px; }
        .btn-cancel { background: #e5e7eb; color: #333; padding: 10px 18px; border: none; border-radius: 10px; cursor: pointer; }

        .next-patient-card {
            background: linear-gradient(135deg, rgba(31, 77, 120, 0.95), rgba(47, 126, 199, 0.85));
            color: #ffffff;
            padding: 22px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .np-info h3 { font-size: 1.2rem; margin-bottom: 4px; }
        .np-info p { opacity: 0.9; }
        .np-time { background: rgba(255,255,255,0.2); padding: 10px 18px; border-radius: 10px; text-align: center; }
        .np-time span { display: block; font-size: 0.8rem; opacity: 0.8; }
        .np-time strong { font-size: 1.1rem; }

        @media (max-width: 980px) {
            .sidebar { width: 220px; }
            .search-bar { width: 220px; }
        }

        @media (max-width: 840px) {
            body { flex-direction: column; height: auto; }
            .sidebar { width: 100%; flex-direction: row; overflow-x: auto; }
            .menu { display: flex; padding: 0; }
            .menu-item { border-left: none; border-bottom: 3px solid transparent; }
            .menu-item.active { border-bottom-color: var(--accent-400); }
            .main-content { height: auto; }
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
    <link rel="stylesheet" href="assets/css/decor.css">
</head>
<body class="has-sidebar">

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-paw"></i>
            <span>Dr. <?php echo $vet_data['name']; ?></span>
        </div>
        <div class="menu">
            <a href="#" class="menu-item active" onclick="showView('dashboard')">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="#" class="menu-item" onclick="showView('schedule')">
                <i class="fas fa-calendar-alt"></i> My Schedule
            </a>
            <a href="#" class="menu-item" onclick="showView('patients')">
                <i class="fas fa-user-injured"></i> Patient History
            </a>
            <a href="#" class="menu-item" onclick="showView('petrecords')">
                <i class="fas fa-notes-medical"></i> Pet Records
            </a>
            <a href="#" class="menu-item" onclick="showView('prescriptions')">
                <i class="fas fa-prescription-bottle-medical"></i> Prescriptions
            </a>
        </div>
        <div class="logout">
            <button class="logout-btn" onclick="window.location.href='logout.php'">
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
                <input type="text" id="globalSearch" placeholder="Search patients..." onkeyup="filterAppointments()">
            </div>
            <div class="user-info">
                <div style="text-align: right; margin-right: 10px;">
                    <h4 style="font-size:0.9rem;">Dr. <?php echo $vet_data['name']; ?></h4>
                    <span style="font-size: 0.75rem; color: #6b7280;">General Practitioner</span>
                </div>
                <div class="avatar">
                    <?php echo strtoupper(substr($vet_data['name'],0,1)); ?>
                </div>
            </div>
        </div>

        <!-- VIEW 1: DASHBOARD OVERVIEW -->
        <div id="dashboard" class="view-container active">
            <h2>Dashboard Overview</h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <?php
                        $today_sql = "SELECT COUNT(*) AS total 
                                      FROM appointments 
                                      WHERE vet_id='$vet_id'
                                      AND DATE(appointment_date) = CURDATE()
                                      AND status <> 'cancelled'";

                        $today_result = $conn->query($today_sql);
                        $today_data = $today_result ? $today_result->fetch_assoc() : ['total' => 0];
                        ?>
                        <h3><?php echo $today_data['total']; ?></h3>
                        <p>Appointments Today</p>
                    </div>
                    <div class="stat-icon bg-blue"><i class="fas fa-calendar-day"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <?php
                        $complete_sql = "SELECT COUNT(*) AS total
                                         FROM appointments
                                         WHERE vet_id='$vet_id'
                                         AND status='completed'";

                        $complete_result = $conn->query($complete_sql);
                        $complete_data = $complete_result ? $complete_result->fetch_assoc() : ['total' => 0];
                        ?>
                        <h3><?php echo $complete_data['total']; ?></h3>
                        <p>Completed Today</p>
                    </div>
                    <div class="stat-icon bg-green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <?php
                        $count_sql = "SELECT COUNT(DISTINCT appointments.pet_id) AS total
                                      FROM appointments
                                      WHERE vet_id='$vet_id'";
                        $count_result = $conn->query($count_sql);
                        $count_data = $count_result ? $count_result->fetch_assoc() : ['total' => 0];
                        ?>
                        <h3><?php echo $count_data['total']; ?></h3>
                        <p>Total Patients</p>
                    </div>
                    <div class="stat-icon bg-orange"><i class="fas fa-users"></i></div>
                </div>
            </div>

            <div class="table-container">
                <h3>Today's Schedule</h3>
                <table id="recentTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Pet Name</th>
                            <th>Owner</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

<?php
while($row = $result->fetch_assoc()){
?>

<tr>
<td><?php echo $row['appointment_date']; ?></td>

<td><?php echo $row['appointment_time']; ?></td>

<td>
<strong><?php echo $row['pet_name']; ?></strong>
</td>

<td>
<?php echo $row['owner_name']; ?>
</td>

<td>
<?php
$svc = $row['service_name'] ?? '';
if ($svc === '' || $svc === null) {
    $service = $row['service_id'];
    if($service == 1){
        $svc = "General Care";
    }
    elseif($service == 2){
        $svc = "Vaccination";
    }
    elseif($service == 3){
        $svc = "Diagnostics";
    }
    else{
        $svc = "Unknown";
    }
}
echo $svc;
?>
</td>

<td>
<?php
$status_value = trim($row['status'] ?? '');
if ($status_value === '') {
    $status_value = 'pending';
}
$status_label = ($status_value === 'cancelled') ? 'rejected' : $status_value;
?>
<span class="status-badge status-<?php echo $status_label; ?>">
<?php echo $status_label; ?>
</span>
</td>

<td>
<?php
$row_status = trim($row['status'] ?? '');
if ($row_status === '') {
    $row_status = 'pending';
}
if ($row_status !== 'completed') { ?>
<a href="accept-appointment.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn btn-accept">Approve</a>
<a href="reject-appointment.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn btn-reject">Reject</a>
<button class="action-btn btn-notes"
onclick="openNotesModal('<?php echo $row['pet_id']; ?>','<?php echo $row['pet_name']; ?>')">
Notes
</button>
<?php } else { ?>
-
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

        <!-- VIEW 2: MY SCHEDULE (Full List) -->
        <div id="schedule" class="view-container">
            <h2>
                My Schedule 
                <button class="btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> New Appointment
                </button>
            </h2>
            <div class="table-container">
                <table id="appointmentTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Pet Name</th>
                            <th>Owner</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="appointmentTableBody">
<?php
$schedule_result = $conn->query($sql);
while($row = $schedule_result->fetch_assoc()){
?>
<tr>
<td><?php echo $row['appointment_date']; ?></td>
<td><?php echo $row['appointment_time']; ?></td>
<td><strong><?php echo $row['pet_name']; ?></strong></td>
<td><?php echo $row['owner_name']; ?></td>
<td>
<?php
$svc = $row['service_name'] ?? '';
if ($svc === '' || $svc === null) {
    $service = $row['service_id'];
    if($service == 1){
        $svc = "General Care";
    }
    elseif($service == 2){
        $svc = "Vaccination";
    }
    elseif($service == 3){
        $svc = "Diagnostics";
    }
    else{
        $svc = "Unknown";
    }
}
echo $svc;
?>
</td>
<td>
<?php
$status_value = trim($row['status'] ?? '');
if ($status_value === '') {
    $status_value = 'pending';
}
$status_label = ($status_value === 'cancelled') ? 'rejected' : $status_value;
?>
<span class="status-badge status-<?php echo $status_label; ?>">
<?php echo $status_label; ?>
</span>
</td>
<td>
<?php if($row['status'] == "confirmed"){ ?>
<a href="complete-appointment.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn btn-complete">Complete</a>
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

        <!-- VIEW 3: PATIENT HISTORY -->
        <div id="patients" class="view-container">
            <h2>Patient History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Pet Name</th>
                            <th>Owner</th>
                            <th>Breed</th>
                            <th>Last Visit</th>
                            <th>Condition</th>
                        </tr>
                   </thead>
                   <tbody>
<?php if($history_result->num_rows > 0){ ?>
<?php while($hist = $history_result->fetch_assoc()){ ?>
<tr>
<td><?php echo $hist['pet_name']; ?></td>
<td><?php echo $hist['owner_name']; ?></td>
<td><?php echo $hist['breed']; ?></td>
<td><?php echo $hist['appointment_date']; ?></td>
<td>
<?php
$svc = $hist['service_name'] ?? '';
if ($svc === '' || $svc === null) {
    $service = $hist['service_id'];
    if($service == 1){
        $svc = "General Care";
    }
    elseif($service == 2){
        $svc = "Vaccination";
    }
    elseif($service == 3){
        $svc = "Diagnostics";
    }
    else{
        $svc = "Unknown";
    }
}
echo $svc;
?>
</td>
</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="4" style="color:#6b7280;">No completed appointments yet</td>
</tr>
<?php } ?>
</tbody>
                </table>
            </div>
</div>
            <div id="petrecords" class="view-container">
                <div class="table-container">
                    <div class="table-header">
                        <div>
                            <div class="table-title">Pet Records</div>
                            <div class="table-subtitle">Review patient history, search, and keep notes updated.</div>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="petRecordSearch" placeholder="Search pet, owner, or breed..." style="padding: 8px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-alt);">
                            <span class="badge"><i class="fa-solid fa-notes-medical"></i> Active Records</span>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Pet Name</th>
                                <th>Owner</th>
                                <th>Breed</th>
                                <th>Vet Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($record = $records_result->fetch_assoc()){ ?>
                            <tr data-pet="<?php echo strtolower($record['pet_name']); ?>" data-owner="<?php echo strtolower($record['owner_name']); ?>" data-breed="<?php echo strtolower($record['breed']); ?>">
                                <td><strong><?php echo $record['pet_name']; ?></strong></td>
                                <td><?php echo $record['owner_name']; ?></td>
                                <td><?php echo $record['breed']; ?></td>
                                <td>
                                    <form method="POST" action="save-vet-notes.php">
                                        <input type="hidden" name="pet_id" value="<?php echo $record['pet_id']; ?>">
                                        <textarea name="vet_notes" class="notes-area" data-pet-id="<?php echo $record['pet_id']; ?>"><?php echo $record['vet_notes']; ?></textarea>
                                        <div style="display:flex; justify-content: space-between; align-items: center; margin-top:8px;">
                                            <small class="table-subtitle" data-updated="<?php echo $record['pet_id']; ?>">
                                                Last updated: <?php echo !empty($record['vet_notes_updated_at']) ? date('M d, Y g:i A', strtotime($record['vet_notes_updated_at'])) : 'Not yet'; ?>
                                            </small>
                                            <button type="submit" class="action-btn btn-notes">Save Notes</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="prescriptions" class="view-container">
        <h2>Pet Prescriptions</h2>
        <div class="table-container" style="margin-bottom: 20px;">
            <div class="table-header">
                <div>
                    <div class="table-title">Create Prescription</div>
                    <div class="table-subtitle">Add medication details for a patient.</div>
                </div>
            </div>
            <?php if (isset($_GET['prescribed']) && $_GET['prescribed'] === '1'): ?>
                <div class="badge" style="margin-bottom: 12px;">
                    <i class="fa-solid fa-check"></i> Prescription saved.
                </div>
            <?php endif; ?>
            <form method="POST" action="save-prescription.php">
                <div class="form-group">
                    <label>Pet</label>
                    <select name="pet_id" id="prescriptionPet" required>
                        <option value="">Select a pet</option>
                        <?php if ($pets_list && $pets_list->num_rows > 0): ?>
                            <?php while($pet = $pets_list->fetch_assoc()): ?>
                                <option value="<?php echo $pet['pet_id']; ?>" data-owner="<?php echo htmlspecialchars($pet['owner_name'] ?? '', ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($pet['pet_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pet Owner</label>
                    <input type="text" id="prescriptionOwner" name="owner_name" placeholder="Auto-filled" readonly>
                </div>
                <div class="form-group">
                    <label>Medication</label>
                    <input type="text" name="medication" placeholder="e.g. Amoxicillin" required>
                </div>
                <div class="form-group">
                    <label>Dosage</label>
                    <input type="text" name="dosage" placeholder="e.g. 5 mg/kg" required>
                </div>
                <div class="form-group">
                    <label>Frequency</label>
                    <input type="text" name="frequency" placeholder="e.g. Twice daily" required>
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" name="duration" placeholder="e.g. 7 days" required>
                </div>
                <div class="form-group">
                    <label>Instructions</label>
                    <textarea name="instructions" rows="3" placeholder="Additional instructions..."></textarea>
                </div>
                <div class="modal-footer" style="padding: 0;">
                    <button type="submit" class="btn-save">Save Prescription</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <div>
                    <div class="table-title">Recent Prescriptions</div>
                    <div class="table-subtitle">Latest prescriptions for your patients.</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Pet</th>
                        <th>Owner</th>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($prescriptions_result && $prescriptions_result->num_rows > 0): ?>
                    <?php while($rx = $prescriptions_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rx['pet_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($rx['owner_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($rx['medication']); ?></td>
                            <td><?php echo htmlspecialchars($rx['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($rx['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($rx['duration']); ?></td>
                            <td><?php echo !empty($rx['created_at']) ? date('M d, Y', strtotime($rx['created_at'])) : ''; ?></td>
<td>
    <button
        class="action-btn btn-notes"
        onclick="printPrescription(this)"
        data-pet="<?php echo htmlspecialchars($rx['pet_name'] ?? 'Unknown'); ?>"
        data-owner="<?php echo htmlspecialchars($rx['owner_name'] ?? ''); ?>"
        data-medication="<?php echo htmlspecialchars($rx['medication']); ?>"
        data-dosage="<?php echo htmlspecialchars($rx['dosage']); ?>"
        data-frequency="<?php echo htmlspecialchars($rx['frequency']); ?>"
        data-duration="<?php echo htmlspecialchars($rx['duration']); ?>"
        data-date="<?php echo !empty($rx['created_at']) ? date('M d, Y', strtotime($rx['created_at'])) : ''; ?>"
        data-instructions="<?php echo htmlspecialchars($rx['instructions'] ?? '-'); ?>"
    >
        <i class="fas fa-print"></i> Print/PDF
    </button>
</td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="color:#6b7280;">No prescriptions yet</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    </main>

    <!-- ADD APPOINTMENT MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Book New Appointment</h3>
                <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Pet Name</label>
                    <input type="text" id="newPet" placeholder="e.g. Buddy">
                </div>
                <div class="form-group">
                    <label>Owner Name</label>
                    <input type="text" id="newOwner" placeholder="e.g. John Doe">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" id="newDate">
                </div>
                <div class="form-group">
                    <label>Service</label>
                    <select id="newService">
                        <!-- Populated by JS -->
                    </select>
                </div>
                <div class="form-group">
                    <label>Veterinarian</label>
                    <input type="text" value="Dr. <?php echo $vet_data['name']; ?>" disabled style="background-color: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Initial Notes</label>
                    <textarea id="newNotes" rows="3" placeholder="Reason for visit..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button class="btn-save" onclick="saveNewAppointment()">Book Appointment</button>
            </div>
        </div>
    </div>

    <!-- NOTES MODAL -->
    <div id="notesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Patient Notes</h3>
                <span class="close-modal" onclick="closeModal('notesModal')">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="petId">
                <div class="form-group">
                    <label>Patient: <span id="notePatientName" style="font-weight: 400; color: var(--brand-700);"></span></label>
                    <textarea id="noteText" rows="5" placeholder="Enter diagnosis or treatment notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('notesModal')">Cancel</button>
                <button class="btn-save" onclick="saveNotes()">Save Notes</button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC (DATABASE-BACKED) -->
    <script>
        function showView(viewId) {
            document.querySelectorAll('.view-container').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('active'));

            document.getElementById(viewId).classList.add('active');

            const menuItems = document.querySelectorAll('.menu-item');

            if(viewId === 'dashboard') menuItems[0].classList.add('active');
            if(viewId === 'schedule') menuItems[1].classList.add('active');
            if(viewId === 'patients') menuItems[2].classList.add('active');
            if(viewId === 'petrecords') menuItems[3].classList.add('active');
            if(viewId === 'prescriptions') menuItems[4].classList.add('active');
        }

        function openAddModal() {
            document.getElementById('newPet').value = '';
            document.getElementById('newOwner').value = '';
            document.getElementById('newDate').value = '';
            document.getElementById('newNotes').value = '';
            document.getElementById('addModal').style.display = 'flex';
        }

        function saveNewAppointment() {
            closeModal('addModal');
        }

        function openNotesModal(petId, petName){
            document.getElementById("petId").value = petId;
            document.getElementById("notePatientName").innerText = petName;
            document.getElementById("notesModal").style.display = "flex";
        }

        function saveNotes(){
            let petId = document.getElementById("petId").value;
            let notes = document.getElementById("noteText").value;

            fetch("save-vet-notes.php",{
                method:"POST",
                headers:{
                    "Content-Type":"application/x-www-form-urlencoded"
                },
                body:"pet_id="+petId+"&vet_notes="+notes
            })
            .then(()=>{
                alert("Notes saved");
                closeModal("notesModal");
            });
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function filterAppointments() {
            const text = document.getElementById('globalSearch').value.toLowerCase();
            const filterRows = (tbody, petIndex, ownerIndex) => {
                if (!tbody) return;
                Array.from(tbody.querySelectorAll('tr')).forEach(row => {
                    const pet = row.children[petIndex] ? row.children[petIndex].innerText.toLowerCase() : '';
                    const owner = row.children[ownerIndex] ? row.children[ownerIndex].innerText.toLowerCase() : '';
                    row.style.display = (!text || pet.includes(text) || owner.includes(text)) ? '' : 'none';
                });
            };

            const scheduleBody = document.getElementById('appointmentTableBody');
            filterRows(scheduleBody, 2, 3);

            const todayBody = document.querySelector('#recentTable tbody');
            filterRows(todayBody, 1, 2);

            const petRows = document.querySelectorAll('#petrecords tbody tr');
            petRows.forEach(row => {
                const pet = row.dataset.pet || '';
                const owner = row.dataset.owner || '';
                const breed = row.dataset.breed || '';
                row.style.display = (!text || pet.includes(text) || owner.includes(text) || breed.includes(text)) ? '' : 'none';
            });
        }

        const petSearch = document.getElementById('petRecordSearch');
        if (petSearch) {
            petSearch.addEventListener('input', () => {
                const q = petSearch.value.toLowerCase();
                document.querySelectorAll('#petrecords tbody tr').forEach(row => {
                    const pet = row.dataset.pet || '';
                    const owner = row.dataset.owner || '';
                    const breed = row.dataset.breed || '';
                    row.style.display = (!q || pet.includes(q) || owner.includes(q) || breed.includes(q)) ? '' : 'none';
                });
            });
        }

        let autosaveTimer = null;
        document.querySelectorAll('.notes-area').forEach(area => {
            area.addEventListener('input', () => {
                if (autosaveTimer) clearTimeout(autosaveTimer);
                autosaveTimer = setTimeout(() => {
                    const petId = area.dataset.petId;
                    const notes = area.value;
                    const formData = new URLSearchParams();
                    formData.append('pet_id', petId);
                    formData.append('vet_notes', notes);
                    formData.append('ajax', '1');

                    fetch('save-vet-notes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    }).then(() => {
                        const updatedEl = document.querySelector(`[data-updated='${petId}']`);
                        if (updatedEl) {
                            const now = new Date();
                            const options = { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit' };
                            updatedEl.textContent = `Last updated: ${now.toLocaleString('en-US', options)}`;
                        }
                    });
                }, 800);
            });
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }

        const setMaxToday = (input) => {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            input.max = `${yyyy}-${mm}-${dd}`;
        };

        document.querySelectorAll('input[type="date"]').forEach(input => {
            setMaxToday(input);
            input.addEventListener('click', () => {
                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                }
            });
        });

        function printPrescription(button) {
            const data = button.dataset;
            const win = window.open('', '_blank', 'width=900,height=700');
            if (!win) return;
            win.document.write(`
                <html>
                <head>
                    <title>Prescription</title>
                    <style>
                        @page { margin: 18mm; }
                        * { box-sizing: border-box; }
                        body { font-family: 'DM Sans', Arial, sans-serif; color: #0f172a; background: #ffffff; }
                        .header { display:flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 18px; }
                        .logo { display:flex; align-items:center; gap: 10px; }
                        .logo-mark { width: 44px; height: 44px; }
                        .brand { font-size: 1.25rem; font-weight: 700; letter-spacing: 0.4px; }
                        .brand-sub { font-size: 0.85rem; color: #64748b; }
                        .meta { text-align:right; font-size: 0.9rem; color: #475569; }
                        .title { font-size: 1.1rem; font-weight: 700; margin: 6px 0 2px; }
                        .card { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
                        .section { padding: 14px 16px; }
                        .section + .section { border-top: 1px solid #e2e8f0; }
                        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; }
                        .label { font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
                        .value { font-weight: 600; margin-top: 2px; }
                        .instructions { background: #f8fafc; border: 1px dashed #cbd5f5; padding: 12px; border-radius: 10px; min-height: 64px; }
                        .footer { margin-top: 16px; display:flex; justify-content: space-between; align-items: flex-end; color: #64748b; font-size: 0.8rem; }
                        .sig { width: 220px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 6px; }
                        .watermark { position: fixed; top: 45%; left: 50%; transform: translate(-50%, -50%); font-size: 4.5rem; font-weight: 700; color: rgba(15,23,42,0.04); pointer-events: none; }
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
                    <div class="watermark">PAMPER YOUR PET</div>
                    <div class="header">
                        <div class="logo">
                            <div class="logo-mark">
                                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0" stop-color="#2f7ec7" />
                                            <stop offset="1" stop-color="#f2b48d" />
                                        </linearGradient>
                                    </defs>
                                    <circle cx="32" cy="32" r="28" fill="url(#g)" />
                                    <path d="M23 36c4 0 5-4 9-4s5 4 9 4c4 0 7-3 7-7 0-4-3-8-7-8-3 0-6 2-9 4-3-2-6-4-9-4-4 0-7 4-7 8 0 4 3 7 7 7z" fill="#fff" opacity="0.9"/>
                                    <circle cx="22" cy="22" r="5" fill="#fff" />
                                    <circle cx="42" cy="22" r="5" fill="#fff" />
                                </svg>
                            </div>
                            <div>
                                <div class="brand">Pamper Your Pet</div>
                                <div class="brand-sub">Veterinary Care & Wellness</div>
                            </div>
                        </div>
                        <div class="meta">
                            <div class="title">Prescription</div>
                            <div>Date: ${data.date || '-'}</div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="section">
                            <div class="grid">
                                <div>
                                    <div class="label">Patient</div>
                                    <div class="value">${data.pet || '-'}</div>
                                </div>
                                <div>
                                    <div class="label">Owner</div>
                                    <div class="value">${data.owner || '-'}</div>
                                </div>
                            </div>
                        </div>
                        <div class="section">
                            <div class="grid">
                                <div>
                                    <div class="label">Medication</div>
                                    <div class="value">${data.medication || '-'}</div>
                                </div>
                                <div>
                                    <div class="label">Dosage</div>
                                    <div class="value">${data.dosage || '-'}</div>
                                </div>
                                <div>
                                    <div class="label">Frequency</div>
                                    <div class="value">${data.frequency || '-'}</div>
                                </div>
                                <div>
                                    <div class="label">Duration</div>
                                    <div class="value">${data.duration || '-'}</div>
                                </div>
                            </div>
                        </div>
                        <div class="section">
                            <div class="label" style="margin-bottom:6px;">Instructions</div>
                            <div class="instructions">${data.instructions || '-'}</div>
                        </div>
                    </div>

                    <div class="footer">
                        <div>Provide medications exactly as prescribed.</div>
                        <div class="sig">Veterinarian Signature</div>
                    </div>
                    <script>
                        window.onload = () => window.print();
                    <\/script>
                </body>
                </html>
            `);
            win.document.close();
        }
        const prescriptionPet = document.getElementById('prescriptionPet');
        const prescriptionOwner = document.getElementById('prescriptionOwner');

        function updatePrescriptionOwner() {
            if (!prescriptionPet || !prescriptionOwner) return;
            const option = prescriptionPet.options[prescriptionPet.selectedIndex];
            const owner = option && option.dataset ? option.dataset.owner || "" : "";
            prescriptionOwner.value = owner;
        }

        if (prescriptionPet) {
            prescriptionPet.addEventListener('change', updatePrescriptionOwner);
            updatePrescriptionOwner();
        }

const params = new URLSearchParams(window.location.search);
        const initialView = params.get('view');
        if (initialView) {
            showView(initialView);
        }
    </script>
</body>
</html>

