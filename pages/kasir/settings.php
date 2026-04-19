<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
require_once '../../includes/kasir_nav.php';
requireRole('kasir');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pengaturan - TursMovie Kasir</title><?= getBaseStyles() ?>
    <style>
        <?php include '../../includes/admin_styles.php'; ?>.settings-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px
        }

        .settings-section h3 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border)
        }

        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04)
        }

        .setting-row:last-child {
            border-bottom: none
        }

        .setting-row label {
            font-size: 0.875rem
        }

        .toggle {
            width: 44px;
            height: 24px;
            background: var(--bg-card2);
            border-radius: 12px;
            position: relative;
            cursor: pointer;
            border: none;
            transition: all 0.2s
        }

        .toggle.on {
            background: var(--green)
        }

        .toggle::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            top: 3px;
            left: 3px;
            transition: all 0.2s
        }

        .toggle.on::after {
            left: 23px
        }
    </style>
</head>

<body>
    <?php kasirNav('settings'); ?>

    <div class="overlay" id="ov" onclick="this.classList.remove('show');document.querySelector('.sidebar').classList.remove('open')"></div>
    <div class="main-content">
        <div class="topbar"><button class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('open');document.getElementById('ov').classList.toggle('show')"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg></button><span class="topbar-title">TursMovie Kasir</span></div>
        <div class="page-content">
            <h1 style="font-size:1.3rem;font-weight:800;margin-bottom:20px">Pengaturan</h1>
            <div class="settings-section">
                <h3>Informasi Pengguna</h3>
                <div class="setting-row"><span style="color:var(--text-muted)">Nama</span><span><?= htmlspecialchars($_SESSION['name']) ?></span></div>
                <div class="setting-row"><span style="color:var(--text-muted)">Username</span><span><?= htmlspecialchars($_SESSION['username']) ?></span></div>
                <div class="setting-row"><span style="color:var(--text-muted)">Role</span><span class="badge badge-blue"><?= htmlspecialchars($_SESSION['role']) ?></span></div>
            </div>
            <div class="settings-section">
                <h3>Backup & Restore</h3>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="proses_backup.php" class="btn btn-primary" style="text-decoration:none">
                        💾 Backup Database Sekarang
                    </a>

                    <form action="proses_restore.php" method="POST" enctype="multipart/form-data" id="restoreForm" style="display:inline">
                        <input type="file" name="backup_file" id="fileInput" hidden accept=".sql" onchange="confirmRestore()">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('fileInput').click()">
                            📂 Restore dari Backup
                        </button>
                    </form>
                </div>

                <?php if (isset($_GET['status']) && $_GET['status'] == 'restore_success'): ?>
                    <p style="color:var(--green); font-size:0.8rem; margin-top:10px">✅ Database berhasil dipulihkan!</p>
                <?php endif; ?>

                <p style="font-size:0.8rem;color:var(--text-muted);margin-top:12px">
                    📅 Backup terakhir: <?= date('d F Y, H:i') ?> WIB
                </p>
            </div>

            <script>
                function confirmRestore() {
                    if (confirm("⚠️ PERINGATAN: Restore akan menimpa data saat ini. Lanjutkan?")) {
                        document.getElementById('restoreForm').submit();
                    } else {
                        document.getElementById('fileInput').value = "";
                    }
                }
            </script>
            <div class="settings-section">
                <h3>Notifikasi</h3>
                <div class="setting-row"><label>Pesanan Baru</label><button class="toggle on" onclick="this.classList.toggle('on')"></button></div>
                <div class="setting-row"><label>Pembayaran Selesai</label><button class="toggle on" onclick="this.classList.toggle('on')"></button></div>
                <div class="setting-row"><label>Laporan Harian</label><button class="toggle" onclick="this.classList.toggle('on')"></button></div>
            </div>
            <div class="settings-section">
                <h3>Keamanan & Privasi</h3>
                <button class="btn btn-outline" style="margin-bottom:8px">🔑 Ubah Password</button>
            </div>
            <div class="settings-section">
                <h3>Bantuan & Dokumentasi</h3>
                <p style="color:var(--text-muted);font-size:0.875rem">Butuh bantuan? Hubungi tim support TursMovie melalui email atau WhatsApp.</p>
                <div style="display:flex;gap:8px;margin-top:10px">
                    <button class="btn btn-outline btn-sm">📧 Email Support</button>
                    <button class="btn btn-outline btn-sm">💬 WhatsApp</button>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>

</html>