<?php
session_start();
require "db.php";

$services = $conn->query("SELECT * FROM services");
$serviceItems = [];
$categories = [];
$categoryLabels = [
    'preventive' => 'Preventive Care',
    'diagnostics' => 'Diagnostics',
    'surgery' => 'Surgery & Procedures',
    'wellness' => 'Wellness & Nutrition',
    'other' => 'Other'
];
$categoryOrder = ['preventive', 'diagnostics', 'surgery', 'wellness', 'other'];
$categoryMap = [
    'General Checkup' => 'preventive',
    'Vaccination' => 'preventive',
    'Parasite Control' => 'preventive',
    'Microchipping' => 'preventive',
    'Senior Pet Screening' => 'preventive',
    'Diagnostics' => 'diagnostics',
    'X-Ray Imaging' => 'diagnostics',
    'Ultrasound' => 'diagnostics',
    'Laboratory Tests' => 'diagnostics',
    'Surgery' => 'surgery',
    'Spay / Neuter' => 'surgery',
    'Dental Care' => 'surgery',
    'Wound Care' => 'surgery',
    'Pain Management' => 'surgery',
    'Nutrition Counseling' => 'wellness',
    'Weight Management' => 'wellness',
    'Chronic Care Plan' => 'wellness',
    'Post-Op Follow Up' => 'wellness'
];
$imageMap = [
    'General Checkup' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=800&q=80',
    'Vaccination' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=800&q=80',
    'Parasite Control' => 'https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=800&q=80',
    'Microchipping' => 'https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=800&q=80',
    'Senior Pet Screening' => 'https://images.unsplash.com/photo-1508672019048-805c876b67e2?auto=format&fit=crop&w=800&q=80',
    'Diagnostics' => 'https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?auto=format&fit=crop&w=800&q=80',
    'X-Ray Imaging' => 'https://images.unsplash.com/photo-1495433324511-bf8e92934d90?auto=format&fit=crop&w=800&q=80',
    'Ultrasound' => 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=800&q=80',
    'Laboratory Tests' => 'https://images.unsplash.com/photo-1580281657527-47f249e8f6f5?auto=format&fit=crop&w=800&q=80',
    'Surgery' => 'https://images.unsplash.com/photo-1504208434309-cb69f4fe52b0?auto=format&fit=crop&w=800&q=80',
    'Spay / Neuter' => 'https://images.unsplash.com/photo-1525253086316-d0c936c814f8?auto=format&fit=crop&w=800&q=80',
    'Dental Care' => 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=800&q=80',
    'Wound Care' => 'https://images.unsplash.com/photo-1518020382113-a7e8fc38eac9?auto=format&fit=crop&w=800&q=80',
    'Pain Management' => 'https://images.unsplash.com/photo-1525253086316-d0c936c814f8?auto=format&fit=crop&w=800&q=80&sat=-10',
    'Nutrition Counseling' => 'https://images.unsplash.com/photo-1470390357047-6b0f2e5f7f15?auto=format&fit=crop&w=800&q=80',
    'Weight Management' => 'https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=800&q=80',
    'Chronic Care Plan' => 'https://images.unsplash.com/photo-1508675801627-066ac4346a0e?auto=format&fit=crop&w=800&q=80',
    'Post-Op Follow Up' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=800&q=80'
];
$excludedServices = [
    'Grooming',
    'Bath & Brush',
    'Nail Trim & Paw Care',
    'Coat Styling & Trim',
    'Basic Obedience',
    'Social Playtime',
    'Trick & Confidence Class',
    'Overnight Boarding',
    'Nutrition Starter Plan',
    'Day Boarding',
    'Wellness Check-Ins'
];

if ($services) {
    while ($row = $services->fetch_assoc()) {
        $name = trim($row['service_name'] ?? '');
        if ($name === '' || in_array($name, $excludedServices, true)) {
            continue;
        }
        $desc = trim($row['description'] ?? '');
        $category = $categoryMap[$name] ?? 'other';
        $categories[$category] = $categoryLabels[$category] ?? 'Other';
        $serviceItems[] = [
            'name' => $name,
            'desc' => $desc,
            'category' => $category,
            'category_label' => $categoryLabels[$category] ?? 'Other',
            'image' => $imageMap[$name] ?? 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=800&q=80'
        ];
    }
}

