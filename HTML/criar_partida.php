<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
include("conexao.php");
$id_usuario = $_SESSION["usuario"]["id_usuario"];

$mensagem = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $estado = $_POST['estado'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $data = $_POST['data'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $turno = $_POST['turno'] ?? '';
    $posicoes = $_POST['posicoes'] ?? [];

    if ($estado && $cidade && $endereco && $data && $horario && $turno && !empty($posicoes)) {
        $posicoes_str = implode(",", $posicoes);

        $stmt = $mysqli->prepare("INSERT INTO partidas (id_usuario, estado, cidade, endereco, data, horario, turno, posicoes_restantes, posicoes_marcadas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '')");
        $stmt->bind_param("isssssss", $id_usuario, $estado, $cidade, $endereco, $data, $horario, $turno, $posicoes_str);
        $stmt->execute();

        echo "<script>setTimeout(() => window.location.href='partidas_criadas.php', 2000);</script>";
        $mensagem = "✔️ Partida criada com sucesso!";
    } else {
        $mensagem = "❌ Complete todos os campos obrigatórios!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>IFUT - Criar Partida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/geral.css">
    <style>
        .modal-custom {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .modal-content-custom {
            background: #111;
            padding: 25px;
            border: 2px solid lime;
            box-shadow: 0 0 20px lime;
            border-radius: 12px;
            text-align: center;
            color: lime;
            animation: zoomIn 0.3s ease;
        }
        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
    <h2 class="titulo-pagina">Criar Partida</h2>
    <form method="POST" id="form-criar" class="w-50 mx-auto" style="max-width: 450px;">
        <div class="mb-3">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" class="form-control" required>
                <option value="" disabled selected>Selecione o Estado</option>
                <?php foreach (["AC","AL","AP","AM","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR","PE","PI","RJ","RN","RS","RO","RR","SC","SP","SE","TO"] as $uf): ?>
                    <option value="<?= $uf ?>"><?= $uf ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Cidade</label>
            <input type="text" name="cidade" class="form-control" placeholder="Digite sua cidade" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Endereço</label>
            <input type="text" name="endereco" class="form-control" placeholder="Digite o endereço" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Data</label>
            <input type="date" name="data" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Horário</label>
            <input type="time" name="horario" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Turno:</label>
            <select name="turno" class="form-control text-center" required>
                <option value="" disabled selected>Selecione o Turno</option>
                <option value="MANHÃ">MANHÃ</option>
                <option value="TARDE">TARDE</option>
                <option value="NOITE">NOITE</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Posições disponíveis:</label><br>
            <?php foreach (["GOL", "ZAG", "ALA D", "ALA E", "VOL", "MEI", "ATA"] as $p): ?>
                <label class="me-2"><input type="checkbox" name="posicoes[]" value="<?= $p ?>"> <?= $p ?></label>
            <?php endforeach; ?>
        </div>
        <div class="mt-4">
            <button type="button" onclick="abrirModalConfirmar()" class="btn btn-success w-100">Criar Partida</button>
            <button type="button" onclick="abrirModalCriadas()" class="btn btn-primary w-100 mt-2">Suas Partidas Criadas</button>
        </div>
    </form>
</div>

<div id="modal-msg" class="modal-custom">
    <div class="modal-content-custom">
        <h5 id="texto-msg">Mensagem</h5>
        <button class="btn btn-success mt-2" onclick="fecharModal()">OK</button>
    </div>
</div>

<div id="modal-confirmar" class="modal-custom">
  <div class="modal-content-custom">
    <h5>⚽ Tem certeza que deseja criar esta partida?</h5>
    <div class="mt-3 d-flex justify-content-center gap-3">
        <button type="button" class="btn btn-success" onclick="confirmarCriacaoPartida()">Sim, criar</button>
        <button type="button" class="btn btn-danger" onclick="fecharModal()">Voltar</button>
    </div>
  </div>
</div>

<div id="modal-criadas" class="modal-custom">
  <div class="modal-content-custom">
    <h5>📄 Deseja ir para suas partidas criadas?</h5>
    <div class="mt-3 d-flex justify-content-center gap-3">
        <a href="partidas_criadas.php" class="btn btn-success">Sim</a>
        <button type="button" class="btn btn-danger" onclick="fecharModal()">Voltar</button>
    </div>
  </div>
</div>

<script>
function abrirModalConfirmar() {
    const form = document.getElementById('form-criar');
    const requiredFields = ['estado', 'cidade', 'endereco', 'data', 'horario', 'turno'];
    for (const name of requiredFields) {
        if (!form[name].value.trim()) {
            abrirMensagem("❌ Preencha todos os campos obrigatórios.");
            return;
        }
    }
    const checkboxes = document.querySelectorAll("input[name='posicoes[]']:checked");
    if (checkboxes.length === 0) {
        abrirMensagem("❌ Selecione ao menos uma posição disponível.");
        return;
    }
    document.getElementById("modal-confirmar").style.display = "flex";
}
function abrirModalCriadas() {
    document.getElementById("modal-criadas").style.display = "flex";
}
function abrirMensagem(texto) {
    document.getElementById("texto-msg").innerText = texto;
    document.getElementById("modal-msg").style.display = "flex";
}
function fecharModal() {
    document.querySelectorAll(".modal-custom").forEach(modal => modal.style.display = "none");
}
function confirmarCriacaoPartida() {
    document.getElementById('form-criar').submit();
}
function abrirModalCriadas() {
    document.getElementById("modal-criadas-confirmar").style.display = "flex";
}
</script>

<?php if ($mensagem): ?>
<script>
    abrirMensagem("<?= $mensagem ?>");
</script>
<?php endif; ?>
<!-- Modal confirmar saída com campos preenchidos -->
<div id="modal-criadas-confirmar" class="modal-custom">
  <div class="modal-content-custom">
    <h5>⚠️ Tem certeza que deseja sair sem salvar sua partida?</h5>
    <div class="mt-3 d-flex justify-content-center gap-3">
        <a href="partidas_criadas.php" class="btn btn-success">Sim, sair</a>
        <button class="btn btn-danger" onclick="fecharModal()">Voltar</button>
    </div>
  </div>
</div>
</body>
</html>