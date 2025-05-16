<?php
session_start();

$mensagem = $_SESSION["mensagem"] ?? "";
unset($_SESSION["mensagem"]);

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
include('conexao.php');

$usuarioLogado = $_SESSION["usuario"];
$id_usuario = $usuarioLogado["id_usuario"];

$filtro_posicao = $_GET["posicao"] ?? "";
$filtro_turno = $_GET["turno"] ?? "";
$filtro_estado = $_GET["estado"] ?? "";

$mensagemErro = "";

// Marcar presença
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id_partida"], $_POST["posicao"])) {
    $id_partida = $_POST["id_partida"];
    $posicaoEscolhida = $_POST["posicao"];

    // Verifica se é o criador
    $verificaCriador = $mysqli->prepare("SELECT id_usuario FROM partidas WHERE id_partida = ?");
    $verificaCriador->bind_param("i", $id_partida);
    $verificaCriador->execute();
    $resCriador = $verificaCriador->get_result()->fetch_assoc();

    if ($resCriador["id_usuario"] == $id_usuario) {
        $_SESSION["mensagem"] = "❌ Você não pode marcar sua própria partida!";
    } else {
        $verifica = $mysqli->prepare("SELECT * FROM marcacoes WHERE id_usuario = ? AND id_partida = ?");
        $verifica->bind_param("ii", $id_usuario, $id_partida);
        $verifica->execute();
        $verifica->store_result();

        if ($verifica->num_rows === 0) {
            // Verifica se posição ainda está disponível
            $res = $mysqli->query("SELECT posicoes_restantes FROM partidas WHERE id_partida = $id_partida");
            $dados = $res->fetch_assoc();
            $restantes = explode(",", $dados["posicoes_restantes"]);

            if (in_array($posicaoEscolhida, $restantes)) {
                $novaLista = array_diff($restantes, [$posicaoEscolhida]);
                $novaString = implode(",", $novaLista);

                $mysqli->query("UPDATE partidas SET posicoes_restantes = '$novaString' WHERE id_partida = $id_partida");

                $stmt = $mysqli->prepare("INSERT INTO marcacoes (id_usuario, id_partida, posicao) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $id_usuario, $id_partida, $posicaoEscolhida);
                $stmt->execute();
                $_SESSION["mensagem"] = "✅ Partida marcada com sucesso! ⚽";
            }
        } else {
            $_SESSION["mensagem"] = "🚫 Você já marcou presença nessa partida!";
        }
    }
    header("Location: encontrar_partida.php");
    exit;
}

