<?php
// ════════════════════════════════════════════════════════
//  MangaVerse — Navbar Partilhada com RBAC
//
//  Como usar em cada página:
//    $currentPage = 'marketplace';   // → loja | marketplace | contacto
//                                    //   suporte | carrinho | login | registo | admin
//    $basePath    = '';              // '' = raiz; '../' = pasta admin/
//    require_once 'assets/includes/navbar.php';  (ou caminho equivalente)
// ════════════════════════════════════════════════════════

// Valores default se a página não os definiu
$currentPage = $currentPage ?? '';
$basePath    = $basePath    ?? '';

// Utilizador atual (já carregado pelo require_once config/database.php na página)
$_nav_user = getLoggedUser();
$_nav_role = $_nav_user['role'] ?? '';

// Helper: classe active
function navActive(string $page, string $current): string {
    return $page === $current ? ' class="active"' : '';
}

// Cart count via PHP session (admin não precisa de carrinho)
$_nav_cartCount = 0;
if (isLoggedIn() && $_nav_role !== 'admin') {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COALESCE(SUM(quantidade),0) FROM carrinho WHERE utilizador_id = ?");
        $stmt->execute([$_nav_user['id']]);
        $_nav_cartCount = (int)$stmt->fetchColumn();
    } catch (Throwable $e) { $_nav_cartCount = 0; }
}
?>
<nav>
  <a href="<?= $basePath ?>index.html" class="nav-logo">
    <div class="logo-dot"></div>
    Manga<span>Verse</span>
  </a>

  <ul class="nav-links">
    <li><a href="<?= $basePath ?>index.html"<?= navActive('loja', $currentPage) ?>>Loja</a></li>
    <li><a href="<?= $basePath ?>marketplace.php"<?= navActive('marketplace', $currentPage) ?>>Marketplace</a></li>
    <li><a href="<?= $basePath ?>contacto.html"<?= navActive('contacto', $currentPage) ?>>Contacto</a></li>
    <li><a href="<?= $basePath ?>suporte.php"<?= navActive('suporte', $currentPage) ?>>Suporte</a></li>

    <?php if ($_nav_user): ?>
      <!-- ── Utilizador autenticado ── -->

      <?php if ($_nav_role === 'admin'): ?>
        <!-- Admin badge + link para painel -->
        <li>
          <a href="<?= $basePath ?>admin/index.php"<?= navActive('admin', $currentPage) ?> class="admin-link">
            ⚙ Painel Admin
          </a>
        </li>
      <?php endif; ?>

      <!-- Nome do utilizador com role indicator -->
      <li class="user-info-nav">
        <span class="user-badge">
          <?php if ($_nav_role === 'admin'): ?>
            🛡 <?= htmlspecialchars($_nav_user['nome']) ?>
          <?php elseif ($_nav_role === 'vendedor'): ?>
            🏪 <?= htmlspecialchars($_nav_user['nome']) ?>
          <?php else: ?>
            👤 <?= htmlspecialchars($_nav_user['nome']) ?>
          <?php endif; ?>
        </span>
        <span class="role-badge role-<?= $_nav_role ?>"><?= $_nav_role ?></span>
      </li>

      <!-- Carrinho — apenas para clientes e vendedores -->
      <?php if ($_nav_role !== 'admin'): ?>
        <li>
          <a href="<?= $basePath ?>carrinho.php"<?= navActive('carrinho', $currentPage) ?> class="cart-btn">
            🛒 Carrinho
            <span class="cart-count" id="nav-cart-count"><?= $_nav_cartCount ?></span>
          </a>
        </li>
      <?php endif; ?>

      <!-- Sair -->
      <li><a href="#" id="nav-logout-btn" class="nav-logout">Sair</a></li>

    <?php else: ?>
      <!-- ── Visitante (não autenticado) ── -->
      <li><a href="<?= $basePath ?>login.php"<?= navActive('login', $currentPage) ?>>Login</a></li>
      <li><a href="<?= $basePath ?>registo.php"<?= navActive('registo', $currentPage) ?> class="nav-register-btn">Registar</a></li>
    <?php endif; ?>
  </ul>
</nav>

