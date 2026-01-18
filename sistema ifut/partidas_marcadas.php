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

// Processar cancelamento de marcação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_marcacao'])) {
    $marcacao_id = $_POST['marcacao_id'];
    $partida_id = $_POST['partida_id'];
    $posicao = $_POST['posicao'];
    
    try {
        $db = new Database();
        
        // Verificar se a partida é em menos de 24 horas
        $stmt = $db->getConnection()->prepare("SELECT data, horario FROM partidas WHERE id_partida = ?");
        $stmt->execute([$partida_id]);
        $partida = $stmt->fetch();
        
        $data_partida = strtotime($partida['data'] . ' ' . $partida['horario']);
        $agora = time();
        $diferenca_horas = ($data_partida - $agora) / 3600;
        
        if ($diferenca_horas < 24) {
            $message = 'Não é possível cancelar marcações com menos de 24 horas de antecedência.';
            $messageType = 'warning';
        } else {
            // Remover marcação
            $stmt = $db->getConnection()->prepare("DELETE FROM marcacoes WHERE id_marcacao = ? AND id_usuario = ?");
            if ($stmt->execute([$marcacao_id, $user['id_usuario']])) {
                // Atualizar posições da partida
                $stmt = $db->getConnection()->prepare("SELECT posicoes_restantes FROM partidas WHERE id_partida = ?");
                $stmt->execute([$partida_id]);
                $partida_atual = $stmt->fetch();
                
                $posicoes_restantes = $partida_atual['posicoes_restantes'] ? explode(',', $partida_atual['posicoes_restantes']) : [];
                
                // Adicionar posição de volta às restantes
                $posicoes_restantes[] = $posicao;
                
                $stmt = $db->getConnection()->prepare("UPDATE partidas SET posicoes_restantes = ? WHERE id_partida = ?");
                $stmt->execute([implode(',', $posicoes_restantes), $partida_id]);
                
                $message = 'Marcação cancelada com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao cancelar marcação.';
                $messageType = 'danger';
            }
        }
    } catch (PDOException $e) {
        $message = 'Erro no sistema.';
        $messageType = 'danger';
    }
}

