<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment सफल | Pamper Your Pet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <meta http-equiv="refresh" content="5;url=pet-parent-dashboard.php">
    <style>
        :root {
            --brand-900: #0f2b46;
            --brand-700: #1f4d78;
            --brand-500: #2f7ec7;
            --accent-400: #f2b48d;
            --ink-900: #0f172a;
            --ink-600: #475569;
            --surface: #ffffff;
            --surface-alt: #f7f9fc;
            --border: #e2e8f0;
            --shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            --radius-lg: 22px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'DM Sans', sans-serif; }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at 15% 20%, #eff6ff 0%, #f8fafc 45%, #ffffff 100%);
            color: var(--ink-900);
            padding: 24px;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            max-width: 520px;
            width: 100%;
            padding: 28px;
            text-align: center;
        }

        .badge {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--brand-500), var(--accent-400));
            display: grid;
            place-items: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0 auto 16px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 8px;
        }

        p {
            color: var(--ink-600);
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .timer {
            font-weight: 700;
            color: var(--brand-700);
        }

        .link {
            display: inline-block;
            margin-top: 10px;
            color: var(--brand-700);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">✓</div>
        <h1>Payment Successful</h1>
        <p>Your appointment is confirmed. You will be redirected to your dashboard shortly.</p>
        <p>Redirecting in <span class="timer" id="timer">5</span> seconds…</p>
        <a class="link" href="pet-parent-dashboard.php">Go now</a>
    </div>

    <script>
        const timerEl = document.getElementById('timer');
        let remaining = 5;
        const interval = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(interval);
                window.location.href = 'pet-parent-dashboard.php';
                return;
            }
            timerEl.textContent = remaining;
        }, 1000);
    </script>
</body>
</html>
