<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('kasir');

$db = getDB();
$today = date('Y-m-d');

$salesToday = $db->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=? AND payment_status='paid'");
$salesToday->execute([$today]);
$salesToday = $salesToday->fetchColumn();
$transToday = $db->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=?");
$transToday->execute([$today]);
$transToday = $transToday->fetchColumn();
$ticketsSold = $db->prepare("SELECT COUNT(*) FROM order_seats os JOIN orders o ON os.order_id=o.id WHERE DATE(o.created_at)=?");
$ticketsSold->execute([$today]);
$ticketsSold = $ticketsSold->fetchColumn();
$fnbSold = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM order_fnb of JOIN orders o ON of.order_id=o.id WHERE DATE(o.created_at)=?");
$fnbSold->execute([$today]);
$fnbSold = $fnbSold->fetchColumn();
$recentOrders = $db->query("SELECT o.*, u.name as cname, f.title FROM orders o JOIN users u ON o.user_id=u.id JOIN showtimes s ON o.showtime_id=s.id JOIN films f ON s.film_id=f.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
$topFilms = $db->query("SELECT f.title, COUNT(os.id) as tickets, COALESCE(SUM(o.total_amount),0) as revenue FROM order_seats os JOIN orders o ON os.order_id=o.id JOIN showtimes s ON o.showtime_id=s.id JOIN films f ON s.film_id=f.id GROUP BY f.id ORDER BY tickets DESC LIMIT 4")->fetchAll();
$pendingCount = $db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - TursMovie Kasir</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --red: #e61515;
      --bg-dark: #0f1117;
      --bg-card: #1a1d27;
      --bg-card2: #21253a;
      --text: #f0f0f0;
      --text-muted: #8892a4;
      --border: rgba(255, 255, 255, 0.08);
      --green: #22c55e;
      --yellow: #f59e0b;
      --blue: #3b82f6;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg-dark);
      color: var(--text);
      min-height: 100vh;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .admin-layout {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 240px;
      flex-shrink: 0;
      background: #0a0c14;
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      z-index: 200;
      transition: transform 0.3s;
      overflow-y: auto;
    }

    .sb-hdr {
      padding: 18px 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .sb-logo {
      background: var(--red);
      border-radius: 8px;
      padding: 5px 9px;
      font-weight: 800;
      color: white;
      font-size: 0.9rem;
    }

    .sb-user {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
    }

    .sb-av {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e61515, #7c3aed);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.875rem;
      flex-shrink: 0;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 20px;
      color: var(--text-muted);
      font-size: 0.875rem;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.15s;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text);
    }

    .nav-link.active {
      background: rgba(230, 21, 21, 0.1);
      color: var(--red);
      border-right: 3px solid var(--red);
    }

    .nav-badge {
      background: var(--red);
      color: white;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 10px;
      margin-left: auto;
    }

    .sb-logout {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 20px;
      color: var(--text-muted);
      font-size: 0.875rem;
      text-decoration: none;
      border-top: 1px solid var(--border);
    }

    .sb-logout:hover {
      color: #ef4444;
    }

    .main-content {
      margin-left: 240px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .topbar {
      background: var(--bg-card);
      border-bottom: 1px solid var(--border);
      padding: 14px 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .topbar-title {
      font-size: 1rem;
      font-weight: 700;
      flex: 1;
    }

    .hamburger {
      display: none;
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text);
    }

    .page-content {
      padding: 24px;
      flex: 1;
    }

    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 199;
    }

    .overlay.show {
      display: block;
    }

    @media(max-width:900px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }

      .hamburger {
        display: flex;
      }
    }

    .badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.7rem;
      font-weight: 700;
    }

    .badge-green {
      background: rgba(34, 197, 94, 0.15);
      color: #22c55e;
    }

    .badge-yellow {
      background: rgba(245, 158, 11, 0.15);
      color: #f59e0b;
    }

    .badge-red {
      background: rgba(230, 21, 21, 0.15);
      color: #e61515;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 18px;
    }

    .stat-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
    }

    .stat-label {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 4px;
    }

    .stat-value {
      font-size: 1.5rem;
      font-weight: 800;
    }

    .stat-trend {
      font-size: 0.78rem;
      font-weight: 600;
      margin-top: 4px;
    }

    .trend-up {
      color: #22c55e;
    }

    .trend-down {
      color: #ef4444;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 16px;
    }

    @media(max-width:768px) {
      .grid-2 {
        grid-template-columns: 1fr;
      }
    }

    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      margin-bottom: 16px;
    }

    .card-hdr {
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .card-hdr h3 {
      font-size: 0.9rem;
      font-weight: 700;
    }

    .card-hdr a {
      font-size: 0.8rem;
      color: var(--red);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    td {
      padding: 11px 16px;
      font-size: 0.875rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      vertical-align: middle;
    }

    tr:last-child td {
      border-bottom: none;
    }
  </style>
</head>

<body>
  <div class="admin-layout">

    <div class="sidebar" id="sidebar">
      <div class="sb-hdr">
        <span class="sb-logo">TM</span>
        <div>
          <div style="font-weight:700;font-size:0.9rem">TursMovie</div>
          <div style="font-size:0.72rem;color:var(--text-muted)">Kasir Panel</div>
        </div>
      </div>
      <div class="sb-user">
        <div class="sb-av"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
        <div>
          <div style="font-size:0.875rem;font-weight:600"><?= htmlspecialchars($_SESSION['name']) ?></div>
          <div style="font-size:0.72rem;color:var(--text-muted)">Kasir</div>
        </div>
      </div>
      <nav style="flex:1;padding:10px 0">
        <a href="dashboard.php" class="nav-link active"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
          </svg>Dashboard</a>
        <a href="orders.php" class="nav-link"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="9" cy="21" r="1" />
            <circle cx="20" cy="21" r="1" />
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
          </svg>Pesanan<?php if ($pendingCount > 0): ?><span class="nav-badge"><?= $pendingCount ?></span><?php endif; ?></a>
        <a href="new_booking.php" class="nav-link"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="16" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>Booking Baru</a>
        <a href="bookings.php" class="nav-link"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="8" y1="6" x2="21" y2="6" />
            <line x1="8" y1="12" x2="21" y2="12" />
            <line x1="8" y1="18" x2="21" y2="18" />
            <line x1="3" y1="6" x2="3.01" y2="6" />
            <line x1="3" y1="12" x2="3.01" y2="12" />
            <line x1="3" y1="18" x2="3.01" y2="18" />
          </svg>Daftar Booking</a>
        <a href="films.php" class="nav-link ' . ($active === 'films' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0" />
          </svg> Kelola Film</a>
        <a href="menu.php" class="nav-link '.($active==='menu'?'active':'').'"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg> Kelola Menu</a>
        <a href="reports.php" class="nav-link"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="18" y1="20" x2="18" y2="10" />
            <line x1="12" y1="20" x2="12" y2="4" />
            <line x1="6" y1="20" x2="6" y2="14" />
          </svg>Laporan</a>
        <a href="settings.php" class="nav-link"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
          </svg>Pengaturan</a>
      </nav>
      <a href="../../logout.php" class="sb-logout"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" y1="12" x2="9" y2="12" />
        </svg>Keluar</a>
    </div>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <div class="main-content">
      <div class="topbar">
        <button class="hamburger" onclick="toggleSidebar()">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
          </svg>
        </button>
        <span class="topbar-title">Dashboard Kasir</span>
        <span style="font-size:0.8rem;color:var(--text-muted)"><?= date('d M Y') ?></span>
      </div>

      <div class="page-content">
        <h1 style="font-size:1.4rem;font-weight:800;margin-bottom:4px">Selamat Datang, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>! 👋</h1>
        <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:24px">Ringkasan aktivitas hari ini.</p>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,0.15)"><svg width="22" height="22" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                <polyline points="17 6 23 6 23 12" />
              </svg></div>
            <div class="stat-label">Penjualan Hari Ini</div>
            <div class="stat-value" style="font-size:1.2rem"><?= formatRupiah($salesToday) ?></div>
            <div class="stat-trend trend-up">↑ 12.5% dari kemarin</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,0.15)"><svg width="22" height="22" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
              </svg></div>
            <div class="stat-label">Total Transaksi</div>
            <div class="stat-value"><?= $transToday ?></div>
            <div class="stat-trend trend-up">↑ +8 dari kemarin</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background:rgba(168,85,247,0.15)"><svg width="22" height="22" fill="none" stroke="#a855f7" stroke-width="2" viewBox="0 0 24 24">
                <rect x="2" y="7" width="20" height="14" rx="2" />
                <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
              </svg></div>
            <div class="stat-label">Tiket Terjual</div>
            <div class="stat-value"><?= $ticketsSold ?></div>
            <div class="stat-trend trend-up">↑ +15 dari kemarin</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,0.15)"><svg width="22" height="22" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                <line x1="3" y1="6" x2="21" y2="6" />
              </svg></div>
            <div class="stat-label">F&B Terjual</div>
            <div class="stat-value"><?= $fnbSold ?></div>
            <div class="stat-trend trend-down">↓ -3 dari kemarin</div>
          </div>
        </div>

        <div class="grid-2">
          <div class="card">
            <div class="card-hdr">
              <h3>Pesanan Terbaru</h3><a href="orders.php">Lihat Semua →</a>
            </div>
            <table>
              <tbody>
                <?php if (empty($recentOrders)): ?>
                  <tr>
                    <td style="text-align:center;color:var(--text-muted);padding:24px">Belum ada pesanan</td>
                  </tr>
                  <?php else: foreach ($recentOrders as $o): ?>
                    <tr>
                      <td>
                        <div style="font-weight:600;font-size:0.83rem"><?= htmlspecialchars($o['cname']) ?></div>
                        <div style="font-size:0.72rem;color:var(--text-muted)"><?= htmlspecialchars($o['title']) ?></div>
                      </td>
                      <td style="text-align:right">
                        <div style="font-weight:700;font-size:0.83rem"><?= formatRupiah($o['total_amount']) ?></div>
                        <span class="badge <?= $o['payment_status'] === 'paid' ? 'badge-green' : ($o['payment_status'] === 'pending' ? 'badge-yellow' : 'badge-red') ?>"><?= $o['payment_status'] === 'paid' ? 'Lunas' : ($o['payment_status'] === 'pending' ? 'Pending' : 'Gagal') ?></span>
                      </td>
                    </tr>
                <?php endforeach;
                endif; ?>
              </tbody>
            </table>
          </div>

          <div class="card">
            <div class="card-hdr">
              <h3>Film Terlaris</h3><a href="reports.php">Lihat Laporan →</a>
            </div>
            <div style="padding:4px 0">
              <?php if (empty($topFilms)): ?>
                <div style="text-align:center;color:var(--text-muted);padding:24px;font-size:0.875rem">Belum ada data</div>
                <?php else: $rank = 1;
                $clrs = ['#e61515', '#f59e0b', '#3b82f6', '#22c55e'];
                foreach ($topFilms as $tf): ?>
                  <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border)">
                    <div style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;background:<?= $clrs[$rank - 1] ?>22;color:<?= $clrs[$rank - 1] ?>;flex-shrink:0"><?= $rank ?></div>
                    <div style="flex:1;min-width:0">
                      <div style="font-size:0.83rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($tf['title']) ?></div>
                      <div style="font-size:0.72rem;color:var(--text-muted)"><?= $tf['tickets'] ?> tiket</div>
                    </div>
                    <span style="font-size:0.82rem;font-weight:700;color:var(--red);flex-shrink:0"><?= formatRupiah($tf['revenue']) ?></span>
                  </div>
              <?php $rank++;
                endforeach;
              endif; ?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-hdr">
            <h3>Aksi Cepat</h3>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;padding:16px">
            <?php $qa = [['orders.php', 'Proses Pesanan', '#e61515', 'M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18'], ['bookings.php', 'Daftar Booking', '#3b82f6', 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'], ['menu.php', 'Kelola Menu', '#f59e0b', 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'], ['reports.php', 'Lihat Laporan', '#22c55e', 'M18 20V10M12 20V4M6 20v-6']];
            foreach ($qa as [$href, $lbl, $clr, $d]): ?>
              <a href="<?= $href ?>" style="background:var(--bg-card2);border:1.5px solid var(--border);border-radius:12px;padding:14px 10px;text-align:center;display:block;color:var(--text);"
                onmouseover="this.style.borderColor='<?= $clr ?>'" onmouseout="this.style.borderColor='var(--border)'">
                <div style="width:36px;height:36px;border-radius:10px;background:<?= $clr ?>22;margin:0 auto 8px;display:flex;align-items:center;justify-content:center">
                  <svg width="18" height="18" fill="none" stroke="<?= $clr ?>" stroke-width="2" viewBox="0 0 24 24">
                    <path d="<?= $d ?>" />
                  </svg>
                </div>
                <span style="font-size:0.78rem;font-weight:600"><?= $lbl ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('overlay').classList.toggle('show');
    }

    function closeSidebar() {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('overlay').classList.remove('show');
    }
  </script>
</body>

</html>