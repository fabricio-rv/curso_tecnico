<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Verificar se está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$user = getUser();

// Verificar parâmetros
if (!isset($_GET['partida']) || !is_numeric($_GET['partida'])) {
    echo json_encode(['success' => false, 'error' => 'Partida inválida']);
    exit;
}

$id_partida = (int)$_GET['partida'];
$last_count = isset($_GET['last']) ? (int)$_GET['last'] : 0;

try {
    $db = new Database();
    
    // Verificar se o usuário tem permissão para acessar este chat
    $stmt = $db->getConnection()->prepare("
        SELECT p.id_partida
        FROM partidas p
        WHERE p.id_partida = ? AND (
            p.id_usuario = ? OR 
            EXISTS (SELECT 1 FROM marcacoes m WHERE m.id_partida = p.id_partida AND m.id_usuario = ?)
        )
    ");
    $stmt->execute([$id_partida, $user['id_usuario'], $user['id_usuario']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão']);
        exit;
    }
    
    // Buscar novas mensagens
    $stmt = $db->getConnection()->prepare("
        SELECT m.*, u.nome as remetente_nome
        FROM mensagens_chat m
        JOIN usuarios u ON m.id_remetente = u.id_usuario
        WHERE m.id_partida = ?
        ORDER BY m.data_envio ASC
    ");
    $stmt->execute([$id_partida]);
    $all_messages = $stmt->fetchAll();
    
    // Se temos mais mensagens do que o último contado
    if (count($all_messages) > $last_count) {
        $new_messages = array_slice($all_messages, $last_count);
        
        // Formatar mensagens para JSON
        $formatted_messages = [];
        foreach ($new_messages as $msg) {
            $formatted_messages[] = [
                'mensagem' => htmlspecialchars($msg['mensagem']),
                'remetente_nome' => htmlspecialchars($msg['remetente_nome']),
                'data_formatada' => formatarDataHora($msg['data_envio']),
                'is_sent' => ($msg['id_remetente'] == $user['id_usuario'])
            ];
        }
        
        // Marcar mensagens como lidas
        $stmt = $db->getConnection()->prepare("
            UPDATE mensagens_chat
            SET lida = 1
            WHERE id_partida = ? AND id_remetente != ?
        ");
        $stmt->execute([$id_partida, $user['id_usuario']]);
        
        echo json_encode([
            'success' => true,
            'messages' => $formatted_messages
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'messages' => []
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no sistema']);
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