<!-- CSS da navbar (injeta uma vez) -->
<?php if (!defined('NAVBAR_STYLES_LOADED')): define('NAVBAR_STYLES_LOADED', true); ?>
<style>
  nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
    background: rgba(255,255,255,0.92); backdrop-filter: blur(16px);
    border-bottom: 1.5px solid #ececec;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 48px; height: 72px;
  }
  .nav-logo {
    font-family: 'Orbitron', sans-serif; font-size: 1.35rem; font-weight: 900;
    letter-spacing: 0.08em; color: #0a0a0a; text-decoration: none;
    display: flex; align-items: center; gap: 10px;
  }
  .nav-logo span { color: #e8002d; }
  .logo-dot {
    width: 8px; height: 8px; background: #e8002d; border-radius: 50%;
    animation: navDotPulse 1.8s infinite;
  }
  @keyframes navDotPulse {
    0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(1.5)}
  }
  .nav-links {
    display: flex; align-items: center; gap: 28px; list-style: none; flex-wrap: nowrap;
  }
  .nav-links a {
    font-family: 'Space Mono', monospace; font-size: 0.72rem;
    letter-spacing: 0.15em; text-transform: uppercase;
    color: #8a8a8a; text-decoration: none; transition: color 0.2s;
  }
  .nav-links a:hover { color: #0a0a0a; }
  .nav-links a.active { color: #0a0a0a; font-weight: 700; }

  /* Admin link */
  .admin-link {
    color: #e8002d !important; border: 1.5px solid #e8002d;
    padding: 6px 14px; border-radius: 4px;
  }
  .admin-link:hover { background: #e8002d; color: #fff !important; }

  /* User info block */
  .user-info-nav { display: flex; align-items: center; gap: 6px; }
  .user-badge {
    font-family: 'Space Mono', monospace; font-size: 0.68rem;
    color: #0a0a0a; letter-spacing: 0.08em;
  }
  .role-badge {
    font-family: 'Space Mono', monospace; font-size: 0.55rem;
    letter-spacing: 0.12em; text-transform: uppercase;
    padding: 2px 7px; border-radius: 100px;
  }
  .role-admin   { background: #ffd6d6; color: #e8002d; }
  .role-vendedor{ background: #d6e8ff; color: #0057ff; }
  .role-cliente { background: #ececec; color: #555; }

  /* Cart button */
  .cart-btn {
    display: flex; align-items: center; gap: 8px;
    background: #0a0a0a; color: #fff !important;
    padding: 9px 18px; border-radius: 4px;
    font-family: 'Space Mono', monospace !important;
    font-size: 0.7rem !important; letter-spacing: 0.12em;
    text-transform: uppercase; text-decoration: none;
    transition: background 0.2s, transform 0.15s;
  }
  .cart-btn:hover { background: #e8002d !important; transform: translateY(-1px); }
  .cart-count {
    background: #e8002d; color: #fff; border-radius: 50%;
    width: 18px; height: 18px; font-size: 0.62rem;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Space Mono', monospace; font-weight: 700;
  }

  /* Register button */
  .nav-register-btn {
    background: #e8002d; color: #fff !important;
    padding: 9px 18px; border-radius: 4px;
    border: 1.5px solid #e8002d;
  }
  .nav-register-btn:hover { background: #0a0a0a !important; border-color: #0a0a0a; }

  /* Logout */
  .nav-logout { color: #8a8a8a !important; }
  .nav-logout:hover { color: #e8002d !important; }

  @media (max-width: 900px) {
    nav { padding: 0 20px; }
    .nav-links { gap: 16px; }
    .nav-links a { font-size: 0.62rem; }
  }
</style>
<?php endif; ?>

<!-- JS: logout + actualização do contador do carrinho -->
<script>
(function($) {
  'use strict';
  $(function() {
    // ── Logout
    $('#nav-logout-btn').on('click', function(e) {
      e.preventDefault();
      $.get('<?= $basePath ?>assets/controller/controllerAuth.php', { acao: 'logout' }, function() {
        window.location.href = '<?= $basePath ?>login.php';
      }, 'json').fail(function() {
        window.location.href = '<?= $basePath ?>login.php';
      });
    });

    <?php if ($_nav_user && $_nav_role !== 'admin'): ?>
    // ── Actualizar contador do carrinho via AJAX
    $.get('<?= $basePath ?>assets/controller/controllerCarrinho.php', { acao: 'contar' }, function(res) {
      if (res && res.success) {
        $('#nav-cart-count').text(res.total_itens);
      }
    }, 'json');
    <?php endif; ?>
  });
})(jQuery);
</script>
