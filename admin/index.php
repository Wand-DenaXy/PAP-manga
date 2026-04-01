<?php
require_once '../assets/config/database.php';
initSession();
requireAdmin('../');

$currentPage = 'admin';
$basePath    = '../';

$pdo = getDB();

// ── Estatísticas ──────────────────────────────────────────────
$stats = [];
foreach ([
    'utilizadores'    => 'SELECT COUNT(*) FROM utilizadores',
    'produtos'        => 'SELECT COUNT(*) FROM produtos',
    'encomendas'      => 'SELECT COUNT(*) FROM encomendas',
    'tickets'         => 'SELECT COUNT(*) FROM suporte_tickets',
    'contactos'       => 'SELECT COUNT(*) FROM contactos',
    'receita'         => 'SELECT COALESCE(SUM(total), 0) FROM encomendas WHERE estado != "cancelada"',
] as $key => $sql) {
    $stats[$key] = $pdo->query($sql)->fetchColumn();
}

// ── Últimas encomendas ─────────────────────────────────────────
$ultimasEncomendas = $pdo->query(
    'SELECT e.id, u.nome AS cliente, e.total, e.estado, e.criado_em
     FROM encomendas e
     JOIN utilizadores u ON u.id = e.utilizador_id
     ORDER BY e.criado_em DESC LIMIT 8'
)->fetchAll(PDO::FETCH_ASSOC);

// ── Últimos tickets abertos ────────────────────────────────────
$ultimosTickets = $pdo->query(
    'SELECT t.id, u.nome AS utilizador, t.assunto, t.estado, t.criado_em
     FROM suporte_tickets t
     JOIN utilizadores u ON u.id = t.utilizador_id
     WHERE t.estado = "aberto"
     ORDER BY t.criado_em DESC LIMIT 6'
)->fetchAll(PDO::FETCH_ASSOC);

// ── Últimas mensagens de contacto ─────────────────────────────
$ultimosContactos = $pdo->query(
    'SELECT id, nome, email, assunto, criado_em
     FROM contactos
     ORDER BY criado_em DESC LIMIT 6'
)->fetchAll(PDO::FETCH_ASSOC);

