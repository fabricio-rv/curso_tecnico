<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    echo "erro_sessao";
    exit;
}

include("conexao.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_partida = $_POST["id_partida"] ?? null;
    $cidade = $_POST["cidade"] ?? "";
    $endereco = $_POST["endereco"] ?? "";
    $estado = $_POST["estado"] ?? "";
    $data = $_POST["data"] ?? "";
    $horario = $_POST["horario"] ?? "";
    $turno = $_POST["turno"] ?? "";
    $posicoes_restantes = $_POST["posicoes"] ?? "";

    if (!$id_partida) {
        echo "erro_id";
        exit;
    }

    $sql = "UPDATE partidas SET cidade = ?, endereco = ?, estado = ?, data = ?, horario = ?, turno = ?, posicoes_restantes = ? WHERE id_partida = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssssssi", $cidade, $endereco, $estado, $data, $horario, $turno, $posicoes_restantes, $id_partida);

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "erro_execucao: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "erro_metodo";
}
?>