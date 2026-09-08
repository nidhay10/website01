<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pamper Your Pet - Home</title>
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
            --ink-600: #475569;
            --ink-500: #64748b;
            --surface: #ffffff;
            --surface-alt: #f7f9fc;
            --border: #e2e8f0;
            --shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            --radius: 14px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'DM Sans', sans-serif;
        }

        body {
            color: var(--ink-900);
            background: radial-gradient(circle at 20% 20%, #eff6ff 0%, #f8fafc 45%, #ffffff 100%);
        }

        img {
            max-width: 100%;
            display: block;
        }

        a {
            color: inherit;
        }

        .container {
            max-width: 1150px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* NAVBAR */
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
            text-decoration: none;
            color: var(--ink-600);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--brand-700);
        }

        .auth-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-login {
            background-color: transparent;
            color: var(--brand-900);
            border: 1px solid var(--border);
        }

        .btn-signup {
            background-color: var(--brand-700);
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(31, 77, 120, 0.25);
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        /* HERO SECTION */
        .hero {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 32px;
            align-items: center;
            padding: 28px 0 36px;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            line-height: 1.1;
            color: var(--brand-900);
            margin-bottom: 16px;
        }

        .hero p {
            font-size: 1.05rem;
            color: var(--ink-600);
            margin-bottom: 18px;
        }

        .hero-btns {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-explore {
            background-color: var(--brand-700);
            color: #ffffff;
            padding: 12px 22px;
            box-shadow: 0 12px 24px rgba(31, 77, 120, 0.25);
        }

        .btn-learn {
            background-color: #ffffff;
            color: var(--brand-900);
            padding: 12px 22px;
            border: 1px solid var(--border);
        }

        .hero-card {
            background: #ffffff;
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .hero-card img {
            border-radius: 12px;
            height: 340px;
            object-fit: cover;
        }

        .hero-meta {
            display: flex;
            gap: 14px;
            margin-top: 14px;
        }

        .meta-tile {
            flex: 1;
            background: var(--surface-alt);
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.9rem;
            color: var(--ink-500);
        }

        .meta-tile strong {
            display: block;
            color: var(--brand-900);
            font-size: 1.05rem;
        }

        .trust-row {
            margin-top: 18px;
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            color: var(--ink-500);
            font-size: 0.9rem;
        }

        .trust-pill {
            background: var(--accent-100);
            padding: 6px 12px;
            border-radius: 999px;
        }

        /* COMMITMENT SECTION */
        .commitment {
            padding: 36px 0;
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 28px;
            align-items: center;
            background: var(--surface);
        }

        .section-tag {
            color: var(--brand-700);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 2px;
            margin-bottom: 8px;
            display: block;
        }

        .commitment h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            margin-bottom: 14px;
            color: var(--ink-900);
        }

        .commitment p {
            color: var(--ink-600);
            margin-bottom: 18px;
            line-height: 1.6;
        }

        .expertise-box {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .expertise-item {
            background: var(--surface-alt);
            padding: 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .expertise-item h4 {
            color: var(--brand-900);
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .expertise-item p {
            font-size: 0.86rem;
            margin: 0;
        }

        .commitment-img {
            position: relative;
        }

        .commitment-img img {
            width: 100%;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .info-banner {
            margin-top: 14px;
            background: var(--brand-900);
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 0.9rem;
        }

        /* FEATURES SECTION */
        .features {
            background: linear-gradient(135deg, #0f2b46, #1f4d78);
            padding: 34px 0;
            color: #ffffff;
            border-radius: 24px;
        }

        .features-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .feature-card {
            background-color: rgba(255, 255, 255, 0.12);
            padding: 22px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
        }

        .feature-card.highlight {
            background-color: #ffffff;
            color: var(--brand-900);
        }

        .feature-card h3 {
            margin: 12px 0 6px;
            font-size: 1.1rem;
        }

        .feature-card p {
            color: inherit;
            font-size: 0.92rem;
            opacity: 0.9;
        }

        .icon-wrap {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.25);
            display: grid;
            place-items: center;
        }

        .feature-card.highlight .icon-wrap {
            background: var(--accent-100);
        }

        /* SERVICES SECTION */
        .services {
            padding: 36px 0;
            background-color: var(--surface-alt);
        }

        .services h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin-bottom: 6px;
        }

        .services p {
            color: var(--ink-600);
            margin-bottom: 18px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .service-item {
            background: var(--surface);
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            border: 1px solid var(--border);
            transition: transform 0.3s;
        }

        .service-item:hover {
            transform: translateY(-5px);
        }

        .service-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--accent-100);
            display: grid;
            place-items: center;
            margin-bottom: 12px;
        }

        .service-item h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .service-item p {
            font-size: 0.9rem;
            margin-bottom: 14px;
            color: var(--ink-600);
        }

        .view-details {
            color: var(--brand-700);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            background: none;
            padding: 0;
            cursor: pointer;
        }

        .details-panel {
            display: none;
            margin-top: 10px;
            color: var(--ink-600);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .service-item.open .details-panel { display: block; }

        .split-section {
            padding: 34px 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: center;
        }

        .checklist {
            display: grid;
            gap: 10px;
        }

        .checklist li {
            list-style: none;
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 10px 12px;
            border-radius: 12px;
            color: var(--ink-600);
        }

        .cta {
            padding: 30px;
            background: linear-gradient(135deg, #fff3e9, #ffffff);
            border-radius: 20px;
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .cta h3 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 8px;
        }

        /* FOOTER */
        footer {
            background-color: #0b1c2e;
            color: #ffffff;
            padding: 32px 0 24px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 0.8fr 0.8fr;
            gap: 24px;
            align-items: start;
            margin-bottom: 18px;
        }

        .footer-logo {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: inline-block;
        }

        .footer-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .footer-text {
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .footer-meta {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 0;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            padding-top: 12px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .copyright {
            color: #64748b;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero { grid-template-columns: 1fr; }
            .hero h1 { font-size: 2.2rem; }
            .commitment { grid-template-columns: 1fr; text-align: center; }
            .features { border-radius: 18px; }
            .features-grid, .services-grid, .feedbacks-grid { grid-template-columns: 1fr; }
            .split-section { grid-template-columns: 1fr; }
            .cta { flex-direction: column; text-align: center; }
            .feedbacks-header { flex-direction: column; align-items: flex-start; }
            .info-banner { flex-direction: column; }            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .footer-links { align-items: center; }
            .footer-bottom { justify-content: center; text-align: center; }

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
                /* FEEDBACKS */
        .feedbacks {
            padding: 30px 0 30px;
        }

        .feedbacks-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .feedbacks-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--brand-900);
        }

        .feedbacks-header p {
            color: var(--ink-600);
            max-width: 520px;
        }

        .feedbacks-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .feedback-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
            display: grid;
            gap: 12px;
        }

        .feedback-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .feedback-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand-500), var(--accent-400));
            color: #ffffff;
            font-weight: 700;
            display: grid;
            place-items: center;
            letter-spacing: 0.5px;
        }

        .feedback-name {
            font-weight: 700;
            color: var(--brand-900);
        }

        .feedback-text {
            color: var(--ink-600);
            line-height: 1.6;
        }

        .feedback-meta {
            font-size: 0.9rem;
            color: var(--ink-500);
            font-weight: 500;
        }</style>
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>

    <!-- NAVIGATION -->
    <div class="container">
        <nav>
            <div class="logo">
                <span class="logo-mark">P</span>
                <span>PAMPER YOUR PET</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="services.html">Services</a></li>
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

        <!-- HERO SECTION -->
        <section class="hero">
            <div>
                <span class="section-tag">Trusted Veterinary Care</span>
                <h1>Health-first care that pets (and parents) feel right away.</h1>
                <p>From routine wellness to advanced diagnostics, our team blends compassion with modern veterinary science to keep your companions thriving.</p>
                <div class="hero-btns">
                    <a href="services.html" class="btn btn-explore">Explore Services</a>
                    <a href="signup.php" class="btn btn-learn">Book an Appointment</a>
                </div>
                <div class="trust-row">
                    <span class="trust-pill">Certified veterinary surgeons</span>
                    <span class="trust-pill">24/7 emergency support</span>
                </div>
            </div>
            <div class="hero-card">
                <img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=900&q=80" alt="Veterinarian with pet">
                <div class="hero-meta">
                </div>
            </div>
        </section>
    </div>

    <!-- COMMITMENT SECTION -->
    <section class="commitment">
        <div class="commitment-img">
            <img src="https://images.unsplash.com/photo-1508672019048-805c876b67e2?auto=format&fit=crop&w=900&q=80" alt="Veterinary care consultation">
            <div class="info-banner">
                <span>Same-day consult slots available</span>
            </div>
        </div>
        <div class="commitment-text">
            <span class="section-tag">Our Promise</span>
            <h2>Precision medicine with a gentle, personal touch.</h2>
            <p>Every visit is designed to be low-stress, high-trust. We pair advanced diagnostics with clear communication so you feel confident in every decision.</p>
            <div class="expertise-box">
                <div class="expertise-item">
                    <h4>Certified Expertise</h4>
                    <p>Specialists in surgery, dermatology, and dentistry.</p>
                </div>
                <div class="expertise-item">
                    <h4>Modern Technology</h4>
                    <p>Digital radiology, ultrasound, and in-house labs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features">
        <div class="container">
            <div class="features-header">
                <h2>What Sets Us Apart</h2>
                <p>Clear care plans, transparent pricing, and a team that treats your pet like family.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 7v5l3 2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="9" stroke="white" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Simple booking support and quick appointment scheduling for your pet’s next visit.</p>
                </div>
                <div class="feature-card highlight">
                    <div class="icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3l8 4v5c0 5-3.5 9-8 9s-8-4-8-9V7l8-4z" stroke="#1f4d78" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9.5 12l2 2 4-4" stroke="#1f4d78" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Trusted Quality</h3>
                    <p>International protocols, safety-first procedures, and caring follow-ups.</p>
                </div>
                <div class="feature-card">
                    <div class="icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 5.6-7 10-7 10z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Pet-First Care</h3>
                    <p>Low-stress handling tailored to each pet's personality.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Expertise -->
    <section class="services">
        <div class="container">
            <h2>Our Expertise</h2>
            <p>Specialized capabilities delivered by certified veterinary teams.</p>
            <div class="services-grid">
                <div class="service-item">
                    <div class="service-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h10" stroke="#1f4d78" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Advanced Internal Medicine</h3>
                    <p>Complex case workups, chronic condition management, and detailed care plans.</p>
                    <button type="button" class="view-details" aria-expanded="false">View Details</button>
                    <div class="details-panel">
                        Chronic disease tracking, medication adjustments, and long-term wellness monitoring to keep care consistent between visits.
                    </div>
                </div>
                <div class="service-item">
                    <div class="service-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3v18M7 8h10M7 16h10" stroke="#1f4d78" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Diagnostic Pathology</h3>
                    <p>Tissue analysis and lab interpretation for precise, evidence-based decisions.</p>
                    <button type="button" class="view-details" aria-expanded="false">View Details</button>
                    <div class="details-panel">
                        Fast lab turnarounds, clear result summaries, and actionable next steps shared with your vet right away.
                    </div>
                </div>
                <div class="service-item">
                    <div class="service-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 12h16M12 4v16" stroke="#1f4d78" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Surgical Excellence</h3>
                    <p>Soft-tissue and orthopedic procedures with rigorous safety protocols.</p>
                    <button type="button" class="view-details" aria-expanded="false">View Details</button>
                    <div class="details-panel">
                        Pre-op screening, careful anesthesia monitoring, and post-op recovery plans tailored to each pet.
                    </div>
                </div>
                <div class="service-item">
                    <div class="service-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="6" stroke="#1f4d78" stroke-width="2"/>
                            <path d="M20 20l-3.5-3.5" stroke="#1f4d78" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Digital Imaging Suite</h3>
                    <p>High-definition radiology with rapid reads and specialist review.</p>
                    <button type="button" class="view-details" aria-expanded="false">View Details</button>
                    <div class="details-panel">
                        Quick imaging, clear findings, and copies available for referrals or second opinions.
                    </div>
                </div>
                <div class="service-item">
                    <div class="service-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 12c4-6 12-6 16 0-4 6-12 6-16 0z" stroke="#1f4d78" stroke-width="2"/>
                            <circle cx="12" cy="12" r="2" fill="#1f4d78"/>
                        </svg>
                    </div>
                    <h3>Ultrasound & Cardiac Scans</h3>
                    <p>Non-invasive imaging for soft tissue and cardiac insights.</p>
                    <button type="button" class="view-details" aria-expanded="false">View Details</button>
                    <div class="details-panel">
                        Comfortable scanning with real-time explanations and follow-up recommendations if needed.
                    </div>
                </div>
                <div class="service-item">
                    <div class="service-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 3h8v6l2 2v8H6V11l2-2V3z" stroke="#1f4d78" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>In-House Laboratory</h3>
                    <p>Rapid panels, blood work, and microbiology testing on site.</p>
                    <button type="button" class="view-details" aria-expanded="false">View Details</button>
                    <div class="details-panel">
                        Same-day test results for common checks to speed up treatment decisions.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="split-section container">
        <div>
            <span class="section-tag">Care Journey</span>
            <h2 style="font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 10px;">A clear path from symptoms to solutions.</h2>
            <p style="color: var(--ink-600); margin-bottom: 16px;">We keep you informed at every step with transparent timelines, estimates, and wellness guidance.</p>
            <ul class="checklist">
                <li>Personalized intake and health history review</li>
                <li>On-site diagnostics with same-day updates</li>
                <li>Tailored recovery plans and follow-up reminders</li>
            </ul>
        </div>
        <div class="cta">
            <div>
                <h3>Ready to meet the team?</h3>
                <p style="color: var(--ink-600);">Schedule a visit or request a call-back today.</p>
            </div>
            <div class="hero-btns">
                <a href="signup.php" class="btn btn-explore">Book Now</a>
                <a href="login.php" class="btn btn-learn">Pet Parent Login</a>
            </div>
        </div>
    </section>

    <section class="feedbacks">
    <div class="container">
        <div class="feedbacks-header">
            <div>
                <span class="section-tag">Testimonials</span>
                <h2>Pet parents love the experience</h2>
                <p>Real words from visitors who booked, visited, and used the portal.</p>
            </div>
        </div>
        <div class="feedbacks-grid">
            <article class="feedback-card">
                <div class="feedback-top">
                    <div class="feedback-avatar">MS</div>
                    <div>
                        <div class="feedback-name">Meera S.</div>
                        <div class="feedback-meta">Golden retriever parent</div>
                    </div>
                </div>
                <p class="feedback-text">Booking took two minutes, and reminders were perfectly timed. The dashboard made everything easy to track.</p>
            </article>
            <article class="feedback-card">
                <div class="feedback-top">
                    <div class="feedback-avatar">AP</div>
                    <div>
                        <div class="feedback-name">Arjun P.</div>
                        <div class="feedback-meta">Cat rescue volunteer</div>
                    </div>
                </div>
                <p class="feedback-text">Clear service descriptions and a simple booking flow. It helped us pick the right care quickly.</p>
            </article>
            <article class="feedback-card">
                <div class="feedback-top">
                    <div class="feedback-avatar">PK</div>
                    <div>
                        <div class="feedback-name">Priya K.</div>
                        <div class="feedback-meta">First-time pet parent</div>
                    </div>
                </div>
                <p class="feedback-text">From signup to follow-up, the experience felt smooth and supportive. Loved the updates after our visit.</p>
            </article>
        </div>
    </div>
</section>
    <!-- FOOTER -->
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

    <script>
        document.querySelectorAll('.service-item .view-details').forEach((btn) => {
            btn.addEventListener('click', () => {
                const card = btn.closest('.service-item');
                if (!card) return;
                const isOpen = card.classList.toggle('open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                btn.textContent = isOpen ? 'View Less' : 'View Details';
            });
        });
    </script>
</body>
</html>

















