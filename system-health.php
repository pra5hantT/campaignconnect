<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
if (!isAdmin()) {
    setFlash('danger', 'You do not have permission to view system health.');
    redirect('dashboard.php');
}

$pdo = getDatabaseConnection();
$title = 'System Health';

// Stats
$dbConnections = $pdo->query('SELECT COUNT(*) FROM external_databases')->fetchColumn();
$smtpCount = $pdo->query('SELECT COUNT(*) FROM smtp_accounts')->fetchColumn();
$diskFree = disk_free_space(__DIR__);
$diskTotal = disk_total_space(__DIR__);
$diskUsagePercent = ($diskTotal > 0) ? round((1 - $diskFree / $diskTotal) * 100, 2) : 0;

include __DIR__ . '/includes/header.php';
?>
<h3>System Health</h3>
<div class="row mt-3">
    <div class="col-md-4">
        <div class="stats-card text-center">
            <h4>External DB Connections</h4>
            <p><?= e($dbConnections) ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card text-center">
            <h4>SMTP Accounts</h4>
            <p><?= e($smtpCount) ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card text-center">
            <h4>PHP Version</h4>
            <p><?= e(PHP_VERSION) ?></p>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Disk Usage</h5>
                <p>Total Space: <?= number_format($diskTotal / (1024 * 1024 * 1024), 2) ?> GB</p>
                <p>Free Space: <?= number_format($diskFree / (1024 * 1024 * 1024), 2) ?> GB</p>
                <p>Usage: <?= e($diskUsagePercent) ?>%</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>