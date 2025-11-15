<?php
require_once 'config.php';
require_once 'includes/BillfishManager.php';

$manager = new BillfishManager(BILLFISH_PATH);
$files = [];
$manager->getAllFiles($files);

foreach ($files as $file) {
    if ($file['name'] === 'begin-01.mp4') {
        echo 'File ID: ' . $file['id'] . "\n";
        echo 'Preview: ' . $file['preview_path'] . "\n";
        break;
    }
}
?>