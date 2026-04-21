<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
require_once '../../includes/kasir_nav.php';
requireRole('kasir');
$db = getDB();
$filter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$pendingCount = $db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $db->prepare("UPDATE orders SET order_status='confirmed', payment_status='paid' WHERE id=?")
       ->execute([$_POST['oid']]);
    header("Location: orders.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    $db->prepare("UPDATE orders SET order_status='cancelled' WHERE id=?")->execute([$_POST['oid']]);
    header("Location: orders.php");
    exit;
}

$sql = "SELECT o.*, u.name as cname, f.title, s.show_time, s.theater, s.show_date 
        FROM orders o JOIN users u ON o.user_id=u.id JOIN showtimes s ON o.showtime_id=s.id JOIN films f ON s.film_id=f.id WHERE 1=1";
$p = [];
if ($filter === 'pending') {
    $sql .= " AND o.order_status='pending'";
} elseif ($filter === 'confirmed') {
    $sql .= " AND o.order_status='confirmed'";
}
if ($search) {
    $sql .= " AND (u.name LIKE ? OR o.id LIKE ?)";
    $p[] = "%$search%";
    $p[] = "%$search%";
}
$sql .= " ORDER BY o.created_at DESC LIMIT 50";
$stmt = $db->prepare($sql);
$stmt->execute($p);
$orders = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pesanan - TursMovie Kasir</title>
    <?= getBaseStyles() ?>
    <style>
        <?php include '../../includes/admin_styles.php'; ?>
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php kasirNav('orders'); ?>

        <div class="overlay" id="ov"
            onclick="this.classList.remove('show');document.querySelector('.sidebar').classList.remove('open')"></div>
        <div class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <button class="hamburger"
                    onclick="document.querySelector('.sidebar').classList.toggle('open');document.getElementById('ov').classList.toggle('show')">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
                <span class="topbar-title">TursMovie Kasir</span>
            </div>
            <div class="page-content">
                <h1 style="font-size:1.3rem;font-weight:800;margin-bottom:4px">Pesanan Masuk</h1>
                <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:20px">Kelola dan verifikasi pesanan
                    pelanggan</p>
                <form method="GET">
                    <div class="search-input"><svg width="16" height="16" fill="none" stroke="var(--text-muted)"
                            stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input type="hidden" name="status" value="<?= $filter ?>"><input type="search" name="q"
                            placeholder="Cari pesanan atau pelanggan..." value="<?= htmlspecialchars($search) ?>"
                            onchange="this.form.submit()">
                    </div>
                </form>
                <div class="filter-tabs">
                    <a href="orders.php" class="tab-btn <?= $filter === 'all' ? 'active' : '' ?>">Semua</a>
                    <a href="orders.php?status=pending"
                        class="tab-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
                    <a href="orders.php?status=confirmed"
                        class="tab-btn <?= $filter === 'confirmed' ? 'active' : '' ?>">Dikonfirmasi</a>
                </div>
                <?php if (empty($orders)): ?>
                    <div style="text-align:center;padding:60px;color:var(--text-muted)"><svg width="48" height="48"
                            fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
                            style="margin:0 auto 12px;display:block">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                        </svg>Tidak ada pesanan ditemukan</div>
                <?php else: ?>
                    <?php foreach ($orders as $o):
                        $seats = $db->prepare("SELECT seat_code FROM order_seats WHERE order_id=?");
                        $seats->execute([$o['id']]);
                        $seats = array_column($seats->fetchAll(), 'seat_code');
                        $fnbs = $db->prepare("SELECT of.*,m.name FROM order_fnb of JOIN fnb_menu m ON of.fnb_id=m.id WHERE of.order_id=?");
                        $fnbs->execute([$o['id']]);
                        $fnbs = $fnbs->fetchAll();
                        ?>
                        <div
                            style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:18px;margin-bottom:12px">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                                <div>
                                    <div style="font-size:0.78rem;color:var(--text-muted)"><?= $o['id'] ?></div>
                                    <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($o['cname']) ?></div>
                                </div>
                                <div style="text-align:right">
                                    <div style="font-weight:700"><?= formatRupiah($o['total_amount']) ?></div>
                                    <span
                                        class="badge <?= $o['payment_status'] === 'paid' ? 'badge-green' : 'badge-yellow' ?>"><?= $o['payment_status'] === 'paid' ? 'Lunas' : 'Pending' ?></span>
                                </div>
                            </div>
                            <div
                                style="background:var(--bg-card2);border-radius:10px;padding:12px;font-size:0.85rem;margin-bottom:12px">
                                <div style="font-weight:700;margin-bottom:4px">Film & Jadwal</div>
                                <div style="font-size:0.95rem;font-weight:700"><?= htmlspecialchars($o['title']) ?></div>
                                <div style="color:var(--text-muted);margin-top:4px">
                                    <?= date('Y-m-d', strtotime($o['show_date'])) ?> • <?= substr($o['show_time'], 0, 5) ?> •
                                    <?= htmlspecialchars($o['theater']) ?></div>
                                <div style="margin-top:8px"><b>Kursi</b><br><span
                                        style="font-size:1rem;font-weight:700"><?= implode(', ', $seats) ?></span>
                                    (<?= count($seats) ?> kursi)</div>
                                <?php if (!empty($fnbs)): ?>
                                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border)"><b>Makanan &
                                            Minuman</b>
                                        <?php foreach ($fnbs as $f): ?>
                                            <div><?= $f['quantity'] ?>x <?= htmlspecialchars($f['name']) ?> -
                                                <?= formatRupiah($f['price']) ?></div><?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($o['order_status'] === 'pending'): ?>
                                <div style="display:flex;gap:8px">
                                    <form method="POST" style="flex:1"><input type="hidden" name="oid"
                                            value="<?= $o['id'] ?>"><button name="verify" class="btn btn-success"
                                            style="width:100%">✓ Verifikasi</button></form>
                                    <form method="POST"><input type="hidden" name="oid" value="<?= $o['id'] ?>"><button
                                            name="cancel" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Batalkan?')">✕</button></form>
                                </div>
                            <?php elseif ($o['order_status'] === 'confirmed'): ?>
                                <span class="badge badge-green" style="padding:8px 16px">✓ Dikonfirmasi</span>
                            <?php else: ?>
                                <span class="badge badge-red" style="padding:8px 16px">Dibatalkan</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>