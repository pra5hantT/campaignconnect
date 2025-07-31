<?php
/**
 * Common helper functions for CampaignConnect
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Sanitize output to prevent XSS
 */
function e($string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a secure CSRF token and store it in session
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST data
 */
function verifyCsrfToken(): bool
{
    return isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Flash message helper: set message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Get flash messages and clear them
 */
function getFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Redirect to given URL
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Format date/time in a user-friendly way
 */
function formatDateTime(?string $datetime): string
{
    if (empty($datetime)) {
        return '';
    }
    return date('Y-m-d H:i', strtotime($datetime));
}

/**
 * Check if current user has role superadmin
 */
function isSuperAdmin(): bool
{
    return isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == ROLE_SUPERADMIN;
}

/**
 * Check if current user has at least admin role
 */
function isAdmin(): bool
{
    $role = $_SESSION['user']['role_id'] ?? null;
    return $role == ROLE_SUPERADMIN || $role == ROLE_ADMIN;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user']);
}

/**
 * Ensure the user is logged in, otherwise redirect to login
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Save an audit log entry
 */
function logAudit(int $userId, string $action): void
{
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, created_at) VALUES (:user_id, :action, NOW())');
    $stmt->execute([
        ':user_id' => $userId,
        ':action'  => $action,
    ]);
}
