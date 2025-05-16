<?php
session_start();
include('conexao.php');
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if ($senha === $usuario["senha"]) {
            $_SESSION["usuario"] = $usuario;
            header("Location: index.php");
            exit;
        } else {
            $mensagem = "❌ Senha incorreta.";
        }
    } else {
        $mensagem = "⚠️ E-mail não encontrado.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFUT - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../CSS/geral.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
    <h2 class="titulo-pagina">Login</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-warning text-center"><?= $mensagem ?></div>
    <?php endif; ?>

    <form method="POST" class="w-50 mx-auto" style="max-width: 450px;">
        <div class="mb-3">
            <label for="email" class="form-label">Seu E-mail:</label>
            <input type="email" class="form-control" name="email" placeholder="Digite seu e-mail" required>
        </div>
        <div class="mb-3 position-relative">
            <label for="senha" class="form-label">Sua Senha:</label>
            <div class="input-senha-container">
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua Senha" required>
                <span class="toggle-olho" onclick="toggleSenha('senha')">👁️</span>
            </div>
        </div>
        <button type="submit" class="btn btn-success w-50 d-block mx-auto mt-2">Entrar</button>
        <a href="recuperacao.php" class="btn btn-success w-50 d-block mx-auto mt-2">Esqueceu sua senha?</a>
        <a href="cadastro.php" class="btn btn-success w-50 d-block mx-auto mt-2">Cadastre-se</a>
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