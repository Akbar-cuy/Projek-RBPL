<?php
require_once '../../config.php';
require_once '../../includes/layout.php';
require_once '../../includes/kasir_nav.php';
requireRole('kasir');
$db = getDB();

// ── Upload helper ──────────────────────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/../../uploads/posters/');
define('UPLOAD_URL', '../../uploads/posters/');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

function uploadPoster($file, $oldImage = null)
{
    if (empty($file['name'])) return $oldImage; // no new file
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed)) {
        return ['error' => 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Ukuran gambar maksimal 5MB.'];
    }
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = 'poster_' . uniqid() . '.' . strtolower($ext);
    $dest = UPLOAD_DIR . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'Gagal menyimpan gambar. Cek permission folder uploads/posters/'];
    }
    // delete old local poster
    if ($oldImage && strpos($oldImage, 'uploads/posters/') !== false) {
        $oldPath = __DIR__ . '/../../' . ltrim(str_replace('../../', '', $oldImage), '/');
        if (file_exists($oldPath)) @unlink($oldPath);
    }
    return UPLOAD_URL . $name;
}

// ── CRUD Actions ───────────────────────────────────────────────────────────────
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // DELETE
    if (isset($_POST['delete_film'])) {
        $fid = intval($_POST['fid']);
        $img = $db->prepare("SELECT image FROM films WHERE id=?")->execute([$fid]) ? null : null;
        $row = $db->prepare("SELECT image FROM films WHERE id=?");
        $row->execute([$fid]);
        $row = $row->fetch();
        if ($row && $row['image'] && strpos($row['image'], 'uploads/posters/') !== false) {
            $p = UPLOAD_DIR . basename($row['image']);
            if (file_exists($p)) @unlink($p);
        }
        $db->prepare("DELETE FROM films WHERE id=?")->execute([$fid]);
        $success = 'Film berhasil dihapus.';

        // ADD / EDIT
    } elseif (isset($_POST['save_film'])) {
        $id       = intval($_POST['id'] ?? 0);
        $title    = trim($_POST['title']);
        $genre    = trim($_POST['genre']);
        $synopsis = trim($_POST['synopsis']);
        $duration = intval($_POST['duration']);
        $rating   = trim($_POST['rating']);
        $score    = floatval($_POST['score']);
        $price    = intval($_POST['price']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // validate
        if (!$title) {
            $error = 'Judul film wajib diisi.';
        } else {
            // handle image
            $oldImage = $_POST['old_image'] ?? null;
            $imageResult = uploadPoster($_FILES['poster'] ?? ['name' => ''], $oldImage);
            if (is_array($imageResult) && isset($imageResult['error'])) {
                $error = $imageResult['error'];
            } else {
                $image = $imageResult ?: $oldImage;
                if ($id) {
                    $db->prepare("UPDATE films SET title=?,genre=?,synopsis=?,duration=?,rating=?,score=?,price=?,image=?,is_active=? WHERE id=?")
                        ->execute([$title, $genre, $synopsis, $duration, $rating, $score, $price, $image, $is_active, $id]);
                    $success = 'Film berhasil diperbarui.';
                } else {
                    $db->prepare("INSERT INTO films (title,genre,synopsis,duration,rating,score,price,image,is_active) VALUES (?,?,?,?,?,?,?,?,?)")
                        ->execute([$title, $genre, $synopsis, $duration, $rating, $score, $price, $image, $is_active]);
                    $success = 'Film berhasil ditambahkan.';
                }
            }
        }
    }
}

// ── Fetch Data ─────────────────────────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$filter  = $_GET['status'] ?? 'all';
$sql     = "SELECT * FROM films WHERE 1";
$params  = [];
if ($search) {
    $sql .= " AND title LIKE ?";
    $params[] = "%$search%";
}
if ($filter === 'active') {
    $sql .= " AND is_active=1";
}
if ($filter === 'inactive') {
    $sql .= " AND is_active=0";
}
$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$films = $stmt->fetchAll();
$totalActive   = $db->query("SELECT COUNT(*) FROM films WHERE is_active=1")->fetchColumn();
$totalInactive = $db->query("SELECT COUNT(*) FROM films WHERE is_active=0")->fetchColumn();

