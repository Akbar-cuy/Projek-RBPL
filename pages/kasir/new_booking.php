<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
require_once '../../includes/kasir_nav.php';
requireRole('kasir');

$db = getDB();

// ── AJAX (must be before any output) ─────────────────────────────────────────
if (isset($_GET['ajax'])) {
  header('Content-Type: application/json');
  if ($_GET['ajax'] === 'showtimes') {
    $filmId = intval($_GET['film_id'] ?? 0);
    // Ganti sementara baris ini:
    $rows = $db->prepare("SELECT id, show_date, show_time, theater, available_seats FROM showtimes WHERE film_id=? ORDER BY show_date,show_time");
    $rows->execute([$filmId]);
    $out = [];
    foreach ($rows->fetchAll() as $r) {
      $r['show_date_fmt'] = date('d M Y', strtotime($r['show_date']));
      $r['show_time'] = substr($r['show_time'], 0, 5);
      $out[] = $r;
    }
    echo json_encode($out);
    exit;
  }
  if ($_GET['ajax'] === 'seats') {
    $sid = intval($_GET['showtime_id'] ?? 0);
    $bs = $db->prepare("SELECT seat_code FROM order_seats os JOIN orders o ON os.order_id=o.id WHERE o.showtime_id=? AND o.order_status!='cancelled'");
    $bs->execute([$sid]);
    echo json_encode(array_column($bs->fetchAll(), 'seat_code'));
    exit;
  }
  echo json_encode([]);
  exit;
}

$pendingCount = $db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();
$step = $_GET['step'] ?? '';
$error = '';

// ── Process booking submission ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
  $guestName   = trim($_POST['guest_name'] ?? '');
  $userId      = intval($_POST['user_id'] ?? 0) ?: null;
  $showtimeId  = intval($_POST['showtime_id'] ?? 0) ?: null;
  $seats       = json_decode($_POST['selected_seats'] ?? '[]', true);
  $fnbItems    = json_decode($_POST['fnb_items'] ?? '[]', true);
  $payMethod   = $_POST['payment_method'] ?? 'cash';
  $hasTicket   = $showtimeId && !empty($seats);
  $hasFnb      = !empty(array_filter($fnbItems, fn($i) => $i['qty'] > 0));

  if (!$guestName && $userId) {
    $u = $db->prepare("SELECT name FROM users WHERE id=?");
    $u->execute([$userId]);
    $guestName = $u->fetchColumn() ?: 'Tamu';
  }

  if (!$guestName) {
    $error = 'Nama pelanggan wajib diisi.';
  } elseif (!$hasTicket && !$hasFnb) {
    $error = 'Pilih minimal tiket atau makanan/minuman.';
  } else {
    $ticketPrice = 0;
    if ($hasTicket) {
      $fp = $db->prepare("SELECT f.price FROM showtimes s JOIN films f ON s.film_id=f.id WHERE s.id=?");
      $fp->execute([$showtimeId]);
      $ticketPrice = $fp->fetchColumn();
    }
    $ticketTotal = $ticketPrice * count($seats);
    $fnbTotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $fnbItems));
    $grandTotal = $ticketTotal + $fnbTotal;

    $orderType = $hasTicket && $hasFnb ? 'ticket_fnb' : ($hasTicket ? 'ticket' : 'fnb');
    $orderId   = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8));

    try {
      $db->beginTransaction();
      $db->prepare("INSERT INTO orders (id,user_id,showtime_id,guest_name,total_amount,payment_method,payment_status,order_status,order_type,qr_code) VALUES (?,?,?,?,?,?,'paid','confirmed',?,?)")
        ->execute([$orderId, $userId, $showtimeId, $guestName, $grandTotal, $payMethod, $orderType, 'QR-' . $orderId]);

      if ($hasTicket) {
        foreach ($seats as $seat) {
          $db->prepare("INSERT INTO order_seats (order_id,seat_code) VALUES (?,?)")->execute([$orderId, $seat]);
        }
        $db->prepare("UPDATE showtimes SET available_seats = available_seats - ? WHERE id=?")->execute([count($seats), $showtimeId]);
      }

      foreach ($fnbItems as $item) {
        if ($item['qty'] > 0) {
          $db->prepare("INSERT INTO order_fnb (order_id,fnb_id,quantity,price) VALUES (?,?,?,?)")->execute([$orderId, $item['id'], $item['qty'], $item['price']]);
        }
      }
      $db->commit();
      header("Location: new_booking.php?step=done&order_id=$orderId");
      exit;
    } catch (Exception $e) {
      $db->rollBack();
      $error = 'Gagal menyimpan: ' . $e->getMessage();
    }
  }
}

