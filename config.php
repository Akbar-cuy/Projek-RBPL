<?php


define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tursmovie');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;color:#c00">
                <h2>⚠️ Koneksi Database Gagal</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Pastikan konfigurasi DB_HOST, DB_USER, DB_PASS, DB_NAME di <code>config.php</code> sudah benar.</p>
            </div>');
        }
    }
    return $pdo;
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'index.php');
        exit;
    }
}

function requireRole($role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isLoggedIn()) {
        header('Location: ../../index.php');
        exit;
    }
    if ($_SESSION['role'] !== $role) {
        // Redirect to correct dashboard
        switch ($_SESSION['role']) {
            case 'customer': header('Location: ../../pages/customer/home.php'); break;
            case 'kasir': header('Location: ../../pages/kasir/dashboard.php'); break;
            case 'chef': header('Location: ../../pages/chef/kitchen.php'); break;
            case 'operator': header('Location: ../../pages/operator/schedule.php'); break;
            default: header('Location: ../../index.php');
        }
        exit;
    }
}

function getCurrentUser() {
    return $_SESSION ?? null;
}

function formatRupiah($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function getBaseUrl() {
    $depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
    return str_repeat('../', max(0, $depth - 1));
}
?>
