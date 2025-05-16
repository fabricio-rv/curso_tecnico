<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
include('conexao.php');

$id_usuario = $_SESSION["usuario"]["id_usuario"];

$sql = "SELECT m.id_marcacao, m.posicao, p.*
        FROM marcacoes m
        JOIN partidas p ON m.id_partida = p.id_partida
        WHERE m.id_usuario = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>IFUT - Partidas Marcadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../CSS/geral.css">
    <style>
        .modal-custom {
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
        }
        .modal-content-custom {
            background-color: #222;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            color: #00ff00;
            box-shadow: 0 0 20px lime;
        }
        .fade-out {
            animation: fadeOut 0.6s forwards;
        }
        @keyframes fadeOut {
            to {
                opacity: 0;
                height: 0;
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container mt-4">
<h2 class="titulo-pagina">Partidas Marcadas</h2>
    <table class="table table-dark table-bordered text-center">
        <thead>
            <tr>
                <th>Partida</th>
                <th>Cidade</th>
                <th>Data</th>
                <th>Turno</th>
                <th>Sua Posição</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="tabelaPartidas">
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <tr id="linha_<?= $row["id_marcacao"] ?>">
                    <td><?= $row["id_partida"] ?></td>
                    <td><?= $row["cidade"] ?></td>
                    <td><?= $row["data"] ?></td>
                    <td><?= $row["turno"] ?></td>
                    <td><strong><?= $row["posicao"] ?></strong></td>
                    <td>
                        <button class="btn-acao-ifut btn-ver-detalhes" onclick="abrirDetalhes(
                            '<?= $row['cidade'] ?>',
                            '<?= $row['endereco'] ?>',
                            '<?= $row['data'] ?>',
                            '<?= $row['horario'] ?>',
                            '<?= $row['turno'] ?>',
                            '<?= $row['posicao'] ?>'
                        )">Ver Detalhes</button>

                        <button class="btn-acao-ifut btn-desmarcar" onclick="abrirModal(<?= $row['id_marcacao'] ?>)">Desmarcar</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal CONFIRMAR DESMARCAR -->
<div id="modalDesmarcar" class="modal-custom">
    <div class="modal-content-custom">
        <h4>❌ Deseja desmarcar esta partida?</h4>
        <form id="formDesmarcar">
            <input type="hidden" name="id_marcacao" id="idMarcacaoModal">
            <div class="mt-3">
                <button type="submit" class="btn btn-success">Sim, desmarcar</button>
                <button type="button" onclick="fecharModal()" class="btn btn-danger">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal DETALHES -->
<div id="modalDetalhes" class="modal-custom">
    <div class="modal-content-custom" id="conteudoDetalhes">
        <!-- Detalhes injetados via JS -->
    </div>
</div>

<script>
function abrirModal(idMarcacao) {
    document.getElementById("idMarcacaoModal").value = idMarcacao;
    document.getElementById("modalDesmarcar").style.display = "flex";
}
function fecharModal() {
    document.getElementById("modalDesmarcar").style.display = "none";
}

document.getElementById("formDesmarcar").addEventListener("submit", async function(e) {
    e.preventDefault();
    const id = document.getElementById("idMarcacaoModal").value;

    const formData = new FormData();
    formData.append("id_marcacao", id);

    const response = await fetch("desmarcar_partida.php", {
        method: "POST",
        body: formData
    });

    if (response.ok) {
        fecharModal();
        const linha = document.getElementById("linha_" + id);
        linha.classList.add("fade-out");
        setTimeout(() => linha.remove(), 600);
    }
});

// Modal de detalhes
function abrirDetalhes(cidade, endereco, data, horario, turno, posicao) {
    const html = `
        <h4>📋 Detalhes da Partida</h4>
        <p><strong>Cidade:</strong> ${cidade}</p>
        <p><strong>Endereço:</strong> ${endereco}</p>
        <p><strong>Data:</strong> ${data}</p>
        <p><strong>Horário:</strong> ${horario}</p>
        <p><strong>Turno:</strong> ${turno}</p>
        <p><strong>Sua posição:</strong> ${posicao}</p>
        <button onclick="fecharDetalhes()" class="btn btn-success mt-2">Fechar</button>
    `;
    document.getElementById("conteudoDetalhes").innerHTML = html;
    document.getElementById("modalDetalhes").style.display = "flex";
}

function fecharDetalhes() {
    document.getElementById("modalDetalhes").style.display = "none";
}
</script>
</body>
</html>