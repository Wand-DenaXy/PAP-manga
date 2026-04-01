<?php
// ════════════════════════════════════════════════════════
//  MangaVerse — Configuração da Base de Dados
// ════════════════════════════════════════════════════════

define('DB_HOST', 'localhost');
define('DB_NAME', 'mangaverse_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Stripe API Keys (substituir por chaves reais em produção)
define('STRIPE_PUBLIC_KEY', 'pk_test_XXXXXXXXXXXXXXXXXXXX');
define('STRIPE_SECRET_KEY', 'sk_test_XXXXXXXXXXXXXXXXXXXX');

// Configurações gerais
define('SITE_NAME', 'MangaVerse');
define('SITE_URL', 'http://localhost/PAP-manga');

/**
 * Conexão PDO à base de dados
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro de conexão à base de dados.']);
            exit;
        }
    }
    return $pdo;
}

/**
 * Iniciar sessão se ainda não estiver ativa
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verificar se o utilizador está autenticado
 */
function isLoggedIn() {
    initSession();
    return isset($_SESSION['user_id']);
}

/**
 * Obter dados do utilizador autenticado
 */
function getLoggedUser() {
    initSession();
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'nome'  => $_SESSION['user_nome'],
        'email' => $_SESSION['user_email'],
        'role'  => $_SESSION['user_role']
    ];
}

// ─── RBAC ────────────────────────────────────────────────

/**
 * Verifica se o utilizador tem role admin
 */
function isAdmin(): bool {
    initSession();
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Verifica se o utilizador pode vender (vendedor ou admin)
 */
function isVendedor(): bool {
    initSession();
    return isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['vendedor', 'admin']);
}

/**
 * Redireciona para login se não autenticado
 */
function requireLogin(string $redirect = 'login.php'): void {
    initSession();
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Redireciona se não for admin
 */
function requireAdmin(string $base = ''): void {
    requireLogin($base . 'login.php');
    if (!isAdmin()) {
        header('Location: ' . $base . 'index.html?erro=sem_permissao');
        exit;
    }
}

/**
 * Requer um dos roles especificados (array de strings)
 */
function requireRole(array $roles, string $base = ''): void {
    requireLogin($base . 'login.php');
    if (!in_array($_SESSION['user_role'] ?? '', $roles)) {
        header('Location: ' . $base . 'index.html?erro=sem_permissao');
        exit;
    }
}

/**
 * Resposta JSON
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
