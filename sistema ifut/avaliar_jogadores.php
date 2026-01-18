<?php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

$user = getUser();
$message = '';
$messageType = '';

// Verificar se o ID da partida foi fornecido
if (!isset($_GET['partida']) || !is_numeric($_GET['partida'])) {
    header("Location: partidas_criadas.php");
    exit;
}

$id_partida = (int)$_GET['partida'];

// Processar avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avaliar_jogador'])) {
    $id_jogador = $_POST['id_jogador'];
    $nota = (int)$_POST['nota'];
    $feedback = trim($_POST['feedback'] ?? '');
    
    if ($nota < 1 || $nota > 5) {
        $message = 'A nota deve estar entre 1 e 5.';
        $messageType = 'danger';
    } elseif (empty($feedback)) {
        $message = 'O feedback é obrigatório.';
        $messageType = 'danger';
    } else {
        try {
            $db = new Database();
            
            // Verificar se já avaliou este jogador nesta partida
            $stmt = $db->getConnection()->prepare("
                SELECT * FROM avaliacoes_jogador 
                WHERE id_partida = ? AND id_jogador = ? AND id_criador = ?
            ");
            $stmt->execute([$id_partida, $id_jogador, $user['id_usuario']]);
            
            if ($stmt->fetch()) {
                // Atualizar avaliação existente
                $stmt = $db->getConnection()->prepare("
                    UPDATE avaliacoes_jogador 
                    SET nota = ?, feedback = ?, data_avaliacao = NOW()
                    WHERE id_partida = ? AND id_jogador = ? AND id_criador = ?
                ");
                $success = $stmt->execute([$nota, $feedback, $id_partida, $id_jogador, $user['id_usuario']]);
            } else {
                // Criar nova avaliação
                $stmt = $db->getConnection()->prepare("
                    INSERT INTO avaliacoes_jogador (id_partida, id_jogador, id_criador, nota, feedback)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $success = $stmt->execute([$id_partida, $id_jogador, $user['id_usuario'], $nota, $feedback]);
            }
            
            if ($success) {
                $message = 'Avaliação salva com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao salvar avaliação.';
                $messageType = 'danger';
            }
        } catch (PDOException $e) {
            $message = 'Erro no sistema.';
            $messageType = 'danger';
        }
    }
}

try {
    $db = new Database();
    
    // Buscar dados da partida
    $stmt = $db->getConnection()->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM marcacoes m WHERE m.id_partida = p.id_partida) as total_jogadores
        FROM partidas p 
        WHERE p.id_partida = ? AND p.id_usuario = ?
    ");
    $stmt->execute([$id_partida, $user['id_usuario']]);
    $partida = $stmt->fetch();
    
    if (!$partida) {
        header("Location: partidas_criadas.php");
        exit;
    }
    
    // Verificar se a partida já aconteceu
    $hoje = date('Y-m-d');
    $hora_atual = date('H:i:s');
    $partida_passou = ($partida['data'] < $hoje) || ($partida['data'] == $hoje && $partida['horario'] <= $hora_atual);
    
    if (!$partida_passou) {
        header("Location: partidas_criadas.php?error=partida_nao_realizada");
        exit;
    }
    
    // Buscar jogadores que participaram
    $stmt = $db->getConnection()->prepare("
        SELECT m.*, u.nome, u.telefone, u.email,
               aj.nota, aj.feedback, aj.data_avaliacao
        FROM marcacoes m
        JOIN usuarios u ON m.id_usuario = u.id_usuario
        LEFT JOIN avaliacoes_jogador aj ON (aj.id_partida = m.id_partida AND aj.id_jogador = m.id_usuario AND aj.id_criador = ?)
        WHERE m.id_partida = ?
        ORDER BY u.nome ASC
    ");
    $stmt->execute([$user['id_usuario'], $id_partida]);
    $jogadores = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $partida = null;
    $jogadores = [];
}

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
    <title>Avaliar Jogadores - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
        .jogador-card {
            border: 1px solid #00ff00;
            border-radius: 10px;
            margin-bottom: 20px;
            background-color: rgba(0, 0, 0, 0.7);
            transition: all 0.3s ease;
        }
        
        .jogador-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 255, 0, 0.2);
        }
        
        .jogador-info {
            padding: 20px;
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
        
        .badge-posicao {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.85rem;
        }
        
        .rating-stars {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .star:hover,
        .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }
        
        .avaliacao-existente {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .avaliacao-existente h6 {
            color: #00ff00;
            margin-bottom: 10px;
        }
        
        .nota-display {
            display: flex;
            gap: 2px;
            margin-bottom: 10px;
        }
        
        .nota-display .star {
            font-size: 1.2rem;
            cursor: default;
        }
        
        .nota-display .star:hover {
            transform: none;
        }
        
        .btn-avaliar {
            background-color: #00ff00;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-avaliar:hover {
            background-color: #00cc00;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 255, 0, 0.4);
        }
        
        .btn-voltar {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-voltar:hover {
            background-color: #c82333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            text-decoration: none;
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
        
        .partida-info-card {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px dashed rgba(0, 255, 0, 0.3);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: rgba(0, 255, 0, 0.5);
            margin-bottom: 15px;
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
                            <i class="bi bi-star"></i> Avaliar Jogadores
                        </h2>
                        <a href="partidas_criadas.php" class="btn-voltar">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($partida): ?>
                        <!-- Informações da Partida -->
                        <div class="partida-info-card">
                            <h4 class="neon-text-white mb-3">
                                <i class="bi bi-info-circle"></i> Informações da Partida
                            </h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <i class="bi bi-calendar"></i>
                                        <span><?= date('d/m/Y', strtotime($partida['data'])) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-clock"></i>
                                        <span><?= htmlspecialchars($partida['turno']) ?> - <?= date('H:i', strtotime($partida['horario'])) ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <i class="bi bi-geo-alt"></i>
                                        <span><?= htmlspecialchars($partida['cidade']) ?> - <?= htmlspecialchars($partida['estado']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-people"></i>
                                        <span><?= $partida['total_jogadores'] ?> jogador(es) participaram</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Jogadores -->
                        <h3 class="section-title">Jogadores que Participaram</h3>
                        
                        <?php if (empty($jogadores)): ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h5 class="text-muted">Nenhum jogador participou</h5>
                                <p class="text-muted">Não há jogadores para avaliar nesta partida.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($jogadores as $jogador): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="jogador-card">
                                            <div class="jogador-info">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h5 class="neon-text-white"><?= htmlspecialchars($jogador['nome']) ?></h5>
                                                    <span class="badge-posicao"><?= getNomePosicao($jogador['posicao']) ?></span>
                                                </div>
                                                
                                                <div class="info-item">
                                                    <i class="bi bi-envelope"></i>
                                                    <span><?= htmlspecialchars($jogador['email']) ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="bi bi-phone"></i>
                                                    <span><?= htmlspecialchars($jogador['telefone']) ?></span>
                                                </div>
                                                
                                                <?php if ($jogador['nota']): ?>
                                                    <!-- Avaliação Existente -->
                                                    <div class="avaliacao-existente">
                                                        <h6><i class="bi bi-check-circle"></i> Avaliação Realizada</h6>
                                                        <div class="nota-display">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="bi bi-star<?= $i <= $jogador['nota'] ? '-fill' : '' ?> star <?= $i <= $jogador['nota'] ? 'active' : '' ?>"></i>
                                                            <?php endfor; ?>
                                                            <span class="ms-2">(<?= $jogador['nota'] ?>/5)</span>
                                                        </div>
                                                        <p class="mb-2"><strong>Feedback:</strong></p>
                                                        <p class="mb-1"><?= htmlspecialchars($jogador['feedback']) ?></p>
                                                        <small class="text-muted">Avaliado em <?= date('d/m/Y H:i', strtotime($jogador['data_avaliacao'])) ?></small>
                                                        
                                                        <button type="button" class="btn btn-warning btn-sm mt-2" onclick="editarAvaliacao(<?= $jogador['id_usuario'] ?>, <?= $jogador['nota'] ?>, '<?= addslashes($jogador['feedback']) ?>')">
                                                            <i class="bi bi-pencil"></i> Editar Avaliação
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Formulário de Avaliação -->
                                                    <form method="POST" class="mt-3">
                                                        <input type="hidden" name="id_jogador" value="<?= $jogador['id_usuario'] ?>">
                                                        <input type="hidden" name="avaliar_jogador" value="1">
                                                        
                                                        <label class="form-label text-success">
                                                            <i class="bi bi-star"></i> Nota (1 a 5 estrelas)
                                                        </label>
                                                        <div class="rating-stars" data-jogador="<?= $jogador['id_usuario'] ?>">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="bi bi-star star" data-rating="<?= $i ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <input type="hidden" name="nota" id="nota_<?= $jogador['id_usuario'] ?>" required>
                                                        
                                                        <div class="mb-3">
                                                            <label for="feedback_<?= $jogador['id_usuario'] ?>" class="form-label text-success">
                                                                <i class="bi bi-chat-text"></i> Feedback sobre a participação
                                                            </label>
                                                            <textarea class="form-control neon-input" id="feedback_<?= $jogador['id_usuario'] ?>" name="feedback" rows="3" 
                                                                      placeholder="Descreva como foi a participação deste jogador..." required></textarea>
                                                        </div>
                                                        
                                                        <button type="submit" class="btn-avaliar">
                                                            <i class="bi bi-star"></i> Avaliar Jogador
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Avaliação -->
    <div class="modal fade" id="editarAvaliacaoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: #000; border: 2px solid #00ff00;">
                <div class="modal-header" style="border-bottom: 1px solid #00ff00;">
                    <h5 class="modal-title" style="color: #00ff00;">
                        <i class="bi bi-pencil"></i> Editar Avaliação
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editarAvaliacaoForm">
                    <div class="modal-body" style="color: white;">
                        <input type="hidden" name="id_jogador" id="edit_id_jogador">
                        <input type="hidden" name="avaliar_jogador" value="1">
                        
                        <label class="form-label text-success">
                            <i class="bi bi-star"></i> Nota (1 a 5 estrelas)
                        </label>
                        <div class="rating-stars" id="edit-rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star star" data-rating="<?= $i ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="nota" id="edit_nota" required>
                        
                        <div class="mb-3">
                            <label for="edit_feedback" class="form-label text-success">
                                <i class="bi bi-chat-text"></i> Feedback sobre a participação
                            </label>
                            <textarea class="form-control neon-input" id="edit_feedback" name="feedback" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #00ff00;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar sistema de estrelas para todos os formulários
            document.querySelectorAll('.rating-stars').forEach(function(container) {
                const stars = container.querySelectorAll('.star');
                const jogadorId = container.getAttribute('data-jogador') || 'edit';
                const notaInput = document.getElementById('nota_' + jogadorId) || document.getElementById('edit_nota');
                
                stars.forEach(function(star, index) {
                    star.addEventListener('click', function() {
                        const rating = parseInt(this.getAttribute('data-rating'));
                        
                        // Atualizar input hidden
                        if (notaInput) {
                            notaInput.value = rating;
                        }
                        
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
                    });
                    
                    // Efeito hover
                    star.addEventListener('mouseenter', function() {
                        const rating = parseInt(this.getAttribute('data-rating'));
                        stars.forEach(function(s, i) {
                            if (i < rating) {
                                s.classList.add('active');
                            } else {
                                s.classList.remove('active');
                            }
                        });
                    });
                });
                
                // Restaurar estado ao sair do hover
                container.addEventListener('mouseleave', function() {
                    const currentRating = notaInput ? parseInt(notaInput.value) || 0 : 0;
                    stars.forEach(function(s, i) {
                        if (i < currentRating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
            });
        });
        
        function editarAvaliacao(idJogador, nota, feedback) {
            // Preencher modal
            document.getElementById('edit_id_jogador').value = idJogador;
            document.getElementById('edit_nota').value = nota;
            document.getElementById('edit_feedback').value = feedback;
            
            // Atualizar estrelas
            const stars = document.querySelectorAll('#edit-rating-stars .star');
            stars.forEach(function(star, index) {
                if (index < nota) {
                    star.classList.remove('bi-star');
                    star.classList.add('bi-star-fill', 'active');
                } else {
                    star.classList.remove('bi-star-fill', 'active');
                    star.classList.add('bi-star');
                }
            });
            
            // Mostrar modal
            new bootstrap.Modal(document.getElementById('editarAvaliacaoModal')).show();
        }
    </script>
</body>
</html>
