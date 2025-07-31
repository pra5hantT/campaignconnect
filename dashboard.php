<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$title = 'Dashboard';
$pdo = getDatabaseConnection();

// Quick stats
// Total contacts
$totalContacts = (int) $pdo->query('SELECT COUNT(*) FROM contacts')->fetchColumn();
// Active campaigns (scheduled or draft)
$activeCampaigns = (int) $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status IN ('" . CAMPAIGN_STATUS_DRAFT . "', '" . CAMPAIGN_STATUS_SCHEDULED . "')")->fetchColumn();
// Recent opens (last 24 hours)
$recentOpens = (int) $pdo->query("SELECT SUM(open_count) FROM campaign_recipients WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn();
// Click rate (average clicks / recipients for last 7 days)
$clickRate = 0;
$res = $pdo->query("SELECT SUM(click_count) AS clicks, COUNT(*) AS recipients FROM campaign_recipients WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch();
if ($res && $res['recipients'] > 0) {
    $clickRate = round(($res['clicks'] / $res['recipients']) * 100, 2);
}

// Recent campaigns list (last 5)
$stmt = $pdo->prepare('SELECT c.*, u.username FROM campaigns c LEFT JOIN users u ON c.created_by = u.id ORDER BY c.created_at DESC LIMIT 5');
$stmt->execute();
$recentCampaigns = $stmt->fetchAll();

// Data for engagement chart (last 7 days totals)
$chartLabels = [];
$opensData = [];
$clicksData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = $date;
    $stmt = $pdo->prepare('SELECT SUM(open_count) AS opens, SUM(click_count) AS clicks FROM campaign_recipients WHERE DATE(sent_at) = :date');
    $stmt->execute([':date' => $date]);
    $row = $stmt->fetch();
    $opensData[] = (int) $row['opens'];
    $clicksData[] = (int) $row['clicks'];
}

include __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3>Total Contacts</h3>
            <p><?= e($totalContacts) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3>Active Campaigns</h3>
            <p><?= e($activeCampaigns) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3>Recent Opens (24h)</h3>
            <p><?= e($recentOpens) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h3>Click Rate (%)</h3>
            <p><?= e($clickRate) ?></p>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>Recent Campaigns</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-hover datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Recipients</th>
                            <th>Sent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentCampaigns as $camp): ?>
                            <tr>
                                <td><?= e($camp['campaign_name']) ?></td>
                                <td><?= e($camp['subject_line']) ?></td>
                                <td><?= e(ucfirst($camp['status'])) ?></td>
                                <td>
                                    <?php
                                    $countRec = $pdo->prepare('SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = :id');
                                    $countRec->execute([':id' => $camp['id']]);
                                    echo $countRec->fetchColumn();
                                    ?>
                                </td>
                                <td><?= e($camp['status'] == CAMPAIGN_STATUS_SENT ? formatDateTime($camp['sent_at']) : '-') ?></td>
                                <td>
                                    <a href="edit-campaign.php?id=<?= $camp['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <a href="preview-campaign.php?id=<?= $camp['id'] ?>" class="btn btn-sm btn-outline-primary">Preview</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="create-campaign.php" class="btn btn-primary mb-2 w-100">Create Campaign</a>
                <a href="import-contacts.php" class="btn btn-secondary mb-2 w-100">Import Contacts</a>
                <a href="reports.php" class="btn btn-outline-primary w-100">View Reports</a>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>System Status</h5>
            </div>
            <div class="card-body">
                <p>Everything looks good!</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>Email Engagement (Last 7 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="opensClicksChart" data-labels='<?= e(json_encode($chartLabels)) ?>' data-opens='<?= e(json_encode($opensData)) ?>' data-clicks='<?= e(json_encode($clicksData)) ?>' width="400" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>