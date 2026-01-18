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

// Processar edição de partida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_partida'])) {
    $partida_id = $_POST['partida_id'];
    $estado = trim($_POST['estado'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $nome_local = trim($_POST['nome_local'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $contato_local = trim($_POST['contato_local'] ?? '');
    $data_partida = $_POST['data_partida'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $turno = $_POST['turno'] ?? '';
    $nivel_jogo = trim($_POST['nivel_jogo'] ?? '');
    $valor_por_pessoa = $_POST['valor_por_pessoa'] ?? '';
    $duracao_minutos = $_POST['duracao_minutos'] ?? '';
    $idade_minima = $_POST['idade_minima'] ?? '';
    $idade_maxima = $_POST['idade_maxima'] ?? '';
    $intervalo = isset($_POST['intervalo']);
    $arbitro_incluso = isset($_POST['arbitro_incluso']);
    $nivel_minimo = $_POST['nivel_minimo'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $posicoes_disponiveis = $_POST['posicoes_disponiveis'] ?? '';
    $tem_vestiario = isset($_POST['tem_vestiario']);
    $tem_chuveiro = isset($_POST['tem_chuveiro']);
    $tem_estacionamento = isset($_POST['tem_estacionamento']);
    
    // Validações
    if (empty($estado) || empty($cidade) || empty($bairro) || empty($endereco) || empty($nome_local) || empty($cep) || empty($contato_local) || empty($data_partida) || empty($horario) || empty($turno) || empty($nivel_jogo) || empty($duracao_minutos) || empty($idade_minima) || empty($idade_maxima)) {
        header("Location: partidas_criadas.php?error=preenchimento");
        exit;
    } elseif (empty($posicoes_disponiveis)) {
        header("Location: partidas_criadas.php?error=posicao");
        exit;
    } else {
        try {
            $db = new Database();
            
            // Buscar dados originais da partida
            $stmt = $db->getConnection()->prepare("SELECT * FROM partidas WHERE id_partida = ? AND id_usuario = ?");
            $stmt->execute([$partida_id, $user['id_usuario']]);
            $partida_original = $stmt->fetch();
            
            if ($partida_original) {
                // Verificar se houve mudanças
                $houve_mudancas = (
                    $partida_original['estado'] !== $estado ||
                    $partida_original['cidade'] !== $cidade ||
                    $partida_original['bairro'] !== $bairro ||
                    $partida_original['endereco'] !== $endereco ||
                    $partida_original['nome_local'] !== $nome_local ||
                    $partida_original['cep'] !== $cep ||
                    $partida_original['contato_local'] !== $contato_local ||
                    $partida_original['data'] !== $data_partida ||
                    $partida_original['horario'] !== $horario ||
                    $partida_original['turno'] !== $turno ||
                    $partida_original['nivel_jogo'] !== $nivel_jogo ||
                    $partida_original['valor_por_pessoa'] !== $valor_por_pessoa ||
                    $partida_original['duracao_minutos'] !== $duracao_minutos ||
                    $partida_original['idade_minima'] !== $idade_minima ||
                    $partida_original['idade_maxima'] !== $idade_maxima ||
                    ($partida_original['intervalo'] ?? false) !== $intervalo ||
                    ($partida_original['arbitro_incluso'] ?? false) !== $arbitro_incluso ||
                    $partida_original['nivel_minimo'] !== $nivel_minimo ||
                    $partida_original['descricao'] !== $descricao ||
                    $partida_original['posicoes_restantes'] !== $posicoes_disponiveis ||
                    ($partida_original['tem_vestiario'] ?? false) !== $tem_vestiario ||
                    ($partida_original['tem_chuveiro'] ?? false) !== $tem_chuveiro ||
                    ($partida_original['tem_estacionamento'] ?? false) !== $tem_estacionamento
                );
                
                // Atualizar partida
                $stmt = $db->getConnection()->prepare("
                    UPDATE partidas 
                    SET estado = ?, cidade = ?, bairro = ?, endereco = ?, nome_local = ?, cep = ?, contato_local = ?, data = ?, horario = ?, turno = ?, nivel_jogo = ?, valor_por_pessoa = ?, duracao_minutos = ?, idade_minima = ?, idade_maxima = ?, intervalo = ?, arbitro_incluso = ?, nivel_minimo = ?, descricao = ?, posicoes_restantes = ?, tem_vestiario = ?, tem_chuveiro = ?, tem_estacionamento = ?
                    WHERE id_partida = ? AND id_usuario = ?
                ");
                
                if ($stmt->execute([$estado, $cidade, $bairro, $endereco, $nome_local, $cep, $contato_local, $data_partida, $horario, $turno, $nivel_jogo, $valor_por_pessoa, $duracao_minutos, $idade_minima, $idade_maxima, $intervalo, $arbitro_incluso, $nivel_minimo, $descricao, $posicoes_disponiveis, $tem_vestiario, $tem_chuveiro, $tem_estacionamento, $partida_id, $user['id_usuario']])) {
                    if ($houve_mudancas) {
                        header("Location: partidas_criadas.php?success=alterada");
                    } else {
                        header("Location: partidas_criadas.php?info=nenhuma");
                    }
                    exit;
                } else {
                    header("Location: partidas_criadas.php?error=salvar");
                    exit;
                }
            } else {
                header("Location: partidas_criadas.php?error=notfound");
                exit;
            }
        } catch (PDOException $e) {
            header("Location: partidas_criadas.php?error=sistema");
            exit;
        }
    }
}

// Processar exclusão de partida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_partida'])) {
    $partida_id = $_POST['partida_id'];
    
    try {
        $db = new Database();
        
        // Verificar se a partida tem marcações
        $stmt = $db->getConnection()->prepare("SELECT COUNT(*) as total FROM marcacoes WHERE id_partida = ?");
        $stmt->execute([$partida_id]);
        $marcacoes = $stmt->fetch();
        
        if ($marcacoes['total'] > 0) {
            $message = 'Não é possível excluir partidas que já têm jogadores confirmados.';
            $messageType = 'warning';
        } else {
            $stmt = $db->getConnection()->prepare("DELETE FROM partidas WHERE id_partida = ? AND id_usuario = ?");
            if ($stmt->execute([$partida_id, $user['id_usuario']])) {
                header("Location: partidas_criadas.php?success=excluida");
                exit;
            } else {
                $message = 'Erro ao excluir partida.';
                $messageType = 'danger';
            }
        }
    } catch (PDOException $e) {
        $message = 'Erro no sistema.';
        $messageType = 'danger';
    }
}

// Buscar partidas criadas pelo usuário
try {
    $db = new Database();
    $hoje = date('Y-m-d');
    
    // Partidas ativas (futuras)
    $stmt = $db->getConnection()->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM marcacoes m WHERE m.id_partida = p.id_partida) as total_marcados,
               (SELECT GROUP_CONCAT(m.posicao) FROM marcacoes m WHERE m.id_partida = p.id_partida) as posicoes_marcadas,
               (SELECT COUNT(*) FROM solicitacoes_participacao s WHERE s.id_partida = p.id_partida AND s.status = 'pendente') as solicitacoes_pendentes
        FROM partidas p 
        WHERE p.id_usuario = ? AND (p.data > ? OR (p.data = ? AND p.horario > ?))
        ORDER BY p.data ASC, p.horario ASC
    ");
    $hora_atual = date('H:i:s');
    $stmt->execute([$user['id_usuario'], $hoje, $hoje, $hora_atual]);
    $partidas_ativas = $stmt->fetchAll();
    
    // Partidas realizadas (passadas)
    $stmt = $db->getConnection()->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM marcacoes m WHERE m.id_partida = p.id_partida) as total_marcados,
               (SELECT GROUP_CONCAT(m.posicao) FROM marcacoes m WHERE m.id_partida = p.id_partida) as posicoes_marcadas,
               (SELECT COUNT(*) FROM avaliacoes_jogador aj WHERE aj.id_partida = p.id_partida AND aj.id_criador = p.id_usuario) as avaliacoes_feitas,
               (SELECT COUNT(*) FROM marcacoes m WHERE m.id_partida = p.id_partida) as total_jogadores_participaram
        FROM partidas p 
        WHERE p.id_usuario = ? AND (p.data < ? OR (p.data = ? AND p.horario <= ?))
        ORDER BY p.data DESC, p.horario DESC
    ");
    $stmt->execute([$user['id_usuario'], $hoje, $hoje, $hora_atual]);
    $partidas_realizadas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $partidas_ativas = [];
    $partidas_realizadas = [];
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
    <title>Partidas Criadas - IFUT</title>
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
    
    /* Cursor de bloqueio para todas as posições do campo nas partidas criadas */
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

    .badge-laranja {
        background-color: #ff8c00;
        color: white;
        font-weight: bold;
        padding: 5px 10px;
        border-radius: 5px;
        margin-right: 10px;
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

    /* Botões de ação especiais */
    .btn-solicitacoes {
        background-color: #ffc107;
        color: #000;
        font-weight: bold;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        text-align: center;
        margin-right: 10px;
    }

    .btn-solicitacoes:hover {
        background-color: #e0a800;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        color: #000;
        text-decoration: none;
    }

    .btn-avaliar-jogadores {
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
    }

    .btn-avaliar-jogadores:hover {
        background-color: #138496;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-acoes {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }
    
    .btn-editar, .btn-excluir {
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        flex: 1;
        font-size: 1rem;
        font-weight: bold;
        width: 50%;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    .btn-editar {
        background-color: #ffc107;
        color: #000;
    }
    
    .btn-editar:hover {
        background-color: #e0a800;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
    }
    
    .btn-excluir {
        background-color: #dc3545;
        color: white;
    }
    
    .btn-excluir:hover {
        background-color: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }
    
    .btn-excluir:disabled {
        background-color: #666;
        cursor: not-allowed;
        transform: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    .aviso-exclusao {
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
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 5px;
        margin-right: 10px;
        font-size: 0.85rem;
    }
    
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

    /* Notificação de solicitações pendentes */
    .notification-badge {
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.75rem;
        position: relative;
        top: -2px;
        margin-left: 5px;
    }

    /* Status de avaliações */
    .avaliacoes-status {
        background: rgba(23, 162, 184, 0.1);
        border: 1px solid #17a2b8;
        border-radius: 5px;
        padding: 8px;
        margin-top: 10px;
        font-size: 0.9rem;
    }
    
    /* Estilos do modal de edição */
    .modal-edicao {
        background-color: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(2px);
    }

    .modal-edicao .modal-content {
        background-color: #000000;
        border: 2px solid #00ff00;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
    }
    
    .modal-edicao .modal-header {
        border-bottom: 1px solid #00ff00;
        background-color: #000000;
        padding: 15px 20px;
    }
    
    .modal-edicao .modal-title {
        color: #00ff00;
        font-weight: bold;
        text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .modal-edicao .modal-body {
        color: white;
        background-color: #000000;
        padding: 20px;
    }
    
    .modal-edicao .form-label {
        color: #00ff00;
        font-weight: bold;
    }

    .modal-edicao .neon-input {
        background-color: rgba(0, 0, 0, 0.8);
        border: 1px solid #00ff00;
        color: #ffffff;
        border-radius: 5px;
    }

    .modal-edicao .neon-input:focus {
        background-color: rgba(0, 0, 0, 0.9);
        border-color: #00ff00;
        box-shadow: 0 0 10px rgba(0, 255, 0, 0.3);
        color: #ffffff;
    }

    .modal-edicao .text-muted {
        color: #cccccc !important;
    }

    .modal-edicao .modal-footer {
        border-top: 1px solid #00ff00;
        background-color: #000000;
        padding: 15px 20px;
        gap: 15px;
    }

    .modal-edicao .btn-voltar {
        background: #dc3545;
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 1.1rem;
        transition: all 0.3s;
        min-width: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    .modal-edicao .btn-voltar:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        color: white;
    }

    .modal-edicao .btn-salvar {
        background: #00ff00;
        color: #000;
        border: none;
        padding: 14px 30px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 1.1rem;
        transition: all 0.3s;
        min-width: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
    }

    .modal-edicao .btn-salvar:hover {
        background: #00cc00;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 255, 0, 0.4);
        color: #000;
    }

/* Modal de Confirmação de Exclusão */
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

.modal-confirmacao .partida-info-exclusao {
    background: rgba(0, 255, 0, 0.1);
    border: 1px solid #00ff00;
    border-radius: 8px;
    padding: 15px;
    margin: 20px 0;
    color: #00ff00;
    text-align: left;
}

.modal-confirmacao .partida-info-exclusao div {
    margin-bottom: 8px;
}

.modal-confirmacao .partida-info-exclusao i {
    color: #00ff00;
    margin-right: 10px;
}

.modal-confirmacao .alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    color: #ffc107;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

.modal-confirmacao .btn-confirmar-sim {
    background: #00ff00;
    color: #000;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: bold;
    margin: 0 10px;
    transition: all 0.3s;
    min-width: 120px;
}

.modal-confirmacao .btn-confirmar-sim:hover {
    background: #00cc00;
    transform: translateY(-2px);
}

.modal-confirmacao .btn-confirmar-nao {
    background: #dc3545;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: bold;
    margin: 0 10px;
    transition: all 0.3s;
    min-width: 120px;
}

.modal-confirmacao .btn-confirmar-nao:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.modal-confirmacao .icon-warning {
    font-size: 4em;
    color: #00ff00;
    margin-bottom: 20px;
    text-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
}

.modal-confirmacao p.text-success {
    color: #00ff00 !important;
    font-size: 0.9em;
}

.modal-confirmacao p.text-success i {
    color: #00ff00;
}

/* Estilos dos modais de feedback */
.modal-feedback .modal-dialog {
    max-width: 450px;
}

.modal-feedback .modal-content {
    background-color: #000000;
    border: 2px solid #00ff00;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
}

.modal-feedback .modal-header {
    border-bottom: 1px solid #00ff00;
    background-color: #000000;
    padding: 15px 20px;
}

.modal-feedback .modal-title {
    color: #00ff00;
    font-weight: bold;
    text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-feedback .modal-body {
    text-align: center;
    padding: 30px;
    color: #ffffff;
    background: #000000;
}

.modal-feedback .modal-body p {
    font-size: 1.2em;
    margin-bottom: 25px;
    color: #ffffff;
    font-weight: bold;
}

.modal-feedback .icon-success {
    font-size: 4em;
    color: #00ff00;
    margin-bottom: 20px;
    text-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
}

.icon-info {
    font-size: 4em;
    color: #00ff00;
    margin-bottom: 20px;
    text-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
}

.modal-feedback .btn-ok {
    background: #00ff00;
    color: #000;
    border: none;
    padding: 14px 30px;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1.1rem;
    margin: 0 10px;
    transition: all 0.3s;
    min-width: 140px;
    box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
}

.modal-feedback .btn-ok:hover {
    background: #00cc00;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 255, 0, 0.4);
}

/* Estilos dos botões do modal de exclusão bem-sucedida */
.btn-criar-nova {
    background: #00ff00;
    color: #000;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
    min-width: 160px;
}

.btn-criar-nova:hover {
    background: #00cc00;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 255, 0, 0.4);
    color: #000;
}

.btn-ver-criadas {
    background: #007bff;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    min-width: 160px;
}

.btn-ver-criadas:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    color: white;
}

.btn-verde-modal {
    background: #00ff00;
    color: #000;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
    min-width: 180px;
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
    min-width: 180px;
}

.btn-azul-modal:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    color: white;
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
        
        .btn-acoes {
            flex-direction: column;
        }

        .btn-editar, .btn-excluir {
            width: 100%;
        }

        .modal-edicao .modal-footer {
            flex-direction: column;
            gap: 10px;
        }
        
        .modal-edicao .btn-voltar, .modal-edicao .btn-salvar {
            width: 100%;
            min-width: auto;
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
                            <i class="bi bi-list-task"></i> Minhas Partidas Criadas
                        </h2>
                        <a href="criar_partida.php" class="btn btn-success neon-btn">
                            <i class="bi bi-plus-circle"></i> Nova Partida
                        </a>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Partidas Ativas -->
                    <h3 class="section-title mt-4">Partidas Ativas</h3>
                    
                    <?php if (empty($partidas_ativas)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-plus"></i>
                            <h5 class="text-muted">Nenhuma partida ativa criada</h5>
                            <p class="text-muted">Você não tem partidas futuras criadas.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($partidas_ativas as $partida): ?>
                            <?php 
                            $posicoes_marcadas = $partida['posicoes_marcadas'] ? array_filter(explode(',', $partida['posicoes_marcadas'])) : [];
                            $posicoes_restantes = array_filter(explode(',', $partida['posicoes_restantes']));
                            $pode_excluir = $partida['total_marcados'] == 0;
                            ?>
                            
                            <div class="partida-card">
                                <div class="partida-content">
                                    <div class="partida-info">
                                        <div class="info-item">
                                            <i class="bi bi-check-circle"></i>
                                            <span class="partida-ativa-badge">PARTIDA ATIVA</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-people-fill"></i>
                                            <span class="badge-azul"><?= $partida['total_marcados'] ?> jogador(es) confirmado(s)</span>
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
                                            <i class="bi bi-chat-dots-fill"></i>
                                            <a href="chat.php?partida=<?= $partida['id_partida'] ?>" class="btn-chat">
                                                CHAT
                                            </a>
                                        </div> 
                                        <div class="info-item">
                                            <i class="bi bi-person-check"></i>
                                            <?php if ($partida['solicitacoes_pendentes'] > 0): ?>
                                                <a href="solicitacoes_participacao.php" class="btn-solicitacoes">
                                                    SOLICITAÇÕES
                                                </a>
                                                <span class="notification-badge"><?= $partida['solicitacoes_pendentes'] ?></span>
                                            <?php else: ?>
                                                <a href="solicitacoes_participacao.php" class="btn-solicitacoes">
                                                    SOLICITAÇÕES
                                                </a>
                                            <?php endif; ?>
                                        </div>                      
                                        <div class="btn-acoes">
                                            <button type="button" class="btn-editar" onclick="abrirModalEdicao(<?= $partida['id_partida'] ?>)">
                                                ✏️ Editar
                                            </button>
                                            
                                            <?php if ($pode_excluir): ?>
                                                <button type="button" class="btn-excluir" data-bs-toggle="modal" data-bs-target="#confirmarExclusaoModal<?= $partida['id_partida'] ?>">
                                                    🗑️ Excluir
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-excluir" disabled>
                                                    🗑️ Excluir
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!$pode_excluir): ?>
                                            <div class="aviso-exclusao">
                                                <i class="bi bi-info-circle"></i> Não é possível excluir partidas com jogadores confirmados
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="partida-campo">
                                        <div id="campo-ativa-<?= $partida['id_partida'] ?>" class="campo-futebol-container">
                                            <div class="campo-futebol"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal de Confirmação de Exclusão -->
                            <?php if ($pode_excluir): ?>
                            <div class="modal fade modal-confirmacao" id="confirmarExclusaoModal<?= $partida['id_partida'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-exclamation-triangle"></i> Confirmar Exclusão
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="icon-warning">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                            </div>
                                            <p><strong>Tem certeza que deseja excluir esta partida?</strong></p>
                                            <div class="partida-info-exclusao">
                                                <div><i class="bi bi-calendar"></i> <strong><?= date('d/m/Y', strtotime($partida['data'])) ?></strong></div>
                                                <div><i class="bi bi-clock"></i> <?= htmlspecialchars($partida['turno']) ?> - <?= date('H:i', strtotime($partida['horario'])) ?></div>
                                                <div><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($partida['cidade']) ?> - <?= htmlspecialchars($partida['estado']) ?></div>
                                            </div>
                                            <div class="alert alert-warning">
                                                <i class="bi bi-info-circle"></i>
                                                <strong>Esta ação não pode ser desfeita!</strong>
                                            </div>
                                            
                                            <div class="d-flex justify-content-center gap-3 mt-4">
                                                <button type="button" class="btn-confirmar-nao" data-bs-dismiss="modal">
                                                    <i class="bi bi-x-circle"></i> Voltar
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="partida_id" value="<?= $partida['id_partida'] ?>">
                                                    <button type="submit" name="excluir_partida" class="btn-confirmar-sim">
                                                        <i class="bi bi-check-circle"></i> Sim, Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Modal de Edição -->
                            <div class="modal fade modal-edicao" id="editarModal<?= $partida['id_partida'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-pencil"></i> Editar Partida
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" id="editarForm<?= $partida['id_partida'] ?>">
                                            <div class="modal-body">
                                                <input type="hidden" name="partida_id" value="<?= $partida['id_partida'] ?>">
                                                <input type="hidden" name="editar_partida" value="1">
                                                
                                                <!-- Dados originais para comparação -->
                                                <input type="hidden" id="original_estado_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['estado']) ?>">
                                                <input type="hidden" id="original_cidade_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['cidade']) ?>">
                                                <input type="hidden" id="original_bairro_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['bairro'] ?? '') ?>">
                                                <input type="hidden" id="original_endereco_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['endereco']) ?>">
                                                <input type="hidden" id="original_nome_local_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['nome_local'] ?? '') ?>">
                                                <input type="hidden" id="original_cep_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['cep'] ?? '') ?>">
                                                <input type="hidden" id="original_contato_local_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['contato_local'] ?? '') ?>">
                                                <input type="hidden" id="original_data_<?= $partida['id_partida'] ?>" value="<?= $partida['data'] ?>">
                                                <input type="hidden" id="original_horario_<?= $partida['id_partida'] ?>" value="<?= $partida['horario'] ?>">
                                                <input type="hidden" id="original_turno_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['turno']) ?>">
                                                <input type="hidden" id="original_nivel_jogo_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['nivel_jogo'] ?? '') ?>">
                                                <input type="hidden" id="original_valor_por_pessoa_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['valor_por_pessoa'] ?? '0') ?>">
                                                <input type="hidden" id="original_duracao_minutos_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['duracao_minutos'] ?? '90') ?>">
                                                <input type="hidden" id="original_idade_minima_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['idade_minima'] ?? '14') ?>">
                                                <input type="hidden" id="original_idade_maxima_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['idade_maxima'] ?? '60') ?>">
                                                <input type="hidden" id="original_intervalo_<?= $partida['id_partida'] ?>" value="<?= ($partida['intervalo'] ?? false) ? 'true' : 'false' ?>">
                                                <input type="hidden" id="original_arbitro_incluso_<?= $partida['id_partida'] ?>" value="<?= ($partida['arbitro_incluso'] ?? false) ? 'true' : 'false' ?>">
                                                <input type="hidden" id="original_nivel_minimo_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['nivel_minimo'] ?? '') ?>">
                                                <input type="hidden" id="original_descricao_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['descricao']) ?>">
                                                <input type="hidden" id="original_posicoes_<?= $partida['id_partida'] ?>" value="<?= htmlspecialchars($partida['posicoes_restantes']) ?>">
                                                <input type="hidden" id="original_tem_vestiario_<?= $partida['id_partida'] ?>" value="<?= ($partida['tem_vestiario'] ?? false) ? 'true' : 'false' ?>">
                                                <input type="hidden" id="original_tem_chuveiro_<?= $partida['id_partida'] ?>" value="<?= ($partida['tem_chuveiro'] ?? false) ? 'true' : 'false' ?>">
                                                <input type="hidden" id="original_tem_estacionamento_<?= $partida['id_partida'] ?>" value="<?= ($partida['tem_estacionamento'] ?? false) ? 'true' : 'false' ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="estado<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-map"></i> Estado
                                                        </label>
                                                        <select class="form-control neon-input" id="estado<?= $partida['id_partida'] ?>" name="estado" required>
                                                            <option value="">Selecione o estado</option>
                                                            <option value="AC" <?= $partida['estado'] === 'AC' ? 'selected' : '' ?>>Acre</option>
                                                            <option value="AL" <?= $partida['estado'] === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                                                            <option value="AP" <?= $partida['estado'] === 'AP' ? 'selected' : '' ?>>Amapá</option>
                                                            <option value="AM" <?= $partida['estado'] === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                                                            <option value="BA" <?= $partida['estado'] === 'BA' ? 'selected' : '' ?>>Bahia</option>
                                                            <option value="CE" <?= $partida['estado'] === 'CE' ? 'selected' : '' ?>>Ceará</option>
                                                            <option value="DF" <?= $partida['estado'] === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                                                            <option value="ES" <?= $partida['estado'] === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                                                            <option value="GO" <?= $partida['estado'] === 'GO' ? 'selected' : '' ?>>Goiás</option>
                                                            <option value="MA" <?= $partida['estado'] === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                                                            <option value="MT" <?= $partida['estado'] === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                                                            <option value="MS" <?= $partida['estado'] === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                                                            <option value="MG" <?= $partida['estado'] === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                                                            <option value="PA" <?= $partida['estado'] === 'PA' ? 'selected' : '' ?>>Pará</option>
                                                            <option value="PB" <?= $partida['estado'] === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                                                            <option value="PR" <?= $partida['estado'] === 'PR' ? 'selected' : '' ?>>Paraná</option>
                                                            <option value="PE" <?= $partida['estado'] === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                                                            <option value="PI" <?= $partida['estado'] === 'PI' ? 'selected' : '' ?>>Piauí</option>
                                                            <option value="RJ" <?= $partida['estado'] === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                                                            <option value="RN" <?= $partida['estado'] === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                                                            <option value="RS" <?= $partida['estado'] === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                                                            <option value="RO" <?= $partida['estado'] === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                                                            <option value="RR" <?= $partida['estado'] === 'RR' ? 'selected' : '' ?>>Roraima</option>
                                                            <option value="SC" <?= $partida['estado'] === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                                                            <option value="SP" <?= $partida['estado'] === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                                                            <option value="SE" <?= $partida['estado'] === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                                                            <option value="TO" <?= $partida['estado'] === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="cidade<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-building"></i> Cidade
                                                        </label>
                                                        <input type="text" class="form-control neon-input" 
                                                               id="cidade<?= $partida['id_partida'] ?>" name="cidade" 
                                                               value="<?= htmlspecialchars($partida['cidade']) ?>" required>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="bairro<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-signpost"></i> Bairro
                                                        </label>
                                                        <input type="text" class="form-control neon-input" 
                                                               id="bairro<?= $partida['id_partida'] ?>" name="bairro" 
                                                               value="<?= htmlspecialchars($partida['bairro'] ?? '') ?>" placeholder="Nome do bairro" required>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="endereco<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-geo-alt"></i> Endereço
                                                        </label>
                                                        <input type="text" class="form-control neon-input" 
                                                               id="endereco<?= $partida['id_partida'] ?>" name="endereco" 
                                                               value="<?= htmlspecialchars($partida['endereco']) ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="nome_local<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-shop"></i> Nome do Campo/Quadra
                                                        </label>
                                                        <input type="text" class="form-control neon-input" 
                                                               id="nome_local<?= $partida['id_partida'] ?>" name="nome_local" 
                                                               value="<?= htmlspecialchars($partida['nome_local'] ?? '') ?>" placeholder="Ex: Arena Sports" required>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="cep<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-mailbox"></i> CEP
                                                        </label>
                                                        <input type="text" class="form-control neon-input cep-mask" 
                                                               id="cep<?= $partida['id_partida'] ?>" name="cep" 
                                                               value="<?= htmlspecialchars($partida['cep'] ?? '') ?>" placeholder="00000-000" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="contato_local<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-telephone"></i> Telefone do Local
                                                        </label>
                                                        <input type="tel" class="form-control neon-input telefone-mask" 
                                                               id="contato_local<?= $partida['id_partida'] ?>" name="contato_local" 
                                                               value="<?= htmlspecialchars($partida['contato_local'] ?? '') ?>" placeholder="(XX) 99999-9999" required>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        <i class="bi bi-check2-square"></i> Facilidades do Local (Opcional)
                                                    </label>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       id="tem_vestiario<?= $partida['id_partida'] ?>" name="tem_vestiario" 
                                                                       <?= ($partida['tem_vestiario'] ?? false) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-white" for="tem_vestiario<?= $partida['id_partida'] ?>">
                                                                    Vestiário
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       id="tem_chuveiro<?= $partida['id_partida'] ?>" name="tem_chuveiro" 
                                                                       <?= ($partida['tem_chuveiro'] ?? false) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-white" for="tem_chuveiro<?= $partida['id_partida'] ?>">
                                                                    Chuveiro
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       id="tem_estacionamento<?= $partida['id_partida'] ?>" name="tem_estacionamento" 
                                                                       <?= ($partida['tem_estacionamento'] ?? false) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-white" for="tem_estacionamento<?= $partida['id_partida'] ?>">
                                                                    Estacionamento
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="data_partida<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-calendar"></i> Data
                                                        </label>
                                                        <input type="date" class="form-control neon-input" 
                                                               id="data_partida<?= $partida['id_partida'] ?>" name="data_partida" 
                                                               value="<?= $partida['data'] ?>" required>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="horario<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-clock"></i> Horário
                                                        </label>
                                                        <input type="time" class="form-control neon-input" 
                                                               id="horario<?= $partida['id_partida'] ?>" name="horario" 
                                                               value="<?= $partida['horario'] ?>" required>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="turno<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-sun"></i> Turno
                                                        </label>
                                                        <select class="form-control neon-input" 
                                                                id="turno<?= $partida['id_partida'] ?>" name="turno" required>
                                                            <option value="MANHÃ" <?= $partida['turno'] === 'MANHÃ' ? 'selected' : '' ?>>MANHÃ</option>
                                                            <option value="TARDE" <?= $partida['turno'] === 'TARDE' ? 'selected' : '' ?>>TARDE</option>
                                                            <option value="NOITE" <?= $partida['turno'] === 'NOITE' ? 'selected' : '' ?>>NOITE</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="nivel_jogo<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-trophy"></i> Nível do Jogo
                                                        </label>
                                                        <select class="form-control neon-input" id="nivel_jogo<?= $partida['id_partida'] ?>" name="nivel_jogo" required>
                                                            <option value="">Selecione o nível</option>
                                                            <?php 
                                                            $niveis_jogo = ['Família/Amigos', 'Resenha', 'Iniciante', 'Intermediário', 'Avançado', 'Semi-Amador', 'Amador'];
                                                            foreach ($niveis_jogo as $nivel): ?>
                                                                <option value="<?= $nivel ?>" <?= ($partida['nivel_jogo'] ?? '') === $nivel ? 'selected' : '' ?>>
                                                                    <?= $nivel ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="valor_por_pessoa<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-currency-dollar"></i> Valor por Pessoa
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-dark text-success border-success">R$</span>
                                                            <input type="number" class="form-control neon-input" id="valor_por_pessoa<?= $partida['id_partida'] ?>" name="valor_por_pessoa" 
                                                                   value="<?= htmlspecialchars($partida['valor_por_pessoa'] ?? '0') ?>" min="0" max="999.99" step="0.01" placeholder="0,00" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="duracao_minutos<?= $partida['id_partida'] ?>" class="form-label">
                                                            <i class="bi bi-stopwatch"></i> Duração (minutos)
                                                        </label>
                                                        <input type="number" class="form-control neon-input text-center" id="duracao_minutos<?= $partida['id_partida'] ?>" name="duracao_minutos" 
                                                               value="<?= htmlspecialchars($partida['duracao_minutos'] ?? '90') ?>" min="10" max="300" required>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">
                                                            <i class="bi bi-people"></i> Restrições de Idade
                                                        </label>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <label for="idade_minima<?= $partida['id_partida'] ?>" class="form-label small">Idade Mínima</label>
                                                                <input type="number" class="form-control neon-input text-center" id="idade_minima<?= $partida['id_partida'] ?>" name="idade_minima" 
                                                                       value="<?= htmlspecialchars($partida['idade_minima'] ?? '14') ?>" min="14" max="99" required>
                                                            </div>
                                                            <div class="col-6">
                                                                <label for="idade_maxima<?= $partida['id_partida'] ?>" class="form-label small">Idade Máxima</label>
                                                                <input type="number" class="form-control neon-input text-center" id="idade_maxima<?= $partida['id_partida'] ?>" name="idade_maxima" 
                                                                       value="<?= htmlspecialchars($partida['idade_maxima'] ?? '60') ?>" min="15" max="100" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">
                                                            <i class="bi bi-check2-square"></i> Opções da Partida (Opcional)
                                                        </label>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="intervalo<?= $partida['id_partida'] ?>" name="intervalo" 
                                                                           <?= ($partida['intervalo'] ?? false) ? 'checked' : '' ?>>
                                                                    <label class="form-check-label text-white" for="intervalo<?= $partida['id_partida'] ?>">
                                                                        Tem Intervalo
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="arbitro_incluso<?= $partida['id_partida'] ?>" name="arbitro_incluso" 
                                                                           <?= ($partida['arbitro_incluso'] ?? false) ? 'checked' : '' ?>>
                                                                    <label class="form-check-label text-white" for="arbitro_incluso<?= $partida['id_partida'] ?>">
                                                                        Árbitro Incluso
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        <i class="bi bi-star"></i> Níveis Mínimos Aceitos
                                                    </label>
                                                    <p class="text-muted small">Selecione os níveis de jogadores que podem participar:</p>
                                                    
                                                    <div class="row">
                                                        <?php 
                                                        $niveis_minimos = ['Resenha', 'Iniciante', 'Intermediário', 'Avançado', 'Semi-Amador', 'Amador'];
                                                        $niveis_selecionados = explode(',', $partida['nivel_minimo'] ?? '');
                                                        ?>
                                                        <?php foreach ($niveis_minimos as $nivel): ?>
                                                            <div class="col-md-4 col-6 mb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input nivel-min-check-<?= $partida['id_partida'] ?>" type="checkbox" 
                                                                           id="nivel_min_<?= strtolower(str_replace([' ', '-', '/'], '_', $nivel)) ?>_<?= $partida['id_partida'] ?>" 
                                                                           name="nivel_min_checks[]" value="<?= $nivel ?>"
                                                                           <?= in_array($nivel, $niveis_selecionados) ? 'checked' : '' ?>>
                                                                    <label class="form-check-label text-white" 
                                                                           for="nivel_min_<?= strtolower(str_replace([' ', '-', '/'], '_', $nivel)) ?>_<?= $partida['id_partida'] ?>">
                                                                        <?= $nivel ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <input type="hidden" id="nivel_minimo<?= $partida['id_partida'] ?>" name="nivel_minimo" value="<?= htmlspecialchars($partida['nivel_minimo'] ?? '') ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="descricao<?= $partida['id_partida'] ?>" class="form-label">
                                                        <i class="bi bi-card-text"></i> Descrição
                                                    </label>
                                                    <textarea class="form-control neon-input" 
                                                               id="descricao<?= $partida['id_partida'] ?>" name="descricao" rows="3"><?= htmlspecialchars($partida['descricao']) ?></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        <i class="bi bi-people"></i> Posições Disponíveis
                                                    </label>
                                                    <p class="text-muted small">Clique nas posições que estarão disponíveis:</p>
                                                    <div id="campo-editar-<?= $partida['id_partida'] ?>"></div>
                                                    <input type="hidden" id="posicoes_disponiveis<?= $partida['id_partida'] ?>" 
                                                           name="posicoes_disponiveis" value="<?= htmlspecialchars($partida['posicoes_restantes']) ?>" required>
                                                </div>
                                                
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn-voltar" onclick="confirmarVoltar(<?= $partida['id_partida'] ?>)">
                                                    ❌ Voltar
                                                </button>
                                                <button type="button" class="btn-salvar" onclick="confirmarSalvar(<?= $partida['id_partida'] ?>)">
                                                    ✅ Salvar Alterações
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Campo principal
                                    initCampoFutebol('campo-ativa-<?= $partida['id_partida'] ?>', {
                                        readOnly: true,
                                        posicoesSelecionadas: [],
                                        posicoesDisponiveis: <?= json_encode($posicoes_restantes) ?>,
                                        posicoesOcupadas: <?= json_encode($posicoes_marcadas) ?>
                                    });
                                    
                                    // Campo do modal de edição
                                    setTimeout(() => {
                                        if (typeof initCampoFutebol === 'function') {
                                            initCampoFutebol('campo-editar-<?= $partida['id_partida'] ?>', {
                                                multiSelect: true,
                                                showLegenda: false,
                                                posicoesSelecionadas: <?= json_encode($posicoes_restantes) ?>,
                                                posicoesDisponiveis: ['GOL', 'ZAG', 'ALA ESQ', 'ALA DIR', 'VOL', 'MEI', 'ATA'],
                                                posicoesOcupadas: [],
                                                readOnly: false,
                                                onChange: function(selecionadas) {
                                                    const input = document.getElementById('posicoes_disponiveis<?= $partida['id_partida'] ?>');
                                                    if (input) {
                                                        input.value = selecionadas.join(',');
                                                    }
                                                }
                                            });
                                        }
                                    }, 500);
                                    
                                    // Definir data mínima
                                    const dataInput = document.getElementById('data_partida<?= $partida['id_partida'] ?>');
                                    if (dataInput) {
                                        const hoje = new Date().toISOString().split('T')[0];
                                        dataInput.min = hoje;
                                    }
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
                            <p class="text-muted">Seu histórico de partidas criadas aparecerá aqui após a data dos jogos.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($partidas_realizadas as $partida): ?>
                            <?php 
                            $posicoes_marcadas = $partida['posicoes_marcadas'] ? array_filter(explode(',', $partida['posicoes_marcadas'])) : [];
                            $posicoes_restantes = array_filter(explode(',', $partida['posicoes_restantes']));
                            ?>
                            
                            <div class="partida-card">
                                <div class="partida-content">
                                    <div class="partida-info">
                                        <div class="info-item">
                                            <i class="bi bi-hourglass-bottom"></i>
                                            <span class="partida-realizada-badge">PARTIDA REALIZADA</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-people-fill"></i>
                                            <span class="badge-azul"><?= $partida['total_marcados'] ?> jogador(es) participaram</span>
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
                                            <i class="bi bi-chat-dots-fill"></i>
                                            <a href="chat.php?partida=<?= $partida['id_partida'] ?>" class="btn-chat">
                                                CHAT
                                            </a>
                                        </div>
                                        <div class="info-item">
                                            <i class="bi bi-star"></i>
                                            <?php if ($partida['total_jogadores_participaram'] > 0): ?>
                                                <a href="avaliar_jogadores.php?partida=<?= $partida['id_partida'] ?>" class="btn-avaliar-jogadores">
                                                    AVALIAR JOGADORES
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($partida['total_jogadores_participaram'] > 0): ?>
                                            <div class="info-item">
                                                <i class="bi bi-star-fill"></i>
                                                <span>
                                                    <strong>Avaliações:</strong> <?= $partida['avaliacoes_feitas'] ?>/<?= $partida['total_jogadores_participaram'] ?> jogadores avaliados
                                                    <?php if ($partida['avaliacoes_feitas'] < $partida['total_jogadores_participaram']): ?>
                                                        <br><small class="text-warning">Você ainda pode avaliar <?= ($partida['total_jogadores_participaram'] - $partida['avaliacoes_feitas']) ?> jogador(es)</small>
                                                    <?php else: ?>
                                                        <br><small class="text-success">Todos os jogadores foram avaliados!</small>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="info-item">
                                            <i class="bi bi-trophy"></i>
                                            <span class="text-success">Partida concluída com sucesso!</span>
                                        </div>
                                    </div>
                                    <div class="partida-campo">
                                        <div id="campo-realizada-<?= $partida['id_partida'] ?>" class="campo-futebol-container">
                                            <div class="campo-futebol"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // PARTIDAS REALIZADAS: Vermelho = posições disponíveis, Cinza = todas as outras
                                    initCampoFutebol('campo-realizada-<?= $partida['id_partida'] ?>', {
                                        readOnly: true,
                                        posicoesSelecionadas: [],
                                        posicoesDisponiveis: [], // Nenhuma posição disponível (todas cinzas)
                                        posicoesOcupadas: <?= json_encode($posicoes_restantes) ?> // Posições que estavam disponíveis ficam vermelhas
                                    });
                                });
                            </script>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação para Voltar -->
    <div class="modal fade modal-confirmacao" id="modalConfirmarVoltar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Confirmar Saída
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="icon-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <p id="mensagemVoltar"><strong>Tem certeza que deseja voltar sem salvar as alterações?</strong></p>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i>
                        <strong>Todas as alterações feitas serão perdidas!</strong>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <button type="button" class="btn-confirmar-nao" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Continuar Editando
                        </button>
                        <button type="button" class="btn-confirmar-sim" id="btnConfirmarVoltar">
                            <i class="bi bi-check-circle"></i> Sim, Voltar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação para Salvar -->
    <div class="modal fade modal-confirmacao" id="modalConfirmarSalvar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle"></i> Confirmar Alterações
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div style="font-size: 4em; color: #00ff00; margin-bottom: 20px; text-shadow: 0 0 20px rgba(0, 255, 0, 0.5); text-align: center;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <p><strong>Tem certeza que deseja salvar as alterações da partida?</strong></p>
                    <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; border-radius: 8px; padding: 15px; margin: 20px 0; color: #00ff00;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-info-circle" style="font-size: 1.2em;"></i>
                            <span>As alterações serão aplicadas imediatamente.</span>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <button type="button" class="btn-confirmar-nao" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="button" class="btn-confirmar-sim" id="btnConfirmarSalvar">
                            <i class="bi bi-check-circle"></i> Sim, Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso - Partida Alterada -->
    <div class="modal fade modal-feedback" id="modalSucessoAlterada" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle"></i> Sucesso!
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="icon-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <p><strong>🎉 Sua partida foi alterada com sucesso!</strong></p>
                    <button type="button" class="btn-ok" onclick="window.location.href='partidas_criadas.php'">
                        <i class="bi bi-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Info - Nenhuma Alteração -->
    <div class="modal fade modal-feedback" id="modalInfoNenhuma" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle"></i> Informação
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="icon-info">
                        <i class="bi bi-info-circle-fill"></i>
                    </div>
                    <p><strong>Nenhum dado foi alterado.</strong></p>
                    <button type="button" class="btn-verde-modal" onclick="window.location.href='partidas_criadas.php'">
                        <i class="bi bi-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso - Partida Excluída -->
    <div class="modal fade modal-feedback" id="modalSucessoExcluida" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle"></i> Sucesso!
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="icon-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <p><strong>🗑️ Partida excluída com sucesso!</strong></p>
                    <p>O que deseja fazer agora?</p>
                    
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <button type="button" class="btn-criar-nova" onclick="window.location.href='criar_partida.php'">
                            <i class="bi bi-plus-circle"></i> Criar Nova Partida
                        </button>
                        <button type="button" class="btn-ver-criadas" onclick="window.location.href='partidas_criadas.php'">
                            <i class="bi bi-list-task"></i> Ver Partidas Criadas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Erro (Preenchimento) -->
    <div class="modal fade modal-feedback" id="modalErroPreenchimento" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Erro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="icon-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <p><strong>Todos os campos obrigatórios devem ser preenchidos.</strong></p>
                    <button type="button" class="btn-ok" data-bs-dismiss="modal">
                        <i class="bi bi-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Erro (Posição) -->
    <div class="modal fade modal-feedback" id="modalErroPosicao" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Erro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="icon-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <p><strong>Selecione pelo menos uma posição disponível.</strong></p>
                    <button type="button" class="btn-ok" data-bs-dismiss="modal">
                        <i class="bi bi-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Erro (Salvar) -->
    <div class="modal fade modal-feedback" id="modalErroSalvar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Erro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="icon-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <p><strong>Erro ao salvar alterações.</strong></p>
                    <button type="button" class="btn-ok" data-bs-dismiss="modal">
                        <i class="bi bi-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Erro (Não Encontrada) -->
    <div class="modal fade modal-feedback" id="modalErroNaoEncontrada" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Erro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="icon-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <p><strong>Partida não encontrada.</strong></p>
                    <button type="button" class="btn-ok" data-bs-dismiss="modal">
                        <i class="bi bi-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Erro (Sistema) -->
    <div class="modal fade modal-feedback" id="modalErroSistema" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Erro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="icon-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <p><strong>Erro no sistema.</strong></p>
                    <button type="button" class="btn-ok" data-bs-dismiss="modal">
                        <i class="bi bi-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script src="JS/campo-futebol.js"></script>
    <script>
        let partidaAtualId = null;

        function abrirModalEdicao(partidaId) {
            partidaAtualId = partidaId;
            new bootstrap.Modal(document.getElementById('editarModal' + partidaId)).show();
        }

        function confirmarVoltar(partidaId) {
            document.getElementById('mensagemVoltar').innerHTML = '<strong>Tem certeza que deseja voltar sem salvar as alterações?</strong>';
            document.getElementById('btnConfirmarVoltar').onclick = function() {
                bootstrap.Modal.getInstance(document.getElementById('modalConfirmarVoltar')).hide();
                bootstrap.Modal.getInstance(document.getElementById('editarModal' + partidaId)).hide();
            };
            new bootstrap.Modal(document.getElementById('modalConfirmarVoltar')).show();
        }

        function confirmarSalvar(partidaId) {
            document.getElementById('btnConfirmarSalvar').onclick = function() {
                bootstrap.Modal.getInstance(document.getElementById('modalConfirmarSalvar')).hide();
                document.getElementById('editarForm' + partidaId).submit();
            };
            new bootstrap.Modal(document.getElementById('modalConfirmarSalvar')).show();
        }

        document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Verificar parâmetros da URL para mostrar o modal apropriado
    if (urlParams.get('info') === 'nenhuma') {
        // Priorizar mostrar o modal de "nenhuma alteração" se esse parâmetro existir
        new bootstrap.Modal(document.getElementById('modalInfoNenhuma')).show();
    } else if (urlParams.get('success') === 'alterada') {
        new bootstrap.Modal(document.getElementById('modalSucessoAlterada')).show();
    } else if (urlParams.get('success') === 'excluida') {
        new bootstrap.Modal(document.getElementById('modalSucessoExcluida')).show();
    } else if (urlParams.get('error') === 'preenchimento') {
        new bootstrap.Modal(document.getElementById('modalErroPreenchimento')).show();
    } else if (urlParams.get('error') === 'posicao') {
        new bootstrap.Modal(document.getElementById('modalErroPosicao')).show();
    } else if (urlParams.get('error') === 'salvar') {
        new bootstrap.Modal(document.getElementById('modalErroSalvar')).show();
    } else if (urlParams.get('error') === 'notfound') {
        new bootstrap.Modal(document.getElementById('modalErroNaoEncontrada')).show();
    } else if (urlParams.get('error') === 'sistema') {
        new bootstrap.Modal(document.getElementById('modalErroSistema')).show();
    }
            
            // Inicializar máscaras para CEP e telefone nos modais de edição
            document.querySelectorAll('.cep-mask').forEach(function(input) {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 5) {
                        value = value.substring(0, 5) + '-' + value.substring(5, 8);
                    }
                    e.target.value = value;
                });
            });
            
            document.querySelectorAll('.telefone-mask').forEach(function(input) {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = '(' + value;
                        if (value.length > 3) {
                            value = value.substring(0, 3) + ') ' + value.substring(3);
                        }
                        if (value.length > 10) {
                            value = value.substring(0, 10) + '-' + value.substring(10, 14);
                        }
                    }
                    e.target.value = value;
                });
            });
            
            // Inicializar checkboxes de níveis mínimos
            document.querySelectorAll('[id^="editarModal"]').forEach(function(modal) {
                const partidaId = modal.id.replace('editarModal', '');
                const checkboxes = document.querySelectorAll('.nivel-min-check-' + partidaId);
                const nivelInput = document.getElementById('nivel_minimo' + partidaId);
                
                if (checkboxes.length > 0 && nivelInput) {
                    checkboxes.forEach(function(checkbox) {
                        checkbox.addEventListener('change', function() {
                            const selecionados = Array.from(checkboxes)
                                .filter(cb => cb.checked)
                                .map(cb => cb.value);
                            nivelInput.value = selecionados.join(',');
                        });
                    });
                }
            });
        });
    </script>
</body>
</html>
