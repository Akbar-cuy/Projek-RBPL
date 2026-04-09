<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();
$order_id = htmlspecialchars($_GET['order_id'] ?? '');
$order = $db->prepare("SELECT o.*, s.show_date, s.show_time, s.theater, f.title, f.image FROM orders o JOIN showtimes s ON o.showtime_id=s.id JOIN films f ON s.film_id=f.id WHERE o.id=? AND o.user_id=?");
$order->execute([$order_id, $_SESSION['user_id']]);
$order = $order->fetch();
if (!$order) {
    header('Location: home.php');
    exit;
}

$seats = $db->prepare("SELECT seat_code FROM order_seats WHERE order_id=?");
$seats->execute([$order_id]);
$seats = array_column($seats->fetchAll(), 'seat_code');

$fnbs = $db->prepare("SELECT of.*, m.name FROM order_fnb of JOIN fnb_menu m ON of.fnb_id=m.id WHERE of.order_id=?");
$fnbs->execute([$order_id]);
$fnbs = $fnbs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - TursMovie</title>
    <?= getBaseStyles() ?>
    <style>
        .mobile-wrap {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            padding: 0 0 30px;
        }

        .success-header {
            text-align: center;
            padding: 48px 20px 32px;
        }

        .check-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.3);
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            from {
                transform: scale(0);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .success-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .ticket-card {
            background: white;
            margin: 0 20px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            color: #111;
        }

        .ticket-top {
            padding: 20px;
        }

        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f8f8f8;
        }

        .qr-box {
            width: 160px;
            height: 160px;
            background: white;
            margin: 0 auto 10px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #eee;
            font-size: 0.7rem;
            color: #666;
            background-image: repeating-linear-gradient(0deg, transparent, transparent 10px, rgba(0, 0, 0, 0.03) 10px, rgba(0, 0, 0, 0.03) 11px),
                repeating-linear-gradient(90deg, transparent, transparent 10px, rgba(0, 0, 0, 0.03) 10px, rgba(0, 0, 0, 0.03) 11px);
        }

        .qr-code {
            font-size: 0.75rem;
            color: #888;
            margin-top: 4px;
        }

        .qr-hint {
            font-size: 0.78rem;
            color: #666;
            margin-top: 8px;
        }

        .ticket-divider {
            position: relative;
            padding: 0 20px;
            display: flex;
            align-items: center;
        }

        .ticket-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            border-top: 2px dashed #ddd;
        }

        .ticket-divider .dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--bg-dark);
            flex-shrink: 0;
            z-index: 1;
        }

        .ticket-details {
            padding: 16px 20px;
        }

        .order-id-label {
            font-size: 0.75rem;
            color: #888;
        }

        .order-id-val {
            font-size: 0.9rem;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .detail-label {
            font-size: 0.75rem;
            color: #888;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.85rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-row:last-child {
            border: none;
        }

        .detail-val {
            font-weight: 600;
        }

        .pay-info {
            padding: 12px 20px;
            background: #f8f8f8;
            display: flex;
            justify-content: space-between;
        }

        .pay-info .total {
            font-size: 0.85rem;
        }

        .pay-info .total-val {
            font-size: 1rem;
            font-weight: 800;
            color: var(--red);
        }

        .status-badge {
            display: inline-block;
            background: #dcfce7;
            color: #16a34a;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .actions {
            display: flex;
            gap: 10px;
            padding: 20px;
        }

        .btn-action {
            flex: 1;
            padding: 13px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-dl {
            background: #1a1d27;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-share {
            background: #1a1d27;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-home {
            width: calc(100% - 40px);
            margin: 0 20px;
            background: var(--red);
            color: white;
            padding: 15px;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
</head>

<body>
    <div class="mobile-wrap">
        <div class="success-header">
            <div class="check-circle">
                <svg width="36" height="36" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </div>
            <h1>Pembayaran Berhasil!</h1>
            <p>Pesanan Anda telah dikonfirmasi</p>
        </div>

        <div class="ticket-card">
            <div class="qr-section">
                <div class="qr-box">
                    <span>QR CODE</span>
                </div>
                <div class="qr-code"><?= htmlspecialchars($order['qr_code']) ?></div>
                <div class="qr-hint">Tunjukkan QR code ini ke kasir</div>
            </div>

            <div class="ticket-divider">
                <div class="dot"></div>
                <div style="flex:1"></div>
                <div class="dot"></div>
            </div>

            <div class="ticket-details">
                <div class="order-id-label">Order ID</div>
                <div class="order-id-val"><?= htmlspecialchars($order_id) ?></div>

                <div class="detail-label">Detail Pemesanan</div>
                <div class="detail-row"><span>Film</span><span class="detail-val"><?= htmlspecialchars($order['title']) ?></span></div>
                <div class="detail-row"><span>Jadwal</span><span class="detail-val"><?= date('Y-m-d', strtotime($order['show_date'])) ?> • <?= substr($order['show_time'], 0, 5) ?></span></div>
                <div class="detail-row"><span>Kursi</span><span class="detail-val"><?= implode(', ', $seats) ?></span></div>
                <div class="detail-row"><span>Ruang</span><span class="detail-val"><?= htmlspecialchars($order['theater']) ?></span></div>

                <?php if (!empty($fnbs)): ?>
                    <div class="detail-label" style="margin-top:12px">Makanan & Minuman</div>
                    <?php foreach ($fnbs as $f): ?>
                        <div class="detail-row"><span><?= $f['quantity'] ?>x <?= htmlspecialchars($f['name']) ?></span><span class="detail-val"><?= formatRupiah($f['price']) ?></span></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="pay-info">
                <div>
                    <div class="total">Total Dibayar</div>
                    <div class="total-val"><?= formatRupiah($order['total_amount']) ?></div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:0.75rem;color:#888">Status</div>
                    <span class="status-badge">Lunas</span>
                </div>
            </div>
        </div>

        <div class="actions">
            <button class="btn-action btn-dl">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                Unduh
            </button>
            <button class="btn-action btn-share">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="18" cy="5" r="3" />
                    <circle cx="6" cy="12" r="3" />
                    <circle cx="18" cy="19" r="3" />
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                </svg>
                Bagikan
            </button>
        </div>
        <button class="btn-home" onclick="location.href='home.php'">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            Kembali ke Beranda
        </button>
    </div>
</body>

</html>