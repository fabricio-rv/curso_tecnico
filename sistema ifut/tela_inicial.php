<?php
require_once 'includes/session.php';
require_once 'config/database.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = getUser();

// Buscar estatísticas do usuário
try {
    $db = new Database();
    $hoje = date('Y-m-d');
    $hora_atual = date('H:i:s');
    
    // Partidas criadas
    $stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM partidas WHERE id_usuario = ?");
    $stmt->execute([$user['id_usuario']]);
    $partidas_criadas = $stmt->fetchColumn();
    
    // Partidas participando (futuras)
    $stmt = $db->getConnection()->prepare("
        SELECT COUNT(*) FROM marcacoes m
        JOIN partidas p ON m.id_partida = p.id_partida
        WHERE m.id_usuario = ? AND (p.data > ? OR (p.data = ? AND p.horario > ?))
    ");
    $stmt->execute([$user['id_usuario'], $hoje, $hoje, $hora_atual]);
    $partidas_participando = $stmt->fetchColumn();
    
    // Total de jogos (incluindo passados)
    $stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM marcacoes WHERE id_usuario = ?");
    $stmt->execute([$user['id_usuario']]);
    $total_jogos = $stmt->fetchColumn();
    
    // Próximas partidas - QUERY SIMPLIFICADA E CORRIGIDA
    $stmt = $db->getConnection()->prepare("
        SELECT DISTINCT p.*, u.nome as criador_nome,
               CASE 
                   WHEN p.id_usuario = ? THEN 'criada'
                   ELSE 'participando'
               END as tipo_participacao
        FROM partidas p 
        JOIN usuarios u ON p.id_usuario = u.id_usuario 
        LEFT JOIN marcacoes m ON p.id_partida = m.id_partida AND m.id_usuario = ?
        WHERE (p.id_usuario = ? OR m.id_usuario = ?) 
        AND (p.data > ? OR (p.data = ? AND p.horario > ?))
        ORDER BY p.data ASC, p.horario ASC 
        LIMIT 5
    ");
    $stmt->execute([
        $user['id_usuario'], // Para o CASE
        $user['id_usuario'], // Para o LEFT JOIN
        $user['id_usuario'], // Para partidas criadas
        $user['id_usuario'], // Para partidas participando
        $hoje, $hoje, $hora_atual // Para filtro de data
    ]);
    $proximas_partidas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $partidas_criadas = 0;
    $partidas_participando = 0;
    $total_jogos = 0;
    $proximas_partidas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela Inicial - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
        .stat-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 255, 0, 0.3);
            background: rgba(0, 0, 0, 0.6);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 255, 0, 0.2);
            border-color: #00ff00;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: #00ff00;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }
        
        .action-card {
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 255, 0, 0.2);
        }
        
        .action-icon {
            font-size: 3rem;
            color: #00ff00;
            margin-bottom: 15px;
        }
        
        .section-title {
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

        /* BADGES PADRONIZADOS E MAIORES */
        .badge-participando {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .badge-criado {
            background-color: #ffc107;
            color: #000;
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        /* BOTÃO PARA CADA PARTIDA */
        .btn-ver-partida {
            background-color: #00ff00;
            color: #000;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.85rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }

        .btn-ver-partida:hover {
            background-color: #00cc00;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 255, 0, 0.3);
        }

        .partida-card {
            position: relative;
            transition: all 0.3s ease;
        }

        .partida-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 255, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col">
                    <h1 class="neon-text">Bem-vindo, <?= htmlspecialchars($user['nome']) ?>! ⚽</h1>
                    <p class="text-muted">Gerencie suas partidas e encontre novos jogos</p>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="row mb-5">
                <div class="col-md-4 mb-3">
                    <div class="card stat-card text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-plus-circle stat-icon"></i>
                            <h3 class="stat-value"><?= $partidas_criadas ?></h3>
                            <p class="stat-label">Partidas Criadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-calendar-check stat-icon"></i>
                            <h3 class="stat-value"><?= $partidas_participando ?></h3>
                            <p class="stat-label">Participando</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stat-card text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-trophy stat-icon"></i>
                            <h3 class="stat-value"><?= $total_jogos ?></h3>
                            <p class="stat-label">Total de Jogos</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="row mb-4">
                <div class="col">
                    <h3 class="neon-text-white section-title">Ações Rápidas</h3>
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6 mb-3">
                    <a href="criar_partida.php" class="card neon-card action-card text-decoration-none h-100">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-plus-circle action-icon"></i>
                            <h5 class="neon-text-white">Criar Nova Partida</h5>
                            <p class="text-muted">Organize um novo jogo</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="encontrar_partida.php" class="card neon-card action-card text-decoration-none h-100">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-search action-icon"></i>
                            <h5 class="neon-text-white">Encontrar Partidas</h5>
                            <p class="text-muted">Busque jogos próximos</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Próximas Partidas -->
            <div class="row">
                <div class="col">
                    <h3 class="neon-text-white section-title">Próximas Partidas</h3>
                    <?php if (empty($proximas_partidas)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h5 class="text-muted">Nenhuma partida agendada</h5>
                            <p class="text-muted">Crie uma nova partida ou encontre jogos para participar!</p>
                            <div class="d-flex gap-2 justify-content-center mt-3">
                                <a href="criar_partida.php" class="btn btn-success">Criar Partida</a>
                                <a href="encontrar_partida.php" class="btn btn-outline-success">Encontrar Jogos</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($proximas_partidas as $partida): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card neon-card partida-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="neon-text-white"><?= htmlspecialchars($partida['cidade']) ?> - <?= htmlspecialchars($partida['estado']) ?></h6>
                                                <?php if ($partida['tipo_participacao'] == 'criada'): ?>
                                                    <span class="badge-criado">Criada por você</span>
                                                <?php else: ?>
                                                    <span class="badge-participando">Participando</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($partida['endereco']) ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($partida['data'])) ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-clock"></i> <?= date('H:i', strtotime($partida['horario'])) ?>
                                            </p>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-person"></i> Criada por <?= htmlspecialchars($partida['criador_nome']) ?>
                                            </p>
                                            
                                            <!-- BOTÃO INDIVIDUAL PARA CADA PARTIDA -->
                                            <?php if ($partida['tipo_participacao'] == 'criada'): ?>
                                                <a href="partidas_criadas.php" class="btn-ver-partida">
                                                    <i class="bi bi-eye"></i> Ver Partida
                                                </a>
                                            <?php else: ?>
                                                <a href="partidas_marcadas.php" class="btn-ver-partida">
                                                    <i class="bi bi-eye"></i> Ver Partida
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
</body>
</html>
