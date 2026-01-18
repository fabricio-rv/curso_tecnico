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

// Processar resposta à solicitação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder_solicitacao'])) {
    $id_solicitacao = (int)$_POST['id_solicitacao'];
    $resposta = $_POST['resposta']; // 'aceita' ou 'recusada'
    
    try {
        $db = new Database();
        
        // Buscar dados da solicitação
        $stmt = $db->getConnection()->prepare("
            SELECT s.*, p.*, u.nome as jogador_nome
            FROM solicitacoes_participacao s
            JOIN partidas p ON s.id_partida = p.id_partida
            JOIN usuarios u ON s.id_usuario = u.id_usuario
            WHERE s.id_solicitacao = ? AND p.id_usuario = ? AND s.status = 'pendente'
        ");
        $stmt->execute([$id_solicitacao, $user['id_usuario']]);
        $solicitacao = $stmt->fetch();
        
        if (!$solicitacao) {
            $message = 'Solicitação não encontrada ou já foi respondida.';
            $messageType = 'warning';
        } else {
            if ($resposta === 'aceita') {
                // Verificar se a posição ainda está disponível
                $posicoes_restantes = array_filter(explode(',', $solicitacao['posicoes_restantes']));
                
                if (!in_array($solicitacao['posicao'], $posicoes_restantes)) {
                    $message = 'Esta posição não está mais disponível.';
                    $messageType = 'warning';
                } else {
                    // Aceitar solicitação
                    $stmt = $db->getConnection()->prepare("
                        UPDATE solicitacoes_participacao 
                        SET status = 'aceita', data_resposta = NOW()
                        WHERE id_solicitacao = ?
                    ");
                    $stmt->execute([$id_solicitacao]);
                    
                    // Criar marcação
                    $stmt = $db->getConnection()->prepare("
                        INSERT INTO marcacoes (id_usuario, id_partida, posicao)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$solicitacao['id_usuario'], $solicitacao['id_partida'], $solicitacao['posicao']]);
                    
                    // Atualizar posições da partida
                    $posicoes_marcadas = $solicitacao['posicoes_marcadas'] ? explode(',', $solicitacao['posicoes_marcadas']) : [];
                    $posicoes_marcadas[] = $solicitacao['posicao'];
                    $posicoes_restantes = array_diff($posicoes_restantes, [$solicitacao['posicao']]);
                    
                    $stmt = $db->getConnection()->prepare("
                        UPDATE partidas 
                        SET posicoes_restantes = ?, posicoes_marcadas = ?
                        WHERE id_partida = ?
                    ");
                    $stmt->execute([
                        implode(',', $posicoes_restantes),
                        implode(',', $posicoes_marcadas),
                        $solicitacao['id_partida']
                    ]);
                    
                    $message = 'Solicitação aceita com sucesso!';
                    $messageType = 'success';
                }
            } else {
                // Recusar solicitação
                $stmt = $db->getConnection()->prepare("
                    UPDATE solicitacoes_participacao 
                    SET status = 'recusada', data_resposta = NOW()
                    WHERE id_solicitacao = ?
                ");
                $stmt->execute([$id_solicitacao]);
                
                $message = 'Solicitação recusada.';
                $messageType = 'info';
            }
        }
    } catch (PDOException $e) {
        $message = 'Erro no sistema: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Buscar solicitações pendentes para partidas do usuário
try {
    $db = new Database();
    
    $stmt = $db->getConnection()->prepare("
        SELECT s.*, p.*, u.nome as jogador_nome, u.telefone as jogador_telefone,
               u.posicao as posicoes_jogador,
               (SELECT AVG(ac.nota) FROM avaliacoes_criador ac WHERE ac.id_jogador = u.id_usuario) as media_avaliacoes
        FROM solicitacoes_participacao s
        JOIN partidas p ON s.id_partida = p.id_partida
        JOIN usuarios u ON s.id_usuario = u.id_usuario
        WHERE p.id_usuario = ? AND s.status = 'pendente'
        ORDER BY s.data_solicitacao DESC
    ");
    $stmt->execute([$user['id_usuario']]);
    $solicitacoes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $solicitacoes = [];
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
    <title>Solicitações de Participação - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
        .solicitacao-card {
            border: 1px solid #00ff00;
            border-radius: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .jogador-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .jogador-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #00ff00, #00cc00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
            color: #000;
            font-size: 1.5rem;
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
        
        .badge-verde {
            background-color: #00ff00;
            color: #000;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            margin-right: 10px;
        }
        
        .badge-azul {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            margin-right: 10px;
        }
        
        .badge-amarelo {
            background-color: #ffc107;
            color: #000;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            margin-right: 10px;
        }
        
        .btn-aceitar {
            background: linear-gradient(45deg, #00cc00, #00ff00);
            border: none;
            color: #000000;
            font-weight: bold;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 255, 0, 0.25);
            margin-right: 10px;
        }
        
        .btn-aceitar:hover {
            background: linear-gradient(45deg, #00ff00, #00cc00);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 255, 0, 0.35);
            color: #000000;
        }
        
        .btn-recusar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-recusar:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .btn-voltar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-voltar:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .avaliacoes-jogador {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .star-rating {
            color: #00ff00;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: rgba(0, 0, 0, 0.5);
            border: 1px dashed rgba(0, 255, 0, 0.3);
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .empty-state i {
            color: rgba(0, 255, 0, 0.5);
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .partida-info {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .posicoes-jogador {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        
        .posicao-badge {
            background-color: rgba(0, 123, 255, 0.2);
            color: #007bff;
            border: 1px solid #007bff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container py-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="neon-text">
                            <i class="bi bi-clock-history"></i> Solicitações de Participação
                        </h2>
                        <a href="partidas_criadas.php" class="btn-voltar">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'info' ? 'info-circle' : 'exclamation-triangle') ?>"></i> 
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($solicitacoes)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h4 class="text-muted">Nenhuma solicitação pendente</h4>
                            <p class="text-muted">Quando jogadores solicitarem participação em suas partidas, elas aparecerão aqui para você aprovar ou recusar.</p>
                        </div>
                    <?php else: ?>
                        <h4 class="neon-text mb-4">
                            <i class="bi bi-bell"></i> <?= count($solicitacoes) ?> Solicitação(ões) Pendente(s)
                        </h4>
                        
                        <?php foreach ($solicitacoes as $solicitacao): ?>
                            <div class="solicitacao-card">
                                <div class="partida-info">
                                    <h5 class="text-success mb-2">
                                        <i class="bi bi-calendar-event"></i> Partida: <?= date('d/m/Y', strtotime($solicitacao['data'])) ?> - <?= htmlspecialchars($solicitacao['turno']) ?> - <?= date('H:i', strtotime($solicitacao['horario'])) ?>
                                    </h5>
                                    <div class="info-item">
                                        <i class="bi bi-geo-alt"></i>
                                        <span><?= htmlspecialchars($solicitacao['endereco']) ?> - <?= htmlspecialchars($solicitacao['cidade']) ?>/<?= htmlspecialchars($solicitacao['estado']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="jogador-info">
                                    <div class="jogador-avatar">
                                        <?= strtoupper(substr($solicitacao['jogador_nome'], 0, 2)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 text-white"><?= htmlspecialchars($solicitacao['jogador_nome']) ?></h5>
                                        <div class="info-item">
                                            <i class="bi bi-dribbble"></i>
                                            <span class="badge-azul">Quer jogar de: <?= getNomePosicao($solicitacao['posicao']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-phone"></i>
                                            <span><?= htmlspecialchars($solicitacao['jogador_telefone']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-person-badge"></i>
                                            <span>Posições que joga:</span>
                                            <div class="posicoes-jogador">
                                                <?php 
                                                $posicoes_jogador = array_filter(explode(',', $solicitacao['posicoes_jogador']));
                                                foreach ($posicoes_jogador as $pos): 
                                                ?>
                                                    <span class="posicao-badge"><?= getNomePosicao(trim($pos)) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php if ($solicitacao['media_avaliacoes']): ?>
                                        <div class="info-item">
                                            <i class="bi bi-star"></i>
                                            <div class="avaliacoes-jogador">
                                                <span>Avaliação média:</span>
                                                <?php 
                                                $media = round($solicitacao['media_avaliacoes'], 1);
                                                for ($i = 1; $i <= 5; $i++): 
                                                ?>
                                                    <i class="bi bi-star<?= $i <= $media ? '-fill' : '' ?> star-rating"></i>
                                                <?php endfor; ?>
                                                <span class="text-muted">(<?= $media ?>/5)</span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <div class="info-item">
                                            <i class="bi bi-clock"></i>
                                            <span class="badge-amarelo">Solicitado em: <?= date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="responder_solicitacao" value="1">
                                        <input type="hidden" name="id_solicitacao" value="<?= $solicitacao['id_solicitacao'] ?>">
                                        <input type="hidden" name="resposta" value="aceita">
                                        <button type="submit" class="btn-aceitar" onclick="return confirm('Tem certeza que deseja ACEITAR esta solicitação?')">
                                            <i class="bi bi-check-circle"></i> Aceitar
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="responder_solicitacao" value="1">
                                        <input type="hidden" name="id_solicitacao" value="<?= $solicitacao['id_solicitacao'] ?>">
                                        <input type="hidden" name="resposta" value="recusada">
                                        <button type="submit" class="btn-recusar" onclick="return confirm('Tem certeza que deseja RECUSAR esta solicitação?')">
                                            <i class="bi bi-x-circle"></i> Recusar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
</body>
</html>
