<?php
require_once 'includes/session.php';
require_once 'config/database.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = getUser();
$partida_id = $_GET['id'] ?? 0;

// Buscar dados completos da partida
try {
    $db = new Database();
    
    $stmt = $db->getConnection()->prepare("
        SELECT p.*, u.nome as criador_nome, u.telefone as criador_telefone,
               (SELECT AVG(nota) FROM avaliacoes_criador WHERE id_criador = p.id_usuario) as media_criador,
               (SELECT COUNT(*) FROM marcacoes m WHERE m.id_partida = p.id_partida) as total_marcados,
               (SELECT GROUP_CONCAT(CONCAT(us.nome, ':', m.posicao) SEPARATOR '|') 
                FROM marcacoes m 
                JOIN usuarios us ON m.id_usuario = us.id_usuario 
                WHERE m.id_partida = p.id_partida) as jogadores_confirmados,
               (SELECT m.posicao FROM marcacoes m WHERE m.id_partida = p.id_partida AND m.id_usuario = ?) as minha_posicao
        FROM partidas p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE p.id_partida = ?
    ");
    $stmt->execute([$user['id_usuario'], $partida_id]);
    $partida = $stmt->fetch();
    
    if (!$partida) {
        header("Location: partidas_marcadas.php");
        exit;
    }
    
    // CORREÇÃO 1: Calcular jogadores confirmados igual ao partidas_marcadas
    $posicoes_restantes = array_filter(explode(',', $partida['posicoes_restantes']));
    $jogadores_confirmados_correto = 7 - count($posicoes_restantes);
    
} catch (PDOException $e) {
    header("Location: partidas_marcadas.php");
    exit;
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
    <title>Detalhes da Partida - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="CSS/campo-futebol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
    .detalhes-card {
        background: rgba(20, 20, 20, 0.95);
        border: 1px solid rgba(0, 255, 0, 0.4);
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
        margin-bottom: 20px;
    }
    
    .detalhes-header {
        background: rgba(0, 255, 0, 0.08);
        border-bottom: 1px solid rgba(0, 255, 0, 0.3);
        padding: 1.5rem;
        border-radius: 15px 15px 0 0;
    }
    
    .detalhes-body {
        padding: 2rem;
    }
    
    .info-section {
        margin-bottom: 2rem;
    }
    
    .section-title {
        color: #00ff00;
        font-weight: bold;
        font-size: 1.2rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #00ff00;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .info-item {
        background: rgba(0, 255, 0, 0.05);
        border: 1px solid rgba(0, 255, 0, 0.2);
        border-radius: 8px;
        padding: 1rem;
    }
    
    .info-label {
        color: #00ff00;
        font-weight: bold;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .info-value {
        color: #ffffff;
        font-size: 1rem;
        word-wrap: break-word;
    }
    
    .badge-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9rem;
    }
    
    .status-ativa {
        background: linear-gradient(45deg, #007bff, #0056b3);
        color: white;
    }
    
    .status-realizada {
        background: linear-gradient(45deg, #6c757d, #495057);
        color: white;
    }
    
    .jogadores-lista {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .jogador-item {
        background: rgba(0, 123, 255, 0.1);
        border: 1px solid rgba(0, 123, 255, 0.3);
        border-radius: 8px;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .posicao-badge {
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: bold;
        min-width: 60px;
        text-align: center;
    }
    
    .campo-detalhes {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 10px;
        padding: 1.5rem;
        margin: 1.5rem 0;
    }
    
    .rating-display {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .rating-display .star {
        color: #ffc107;
        font-size: 1rem;
    }
    
    .btn-voltar {
        background: linear-gradient(45deg, #dc3545, #c82333);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: bold;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-voltar:hover {
        background: linear-gradient(45deg, #c82333, #a71e2a);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
    }
    
    .empty-jogadores {
        text-align: center;
        padding: 2rem;
        color: #888;
        font-style: italic;
    }

    /* Botão Chat */
    .btn-chat {
        background: #00ff00;
        color: #000;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: bold;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-right: 15px;
    }
    
    .btn-chat:hover {
        background: #00cc00;
        color: #000;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 255, 0, 0.4);
    }

    /* Botões de ação */
    .acoes-container {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .jogadores-lista {
            grid-template-columns: 1fr;
        }
        
        .detalhes-body {
            padding: 1rem;
        }
        
        .acoes-container {
            flex-direction: column;
        }
        
        .btn-voltar, .btn-chat {
            width: 100%;
            justify-content: center;
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
                    <div class="detalhes-card">
                        <div class="detalhes-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="neon-text mb-0">
                                    <i class="bi bi-info-circle"></i> Detalhes da Partida
                                </h2>
                                <?php 
                                $hoje = date('Y-m-d');
                                $hora_atual = date('H:i:s');
                                $is_futura = ($partida['data'] > $hoje) || ($partida['data'] == $hoje && $partida['horario'] > $hora_atual);
                                ?>
                                <span class="badge-status <?= $is_futura ? 'status-ativa' : 'status-realizada' ?>">
                                    <?= $is_futura ? 'PARTIDA ATIVA' : 'PARTIDA REALIZADA' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="detalhes-body">
                            <!-- Informações Básicas -->
                            <div class="info-section">
                                <h3 class="section-title">
                                    <i class="bi bi-calendar-event"></i> Informações da Partida
                                </h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-calendar"></i> Data
                                        </div>
                                        <div class="info-value"><?= date('d/m/Y', strtotime($partida['data'])) ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-clock"></i> Horário
                                        </div>
                                        <div class="info-value"><?= date('H:i', strtotime($partida['horario'])) ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-sun"></i> Turno
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['turno']) ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-people"></i> Jogadores Confirmados
                                        </div>
                                        <div class="info-value"><?= $jogadores_confirmados_correto ?>/7 jogadores</div>
                                    </div>
                                    <?php if (!empty($partida['duracao_minutos'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-stopwatch"></i> Duração
                                        </div>
                                        <div class="info-value"><?= $partida['duracao_minutos'] ?> minutos</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['nivel_jogo'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-trophy"></i> Nível do Jogo
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['nivel_jogo']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Localização -->
                            <div class="info-section">
                                <h3 class="section-title">
                                    <i class="bi bi-geo-alt"></i> Localização
                                </h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-flag"></i> Estado
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['estado']) ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-building"></i> Cidade
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['cidade']) ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-pin-map"></i> Endereço
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['endereco']) ?></div>
                                    </div>
                                    <?php if (!empty($partida['nome_local'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-house"></i> Nome do Local
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['nome_local']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['cep'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-mailbox"></i> CEP
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['cep']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['bairro'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-map"></i> Bairro
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['bairro']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Informações do Criador -->
                            <div class="info-section">
                                <h3 class="section-title">
                                    <i class="bi bi-person-badge"></i> Criador da Partida
                                </h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-person"></i> Nome
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['criador_nome']) ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-telephone"></i> Telefone
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['criador_telefone']) ?></div>
                                    </div>
                                    <?php if ($partida['media_criador']): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-star"></i> Avaliação
                                        </div>
                                        <div class="info-value">
                                            <div class="rating-display">
                                                <?php 
                                                $media = round($partida['media_criador']);
                                                for ($i = 1; $i <= 5; $i++): 
                                                ?>
                                                    <i class="bi bi-star<?= $i <= $media ? '-fill' : '' ?> star"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2"><?= number_format($partida['media_criador'], 1) ?>/5</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Descrição -->
                            <?php if (!empty($partida['descricao'])): ?>
                            <div class="info-section">
                                <h3 class="section-title">
                                    <i class="bi bi-card-text"></i> Descrição
                                </h3>
                                <div class="info-item">
                                    <div class="info-value"><?= nl2br(htmlspecialchars($partida['descricao'])) ?></div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Informações Adicionais -->
                            <?php if (!empty($partida['valor_por_pessoa']) || !empty($partida['idade_minima']) || !empty($partida['idade_maxima'])): ?>
                            <div class="info-section">
                                <h3 class="section-title">
                                    <i class="bi bi-info-square"></i> Informações Adicionais
                                </h3>
                                <div class="info-grid">
                                    <?php if (!empty($partida['valor_por_pessoa']) && $partida['valor_por_pessoa'] > 0): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-currency-dollar"></i> Valor por Pessoa
                                        </div>
                                        <div class="info-value">R$ <?= number_format($partida['valor_por_pessoa'], 2, ',', '.') ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['idade_minima']) || !empty($partida['idade_maxima'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-person"></i> Faixa Etária
                                        </div>
                                        <div class="info-value"><?= $partida['idade_minima'] ?>-<?= $partida['idade_maxima'] ?> anos</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['intervalo'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-pause-circle"></i> Intervalo
                                        </div>
                                        <div class="info-value"><?= $partida['intervalo'] ? 'Sim' : 'Não' ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['arbitro_incluso'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-whistle"></i> Árbitro
                                        </div>
                                        <div class="info-value"><?= $partida['arbitro_incluso'] ? 'Incluso' : 'Não incluso' ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Infraestrutura -->
                            <?php if (!empty($partida['tem_vestiario']) || !empty($partida['tem_chuveiro']) || !empty($partida['tem_estacionamento'])): ?>
                            <div class="info-section">
                                <h3 class="section-title">
                                    <i class="bi bi-building"></i> Infraestrutura
                                </h3>
                                <div class="info-grid">
                                    <?php if (!empty($partida['tem_vestiario'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-door-open"></i> Vestiário
                                        </div>
                                        <div class="info-value"><?= $partida['tem_vestiario'] ? 'Disponível' : 'Não disponível' ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['tem_chuveiro'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-droplet"></i> Chuveiro
                                        </div>
                                        <div class="info-value"><?= $partida['tem_chuveiro'] ? 'Disponível' : 'Não disponível' ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['tem_estacionamento'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-car-front"></i> Estacionamento
                                        </div>
                                        <div class="info-value"><?= $partida['tem_estacionamento'] ? 'Disponível' : 'Não disponível' ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($partida['contato_local'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bi bi-telephone"></i> Contato do Local
                                        </div>
                                        <div class="info-value"><?= htmlspecialchars($partida['contato_local']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Campo de Futebol -->
                            <div class="info-section">
                                <h3 class="section-title">
                                    <i class="bi bi-diagram-3"></i> Posições no Campo
                                </h3>
                                <div class="campo-detalhes">
                                    <div id="campo-detalhes" style="height: 350px;"></div>
                                </div>
                            </div>

                            <!-- Jogadores Confirmados -->
                            <div class="info-section">
                                <h3 class="section-title">
                                    <i class="bi bi-people-fill"></i> Jogadores Confirmados (<?= $jogadores_confirmados_correto ?>/7)
                                </h3>
                                <?php if (!empty($partida['jogadores_confirmados'])): ?>
                                    <div class="jogadores-lista">
                                        <?php 
                                        $jogadores = explode('|', $partida['jogadores_confirmados']);
                                        foreach ($jogadores as $jogador): 
                                            $dados = explode(':', $jogador);
                                            if (count($dados) == 2):
                                        ?>
                                            <div class="jogador-item">
                                                <span class="posicao-badge"><?= htmlspecialchars($dados[1]) ?></span>
                                                <div>
                                                    <strong><?= htmlspecialchars($dados[0]) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= getNomePosicao($dados[1]) ?></small>
                                                </div>
                                            </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-jogadores">
                                        <i class="bi bi-person-x" style="font-size: 3rem; color: #666;"></i>
                                        <p>Nenhum jogador confirmado ainda</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Botões de Ação -->
                            <div class="acoes-container">
                                <a href="chat.php?partida=<?= $partida['id_partida'] ?>" class="btn-chat">
                                    <i class="bi bi-chat-dots"></i> CHAT DA PARTIDA
                                </a>
                                <a href="javascript:history.back()" class="btn-voltar">
                                    <i class="bi bi-arrow-left"></i> Voltar
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
    <script src="JS/campo-futebol.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // CORREÇÃO 2: Preparar dados do campo corretamente
            const posicoes_restantes = <?= json_encode($posicoes_restantes) ?>;
            const jogadores_confirmados = <?= json_encode(!empty($partida['jogadores_confirmados']) ? array_map(function($j) { $dados = explode(':', $j); return count($dados) == 2 ? $dados[1] : ''; }, explode('|', $partida['jogadores_confirmados'])) : []) ?>;
            const minha_posicao = <?= json_encode($partida['minha_posicao'] ? [$partida['minha_posicao']] : []) ?>;
            
            // Separar outras posições ocupadas (excluindo a minha)
            const outras_ocupadas = jogadores_confirmados.filter(p => p !== '' && !minha_posicao.includes(p));
            
            // Inicializar campo com a posição do usuário em azul
            initCampoFutebol('campo-detalhes', {
                readOnly: true,
                showLegenda: true,
                posicoesSelecionadas: minha_posicao, // POSIÇÃO DO USUÁRIO EM AZUL
                posicoesDisponiveis: posicoes_restantes, // POSIÇÕES DISPONÍVEIS EM VERDE
                posicoesOcupadas: outras_ocupadas // OUTRAS POSIÇÕES OCUPADAS EM CINZA
            });
        });
    </script>
</body>
</html>
