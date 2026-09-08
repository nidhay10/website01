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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/footer.css">
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
        img { max-width: 100%; display: block; }

        .container {
            max-width: 1150px;
            margin: 0 auto;
            padding: 0 24px;
        }

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

        .nav-links a:hover,
        .nav-links a.active { color: var(--brand-700); }

        .auth-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-login {
            background-color: transparent;
            color: var(--brand-900);
            border: 1px solid var(--border);
        }

        .btn-signup {
            background-color: #1d4ed8;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(31, 77, 120, 0.25);
        }

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

        .hero {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 20px;
            margin: 22px 0 30px;
        }

        .hero-content {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 26px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
        }

        .hero-content h1 {
            font-size: 2.4rem;
            margin-bottom: 10px;
        }

        .hero-content p {
            color: var(--ink-600);
            margin-bottom: 18px;
        }

        .hero-panel {
            background: linear-gradient(120deg, rgba(31, 77, 120, 0.92), rgba(47, 126, 199, 0.75));
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 26px;
            display: grid;
            gap: 14px;
        }

        .hero-panel h2 { font-size: 1.5rem; }
        .hero-panel p { opacity: 0.9; }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.14);
            border-radius: 14px;
            padding: 12px;
            text-align: center;
        }

        .stat-card span { display: block; font-weight: 700; font-size: 1.1rem; }
        .stat-card small { opacity: 0.85; }

        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink-600);
            font-weight: 600;
            cursor: pointer;
        }

        .filter-btn.active {
            border-color: var(--brand-700);
            color: var(--brand-700);
            background: var(--surface-alt);
        }

        .filter-note { color: var(--ink-500); font-size: 0.9rem; }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 40px;
        }

        .service-card {
            background: var(--surface);
            border-radius: 18px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            display: grid;
            grid-template-rows: 200px auto;
        }

        .card-media img { width: 100%; height: 100%; object-fit: cover; }

        .media-tag {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(255,255,255,0.92);
            color: var(--brand-900);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .card-media { position: relative; }

        .card-body {
            padding: 18px;
            display: grid;
            gap: 10px;
        }

        .card-body h3 { font-size: 1.2rem; }
        .card-body p { color: var(--ink-600); }

        .card-actions { display: flex; justify-content: flex-end; }

        .ghost-btn {
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--surface-alt);
            font-weight: 600;
            cursor: pointer;
        }

        .view-more-wrap {
            display: flex;
            justify-content: center;
            margin-top: 16px;
        }

        .visitor-section {
            margin: 46px 0;
        }

        .visitor-header {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }

        .visitor-header h2 {
            font-size: 1.9rem;
        }

        .visitor-header p {
            color: var(--ink-600);
        }

        .visitor-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .visitor-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px;
            box-shadow: var(--shadow-card);
            display: grid;
            gap: 10px;
        }

        .visitor-card h3 {
            font-size: 1.1rem;
        }

        .visitor-card p {
            color: var(--ink-600);
            font-size: 0.95rem;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .step-card {
            background: var(--surface-alt);
            border: 1px dashed rgba(47, 126, 199, 0.25);
            border-radius: var(--radius-md);
            padding: 16px;
            display: grid;
            gap: 8px;
        }

        .step-card span {
            font-weight: 700;
            color: var(--brand-700);
        }

        .step-card p {
            color: var(--ink-600);
            font-size: 0.95rem;
        }

        .cta-band {
            background: linear-gradient(120deg, rgba(31, 77, 120, 0.92), rgba(47, 126, 199, 0.75));
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 24px;
            display: grid;
            gap: 10px;
            margin-top: 40px;
        }

        .cta-band p { opacity: 0.9; }

        .cta-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .modal.open { display: flex; }

        .modal-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            max-width: 680px;
            width: 100%;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-soft);
        }

        .modal-media img { width: 100%; height: 280px; object-fit: cover; }
        .modal-body { padding: 20px; }
        .modal-body h3 { margin-bottom: 10px; }
        .modal-actions { padding: 0 20px 20px; display: flex; justify-content: flex-end; }

        .close-btn {
            padding: 8px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: transparent;
            font-weight: 600;
            cursor: pointer;
        }

        @media (max-width: 980px) {
            .hero { grid-template-columns: 1fr; }
            .services-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .visitor-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .steps { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .nav-links { display: none; }
            .services-grid { grid-template-columns: 1fr; }
            .hero-content h1 { font-size: 2rem; }
            .visitor-grid { grid-template-columns: 1fr; }
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
    <div class="container">
        <nav>
            <div class="logo">
                <span class="logo-mark">P</span>
                <span>PAMPER YOUR PET</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="services.html" class="active">Services</a></li>
                <li><a href="about.php">About</a></li>
            </ul>
            <div class="auth-buttons">
<?php if(isset($_SESSION['user_id'])){ ?>
                <a href="logout.php" class="btn btn-login">Logout</a>
<?php } else { ?>
                <a href="login.php" class="btn btn-login">Login</a>
                <a href="signup.php" class="btn btn-signup">Sign Up</a>
<?php } ?>
            </div>
        </nav>
<section class="hero">
            <div class="hero-content">
                <h1>Expert care for pets, with comfort for families.</h1>
                <p>Discover tailored services, transparent pricing, and compassionate vets ready to help.</p>
            </div>
            <div class="hero-panel">
                <div>
                    <h2>From checkups to advanced care, everything in one place.</h2>
                    <p>Choose a service, see what it includes, and book instantly.</p>
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
                        <span>24/7</span>
                        <small>Support</small>
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
        <?php if ($serviceCount > 6): ?>
        <div class="view-more-wrap">
            <button type="button" class="btn btn-outline" id="toggleServices">View More</button>
        </div>
        <?php endif; ?>

        <section class="visitor-section">
            <div class="visitor-header">
                <h2>Why Pet Parents Choose Us</h2>
                <p>Clear pricing, friendly guidance, and a calm experience for every visit.</p>
            </div>
            <div class="visitor-grid">
                <div class="visitor-card">
                    <h3>Transparent Care Plans</h3>
                    <p>Know what to expect with straightforward estimates and step-by-step explanations.</p>
                </div>
                <div class="visitor-card">
                    <h3>Comfort-First Handling</h3>
                    <p>Low-stress techniques and patient-centered care for nervous pets.</p>
                </div>
                <div class="visitor-card">
                    <h3>Modern Diagnostics</h3>
                    <p>Fast lab work and imaging for clear answers when your pet needs them.</p>
                </div>
            </div>
        </section>
        

        <section class="visitor-section">
            <div class="visitor-header">
                <h2>How It Works</h2>
                <p>Book a visit in minutes and stay informed throughout your pet's care.</p>
            </div>
            <div class="steps">
                <div class="step-card">
                    <span>Step 1</span>
                    <p>Select a service and choose your preferred vet.</p>
                </div>
                <div class="step-card">
                    <span>Step 2</span>
                    <p>Pick a convenient date and time for your appointment.</p>
                </div>
                <div class="step-card">
                    <span>Step 3</span>
                    <p>Complete booking and receive updates in your dashboard.</p>
                </div>
            </div>
        </section>

        <section class="cta-band">
            <h2>Ready to book a visit?</h2>
            <p>Choose a service, select your vet, and reserve a slot in just a few clicks.</p>
            <div class="cta-actions">
<a class="btn btn-outline" href="login.php">Pet Parent Login</a>
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
                        <a href="services.php">Services</a>
<a href="login.php">Pet Parent Login</a>
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
                showAllServices = false;
                applyServiceLimit();
            });
        });

        let showAllServices = false;
        const toggleBtn = document.getElementById('toggleServices');

        const applyServiceLimit = () => {
            const limit = showAllServices ? Number.MAX_SAFE_INTEGER : 6;
            const activeFilter = document.querySelector('.filter-btn.active')?.getAttribute('data-filter') || 'all';
            let shown = 0;
            let matching = 0;
            serviceCards.forEach((card) => {
                const category = card.getAttribute('data-category');
                const matchesFilter = activeFilter === 'all' || category === activeFilter;
                if (!matchesFilter) {
                    card.style.display = 'none';
                    return;
                }
                matching += 1;
                shown += 1;
                card.style.display = shown <= limit ? 'grid' : 'none';
            });
            if (toggleBtn) {
                const anyHidden = matching > limit;
                toggleBtn.style.display = anyHidden && !showAllServices ? 'inline-flex' : 'none';
            }
        };

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                showAllServices = true;
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