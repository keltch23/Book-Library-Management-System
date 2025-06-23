<?php
// filepath: c:\xampp\htdocs\Book Library Management\get_book.php
require_once 'db_connect.php';
$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT id, isbn, title, author, publisher, publication_year, genre FROM books WHERE id=?");
$stmt->execute([$id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));