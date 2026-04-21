<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
require_once '../../includes/kasir_nav.php';
requireRole('kasir');

$db = getDB();
$success = '';
$error = '';

// ── Proses Simpan / Hapus ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TAMBAH JADWAL
    if (isset($_POST['add_schedule'])) {
        $film_id   = intval($_POST['film_id']);
        $show_date = $_POST['show_date'];
        $show_time = $_POST['show_time'];
        $theater   = $_POST['theater'];
        $seats     = intval($_POST['total_seats']);

        if ($film_id && $show_date && $show_time && $theater) {
            $stmt = $db->prepare("INSERT INTO showtimes (film_id, show_date, show_time, theater, total_seats, available_seats) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$film_id, $show_date, $show_time, $theater, $seats, $seats]);
            $success = "Jadwal berhasil ditambahkan.";
        } else {
            $error = "Semua kolom wajib diisi.";
        }
    }

    // HAPUS JADWAL
    if (isset($_POST['delete_id'])) {
        $db->prepare("DELETE FROM showtimes WHERE id = ?")->execute([$_POST['delete_id']]);
        $success = "Jadwal berhasil dihapus.";
    }
}

// ── Ambil Data ──────────────────────────────────────────────────────────────
$films = $db->query("SELECT id, title FROM films WHERE is_active = 1 ORDER BY title")->fetchAll();

$sql = "SELECT s.*, f.title, f.image 
        FROM showtimes s 
        JOIN films f ON s.film_id = f.id 
        ORDER BY s.show_date ASC, s.show_time ASC";
$schedules = $db->query($sql)->fetchAll();

