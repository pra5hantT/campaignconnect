<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();

$title = 'Create Campaign';

// If editing existing draft campaign
$campaignId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$campaign = null;
if ($campaignId) {
    $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = :id');
    $stmt->execute([':id' => $campaignId]);
    $campaign = $stmt->fetch();
    if (!$campaign || $campaign['status'] != CAMPAIGN_STATUS_DRAFT) {
        setFlash('danger', 'Campaign not found or not editable.');
        redirect('campaigns.php');
    }
}

// Fetch lists for recipients step
$lists = $pdo->query('SELECT * FROM contact_lists ORDER BY list_name ASC')->fetchAll();
// Fetch templates for email content step
$templates = $pdo->query('SELECT * FROM templates ORDER BY template_name ASC')->fetchAll();
// Fetch SMTP accounts for schedule step (choose sending account)
$smtpAccounts = $pdo->query('SELECT * FROM smtp_accounts WHERE status="active"')->fetchAll();

// Handle final submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_campaign'])) {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        // Gather details
        $name = trim($_POST['campaign_name'] ?? '');
        $subject = trim($_POST['subject_line'] ?? '');
        $senderName = trim($_POST['sender_name'] ?? '');
        $senderEmail = trim($_POST['sender_email'] ?? '');
        $campaignType = trim($_POST['campaign_type'] ?? 'newsletter');
        $selectedLists = $_POST['lists'] ?? [];
        $content = trim($_POST['email_content'] ?? '');
        $sendOption = $_POST['send_option'] ?? 'now';
        $scheduledTime = null;
        if ($sendOption === 'schedule') {
            $scheduledTime = $_POST['scheduled_time'] ?? null;
        }
        $smtpId = (int) ($_POST['smtp_account'] ?? 0);
        // Validate
        if ($name === '' || $subject === '' || $senderEmail === '' || empty($selectedLists) || $content === '' || !$smtpId) {
            setFlash('danger', 'Please fill in all required fields and select at least one list.');
        } else {
            try {
                $pdo->beginTransaction();
                if ($campaignId) {
                    // Update existing draft
                    $stmt = $pdo->prepare('UPDATE campaigns SET campaign_name=:name, subject_line=:subject, sender_name=:sender_name, sender_email=:sender_email, campaign_type=:type, content=:content, status=:status, scheduled_time=:scheduled_time, smtp_account_id=:smtp_id WHERE id=:id');
                    $status = ($sendOption === 'now') ? CAMPAIGN_STATUS_SENT : CAMPAIGN_STATUS_SCHEDULED;
                    $stmt->execute([
                        ':name' => $name,
                        ':subject' => $subject,
                        ':sender_name' => $senderName,
                        ':sender_email' => $senderEmail,
                        ':type' => $campaignType,
                        ':content' => $content,
                        ':status' => $status,
                        ':scheduled_time' => $sendOption === 'schedule' ? $scheduledTime : null,
                        ':smtp_id' => $smtpId,
                        ':id' => $campaignId,
                    ]);
                    $currentCampaignId = $campaignId;
                    // Remove existing recipients (draft) and re-add
                    $pdo->prepare('DELETE FROM campaign_recipients WHERE campaign_id=:id')->execute([':id' => $currentCampaignId]);
                } else {
                    // Insert new campaign
                    $status = ($sendOption === 'now') ? CAMPAIGN_STATUS_SENT : CAMPAIGN_STATUS_SCHEDULED;
                    $stmt = $pdo->prepare('INSERT INTO campaigns (campaign_name, subject_line, sender_name, sender_email, campaign_type, template_id, content, status, scheduled_time, smtp_account_id, created_by, created_at) VALUES (:name, :subject, :sender_name, :sender_email, :type, NULL, :content, :status, :scheduled_time, :smtp_id, :created_by, NOW())');
                    $stmt->execute([
                        ':name' => $name,
                        ':subject' => $subject,
                        ':sender_name' => $senderName,
                        ':sender_email' => $senderEmail,
                        ':type' => $campaignType,
                        ':content' => $content,
                        ':status' => $status,
                        ':scheduled_time' => $sendOption === 'schedule' ? $scheduledTime : null,
                        ':smtp_id' => $smtpId,
                        ':created_by' => $_SESSION['user']['id'],
                    ]);
                    $currentCampaignId = $pdo->lastInsertId();
                }
                // Determine recipients from lists
                $recipientIds = [];
                foreach ($selectedLists as $listId) {
                    $stmt = $pdo->prepare('SELECT contact_id FROM contact_list_members WHERE list_id = :list');
                    $stmt->execute([':list' => $listId]);
                    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $recipientIds = array_merge($recipientIds, $ids);
                }
                $recipientIds = array_unique($recipientIds);
                // Insert into campaign_recipients
                $insertRec = $pdo->prepare('INSERT INTO campaign_recipients (campaign_id, contact_id, sent_at, open_count, click_count, bounce, status) VALUES (:campaign_id, :contact_id, :sent_at, 0, 0, 0, :status)');
                $sentAt = null;
                $recStatus = ($sendOption === 'now') ? 'sent' : 'scheduled';
                if ($sendOption === 'now') {
                    $sentAt = date('Y-m-d H:i:s');
                }
                foreach ($recipientIds as $contactId) {
                    $insertRec->execute([
                        ':campaign_id' => $currentCampaignId,
                        ':contact_id' => $contactId,
                        ':sent_at' => $sentAt,
                        ':status' => $recStatus,
                    ]);
                }
                // If send now, we would dispatch emails here. For demonstration, we simply mark as sent.
                if ($sendOption === 'now') {
                    // update campaign status and sent_at
                    $pdo->prepare('UPDATE campaigns SET status=:status, sent_at=NOW() WHERE id=:id')->execute([':status' => CAMPAIGN_STATUS_SENT, ':id' => $currentCampaignId]);
                }
                $pdo->commit();
                setFlash('success', $sendOption === 'now' ? 'Campaign sent successfully.' : 'Campaign scheduled successfully.');
                redirect('campaigns.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                setFlash('danger', 'An error occurred: ' . $e->getMessage());
                redirect('campaigns.php');
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<h3><?= $campaignId ? 'Edit' : 'Create' ?> Campaign</h3>

<form method="post" id="campaignForm">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <div id="stepIndicator" class="mb-3"></div>
    <!-- Step 1: Campaign Details -->
    <div class="campaign-step">
        <h5>Step 1: Campaign Details</h5>
        <div class="mb-3">
            <label class="form-label">Campaign Name</label>
            <input type="text" class="form-control" name="campaign_name" value="<?= e($campaign['campaign_name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Subject Line</label>
            <input type="text" class="form-control" name="subject_line" value="<?= e($campaign['subject_line'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Sender Name</label>
            <input type="text" class="form-control" name="sender_name" value="<?= e($campaign['sender_name'] ?? $_SESSION['user']['name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Sender Email</label>
            <input type="email" class="form-control" name="sender_email" value="<?= e($campaign['sender_email'] ?? $_SESSION['user']['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Campaign Type</label>
            <select class="form-select" name="campaign_type">
                <option value="newsletter" <?= ($campaign['campaign_type'] ?? '') == 'newsletter' ? 'selected' : '' ?>>Newsletter</option>
                <option value="promotion" <?= ($campaign['campaign_type'] ?? '') == 'promotion' ? 'selected' : '' ?>>Promotion</option>
            </select>
        </div>
    </div>
    <!-- Step 2: Recipients -->
    <div class="campaign-step">
        <h5>Step 2: Select Recipients</h5>
        <div class="mb-3">
            <label class="form-label">Contact Lists</label>
            <select class="form-select" name="lists[]" multiple required size="6">
                <?php foreach ($lists as $list): ?>
                    <?php $selected = ($campaignId && in_array($list['id'], [])) ? 'selected' : ''; ?>
                    <option value="<?= $list['id'] ?>" <?= $selected ?>><?= e($list['list_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="text-muted">Hold Ctrl/Cmd to select multiple lists.</p>
    </div>
    <!-- Step 3: Email Content -->
    <div class="campaign-step">
        <h5>Step 3: Email Content</h5>
        <div class="mb-3">
            <label class="form-label">Select Template</label>
            <select class="form-select" id="templateSelect">
                <option value="">-- Choose Template --</option>
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?= e($tpl['id']) ?>" data-content="<?= htmlspecialchars($tpl['html_content'], ENT_QUOTES) ?>">
                        <?= e($tpl['template_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Selecting a template will overwrite current content.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Email Content (HTML allowed)</label>
            <textarea class="form-control" name="email_content" id="emailContent" rows="10" required><?= e($campaign['content'] ?? '') ?></textarea>
        </div>
        <p>Use personalization tags: <code>{{name}}</code>, <code>{{email}}</code></p>
    </div>
    <!-- Step 4: Schedule & Send -->
    <div class="campaign-step">
        <h5>Step 4: Schedule & Send</h5>
        <div class="mb-3">
            <label class="form-label">Send Option</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="send_option" id="sendNow" value="now" checked>
                <label class="form-check-label" for="sendNow">Send Now</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="send_option" id="sendSchedule" value="schedule">
                <label class="form-check-label" for="sendSchedule">Schedule</label>
            </div>
        </div>
        <div class="mb-3 schedule-time d-none">
            <label class="form-label">Scheduled Time</label>
            <input type="datetime-local" class="form-control" name="scheduled_time">
        </div>
        <div class="mb-3">
            <label class="form-label">SMTP Account</label>
            <select class="form-select" name="smtp_account" required>
                <option value="">Select Account</option>
                <?php foreach ($smtpAccounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= e($acc['account_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mt-4">
        <button type="button" class="btn btn-secondary prev-step">Previous</button>
        <button type="button" class="btn btn-primary next-step">Next</button>
        <button type="submit" class="btn btn-success submit-campaign" name="submit_campaign">Submit</button>
    </div>
</form>

<script>
// Template selection
$('#templateSelect').on('change', function () {
    const selected = $(this).find('option:selected');
    const content = selected.data('content');
    if (content) {
        if (confirm('Replace current content with selected template?')) {
            $('#emailContent').val(content);
        }
    }
});
// Schedule option toggle
$('input[name="send_option"]').on('change', function () {
    if ($(this).val() === 'schedule') {
        $('.schedule-time').removeClass('d-none');
    } else {
        $('.schedule-time').addClass('d-none');
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>