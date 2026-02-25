<?php
require_once 'config.php';

$mode = $_GET['mode'] ?? 'login'; // 'login' or 'register'
$error = '';
$success = '';

// REGISTER 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['reg_username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name || !$username || !$email || !$password || !$confirm) {
        $error = 'Semua field wajib diisi.';
        $mode  = 'register';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
        $mode  = 'register';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
        $mode  = 'register';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
        $mode  = 'register';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
        $mode  = 'register';
    } else {
        $db = getDB();
        // cek username & email sudah ada
        $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username=? OR email=?");
        $check->execute([$username, $email]);
        if ($check->fetchColumn() > 0) {
            $cku = $db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
            $cku->execute([$username]);
            $error = $cku->fetchColumn() > 0 ? 'Username sudah digunakan.' : 'Email sudah terdaftar.';
            $mode  = 'register';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (name,username,password,email,phone,role) VALUES (?,?,?,?,?,'customer')")
               ->execute([$name, $username, $hash, $email, $phone]);
            $success = 'Akun berhasil dibuat! Silakan login.';
            $mode    = 'login';
        }
    }
}

// LOGIN 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username=?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['name']     = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['phone']    = $user['phone'];
            $_SESSION['location'] = $user['location'];
            switch ($user['role']) {
                case 'customer': header('Location: pages/customer/home.php'); break;
                case 'kasir':    header('Location: pages/kasir/dashboard.php'); break;
                case 'chef':     header('Location: pages/chef/kitchen.php'); break;
                case 'operator': header('Location: pages/operator/schedule.php'); break;
            }
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua field.';
    }
}

// redirect jika sudah login
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'customer': header('Location: pages/customer/home.php'); exit;
        case 'kasir':    header('Location: pages/kasir/dashboard.php'); exit;
        case 'chef':     header('Location: pages/chef/kitchen.php'); exit;
        case 'operator': header('Location: pages/operator/schedule.php'); exit;
    }
}
?>