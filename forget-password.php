<?php
session_start();
require "db.php";

use PHPMailer\PHPMailer\PHPMailer;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$message="";

if(isset($_POST['email'])){

$email = mysqli_real_escape_string($conn, $_POST['email']);

$check=$conn->query("SELECT * FROM users WHERE email='$email'");

if($check->num_rows==1){

$otp=rand(100000,999999);

$_SESSION['otp']=$otp;
$_SESSION['reset_email']=$email;

$mail=new PHPMailer(true);

$mail->isSMTP();
$mail->Host='smtp.gmail.com';
$mail->SMTPAuth=true;

$mail->Username='nidhay.masani@gmail.com';
$mail->Password='ixyftfmxdjkiwsii';

$mail->SMTPSecure='tls';
$mail->Port=587;

$mail->setFrom('nidhay.masani@gmail.com','Pamper Your Pet');
$mail->addAddress($email);

$mail->Subject='Password Reset OTP';
$mail->Body="Your OTP is: $otp";

$mail->SMTPDebug = 2;
$mail->Debugoutput = 'html';

$mail->send();

header("Location: verify-otp.php");
exit();

}else{
$message="Email not registered";
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Pamper Your Pet</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }

        body {
            color: var(--ink-900);
            background: radial-gradient(circle at 20% 20%, #eff6ff 0%, #f8fafc 45%, #ffffff 100%);
            min-height: 100vh;
        }

        a { color: inherit; }

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

        .nav-links a:hover, .nav-links a.active { color: var(--brand-700); }

        .auth-buttons { display: flex; gap: 10px; }

        .btn {
            padding: 10px 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-login { background-color: transparent; color: var(--brand-900); border: 1px solid var(--border); }
        .btn-signup { background-color: var(--brand-700); color: #ffffff; box-shadow: 0 10px 20px rgba(31, 77, 120, 0.25); }
        .btn:hover { transform: translateY(-1px); }

        .auth-layout {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 28px;
            align-items: stretch;
            margin: 18px auto 40px;
        }

        .auth-hero {
            background: linear-gradient(135deg, rgba(15, 43, 70, 0.95), rgba(31, 77, 120, 0.88)),
                url('https://images.unsplash.com/photo-1508672019048-805c876b67e2?auto=format&fit=crop&w=1200&q=80') center/cover;
            color: #ffffff;
            border-radius: 22px;
            padding: 28px;
            display: grid;
            gap: 16px;
            min-height: 420px;
        }

        .auth-hero h1 { font-family: 'Playfair Display', serif; font-size: 2.4rem; }
        .auth-hero p { opacity: 0.9; line-height: 1.6; }

        .hero-list { display: grid; gap: 10px; font-size: 0.95rem; }
        .hero-list li { list-style: none; background: rgba(255, 255, 255, 0.14); padding: 10px 12px; border-radius: 10px; }

        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        }

        .form-header h2 { font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 6px; }
        .form-header p { color: var(--ink-600); margin-bottom: 18px; }

        .form-group { margin-bottom: 16px; }
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

        .error-banner {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.9rem;
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
        }

        .submit-btn:hover { background-color: #173c5b; }

        .form-footer {
            margin-top: 14px;
            text-align: center;
            color: var(--ink-600);
            font-size: 0.9rem;
        }

        .form-footer a { color: var(--brand-700); text-decoration: none; font-weight: 600; }

        footer {
            background-color: #0b1c2e;
            color: #ffffff;
            padding: 24px 0;
            margin-top: 40px;
        }

        .footer-links { display: flex; justify-content: center; gap: 20px; margin-bottom: 10px; }
        .footer-links a { color: #94a3b8; text-decoration: none; }
        .copyright { color: #64748b; font-size: 0.8rem; }

        @media (max-width: 900px) {
            .auth-layout { grid-template-columns: 1fr; }
            .auth-hero { min-height: 320px; }
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

        <div class="auth-layout">
            <section class="auth-hero">
                <div>
                    <span class="section-tag">Password Recovery</span>
                    <h1>Securely reset your account.</h1>
                    <p>We will send a one-time code to verify your identity and help you set a new password.</p>
                </div>
                <ul class="hero-list">
                    <li>One-time verification code sent to email</li>
                    <li>Fast, secure, and private recovery process</li>
                    <li>Support available if you need help</li>
                </ul>
            </section>

            <section class="form-card">
                <div class="form-header">
                    <h2>Forgot Password</h2>
                    <p>Enter your email address to receive an OTP.</p>
                </div>

                <?php if($message!=""){ echo "<div class='error-banner'>$message</div>"; } ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                    <button type="submit" class="submit-btn">Send OTP</button>
                </form>

                <div class="form-footer">
                    Remembered your password? <a href="login.php">Log in</a>
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
</body>
</html>



