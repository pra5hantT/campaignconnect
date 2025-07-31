<?php
/**
 * Authentication helper functions
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Attempt to authenticate user with username and password
 */
function loginUser(string $username, string $password): bool
{
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();
    if ($user) {
        // Compare SHA256 hashed password
        $hashedInput = hash('sha256', $password);
        if (hash_equals($user['password'], $hashedInput)) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            session_regenerate_id(true);
            logAudit($user['id'], 'Logged in');
            return true;
        }
    }
    return false;
}

/**
 * Log out current user
 */
function logoutUser(): void
{
    if (isset($_SESSION['user'])) {
        logAudit($_SESSION['user']['id'], 'Logged out');
    }
    $_SESSION = [];
    // Destroy session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Get currently logged-in user's data
 */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}
