<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
require_once '../../includes/kasir_nav.php';
requireRole('kasir');
$db = getDB();

$totalRevenue = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
$totalTickets = $db->query("SELECT COUNT(*) FROM order_seats")->fetchColumn();
$totalFnb = $db->query("SELECT COALESCE(SUM(quantity),0) FROM order_fnb")->fetchColumn();
$topFilms = $db->query("SELECT f.title, COUNT(os.id) as tickets, COALESCE(SUM(o.total_amount),0) as revenue FROM orders o JOIN order_seats os ON os.order_id=o.id JOIN showtimes s ON o.showtime_id=s.id JOIN films f ON s.film_id=f.id GROUP BY f.id ORDER BY tickets DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Laporan - TursMovie Kasir</title><?= getBaseStyles() ?>
    <style>
        <?php include '../../includes/admin_styles.php'; ?>.report-bar {
            height: 10px;
            border-radius: 5px;
            background: rgba(230, 21, 21, 0.15);
            position: relative;
            overflow: hidden
        }

        .report-bar-fill {
            height: 100%;
            background: var(--red);
            border-radius: 5px;
            transition: width 0.5s
        }
    </style>
</head>

<body>
    <?php kasirNav('reports'); ?>

    <div class="overlay" id="ov" onclick="this.classList.remove('show');document.querySelector('.sidebar').classList.remove('open')"></div>
    <div class="main-content">
        <div class="topbar"><button class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('open');document.getElementById('ov').classList.toggle('show')"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg></button><span class="topbar-title">TursMovie Kasir</span>
            <button class="btn btn-primary btn-sm">📥 Export</button>
        </div>
        <div class="page-content">
            <h1 style="font-size:1.3rem;font-weight:800;margin-bottom:4px">Laporan Penjualan</h1>
            <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:20px">Analisis performa penjualan</p>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(34,197,94,0.15)"><svg width="22" height="22" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                        </svg></div>
                    <div class="stat-label">Total Penjualan</div>
                    <div class="stat-value" style="font-size:1.2rem"><?= formatRupiah($totalRevenue) ?></div>
                    <div class="stat-trend trend-up">↑ 12.5% dari periode sebelumnya</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(168,85,247,0.15)"><svg width="22" height="22" fill="none" stroke="#a855f7" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="2" y="7" width="20" height="14" rx="2" />
                        </svg></div>
                    <div class="stat-label">Tiket Terjual</div>
                    <div class="stat-value"><?= $totalTickets ?></div>
                    <div class="stat-trend trend-up">↑ 8.3%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(245,158,11,0.15)"><svg width="22" height="22" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        </svg></div>
                    <div class="stat-label">F&B Terjual</div>
                    <div class="stat-value"><?= $totalFnb ?></div>
                    <div class="stat-trend trend-down">↓ 2.1%</div>
                </div>
            </div>

            <div class="card" style="margin-bottom:20px">
                <div class="card-hdr">
                    <h3>Film Terlaris</h3>
                </div>
                <?php $maxT = max(array_column($topFilms, 'tickets') ?: [1]); ?>
                <?php foreach ($topFilms as $i => $tf): ?>
                    <div style="padding:14px 16px;border-bottom:1px solid var(--border)">
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                            <div><b><?= $i + 1 ?>. <?= htmlspecialchars($tf['title']) ?></b><br><span style="font-size:0.78rem;color:var(--text-muted)"><?= $tf['tickets'] ?> tiket terjual</span></div>
                            <span style="font-weight:700;color:var(--red)"><?= formatRupiah($tf['revenue']) ?></span>
                        </div>
                        <div class="report-bar">
                            <div class="report-bar-fill" style="width:<?= round($tf['tickets'] / $maxT * 100) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-hdr">
                    <h3>Ringkasan</h3>
                </div>
                <table>
                    <tbody>
                        <tr>
                            <td>Total Pendapatan Tiket</td>
                            <td style="text-align:right;font-weight:700"><?= formatRupiah($totalRevenue) ?></td>
                        </tr>
                        <tr>
                            <td>Total Tiket Terjual</td>
                            <td style="text-align:right;font-weight:700"><?= $totalTickets ?></td>
                        </tr>
                        <tr>
                            <td>Total Item F&B Terjual</td>
                            <td style="text-align:right;font-weight:700"><?= $totalFnb ?></td>
                        </tr>
                        <tr>
                            <td>Total Film Tersedia</td>
                            <td style="text-align:right;font-weight:700"><?= count($topFilms) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</body>

</html>