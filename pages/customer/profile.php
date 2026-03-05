<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
requireRole('customer');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - TursMovie</title>
    <?= getBaseStyles() ?>
    <style>
        .mobile-wrap {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        .top-header {
            background: var(--red);
            padding: 20px 20px 24px;
        }

        .top-header h1 {
            font-size: 1.3rem;
            font-weight: 800;
        }

        .content {
            padding: 20px;
        }

        .profile-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
        }

        .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a855f7, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profile-info h2 {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .profile-info .role {
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: capitalize;
            margin-bottom: 12px;
        }

        .profile-meta {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .menu-list {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.15s;
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item:hover {
            background: var(--bg-card2);
        }

        .menu-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .menu-item-info h4 {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .menu-item svg.arrow {
            margin-left: auto;
            color: var(--text-muted);
        }

        .logout-btn {
            width: 100%;
            background: var(--red);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 15px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: var(--red-dark);
        }

        .version {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.78rem;
            margin-top: 16px;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: rgba(15, 17, 23, 0.95);
            backdrop-filter: blur(16px);
            border-top: 1px solid var(--border);
            display: flex;
            z-index: 100;
            padding: 8px 0 12px;
        }

        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 600;
        }

        .nav-item.active {
            color: var(--red);
        }

        .nav-item svg {
            width: 22px;
            height: 22px;
        }
    </style>
</head>

<body>
    <div class="mobile-wrap">
        <div class="top-header">
            <h1>Profil Saya</h1>
        </div>
        <div class="content">
            <div class="profile-card">
                <div class="avatar">
                    <svg width="32" height="32" fill="white" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" fill="white" />
                    </svg>
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($_SESSION['name']) ?></h2>
                    <div class="role"><?= htmlspecialchars($_SESSION['role']) ?></div>
                    <div class="profile-meta">
                        <div class="meta-row">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg>
                            <?= htmlspecialchars($_SESSION['email'] ?? '-') ?>
                        </div>
                        <div class="meta-row">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.56a16 16 0 0 0 5.55 5.55l.63-1.05a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>
                            <?= htmlspecialchars($_SESSION['phone'] ?? '-') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="menu-list">
                <div class="menu-item">
                    <div class="menu-icon" style="background:rgba(59,130,246,0.15)">
                        <svg width="18" height="18" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3" />
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                    </div>
                    <div class="menu-item-info">
                        <h4>Pengaturan Akun</h4>
                    </div>
                    <svg class="arrow" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </div>
                <div class="menu-item">
                    <div class="menu-icon" style="background:rgba(245,158,11,0.15)">
                        <svg width="18" height="18" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                    </div>
                    <div class="menu-item-info">
                        <h4>Notifikasi</h4>
                    </div>
                    <svg class="arrow" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </div>
                <div class="menu-item">
                    <div class="menu-icon" style="background:rgba(34,197,94,0.15)">
                        <svg width="18" height="18" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                    </div>
                    <div class="menu-item-info">
                        <h4>Bantuan & Dukungan</h4>
                    </div>
                    <svg class="arrow" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </div>
            </div>

            <button class="logout-btn" onclick="if(confirm('Yakin ingin keluar?')) location.href='../../logout.php'">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Keluar
            </button>
            <div class="version">TursMovie v1.0.0</div>
        </div>
    </div>
    <nav class="bottom-nav">
        <a class="nav-item" href="home.php"><svg fill="currentColor" viewBox="0 0 24 24">
                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" />
            </svg>Home</a>
        <a class="nav-item" href="films.php"><svg fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <rect x="2" y="2" width="20" height="20" rx="2.18" />
                <line x1="7" y1="2" x2="7" y2="22" />
                <line x1="17" y1="2" x2="17" y2="22" />
                <line x1="2" y1="12" x2="22" y2="12" />
            </svg>Films</a>
        <a class="nav-item active" href="profile.php"><svg fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>Profile</a>
    </nav>
</body>

</html>