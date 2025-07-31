<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$title = 'Contact Lists';
$pdo = getDatabaseConnection();

// Handle add/edit list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $name = trim($_POST['list_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name === '') {
            setFlash('danger', 'List name cannot be empty.');
        } else {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare('INSERT INTO contact_lists (list_name, description, created_at) VALUES (:name, :description, NOW())');
                $stmt->execute([':name' => $name, ':description' => $desc]);
                setFlash('success', 'List created.');
            } elseif ($_POST['action'] === 'edit') {
                $id = (int) $_POST['id'];
                $stmt = $pdo->prepare('UPDATE contact_lists SET list_name=:name, description=:description WHERE id=:id');
                $stmt->execute([':name' => $name, ':description' => $desc, ':id' => $id]);
                setFlash('success', 'List updated.');
            }
        }
    }
    redirect('contact-lists.php');
}
// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM contact_lists WHERE id=:id')->execute([':id' => $id]);
    $pdo->prepare('DELETE FROM contact_list_members WHERE list_id=:id')->execute([':id' => $id]);
    setFlash('success', 'List deleted.');
    redirect('contact-lists.php');
}

// Fetch lists
$lists = $pdo->query('SELECT * FROM contact_lists ORDER BY id DESC')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contact Lists</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addListModal">Add List</button>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover datatable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Contacts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lists as $list): ?>
                    <tr>
                        <td><?= e($list['list_name']) ?></td>
                        <td><?= e($list['description']) ?></td>
                        <td>
                            <?php
                            $count = $pdo->prepare('SELECT COUNT(*) FROM contact_list_members WHERE list_id=:id');
                            $count->execute([':id' => $list['id']]);
                            echo $count->fetchColumn();
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editListModal<?= $list['id'] ?>">Edit</button>
                            <a href="contact-lists.php?delete=<?= $list['id'] ?>" class="btn btn-sm btn-danger confirm-delete">Delete</a>
                        </td>
                    </tr>
                    <!-- Edit List Modal -->
                    <div class="modal fade" id="editListModal<?= $list['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit List</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $list['id'] ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">List Name</label>
                                            <input type="text" class="form-control" name="list_name" value="<?= e($list['list_name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description"><?= e($list['description']) ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add List Modal -->
<div class="modal fade" id="addListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">List Name</label>
                        <input type="text" class="form-control" name="list_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add List</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>