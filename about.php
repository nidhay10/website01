<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | Pamper Your Pet</title>
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
            min-height: 100vh;
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
            text-decoration: none;
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

        .nav-links a:hover, .nav-links a.active {
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

        .hero {
            background: linear-gradient(135deg, rgba(15, 43, 70, 0.95), rgba(31, 77, 120, 0.88)),
                url('https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1400&q=80') center/cover;
            color: #ffffff;
            border-radius: 24px;
            padding: 40px 32px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
            align-items: center;
            margin: 10px 0 28px;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            margin-bottom: 12px;
        }

        .hero p {
            font-size: 1.05rem;
            opacity: 0.92;
        }

        .hero-card {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 18px;
            padding: 18px;
            backdrop-filter: blur(6px);
            display: grid;
            gap: 10px;
        }

        .hero-card h3 {
            font-size: 1.1rem;
        }

        .hero-card p {
            font-size: 0.92rem;
            opacity: 0.9;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .section-subtitle {
            color: var(--ink-600);
            margin-bottom: 20px;
        }

        .split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 28px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .values {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        .value-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }

        .value-card h4 {
            margin-bottom: 6px;
        }

        .value-card p {
            color: var(--ink-600);
            font-size: 0.9rem;
        }

        .milestones {
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        .milestone {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }

        .milestone strong {
            display: block;
            font-size: 1.4rem;
            margin-bottom: 4px;
        }

                .journey-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .journey-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .journey-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--accent-100);
            display: grid;
            place-items: center;
            margin-bottom: 10px;
        }

        .journey-card p {
            color: var(--ink-600);
            font-size: 0.9rem;
        }

        .framework {
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 18px;
            margin-bottom: 32px;
        }

        .framework ul {
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .framework li {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 12px;
            color: var(--ink-600);
            font-size: 0.9rem;
        }

        .cta {
            background: linear-gradient(135deg, #fff3e9, #ffffff);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .cta h3 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 8px;
        }

        footer {
            background-color: #0b1c2e;
            color: #ffffff;
            padding: 26px 0;
            margin-top: 20px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
        }

        .copyright {
            color: #64748b;
            font-size: 0.8rem;
        }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr;             .journey-grid { grid-template-columns: repeat(2, 1fr); }
            .framework { grid-template-columns: 1fr; }
}
            .split { grid-template-columns: 1fr; }
            .values { grid-template-columns: 1fr; }
            .milestones { grid-template-columns: 1fr; }
            .team { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .nav-links { display: none;             .journey-grid { grid-template-columns: 1fr; }
}
            .hero h1 { font-size: 2.2rem; }
            .cta { flex-direction: column; text-align: center; }
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
                <li><a href="index.php">Home</a></li>
                <li><a href="services.html">Services</a></li>
                <li><a href="about.php" class="active">About</a></li>
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
            <div>
                <span class="section-tag">About Us</span>
                <h1>Care teams that treat every pet like family.</h1>
                <p>We are a modern veterinary group built on clinical excellence, empathetic care, and clear communication.</p>
            </div>
            <div class="hero-card">
                <h3>Our mission</h3>
                <p>Deliver exceptional veterinary care with transparency, compassion, and the latest medical standards.</p>
            </div>
        </section>

        <section class="split">
            <div class="card">
                <h2 class="section-title">How We Work</h2>
                <p class="section-subtitle">Every visit is designed to be clear, supportive, and focused on outcomes.</p>
                <p>We combine experienced clinicians, modern diagnostics, and compassionate handling to provide care plans that are easy to understand and tailored to your pet.</p>
            </div>
            <div class="card">
                <h2 class="section-title">Our Promise</h2>
                <p class="section-subtitle">A relationship built on trust and transparency.</p>
            </div>
        </section>

        <section>
            <h2 class="section-title">Our Values</h2>
            <p class="section-subtitle">The principles that shape every decision we make.</p>
            <div class="values">
                <div class="value-card">
                    <h4>Compassion First</h4>
                    <p>We create a calm environment for pets and families from check-in to recovery.</p>
                </div>
                <div class="value-card">
                    <h4>Clinical Rigor</h4>
                    <p>Evidence-based protocols and careful monitoring in every procedure.</p>
                </div>
                <div class="value-card">
                    <h4>Clear Communication</h4>
                    <p>Transparent estimates, clear next steps, and no surprises.</p>
                </div>
            </div>
        </section>
<section>
            <h2 class="section-title">Our Care Journey</h2>
            <p class="section-subtitle">A structured, transparent approach from first visit to long-term wellness.</p>
            <div class="journey-grid">
                <div class="journey-card">
                    <div class="journey-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="#1f4d78" stroke-width="2"/>
                            <path d="M12 7v5l3 2" stroke="#1f4d78" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h4>Intake & Assessment</h4>
                    <p>We listen first, capture history, and define clear care goals.</p>
                </div>
                <div class="journey-card">
                    <div class="journey-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 12h16" stroke="#1f4d78" stroke-width="2" stroke-linecap="round"/>
                            <path d="M12 4v16" stroke="#1f4d78" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h4>Diagnostics</h4>
                    <p>On-site imaging and lab testing for fast, confident decisions.</p>
                </div>
                <div class="journey-card">
                    <div class="journey-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 12l3 3 7-7" stroke="#1f4d78" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="9" stroke="#1f4d78" stroke-width="2"/>
                        </svg>
                    </div>
                    <h4>Treatment Plan</h4>
                    <p>Evidence-based care with transparent options and pricing.</p>
                </div>
                <div class="journey-card">
                    <div class="journey-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 5.6-7 10-7 10z" stroke="#1f4d78" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h4>Recovery & Follow-up</h4>
                    <p>Continuous support, reminders, and proactive wellness checks.</p>
                </div>
            </div>
        </section>

        <section class="framework">
            <div>
                <h2 class="section-title">Our Quality Framework</h2>
                <p class="section-subtitle">Standardized protocols and compassionate delivery across every visit.</p>
                <p>We maintain strict safety, sanitation, and monitoring standards across all care stages to ensure consistent outcomes.</p>
            </div>
            <ul>
                <li>ISO-aligned clinical protocols and safety checklists</li>
                <li>Real-time updates and post-visit summaries</li>
                <li>Low-stress handling and comfort-first environments</li>
                <li>Specialist consults for complex cases</li>
            </ul>
        </section>

        <section class="cta">
            <div>
                <h3>Want to learn more?</h3>
                <p style="color: var(--ink-600);">Explore our services or schedule a consult with our care team.</p>
            </div>
            <div class="auth-buttons">
                <a href="services.html" class="btn btn-login">View Services</a>
                <a href="signup.php" class="btn btn-signup">Book a Visit</a>
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

    <script>
        const year = new Date().getFullYear();
        document.getElementById('footer-year').textContent = `© ${year} Pamper Your Pet. Excellence in Veterinary Care.`;
    </script>
</body>
</html>









