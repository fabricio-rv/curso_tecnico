<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
include('conexao.php');
$id_usuario = $_SESSION["usuario"]["id_usuario"];

$sql = "SELECT * FROM partidas WHERE id_usuario = ? AND data >= CURDATE() ORDER BY data ASC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$partidas = $stmt->get_result();

function gerarStatusPosicoes($id_partida, $posicoes_str, $mysqli) {
    if (empty($posicoes_str)) {
        return "⏳";
    }

    $status = "";
    $posicoes = array_filter(array_map('trim', explode(",", $posicoes_str)));

    if (empty($posicoes)) {
        return "⏳";
    }

    foreach ($posicoes as $posicao) {
        $query = $mysqli->prepare("SELECT COUNT(*) FROM marcacoes WHERE id_partida = ? AND posicao = ?");
        $query->bind_param("is", $id_partida, $posicao);
        $query->execute();
        $query->bind_result($total);
        $query->fetch();
        $query->close();
        $icone = $total > 0 ? "✅" : "⏳";
        $status .= "<span>$posicao $icone</span><br>";
    }

    return $status;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Partidas Criadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/geral.css">
    <style>
        .modal-custom {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            justify-content: center;
            align-items: center;
            background-color: rgba(0,0,0,0.7);
        }
        .modal-content-custom {
            background-color: #111;
            border: 2px solid lime;
            box-shadow: 0 0 20px lime;
            padding: 25px;
            border-radius: 12px;
            color: lime;
            max-height: 90vh;
            overflow-y: auto;
            width: 600px;
        }
        #msg-btn-group {
            display: flex;
            justify-content: center;
            gap: 12px; /* espaçamento sutil entre Confirmar e Voltar */
            margin-top: 10px;
        }
    </style>
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
    <h2 class="titulo-pagina">Partidas Criadas</h2>
    <table class="table table-dark table-bordered text-center">
        <thead>
            <tr>
                <th>Partida</th><th>Cidade</th><th>Endereço</th><th>Data</th><th>Horário</th><th>Turno</th><th>Posições</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($partida = $partidas->fetch_assoc()): ?>
                <tr>
                    <td data-id='<?= $partida["id_partida"] ?>'><?= $partida["id_partida"] ?></td>
                    <td><?= $partida["cidade"] ?></td>
                    <td><?= $partida["endereco"] ?></td>
                    <td><?= $partida["data"] ?></td>
                    <td><?= $partida["horario"] ?></td>
                    <td><?= $partida["turno"] ?></td>
                    <td><?= gerarStatusPosicoes(id_partida: $partida["id_partida"], posicoes_str: $partida["posicoes_restantes"], mysqli: $mysqli) ?></td>
                    <td>
                        <div class="d-flex gap-2 justify-content-center">
                            <button class="btn btn-sm btn-warning"
                            onclick='abrirEditarPartida(<?= json_encode($partida + ["posicoes" => explode(",", $partida["posicoes_restantes"])], JSON_UNESCAPED_UNICODE) ?>)'>
                            Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="abrirModalExcluir(<?= $partida['id_partida'] ?>)">Excluir</button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- MODAL EDITAR -->
