<?php
session_start();

// Define usernames and plain passwords for your gates
$users = [
    'gate2_user' => 'gate2pass123',  // Credentials for Gate 2 verification desk
    'admin_user' => 'adminpass123'    // Credentials for Admin command suite
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($users[$username]) && $users[$username] === $password) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = ($username === 'admin_user') ? 'admin' : 'gate2';
        
        // Redirect based on login credentials
        if ($_SESSION['role'] === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: gate2.php');
        }
        exit;
    } else {
        $error = 'Invalid username or password configuration.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VRS Gateway Login</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 0% 0%, #f5f3ff 0%, #f8fafc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .header-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="header-gradient">
        <h4 class="m-0 fw-bold">System Gate Authentication</h4>
    </div>
    <div class="p-4 bg-white">
        <?php if ($error): ?>
            <div class="alert alert-danger p-2 small"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Username</label>
                <input type="text" name="username" class="form-control" placeholder="e.g. gate2_user" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">Sign In</button>
        </form>
    </div>
</div>
</body>
</html>
