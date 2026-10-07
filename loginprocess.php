<?php

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once  'config/database.php';
require_once  'config/activity_log.php';

date_default_timezone_set('Asia/Manila');

// Send error messages back to the login page.
function loginError($message)
{
    $_SESSION['login_error'] = $message;

    header("Location: login.php");
    exit();
}

// ========================================
// LOGIN LIMIT SETTINGS
// ========================================

$maxAttempts = 5;
$lockDuration = 5 * 60;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (!is_string($username) || !is_string($password)) {
    loginError("Invalid login request.");
}

$username = trim($username);

if ($username === '' || $password === '') {
    loginError("Username and password are required.");
}

$transactionOpen = false;

try {

    // ========================================
    // FIND USER
    // ========================================

    $stmt = $conn->prepare("
        SELECT UserID, Username, Password, Role, Active
        FROM users
        WHERE Username = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $attemptIdentity = $user
        ? 'user:' . $user['UserID']
        : 'username:' . strtolower($username);

    $attemptKey = hash('sha256', $attemptIdentity);

    // ========================================
    // GET LOGIN ATTEMPTS
    // ========================================

    $conn->begin_transaction();
    $transactionOpen = true;

    $stmt = $conn->prepare("
        INSERT INTO login_attempts (AttemptKey)
        VALUES (?)
        ON DUPLICATE KEY UPDATE AttemptKey = VALUES(AttemptKey)
    ");

    $stmt->bind_param("s", $attemptKey);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT FailedAttempts, LockedUntil
        FROM login_attempts
        WHERE AttemptKey = ?
        FOR UPDATE
    ");

    $stmt->bind_param("s", $attemptKey);
    $stmt->execute();

    $attempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $now = time();
    $failedAttempts = (int) $attempt['FailedAttempts'];
    $lockedUntil = (int) $attempt['LockedUntil'];

    // ========================================
    // CHECK IF LOGIN IS LOCKED
    // ========================================

    if ($lockedUntil > $now) {

        $remainingMinutes = (int) ceil(
            ($lockedUntil - $now) / 60
        );

        $conn->commit();
        $transactionOpen = false;

        loginError(
            "Too many failed attempts. Please try again in " .
            $remainingMinutes .
            " minute(s)."
        );
    }

    // Reset the count after the lockout expires.
    if ($lockedUntil > 0) {
        $failedAttempts = 0;
    }

    // ========================================
    // CHECK PASSWORD
    // ========================================

    $dummyHash =
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';

    $passwordMatches = password_verify(
        $password,
        $user ? $user['Password'] : $dummyHash
    );

    $loginValid = $user
        && (int) $user['Active'] === 1
        && $passwordMatches;

    // ========================================
    // FAILED LOGIN
    // ========================================

    if (!$loginValid) {

        $failedAttempts++;

        $lockedUntil = $failedAttempts >= $maxAttempts
            ? $now + $lockDuration
            : 0;

        $stmt = $conn->prepare("
            UPDATE login_attempts
            SET FailedAttempts = ?, LockedUntil = ?
            WHERE AttemptKey = ?
        ");

        $stmt->bind_param(
            "iis",
            $failedAttempts,
            $lockedUntil,
            $attemptKey
        );

        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $transactionOpen = false;

        logActivity(
            $conn,
            $user ? $user['UserID'] : NULL,
            $user ? $user['Username'] : $username,
            $user ? $user['Role'] : "unknown",
            $lockedUntil > 0
                ? "Failed login - locked for 5 minutes"
                : "Failed login attempt"
        );

        if ($lockedUntil > 0) {
            loginError(
                "Too many failed attempts. " .
                "Login is temporarily locked for 5 minutes."
            );
        }

        $remainingAttempts = $maxAttempts - $failedAttempts;

        loginError(
            "Invalid username or password. " .
            $remainingAttempts .
            " attempt(s) remaining."
        );
    }

    // ========================================
    // CHECK USER ROLE
    // ========================================

    $destinations = [
        'superadmin' => 'superadmin/dashboard.php',
        'admin' => 'admin/dashboard.php',
        'staff' => 'staff/dashboard.php'
    ];

    if (!isset($destinations[$user['Role']])) {

        $conn->rollback();
        $transactionOpen = false;

        loginError(
            "This account cannot log in. Contact your administrator."
        );
    }

    // ========================================
    // RESET ATTEMPTS AFTER SUCCESSFUL LOGIN
    // ========================================

    $stmt = $conn->prepare("
        UPDATE login_attempts
        SET FailedAttempts = 0, LockedUntil = 0
        WHERE AttemptKey = ?
    ");

    $stmt->bind_param("s", $attemptKey);
    $stmt->execute();
    $stmt->close();

    // ========================================
    // REMEMBER ME
    // ========================================

    if ($remember) {

        $rememberToken = bin2hex(random_bytes(32));

        $stmt = $conn->prepare("
            UPDATE users
            SET RememberToken = ?
            WHERE UserID = ?
        ");

        $stmt->bind_param(
            "si",
            $rememberToken,
            $user['UserID']
        );

        $stmt->execute();
        $stmt->close();
    }

    // ========================================
    // UPDATE LAST ACTIVITY
    // ========================================

    $stmt = $conn->prepare("
        UPDATE users
        SET LastActivity = NOW()
        WHERE UserID = ?
    ");

    $stmt->bind_param("i", $user['UserID']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $transactionOpen = false;

    // ========================================
    // ACTIVITY LOG
    // ========================================

    logActivity(
        $conn,
        $user['UserID'],
        $user['Username'],
        $user['Role'],
        "Logged in"
    );

    // ========================================
    // CREATE LOGIN SESSION
    // ========================================

    session_regenerate_id(true);

    unset($_SESSION['login_error']);

    $_SESSION['id'] = $user['UserID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['Role'];

    if ($remember) {

        $isHttps = !empty($_SERVER['HTTPS'])
            && strtolower($_SERVER['HTTPS']) !== 'off';

        setcookie(
            "remember_token",
            $rememberToken,
            [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    header("Location: " . $destinations[$user['Role']]);
    exit();

} catch (Throwable $error) {

    if ($transactionOpen) {
        $conn->rollback();
    }

    error_log("ParkZen login error: " . $error->getMessage());

    loginError(
        "Unable to process login right now. Please try again later."
    );
}