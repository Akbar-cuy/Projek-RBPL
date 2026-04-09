<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();
$showtime_id = intval($_GET['showtime_id'] ?? 0);
$film_id = intval($_GET['film_id'] ?? 0);
$seats = htmlspecialchars($_GET['seats'] ?? '');
$cartJson = $_GET['cart'] ?? '{}';
$cart = json_decode($cartJson, true) ?? [];

$showtime = $db->prepare("SELECT s.*, f.title, f.price, f.image FROM showtimes s JOIN films f ON s.film_id=f.id WHERE s.id=?");
$showtime->execute([$showtime_id]);
$showtime = $showtime->fetch();
if (!$showtime) { header('Location: home.php'); exit; }

$seatArr = array_filter(explode(',', $seats));
$ticketTotal = $showtime['price'] * count($seatArr);
$fnbTotal = 0;
foreach ($cart as $item) { $fnbTotal += ($item['price'] ?? 0) * ($item['qty'] ?? 0); }
$grandTotal = $ticketTotal + $fnbTotal;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ringkasan Pesanan - TursMovie</title>
<?= getBaseStyles() ?>
<style>
.mobile-wrap { max-width: 480px; margin: 0 auto; min-height: 100vh; padding-bottom: 100px; }
.top-bar {
    background: linear-gradient(135deg, var(--red), var(--red-dark));
    padding: 16px 20px; display: flex; align-items: center; gap: 14px;
}
.back-btn { width:38px;height:38px;background:rgba(255,255,255,0.2);border-radius:10px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.top-bar h1 { font-size:1rem;font-weight:700;color:white; }
.top-bar p { font-size:0.78rem;color:rgba(255,255,255,0.75); }
.content { padding: 20px; }
.film-card { background:var(--bg-card);border-radius:16px;border:1px solid var(--border);padding:16px;display:flex;gap:14px;margin-bottom:16px; }
.film-thumb { width:70px;height:90px;border-radius:10px;overflow:hidden;flex-shrink:0; }
.film-thumb img { width:100%;height:100%;object-fit:cover; }
.film-info h2 { font-size:1rem;font-weight:700;margin-bottom:8px; }
.film-meta-row { display:flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--text-muted);margin-bottom:4px; }
.ticket-row { display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-top:1px solid var(--border);margin-top:8px; }
.ticket-label { font-size:0.82rem;color:var(--text-muted); }
.ticket-price { font-size:0.9rem;font-weight:700; }
.section-card { background:var(--bg-card);border-radius:16px;border:1px solid var(--border);padding:16px;margin-bottom:16px; }
.section-header { display:flex;align-items:center;gap:8px;font-size:0.9rem;font-weight:700;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--border); }
.fnb-row { display:flex;justify-content:space-between;font-size:0.82rem;padding:6px 0; }
.fnb-subtotal { display:flex;justify-content:space-between;font-size:0.82rem;color:var(--text-muted);padding-top:8px;border-top:1px solid var(--border);margin-top:4px; }
.total-box { background:var(--bg-card);border-radius:16px;border:1px solid var(--border);padding:16px;margin-bottom:16px; }
.total-row { display:flex;justify-content:space-between;font-size:0.85rem;padding:5px 0; }
.total-row.grand { font-size:1.1rem;font-weight:800;color:var(--text);padding-top:12px;margin-top:8px;border-top:1px solid var(--border); }
.total-row.grand .amount { color:var(--red); }
.pay-btn {
    position:fixed;bottom:20px;left:50%;transform:translateX(-50%);
    width:calc(100% - 40px);max-width:440px;
    background:linear-gradient(135deg,var(--red),var(--red-dark));
    color:white;font-size:1rem;font-weight:700;border:none;border-radius:16px;
    padding:16px;cursor:pointer;z-index:100;
    box-shadow:0 8px 25px rgba(230,21,21,0.4);transition:all 0.2s;
}
.pay-btn:hover { transform:translateX(-50%) translateY(-2px); }
</style>
</head>
<body>
<div class="mobile-wrap">
    <div class="top-bar">
        <button class="back-btn" onclick="history.back()">
            <svg width="18" height="18" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </button>
        <div>
            <h1>Ringkasan Pesanan</h1>
            <p>Periksa kembali pesanan Anda</p>
        </div>
    </div>

    <div class="content">
        <div class="film-card">
            <div class="film-thumb">
                <img src="<?= htmlspecialchars($showtime['image']) ?>" alt="">
            </div>
            <div class="film-info">
                <h2><?= htmlspecialchars($showtime['title']) ?></h2>
                <div class="film-meta-row">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?= date('Y-m-d', strtotime($showtime['show_date'])) ?> • <?= substr($showtime['show_time'],0,5) ?>
                </div>
                <div class="film-meta-row">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?= htmlspecialchars($showtime['theater']) ?>
                </div>
                <div class="film-meta-row">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    <?= implode(', ', $seatArr) ?>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label"><?= count($seatArr) ?> Tiket</span>
                    <span class="ticket-price"><?= formatRupiah($ticketTotal) ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($cart)): ?>
        <div class="section-card">
            <div class="section-header">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
                Makanan & Minuman
            </div>
            <?php foreach ($cart as $item): ?>
            <div class="fnb-row">
                <span><?= htmlspecialchars($item['name']) ?> ×<?= $item['qty'] ?></span>
                <span><?= formatRupiah($item['price'] * $item['qty']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="fnb-subtotal">
                <span>Subtotal F&B</span>
                <span><?= formatRupiah($fnbTotal) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="total-box">
            <div class="total-row"><span>Tiket Film</span><span><?= formatRupiah($ticketTotal) ?></span></div>
            <?php if ($fnbTotal > 0): ?>
            <div class="total-row"><span>Makanan & Minuman</span><span><?= formatRupiah($fnbTotal) ?></span></div>
            <?php endif; ?>
            <div class="total-row grand">
                <span>Total Pembayaran</span>
                <span class="amount"><?= formatRupiah($grandTotal) ?></span>
            </div>
        </div>
    </div>
</div>

<form id="payForm" action="payment.php" method="GET" style="display:none">
    <input name="showtime_id" value="<?= $showtime_id ?>">
    <input name="film_id" value="<?= $film_id ?>">
    <input name="seats" value="<?= htmlspecialchars($seats) ?>">
    <input name="cart" value="<?= htmlspecialchars($cartJson) ?>">
    <input name="total" value="<?= $grandTotal ?>">
</form>
<button class="pay-btn" onclick="document.getElementById('payForm').submit()">Lanjut ke Pembayaran</button>
</body>
</html>
