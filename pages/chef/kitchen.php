<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('chef');

$db = getDB();

// Handle status updates
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['start_cook'])) {
        $db->prepare("UPDATE order_fnb SET cook_status='cooking' WHERE id=?")->execute([$_POST['fnb_id']]);
    }
    if (isset($_POST['mark_ready'])) {
        $db->prepare("UPDATE order_fnb SET cook_status='ready' WHERE id=?")->execute([$_POST['fnb_id']]);
    }
    if (isset($_POST['mark_done'])) {
        $db->prepare("UPDATE order_fnb SET cook_status='done' WHERE id=?")->execute([$_POST['fnb_id']]);
    }
    header("Location: kitchen.php"); exit;
}

$tab = $_GET['tab'] ?? 'new';

function getFnbByStatus($db, $status) {
    $sql = "SELECT of.*, m.name as item_name, u.name as cname, o.id as order_id
            FROM order_fnb of JOIN fnb_menu m ON of.fnb_id=m.id 
            JOIN orders o ON of.order_id=o.id JOIN users u ON o.user_id=u.id
            WHERE of.cook_status=? ORDER BY o.created_at ASC";
    $stmt = $db->prepare($sql); $stmt->execute([$status]);
    return $stmt->fetchAll();
}

$newOrders = getFnbByStatus($db, 'new');
$cooking = getFnbByStatus($db, 'cooking');
$ready = getFnbByStatus($db, 'ready');
$done = getFnbByStatus($db, 'done');

