<?php
// For simplicity, reuse create-campaign.php for editing
if (isset($_GET['id'])) {
    header('Location: create-campaign.php?id=' . intval($_GET['id']));
    exit;
}
header('Location: campaigns.php');
exit;