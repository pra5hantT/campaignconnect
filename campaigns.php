<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$title = 'Campaigns';
$pdo = getDatabaseConnection();

// Fetch campaigns
$stmt = $pdo->query('SELECT c.*, u.username FROM campaigns c LEFT JOIN users u ON c.created_by = u.id ORDER BY c.created_at DESC');
$campaigns = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Campaigns</h3>
    <a href="create-campaign.php" class="btn btn-primary">Create Campaign</a>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover datatable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Recipients</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $camp): ?>
                    <tr>
                        <td><?= e($camp['campaign_name']) ?></td>
                        <td><?= e($camp['subject_line']) ?></td>
                        <td><?= e(ucfirst($camp['campaign_type'])) ?></td>
                        <td><span class="badge bg-<?= $camp['status'] == CAMPAIGN_STATUS_SENT ? 'success' : ($camp['status'] == CAMPAIGN_STATUS_SCHEDULED ? 'warning' : 'secondary') ?>"><?= e(ucfirst($camp['status'])) ?></span></td>
                        <td>
                            <?php
                            $stmtc = $pdo->prepare('SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = :id');
                            $stmtc->execute([':id' => $camp['id']]);
                            echo $stmtc->fetchColumn();
                            ?>
                        </td>
                        <td><?= e(formatDateTime($camp['created_at'])) ?></td>
                        <td>
                            <a href="edit-campaign.php?id=<?= $camp['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <a href="preview-campaign.php?id=<?= $camp['id'] ?>" class="btn btn-sm btn-outline-primary">Preview</a>
                            <?php if ($camp['status'] == CAMPAIGN_STATUS_DRAFT): ?>
                                <a href="create-campaign.php?id=<?= $camp['id'] ?>" class="btn btn-sm btn-outline-warning">Continue</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>