<?php
require_once 'includes/session.php';
require_once 'config/database.php';

// Se já estiver logado, redirecionar
if (isLoggedIn()) {
    header("Location: tela_inicial.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $posicao = $_POST['posicao'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $nivel_jogador = $_POST['nivel_jogador'] ?? '';
    $altura = $_POST['altura'] ?? '';
    $peso = $_POST['peso'] ?? '';
    $cep = trim($_POST['cep'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    
    // Validações básicas - TODOS OS CAMPOS OBRIGATÓRIOS
    if (empty($nome) || empty($email) || empty($telefone) || empty($cpf) || empty($senha) || 
        empty($posicao) || empty($data_nascimento) || empty($nivel_jogador) || 
        empty($altura) || empty($peso) || empty($cep) || empty($bairro)) {
        $error = 'Todos os campos obrigatórios devem ser preenchidos.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 4) {
        $error = 'A senha deve ter pelo menos 4 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } else {
        // Validar idade mínima (14 anos)
        $data_nasc = new DateTime($data_nascimento);
        $hoje = new DateTime();
        $idade = $hoje->diff($data_nasc)->y;
        
        if ($idade < 14) {
            $error = 'Você deve ter pelo menos 14 anos para se cadastrar.';
        } elseif ($idade > 100) {
            $error = 'Data de nascimento inválida.';
        } elseif ($altura < 100 || $altura > 250) {
            $error = 'Altura deve estar entre 100cm e 250cm.';
        } elseif ($peso < 30 || $peso > 300) {
            $error = 'Peso deve estar entre 30kg e 300kg.';
        } elseif (!preg_match('/^\d{5}-?\d{3}$/', $cep)) {
            $error = 'CEP deve ter o formato 00000-000.';
        } else {
            try {
                $db = new Database();
                // Verificar se email já existe
                $stmt = $db->getConnection()->prepare("SELECT id_usuario FROM usuarios WHERE email = ? OR cpf = ?");
                $stmt->execute([$email, $cpf]);
                
                if ($stmt->fetch()) {
                    $error = 'Este email ou CPF já está cadastrado.';
                } else {
                    // Formatar CEP
                    $cep = preg_replace('/\D/', '', $cep);
                    $cep = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
                    
                    // Criar usuário
                    $stmt = $db->getConnection()->prepare("
                        INSERT INTO usuarios (nome, email, telefone, cpf, senha, posicao, data_nascimento, nivel_jogador, altura, peso, cep, bairro) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$nome, $email, $telefone, $cpf, $senha, $posicao, $data_nascimento, $nivel_jogador, $altura, $peso, $cep, $bairro])) {
                        $success = 'Cadastro realizado com sucesso! Você pode fazer login agora.';
                    } else {
                        $error = 'Erro ao criar conta. Tente novamente.';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erro no sistema. Tente novamente.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/estilo.css">
    <link rel="stylesheet" href="CSS/campo-futebol.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMG/logo_ifut.png">
<link rel="shortcut icon" href="IMG/logo_ifut.png" type="image/x-icon">

<link rel="apple-touch-icon" sizes="180x180" href="IMG/logo_ifut.png">

<link rel="icon" sizes="192x192" href="IMG/logo_ifut.png">
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-12">
                    <div class="card neon-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="bi bi-person-plus display-4 text-success"></i>
                                <h2 class="neon-text-white mt-3">Criar Conta</h2>
                                <p class="text-muted">Junte-se à comunidade IFUT</p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="cadastroForm">
                                <!-- Dados Básicos -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-person-circle"></i> Dados Pessoais
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nome" class="form-label">
                                                <i class="bi bi-person"></i> Nome Completo
                                            </label>
                                            <input type="text" class="form-control neon-input" id="nome" name="nome" 
                                                   value="<?= htmlspecialchars($nome ?? '') ?>" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">
                                                <i class="bi bi-envelope"></i> Email
                                            </label>
                                            <input type="email" class="form-control neon-input" id="email" name="email" 
                                                   value="<?= htmlspecialchars($email ?? '') ?>" placeholder="seu@gmail.com" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="telefone" class="form-label">
                                                <i class="bi bi-phone"></i> Telefone
                                            </label>
                                            <input type="tel" class="form-control neon-input telefone-mask" id="telefone" name="telefone" 
                                                   value="<?= htmlspecialchars($telefone ?? '') ?>" placeholder="(11) 99999-9999" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="cpf" class="form-label">
                                                <i class="bi bi-card-text"></i> CPF
                                            </label>
                                            <input type="text" class="form-control neon-input cpf-mask" id="cpf" name="cpf" 
                                                   value="<?= htmlspecialchars($cpf ?? '') ?>" placeholder="000.000.000-00" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="data_nascimento" class="form-label">
                                                <i class="bi bi-calendar"></i> Data de Nascimento
                                            </label>
                                            <input type="date" class="form-control neon-input" id="data_nascimento" name="data_nascimento" 
                                                   value="<?= htmlspecialchars($data_nascimento ?? '') ?>" required>
                                            <div class="form-text text-white">Idade mínima: 14 anos</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="senha" class="form-label">
                                                <i class="bi bi-lock"></i> Senha
                                            </label>
                                            <div class="input-group">
                                                <input type="password" class="form-control neon-input" id="senha" name="senha" required>
                                                <button type="button" class="btn btn-outline-success" data-toggle="password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                            <div class="form-text text-white">Mínimo de 4 caracteres</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="confirmar_senha" class="form-label">
                                                <i class="bi bi-lock-fill"></i> Confirmar Senha
                                            </label>
                                            <input type="password" class="form-control neon-input" id="confirmar_senha" name="confirmar_senha" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dados Físicos -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-person-badge"></i> Características Físicas
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="altura" class="form-label">
                                                <i class="bi bi-arrows-vertical"></i> Altura (cm)
                                            </label>
                                            <input type="number" class="form-control neon-input" id="altura" name="altura" 
                                                   value="<?= htmlspecialchars($altura ?? '') ?>" min="100" max="250" placeholder="175" required>
                                            <div class="form-text text-white">Entre 100cm e 250cm</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="peso" class="form-label">
                                                <i class="bi bi-speedometer2"></i> Peso (kg)
                                            </label>
                                            <input type="number" class="form-control neon-input" id="peso" name="peso" 
                                                   value="<?= htmlspecialchars($peso ?? '') ?>" min="30" max="300" step="0.1" placeholder="70.5" required>
                                            <div class="form-text text-white">Entre 30kg e 300kg</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Localização -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-geo-alt"></i> Localização
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cep" class="form-label">
                                                <i class="bi bi-mailbox"></i> CEP
                                            </label>
                                            <input type="text" class="form-control neon-input cep-mask" id="cep" name="cep" 
                                                   value="<?= htmlspecialchars($cep ?? '') ?>" placeholder="00000-000" required>
                                            <div class="form-text text-white">Para sugerir partidas próximas</div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="bairro" class="form-label">
                                                <i class="bi bi-building"></i> Bairro
                                            </label>
                                            <input type="text" class="form-control neon-input" id="bairro" name="bairro" 
                                                   value="<?= htmlspecialchars($bairro ?? '') ?>" placeholder="Nome do seu bairro" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nível do Jogador -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-trophy"></i> Nível de Jogo
                                    </h5>
                                    <label class="form-label">
                                        <i class="bi bi-star"></i> Selecione seus níveis de jogo
                                    </label>
                                    <p class="text-muted small mb-3">Você pode selecionar múltiplos níveis:</p>
                                    
                                    <div class="row">
                                        <?php 
                                        $niveis = ['Resenha', 'Iniciante', 'Intermediário', 'Avançado', 'Semi-Amador', 'Amador'];
                                        $niveis_selecionados = explode(',', $nivel_jogador ?? '');
                                        ?>
                                        <?php foreach ($niveis as $nivel): ?>
                                            <div class="col-md-4 col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="nivel_<?= strtolower(str_replace([' ', '-'], '_', $nivel)) ?>" 
                                                           name="nivel_checks[]" value="<?= $nivel ?>"
                                                           <?= in_array($nivel, $niveis_selecionados) ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-white" 
                                                           for="nivel_<?= strtolower(str_replace([' ', '-'], '_', $nivel)) ?>">
                                                        <?= $nivel ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" id="nivel_jogador" name="nivel_jogador" value="<?= htmlspecialchars($nivel_jogador ?? '') ?>" required>
                                    <div class="form-text text-danger" id="nivel-error" style="display: none;">
                                        Selecione pelo menos um nível de jogo.
                                    </div>
                                </div>

                                <!-- Posições -->
                                <div class="mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-geo-alt"></i> Posições Preferidas
                                    </h5>
                                    <label class="form-label">
                                        <i class="bi bi-geo-alt"></i> Suas Posições Preferidas
                                    </label>
                                    <p class="text-muted small mb-3">Clique na sua posição preferida ou em mais de uma que você goste de jogar no campo abaixo:</p>
                                    
                                    <div id="campo-posicao-cadastro"></div>
                                    <input type="hidden" id="posicao" name="posicao" value="<?= htmlspecialchars($posicao ?? '') ?>" required>
                                    
                                    <div class="invalid-feedback" id="posicao-error" style="display: none;">
                                        Selecione pelo menos uma posição preferida no campo.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success w-100 neon-btn">
                                    <i class="bi bi-person-plus"></i> Criar Conta
                                </button>
                            </form>

                            <div class="text-center mt-4">
                                <p class="text-muted">
                                    Já tem uma conta? 
                                    <a href="login.php" class="text-success">Fazer Login</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts na ordem correta -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/logica.js"></script>
    <script src="JS/campo-futebol.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("🔧 Inicializando página de cadastro...")
            
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
            
            // Gerenciar seleção de níveis
            const niveisCheckboxes = document.querySelectorAll('input[name="nivel_checks[]"]');
            const niveisInput = document.getElementById('nivel_jogador');
            
            function atualizarNiveis() {
                const selecionados = Array.from(niveisCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                niveisInput.value = selecionados.join(',');
                console.log("📝 Níveis selecionados:", selecionados);
                
                // Remover erro se pelo menos um nível foi selecionado
                if (selecionados.length > 0) {
                    const errorElement = document.getElementById('nivel-error');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                }
            }
            
            niveisCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', atualizarNiveis);
            });
            
            // Aguardar um pouco para garantir que os scripts carregaram
            setTimeout(() => {
                // Verificar se a função existe
                if (typeof initCampoFutebol === 'function') {
                    console.log("✅ Função initCampoFutebol encontrada")
                    
                    // Inicializar campo de futebol
                    const campoCadastro = initCampoFutebol('campo-posicao-cadastro', {
                        multiSelect: true,
                        showLegenda: false,
                        posicoesSelecionadas: ['<?= $posicao ?? '' ?>'].filter(p => p),
                        posicoesDisponiveis: ['GOL', 'ZAG', 'ALA ESQ', 'ALA DIR', 'VOL', 'MEI', 'ATA'],
                        onChange: function(selecionadas) {
                            console.log("📝 Posições selecionadas:", selecionadas)
                            const inputPosicao = document.getElementById('posicao')
                            if (inputPosicao) {
                                inputPosicao.value = selecionadas.join(',')
                                console.log("📝 Input atualizado:", inputPosicao.value)
                                
                                // Remover erro se posição foi selecionada
                                if (selecionadas.length > 0) {
                                    const errorElement = document.getElementById('posicao-error')
                                    if (errorElement) {
                                        errorElement.style.display = 'none'
                                    }
                                }
                            }
                        }
                    })
                    
                    console.log("✅ Campo inicializado:", campoCadastro)
                } else {
                    console.error("❌ Função initCampoFutebol não encontrada")
                }
            }, 500)
            
            // Validação do formulário
            const form = document.getElementById('cadastroForm')
            if (form) {
                form.addEventListener('submit', function(e) {
                    let hasError = false;
                    
                    // Validar posição
                    const posicao = document.getElementById('posicao').value
                    if (!posicao) {
                        hasError = true;
                        const errorElement = document.getElementById('posicao-error')
                        if (errorElement) {
                            errorElement.style.display = 'block'
                        }
                    }
                    
                    // Validar níveis
                    const niveis = document.getElementById('nivel_jogador').value
                    if (!niveis) {
                        hasError = true;
                        const errorElement = document.getElementById('nivel-error')
                        if (errorElement) {
                            errorElement.style.display = 'block'
                        }
                    }
                    
                    if (hasError) {
                        e.preventDefault()
                        console.log("❌ Formulário bloqueado - campos obrigatórios não preenchidos")
                        return false
                    }
                    
                    console.log("✅ Formulário válido, enviando...")
                })
            }
        })
    </script>
</body>
</html>
