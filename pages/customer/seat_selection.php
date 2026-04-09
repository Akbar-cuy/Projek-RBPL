<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();
$showtime_id = intval($_GET['showtime_id'] ?? 0);
$film_id     = intval($_GET['film_id'] ?? 0);

if (!$showtime_id) {
    header('Location: home.php');
    exit;
}

$showtime = $db->prepare("
    SELECT s.*, f.title, f.price, f.image, f.rating, f.duration
    FROM showtimes s
    JOIN films f ON s.film_id = f.id
    WHERE s.id = ?
");
$showtime->execute([$showtime_id]);
$showtime = $showtime->fetch();
if (!$showtime) {
    header('Location: home.php');
    exit;
}

// Booked seats — check BOTH order_status and payment_status
$bookedStmt = $db->prepare("
    SELECT os.seat_code
    FROM order_seats os
    JOIN orders o ON os.order_id = o.id
    WHERE o.showtime_id = ?
      AND o.order_status != 'cancelled'
");
$bookedStmt->execute([$showtime_id]);
$booked = array_column($bookedStmt->fetchAll(), 'seat_code');

$rows = ['A', 'B', 'C', 'D', 'E', 'F'];
$totalSeats   = 48; // 6 rows x 8 cols
$bookedCount  = count($booked);
$availCount   = $totalSeats - $bookedCount;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Pilih Kursi – <?= htmlspecialchars($showtime['title']) ?></title>
    <?= getBaseStyles() ?>
    <style>
        .mobile-wrap {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            padding-bottom: 130px;
        }

        /* Top bar */
        .top-bar {
            background: var(--bg-card);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .back-btn {
            width: 38px;
            height: 38px;
            background: var(--bg-card2);
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .top-bar-info h1 {
            font-size: 0.95rem;
            font-weight: 700;
        }

        .top-bar-info p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Film info strip */
        .film-strip {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: var(--bg-card2);
            border-bottom: 1px solid var(--border);
        }

        .film-strip img {
            width: 44px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .film-strip-info {
            flex: 1;
            min-width: 0;
        }

        .film-strip-info h2 {
            font-size: 0.88rem;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .film-strip-info p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .avail-chip {
            background: rgba(34, 197, 94, 0.15);
            color: var(--green);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            white-space: nowrap;
        }

        /* Screen */
        .screen-wrap {
            padding: 20px 16px 10px;
        }

        .screen-label {
            background: linear-gradient(90deg, transparent, rgba(230, 21, 21, 0.2), transparent);
            border-top: 3px solid rgba(230, 21, 21, 0.5);
            text-align: center;
            padding: 8px;
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 700;
            letter-spacing: 3px;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        /* Seat grid */
        .seat-scroll {
            overflow-x: auto;
            padding: 0 16px;
        }

        .seat-grid {
            display: flex;
            flex-direction: column;
            gap: 7px;
            width: fit-content;
            margin: 0 auto;
        }

        .seat-row {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .row-label {
            width: 18px;
            text-align: center;
            font-size: 0.68rem;
            color: var(--text-muted);
            font-weight: 700;
            flex-shrink: 0;
        }

        .col-nums {
            display: flex;
            gap: 5px;
            margin-left: 23px;
            margin-bottom: 4px;
        }

        .col-num {
            width: 34px;
            text-align: center;
            font-size: 0.65rem;
            color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        .aisle-gap {
            width: 10px;
            flex-shrink: 0;
        }

        .seat {
            width: 34px;
            height: 30px;
            border-radius: 6px 6px 4px 4px;
            border: 1.5px solid var(--border);
            cursor: pointer;
            transition: all 0.12s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.62rem;
            font-weight: 700;
            color: var(--text-muted);
            background: var(--bg-card2);
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        .seat.available:hover,
        .seat.available:active {
            border-color: var(--red);
            background: rgba(230, 21, 21, 0.15);
            color: var(--red);
        }

        .seat.selected {
            background: var(--red);
            border-color: var(--red);
            color: white;
            transform: scale(1.08);
            box-shadow: 0 3px 10px rgba(230, 21, 21, 0.4);
        }

        .seat.booked {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.15);
            cursor: not-allowed;
        }

        /* Legend */
        .legend {
            display: flex;
            gap: 16px;
            justify-content: center;
            padding: 14px 16px;
            flex-wrap: wrap;
        }

        .leg-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.73rem;
            color: var(--text-muted);
        }

        .leg-box {
            width: 20px;
            height: 16px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        /* Summary bar */
        .summary-bar {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: rgba(26, 29, 39, 0.97);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--border);
            padding: 14px 16px 24px;
        }

        .sum-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .sum-price {
            font-size: 1.2rem;
            font-weight: 800;
        }

        .sum-seats-label {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .seat-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            max-height: 40px;
            overflow: hidden;
        }

        .seat-chip {
            background: var(--red);
            color: white;
            border-radius: 5px;
            padding: 2px 7px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .no-seat-hint {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="mobile-wrap">

        <!-- Top bar -->
        <div class="top-bar">
            <button class="back-btn" onclick="history.back()">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M19 12H5M12 5l-7 7 7 7" />
                </svg>
            </button>
            <div class="top-bar-info">
                <h1>Pilih Kursi</h1>
                <p><?= date('d M Y', strtotime($showtime['show_date'])) ?> &nbsp;·&nbsp; <?= substr($showtime['show_time'], 0, 5) ?> &nbsp;·&nbsp; <?= htmlspecialchars($showtime['theater']) ?></p>
            </div>
        </div>

        <!-- Film strip -->
        <div class="film-strip">
            <img src="<?= htmlspecialchars($showtime['image']) ?>" alt="" onerror="this.style.display='none'">
            <div class="film-strip-info">
                <h2><?= htmlspecialchars($showtime['title']) ?></h2>
                <p><?= $showtime['duration'] ?> menit &nbsp;·&nbsp; <?= htmlspecialchars($showtime['rating']) ?> &nbsp;·&nbsp; <?= formatRupiah($showtime['price']) ?>/kursi</p>
            </div>
            <span class="avail-chip"><?= $availCount ?> tersedia</span>
        </div>

        <!-- Screen -->
        <div class="screen-wrap">
            <div class="screen-label">L A Y A R</div>
        </div>

        <!-- Column numbers -->
        <div class="seat-scroll">
            <div class="col-nums">
                <?php for ($c = 1; $c <= 8; $c++): ?>
                    <div class="col-num"><?= $c ?></div>
                    <?php if ($c === 4): ?><div class="col-num" style="width:10px"></div><?php endif; ?>
                <?php endfor; ?>
            </div>

            <!-- Seat grid -->
            <div class="seat-grid">
                <?php foreach ($rows as $row): ?>
                    <div class="seat-row">
                        <div class="row-label"><?= $row ?></div>
                        <?php for ($col = 1; $col <= 8; $col++):
                            $code = $row . $col;
                            $isBooked = in_array($code, $booked);
                            $cls = $isBooked ? 'booked' : 'available';
                        ?>
                            <div class="seat <?= $cls ?>"
                                id="seat-<?= $code ?>"
                                data-code="<?= $code ?>"
                                data-booked="<?= $isBooked ? '1' : '0' ?>"
                                onclick="toggleSeat(this)"><?= $code ?></div>
                            <?php if ($col === 4): ?><div class="aisle-gap"></div><?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="leg-item">
                <div class="leg-box" style="background:var(--bg-card2);border:1.5px solid var(--border)"></div>Tersedia
            </div>
            <div class="leg-item">
                <div class="leg-box" style="background:var(--red)"></div>Dipilih
            </div>
            <div class="leg-item">
                <div class="leg-box" style="background:rgba(255,255,255,0.04);border:1.5px solid rgba(255,255,255,0.06)"></div>Terisi
            </div>
        </div>

    </div><!-- /mobile-wrap -->

    <!-- Summary bar -->
    <div class="summary-bar">
        <div class="sum-row">
            <div>
                <div class="sum-seats-label">Kursi dipilih:</div>
                <div id="seat-chips-wrap" class="seat-chips">
                    <span class="no-seat-hint">Belum ada kursi dipilih</span>
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-size:0.72rem;color:var(--text-muted)">Total</div>
                <div class="sum-price" id="totalPrice">Rp 0</div>
            </div>
        </div>
        <button class="btn btn-primary btn-lg" id="continueBtn" onclick="continueBooking()" disabled
            style="border-radius:14px;font-size:1rem;font-weight:700;letter-spacing:0.2px">
            Lanjut →
        </button>
    </div>

    <script>
        const PRICE = <?= intval($showtime['price']) ?>;
        const SHOWTIME_ID = <?= $showtime_id ?>;
        const FILM_ID = <?= $film_id ?>;
        let selected = [];

        function toggleSeat(el) {
            if (el.dataset.booked === '1') return;
            const code = el.dataset.code;
            const idx = selected.indexOf(code);
            if (idx > -1) {
                selected.splice(idx, 1);
                el.classList.remove('selected');
                el.classList.add('available');
            } else {
                selected.push(code);
                el.classList.remove('available');
                el.classList.add('selected');
            }
            updateBar();
        }

        function updateBar() {
            const wrap = document.getElementById('seat-chips-wrap');
            if (selected.length === 0) {
                wrap.innerHTML = '<span class="no-seat-hint">Belum ada kursi dipilih</span>';
            } else {
                wrap.innerHTML = selected.map(s => `<span class="seat-chip">${s}</span>`).join('');
            }
            const total = PRICE * selected.length;
            document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('continueBtn').disabled = selected.length === 0;
            document.getElementById('continueBtn').textContent = selected.length ?
                `Lanjut — ${selected.length} Kursi →` :
                'Lanjut →';
        }

        function continueBooking() {
            if (!selected.length) return;
            const p = new URLSearchParams({
                showtime_id: SHOWTIME_ID,
                film_id: FILM_ID,
                seats: selected.join(',')
            });
            location.href = 'fnb_selection.php?' + p.toString();
        }
    </script>
</body>

</html>