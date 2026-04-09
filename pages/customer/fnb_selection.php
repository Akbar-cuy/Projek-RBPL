<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();
$showtime_id = intval($_GET['showtime_id'] ?? 0);
$film_id = intval($_GET['film_id'] ?? 0);
$seats = htmlspecialchars($_GET['seats'] ?? '');

$showtime = $db->prepare("SELECT s.*, f.title, f.price FROM showtimes s JOIN films f ON s.film_id=f.id WHERE s.id=?");
$showtime->execute([$showtime_id]);
$showtime = $showtime->fetch();
if (!$showtime) { header('Location: home.php'); exit; }

$menus = $db->query("SELECT * FROM fnb_menu WHERE is_available=1 ORDER BY category, id")->fetchAll();
$seatArr = explode(',', $seats);
$ticketTotal = $showtime['price'] * count($seatArr);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Makanan & Minuman - TursMovie</title>
<?= getBaseStyles() ?>
<style>
.mobile-wrap { max-width: 480px; margin: 0 auto; min-height: 100vh; padding-bottom: 160px; }
.top-bar {
    background: linear-gradient(135deg, var(--red), var(--red-dark));
    padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
    position: sticky; top: 0; z-index: 50;
}
.back-btn {
    width: 38px; height: 38px; background: rgba(255,255,255,0.2);
    border-radius: 10px; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.top-bar-info { flex: 1; }
.top-bar-info h1 { font-size: 1rem; font-weight: 700; color: white; }
.top-bar-info p { font-size: 0.78rem; color: rgba(255,255,255,0.75); }
.cart-badge {
    background: white; color: var(--red);
    border-radius: 50%; width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 700;
    position: absolute; top: -5px; right: -5px;
}
.cart-btn { position: relative; }
.filter-row {
    display: flex; gap: 8px; padding: 14px 20px;
    overflow-x: auto; border-bottom: 1px solid var(--border);
}
.filter-row::-webkit-scrollbar { display: none; }
.filter-btn {
    flex-shrink: 0; padding: 7px 16px; border-radius: 20px;
    font-size: 0.8rem; font-weight: 600; cursor: pointer;
    border: 1.5px solid var(--border); background: transparent; color: var(--text);
    transition: all 0.2s;
}
.filter-btn.active { background: var(--red); border-color: var(--red); color: white; }
.menu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 16px 20px; }
.menu-card { background: var(--bg-card); border-radius: 14px; overflow: hidden; border: 1px solid var(--border); }
.menu-img { height: 120px; overflow: hidden; }
.menu-img img { width: 100%; height: 100%; object-fit: cover; }
.menu-info { padding: 10px 12px; }
.menu-info h4 { font-size: 0.85rem; font-weight: 700; margin-bottom: 4px; }
.menu-price { color: var(--red); font-weight: 700; font-size: 0.85rem; margin-bottom: 8px; }
.qty-control {
    display: flex; align-items: center; gap: 8px;
}
.qty-btn {
    width: 28px; height: 28px; border-radius: 8px;
    border: none; cursor: pointer; display: flex;
    align-items: center; justify-content: center;
    font-size: 1rem; font-weight: 700; transition: all 0.15s;
}
.qty-btn.minus { background: var(--bg-card2); color: var(--text-muted); }
.qty-btn.plus { background: var(--red); color: white; }
.qty-btn.add-btn { width: 100%; border-radius: 8px; padding: 8px; font-size: 0.8rem; font-weight: 600; }
.qty-num { font-size: 0.9rem; font-weight: 700; min-width: 20px; text-align: center; }
.bottom-area {
    position: fixed; bottom: 0; left: 50%;
    transform: translateX(-50%); width: 100%; max-width: 480px;
    background: var(--bg-card); border-top: 1px solid var(--border);
    padding: 14px 20px; z-index: 100;
}
.order-summary { background: var(--bg-card2); border-radius: 12px; padding: 12px; margin-bottom: 12px; }
.summary-line { display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px; }
.summary-line.total { font-weight: 700; color: var(--red); font-size: 0.9rem; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border); }
.btn-row { display: flex; gap: 10px; }
.btn-skip { flex: 1; background: var(--bg-card2); color: var(--text); border: 1.5px solid var(--border); border-radius: 12px; padding: 13px; font-weight: 600; font-size: 0.875rem; cursor: pointer; }
.btn-continue { flex: 2; background: var(--red); color: white; border: none; border-radius: 12px; padding: 13px; font-weight: 700; font-size: 0.875rem; cursor: pointer; }
</style>
</head>
<body>
<div class="mobile-wrap">
    <div class="top-bar">
        <button class="back-btn" onclick="history.back()">
            <svg width="18" height="18" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </button>
        <div class="top-bar-info">
            <h1>Makanan & Minuman</h1>
            <p>Opsional - Bisa dilewati</p>
        </div>
        <div class="cart-btn">
            <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <div class="cart-badge" id="cartCount">0</div>
        </div>
    </div>

    <div class="filter-row">
        <button class="filter-btn active" onclick="filterMenu('all', this)">Semua</button>
        <button class="filter-btn" onclick="filterMenu('popcorn', this)">Popcorn</button>
        <button class="filter-btn" onclick="filterMenu('drinks', this)">Minuman</button>
        <button class="filter-btn" onclick="filterMenu('snacks', this)">Snacks</button>
    </div>

    <div class="menu-grid" id="menuGrid">
        <?php foreach ($menus as $m): ?>
        <div class="menu-card" data-category="<?= $m['category'] ?>">
            <div class="menu-img">
                <img src="<?= htmlspecialchars($m['image']) ?>" alt="<?= htmlspecialchars($m['name']) ?>" loading="lazy">
            </div>
            <div class="menu-info">
                <h4><?= htmlspecialchars($m['name']) ?></h4>
                <div class="menu-price"><?= formatRupiah($m['price']) ?></div>
                <div class="qty-control" id="ctrl-<?= $m['id'] ?>">
                    <button class="qty-btn add-btn plus" onclick="addItem(<?= $m['id'] ?>, '<?= htmlspecialchars($m['name']) ?>', <?= $m['price'] ?>)">
                        + Tambah
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="bottom-area">
    <div class="order-summary" id="orderSummary" style="display:none">
        <div id="summaryItems"></div>
        <div class="summary-line total">
            <span>Total</span>
            <span id="fnbTotal">Rp 0</span>
        </div>
    </div>
    <div class="btn-row">
        <button class="btn-skip" onclick="skipFnb()">Lewati</button>
        <button class="btn-continue" onclick="continueFnb()">Lanjutkan</button>
    </div>
