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
    header("Location: tela_inicial.php");
    exit;
}

$id_partida = (int)$_GET['partida'];

try {
    $db = new Database();
    
    // Verificar se o usuário tem permissão para acessar este chat
    // (deve ser o criador da partida ou ter marcado presença)
    $stmt = $db->getConnection()->prepare("
        SELECT p.*, u.nome as criador_nome, u.id_usuario as criador_id
        FROM partidas p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE p.id_partida = ?
    ");
    $stmt->execute([$id_partida]);
    $partida = $stmt->fetch();
    
    if (!$partida) {
        header("Location: tela_inicial.php");
        exit;
    }
    
    // Verificar se o usuário é o criador ou marcou presença
    $is_criador = ($partida['id_usuario'] == $user['id_usuario']);
    
    if (!$is_criador) {
        // Verificar se marcou presença
        $stmt = $db->getConnection()->prepare("
            SELECT * FROM marcacoes 
            WHERE id_partida = ? AND id_usuario = ?
        ");
        $stmt->execute([$id_partida, $user['id_usuario']]);
        $marcacao = $stmt->fetch();
        
        if (!$marcacao) {
            header("Location: tela_inicial.php");
            exit;
        }
    }
    
    // Processar envio de mensagem
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
        $mensagem = trim($_POST['mensagem']);
        
        if (!empty($mensagem)) {
            $stmt = $db->getConnection()->prepare("
                INSERT INTO mensagens_chat (id_partida, id_remetente, mensagem)
                VALUES (?, ?, ?)
            ");
            
            if ($stmt->execute([$id_partida, $user['id_usuario'], $mensagem])) {
                $success = 'Mensagem enviada com sucesso!';
            } else {
                $error = 'Erro ao enviar mensagem.';
            }
        } else {
            $error = 'A mensagem não pode estar vazia.';
        }
    }
    
    // Buscar todas as mensagens do chat
    $stmt = $db->getConnection()->prepare("
        SELECT m.*, u.nome as remetente_nome
        FROM mensagens_chat m
        JOIN usuarios u ON m.id_remetente = u.id_usuario
        WHERE m.id_partida = ?
        ORDER BY m.data_envio ASC
    ");
    $stmt->execute([$id_partida]);
    $mensagens = $stmt->fetchAll();
    
    // Marcar mensagens como lidas (se não for o remetente)
    $stmt = $db->getConnection()->prepare("
        UPDATE mensagens_chat
        SET lida = 1
        WHERE id_partida = ? AND id_remetente != ?
    ");
    $stmt->execute([$id_partida, $user['id_usuario']]);
    
} catch (PDOException $e) {
    $error = 'Erro no sistema: ' . $e->getMessage();
    $partida = null;
    $mensagens = [];
}

// Função para formatar data/hora
function formatarDataHora($datetime) {
    $data = new DateTime($datetime);
    $hoje = new DateTime('today');
    $ontem = new DateTime('yesterday');
    
    if ($data->format('Y-m-d') === $hoje->format('Y-m-d')) {
        return 'Hoje, ' . $data->format('H:i');
    } elseif ($data->format('Y-m-d') === $ontem->format('Y-m-d')) {
        return 'Ontem, ' . $data->format('H:i');
    } else {
        return $data->format('d/m/Y H:i');
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat da Partida - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    <style>
        .chat-container {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #00ff00;
            border-radius: 15px;
            padding: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 80vh;
            max-height: 700px;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.2);
        }
        
        .chat-header {
            background: rgba(0, 255, 0, 0.1);
            border-bottom: 1px solid #00ff00;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header h3 {
            margin: 0;
            color: #00ff00;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-header .partida-info {
            display: flex;
            flex-direction: column;
        }
        
        .chat-header .partida-data {
            font-size: 0.9rem;
            color: #cccccc;
        }
        
        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .chat-message {
            max-width: 80%;
            padding: 12px 15px;
            border-radius: 15px;
            position: relative;
            word-break: break-word;
        }
        
        .message-sent {
            background: linear-gradient(135deg, #00ff00 0%, #00cc00 100%);
            color: #000;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        
        .message-received {
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
            color: #fff;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
        
        .message-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            margin-top: 5px;
        }
        
        .message-sent .message-info {
            color: rgba(0, 0, 0, 0.7);
        }
        
        .message-received .message-info {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .message-sender {
            font-weight: bold;
        }
        
        .message-time {
            font-style: italic;
        }
        
        .chat-footer {
            background: rgba(0, 0, 0, 0.8);
            border-top: 1px solid #00ff00;
            padding: 15px;
        }
        
        .chat-input-container {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            background: rgba(30, 30, 30, 0.9) !important;
            border: 2px solid rgba(0, 255, 0, 0.4) !important;
            color: #ffffff !important;
            border-radius: 25px !important;
            padding: 12px 20px !important;
            transition: all 0.3s ease;
        }
        
        .chat-input:focus {
            background: rgba(40, 40, 40, 0.95) !important;
            border-color: #00ff00 !important;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.3) !important;
        }
        
        .btn-enviar {
            background: linear-gradient(45deg, #00cc00, #00ff00);
            border: none;
            color: #000000;
            font-weight: bold;
            border-radius: 25px;
            padding: 12px 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 255, 0, 0.25);
        }
        
        .btn-enviar:hover {
            background: linear-gradient(45deg, #00ff00, #00cc00);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 255, 0, 0.35);
        }
        
        .btn-voltar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
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
        
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #cccccc;
            text-align: center;
            padding: 20px;
        }
        
        .empty-chat i {
            font-size: 4rem;
            color: rgba(0, 255, 0, 0.3);
            margin-bottom: 20px;
        }
        
        .empty-chat p {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .empty-chat span {
            font-size: 0.9rem;
            max-width: 400px;
        }
        
        .partida-info-card {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #00ff00;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .partida-info-card h4 {
            color: #00ff00;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        /* Responsividade */
        @media (max-width: 768px) {
            .chat-container {
                height: 70vh;
            }
            
            .chat-message {
                max-width: 90%;
            }
            
            .btn-enviar {
                padding: 12px 15px;
            }
            
            .btn-enviar span {
                display: none;
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
        <i class="bi bi-chat-dots"></i> Chat da Partida
    </h2>
    <a href="javascript:history.back()" class="btn-voltar">
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
                    
                    <?php if ($partida): ?>
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="partida-info-card">
                                    <h4><i class="bi bi-info-circle"></i> Informações da Partida</h4>
                                    
                                    <div class="info-item">
                                        <i class="bi bi-calendar"></i>
                                        <span class="badge-verde"><?= date('d/m/Y', strtotime($partida['data'])) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-clock-fill"></i>
                                        <span class="badge-verde"><?= htmlspecialchars($partida['turno']) ?> - <?= date('H:i', strtotime($partida['horario'])) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-flag"></i>
                                        <span><?= htmlspecialchars($partida['cidade']) ?> - <?= htmlspecialchars($partida['estado']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-geo-alt"></i>
                                        <span><?= htmlspecialchars($partida['endereco']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-person"></i>
                                        <span>Criada por: <strong><?= htmlspecialchars($partida['criador_nome']) ?></strong></span>
                                    </div>
                                    <?php if (!empty($partida['descricao'])): ?>
                                    <div class="info-item">
                                        <i class="bi bi-card-text"></i>
                                        <span>Descrição: <?= htmlspecialchars($partida['descricao']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="info-item mt-3">
                                        <i class="bi bi-chat-dots-fill"></i>
                                        <span>Você está conversando com <?= $is_criador ? 'os jogadores que marcaram presença' : 'o organizador da partida' ?>.</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="chat-container">
                                    <div class="chat-header">
                                        <div class="partida-info">
                                            <h3>
                                                <i class="bi bi-chat-dots-fill"></i>
                                                Chat da Partida
                                            </h3>
                                            <div class="partida-data">
                                                <?= date('d/m/Y', strtotime($partida['data'])) ?> - <?= htmlspecialchars($partida['turno']) ?> - <?= date('H:i', strtotime($partida['horario'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="chat-body" id="chat-body">
                                        <?php if (empty($mensagens)): ?>
                                            <div class="empty-chat">
                                                <i class="bi bi-chat-dots"></i>
                                                <p>Nenhuma mensagem ainda</p>
                                                <span>Seja o primeiro a enviar uma mensagem para iniciar a conversa sobre esta partida.</span>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($mensagens as $mensagem): ?>
                                                <?php $is_sent = ($mensagem['id_remetente'] == $user['id_usuario']); ?>
                                                <div class="chat-message <?= $is_sent ? 'message-sent' : 'message-received' ?>">
                                                    <?= htmlspecialchars($mensagem['mensagem']) ?>
                                                    <div class="message-info">
                                                        <span class="message-sender"><?= htmlspecialchars($mensagem['remetente_nome']) ?></span>
                                                        <span class="message-time"><?= formatarDataHora($mensagem['data_envio']) ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="chat-footer">
                                        <form method="POST" id="chat-form">
                                            <div class="chat-input-container">
                                                <input type="text" class="form-control chat-input" id="mensagem" name="mensagem" placeholder="Digite sua mensagem..." autocomplete="off">
                                                <button type="submit" class="btn-enviar">
                                                    <i class="bi bi-send-fill"></i>
                                                    <span>Enviar</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Partida não encontrada ou você não tem permissão para acessar este chat.
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
            // Rolar para o final do chat
            const chatBody = document.getElementById('chat-body');
            if (chatBody) {
                chatBody.scrollTop = chatBody.scrollHeight;
            }
            
            // Focar no campo de mensagem
            const mensagemInput = document.getElementById('mensagem');
            if (mensagemInput) {
                mensagemInput.focus();
            }
            
            // Verificar novas mensagens a cada 10 segundos
            setInterval(function() {
                checkNewMessages();
            }, 10000);
            
            // Função para verificar novas mensagens via AJAX
            function checkNewMessages() {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajax/check_messages.php?partida=<?= $id_partida ?>&last=' + getLastMessageTime(), true);
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        if (response.success && response.messages.length > 0) {
                            addNewMessages(response.messages);
                        }
                    }
                };
                xhr.send();
            }
            
            // Obter timestamp da última mensagem
            function getLastMessageTime() {
                const messages = document.querySelectorAll('.chat-message');
                if (messages.length === 0) return 0;
                
                const lastMessage = messages[messages.length - 1];
                const timeElement = lastMessage.querySelector('.message-time');
                if (!timeElement) return 0;
                
                // Aqui você precisaria extrair o timestamp da mensagem
                // Como estamos usando formatação personalizada, vamos retornar o número de mensagens
                return messages.length;
            }
            
            // Adicionar novas mensagens ao chat
            function addNewMessages(messages) {
                const chatBody = document.getElementById('chat-body');
                if (!chatBody) return;
                
                // Remover mensagem de chat vazio se existir
                const emptyChat = chatBody.querySelector('.empty-chat');
                if (emptyChat) {
                    chatBody.removeChild(emptyChat);
                }
                
                // Adicionar cada nova mensagem
                messages.forEach(function(msg) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'chat-message ' + (msg.is_sent ? 'message-sent' : 'message-received');
                    
                    messageDiv.innerHTML = `
                        ${msg.mensagem}
                        <div class="message-info">
                            <span class="message-sender">${msg.remetente_nome}</span>
                            <span class="message-time">${msg.data_formatada}</span>
                        </div>
                    `;
                    
                    chatBody.appendChild(messageDiv);
                });
                
                // Rolar para o final
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        });
    </script>
</body>
</html>