// Buscar partidas marcadas pelo usuário
try {
    $db = new Database();
    $hoje = date('Y-m-d');
    $hora_atual = date('H:i:s');
    
    // Partidas ativas (futuras) - incluindo marcações diretas
    $stmt = $db->getConnection()->prepare("
        SELECT m.*, p.*, u.nome as criador_nome,
               (SELECT AVG(nota) FROM avaliacoes_criador WHERE id_criador = p.id_usuario) as media_criador
        FROM marcacoes m
        JOIN partidas p ON m.id_partida = p.id_partida
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE m.id_usuario = ? AND (p.data > ? OR (p.data = ? AND p.horario > ?))
        ORDER BY p.data ASC, p.horario ASC
    ");
    $stmt->execute([$user['id_usuario'], $hoje, $hoje, $hora_atual]);
    $partidas_ativas = $stmt->fetchAll();
    
    // Partidas realizadas (passadas)
    $stmt = $db->getConnection()->prepare("
        SELECT m.*, p.*, u.nome as criador_nome,
               (SELECT AVG(nota) FROM avaliacoes_criador WHERE id_criador = p.id_usuario) as media_criador,
               ac.nota as minha_avaliacao_criador
        FROM marcacoes m
        JOIN partidas p ON m.id_partida = p.id_partida
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        LEFT JOIN avaliacoes_criador ac ON (ac.id_partida = p.id_partida AND ac.id_criador = p.id_usuario AND ac.id_jogador = m.id_usuario)
        WHERE m.id_usuario = ? AND (p.data < ? OR (p.data = ? AND p.horario <= ?))
        ORDER BY p.data DESC, p.horario DESC
    ");
    $stmt->execute([$user['id_usuario'], $hoje, $hoje, $hora_atual]);
    $partidas_realizadas = $stmt->fetchAll();
    
    // Buscar solicitações pendentes e recusadas (últimas 12 horas)
    $doze_horas_atras = date('Y-m-d H:i:s', strtotime('-12 hours'));
    $stmt = $db->getConnection()->prepare("
        SELECT s.*, p.*, u.nome as criador_nome,
               (SELECT AVG(nota) FROM avaliacoes_criador WHERE id_criador = p.id_usuario) as media_criador
        FROM solicitacoes_participacao s
        JOIN partidas p ON s.id_partida = p.id_partida
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE s.id_usuario = ? AND s.status IN ('pendente', 'recusada') 
        AND (s.status = 'pendente' OR (s.status = 'recusada' AND s.data_resposta >= ?))
        AND (p.data > ? OR (p.data = ? AND p.horario > ?))
        ORDER BY s.data_solicitacao DESC
    ");
    $stmt->execute([$user['id_usuario'], $doze_horas_atras, $hoje, $hoje, $hora_atual]);
    $solicitacoes_pendentes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $partidas_ativas = [];
    $partidas_realizadas = [];
    $solicitacoes_pendentes = [];
    $message = 'Erro ao buscar partidas: ' . $e->getMessage();
    $messageType = 'danger';
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
    <title>Partidas Marcadas - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="CSS/campo-futebol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
    .partida-card {
        border: 1px solid #00ff00;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 20px;
        background-color: rgba(0, 0, 0, 0.7);
    }
    
    .partida-content {
        display: flex;
        flex-direction: row;
        padding: 15px;
    }
    
    .partida-info {
        flex: 1;
        padding-right: 15px;
    }
    
    .partida-campo {
        flex: 1.2;
        padding: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color:rgba(0, 0, 0, 0.7);
    }
    
    .campo-futebol-container {
        width: 100%;
        height: 300px;
        border-radius: 5px;
        position: relative;
    }

    .campo-futebol {
        max-width: 100%;
        height: 300px;
        margin: 0 auto;
    }
    
    /* Cursor de bloqueio para todas as posições do campo nas partidas marcadas */
    .campo-futebol-container .posicao {
        cursor: not-allowed !important;
    }
    
    .campo-futebol-container .posicao:hover {
        cursor: not-allowed !important;
    }
    
    .info-item {
        margin-bottom: 12px;
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
    
    /* BADGES UNIFORMES E EM NEGRITO */
    .badge-verde {
        background-color: #00ff00;
        color: #000;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 5px;
        margin-right: 10px;
        font-size: 0.85rem;
    }
    
    .badge-azul {
        background-color: #007bff;
        color: white;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 5px;
        margin-right: 10px;
        font-size: 0.85rem;
    }

    .badge-amarelo {
        background-color: #ffc107;
        color: #000;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 5px;
        margin-right: 10px;
        font-size: 0.85rem;
    }

    .badge-vermelho {
        background-color: #dc3545;
        color: white;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 5px;
        margin-right: 10px;
        font-size: 0.85rem;
    }

    .badge-laranja {
        background-color: #ff8c00;
        color: white;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 5px;
        margin-right: 10px;
        font-size: 0.85rem;
    }
    
    .btn-cancelar {
        background-color: #ff3333;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        margin-top: 10px;
        cursor: pointer;
        transition: all 0.3s;
        width: auto;
        display: inline-block;
    }
    
    .btn-cancelar:hover {
        background-color: #cc0000;
    }

    .btn-avaliar-criador {
        background-color: #17a2b8;
        color: white;
        font-weight: bold;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        text-align: center;
        margin-top: 10px;
        width: 100%;
        display: block;
    }

    .btn-avaliar-criador:hover {
        background-color: #138496;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .aviso-cancelamento {
        background-color: rgba(255, 255, 0, 0.1);
        border: 1px solid rgba(255, 255, 0, 0.3);
        color: #ffff00;
        padding: 8px;
        border-radius: 5px;
        font-size: 0.9em;
        margin-top: 10px;
        text-align: center;
    }
    
    .partida-realizada-badge {
        background-color: #666;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 0.8em;
        font-weight: bold;
        margin-right: 10px;
    }
    
    /* BADGE PARTIDA ATIVA - AZUL E MAIOR */
    .partida-ativa-badge {
        background-color: #007bff;
        color: white;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 5px;
        margin-right: 10px;
        font-size: 0.85rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 30px;
        background-color: rgba(0, 0, 0, 0.5);
        border: 1px dashed rgba(0, 255, 0, 0.3);
        border-radius: 10px;
        margin: 20px 0;
    }
    
    .empty-state i {
        color: rgba(0, 255, 0, 0.5);
        font-size: 3em;
        margin-bottom: 15px;
    }

    /* Modal de Cancelamento - PADRÃO ATUALIZADO */
.modal-cancelamento {
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(2px);
}

.modal-cancelamento .modal-dialog {
    max-width: 500px;
}

.modal-cancelamento .modal-content {
    background-color: #000000;
    border: 2px solid #00ff00;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
}

.modal-cancelamento .modal-header {
    border-bottom: 1px solid #00ff00;
    background-color: #000000;
    padding: 15px 20px;
}

.modal-cancelamento .modal-title {
    color: #00ff00;
    font-weight: bold;
    text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-cancelamento .modal-body {
    text-align: center;
    padding: 30px;
    color: #ffffff;
    background: #000000;
}

.modal-cancelamento .modal-body p {
    font-size: 1.1em;
    margin-bottom: 25px;
    color: #ffffff;
}

.modal-cancelamento .icon-warning {
    font-size: 4em;
    color: #00ff00;
    margin-bottom: 20px;
    text-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
}

.modal-cancelamento .dados-partida {
    background: rgba(0, 255, 0, 0.1);
    border: 1px solid #00ff00;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
    color: #00ff00;
    text-align: left;
}

.modal-cancelamento .info-partida {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    color: #ffffff;
}

.modal-cancelamento .info-partida i {
    color: #00ff00;
    margin-right: 10px;
    width: 20px;
}

.modal-cancelamento .info-partida strong {
    color: #00ff00;
}

.modal-cancelamento .alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    color: #ffc107;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

.modal-cancelamento .modal-footer {
    border-top: 1px solid #00ff00;
    background-color: #000000;
    padding: 15px 20px;
    justify-content: center;
    gap: 15px;
}

.btn-voltar {
    background: #dc3545;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    min-width: 120px;
}

.btn-voltar:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    color: white;
}

.btn-azul-modal {
    background: #007bff;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    min-width: 200px;
    flex: 1;
}

.btn-azul-modal:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    color: white;
}

.btn-confirmar {
    background: #00ff00;
    color: #000;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
    min-width: 200px;
    flex: 1;
}

.btn-confirmar:hover {
    background: #00cc00;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 255, 0, 0.4);
    color: #000;
}

/* Botão CHAT */
.btn-chat {
    display: inline-block;
    background-color: #00ff00;
    color: #000;
    font-weight: bold;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    text-align: center;
    justify-content: center;
    align-items: center;
}

.btn-chat:hover {
    background-color: #00cc00;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 255, 0, 0.4);
    color: #000;
    text-decoration: none;
}

.btn-chat i {
    margin-right: 5px;
}

/* NOVO: Botão DETALHES DA PARTIDA - AZUL */
.btn-detalhes {
    display: inline-block;
    background-color: #007bff;
    color: white;
    font-weight: bold;
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    text-align: center;
    justify-content: center;
    align-items: center;
    display: inline-block;
}

.btn-detalhes:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
    color: white;
    text-decoration: none;
}

.btn-detalhes i {
    margin-right: 5px;
}

/* Rating display para criadores */
.rating-display {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-display .star {
    color: #ffc107;
    font-size: 0.9rem;
}

/* Status de avaliação */
.avaliacao-status {
    background: rgba(23, 162, 184, 0.1);
    border: 1px solid #17a2b8;
    border-radius: 5px;
    padding: 8px;
    margin-top: 10px;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .modal-cancelamento .modal-dialog {
        margin: 10px;
    }
    
    .modal-cancelamento .modal-body {
        padding: 20px;
    }
    
    .modal-cancelamento .modal-footer {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn-voltar, .btn-confirmar, .btn-azul-modal {
        width: 100%;
        min-width: auto;
        flex: none;
    }
    
    .partida-content {
        flex-direction: column;
    }
    
    .partida-info {
        margin-bottom: 15px;
    }
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
                            <i class="bi bi-calendar-check"></i> Minhas Partidas Marcadas
                        </h2>
                        <a href="encontrar_partida.php" class="btn btn-success neon-btn">
                            <i class="bi bi-search"></i> Buscar Mais Partidas
                        </a>
                    </div>
                    
                    <?php if ($message && $messageType !== 'success'): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Solicitações Pendentes e Recusadas -->
                    <?php if (!empty($solicitacoes_pendentes)): ?>
                        <h3 class="section-title mt-4">Solicitações de Participação</h3>
                        
                        <?php foreach ($solicitacoes_pendentes as $solicitacao): ?>
                            <?php 
                            $posicoes_restantes = array_filter(explode(',', $solicitacao['posicoes_restantes']));
                            $sua_posicao = [$solicitacao['posicao']];
                            
                            // Buscar todas as posições marcadas na partida
                            $stmt = $db->getConnection()->prepare("SELECT posicao FROM marcacoes WHERE id_partida = ?");
                            $stmt->execute([$solicitacao['id_partida']]);
                            $todas_marcacoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            // Calcular jogadores confirmados: 7 total - posições restantes
                            $jogadores_confirmados = 7 - count($posicoes_restantes);
                            ?>
                            
                            <div class="partida-card">
                                <div class="partida-content">
                                    <div class="partida-info">
                                        <div class="info-item">
                                            <i class="bi bi-clock"></i>
                                            <?php if ($solicitacao['status'] === 'pendente'): ?>
                                                <span class="badge-amarelo">SOLICITAÇÃO PENDENTE</span>
                                            <?php else: ?>
                                                <span class="badge-vermelho">SOLICITAÇÃO RECUSADA</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-dribbble"></i>
                                            <span class="badge-azul"><?= getNomePosicao($solicitacao['posicao']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-people-fill"></i>
                                            <?php if ($jogadores_confirmados == 7): ?>
                                                <span class="badge-verde"><?= $jogadores_confirmados ?>/7 jogadores confirmados</span>
                                            <?php else: ?>
                                                <span class="badge-laranja"><?= $jogadores_confirmados ?>/7 jogadores confirmados</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-calendar"></i>
                                            <span class="badge-verde"><?= date('d/m/Y', strtotime($solicitacao['data'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-clock-fill"></i>
                                            <span class="badge-verde"><?= htmlspecialchars($solicitacao['turno']) ?> - <?= date('H:i', strtotime($solicitacao['horario'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-info-circle"></i>
                                            <a href="detalhes_partida.php?id=<?= $solicitacao['id_partida'] ?>" class="btn-detalhes"><i class="bi bi-eye"></i> DETALHES</a>
                                        </div> 
                                        <?php if ($solicitacao['status'] === 'pendente'): ?>
                                            <div class="aviso-cancelamento">
                                                <i class="bi bi-clock"></i> Aguardando aprovação do criador da partida
                                            </div>
                                        <?php else: ?>
                                            <div class="aviso-cancelamento" style="background-color: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3); color: #dc3545;">
                                                <i class="bi bi-x-circle"></i> Solicitação recusada - Esta partida voltará para "Encontrar Partidas" em breve
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="partida-campo">
                                        <div id="campo-solicitacao-<?= $solicitacao['id_solicitacao'] ?>" class="campo-futebol-container">
                                        <div class="campo-futebol"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    initCampoFutebol('campo-solicitacao-<?= $solicitacao['id_solicitacao'] ?>', {
                                        readOnly: true,
                                        posicoesSelecionadas: <?= json_encode($sua_posicao) ?>,
                                        posicoesDisponiveis: <?= json_encode($posicoes_restantes) ?>,
                                        posicoesOcupadas: <?= json_encode($todas_marcacoes) ?>
                                    });
                                });
                            </script>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Partidas Ativas -->
                    <h3 class="section-title mt-4">Partidas Ativas</h3>
                    
                    <?php if (empty($partidas_ativas)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h5 class="text-muted">Nenhuma partida ativa</h5>
                            <p class="text-muted">Você não tem partidas futuras marcadas.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($partidas_ativas as $partida): ?>
                            <?php 
                            $data_partida = strtotime($partida['data'] . ' ' . $partida['horario']);
                            $agora = time();
                            $diferenca_horas = ($data_partida - $agora) / 3600;
                            $pode_cancelar = $diferenca_horas >= 24;
                            
                            $posicoes_restantes = array_filter(explode(',', $partida['posicoes_restantes']));
                            $sua_posicao = [$partida['posicao']];
                            
                            // Buscar todas as posições marcadas na partida
                            $stmt = $db->getConnection()->prepare("SELECT posicao FROM marcacoes WHERE id_partida = ?");
                            $stmt->execute([$partida['id_partida']]);
                            $todas_marcacoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            $outras_ocupadas = array_diff($todas_marcacoes, $sua_posicao);
                            
                            // Calcular jogadores confirmados: 7 total - posições restantes
                            $jogadores_confirmados = 7 - count($posicoes_restantes);
                            ?>
                            
                            <div class="partida-card">
                                <div class="partida-content">
                                    <div class="partida-info">
                                        <div class="info-item">
                                            <i class="bi bi-check-circle"></i>
                                            <span class="partida-ativa-badge">PARTIDA ATIVA</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-dribbble"></i>
                                            <span class="badge-azul"><?= getNomePosicao($partida['posicao']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-people-fill"></i>
                                            <?php if ($jogadores_confirmados == 7): ?>
                                                <span class="badge-verde"><?= $jogadores_confirmados ?>/7 jogadores confirmados</span>
                                            <?php else: ?>
                                                <span class="badge-amarelo"><?= $jogadores_confirmados ?>/7 jogadores confirmados</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-calendar"></i>
                                            <span class="badge-verde"><?= date('d/m/Y', strtotime($partida['data'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-clock-fill"></i>
                                            <span class="badge-verde"><?= htmlspecialchars($partida['turno']) ?> - <?= date('H:i', strtotime($partida['horario'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-chat-dots"></i>
                                            <a href="chat.php?partida=<?= $partida['id_partida'] ?>" class="btn-chat">CHAT</a>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-info-circle"></i>
                                            <a href="detalhes_partida.php?id=<?= $partida['id_partida'] ?>" class="btn-detalhes"><i class="bi bi-eye"></i> DETALHES</a>
                                        </div>                                         
                                        <?php if ($pode_cancelar): ?>
                                            <div class="info-item">
                                                <i class="bi bi-x-octagon"></i>
                                                <form method="POST" id="form-cancelar-<?= $partida['id_marcacao'] ?>" style="display: inline;">
                                                    <input type="hidden" name="marcacao_id" value="<?= $partida['id_marcacao'] ?>">
                                                    <input type="hidden" name="partida_id" value="<?= $partida['id_partida'] ?>">
                                                    <input type="hidden" name="posicao" value="<?= $partida['posicao'] ?>">
                                                    <button type="button" class="btn-cancelar" onclick="abrirModalCancelamento(<?= $partida['id_marcacao'] ?>, '<?= date('d/m/Y', strtotime($partida['data'])) ?>', '<?= htmlspecialchars($partida['turno']) ?>', '<?= date('H:i', strtotime($partida['horario'])) ?>', '<?= htmlspecialchars($partida['endereco']) ?>', '<?= getNomePosicao($partida['posicao']) ?>')">
                                                        ❌ Cancelar Participação
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <div class="aviso-cancelamento">
                                                <i class="bi bi-exclamation-triangle"></i> Não é possível cancelar com menos de 24h de antecedência
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="partida-campo">
                                        <div id="campo-marcada-<?= $partida['id_marcacao'] ?>" class="campo-futebol-container">
                                        <div class="campo-futebol"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    initCampoFutebol('campo-marcada-<?= $partida['id_marcacao'] ?>', {
                                        readOnly: true,
                                        posicoesSelecionadas: <?= json_encode($sua_posicao) ?>,
                                        posicoesDisponiveis: <?= json_encode($posicoes_restantes) ?>,
                                        posicoesOcupadas: <?= json_encode($outras_ocupadas) ?>
                                    });
                                });
                            </script>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Partidas Realizadas -->
                    <h3 class="section-title mt-5">Partidas Realizadas</h3>
                    
                    <?php if (empty($partidas_realizadas)): ?>
                        <div class="empty-state">
                            <i class="bi bi-hourglass"></i>
                            <h5 class="text-muted">Nenhuma partida realizada</h5>
                            <p class="text-muted">Seu histórico de partidas aparecerá aqui após a data dos jogos.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($partidas_realizadas as $partida): ?>
                            <?php 
                            $posicoes_restantes = array_filter(explode(',', $partida['posicoes_restantes']));
                            $sua_posicao = [$partida['posicao']];
                            
                            // Buscar todas as posições marcadas na partida
                            $stmt = $db->getConnection()->prepare("SELECT posicao FROM marcacoes WHERE id_partida = ?");
                            $stmt->execute([$partida['id_partida']]);
                            $todas_marcacoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            $outras_ocupadas = array_diff($todas_marcacoes, $sua_posicao);
                            
                            // Calcular jogadores confirmados: 7 total - posições restantes
                            $jogadores_confirmados = 7 - count($posicoes_restantes);
                            ?>
                            
                            <div class="partida-card">
                                <div class="partida-content">
                                    <div class="partida-info">
                                        <div class="info-item">
                                            <i class="bi bi-hourglass-bottom"></i>
                                            <span class="partida-realizada-badge">PARTIDA REALIZADA</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-dribbble"></i>
                                            <span class="badge-azul"><?= getNomePosicao($partida['posicao']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-people-fill"></i>
                                            <?php if ($jogadores_confirmados == 7): ?>
                                                <span class="badge-verde"><?= $jogadores_confirmados ?>/7 jogadores participaram</span>
                                            <?php else: ?>
                                                <span class="badge-amarelo"><?= $jogadores_confirmados ?>/7 jogadores participaram</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-calendar"></i>
                                            <span class="badge-verde"><?= date('d/m/Y', strtotime($partida['data'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-clock-fill"></i>
                                            <span class="badge-verde"><?= htmlspecialchars($partida['turno']) ?> - <?= date('H:i', strtotime($partida['horario'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-chat-dots"></i>
                                            <a href="chat.php?partida=<?= $partida['id_partida'] ?>" class="btn-chat">CHAT</a>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-info-circle"></i>
                                            <a href="detalhes_partida.php?id=<?= $partida['id_partida'] ?>" class="btn-detalhes"><i class="bi bi-eye"></i> DETALHES</a>
                                        </div>
                                        <?php if ($partida['minha_avaliacao_criador']): ?>
                                            <div class="avaliacao-status">
                                                <i class="bi bi-star-fill"></i>
                                                <strong>Avaliação do criador:</strong> Você já avaliou este criador com <?= $partida['minha_avaliacao_criador'] ?>/5 estrelas
                                            </div>
                                        <?php else: ?>
                                            <a href="avaliar_criador.php?partida=<?= $partida['id_partida'] ?>" class="btn-avaliar-criador">
                                                <i class="bi bi-star"></i> AVALIAR CRIADOR
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="partida-campo">
                                        <div id="campo-realizada-<?= $partida['id_marcacao'] ?>" class="campo-futebol-container">
                                        <div class="campo-futebol"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    initCampoFutebol('campo-realizada-<?= $partida['id_marcacao'] ?>', {
                                        readOnly: true,
                                        posicoesSelecionadas: <?= json_encode($sua_posicao) ?>,
                                        posicoesDisponiveis: <?= json_encode($posicoes_restantes) ?>,
                                        posicoesOcupadas: <?= json_encode($outras_ocupadas) ?>
                                    });
                                });
                            </script>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cancelamento -->
<div class="modal fade modal-cancelamento" id="modalCancelamento" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Cancelar Participação
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="icon-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <p><strong>Tem certeza que deseja cancelar sua participação na partida?</strong></p>
                
                <div class="dados-partida">
                    <div class="info-partida">
                        <i class="bi bi-calendar"></i>
                        <span>Data: <strong id="modal-data"></strong></span>
                    </div>
                    <div class="info-partida">
                        <i class="bi bi-clock"></i>
                        <span>Horário: <strong id="modal-horario"></strong></span>
                    </div>
                    <div class="info-partida">
                        <i class="bi bi-geo-alt"></i>
                        <span>Local: <strong id="modal-local"></strong></span>
                    </div>
                    <div class="info-partida">
                        <i class="bi bi-dribbble"></i>
                        <span>Sua Posição: <strong id="modal-posicao"></strong></span>
                    </div>
                </div>
                
                <div class="alert-warning">
                    <i class="bi bi-info-circle"></i>
                    <strong>Atenção:</strong> Esta ação não poderá ser desfeita e sua vaga ficará disponível para outros jogadores.
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="button" class="btn-voltar" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </button>
                    <button type="button" class="btn-confirmar" id="btnConfirmarCancelamento">
                        <i class="bi bi-check-circle"></i> Sim, Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script src="JS/campo-futebol.js"></script>
    <script>
        let formCancelamento = null;

        function abrirModalCancelamento(marcacaoId, data, turno, horario, local, posicao) {
            document.getElementById('modal-data').textContent = data;
            document.getElementById('modal-horario').textContent = turno + ' - ' + horario;
            document.getElementById('modal-local').textContent = local;
            document.getElementById('modal-posicao').textContent = posicao;
            
            formCancelamento = document.getElementById('form-cancelar-' + marcacaoId);
            
            document.getElementById('btnConfirmarCancelamento').onclick = function() {
                if (formCancelamento) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'cancelar_marcacao';
                    input.value = '1';
                    formCancelamento.appendChild(input);
                    formCancelamento.submit();
                }
            };
            
            new bootstrap.Modal(document.getElementById('modalCancelamento')).show();
        }

        // Mostrar mensagem de sucesso se necessário
        <?php if ($message && $messageType === 'success'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container').insertBefore(alert, document.querySelector('.container').firstChild);
            });
        <?php endif; ?>
    </script>
</body>
</html>
