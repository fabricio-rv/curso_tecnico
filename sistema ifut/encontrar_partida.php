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

// Função para normalizar texto (remover acentos e converter para minúsculo)
function normalizeString($string) {
    // Remove acentos
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    // Converte para minúsculo
    return strtolower(trim($string));
}

// Processar solicitação de participação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_participacao'])) {
    $partida_id = $_POST['partida_id'];
    $posicao = $_POST['posicao'];
    
    try {
        $db = new Database();
        
        // Verificar se já solicitou participação nesta partida
        $stmt = $db->getConnection()->prepare("SELECT * FROM solicitacoes_participacao WHERE id_usuario = ? AND id_partida = ?");
        $stmt->execute([$user['id_usuario'], $partida_id]);
        
        if ($stmt->fetch()) {
            $message = 'Você já solicitou participação nesta partida!';
            $messageType = 'warning';
        } else {
            // Verificar se já está marcado nesta partida
            $stmt = $db->getConnection()->prepare("SELECT * FROM marcacoes WHERE id_usuario = ? AND id_partida = ?");
            $stmt->execute([$user['id_usuario'], $partida_id]);
            
            if ($stmt->fetch()) {
                $message = 'Você já está participando desta partida!';
                $messageType = 'warning';
            } else {
                // Criar solicitação
                $stmt = $db->getConnection()->prepare("INSERT INTO solicitacoes_participacao (id_usuario, id_partida, posicao) VALUES (?, ?, ?)");
                if ($stmt->execute([$user['id_usuario'], $partida_id, $posicao])) {
                    $message = 'Solicitação enviada com sucesso! Aguarde a aprovação do criador.';
                    $messageType = 'success';
                } else {
                    $message = 'Erro ao enviar solicitação.';
                    $messageType = 'danger';
                }
            }
        }
    } catch (PDOException $e) {
        $message = 'Erro no sistema.';
        $messageType = 'danger';
    }
}

// Buscar partidas disponíveis
$filtro_estado = $_GET['estado'] ?? '';
$filtro_cidade = $_GET['cidade'] ?? '';
$filtro_turno = $_GET['turno'] ?? '';
$filtro_posicao = $_GET['posicao'] ?? '';
$filtro_data = $_GET['data'] ?? '';

