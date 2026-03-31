<?php
session_start();

/* ================= LIGAÇÃO BASE DE DADOS ================= */
$ligacao = new mysqli("localhost", "root", "", "biblioteca_db");
if ($ligacao->connect_error) { die("Erro na ligação: " . $ligacao->connect_error); }

$mensagem = "";
$tipo_mensagem = "erro";

/* ================= REGISTO ================= */
if(isset($_POST["registar"])) {
    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if(empty($nome) || empty($email) || empty($password)) {
        $mensagem = "Todos os campos são obrigatórios.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Email inválido.";
    } elseif(strlen($password)<6) {
        $mensagem = "Password deve ter pelo menos 6 caracteres.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $ligacao->prepare("INSERT INTO utilizadores (nome,email,password) VALUES (?,?,?)");
        $stmt->bind_param("sss",$nome,$email,$password_hash);
        if($stmt->execute()) { 
            $mensagem = "Conta criada com sucesso."; 
            $tipo_mensagem = "sucesso";
        } else { 
            $mensagem = "Este email já está registado."; 
        }
    }
}

/* ================= LOGIN ================= */
if(isset($_POST["login"])) {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $ligacao->prepare("SELECT * FROM utilizadores WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if($resultado->num_rows == 1) {
        $user = $resultado->fetch_assoc();
        if(password_verify($password,$user["password"])) {
            $_SESSION["user"] = $user["nome"];
            $tipo_mensagem = "sucesso";
        } else { 
            $mensagem="Password incorreta."; 
        }
    } else { 
        $mensagem="Utilizador não encontrado."; 
    }
}

/* ================= LOGOUT ================= */
if(isset($_GET["logout"])) { 
    session_destroy(); 
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit; 
}

/* ================= CARRINHO ================= */
if(!isset($_SESSION["carrinho"])) { $_SESSION["carrinho"] = []; }

$res = $ligacao->query("SELECT * FROM livros");
$livros = [];
while($row = $res->fetch_assoc()) { $livros[$row['id']] = $row; }

/* ================= ADICIONAR / REMOVER ================= */
if(isset($_GET["add"]) && isset($livros[$_GET["add"]])) { 
    $_SESSION["carrinho"][] = $_GET["add"]; 
    $mensagem = "Adicionado ao carrinho";
    $tipo_mensagem = "sucesso";
}

if(isset($_GET["remove"]) && isset($livros[$_GET["remove"]])) {
    $key = array_search($_GET["remove"], $_SESSION["carrinho"]);
    if($key!==false){ 
        unset($_SESSION["carrinho"][$key]); 
        $mensagem = "Removido do carrinho";
        $tipo_mensagem = "sucesso";
    }
}

/* ================= CALCULA QUANTIDADES ================= */
$carrinho_count = [];
foreach($_SESSION["carrinho"] as $id) {
    if(isset($carrinho_count[$id])) $carrinho_count[$id]++;
    else $carrinho_count[$id]=1;
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>

    </style>
</head>

<body>
    <nav>
        <div class="nav-container">
            <div class="logo">BIBLIOTECA</div>
            <?php if(isset($_SESSION["user"])): ?>
            <ul class="nav-menu">
                <li><span class="user-name"><?= htmlspecialchars($_SESSION["user"]) ?></span></li>
                <li><a href="#catalogo">Catálogo</a></li>
                <li>
                    <a href="#carrinho" class="cart-link">
                        Carrinho
                        <?php if(count($_SESSION["carrinho"]) > 0): ?>
                        <span class="cart-badge"><?= count($_SESSION["carrinho"]) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="?logout=1" class="logout-link">Sair</a></li>
            </ul>
            <?php endif; ?>
        </div>
    </nav>

    <?php if($mensagem): ?>
    <div class="notification <?= $tipo_mensagem ?>" id="notification">
        <?= htmlspecialchars($mensagem) ?>
    </div>
    <script>
    setTimeout(() => {
        const notif = document.getElementById('notification');
        if (notif) {
            notif.style.opacity = '0';
            notif.style.transform = 'translateX(400px)';
            setTimeout(() => notif.remove(), 300);
        }
    }, 3500);
    </script>
    <?php endif; ?>

    <div class="container">

        <?php if(!isset($_SESSION["user"])): ?>
        <!-- Hero -->
        <div class="hero">
            <h1>Biblioteca Digital</h1>
            <p>A tua coleção de manga</p>
        </div>

        <!-- Auth forms -->
        <div class="auth-grid">
            <div class="auth-card">
                <h2>Entrar</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="seu@email.com" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="login" class="btn">Entrar</button>
                </form>
            </div>

            <div class="auth-card">
                <h2>Criar Conta</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="nome" placeholder="O teu nome" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="seu@email.com" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <button type="submit" name="registar" class="btn secondary">Criar Conta</button>
                </form>
            </div>
        </div>

        <?php else: ?>

        <!-- Catálogo -->
        <section id="catalogo">
            <div class="section-header">
                <h2>Catálogo</h2>
                <p>Explora a coleção</p>
            </div>

            <div class="manga-grid">
                <?php foreach($livros as $id => $livro): ?>
                <div class="manga-card">
                    <div class="manga-cover">
                        <img src="capas/<?= $id ?>.jpg" alt="<?= htmlspecialchars($livro['titulo']) ?>"
                            onerror="this.src='https://via.placeholder.com/200x290/1a1a1a/666666?text=Sem+Capa'">
                    </div>
                    <div class="manga-info">
                        <div class="manga-title"><?= htmlspecialchars($livro['titulo']) ?></div>
                        <div class="manga-author"><?= htmlspecialchars($livro['autor']) ?></div>
                        <div class="manga-price">€<?= number_format($livro['preco'], 2) ?></div>
                        <a href="?add=<?= $id ?>#catalogo" class="add-btn">Adicionar</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Carrinho -->
        <section id="carrinho">
            <div class="section-header">
                <h2>Carrinho</h2>
                <p>Itens selecionados</p>
            </div>

            <div class="cart-section">
                <?php if(count($carrinho_count) > 0): ?>
                <?php 
                $total = 0;
                foreach($carrinho_count as $id => $qtd): 
                    $subtotal = $livros[$id]['preco'] * $qtd;
                    $total += $subtotal;
                ?>
                <div class="cart-item">
                    <img src="capas/<?= $id ?>.jpg" alt="<?= htmlspecialchars($livros[$id]['titulo']) ?>"
                        class="cart-item-img"
                        onerror="this.src='https://via.placeholder.com/100x145/1a1a1a/666666?text=Sem+Capa'">
                    <div class="cart-item-info">
                        <h3><?= htmlspecialchars($livros[$id]['titulo']) ?></h3>
                        <div class="cart-item-details">
                            <span>€<?= number_format($livros[$id]['preco'], 2) ?></span>
                            <span class="cart-qty">× <?= $qtd ?></span>
                            <span>€<?= number_format($subtotal, 2) ?></span>
                        </div>
                    </div>
                    <a href="?remove=<?= $id ?>#carrinho" class="remove-btn">Remover</a>
                </div>
                <?php endforeach; ?>

                <div class="cart-total">
                    <h3>Total: €<?= number_format($total, 2) ?></h3>
                </div>

                <?php else: ?>
                <div class="empty-cart">
                    <h3>Carrinho vazio</h3>
                    <p>Adiciona alguns itens</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <?php endif; ?>

    </div>

    <script>
    </script>

</body>

</html>