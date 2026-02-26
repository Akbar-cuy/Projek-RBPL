<?php
require_once 'config.php';

$mode = $_GET['mode'] ?? 'login'; // 'login' or 'register'
$error = '';
$success = '';

// REGISTER 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
  $name = trim($_POST['name'] ?? '');
  $username = trim($_POST['reg_username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $password = $_POST['reg_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if (!$name || !$username || !$email || !$password || !$confirm) {
    $error = 'Semua field wajib diisi.';
    $mode = 'register';
  } elseif (strlen($username) < 4) {
    $error = 'Username minimal 4 karakter.';
    $mode = 'register';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Format email tidak valid.';
    $mode = 'register';
  } elseif (strlen($password) < 6) {
    $error = 'Password minimal 6 karakter.';
    $mode = 'register';
  } elseif ($password !== $confirm) {
    $error = 'Konfirmasi password tidak cocok.';
    $mode = 'register';
  } else {
    $db = getDB();
    // cek username & email sudah ada
    $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username=? OR email=?");
    $check->execute([$username, $email]);
    if ($check->fetchColumn() > 0) {
      $cku = $db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
      $cku->execute([$username]);
      $error = $cku->fetchColumn() > 0 ? 'Username sudah digunakan.' : 'Email sudah terdaftar.';
      $mode = 'register';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $db->prepare("INSERT INTO users (name,username,password,email,phone,role) VALUES (?,?,?,?,?,'customer')")
        ->execute([$name, $username, $hash, $email, $phone]);
      $success = 'Akun berhasil dibuat! Silakan login.';
      $mode = 'login';
    }
  }
}

// LOGIN 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($username && $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['name'] = $user['name'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['email'] = $user['email'];
      $_SESSION['phone'] = $user['phone'];
      $_SESSION['location'] = $user['location'];
      switch ($user['role']) {
        case 'customer':
          header('Location: pages/customer/home.php');
          break;
        case 'kasir':
          header('Location: pages/kasir/dashboard.php');
          break;
        case 'chef':
          header('Location: pages/chef/kitchen.php');
          break;
        case 'operator':
          header('Location: pages/operator/schedule.php');
          break;
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
    case 'customer':
      header('Location: pages/customer/home.php');
      exit;
    case 'kasir':
      header('Location: pages/kasir/dashboard.php');
      exit;
    case 'chef':
      header('Location: pages/chef/kitchen.php');
      exit;
    case 'operator':
      header('Location: pages/operator/schedule.php');
      exit;
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>TursMovie – <?= $mode === 'register' ? 'Daftar Akun' : 'Sign In' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <style>
    /* ── reset & base ── */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(160deg, #e61515 0%, #b30000 50%, #4b0082 100%);
      padding: 24px 16px;
    }

    /* ── wrapper ── */
    .wrap {
      width: 100%;
      max-width: 430px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 28px;
    }

    /* ── logo ── */
    .logo-sec {
      text-align: center;
      color: white;
    }

    .logo-icon {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.96);
      border-radius: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      fill: #E30613;
    }

    .logo-sec h1 {
      font-size: 1.9rem;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .logo-sec p {
      font-size: 0.88rem;
      opacity: 0.8;
      margin-top: 4px;
    }

    /* ── card ── */
    .card {
      width: 100%;
      background: rgba(255, 255, 255, 0.97);
      border-radius: 24px;
      padding: 0;
      box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }

    /* ── tab switcher ── */
    .tab-bar {
      display: flex;
      border-bottom: 2px solid #f0f0f0;
    }

    .tab-btn {
      flex: 1;
      padding: 16px;
      font-size: 0.9rem;
      font-weight: 700;
      background: none;
      border: none;
      cursor: pointer;
      color: #aaa;
      font-family: inherit;
      transition: all 0.2s;
      border-bottom: 3px solid transparent;
      margin-bottom: -2px;
    }

    .tab-btn.active {
      color: #e61515;
      border-bottom-color: #e61515;
    }

    /* ── form body ── */
    .form-body {
      padding: 24px 28px 28px;
    }

    /* ── fields ── */
    .fg {
      margin-bottom: 16px;
    }

    .fg label {
      display: block;
      font-size: 0.82rem;
      font-weight: 700;
      color: #444;
      margin-bottom: 7px;
      letter-spacing: 0.2px;
    }

    .iw {
      position: relative;
    }

    .iw input {
      width: 100%;
      padding: 13px 16px;
      border: 1.5px solid #e8e8e8;
      border-radius: 12px;
      font-size: 0.92rem;
      font-family: inherit;
      background: #fafafa;
      transition: all 0.2s;
      outline: none;
      color: #333;
    }

    .iw input:focus {
      border-color: #e61515;
      background: white;
      box-shadow: 0 0 0 3px rgba(230, 21, 21, 0.08);
    }

    .iw input.err-inp {
      border-color: #ef4444;
    }

    .iw input::placeholder {
      color: #bbb;
    }

    .iw .icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      color: #ccc;
    }

    .iw.has-icon input {
      padding-left: 42px;
    }

    .eye-btn {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #ccc;
      padding: 4px;
      transition: color 0.15s;
    }

    .eye-btn:hover {
      color: #888;
    }

    /* 2-column row */
    .row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    /* ── btn ── */
    .btn-submit {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, #e61515, #c00);
      color: white;
      font-size: 1rem;
      font-weight: 700;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s;
      font-family: inherit;
      margin-top: 4px;
      letter-spacing: 0.2px;
    }

    .btn-submit:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 25px rgba(230, 21, 21, 0.4);
    }

    .btn-submit:active {
      transform: translateY(0);
    }

    /* ── alerts ── */
    .alert {
      padding: 12px 14px;
      border-radius: 10px;
      font-size: 0.84rem;
      font-weight: 500;
      margin-bottom: 18px;
      display: flex;
      align-items: flex-start;
      gap: 8px;
    }

    .alert-err {
      background: #fff0f0;
      border: 1px solid #ffc9c9;
      color: #c62828;
    }

    .alert-ok {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #166534;
    }

    /* ── divider ── */
    .divider {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 18px 0;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #eee;
    }

    .divider span {
      font-size: 0.78rem;
      color: #bbb;
      font-weight: 600;
    }

    /* ── hint ── */
    .hint {
      background: #f5f7ff;
      border-radius: 12px;
      padding: 12px 14px;
      margin-top: 16px;
      border: 1px solid #dde3ff;
    }

    .hint p {
      font-size: 0.76rem;
      color: #666;
      font-weight: 600;
      margin-bottom: 6px;
    }

    .hint-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
    }

    .hint span {
      display: inline-block;
      background: white;
      border-radius: 6px;
      padding: 3px 9px;
      font-size: 0.74rem;
      color: #444;
      border: 1px solid #ddd;
      font-family: monospace;
    }

    /* ── password strength ── */
    .pw-strength {
      margin-top: 6px;
      height: 4px;
      border-radius: 2px;
      background: #eee;
      overflow: hidden;
    }

    .pw-strength-bar {
      height: 100%;
      border-radius: 2px;
      transition: all 0.3s;
    }

    .pw-hint {
      font-size: 0.73rem;
      margin-top: 4px;
    }

    /* ── tos ── */
    .tos {
      font-size: 0.78rem;
      color: #888;
      text-align: center;
      margin-top: 14px;
      line-height: 1.6;
    }

    .tos a {
      color: #e61515;
      font-weight: 600;
    }

    /* ── panel transition ── */
    .panel {
      display: none;
    }

    .panel.active {
      display: block;
    }
  </style>
