<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
if (!isSuperAdmin()) {
    setFlash('danger', 'You do not have permission to access this page.');
    redirect('dashboard.php');
}

$title = 'Role Management';
$pdo = getDatabaseConnection();

// Handle add role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $roleName = trim($_POST['role_name'] ?? '');
        if ($roleName === '') {
            setFlash('danger', 'Role name cannot be empty.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO roles (name) VALUES (:name)');
            $stmt->execute([':name' => $roleName]);
            setFlash('success', 'Role added successfully.');
        }
    }
}

// Handle delete role
if (isset($_GET['delete'])) {
    $roleId = (int) $_GET['delete'];
    if ($roleId <= 3) {
        setFlash('danger', 'Default roles cannot be deleted.');
    } else {
        $stmt = $pdo->prepare('DELETE FROM roles WHERE id = :id');
        $stmt->execute([':id' => $roleId]);
        setFlash('success', 'Role deleted.');
    }
    redirect('roles.php');
}

// Fetch roles
$roles = $pdo->query('SELECT * FROM roles')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Role Management</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">Add Role</button>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm table-striped datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= e($role['id']) ?></td>
                        <td><?= e($role['name']) ?></td>
                        <td>
                            <?php if ($role['id'] > 3): ?>
                                <a href="roles.php?delete=<?= $role['id'] ?>" class="btn btn-sm btn-danger confirm-delete">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="role_name" name="role_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>