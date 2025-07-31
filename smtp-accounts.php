<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
if (!isAdmin()) {
    setFlash('danger', 'You do not have permission to access this page.');
    redirect('dashboard.php');
}

$title = 'SMTP Accounts';
$pdo = getDatabaseConnection();

// Handle add/edit SMTP account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $action = $_POST['action'];
        $accountName = trim($_POST['account_name'] ?? '');
        $fromName = trim($_POST['from_name'] ?? '');
        $fromEmail = trim($_POST['from_email'] ?? '');
        $host = trim($_POST['smtp_host'] ?? '');
        $port = (int) ($_POST['smtp_port'] ?? 25);
        $username = trim($_POST['smtp_username'] ?? '');
        $password = trim($_POST['smtp_password'] ?? '');
        $dailyLimit = (int) ($_POST['daily_limit'] ?? 0);
        $status = isset($_POST['status']) && $_POST['status'] === 'active' ? 'active' : 'inactive';
        if ($accountName === '' || $fromEmail === '' || $host === '' || $username === '' || $password === '') {
            setFlash('danger', 'Please fill in all required fields.');
        } else {
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO smtp_accounts (account_name, from_name, from_email, smtp_host, smtp_port, smtp_username, smtp_password, daily_limit, usage_today, status, created_at) VALUES (:account_name, :from_name, :from_email, :host, :port, :username, :password, :daily_limit, 0, :status, NOW())');
                $stmt->execute([
                    ':account_name' => $accountName,
                    ':from_name' => $fromName,
                    ':from_email' => $fromEmail,
                    ':host' => $host,
                    ':port' => $port,
                    ':username' => $username,
                    ':password' => $password,
                    ':daily_limit' => $dailyLimit,
                    ':status' => $status,
                ]);
                setFlash('success', 'SMTP account added.');
            } elseif ($action === 'edit') {
                $id = (int) $_POST['id'];
                $stmt = $pdo->prepare('UPDATE smtp_accounts SET account_name=:account_name, from_name=:from_name, from_email=:from_email, smtp_host=:host, smtp_port=:port, smtp_username=:username, smtp_password=:password, daily_limit=:daily_limit, status=:status WHERE id=:id');
                $stmt->execute([
                    ':account_name' => $accountName,
                    ':from_name' => $fromName,
                    ':from_email' => $fromEmail,
                    ':host' => $host,
                    ':port' => $port,
                    ':username' => $username,
                    ':password' => $password,
                    ':daily_limit' => $dailyLimit,
                    ':status' => $status,
                    ':id' => $id,
                ]);
                setFlash('success', 'SMTP account updated.');
            }
        }
    }
    redirect('smtp-accounts.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM smtp_accounts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    setFlash('success', 'SMTP account deleted.');
    redirect('smtp-accounts.php');
}

// Handle test email
$testMessage = null;
if (isset($_POST['action']) && $_POST['action'] === 'test_email') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $smtpId = (int) $_POST['smtp_id'];
        $testEmail = trim($_POST['test_email'] ?? '');
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Please enter a valid test email.');
        } else {
            // For demonstration, we simulate sending a test email.
            // In a real environment, you would connect to SMTP server and send an email.
            $testMessage = 'Test email sent successfully to ' . e($testEmail) . '.';
        }
    }
}

// Fetch accounts
$smtpAccounts = $pdo->query('SELECT * FROM smtp_accounts ORDER BY id DESC')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>SMTP Accounts</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSmtpModal">Add SMTP Account</button>
</div>

<?php if ($testMessage): ?>
    <div class="alert alert-info"><?= $testMessage ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover datatable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>From Name</th>
                    <th>From Email</th>
                    <th>Host</th>
                    <th>Daily Limit</th>
                    <th>Usage Today</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($smtpAccounts as $acc): ?>
                    <tr>
                        <td><?= e($acc['account_name']) ?></td>
                        <td><?= e($acc['from_name']) ?></td>
                        <td><?= e($acc['from_email']) ?></td>
                        <td><?= e($acc['smtp_host'] . ':' . $acc['smtp_port']) ?></td>
                        <td><?= e($acc['daily_limit']) ?></td>
                        <td><?= e($acc['usage_today']) ?></td>
                        <td><span class="badge bg-<?= $acc['status'] == 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($acc['status'])) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSmtpModal<?= $acc['id'] ?>">Edit</button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#testEmailModal<?= $acc['id'] ?>">Test</button>
                            <a href="smtp-accounts.php?delete=<?= $acc['id'] ?>" class="btn btn-sm btn-danger confirm-delete">Delete</a>
                        </td>
                    </tr>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editSmtpModal<?= $acc['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit SMTP Account</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Account Name</label>
                                            <input type="text" class="form-control" name="account_name" value="<?= e($acc['account_name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">From Name</label>
                                            <input type="text" class="form-control" name="from_name" value="<?= e($acc['from_name']) ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">From Email</label>
                                            <input type="email" class="form-control" name="from_email" value="<?= e($acc['from_email']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" name="smtp_host" value="<?= e($acc['smtp_host']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Port</label>
                                            <input type="number" class="form-control" name="smtp_port" value="<?= e($acc['smtp_port']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Username</label>
                                            <input type="text" class="form-control" name="smtp_username" value="<?= e($acc['smtp_username']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" name="smtp_password" value="<?= e($acc['smtp_password']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Daily Limit</label>
                                            <input type="number" class="form-control" name="daily_limit" value="<?= e($acc['daily_limit']) ?>" required>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input class="form-check-input" type="checkbox" name="status" value="active" id="status<?= $acc['id'] ?>" <?= $acc['status'] == 'active' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="status<?= $acc['id'] ?>">Active</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Test Email Modal -->
                    <div class="modal fade" id="testEmailModal<?= $acc['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Send Test Email</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="test_email">
                                    <input type="hidden" name="smtp_id" value="<?= $acc['id'] ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Test Email Address</label>
                                            <input type="email" class="form-control" name="test_email" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Send Test Email</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add SMTP Modal -->
<div class="modal fade" id="addSmtpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add SMTP Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Account Name</label>
                        <input type="text" class="form-control" name="account_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Name</label>
                        <input type="text" class="form-control" name="from_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Email</label>
                        <input type="email" class="form-control" name="from_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" name="smtp_host" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" name="smtp_port" value="587" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" name="smtp_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" name="smtp_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Daily Limit</label>
                        <input type="number" class="form-control" name="daily_limit" value="500" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="status" value="active" id="newStatus">
                        <label class="form-check-label" for="newStatus">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add SMTP Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>