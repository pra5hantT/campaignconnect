<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    setFlash('danger', 'Invalid contact ID.');
    redirect('contacts.php');
}

$stmt = $pdo->prepare('SELECT * FROM contacts WHERE id=:id');
$stmt->execute([':id' => $id]);
$contact = $stmt->fetch();
if (!$contact) {
    setFlash('danger', 'Contact not found.');
    redirect('contacts.php');
}

$title = 'Edit Contact';
// Fetch all lists
$lists = $pdo->query('SELECT * FROM contact_lists ORDER BY list_name ASC')->fetchAll();
// Fetch membership lists
$currentLists = $pdo->prepare('SELECT list_id FROM contact_list_members WHERE contact_id=:cid');
$currentLists->execute([':cid' => $id]);
$currentListIds = $currentLists->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $type = trim($_POST['contact_type'] ?? 'subscriber');
        $status = trim($_POST['status'] ?? 'active');
        $listIds = $_POST['lists'] ?? [];
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Please provide a valid name and email.');
        } else {
            // Update contact details
            $stmt = $pdo->prepare('UPDATE contacts SET name=:name, email=:email, contact_type=:type, status=:status WHERE id=:id');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':type' => $type,
                ':status' => $status,
                ':id' => $id,
            ]);
            // Update list membership: delete all then re-add
            $pdo->prepare('DELETE FROM contact_list_members WHERE contact_id=:cid')->execute([':cid' => $id]);
            foreach ($listIds as $listId) {
                $pdo->prepare('INSERT INTO contact_list_members (list_id, contact_id) VALUES (:list, :cid)')->execute([':list' => $listId, ':cid' => $id]);
            }
            setFlash('success', 'Contact updated successfully.');
            redirect('contacts.php');
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<h3>Edit Contact</h3>
<form method="post" class="mt-3">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" class="form-control" name="name" value="<?= e($contact['name']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="<?= e($contact['email']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Contact Type</label>
        <select class="form-select" name="contact_type">
            <option value="subscriber" <?= $contact['contact_type'] == 'subscriber' ? 'selected' : '' ?>>Subscriber</option>
            <option value="customer" <?= $contact['contact_type'] == 'customer' ? 'selected' : '' ?>>Customer</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
            <option value="active" <?= $contact['status'] == 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $contact['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Assign to Lists</label>
        <select class="form-select" name="lists[]" multiple size="5">
            <?php foreach ($lists as $list): ?>
                <option value="<?= $list['id'] ?>" <?= in_array($list['id'], $currentListIds) ? 'selected' : '' ?>><?= e($list['list_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="contacts.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>