<div id="modal-editar" class="modal-custom">
  <div class="modal-content-custom">
    <h4 class="mb-3 text-center">✏️ Editar Partida</h4>
    <form id="form-editar-partida">
      <input type="hidden" name="id_partida" id="id_partida">
      <div class="mb-2"><label>Estado:</label>
        <select name="estado" id="estado" class="form-control">
          <?php foreach (["AC","AL","AP","AM","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR","PE","PI","RJ","RN","RS","RO","RR","SC","SP","SE","TO"] as $uf): ?>
            <option value="<?= $uf ?>"><?= $uf ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-2"><label>Cidade:</label><input type="text" name="cidade" id="cidade" class="form-control" required></div>
      <div class="mb-2"><label>Endereço:</label><input type="text" name="endereco" id="endereco" class="form-control" required></div>
      <div class="mb-2"><label>Data:</label><input type="date" name="data" id="data" class="form-control" required></div>
      <div class="mb-2"><label>Horário:</label><input type="time" name="horario" id="horario" class="form-control" required></div>
      <div class="mb-2">
        <label>Turno:</label>
        <select name="turno" id="turno" class="form-control">
          <option value="MANHÃ">MANHÃ</option><option value="TARDE">TARDE</option><option value="NOITE">NOITE</option>
        </select>
      </div>
      <div class="mb-3">
        <label>Posições Disponíveis:</label><br>
        <?php foreach (["GOL", "ZAG", "ALA D", "ALA E", "VOL", "MEI", "ATA"] as $p): ?>
            <label class='me-2'><input type='checkbox' class='check-posicao' name='posicoes[]' value='<?= $p ?>'> <?= $p ?></label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-success w-100">Salvar Alterações</button>
      <button type="button" onclick="voltarEditarPartida()" class="btn btn-danger w-100 mt-2">Voltar</button>
    </form>
  </div>
</div>

<!-- MODAL DE MENSAGEM -->
<div class="modal-custom" id="modal-msg">
  <div class="modal-content-custom">
    <h5 id="texto-msg"></h5>
    <div id="msg-btn-group">
      <button id="btn-confirmar-msg" class="btn btn-success">Confirmar</button>
      <button id="btn-voltar-msg" class="btn btn-danger">Voltar</button>
    </div>
    <div id="msg-btn-prosseguir" style="display: none;">
      <button id="btn-prosseguir-msg" class="btn btn-success">🚀 Prosseguir</button>
    </div>
  </div>
</div>

<!-- MODAL DE EXCLUSÃO -->
<div id="modal-excluir" class="modal-custom" style="display: none;">
  <div class="modal-content-custom text-center">
    <h5 id="texto-modal-excluir" class="mb-3">Deseja excluir esta partida?</h5>
    <button id="btn-sim-excluir" class="btn btn-success w-100 mb-2">Sim, excluir</button>
    <button onclick="fecharModalExcluir()" class="btn btn-danger w-100">Voltar</button>
  </div>
</div>

<script>
let partidaOriginal = {};

function abrirEditarPartida(dados) {
    partidaOriginal = {
        cidade: dados.cidade,
        endereco: dados.endereco,
        estado: dados.estado,
        data: dados.data,
        horario: dados.horario,
        turno: dados.turno,
        posicoes: dados.posicoes
    };

    document.getElementById("id_partida").value = dados.id_partida;
    document.getElementById("cidade").value = dados.cidade;
    document.getElementById("endereco").value = dados.endereco;
    document.getElementById("estado").value = dados.estado;
    document.getElementById("data").value = dados.data;
    document.getElementById("horario").value = dados.horario;
    document.getElementById("turno").value = dados.turno;

    document.querySelectorAll(".check-posicao").forEach(cb => {
        cb.checked = dados.posicoes.includes(cb.value);
    });

    document.getElementById("modal-editar").style.display = "flex";
}

function voltarEditarPartida() {
    const atual = {
        cidade: document.getElementById("cidade").value,
        endereco: document.getElementById("endereco").value,
        estado: document.getElementById("estado").value,
        data: document.getElementById("data").value,
        horario: document.getElementById("horario").value,
        turno: document.getElementById("turno").value,
        posicoes: Array.from(document.querySelectorAll(".check-posicao"))
            .filter(cb => cb.checked)
            .map(cb => cb.value)
    };

    const houveAlteracao = JSON.stringify(atual) !== JSON.stringify(partidaOriginal);
    const mensagem = houveAlteracao
        ? "⚠️ Deseja voltar sem salvar as alterações?"
        : "⚠️ Deseja voltar sem fazer nenhuma alteração? 👀";

    abrirMensagem(mensagem, false, () => {
        document.getElementById("modal-editar").style.display = "none";
    });
}