// ── Data Fetching ────────────────────────────────────────────────────────────
$doneOrder = null;
$doneSeats = [];
$doneFnb = [];
if ($step === 'done' && isset($_GET['order_id'])) {
  $oid = $_GET['order_id'];
  $stmt = $db->prepare("SELECT o.*,f.title,s.show_date,s.show_time,s.theater FROM orders o LEFT JOIN showtimes s ON o.showtime_id=s.id LEFT JOIN films f ON s.film_id=f.id WHERE o.id=?");
  $stmt->execute([$oid]);
  $doneOrder = $stmt->fetch();
  $s2 = $db->prepare("SELECT seat_code FROM order_seats WHERE order_id=?");
  $s2->execute([$oid]);
  $doneSeats = array_column($s2->fetchAll(), 'seat_code');
  $f2 = $db->prepare("SELECT of.*,m.name as item_name FROM order_fnb of JOIN fnb_menu m ON of.fnb_id=m.id WHERE of.order_id=?");
  $f2->execute([$oid]);
  $doneFnb = $f2->fetchAll();
}

$customers = $db->query("SELECT id,name,username,phone FROM users WHERE role='customer' ORDER BY name")->fetchAll();
$films     = $db->query("SELECT id,title,price FROM films WHERE is_active=1 ORDER BY title")->fetchAll();
$fnbMenu   = $db->query("SELECT * FROM fnb_menu WHERE is_available=1 ORDER BY category,name")->fetchAll();
$fnbBycat  = [];
foreach ($fnbMenu as $m) {
  $fnbBycat[$m['category']][] = $m;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
  <title>Pesanan - TursMovie Kasir</title>
  <?= getBaseStyles() ?>
  <style>
    <?php include '../../includes/admin_styles.php'; ?> :root {
      --red: #e61515;
      --bg: #0f1117;
      --card: #1a1d27;
      --card2: #21253a;
      --text: #f0f0f0;
      --muted: #8892a4;
      --border: rgba(255, 255, 255, 0.08);
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      overflow-x: hidden;
      width: 100%;
    }

    /* Layout Fixes */
    .admin-layout {
      display: flex;
      min-height: 100vh;
    }

    .main-content {
      margin-left: 240px;
      flex: 1;
      width: 100%;
      min-width: 0;
      display: flex;
      flex-direction: column;
      transition: margin 0.3s;
    }

    .pc {
      padding: 24px;
      flex: 1;
    }

    .topbar {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      padding: 14px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .grid-bk {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 20px;
      align-items: start;
    }

    /* Mobile Adjustments */
    @media (max-width: 1100px) {
      .main-content {
        margin-left: 0;
      }

      .grid-bk {
        grid-template-columns: 1fr;
      }

      .summary-panel {
        position: relative !important;
        top: 0 !important;
        margin-top: 20px;
      }

      .pc {
        padding: 16px;
      }
    }

    /* Card Styles */
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      margin-bottom: 16px;
    }

    .card-hdr {
      padding: 13px 18px;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .card-hdr h3 {
      font-size: .9rem;
      font-weight: 700;
      margin: 0;
    }

    .card-body {
      padding: 18px;
    }

    /* Types & Forms */
    .type-toggle {
      display: flex;
      gap: 8px;
      margin-bottom: 20px;
    }

    .type-chip {
      flex: 1;
      padding: 12px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      background: var(--card2);
      text-align: center;
      cursor: pointer;
      font-size: .83rem;
      font-weight: 600;
      color: var(--muted);
    }

    .type-chip.on {
      border-color: var(--red);
      color: var(--text);
      background: rgba(230, 21, 21, .08);
    }

    .fg {
      margin-bottom: 14px;
    }

    .fg label {
      display: block;
      font-size: .8rem;
      font-weight: 700;
      color: var(--muted);
      margin-bottom: 6px;
    }

    .fg input,
    .fg select {
      width: 100%;
      background: var(--card2);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      padding: 11px 13px;
      color: var(--text);
      font-family: inherit;
      outline: none;
    }

    .fg input:focus {
      border-color: var(--red);
    }

    /* Seats Fix */
    .seat-container {
      overflow-x: auto;
      padding: 15px 0;
      -webkit-overflow-scrolling: touch;
    }

    .seat-screen {
      background: linear-gradient(to bottom, rgba(230, 21, 21, .2), transparent);
      border: 1px solid rgba(230, 21, 21, .1);
      border-radius: 8px;
      text-align: center;
      padding: 8px;
      font-size: .7rem;
      color: var(--muted);
      font-weight: 800;
      letter-spacing: 3px;
      margin-bottom: 20px;
      min-width: 300px;
    }

    .seat-grid {
      display: flex;
      flex-direction: column;
      gap: 6px;
      align-items: center;
      min-width: 300px;
    }

    .seat-row {
      display: flex;
      gap: 5px;
      align-items: center;
    }

    .seat {
      width: 32px;
      height: 32px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      font-size: .7rem;
      font-weight: 700;
      transition: .15s;
    }

    .seat.avail {
      background: var(--card2);
      color: var(--muted);
      border: 1px solid var(--border);
    }

    .seat.sel {
      background: var(--red);
      color: #fff;
    }

    .seat.bkd {
      background: rgba(255, 255, 255, .05);
      color: rgba(255, 255, 255, .1);
      cursor: not-allowed;
    }

    /* FnB Grid */
    .fnb-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 10px;
      padding: 0 18px 18px;
    }

    @media (max-width: 480px) {
      .fnb-grid {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        padding: 0 12px 12px;
      }
    }

    .fnb-card {
      background: var(--card2);
      border: 1.5px solid var(--border);
      border-radius: 12px;
      padding: 12px;
      text-align: center;
    }

    .fnb-card.has-qty {
      border-color: var(--red);
    }

    /* Summary Sticky */
    .summary-panel {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      position: sticky;
      top: 80px;
    }

    .sum-row {
      display: flex;
      justify-content: space-between;
      font-size: .875rem;
      margin-bottom: 8px;
    }

    .sum-row.total {
      font-weight: 800;
      font-size: 1.1rem;
      border-top: 1px solid var(--border);
      padding-top: 12px;
      margin-top: 10px;
    }

    /* Buttons */
    .btn {
      padding: 12px 18px;
      border-radius: 10px;
      font-size: .875rem;
      font-weight: 700;
      cursor: pointer;
      border: none;
      transition: .15s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-primary {
      background: var(--red);
      color: #fff;
      width: 100%;
    }

    .btn-primary:disabled {
      opacity: .5;
      cursor: not-allowed;
    }

    .btn-outline {
      background: transparent;
      border: 1.5px solid var(--border);
      color: var(--text);
    }

    .chip {
      display: inline-block;
      background: var(--card2);
      border: 1px solid var(--border);
      border-radius: 5px;
      padding: 2px 7px;
      font-size: .72rem;
      font-weight: 700;
      margin: 2px;
    }

    .cust-mode-btn {
      padding: 7px 14px;
      border-radius: 8px;
      font-size: .78rem;
      font-weight: 700;
      cursor: pointer;
      border: 1.5px solid var(--border);
      background: var(--card2);
      color: var(--muted);
    }

    .cust-mode-btn.on {
      border-color: var(--red);
      color: var(--red);
      background: rgba(230, 21, 21, .08);
    }
  </style>
</head>

<body>
  <div class="admin-layout">
    <?php kasirNav('new_booking'); ?>
    <div class="overlay" id="ov" onclick="toggleSidebar()"></div>

    <div class="main-content">
      <div class="topbar">
        <button class="hbg" onclick="toggleSidebar()" style="display:block; background:none; border:none; color:#fff; cursor:pointer;">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
          </svg>
        </button>
        <span style="font-weight:800; font-size:1.1rem; flex:1;">Booking Manual</span>
        <a href="bookings.php" class="btn btn-outline" style="padding:6px 12px; font-size:.75rem;">← List</a>
      </div>

      <div class="pc">
        <?php if ($step === 'done' && $doneOrder): ?>
          <div style="max-width:500px; margin: 0 auto; text-align:center;">
            <div style="width:70px; height:70px; background:rgba(34,197,94,.15); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
              <svg width="35" height="35" fill="none" stroke="#22c55e" stroke-width="3" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12" />
              </svg>
            </div>
            <h2 style="font-weight:800; margin-bottom:5px;">Booking Berhasil!</h2>
            <p style="color:var(--muted); font-size:.9rem;">Transaksi ID: <b style="color:var(--red)"><?= $doneOrder['id'] ?></b></p>

            <div class="card" style="text-align:left; margin-top:25px; padding:20px;">
              <div class="sum-row"><span>Nama</span><span><?= htmlspecialchars($doneOrder['guest_name']) ?></span></div>
              <?php if ($doneOrder['title']): ?>
                <div class="sum-row"><span>Film</span><span><?= htmlspecialchars($doneOrder['title']) ?></span></div>
                <div class="sum-row"><span>Studio</span><span><?= htmlspecialchars($doneOrder['theater']) ?></span></div>
                <div class="sum-row"><span>Kursi</span><span><?= implode(', ', $doneSeats) ?></span></div>
              <?php endif; ?>
              <div class="sum-row total"><span>Total Bayar</span><span style="color:var(--red)"><?= formatRupiah($doneOrder['total_amount']) ?></span></div>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
              <a href="new_booking.php" class="btn btn-primary">Booking Baru</a>
              <a href="bookings.php" class="btn btn-outline" style="flex:1;">Riwayat</a>
            </div>
          </div>

        <?php else: ?>
          <div class="grid-bk">
            <div class="left-col">
              <div class="card">
                <div class="card-hdr">
                  <h3>① Pelanggan</h3>
                </div>
                <div class="card-body">
                  <div style="display:flex; gap:8px; margin-bottom:15px;">
                    <button class="cust-mode-btn on" id="btn-manual" onclick="setCustMode('manual')">Ketik Nama</button>
                    <button class="cust-mode-btn" id="btn-akun" onclick="setCustMode('akun')">Pilih User</button>
                  </div>
                  <div id="mode-manual" class="fg">
                    <input type="text" id="inp-name" placeholder="Nama Lengkap Tamu..." oninput="state.guestName=this.value;updateSummary()">
                  </div>
                  <div id="mode-akun" style="display:none;" class="fg">
                    <select id="sel-cust" onchange="pickAccount(this)">
                      <option value="">-- Cari Pelanggan --</option>
                      <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-hdr">
                  <h3>② Jenis Pesanan</h3>
                </div>
                <div class="card-body">
                  <div class="type-toggle">
                    <div class="type-chip on" id="tc-fnb" onclick="setType('fnb',this)">🍿 FnB Saja</div>
                    <div class="type-chip" id="tc-ticket" onclick="setType('ticket',this)">🎬 Tiket + FnB</div>
                  </div>
                  <div id="film-section" style="display:none;">
                    <div class="fg">
                      <label>Pilih Film</label>
                      <select id="sel-film" onchange="loadShowtimes(this.value)">
                        <option value="">-- Pilih Film --</option>
                        <?php foreach ($films as $f): ?>
                          <option value="<?= $f['id'] ?>" data-price="<?= $f['price'] ?>"><?= htmlspecialchars($f['title']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="fg" id="showtime-group" style="display:none;">
                      <label>Jadwal</label>
                      <select id="sel-showtime" onchange="loadSeats(this.value)"></select>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card" id="seat-card" style="display:none;">
                <div class="card-hdr">
                  <h3>③ Pilih Kursi</h3> <span id="seat-lbl" style="font-size:.75rem; color:var(--muted);">0 Selected</span>
                </div>
                <div class="seat-container">
                  <div class="seat-screen">L A Y A R</div>
                  <div class="seat-grid" id="seat-grid"></div>
                </div>
              </div>

              <div class="card">
                <div class="card-hdr">
                  <h3 id="fnb-label">② Menu FnB</h3>
                </div>
                <?php foreach ($fnbBycat as $cat => $items): ?>
                  <div style="font-size:.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; padding:15px 18px 5px;"><?= $cat ?></div>
                  <div class="fnb-grid">
                    <?php foreach ($items as $m): ?>
                      <div class="fnb-card" id="fnb-<?= $m['id'] ?>">
                        <div style="font-size:.8rem; font-weight:700; margin-bottom:4px;"><?= $m['name'] ?></div>
                        <div style="font-size:.75rem; color:var(--red); font-weight:700; margin-bottom:10px;"><?= number_format($m['price'], 0, ',', '.') ?></div>
                        <div style="display:flex; align-items:center; justify-content:center; gap:10px;">
                          <button class="cust-mode-btn" style="padding:4px 10px;" onclick="changeQty(<?= $m['id'] ?>,<?= $m['price'] ?>,-1)">−</button>
                          <b id="qty-<?= $m['id'] ?>">0</b>
                          <button class="cust-mode-btn" style="padding:4px 10px;" onclick="changeQty(<?= $m['id'] ?>,<?= $m['price'] ?>,1)">+</button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="summary-panel">
              <div class="card-hdr">
                <h3>Ringkasan</h3>
              </div>
              <div class="card-body">
                <div class="fg">
                  <label>METODE BAYAR</label>
                  <select onchange="state.payMethod=this.value" id="h-pay-sel">
                    <option value="cash">Tunai (Cash)</option>
                    <option value="ewallet">QRIS / E-Wallet</option>
                    <option value="card">Debit / Credit Card</option>
                  </select>
                </div>
                <div style="font-size:.85rem; border-top:1px solid var(--border); padding-top:15px;">
                  <div class="sum-row"><span>Pelanggan:</span><span id="sum-cust" style="font-weight:700;">-</span></div>
                  <div id="sum-ticket-row" style="display:none;">
                    <div class="sum-row"><span>Tiket:</span><span id="sum-t-qty">0</span></div>
                    <div class="sum-row"><span>Subtotal Tiket:</span><span id="sum-t-price">0</span></div>
                  </div>
                  <div class="sum-row"><span>Subtotal FnB:</span><span id="sum-f-price">0</span></div>
                  <div class="sum-row total"><span>Total</span><span id="sum-total" style="color:var(--red);">Rp 0</span></div>
                </div>
                <form method="POST" id="bk-form" style="margin-top:20px;">
                  <input type="hidden" name="confirm_booking" value="1">
                  <input type="hidden" name="user_id" id="h-uid">
                  <input type="hidden" name="guest_name" id="h-name">
                  <input type="hidden" name="showtime_id" id="h-sid">
                  <input type="hidden" name="selected_seats" id="h-seats">
                  <input type="hidden" name="fnb_items" id="h-fnb">
                  <input type="hidden" name="payment_method" id="h-pay" value="cash">
                  <button type="button" id="submit-btn" class="btn btn-primary" onclick="submitBooking()" disabled>Konfirmasi Pesanan</button>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    const state = {
      guestName: '',
      userId: null,
      bookingType: 'fnb',
      showtimeId: null,
      seats: [],
      ticketPrice: 0,
      fnb: {},
      payMethod: 'cash'
    };

    function toggleSidebar() {
      document.querySelector('.sidebar').classList.toggle('open');
      document.getElementById('ov').classList.toggle('show');
    }

    function setCustMode(mode) {
      document.getElementById('mode-manual').style.display = mode === 'manual' ? 'block' : 'none';
      document.getElementById('mode-akun').style.display = mode === 'akun' ? 'block' : 'none';
      document.getElementById('btn-manual').classList.toggle('on', mode === 'manual');
      document.getElementById('btn-akun').classList.toggle('on', mode === 'akun');
      state.userId = null;
      state.guestName = '';
      updateSummary();
    }

    function pickAccount(sel) {
      const opt = sel.options[sel.selectedIndex];
      state.userId = sel.value || null;
      state.guestName = opt.dataset.name || '';
      updateSummary();
    }

    function setType(type, el) {
      state.bookingType = type;
      document.querySelectorAll('.type-chip').forEach(c => c.classList.remove('on'));
      el.classList.add('on');
      document.getElementById('film-section').style.display = type === 'ticket' ? 'block' : 'none';
      document.getElementById('fnb-label').textContent = type === 'ticket' ? '④ Menu FnB' : '② Menu FnB';
      if (type === 'fnb') {
        state.showtimeId = null;
        state.seats = [];
        document.getElementById('seat-card').style.display = 'none';
      }
      updateSummary();
    }

    function loadShowtimes(fid) {
      const sel = document.getElementById('sel-film');
      state.ticketPrice = parseInt(sel.options[sel.selectedIndex].dataset.price || 0);
      if (!fid) return;
      fetch('?ajax=showtimes&film_id=' + fid).then(r => r.json()).then(data => {
        const s = document.getElementById('sel-showtime');
        s.innerHTML = '<option value="">-- Pilih Jadwal --</option>';
        data.forEach(t => {
          s.innerHTML += `<option value="${t.id}">${t.show_date_fmt} - ${t.show_time} (${t.theater})</option>`;
        });
        document.getElementById('showtime-group').style.display = 'block';
      });
    }

    function loadSeats(sid) {
      state.showtimeId = sid;
      state.seats = [];
      if (!sid) return;
      fetch('?ajax=seats&showtime_id=' + sid).then(r => r.json()).then(booked => {
        let html = '';
        ['A', 'B', 'C', 'D', 'E'].forEach(r => {
          html += `<div class="seat-row"><small style="width:15px; color:var(--muted)">${r}</small>`;
          for (let i = 1; i <= 8; i++) {
            const code = r + i,
              isB = booked.includes(code);
            html += `<button class="seat ${isB?'bkd':'avail'}" onclick="toggleSeat('${code}',this)" ${isB?'disabled':''}>${i}</button>`;
          }
          html += '</div>';
        });
        document.getElementById('seat-grid').innerHTML = html;
        document.getElementById('seat-card').style.display = 'block';
        updateSummary();
      });
    }

    function toggleSeat(c, btn) {
      const i = state.seats.indexOf(c);
      if (i === -1) {
        state.seats.push(c);
        btn.classList.add('sel');
      } else {
        state.seats.splice(i, 1);
        btn.classList.remove('sel');
      }
      document.getElementById('seat-lbl').textContent = state.seats.length + ' dipilih';
      updateSummary();
    }

    function changeQty(id, p, d) {
      if (!state.fnb[id]) state.fnb[id] = {
        id,
        price: p,
        qty: 0
      };
      state.fnb[id].qty = Math.max(0, state.fnb[id].qty + d);
      document.getElementById('qty-' + id).textContent = state.fnb[id].qty;
      document.getElementById('fnb-' + id).classList.toggle('has-qty', state.fnb[id].qty > 0);
      updateSummary();
    }

    function updateSummary() {
      document.getElementById('sum-cust').textContent = state.guestName || '-';
      const fnbArr = Object.values(state.fnb).filter(x => x.qty > 0);
      const tPrice = state.seats.length * state.ticketPrice;
      const fPrice = fnbArr.reduce((a, b) => a + (b.qty * b.price), 0);

      document.getElementById('sum-ticket-row').style.display = state.bookingType === 'ticket' ? 'block' : 'none';
      document.getElementById('sum-t-qty').textContent = state.seats.length;
      document.getElementById('sum-t-price').textContent = 'Rp ' + tPrice.toLocaleString();
      document.getElementById('sum-f-price').textContent = 'Rp ' + fPrice.toLocaleString();
      document.getElementById('sum-total').textContent = 'Rp ' + (tPrice + fPrice).toLocaleString();

      const valid = state.guestName.length > 1 && (state.seats.length > 0 || fnbArr.length > 0);
      document.getElementById('submit-btn').disabled = !valid;
    }

    function submitBooking() {
      document.getElementById('h-uid').value = state.userId || '';
      document.getElementById('h-name').value = state.guestName;
      document.getElementById('h-sid').value = state.showtimeId || '';
      document.getElementById('h-seats').value = JSON.stringify(state.seats);
      document.getElementById('h-fnb').value = JSON.stringify(Object.values(state.fnb).filter(x => x.qty > 0));
      document.getElementById('h-pay').value = document.getElementById('h-pay-sel').value;
      document.getElementById('bk-form').submit();
    }
  </script>
</body>

</html>