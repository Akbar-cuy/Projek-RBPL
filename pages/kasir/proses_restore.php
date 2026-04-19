<?php
require_once '../../config.php';
requireRole('kasir');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
    try {
        $pdo = getDB();
        $file_content = file_get_contents($_FILES['backup_file']['tmp_name']);
        
        // Mematikan check foreign key agar tidak error saat menimpa data berelasi
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        $pdo->exec($file_content);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

        header("Location: settings.php?status=success&msg=Database berhasil dipulihkan");
    } catch (Exception $e) {
        header("Location: settings.php?status=error&msg=" . urlencode($e->getMessage()));
    }
    exit;
}