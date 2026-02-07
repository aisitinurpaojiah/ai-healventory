<?php
include '../../config/database.php';
$id = $_POST['id'];

$stmt = $pdo->prepare("DELETE FROM obat WHERE id=?");
$stmt->execute([$id]);

echo "success";
?>
