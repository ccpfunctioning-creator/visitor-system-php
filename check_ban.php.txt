<?php
require_once 'db.php';
header('Content-Type: application/json');

if (isset($_GET['cid'])) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM banned_inmates WHERE inmateCid = ?");
    $stmt->execute([$_GET['cid']]);
    $isBanned = $stmt->fetchColumn() > 0;
    echo json_encode(['banned' => $isBanned]);
    exit;
}
echo json_encode(['banned' => false]);
?>
