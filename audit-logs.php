<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
if (!isAdmin()) {
    setFlash('danger', 'You do not have permission to view audit logs.');
    redirect('dashboard.php');
}

$pdo = getDatabaseConnection();
$title = 'Audit Logs';

// Fetch logs
$logs = $pdo->query('SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 500')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<h3>Audit Logs</h3>
<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover datatable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= e($log['username']) ?></td>
                        <td><?= e($log['action']) ?></td>
                        <td><?= e(formatDateTime($log['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>