</head>

<body>
  <div class="wrap">

    <!-- Logo -->
    <div class="logo-sec">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24">
          <path
            d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4h-2l2 4h-3l-2-4H7L5 8H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z" />
        </svg>
      </div>
      <h1>TursMovie</h1>
      <p>Your Pocket Cinema</p>
    </div>

    <!-- Card -->
    <div class="card">

      <!-- Tab Bar -->
      <div class="tab-bar">
        <button class="tab-btn <?= $mode === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">Masuk</button>
        <button class="tab-btn <?= $mode === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Daftar</button>
      </div>

      <div class="form-body">

        <!-- ── ALERTS ── -->
        <?php if ($error): ?>
          <div class="alert alert-err">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
              style="flex-shrink:0;margin-top:1px">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-ok">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
              style="flex-shrink:0;margin-top:1px">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>

        <!-- ══════════ LOGIN PANEL ══════════ -->
        <div class="panel <?= $mode === 'login' ? 'active' : '' ?>" id="panel-login">
          <form method="POST" novalidate>
            <input type="hidden" name="action" value="login">

            <div class="fg">
              <label>Username</label>
              <div class="iw has-icon">
                <svg class="icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"
                  viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
                <input type="text" name="username" placeholder="Masukkan username"
                  value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" autofocus>
              </div>
            </div>

            <div class="fg">
              <label>Password</label>
              <div class="iw has-icon">
                <svg class="icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"
                  viewBox="0 0 24 24">
                  <rect x="3" y="11" width="18" height="11" rx="2" />
                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                <input type="password" name="password" id="pwd-login" placeholder="Masukkan password"
                  autocomplete="current-password">
                <button type="button" class="eye-btn" onclick="toggleEye('pwd-login',this)">
                  <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                </button>
              </div>
            </div>

            <button type="submit" class="btn-submit">Masuk</button>
          </form>
        </div>

        <!-- ══════════ REGISTER PANEL ══════════ -->
        <div class="panel <?= $mode === 'register' ? 'active' : '' ?>" id="panel-register">
          <form method="POST" novalidate id="regForm">
            <input type="hidden" name="action" value="register">

            <div class="fg">
              <label>Nama Lengkap <span style="color:#e61515">*</span></label>
              <div class="iw has-icon">
                <svg class="icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"
                  viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
                <input type="text" name="name" placeholder="Masukkan nama lengkap"
                  value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="name" maxlength="100">
              </div>
            </div>

            <div class="row-2">
              <div class="fg" style="margin-bottom:0">
                <label>Username <span style="color:#e61515">*</span></label>
                <div class="iw">
                  <input type="text" name="reg_username" id="reg_username" placeholder="Masukkan username"
                    value="<?= htmlspecialchars($_POST['reg_username'] ?? '') ?>" autocomplete="username" maxlength="50"
                    oninput="checkUsername(this)">
                </div>
                <div id="un-hint" style="font-size:0.72rem;margin-top:5px;color:#aaa"></div>
              </div>
              <div class="fg" style="margin-bottom:0">
                <label>No. HP</label>
                <div class="iw">
                  <input type="tel" name="phone" placeholder="+62 812-xxxx"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" autocomplete="tel" maxlength="20">
                </div>
              </div>
            </div>

            <div class="fg" style="margin-top:16px">
              <label>Email <span style="color:#e61515">*</span></label>
              <div class="iw has-icon">
                <svg class="icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"
                  viewBox="0 0 24 24">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                  <polyline points="22,6 12,13 2,6" />
                </svg>
                <input type="email" name="email" placeholder="Masukkan email"
                  value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" maxlength="100">
              </div>
            </div>

            <div class="fg">
              <label>Password <span style="color:#e61515">*</span></label>
              <div class="iw has-icon">
                <svg class="icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"
                  viewBox="0 0 24 24">
                  <rect x="3" y="11" width="18" height="11" rx="2" />
                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                <input type="password" name="reg_password" id="pwd-reg" placeholder="Masukkan password"
                  autocomplete="new-password" oninput="checkStrength(this.value)">
                <button type="button" class="eye-btn" onclick="toggleEye('pwd-reg',this)">
                  <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                </button>
              </div>
              <div class="pw-strength">
                <div class="pw-strength-bar" id="pwBar"></div>
              </div>
              <div class="pw-hint" id="pwHint" style="color:#aaa"></div>
            </div>

            <div class="fg">
              <label>Konfirmasi Password <span style="color:#e61515">*</span></label>
              <div class="iw has-icon">
                <svg class="icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"
                  viewBox="0 0 24 24">
                  <rect x="3" y="11" width="18" height="11" rx="2" />
                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                <input type="password" name="confirm_password" id="pwd-confirm" placeholder="Ulangi password"
                  autocomplete="new-password" oninput="checkConfirm()">
                <button type="button" class="eye-btn" onclick="toggleEye('pwd-confirm',this)">
                  <svg width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                </button>
              </div>
              <div id="confirmHint" style="font-size:0.73rem;margin-top:5px"></div>
            </div>

            <button type="submit" class="btn-submit" id="regBtn">Buat Akun</button>
          </form>

          <p class="tos">Dengan mendaftar, kamu menyetujui <a href="#">Syarat & Ketentuan</a> serta <a
              href="#">Kebijakan Privasi</a> TursMovie.</p>
        </div>

      </div><!-- /form-body -->
    </div><!-- /card -->
  </div><!-- /wrap -->

  <script>
    // ── Tab switch ──────────────────────────────────────────
    function switchTab(tab) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
      document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
      document.getElementById('panel-' + tab).classList.add('active');
    }

    // ── Toggle eye ──────────────────────────────────────────
    function toggleEye(id, btn) {
      const f = document.getElementById(id);
      f.type = f.type === 'password' ? 'text' : 'password';
      btn.style.color = f.type === 'text' ? '#e61515' : '#ccc';
    }

    // ── Fill demo login ─────────────────────────────────────
    function fillLogin(user) {
      document.querySelector('[name="username"]').value = user;
      document.querySelector('[name="password"]').value = 'password';
      switchTab('login');
    }

    // ── Password strength ────────────────────────────────────
    function checkStrength(val) {
      const bar = document.getElementById('pwBar');
      const hint = document.getElementById('pwHint');
      if (!val) { bar.style.width = '0'; hint.textContent = ''; return; }
      let score = 0;
      if (val.length >= 6) score++;
      if (val.length >= 10) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;
      const levels = [
        { w: '20%', c: '#ef4444', t: 'Sangat lemah' },
        { w: '40%', c: '#f97316', t: 'Lemah' },
        { w: '60%', c: '#f59e0b', t: 'Sedang' },
        { w: '80%', c: '#84cc16', t: 'Kuat' },
        { w: '100%', c: '#22c55e', t: 'Sangat kuat' },
      ];
      const lvl = levels[Math.min(score, 4)];
      bar.style.width = lvl.w;
      bar.style.background = lvl.c;
      hint.textContent = lvl.t;
      hint.style.color = lvl.c;
      checkConfirm();
    }

    // ── Confirm match ────────────────────────────────────────
    function checkConfirm() {
      const pw = document.getElementById('pwd-reg').value;
      const cf = document.getElementById('pwd-confirm').value;
      const hint = document.getElementById('confirmHint');
      if (!cf) { hint.textContent = ''; return; }
      if (pw === cf) {
        hint.textContent = '✓ Password cocok';
        hint.style.color = '#22c55e';
      } else {
        hint.textContent = '✗ Password tidak cocok';
        hint.style.color = '#ef4444';
      }
    }

    // ── Username hint ─────────────────────────────────────────
    function checkUsername(el) {
      const val = el.value;
      const hint = document.getElementById('un-hint');
      if (!val) { hint.textContent = ''; return; }
      if (val.length < 4) {
        hint.textContent = 'Min. 4 karakter'; hint.style.color = '#f97316';
      } else if (/^[a-zA-Z0-9_]+$/.test(val)) {
        hint.textContent = '✓ Username valid'; hint.style.color = '#22c55e';
      } else {
        hint.textContent = 'Hanya huruf, angka, underscore'; hint.style.color = '#ef4444';
      }
    }
  </script>
</body>

</html>