<?php
function kasirSidebar($active = 'dashboard')
{
    $db = getDB();
    $pendingCount = $db->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();
    $items = [
        ['dashboard', 'Dashboard', 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'],
        ['orders', 'Pesanan', 'M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0'],
        ['bookings', 'Daftar Booking', 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
        ['menu', 'Kelola Menu', 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'],
        ['reports', 'Laporan', 'M18 20V10M12 20V4M6 20v-6'],
        ['settings', 'Pengaturan', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z'],
    ];
    echo '<div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <span style="background:var(--red);border-radius:8px;padding:5px 9px;font-weight:800;font-size:0.9rem;color:white;">TM</span>
                <div>
                    <div style="font-weight:700;font-size:0.9rem">TursMovie</div>
                    <div style="font-size:0.72rem;color:var(--text-muted)">Kasir Panel</div>
                </div>
            </div>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar">' . strtoupper(substr($_SESSION['name'], 0, 1)) . '</div>
            <div><div class="user-name">' . htmlspecialchars($_SESSION['name']) . '</div><div class="user-role">Kasir</div></div>
        </div>
        <nav class="sidebar-nav">';
    foreach ($items as [$id, $label, $d]) {
        $href = $id . '.php';
        $isActive = $active === $id;
        $badge = ($id === 'orders' && $pendingCount > 0) ? "<span class='nav-badge'>$pendingCount</span>" : '';
        echo "<a href='$href' class='nav-link " . ($isActive ? 'active' : '') . "'>
            <svg width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='$d'/></svg>
            $label $badge
        </a>";
    }
    echo '</nav><a href="../../logout.php" class="sidebar-logout">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Keluar
    </a></div>';
}
?>