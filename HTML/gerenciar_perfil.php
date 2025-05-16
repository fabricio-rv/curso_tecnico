<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
$usuario = $_SESSION["usuario"];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>IFUT - Gerenciar Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../CSS/geral.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4 text-center">
    <h2 class="titulo-pagina">Gerenciar Perfil</h2>
    <p><strong>Nome:</strong> <?= htmlspecialchars($usuario["nome"]) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($usuario["email"]) ?></p>
    <p><strong>Telefone:</strong> <?= htmlspecialchars($usuario["telefone"]) ?></p>
    <?php
    $cpfOriginal = $usuario["cpf"];
    $cpfMascarado = substr($cpfOriginal, 0, 3) . '.***.***-' . substr($cpfOriginal, -2);
    $senhaOriginal = $usuario["senha"];
    $senhaMascarada = substr($senhaOriginal, 0, 3) . str_repeat('*', max(0, strlen($senhaOriginal) - 3));
    ?>
    <p><strong>CPF:</strong> <?= $cpfMascarado ?></p>
    <p><strong>Senha:</strong> <?= $senhaMascarada ?></p>
    <p><strong>Posição:</strong> <?= htmlspecialchars($usuario["posicao"]) ?></p>
</div>

<div class="d-grid gap-2 col-6 mx-auto mb-4">
    <button class="btn btn-success btn-large" id="btn-editar">Editar Usuário</button>
    <button class="btn btn-success btn-large" id="btn-alterar">Alterar Senha</button>
    <button class="btn btn-danger btn-large" id="btn-sair">Sair da Conta</button>
</div>
<!-- Modal de confirmação -->
<div id="modal-confirmacao" class="modal-custom" style="display: none;">
  <div class="modal-content-custom">
    <h5 id="texto-modal">Deseja continuar?</h5>
    <a id="link-confirmar" href="#" class="btn btn-success">Sim</a>
    <button onclick="fecharModal()" class="btn btn-danger mt-2">Voltar</button>
  </div>
</div>
<script>
function abrirModal(texto, destino) {
  document.getElementById("texto-modal").textContent = texto;
  document.getElementById("link-confirmar").href = destino;
  document.getElementById("modal-confirmacao").style.display = "flex";
}

function fecharModal() {
  document.getElementById("modal-confirmacao").style.display = "none";
}

document.getElementById("btn-editar").onclick = () =>
  abrirModal("Deseja ir para edição do usuário?", "editar_usuario.php");

document.getElementById("btn-alterar").onclick = () =>
  abrirModal("Deseja alterar sua senha?", "alterar_senha.php");

  document.querySelectorAll("#btn-sair").forEach(function(botao) {
  botao.addEventListener("click", function () {
    abrirModal("Deseja realmente sair da conta?", "logout.php");
  });
});
</script>
</body>
</html>