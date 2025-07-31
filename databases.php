<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
if (!isAdmin()) {
    setFlash('danger', 'You do not have permission to access this page.');
    redirect('dashboard.php');
}

$title = 'External Databases';
$pdo = getDatabaseConnection();

// Handle add/edit database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $action = $_POST['action'];
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['db_type'] ?? 'mysql');
        $host = trim($_POST['host'] ?? '');
        $port = trim($_POST['port'] ?? '');
        $username = trim($_POST['db_username'] ?? '');
        $password = trim($_POST['db_password'] ?? '');
        $dbname = trim($_POST['dbname'] ?? '');
        if ($name === '' || $host === '' || $username === '' || $dbname === '') {
            setFlash('danger', 'Please fill in all required fields.');
        } else {
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO external_databases (name, db_type, host, port, username, password, dbname, status, last_sync, created_at) VALUES (:name, :db_type, :host, :port, :username, :password, :dbname, :status, NULL, NOW())');
                $stmt->execute([
                    ':name' => $name,
                    ':db_type' => $type,
                    ':host' => $host,
                    ':port' => $port,
                    ':username' => $username,
                    ':password' => $password,
                    ':dbname' => $dbname,
                    ':status' => 'inactive',
                ]);
                setFlash('success', 'Database connection added.');
            } elseif ($action === 'edit') {
                $id = (int) $_POST['id'];
                $stmt = $pdo->prepare('UPDATE external_databases SET name=:name, db_type=:db_type, host=:host, port=:port, username=:username, password=:password, dbname=:dbname WHERE id = :id');
                $stmt->execute([
                    ':name' => $name,
                    ':db_type' => $type,
                    ':host' => $host,
                    ':port' => $port,
                    ':username' => $username,
                    ':password' => $password,
                    ':dbname' => $dbname,
                    ':id' => $id,
                ]);
                setFlash('success', 'Database connection updated.');
            }
        }
    }
    redirect('databases.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM external_databases WHERE id = :id');
    $stmt->execute([':id' => $id]);
    setFlash('success', 'Database connection deleted.');
    redirect('databases.php');
}

// Handle test connection
$testResult = null;
if (isset($_GET['test'])) {
    $id = (int) $_GET['test'];
    $stmt = $pdo->prepare('SELECT * FROM external_databases WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $conn = $stmt->fetch();
    if ($conn) {
        $dsn = $conn['db_type'] . ':host=' . $conn['host'] . ';dbname=' . $conn['dbname'];
        if (!empty($conn['port'])) {
            $dsn .= ';port=' . $conn['port'];
        }
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];
            $testPdo = new PDO($dsn, $conn['username'], $conn['password'], $options);
            $testResult = 'Connection successful!';
            // Update status
            $stmtUpdate = $pdo->prepare('UPDATE external_databases SET status=:status WHERE id=:id');
            $stmtUpdate->execute([':status' => 'active', ':id' => $id]);
        } catch (PDOException $e) {
            $testResult = 'Connection failed: ' . $e->getMessage();
            // Update status
            $stmtUpdate = $pdo->prepare('UPDATE external_databases SET status=:status WHERE id=:id');
            $stmtUpdate->execute([':status' => 'inactive', ':id' => $id]);
        }
    }
}

// Fetch all connections
$stmt = $pdo->query('SELECT * FROM external_databases ORDER BY id DESC');
$connections = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>External Databases</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDatabaseModal">Add New Database</button>
</div>

<?php if ($testResult !== null): ?>
    <div class="alert alert-info">Test Result: <?= e($testResult) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover datatable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Host</th>
                    <th>Status</th>
                    <th>Last Sync</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($connections as $conn): ?>
                    <tr>
                        <td><?= e($conn['name']) ?></td>
                        <td><?= e(strtoupper($conn['db_type'])) ?></td>
                        <td><?= e($conn['host'] . (!empty($conn['port']) ? ':' . $conn['port'] : '')) ?></td>
                        <td><span class="badge bg-<?= $conn['status'] == 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($conn['status'])) ?></span></td>
                        <td><?= e($conn['last_sync'] ? formatDateTime($conn['last_sync']) : '-') ?></td>
                        <td>
                            <a href="databases.php?test=<?= $conn['id'] ?>" class="btn btn-sm btn-outline-primary">Test</a>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editDatabaseModal<?= $conn['id'] ?>">Edit</button>
                            <a href="databases.php?delete=<?= $conn['id'] ?>" class="btn btn-sm btn-danger confirm-delete">Delete</a>
                        </td>
                    </tr>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editDatabaseModal<?= $conn['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Database Connection</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" action="">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $conn['id'] ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" value="<?= e($conn['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Database Type</label>
                                            <select class="form-select" name="db_type">
                                                <option value="mysql" <?= $conn['db_type'] == 'mysql' ? 'selected' : '' ?>>MySQL</option>
                                                <option value="pgsql" <?= $conn['db_type'] == 'pgsql' ? 'selected' : '' ?>>PostgreSQL</option>
                                                <option value="sqlsrv" <?= $conn['db_type'] == 'sqlsrv' ? 'selected' : '' ?>>SQL Server</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Host</label>
                                            <input type="text" class="form-control" name="host" value="<?= e($conn['host']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Port</label>
                                            <input type="text" class="form-control" name="port" value="<?= e($conn['port']) ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="db_username" value="<?= e($conn['username']) ?>" required>
                                        </div>
                                            <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="db_password" value="<?= e($conn['password']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Database Name</label>
                                            <input type="text" class="form-control" name="dbname" value="<?= e($conn['dbname']) ?>" required>
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

<!-- Add Database Modal -->
<div class="modal fade" id="addDatabaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Database Connection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Type</label>
                        <select class="form-select" name="db_type">
                            <option value="mysql">MySQL</option>
                            <option value="pgsql">PostgreSQL</option>
                            <option value="sqlsrv">SQL Server</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Host</label>
                        <input type="text" class="form-control" name="host" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Port</label>
                        <input type="text" class="form-control" name="port" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="db_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="db_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Name</label>
                        <input type="text" class="form-control" name="dbname" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Connection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>