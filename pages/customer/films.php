<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();
$genre = $_GET['genre'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM films WHERE is_active=1";
$params = [];
if ($search) { $sql .= " AND title LIKE ?"; $params[] = "%$search%"; }
if ($genre !== 'all') { $sql .= " AND genre LIKE ?"; $params[] = "%$genre%"; }
$sql .= " ORDER BY score DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$films = $stmt->fetchAll();
$genres = ['Action','Horror','Romance','Thriller','Sci-Fi','Drama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Film - TursMovie</title>
<?= getBaseStyles() ?>
<style>
.mobile-wrap { max-width:480px; margin:0 auto; min-height:100vh; padding-bottom:80px; }
.top-header { background:var(--bg-card); padding:20px 20px 16px; border-bottom:1px solid var(--border); position:sticky;top:0;z-index:50; }
.top-header h1 { font-size:1.4rem; font-weight:800; margin-bottom:14px; }
.search-wrap { position:relative; }
.search-wrap input { padding-left:42px; }
.search-wrap svg { position:absolute; left:14px; top:50%; transform:translateY(-50%); pointer-events:none; }
.genres-row { display:flex; gap:8px; overflow-x:auto; padding:12px 20px; border-bottom:1px solid var(--border); }
.genres-row::-webkit-scrollbar { display:none; }
.genre-btn { flex-shrink:0; padding:7px 16px; border-radius:20px; font-size:0.8rem; font-weight:600; cursor:pointer; border:1.5px solid var(--border); background:transparent; color:var(--text); transition:all 0.2s; }
.genre-btn.active { background:var(--red); border-color:var(--red); color:white; }
.content { padding:16px 20px; }
.result-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
.result-count { font-size:0.85rem; color:var(--text-muted); }
.films-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.film-card { border-radius:14px; overflow:hidden; background:var(--bg-card); border:1px solid var(--border); cursor:pointer; transition:transform 0.2s; }
.film-card:hover { transform:translateY(-3px); }
.film-img { position:relative; height:160px; overflow:hidden; }
.film-img img { width:100%; height:100%; object-fit:cover; }
.film-score { position:absolute; top:10px; right:10px; background:rgba(0,0,0,0.7); color:white; font-size:0.75rem; font-weight:600; padding:3px 8px; border-radius:8px; display:flex; align-items:center; gap:3px; backdrop-filter:blur(4px); }
.film-info { padding:12px; }
.film-info h4 { font-size:0.85rem; font-weight:700; margin-bottom:3px; }
.film-info p { font-size:0.72rem; color:var(--text-muted); margin-bottom:8px; }
.film-footer { display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; }
.film-price { color:var(--red); font-weight:700; }
.film-dur { color:var(--text-muted); display:flex; align-items:center; gap:3px; }
.bottom-nav { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:rgba(15,17,23,0.95); backdrop-filter:blur(16px); border-top:1px solid var(--border); display:flex; z-index:100; padding:8px 0 12px; }
.nav-item { flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; padding:8px; text-decoration:none; color:var(--text-muted); font-size:0.72rem; font-weight:600; transition:color 0.2s; }
.nav-item.active { color:var(--red); }
.nav-item svg { width:22px; height:22px; }
</style>
</head>
<body>
<div class="mobile-wrap">
    <div class="top-header">
        <h1>Daftar Film</h1>
        <form method="GET" class="search-wrap">
            <svg width="16" height="16" fill="none" stroke="var(--text-muted)" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" name="q" placeholder="Cari film..." value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>
    <div class="genres-row">
        <a href="films.php" class="genre-btn <?= $genre==='all'?'active':'' ?>">Semua</a>
        <?php foreach ($genres as $g): ?>
        <a href="films.php?genre=<?= urlencode($g) ?><?= $search?"&q=".urlencode($search):'' ?>" class="genre-btn <?= $genre===$g?'active':'' ?>"><?= $g ?></a>
        <?php endforeach; ?>
    </div>
    <div class="content">
        <div class="result-header">
            <span class="result-count"><?= count($films) ?> film ditemukan</span>
        </div>
        <div class="films-grid">
            <?php foreach ($films as $film): ?>
            <div class="film-card" onclick="location.href='film_detail.php?id=<?= $film['id'] ?>'">
                <div class="film-img">
                    <img src="<?= htmlspecialchars($film['image']) ?>" alt="" loading="lazy">
                    <span class="film-score">⭐ <?= $film['score'] ?></span>
                </div>
                <div class="film-info">
                    <h4><?= htmlspecialchars($film['title']) ?></h4>
                    <p><?= htmlspecialchars($film['genre']) ?></p>
                    <div class="film-footer">
                        <span class="film-price">Rp <?= number_format($film['price']/1000,0)?>k</span>
                        <span class="film-dur"><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?= $film['duration'] ?>m</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<nav class="bottom-nav">
    <a class="nav-item" href="home.php"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>Home</a>
    <a class="nav-item active" href="films.php"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="17" y1="7" x2="22" y2="7"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="2" y1="17" x2="7" y2="17"/></svg>Films</a>
    <a class="nav-item" href="profile.php"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile</a>
</nav>
</body>
</html>