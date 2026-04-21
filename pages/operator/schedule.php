<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('operator');

$db    = getDB();
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = intval($_POST['show_id']);
    if (isset($_POST['mark_ready']))  $db->prepare("UPDATE showtimes SET status='ready'    WHERE id=?")->execute([$sid]);
    if (isset($_POST['start_show']))  $db->prepare("UPDATE showtimes SET status='showing'  WHERE id=?")->execute([$sid]);
    if (isset($_POST['finish_show'])) $db->prepare("UPDATE showtimes SET status='finished' WHERE id=?")->execute([$sid]);
    $back = isset($_GET['tab']) ? '?tab=' . $_GET['tab'] : '';
    header("Location: schedule.php" . $back);
    exit;
}

$tab = $_GET['tab'] ?? 'scheduled';

function getShows($db, $status) // Hapus parameter $today
{
    $stmt = $db->prepare(
        "SELECT s.*, f.title, f.duration,
                (s.total_seats - s.available_seats) AS booked
         FROM showtimes s
         JOIN films f ON s.film_id = f.id
         WHERE s.status = ? 
         ORDER BY s.show_date ASC, s.show_time ASC" // Diurutkan berdasarkan tanggal juga
    );
    $stmt->execute([$status]); // Hanya mengirim status
    return $stmt->fetchAll();
}

$scheduled = getShows($db, 'scheduled');
$ready     = getShows($db, 'ready');
$showing   = getShows($db, 'showing');
$finished  = getShows($db, 'finished');

$totalViewers = (int) $db->query(
    "SELECT COUNT(*) FROM order_seats os
     JOIN orders o ON os.order_id = o.id
     JOIN showtimes s ON o.showtime_id = s.id"
)->fetchColumn();

$tabs = [
    'scheduled' => ['label' => 'Jadwal',  'count' => count($scheduled), 'orders' => $scheduled],
    'ready'     => ['label' => 'Siap',    'count' => count($ready),     'orders' => $ready],
    'showing'   => ['label' => 'Tayang',  'count' => count($showing),   'orders' => $showing],
    'finished'  => ['label' => 'Selesai', 'count' => count($finished),  'orders' => $finished],
];