require_once '../assets/includes/navbar.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Painel Admin — MangaVerse</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Noto+Sans+JP:wght@300;400;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
    :root {
      --white: #ffffff; --off-white: #f7f7f5; --black: #0a0a0a;
      --accent: #e8002d; --accent2: #0057ff; --grey: #8a8a8a;
      --light-grey: #ececec; --card-border: #e0e0e0;
      --glow: rgba(232,0,45,0.18);
      --font-display: 'Orbitron', sans-serif;
      --font-body: 'Noto Sans JP', sans-serif;
      --font-mono: 'Space Mono', monospace;
    }
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body { font-family: var(--font-body); background: var(--off-white); color: var(--black); min-height: 100vh; display: flex; flex-direction: column; }

    /* ─── ADMIN LAYOUT ─── */
    .admin-wrap { padding-top: 72px; flex: 1; }

    .admin-header {
      background: var(--black); color: white; padding: 48px 64px 40px;
      position: relative; overflow: hidden;
    }
    .admin-header::before {
      content: 'ADMIN'; position: absolute; right: -20px; top: 50%;
      transform: translateY(-50%); font-family: var(--font-display);
      font-size: 9rem; font-weight: 900; color: rgba(255,255,255,0.04);
      pointer-events: none; letter-spacing: -4px;
    }
    .admin-eyebrow {
      font-family: var(--font-mono); font-size: 0.65rem; letter-spacing: 0.22em;
      text-transform: uppercase; color: var(--accent); margin-bottom: 12px;
      display: flex; align-items: center; gap: 10px;
    }
    .admin-eyebrow::before { content: ''; width: 24px; height: 1.5px; background: var(--accent); }
    .admin-header h1 {
      font-family: var(--font-display); font-size: clamp(1.6rem, 3vw, 2.4rem);
      font-weight: 900; margin-bottom: 6px;
    }
    .admin-header p { color: rgba(255,255,255,0.45); font-size: 0.9rem; }

    /* ─── STATS GRID ─── */
    .stats-section { padding: 48px 64px 0; }
    .stats-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;
    }
    .stat-card {
      background: var(--white); border: 1.5px solid var(--card-border); border-radius: 12px;
      padding: 28px 24px; position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.08); }
    .stat-card.accent { border-color: var(--accent); background: #fff5f6; }
    .stat-icon { font-size: 1.8rem; margin-bottom: 14px; }
    .stat-value {
      font-family: var(--font-display); font-size: 2rem; font-weight: 900;
      line-height: 1; margin-bottom: 6px;
    }
    .stat-card.accent .stat-value { color: var(--accent); }
    .stat-label {
      font-family: var(--font-mono); font-size: 0.62rem; letter-spacing: 0.15em;
      text-transform: uppercase; color: var(--grey);
    }
    .stat-link {
      position: absolute; bottom: 16px; right: 20px;
      font-family: var(--font-mono); font-size: 0.58rem; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--accent); text-decoration: none;
      transition: opacity 0.2s; opacity: 0.7;
    }
    .stat-link:hover { opacity: 1; }

    /* ─── TABLES SECTION ─── */
    .tables-section { padding: 40px 64px 64px; }
    .tables-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 28px; }
    .panel-card {
      background: var(--white); border: 1.5px solid var(--card-border); border-radius: 12px; overflow: hidden;
    }
    .panel-card-header {
      padding: 20px 24px 18px; border-bottom: 1.5px solid var(--light-grey);
      display: flex; align-items: center; justify-content: space-between;
    }
    .panel-card-title {
      font-family: var(--font-mono); font-size: 0.68rem; letter-spacing: 0.18em;
      text-transform: uppercase; color: var(--black); font-weight: 700;
    }
    .panel-card-more {
      font-family: var(--font-mono); font-size: 0.58rem; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--accent); text-decoration: none;
      transition: opacity 0.2s; opacity: 0.7;
    }
    .panel-card-more:hover { opacity: 1; }
    .panel-table { width: 100%; border-collapse: collapse; }
    .panel-table th {
      font-family: var(--font-mono); font-size: 0.58rem; letter-spacing: 0.15em;
      text-transform: uppercase; color: var(--grey); padding: 12px 24px 10px;
      text-align: left; border-bottom: 1px solid var(--light-grey);
    }
    .panel-table td { padding: 12px 24px; font-size: 0.88rem; border-bottom: 1px solid var(--off-white); vertical-align: middle; }
    .panel-table tr:last-child td { border-bottom: none; }
    .panel-table tr:hover td { background: var(--off-white); }
    .estado-badge {
      display: inline-block; padding: 3px 10px; border-radius: 20px;
      font-family: var(--font-mono); font-size: 0.58rem; letter-spacing: 0.1em; text-transform: uppercase;
    }
    .estado-pendente  { background: #fff3cd; color: #856404; }
    .estado-pago      { background: #d1e7dd; color: #0f5132; }
    .estado-enviado   { background: #cfe2ff; color: #084298; }
    .estado-entregue  { background: #d1e7dd; color: #0f5132; }
    .estado-cancelada { background: #f8d7da; color: #842029; }
    .estado-aberto    { background: #fff3cd; color: #856404; }
    .estado-em_analise{ background: #cfe2ff; color: #084298; }
    .estado-resolvido { background: #d1e7dd; color: #0f5132; }
    .td-mono { font-family: var(--font-mono); font-size: 0.78rem; color: var(--grey); }

    /* ─── CONTACTOS SECTION ─── */
    .contactos-section { padding: 0 64px 64px; }
    .contactos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
    .contacto-card {
      background: var(--white); border: 1.5px solid var(--card-border); border-radius: 10px;
      padding: 20px 22px;
    }
    .contacto-card .nome { font-weight: 700; font-size: 0.92rem; margin-bottom: 2px; }
    .contacto-card .email { font-family: var(--font-mono); font-size: 0.62rem; color: var(--grey); margin-bottom: 8px; }
    .contacto-card .assunto { font-size: 0.85rem; color: var(--black); margin-bottom: 6px; }
    .contacto-card .data { font-family: var(--font-mono); font-size: 0.58rem; color: var(--grey); letter-spacing: 0.08em; }

    /* ─── EMPTY STATE ─── */
    .empty-row td { text-align: center; color: var(--grey); font-size: 0.82rem; padding: 28px; }

    @media (max-width: 1100px) {
      .admin-header, .stats-section, .tables-section, .contactos-section { padding-left: 24px; padding-right: 24px; }
      .tables-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>

<div class="admin-wrap">

  <!-- ─── HEADER ─── -->
  <div class="admin-header">
    <div class="admin-eyebrow">// Painel de Controlo</div>
    <h1>Admin Dashboard</h1>
    <p>Bem-vindo, <?= htmlspecialchars($_SESSION['user_nome']) ?>. Aqui tens um resumo da plataforma.</p>
  </div>

  <!-- ─── STATS ─── -->
  <section class="stats-section">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= number_format($stats['utilizadores']) ?></div>
        <div class="stat-label">Utilizadores</div>
        <a href="#" class="stat-link">Ver todos →</a>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📚</div>
        <div class="stat-value"><?= number_format($stats['produtos']) ?></div>
        <div class="stat-label">Produtos</div>
        <a href="../marketplace.php" class="stat-link">Ver loja →</a>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-value"><?= number_format($stats['encomendas']) ?></div>
        <div class="stat-label">Encomendas</div>
        <a href="#" class="stat-link">Ver todas →</a>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🎫</div>
        <div class="stat-value"><?= number_format($stats['tickets']) ?></div>
        <div class="stat-label">Tickets Suporte</div>
        <a href="../suporte.php" class="stat-link">Ver suporte →</a>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✉️</div>
        <div class="stat-value"><?= number_format($stats['contactos']) ?></div>
        <div class="stat-label">Contactos</div>
        <a href="../contacto.html" class="stat-link">Ver formulário →</a>
      </div>
      <div class="stat-card accent">
        <div class="stat-icon">💶</div>
        <div class="stat-value"><?= number_format($stats['receita'], 2, ',', '.') ?>€</div>
        <div class="stat-label">Receita Total</div>
      </div>
    </div>
  </section>

  <!-- ─── ENCOMENDAS + TICKETS ─── -->
  <section class="tables-section">
    <div class="tables-grid">

      <!-- Últimas encomendas -->
      <div class="panel-card">
        <div class="panel-card-header">
          <span class="panel-card-title">// Últimas Encomendas</span>
          <a href="#" class="panel-card-more">Ver todas →</a>
        </div>
        <table class="panel-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($ultimasEncomendas)): ?>
              <tr class="empty-row"><td colspan="5">Sem encomendas ainda.</td></tr>
            <?php else: foreach ($ultimasEncomendas as $enc): ?>
              <tr>
                <td class="td-mono">#<?= $enc['id'] ?></td>
                <td><?= htmlspecialchars($enc['cliente']) ?></td>
                <td class="td-mono"><?= number_format($enc['total'], 2, ',', '.') ?>€</td>
                <td><span class="estado-badge estado-<?= htmlspecialchars($enc['estado']) ?>"><?= htmlspecialchars($enc['estado']) ?></span></td>
                <td class="td-mono"><?= date('d/m/y', strtotime($enc['criado_em'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Tickets abertos -->
      <div class="panel-card">
        <div class="panel-card-header">
          <span class="panel-card-title">// Tickets Abertos</span>
          <a href="../suporte.php" class="panel-card-more">Ver suporte →</a>
        </div>
        <table class="panel-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Utilizador</th>
              <th>Assunto</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($ultimosTickets)): ?>
              <tr class="empty-row"><td colspan="4">Nenhum ticket aberto.</td></tr>
            <?php else: foreach ($ultimosTickets as $t): ?>
              <tr>
                <td class="td-mono">#<?= $t['id'] ?></td>
                <td><?= htmlspecialchars($t['utilizador']) ?></td>
                <td><?= htmlspecialchars(mb_strimwidth($t['assunto'], 0, 30, '…')) ?></td>
                <td class="td-mono"><?= date('d/m/y', strtotime($t['criado_em'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </section>

  <!-- ─── CONTACTOS ─── -->
  <?php if (!empty($ultimosContactos)): ?>
  <section class="contactos-section">
    <div class="panel-card-header" style="padding: 0 0 16px;">
      <span class="panel-card-title">// Últimas Mensagens de Contacto</span>
    </div>
    <div class="contactos-grid">
      <?php foreach ($ultimosContactos as $c): ?>
      <div class="contacto-card">
        <div class="nome"><?= htmlspecialchars($c['nome']) ?></div>
        <div class="email"><?= htmlspecialchars($c['email']) ?></div>
        <div class="assunto"><?= htmlspecialchars($c['assunto']) ?></div>
        <div class="data"><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

</div><!-- /.admin-wrap -->

<!-- ─── FOOTER ─── -->
<footer style="background:var(--black);color:white;padding:28px 64px;display:flex;align-items:center;justify-content:space-between;margin-top:auto;">
  <span style="font-family:var(--font-display);font-size:1rem;font-weight:900;letter-spacing:0.08em;">Manga<span style="color:#e8002d;">Verse</span></span>
  <span style="font-family:var(--font-mono);font-size:0.6rem;letter-spacing:0.1em;color:rgba(255,255,255,0.3);">PAINEL ADMIN — ACESSO RESTRITO</span>
</footer>

</body>
</html>
