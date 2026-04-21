<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
require_once '../../includes/kasir_nav.php';
requireRole('kasir');

$db = getDB();

// Filters
$filterFilm  = $_GET['film'] ?? '';
$filterFnb   = $_GET['fnb'] ?? '';
$filterTab   = $_GET['tab'] ?? 'ticket'; // 'ticket' | 'fnb'
$search      = trim($_GET['q'] ?? '');

// ── TICKET QUERY ─────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
if ($filterFilm) {
  $where[]  = 'f.id = ?';
  $params[] = $filterFilm;
}
if ($search && $filterTab === 'ticket') {
  $where[]  = '(u.name LIKE ? OR o.id LIKE ?)';
  $params[] = "%$search%";
  $params[] = "%$search%";
}

$sqlTicket = "SELECT o.id, o.total_amount, o.payment_method, o.payment_status,
                     o.order_status, o.created_at, o.guest_name,
                     u.name AS cname, f.title, f.id AS film_id,
                     s.show_date, s.show_time, s.theater
              FROM orders o
              LEFT JOIN users u    ON o.user_id      = u.id
              JOIN showtimes s     ON o.showtime_id  = s.id
              JOIN films f         ON s.film_id       = f.id
              WHERE " . implode(' AND ', $where) . "
              ORDER BY o.created_at DESC";

$stmt     = $db->prepare($sqlTicket);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// ── FNB QUERY ─────────────────────────────────────────────────────────────────
$fnbWhere  = ['1=1'];
$fnbParams = [];

if ($filterFnb) {
  $fnbWhere[]  = 'fm.category = ?';
  $fnbParams[] = $filterFnb;
}

if ($search && $filterTab === 'fnb') {
  // Tambahkan pencarian ke guest_name juga
  $fnbWhere[]  = '(u.name LIKE ? OR o.guest_name LIKE ? OR of2.order_id LIKE ? OR fm.name LIKE ?)';
  $fnbParams[] = "%$search%";
  $fnbParams[] = "%$search%";
  $fnbParams[] = "%$search%";
  $fnbParams[] = "%$search%";
}

$sqlFnb = "SELECT of2.id AS fnb_line_id,
                  of2.order_id, of2.quantity, of2.price AS line_price,
                  of2.cook_status, o.created_at, o.guest_name, o.user_id,
                  o.payment_method, o.payment_status,
                  fm.name AS item_name, fm.category,
                  u.name AS cname
           FROM order_fnb of2
           JOIN orders   o   ON of2.order_id = o.id
           JOIN fnb_menu fm  ON of2.fnb_id   = fm.id
           LEFT JOIN users u ON o.user_id    = u.id 
           WHERE " . implode(' AND ', $fnbWhere) . "
           ORDER BY o.created_at DESC";

$fnbStmt = $db->prepare($sqlFnb);
$fnbStmt->execute($fnbParams);
$fnbOrders = $fnbStmt->fetchAll();

// ── FILM LIST ─────────────────────────────────────────────────────────────────
$films = $db->query("SELECT id, title FROM films ORDER BY title")->fetchAll();

