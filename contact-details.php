<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$pdo = getDatabaseConnection();
$title = 'Contact Details';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    setFlash('danger', 'Invalid contact ID.');
    redirect('contacts.php');
}
// Fetch contact
$stmt = $pdo->prepare('SELECT * FROM contacts WHERE id = :id');
$stmt->execute([':id' => $id]);
$contact = $stmt->fetch();
if (!$contact) {
    setFlash('danger', 'Contact not found.');
    redirect('contacts.php');
}

// Fetch lists the contact is part of
$lists = $pdo->prepare('SELECT cl.list_name FROM contact_lists cl JOIN contact_list_members clm ON cl.id = clm.list_id WHERE clm.contact_id = :id');
$lists->execute([':id' => $id]);
$listNames = $lists->fetchAll(PDO::FETCH_COLUMN);

// Fetch engagement stats
$engagement = $pdo->prepare('SELECT SUM(open_count) AS opens, SUM(click_count) AS clicks FROM campaign_recipients WHERE contact_id = :id');
$engagement->execute([':id' => $id]);
$engData = $engagement->fetch();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contact Details</h3>
    <div>
        <a href="edit-contact.php?id=<?= $contact['id'] ?>" class="btn btn-secondary">Edit</a>
        <a href="contacts.php?delete=<?= $contact['id'] ?>" class="btn btn-danger confirm-delete">Delete</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5>Basic Info</h5>
        <p><strong>Name:</strong> <?= e($contact['name']) ?></p>
        <p><strong>Email:</strong> <?= e($contact['email']) ?></p>
        <p><strong>Contact Type:</strong> <?= e(ucfirst($contact['contact_type'])) ?></p>
        <p><strong>Status:</strong> <span class="badge bg-<?= $contact['status'] == 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($contact['status'])) ?></span></p>
        <p><strong>Last Activity:</strong> <?= e($contact['last_activity'] ? formatDateTime($contact['last_activity']) : '-') ?></p>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5>Lists</h5>
        <?php if ($listNames): ?>
            <ul>
                <?php foreach ($listNames as $name): ?>
                    <li><?= e($name) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No list membership.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5>Engagement</h5>
        <p><strong>Opens:</strong> <?= e($engData['opens'] ?? 0) ?></p>
        <p><strong>Clicks:</strong> <?= e($engData['clicks'] ?? 0) ?></p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5>Sent Campaigns</h5>
        <div class="table-responsive">
            <table class="table table-sm table-hover datatable">
                <thead>
                    <tr>
                        <th>Campaign Name</th>
                        <th>Subject</th>
                        <th>Sent At</th>
                        <th>Opens</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare('SELECT c.campaign_name, c.subject_line, cr.sent_at, cr.open_count, cr.click_count FROM campaign_recipients cr JOIN campaigns c ON cr.campaign_id = c.id WHERE cr.contact_id = :id');
                    $stmt->execute([':id' => $id]);
                    $rows = $stmt->fetchAll();
                    foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['campaign_name']) ?></td>
                            <td><?= e($row['subject_line']) ?></td>
                            <td><?= e(formatDateTime($row['sent_at'])) ?></td>
                            <td><?= e($row['open_count']) ?></td>
                            <td><?= e($row['click_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>