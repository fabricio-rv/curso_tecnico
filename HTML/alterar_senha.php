<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
include("conexao.php");

$usuario = $_SESSION["usuario"];
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $senhaAtual = $_POST["senha_atual"];
    $novaSenha = $_POST["nova_senha"];
    $confirmarSenha = $_POST["confirmar_senha"];

    $stmt = $mysqli->prepare("SELECT senha FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $usuario["id_usuario"]);
    $stmt->execute();
    $stmt->fetch();
    $stmt->close();

    if ($senhaAtual == $senha) {
        $mensagem = "❌ Senha atual incorreta!";
    } elseif (strlen($novaSenha) < 6) {
        $mensagem = "❌ A nova senha deve ter no mínimo 6 caracteres.";
    } elseif ($novaSenha !== $confirmarSenha) {
        $mensagem = "❌ A nova senha e a confirmação não coincidem!";
    } else {
        $stmt = $mysqli->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $novaSenha, $usuario["id_usuario"]);
        if ($stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', () => {
                    abrirModal('✔️ Senha alterada com sucesso!', 'gerenciar_perfil.php', true);
                });
            </script>";
        } else {
            $mensagem = "❌ Erro ao alterar senha.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Alterar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/geral.css">
    <link rel="stylesheet" href="../CSS/gerenciar_perfil.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
    <h2 class="titulo-pagina">Alterar Senha</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-info text-center"><?= $mensagem ?></div>
    <?php endif; ?>

    <form method="POST" id="form-senha" class="w-50 mx-auto" style="max-width: 450px;">
        <div class="mb-3">
            <label class="form-label">Senha Atual</label>
            <div class="input-senha-container">
                <input type="password" class="form-control" id="senhaAtual" name="senha_atual" placeholder="Digite sua Senha" required>
                <span class="toggle-olho" onclick="toggleSenha('senhaAtual')">👁️</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Nova Senha</label>
            <div class="input-senha-container">
                <input type="password" class="form-control" id="novaSenha" name="nova_senha" placeholder="Digite sua Nova Senha" required>
                <span class="toggle-olho" onclick="toggleSenha('novaSenha')">👁️</span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirmar Nova Senha</label>
            <div class="input-senha-container">
                <input type="password" class="form-control" id="confirmarSenha" name="confirmar_senha" placeholder="Confirme sua Nova Senha" required>
                <span class="toggle-olho" onclick="toggleSenha('confirmarSenha')">👁️</span>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100 mt-2">Confirmar Nova Senha</button>
        <button id="btn-voltar" class="btn btn-danger w-100 mt-2">Voltar</button>
    </form>
</div>

<!-- MODAL GLOBAL -->
<div id="modal-confirmacao" class="modal-custom" style="display: none;">
  <div class="modal-content-custom">
    <h5 id="texto-modal"></h5>
    <a id="link-confirmar" href="#" class="btn btn-success">Confirmar</a>
    <button id="btn-cancelar" onclick="fecharModal()" class="btn btn-danger mt-2">Voltar</button>
  </div>
</div>

<script>
function toggleSenha(id) {
    const campo = document.getElementById(id);
    campo.type = campo.type === "password" ? "text" : "password";
}

function abrirModal(texto, destino = null, apenasConfirmar = false, submeter = false) {
    const modal = document.getElementById("modal-confirmacao");
    const textoModal = document.getElementById("texto-modal");
    const btnConfirmar = document.getElementById("link-confirmar");
    const btnCancelar = document.getElementById("btn-cancelar");

    textoModal.innerHTML = texto;

    if (apenasConfirmar) {
        btnConfirmar.textContent = "Confirmar";
        btnConfirmar.href = destino || "#";
        btnConfirmar.onclick = null;
        btnConfirmar.style.display = "inline-block";
        btnCancelar.style.display = "none";
    } else if (submeter) {
        btnConfirmar.textContent = "Sim";
        btnConfirmar.href = "#";
        btnConfirmar.onclick = () => document.getElementById("form-senha").submit();
        btnConfirmar.style.display = "inline-block";
        btnCancelar.style.display = "inline-block";
    } else if (destino) {
        btnConfirmar.textContent = "Sim";
        btnConfirmar.href = destino;
        btnConfirmar.onclick = null;
        btnConfirmar.style.display = "inline-block";
        btnCancelar.style.display = "inline-block";
    } else {
        btnConfirmar.style.display = "none";
        btnCancelar.style.display = "inline-block";
    }

    modal.style.display = "flex";
}

function fecharModal() {
    document.getElementById("modal-confirmacao").style.display = "none";
}
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("form-senha");

    document.getElementById("btn-voltar").addEventListener("click", function(e) {
        e.preventDefault();

        const atual = form.senha_atual.value;
        const nova = form.nova_senha.value;
        const confirma = form.confirmar_senha.value;

        const mudou = atual || nova || confirma;

        if (mudou) {
            abrirModal("⚠️ Você alterou os dados. Deseja sair sem salvar?", "gerenciar_perfil.php");
        } else {
            abrirModal("Tem certeza que deseja voltar sem alterar a senha?", "gerenciar_perfil.php");
        }
    });
});
</script>
</body>
</html> 