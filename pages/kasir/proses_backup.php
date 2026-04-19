<?php
require_once '../../config.php';
requireRole('kasir');

try {
    // Memanggil koneksi dari fungsi getDB() sesuai config.php Anda
    $pdo = getDB();

    // Ambil daftar tabel
    $tables = [];
    $query = $pdo->query("SHOW TABLES");
    while ($row = $query->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sql_output = "-- TursMovie Backup\n";
    $sql_output .= "-- Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Backup Struktur
        $res = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $sql_output .= "\n\n" . $res['Create Table'] . ";\n\n";

        // Backup Data
        $data_query = $pdo->query("SELECT * FROM `$table` shadow_table");
        while ($row = $data_query->fetch(PDO::FETCH_ASSOC)) {
            $columns = array_keys($row);
            $values = array_map(function($v) use ($pdo) {
                if ($v === null) return 'NULL';
                return $pdo->quote($v);
            }, array_values($row));

            $sql_output .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
    }

    $sql_output .= "\nSET FOREIGN_KEY_CHECKS=1;";

    // Download file
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="backup_tursmovie_' . date('Y-m-d') . '.sql"');
    echo $sql_output;
    exit;

} catch (Exception $e) {
    die("Gagal Backup: " . $e->getMessage());
}