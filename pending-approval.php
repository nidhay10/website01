<?php
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Pending | Pamper Your Pet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-900: #0f2b46;
            --brand-700: #1f4d78;
            --accent-400: #f2b48d;
            --ink-900: #0f172a;
            --ink-600: #475569;
            --surface: #ffffff;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'DM Sans', sans-serif;
        }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at 20% 20%, #eff6ff 0%, #f8fafc 45%, #ffffff 100%);
            color: var(--ink-900);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: min(520px, 100%);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            text-align: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #fff4ec;
            color: var(--brand-700);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 16px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 12px;
        }

        p {
            color: var(--ink-600);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 999px;
            background: var(--brand-700);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 12px 24px rgba(31, 77, 120, 0.25);
        }

        .helper {
            margin-top: 14px;
            font-size: 0.9rem;
            color: var(--ink-600);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">Approval Pending</div>
        <h1>Your vet profile is under review.</h1>
        <p>Thanks for signing up! Our admin team is verifying your registration details. You will be able to log in once your account is approved.</p>
        <a class="cta" href="login.php">Go to Login</a>
        <div class="helper">If you have updates to your details, please contact the admin.</div>
    </div>
</body>
</html>
