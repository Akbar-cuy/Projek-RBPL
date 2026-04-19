<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('chef');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_cook'])) {
        $db->prepare("UPDATE order_fnb SET cook_status='cooking' WHERE id=?")->execute([$_POST['fnb_id']]);
    }
    if (isset($_POST['mark_ready'])) {
        $db->prepare("UPDATE order_fnb SET cook_status='ready' WHERE id=?")->execute([$_POST['fnb_id']]);
    }
    if (isset($_POST['mark_done'])) {
        $db->prepare("UPDATE order_fnb SET cook_status='done' WHERE id=?")->execute([$_POST['fnb_id']]);
    }
    $back = isset($_GET['tab']) ? '?tab=' . $_GET['tab'] : '';
    header("Location: kitchen.php" . $back);
    exit;
}

$tab = $_GET['tab'] ?? 'new';

function getFnbByStatus($db, $status)
{
    // Gunakan LEFT JOIN agar order tetap muncul meski user_id NULL
    // Gunakan COALESCE untuk mengambil guest_name jika u.name kosong
    $sql = "SELECT of.*, m.name as item_name, 
            COALESCE(u.name, o.guest_name) as cname, 
            o.id as order_id, o.created_at as order_time
            FROM order_fnb of
            JOIN fnb_menu m ON of.fnb_id = m.id
            JOIN orders o ON of.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE of.cook_status = ?
            ORDER BY o.created_at ASC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

function groupByOrder($items)
{
    $grouped = [];
    foreach ($items as $item) {
        $grouped[$item['order_id']][] = $item;
    }
    return $grouped;
}

$newOrders = getFnbByStatus($db, 'new');
$cooking = getFnbByStatus($db, 'cooking');
$ready = getFnbByStatus($db, 'ready');
$done = getFnbByStatus($db, 'done');
$totalToday = count($newOrders) + count($cooking) + count($ready) + count($done);

$tabs = [
    'new' => ['label' => 'Baru', 'count' => count($newOrders), 'orders' => $newOrders],
    'cooking' => ['label' => 'Masak', 'count' => count($cooking), 'orders' => $cooking],
    'ready' => ['label' => 'Siap', 'count' => count($ready), 'orders' => $ready],
    'done' => ['label' => 'Selesai', 'count' => count($done), 'orders' => $done],
];

$activeItems = $tabs[$tab]['orders'] ?? $newOrders;
$activeGrouped = groupByOrder($activeItems);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chef Kitchen</title>
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
            color: #E7000B;
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

        /* Indikator titik kuning untuk tab ada pesanan tapi tidak aktif */
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

        .dot-new {
            background: #F9A825;
        }

        .dot-cooking {
            background: #1565C0;
        }

        .dot-ready {
            background: #2E7D32;
        }

        .dot-done {
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

        /* ── ORDER CARD ── */
        .order-card {
            background: #fff;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .card-name {
            font-size: 0.93rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .card-id {
            font-size: 0.72rem;
            color: #bbb;
            margin-top: 2px;
        }

        .card-time {
            font-size: 0.72rem;
            color: #bbb;
            margin-top: 2px;
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .badge-new {
            background: #FFF9C4;
            color: #F57F17;
        }

        .badge-cooking {
            background: #BBDEFB;
            color: #0D47A1;
        }

        .badge-ready {
            background: #C8E6C9;
            color: #1B5E20;
        }

        .badge-done {
            background: #F5F5F5;
            color: #757575;
        }

        .priority-tag {
            font-size: 0.65rem;
            font-weight: 700;
            background: #FFF3E0;
            color: #E65100;
            padding: 2px 7px;
            border-radius: 5px;
            margin-top: 4px;
            display: inline-block;
        }

        .items-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }

        .item-row {
            font-size: 0.82rem;
            color: #444;
            padding: 3px 0;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .item-dot {
            width: 5px;
            height: 5px;
            background: #E7000B;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* Buttons */
        .btn-cook {
            width: 100%;
            padding: 12px;
            background: #E7000B;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.86rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-cook:hover {
            background: #C0000A;
        }

        .btn-ready {
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

        .btn-ready:hover {
            background: #f1f8f1;
        }

        .btn-done {
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

        .btn-done:hover {
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
            color: #E7000B;
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
                    <div class="header-title">Chef Kitchen</div>
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
                    <a href="kitchen.php?tab=<?= $key ?>" class="tab-btn <?= $tab === $key ? 'active' : '' ?>">
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

            <?php
            $sectionMeta = [
                'new' => ['dot' => 'new', 'label' => 'Pesanan Baru'],
                'cooking' => ['dot' => 'cooking', 'label' => 'Sedang Dimasak'],
                'ready' => ['dot' => 'ready', 'label' => 'Siap Diambil'],
                'done' => ['dot' => 'done', 'label' => 'Selesai'],
            ];
            $meta = $sectionMeta[$tab];
            ?>

            <div class="section-bar">
                <span class="section-dot dot-<?= $meta['dot'] ?>"></span>
                <span class="section-label"><?= $meta['label'] ?></span>
                <span class="section-count"><?= count($activeGrouped) ?> pesanan</span>
            </div>

            <?php if (empty($activeGrouped)): ?>
                <div class="empty">Tidak ada pesanan</div>

            <?php else:
                $priority = 1;
                foreach ($activeGrouped as $oid => $orderItems):
                    $first = $orderItems[0];
                    $timeStr = date('H:i', strtotime($first['order_time']));
                    $badgeMap = [
                        'new' => ['class' => 'badge-new', 'text' => 'Pending'],
                        'cooking' => ['class' => 'badge-cooking', 'text' => 'Sedang Dimasak'],
                        'ready' => ['class' => 'badge-ready', 'text' => 'Siap'],
                        'done' => ['class' => 'badge-done', 'text' => 'Selesai'],
                    ];
                    $bInfo = $badgeMap[$first['cook_status']];
                    ?>
                    <div class="order-card">
                        <div class="card-top">
                            <div>
                                <div class="card-name"><?= htmlspecialchars($first['cname']) ?></div>
                                <div class="card-id">Order #<?= $oid ?></div>
                                <div class="card-time"><?= $timeStr ?></div>
                            </div>
                            <div style="text-align:right">
                                <span class="badge <?= $bInfo['class'] ?>"><?= $bInfo['text'] ?></span>
                                <?php if ($tab === 'new'): ?>
                                    <br><span class="priority-tag">Prioritas <?= $priority ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="items-box">
                            <?php foreach ($orderItems as $fi): ?>
                                <div class="item-row">
                                    <span class="item-dot"></span>
                                    <?= $fi['quantity'] ?>x <?= htmlspecialchars($fi['item_name']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($tab === 'new'): ?>
                            <form method="POST" action="kitchen.php?tab=new">
                                <input type="hidden" name="fnb_id" value="<?= $first['id'] ?>">
                                <button type="submit" name="start_cook" class="btn-cook">Mulai Memasak</button>
                            </form>

                        <?php elseif ($tab === 'cooking'): ?>
                            <form method="POST" action="kitchen.php?tab=cooking">
                                <input type="hidden" name="fnb_id" value="<?= $first['id'] ?>">
                                <button type="submit" name="mark_ready" class="btn-ready">Tandai Siap</button>
                            </form>

                        <?php elseif ($tab === 'ready'): ?>
                            <form method="POST" action="kitchen.php?tab=ready">
                                <input type="hidden" name="fnb_id" value="<?= $first['id'] ?>">
                                <button type="submit" name="mark_done" class="btn-done">Selesai &amp; Ambil</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php $priority++; endforeach; endif; ?>

        </div>
    </div>

    <nav class="bottom-nav">
        <a class="nav-item active" href="kitchen.php">
            <svg viewBox="0 0 24 24">
                <path d="M17 8h1a4 4 0 1 1 0 8h-1" />
                <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4z" />
                <line x1="6" y1="2" x2="6" y2="4" />
                <line x1="10" y1="2" x2="10" y2="4" />
                <line x1="14" y1="2" x2="14" y2="4" />
            </svg>
            Pesanan
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