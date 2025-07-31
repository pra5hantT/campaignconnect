<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();
$title = 'Email Templates';

// Handle add/edit template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $name = trim($_POST['template_name'] ?? '');
        $content = trim($_POST['html_content'] ?? '');
        if ($name === '' || $content === '') {
            setFlash('danger', 'Please fill in all fields.');
        } else {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare('INSERT INTO templates (template_name, html_content, created_by, created_at) VALUES (:name, :content, :uid, NOW())');
                $stmt->execute([':name' => $name, ':content' => $content, ':uid' => $_SESSION['user']['id']]);
                setFlash('success', 'Template added.');
            } elseif ($_POST['action'] === 'edit') {
                $id = (int) $_POST['id'];
                $stmt = $pdo->prepare('UPDATE templates SET template_name=:name, html_content=:content WHERE id=:id');
                $stmt->execute([':name' => $name, ':content' => $content, ':id' => $id]);
                setFlash('success', 'Template updated.');
            }
        }
    }
    redirect('templates.php');
}
// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM templates WHERE id=:id')->execute([':id' => $id]);
    setFlash('success', 'Template deleted.');
    redirect('templates.php');
}

// Fetch templates
$templates = $pdo->query('SELECT t.*, u.username FROM templates t LEFT JOIN users u ON t.created_by = u.id ORDER BY t.id DESC')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Email Templates</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">Add Template</button>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover datatable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $tpl): ?>
                    <tr>
                        <td><?= e($tpl['template_name']) ?></td>
                        <td><?= e($tpl['username']) ?></td>
                        <td><?= e(formatDateTime($tpl['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTemplateModal<?= $tpl['id'] ?>">Edit</button>
                            <a href="templates.php?delete=<?= $tpl['id'] ?>" class="btn btn-sm btn-danger confirm-delete">Delete</a>
                        </td>
                    </tr>
                    <!-- Edit Template Modal -->
                    <div class="modal fade" id="editTemplateModal<?= $tpl['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Template</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $tpl['id'] ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Template Name</label>
                                            <input type="text" class="form-control" name="template_name" value="<?= e($tpl['template_name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">HTML Content</label>
                                            <textarea class="form-control" name="html_content" rows="10" required><?= e($tpl['html_content']) ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save</button>
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

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Template Name</label>
                        <input type="text" class="form-control" name="template_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HTML Content</label>
                        <textarea class="form-control" name="html_content" rows="10" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>