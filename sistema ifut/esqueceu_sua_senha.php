<?php
require_once 'includes/session.php';
require_once 'config/database.php';

$message = '';
$messageType = '';
$step = 1; // 1 = email, 2 = security question, 3 = new password

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step'])) {
        $step = (int)$_POST['step'];
        
        if ($step === 1) {
            // Verificar email
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email)) {
                $message = 'Digite seu email.';
                $messageType = 'danger';
            } else {
                try {
                    $db = new Database();
                    $stmt = $db->getConnection()->prepare("SELECT * FROM usuarios WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        $_SESSION['reset_user_id'] = $user['id_usuario'];
                        $_SESSION['reset_email'] = $email;
                        $step = 2;
                        $message = 'Email encontrado! Responda a pergunta de segurança.';
                        $messageType = 'success';
                    } else {
                        $message = 'Email não encontrado.';
                        $messageType = 'danger';
                    }
                } catch (PDOException $e) {
                    $message = 'Erro no sistema.';
                    $messageType = 'danger';
                }
            }
        } elseif ($step === 2) {
            // Verificar CPF como pergunta de segurança
            $cpf = trim($_POST['cpf'] ?? '');
            
            if (empty($cpf)) {
                $message = 'Digite seu CPF.';
                $messageType = 'danger';
            } else {
                try {
                    $db = new Database();
                    $stmt = $db->getConnection()->prepare("SELECT * FROM usuarios WHERE id_usuario = ? AND cpf = ?");
                    $stmt->execute([$_SESSION['reset_user_id'], $cpf]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        $step = 3;
                        $message = 'CPF correto! Agora defina sua nova senha.';
                        $messageType = 'success';
                    } else {
                        $message = 'CPF incorreto.';
                        $messageType = 'danger';
                        $step = 2;
                    }
                } catch (PDOException $e) {
                    $message = 'Erro no sistema.';
                    $messageType = 'danger';
                    $step = 2;
                }
            }
        } elseif ($step === 3) {
            // Alterar senha
            $nova_senha = $_POST['nova_senha'] ?? '';
            $confirmar_senha = $_POST['confirmar_senha'] ?? '';
            
            if (empty($nova_senha) || empty($confirmar_senha)) {
                $message = 'Preencha todos os campos.';
                $messageType = 'danger';
                $step = 3;
            } elseif ($nova_senha !== $confirmar_senha) {
                $message = 'Senhas não coincidem.';
                $messageType = 'danger';
                $step = 3;
            } elseif (strlen($nova_senha) < 6) {
                $message = 'Senha deve ter pelo menos 6 caracteres.';
                $messageType = 'danger';
                $step = 3;
            } else {
                try {
                    $db = new Database();
                    $stmt = $db->getConnection()->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
                    
                    if ($stmt->execute([$nova_senha, $_SESSION['reset_user_id']])) {
                        unset($_SESSION['reset_user_id']);
                        unset($_SESSION['reset_email']);
                        $message = 'Senha alterada com sucesso! Você pode fazer login agora.';
                        $messageType = 'success';
                        $step = 4; // Sucesso
                    } else {
                        $message = 'Erro ao alterar senha.';
                        $messageType = 'danger';
                        $step = 3;
                    }
                } catch (PDOException $e) {
                    $message = 'Erro no sistema.';
                    $messageType = 'danger';
                    $step = 3;
                }
            }
        }
    }
}

// Recuperar dados da sessão se existirem
if (isset($_SESSION['reset_user_id']) && $step === 1) {
    $step = 2;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - IFUT</title>
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
                                <i class="bi bi-key display-4 text-success"></i>
                                <h2 class="neon-text-white mt-3">Recuperar Senha</h2>
                                <p class="text-muted">
                                    <?php if ($step === 1): ?>
                                        Digite seu email para começar
                                    <?php elseif ($step === 2): ?>
                                        Confirme sua identidade
                                    <?php elseif ($step === 3): ?>
                                        Defina sua nova senha
                                    <?php else: ?>
                                        Senha alterada com sucesso!
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress mb-4" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?= ($step / 4) * 100 ?>%"></div>
                            </div>

                            <?php if ($message): ?>
                                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                    <?= $message ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($step === 1): ?>
                                <!-- Step 1: Email -->
                                <form method="POST">
                                    <input type="hidden" name="step" value="1">
                                    <div class="mb-4">
                                        <label for="email" class="form-label">
                                            <i class="bi bi-envelope"></i> Email
                                        </label>
                                        <input type="email" class="form-control neon-input" id="email" name="email" 
                                               placeholder="seu@gmail.com" required>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 neon-btn">
                                        <i class="bi bi-arrow-right"></i> Continuar
                                    </button>
                                </form>

                            <?php elseif ($step === 2): ?>
                                <!-- Step 2: Security Question -->
                                <form method="POST">
                                    <input type="hidden" name="step" value="2">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-envelope-check"></i> Email
                                        </label>
                                        <input type="text" class="form-control neon-input" value="<?= htmlspecialchars($_SESSION['reset_email']) ?>" disabled>
                                    </div>
                                    <div class="mb-4">
                                        <label for="cpf" class="form-label">
                                            <i class="bi bi-card-text"></i> Digite seu CPF para confirmar
                                        </label>
                                        <input type="text" class="form-control neon-input" id="cpf" name="cpf" 
                                               placeholder="000.000.000-00" required>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 neon-btn">
                                        <i class="bi bi-arrow-right"></i> Verificar
                                    </button>
                                </form>

                            <?php elseif ($step === 3): ?>
                                <!-- Step 3: New Password -->
                                <form method="POST">
                                    <input type="hidden" name="step" value="3">
                                    <div class="mb-3">
                                        <label for="nova_senha" class="form-label">
                                            <i class="bi bi-lock"></i> Nova Senha
                                        </label>
                                        <input type="password" class="form-control neon-input" id="nova_senha" name="nova_senha" required>
                                        <div class="form-text">Mínimo de 6 caracteres</div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="confirmar_senha" class="form-label">
                                            <i class="bi bi-lock-fill"></i> Confirmar Nova Senha
                                        </label>
                                        <input type="password" class="form-control neon-input" id="confirmar_senha" name="confirmar_senha" required>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 neon-btn">
                                        <i class="bi bi-check-circle"></i> Alterar Senha
                                    </button>
                                </form>

                            <?php else: ?>
                                <!-- Step 4: Success -->
                                <div class="text-center">
                                    <i class="bi bi-check-circle display-1 text-success mb-3"></i>
                                    <h4 class="neon-text-white mb-3">Senha Alterada!</h4>
                                    <p class="text-muted mb-4">Sua senha foi alterada com sucesso. Agora você pode fazer login com a nova senha.</p>
                                    <a href="login.php" class="btn btn-success neon-btn">
                                        <i class="bi bi-box-arrow-in-right"></i> Fazer Login
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="text-center mt-4">
                                <a href="login.php" class="text-muted">
                                    <i class="bi bi-arrow-left"></i> Voltar ao Login
                                </a>
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
