<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();
$title = 'Preview Campaign';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    setFlash('danger', 'Invalid campaign ID.');
    redirect('campaigns.php');
}
$stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = :id');
$stmt->execute([':id' => $id]);
$campaign = $stmt->fetch();
if (!$campaign) {
    setFlash('danger', 'Campaign not found.');
    redirect('campaigns.php');
}

// Choose a sample recipient for personalization tags
$stmt = $pdo->prepare('SELECT contacts.* FROM contacts JOIN campaign_recipients cr ON contacts.id = cr.contact_id WHERE cr.campaign_id = :cid LIMIT 1');
$stmt->execute([':cid' => $id]);
$sample = $stmt->fetch();
if (!$sample) {
    // Use dummy values
    $sample = ['name' => 'John Doe', 'email' => 'johndoe@example.com'];
}
// Replace tags
$content = $campaign['content'];
$content = str_replace(['{{name}}','{{email}}'], [e($sample['name']), e($sample['email'])], $content);

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Preview Campaign: <?= e($campaign['campaign_name']) ?></h3>
    <a href="campaigns.php" class="btn btn-secondary">Back</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5>Subject: <?= e($campaign['subject_line']) ?></h5>
        <div class="border p-3" style="background-color:#ffffff;">
            <?= $content ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>