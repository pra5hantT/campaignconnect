<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();
$title = 'Add Contact';

// Fetch lists for assignment
$lists = $pdo->query('SELECT * FROM contact_lists ORDER BY list_name ASC')->fetchAll();

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
            // Check duplicate
            $stmt = $pdo->prepare('SELECT id FROM contacts WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $existing = $stmt->fetchColumn();
            if ($existing) {
                setFlash('danger', 'A contact with this email already exists.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO contacts (name, email, contact_type, status, created_at) VALUES (:name, :email, :type, :status, NOW())');
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':type' => $type,
                    ':status' => $status,
                ]);
                $contactId = $pdo->lastInsertId();
                // Assign lists
                foreach ($listIds as $listId) {
                    $pdo->prepare('INSERT INTO contact_list_members (list_id, contact_id) VALUES (:lid, :cid)')->execute([':lid' => $listId, ':cid' => $contactId]);
                }
                setFlash('success', 'Contact added successfully.');
                redirect('contacts.php');
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<h3>Add Contact</h3>
<form method="post" class="mt-3">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" class="form-control" name="name" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Contact Type</label>
        <select class="form-select" name="contact_type">
            <option value="subscriber">Subscriber</option>
            <option value="customer">Customer</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Assign to Lists</label>
        <select class="form-select" name="lists[]" multiple size="5">
            <?php foreach ($lists as $list): ?>
                <option value="<?= $list['id'] ?>"><?= e($list['list_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Add Contact</button>
    <a href="contacts.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>