<?php
declare(strict_types=1);
/** @var string $title */
/** @var string $superAdminName */
/** @var bool $hideTopbar */
$hideTopbar = !empty($hideTopbar);
$_saUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= htmlspecialchars($title ?? 'Super Admin', ENT_QUOTES, 'UTF-8') ?> · MultiMenu</title>
  <style>
    :root {
      --sw: 244px;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
      --ring: #0f172a;
      --primary: #0f172a;
      --primary-fg: #f8fafc;
      --secondary: #f1f5f9;
      --secondary-fg: #0f172a;
      --destructive: #dc2626;
      --destructive-fg: #ffffff;
      --success: #166534;
      --success-bg: #dcfce7;
      --sidebar: #020617;
      --sidebar-muted: #94a3b8;
      --sidebar-border: #1e293b;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
      font-size: .925rem;
      color: var(--text);
      background: radial-gradient(circle at 20% 0%, #eef2ff 0%, var(--bg) 38%);
      min-height: 100vh;
    }

    /* ── Sidebar ── */
    .sidebar { position: fixed; top: 0; left: 0; width: var(--sw); height: 100vh; background: var(--sidebar); display: flex; flex-direction: column; z-index: 100; border-right: 1px solid var(--sidebar-border); }
    .sb-brand { padding: 1.1rem 1.25rem .85rem; border-bottom: 1px solid var(--sidebar-border); color: #e2e8f0; }
    .sb-logo { font-size: 1.02rem; font-weight: 700; letter-spacing: -.02em; }
    .sb-sub { font-size: .67rem; color: var(--sidebar-muted); margin-top: .12rem; text-transform: uppercase; letter-spacing: .07em; }
    .sb-user { padding: .65rem 1.25rem; border-bottom: 1px solid var(--sidebar-border); font-size: .77rem; color: var(--sidebar-muted); }
    .sb-user strong { display: block; color: #e2e8f0; font-size: .82rem; margin-bottom: .1rem; }
    .sb-nav { flex: 1; padding: .6rem 0; overflow-y: auto; }
    .sb-section { padding: .45rem 1.25rem .2rem; font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #475569; }
    .nav-item { display: flex; align-items: center; gap: .6rem; padding: .52rem 1.25rem; color: var(--sidebar-muted); text-decoration: none; font-size: .84rem; font-weight: 500; transition: background .14s, color .14s; }
    .nav-item:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }
    .nav-item.active { background: rgba(148,163,184,.14); color: #e2e8f0; border-right: 2px solid #e2e8f0; }
    .nav-item svg { width: 15px; height: 15px; flex-shrink: 0; }
    .sb-footer { padding: .8rem 1.25rem; border-top: 1px solid var(--sidebar-border); }
    .sb-footer form { margin: 0; }
    .btn-logout { width: 100%; display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .45rem .9rem; border: 1px solid #334155; background: transparent; color: #cbd5e1; border-radius: 8px; cursor: pointer; font-size: .82rem; font-weight: 500; transition: background .14s, color .14s; }
    .btn-logout:hover { background: rgba(220,38,38,.16); color: #fecaca; border-color: #7f1d1d; }

    /* ── Main ── */
    .main-wrap { margin-left: var(--sw); display: flex; flex-direction: column; min-height: 100vh; }
    .topbar { background: rgba(255,255,255,.9); border-bottom: 1px solid var(--border); padding: .75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; backdrop-filter: blur(8px); }
    .topbar-title { font-size: .95rem; font-weight: 700; color: var(--text); }
    .topbar-time { font-size: .78rem; color: var(--muted); }
    .shell { flex: 1; padding: 1.4rem 1.5rem 2rem; }

    /* ── Cards ── */
    .card { background: var(--card); border-radius: 12px; padding: 1.25rem 1.5rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); border: 1px solid var(--border); }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
    .stat-card { background: var(--card); border-radius: 12px; padding: 1rem 1.2rem; border: 1px solid var(--border); box-shadow: 0 1px 2px rgba(0,0,0,.04); }
    .stat-label { font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); margin-bottom: .3rem; }
    .stat-value { font-size: 1.75rem; font-weight: 800; color: var(--text); line-height: 1; }
    .stat-sub { font-size: .73rem; color: var(--muted); margin-top: .2rem; }

    /* ── Tipografia ── */
    h1 { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: -.02em; }
    h2 { margin: 0; font-size: .95rem; font-weight: 700; }
    .sub { margin: .15rem 0 0; color: var(--muted); font-size: .8rem; }
    label { display: block; font-weight: 600; font-size: .78rem; margin-bottom: .3rem; color: #475569; }

    /* ── Formulários ── */
    input[type="text"], input[type="email"], input[type="password"], input[type="search"], textarea, select {
      width: 100%; max-width: 480px; padding: .48rem .7rem; border: 1px solid var(--border); border-radius: 8px; font-size: .9rem; color: var(--text); background: #fff; transition: border-color .15s, box-shadow .15s;
    }
    input[type="search"] { max-width: 240px; }
    textarea { min-height: 80px; resize: vertical; max-width: 100%; }
    input:focus, textarea:focus, select:focus { outline: none; border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(15,23,42,.08); }
    .row { margin-bottom: 1rem; }
    .err { color: var(--danger); font-size: .78rem; margin-top: .25rem; }
    .hint { font-size: .75rem; color: var(--muted); margin-top: .2rem; }
    .readonly { background: #f8fafc !important; color: #64748b !important; cursor: not-allowed; }

    /* ── Flash ── */
    .flash { padding: .65rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: .875rem; }
    .flash.ok { background: #ecfdf5; color: var(--ok); border: 1px solid #6ee7b7; }
    .flash.bad { background: #fef2f2; color: var(--danger); border: 1px solid #fca5a5; }

    /* ── Botões ── */
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .5rem 1rem; border: 1px solid transparent; border-radius: 8px; font-weight: 600; font-size: .85rem; cursor: pointer; text-decoration: none; transition: background-color .12s, border-color .12s, color .12s; background: var(--primary); color: var(--primary-fg); }
    .btn:hover { background: #1e293b; }
    .btn.secondary { background: var(--card); color: var(--secondary-fg); border-color: var(--border); }
    .btn.secondary:hover { background: var(--secondary); }
    .btn.danger { background: var(--destructive); color: var(--destructive-fg); border-color: var(--destructive); }
    .btn.danger:hover { background: #b91c1c; border-color: #b91c1c; }
    .btn.sm { padding: .28rem .6rem; font-size: .77rem; }

    /* ── Toolbar ── */
    .toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
    .toolbar-left { display: flex; flex-direction: column; gap: .1rem; }
    .toolbar-right { display: flex; gap: .5rem; align-items: center; }

    /* ── Tabela ── */
    .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--border); background: #fff; }
    table { width: 100%; border-collapse: collapse; font-size: .855rem; }
    thead { background: #f8fafc; }
    th { text-align: left; padding: .55rem .75rem; font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap; }
    td { padding: .6rem .75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }

    /* ── Badges ── */
    .badge { display: inline-flex; align-items: center; gap: .25rem; padding: .16rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 700; }
    .badge.on { background: var(--success-bg); color: var(--success); }
    .badge.off { background: #f1f5f9; color: #475569; }

    /* ── Ações ── */
    .actions { display: flex; gap: .35rem; align-items: center; }
    .actions form { margin: 0; }

    /* ── Paginação ── */
    .pager { display: flex; align-items: center; gap: .5rem; margin-top: 1rem; font-size: .82rem; color: var(--muted); }
    .pager a { padding: .28rem .65rem; border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-weight: 600; text-decoration: none; }
    .pager a:hover { background: var(--secondary); }

    /* ── Section title ── */
    .section-title { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin: 1.5rem 0 .75rem; padding-bottom: .35rem; border-bottom: 1px solid var(--border); }
    .chk { display: flex; align-items: center; gap: .5rem; font-weight: 600; font-size: .875rem; cursor: pointer; }
    input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; }
  </style>
</head>
<body>
<?php if (!$hideTopbar): ?>
  <aside class="sidebar">
    <div class="sb-brand">
      <div class="sb-logo">⚡ MultiMenu</div>
      <div class="sb-sub">Super Admin</div>
    </div>
    <?php if (!empty($superAdminName)): ?>
      <div class="sb-user">
        <strong><?= htmlspecialchars((string)$superAdminName, ENT_QUOTES, 'UTF-8') ?></strong>
        Super Administrador
      </div>
    <?php endif; ?>
    <nav class="sb-nav">
      <div class="sb-section">Gestão MultiMenu</div>
      <a class="nav-item <?= ($_saUri === '/superadmin' || $_saUri === '/superadmin/') ? 'active' : '' ?>"
         href="<?= htmlspecialchars(base_url('superadmin'), ENT_QUOTES, 'UTF-8') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Lojas
      </a>
      <a class="nav-item <?= ($_saUri === '/superadmin/catalog') ? 'active' : '' ?>"
         href="<?= htmlspecialchars(base_url('superadmin/catalog'), ENT_QUOTES, 'UTF-8') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
        Cardápios e Produtos
      </a>
      <a class="nav-item <?= ($_saUri === '/superadmin/orders-live') ? 'active' : '' ?>"
         href="<?= htmlspecialchars(base_url('superadmin/orders-live'), ENT_QUOTES, 'UTF-8') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Pedidos em Tempo Real
      </a>
      <a class="nav-item <?= ($_saUri === '/superadmin/operators') ? 'active' : '' ?>"
         href="<?= htmlspecialchars(base_url('superadmin/operators'), ENT_QUOTES, 'UTF-8') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V9H2v11h5m10 0v-4a3 3 0 00-6 0v4m6 0H7"/></svg>
        Usuários e Operadores
      </a>
      <div class="sb-section">Provisionamento</div>
      <a class="nav-item <?= str_contains($_saUri, '/superadmin/companies') ? 'active' : '' ?>"
         href="<?= htmlspecialchars(base_url('superadmin/companies/create'), ENT_QUOTES, 'UTF-8') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Nova Loja
      </a>
      <div class="sb-section">Operação e Segurança</div>
      <a class="nav-item <?= ($_saUri === '/superadmin/observability') ? 'active' : '' ?>"
         href="<?= htmlspecialchars(base_url('superadmin/observability'), ENT_QUOTES, 'UTF-8') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h4l3 8 4-16 3 8h4"/></svg>
        Observabilidade
      </a>
      <a class="nav-item <?= ($_saUri === '/superadmin/events') ? 'active' : '' ?>"
         href="<?= htmlspecialchars(base_url('superadmin/events'), ENT_QUOTES, 'UTF-8') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-3 3-3-3z"/></svg>
        Eventos
      </a>
      <a class="nav-item <?= ($_saUri === '/superadmin/billing') ? 'active' : '' ?>"
         href="<?= htmlspecialchars(base_url('superadmin/billing'), ENT_QUOTES, 'UTF-8') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10v12m9-6a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Billing SaaS
      </a>
    </nav>
    <div class="sb-footer">
      <form method="post" action="<?= htmlspecialchars(base_url('superadmin/logout'), ENT_QUOTES, 'UTF-8') ?>">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <button type="submit" class="btn-logout">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Sair
        </button>
      </form>
    </div>
  </aside>

  <div class="main-wrap">
    <header class="topbar">
      <span class="topbar-title"><?= htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></span>
      <span class="topbar-time"><?php
        try { $__now = new DateTime('now', new DateTimeZone('America/Sao_Paulo')); echo htmlspecialchars($__now->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8'); } catch (Throwable $e) { echo ''; }
      ?></span>
    </header>
    <div class="shell">
<?php else: ?>
  <div class="main-wrap" style="margin-left:0;background:radial-gradient(circle at 50% 0%,#e2e8f0 0%,#f8fafc 45%);min-height:100vh;display:flex;align-items:center;justify-content:center;">
    <div style="width:100%;max-width:440px;padding:1.5rem;">
  <?php endif; ?>