</div>

<script>
const SHOWTIME_ID = <?= $showtime_id ?>;
const FILM_ID = <?= $film_id ?>;
const SEATS = '<?= $seats ?>';
const TICKET_TOTAL = <?= $ticketTotal ?>;
let cart = {};

function addItem(id, name, price) {
    cart[id] = { name, price, qty: (cart[id]?.qty || 0) + 1 };
    renderQty(id);
    updateSummary();
}

function changeQty(id, name, price, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) {
        delete cart[id];
        document.getElementById('ctrl-'+id).innerHTML = `<button class="qty-btn add-btn plus" onclick="addItem(${id}, '${name}', ${price})">+ Tambah</button>`;
    } else {
        renderQty(id);
    }
    updateSummary();
}

function renderQty(id) {
    const item = cart[id];
    if (!item) return;
    document.getElementById('ctrl-'+id).innerHTML = `
        <button class="qty-btn minus" onclick="changeQty(${id}, '${item.name}', ${item.price}, -1)">−</button>
        <span class="qty-num">${item.qty}</span>
        <button class="qty-btn plus" onclick="changeQty(${id}, '${item.name}', ${item.price}, 1)">+</button>
    `;
}

function updateSummary() {
    let total = 0, count = 0;
    let html = '';
    for (const [id, item] of Object.entries(cart)) {
        const sub = item.price * item.qty;
        total += sub;
        count += item.qty;
        html += `<div class="summary-line"><span>${item.qty}x ${item.name}</span><span>Rp ${sub.toLocaleString('id-ID')}</span></div>`;
    }
    document.getElementById('cartCount').textContent = count;
    const summary = document.getElementById('orderSummary');
    summary.style.display = count > 0 ? '' : 'none';
    document.getElementById('summaryItems').innerHTML = html;
    document.getElementById('fnbTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function filterMenu(cat, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.menu-card').forEach(c => {
        c.style.display = (cat === 'all' || c.dataset.category === cat) ? '' : 'none';
    });
}

function skipFnb() {
    goToSummary({});
}

function continueFnb() {
    goToSummary(cart);
}

function goToSummary(cartData) {
    const params = new URLSearchParams({
        showtime_id: SHOWTIME_ID,
        film_id: FILM_ID,
        seats: SEATS,
        cart: JSON.stringify(cartData)
    });
    location.href = 'order_summary.php?' + params;
}
</script>
</body>
</html>
