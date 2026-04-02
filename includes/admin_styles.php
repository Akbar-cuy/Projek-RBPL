/* Admin Shared Styles - included in all admin pages */
.admin-layout{display:flex;min-height:100vh;}
.sidebar{width:240px;flex-shrink:0;background:#0a0c14;border-right:1px solid
var(--border);display:flex;flex-direction:column;position:fixed;left:0;top:0;bottom:0;z-index:200;transition:transform
0.3s;overflow-y:auto;}
.sidebar-header{padding:18px 20px;border-bottom:1px solid var(--border);}
.brand{display:flex;align-items:center;gap:10px;margin-bottom:2px;}
.sidebar-user{display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border);}
.user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--red),#7c3aed);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.875rem;flex-shrink:0;}
.user-name{font-size:0.875rem;font-weight:600;}
.user-role{font-size:0.72rem;color:var(--text-muted);text-transform:capitalize;}
.sidebar-nav{flex:1;padding:10px 0;}
.nav-link{display:flex;align-items:center;gap:10px;padding:11px
20px;color:var(--text-muted);font-size:0.875rem;font-weight:500;text-decoration:none;transition:all 0.15s;}
.nav-link:hover{background:rgba(255,255,255,0.05);color:var(--text);}
.nav-link.active{background:rgba(230,21,21,0.1);color:var(--red);border-right:3px solid var(--red);}
.nav-badge{background:var(--red);color:white;font-size:0.65rem;font-weight:700;padding:2px
6px;border-radius:10px;margin-left:auto;}
.sidebar-logout{display:flex;align-items:center;gap:10px;padding:14px
20px;color:var(--text-muted);font-size:0.875rem;text-decoration:none;border-top:1px solid var(--border);transition:all
0.15s;}
.sidebar-logout:hover{color:#ef4444;}
.main-content{margin-left:240px;flex:1;min-height:100vh;display:flex;flex-direction:column;}
.topbar{background:var(--bg-card);border-bottom:1px solid var(--border);padding:14px
24px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:100;}
.topbar-title{font-size:1rem;font-weight:700;flex:1;}
.hamburger{display:none;background:none;border:none;cursor:pointer;color:var(--text);align-items:center;}
.page-content{padding:24px;flex:1;}
@media(max-width:900px){.sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}.main-content{margin-left:0;}.hamburger{display:flex;}.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:199;}.overlay.show{display:block;}}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
.stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:18px;}
.stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.stat-label{font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;}
.stat-value{font-size:1.5rem;font-weight:800;}
.stat-trend{font-size:0.78rem;font-weight:600;margin-top:4px;}
.trend-up{color:var(--green);}
.trend-down{color:#ef4444;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media(max-width:768px){.grid-2{grid-template-columns:1fr;}}
.card{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
.card-hdr{padding:14px 16px;border-bottom:1px solid
var(--border);display:flex;justify-content:space-between;align-items:center;}
.card-hdr h3{font-size:0.9rem;font-weight:700;}
.card-hdr a{font-size:0.8rem;color:var(--red);text-decoration:none;}
table{width:100%;border-collapse:collapse;}
th{font-size:0.78rem;color:var(--text-muted);font-weight:600;text-align:left;padding:10px 16px;border-bottom:1px solid
var(--border);}
td{padding:11px 16px;font-size:0.875rem;border-bottom:1px solid rgba(255,255,255,0.04);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(255,255,255,0.02);}
.filter-tabs{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;}
.tab-btn{padding:7px 16px;border-radius:20px;font-size:0.8rem;font-weight:600;cursor:pointer;border:1.5px solid
var(--border);background:transparent;color:var(--text);transition:all 0.2s;}
.tab-btn.active{background:var(--red);border-color:var(--red);color:white;}
.search-input{position:relative;margin-bottom:16px;}
.search-input input{padding-left:38px;}
.search-input svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;}