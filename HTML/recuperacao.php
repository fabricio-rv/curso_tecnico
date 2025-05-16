<?php
session_start();
include('conexao.php');
$usuarioLogado = isset($_SESSION["usuario"]);
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];

    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mensagem = "📨 Um link de redefinição foi enviado para seu e-mail (simulação).";
    } else {
        $mensagem = "❌ E-mail não encontrado.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFUT - Recuperar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../CSS/geral.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
    <h2 class="titulo-pagina">Recuperação de Senha</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-info text-center"><?= $mensagem ?></div>
    <?php endif; ?>

    <form method="POST" class="w-50 mx-auto" style="max-width: 450px;">
        <p class="text-center">Digite seu e-mail para receber um link de redefinição de senha.</p>
        <div class="mb-3">
            <label for="email" class="form-label">Seu E-Mail:</label>
            <input type="email" class="form-control" name="email" placeholder="Digite seu e-mail" required>
        </div>
        <button type="submit" class="btn btn-success w-50 d-block mx-auto">Enviar Link</button>
        <a href="login.php" class="btn btn-success w-50 d-block mx-auto mt-2">Voltar ao Login</a>
    </form>
</div>
</body>
</html>