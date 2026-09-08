<?php

require "db.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){

$role = $_POST['role'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

if($password !== $confirm_password){
    die("Passwords do not match");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);


/* PET PARENT SIGNUP */
if($role == "pet_parent"){

$name = $_POST['name'];
$username = $_POST['username'];

$sql = "INSERT INTO users(name,email,username,password,role,status)
VALUES('$name','$email','$username','$hashedPassword','pet_parent','approved')";

}


/* VET SIGNUP */
if($role == "vet"){

$clinic_name = $_POST['clinic_name'];
$full_name = $_POST['full_name'];
$username = $_POST['username'];
$vet_registration = $_POST['vet_registration'];
$clinic_address = $_POST['clinic_address'];

$sql = "INSERT INTO users
(name,email,username,password,role,status,clinic_name,vet_registration,clinic_address)
VALUES
('$full_name','$email','$username','$hashedPassword','vet','pending','$clinic_name','$vet_registration','$clinic_address')";

}


/* EXECUTE QUERY */

if($conn->query($sql)){
    if ($role === "vet") {
        header("Location: pending-approval.php");
    } else {
        header("Location: login.php");
    }
    exit();
}
else{
echo "Error: " . $conn->error;
}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Pamper Your Pet</title>
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
            --error: #ef4444;
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

        a { color: inherit; }

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

        .btn:hover { transform: translateY(-1px); }

        .signup-layout {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 28px;
            align-items: stretch;
            margin: 18px auto 40px;
        }

        .signup-hero {
            background: linear-gradient(135deg, rgba(15, 43, 70, 0.95), rgba(31, 77, 120, 0.88)),
                url('https://images.unsplash.com/photo-1508672019048-805c876b67e2?auto=format&fit=crop&w=1200&q=80') center/cover;
            color: #ffffff;
            border-radius: 22px;
            padding: 28px;
            display: grid;
            gap: 16px;
            min-height: 520px;
        }

        .signup-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
        }

        .signup-hero p {
            opacity: 0.9;
            line-height: 1.6;
        }

        .hero-list {
            display: grid;
            gap: 10px;
            font-size: 0.95rem;
        }

        .hero-list li {
            list-style: none;
            background: rgba(255, 255, 255, 0.14);
            padding: 10px 12px;
            border-radius: 10px;
        }

        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        }

        .form-header {
            margin-bottom: 18px;
        }

        .form-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 6px;
        }

        .form-header p {
            color: var(--ink-600);
        }

        .tabs {
            display: flex;
            background: var(--surface-alt);
            padding: 6px;
            border-radius: 12px;
            margin-bottom: 18px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            color: var(--ink-600);
            font-weight: 600;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .tab-btn.active {
            background-color: #ffffff;
            color: var(--brand-700);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            color: var(--ink-600);
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px rgba(47, 126, 199, 0.15);
        }

        .form-group input.error {
            border-color: var(--error);
            background-color: #fef2f2;
        }

        .error-text {
            color: var(--error);
            font-size: 0.8rem;
            margin-top: 4px;
            display: none;
        }

        .form-group.has-error .error-text {
            display: block;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--brand-700);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 6px;
        }

        .submit-btn:hover { background-color: #173c5b; }

        .form-footer {
            margin-top: 14px;
            text-align: center;
            color: var(--ink-600);
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--brand-700);
            text-decoration: none;
            font-weight: 600;
        }

        footer {
            background-color: #0b1c2e;
            color: #ffffff;
            padding: 24px 0;
            margin-top: 40px;
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
            .signup-layout { grid-template-columns: 1fr; }
            .signup-hero { min-height: 320px; }
        }

        @media (max-width: 640px) {
            .nav-links { display: none; }
            .form-card { padding: 20px; }
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
                <li><a href="about.php">About</a></li>
</ul>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-login">Login</a>
                <a href="signup.php" class="btn btn-signup">Sign Up</a>
            </div>
        </nav>

        <div class="signup-layout">
            <section class="signup-hero">
                <div>
                    <span class="section-tag">Join Our Care Network</span>
                    <h1>Build a healthier life for every companion.</h1>
                    <p>Pet parents and veterinarians trust us for modern diagnostics, compassionate care, and clear communication.</p>
                </div>
                <ul class="hero-list">
                    <li>Personalized care plans and wellness tracking</li>
                    <li>Digital records and appointment reminders</li>
                    <li>Specialist referrals and post-visit support</li>
                </ul>
            </section>

            <section class="form-card">
                <div class="form-header">
                    <h2>Create Account</h2>
                    <p>Choose your role to get started.</p>
                </div>

                <div class="tabs">
                    <button class="tab-btn active" id="btn-pet-parent" onclick="switchTab('pet-parent')">Pet Parent</button>
                    <button class="tab-btn" id="btn-vet" onclick="switchTab('veterinarian')">Veterinarian</button>
                </div>

                <form method="POST" action="" id="pet-parent-form" onsubmit="return validateForm('pet-parent')">
                    <div class="form-group" style="display:none;">
                        <input type="hidden" name="role" value="pet_parent">
                    </div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="pp-name" name="name" placeholder="Full name">
                        <div class="error-text">Name is required.</div>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="pp-email" name="email" placeholder="Email">
                        <div class="error-text">Please enter a valid email.</div>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="pp-username" name="username" placeholder="Choose a username">
                        <div class="error-text">Username is required.</div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="pp-pass" name="password" placeholder="Create password">
                        <div class="error-text">Password must be at least 6 characters.</div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" id="pp-confirm" name="confirm_password" placeholder="Confirm password">
                        <div class="error-text">Does not match with the password.</div>
                    </div>

                    <button type="submit" class="submit-btn">Create Account</button>
                </form>

                <form method="POST" action="" id="vet-form" style="display:none;" onsubmit="return validateForm('veterinarian')">
                    <div class="form-group" style="display:none;">
                        <input type="hidden" name="role" value="vet">
                    </div>

                    <div class="form-group">
                        <label>Doctor Name</label>
                        <input type="text" id="vet-full-name" name="full_name" placeholder="Dr. full name">
                        <div class="error-text">Dr name is required.</div>
                    </div>

                    <div class="form-group">
                        <label>Clinic Name</label>
                        <input type="text" id="vet-name" name="clinic_name" placeholder="Clinic Name">
                        <div class="error-text">Name is required.</div>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="vet-email" name="email" placeholder="Email">
                        <div class="error-text">Please enter a valid email.</div>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="vet-username" name="username" placeholder="Choose a username">
                        <div class="error-text">Username is required.</div>
                    </div>

                    <div class="form-group">
                        <label>Veterinarian Registration No.</label>
                        <input type="text" id="vet-reg" name="vet_registration" placeholder="License Number">
                        <div class="error-text">Registration number is required.</div>
                    </div>

                    <div class="form-group">
                        <label>Clinic Address</label>
                        <input type="text" id="vet-address" name="clinic_address" placeholder="Clinic Address">
                        <div class="error-text">Address is required.</div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="vet-pass" name="password" placeholder="Create password">
                        <div class="error-text">Password must be at least 6 characters.</div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" id="vet-confirm" name="confirm_password" placeholder="Confirm password">
                        <div class="error-text">Does not match with the password.</div>
                    </div>

                    <button type="submit" class="submit-btn">Create Account</button>
                </form>

                <div class="form-footer">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
            </section>
        </div>
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
        function switchTab(role) {
            const parentBtn = document.getElementById('btn-pet-parent');
            const vetBtn = document.getElementById('btn-vet');
            const parentForm = document.getElementById('pet-parent-form');
            const vetForm = document.getElementById('vet-form');

            if (role === 'pet-parent') {
                parentBtn.classList.add('active');
                vetBtn.classList.remove('active');
                parentForm.style.display = 'block';
                vetForm.style.display = 'none';
            } else {
                parentBtn.classList.remove('active');
                vetBtn.classList.add('active');
                parentForm.style.display = 'none';
                vetForm.style.display = 'block';
            }
        }

        function validateForm(type) {
            let isValid = true;
            const setError = (id, isError) => {
                const el = document.getElementById(id);
                const group = el.parentElement;
                if (isError) {
                    el.classList.add('error');
                    group.classList.add('has-error');
                    return false;
                } else {
                    el.classList.remove('error');
                    group.classList.remove('has-error');
                    return true;
                }
            };

            if (type === 'pet-parent') {
                if(document.getElementById('pp-name').value.trim() === "") isValid = setError('pp-name', true); else setError('pp-name', false);
                const email = document.getElementById('pp-email').value;
                if(email === "" || !email.includes('@')) isValid = setError('pp-email', true); else setError('pp-email', false);
                if(document.getElementById('pp-username').value.trim() === "") isValid = setError('pp-username', true); else setError('pp-username', false);
                const pass = document.getElementById('pp-pass').value;
                if(pass.length < 6) isValid = setError('pp-pass', true); else setError('pp-pass', false);
                const confirm = document.getElementById('pp-confirm').value;
                if(pass !== confirm) isValid = setError('pp-confirm', true); else setError('pp-confirm', false);
            } else if (type === 'veterinarian') {
                if(document.getElementById('vet-full-name').value.trim() === "") isValid = setError('vet-full-name', true); else setError('vet-full-name', false);
                if(document.getElementById('vet-name').value.trim() === "") isValid = setError('vet-name', true); else setError('vet-name', false);
                const email = document.getElementById('vet-email').value;
                if(email === "" || !email.includes('@')) isValid = setError('vet-email', true); else setError('vet-email', false);
                if(document.getElementById('vet-username').value.trim() === "") isValid = setError('vet-username', true); else setError('vet-username', false);
                if(document.getElementById('vet-reg').value.trim() === "") isValid = setError('vet-reg', true); else setError('vet-reg', false);
                if(document.getElementById('vet-address').value.trim() === "") isValid = setError('vet-address', true); else setError('vet-address', false);
                const pass = document.getElementById('vet-pass').value;
                if(pass.length < 6) isValid = setError('vet-pass', true); else setError('vet-pass', false);
                const confirm = document.getElementById('vet-confirm').value;
                if(pass !== confirm) isValid = setError('vet-confirm', true); else setError('vet-confirm', false);
            }

            if (isValid) {
                if (type === 'veterinarian') {
                    alert("Registration submitted! Your profile will be reviewed for approval.");
                } else {
                    alert("Registration Successful! Redirecting to Login...");
                }
                return true;
            } else {
                return false;
            }
        }
    </script>
</body>
</html>






