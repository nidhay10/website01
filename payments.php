<?php
session_start();
require "db.php";

$errors = [];
$payment_method = $_POST["payment_method"] ?? "";
$upi_id = trim($_POST["upi_id"] ?? "");
$card_name = trim($_POST["card_name"] ?? "");
$card_number = trim($_POST["card_number"] ?? "");
$card_expiry = trim($_POST["card_expiry"] ?? "");
$card_cvv = trim($_POST["card_cvv"] ?? "");
$card_otp = trim($_POST["card_otp"] ?? "");
$demo_otp = $_SESSION["demo_payment_otp"] ?? "";

if (empty($demo_otp)) {
    $_SESSION["demo_payment_otp"] = (string) rand(100000, 999999);
    $demo_otp = $_SESSION["demo_payment_otp"];
}

function add_error(&$errors, $key, $message) {
    if (empty($errors[$key])) {
        $errors[$key] = $message;
    }
}

if (isset($_POST["pay_btn"])) {
    if ($payment_method === "") {
        add_error($errors, "payment_method", "Please choose a payment method.");
    }

    if ($payment_method === "upi") {
        if ($upi_id === "") {
            add_error($errors, "upi_id", "UPI ID is required.");
        }
    }

    if ($payment_method === "card") {
        if ($card_name === "") {
            add_error($errors, "card_name", "Cardholder name is required.");
        }
        $clean_card = preg_replace("/\D+/", "", $card_number);
        if ($clean_card === "" || strlen($clean_card) < 12) {
            add_error($errors, "card_number", "Please enter a valid card number.");
        }
        if ($card_expiry === "") {
            add_error($errors, "card_expiry", "Expiry is required.");
        }
        if ($card_cvv === "" || !preg_match("/^\d{3,4}$/", $card_cvv)) {
            add_error($errors, "card_cvv", "Please enter a valid CVV.");
        }

        if (empty($errors)) {
            if (empty($_SESSION["demo_payment_otp"])) {
                $_SESSION["demo_payment_otp"] = (string) rand(100000, 999999);
            }
            $demo_otp = $_SESSION["demo_payment_otp"];
            if ($card_otp === "") {
                add_error($errors, "card_otp", "Enter the OTP sent to your phone.");
            } elseif ($card_otp !== $demo_otp) {
                add_error($errors, "card_otp", "Invalid OTP. Please try again.");
            } else {
                unset($_SESSION["demo_payment_otp"]);
            }
        }
    }

    if (empty($errors)) {
        header("Location: payment-success.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Pamper Your Pet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.12);
            --shadow-card: 0 10px 25px rgba(15, 23, 42, 0.08);
            --radius-lg: 22px;
            --radius-md: 16px;
            --radius-sm: 12px;
            --error: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at 15% 20%, #eff6ff 0%, #f8fafc 45%, #ffffff 100%);
            color: var(--ink-900);
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

        .payment-section {
            padding: 28px 0 36px;
        }

        .payment-container {
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

        .payment-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 24px;
        }

        .section-title {
            margin-bottom: 12px;
            color: var(--brand-900);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }

        .subtitle {
            color: var(--ink-600);
            margin-bottom: 18px;
        }

        .amount {
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 14px;
            font-weight: 700;
            color: var(--brand-700);
            margin-bottom: 18px;
        }

        .form-group { margin-bottom: 16px; }

        label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 6px;
            color: var(--ink-700);
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface-alt);
            font-size: 0.95rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, select:focus {
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

        .helper-text {
            color: var(--ink-500);
            font-size: 0.8rem;
            margin-top: 6px;
            display: block;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
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

        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .main-nav ul { display: none; }
            .page-header h1 { font-size: 2rem; }
            .payment-container { grid-template-columns: 1fr; }
            .summary-panel { order: 2; }
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
    <header>
        <div class="container nav-wrapper">
            <a href="pet-parent.php" class="brand">
                <i class="fa-solid fa-paw"></i> PAMPER YOUR PET
            </a>

            <nav class="main-nav">
                <ul>
                    <li><a href="pet-parent.php">Services</a></li>
                    <li><a href="book-appointment.php">Book Appointment</a></li>
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
            <div class="breadcrumb">Home / Payment</div>
            <h1>Complete Payment</h1>
        </div>
    </section>

    <main class="payment-section">
        <div class="container">
            <div class="payment-container">
                <aside class="summary-panel">
                    <div class="summary-card">
                        <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> Payment Summary</h3>
                        <div class="summary-list">
                            <div class="summary-item">
                                <span class="summary-label">Booking Charge</span>
                                <span class="summary-value">Rs 250</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Taxes</span>
                                <span class="summary-value">Included</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total</span>
                                <span class="summary-value">Rs 250</span>
                            </div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <h3 class="section-title"><i class="fa-solid fa-shield-heart"></i> Secure Payment</h3>
                        <p class="subtitle">Your payment is processed securely. Choose UPI or card to continue.</p>
                    </div>
                </aside>

                <div class="payment-card">
                    <h3 class="section-title"><i class="fa-solid fa-credit-card"></i> Payment Details</h3>
                    <p class="subtitle">Confirm your booking and pay the fee below.</p>

                    <div class="amount">Booking Charge: Rs 250</div>

                    <form method="POST" action="payments.php">
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" id="paymentMethod" class="<?php echo !empty($errors["payment_method"]) ? "input-error" : ""; ?>" required>
                                <option value="">Choose method...</option>
                                <option value="upi" <?php echo $payment_method === "upi" ? "selected" : ""; ?>>UPI</option>
                                <option value="card" <?php echo $payment_method === "card" ? "selected" : ""; ?>>Card</option>
                            </select>
                            <span class="error-msg"><?php echo $errors["payment_method"] ?? ""; ?></span>
                        </div>

                        <div id="upiFields" style="display: none;">
                            <div class="form-group">
                                <label>UPI ID *</label>
                                <input type="text" name="upi_id" value="<?php echo htmlspecialchars($upi_id); ?>" class="<?php echo !empty($errors["upi_id"]) ? "input-error" : ""; ?>" placeholder="name@bank">
                                <span class="error-msg"><?php echo $errors["upi_id"] ?? ""; ?></span>
                            </div>
                        </div>

                    <div id="cardFields" style="display: none;">
                        <div class="form-group">
                            <label>Cardholder Name *</label>
                            <input type="text" name="card_name" value="<?php echo htmlspecialchars($card_name); ?>" class="<?php echo !empty($errors["card_name"]) ? "input-error" : ""; ?>" placeholder="Name on card">
                            <span class="error-msg"><?php echo $errors["card_name"] ?? ""; ?></span>
                        </div>

                            <div class="form-group">
                                <label>Card Number *</label>
                                <input type="text" name="card_number" value="<?php echo htmlspecialchars($card_number); ?>" class="<?php echo !empty($errors["card_number"]) ? "input-error" : ""; ?>" placeholder="1234 5678 9012 3456" maxlength="19">
                                <span class="error-msg"><?php echo $errors["card_number"] ?? ""; ?></span>
                            </div>

                            <div class="grid">
                                <div class="form-group">
                                    <label>Expiry (MM/YY) *</label>
                                    <input type="text" name="card_expiry" value="<?php echo htmlspecialchars($card_expiry); ?>" class="<?php echo !empty($errors["card_expiry"]) ? "input-error" : ""; ?>" placeholder="MM/YY">
                                    <span class="error-msg"><?php echo $errors["card_expiry"] ?? ""; ?></span>
                                </div>
                                <div class="form-group">
                                    <label>CVV *</label>
                                    <input type="password" name="card_cvv" value="<?php echo htmlspecialchars($card_cvv); ?>" class="<?php echo !empty($errors["card_cvv"]) ? "input-error" : ""; ?>" placeholder="123" maxlength="4">
                                    <span class="error-msg"><?php echo $errors["card_cvv"] ?? ""; ?></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>OTP *</label>
                            <input type="text" name="card_otp" value="<?php echo htmlspecialchars($card_otp); ?>" class="<?php echo !empty($errors["card_otp"]) ? "input-error" : ""; ?>" placeholder="Enter 6-digit OTP" maxlength="6" inputmode="numeric">
                            <?php if (!empty($demo_otp)) { ?>
                                <span class="helper-text">Demo OTP: <?php echo htmlspecialchars($demo_otp); ?></span>
                            <?php } ?>
                            <span class="error-msg"><?php echo $errors["card_otp"] ?? ""; ?></span>
                        </div>
                    </div>

                        <button type="submit" name="pay_btn" class="submit-btn">Pay Rs 250</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        const paymentMethod = document.getElementById("paymentMethod");
        const upiFields = document.getElementById("upiFields");
        const cardFields = document.getElementById("cardFields");

        function togglePaymentFields() {
            const method = paymentMethod.value;
            if (method === "upi") {
                upiFields.style.display = "block";
                cardFields.style.display = "none";
            } else if (method === "card") {
                upiFields.style.display = "none";
                cardFields.style.display = "block";
            } else {
                upiFields.style.display = "none";
                cardFields.style.display = "none";
            }
        }

        if (paymentMethod) {
            paymentMethod.addEventListener("change", togglePaymentFields);
            togglePaymentFields();
        }
    </script>
</body>
</html>

