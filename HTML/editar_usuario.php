<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
include('conexao.php');
$usuario = $_SESSION["usuario"];
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $telefone = $_POST["telefone"];
    $cpf = $_POST["cpf"];
    $posicao = $_POST["posicao"];

    $stmt = $mysqli->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, posicao = ?, cpf = ? WHERE id_usuario = ?");
    $stmt->bind_param("sssssi", $nome, $email, $telefone, $posicao, $cpf, $usuario["id_usuario"]);

    if ($stmt->execute()) {
        $_SESSION["usuario"]["nome"] = $nome;
        $_SESSION["usuario"]["email"] = $email;
        $_SESSION["usuario"]["telefone"] = $telefone;
        $_SESSION["usuario"]["cpf"] = $cpf;
        $_SESSION["usuario"]["posicao"] = $posicao;
        header("Location: editar_usuario.php?sucesso=1");
        exit;
    } else {
        $mensagem = "❌ Erro ao atualizar dados!";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário - IFUT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/geral.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
    <h2 class="titulo-pagina">Editar Usuário</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-info text-center"><?= $mensagem ?></div>
    <?php endif; ?>

    <form id="form-usuario" method="POST" class="w-50 mx-auto" style="max-width: 450px;">
        <div class="mb-3">
            <label class="form-label">Nome:</label>
            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($usuario["nome"]) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">E-mail:</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario["email"]) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Telefone:</label>
            <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($usuario["telefone"]) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">CPF:</label>
            <input type="text" name="cpf" class="form-control" value="<?= htmlspecialchars($usuario["cpf"]) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Posição:</label>
            <select name="posicao" class="form-control" required>
                <?php
                $posicoes = ["GOL", "ZAG", "ALA D", "ALA E", "VOL", "MEI", "ATA"];
                foreach ($posicoes as $p) {
                    $selected = ($usuario['posicao'] === $p) ? 'selected' : '';
                    echo "<option value='$p' $selected>$p</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" class="btn btn-success w-50 d-block mx-auto mt-2">Salvar Alterações</button>
        <button id="btn-voltar" class="btn btn-danger w-50 d-block mx-auto mt-2">Voltar</button>
    </form>
</div>

<!-- MODAL -->
<div id="modal-confirmacao" class="modal-custom" style="display: none;">
  <div class="modal-content-custom">
    <h5 id="texto-modal"></h5>
    <a id="link-confirmar" href="#" class="btn btn-success">Confirmar</a>
    <button id="btn-cancelar" onclick="fecharModal()" class="btn btn-danger mt-2">Voltar</button>
  </div>
</div>

<script>
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
        btnCancelar.style.display = "none"; // <- aqui escondemos
    } else if (submeter) {
        btnConfirmar.textContent = "Sim";
        btnConfirmar.href = "#";
        btnConfirmar.onclick = () => document.getElementById("form-usuario").submit();
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
    const form = document.getElementById("form-usuario");

    const original = {
        nome: form.nome.value,
        email: form.email.value,
        telefone: form.telefone.value,
        cpf: form.cpf.value,
        posicao: form.posicao.value,
    };

    form.addEventListener("submit", function(e) {
        const atual = {
            nome: form.nome.value,
            email: form.email.value,
            telefone: form.telefone.value,
            cpf: form.cpf.value,
            posicao: form.posicao.value,
        };

        const telValido = /^\(?\d{2}\)?9\d{4}-?\d{4}$/.test(atual.telefone);
        const cpfValido = /^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/.test(atual.cpf);
        const emailValido = /^[^\s@]+@gmail\.com$/.test(atual.email);

        let msg = "";
        if (!telValido) msg += "⚠️ Telefone inválido. Formato esperado: (DD)9XXXX-XXXX<br>";
        if (!cpfValido) msg += "⚠️ CPF inválido. Formato esperado: 000.000.000-00<br>";
        if (!emailValido) msg += "⚠️ Email precisa ser do tipo '@gmail.com'";

        if (msg) {
            e.preventDefault();
            abrirModal(msg, null, true);
            return;
        }

        const alterou = Object.keys(original).some(campo => original[campo] !== atual[campo]);

        if (!alterou) {
            e.preventDefault();
            abrirModal("⚠️ Nenhum dado foi alterado.", "gerenciar_perfil.php", true);
        }
    });

    document.getElementById("btn-voltar").addEventListener("click", function(e) {
        e.preventDefault();
        const atual = {
            nome: form.nome.value,
            email: form.email.value,
            telefone: form.telefone.value,
            cpf: form.cpf.value,
            posicao: form.posicao.value,
        };
        const alterou = Object.keys(original).some(campo => original[campo] !== atual[campo]);

        if (alterou) {
            abrirModal("⚠️ Você alterou os dados. Deseja sair sem salvar?", "gerenciar_perfil.php");
        } else {
            abrirModal("Tem certeza que deseja voltar sem alterar nada?", "gerenciar_perfil.php");
        }
    });

    <?php if (isset($_GET["sucesso"])): ?>
        abrirModal("✔️ Alterações salvas com sucesso!", "gerenciar_perfil.php", true);
    <?php endif; ?>
});
</script>
</body>
</html>