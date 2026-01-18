<?php
require_once 'includes/session.php';
require_once 'config/database.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = getUser();
$message = '';
$messageType = '';

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $posicao = $_POST['posicao'] ?? '';
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações básicas
    if (empty($nome) || empty($email) || empty($telefone)) {
        $message = 'Nome, email e telefone são obrigatórios.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email inválido.';
        $messageType = 'danger';
    } elseif (empty($posicao)) {
        $message = 'Selecione pelo menos uma posição.';
        $messageType = 'danger';
    } else {
        try {
            $db = new Database();
            
            // Verificar se o email já existe para outro usuário
            $stmt = $db->getConnection()->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
            $stmt->execute([$email, $user['id_usuario']]);
            
            if ($stmt->fetch()) {
                $message = 'Este email já está sendo usado por outro usuário.';
                $messageType = 'danger';
            } else {
                // Se foi fornecida nova senha, validar
                if (!empty($nova_senha)) {
                    if (empty($senha_atual)) {
                        $message = 'Informe sua senha atual para alterar a senha.';
                        $messageType = 'danger';
                    } elseif (!password_verify($senha_atual, $user['senha'])) {
                        $message = 'Senha atual incorreta.';
                        $messageType = 'danger';
                    } elseif (strlen($nova_senha) < 6) {
                        $message = 'A nova senha deve ter pelo menos 6 caracteres.';
                        $messageType = 'danger';
                    } elseif ($nova_senha !== $confirmar_senha) {
                        $message = 'A confirmação da nova senha não confere.';
                        $messageType = 'danger';
                    } else {
                        // Atualizar com nova senha
                        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                        $stmt = $db->getConnection()->prepare("
                            UPDATE usuarios 
                            SET nome = ?, email = ?, telefone = ?, posicao = ?, senha = ?
                            WHERE id_usuario = ?
                        ");
                        
                        if ($stmt->execute([$nome, $email, $telefone, $posicao, $senha_hash, $user['id_usuario']])) {
                            // Atualizar sessão
                            $_SESSION['user']['nome'] = $nome;
                            $_SESSION['user']['email'] = $email;
                            $_SESSION['user']['telefone'] = $telefone;
                            $_SESSION['user']['posicao'] = $posicao;
                            $_SESSION['user']['senha'] = $senha_hash;
                            
                            $message = 'Perfil e senha atualizados com sucesso!';
                            $messageType = 'success';
                        } else {
                            $message = 'Erro ao atualizar perfil.';
                            $messageType = 'danger';
                        }
                    }
                } else {
                    // Atualizar sem alterar senha
                    $stmt = $db->getConnection()->prepare("
                        UPDATE usuarios 
                        SET nome = ?, email = ?, telefone = ?, posicao = ?
                        WHERE id_usuario = ?
                    ");
                    
                    if ($stmt->execute([$nome, $email, $telefone, $posicao, $user['id_usuario']])) {
                        // Atualizar sessão
                        $_SESSION['user']['nome'] = $nome;
                        $_SESSION['user']['email'] = $email;
                        $_SESSION['user']['telefone'] = $telefone;
                        $_SESSION['user']['posicao'] = $posicao;
                        
                        $message = 'Perfil atualizado com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Erro ao atualizar perfil.';
                        $messageType = 'danger';
                    }
                }
            }
        } catch (PDOException $e) {
            $message = 'Erro no sistema.';
            $messageType = 'danger';
        }
    }
    
    // Recarregar dados do usuário
    $user = getUser();
}

// Buscar estatísticas do usuário
try {
    $db = new Database();
    
    // Estatísticas gerais
    $stmt = $db->getConnection()->prepare("
        SELECT 
            (SELECT COUNT(*) FROM partidas WHERE id_usuario = ?) as partidas_criadas,
            (SELECT COUNT(*) FROM marcacoes WHERE id_usuario = ?) as partidas_participadas,
            (SELECT COUNT(*) FROM partidas p JOIN marcacoes m ON p.id_partida = m.id_partida 
             WHERE m.id_usuario = ? AND (p.data < CURDATE() OR (p.data = CURDATE() AND p.horario <= CURTIME()))) as partidas_jogadas,
            (SELECT COUNT(*) FROM solicitacoes_participacao WHERE id_usuario = ? AND status = 'pendente') as solicitacoes_pendentes
    ");
    $stmt->execute([$user['id_usuario'], $user['id_usuario'], $user['id_usuario'], $user['id_usuario']]);
    $stats = $stmt->fetch();
    
    // Avaliações como jogador
    $stmt = $db->getConnection()->prepare("
        SELECT AVG(nota) as media_jogador, COUNT(*) as total_avaliacoes_jogador
        FROM avaliacoes_jogador 
        WHERE id_jogador = ?
    ");
    $stmt->execute([$user['id_usuario']]);
    $avaliacoes_jogador = $stmt->fetch();
    
    // Avaliações como criador
    $stmt = $db->getConnection()->prepare("
        SELECT AVG(nota) as media_criador, COUNT(*) as total_avaliacoes_criador
        FROM avaliacoes_criador 
        WHERE id_criador = ?
    ");
    $stmt->execute([$user['id_usuario']]);
    $avaliacoes_criador = $stmt->fetch();
    
    // Últimas partidas participadas
    $stmt = $db->getConnection()->prepare("
        SELECT p.data, p.horario, p.turno, p.cidade, p.estado, m.posicao, u.nome as criador_nome
        FROM marcacoes m
        JOIN partidas p ON m.id_partida = p.id_partida
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE m.id_usuario = ?
        ORDER BY p.data DESC, p.horario DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id_usuario']]);
    $ultimas_partidas = $stmt->fetchAll();
    
    // Últimas partidas criadas
    $stmt = $db->getConnection()->prepare("
        SELECT p.data, p.horario, p.turno, p.cidade, p.estado,
               (SELECT COUNT(*) FROM marcacoes m WHERE m.id_partida = p.id_partida) as total_jogadores
        FROM partidas p
        WHERE p.id_usuario = ?
        ORDER BY p.data DESC, p.horario DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id_usuario']]);
    $ultimas_criadas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $stats = ['partidas_criadas' => 0, 'partidas_participadas' => 0, 'partidas_jogadas' => 0, 'solicitacoes_pendentes' => 0];
    $avaliacoes_jogador = ['media_jogador' => null, 'total_avaliacoes_jogador' => 0];
    $avaliacoes_criador = ['media_criador' => null, 'total_avaliacoes_criador' => 0];
    $ultimas_partidas = [];
    $ultimas_criadas = [];
}

// Função para converter posição abreviada para nome completo
function getNomePosicao($posicao) {
    $posicoes = [
        'GOL' => 'GOLEIRO',
        'ZAG' => 'ZAGUEIRO', 
        'ALA ESQ' => 'ALA ESQUERDO',
        'ALA DIR' => 'ALA DIREITO',
        'VOL' => 'VOLANTE',
        'MEI' => 'MEIA',
        'ATA' => 'ATACANTE'
    ];
    
    return isset($posicoes[$posicao]) ? $posicoes[$posicao] : strtoupper($posicao);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="CSS/campo-futebol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
        .perfil-card {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .stats-card {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #007bff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #00ff00;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }
        
        .stats-label {
            color: #ffffff;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .avaliacoes-card {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .rating-display {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .rating-display .star {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .historico-card {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #17a2b8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .partida-item {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .partida-item:last-child {
            margin-bottom: 0;
        }
        
        .info-item {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .info-item i {
            color: #00ff00;
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .section-title {
            color: #00ff00;
            font-weight: bold;
            position: relative;
            margin-bottom: 25px;
            padding-bottom: 10px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: #00ff00;
        }
        
        .campo-futebol-container {
            width: 100%;
            height: 300px;
            border-radius: 5px;
            position: relative;
            margin: 20px 0;
        }

        .campo-futebol {
            max-width: 100%;
            height: 300px;
            margin: 0 auto;
        }
        
        .btn-atualizar {
            background-color: #00ff00;
            color: #000;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            width: 100%;
            font-size: 1.1rem;
        }
        
        .btn-atualizar:hover {
            background-color: #00cc00;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 255, 0, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #888;
            font-style: italic;
        }
        
        .badge-posicao {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-right: 5px;
        }
        
        .posicoes-usuario {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        
        .avatar-section {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .avatar-icon {
            font-size: 4rem;
            color: #00ff00;
            background: rgba(0, 255, 0, 0.1);
            border: 2px solid #00ff00;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .user-name {
            color: #00ff00;
            font-size: 1.5rem;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }
        
        .user-email {
            color: #ccc;
            font-size: 1rem;
        }
        
        .alert-pendente {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container py-4">
            <div class="row">
                <div class="col-12">
                    <h2 class="neon-text mb-4">
                        <i class="bi bi-person-circle"></i> Meu Perfil
                    </h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($stats['solicitacoes_pendentes'] > 0): ?>
                        <div class="alert-pendente">
                            <i class="bi bi-clock"></i>
                            <strong>Você tem <?= $stats['solicitacoes_pendentes'] ?> solicitação(ões) de participação pendente(s)!</strong>
                            <br>Acesse <a href="partidas_marcadas.php" style="color: #ffc107; text-decoration: underline;">Partidas Marcadas</a> para acompanhar o status.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row">
                <!-- Coluna da Esquerda - Dados do Perfil -->
                <div class="col-md-6">
                    <!-- Informações Básicas -->
                    <div class="perfil-card">
                        <div class="avatar-section">
                            <div class="avatar-icon">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="user-name"><?= htmlspecialchars($user['nome']) ?></div>
                            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        
                        <div class="info-item">
                            <i class="bi bi-phone"></i>
                            <span><?= htmlspecialchars($user['telefone']) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <i class="bi bi-calendar-plus"></i>
                            <span>Nascido em <?= date('d/m/Y', strtotime($user['data_nascimento'])) ?></span>
                        </div>
                        
                        <div class="info-item">
                            <i class="bi bi-dribbble"></i>
                            <div>
                                <span>Posições que joga:</span>
                                <div class="posicoes-usuario">
                                    <?php 
                                    $posicoes = explode(',', $user['posicao']);
                                    foreach ($posicoes as $pos): 
                                    ?>
                                        <span class="badge-posicao"><?= trim($pos) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campo de Futebol com Posições do Usuário -->
                        <h6 class="text-success mt-3 mb-2">
                            <i class="bi bi-diagram-3"></i> Suas Posições no Campo
                        </h6>
                        <div id="campo-perfil" class="campo-futebol-container">
                            <div class="campo-futebol"></div>
                        </div>
                    </div>
                    
                    <!-- Avaliações -->
                    <div class="avaliacoes-card">
                        <h5 class="text-warning mb-3">
                            <i class="bi bi-star"></i> Suas Avaliações
                        </h5>
                        
                        <div class="row">
                            <div class="col-6">
                                <h6 class="text-info">Como Jogador</h6>
                                <?php if ($avaliacoes_jogador['total_avaliacoes_jogador'] > 0): ?>
                                    <div class="rating-display">
                                        <?php 
                                        $media = round($avaliacoes_jogador['media_jogador']);
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <i class="bi bi-star<?= $i <= $media ? '-fill' : '' ?> star"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2"><?= number_format($avaliacoes_jogador['media_jogador'], 1) ?>/5</span>
                                    </div>
                                    <small class="text-muted"><?= $avaliacoes_jogador['total_avaliacoes_jogador'] ?> avaliação(ões)</small>
                                <?php else: ?>
                                    <p class="text-muted small">Ainda não foi avaliado como jogador</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-6">
                                <h6 class="text-info">Como Criador</h6>
                                <?php if ($avaliacoes_criador['total_avaliacoes_criador'] > 0): ?>
                                    <div class="rating-display">
                                        <?php 
                                        $media = round($avaliacoes_criador['media_criador']);
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <i class="bi bi-star<?= $i <= $media ? '-fill' : '' ?> star"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2"><?= number_format($avaliacoes_criador['media_criador'], 1) ?>/5</span>
                                    </div>
                                    <small class="text-muted"><?= $avaliacoes_criador['total_avaliacoes_criador'] ?> avaliação(ões)</small>
                                <?php else: ?>
                                    <p class="text-muted small">Ainda não foi avaliado como criador</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Coluna da Direita - Estatísticas e Histórico -->
                <div class="col-md-6">
                    <!-- Estatísticas -->
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-number"><?= $stats['partidas_criadas'] ?></div>
                                <div class="stats-label">Partidas Criadas</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-number"><?= $stats['partidas_participadas'] ?></div>
                                <div class="stats-label">Partidas Marcadas</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-number"><?= $stats['partidas_jogadas'] ?></div>
                                <div class="stats-label">Partidas Jogadas</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-number"><?= $stats['solicitacoes_pendentes'] ?></div>
                                <div class="stats-label">Solicitações Pendentes</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Últimas Partidas Participadas -->
                    <div class="historico-card">
                        <h5 class="text-info mb-3">
                            <i class="bi bi-clock-history"></i> Últimas Partidas Participadas
                        </h5>
                        
                        <?php if (empty($ultimas_partidas)): ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <p>Nenhuma partida participada ainda</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($ultimas_partidas as $partida): ?>
                                <div class="partida-item">
                                    <div class="info-item">
                                        <i class="bi bi-calendar"></i>
                                        <span><?= date('d/m/Y', strtotime($partida['data'])) ?> - <?= htmlspecialchars($partida['turno']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-geo-alt"></i>
                                        <span><?= htmlspecialchars($partida['cidade']) ?> - <?= htmlspecialchars($partida['estado']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-dribbble"></i>
                                        <span>Posição: <strong><?= getNomePosicao($partida['posicao']) ?></strong></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-person"></i>
                                        <span>Criada por <?= htmlspecialchars($partida['criador_nome']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Últimas Partidas Criadas -->
                    <div class="historico-card">
                        <h5 class="text-info mb-3">
                            <i class="bi bi-plus-circle"></i> Últimas Partidas Criadas
                        </h5>
                        
                        <?php if (empty($ultimas_criadas)): ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-plus"></i>
                                <p>Nenhuma partida criada ainda</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($ultimas_criadas as $partida): ?>
                                <div class="partida-item">
                                    <div class="info-item">
                                        <i class="bi bi-calendar"></i>
                                        <span><?= date('d/m/Y', strtotime($partida['data'])) ?> - <?= htmlspecialchars($partida['turno']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-geo-alt"></i>
                                        <span><?= htmlspecialchars($partida['cidade']) ?> - <?= htmlspecialchars($partida['estado']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-people"></i>
                                        <span><?= $partida['total_jogadores'] ?> jogador(es) participaram</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Formulário de Edição -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="perfil-card">
                        <h3 class="section-title">Editar Perfil</h3>
                        
                        <form method="POST">
                            <input type="hidden" name="atualizar_perfil" value="1">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label text-success">
                                        <i class="bi bi-person"></i> Nome Completo
                                    </label>
                                    <input type="text" class="form-control neon-input" id="nome" name="nome" 
                                           value="<?= htmlspecialchars($user['nome']) ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label text-success">
                                        <i class="bi bi-envelope"></i> Email
                                    </label>
                                    <input type="email" class="form-control neon-input" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telefone" class="form-label text-success">
                                        <i class="bi bi-phone"></i> Telefone
                                    </label>
                                    <input type="tel" class="form-control neon-input" id="telefone" name="telefone" 
                                           value="<?= htmlspecialchars($user['telefone']) ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-success">
                                        <i class="bi bi-dribbble"></i> Posições que Joga
                                    </label>
                                    <p class="text-muted small">Clique nas posições que você joga:</p>
                                    <div id="campo-edicao"></div>
                                    <input type="hidden" id="posicao" name="posicao" value="<?= htmlspecialchars($user['posicao']) ?>" required>
                                </div>
                            </div>
                            
                            <hr class="my-4" style="border-color: #00ff00;">
                            
                            <h5 class="text-warning mb-3">
                                <i class="bi bi-shield-lock"></i> Alterar Senha (Opcional)
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="senha_atual" class="form-label text-success">Senha Atual</label>
                                    <input type="password" class="form-control neon-input" id="senha_atual" name="senha_atual">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="nova_senha" class="form-label text-success">Nova Senha</label>
                                    <input type="password" class="form-control neon-input" id="nova_senha" name="nova_senha">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="confirmar_senha" class="form-label text-success">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control neon-input" id="confirmar_senha" name="confirmar_senha">
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn-atualizar">
                                    <i class="bi bi-check-circle"></i> Atualizar Perfil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script src="JS/campo-futebol.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Campo do perfil (apenas visualização)
            const posicoesUsuario = <?= json_encode(explode(',', $user['posicao'])) ?>;
            initCampoFutebol('campo-perfil', {
                readOnly: true,
                posicoesSelecionadas: posicoesUsuario,
                posicoesDisponiveis: [],
                posicoesOcupadas: []
            });
            
            // Campo de edição (interativo)
            initCampoFutebol('campo-edicao', {
                multiSelect: true,
                showLegenda: false,
                posicoesSelecionadas: posicoesUsuario,
                posicoesDisponiveis: ['GOL', 'ZAG', 'ALA ESQ', 'ALA DIR', 'VOL', 'MEI', 'ATA'],
                posicoesOcupadas: [],
                readOnly: false,
                onChange: function(selecionadas) {
                    const input = document.getElementById('posicao');
                    if (input) {
                        input.value = selecionadas.join(',');
                    }
                }
            });
        });
    </script>
</body>
</html>
