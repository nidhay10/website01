<?php
session_start();
require "db.php";

$user_id = $_SESSION['user_id'];

$user_query = $conn->query("SELECT name FROM users WHERE user_id='$user_id'");
$user = $user_query->fetch_assoc();
$username = $user['name'];

/* Count pets */
$pets_count_query = $conn->query("SELECT COUNT(*) AS total FROM pets WHERE user_id='$user_id'");
$pets_count = $pets_count_query->fetch_assoc()['total'];

/* Count upcoming appointments (exclude completed/rejected/cancelled) */
$app_count_query = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE user_id='$user_id' AND status NOT IN ('completed','rejected','cancelled')");
$app_count = $app_count_query->fetch_assoc()['total'];

// Refund banner flag (used to hide actions right after a refund request)
$refund_requested_param = (isset($_GET['refund']) && $_GET['refund'] === 'requested');

/* Ensure owner notes column exists */
$owner_notes_col = $conn->query("SHOW COLUMNS FROM appointments LIKE 'owner_notes'");
if (!$owner_notes_col || $owner_notes_col->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN owner_notes TEXT NULL");
}

/* Ensure refund_requested column exists */
$refund_col = $conn->query("SHOW COLUMNS FROM appointments LIKE 'refund_requested'");
if (!$refund_col || $refund_col->num_rows === 0) {
    $conn->query("ALTER TABLE appointments ADD COLUMN refund_requested TINYINT(1) NOT NULL DEFAULT 0");
}

