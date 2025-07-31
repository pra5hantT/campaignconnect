<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$title = 'Import Contacts';
$pdo = getDatabaseConnection();

// Fetch contact lists for assignment
$lists = $pdo->query('SELECT * FROM contact_lists ORDER BY list_name ASC')->fetchAll();

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('danger', 'Invalid CSRF token.');
    } else {
        $listIds = $_POST['lists'] ?? [];
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            if ($handle !== false) {
                $imported = 0;
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    // Expect columns: name, email, type (optional), status (optional)
                    $name = trim($data[0] ?? '');
                    $email = trim($data[1] ?? '');
                    $type = trim($data[2] ?? 'subscriber');
                    $status = trim($data[3] ?? 'active');
                    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        // Check if contact already exists
                        $stmt = $pdo->prepare('SELECT id FROM contacts WHERE email = :email');
                        $stmt->execute([':email' => $email]);
                        $existing = $stmt->fetchColumn();
                        if ($existing) {
                            $contactId = $existing;
                            // Update existing contact details
                            $update = $pdo->prepare('UPDATE contacts SET name=:name, contact_type=:type, status=:status WHERE id=:id');
                            $update->execute([
                                ':name' => $name,
                                ':type' => $type,
                                ':status' => $status,
                                ':id' => $contactId,
                            ]);
                        } else {
                            // Insert new contact
                            $insert = $pdo->prepare('INSERT INTO contacts (name, email, contact_type, status, created_at) VALUES (:name, :email, :type, :status, NOW())');
                            $insert->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':type' => $type,
                                ':status' => $status,
                            ]);
                            $contactId = $pdo->lastInsertId();
                        }
                        // Assign to lists
                        foreach ($listIds as $listId) {
                            // Check if membership exists
                            $check = $pdo->prepare('SELECT 1 FROM contact_list_members WHERE contact_id=:cid AND list_id=:lid');
                            $check->execute([':cid' => $contactId, ':lid' => $listId]);
                            if (!$check->fetch()) {
                                $pdo->prepare('INSERT INTO contact_list_members (list_id, contact_id) VALUES (:lid, :cid)')->execute([':lid' => $listId, ':cid' => $contactId]);
                            }
                        }
                        $imported++;
                    }
                }
                fclose($handle);
                setFlash('success', "Imported $imported contacts successfully.");
            } else {
                setFlash('danger', 'Failed to open uploaded file.');
            }
        } else {
            setFlash('danger', 'Please upload a CSV file.');
        }
    }
    redirect('contacts.php');
}

include __DIR__ . '/includes/header.php';
?>

<h3>Import Contacts</h3>
<form method="post" enctype="multipart/form-data" class="mt-3">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <div class="mb-3">
        <label class="form-label">CSV File (Name, Email, Type, Status)</label>
        <input type="file" class="form-control" name="csv_file" accept=".csv" required>
        <div class="form-text">Columns: name, email, type (subscriber/customer), status (active/inactive). Columns 3 and 4 are optional.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Assign to Lists</label>
        <select class="form-select" name="lists[]" multiple size="5">
            <?php foreach ($lists as $list): ?>
                <option value="<?= $list['id'] ?>"><?= e($list['list_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">Hold Ctrl/Cmd to select multiple lists.</div>
    </div>
    <button type="submit" class="btn btn-primary">Import</button>
    <a href="contacts.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>