<?php
// Start the session and set timezone
if (session_status() === PHP_SESSION_NONE) {
    $config = require __DIR__ . '/../config/config.php';
    session_name($config['session_name']);
    session_start();
    date_default_timezone_set($config['timezone']);
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/constants.php';

// Generate CSRF token for forms
$csrfToken = generateCsrfToken();

// Determine page title
$pageTitle = $config['site_name'];
if (isset($title) && $title) {
    $pageTitle .= ' - ' . $title;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-VkKnv6uAzsX7jjMnGGVfX9dR5gP0A8tsRIod0W5R73L+Rxyya6uhE/k34RaHAq6v" crossorigin="anonymous">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <link rel="stylesheet" href="assets/css/tables.css">
    <link rel="stylesheet" href="assets/css/modals.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <!-- Custom components -->
    <link rel="stylesheet" href="assets/css/components/buttons.css">
    <link rel="stylesheet" href="assets/css/components/cards.css">
    <link rel="stylesheet" href="assets/css/components/navigation.css">
    <link rel="stylesheet" href="assets/css/components/alerts.css">
    <style>
        /* Minimal override for DataTables inside Bootstrap */
        table.dataTable thead th, table.dataTable tbody td {
            padding: 0.5rem 0.75rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">CampaignConnect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="contacts.php">Contacts</a></li>
                        <li class="nav-item"><a class="nav-link" href="campaigns.php">Campaigns</a></li>
                        <li class="nav-item"><a class="nav-link" href="templates.php">Templates</a></li>
                        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="databases.php">Databases</a></li>
                            <li class="nav-item"><a class="nav-link" href="smtp-accounts.php">SMTP Accounts</a></li>
                        <?php endif; ?>
                        <?php if (isSuperAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="roles.php">Roles</a></li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= e($_SESSION['user']['username'] ?? 'User') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container-fluid mt-3">
<?php
// Display flash messages
$flashMessages = getFlash();
foreach ($flashMessages as $type => $messages) {
    foreach ($messages as $msg) {
        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">' . e($msg) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}
?>