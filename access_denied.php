<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
http_response_code(403);

// Fixed destinations based on the user's role.
$dashboards = [
    'superadmin' => '/pms/superadmin/dashboard.php',
    'admin' => '/pms/admin/dashboard.php',
    'staff' => '/pms/staff/dashboard.php'
];

$role = $_SESSION['role'] ?? '';

$destination = isset($_SESSION['id'])
    ? ($dashboards[$role] ?? '/pms/login.php')
    : '/pms/login.php';

$buttonText = $destination === '/pms/login.php'
    ? 'Go to Login'
    : 'Return to Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | ParkZen</title>

    <link rel="stylesheet" href="/pms/css/access_denied.css?v=1">
</head>

<body>
    <main class="denied-card">
        <div class="brand">PARKZEN</div>

        <div class="denied-icon" aria-hidden="true">
            <svg
                width="36"
                height="36"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <rect x="5" y="10" width="14" height="11" rx="2"/>
                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                <path d="M12 14v3"/>
            </svg>
        </div>

        <p class="error-code">ERROR 403</p>
        <h1>Access Denied</h1>

        <p class="denied-message">
            You don’t have permission to view this page.
            Return to your dashboard or contact your administrator
            if you need access.
        </p>

        <a
            href="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>"
            class="denied-button"
        >
            <?= htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8') ?>
        </a>

        <p class="denied-footer">ParkZen Parking Management System</p>
    </main>
</body>
</html>