$statusLabel = [
    'scheduled' => ['badge-yellow', 'Terjadwal'],
    'ready'     => ['badge-blue', 'Siap'],
    'showing'   => ['badge-green', 'Tayang'],
    'finished'  => ['badge-red', 'Selesai']
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Jadwal - TursMovie Kasir</title>
    <?= getBaseStyles() ?>
    <style>
        <?php include '../../includes/admin_styles.php'; ?>
        
        .grid-inputs select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: var(--bg-card2);
            /* Menggunakan SVG yang lebih kompatibel dengan background-size yang pas */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238892a4' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 14px;
            padding-right: 40px !important;
            /* Ruang agar teks tidak menabrak panah */
            cursor: pointer;
        }

        /* Pastikan saat diklik (focus), panahnya tetap ada atau berubah warna */
        .grid-inputs select:focus {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23e61515' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        }

        /* Solusi Teks Putih di Background Putih (Dropdown Option) */
        .grid-inputs select option {
            background-color: #1a1d27;
            /* Warna gelap card */
            color: white;
            padding: 12px;
        }

        /* Container Form */
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* Judul di dalam form */
        .form-card h3 {
            font-size: 1rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
        }

        /* Layout Grid Form */
        .grid-inputs {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 0.8fr 0.8fr auto;
            gap: 15px;
            align-items: flex-end;
        }

        /* Grouping Label & Input */
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Styling Input, Select, & Number */
        .grid-inputs input,
        .grid-inputs select {
            background: var(--bg-card2);
            border: 1.5px solid var(--border);
            color: var(--text);
            padding: 12px 14px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.85rem;
            width: 100%;
            transition: all 0.2s;
            outline: none;
        }

        /* Efek saat Input diklik (Focus) */
        .grid-inputs input:focus,
        .grid-inputs select:focus {
            border-color: var(--red);
            background: rgba(230, 21, 21, 0.05);
            box-shadow: 0 0 0 4px rgba(230, 21, 21, 0.1);
        }

        /* Tombol Tambah agar lebih gagah */
        .btn-add {
            padding: 12px 24px;
            height: 46px;
            /* Samakan dengan tinggi input */
            font-weight: 700;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Mempercantik ikon kalender & jam bawaan browser (khusus chrome/edge) */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            /* Bikin ikonnya jadi putih/terang */
            cursor: pointer;
            opacity: 0.6;
        }

        @media (max-width: 1000px) {
            .grid-inputs {
                grid-template-columns: 1fr 1fr;
            }

            .btn-add {
                grid-column: span 2;
                margin-top: 10px;
            }
        }

        .sched-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }

        .sched-img {
            width: 50px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--bg-card2);
        }

        .sched-info {
            flex: 1;
        }

        .sched-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .sched-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            gap: 10px;
        }

        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .grid-inputs {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: flex-end;
        }

        @media (max-width: 900px) {
            .grid-inputs {
                grid-template-columns: 1fr 1fr;
            }

            .btn-add {
                grid-column: span 2;
            }
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php kasirNav('schedulesfilm'); // Pastikan nav link ini ada atau gunakan link manual 
        ?>

        <div class="main-content">
            <div class="topbar">
                <span class="topbar-title">Penjadwalan Film</span>
            </div>

            <div class="page-content">
                <h1 style="font-size:1.3rem;font-weight:800;margin-bottom:4px">📅 Kelola Jadwal Tayang</h1>
                <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:24px">Atur waktu penayangan dan studio untuk film yang aktif</p>

                <?php if ($success): ?><div style="color:#22c55e; margin-bottom:15px">✅ <?= $success ?></div><?php endif; ?>

                <div class="form-card">
                    <h3>
                        <svg width="20" height="20" fill="none" stroke="var(--red)" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Buat Jadwal Penayangan
                    </h3>

                    <form method="POST" class="grid-inputs">
                        <div class="input-group">
                            <label>Pilih Film</label>
                            <select name="film_id" required>
                                <option value="">— Cari Film —</option>
                                <?php foreach ($films as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Tanggal Tayang</label>
                            <input type="date" name="show_date" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="input-group">
                            <label>Waktu / Jam</label>
                            <input type="time" name="show_time" required>
                        </div>

                        <div class="input-group">
                            <label>Studio</label>
                            <select name="theater" required>
                                <option value="Theater 1">Theater 1</option>
                                <option value="Theater 2">Theater 2</option>
                                <option value="Theater 3">Theater 3</option>
                                <option value="Premiere">Premiere</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Kapasitas</label>
                            <input type="number" name="total_seats" value="40" min="1" required>
                        </div>

                        <button type="submit" name="add_schedule" class="btn btn-primary btn-add">
                            <span>Tambah</span>
                        </button>
                    </form>
                </div>

                <div style="margin-top:30px">
                    <h3 style="font-size:0.9rem; margin-bottom:15px">Jadwal Mendatang</h3>
                    <?php if (empty($schedules)): ?>
                        <div style="text-align:center; padding:40px; color:var(--text-muted)">Belum ada jadwal yang diatur.</div>
                        <?php else: foreach ($schedules as $s): ?>
                            <div class="sched-card">
                                <img src="<?= $s['image'] ?: '../../assets/img/no-poster.jpg' ?>" class="sched-img">
                                <div class="sched-info">
                                    <div class="sched-title"><?= htmlspecialchars($s['title']) ?></div>
                                    <div class="sched-meta">
                                        <span>📅 <?= date('d M Y', strtotime($s['show_date'])) ?></span>
                                        <span>⏰ <?= substr($s['show_time'], 0, 5) ?></span>
                                        <span>🎬 <?= $s['theater'] ?></span>
                                        <span>💺 <?= $s['available_seats'] ?>/<?= $s['total_seats'] ?></span>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge <?= $statusLabel[$s['status']][0] ?>"><?= $statusLabel[$s['status']][1] ?></span>
                                </div>
                                <form method="POST" onsubmit="return confirm('Hapus jadwal ini?')">
                                    <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                    <button class="btn btn-danger btn-sm" style="padding:8px">🗑</button>
                                </form>
                            </div>
                    <?php endforeach;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>