$hoje = date("Y-m-d");
$sql = "SELECT * FROM partidas WHERE data >= '$hoje'";
if ($filtro_posicao) $sql .= " AND posicoes_restantes LIKE '%$filtro_posicao%'";
if ($filtro_turno) $sql .= " AND turno = '$filtro_turno'";
if ($filtro_estado) $sql .= " AND estado = '$filtro_estado'";
$resultado = $mysqli->query($sql);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>IFUT - Encontrar Partida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/geral.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4 text-center">
    <h2 class="titulo-pagina">Encontre seu Jogo</h2>

    <?php if ($mensagemErro): ?>
        <div class="alert alert-danger"><?= $mensagemErro ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" class="row justify-content-center"
        onsubmit="mostrarMensagem('🔍 Buscando partidas...'); setTimeout(() => fecharMensagem(), 1200);">
        <div class="col-md-3">
            <label class="form-label">Posição que deseja jogar:</label>
            <select class="form-control text-center" name="posicao">
                <option value="">Posição</option>
                <?php
                $posicoes = ["GOL", "ZAG", "ALA D", "ALA E", "VOL", "MEI", "ATA"];
                foreach ($posicoes as $p) {
                    $sel = $filtro_posicao === $p ? "selected" : "";
                    echo "<option value='$p' $sel>$p</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Selecione o Turno que deseja:</label>
            <select class="form-control text-center" name="turno">
                <option value="">Turno</option>
                <?php
                foreach (["MANHÃ", "TARDE", "NOITE"] as $turno) {
                    $sel = $filtro_turno === $turno ? "selected" : "";
                    echo "<option value='$turno' $sel>$turno</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Selecione seu Estado:</label>
            <select class="form-control" name="estado">
                <option value="">Estado</option>
                <?php
                $estados = ["AC","AL","AP","AM","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR","PE","PI","RJ","RN","RS","RO","RR","SC","SP","SE","TO"];
                foreach ($estados as $uf) {
                    $sel = $filtro_estado === $uf ? "selected" : "";
                    echo "<option value='$uf' $sel>$uf</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label d-block invisible">.</label>
            <button type="button" class="btn btn-success w-100 mt-2" onclick="buscarPartidas()">Buscar 🔍</button>
            <form class="row g-2 mb-4" method="GET"
                onsubmit="mostrarMensagem('🔍 Buscando partidas...'); setTimeout(() => fecharMensagem(), 1000);">
        </div>
    </form>

    <!-- Resultados -->
    <div class="table-responsive mt-4">
        <table class="table table-dark table-bordered">
            <thead>
                <tr><th>Partida</th><th>Cidade</th><th>Endereço</th><th>Data</th><th>Horário</th><th>Turno</th><th>Posições</th><th>Marcar</th></tr>
            </thead>
            <tbody>
                <?php while ($row = $resultado->fetch_assoc()):
                    if ($row["id_usuario"] == $id_usuario) continue;

                    $posicoesDisponiveis = explode(",", $row["posicoes_restantes"]);
                    ?>
                    <tr>
                        <td><?= $row["id_partida"] ?></td>
                        <td><?= $row["cidade"] ?></td>
                        <td><?= $row["endereco"] ?></td>
                        <td><?= $row["data"] ?></td>
                        <td><?= $row["horario"] ?></td>
                        <td><?= $row["turno"] ?></td>
                        <td>
                            <?php foreach (["GOL", "ZAG", "ALA D", "ALA E", "VOL", "MEI", "ATA"] as $p):
                                $icone = in_array($p, $posicoesDisponiveis) ? "⏳" : "✅";
                                echo "<span class='badge bg-success m-1'>$p $icone</span>";
                            endforeach; ?>
                        </td>
                        <td>
                            <form method="POST" class="d-flex flex-column align-items-center">
                                <input type="hidden" name="id_partida" value="<?= $row["id_partida"] ?>">
                                <select class="form-control mb-2" name="posicao">
                                    <option>Posição</option>
                                    <?php foreach ($posicoesDisponiveis as $p): ?>
                                        <option value="<?= $p ?>"><?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-success" onclick="abrirModal(this)">✅</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalMarcar" class="modal-custom">
    <div class="modal-content-custom">
        <h4 id="textoModalMarcar" class="text-center mb-3"></h4>
        <form id="formMarcar" method="POST" action="encontrar_partida.php">
            <input type="hidden" name="id_partida" id="idPartidaModal">
            <input type="hidden" name="posicao" id="posicaoModal">
            <div class="mt-3 d-flex justify-content-center gap-3">
                <button type="submit" class="btn btn-success">Sim, marcar</button>
                <button type="button" class="btn btn-danger" onclick="fecharModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

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
    background-color: #121212;
    padding: 30px;
    border-radius: 10px;
    border: 2px solid #00ff00;
    color: #00ff00;
    box-shadow: 0 0 20px #00ff00;
    text-align: center;
    width: 100%;
    max-width: 400px;
}
td:nth-child(7), th:nth-child(7) {
    max-width: 200px;
    width: 200px;
    overflow-x: auto;
    text-wrap: wrap;
}
#textoModalMarcar {
    color: #00ff00 !important;
}
</style>

<script>

let buscarTimeout = null;

function abrirModal(botao) {
    const linha = botao.closest("tr");
    const id = linha.querySelector("input[name='id_partida']").value;
    const select = linha.querySelector("select[name='posicao']");
    const posicao = select.value;

    if (!posicao || posicao === "Posição") {
        document.getElementById("modalErro").style.display = "flex";
        return;
    }

    document.getElementById("idPartidaModal").value = id;
    document.getElementById("posicaoModal").value = posicao;

    document.getElementById("textoModalMarcar").innerHTML =
        `<strong>Tem certeza que deseja marcar essa partida?<br>Sua posição: ${posicao}</strong>`;

    document.getElementById("modalMarcar").style.display = "flex";
}
function fecharModal() {
    document.getElementById("modalMarcar").style.display = "none";
}
function fecharModalErro() {
    document.getElementById("modalErro").style.display = "none";
}
function mostrarMensagem(texto, mostrarBotao = false) {
    document.getElementById("mensagemTexto").innerText = texto;
    document.getElementById("modalMensagem").style.display = "flex";
    const btnMarcar = document.getElementById("btn-ir-marcadas");
    btnMarcar.style.display = mostrarBotao ? "inline-block" : "none";
}

function fecharMensagem() {
  document.getElementById("modalMensagem").style.display = "none";
  if (buscarTimeout) {
    clearTimeout(buscarTimeout);
    buscarTimeout = null;
  }
}
</script>

<div id="modalMensagem" class="modal-custom" style="display: none;">
  <div class="modal-content-custom text-center">
    <h4 id="mensagemTexto">Mensagem</h4>
    <a href="partidas_marcadas.php" id="btn-ir-marcadas" class="btn btn-primary mt-3" style="display: none;">
        Minhas Partidas Marcadas
    </a>
    <button class="btn btn-danger mt-3" onclick="fecharMensagem()">Voltar</button>
  </div>
</div>

<?php if (!empty($mensagem)): ?>
<script>
    window.addEventListener("DOMContentLoaded", () => {
    mostrarMensagem("<?= addslashes($mensagem) ?>", true);
});
</script>
<?php endif; ?>
<div id="modalErro" class="modal-custom" style="display: none;">
  <div class="modal-content-custom text-center">
    <h4 class="text-danger">⚠️ Selecione uma posição antes de marcar!</h4>
    <button class="btn btn-danger mt-3" onclick="fecharModalErro()">Voltar</button>
  </div>
</div>
<script>
function buscarPartidas() {
  mostrarMensagem("🔍 Buscando partidas...");
  buscarTimeout = setTimeout(() => {
    document.querySelector("form").submit();
  }, 1000);
}
</script>
</body>
</html>