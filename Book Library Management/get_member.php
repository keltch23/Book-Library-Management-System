<?php
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT id, full_name, email, phone, status FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if ($member) {
    echo json_encode($member);
} else {
    echo json_encode(['error' => 'Member not found']);
}
?>