<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();
$films = $db->query("SELECT * FROM films WHERE is_active=1 ORDER BY score DESC")->fetchAll();
$popular = array_slice($films, 0, 3);
$nowShowing = $films;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>TursMovie - Home</title>
<?= getBaseStyles() ?>
<style>
.mobile-wrap {
    max-width: 480px; margin: 0 auto;
    min-height: 100vh; position: relative;
    padding-bottom: 80px;
}
.top-header {
    background: linear-gradient(160deg, #e61515 0%, #c00 60%, #880000 100%);
    padding: 20px 20px 30px;
}
.top-header-row {
    display: flex; align-items: flex-start;
    justify-content: space-between; margin-bottom: 18px;
}
.top-header h2 { font-size: 0.8rem; opacity: 0.8; font-weight: 500; }
.top-header h1 { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; }
.notif-btn {
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.2); border: none;
    cursor: pointer; position: relative; display: flex;
    align-items: center; justify-content: center;
}
.notif-dot {
    position: absolute; top: 8px; right: 8px;
    width: 8px; height: 8px; background: #fbbf24;
    border-radius: 50%; border: 2px solid transparent;
}
.search-bar {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,0.15);
    border-radius: 14px; padding: 12px 16px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}
.search-bar input {
    background: none; border: none; color: white;
    font-size: 0.9rem; flex: 1; width: auto;
    padding: 0; box-shadow: none;
}
.search-bar input::placeholder { color: rgba(255,255,255,0.6); }
.search-bar input:focus { box-shadow: none; border: none; }
.content-area { padding: 20px; }
.section-header {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 16px; font-size: 1rem; font-weight: 700;
}
.popular-scroll {
    display: flex; gap: 14px;
    overflow-x: auto; padding-bottom: 8px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
}
.popular-scroll::-webkit-scrollbar { display: none; }
.popular-card {
    flex: 0 0 280px; border-radius: 16px;
    overflow: hidden; position: relative;
    scroll-snap-align: start; cursor: pointer;
    height: 180px;
}
.popular-card img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}
.popular-card-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 50%);
    padding: 14px;
    display: flex; flex-direction: column;
    justify-content: flex-end;
}
.popular-card-overlay h3 { font-size: 1rem; font-weight: 700; }
.popular-card-overlay p { font-size: 0.75rem; color: rgba(255,255,255,0.7); }
.card-badges {
    position: absolute; top: 12px; left: 12px;
    display: flex; gap: 6px; align-items: center;
}
.age-badge {
    background: var(--red); color: white;
    font-size: 0.7rem; font-weight: 700;
    padding: 3px 8px; border-radius: 6px;
}
.score-badge {
    background: rgba(0,0,0,0.6); color: white;
    font-size: 0.75rem; font-weight: 600;
    padding: 3px 8px; border-radius: 6px;
    display: flex; align-items: center; gap: 4px;
    backdrop-filter: blur(4px);
}
.films-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.film-card {
    border-radius: 14px; overflow: hidden;
    background: var(--bg-card);
    border: 1px solid var(--border);
    cursor: pointer; transition: transform 0.2s;
}
.film-card:hover { transform: translateY(-3px); }
.film-card-img {
    position: relative; height: 160px; overflow: hidden;
}
.film-card-img img {
    width: 100%; height: 100%; object-fit: cover;
}
.film-score {
    position: absolute; top: 10px; right: 10px;
    background: rgba(0,0,0,0.7); color: white;
    font-size: 0.75rem; font-weight: 600;
    padding: 3px 8px; border-radius: 8px;
    display: flex; align-items: center; gap: 3px;
    backdrop-filter: blur(4px);
}
.film-card-info { padding: 12px; }
.film-card-info h4 { font-size: 0.85rem; font-weight: 700; margin-bottom: 3px; }
.film-card-info p { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 6px; }
.film-card-footer {
    display: flex; justify-content: space-between;
    align-items: center; font-size: 0.75rem;
}
.film-price { color: var(--red); font-weight: 700; }
.film-duration { color: var(--text-muted); display: flex; align-items: center; gap: 3px; }
.bottom-nav {
    position: fixed; bottom: 0; left: 50%;
    transform: translateX(-50%);
    width: 100%; max-width: 480px;
    background: rgba(15,17,23,0.95);
    backdrop-filter: blur(16px);
    border-top: 1px solid var(--border);
    display: flex; z-index: 100;
    padding: 8px 0 12px;
}
.nav-item {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; gap: 4px;
    padding: 8px; text-decoration: none;
    color: var(--text-muted); font-size: 0.72rem;
    font-weight: 600; transition: color 0.2s;
    cursor: pointer; border: none; background: none;
}
.nav-item.active { color: var(--red); }
.nav-item svg { width: 22px; height: 22px; }
</style>
</head>
<body>
<div class="mobile-wrap">
    <div class="top-header">
        <div class="top-header-row">
            <div style="color:white">
                <h2>Selamat Datang</h2>
                <h1>TursMovie</h1>
            </div>
            <button class="notif-btn">
                <svg width="22" height="22" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <div class="notif-dot"></div>
            </button>
        </div>
        <div class="search-bar">
            <svg width="18" height="18" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" placeholder="Cari film favorit..." id="searchInput" oninput="filterFilms(this.value)">
        </div>
    </div>

    <div class="content-area">
        <!-- Popular Section -->
        <div class="section-header">
            <svg width="18" height="18" fill="none" stroke="var(--red)" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                <polyline points="17 6 23 6 23 12"/>
            </svg>
            Sedang Populer
        </div>
        <div class="popular-scroll">
            <?php foreach ($popular as $film): ?>
            <div class="popular-card" onclick="location.href='film_detail.php?id=<?= $film['id'] ?>'">
                <img src="<?= htmlspecialchars($film['image']) ?>" alt="<?= htmlspecialchars($film['title']) ?>" loading="lazy">
                <div class="popular-card-overlay">
                    <h3><?= htmlspecialchars($film['title']) ?></h3>
                    <p><?= htmlspecialchars($film['genre']) ?></p>
                </div>
                <div class="card-badges">
                    <span class="age-badge"><?= $film['rating'] ?></span>
                    <span class="score-badge">⭐ <?= $film['score'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Now Showing -->
        <div class="section-header" style="margin-top:24px">
            Sedang Tayang
        </div>
        <div class="films-grid" id="filmsGrid">
            <?php foreach ($nowShowing as $film): ?>
            <div class="film-card" onclick="location.href='film_detail.php?id=<?= $film['id'] ?>'"
                data-title="<?= strtolower(htmlspecialchars($film['title'])) ?>">
                <div class="film-card-img">
                    <img src="<?= htmlspecialchars($film['image']) ?>" alt="<?= htmlspecialchars($film['title']) ?>" loading="lazy">
                    <span class="film-score">⭐ <?= $film['score'] ?></span>
                </div>
                <div class="film-card-info">
                    <h4><?= htmlspecialchars($film['title']) ?></h4>
                    <p><?= htmlspecialchars($film['genre']) ?></p>
                    <div class="film-card-footer">
                        <span class="film-price"><?= formatRupiah($film['price']) ?></span>
                        <span class="film-duration">
                            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= $film['duration'] ?>m
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<nav class="bottom-nav">
    <a class="nav-item active" href="home.php">
        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        Home
    </a>
    <a class="nav-item" href="films.php">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="2" y="2" width="20" height="20" rx="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="17" y1="7" x2="22" y2="7"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="2" y1="17" x2="7" y2="17"/>
        </svg>
        Films
    </a>
    <a class="nav-item" href="profile.php">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        Profile
    </a>
</nav>

<script>
function filterFilms(q) {
    document.querySelectorAll('.film-card').forEach(c => {
        const title = c.dataset.title || '';
        c.style.display = title.includes(q.toLowerCase()) ? '' : 'none';
    });
}
</script>
</body>
</html>
