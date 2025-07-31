<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();
$title = 'Analytics Dashboard';

// Simple analytics: top campaigns by opens and clicks
$topOpens = $pdo->query('SELECT c.campaign_name, SUM(cr.open_count) AS opens FROM campaign_recipients cr JOIN campaigns c ON cr.campaign_id = c.id GROUP BY cr.campaign_id ORDER BY opens DESC LIMIT 5')->fetchAll();
$topClicks = $pdo->query('SELECT c.campaign_name, SUM(cr.click_count) AS clicks FROM campaign_recipients cr JOIN campaigns c ON cr.campaign_id = c.id GROUP BY cr.campaign_id ORDER BY clicks DESC LIMIT 5')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<h3>Analytics Dashboard</h3>
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><h5>Top Campaigns by Opens</h5></div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($topOpens as $row): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= e($row['campaign_name']) ?>
                            <span class="badge bg-primary rounded-pill"><?= e($row['opens']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><h5>Top Campaigns by Clicks</h5></div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($topClicks as $row): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= e($row['campaign_name']) ?>
                            <span class="badge bg-primary rounded-pill"><?= e($row['clicks']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>