$serviceCount = count($serviceItems);
$orderedCategories = [];
foreach ($categoryOrder as $key) {
    if (isset($categories[$key])) {
        $orderedCategories[$key] = $categories[$key];
    }
}
$categoryCount = count($orderedCategories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services | Pamper Your Pet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }

        body {
            color: var(--ink-900);
            background: radial-gradient(circle at 10% 20%, #eff6ff 0%, #f8fafc 40%, #ffffff 100%);
            min-height: 100vh;
        }

        a { color: inherit; text-decoration: none; }

        .container { max-width: 1150px; margin: 0 auto; padding: 0 24px; }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--brand-900);
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand-500), var(--accent-400));
            display: grid;
            place-items: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 1rem;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 26px;
        }

        .nav-links a {
            color: var(--ink-600);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover, .nav-links a.active { color: var(--brand-700); }

        .nav-actions { display: flex; gap: 10px; }

        .btn {
            padding: 10px 18px;
            border-radius: 999px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-outline {
            background: transparent;
            color: var(--brand-900);
            border: 1px solid var(--border);
        }

        .btn-solid {
            background: var(--brand-700);
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(31, 77, 120, 0.25);
        }

        .btn:hover { transform: translateY(-1px); }

        .hero {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 28px;
            align-items: stretch;
            margin: 18px auto 36px;
        }

        .hero-content {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-soft);
            display: grid;
            gap: 18px;
            position: relative;
            overflow: hidden;
        }

        .hero-content::after {
            content: "";
            position: absolute;
            inset: -20% 50% auto auto;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(47, 126, 199, 0.15), transparent 70%);
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent-100);
            color: #c55d25;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            width: fit-content;
        }

        .hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.6rem;
            color: var(--brand-900);
        }

        .hero-content p {
            color: var(--ink-600);
            line-height: 1.7;
        }

        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }

        .hero-panel {
            background: linear-gradient(135deg, rgba(15, 43, 70, 0.95), rgba(31, 77, 120, 0.88)),
                url('https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=1200&q=80') center/cover;
            border-radius: var(--radius-lg);
            padding: 28px;
            color: #ffffff;
            display: grid;
            gap: 16px;
            min-height: 320px;
            box-shadow: var(--shadow-soft);
        }

        .hero-panel h2 { font-family: 'Playfair Display', serif; font-size: 2rem; }
        .hero-panel p { opacity: 0.9; line-height: 1.6; }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 12px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-card span { font-size: 1.1rem; font-weight: 700; display: block; }
        .stat-card small { opacity: 0.8; }

        .filter-bar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-card);
            padding: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .filter-group { display: flex; flex-wrap: wrap; gap: 10px; }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid transparent;
            background: var(--surface-alt);
            color: var(--ink-600);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn:hover { border-color: rgba(47, 126, 199, 0.4); color: var(--brand-700); }
        .filter-btn.active { background: var(--brand-700); color: #ffffff; }

        .filter-note { color: var(--ink-500); font-size: 0.9rem; }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 22px;
            margin-bottom: 40px;
        }

        .service-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            display: grid;
            grid-template-rows: 200px 1fr;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .service-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-soft); }

        .card-media { position: relative; overflow: hidden; }
        .card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .service-card:hover .card-media img { transform: scale(1.05); }

        .media-tag {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(255, 255, 255, 0.92);
            color: var(--brand-700);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .card-body {
            padding: 20px;
            display: grid;
            gap: 12px;
        }

        .card-body h3 { font-size: 1.2rem; color: var(--ink-900); }
        .card-body p { color: var(--ink-600); font-size: 0.95rem; line-height: 1.6; }

        .card-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 6px;
        }

        .ghost-btn {
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--ink-600);
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 2000;
        }
        .modal.open { display: flex; }
        .modal-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            max-width: 540px;
            width: 100%;
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }
        .modal-media {
            height: 220px;
            background: var(--surface-alt);
        }
        .modal-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .modal-body {
            padding: 22px;
            display: grid;
            gap: 10px;
        }
        .modal-body h3 {
            font-size: 1.4rem;
            color: var(--ink-900);
        }
        .modal-body p {
            color: var(--ink-600);
            line-height: 1.6;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            padding: 0 22px 22px;
        }
        .close-btn {
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: transparent;
            font-weight: 600;
            cursor: pointer;
            color: var(--ink-600);
        }

        .promise {
            background: linear-gradient(120deg, rgba(242, 180, 141, 0.25), rgba(47, 126, 199, 0.15));
            border-radius: var(--radius-lg);
            padding: 26px;
            display: grid;
            gap: 12px;
            margin-bottom: 50px;
            border: 1px solid rgba(47, 126, 199, 0.2);
        }

        .promise h3 { font-family: 'Playfair Display', serif; font-size: 1.8rem; }
        .promise p { color: var(--ink-700); }

        footer {
            background-color: #0b1c2e;
            color: #ffffff;
            padding: 24px 0;
            margin-top: 40px;
        }

        .footer-links { display: flex; justify-content: center; gap: 20px; margin-bottom: 10px; flex-wrap: wrap; }
        .footer-links a { color: #94a3b8; text-decoration: none; }
        .copyright { color: #64748b; font-size: 0.8rem; text-align: center; }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; }
            .hero-panel { min-height: 260px; }
            .hero-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 640px) {
            .nav-links { display: none; }
            .hero-content h1 { font-size: 2.1rem; }
            .hero-stats { grid-template-columns: 1fr; }
            .card-actions { flex-direction: column; align-items: stretch; }
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
    <div class="container">
        <nav>
            <a href="index.php" class="logo">
                <span class="logo-mark">P</span>
                <span>PAMPER YOUR PET</span>
            </a>
            <ul class="nav-links">
                <li><a href="pet-parent.php" class="active">Services</a></li>
                <li><a href="book-appointment.php">Book Appointment</a></li>
                <li><a href="pet-parent-dashboard.php">Dashboard</a></li>
            </ul>
            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-outline" href="pet-parent-dashboard.php">My Dashboard</a>
                    <a class="btn btn-solid" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-outline" href="login.php">Login</a>
                    <a class="btn btn-solid" href="signup.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>

        <section class="hero">
            <div class="hero-content">
                <span class="hero-tag">Campus-friendly care</span>
                <h1>Simple, approachable services built for a college project.</h1>
                <p>These offerings keep the site easy to understand while still showcasing a complete pet-care experience for your project.</p>
                <div class="hero-actions">
                    <a class="btn btn-solid" href="book-appointment.php">Book Appointment</a>
                    <a class="btn btn-outline" href="pet-parent-dashboard.php">Go to Dashboard</a>
                </div>
            </div>
            <div class="hero-panel">
                <div>
                    <h2>Friendly services with a clean, modern presentation.</h2>
                    <p>Organized categories, clear visuals, and short descriptions keep it easy for reviewers to scan.</p>
                </div>
                <div class="hero-stats">
                    <div class="stat-card">
                        <span><?php echo $serviceCount; ?></span>
                        <small>Service Types</small>
                    </div>
                    <div class="stat-card">
                        <span><?php echo $categoryCount; ?></span>
                        <small>Care Categories</small>
                    </div>
                    <div class="stat-card">
                        <span>1</span>
                        <small>Unified Design</small>
                    </div>
                </div>
            </div>
        </section>

        <section class="filter-bar">
            <div class="filter-group">
                <button class="filter-btn active" data-filter="all">All Services</button>
                <?php foreach ($orderedCategories as $slug => $label): ?>
                    <button class="filter-btn" data-filter="<?php echo htmlspecialchars($slug); ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <span class="filter-note">Grouped into cleaner categories for browsing.</span>
        </section>

        <section class="services-grid">
            <?php if ($serviceCount === 0): ?>
                <div class="card-body">
                    <h3>No services found</h3>
                    <p>Please add services in the database to see them here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($serviceItems as $index => $service): ?>
                    <article class="service-card" data-category="<?php echo htmlspecialchars($service['category']); ?>" data-index="<?php echo $index; ?>">
                        <div class="card-media">
                            <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=800&q=80';">
                            <span class="media-tag"><?php echo htmlspecialchars($service['category_label']); ?></span>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p><?php echo htmlspecialchars($service['desc'] !== '' ? $service['desc'] : 'Service details coming soon.'); ?></p>
                            <div class="card-actions">
                                <button type="button" class="ghost-btn learn-more-btn"
                                    data-name="<?php echo htmlspecialchars($service['name']); ?>"
                                    data-desc="<?php echo htmlspecialchars($service['desc'] !== '' ? $service['desc'] : 'Service details coming soon.'); ?>"
                                    data-image="<?php echo htmlspecialchars($service['image']); ?>">
                                    Learn More
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <?php if ($serviceCount > 9): ?>
        <div style="display:flex; justify-content:center; margin-top: 16px;">
            <button type="button" class="btn btn-outline" id="toggleServices">View More</button>
        </div>
        <?php endif; ?>

        <section class="promise">
            <h3>Clean structure for a clean demo.</h3>
            <p>These service options are simple, realistic, and easy to explain in presentations or project reviews.</p>
            <div class="hero-actions">
                <a class="btn btn-solid" href="book-appointment.php">Schedule a Visit</a>
            </div>
        </section>
    </div>

        <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">PAMPER YOUR PET</div>
                    <p class="footer-text">Compassionate veterinary care with modern diagnostics and attentive follow-up.</p>
                </div>
                <div class="footer-col">
                    <div class="footer-title">Explore</div>
                    <div class="footer-links">
                        <a href="index.php">Home</a>
                        <a href="services.html">Services</a>
                        <a href="signup.php">Book Appointment</a>
                        <a href="login.php">Patient Portal</a>
                    </div>
                </div>
                <div class="footer-col">
                    <div class="footer-title">Care Standards</div>
                    <div class="footer-text">ISO-aligned protocols, transparent treatment plans, and post-visit follow-ups included.</div>
                    <div class="footer-meta">Emergency readiness • Compassion-first handling</div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="copyright">© <?php echo date("Y"); ?> Pamper Your Pet. All rights reserved.</div>
                <div class="footer-meta">ISO-aligned care protocols</div>
            </div>
        </div>
    </footer>

    <div class="modal" id="serviceModal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-media">
                <img id="modalImage" src="" alt="">
            </div>
            <div class="modal-body">
                <h3 id="modalTitle"></h3>
                <p id="modalDesc"></p>
            </div>
            <div class="modal-actions">
                <button type="button" class="close-btn" id="modalClose">Close</button>
            </div>
        </div>
    </div>

    <script>
        const filterButtons = document.querySelectorAll('.filter-btn');
        const serviceCards = document.querySelectorAll('.service-card');

        filterButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                filterButtons.forEach((button) => button.classList.remove('active'));
                btn.classList.add('active');

                applyServiceLimit();
            });
        });

        const toggleBtn = document.getElementById('toggleServices');
        let showAllServices = false;

        const applyServiceLimit = () => {
            const limit = showAllServices ? Number.MAX_SAFE_INTEGER : 9;
            let shown = 0;
            serviceCards.forEach((card) => {
                const category = card.getAttribute('data-category');
                const activeFilter = document.querySelector('.filter-btn.active')?.getAttribute('data-filter') || 'all';
                const matchesFilter = activeFilter === 'all' || category === activeFilter;
                if (!matchesFilter) {
                    card.style.display = 'none';
                    return;
                }
                shown += 1;
                card.style.display = shown <= limit ? 'grid' : 'none';
            });
            if (toggleBtn) {
                toggleBtn.textContent = showAllServices ? 'View Less' : 'View More';
                const anyHidden = serviceCards.length > limit;
                toggleBtn.style.display = anyHidden ? 'inline-flex' : 'none';
            }
        };

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                showAllServices = !showAllServices;
                applyServiceLimit();
            });
        }

        applyServiceLimit();

        const modal = document.getElementById('serviceModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalDesc = document.getElementById('modalDesc');
        const modalImage = document.getElementById('modalImage');
        const modalClose = document.getElementById('modalClose');

        document.querySelectorAll('.learn-more-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                modalTitle.textContent = btn.dataset.name || 'Service';
                modalDesc.textContent = btn.dataset.desc || 'Service details coming soon.';
                modalImage.src = btn.dataset.image || '';
                modalImage.alt = btn.dataset.name || 'Service';
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            });
        });

        const closeModal = () => {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        };

        modalClose.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                closeModal();
            }
        });
    </script>
</body>
</html>