$totalToday = count($newOrders) + count($cooking) + count($ready) + count($done);
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chef Kitchen - TursMovie</title><?= getBaseStyles() ?>
<style>
.mobile-wrap{max-width:480px;margin:0 auto;min-height:100vh;padding-bottom:80px}
.chef-header{background:linear-gradient(135deg,#1a0a00,#2d1500);border-bottom:1px solid rgba(245,158,11,0.2);padding:16px 20px}
.chef-header-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px}
.chef-name{font-size:0.82rem;color:rgba(245,158,11,0.8)}
.chef-title{font-size:1.3rem;font-weight:800}
.chef-stats{display:flex;gap:16px;margin-top:12px}
.chef-stat{text-align:center;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.15);border-radius:10px;padding:8px 16px}
.chef-stat-num{font-size:1.2rem;font-weight:800;color:#f59e0b}
.chef-stat-lbl{font-size:0.7rem;color:var(--text-muted)}
.tab-nav{display:flex;background:var(--bg-card);border-bottom:1px solid var(--border)}
.tab-item{flex:1;padding:12px;text-align:center;font-size:0.8rem;font-weight:600;color:var(--text-muted);cursor:pointer;text-decoration:none;border-bottom:2px solid transparent;transition:all 0.2s}
.tab-item.active{color:#f59e0b;border-bottom-color:#f59e0b}
.tab-count{background:var(--red);color:white;border-radius:10px;padding:1px 6px;font-size:0.65rem;margin-left:4px}
.content{padding:16px}
.order-card{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:16px;margin-bottom:12px}
.order-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px}
.order-id{font-size:0.75rem;color:var(--text-muted)}
.customer-name{font-size:0.95rem;font-weight:700}
.order-time{font-size:0.75rem;color:var(--text-muted)}
.priority-badge{font-size:0.7rem;font-weight:700;padding:3px 8px;border-radius:6px}
.priority-1{background:rgba(230,21,21,0.2);color:var(--red)}
.items-list{background:var(--bg-card2);border-radius:10px;padding:10px;margin-bottom:10px}
.item-row{font-size:0.84rem;padding:4px 0;display:flex;align-items:center;gap:6px}
.item-row::before{content:'•';color:#f59e0b}
.action-btns{display:flex;gap:8px}
.empty-state{text-align:center;padding:48px 20px;color:var(--text-muted)}
.empty-state svg{margin:0 auto 12px;display:block}
.bottom-nav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:480px;background:rgba(15,17,23,0.95);backdrop-filter:blur(16px);border-top:1px solid var(--border);display:flex;z-index:100;padding:8px 0 12px}
.nav-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:8px;text-decoration:none;color:var(--text-muted);font-size:0.7rem;font-weight:600}
.nav-item.active{color:#f59e0b}
.nav-item svg{width:22px;height:22px}
.status-bubble{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600}
.bubble-new{background:rgba(245,158,11,0.15);color:#f59e0b}
.bubble-cooking{background:rgba(230,21,21,0.15);color:var(--red)}
.bubble-ready{background:rgba(34,197,94,0.15);color:var(--green)}
.bubble-done{background:rgba(255,255,255,0.08);color:var(--text-muted)}
</style></head><body>
<div class="mobile-wrap">
    <div class="chef-header">
        <div class="chef-header-row">
            <div><div class="chef-name">Chef Kitchen</div><div class="chef-title"><?= htmlspecialchars($_SESSION['name']) ?></div></div>
        </div>
        <div class="chef-stats">
            <div class="chef-stat"><div class="chef-stat-num"><?= count($newOrders) ?></div><div class="chef-stat-lbl">Baru</div></div>
            <div class="chef-stat"><div class="chef-stat-num"><?= count($cooking) ?></div><div class="chef-stat-lbl">Masak</div></div>
            <div class="chef-stat"><div class="chef-stat-num"><?= count($ready) ?></div><div class="chef-stat-lbl">Siap</div></div>
            <div class="chef-stat"><div class="chef-stat-num"><?= $totalToday ?></div><div class="chef-stat-lbl">Total</div></div>
        </div>
    </div>

    <div class="tab-nav">
        <a href="kitchen.php?tab=new" class="tab-item <?= $tab==='new'?'active':'' ?>">Baru<?php if(count($newOrders)):?><span class="tab-count"><?=count($newOrders)?></span><?php endif;?></a>
        <a href="kitchen.php?tab=cooking" class="tab-item <?= $tab==='cooking'?'active':'' ?>">Masak<?php if(count($cooking)):?><span class="tab-count"><?=count($cooking)?></span><?php endif;?></a>
        <a href="kitchen.php?tab=ready" class="tab-item <?= $tab==='ready'?'active':'' ?>">Siap<?php if(count($ready)):?><span class="tab-count"><?=count($ready)?></span><?php endif;?></a>
        <a href="kitchen.php?tab=done" class="tab-item <?= $tab==='done'?'active':'' ?>">Selesai</a>
    </div>

    <div class="content">
        <?php
        $items = match($tab) {
            'new' => $newOrders,
            'cooking' => $cooking,
            'ready' => $ready,
            'done' => $done,
            default => $newOrders
        };
        
        // Group by order_id
        $grouped = [];
        foreach ($items as $item) { $grouped[$item['order_id']][] = $item; }
        
        if (empty($grouped)):
        ?>
        <div class="empty-state">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Tidak ada pesanan
        </div>
        <?php else: ?>
        <?php $priority=1; foreach ($grouped as $oid => $orderItems):
            $first = $orderItems[0]; ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="customer-name"><?= htmlspecialchars($first['cname']) ?></div>
                    <div class="order-id">Order #<?= $oid ?></div>
                </div>
                <div style="text-align:right">
                    <span class="status-bubble bubble-<?= $first['cook_status'] ?>"><?= ucfirst($first['cook_status']==='new'?'Baru':($first['cook_status']==='cooking'?'Sedang Dimasak':($first['cook_status']==='ready'?'Siap':'Selesai'))) ?></span>
                    <?php if ($tab==='new'): ?><br><span class="priority-badge priority-1" style="margin-top:4px;display:inline-block">Prioritas <?= $priority ?></span><?php endif; ?>
                </div>
            </div>
            <div class="items-list">
                <?php foreach ($orderItems as $fi): ?>
                <div class="item-row"><?= $fi['quantity'] ?>x <?= htmlspecialchars($fi['item_name']) ?></div>
                <?php endforeach; ?>
            </div>
            <?php if ($tab==='new'): ?>
            <form method="POST" class="action-btns">
                <input type="hidden" name="fnb_id" value="<?= $first['id'] ?>">
                <?php foreach($orderItems as $fi): if($fi['id']!==$first['id']): ?><input type="hidden" name="extra_ids[]" value="<?= $fi['id'] ?>"><?php endif; endforeach; ?>
                <button name="start_cook" class="btn btn-primary" style="flex:1">🍳 Mulai Memasak</button>
            </form>
            <?php elseif ($tab==='cooking'): ?>
            <form method="POST" class="action-btns">
                <input type="hidden" name="fnb_id" value="<?= $first['id'] ?>">
                <button name="mark_ready" class="btn btn-success" style="flex:1">✓ Tandai Siap</button>
            </form>
            <?php elseif ($tab==='ready'): ?>
            <form method="POST" class="action-btns">
                <input type="hidden" name="fnb_id" value="<?= $first['id'] ?>">
                <button name="mark_done" class="btn btn-outline" style="flex:1">📦 Selesai & Ambil</button>
            </form>
            <?php endif; ?>
        </div>
        <?php $priority++; endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<nav class="bottom-nav">
    <a class="nav-item active" href="kitchen.php"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>Dapur</a>
    <a class="nav-item" href="../../logout.php"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Keluar</a>
</nav>
</body></html>
