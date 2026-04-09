<?php
function mobile_head($title, $extra_css = '')
{
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>$title - TursMovie</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Segoe UI', sans-serif; background: #0f1929; color: #fff; max-width: 480px; margin: 0 auto; min-height: 100vh; position: relative; }
  .top-bar { background: linear-gradient(135deg, #cc0000, #990000); padding: 20px 20px 24px; }
  .top-bar h2 { font-size: 0.8rem; color: rgba(255,255,255,0.8); font-weight: 400; }
  .top-bar h1 { font-size: 1.6rem; font-weight: 700; }
  .top-bar-row { display: flex; justify-content: space-between; align-items: flex-start; }
  .notif-btn { width: 44px; height: 44px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; position: relative; color: white; }
  .notif-dot { position: absolute; top: 8px; right: 8px; width: 8px; height: 8px; background: #ff9500; border-radius: 50%; }
  .search-bar { margin: 14px 20px 0; position: relative; }
  .search-bar input { width: 100%; padding: 12px 16px 12px 44px; border-radius: 50px; border: none; background: rgba(255,255,255,0.15); color: white; font-size: 0.9rem; outline: none; }
  .search-bar input::placeholder { color: rgba(255,255,255,0.6); }
  .search-bar svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); opacity: 0.7; }
  .content { padding: 20px; padding-bottom: 80px; }
  .section-title { font-size: 1rem; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
  .bottom-nav { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 480px; background: #1a2436; border-top: 1px solid rgba(255,255,255,0.1); display: flex; }
  .nav-item { flex: 1; padding: 12px 8px 10px; display: flex; flex-direction: column; align-items: center; gap: 4px; text-decoration: none; color: rgba(255,255,255,0.5); font-size: 0.7rem; border: none; background: none; cursor: pointer; }
  .nav-item.active { color: #cc0000; }
  .nav-item svg { width: 22px; height: 22px; }
  .movie-card { background: #1a2436; border-radius: 16px; overflow: hidden; margin-bottom: 12px; }
  .movie-card img { width: 100%; height: 200px; object-fit: cover; }
  .movie-card .info { padding: 14px; }
  .movie-card .title { font-weight: 700; font-size: 1rem; }
  .movie-card .genre { color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-top: 2px; }
  .movie-card .meta { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
  .price { color: #cc0000; font-weight: 700; font-size: 0.95rem; }
  .badge { padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
  .badge-age { background: #cc0000; color: white; }
  .badge-rating { background: rgba(255,255,255,0.15); color: white; display: flex; align-items: center; gap: 4px; }
  .star { color: #ffd700; }
  .btn { padding: 14px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; font-size: 1rem; width: 100%; transition: all 0.2s; }
  .btn-primary { background: #cc0000; color: white; }
  .btn-primary:hover { background: #aa0000; }
  .btn-secondary { background: rgba(255,255,255,0.1); color: white; }
  .card { background: #1a2436; border-radius: 16px; padding: 18px; margin-bottom: 14px; }
  .form-group { margin-bottom: 16px; }
  .form-group label { display: block; font-size: 0.85rem; color: rgba(255,255,255,0.7); margin-bottom: 6px; }
  .form-group select, .form-group input { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.08); color: white; font-size: 0.9rem; outline: none; }
  .schedule-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
  .schedule-item { background: rgba(255,255,255,0.08); border-radius: 12px; padding: 12px 8px; text-align: center; cursor: pointer; border: 1.5px solid transparent; }
  .schedule-item:hover, .schedule-item.selected { border-color: #cc0000; background: rgba(204,0,0,0.15); }
  .schedule-item .time { font-weight: 700; font-size: 1rem; }
  .schedule-item .theater { font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-top: 4px; }
  .schedule-item .seats { font-size: 0.75rem; color: #00cc66; margin-top: 2px; }
  .back-btn { width: 38px; height: 38px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; border: none; cursor: pointer; }
  .page-header { display: flex; align-items: center; gap: 14px; padding: 18px 20px; background: linear-gradient(135deg, #cc0000, #990000); }
  .page-header h1 { font-size: 1.2rem; font-weight: 700; }
  .page-header p { font-size: 0.8rem; color: rgba(255,255,255,0.7); margin-top: 1px; }
  .tag { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
  .tag-success { background: rgba(0,204,102,0.2); color: #00cc66; }
  .tag-danger { background: rgba(255,60,60,0.2); color: #ff5050; }
  .tag-warning { background: rgba(255,165,0,0.2); color: #ffaa00; }
  $extra_css
</style>
</head>
<body>
HTML;
}

function bottom_nav($active = 'home')
{
    $root = '';
    $depth = substr_count($_SERVER['PHP_SELF'], '/', 1) - 1;
    $root = str_repeat('../', $depth);

    $items = [
        'home' => ['href' => $root . 'pages/home.php', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>', 'label' => 'Home'],
        'films' => ['href' => $root . 'pages/films.php', 'icon' => '<rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/>', 'label' => 'Films'],
        'profile' => ['href' => $root . 'pages/profile.php', 'icon' => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>', 'label' => 'Profile'],
    ];

    echo '<nav class="bottom-nav">';
    foreach ($items as $key => $item) {
        $cls = $active === $key ? 'nav-item active' : 'nav-item';
        echo "<a href='{$item['href']}' class='$cls'><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>{$item['icon']}</svg>{$item['label']}</a>";
    }
    echo '</nav>';
}
