<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();
$title = 'Campaign Reports';

// Get date range from query
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Fetch campaigns within range
$stmt = $pdo->prepare('SELECT c.*, (SELECT SUM(open_count) FROM campaign_recipients cr WHERE cr.campaign_id = c.id) AS total_opens, (SELECT SUM(click_count) FROM campaign_recipients cr WHERE cr.campaign_id = c.id) AS total_clicks, (SELECT SUM(bounce) FROM campaign_recipients cr WHERE cr.campaign_id = c.id) AS total_bounces, (SELECT COUNT(*) FROM campaign_recipients cr WHERE cr.campaign_id = c.id) AS recipients FROM campaigns c WHERE DATE(c.created_at) BETWEEN :start AND :end');
$stmt->execute([':start' => $startDate, ':end' => $endDate]);
$campaigns = $stmt->fetchAll();

// Summary stats
$totalCampaigns = count($campaigns);
$avgOpenRate = 0;
$avgClickRate = 0;
$bounceRate = 0;
if ($totalCampaigns > 0) {
    $sumOpenRate = 0;
    $sumClickRate = 0;
    $sumBounceRate = 0;
    foreach ($campaigns as $c) {
        $recipients = $c['recipients'] ?: 1;
        $sumOpenRate += ($c['total_opens'] / $recipients) * 100;
        $sumClickRate += ($c['total_clicks'] / $recipients) * 100;
        $sumBounceRate += ($c['total_bounces'] / $recipients) * 100;
    }
    $avgOpenRate = round($sumOpenRate / $totalCampaigns, 2);
    $avgClickRate = round($sumClickRate / $totalCampaigns, 2);
    $bounceRate = round($sumBounceRate / $totalCampaigns, 2);
}

// Chart data: open/click trends across date range
$chartLabels = [];
$openTrend = [];
$clickTrend = [];
$period = new DatePeriod(new DateTime($startDate), new DateInterval('P1D'), (new DateTime($endDate))->modify('+1 day'));
foreach ($period as $date) {
    $d = $date->format('Y-m-d');
    $chartLabels[] = $d;
    $stmt = $pdo->prepare('SELECT SUM(cr.open_count) AS opens, SUM(cr.click_count) AS clicks FROM campaign_recipients cr JOIN campaigns c ON cr.campaign_id = c.id WHERE DATE(c.created_at) = :d');
    $stmt->execute([':d' => $d]);
    $row = $stmt->fetch();
    $openTrend[] = (int) $row['opens'];
    $clickTrend[] = (int) $row['clicks'];
}

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Campaign Reports</h3>
    <form method="get" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label">Start Date</label>
            <input type="date" class="form-control" name="start_date" value="<?= e($startDate) ?>">
        </div>
        <div class="col-auto">
            <label class="form-label">End Date</label>
            <input type="date" class="form-control" name="end_date" value="<?= e($endDate) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h4>Total Campaigns</h4>
            <p><?= e($totalCampaigns) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h4>Average Open Rate (%)</h4>
            <p><?= e($avgOpenRate) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h4>Average Click Rate (%)</h4>
            <p><?= e($avgClickRate) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card text-center">
            <h4>Bounce Rate (%)</h4>
            <p><?= e($bounceRate) ?></p>
        </div>
    </div>
</div>

<!-- Campaigns performance table -->
<div class="card shadow-sm mb-4">
    <div class="card-body table-responsive">
        <h5>Campaign Performance</h5>
        <table class="table table-sm table-hover datatable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Sent Date</th>
                    <th>Recipients</th>
                    <th>Opens</th>
                    <th>Clicks</th>
                    <th>Bounces</th>
                    <th>Open Rate (%)</th>
                    <th>Click Rate (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $c): ?>
                    <tr>
                        <td><?= e($c['campaign_name']) ?></td>
                        <td><?= e($c['sent_at'] ? formatDateTime($c['sent_at']) : '-') ?></td>
                        <td><?= e($c['recipients']) ?></td>
                        <td><?= e($c['total_opens']) ?></td>
                        <td><?= e($c['total_clicks']) ?></td>
                        <td><?= e($c['total_bounces']) ?></td>
                        <td><?= e($c['recipients'] ? round(($c['total_opens'] / $c['recipients']) * 100, 2) : 0) ?></td>
                        <td><?= e($c['recipients'] ? round(($c['total_clicks'] / $c['recipients']) * 100, 2) : 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Performance Charts -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Open Rate Trends</h5>
                <canvas class="campaign-chart" data-labels='<?= e(json_encode($chartLabels)) ?>' data-opens='<?= e(json_encode($openTrend)) ?>' data-clicks='<?= e(json_encode($clickTrend)) ?>' data-title="Open & Click Trends" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Engagement Heatmap (Not implemented)</h5>
                <p>Future work: display heatmap of engagement by time of day.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>