// edit mode
$editFilm = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM films WHERE id=?");
    $s->execute([$_GET['edit']]);
    $editFilm = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Film - TursMovie Kasir</title>
    <?= getBaseStyles() ?>
    <style>
        <?php include '../../includes/admin_styles.php'; ?>

        /* ── Page-specific ── */
        .film-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px
        }

        .film-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s
        }

        .film-card:hover {
            transform: translateY(-2px);
            border-color: rgba(230, 21, 21, 0.3)
        }

        .film-poster {
            position: relative;
            height: 200px;
            background: var(--bg-card2);
            overflow: hidden
        }

        .film-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s
        }

        .film-card:hover .film-poster img {
            transform: scale(1.04)
        }

        .film-poster-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            gap: 8px;
            font-size: 0.8rem
        }

        .film-overlay {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            gap: 6px
        }

        .film-body {
            padding: 14px
        }

        .film-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .film-meta {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 8px
        }

        .film-score {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #f59e0b
        }

        .film-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.78rem;
            border-radius: 8px
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3)
        }

        .btn-danger:hover {
            background: #ef4444;
            color: white
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 500;
            align-items: center;
            justify-content: center;
            padding: 16px;
            overflow-y: auto
        }

        .modal.show {
            display: flex
        }

        .modal-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            margin: auto
        }

        .modal-box h3 {
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px
        }

        .form-group {
            margin-bottom: 14px
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-muted)
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px
        }

        /* Upload zone */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative
        }

        .upload-zone:hover,
        .upload-zone.drag {
            border-color: var(--red);
            background: rgba(230, 21, 21, 0.05)
        }

        .upload-zone input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%
        }

        .upload-preview {
            width: 100%;
            max-height: 160px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            display: none
        }

        .upload-preview.show {
            display: block
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444
        }

        /* Stats */
        .film-stats {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap
        }

        .film-stat {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 140px
        }

        .film-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0
        }

        .film-stat-val {
            font-size: 1.5rem;
            font-weight: 800
        }

        .film-stat-lbl {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <?php kasirNav('films'); ?>

        <div class="overlay" id="ov" onclick="this.classList.remove('show');document.querySelector('.sidebar').classList.remove('open')"></div>
        <div class="main-content">

            <!-- Topbar -->
            <div class="topbar">
                <button class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('open');document.getElementById('ov').classList.toggle('show')">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
                <span class="topbar-title">TursMovie Kasir</span>
                <button class="btn btn-primary btn-sm" onclick="openAddModal()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Tambah Film
                </button>
            </div>

            <div class="page-content">
                <h1 style="font-size:1.3rem;font-weight:800;margin-bottom:4px">🎬 Kelola Film</h1>
                <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:20px">Tambah, edit, dan hapus data film beserta poster</p>

                <?php if ($success): ?>
                    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="film-stats">
                    <div class="film-stat">
                        <div class="film-stat-icon" style="background:rgba(230,21,21,0.1)">🎬</div>
                        <div>
                            <div class="film-stat-val"><?= count($films) ?></div>
                            <div class="film-stat-lbl">Total Film</div>
                        </div>
                    </div>
                    <div class="film-stat">
                        <div class="film-stat-icon" style="background:rgba(34,197,94,0.1)">✅</div>
                        <div>
                            <div class="film-stat-val"><?= $totalActive ?></div>
                            <div class="film-stat-lbl">Film Aktif</div>
                        </div>
                    </div>
                    <div class="film-stat">
                        <div class="film-stat-icon" style="background:rgba(245,158,11,0.1)">⏸️</div>
                        <div>
                            <div class="film-stat-val"><?= $totalInactive ?></div>
                            <div class="film-stat-lbl">Tidak Aktif</div>
                        </div>
                    </div>
                </div>

                <!-- Filter & Search -->
                <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
                    <div class="filter-tabs" style="margin:0">
                        <a href="films.php<?= $search ? "?q=" . urlencode($search) : '' ?>" class="tab-btn <?= $filter === 'all' ? 'active' : '' ?>">Semua</a>
                        <a href="films.php?status=active<?= $search ? "&q=" . urlencode($search) : '' ?>" class="tab-btn <?= $filter === 'active' ? 'active' : '' ?>">Aktif</a>
                        <a href="films.php?status=inactive<?= $search ? "&q=" . urlencode($search) : '' ?>" class="tab-btn <?= $filter === 'inactive' ? 'active' : '' ?>">Nonaktif</a>
                    </div>
                    <form method="GET" style="flex:1;min-width:200px;max-width:360px;position:relative">
                        <?php if ($filter !== 'all'): ?><input type="hidden" name="status" value="<?= $filter ?>"><?php endif; ?>
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul film…" style="padding-left:38px;width:100%">
                    </form>
                </div>

                <!-- Film Grid -->
                <?php if (empty($films)): ?>
                    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
                        <div style="font-size:3rem;margin-bottom:12px">🎬</div>
                        <div style="font-weight:700;margin-bottom:8px">Belum ada film</div>
                        <div style="font-size:0.875rem">Klik tombol "Tambah Film" untuk menambahkan film pertama</div>
                    </div>
                <?php else: ?>
                    <div class="film-grid">
                        <?php foreach ($films as $f): ?>
                            <div class="film-card">
                                <div class="film-poster">
                                    <?php if ($f['image']): ?>
                                        <img src="<?= htmlspecialchars($f['image']) ?>" alt="<?= htmlspecialchars($f['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="film-poster-placeholder">
                                            <span style="font-size:2.5rem">🎬</span>
                                            <span>Belum ada poster</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="film-overlay">
                                        <span class="badge <?= $f['is_active'] ? 'badge-green' : 'badge-red' ?>"><?= $f['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                                    </div>
                                </div>
                                <div class="film-body">
                                    <div class="film-title" title="<?= htmlspecialchars($f['title']) ?>"><?= htmlspecialchars($f['title']) ?></div>
                                    <div class="film-meta"><?= htmlspecialchars($f['genre'] ?: '—') ?> • <?= $f['duration'] ?> menit • <?= htmlspecialchars($f['rating'] ?: '—') ?></div>
                                    <div style="display:flex;align-items:center;justify-content:space-between">
                                        <span style="color:var(--red);font-weight:700;font-size:0.875rem"><?= formatRupiah($f['price']) ?></span>
                                        <?php if ($f['score']): ?>
                                            <span class="film-score">⭐ <?= number_format($f['score'], 1) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="film-actions">
                                        <a href="films.php?edit=<?= $f['id'] ?><?= $search ? "&q=" . urlencode($search) : '' ?><?= $filter !== 'all' ? "&status=$filter" : '' ?>" class="btn btn-primary btn-sm" style="flex:1">✏️ Edit</a>
                                        <form method="POST" onsubmit="return confirm('Yakin hapus film ini? Data jadwal terkait juga akan dihapus!')">
                                            <input type="hidden" name="fid" value="<?= $f['id'] ?>">
                                            <button name="delete_film" class="btn btn-danger btn-sm">🗑</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ ADD MODAL ═════════════════════════════════════════════════════════════ -->
    <div class="modal" id="addModal">
        <div class="modal-box">
            <h3>🎬 Tambah Film Baru</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="0">
                <div class="form-group">
                    <label>Judul Film *</label>
                    <input type="text" name="title" required placeholder="Contoh: Avengers: Endgame">
                </div>
                <div class="form-row">
                    <div class="form-group" style="margin:0">
                        <label>Genre</label>
                        <input type="text" name="genre" placeholder="Action, Drama, ...">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Rating Usia</label>
                        <select name="rating">
                            <option value="">— Pilih —</option>
                            <option value="SU">SU (Semua Umur)</option>
                            <option value="13+">13+</option>
                            <option value="17+">17+</option>
                            <option value="21+">21+</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Sinopsis</label>
                    <textarea name="synopsis" rows="3" placeholder="Deskripsi singkat film..."></textarea>
                </div>
                <div class="form-row-3">
                    <div class="form-group" style="margin:0">
                        <label>Durasi (menit)</label>
                        <input type="number" name="duration" min="1" placeholder="120">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Skor (0–10)</label>
                        <input type="number" name="score" step="0.1" min="0" max="10" placeholder="8.5">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Harga Tiket (Rp)</label>
                        <input type="number" name="price" min="0" placeholder="45000">
                    </div>
                </div>

                <!-- Upload Poster -->
                <div class="form-group">
                    <label>Poster Film</label>
                    <div class="upload-zone" id="addUploadZone"
                        ondragover="this.classList.add('drag')"
                        ondragleave="this.classList.remove('drag')"
                        ondrop="this.classList.remove('drag')">
                        <input type="file" name="poster" accept="image/*" onchange="previewPoster(this,'addPreview','addUploadHint')">
                        <div id="addUploadHint">
                            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--text-muted);margin-bottom:8px">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="17 8 12 3 7 8" />
                                <line x1="12" y1="3" x2="12" y2="15" />
                            </svg>
                            <div style="font-size:0.82rem;color:var(--text-muted)">Klik atau seret gambar ke sini</div>
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">JPG, PNG, WebP — maks. 5MB</div>
                        </div>
                        <img id="addPreview" class="upload-preview" alt="preview">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="is_active" checked style="width:16px;height:16px;accent-color:var(--red)">
                        <span>Film Aktif (tampil di layar pemilihan)</span>
                    </label>
                </div>
                <div style="display:flex;gap:8px;margin-top:4px">
                    <button type="submit" name="save_film" class="btn btn-primary" style="flex:1">💾 Simpan Film</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('show')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ EDIT MODAL ════════════════════════════════════════════════════════════ -->
    <?php if ($editFilm): ?>
        <div class="modal show" id="editModal">
            <div class="modal-box">
                <h3>✏️ Edit Film</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $editFilm['id'] ?>">
                    <input type="hidden" name="old_image" value="<?= htmlspecialchars($editFilm['image'] ?? '') ?>">
                    <div class="form-group">
                        <label>Judul Film *</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($editFilm['title']) ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="margin:0">
                            <label>Genre</label>
                            <input type="text" name="genre" value="<?= htmlspecialchars($editFilm['genre'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label>Rating Usia</label>
                            <select name="rating">
                                <option value="">— Pilih —</option>
                                <?php foreach (['SU', '13+', '17+', '21+'] as $r): ?>
                                    <option value="<?= $r ?>" <?= ($editFilm['rating'] === $r) ? 'selected' : '' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Sinopsis</label>
                        <textarea name="synopsis" rows="3"><?= htmlspecialchars($editFilm['synopsis'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group" style="margin:0">
                            <label>Durasi (menit)</label>
                            <input type="number" name="duration" value="<?= intval($editFilm['duration']) ?>">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label>Skor (0–10)</label>
                            <input type="number" name="score" step="0.1" min="0" max="10" value="<?= $editFilm['score'] ?>">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label>Harga Tiket (Rp)</label>
                            <input type="number" name="price" value="<?= intval($editFilm['price']) ?>">
                        </div>
                    </div>

                    <!-- Upload Poster -->
                    <div class="form-group">
                        <label>Poster Film</label>
                        <?php if ($editFilm['image']): ?>
                            <div style="margin-bottom:10px;border-radius:10px;overflow:hidden;max-height:160px">
                                <img src="<?= htmlspecialchars($editFilm['image']) ?>" alt="current" style="width:100%;max-height:160px;object-fit:cover">
                            </div>
                            <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:8px">Upload gambar baru untuk mengganti poster saat ini</div>
                        <?php endif; ?>
                        <div class="upload-zone" id="editUploadZone"
                            ondragover="this.classList.add('drag')"
                            ondragleave="this.classList.remove('drag')"
                            ondrop="this.classList.remove('drag')">
                            <input type="file" name="poster" accept="image/*" onchange="previewPoster(this,'editPreview','editUploadHint')">
                            <div id="editUploadHint">
                                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--text-muted);margin-bottom:6px">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                                <div style="font-size:0.82rem;color:var(--text-muted)">Klik atau seret untuk ganti poster</div>
                            </div>
                            <img id="editPreview" class="upload-preview" alt="preview">
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="is_active" <?= $editFilm['is_active'] ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--red)">
                            <span>Film Aktif</span>
                        </label>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:4px">
                        <button type="submit" name="save_film" class="btn btn-primary" style="flex:1">💾 Update Film</button>
                        <a href="films.php" class="btn btn-outline">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('show');
        }
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });

        function previewPoster(input, previewId, hintId) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(previewId);
                const hint = document.getElementById(hintId);
                img.src = e.target.result;
                img.classList.add('show');
                hint.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>

</html>