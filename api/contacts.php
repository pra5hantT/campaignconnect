<?php
// Basic API for contacts
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
// Only allow logged in users
session_start();
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$pdo = getDatabaseConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Return contact by id
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM contacts WHERE id=:id');
            $stmt->execute([':id' => $id]);
            $contact = $stmt->fetch();
            echo json_encode($contact);
        } else {
            echo json_encode(['error' => 'ID required']);
        }
        break;
    case 'POST':
        // Add a contact (expects JSON body)
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Invalid data']);
            break;
        }
        $stmt = $pdo->prepare('INSERT INTO contacts (name, email, contact_type, status, created_at) VALUES (:name, :email, :type, :status, NOW())');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':type' => $data['contact_type'] ?? 'subscriber',
            ':status' => $data['status'] ?? 'active',
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;
    default:
        echo json_encode(['error' => 'Unsupported method']);
}