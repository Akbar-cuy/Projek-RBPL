<?php
session_start();
require_once '../../includes/data.php';
require_once '../../includes/mobile_layout.php';
requireLogin(['chef']);

$orders = getOrders();
$tab = $_GET['tab'] ?? 'new';

mobile_head('Chef Kitchen', '
body { background:#0f1929; max-width:100%; }
.chef-header { background:linear-gradient(135deg,#cc0000,#880000); padding:18px 20px; display:flex; align-items:center; justify-content:space-between; }
.chef-header h1 { font-size:1.2rem; font-weight:800; }
.chef-header .user { text-align:right; }
.chef-header .user .name { font-weight:700; font-size:0.9rem; }
.chef-header .user .role { font-size:0.75rem; color:rgba(255,255,255,0.6); }
.tab-bar { background:#1a2436; padding:0 20px; display:flex; border-bottom:1px solid rgba(255,255,255,0.08); }
.tab-item { padding:14px 20px; font-size:0.85rem; font-weight:600; color:rgba(255,255,255,0.5); cursor:pointer; border-bottom:2px solid transparent; white-space:nowrap; text-decoration:none; }
.tab-item.active { color:white; border-bottom-color:#cc0000; }
.order-card { background:#1a2436; border-radius:16px; margin:0 20px 14px; overflow:hidden; }
.order-card-header { padding:14px 18px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.07); }
.order-priority { background:#cc0000; color:white; padding:3px 8px; border-radius:6px; font-size:0.75rem; font-weight:700; }
.order-items { padding:14px 18px; }
.order-item-row { display:flex; align-items:center; gap:8px; padding:5px 0; font-size:0.9rem; }
.qty-circle { width:26px; height:26px; background:#cc0000; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.8rem; flex-shrink:0; }
.order-actions { padding:0 18px 16px; }
.cooking-indicator { display:flex; align-items:center; gap:6px; font-size:0.8rem; color:rgba(255,255,255,0.5); margin-top:6px; }
.pulse { width:10px; height:10px; background:#ff9900; border-radius:50%; animation:pulse 1.2s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(1.2)} }
');
?>
<div class="chef-header">
  <div>
    <div style="font-size:0.75rem;color:rgba(255,255,255,0.6);">Chef Kitchen</div>
    <h1><?= htmlspecialchars($_SESSION['name']) ?></h1>
  </div>
  <a href="../../logout.php" style="color:rgba(255,255,255,0.6);text-decoration:none;font-size:0.8rem;">Keluar</a>
</div>

<div class="tab-bar" style="overflow-x:auto;">
  <a href="?tab=new" class="tab-item <?= $tab==='new'?'active':'' ?>">Baru</a>
  <a href="?tab=cooking" class="tab-item <?= $tab==='cooking'?'active':'' ?>">Masak</a>
  <a href="?tab=ready" class="tab-item <?= $tab==='ready'?'active':'' ?>">Siap</a>
  <a href="?tab=done" class="tab-item <?= $tab==='done'?'active':'' ?>">Selesai</a>
</div>

<div style="padding:20px 0 80px;" id="ordersArea">

<?php if ($tab === 'new'): ?>
  <div style="padding:0 20px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-weight:700;font-size:0.95rem;">Pesanan Baru</span>
    <span style="font-size:0.8rem;color:rgba(255,255,255,0.4);"><?= count($orders) ?> pesanan menunggu</span>
  </div>
  <?php foreach ($orders as $i => $ord): ?>
  <div class="order-card">
    <div class="order-card-header">
      <div>
        <div style="font-weight:700;"><?= htmlspecialchars($ord['customer']) ?></div>
        <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Order #<?= $ord['id'] ?> • <span style="color:#ff9900;"><?= ucfirst($ord['status']) ?></span></div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);"><?= $ord['created_at'] ?></div>
        <span class="order-priority">Prioritas <?= $i+1 ?></span>
      </div>
    </div>
    <div class="order-items">
      <?php foreach ($ord['items'] as $item): ?>
      <div class="order-item-row">
        <div class="qty-circle"><?= $item['qty'] ?></div>
        <span><?= htmlspecialchars($item['name']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="order-actions">
      <a href="?tab=cooking" class="btn btn-primary" style="display:block;text-align:center;text-decoration:none;">Mulai Memasak</a>
    </div>
  </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'cooking'): ?>
  <div style="padding:0 20px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-weight:700;font-size:0.95rem;">Sedang Dimasak</span>
    <span style="font-size:0.8rem;color:rgba(255,255,255,0.4);">2 pesanan dalam proses</span>
  </div>
  <?php foreach (array_slice($orders, 0, 2) as $ord): ?>
  <div class="order-card">
    <div class="order-card-header">
      <div>
        <div style="font-weight:700;"><?= htmlspecialchars($ord['customer']) ?></div>
        <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Order #<?= $ord['id'] ?></div>
      </div>
      <div class="cooking-indicator">
        <div class="pulse"></div> Sedang Dimasak
      </div>
    </div>
    <div class="order-items">
      <?php foreach ($ord['items'] as $item): ?>
      <div class="order-item-row">
        <div class="qty-circle"><?= $item['qty'] ?></div>
        <span><?= htmlspecialchars($item['name']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="order-actions">
      <a href="?tab=ready" class="btn btn-primary" style="display:block;text-align:center;text-decoration:none;background:#ff9900;">Tandai Siap</a>
    </div>
  </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'ready'): ?>
  <div style="padding:0 20px;margin-bottom:14px;font-weight:700;font-size:0.95rem;">Siap Diambil</div>
  <?php foreach (array_slice($orders, 0, 1) as $ord): ?>
  <div class="order-card">
    <div class="order-card-header">
      <div>
        <div style="font-weight:700;"><?= htmlspecialchars($ord['customer']) ?></div>
        <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);">Order #<?= $ord['id'] ?></div>
      </div>
      <span style="background:#00cc66;color:white;padding:4px 10px;border-radius:8px;font-size:0.8rem;font-weight:700;">Siap</span>
    </div>
    <div class="order-items">
      <?php foreach ($ord['items'] as $item): ?>
      <div class="order-item-row">
        <div class="qty-circle" style="background:#00cc66;"><?= $item['qty'] ?></div>
        <span><?= htmlspecialchars($item['name']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="order-actions">
      <a href="?tab=done" class="btn btn-primary" style="display:block;text-align:center;text-decoration:none;background:#00cc66;">Selesai & Ambil</a>
    </div>
  </div>
  <?php endforeach; ?>

<?php else: ?>
  <div style="text-align:center;padding:60px 20px;color:rgba(255,255,255,0.3);">
    <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:14px;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
    <p>Tidak ada pesanan selesai</p>
  </div>
<?php endif; ?>

</div>
</body></html>
