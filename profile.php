<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$title = 'Profile';
$pdo = getDatabaseConnection();
$user = currentUser();

if (!$user) {
    redirect('login.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        // Validate
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Invalid email address.');
        } else {
            // Update user
            $params = [
                ':email' => $email,
                ':name' => $name,
                ':id' => $user['id'],
            ];
            $sql = 'UPDATE users SET email = :email, name = :name';
            if ($password !== '') {
                $hashed = hash('sha256', $password);
                $sql .= ', password = :password';
                $params[':password'] = $hashed;
            }
            $sql .= ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            // Update session
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['name'] = $name;
            setFlash('success', 'Profile updated successfully.');
            // Refresh user variable
            $user = currentUser();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<h3>Your Profile</h3>
<form method="post" class="mt-3" action="">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" value="<?= e($user['username']) ?>" disabled>
    </div>
    <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="name" name="name" value="<?= e($user['name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= e($user['email'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">New Password (leave blank to keep unchanged)</label>
        <input type="password" class="form-control" id="password" name="password">
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>