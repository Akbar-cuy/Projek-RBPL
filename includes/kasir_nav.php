<?php
// shared sidebar for kasir
function kasirNav($active)
{
    $pc = getDB()->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();
    echo '<div class="sidebar">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px">
        <span style="background:var(--red);border-radius:8px;padding:5px 9px;font-weight:800;color:white;font-size:0.9rem">TM</span>
        <div><div style="font-weight:700;font-size:0.9rem">TursMovie</div><div style="font-size:0.72rem;color:var(--text-muted)">Kasir Panel</div></div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div class="user-avatar">' . strtoupper(substr($_SESSION['name'], 0, 1)) . '</div>
        <div><div class="user-name">' . htmlspecialchars($_SESSION['name']) . '</div><div class="user-role">Kasir</div></div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link ' . ($active === 'dashboard' ? 'active' : '') . '">📊 Dashboard</a>
        <a href="orders.php" class="nav-link ' . ($active === 'orders' ? 'active' : '') . '">🛒 Pesanan ' . ($pc > 0 ? "<span class='nav-badge'>$pc</span>" : '') . '</a>
        <a href="bookings.php" class="nav-link ' . ($active === 'bookings' ? 'active' : '') . '">📋 Daftar Booking</a>
        <a href="menu.php" class="nav-link ' . ($active === 'menu' ? 'active' : '') . '">🍿 Kelola Menu</a>
        <a href="reports.php" class="nav-link ' . ($active === 'reports' ? 'active' : '') . '">📈 Laporan</a>
        <a href="settings.php" class="nav-link ' . ($active === 'settings' ? 'active' : '') . '">⚙️ Pengaturan</a>
    </nav>
    <a href="../../logout.php" class="sidebar-logout">🚪 Keluar</a>
</div>';
}
function kasirPage($title, $active, $content)
{
    require_once '../../config.php';
    require_once '../../includes/layout.php';
    requireRole('kasir');
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $title . ' - TursMovie Kasir</title>' . getBaseStyles() . '<style>';
    include '../../includes/admin_styles.php';
    echo '</style></head><body><div class="admin-layout">';
    kasirNav($active);
    echo '<div class="overlay" id="ov" onclick="this.classList.remove(\'show\');document.querySelector(\'.sidebar\').classList.remove(\'open\')"></div>
    <div class="main-content">
    <div class="topbar">
        <button class="hamburger" onclick="document.querySelector(\'.sidebar\').classList.toggle(\'open\');document.getElementById(\'ov\').classList.toggle(\'show\')">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button><span class="topbar-title">TursMovie Kasir</span></div>
    <div class="page-content">' . $content . '</div></div></div></body></html>';
}
?>