<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$pdo = getDatabaseConnection();

// Fetch all contacts
$contacts = $pdo->query('SELECT name, email, contact_type, status FROM contacts ORDER BY id')->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="contacts_export_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Email', 'Type', 'Status']);
foreach ($contacts as $c) {
    fputcsv($output, [$c['name'], $c['email'], $c['contact_type'], $c['status']]);
}
fclose($output);
exit;