/* Get pets list */
$pets_result = $conn->query("
SELECT p.*, COUNT(a.appointment_id) AS appt_count, MAX(a.appointment_date) AS last_appt
FROM pets p
LEFT JOIN appointments a ON a.pet_id = p.pet_id
WHERE p.user_id='$user_id'
GROUP BY p.pet_id
");

/* Pets list for feedback */
$pets_for_feedback = $conn->query("
SELECT pet_id, pet_name FROM pets WHERE user_id='$user_id' ORDER BY pet_name
");

/* Services list for feedback */
$services_for_feedback = $conn->query("
SELECT service_name FROM services ORDER BY service_name
");

/* Reschedule dropdown data */
$pets_for_reschedule = $conn->query("
SELECT pet_id, pet_name FROM pets WHERE user_id='$user_id' ORDER BY pet_name
");

$services_for_reschedule = $conn->query("
SELECT service_id, service_name FROM services ORDER BY service_name
");

$vets_for_reschedule = $conn->query("
SELECT user_id, doctor_name, name FROM users WHERE role='vet' ORDER BY name
");

/* Vets list for feedback (only vets this user has appointments with) */
$vets_for_feedback = $conn->query("
SELECT DISTINCT u.name AS vet_name
FROM appointments a
JOIN users u ON a.vet_id = u.user_id
WHERE a.user_id = '$user_id'
ORDER BY u.name
");

/* Recent appointments */
$appointments = $conn->query("
SELECT 
a.appointment_date,
a.appointment_time,
p.pet_name,
a.service_id,
s.service_name,
u.name AS vet_name,
u.clinic_name,
u.clinic_address,
a.status
FROM appointments a
JOIN pets p ON a.pet_id = p.pet_id
JOIN users u ON a.vet_id = u.user_id
LEFT JOIN services s ON a.service_id = s.service_id
WHERE a.user_id='$user_id'
ORDER BY a.appointment_date DESC
LIMIT 5
");

/* Prescriptions for this user's pets */
$prescriptions = $conn->query("
SELECT
    prescriptions.medication,
    prescriptions.dosage,
    prescriptions.frequency,
    prescriptions.duration,
    prescriptions.instructions,
    prescriptions.created_at,
    pets.pet_name,
    vets.name AS vet_name
FROM prescriptions
JOIN pets ON prescriptions.pet_id = pets.pet_id
LEFT JOIN users vets ON prescriptions.vet_id = vets.user_id
WHERE pets.user_id = '$user_id'
ORDER BY prescriptions.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAMPER YOUR PET - Parent Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --brand-900: #0f2b46;
            --brand-700: #1f4d78;
            --brand-500: #2f7ec7;
            --accent-400: #f2b48d;
            --accent-100: #fff3e9;
            --ink-900: #0f172a;
            --ink-700: #334155;
            --ink-600: #475569;
            --ink-500: #64748b;
            --surface: #ffffff;
            --surface-alt: #f7f9fc;
            --border: #e2e8f0;
            --shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            display: flex;
            height: 100vh;
            background: #f3f6fb;
            color: var(--ink-900);
            overflow: hidden;
        }

        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, #0f2236 0%, #0b1c2e 100%);
            color: var(--surface);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand {
            padding: 20px 18px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand i { color: var(--accent-400); }
        .brand span { color: #ffffff; opacity: 0.9; }

        .menu { flex: 1; padding: 14px 12px; overflow-y: auto; }
        .menu-item {
            padding: 12px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: 0.2s;
            color: #cbd5f5;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 6px;
            font-size: 0.92rem;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.12);
            color: #ffffff;
            border-left: 3px solid var(--accent-400);
        }

        .logout { padding: 14px; border-top: 1px solid rgba(255,255,255,0.08); }
        .logout-btn {
            width: 100%;
            padding: 9px;
            background: transparent;
            border: 1px solid #f87171;
            color: #f87171;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s;
            font-size: 0.9rem;
        }
        .logout-btn:hover { background: #f87171; color: white; }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

        .top-bar {
            height: 66px;
            background: var(--surface);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 22px;
            border-bottom: 1px solid var(--border);
        }

        .search-bar {
            background: var(--surface-alt);
            padding: 8px 14px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            width: 280px;
            border: 1px solid var(--border);
            gap: 8px;
        }

        .search-bar input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 0.9rem;
        }

        .user-info { display: flex; align-items: center; gap: 10px; }
        .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--brand-500), var(--accent-400));
            color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;
            font-size: 0.85rem;
        }

        .view-container {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            display: none;
        }
        .view-container.active { display: block; animation: fadeIn 0.25s ease; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 14px;
        }

        .page-header h2 {
            font-size: 1.5rem;
            margin-bottom: 4px;
        }

        .subtle {
            color: var(--ink-600);
            font-size: 0.92rem;
        }
        .section-note { color: var(--ink-600); font-size: 0.9rem; margin-bottom: 10px; }
        .quick-actions { display: flex; gap: 10px; }

        .btn-primary {
            background: var(--brand-700);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-secondary {
            background: #ffffff;
            color: var(--brand-700);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.88rem;
            text-decoration: none;
        }

        .btn-primary:hover, .btn-secondary:hover { opacity: 0.92; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .stat-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-info h3 { font-size: 1.6rem; color: var(--ink-900); margin-bottom: 4px; }
        .stat-info p { color: var(--ink-600); font-size: 0.86rem; }
        .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .bg-teal { background: var(--accent-100); color: var(--brand-700); }
        .bg-blue { background: #e0f2fe; color: var(--brand-700); }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2.3fr 0.9fr;
            gap: 14px;
        }

        .panel {
            background: white;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .panel-header h3 { font-size: 1.05rem; }

        .panel-meta { color: var(--ink-500); font-size: 0.82rem; }

        .info-card {
            background: linear-gradient(135deg, #fff7ed, #ffffff);
            border: 1px solid #fde68a;
        }

        .info-card h4 { margin-bottom: 6px; }
        .info-card p { color: var(--ink-600); font-size: 0.9rem; }

        .stack { display: grid; gap: 12px; }

        .pet-mini-list { display: grid; gap: 8px; }
        .pet-mini { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border: 1px solid var(--border); border-radius: 10px; background: #ffffff; font-size: 0.85rem; }

        .pet-meta { display: flex; flex-wrap: wrap; gap: 6px; margin: 6px 0 8px; }
        .chip { padding: 4px 8px; border-radius: 999px; font-size: 0.72rem; background: #e8f0fe; color: var(--brand-700); font-weight: 600; }
        .chip-muted { background: #f1f5f9; color: var(--ink-600); font-weight: 500; }
        .pet-sub { color: var(--ink-600); font-size: 0.82rem; margin-bottom: 8px; }
        .status-pill { padding: 3px 8px; border-radius: 999px; font-size: 0.7rem; font-weight: 600; }
        .status-ok { background: #dcfce7; color: #15803d; }
        .muted { color: var(--ink-500); }
        .btn-link { color: var(--brand-700); text-decoration: none; font-weight: 600; font-size: 0.82rem; }

        .pets-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }

        .pet-card {
            background: white; border-radius: 12px; overflow: hidden; border: 1px solid var(--border);
            box-shadow: 0 6px 16px rgba(15,23,42,0.06); position: relative;
        }
        .pet-img { height: 160px; width: 100%; object-fit: cover; }
        .pet-details { padding: 14px; }
        .pet-details h3 { font-size: 1.05rem; margin-bottom: 4px; color: var(--ink-900); }
        .pet-details p { color: var(--ink-600); font-size: 0.85rem; margin-bottom: 10px; }
        .card-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; border-top: 1px solid var(--border); padding-top: 10px; }
        .btn-icon { background: none; border: none; cursor: pointer; font-size: 1rem; transition: 0.2s; }
        .btn-edit-pet { color: var(--brand-700); }
        .btn-delete-pet { color: var(--danger); }

        .table-container { background: transparent; padding: 0; border-radius: 0; border: none; box-shadow: none; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.86rem; }
        th { color: var(--ink-500); font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.4px; }

        .status-badge { padding: 3px 8px; border-radius: 999px; font-size: 0.68rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-completed { background: #e5e7eb; color: #4b5563; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

        .checkbox-row { display: flex; align-items: center; gap: 10px; justify-content: flex-start; }
        .checkbox-row input[type="checkbox"] { width: 16px; height: 16px; }
        .checkbox-row label { margin: 0; cursor: pointer; }

        input[type="date"] {
            padding-left: 12px;
            cursor: pointer;
        }

        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(15,23,42,0.45); align-items: center; justify-content: center;
        }

        .modal-content {
            background-color: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 460px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); animation: fadeIn 0.2s;
        }

        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .close-modal { cursor: pointer; font-size: 1.1rem; color: #999; }

        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.8rem; font-weight: 600; color: var(--ink-600); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 9px; border: 1px solid var(--border); border-radius: 8px; font-family: 'Poppins'; font-size: 0.85rem;
        }

        .modal-footer { text-align: right; margin-top: 12px; }
        .btn-save { background: var(--brand-700); color: white; padding: 8px 12px; border: none; border-radius: 8px; cursor: pointer; margin-right: 6px; font-size: 0.82rem; }
        .btn-cancel { background: #e2e8f0; color: #334155; padding: 8px 12px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.82rem; }
        .btn-delete { background: #fee2e2; color: #b91c1c; padding: 8px 12px; border: 1px solid #fecaca; border-radius: 8px; cursor: pointer; font-size: 0.82rem; }
        .btn-delete:hover { background: #fecaca; }

        .modal-body { display: flex; flex-direction: column; gap: 8px; }

        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .sidebar { width: 190px; }
            .search-bar { width: 200px; }
        }

        @media (max-width: 640px) {
            .sidebar { display: none; }
            .top-bar { padding: 0 12px; }
            .view-container { padding: 14px; }
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
</head>
<body>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-paw"></i> <span>PAMPER YOUR PET</span>
        </div>
        <div class="menu">
            <a href="javascript:void(0)" class="menu-item active" onclick="showView('dashboard')">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="javascript:void(0)" class="menu-item" onclick="showView('pets')">
                <i class="fas fa-dog"></i> My Pets
            </a>
            <a href="javascript:void(0)" class="menu-item" onclick="showView('appointments')">
                <i class="fas fa-calendar-alt"></i> My Appointments
            </a>
            <a href="javascript:void(0)" class="menu-item" onclick="showView('feedback')">
                <i class="fas fa-comment-dots"></i> Feedback
            </a>
            <a href="javascript:void(0)" class="menu-item" onclick="showView('prescriptions')">
                <i class="fas fa-prescription-bottle-medical"></i> Prescriptions
            </a>
            <a href="pet-parent.php" class="menu-item">
                <i class="fas fa-stethoscope"></i> Services
            </a>
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
                <input type="text" id="globalSearch" placeholder="Search pets..." onkeyup="filterPets()">
            </div>
            <div class="user-info">
                <div style="text-align: right; margin-right: 10px;">
                    <h4 style="font-size:0.9rem;"><?php echo $username; ?></h4>
                    <span style="font-size: 0.75rem; color: #6b7280;">Pet Parent</span>
                </div>
                <div class="avatar">JD</div>
            </div>
        </div>

        <!-- VIEW 1: DASHBOARD OVERVIEW -->
        <div id="dashboard" class="view-container active">
            <div class="page-header">
                <div>
                    <h2>Dashboard Overview</h2>
                    <div class="subtle">Here is a quick snapshot of your care activity.</div>
                </div>
                <div class="quick-actions">
                    <a class="btn-secondary" href="book-appointment.php">Book Appointment</a>
                    <button class="btn-primary" onclick="openPetModal()"><i class="fas fa-plus"></i> Add Pet</button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3 id="totalPets"><?php echo $pets_count; ?></h3>
                        <p>Registered Pets</p>
                    </div>
                    <div class="stat-icon bg-teal"><i class="fas fa-paw"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3 id="upcomingAppts"><?php echo $app_count; ?></h3>
                        <p>Upcoming Appointments</p>
                    </div>
                    <div class="stat-icon bg-blue"><i class="fas fa-calendar-check"></i></div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="panel">
                    <div class="panel-header">
                        <h3>Recent Appointments</h3>
                        <span class="panel-meta">Latest 5 visits</span>
                    </div>
                    <div class="table-container">
                        <table id="recentTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Pet</th>
                                    <th>Service</th>
                                    <th>Vet</th>
                                    <th>Clinic Name</th>
                                    <th>Clinic Address</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
<?php if($appointments->num_rows > 0){ ?>
<?php while($row = $appointments->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['appointment_date']; ?></td>
<td><?php echo $row['appointment_time']; ?></td>
<td><?php echo $row['pet_name']; ?></td>
<td>
<?php
$svc = $row['service_name'] ?? '';
if ($svc === '' || $svc === null) {
    if($row['service_id']==1) $svc = "General Care";
    elseif($row['service_id']==2) $svc = "Vaccination";
    elseif($row['service_id']==3) $svc = "Diagnostics";
    else $svc = "Unknown";
}
echo $svc;
?>
</td>
<td>Dr. <?php echo $row['vet_name']; ?></td>
<td><?php echo htmlspecialchars($row['clinic_name'] ?? '', ENT_QUOTES); ?></td>
<td><?php echo htmlspecialchars($row['clinic_address'] ?? '', ENT_QUOTES); ?></td>
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
</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="8" style="color:#6b7280;">No recent appointments yet</td>
</tr>
<?php } ?>
</tbody>
                        </table>
                    </div>
                </div>
                <div class="stack">
                    <div class="panel info-card">
                        <h4>Care Tip</h4>
                        <p>Keep your pet hydrated and schedule regular wellness checks to stay ahead of health issues.</p>
                    </div>
                    <div class="panel">
                        <div class="panel-header">
                            <h3>Upcoming Visit</h3>
                            <span class="panel-meta">Next appointment</span>
                        </div>
                        <?php
                        $nextApp = $conn->query("
SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.owner_notes, a.pet_id, a.service_id, a.vet_id, p.pet_name, u.name AS vet_name
FROM appointments a
JOIN pets p ON a.pet_id = p.pet_id
JOIN users u ON a.vet_id = u.user_id
WHERE a.user_id='$user_id' AND a.status NOT IN ('completed','rejected','cancelled')
ORDER BY a.appointment_date ASC, a.appointment_time ASC
LIMIT 1
");
                        if($nextApp && $nextApp->num_rows > 0){
                            $next = $nextApp->fetch_assoc();
                            $serviceLabel = "General Care";
                            if($next['service_id']==2) $serviceLabel = "Vaccination";
                            elseif($next['service_id']==3) $serviceLabel = "Diagnostics";
                        ?>
                        <div class="subtle" style="margin-bottom:6px;"><strong><?php echo $next['pet_name']; ?></strong> - <?php echo $serviceLabel; ?></div>
                        <div class="subtle" style="margin-bottom:10px;"><?php echo $next['appointment_date']; ?> at <?php echo $next['appointment_time']; ?> with Dr. <?php echo $next['vet_name']; ?></div>
                        <button
                            class="btn-secondary"
                            type="button"
                            onclick="openRescheduleModal(this)"
                            data-appointment-id="<?php echo htmlspecialchars($next['appointment_id'], ENT_QUOTES); ?>"
                            data-date="<?php echo htmlspecialchars($next['appointment_date'], ENT_QUOTES); ?>"
                            data-time="<?php echo htmlspecialchars($next['appointment_time'], ENT_QUOTES); ?>"
                            data-pet-id="<?php echo htmlspecialchars($next['pet_id'], ENT_QUOTES); ?>"
                            data-service-id="<?php echo htmlspecialchars($next['service_id'], ENT_QUOTES); ?>"
                            data-vet-id="<?php echo htmlspecialchars($next['vet_id'], ENT_QUOTES); ?>"
                            data-pet="<?php echo htmlspecialchars($next['pet_name'], ENT_QUOTES); ?>"
                            data-service="<?php echo htmlspecialchars($serviceLabel, ENT_QUOTES); ?>"
                            data-vet="<?php echo htmlspecialchars($next['vet_name'], ENT_QUOTES); ?>"
                            data-notes="<?php echo htmlspecialchars($next['owner_notes'] ?? '', ENT_QUOTES); ?>"
                        >Reschedule</button>
                        <?php } else { ?>
                        <div class="subtle" style="margin-bottom:10px;">No upcoming visit scheduled.</div>
                        <a class="btn-secondary" href="book-appointment.php">Book Appointment</a>
                        <?php } ?>
                    </div>
                    <div class="panel">
                        <div class="panel-header">
                            <h3>Next Vaccine Due</h3>
                            <span class="panel-meta">Recommended</span>
                        </div>
                        <div class="subtle" style="margin-bottom:10px;">We recommend an annual vaccine review to keep protection up to date.</div>
                        <a class="btn-secondary" href="services.html">View Vaccination Care</a>
                    </div>
                    <div class="panel">
                        <div class="panel-header">
                            <h3>Pet List</h3>
                            <span class="panel-meta">Quick access</span>
                        </div>
                        <div class="pet-mini-list">
                            <?php
                            $pets_preview = $conn->query("SELECT pet_name, species FROM pets WHERE user_id='$user_id' LIMIT 4");
                            if($pets_preview && $pets_preview->num_rows > 0){
                                while($p = $pets_preview->fetch_assoc()){
                            ?>
                            <div class="pet-mini">
                                <span><?php echo htmlspecialchars($p['pet_name'], ENT_QUOTES); ?></span>
                                <span class="subtle"><?php echo htmlspecialchars($p['species'], ENT_QUOTES); ?></span>
                            </div>
                            <?php }
                            } else { ?>
                            <div class="subtle">No pets added yet.</div>
                            <?php } ?>
                        </div>
                        <div style="margin-top:10px;">
                            <button class="btn-primary" onclick="showView('pets')">Manage Pets</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW 2: MY PETS -->
        <div id="pets" class="view-container">
            <h2>
                My Pets
                <button class="btn-primary" onclick="openPetModal()">
                    <i class="fas fa-plus"></i> Add New Pet
                </button>
            </h2>

            <div class="pets-grid">
            <?php if($pets_result->num_rows > 0){ ?>
            <?php while($pet = $pets_result->fetch_assoc()){ ?>
            <div class="pet-card">
                <img src="assets/dog-placeholder.svg" class="pet-img">
                <div class="pet-details">
                    <h3><?php echo $pet['pet_name']; ?></h3>
                    <p><?php echo $pet['breed']; ?> - <?php echo $pet['age']; ?></p>
                    <?php if(!empty($pet['vet_notes'])){ ?>
                    <p><strong>Vet Notes:</strong> <?php echo $pet['vet_notes']; ?></p>
                    <?php } else { ?>
                    <p style="color:gray;">No medical records</p>
                    <?php } ?>
                    <div class="card-actions">
                        <span class="muted">Records updated</span>
                        <button class="btn-icon btn-edit-pet"
                            onclick="openEditPetModal(
                                '<?php echo $pet['pet_id']; ?>',
                                '<?php echo htmlspecialchars($pet['pet_name'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($pet['species'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($pet['breed'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($pet['age'], ENT_QUOTES); ?>'
                            )"
                            title="Edit Pet">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php } ?>
            <?php } else { ?>
                <p>No pets added yet</p>
            <?php } ?>
            </div>
        </div>

        <!-- VIEW 3: MY APPOINTMENTS -->
        <div id="appointments" class="view-container">
            <h2>My Appointments</h2>
            <p class="section-note"><?php echo $app_count; ?> Upcoming Appointments</p>
            <?php if (isset($_GET['refund']) && $_GET['refund'] === 'requested'): ?>
                <div class="panel" style="border:1px solid #bbf7d0; background:#f0fdf4; color:#166534; margin-bottom:12px;">
                    Refund requested. You will receive it within 24 hours.
                </div>
            <?php elseif (isset($_GET['refund']) && $_GET['refund'] === 'error'): ?>
                <div class="panel" style="border:1px solid #fecaca; background:#fef2f2; color:#991b1b; margin-bottom:12px;">
                    Unable to request refund. Please try again.
                </div>
            <?php endif; ?>
            <div class="table-container">
                <table id="appointmentTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Pet</th>
                            <th>Service</th>
                            <th>Vet</th>
                            <th>Clinic Name</th>
                            <th>Clinic Address</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
$apps = $conn->query("
SELECT 
a.appointment_date,
a.appointment_time,
p.pet_name,
a.pet_id,
a.appointment_id,
a.owner_notes,
a.service_id,
 a.vet_id,
u.name AS vet_name,
u.clinic_name,
u.clinic_address,
a.status,
 a.refund_requested,
pay.payment_status,
pay.amount,
pay.payment_method
FROM appointments a
JOIN pets p ON a.pet_id = p.pet_id
JOIN users u ON a.vet_id = u.user_id
LEFT JOIN payments pay ON pay.appointment_id = a.appointment_id
WHERE a.user_id='$user_id'
");

while($row = $apps->fetch_assoc()){
    $serviceLabel = "General Care";
    if($row['service_id']==2) $serviceLabel = "Vaccination";
    elseif($row['service_id']==3) $serviceLabel = "Diagnostics";
?>
<tr>
<td><?php echo $row['appointment_date']; ?></td>
<td><?php echo $row['appointment_time']; ?></td>
<td><?php echo $row['pet_name']; ?></td>

<td>
<?php echo $serviceLabel; ?>
</td>

<td>Dr. <?php echo $row['vet_name']; ?></td>
<td><?php echo htmlspecialchars($row['clinic_name'] ?? '', ENT_QUOTES); ?></td>
<td><?php echo htmlspecialchars($row['clinic_address'] ?? '', ENT_QUOTES); ?></td>

<td>
<span class="status-badge status-<?php echo $row['status']; ?>">
<?php echo $row['status']; ?>
</span>
</td>

<td><?php echo !empty($row['owner_notes']) ? htmlspecialchars($row['owner_notes'], ENT_QUOTES) : '-'; ?></td>
<td>
<?php if (!empty($row['payment_status'])) { ?>
    <?php echo htmlspecialchars($row['payment_status'], ENT_QUOTES); ?>
    <?php if (!empty($row['amount'])) { ?>
        (<?php echo htmlspecialchars($row['amount'], ENT_QUOTES); ?><?php echo !empty($row['payment_method']) ? ', ' . htmlspecialchars($row['payment_method'], ENT_QUOTES) : ''; ?>)
    <?php } ?>
<?php } else { ?>
    -
<?php } ?>
</td>
<td>
    <?php if (($row['status'] === 'rejected' || $row['status'] === 'cancelled') && (int)($row['refund_requested'] ?? 0) !== 1 && ($row['payment_status'] ?? '') !== 'refund_requested' && !$refund_requested_param) : ?>
        <button
            class="btn-secondary"
            type="button"
            onclick="openRescheduleModal(this)"
            data-appointment-id="<?php echo htmlspecialchars($row['appointment_id'], ENT_QUOTES); ?>"
            data-date="<?php echo htmlspecialchars($row['appointment_date'], ENT_QUOTES); ?>"
            data-time="<?php echo htmlspecialchars($row['appointment_time'], ENT_QUOTES); ?>"
            data-pet-id="<?php echo htmlspecialchars($row['pet_id'], ENT_QUOTES); ?>"
            data-service-id="<?php echo htmlspecialchars($row['service_id'], ENT_QUOTES); ?>"
            data-vet-id="<?php echo htmlspecialchars($row['vet_id'], ENT_QUOTES); ?>"
            data-pet="<?php echo htmlspecialchars($row['pet_name'], ENT_QUOTES); ?>"
            data-service="<?php echo htmlspecialchars($serviceLabel, ENT_QUOTES); ?>"
            data-vet="<?php echo htmlspecialchars($row['vet_name'], ENT_QUOTES); ?>"
            data-notes="<?php echo htmlspecialchars($row['owner_notes'] ?? '', ENT_QUOTES); ?>"
        >Reschedule</button>
        <button type="button" class="btn-delete" onclick="openRefundModal('<?php echo htmlspecialchars($row['appointment_id'], ENT_QUOTES); ?>')">Refund</button>
    <?php elseif ($row['status'] !== 'completed' && $row['status'] !== 'cancelled') : ?>
        <button
            class="btn-secondary"
            type="button"
            onclick="openRescheduleModal(this)"
            data-appointment-id="<?php echo htmlspecialchars($row['appointment_id'], ENT_QUOTES); ?>"
            data-date="<?php echo htmlspecialchars($row['appointment_date'], ENT_QUOTES); ?>"
            data-time="<?php echo htmlspecialchars($row['appointment_time'], ENT_QUOTES); ?>"
            data-pet-id="<?php echo htmlspecialchars($row['pet_id'], ENT_QUOTES); ?>"
            data-service-id="<?php echo htmlspecialchars($row['service_id'], ENT_QUOTES); ?>"
            data-vet-id="<?php echo htmlspecialchars($row['vet_id'], ENT_QUOTES); ?>"
            data-pet="<?php echo htmlspecialchars($row['pet_name'], ENT_QUOTES); ?>"
            data-service="<?php echo htmlspecialchars($serviceLabel, ENT_QUOTES); ?>"
            data-vet="<?php echo htmlspecialchars($row['vet_name'], ENT_QUOTES); ?>"
            data-notes="<?php echo htmlspecialchars($row['owner_notes'] ?? '', ENT_QUOTES); ?>"
        >Reschedule</button>
    <?php else : ?>
        -
    <?php endif; ?>
</td>
</tr>

<?php } ?>
</tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 4: FEEDBACK -->
        <div id="feedback" class="view-container">
            <div class="page-header">
                <div>
                    <h2>Share Feedback</h2>
                    <div class="subtle">Help us improve your pet care experience.</div>
                </div>
            </div>
            <?php if (isset($_GET['feedback']) && $_GET['feedback'] === 'sent'): ?>
                <div class="panel" style="border:1px solid #bbf7d0; background:#f0fdf4; color:#166534; margin-bottom:12px;">
                    Thank you! Your feedback has been submitted.
                </div>
            <?php elseif (isset($_GET['feedback']) && $_GET['feedback'] === 'error'): ?>
                <div class="panel" style="border:1px solid #fecaca; background:#fef2f2; color:#991b1b; margin-bottom:12px;">
                    Please fill all required fields before submitting.
                </div>
            <?php endif; ?>
            <div class="panel">
                <form method="POST" action="save-feedback.php">
                    <div class="form-row" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
                        <div class="form-group">
                            <label>Pet</label>
                            <select name="pet_id">
                                <option value="">Select pet (optional)</option>
                                <?php if ($pets_for_feedback && $pets_for_feedback->num_rows > 0): ?>
                                    <?php while($pet = $pets_for_feedback->fetch_assoc()): ?>
                                        <option value="<?php echo $pet['pet_id']; ?>"><?php echo htmlspecialchars($pet['pet_name'], ENT_QUOTES); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Visit Type</label>
                            <select name="visit_type" required>
                                <?php if ($services_for_feedback && $services_for_feedback->num_rows > 0): ?>
                                    <?php while($svc = $services_for_feedback->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($svc['service_name'], ENT_QUOTES); ?>">
                                            <?php echo htmlspecialchars($svc['service_name'], ENT_QUOTES); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="General Care">General Care</option>
                                    <option value="Vaccination">Vaccination</option>
                                    <option value="Diagnostics">Diagnostics</option>
                                <?php endif; ?>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vet Name</label>
                            <select name="vet_name" required>
                                <option value="">Select vet</option>
                                <?php if ($vets_for_feedback && $vets_for_feedback->num_rows > 0): ?>
                                    <?php while($vet = $vets_for_feedback->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($vet['vet_name'], ENT_QUOTES); ?>">
                                            Dr. <?php echo htmlspecialchars($vet['vet_name'], ENT_QUOTES); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Visit Date</label>
                            <input type="date" name="visit_date" class="date-max-today" required max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Overall Rating</label>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="rating" value="5" required> Excellent</label>
                            <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="rating" value="4"> Good</label>
                            <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="rating" value="3"> Average</label>
                            <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="rating" value="2"> Poor</label>
                            <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="rating" value="1"> Very Poor</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Feedback</label>
                        <textarea name="comments" rows="5" placeholder="Tell us what went well and what we can improve..." required></textarea>
                    </div>

                    <div class="form-group checkbox-row">
                        <input type="checkbox" name="contact_ok" value="1" id="contactOk" required>
                        <label for="contactOk" style="margin:0;">You may contact me about this feedback.</label>
                    </div>

                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
                        <a class="btn-secondary" href="pet-parent-dashboard.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- VIEW 5: MY PRESCRIPTIONS -->
        <div id="prescriptions" class="view-container">
            <h2>My Prescriptions</h2>
            <p class="section-note">Prescriptions provided by your veterinarian.</p>
            <div class="table-container">
                <table id="prescriptionsTable">
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Vet</th>
                            <th>Date</th>
                            <th>Instructions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if($prescriptions && $prescriptions->num_rows > 0){ ?>
<?php while($rx = $prescriptions->fetch_assoc()){ ?>
<tr>
<td><?php echo htmlspecialchars($rx['pet_name'] ?? '', ENT_QUOTES); ?></td>
<td><?php echo htmlspecialchars($rx['medication'] ?? '', ENT_QUOTES); ?></td>
<td><?php echo htmlspecialchars($rx['dosage'] ?? '', ENT_QUOTES); ?></td>
<td><?php echo htmlspecialchars($rx['frequency'] ?? '', ENT_QUOTES); ?></td>
<td><?php echo htmlspecialchars($rx['duration'] ?? '', ENT_QUOTES); ?></td>
<td><?php echo !empty($rx['vet_name']) ? 'Dr. ' . htmlspecialchars($rx['vet_name'], ENT_QUOTES) : ''; ?></td>
<td><?php echo !empty($rx['created_at']) ? date('M d, Y', strtotime($rx['created_at'])) : ''; ?></td>
<td><?php echo htmlspecialchars($rx['instructions'] ?? '-', ENT_QUOTES); ?></td>
<td>
    <button
        class="btn-secondary"
        onclick="printPrescription(this)"
        data-pet="<?php echo htmlspecialchars($rx['pet_name'] ?? '', ENT_QUOTES); ?>"
        data-medication="<?php echo htmlspecialchars($rx['medication'] ?? '', ENT_QUOTES); ?>"
        data-dosage="<?php echo htmlspecialchars($rx['dosage'] ?? '', ENT_QUOTES); ?>"
        data-frequency="<?php echo htmlspecialchars($rx['frequency'] ?? '', ENT_QUOTES); ?>"
        data-duration="<?php echo htmlspecialchars($rx['duration'] ?? '', ENT_QUOTES); ?>"
        data-vet="<?php echo !empty($rx['vet_name']) ? 'Dr. ' . htmlspecialchars($rx['vet_name'], ENT_QUOTES) : ''; ?>"
        data-date="<?php echo !empty($rx['created_at']) ? date('M d, Y', strtotime($rx['created_at'])) : ''; ?>"
        data-instructions="<?php echo htmlspecialchars($rx['instructions'] ?? '-', ENT_QUOTES); ?>"
    >
        <i class="fas fa-print"></i> Print/PDF
    </button>
</td>
</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="9" style="color:#6b7280;">No prescriptions yet</td>
</tr>
<?php } ?>
</tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- ADD PET MODAL -->
    <div id="petModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Pet</h3>
                <span class="close-modal" onclick="closeModal('petModal')">&times;</span>
            </div>
            <form action="add-pet.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Pet Name</label>
                    <input type="text" id="newPetName" name="pet_name" placeholder="Enter pet name" required>
                </div>

                <div class="form-group">
                    <label>Species</label>
                    <select name="species">
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Breed</label>
                    <input type="text" id="newPetBreed" name="breed" placeholder="Example: Labrador">
                </div>

                <div class="form-group">
                    <label>Age</label>
                    <input type="text" id="newPetAge" name="age" placeholder="Example: 2 years">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-save">Add Pet</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('petModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT PET MODAL -->
    <div id="editPetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Pet</h3>
                <span class="close-modal" onclick="closeModal('editPetModal')">&times;</span>
            </div>
            <form action="update-pet.php" method="POST">
                <input type="hidden" id="editPetId" name="pet_id">

                <div class="form-group">
                    <label>Pet Name</label>
                    <input type="text" id="editPetName" name="pet_name" required>
                </div>

                <div class="form-group">
                    <label>Species</label>
                    <select id="editPetSpecies" name="species">
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Breed</label>
                    <input type="text" id="editPetBreed" name="breed">
                </div>

                <div class="form-group">
                    <label>Age</label>
                    <input type="text" id="editPetAge" name="age">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-delete" onclick="confirmDeletePet()">Delete Pet</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('editPetModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- RESCHEDULE APPOINTMENT MODAL -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reschedule Appointment</h3>
                <span class="close-modal" onclick="closeModal('rescheduleModal')">&times;</span>
            </div>
            <form action="update-appointment-parent.php" method="POST" id="rescheduleForm">
                <input type="hidden" id="rescheduleAppointmentId" name="appointment_id">

                <div class="form-group">
                    <label>Pet</label>
                    <select id="reschedulePet" name="pet_id" required>
                        <option value="">Select pet</option>
                        <?php if ($pets_for_reschedule && $pets_for_reschedule->num_rows > 0): ?>
                            <?php while($pet = $pets_for_reschedule->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($pet['pet_id'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($pet['pet_name'], ENT_QUOTES); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Service</label>
                    <select id="rescheduleService" name="service_id" required>
                        <option value="">Select service</option>
                        <?php if ($services_for_reschedule && $services_for_reschedule->num_rows > 0): ?>
                            <?php while($svc = $services_for_reschedule->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($svc['service_id'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($svc['service_name'], ENT_QUOTES); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Veterinarian</label>
                    <select id="rescheduleVet" name="vet_id" required>
                        <option value="">Select veterinarian</option>
                        <?php if ($vets_for_reschedule && $vets_for_reschedule->num_rows > 0): ?>
                            <?php while($vet = $vets_for_reschedule->fetch_assoc()): ?>
                                <?php $vet_name = !empty($vet['doctor_name']) ? $vet['doctor_name'] : $vet['name']; ?>
                                <option value="<?php echo htmlspecialchars($vet['user_id'], ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($vet_name ? "Dr. " . $vet_name : "Vet", ENT_QUOTES); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>New Date</label>
                    <input type="date" id="rescheduleDate" name="appointment_date" class="date-min-today" required>
                </div>

                <div class="form-group">
                    <label>New Time</label>
                    <input type="time" id="rescheduleTime" name="appointment_time" step="60" required>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="rescheduleNotes" name="owner_notes" rows="3" placeholder="Any updates or special requests..."></textarea>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-save">Update Appointment</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('rescheduleModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- REFUND CONFIRM MODAL -->
    <div id="refundModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request Refund</h3>
                <span class="close-modal" onclick="closeModal('refundModal')">&times;</span>
            </div>
            <form method="POST" action="request-refund.php" id="refundForm">
                <input type="hidden" id="refundAppointmentId" name="appointment_id">
                <div class="modal-body">
                    <p class="section-note">Do you want a refund for this appointment?</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-delete">Yes, Refund</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('refundModal')">No</button>
                </div>
            </form>
        </div>
    </div>

    <form id="deletePetForm" action="delete-pet.php" method="POST" style="display:none;">
        <input type="hidden" id="deletePetId" name="pet_id">
    </form>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        function showView(viewId) {
            document.querySelectorAll('.view-container').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('active'));
            document.getElementById(viewId).classList.add('active');
            
            const menuItems = document.querySelectorAll('.menu-item');
            if (viewId === 'dashboard') menuItems[0].classList.add('active');
            if (viewId === 'pets') menuItems[1].classList.add('active');
            if (viewId === 'appointments') menuItems[2].classList.add('active');
            if (viewId === 'feedback') menuItems[3].classList.add('active');
            if (viewId === 'prescriptions') menuItems[4].classList.add('active');
        }

        function openPetModal() {
            document.getElementById('newPetName').value = '';
            document.getElementById('newPetBreed').value = '';
            document.getElementById('newPetAge').value = '';
            document.getElementById('petModal').style.display = 'flex';
        }

        function openEditPetModal(id, name, species, breed, age) {
            document.getElementById('editPetId').value = id;
            document.getElementById('deletePetId').value = id;
            document.getElementById('editPetName').value = name;
            document.getElementById('editPetSpecies').value = species || 'Dog';
            document.getElementById('editPetBreed').value = breed || '';
            document.getElementById('editPetAge').value = age || '';
            document.getElementById('editPetModal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function confirmDeletePet() {
            const name = document.getElementById('editPetName').value || 'this pet';
            const ok = confirm(`Delete ${name}? This will remove related appointments and prescriptions.`);
            if (!ok) return;
            const form = document.getElementById('deletePetForm');
            if (form) form.submit();
        }

        function filterPets() {
            const text = document.getElementById('globalSearch').value.toLowerCase();
            const cards = document.querySelectorAll('.pet-card');
            cards.forEach(card => {
                const name = card.querySelector('h3').innerText.toLowerCase();
                if (name.includes(text)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

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

        const setMinToday = (input) => {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            input.min = `${yyyy}-${mm}-${dd}`;
        };

        document.querySelectorAll('.date-max-today').forEach(input => {
            setMaxToday(input);
            input.addEventListener('click', () => {
                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                }
            });
        });

        document.querySelectorAll('.date-min-today').forEach(input => {
            setMinToday(input);
            input.addEventListener('click', () => {
                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                }
            });
        });

        const params = new URLSearchParams(window.location.search);
        const initialView = params.get('view');
        if (initialView) {
            showView(initialView);
        }

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
                        body { font-family: 'Poppins', Arial, sans-serif; color: #0f172a; background: #ffffff; }
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
                                <div class="brand-sub">Veterinary Care and Wellness</div>
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
                                    <div class="label">Prescribed By</div>
                                    <div class="value">${data.vet || '-'}</div>
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
                        <div>Keep this prescription for your records.</div>
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

        function openRescheduleModal(button) {
            if (!button || !button.dataset) return;
            const data = button.dataset;
            const idEl = document.getElementById('rescheduleAppointmentId');
            const petEl = document.getElementById('reschedulePet');
            const serviceEl = document.getElementById('rescheduleService');
            const vetEl = document.getElementById('rescheduleVet');
            const dateEl = document.getElementById('rescheduleDate');
            const timeEl = document.getElementById('rescheduleTime');
            const notesEl = document.getElementById('rescheduleNotes');

            if (idEl) idEl.value = data.appointmentId || '';
            if (petEl) petEl.value = data.petId || '';
            if (serviceEl) serviceEl.value = data.serviceId || '';
            if (vetEl) vetEl.value = data.vetId || '';
            if (dateEl) dateEl.value = data.date || '';

            let timeVal = data.time || '';
            if (timeVal.length >= 5) {
                timeVal = timeVal.substring(0, 5);
            }
            if (timeEl) timeEl.value = timeVal;
            if (notesEl) notesEl.value = data.notes || '';

            const modal = document.getElementById('rescheduleModal');
            if (modal) modal.style.display = 'flex';
        }

        function openRefundModal(appointmentId) {
            const idEl = document.getElementById('refundAppointmentId');
            if (idEl) idEl.value = appointmentId || '';
            const modal = document.getElementById('refundModal');
            if (modal) modal.style.display = 'flex';
        }
    </script>
</body>
</html>

