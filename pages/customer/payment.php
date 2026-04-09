<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();

// Handle POST - process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showtime_id = intval($_POST['showtime_id']);
    $seats = $_POST['seats'];
    $cartJson = $_POST['cart'];
    $total = intval($_POST['total']);
    $payment_method = $_POST['payment_method'] ?? 'ewallet';
    $cart = json_decode($cartJson, true) ?? [];

    $order_id = 'ORD-' . time();
    $qr_code = 'QR-' . time();
    $user_id = $_SESSION['user_id'];

    // Create order
    $stmt = $db->prepare("INSERT INTO orders (id,user_id,showtime_id,total_amount,payment_method,payment_status,order_status,qr_code) VALUES (?,?,?,?,?,'paid','confirmed',?)");
    $stmt->execute([$order_id, $user_id, $showtime_id, $total, $payment_method, $qr_code]);

    // Save seats
    $seatArr = array_filter(explode(',', $seats));
    foreach ($seatArr as $seat) {
        $db->prepare("INSERT INTO order_seats (order_id, seat_code) VALUES (?,?)")->execute([$order_id, trim($seat)]);
    }

    // Save FnB
    foreach ($cart as $fnb_id => $item) {
        $db->prepare("INSERT INTO order_fnb (order_id,fnb_id,quantity,price,cook_status) VALUES (?,?,?,?,'new')")
            ->execute([$order_id, $fnb_id, $item['qty'], $item['price'] * $item['qty']]);
    }

    // Update available seats
    $db->prepare("UPDATE showtimes SET available_seats = available_seats - ? WHERE id=?")->execute([count($seatArr), $showtime_id]);

    header("Location: payment_success.php?order_id=$order_id");
    exit;
}

$showtime_id = intval($_GET['showtime_id'] ?? 0);
$film_id = intval($_GET['film_id'] ?? 0);
$seats = htmlspecialchars($_GET['seats'] ?? '');
$cartJson = $_GET['cart'] ?? '{}';
$total = intval($_GET['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran - TursMovie</title>
    <?= getBaseStyles() ?>
    <style>
        .mobile-wrap {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            padding-bottom: 100px;
        }

        .top-bar {
            background: linear-gradient(135deg, var(--red), var(--red-dark));
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .back-btn {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .top-bar h1 {
            font-size: 1rem;
            font-weight: 700;
            color: white;
        }

        .top-bar p {
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.75);
        }

        .content {
            padding: 20px;
        }

        .total-box {
            background: linear-gradient(135deg, rgba(230, 21, 21, 0.15), rgba(75, 0, 130, 0.1));
            border: 1px solid rgba(230, 21, 21, 0.3);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }

        .total-box p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .total-box h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-top: 4px;
        }

        .method-btn {
            background: var(--bg-card);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }

        .method-btn:hover,
        .method-btn.selected {
            border-color: var(--red);
            background: rgba(230, 21, 21, 0.06);
        }

        .method-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .method-icon.blue {
            background: rgba(59, 130, 246, 0.15);
        }

        .method-icon.purple {
            background: rgba(168, 85, 247, 0.15);
        }

        .method-icon.green {
            background: rgba(34, 197, 94, 0.15);
        }

        .method-info h3 {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .method-info p {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .method-check {
            margin-left: auto;
            color: var(--red);
            display: none;
        }

        .method-btn.selected .method-check {
            display: block;
        }

        .info-box {
            background: var(--bg-card2);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 20px;
            font-size: 0.8rem;
        }

        .info-box p {
            color: var(--text-muted);
            line-height: 1.7;
        }

        .info-box .title {
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pay-btn {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 40px);
            max-width: 440px;
            background: linear-gradient(135deg, var(--red), var(--red-dark));
            color: white;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 16px;
            padding: 16px;
            cursor: pointer;
            z-index: 100;
            box-shadow: 0 8px 25px rgba(230, 21, 21, 0.4);
            transition: all 0.2s;
        }

        .pay-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pay-btn:not(:disabled):hover {
            transform: translateX(-50%) translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="mobile-wrap">
        <div class="top-bar">
            <button class="back-btn" onclick="history.back()">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M19 12H5M12 5l-7 7 7 7" />
                </svg>
            </button>
            <div>
                <h1>Metode Pembayaran</h1>
                <p>Pilih cara pembayaran Anda</p>
            </div>
        </div>
        <div class="content">
            <div class="total-box">
                <p>Total Pembayaran</p>
                <h1>Rp <?= number_format($total, 0, ',', '.') ?></h1>
            </div>

            <button class="method-btn" onclick="selectMethod('ewallet', this)">
                <div class="method-icon blue">
                    <svg width="22" height="22" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="2" y="5" width="20" height="14" rx="2" />
                        <line x1="2" y1="10" x2="22" y2="10" />
                    </svg>
                </div>
                <div class="method-info">
                    <h3>E-Wallet</h3>
                    <p>GoPay, OVO, Dana, LinkAja</p>
                </div>
                <svg class="method-check" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </button>
            <button class="method-btn" onclick="selectMethod('card', this)">
                <div class="method-icon purple">
                    <svg width="22" height="22" fill="none" stroke="#a855f7" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="1" y="4" width="22" height="16" rx="2" />
                        <line x1="1" y1="10" x2="23" y2="10" />
                    </svg>
                </div>
                <div class="method-info">
                    <h3>Kartu Kredit/Debit</h3>
                    <p>Visa, Mastercard, JCB</p>
                </div>
                <svg class="method-check" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </button>
            <button class="method-btn" onclick="selectMethod('cash', this)">
                <div class="method-icon green">
                    <svg width="22" height="22" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="2" y="6" width="20" height="12" rx="2" />
                        <circle cx="12" cy="12" r="3" />
                        <path d="M6 12h.01M18 12h.01" />
                    </svg>
                </div>
                <div class="method-info">
                    <h3>Bayar di Kasir</h3>
                    <p>Tunai di lokasi</p>
                </div>
                <svg class="method-check" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </button>

            <div class="info-box">
                <div class="title">💡 Informasi:</div>
                <p>• Pembayaran e-wallet dan kartu akan diproses langsung<br>
                    • Pilih "Bayar di Kasir" untuk pembayaran tunai di lokasi<br>
                    • Tunjukkan QR code ke kasir setelah pemesanan</p>
            </div>
        </div>
    </div>

    <form id="payForm" action="payment.php" method="POST" style="display:none">
        <input name="showtime_id" value="<?= $showtime_id ?>">
        <input name="film_id" value="<?= $film_id ?>">
        <input name="seats" value="<?= htmlspecialchars($seats) ?>">
        <input name="cart" value="<?= htmlspecialchars($cartJson) ?>">
        <input name="total" value="<?= $total ?>">
        <input name="payment_method" id="pmInput" value="">
    </form>

    <button class="pay-btn" id="payBtn" disabled onclick="submitPay()">Bayar Sekarang</button>

    <script>
        let selectedMethod = null;

        function selectMethod(method, btn) {
            document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedMethod = method;
            document.getElementById('payBtn').disabled = false;
        }

        function submitPay() {
            if (!selectedMethod) return;
            document.getElementById('pmInput').value = selectedMethod;
            document.getElementById('payForm').submit();
        }
    </script>
</body>

</html>