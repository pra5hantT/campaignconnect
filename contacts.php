<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$title = 'Contacts';
$pdo = getDatabaseConnection();

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
    if (verifyCsrfToken()) {
        $ids = $_POST['selected'] ?? [];
        if (!empty($ids)) {
            $in = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM contacts WHERE id IN ($in)");
            $stmt->execute($ids);
            setFlash('success', 'Selected contacts deleted.');
        }
    } else {
        setFlash('danger', 'Invalid CSRF token.');
    }
    redirect('contacts.php');
}

// Handle delete individual contact
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    setFlash('success', 'Contact deleted.');
    redirect('contacts.php');
}

// Search & filters
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE :search OR email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($statusFilter !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $statusFilter;
}
if ($typeFilter !== '') {
    $where[] = 'contact_type = :type';
    $params[':type'] = $typeFilter;
}
$sql = 'SELECT * FROM contacts';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

// Fetch lists mapping for display
$listsMap = [];
$listRows = $pdo->query('SELECT cl.id, cl.list_name, clm.contact_id FROM contact_lists cl JOIN contact_list_members clm ON cl.id = clm.list_id')->fetchAll();
foreach ($listRows as $row) {
    $listsMap[$row['contact_id']][] = $row['list_name'];
}

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contacts</h3>
    <div>
        <a href="import-contacts.php" class="btn btn-secondary me-2">Import Contacts</a>
        <a href="export-contacts.php" class="btn btn-secondary me-2">Export Contacts</a>
        <a href="add-contact.php" class="btn btn-primary">Add Contact</a>
    </div>
</div>

<form method="get" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Name or email">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">Any</option>
                <option value="active" <?= $statusFilter == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select class="form-select" name="type">
                <option value="">Any</option>
                <option value="subscriber" <?= $typeFilter == 'subscriber' ? 'selected' : '' ?>>Subscriber</option>
                <option value="customer" <?= $typeFilter == 'customer' ? 'selected' : '' ?>>Customer</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </div>
</form>

<form method="post" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-sm table-hover datatable" id="contactsTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Lists</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><input type="checkbox" name="selected[]" value="<?= $contact['id'] ?>"></td>
                            <td><a href="contact-details.php?id=<?= $contact['id'] ?>"><?= e($contact['name']) ?></a></td>
                            <td><?= e($contact['email']) ?></td>
                            <td><?= e(ucfirst($contact['contact_type'])) ?></td>
                            <td><span class="badge bg-<?= $contact['status'] == 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($contact['status'])) ?></span></td>
                            <td><?= e(isset($listsMap[$contact['id']]) ? implode(', ', $listsMap[$contact['id']]) : '-') ?></td>
                            <td><?= e($contact['last_activity'] ? formatDateTime($contact['last_activity']) : '-') ?></td>
                            <td>
                                <a href="contact-details.php?id=<?= $contact['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="edit-contact.php?id=<?= $contact['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <a href="contacts.php?delete=<?= $contact['id'] ?>" class="btn btn-sm btn-danger confirm-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <select name="bulk_action" class="form-select w-auto d-inline-block">
                        <option value="">Bulk Action</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-danger ms-2">Apply</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Select all checkboxes
$('#selectAll').on('change', function () {
    const checked = $(this).is(':checked');
    $('#contactsTable tbody input[type="checkbox"]').prop('checked', checked);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>