$activeItems = $tabs[$tab]['orders'] ?? $scheduled;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Film Operator</title>
    <?= getBaseStyles() ?>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #f2f3f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .wrap {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        /* ── HEADER ── */
        .header {
            background: #E7000B;
            padding: 20px 20px 0;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .header-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .header-sub {
            font-size: 0.77rem;
            color: rgba(255, 255, 255, 0.72);
            margin-top: 1px;
        }

        .logout-btn {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            flex-shrink: 0;
        }

        .logout-btn svg {
            width: 17px;
            height: 17px;
            stroke: #fff;
            fill: none;
            stroke-width: 2;
        }

        /* ── TAB BUTTONS ── */
        .tab-row {
            display: flex;
            gap: 8px;
        }

        .tab-btn {
            flex: 1;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            border-radius: 12px 12px 0 0;
            padding: 10px 6px 14px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            display: block;
            position: relative;
            transition: background 0.15s;
        }

        .tab-btn.active {
            background: #fff;
        }

        .tab-btn-num {
            font-size: 1.3rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1;
            display: block;
        }

        .tab-btn.active .tab-btn-num {
            color: #1565C0;
        }

        .tab-btn-lbl {
            font-size: 0.68rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.72);
            margin-top: 3px;
            display: block;
        }

        .tab-btn.active .tab-btn-lbl {
            color: #999;
        }

        .tab-dot {
            position: absolute;
            top: 7px;
            right: 9px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #FFD600;
        }

        /* ── CONTENT ── */
        .content {
            padding: 14px;
        }

        /* Viewer summary card */
        .viewer-card {
            background: #fff;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .viewer-left {
            flex: 1;
        }

        .viewer-label {
            font-size: 0.75rem;
            color: #aaa;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .viewer-num {
            font-size: 2rem;
            font-weight: 800;
            color: #1565C0;
            line-height: 1;
        }

        .viewer-sub {
            font-size: 0.72rem;
            color: #bbb;
            margin-top: 3px;
        }

        .viewer-icon {
            width: 48px;
            height: 48px;
            background: #E3F2FD;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .viewer-icon svg {
            width: 24px;
            height: 24px;
            stroke: #1565C0;
            fill: none;
            stroke-width: 2;
        }

        /* Section bar */
        .section-bar {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 12px;
        }

        .section-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-scheduled {
            background: #F9A825;
        }

        .dot-ready {
            background: #1565C0;
        }

        .dot-showing {
            background: #C62828;
        }

        .dot-finished {
            background: #9E9E9E;
        }

        .section-label {
            font-size: 0.86rem;
            font-weight: 700;
            color: #222;
        }

        .section-count {
            margin-left: auto;
            font-size: 0.75rem;
            color: #aaa;
        }

        /* ── SHOW CARD ── */
        .show-card {
            background: #fff;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        /* Live indicator */
        .live-bar {
            display: flex;
            align-items: center;
            gap: 7px;
            background: #FFF3F3;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 12px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #C62828;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0.2
            }
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #C62828;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
            flex-shrink: 0;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            gap: 10px;
        }

        .film-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .film-meta {
            font-size: 0.76rem;
            color: #aaa;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        /* Badges */
        .badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .badge-scheduled {
            background: #FFF9C4;
            color: #F57F17;
        }

        .badge-ready {
            background: #BBDEFB;
            color: #0D47A1;
        }

        .badge-showing {
            background: #FFEBEE;
            color: #C62828;
        }

        .badge-finished {
            background: #F5F5F5;
            color: #757575;
        }

        /* Seat progress */
        .seat-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
        }

        .seat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 7px;
        }

        .seat-label {
            font-size: 0.78rem;
            color: #666;
            font-weight: 500;
        }

        .seat-num {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1565C0;
        }

        .progress-track {
            background: #E0E0E0;
            border-radius: 4px;
            height: 6px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .progress-fill {
            height: 100%;
            background: #1565C0;
            border-radius: 4px;
        }

        .seat-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            color: #bbb;
        }

        /* Buttons */
        .btn-blue {
            width: 100%;
            padding: 12px;
            background: #1565C0;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-blue:hover {
            background: #0D47A1;
        }

        .btn-green {
            width: 100%;
            padding: 12px;
            background: #fff;
            color: #2E7D32;
            border: 2px solid #2E7D32;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-green:hover {
            background: #f1f8f1;
        }

        .btn-dark {
            width: 100%;
            padding: 12px;
            background: #37474F;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-dark:hover {
            background: #263238;
        }

        /* Empty */
        .empty {
            text-align: center;
            padding: 48px 20px;
            color: #ccc;
            font-size: 0.84rem;
        }

        /* ── BOTTOM NAV ── */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-top: 1px solid #eee;
            display: flex;
            z-index: 100;
            padding: 8px 0 14px;
        }

        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            text-decoration: none;
            color: #bbb;
            font-size: 0.7rem;
            font-weight: 600;
            transition: color 0.15s;
        }

        .nav-item.active {
            color: #1565C0;
        }

        .nav-item svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }
    </style>
</head>

