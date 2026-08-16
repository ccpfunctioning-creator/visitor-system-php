<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['username']);
$userRole = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VRS Portal Dashboard</title>
    <!-- Bootstrap 5 CDN for Premium Layout Sizing -->
    <link href="https://jsdelivr.net" rel="stylesheet">
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.4);
            --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 0% 0%, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            color: #1e293b;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: rgba(15, 23, 42, 0.95) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
        }
        .navbar-brand {
            font-weight: 700;
            background: linear-gradient(to right, #a78bfa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .beautiful-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            width: 100%;
            overflow: hidden;
        }
        .card-header-gradient {
            background: var(--primary-gradient);
            padding: 1.5rem;
            color: white;
        }
        .form-section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 700;
            margin: 1.5rem 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-section-title::after {
            content: '';
            flex-grow: 1;
            height: 1px;
            background: #e2e8f0;
        }
        .section-container {
            background: rgba(248, 250, 252, 0.6);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-gradient:hover {
            opacity: 0.95;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
            color: white;
        }
        /* 🚀 MASTER RESPONSIVE VIEWPORTS CENTERING GRID UTILITY */
        .master-viewport-center-wrapper {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 2rem 1rem;
        }
        .master-content-limiter {
            width: 100%;
            /* Automatically scales from mobile grids up to widescreen desk systems */
            max-width: 950px; 
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark p-3">
    <div class="container-fluid px-md-4">
        <a class="navbar-brand" href="index.php">VRS Gateway Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vrsNavbar" aria-controls="vrsNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="vrsNavbar">
            <div class="navbar-nav ms-auto gap-2 align-items-md-center mt-3 mt-md-0">
                <a class="nav-link text-white small" href="index.php">🏠 Gate 1 Entry</a>
                
                <?php if ($isLoggedIn): ?>
                    <?php if ($userRole === 'gate2' || $userRole === 'admin'): ?>
                        <a class="nav-link text-white small" href="gate2.php">👮 Gate 2 Desk</a>
                    <?php endif; ?>
                    <?php if ($userRole === 'admin'): ?>
                        <a class="nav-link text-white small" href="admin.php">📊 Admin Panel</a>
                        <a class="nav-link text-warning small fw-bold" href="manage_ban.php">🚫 Inmate Restrictions</a>
                    <?php endif; ?>
                    <span class="navbar-text text-secondary mx-md-2 small d-none d-md-inline">|</span>
                    <span class="navbar-text text-light small mb-2 mb-md-0">Signed in: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <a class="btn btn-sm btn-outline-danger px-3 py-1.5" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-light px-3 py-1.5" href="login.php">Internal Staff Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- 💡 AUTOMATED MASTER WRAPPER OPENING TAG: Centers any page layout executing next -->
<div class="master-viewport-center-wrapper">
    <div class="master-content-limiter">
