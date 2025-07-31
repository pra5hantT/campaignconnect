<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();

$listId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$listId) {
    setFlash('danger', 'Invalid list ID.');
    redirect('contact-lists.php');
}
// Fetch list details
$stmt = $pdo->prepare('SELECT * FROM contact_lists WHERE id = :id');
$stmt->execute([':id' => $listId]);
$list = $stmt->fetch();
if (!$list) {
    setFlash('danger', 'List not found.');
    redirect('contact-lists.php');
}

$title = 'Manage List: ' . $list['list_name'];

// Handle add contact to list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_id'])) {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $contactId = (int) $_POST['contact_id'];
        // Check existing membership
        $stmt = $pdo->prepare('SELECT 1 FROM contact_list_members WHERE list_id=:list AND contact_id=:contact');
        $stmt->execute([':list' => $listId, ':contact' => $contactId]);
        if (!$stmt->fetch()) {
            $pdo->prepare('INSERT INTO contact_list_members (list_id, contact_id) VALUES (:list, :contact)')->execute([':list' => $listId, ':contact' => $contactId]);
            setFlash('success', 'Contact added to list.');
        }
    }
    redirect('list-management.php?id=' . $listId);
}

// Handle remove membership
if (isset($_GET['remove_contact'])) {
    $cid = (int) $_GET['remove_contact'];
    $pdo->prepare('DELETE FROM contact_list_members WHERE list_id=:list AND contact_id=:contact')->execute([':list' => $listId, ':contact' => $cid]);
    setFlash('success', 'Contact removed from list.');
    redirect('list-management.php?id=' . $listId);
}

// Fetch contacts in list
$stmt = $pdo->prepare('SELECT c.* FROM contacts c JOIN contact_list_members clm ON c.id = clm.contact_id WHERE clm.list_id = :id');
$stmt->execute([':id' => $listId]);
$listContacts = $stmt->fetchAll();

// Fetch all contacts for adding (exclude those already in list)
$stmt = $pdo->prepare('SELECT c.* FROM contacts c WHERE c.id NOT IN (SELECT contact_id FROM contact_list_members WHERE list_id = :id)');
$stmt->execute([':id' => $listId]);
$availableContacts = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manage List: <?= e($list['list_name']) ?></h3>
    <a href="contact-lists.php" class="btn btn-secondary">Back</a>
</div>

<!-- Add contact to list -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5>Add Contact to List</h5>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="col-md-8">
                <select class="form-select" name="contact_id" required>
                    <option value="">Select Contact</option>
                    <?php foreach ($availableContacts as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name'] . ' (' . $c['email'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Add to List</button>
            </div>
        </form>
    </div>
</div>

<!-- List contacts -->
<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <h5>Contacts in this List</h5>
        <table class="table table-sm table-hover datatable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listContacts as $contact): ?>
                    <tr>
                        <td><?= e($contact['name']) ?></td>
                        <td><?= e($contact['email']) ?></td>
                        <td><?= e(ucfirst($contact['contact_type'])) ?></td>
                        <td><span class="badge bg-<?= $contact['status'] == 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($contact['status'])) ?></span></td>
                        <td>
                            <a href="list-management.php?id=<?= $listId ?>&remove_contact=<?= $contact['id'] ?>" class="btn btn-sm btn-danger confirm-delete">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>