document.getElementById("form-editar-partida").addEventListener("submit", async function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const posicoes = Array.from(document.querySelectorAll(".check-posicao")).filter(cb => cb.checked).map(cb => cb.value);
    formData.set("posicoes", posicoes.join(","));

    const atual = {
        cidade: formData.get("cidade"),
        endereco: formData.get("endereco"),
        estado: formData.get("estado"),
        data: formData.get("data"),
        horario: formData.get("horario"),
        turno: formData.get("turno"),
        posicoes
    };

    if (JSON.stringify(atual) === JSON.stringify(partidaOriginal)) {
        abrirMensagem("⚠️ Nenhum dado foi alterado. 🤷", false, null, true);
        return;
    }

    abrirMensagem("Tem certeza que deseja salvar as alterações? 👀💭", false, async () => {
        const res = await fetch("salvar_edicao_partida.php", {
            method: "POST",
            body: formData
        });

        const txt = await res.text();
        if (txt.trim() === "ok") {
            abrirMensagem("✔️ Alterações salvas com sucesso!", true); // success
        } else {
            abrirMensagem("❌ Erro ao salvar.");
        }
    });
});
function fecharModal() {
    document.getElementById("modal-confirmacao").style.display = "none";
}
function abrirModalExcluir(idPartida) {
    // Atualiza o texto da pergunta
    document.getElementById("texto-modal-excluir").innerText = "Deseja excluir esta partida?";

    // Atualiza o botão SIM para redirecionar com o ID correto
    const btnSim = document.getElementById("btn-sim-excluir");
    btnSim.onclick = function () {
        window.location.href = "excluir_partida.php?id_partida=" + idPartida;
    };

    // Exibe o modal
    document.getElementById("modal-excluir").style.display = "flex";
}

function fecharModalExcluir() {
    document.getElementById("modal-excluir").style.display = "none";
}

let idPartidaParaExcluir = null;

function abrirModalExcluir(idPartida) {
    idPartidaParaExcluir = idPartida;
    document.getElementById("modal-excluir").style.display = "flex";
}

document.getElementById("btn-sim-excluir").onclick = async function () {
    if (!idPartidaParaExcluir) return;

    const response = await fetch("excluir_partida.php?id_partida=" + idPartidaParaExcluir);
    const resultado = await response.text();

    if (resultado.trim() === "ok") {
        // Remove a linha suavemente
        const linha = document.querySelector(`td[data-id='${idPartidaParaExcluir}']`)?.parentElement;
        if (linha) {
            linha.style.transition = "opacity 0.5s";
            linha.style.opacity = 0;
            setTimeout(() => linha.remove(), 500);
        }
    }

    document.getElementById("modal-excluir").style.display = "none";
    idPartidaParaExcluir = null;
};
function fecharMensagem() {
    document.getElementById("modal-msg").style.display = "none";
}

function abrirMensagem(texto, fecharDepois = false, callbackConfirmar = null, forcarProsseguir = false) {
    document.getElementById("texto-msg").innerText = texto;
    document.getElementById("modal-msg").style.display = "flex";

    const btnGroup = document.getElementById("msg-btn-group");
    const btnProsseguir = document.getElementById("msg-btn-prosseguir");

    const mostrarSoProsseguir = texto.includes("salvas com sucesso") || forcarProsseguir;

    if (mostrarSoProsseguir) {
        btnGroup.style.display = "none";
        btnProsseguir.style.display = "block";

        document.getElementById("btn-prosseguir-msg").onclick = () => {
            document.getElementById("modal-msg").style.display = "none";
            document.getElementById("modal-editar").style.display = "none";
            location.href = "partidas_criadas.php";
        };
    } else {
        btnGroup.style.display = "flex";
        btnProsseguir.style.display = "none";

        document.getElementById("btn-confirmar-msg").onclick = () => {
            document.getElementById("modal-msg").style.display = "none";
            if (fecharDepois) {
                document.getElementById("modal-editar").style.display = "none";
                location.href = "partidas_criadas.php";
            }
            if (typeof callbackConfirmar === "function") callbackConfirmar();
        };

        document.getElementById("btn-voltar-msg").onclick = () => {
            document.getElementById("modal-msg").style.display = "none";
        };
    }
}
</script>
</body>
</html>