<body>
    <div class="wrap">

        <!-- HEADER -->
        <div class="header">
            <div class="header-top">
                <div>
                    <div class="header-title">Film Operator</div>
                    <div class="header-sub"><?= htmlspecialchars($_SESSION['name']) ?></div>
                </div>
                <a href="../../logout.php" class="logout-btn" title="Keluar">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                </a>
            </div>

            <div class="tab-row">
                <?php foreach ($tabs as $key => $t): ?>
                    <a href="schedule.php?tab=<?= $key ?>" class="tab-btn <?= $tab === $key ? 'active' : '' ?>">
                        <?php if ($tab !== $key && $t['count'] > 0): ?>
                            <span class="tab-dot"></span>
                        <?php endif; ?>
                        <span class="tab-btn-num"><?= $t['count'] ?></span>
                        <span class="tab-btn-lbl"><?= $t['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- Total Penonton -->
            <div class="viewer-card">
                <div class="viewer-left">
                    <div class="viewer-label">Total Penonton</div>
                    <div class="viewer-num"><?= number_format($totalViewers) ?></div>
                </div>
                <div class="viewer-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>
            </div>

            <?php
            $sectionMeta = [
                'scheduled' => ['dot' => 'scheduled', 'label' => 'Jadwal Penayangan'],
                'ready'     => ['dot' => 'ready',     'label' => 'Siap Tayang'],
                'showing'   => ['dot' => 'showing',   'label' => 'Sedang Tayang'],
                'finished'  => ['dot' => 'finished',  'label' => 'Selesai'],
            ];
            $meta = $sectionMeta[$tab];

            $badgeMap = [
                'scheduled' => ['class' => 'badge-scheduled', 'text' => 'Terjadwal'],
                'ready'     => ['class' => 'badge-ready',     'text' => 'Siap'],
                'showing'   => ['class' => 'badge-showing',   'text' => 'Tayang'],
                'finished'  => ['class' => 'badge-finished',  'text' => 'Selesai'],
            ];
            ?>

            <div class="section-bar">
                <span class="section-dot dot-<?= $meta['dot'] ?>"></span>
                <span class="section-label"><?= $meta['label'] ?></span>
                <span class="section-count"><?= count($activeItems) ?> jadwal</span>
            </div>

            <?php if (empty($activeItems)): ?>
                <div class="empty">Tidak ada jadwal penayangan</div>

                <?php else: foreach ($activeItems as $s):
                    $booked = (int) $s['booked'];
                    $total  = (int) $s['total_seats'];
                    $pct    = $total > 0 ? round($booked / $total * 100) : 0;
                    $bInfo  = $badgeMap[$s['status']] ?? $badgeMap['scheduled'];
                ?>
                    <div class="show-card">

                        <?php if ($s['status'] === 'showing'): ?>
                            <div class="live-bar">
                                <span class="live-dot"></span>
                                Film sedang ditayangkan
                            </div>
                        <?php endif; ?>

                        <div class="card-top">
                            <div style="flex:1;min-width:0">
                                <div class="film-title"><?= htmlspecialchars($s['title']) ?></div>
                                <div class="film-meta">
                                    <span style="color:var(--red); font-weight:700"><?= date('d M Y', strtotime($s['show_date'])) ?></span>
                                    <span><?= $s['duration'] ?> menit &nbsp;·&nbsp; <?= substr($s['show_time'], 0, 5) ?></span>
                                    <span><?= htmlspecialchars($s['theater']) ?></span>
                                </div>
                            </div>
                            <span class="badge <?= $bInfo['class'] ?>"><?= $bInfo['text'] ?></span>
                        </div>

                        <!-- Seat Progress -->
                        <div class="seat-box">
                            <div class="seat-header">
                                <span class="seat-label">Penonton</span>
                                <span class="seat-num"><?= $booked ?> / <?= $total ?> kursi</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                            <div class="seat-footer">
                                <span><?= $pct ?>% terisi</span>
                                <span><?= $total - $booked ?> kursi tersisa</span>
                            </div>
                        </div>

                        <?php if ($tab === 'scheduled'): ?>
                            <form method="POST" action="schedule.php?tab=scheduled">
                                <input type="hidden" name="show_id" value="<?= $s['id'] ?>">
                                <button type="submit" name="mark_ready" class="btn-blue">Tandai Siap Tayang</button>
                            </form>

                        <?php elseif ($tab === 'ready'): ?>
                            <form method="POST" action="schedule.php?tab=ready">
                                <input type="hidden" name="show_id" value="<?= $s['id'] ?>">
                                <button type="submit" name="start_show" class="btn-green">Mulai Penayangan</button>
                            </form>

                        <?php elseif ($tab === 'showing'): ?>
                            <form method="POST" action="schedule.php?tab=showing">
                                <input type="hidden" name="show_id" value="<?= $s['id'] ?>">
                                <button type="submit" name="finish_show" class="btn-dark">Selesaikan Penayangan</button>
                            </form>
                        <?php endif; ?>

                    </div>
            <?php endforeach;
            endif; ?>

        </div>
    </div>

    <nav class="bottom-nav">
        <a class="nav-item active" href="schedule.php">
            <svg viewBox="0 0 24 24">
                <rect x="2" y="7" width="20" height="15" rx="2" />
                <polyline points="17 2 12 7 7 2" />
            </svg>
            Jadwal
        </a>
        <a class="nav-item" href="../../logout.php">
            <svg viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            Keluar
        </a>
    </nav>

</body>

</html>