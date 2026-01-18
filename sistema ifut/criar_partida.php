<?php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = trim($_POST['estado'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $data = $_POST['data'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $turno = $_POST['turno'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $posicoes_restantes = $_POST['posicoes_restantes'] ?? '';
    
    // Novos campos
    $nivel_minimo = $_POST['nivel_minimo'] ?? '';
    $nivel_jogo = $_POST['nivel_jogo'] ?? '';
    $idade_minima = (int)($_POST['idade_minima'] ?? 14);
    $idade_maxima = (int)($_POST['idade_maxima'] ?? 60);
    $valor_por_pessoa = (float)($_POST['valor_por_pessoa'] ?? 0);
    $nome_local = trim($_POST['nome_local'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $tem_vestiario = isset($_POST['tem_vestiario']) ? 1 : 0;
    $tem_chuveiro = isset($_POST['tem_chuveiro']) ? 1 : 0;
    $tem_estacionamento = isset($_POST['tem_estacionamento']) ? 1 : 0;
    $contato_local = trim($_POST['contato_local'] ?? '');
    $duracao_minutos = (int)($_POST['duracao_minutos'] ?? 90);
    $intervalo = isset($_POST['intervalo']) ? 1 : 0;
    $arbitro_incluso = isset($_POST['arbitro_incluso']) ? 1 : 0;
    
    // Validações - TODOS OS CAMPOS OBRIGATÓRIOS
    if (empty($estado) || empty($cidade) || empty($endereco) || empty($data) || empty($horario) || empty($turno) || 
        empty($descricao) || empty($posicoes_restantes) || empty($nivel_jogo) || empty($nome_local) || 
        empty($cep) || empty($bairro) || empty($contato_local) || empty($nivel_minimo)) {
        $error = 'Nem todos os dados obrigatórios foram preenchidos.';
    } elseif (strtotime($data) < strtotime(date('Y-m-d'))) {
        $error = 'A data da partida deve ser hoje ou no futuro.';
    } elseif ($idade_minima < 14 || $idade_maxima > 100 || $idade_minima >= $idade_maxima) {
        $error = 'Idades inválidas. Mínima: 14 anos, Máxima: 100 anos.';
    } elseif ($duracao_minutos < 10 || $duracao_minutos > 300) {
        $error = 'Duração deve estar entre 10 e 300 minutos.';
    } elseif ($valor_por_pessoa < 0 || $valor_por_pessoa > 999.99) {
        $error = 'Valor por pessoa inválido.';
    } elseif (!preg_match('/^\d{5}-?\d{3}$/', $cep)) {
        $error = 'CEP deve ter o formato 00000-000.';
    } elseif (!preg_match('/^\(\d{2}\)\s\d{4,5}-\d{4}$/', $contato_local)) {
        $error = 'Telefone do local deve ter o formato (11) 99999-9999.';
    } else {
        try {
            $db = new Database();
            $user = getUser();
            
            // Formatar CEP
            $cep = preg_replace('/\D/', '', $cep);
            $cep = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
            
            $stmt = $db->getConnection()->prepare("
                INSERT INTO partidas (
                    estado, cidade, endereco, data, horario, turno, descricao, 
                    posicoes_restantes, id_usuario, nivel_minimo, nivel_jogo,
                    idade_minima, idade_maxima, valor_por_pessoa, nome_local,
                    cep, bairro, tem_vestiario, tem_chuveiro, tem_estacionamento,
                    contato_local, duracao_minutos, intervalo, arbitro_incluso
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([
                $estado, $cidade, $endereco, $data, $horario, $turno, $descricao,
                $posicoes_restantes, $user['id_usuario'], $nivel_minimo, $nivel_jogo,
                $idade_minima, $idade_maxima, $valor_por_pessoa, $nome_local,
                $cep, $bairro, $tem_vestiario, $tem_chuveiro, $tem_estacionamento,
                $contato_local, $duracao_minutos, $intervalo, $arbitro_incluso
            ])) {
                $success = 'Partida criada com sucesso!';
                // Limpar campos após sucesso
                $estado = $cidade = $endereco = $data = $horario = $turno = $descricao = $posicoes_restantes = '';
                $nivel_minimo = $nivel_jogo = $nome_local = $cep = $bairro = $contato_local = '';
                $idade_minima = 14; $idade_maxima = 60; $valor_por_pessoa = 0; $duracao_minutos = 90;
                $tem_vestiario = $tem_chuveiro = $tem_estacionamento = $intervalo = $arbitro_incluso = false;
                
                // Mostrar modal de sucesso via JavaScript
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                    });
                </script>";
            } else {
                $error = 'Erro ao criar partida. Tente novamente.';
            }
        } catch (PDOException $e) {
            $error = 'Erro no sistema. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Partida - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="CSS/campo-futebol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
    
    <style>
    /* Estilo específico para posições amarelas na tela de criar partida */
    #campo-posicoes-criar .posicao-jogador.disponivel {
        background: linear-gradient(135deg, #ffc107, #e0a800) !important;
        border-color: #ffffff !important;
        color: #000000 !important;
        cursor: pointer;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.4);
    }

    /* Quando selecionadas, ficam verde neon */
    #campo-posicoes-criar .posicao-jogador.selecionada {
        background: linear-gradient(135deg, #00ff41, #00cc33) !important;
        border-color: #ffffff !important;
        color: #000000 !important;
        box-shadow: 0 0 15px rgba(0, 255, 65, 0.6);
        cursor: pointer;
    }

    /* Botão Voltar em vermelho e mesmo tamanho */
    .btn-voltar {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 12px 160px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 1rem;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 180px;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    .btn-voltar:hover {
        background-color: #c82333;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        text-decoration: none;
    }

    /* Botão Criar Partida ajustado para mesmo tamanho */
    .btn-criar {
        background-color: #00ff00;
        color: #000;
        border: none;
        padding: 12px 160px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 1rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 180px;
        box-shadow: 0 4px 15px rgba(0, 255, 0, 0.3);
    }

    .btn-criar:hover {
        background-color: #00cc00;
        color: #000;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 255, 0, 0.4);
    }

    /* Estilos dos modais de feedback */
    .modal-feedback .modal-dialog {
        max-width: 500px;
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

    .modal-feedback .icon-error {
        font-size: 4em;
        color: #ff3b30;
        margin-bottom: 20px;
        text-shadow: 0 0 20px rgba(255, 59, 48, 0.5);
    }

    .modal-feedback .icon-warning {
        font-size: 4em;
        color: #ffcc00;
        margin-bottom: 20px;
        text-shadow: 0 0 20px rgba(255, 204, 0, 0.5);
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

    .btn-vermelho-modal {
        background: #dc3545;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: bold;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        min-width: 180px;
    }

    .btn-vermelho-modal:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        color: white;
    }

    /* Ajustes para botões nos modais */
    .modal-footer {
        justify-content: center;
        gap: 15px;
    }

    @media (max-width: 768px) {
        .modal-feedback .modal-footer {
            flex-direction: column;
            gap: 10px;
        }
    
        .btn-verde-modal, .btn-azul-modal, .btn-vermelho-modal {
            width: 100%;
            min-width: auto;
        }

        .btn-voltar, .btn-criar {
            width: 100%;
            margin-bottom: 10px;
        }
}

/* Legenda personalizada para criar partida */
.legenda-criar-partida {
    background: rgba(0, 0, 0, 0.8);
    border: 1px solid #00ff00;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.legenda-criar-partida .legenda-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    color: #ffffff;
    font-size: 0.9rem;
}

.legenda-criar-partida .legenda-item:last-child {
    margin-bottom: 0;
}

.legenda-criar-partida .cor-exemplo {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid;
}

.legenda-criar-partida .cor-verde {
    background-color: #00ff00;
    border-color: #00ff00;
}

.legenda-criar-partida .cor-amarela {
    background-color: #ffc107;
    border-color: #ffc107;
}

/* Ajuste para os botões de ação */
.acoes-form {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding: 0 50px;
}
</style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card neon-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="bi bi-plus-circle display-4 text-success"></i>
                                <h2 class="neon-text-white mt-3">Criar Nova Partida</h2>
                                <p class="text-muted">Configure todos os detalhes da sua partida</p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                </div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                                        errorModal.show();
                                    });
                                </script>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="criarPartidaForm">
                                <!-- Informações Básicas -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-info-circle"></i> Informações Básicas
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="estado" class="form-label">
                                                <i class="bi bi-map"></i> Estado
                                            </label>
                                            <select class="form-control neon-input" id="estado" name="estado" required>
                                                <option value="">Selecione o estado</option>
                                                <option value="AC" <?= ($estado ?? '') === 'AC' ? 'selected' : '' ?>>Acre</option>
                                                <option value="AL" <?= ($estado ?? '') === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                                                <option value="AP" <?= ($estado ?? '') === 'AP' ? 'selected' : '' ?>>Amapá</option>
                                                <option value="AM" <?= ($estado ?? '') === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                                                <option value="BA" <?= ($estado ?? '') === 'BA' ? 'selected' : '' ?>>Bahia</option>
                                                <option value="CE" <?= ($estado ?? '') === 'CE' ? 'selected' : '' ?>>Ceará</option>
                                                <option value="DF" <?= ($estado ?? '') === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                                                <option value="ES" <?= ($estado ?? '') === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                                                <option value="GO" <?= ($estado ?? '') === 'GO' ? 'selected' : '' ?>>Goiás</option>
                                                <option value="MA" <?= ($estado ?? '') === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                                                <option value="MT" <?= ($estado ?? '') === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                                                <option value="MS" <?= ($estado ?? '') === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                                                <option value="MG" <?= ($estado ?? '') === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                                                <option value="PA" <?= ($estado ?? '') === 'PA' ? 'selected' : '' ?>>Pará</option>
                                                <option value="PB" <?= ($estado ?? '') === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                                                <option value="PR" <?= ($estado ?? '') === 'PR' ? 'selected' : '' ?>>Paraná</option>
                                                <option value="PE" <?= ($estado ?? '') === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                                                <option value="PI" <?= ($estado ?? '') === 'PI' ? 'selected' : '' ?>>Piauí</option>
                                                <option value="RJ" <?= ($estado ?? '') === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                                                <option value="RN" <?= ($estado ?? '') === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                                                <option value="RS" <?= ($estado ?? '') === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                                                <option value="RO" <?= ($estado ?? '') === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                                                <option value="RR" <?= ($estado ?? '') === 'RR' ? 'selected' : '' ?>>Roraima</option>
                                                <option value="SC" <?= ($estado ?? '') === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                                                <option value="SP" <?= ($estado ?? '') === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                                                <option value="SE" <?= ($estado ?? '') === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                                                <option value="TO" <?= ($estado ?? '') === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="cidade" class="form-label">
                                                <i class="bi bi-building"></i> Cidade
                                            </label>
                                            <input type="text" class="form-control neon-input" id="cidade" name="cidade" 
                                                   value="<?= htmlspecialchars($cidade ?? '') ?>" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="endereco" class="form-label">
                                                <i class="bi bi-geo-alt"></i> Endereço
                                            </label>
                                            <input type="text" class="form-control neon-input" id="endereco" name="endereco" 
                                                   value="<?= htmlspecialchars($endereco ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="data" class="form-label">
                                                <i class="bi bi-calendar"></i> Data
                                            </label>
                                            <input type="date" class="form-control neon-input" id="data" name="data" 
                                                   value="<?= htmlspecialchars($data ?? '') ?>" min="<?= date('Y-m-d') ?>" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="horario" class="form-label">
                                                <i class="bi bi-clock"></i> Horário
                                            </label>
                                            <input type="time" class="form-control neon-input" id="horario" name="horario" 
                                                   value="<?= htmlspecialchars($horario ?? '') ?>" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="turno" class="form-label">
                                                <i class="bi bi-sun"></i> Turno
                                            </label>
                                            <select class="form-control neon-input" id="turno" name="turno" required>
                                                <option value="">Selecione o turno</option>
                                                <option value="MANHÃ" <?= ($turno ?? '') === 'MANHÃ' ? 'selected' : '' ?>>Manhã</option>
                                                <option value="TARDE" <?= ($turno ?? '') === 'TARDE' ? 'selected' : '' ?>>Tarde</option>
                                                <option value="NOITE" <?= ($turno ?? '') === 'NOITE' ? 'selected' : '' ?>>Noite</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Local da Partida -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-building"></i> Detalhes do Local
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nome_local" class="form-label">
                                                <i class="bi bi-shop"></i> Nome do Campo/Quadra
                                            </label>
                                            <input type="text" class="form-control neon-input" id="nome_local" name="nome_local" 
                                                   value="<?= htmlspecialchars($nome_local ?? '') ?>" placeholder="Ex: Arena Sports" required>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="cep" class="form-label">
                                                <i class="bi bi-mailbox"></i> CEP
                                            </label>
                                            <input type="text" class="form-control neon-input cep-mask" id="cep" name="cep" 
                                                   value="<?= htmlspecialchars($cep ?? '') ?>" placeholder="00000-000" required>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="bairro" class="form-label">
                                                <i class="bi bi-signpost"></i> Bairro
                                            </label>
                                            <input type="text" class="form-control neon-input" id="bairro" name="bairro" 
                                                   value="<?= htmlspecialchars($bairro ?? '') ?>" placeholder="Nome do bairro" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contato_local" class="form-label">
                                                <i class="bi bi-telephone"></i> Telefone do Local
                                            </label>
                                            <input type="tel" class="form-control neon-input telefone-mask" id="contato_local" name="contato_local" 
                                                   value="<?= htmlspecialchars($contato_local ?? '') ?>" placeholder="(11) 99999-9999" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                <i class="bi bi-check2-square"></i> Facilidades do Local (Opcional)
                                            </label>
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="tem_vestiario" name="tem_vestiario" 
                                                               <?= ($tem_vestiario ?? false) ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-white" for="tem_vestiario">
                                                            Vestiário
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="tem_chuveiro" name="tem_chuveiro" 
                                                               <?= ($tem_chuveiro ?? false) ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-white" for="tem_chuveiro">
                                                            Chuveiro
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="tem_estacionamento" name="tem_estacionamento" 
                                                               <?= ($tem_estacionamento ?? false) ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-white" for="tem_estacionamento">
                                                            Estacionamento
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configurações da Partida -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-gear"></i> Configurações da Partida
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="nivel_jogo" class="form-label">
                                                <i class="bi bi-trophy"></i> Nível do Jogo
                                            </label>
                                            <select class="form-control neon-input" id="nivel_jogo" name="nivel_jogo" required>
                                                <option value="">Selecione o nível</option>
                                                <?php 
                                                $niveis_jogo = ['Família/Amigos', 'Resenha', 'Iniciante', 'Intermediário', 'Avançado', 'Semi-Amador', 'Amador'];
                                                foreach ($niveis_jogo as $nivel): ?>
                                                    <option value="<?= $nivel ?>" <?= ($nivel_jogo ?? '') === $nivel ? 'selected' : '' ?>>
                                                        <?= $nivel ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="valor_por_pessoa" class="form-label">
                                                <i class="bi bi-currency-dollar"></i> Valor por Pessoa
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-dark text-success border-success">R$</span>
                                                <input type="number" class="form-control neon-input" id="valor_por_pessoa" name="valor_por_pessoa" 
                                                       value="<?= htmlspecialchars($valor_por_pessoa ?? '0') ?>" min="0" max="999.99" step="0.01" placeholder="0,00" required>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="duracao_minutos" class="form-label">
                                                <i class="bi bi-stopwatch"></i> Duração (minutos)
                                            </label>
                                            <div class="input-group">
                                                <button type="button" class="btn btn-outline-success" onclick="alterarDuracao(-10)">
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                                <input type="number" class="form-control neon-input text-center" id="duracao_minutos" name="duracao_minutos" 
                                                       value="<?= htmlspecialchars($duracao_minutos ?? '90') ?>" min="10" max="300" readonly required>
                                                <button type="button" class="btn btn-outline-success" onclick="alterarDuracao(10)">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                            <div class="form-text text-white">Entre 10 e 300 minutos</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                <i class="bi bi-people"></i> Restrições de Idade
                                            </label>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label for="idade_minima" class="form-label small">Idade Mínima</label>
                                                    <div class="input-group">
                                                        <button type="button" class="btn btn-outline-success" onclick="alterarIdade('minima', -1)">
                                                            <i class="bi bi-dash"></i>
                                                        </button>
                                                        <input type="number" class="form-control neon-input text-center" id="idade_minima" name="idade_minima" 
                                                               value="<?= htmlspecialchars($idade_minima ?? '14') ?>" min="14" max="99" readonly required>
                                                        <button type="button" class="btn btn-outline-success" onclick="alterarIdade('minima', 1)">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label for="idade_maxima" class="form-label small">Idade Máxima</label>
                                                    <div class="input-group">
                                                        <button type="button" class="btn btn-outline-success" onclick="alterarIdade('maxima', -1)">
                                                            <i class="bi bi-dash"></i>
                                                        </button>
                                                        <input type="number" class="form-control neon-input text-center" id="idade_maxima" name="idade_maxima" 
                                                               value="<?= htmlspecialchars($idade_maxima ?? '60') ?>" min="15" max="100" readonly required>
                                                        <button type="button" class="btn btn-outline-success" onclick="alterarIdade('maxima', 1)">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    </div>
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
                                                        <input class="form-check-input" type="checkbox" id="intervalo" name="intervalo" 
                                                               <?= ($intervalo ?? false) ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-white" for="intervalo">
                                                            Tem Intervalo
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="arbitro_incluso" name="arbitro_incluso" 
                                                               <?= ($arbitro_incluso ?? false) ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-white" for="arbitro_incluso">
                                                            Árbitro Incluso
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nível Mínimo dos Jogadores -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-star"></i> Requisitos dos Jogadores
                                    </h5>
                                    <label class="form-label">
                                        <i class="bi bi-trophy"></i> Níveis Mínimos Aceitos
                                    </label>
                                    <p class="text-muted small mb-3">Selecione os níveis de jogadores que podem participar:</p>
                                    
                                    <div class="row">
                                        <?php 
                                        $niveis_minimos = ['Resenha', 'Iniciante', 'Intermediário', 'Avançado', 'Semi-Amador', 'Amador'];
                                        $niveis_selecionados = explode(',', $nivel_minimo ?? '');
                                        ?>
                                        <?php foreach ($niveis_minimos as $nivel): ?>
                                            <div class="col-md-4 col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="nivel_min_<?= strtolower(str_replace([' ', '-', '/'], '_', $nivel)) ?>" 
                                                           name="nivel_min_checks[]" value="<?= $nivel ?>"
                                                           <?= in_array($nivel, $niveis_selecionados) ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-white" 
                                                           for="nivel_min_<?= strtolower(str_replace([' ', '-', '/'], '_', $nivel)) ?>">
                                                        <?= $nivel ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" id="nivel_minimo" name="nivel_minimo" value="<?= htmlspecialchars($nivel_minimo ?? '') ?>" required>
                                </div>

                                <!-- Posições Disponíveis -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-people"></i> Posições Disponíveis
                                    </h5>
                                    <label class="form-label">
                                        <i class="bi bi-geo-alt"></i> Selecione as posições que precisam ser preenchidas
                                    </label>
                                    <p class="text-muted small mb-3">Clique nas posições no campo abaixo:</p>
                                    
                                    <div id="campo-posicoes-criar"></div>
                                    
                                    <!-- Legenda personalizada -->
                                    <div class="legenda-criar-partida">
                                        <div class="legenda-item">
                                            <div class="cor-exemplo cor-verde"></div>
                                            <span>Posições disponíveis para serem marcadas pelos usuários</span>
                                        </div>
                                        <div class="legenda-item">
                                            <div class="cor-exemplo cor-amarela"></div>
                                            <span>Posições indisponíveis para serem marcadas, já estão preenchidas</span>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" id="posicoes_restantes" name="posicoes_restantes" value="<?= htmlspecialchars($posicoes_restantes ?? '') ?>" required>
                                </div>

                                <!-- Descrição -->
                                <div class="mb-4">
                                    <label for="descricao" class="form-label">
                                        <i class="bi bi-chat-text"></i> Descrição da Partida
                                    </label>
                                    <textarea class="form-control neon-input" id="descricao" name="descricao" rows="4" 
                                              placeholder="Descreva detalhes adicionais sobre a partida..." required><?= htmlspecialchars($descricao ?? '') ?></textarea>
                                </div>

                                <div class="acoes-form">
                                    <button type="button" class="btn-voltar" id="btnVoltar">
                                        <i class="bi bi-arrow-left-circle"></i> Voltar
                                    </button>
                                    <button type="button" class="btn-criar" id="btnCriarPartida">
                                        <i class="bi bi-plus-circle"></i> Criar Partida
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Saída -->
    <div class="modal fade modal-feedback" id="confirmSairModal" tabindex="-1" aria-labelledby="confirmSairModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmSairModalLabel">
                        <i class="bi bi-question-circle"></i> Confirmar Saída
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="bi bi-exclamation-triangle icon-warning"></i>
                    <p>Tem certeza que deseja voltar sem criar nenhuma partida?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-vermelho-modal" id="btnConfirmarSair">
                        <i class="bi bi-arrow-left-circle"></i> Sim, voltar
                    </button>
                    <button type="button" class="btn-verde-modal" data-bs-dismiss="modal">
                        <i class="bi bi-pencil-square"></i> Continuar criando
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Criação -->
    <div class="modal fade modal-feedback" id="confirmCriarModal" tabindex="-1" aria-labelledby="confirmCriarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmCriarModalLabel">
                        <i class="bi bi-question-circle"></i> Confirmar Criação
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="bi bi-check-circle icon-success"></i>
                    <p>Tem certeza que deseja criar esta partida?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-verde-modal" id="btnConfirmarCriar">
                        <i class="bi bi-check-circle"></i> Sim, criar
                    </button>
                    <button type="button" class="btn-azul-modal" data-bs-dismiss="modal">
                        <i class="bi bi-pencil-square"></i> Revisar dados
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Erro -->
    <div class="modal fade modal-feedback" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="errorModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Erro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="bi bi-x-circle icon-error"></i>
                    <p>Faltam dados obrigatórios para preencher.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-verde-modal" data-bs-dismiss="modal">
                        <i class="bi bi-check-lg"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div class="modal fade modal-feedback" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="bi bi-check-circle"></i> Sucesso!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="bi bi-emoji-sunglasses icon-success"></i>
                    <p>A partida foi criada com sucesso!</p>
                </div>
                <div class="modal-footer">
                    <a href="partidas_criadas.php" class="btn-azul-modal">
                        <i class="bi bi-clipboard-check"></i> Ver minhas partidas
                    </a>
                    <a href="criar_partida.php" class="btn-verde-modal">
                        <i class="bi bi-plus-circle"></i> Criar outra partida
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script src="JS/campo-futebol.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("🔧 Inicializando página de criar partida...");
            
            // Máscara para CEP
            const cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 5) {
                        value = value.substring(0, 5) + '-' + value.substring(5, 8);
                    }
                    e.target.value = value;
                });
            }
            
            // Gerenciar seleção de níveis mínimos
            const niveisCheckboxes = document.querySelectorAll('input[name="nivel_min_checks[]"]');
            const niveisInput = document.getElementById('nivel_minimo');
            
            function atualizarNiveisMinimos() {
                const selecionados = Array.from(niveisCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                niveisInput.value = selecionados.join(',');
                console.log("📝 Níveis mínimos selecionados:", selecionados);
            }
            
            niveisCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', atualizarNiveisMinimos);
            });
            
            // Inicializar campo de futebol
            setTimeout(() => {
                if (typeof initCampoFutebol === 'function') {
                    console.log("✅ Função initCampoFutebol encontrada");
                    
                    const campoCriar = initCampoFutebol('campo-posicoes-criar', {
                        multiSelect: true,
                        showLegenda: false,
                        posicoesSelecionadas: [],
                        posicoesDisponiveis: ['GOL', 'ZAG', 'ALA ESQ', 'ALA DIR', 'VOL', 'MEI', 'ATA'],
                        customColors: {
                            default: '#ffc107',
                            selected: '#00ff00',
                            hover: '#ffc107'
                        },
                        onChange: function(selecionadas) {
                            console.log("📝 Posições selecionadas:", selecionadas);
                            const inputPosicoes = document.getElementById('posicoes_restantes');
                            if (inputPosicoes) {
                                inputPosicoes.value = selecionadas.join(',');
                                console.log("📝 Input atualizado:", inputPosicoes.value);
                            }
                        }
                    });

                    // Forçar aplicação das cores após inicialização
                    setTimeout(() => {
                        const posicoes = document.querySelectorAll('#campo-posicoes-criar .posicao, #campo-posicoes-criar .posicao-jogador');
                        posicoes.forEach(posicao => {
                            if (!posicao.classList.contains('selecionada') && !posicao.classList.contains('selected')) {
                                posicao.style.backgroundColor = '#ffc107';
                                posicao.style.borderColor = '#ffc107';
                                posicao.style.color = '#000';
                            }
                        });
                    }, 100);
                } else {
                    console.error("❌ Função initCampoFutebol não encontrada");
                }
            }, 500);
            
            // Validação do formulário
            const form = document.getElementById('criarPartidaForm');
            
            // Botão Voltar
            const btnVoltar = document.getElementById('btnVoltar');
            if (btnVoltar) {
                btnVoltar.addEventListener('click', function() {
                    const confirmSairModal = new bootstrap.Modal(document.getElementById('confirmSairModal'));
                    confirmSairModal.show();
                });
            }
            
            // Confirmar saída
            const btnConfirmarSair = document.getElementById('btnConfirmarSair');
            if (btnConfirmarSair) {
                btnConfirmarSair.addEventListener('click', function() {
                    window.location.href = 'index.php';
                });
            }
            
            // Botão Criar Partida
            const btnCriarPartida = document.getElementById('btnCriarPartida');
            if (btnCriarPartida) {
                btnCriarPartida.addEventListener('click', function() {
                    // Validar formulário antes de mostrar modal de confirmação
                    if (validarFormulario()) {
                        const confirmCriarModal = new bootstrap.Modal(document.getElementById('confirmCriarModal'));
                        confirmCriarModal.show();
                    }
                });
            }
            
            // Confirmar criação
            const btnConfirmarCriar = document.getElementById('btnConfirmarCriar');
            if (btnConfirmarCriar && form) {
                btnConfirmarCriar.addEventListener('click', function() {
                    form.submit();
                });
            }
            
            // Função para validar o formulário
            function validarFormulario() {
                let hasError = false;
                
                // Validar campos obrigatórios
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value) {
                        hasError = true;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                // Validar posições
                const posicoes = document.getElementById('posicoes_restantes').value;
                if (!posicoes) {
                    hasError = true;
                }
                
                // Validar níveis mínimos
                const niveis = document.getElementById('nivel_minimo').value;
                if (!niveis) {
                    hasError = true;
                }
                
                if (hasError) {
                    console.log("❌ Formulário bloqueado - campos obrigatórios não preenchidos");
                    
                    // Mostrar modal de erro
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();
                    
                    return false;
                }
                
                console.log("✅ Formulário válido");
                return true;
            }
            
            // Inicializar níveis mínimos
            atualizarNiveisMinimos();
        });
        
        // Funções para alterar duração
        function alterarDuracao(incremento) {
            const input = document.getElementById('duracao_minutos');
            let valor = parseInt(input.value) || 90;
            valor += incremento;
            
            if (valor < 10) valor = 10;
            if (valor > 300) valor = 300;
            
            input.value = valor;
        }
        
        // Funções para alterar idade
        function alterarIdade(tipo, incremento) {
            const input = document.getElementById('idade_' + tipo);
            let valor = parseInt(input.value);
            valor += incremento;
            
            if (tipo === 'minima') {
                if (valor < 14) valor = 14;
                if (valor > 99) valor = 99;
                
                // Garantir que mínima seja menor que máxima
                const maxima = parseInt(document.getElementById('idade_maxima').value);
                if (valor >= maxima) {
                    document.getElementById('idade_maxima').value = valor + 1;
                }
            } else {
                if (valor < 15) valor = 15;
                if (valor > 100) valor = 100;
                
                // Garantir que máxima seja maior que mínima
                const minima = parseInt(document.getElementById('idade_minima').value);
                if (valor <= minima) {
                    document.getElementById('idade_minima').value = valor - 1;
                }
            }
            
            input.value = valor;
        }
    </script>
</body>
</html>
