<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
require_once '../../includes/kasir_nav.php';
requireRole('kasir');
$db = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['save_menu'])) {
        $id=$_POST['id']??''; $name=$_POST['name']; $cat=$_POST['category']; $price=intval($_POST['price']); $avail=isset($_POST['is_available'])?1:0;
        if ($id) { $db->prepare("UPDATE fnb_menu SET name=?,category=?,price=?,is_available=? WHERE id=?")->execute([$name,$cat,$price,$avail,$id]); }
        else { $db->prepare("INSERT INTO fnb_menu (name,category,price,is_available) VALUES (?,?,?,?)")->execute([$name,$cat,$price,$avail]); }
    }
    if (isset($_POST['delete_menu'])) { $db->prepare("DELETE FROM fnb_menu WHERE id=?")->execute([$_POST['did']]); }
    header("Location: menu.php"); exit;
}

$cat = $_GET['cat'] ?? 'all';
$menus = $db->query("SELECT * FROM fnb_menu ORDER BY category,name")->fetchAll();
$filtered = $cat==='all' ? $menus : array_filter($menus, fn($m) => $m['category']===$cat);
$editMenu = null;
if (isset($_GET['edit'])) { foreach($menus as $m) if ($m['id']==$_GET['edit']) { $editMenu=$m; break; } }
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola Menu - TursMovie Kasir</title><?= getBaseStyles() ?>
<style><?php include '../../includes/admin_styles.php'; ?>
.menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
.menu-card{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.menu-card img{width:100%;height:140px;object-fit:cover}
.menu-card-body{padding:14px}
.menu-card-body h4{font-weight:700;margin-bottom:4px}
.menu-card-body .price{color:var(--red);font-weight:700;margin-bottom:8px}
.menu-btns{display:flex;gap:8px}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:500;align-items:center;justify-content:center;padding:20px}
.modal.show{display:flex}
.modal-box{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;width:100%;max-width:400px}
.modal-box h3{font-size:1rem;font-weight:700;margin-bottom:16px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:0.82rem;font-weight:600;margin-bottom:6px;color:var(--text-muted)}
</style></head><body>
<div class="admin-layout"><?php kasirNav('menu'); ?>
<div class="overlay" id="ov" onclick="this.classList.remove('show');document.querySelector('.sidebar').classList.remove('open')"></div>
<div class="main-content">
<div class="topbar"><button class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('open');document.getElementById('ov').classList.toggle('show')"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button><span class="topbar-title">TursMovie Kasir</span>
<button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('show')">+ Tambah Menu</button></div>
<div class="page-content">
<h1 style="font-size:1.3rem;font-weight:800;margin-bottom:4px">Kelola Menu F&B</h1>
<p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:20px">Kelola menu makanan dan minuman</p>
<div class="filter-tabs">
    <a href="menu.php" class="tab-btn <?= $cat==='all'?'active':'' ?>">Semua</a>
    <a href="menu.php?cat=popcorn" class="tab-btn <?= $cat==='popcorn'?'active':'' ?>">Popcorn</a>
    <a href="menu.php?cat=drinks" class="tab-btn <?= $cat==='drinks'?'active':'' ?>">Minuman</a>
    <a href="menu.php?cat=snacks" class="tab-btn <?= $cat==='snacks'?'active':'' ?>">Snacks</a>
</div>
<div class="menu-grid">
<?php foreach ($filtered as $m): ?>
<div class="menu-card">
    <?php if($m['image']): ?><img src="<?= htmlspecialchars($m['image']) ?>" alt="" loading="lazy"><?php else: ?><div style="height:140px;background:var(--bg-card2);display:flex;align-items:center;justify-content:center;color:var(--text-muted)">No Image</div><?php endif; ?>
    <div class="menu-card-body">
        <h4><?= htmlspecialchars($m['name']) ?></h4>
        <div class="price"><?= formatRupiah($m['price']) ?></div>
        <div style="display:flex;gap:6px;margin-bottom:10px">
            <span class="badge badge-<?= $m['is_available']?'green':'red' ?>"><?= $m['is_available']?'Tersedia':'Habis' ?></span>
            <span class="badge badge-gray"><?= $m['category'] ?></span>
        </div>
        <div class="menu-btns">
            <a href="menu.php?edit=<?= $m['id'] ?>" class="btn btn-primary btn-sm" style="flex:1">✏️ Edit</a>
            <form method="POST" style="display:inline"><input type="hidden" name="did" value="<?= $m['id'] ?>"><button name="delete_menu" class="btn btn-danger btn-sm" onclick="return confirm('Hapus menu ini?')">🗑</button></form>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
</div></div></div>

<!-- Add Modal -->
<div class="modal" id="addModal">
<div class="modal-box">
    <h3>Tambah Menu Baru</h3>
    <form method="POST">
        <div class="form-group"><label>Nama</label><input type="text" name="name" required placeholder="Nama menu"></div>
        <div class="form-group"><label>Kategori</label><select name="category"><option value="popcorn">Popcorn</option><option value="drinks">Minuman</option><option value="snacks">Snacks</option></select></div>
        <div class="form-group"><label>Harga (Rp)</label><input type="number" name="price" required placeholder="25000"></div>
        <div class="form-group"><label><input type="checkbox" name="is_available" checked> Tersedia</label></div>
        <div style="display:flex;gap:8px">
            <button type="submit" name="save_menu" class="btn btn-primary" style="flex:1">Simpan</button>
            <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('show')">Batal</button>
        </div>
    </form>
</div></div>

<!-- Edit Modal -->
<?php if ($editMenu): ?>
<div class="modal show" id="editModal">
<div class="modal-box">
    <h3>Edit Menu</h3>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $editMenu['id'] ?>">
        <div class="form-group"><label>Nama</label><input type="text" name="name" value="<?= htmlspecialchars($editMenu['name']) ?>" required></div>
        <div class="form-group"><label>Kategori</label><select name="category"><option value="popcorn" <?= $editMenu['category']==='popcorn'?'selected':'' ?>>Popcorn</option><option value="drinks" <?= $editMenu['category']==='drinks'?'selected':'' ?>>Minuman</option><option value="snacks" <?= $editMenu['category']==='snacks'?'selected':'' ?>>Snacks</option></select></div>
        <div class="form-group"><label>Harga (Rp)</label><input type="number" name="price" value="<?= $editMenu['price'] ?>" required></div>
        <div class="form-group"><label><input type="checkbox" name="is_available" <?= $editMenu['is_available']?'checked':'' ?>> Tersedia</label></div>
        <div style="display:flex;gap:8px">
            <button type="submit" name="save_menu" class="btn btn-primary" style="flex:1">Update</button>
            <a href="menu.php" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div></div>
<?php endif; ?>
</body></html>