// ── FILM STATS ────────────────────────────────────────────────────────────────
$filmStats = $db->query("
    SELECT f.id, f.title, f.genre,
           COUNT(os.id)              AS bookings,
           COALESCE(SUM(o.total_amount),0) AS revenue
    FROM films f
    LEFT JOIN showtimes s ON s.film_id  = f.id
    LEFT JOIN orders o    ON o.showtime_id = s.id AND o.payment_status = 'paid'
    LEFT JOIN order_seats os ON os.order_id = o.id
    GROUP BY f.id
    ORDER BY bookings DESC
")->fetchAll();

// ── FNB STATS ─────────────────────────────────────────────────────────────────
$fnbStats = $db->query("
    SELECT fm.name, fm.category,
           SUM(of2.quantity)              AS qty_sold,
           SUM(of2.quantity * of2.price)  AS revenue
    FROM order_fnb of2
    JOIN fnb_menu fm ON of2.fnb_id = fm.id
    JOIN orders o    ON of2.order_id = o.id AND o.payment_status = 'paid'
    GROUP BY fm.id
    ORDER BY qty_sold DESC
")->fetchAll();

// ── MISC ──────────────────────────────────────────────────────────────────────
$pendingCount = $db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();

// FnB summary numbers
$fnbTotalItems   = array_sum(array_column($fnbOrders, 'quantity'));
$fnbTotalRevenue = array_sum(array_map(fn($r) => $r['quantity'] * $r['line_price'], $fnbOrders));
$fnbDone         = count(array_filter($fnbOrders, fn($r) => $r['cook_status'] === 'done'));
$fnbPending      = count(array_filter($fnbOrders, fn($r) => in_array($r['cook_status'], ['new', 'cooking'])));

$cookLabel = ['new' => 'Antrian', 'cooking' => 'Dimasak', 'ready' => 'Siap', 'done' => 'Selesai'];
$cookBadge = ['new' => 'badge-yellow', 'cooking' => 'badge-blue', 'ready' => 'badge-green', 'done' => 'badge-green'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Booking - TursMovie Kasir</title>
  <?= getBaseStyles() ?>
  <style>
    <?php include '../../includes/admin_styles.php'; ?> :root {
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
      --orange: #f97316;
    }

    /* Layout */
    .admin-layout {
      overflow-x: hidden;
    }

    .main-content {
      max-width: 100vw;
      overflow-x: hidden;
    }

    .page-content {
      padding: 24px;
      flex: 1;
    }

    /* Tabs */
    .tab-bar {
      display: flex;
      gap: 4px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 5px;
      margin-bottom: 20px;
      width: fit-content;
    }

    .tab-btn {
      padding: 9px 20px;
      border-radius: 9px;
      font-size: 0.85rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      border: none;
      background: transparent;
      color: var(--text-muted);
      transition: all 0.15s;
      display: flex;
      align-items: center;
      gap: 7px;
      text-decoration: none;
    }

    .tab-btn:hover {
      color: var(--text);
      background: rgba(255, 255, 255, 0.05);
    }

    .tab-btn.active {
      background: var(--red);
      color: white;
    }

    .tab-btn.active.fnb-tab {
      background: var(--orange);
    }

    /* Badges */
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

    .badge-blue {
      background: rgba(59, 130, 246, 0.15);
      color: #3b82f6;
    }

    .badge-orange {
      background: rgba(249, 115, 22, 0.15);
      color: #f97316;
    }

    /* Stat Row */
    .stat-row {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 12px;
      margin-bottom: 20px;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 14px;
    }

    .stat-label {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-bottom: 4px;
    }

    .stat-value {
      font-size: 1.3rem;
      font-weight: 800;
    }

    /* Search */
    .search-bar {
      display: flex;
      gap: 10px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }

    .search-bar input,
    .search-bar select {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 10px 14px;
      color: var(--text);
      font-size: 0.875rem;
      font-family: inherit;
      outline: none;
    }

    .search-bar input {
      flex: 1;
      min-width: 180px;
    }

    .search-bar input:focus,
    .search-bar select:focus {
      border-color: var(--red);
    }

    .search-bar select option {
      background: #1a1d27;
    }

    /* Buttons */
    .btn {
      padding: 10px 18px;
      border-radius: 10px;
      font-size: 0.875rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      border: none;
      transition: all 0.15s;
    }

    .btn-primary {
      background: var(--red);
      color: white;
    }

    .btn-primary:hover {
      background: #c00;
    }

    .btn-orange {
      background: var(--orange);
      color: white;
    }

    .btn-orange:hover {
      background: #ea6c0c;
    }

    /* Cards */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      margin-bottom: 20px;
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

    /* Tables */
    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      padding: 10px 16px;
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--text-muted);
      text-align: left;
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }

    td {
      padding: 12px 16px;
      font-size: 0.875rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      vertical-align: middle;
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    /* Seat chips */
    .seat-chip {
      display: inline-block;
      background: var(--bg-card2);
      border: 1px solid var(--border);
      border-radius: 5px;
      padding: 1px 6px;
      font-size: 0.72rem;
      font-weight: 700;
      margin: 1px;
    }

    /* Category pill */
    .cat-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.7rem;
      font-weight: 700;
    }

    .cat-popcorn {
      background: rgba(249, 115, 22, 0.15);
      color: #f97316;
    }

    .cat-drinks {
      background: rgba(59, 130, 246, 0.15);
      color: #3b82f6;
    }

    .cat-snacks {
      background: rgba(168, 85, 247, 0.15);
      color: #a855f7;
    }

    /* Empty */
    .empty-state {
      text-align: center;
      padding: 40px;
      color: var(--text-muted);
    }

    /* Mobile */
    @media(max-width:768px) {
      .page-content {
        padding: 16px;
      }

      .stat-row {
        grid-template-columns: 1fr 1fr;
      }

      .search-bar input {
        min-width: 0;
        width: 100%;
      }

      .hide-mobile {
        display: none;
      }

      table {
        font-size: 0.8rem;
      }

      td,
      th {
        padding: 10px;
      }

      .tab-bar {
        width: 100%;
      }

      .tab-btn {
        flex: 1;
        justify-content: center;
      }
    }
  </style>
</head>

<body>
  <div class="admin-layout">
    <?php kasirNav('bookings'); ?>
    <div class="overlay" id="ov" onclick="this.classList.remove('show');document.querySelector('.sidebar').classList.remove('open')"></div>

    <div class="main-content">
      <div class="topbar">
        <button class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('open');document.getElementById('ov').classList.toggle('show')">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
          </svg>
        </button>
        <span class="topbar-title">Daftar Booking</span>
        <span style="font-size:0.8rem;color:var(--text-muted)">
          <?= $filterTab === 'ticket' ? count($bookings) . ' transaksi tiket' : count($fnbOrders) . ' item F&B' ?>
        </span>
      </div>

      <div class="page-content">
        <h1 style="font-size:1.3rem;font-weight:800;margin-bottom:4px">Daftar Booking</h1>
        <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:20px">Riwayat semua transaksi tiket &amp; makanan/minuman.</p>

        <!-- Tab Bar -->
        <div class="tab-bar">
          <a href="?tab=ticket<?= $filterFilm ? '&film=' . $filterFilm : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
            class="tab-btn <?= $filterTab === 'ticket' ? 'active' : '' ?>">
            🎟️ Tiket
          </a>
          <a href="?tab=fnb<?= $filterFnb ? '&fnb=' . $filterFnb : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
            class="tab-btn fnb-tab <?= $filterTab === 'fnb' ? 'active' : '' ?>">
            🍿 Makanan &amp; Minuman
          </a>
        </div>

        <?php if ($filterTab === 'ticket'): ?>
          <!-- ═══════════════ TAB: TIKET ═══════════════ -->

          <!-- Summary Stats -->
          <div class="stat-row">
            <?php
            $totalRevenue = array_sum(array_column(
              array_filter($bookings, fn($b) => $b['payment_status'] === 'paid'),
              'total_amount'
            ));
            $confirmed = count(array_filter($bookings, fn($b) => $b['order_status'] === 'confirmed'));
            $pending   = count(array_filter($bookings, fn($b) => $b['order_status'] === 'pending'));
            ?>
            <div class="stat-card">
              <div class="stat-label">Total Booking</div>
              <div class="stat-value"><?= count($bookings) ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Pendapatan</div>
              <div class="stat-value" style="font-size:1rem"><?= formatRupiah($totalRevenue) ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Terkonfirmasi</div>
              <div class="stat-value" style="color:#22c55e"><?= $confirmed ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Menunggu</div>
              <div class="stat-value" style="color:#f59e0b"><?= $pending ?></div>
            </div>
          </div>

          <!-- Search & Filter -->
          <form method="GET" class="search-bar">
            <input type="hidden" name="tab" value="ticket">
            <input type="text" name="q" placeholder="🔍 Cari nama pelanggan / ID order..."
              value="<?= htmlspecialchars($search) ?>">
            <select name="film">
              <option value="">Semua Film</option>
              <?php foreach ($films as $f): ?>
                <option value="<?= $f['id'] ?>" <?= $filterFilm == $f['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($f['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($search || $filterFilm): ?>
              <a href="?tab=ticket" class="btn" style="background:var(--bg-card);border:1px solid var(--border)">Reset</a>
            <?php endif; ?>
          </form>

          <!-- Bookings Table -->
          <div class="card">
            <div class="card-hdr">
              <h3>Riwayat Transaksi Tiket</h3>
              <span style="font-size:0.8rem;color:var(--text-muted)"><?= count($bookings) ?> hasil</span>
            </div>
            <?php if (empty($bookings)): ?>
              <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:0.4">
                  <circle cx="11" cy="11" r="8" />
                  <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <p>Tidak ada data booking ditemukan.</p>
                <?php if ($search || $filterFilm): ?>
                  <p style="font-size:0.8rem;margin-top:6px"><a href="?tab=ticket" style="color:var(--red)">Reset filter</a></p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div style="overflow-x:auto">
                <table>
                  <thead>
                    <tr>
                      <th>ID / Tanggal</th>
                      <th>Pelanggan</th>
                      <th class="hide-mobile">Film</th>
                      <th class="hide-mobile">Jadwal</th>
                      <th>Kursi</th>
                      <th>Total</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($bookings as $b):
                      $seatStmt = $db->prepare("SELECT seat_code FROM order_seats WHERE order_id=?");
                      $seatStmt->execute([$b['id']]);
                      $seats = array_column($seatStmt->fetchAll(), 'seat_code');
                    ?>
                      <tr>
                        <td>
                          <div style="font-weight:700;font-size:0.8rem;color:var(--red)"><?= htmlspecialchars($b['id']) ?></div>
                          <div style="font-size:0.72rem;color:var(--text-muted)"><?= date('d M Y, H:i', strtotime($b['created_at'])) ?></div>
                        </td>
                        <td>
                          <div style="font-weight:600"><?= htmlspecialchars($b['cname'] ?? $b['guest_name']) ?></div>
                          <div style="font-size:0.75rem;color:var(--text-muted)">Via <?= strtoupper($b['payment_method']) ?></div>
                        </td>
                        <td class="hide-mobile" style="font-size:0.83rem"><?= htmlspecialchars($b['title']) ?></td>
                        <td class="hide-mobile" style="font-size:0.78rem;color:var(--text-muted)">
                          <?= date('d M Y', strtotime($b['show_date'])) ?><br>
                          <?= substr($b['show_time'], 0, 5) ?> · <?= htmlspecialchars($b['theater']) ?>
                        </td>
                        <td>
                          <?php foreach ($seats as $sc): ?>
                            <span class="seat-chip"><?= htmlspecialchars($sc) ?></span>
                          <?php endforeach; ?>
                          <?php if (empty($seats)): ?><span style="color:var(--text-muted);font-size:0.75rem">-</span><?php endif; ?>
                        </td>
                        <td style="font-weight:700;color:var(--red);white-space:nowrap"><?= formatRupiah($b['total_amount']) ?></td>
                        <td>
                          <span class="badge <?= $b['order_status'] === 'confirmed' ? 'badge-green' : ($b['order_status'] === 'pending' ? 'badge-yellow' : 'badge-red') ?>">
                            <?= $b['order_status'] === 'confirmed' ? 'Konfirmasi' : ($b['order_status'] === 'pending' ? 'Pending' : 'Batal') ?>
                          </span><br>
                          <span class="badge <?= $b['payment_status'] === 'paid' ? 'badge-blue' : 'badge-yellow' ?>" style="margin-top:3px">
                            <?= $b['payment_status'] === 'paid' ? 'Lunas' : 'Belum' ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Film Stats -->
          <div class="card">
            <div class="card-hdr">
              <h3>Statistik Per Film</h3>
            </div>
            <table>
              <thead>
                <tr>
                  <th>Film</th>
                  <th>Genre</th>
                  <th>Tiket Terjual</th>
                  <th>Pendapatan</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($filmStats as $fs): ?>
                  <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($fs['title']) ?></td>
                    <td style="font-size:0.8rem;color:var(--text-muted)"><?= htmlspecialchars($fs['genre']) ?></td>
                    <td>
                      <div style="display:flex;align-items:center;gap:8px">
                        <div style="flex:1;max-width:80px;height:6px;background:var(--border);border-radius:3px;overflow:hidden">
                          <?php $maxB = max(array_column($filmStats, 'bookings') ?: [1]); ?>
                          <div style="width:<?= $maxB > 0 ? round($fs['bookings'] / $maxB * 100) : 0 ?>%;height:100%;background:var(--red);border-radius:3px"></div>
                        </div>
                        <span style="font-weight:700"><?= $fs['bookings'] ?></span>
                      </div>
                    </td>
                    <td style="font-weight:700;color:var(--red)"><?= formatRupiah($fs['revenue']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        <?php else: ?>
          <!-- ═══════════════ TAB: F&B ═══════════════ -->

          <!-- F&B Summary Stats -->
          <div class="stat-row">
            <div class="stat-card">
              <div class="stat-label">Total Item Dipesan</div>
              <div class="stat-value" style="color:var(--orange)"><?= $fnbTotalItems ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Pendapatan F&amp;B</div>
              <div class="stat-value" style="font-size:1rem"><?= formatRupiah($fnbTotalRevenue) ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Selesai</div>
              <div class="stat-value" style="color:#22c55e"><?= $fnbDone ?></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Dalam Proses</div>
              <div class="stat-value" style="color:#f59e0b"><?= $fnbPending ?></div>
            </div>
          </div>

          <!-- Search & Filter F&B -->
          <form method="GET" class="search-bar">
            <input type="hidden" name="tab" value="fnb">
            <input type="text" name="q" placeholder="🔍 Cari nama pelanggan / ID order / menu..."
              value="<?= htmlspecialchars($search) ?>">
            <select name="fnb">
              <option value="">Semua Kategori</option>
              <option value="popcorn" <?= $filterFnb === 'popcorn'  ? 'selected' : '' ?>>🍿 Popcorn</option>
              <option value="drinks" <?= $filterFnb === 'drinks'   ? 'selected' : '' ?>>🥤 Minuman</option>
              <option value="snacks" <?= $filterFnb === 'snacks'   ? 'selected' : '' ?>>🌮 Snacks</option>
            </select>
            <button type="submit" class="btn btn-orange">Filter</button>
            <?php if ($search || $filterFnb): ?>
              <a href="?tab=fnb" class="btn" style="background:var(--bg-card);border:1px solid var(--border)">Reset</a>
            <?php endif; ?>
          </form>

          <!-- F&B Transactions Table -->
          <div class="card">
            <div class="card-hdr">
              <h3>Riwayat Transaksi Makanan &amp; Minuman</h3>
              <span style="font-size:0.8rem;color:var(--text-muted)"><?= count($fnbOrders) ?> item</span>
            </div>
            <?php if (empty($fnbOrders)): ?>
              <div class="empty-state">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:0.4">
                  <circle cx="11" cy="11" r="8" />
                  <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <p>Tidak ada data F&amp;B ditemukan.</p>
                <?php if ($search || $filterFnb): ?>
                  <p style="font-size:0.8rem;margin-top:6px"><a href="?tab=fnb" style="color:var(--orange)">Reset filter</a></p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div style="overflow-x:auto">
                <table>
                  <thead>
                    <tr>
                      <th>ID Order / Tanggal</th>
                      <th>Pelanggan</th>
                      <th>Menu</th>
                      <th>Kategori</th>
                      <th>Qty</th>
                      <th>Subtotal</th>
                      <th>Status Masak</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($fnbOrders as $r): ?>
                      <tr>
                        <td>
                          <div style="font-weight:700;font-size:0.8rem;color:var(--orange)"><?= htmlspecialchars($r['order_id']) ?></div>
                          <div style="font-size:0.72rem;color:var(--text-muted)"><?= date('d M Y, H:i', strtotime($r['created_at'])) ?></div>
                        </td>
                        <td>
                          <div style="font-weight:600">
                            <?= htmlspecialchars($r['cname'] ?: $r['guest_name']) ?>
                          </div>
                          <div style="font-size:0.75rem;color:var(--text-muted)">
                            Via <?= strtoupper($r['payment_method']) ?>
                          </div>
                        </td>
                        <td style="font-weight:600"><?= htmlspecialchars($r['item_name']) ?></td>
                        <td>
                          <?php
                          $catClass = 'cat-' . ($r['category']);
                          $catIcon  = ['popcorn' => '🍿', 'drinks' => '🥤', 'snacks' => '🌮'];
                          ?>
                          <span class="cat-pill <?= $catClass ?>">
                            <?= $catIcon[$r['category']] ?? '🍽️' ?> <?= ucfirst($r['category']) ?>
                          </span>
                        </td>
                        <td style="font-weight:700;text-align:center"><?= $r['quantity'] ?>x</td>
                        <td style="font-weight:700;color:var(--orange);white-space:nowrap">
                          <?= formatRupiah($r['quantity'] * $r['line_price']) ?>
                        </td>
                        <td>
                          <span class="badge <?= $cookBadge[$r['cook_status']] ?? 'badge-yellow' ?>">
                            <?= $cookLabel[$r['cook_status']] ?? ucfirst($r['cook_status']) ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- F&B Item Stats -->
          <div class="card">
            <div class="card-hdr">
              <h3>Statistik Penjualan Menu</h3>
            </div>
            <?php if (empty($fnbStats)): ?>
              <div class="empty-state">
                <p>Belum ada data penjualan F&amp;B.</p>
              </div>
            <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th>Menu</th>
                    <th>Kategori</th>
                    <th>Qty Terjual</th>
                    <th>Pendapatan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $maxQty = max(array_column($fnbStats, 'qty_sold') ?: [1]);
                  $catIcon = ['popcorn' => '🍿', 'drinks' => '🥤', 'snacks' => '🌮'];
                  ?>
                  <?php foreach ($fnbStats as $fs): ?>
                    <tr>
                      <td style="font-weight:600"><?= htmlspecialchars($fs['name']) ?></td>
                      <td>
                        <span class="cat-pill cat-<?= $fs['category'] ?>">
                          <?= ($catIcon[$fs['category']] ?? '🍽️') . ' ' . ucfirst($fs['category']) ?>
                        </span>
                      </td>
                      <td>
                        <div style="display:flex;align-items:center;gap:8px">
                          <div style="flex:1;max-width:80px;height:6px;background:var(--border);border-radius:3px;overflow:hidden">
                            <div style="width:<?= round($fs['qty_sold'] / $maxQty * 100) ?>%;height:100%;background:var(--orange);border-radius:3px"></div>
                          </div>
                          <span style="font-weight:700"><?= $fs['qty_sold'] ?></span>
                        </div>
                      </td>
                      <td style="font-weight:700;color:var(--orange)"><?= formatRupiah($fs['revenue']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        <?php endif; ?>
      </div><!-- /page-content -->
    </div><!-- /main-content -->
  </div><!-- /admin-layout -->

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