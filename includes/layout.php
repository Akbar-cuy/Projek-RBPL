<?php
// Shared CSS & layout helpers
function getBaseStyles() {
    return '
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    :root {
        --red: #e61515;
        --red-dark: #c00;
        --bg-dark: #0f1117;
        --bg-card: #1a1d27;
        --bg-card2: #21253a;
        --text: #f0f0f0;
        --text-muted: #8892a4;
        --border: rgba(255,255,255,0.08);
        --green: #22c55e;
        --yellow: #f59e0b;
        --blue: #3b82f6;
        --purple: #a855f7;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: "Plus Jakarta Sans", sans-serif;
        background: var(--bg-dark);
        color: var(--text);
        min-height: 100vh;
    }
    a { color: inherit; text-decoration: none; }
    button, input, select, textarea { font-family: inherit; }
    .badge {
        display: inline-flex; align-items: center;
        padding: 3px 10px; border-radius: 20px;
        font-size: 0.7rem; font-weight: 700; letter-spacing: 0.3px;
    }
    .badge-green { background: rgba(34,197,94,0.15); color: #22c55e; }
    .badge-yellow { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .badge-red { background: rgba(230,21,21,0.15); color: #e61515; }
    .badge-blue { background: rgba(59,130,246,0.15); color: #3b82f6; }
    .badge-purple { background: rgba(168,85,247,0.15); color: #a855f7; }
    .badge-gray { background: rgba(255,255,255,0.08); color: var(--text-muted); }
    .card {
        background: var(--bg-card);
        border-radius: 16px;
        border: 1px solid var(--border);
    }
    .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 6px; padding: 10px 20px;
        border-radius: 10px; font-weight: 600;
        font-size: 0.875rem; cursor: pointer;
        border: none; transition: all 0.2s;
    }
    .btn-primary { background: var(--red); color: white; }
    .btn-primary:hover { background: var(--red-dark); transform: translateY(-1px); }
    .btn-success { background: #22c55e; color: white; }
    .btn-success:hover { background: #16a34a; }
    .btn-outline {
        background: transparent; color: var(--text);
        border: 1.5px solid var(--border);
    }
    .btn-outline:hover { border-color: var(--red); color: var(--red); }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; }
    .btn-sm { padding: 7px 14px; font-size: 0.8rem; }
    .btn-lg { padding: 15px 30px; font-size: 1rem; width: 100%; }
    input[type=text], input[type=email], input[type=number], input[type=password], 
    input[type=search], select, textarea {
        background: var(--bg-card2); border: 1.5px solid var(--border);
        color: var(--text); border-radius: 10px;
        padding: 10px 14px; font-size: 0.875rem;
        outline: none; transition: all 0.2s; width: 100%;
    }
    input:focus, select:focus, textarea:focus {
        border-color: var(--red);
        box-shadow: 0 0 0 3px rgba(230,21,21,0.12);
    }
    input::placeholder { color: var(--text-muted); }
    .section-title {
        font-size: 1rem; font-weight: 700;
        color: var(--text); margin-bottom: 16px;
        display: flex; align-items: center; gap: 8px;
    }
    </style>';
}

function renderMobileNav($active = 'home') {
    $role = $_SESSION['role'] ?? 'customer';
    echo '';
}
?>
