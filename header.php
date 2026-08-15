<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Visitor Registration System</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.4);
            --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 0% 0%, #f5f3ff 0%, #f8fafc 100%);
            min-height: 100vh;
            color: #1e293b;
        }
        .navbar {
            background: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .navbar-brand {
            font-weight: 700;
            background: linear-gradient(to right, #a78bfa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
        }
        .beautiful-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 680px;
            overflow: hidden;
        }
        .card-header-gradient {
            background: var(--primary-gradient);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .form-section-title {
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-section-title::after {
            content: '';
            flex-grow: 1;
            height: 1px;
            background: linear-gradient(to right, #e2e8f0, transparent);
        }
        .section-container {
            background: rgba(248, 250, 252, 0.6);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.875rem;
            border-radius: 14px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark p-3">
    <div class="container">
        <a class="navbar-brand" href="#">VRS Gateway (PHP)</a>
        <div class="navbar-nav ms-auto gap-2">
            <a class="nav-link text-white" href="index.php">Gate 1 Entry</a>
            <a class="nav-link text-white" href="gate2.php">Gate 2 Queue</a>
            <a class="nav-link text-white" href="admin.php">Admin Control</a>
        </div>
    </div>
</nav>
<div class="container main-container">
