<?php
session_start();
include('conexao.php');
$usuarioLogado = isset($_SESSION["usuario"]);
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $telefone = $_POST["telefone"];
    $cpf = $_POST["cpf"];
    if (strlen($_POST["senha"]) < 6) {
        $mensagem = "❌ A senha deve ter no mínimo 6 caracteres.";
    } else {
        $senha = $_POST["senha"];
    }
    $posicao = $_POST["posicao"];

    $verifica = $mysqli->prepare("SELECT id_usuario FROM usuarios WHERE cpf = ? OR email = ?");
    $verifica->bind_param("ss", $cpf, $email);
    $verifica->execute();
    $verifica->store_result();

    if ($verifica->num_rows > 0) {
        $mensagem = "❌ Usuário com esse CPF ou E-mail já existe.";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO usuarios (nome, email, telefone, cpf, senha, posicao) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nome, $email, $telefone, $cpf, $senha, $posicao);
        if ($stmt->execute()) {
            $mensagem = "✅ Cadastro realizado com sucesso!";
            header("refresh:2;url=login.php");
        } else {
            $mensagem = "❌ Erro ao cadastrar usuário.";
        }
        $stmt->close();
    }

    $verifica->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/geral.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
    <h2 class="titulo-pagina">Cadastro</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-info text-center"><?= $mensagem ?></div>
    <?php endif; ?>

    <form method="POST" class="w-50 mx-auto" style="max-width: 450px;">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome e Sobrenome:</label>
            <input type="text" class="form-control" name="nome" placeholder="Digite seu Nome e Sobrenome" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Seu E-Mail:</label>
            <input type="email" class="form-control" name="email" placeholder="Digite seu E-mail" required>
        </div>
        <div class="mb-3">
            <label for="telefone" class="form-label">Número de Telefone:</label>
            <input type="tel" class="form-control" name="telefone" placeholder="(00) 00000-0000" required>
        </div>
        <div class="mb-3">
            <label for="cpf" class="form-label">Seu CPF:</label>
            <input type="text" class="form-control" name="cpf" placeholder="Digite seu CPF" required>
        </div>
        <div class="mb-3 position-relative">
            <label for="senha" class="form-label">Sua Senha:</label>
            <div class="input-senha-container">
                <input type="password" class="form-control" name="senha" id="senha" placeholder="Digite sua Senha" required>
                <span class="toggle-olho" onclick="toggleSenha('senha')">👁️</span>
            </div>
        </div>
        <div class="mb-3">
            <label for="posicao" class="form-label">Escolha sua posição:</label>
            <select class="form-control text-center" name="posicao" required>
                <option value="" disabled selected>Selecione a Posição</option>
                <option value="GOL">GOL</option>
                <option value="ZAG">ZAG</option>
                <option value="ALA DIR">ALA DIR</option>
                <option value="ALA ESQ">ALA ESQ</option>
                <option value="VOL">VOL</option>
                <option value="MEI">MEI</option>
                <option value="ATA">ATA</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success w-50 d-block mx-auto">Cadastrar</button>
        <a href="login.php" class="btn btn-success w-50 d-block mx-auto mt-3">Login</a>
    </form>
</div>

<script>
function toggleSenha(id) {
    const campo = document.getElementById(id);
    campo.type = campo.type === "password" ? "text" : "password";
}
</script>
</body>
</html>