try {
    $db = new Database();
    $hoje = date('Y-m-d');
    
    $sql = "SELECT p.*, u.nome as criador_nome,
                   (SELECT COUNT(*) FROM marcacoes m WHERE m.id_partida = p.id_partida) as total_marcados,
                   (SELECT AVG(nota) FROM avaliacoes_criador WHERE id_criador = p.id_usuario) as media_criador,
                   m_user.posicao as minha_posicao,
                   s_user.status as status_solicitacao,
                   s_user.posicao as posicao_solicitada
            FROM partidas p 
            JOIN usuarios u ON p.id_usuario = u.id_usuario 
            LEFT JOIN marcacoes m_user ON (m_user.id_partida = p.id_partida AND m_user.id_usuario = ?)
            LEFT JOIN solicitacoes_participacao s_user ON (s_user.id_partida = p.id_partida AND s_user.id_usuario = ?)
            WHERE (p.data > ? OR (p.data = ? AND p.horario > ?)) 
            AND p.id_usuario != ?";
    
    $hora_atual = date('H:i:s');
    $params = [$user['id_usuario'], $user['id_usuario'], $hoje, $hoje, $hora_atual, $user['id_usuario']];
    
    if ($filtro_estado) {
        $sql .= " AND p.estado = ?";
        $params[] = $filtro_estado;
    }
    
    if ($filtro_cidade) {
        // Modificado para busca case-insensitive e sem acentos
        $sql .= " AND LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
            p.cidade, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u')) 
            LIKE LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
            ?, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'))";
        $params[] = "%$filtro_cidade%";
    }
    
    if ($filtro_turno) {
        $sql .= " AND p.turno = ?";
        $params[] = $filtro_turno;
    }
    
    if ($filtro_posicao) {
        $sql .= " AND FIND_IN_SET(?, p.posicoes_restantes)";
        $params[] = $filtro_posicao;
    }
    
    if ($filtro_data) {
        $sql .= " AND p.data = ?";
        $params[] = $filtro_data;
    }
    
    $sql .= " ORDER BY p.data ASC, p.horario ASC";
    
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute($params);
    $partidas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $partidas = [];
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
    <title>Encontrar Partidas - IFUT</title>
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

    .badge-cinza {
        background-color: #6c757d;
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
    
    .btn-solicitar {
        background-color: #00ff00;
        color: #000;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        margin-top: 10px;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
        font-weight: bold;
    }
    
    .btn-solicitar:hover {
        background-color: #00cc00;
        transform: translateY(-2px);
    }
    
    .btn-completa {
        background-color: #666;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        margin-top: 10px;
        width: 100%;
        cursor: not-allowed;
    }
    
    .partida-disponivel-badge {
        background-color: #00ff00;
        color: #000;
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
    
    .filtros-card {
        background-color: rgba(0, 0, 0, 0.8);
        border: 1px solid #00ff00;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    /* Estilos do modal - PADRÃO ATUALIZADO */
    .modal-confirmacao {
        background-color: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(2px);
    }

    .modal-confirmacao .modal-dialog {
        max-width: 500px;
    }

    .modal-confirmacao .modal-content {
        background-color: #000000;
        border: 2px solid #00ff00;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
    }

    .modal-confirmacao .modal-header {
        border-bottom: 1px solid #00ff00;
        background-color: #000000;
        padding: 15px 20px;
    }

    .modal-confirmacao .modal-title {
        color: #00ff00;
        font-weight: bold;
        text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-confirmacao .modal-body {
        text-align: center;
        padding: 30px;
        color: #ffffff;
        background: #000000;
    }

    .modal-confirmacao .modal-body p {
        font-size: 1.1em;
        margin-bottom: 25px;
        color: #ffffff;
    }

    .modal-confirmacao .icon-success {
        font-size: 4em;
        color: #00ff00;
        margin-bottom: 20px;
        text-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
    }

    .modal-confirmacao .dados-partida {
        background: rgba(0, 255, 0, 0.1);
        border: 1px solid #00ff00;
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        color: #00ff00;
        text-align: left;
    }

    .modal-confirmacao .dados-partida p {
        margin-bottom: 8px;
        color: #ffffff;
        font-size: 1rem;
    }

    .modal-confirmacao .dados-partida strong {
        color: #00ff00;
    }

    .modal-confirmacao .modal-footer {
        border-top: 1px solid #00ff00;
        background-color: #000000;
        padding: 15px 20px;
        justify-content: center;
        gap: 15px;
    }

    .btn-nao-modal {
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

    .btn-nao-modal:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        color: white;
    }

    .btn-sim-modal {
        background: #00ff00;
        color: #000;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: bold;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
        min-width: 120px;
    }

    .btn-sim-modal:hover {
        background: #00cc00;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 255, 0, 0.4);
        color: #000;
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

    @media (max-width: 768px) {
        .modal-confirmacao .modal-dialog {
            margin: 10px;
        }
        
        .modal-confirmacao .modal-body {
            padding: 20px;
        }
        
        .modal-confirmacao .modal-footer {
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-nao-modal, .btn-sim-modal {
            width: 100%;
            min-width: auto;
        }
    }
    
    /* Botões de filtro */
    .btn-buscar {
        background-color: #00ff00;
        color: #000;
        font-weight: bold;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-recarregar {
        background-color: transparent;
        border: 1px solid #00ff00;
        color: #00ff00;
        height: 48px;
        width: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Posição selecionável */
    .campo-futebol-container.selectable .posicao.disponivel {
        cursor: pointer !important;
    }
    
    .campo-futebol-container.selectable .posicao.disponivel:hover {
        transform: scale(1.1);
        box-shadow: 0 0 10px #00ff00;
    }
    
    .campo-futebol-container.selectable .posicao.selecionada {
        border: 3px solid #00ff00;
        box-shadow: 0 0 15px #00ff00;
    }
    
    @media (max-width: 768px) {
        .partida-content {
            flex-direction: column;
        }
        
        .partida-info {
            border-right: none;
            border-bottom: 1px solid rgba(0, 255, 0, 0.3);
            padding-right: 0;
            padding-bottom: 15px;
        }
    }

    /* Estilos dos botões do modal de sucesso */
    .btn-verde-modal {
        background: #00ff00;
        color: #000;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: bold;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
        min-width: 120px;
    }

    .btn-verde-modal:hover {
        background: #00cc00;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 255, 0, 0.4);
        color: #000;
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
        min-width: 120px;
    }

    .btn-azul-modal:hover {
        background: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        color: white;
    }

    /* Estilos do modal de validação */
    .modal-validacao .modal-dialog {
        max-width: 400px;
    }

    .modal-validacao .modal-content {
        background-color: #000000;
        border: 2px solid #00ff00;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
    }

    .modal-validacao .modal-header {
        border-bottom: 1px solid #00ff00;
        background-color: #000000;
        padding: 15px 20px;
    }

    .modal-validacao .modal-title {
        color: #00ff00;
        font-weight: bold;
        text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-validacao .modal-body {
        text-align: center;
        padding: 30px;
        color: #ffffff;
        background: #000000;
    }

    .modal-validacao .modal-body p {
        font-size: 1.1em;
        margin-bottom: 25px;
        color: #ffffff;
    }

    .modal-validacao .icon-warning {
        font-size: 4em;
        color: #00ff00;
        margin-bottom: 20px;
        text-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
    }

    .modal-validacao .modal-footer {
        border-top: 1px solid #00ff00;
        background-color: #000000;
        padding: 15px 20px;
        justify-content: center;
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
                            <i class="bi bi-search"></i> Encontrar Partidas
                        </h2>
                        <a href="criar_partida.php" class="btn btn-success neon-btn">
                            <i class="bi bi-plus-circle"></i> Criar Partida
                        </a>
                    </div>
                    
                    <?php if ($message && $messageType !== 'success'): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filtros Avançados -->
                    <div class="filtros-card">
                        <div class="card-body p-4">
                            <h5 class="neon-text-white mb-3">
                                <i class="bi bi-funnel"></i> Filtros de Busca
                            </h5>
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label for="estado" class="form-label text-success">Estado</label>
                                    <select class="form-select neon-input" id="estado" name="estado">
                                        <option value="">Todos</option>
                                        <option value="AC" <?= $filtro_estado === 'AC' ? 'selected' : '' ?>>Acre</option>
                                        <option value="AL" <?= $filtro_estado === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                                        <option value="AP" <?= $filtro_estado === 'AP' ? 'selected' : '' ?>>Amapá</option>
                                        <option value="AM" <?= $filtro_estado === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                                        <option value="BA" <?= $filtro_estado === 'BA' ? 'selected' : '' ?>>Bahia</option>
                                        <option value="CE" <?= $filtro_estado === 'CE' ? 'selected' : '' ?>>Ceará</option>
                                        <option value="DF" <?= $filtro_estado === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                                        <option value="ES" <?= $filtro_estado === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                                        <option value="GO" <?= $filtro_estado === 'GO' ? 'selected' : '' ?>>Goiás</option>
                                        <option value="MA" <?= $filtro_estado === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                                        <option value="MT" <?= $filtro_estado === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                                        <option value="MS" <?= $filtro_estado === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                                        <option value="MG" <?= $filtro_estado === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                                        <option value="PA" <?= $filtro_estado === 'PA' ? 'selected' : '' ?>>Pará</option>
                                        <option value="PB" <?= $filtro_estado === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                                        <option value="PR" <?= $filtro_estado === 'PR' ? 'selected' : '' ?>>Paraná</option>
                                        <option value="PE" <?= $filtro_estado === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                                        <option value="PI" <?= $filtro_estado === 'PI' ? 'selected' : '' ?>>Piauí</option>
                                        <option value="RJ" <?= $filtro_estado === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                                        <option value="RN" <?= $filtro_estado === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                                        <option value="RS" <?= $filtro_estado === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                                        <option value="RO" <?= $filtro_estado === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                                        <option value="RR" <?= $filtro_estado === 'RR' ? 'selected' : '' ?>>Roraima</option>
                                        <option value="SC" <?= $filtro_estado === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                                        <option value="SP" <?= $filtro_estado === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                                        <option value="SE" <?= $filtro_estado === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                                        <option value="TO" <?= $filtro_estado === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="cidade" class="form-label text-success">Cidade</label>
                                    <input type="text" class="form-control neon-input" id="cidade" name="cidade" 
                                           value="<?= htmlspecialchars($filtro_cidade) ?>" placeholder="Digite a cidade">
                                </div>
                                <div class="col-md-2">
                                    <label for="data" class="form-label text-success">Data</label>
                                    <input type="date" class="form-control neon-input" id="data" name="data" 
                                           value="<?= htmlspecialchars($filtro_data) ?>" min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="turno" class="form-label text-success">Turno</label>
                                    <select class="form-select neon-input" id="turno" name="turno">
                                        <option value="">Todos</option>
                                        <option value="MANHÃ" <?= $filtro_turno === 'MANHÃ' ? 'selected' : '' ?>>Manhã</option>
                                        <option value="TARDE" <?= $filtro_turno === 'TARDE' ? 'selected' : '' ?>>Tarde</option>
                                        <option value="NOITE" <?= $filtro_turno === 'NOITE' ? 'selected' : '' ?>>Noite</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="posicao" class="form-label text-success">Posição</label>
                                    <select class="form-select neon-input" id="posicao" name="posicao">
                                        <option value="">Todas</option>
                                        <option value="GOL" <?= $filtro_posicao === 'GOL' ? 'selected' : '' ?>>Goleiro</option>
                                        <option value="ZAG" <?= $filtro_posicao === 'ZAG' ? 'selected' : '' ?>>Zagueiro</option>
                                        <option value="ALA DIR" <?= $filtro_posicao === 'ALA DIR' ? 'selected' : '' ?>>Ala Direito</option>
                                        <option value="ALA ESQ" <?= $filtro_posicao === 'ALA ESQ' ? 'selected' : '' ?>>Ala Esquerdo</option>
                                        <option value="VOL" <?= $filtro_posicao === 'VOL' ? 'selected' : '' ?>>Volante</option>
                                        <option value="MEI" <?= $filtro_posicao === 'MEI' ? 'selected' : '' ?>>Meia</option>
                                        <option value="ATA" <?= $filtro_posicao === 'ATA' ? 'selected' : '' ?>>Atacante</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-buscar flex-grow-1">
                                            <i class="bi bi-search me-1"></i> BUSCAR
                                        </button>
                                        <a href="encontrar_partida.php" class="btn btn-recarregar">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Partidas -->
                    <h3 class="section-title">Partidas Disponíveis</h3>
                    
                    <?php if (empty($partidas)): ?>
                        <div class="empty-state">
                            <i class="bi bi-search"></i>
                            <h5 class="text-muted">Nenhuma partida encontrada</h5>
                            <p class="text-muted">Tente ajustar os filtros ou crie uma nova partida!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($partidas as $partida): ?>
                            <?php 
// Verificar se o usuário já está marcado nesta partida
$stmt_check = $db->getConnection()->prepare("SELECT posicao FROM marcacoes WHERE id_partida = ? AND id_usuario = ?");
$stmt_check->execute([$partida['id_partida'], $user['id_usuario']]);
$minha_marcacao = $stmt_check->fetch();
$ja_marcado = !empty($minha_marcacao);

// Buscar todas as posições marcadas na partida EXCETO a do usuário atual
$stmt = $db->getConnection()->prepare("SELECT posicao FROM marcacoes WHERE id_partida = ? AND id_usuario != ?");
$stmt->execute([$partida['id_partida'], $user['id_usuario']]);
$outras_marcacoes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$posicoes_restantes = array_filter(explode(',', $partida['posicoes_restantes']));
$tem_vagas = !empty($posicoes_restantes) && !$ja_marcado; // Não tem vagas se já está marcado

// Status do usuário nesta partida
$solicitacao_pendente = $partida['status_solicitacao'] === 'pendente';
$solicitacao_recusada = $partida['status_solicitacao'] === 'recusada';

// Calcular jogadores confirmados: 7 total - posições restantes
$jogadores_confirmados = 7 - count($posicoes_restantes);

// Separar posições para o campo
$posicao_usuario = $ja_marcado ? [$minha_marcacao['posicao']] : [];
?>
                            
                            <div class="partida-card">
                                <div class="partida-content">
                                    <div class="partida-info">
                                        <div class="info-item">
                                            <i class="bi bi-check-circle"></i>
                                            <?php if ($ja_marcado): ?>
        <span class="badge-azul">VOCÊ ESTÁ PARTICIPANDO</span>
    <?php elseif ($solicitacao_pendente): ?>
        <span class="badge-amarelo">SOLICITAÇÃO PENDENTE</span>
    <?php elseif ($solicitacao_recusada): ?>
        <span class="badge-vermelho">SOLICITAÇÃO RECUSADA</span>
    <?php elseif ($tem_vagas): ?>
        <span class="partida-disponivel-badge">VAGAS DISPONÍVEIS</span>
    <?php else: ?>
        <span class="badge-cinza">PARTIDA COMPLETA</span>
    <?php endif; ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-people-fill"></i>
                                            <span class="badge-amarelo"><?= $jogadores_confirmados ?>/7 jogadores confirmados</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-person-plus"></i>
                                            <?php if (count($posicoes_restantes) > 0): ?>
                                                <span class="badge-vermelho"><?= count($posicoes_restantes) ?> vagas restantes</span>
                                            <?php else: ?>
                                                <span class="badge-cinza">0 vagas restantes</span>
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
                                            <i class="bi bi-info-circle"></i>
                                            <a href="detalhes_partida.php?id=<?= $partida['id_partida'] ?>" class="btn-detalhes"><i class="bi bi-eye"></i> DETALHES</a>
                                        </div> 
                                        <?php if ($ja_marcado): ?>
    <div class="btn-completa">
        <i class="bi bi-check-circle"></i> Você já está participando
    </div>
<?php elseif ($solicitacao_pendente): ?>
    <div class="btn-completa">
        <i class="bi bi-clock"></i> Aguardando aprovação
    </div>
<?php elseif ($solicitacao_recusada): ?>
    <div class="btn-completa">
        <i class="bi bi-x-circle"></i> Solicitação recusada
    </div>
<?php elseif ($tem_vagas): ?>
    <button type="button" class="btn-solicitar" id="btn-solicitar-<?= $partida['id_partida'] ?>">
        <i class="bi bi-plus-circle"></i> SOLICITAR PARTICIPAÇÃO
    </button>
    <form method="POST" id="form-solicitar-<?= $partida['id_partida'] ?>">
        <input type="hidden" name="partida_id" value="<?= $partida['id_partida'] ?>">
        <input type="hidden" name="posicao" id="posicao-selecionada-<?= $partida['id_partida'] ?>">
        <input type="hidden" name="solicitar_participacao" value="1">
    </form>
<?php else: ?>
    <div class="btn-completa">
        <i class="bi bi-x-circle"></i> Partida Completa
    </div>
<?php endif; ?>
                                    </div>
                                    <div class="partida-campo">
                                        <div id="campo-partida-<?= $partida['id_partida'] ?>" 
                                             class="campo-futebol-container <?= ($tem_vagas && !$ja_marcado && !$solicitacao_pendente) ? 'selectable' : '' ?>"
                                             data-partida-id="<?= $partida['id_partida'] ?>">
                                            <div class="campo-futebol"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal de Confirmação -->
                            <div class="modal fade modal-confirmacao" id="confirmarModal<?= $partida['id_partida'] ?>" tabindex="-1" data-bs-backdrop="static">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-question-circle"></i> Confirmar Solicitação
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="icon-success">
                                                <i class="bi bi-person-check-fill"></i>
                                            </div>
                                            <p><strong>Tem certeza que deseja solicitar participação nesta partida como <span id="posicao-nome-<?= $partida['id_partida'] ?>"></span>?</strong></p>
                                            
                                            <div class="dados-partida">
                                                <p><i class="bi bi-calendar"></i> <strong>Data:</strong> <?= date('d/m/Y', strtotime($partida['data'])) ?></p>
                                                <p><i class="bi bi-clock"></i> <strong>Horário:</strong> <?= date('H:i', strtotime($partida['horario'])) ?> (<?= htmlspecialchars($partida['turno']) ?>)</p>
                                                <p><i class="bi bi-geo-alt"></i> <strong>Local:</strong> <?= htmlspecialchars($partida['endereco']) ?></p>
                                                <p><i class="bi bi-info-circle"></i> <strong>Observação:</strong> Sua solicitação será enviada ao criador da partida para aprovação.</p>
                                            </div>
                                            
                                            <div class="d-flex justify-content-center gap-3 mt-4">
                                                <button type="button" class="btn-nao-modal" data-bs-dismiss="modal">
                                                    <i class="bi bi-x-circle"></i> CANCELAR
                                                </button>
                                                <button type="button" class="btn-sim-modal" 
                                                        onclick="document.getElementById('form-solicitar-<?= $partida['id_partida'] ?>').submit()">
                                                    <i class="bi bi-check-circle"></i> SOLICITAR
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
document.addEventListener('DOMContentLoaded', function() {
    const campoPartida<?= $partida['id_partida'] ?> = initCampoFutebol('campo-partida-<?= $partida['id_partida'] ?>', {
    readOnly: <?= $ja_marcado ? 'true' : ($tem_vagas ? 'false' : 'true') ?>,
    posicoesSelecionadas: <?= json_encode($posicao_usuario) ?>, // SUA posição em AZUL
    posicoesDisponiveis: <?= json_encode($posicoes_restantes) ?>, // Posições livres em VERDE
    posicoesOcupadas: <?= json_encode($outras_marcacoes) ?>, // Posições de outros em VERMELHO
    multiSelect: false,
    onChange: function(selecionadas) {
        const inputPosicao = document.getElementById('posicao-selecionada-<?= $partida['id_partida'] ?>');
        
        if (selecionadas.length > 0) {
            inputPosicao.value = selecionadas[0];
        } else {
            inputPosicao.value = '';
        }
    }
});
    
    // Configurar evento do botão solicitar participação
    const btnSolicitar = document.getElementById('btn-solicitar-<?= $partida['id_partida'] ?>');
    if (btnSolicitar) {
        btnSolicitar.onclick = function() {
            const inputPosicao = document.getElementById('posicao-selecionada-<?= $partida['id_partida'] ?>');
            
            if (!inputPosicao.value || inputPosicao.value === '') {
                // Mostrar modal de validação
                new bootstrap.Modal(document.getElementById('modalValidacao')).show();
            } else {
                const posicaoNome = getNomePosicao(inputPosicao.value);
                document.getElementById('posicao-nome-<?= $partida['id_partida'] ?>').textContent = posicaoNome;
                new bootstrap.Modal(document.getElementById('confirmarModal<?= $partida['id_partida'] ?>')).show();
            }
        };
    }
});

// Função para converter posição abreviada para nome completo
function getNomePosicao(posicao) {
    const posicoes = {
        'GOL': 'GOLEIRO',
        'ZAG': 'ZAGUEIRO', 
        'ALA ESQ': 'ALA ESQUERDO',
        'ALA DIR': 'ALA DIREITO',
        'VOL': 'VOLANTE',
        'MEI': 'MEIA',
        'ATA': 'ATACANTE'
    };
    
    return posicoes[posicao] || posicao.toUpperCase();
}
</script>
                        <?php endforeach; ?>

<!-- Modal de Validação Global -->
<div class="modal fade modal-validacao" id="modalValidacao" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i> Atenção
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="icon-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <p><strong>Por favor, selecione a posição que você deseja jogar no campinho para solicitar participação.</strong></p>
                
                <div class="d-flex justify-content-center mt-4">
                    <button type="button" class="btn-sim-modal" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Sucesso -->
<div class="modal fade modal-confirmacao" id="modalSucesso" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle"></i> Sucesso
                </h5>
            </div>
            <div class="modal-body">
                <div class="icon-success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <p><strong>Solicitação enviada com sucesso!</strong></p>
                <p>Aguarde a aprovação do criador da partida. Você pode acompanhar o status em "Partidas Marcadas".</p>
                
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn-verde-modal" onclick="window.location.href='encontrar_partida.php'">
                        <i class="bi bi-search"></i> BUSCAR MAIS PARTIDAS
                    </button>
                    <button type="button" class="btn-azul-modal" onclick="window.location.href='partidas_marcadas.php'">
                        <i class="bi bi-calendar-check"></i> VER MINHAS PARTIDAS
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script src="JS/campo-futebol.js"></script>
    <script>
// Mostrar modal de sucesso se necessário
<?php if ($message && $messageType === 'success'): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const modalSucesso = new bootstrap.Modal(document.getElementById('modalSucesso'));
        modalSucesso.show();
    });
<?php endif; ?>
</script>
</body>
</html>
