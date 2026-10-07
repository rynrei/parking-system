<?php

session_start();

$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

require_once "config/database.php";

// Total parking spaces
$parkingCountQuery = $conn->query("
    SELECT COUNT(*) AS total_spaces
    FROM parkingslots
");

$parkingCount = $parkingCountQuery->fetch_assoc()['total_spaces'];


// Active staff
$activeStaffQuery = $conn->query("
    SELECT COUNT(*) AS active_staff
    FROM users
    WHERE Role = 'staff'
      AND Active = 1
      AND LastActivity >= NOW() - INTERVAL 2 MINUTE
");

$activeStaff = $activeStaffQuery->fetch_assoc()['active_staff'];

$logFile = __DIR__ . "/uptime.log";

$uptime = "0.0%";

if (file_exists($logFile)) {

    $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $totalChecks = count($logs);
    $upChecks = 0;

    foreach ($logs as $log) {

        if (strpos($log, "| UP") !== false) {
            $upChecks++;
        }

    }

    if ($totalChecks > 0) {

        $uptimePercentage = ($upChecks / $totalChecks) * 100;

        $uptime = number_format($uptimePercentage, 1) . "%";

    }

} 

// ========================================
// REMEMBER ME LOGIN
// ========================================

if (!isset($_SESSION['id']) && isset($_COOKIE['remember_token'])) {

    $rememberToken = $_COOKIE['remember_token'];

    $stmt = $conn->prepare("
        SELECT UserID, Username, Role
        FROM users
        WHERE RememberToken = ?
          AND Active = 1
    ");

    $stmt->bind_param("s", $rememberToken);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        $_SESSION['id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['Role'];

        // Redirect based on role
        if ($user['Role'] === 'superadmin') {

            header("Location: superadmin/dashboard.php");

        } elseif ($user['Role'] === 'admin') {

            header("Location: admin/dashboard.php");

        } elseif ($user['Role'] === 'staff') {

            header("Location: staff/dashboard.php");

        }

        exit();
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking System Management - Login</title>
    <link rel="stylesheet" href="/pms/css/login.css?=v2">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>

    <div class="login-container">

        <!-- LEFT SIDE -->
        <div class="login-left">

            <!-- Decorative Elements -->
            <div class="circle circle-bottom"></div>
            <div class="orange-glow"></div>
            <div class="gray-glow"></div>
            <div class="circle circle-top"></div>

            <!-- System Status -->
            <div class="system-status">
                <div class="status-dot"></div>
                <div>2026 Operations · All Systems Active</div>
            </div>

            <!-- Parkzen Image -->
            <img
                id="parkzen-image"
                class="parkzen-image"
                src="images/parkzenlogo.svg"
                alt="Parkzen"
            >

            <!-- Parkzen Branding -->
            <h1 id="parkzen-title" class="parkzen-title">
                PARKZEN
            </h1>

            <p id="parkzen-subtitle" class="parkzen-subtitle">
                Smart parking, seamlessly managed.
            </p>

            <!-- Statistics -->
            <div class="statistics">

                <div class="stat">
                    <div class="stat-number">
                        <?php echo $parkingCount; ?>
                    </div>
                    <div class="stat-label">
                        PARKING SPACES
                    </div>
                </div>

                <div class="stat">
                    <div class="stat-number">
                        <?php echo $activeStaff; ?>
                    </div>
                    <div class="stat-label">
                        ACTIVE STAFF
                    </div>
                </div>

                <div class="stat">
                    <div class="stat-number"><?php echo $uptime; ?></div>
                    <div class="stat-label">
                        UPTIME
                    </div>
                </div>

            </div>

        </div>


        <!-- RIGHT SIDE -->
        <div class="login-right">

            <!-- Background Glow -->
            <div class="orange-glow-top"></div>
            <div class="orange-glow-bottom"></div>

            <!-- Login Content -->
            <div class="login-content">

                <h2 id="welcome-title">
                    Welcome back
                </h2>

                <!-- LOGIN FORM -->
                <form
                    id="login-form"
                    class="login-form"
                    action="loginprocess.php"
                    method="POST"
                >

                    <?php if ($loginError !== ''): ?>
                        <div class="login-error" role="alert">
                            <?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    

                    <!-- Username -->
                    <label
                        for="username"
                        class="form-label"
                        id="username-label"
                    >
                        USERNAME
                    </label>

                    <div class="input-group">

                        <div
                            id="username-icon"
                            class="user-icon"
                        ></div>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username or ID"
                            autocomplete="username"
                            required
                        >

                    </div>


                    <!-- Password -->
                    <label
                        for="password"
                        class="form-label password-label"
                        id="password-label"
                    >
                        PASSWORD
                    </label>

                    <div class="input-group">

                        <div
                            id="password-icon"
                            class="password-icon"
                        ></div>

                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                id="togglePassword"
                                class="password-toggle"
                                aria-controls="password"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                Show
                            </button>
                        </div>

                    </div>


                    <!-- Login Options -->
                    <div class="login-options">

                        <label
                            for="remember"
                            class="remember-me"
                        >

                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                            >

                            <span>
                                Remember me
                            </span>

                        </label>


                        <a
                            href="#"
                            id="forgot-password"
                            class="forgot-password"
                        >
                            Forgot password?
                        </a>

                    </div>


                    <!-- Login Button -->
                    <button
                        type="submit"
                        id="login-button"
                        class="login-button"
                    >
                        Log In
                    </button>

                </form>


                <!-- Secure Access -->
                <div
                    id="secure-access"
                    class="secure-access"
                >

                    <div class="secure-line-left"></div>

                    <span>
                        SECURE ACCESS
                    </span>

                    <div class="secure-line-right"></div>

                </div>


                <!-- Footer -->
                <div
                    id="login-footer"
                    class="login-footer"
                >

                    <p>
                        © 2026 Parkzen Parking Management System
                    </p>

                    <p>
                        All security logs are monitored and recorded.
                    </p>

                </div>

            </div>

        </div>


        <!-- Center Divider -->
        <div
            id="login-divider"
            class="login-divider"
        ></div>

    </div>

<script>
const passwordField = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");

togglePassword.addEventListener("click", () => {
    const showPassword = passwordField.type === "password";

    passwordField.type = showPassword ? "text" : "password";
    togglePassword.textContent = showPassword ? "Hide" : "Show";
    togglePassword.setAttribute(
        "aria-label",
        showPassword ? "Hide password" : "Show password"
    );
    togglePassword.setAttribute("aria-pressed", String(showPassword));
});
</script>

</body>
</html>