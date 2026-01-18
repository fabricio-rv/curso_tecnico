<?php
require_once 'includes/session.php';
require_once 'config/database.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = getUser();
$error = '';
$success = '';

// Verificar se o ID da partida foi fornecido
if (!isset($_GET['partida']) || !is_numeric($_GET['partida'])) {
    header("Location: partidas_marcadas.php");
    exit;
}

$id_partida = (int)$_GET['partida'];

try {
    $db = new Database();
    
    // Verificar se o usuário participou da partida
    $stmt = $db->getConnection()->prepare("
        SELECT m.*, p.*, u.nome as criador_nome, u.id_usuario as criador_id,
               ac.nota as nota_dada, ac.feedback as feedback_dado
        FROM marcacoes m
        JOIN partidas p ON m.id_partida = p.id_partida
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        LEFT JOIN avaliacoes_criador ac ON (ac.id_partida = m.id_partida AND ac.id_jogador = m.id_usuario AND ac.id_criador = p.id_usuario)
        WHERE m.id_partida = ? AND m.id_usuario = ?
    ");
    $stmt->execute([$id_partida, $user['id_usuario']]);
    $participacao = $stmt->fetch();
    
    if (!$participacao) {
        header("Location: partidas_marcadas.php");
        exit;
    }
    
    // Verificar se a partida já aconteceu
    $data_partida = strtotime($participacao['data'] . ' ' . $participacao['horario']);
    $agora = time();
    
    if ($data_partida > $agora) {
        header("Location: partidas_marcadas.php?error=partida_nao_realizada");
        exit;
    }
    
    // Processar avaliação
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avaliar_criador'])) {
        $nota = (int)$_POST['nota'];
        $feedback = trim($_POST['feedback'] ?? '');
        
        if ($nota < 1 || $nota > 5) {
            $error = 'Nota deve ser entre 1 e 5.';
        } elseif (empty($feedback)) {
            $error = 'Feedback é obrigatório.';
        } else {
            if ($participacao['nota_dada']) {
                // Atualizar avaliação existente
                $stmt = $db->getConnection()->prepare("
                    UPDATE avaliacoes_criador 
                    SET nota = ?, feedback = ?, data_avaliacao = NOW()
                    WHERE id_partida = ? AND id_jogador = ? AND id_criador = ?
                ");
                if ($stmt->execute([$nota, $feedback, $id_partida, $user['id_usuario'], $participacao['criador_id']])) {
                    $success = 'Avaliação atualizada com sucesso!';
                    $participacao['nota_dada'] = $nota;
                    $participacao['feedback_dado'] = $feedback;
                } else {
                    $error = 'Erro ao atualizar avaliação.';
                }
            } else {
                // Inserir nova avaliação
                $stmt = $db->getConnection()->prepare("
                    INSERT INTO avaliacoes_criador (id_partida, id_criador, id_jogador, nota, feedback)
                    VALUES (?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([$id_partida, $participacao['criador_id'], $user['id_usuario'], $nota, $feedback])) {
                    $success = 'Avaliação salva com sucesso!';
                    $participacao['nota_dada'] = $nota;
                    $participacao['feedback_dado'] = $feedback;
                } else {
                    $error = 'Erro ao salvar avaliação.';
                }
            }
        }
    }
    
} catch (PDOException $e) {
    $error = 'Erro no sistema: ' . $e->getMessage();
    $participacao = null;
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
    <title>Avaliar Criador - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
        .avaliacao-card {
            border: 1px solid #00ff00;
            border-radius: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            margin-bottom: 20px;
            padding: 30px;
        }
        
        .criador-info {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .criador-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #00ff00, #00cc00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-weight: bold;
            color: #000;
            font-size: 2rem;
        }
        
        .rating-stars {
            display: flex;
            gap: 8px;
            margin: 15px 0;
        }
        
        .star {
            font-size: 2.5rem;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .star:hover,
        .star.active {
            color: #00ff00;
            text-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }
        
        .avaliacao-existente {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .btn-avaliar {
            background: linear-gradient(45deg, #00cc00, #00ff00);
            border: none;
            color: #000000;
            font-weight: bold;
            border-radius: 8px;
            padding: 12px 25px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 255, 0, 0.25);
            font-size: 1.1rem;
        }
        
        .btn-avaliar:hover {
            background: linear-gradient(45deg, #00ff00, #00cc00);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 255, 0, 0.35);
            color: #000000;
        }
        
        .btn-voltar {
            background: #dc3545;
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
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .partida-info {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            margin-bottom: 10px;
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
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="neon-text">
                            <i class="bi bi-star"></i> Avaliar Criador
                        </h2>
                        <a href="partidas_marcadas.php" class="btn-voltar">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($participacao): ?>
                        <div class="partida-info">
                            <h4 class="neon-text mb-3">
                                <i class="bi bi-info-circle"></i> Informações da Partida
                            </h4>
                            <div class="info-item">
                                <i class="bi bi-calendar"></i>
                                <span class="badge-verde"><?= date('d/m/Y', strtotime($participacao['data'])) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-clock-fill"></i>
                                <span class="badge-verde"><?= htmlspecialchars($participacao['turno']) ?> - <?= date('H:i', strtotime($participacao['horario'])) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-geo-alt"></i>
                                <span><?= htmlspecialchars($participacao['endereco']) ?> - <?= htmlspecialchars($participacao['cidade']) ?>/<?= htmlspecialchars($participacao['estado']) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-dribbble"></i>
                                <span class="badge-azul">Sua posição: <?= getNomePosicao($participacao['posicao']) ?></span>
                            </div>
                        </div>
                        
                        <div class="avaliacao-card">
                            <div class="criador-info">
                                <div class="criador-avatar">
                                    <?= strtoupper(substr($participacao['criador_nome'], 0, 2)) ?>
                                </div>
                                <div>
                                    <h4 class="mb-1 text-white"><?= htmlspecialchars($participacao['criador_nome']) ?></h4>
                                    <p class="text-muted mb-0">Criador da partida</p>
                                </div>
                            </div>
                            
                            <?php if ($participacao['nota_dada']): ?>
                                <div class="avaliacao-existente">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-check-circle"></i> Avaliação Realizada
                                    </h5>
                                    <div class="mb-3">
                                        <strong>Nota:</strong>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= $participacao['nota_dada'] ? '-fill' : '' ?>" style="color: #00ff00; font-size: 1.5rem;"></i>
                                        <?php endfor; ?>
                                        (<?= $participacao['nota_dada'] ?>/5)
                                    </div>
                                    <div class="mb-3">
                                        <strong>Feedback:</strong><br>
                                        <?= htmlspecialchars($participacao['feedback_dado']) ?>
                                    </div>
                                    <button type="button" class="btn btn-warning" onclick="editarAvaliacao()">
                                        <i class="bi bi-pencil"></i> Editar Avaliação
                                    </button>
                                </div>
                                
                                <div id="form-edicao" style="display: none;">
                                    <form method="POST">
                                        <input type="hidden" name="avaliar_criador" value="1">
                                        <input type="hidden" name="nota" id="nota-input" value="<?= $participacao['nota_dada'] ?>">
                                        
                                        <div class="mb-4">
                                            <label class="form-label text-white">
                                                <i class="bi bi-star"></i> Nota para o criador/partida (1 a 5 estrelas)
                                            </label>
                                            <div class="rating-stars" id="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?= $i <= $participacao['nota_dada'] ? '-fill active' : '' ?> star" data-rating="<?= $i ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="feedback" class="form-label text-white">
                                                <i class="bi bi-chat-text"></i> Feedback sobre a partida/criador
                                            </label>
                                            <textarea class="form-control neon-input" id="feedback" name="feedback" rows="4" placeholder="Como foi a experiência na partida? O criador foi organizado? O local estava adequado?" required><?= htmlspecialchars($participacao['feedback_dado']) ?></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn-avaliar">
                                                <i class="bi bi-check"></i> Salvar Alterações
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="cancelarEdicao()">
                                                <i class="bi bi-x"></i> Cancelar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="avaliar_criador" value="1">
                                    <input type="hidden" name="nota" id="nota-input" value="">
                                    
                                    <div class="mb-4">
                                        <label class="form-label text-white">
                                            <i class="bi bi-star"></i> Nota para o criador/partida (1 a 5 estrelas)
                                        </label>
                                        <div class="rating-stars" id="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star star" data-rating="<?= $i ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="feedback" class="form-label text-white">
                                            <i class="bi bi-chat-text"></i> Feedback sobre a partida/criador
                                        </label>
                                        <textarea class="form-control neon-input" id="feedback" name="feedback" rows="4" placeholder="Como foi a experiência na partida? O criador foi organizado? O local estava adequado?" required></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn-avaliar" disabled id="btn-avaliar">
                                        <i class="bi bi-star"></i> Avaliar Criador
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Participação não encontrada ou você não tem permissão para avaliar.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initRatingSystem();
        });
        
        function initRatingSystem() {
            const stars = document.querySelectorAll('.star');
            const notaInput = document.getElementById('nota-input');
            const btnAvaliar = document.getElementById('btn-avaliar');
            
            stars.forEach(function(star) {
                star.addEventListener('click', function() {
                    const rating = parseInt(star.dataset.rating);
                    notaInput.value = rating;
                    
                    // Atualizar visual das estrelas
                    stars.forEach(function(s, i) {
                        if (i < rating) {
                            s.classList.remove('bi-star');
                            s.classList.add('bi-star-fill', 'active');
                        } else {
                            s.classList.remove('bi-star-fill', 'active');
                            s.classList.add('bi-star');
                        }
                    });
                    
                    // Habilitar botão se nota foi selecionada
                    if (btnAvaliar) {
                        btnAvaliar.disabled = false;
                    }
                });
                
                // Efeito hover
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(star.dataset.rating);
                    stars.forEach(function(s, i) {
                        if (i < rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
            });
            
            // Remover hover quando sair do container
            const container = document.getElementById('rating-stars');
            if (container) {
                container.addEventListener('mouseleave', function() {
                    const currentRating = parseInt(notaInput.value) || 0;
                    stars.forEach(function(s, i) {
                        if (i < currentRating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
            }
        }
        
        function editarAvaliacao() {
            document.querySelector('.avaliacao-existente').style.display = 'none';
            document.getElementById('form-edicao').style.display = 'block';
            initRatingSystem();
        }
        
        function cancelarEdicao() {
            document.querySelector('.avaliacao-existente').style.display = 'block';
            document.getElementById('form-edicao').style.display = 'none';
        }
    </script>
</body>
</html>
