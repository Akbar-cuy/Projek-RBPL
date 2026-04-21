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
        <a href="dashboard.php" class="nav-link ' . ($active === 'dashboard' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Dashboard</a>
        <a href="orders.php" class="nav-link ' . ($active === 'orders' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg> Pesanan ' . ($pc > 0 ? "<span class='nav-badge'>$pc</span>" : '') . '</a>
        <a href="new_booking.php" class="nav-link ' . ($active === 'new_booking' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Booking Baru</a>
        <a href="bookings.php" class="nav-link ' . ($active === 'bookings' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> Daftar Booking</a>
        <a href="films.php" class="nav-link ' . ($active === 'films' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/></svg> Kelola Film</a>
        <a href="schedulesfilm.php" class="nav-link ' . ($active === 'schedulesfilm' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>Jadwal Film</a>
        <a href="menu.php" class="nav-link ' . ($active === 'menu' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg> Kelola Menu</a>
        <a href="reports.php" class="nav-link ' . ($active === 'reports' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Laporan</a>
        <a href="settings.php" class="nav-link ' . ($active === 'settings' ? 'active' : '') . '"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06-.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg> Pengaturan</a>

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
