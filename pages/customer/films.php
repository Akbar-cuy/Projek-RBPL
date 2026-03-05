<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();
$genre = $_GET['genre'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM films WHERE is_active=1";
$params = [];
if ($search) { $sql .= " AND title LIKE ?"; $params[] = "%$search%"; }
if ($genre !== 'all') { $sql .= " AND genre LIKE ?"; $params[] = "%$genre%"; }
$sql .= " ORDER BY score DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$films = $stmt->fetchAll();
$genres = ['Action','Horror','Romance','Thriller','Sci-Fi','Drama'];
?>