<?php
require_once 'includes/session.php';
require_once 'config/database.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $message = "Todos os campos são obrigatórios.";
        $messageType = "danger";
    } else {
        try {
            $db = new Database();
            $stmt = $db->getConnection()->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $password === $user["senha"]) {
                setUser($user);
                header("Location: tela_inicial.php");
                exit;
            } else {
                $message = "Email ou senha incorretos.";
                $messageType = "danger";
            }
        } catch (PDOException $e) {
            $message = "Erro no sistema. Tente novamente.";
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFUT - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-8">
                    <div class="card neon-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="bi bi-person-circle display-4 text-success"></i>
                                <h2 class="neon-text-white mt-3">Login</h2>
                                <p class="text-muted">Entre na sua conta</p>
                            </div>

                            <?php if ($message): ?>
                                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                    <?= $message ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="loginForm">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope"></i> Email
                                    </label>
                                    <input type="email" class="form-control neon-input" id="email" name="email" 
                                           placeholder="seu@gmail.com" value="<?= htmlspecialchars($email ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock"></i> Senha
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control neon-input" id="password" 
                                               name="password" placeholder="Sua senha" required>
                                        <button class="btn btn-outline-success" type="button" id="togglePassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100 neon-btn mb-3">
                                    <i class="bi bi-box-arrow-in-right"></i> Entrar
                                </button>
                            </form>
                            
                            <div class="text-center">
                                <a href="cadastro.php" class="text-success d-block mb-2">Não tem conta? Cadastre-se</a>
                                <a href="esqueceu_sua_senha.php" class="text-muted">Esqueceu a senha?</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
</body>
</html>
