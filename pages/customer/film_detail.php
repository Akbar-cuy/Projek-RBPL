<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$film = $db->prepare("SELECT * FROM films WHERE id=?");
$film->execute([$id]);
$film = $film->fetch();
if (!$film) { header('Location: home.php'); exit; }


$today = date('Y-m-d');
// Ubah ini sementara untuk testing
$showtimes = $db->prepare("SELECT * FROM showtimes WHERE film_id=? ORDER BY show_date, show_time");
$showtimes->execute([$id]);
$showtimes = $showtimes->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($film['title']) ?> - TursMovie</title>
<?= getBaseStyles() ?>
<style>
.mobile-wrap { max-width: 480px; margin: 0 auto; min-height: 100vh; padding-bottom: 100px; }
.hero { position: relative; height: 280px; }
.hero img { width: 100%; height: 100%; object-fit: cover; display: block; }
.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, var(--bg-dark) 100%);
}
.back-btn {
    position: absolute; top: 16px; left: 16px;
    width: 40px; height: 40px; background: rgba(0,0,0,0.5);
    border-radius: 12px; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(8px);
}
.hero-badges {
    position: absolute; top: 16px; right: 16px;
    display: flex; gap: 8px;
}
.age-badge { background: var(--red); color: white; font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 8px; }
.score-badge { background: rgba(0,0,0,0.6); color: white; font-size: 0.8rem; font-weight: 600; padding: 4px 10px; border-radius: 8px; backdrop-filter: blur(4px); }
.content { padding: 20px; }
.film-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; }
.film-meta { display: flex; gap: 16px; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px; }
.film-meta span { display: flex; align-items: center; gap: 5px; }
.synopsis-section { margin-bottom: 24px; }
.synopsis-section h3 { font-size: 1rem; font-weight: 700; margin-bottom: 10px; color: var(--red); }
.synopsis-section p { font-size: 0.875rem; line-height: 1.7; color: var(--text-muted); }
.price-box {
    background: linear-gradient(135deg, rgba(230,21,21,0.15), rgba(75,0,130,0.15));
    border: 1px solid rgba(230,21,21,0.3);
    border-radius: 16px; padding: 16px 20px; margin-bottom: 24px;
}
.price-box p { font-size: 0.8rem; color: var(--text-muted); }
.price-box h2 { font-size: 1.8rem; font-weight: 800; color: white; }
.schedule-section h3 { font-size: 1rem; font-weight: 700; margin-bottom: 14px; }
.date-label {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.85rem; font-weight: 600; color: var(--text-muted);
    margin-bottom: 12px;
}
.showtime-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.showtime-btn {
    background: var(--bg-card); border: 1.5px solid var(--border);
    border-radius: 12px; padding: 12px 8px; text-align: center;
    cursor: pointer; transition: all 0.2s; text-decoration: none;
    display: block;
}
.showtime-btn:hover, .showtime-btn.selected {
    border-color: var(--red); background: rgba(230,21,21,0.1);
}
.showtime-btn .time { font-size: 0.9rem; font-weight: 700; margin-bottom: 4px; }
.showtime-btn .theater { font-size: 0.7rem; color: var(--text-muted); display: flex; align-items: center; gap: 3px; justify-content: center; }
.showtime-btn .seats { font-size: 0.7rem; color: var(--green); font-weight: 600; margin-top: 4px; }
.book-btn {
    position: fixed; bottom: 20px; left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 40px); max-width: 440px;
    background: linear-gradient(135deg, var(--red), var(--red-dark));
    color: white; font-size: 1rem; font-weight: 700;
    border: none; border-radius: 16px; padding: 16px;
    cursor: pointer; transition: all 0.2s; z-index: 100;
    box-shadow: 0 8px 25px rgba(230,21,21,0.4);
}
.book-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.book-btn:not(:disabled):hover { transform: translateX(-50%) translateY(-2px); }
</style>
</head>
<body>
<div class="mobile-wrap">
    <div class="hero">
        <img src="<?= htmlspecialchars($film['image']) ?>" alt="<?= htmlspecialchars($film['title']) ?>">
        <div class="hero-overlay"></div>
        <button class="back-btn" onclick="history.back()">
            <svg width="20" height="20" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </button>
        <div class="hero-badges">
            <span class="age-badge"><?= $film['rating'] ?></span>
            <span class="score-badge">⭐ <?= $film['score'] ?></span>
        </div>
    </div>

    <div class="content">
        <h1 class="film-title"><?= htmlspecialchars($film['title']) ?></h1>
        <div class="film-meta">
            <span>
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= $film['duration'] ?> menit
            </span>
            <span><?= htmlspecialchars($film['genre']) ?></span>
        </div>

        <div class="synopsis-section">
            <h3>Sinopsis</h3>
            <p><?= htmlspecialchars($film['synopsis']) ?></p>
        </div>

        <div class="price-box">
            <p>Harga Tiket</p>
            <h2><?= formatRupiah($film['price']) ?></h2>
        </div>

        <div class="schedule-section">
            <h3>Pilih Jadwal</h3>
            <?php
            $byDate = [];
            foreach ($showtimes as $s) { $byDate[$s['show_date']][] = $s; }
            foreach ($byDate as $date => $times):
                $label = $date === $today ? 'Hari Ini' : date('d M Y', strtotime($date));
            ?>
            <div class="date-label">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?= $label ?>
            </div>
            <div class="showtime-grid">
                <?php foreach ($times as $t): ?>
                <a href="seat_selection.php?showtime_id=<?= $t['id'] ?>&film_id=<?= $id ?>"
                   class="showtime-btn">
                    <div class="time"><?= substr($t['show_time'],0,5) ?></div>
                    <div class="theater">
                        <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= htmlspecialchars($t['theater']) ?>
                    </div>
                    <div class="seats"><?= $t['available_seats'] ?> kursi</div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>
</html>
